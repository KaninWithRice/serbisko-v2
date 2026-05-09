<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function students(Request $request)
    {
        // Fetch the Global Active Year from the central source
        $activeSY = \App\Models\Student::activeYear();

        // Allow manual override via request (for viewing old archives)
        $selectedYear = $request->get('school_year', $activeSY);

        $latestPre = \App\Models\Student::latestPreEnrollmentIds();

        $query = DB::table('users')
            ->join('students', function($join) use ($selectedYear) {
                $join->on('users.id', '=', 'students.user_id')
                     ->where('students.school_year', '=', $selectedYear);
            })
            ->whereNull('users.deleted_at')
            ->where('users.role', 'student')
            ->leftJoinSub($latestPre, 'lp', 'students.id', '=', 'lp.student_id')
            ->leftJoin('pre_enrollments', 'lp.latest_id', '=', 'pre_enrollments.id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id')
            ->select(
                'students.lrn as id', // ALIAS LRN AS ID FOR BLADE VIEW COMPATIBILITY
                'students.lrn', 
                'users.first_name', 'users.last_name', 'users.middle_name',
                'users.created_at', 'users.id as user_primary_id',
                'users.extension_name',
                'pre_enrollments.responses', 
                // DATA FALLBACK LOGIC: 1. Kiosk Table -> 2. Pre-Enrollment JSON
                DB::raw("COALESCE(kiosk_enrollments.grade_level, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.grade_level'))) as display_grade"),
                DB::raw("COALESCE(kiosk_enrollments.track, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.track'))) as display_track"),
                DB::raw("COALESCE(kiosk_enrollments.cluster, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.cluster'))) as display_cluster"),
                DB::raw("COALESCE(kiosk_enrollments.academic_status, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.academic_status'))) as display_status"),
                'kiosk_enrollments.academic_status as kiosk_status',
                'kiosk_enrollments.grade_level as kiosk_grade',
                'kiosk_enrollments.receipt_number'
            );

        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            $query->where(function($q) use ($searchTerm) {
                $q->where('users.first_name', 'like', "%{$searchTerm}%")
                ->orWhere('users.last_name', 'like', "%{$searchTerm}%")
                ->orWhere('users.middle_name', 'like', "%{$searchTerm}%")
                ->orWhere('students.lrn', 'like', "%{$searchTerm}%")
                ->orWhere('kiosk_enrollments.receipt_number', 'like', "%{$searchTerm}%");
            });
        }

        $filters = [
            'student_type' => ['kiosk' => 'kiosk_enrollments.academic_status', 'json' => 'academic_status'],
            'grade_level'  => ['kiosk' => 'kiosk_enrollments.grade_level',     'json' => 'grade_level'],
            'track'        => ['kiosk' => 'kiosk_enrollments.track',           'json' => 'track'],
            'cluster'      => ['kiosk' => 'kiosk_enrollments.cluster',         'json' => 'cluster']
        ];

        foreach ($filters as $requestKey => $keys) {
            if ($request->filled($requestKey)) {
                $val = $request->$requestKey;
                $query->where(function($q) use ($keys, $val) {
                    $q->where($keys['kiosk'], '=', $val);
                    $q->orWhere(function($sq) use ($keys, $val) {
                        $sq->whereNull($keys['kiosk'])
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.{$keys['json']}')) LIKE ?", ["%{$val}%"]);
                    });
                });
            }
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'Registered') {
                $query->whereNull('kiosk_enrollments.grade_level')
                      ->whereNull(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.grade_level'))"));
            } elseif ($status === 'Partial Compliance') {
                $query->where(function($q) {
                    $q->whereNotNull('kiosk_enrollments.grade_level')
                      ->orWhereNotNull(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.grade_level'))"));
                })->where('kiosk_enrollments.academic_status', '!=', 'Enrolled');
            } elseif ($status === 'Enrolled') {
                $query->where('kiosk_enrollments.academic_status', 'Enrolled');
            }
        }

        // Requirements status filter removed because columns don't exist in kiosk_enrollments
        // It should be refactored to use the scans table if needed.

        switch ($request->get('sort')) {
            case 'za': $query->orderBy('users.last_name', 'desc'); break;
            case 'newest': $query->orderBy('users.created_at', 'desc'); break;
            case 'oldest': $query->orderBy('users.created_at', 'asc'); break;
            default: $query->orderBy('users.last_name', 'asc'); break;
        }

        $students = $query->get()->map(function($student) {
            $acronyms = [
                'STEM' => 'STEM',
                'BE'   => 'BE',
                'ASSH' => 'ASSH',
                'TVL'  => 'TechPro',
                'CSS'  => 'CSS',
                'VGD'  => 'VGD',
                'EIM'  => 'EIM',
                'EPAS' => 'EPAS'
            ];

            // Helper to ensure we get a string even if the value is an array
            $asString = function($val) {
                return is_array($val) ? implode(', ', $val) : (string) ($val ?? '—');
            };

            $student->display_grade   = $asString($student->display_grade);
            $student->display_track   = $asString($student->display_track);
            $student->display_status  = $asString($student->display_status);
            
            // Extract abbreviation from cluster string if it's from JSON
            $rawCluster = $asString($student->display_cluster);
            $mappedCluster = '—';
            if ($rawCluster !== '—') {
                $mappedCluster = $rawCluster;
                foreach ($acronyms as $key => $target) {
                    if (str_contains($rawCluster, $key)) {
                        $mappedCluster = $target;
                        break;
                    }
                }
            }
            $student->display_cluster = $mappedCluster;

            // Check verified scans for this student (using lrn or user_id)
            $verifiedCount = DB::table('scans')
                ->where(function($q) use ($student) {
                    $q->where('lrn', $student->lrn)
                      ->orWhere('user_id', $student->user_primary_id);
                })
                ->where('status', 'verified')
                ->count();

            // Determine Required Docs Count
            $academicStatus = strtolower($student->display_status);
            $requiredDocsCount = 3; // Regular default
            if (str_contains($academicStatus, 'als')) {
                $requiredDocsCount = 3;
            } elseif (str_contains($academicStatus, 'feree') || str_contains($academicStatus, 'balik')) {
                $requiredDocsCount = 3;
            }

            // Requirement Status Alignment
            if ($verifiedCount === 0) {
                $student->requirement_display = 'Pending';
                $student->requirement_style = 'text-gray-500 font-medium';
            } elseif ($verifiedCount < $requiredDocsCount) {
                $student->requirement_display = 'Incomplete';
                $student->requirement_style = 'text-amber-600 font-bold';
            } else {
                $student->requirement_display = 'Complete';
                $student->requirement_style = 'text-green-600 font-bold';
            }

            // Student Status Alignment
            if ($student->kiosk_status === 'Enrolled' || $student->kiosk_status === 'Officially Enrolled') {
                $student->enrollment_category = 'Enrolled';
                $student->status_style = 'bg-[#003918] text-white border-green-900';
            } elseif ($student->requirement_display === 'Complete') {
                $student->enrollment_category = 'For Enrollment';
                $student->status_style = 'bg-[#005288] text-white border-blue-900';
            } elseif ($student->kiosk_grade !== null) {
                $student->enrollment_category = 'Partial Compliance';
                $student->status_style = 'bg-[#00923F] text-white border-green-200';
            } else {
                $student->enrollment_category = 'Registered';
                $student->status_style = 'bg-[#048F81] text-white border-[#048F81]';
            }

            return $student;
        });

        if ($request->ajax()) return view('admin.studentpage.partials.student-table', compact('students'))->render();
        return view('admin.studentpage.students', compact('students', 'selectedYear', 'activeSY'));
    }

    // 3. STUDENT PROFILE LOGIC
    public function profilepage($id)
    {
        $activeSY = \App\Models\Student::activeYear();
        $latestPre = \App\Models\Student::latestPreEnrollmentIds();

        // 1. Fetch the student - Using lrn as the identifier ($id)
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.school_year', $activeSY)
            ->leftJoinSub($latestPre, 'lp', 'students.id', '=', 'lp.student_id')
            ->leftJoin('pre_enrollments', 'lp.latest_id', '=', 'pre_enrollments.id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id') 
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->select([
                'students.lrn as id', // Alias for consistency
                'students.*', 
                'sections.name as section_name',
                'users.first_name', 'users.last_name', 'users.extension_name', 'users.middle_name', 'users.birthday', 
                'pre_enrollments.responses',
                // FALLBACK LOGIC
                DB::raw("COALESCE(kiosk_enrollments.grade_level, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.grade_level'))) as final_grade"),
                DB::raw("COALESCE(kiosk_enrollments.track, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.track'))) as final_track"),
                DB::raw("COALESCE(kiosk_enrollments.cluster, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.cluster'))) as final_cluster"),
                DB::raw("COALESCE(kiosk_enrollments.academic_status, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.academic_status'))) as final_status"),
                'kiosk_enrollments.academic_status as kiosk_status'
            ])
            ->where('students.lrn', $id)
            ->whereNull('users.deleted_at')
            ->first();

        if (!$student) abort(404);

        // 2. JSON Parsing & Key Normalization
        $rawDetails = json_decode($student->responses, true) ?: [];
        $details = [];
        foreach ($rawDetails as $key => $value) {
            $details[strtolower(trim($key))] = $value;
        }

        $finalGrade   = $student->final_grade   ?? '—';
        $finalTrack   = $student->final_track   ?? '—';
        $finalStatus  = $student->final_status  ?? '—';
        $finalCluster = $student->final_cluster ?? '—';

        $fixedKeys = [
            'first name', 'given name', 'fname', 'pangalan', 'last name', 'surname', 'family name', 'apelyido',
            'middle name', 'mname', 'extension name', 'ext', 'suffix', 'birthday', 'date of birth', 'dob',
            'grade_level', 'track', 'cluster', 'academic_status', 'timestamp'
        ];

        $dynamicDetails = [];
        foreach ($details as $key => $value) {
            if (!in_array($key, $fixedKeys)) {
                $displayKey = ucwords(str_replace(['_', '-'], ' ', $key));
                $dynamicDetails[$displayKey] = $value;
            }
        }

        // 4. Fetch Scanned Documents from scans table - Deduplicated by latest document type
        $verifiedScans = DB::table('scans')
            ->whereIn('id', function($query) use ($student) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('scans')
                    ->where(function($q) use ($student) {
                        $q->where('lrn', $student->lrn)
                          ->orWhere('user_id', $student->user_id);
                    })
                    ->where('status', 'verified')
                    ->groupBy('document_type');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate if all documents are verified
        $status = strtolower($finalStatus ?? 'regular');
        $requiredDocsCount = 3; // Default for regular
        if (str_contains($status, 'als')) {
            $requiredDocsCount = 4;
        } elseif (str_contains($status, 'feree') || str_contains($status, 'balik')) {
            $requiredDocsCount = 4;
        }
        $isAllVerified = $verifiedScans->count() >= $requiredDocsCount;

        // 5. Fetch Sections data for the profile
        $academicYears = \App\Models\Section::distinct()->pluck('academic_year')->toArray();
        $activeSY = \App\Models\Student::activeYear();
        if (!in_array($activeSY, $academicYears)) {
            $academicYears[] = $activeSY;
        }

        sort($academicYears);

        return view('admin.studentpage.profilepage', compact(
            'student', 'details', 'dynamicDetails', 
            'finalGrade', 'finalTrack', 'finalCluster', 'finalStatus',
            'verifiedScans', 'academicYears', 'activeSY', 'isAllVerified'
        ));
    }

    // 4. STUDENT PROFILE UPDATE LOGIC
    public function updateStudentProfile(Request $request, $id)
    {
        // Log the incoming request for debugging
        \Illuminate\Support\Facades\Log::info("Updating Profile for User ID: " . $id, $request->all());

        // 1. Basic Validation
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Find student by user_id
            $student = DB::table('students')->where('user_id', $id)->first();
            
            if (!$student) {
                // Fallback: Try finding by LRN if user_id search fails
                $student = DB::table('students')->where('lrn', $id)->first();
            }

            if (!$student) throw new \Exception("Student record not found for Identifier: " . $id);

            // 2. Update Master Identity (Users Table)
            DB::table('users')->where('id', $student->user_id)->update([
                'first_name'     => trim($request->first_name),
                'last_name'      => trim($request->last_name),
                'middle_name'    => trim($request->middle_name),
                'extension_name' => trim($request->extension_name),
                'birthday'       => $request->birthday,
                'updated_at'     => now(),
            ]);

            // 3. Prepare Student Data
            $studentFields = $request->only([
                'sex', 'place_of_birth', 'mother_tongue',
                'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city', 'curr_province', 'curr_zip_code',
                'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city', 'perm_province', 'perm_zip_code',
                'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
                'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
                'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number',
                'grade_level', 'section_id', 'school_year'
            ]);

            // Filter out null values except for section_id
            $studentFields = array_filter($studentFields, function($value, $key) {
                return $key === 'section_id' || !is_null($value);
            }, ARRAY_FILTER_USE_BOTH);

            // Clean up section_id if empty string
            if (isset($studentFields['section_id']) && $studentFields['section_id'] === "") {
                $studentFields['section_id'] = null;
            }

            $studentFields['is_manually_edited'] = 1;
            $studentFields['updated_at'] = now();

            // 4. Update the Students Table
            $updated = DB::table('students')->where('id', $student->id)->update($studentFields);
            \Illuminate\Support\Facades\Log::info("Student table updated: " . ($updated ? 'Yes' : 'No/No changes'));

            // 5. Update JSON Responses (Extra Fields)
            if ($request->has('responses')) {
                $preEnrollment = DB::table('pre_enrollments')
                    ->where('student_id', $student->id)
                    ->latest()
                    ->first();

                if ($preEnrollment) {
                    $currentResponses = json_decode($preEnrollment->responses, true) ?? [];
                    $newResponses = is_array($request->responses) ? $request->responses : [];
                    $updatedResponses = array_merge($currentResponses, $newResponses);

                    DB::table('pre_enrollments')
                        ->where('id', $preEnrollment->id)
                        ->update([
                            'responses'  => json_encode($updatedResponses),
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Update failed: " . $e->getMessage());
            return back()->with('error', 'Update failed: ' . $e->getMessage())->withInput();
        }
    }

    public function deleteStudent($id)
    {
        DB::beginTransaction();
        try {
            $student = DB::table('students')->where('lrn', $id)->first();
            if (!$student) return back()->with('error', 'Student not found.');

            // Mark deletion in students table (no id column, so we use lrn)
            DB::table('students')->where('lrn', $id)->update([
                'deleted_by' => Auth::id(),
                'updated_at' => now()
            ]);

            // Soft delete user
            DB::table('users')->where('id', $student->user_id)->update([
                'deleted_at' => now()
            ]);

            DB::commit();
            return redirect()->route('admin.students')->with('success', 'Student archived.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}