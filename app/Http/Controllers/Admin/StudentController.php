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
        // Fetch the Global Active Year from the latest CustomForm model
        $settings = \App\Models\CustomForm::latest()->first();
        $activeSY = $settings ? $settings->school_year : '2025-2026';

        // Allow manual override via request (for viewing old archives)
        $selectedYear = $request->get('school_year', $activeSY);

        $query = DB::table('users')
            ->join('students', 'users.id', '=', 'students.user_id')
            ->whereNull('users.deleted_at')
            ->where('users.role', 'student')
            ->leftJoin('pre_enrollments', 'students.id', '=', 'pre_enrollments.student_id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id')
            ->select(
                'students.lrn as id', // ALIAS LRN AS ID FOR BLADE VIEW COMPATIBILITY
                'students.lrn', 
                'users.first_name', 'users.last_name', 'users.middle_name',
                'users.created_at', 'users.id as user_primary_id',
                'users.extension_name',
                'pre_enrollments.responses', 
                'kiosk_enrollments.grade_level as kiosk_grade',
                'kiosk_enrollments.track as kiosk_track',
                'kiosk_enrollments.cluster as kiosk_cluster',
                'kiosk_enrollments.academic_status as kiosk_status'
            );

        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            $query->where(function($q) use ($searchTerm) {
                $q->where('users.first_name', 'like', "%{$searchTerm}%")
                ->orWhere('users.last_name', 'like', "%{$searchTerm}%")
                ->orWhere('users.middle_name', 'like', "%{$searchTerm}%")
                ->orWhere('students.lrn', 'like', "%{$searchTerm}%");
            });
        }

        $filters = [
            'student_type' => ['kiosk' => 'kiosk_enrollments.academic_status', 'json' => 'Academic Status'],
            'grade_level'  => ['kiosk' => 'kiosk_enrollments.grade_level',     'json' => 'Grade Level to Enroll'],
            'track'        => ['kiosk' => 'kiosk_enrollments.track',           'json' => 'Track'],
            'cluster'      => ['kiosk' => 'kiosk_enrollments.cluster',         'json' => 'Cluster of Electives']
        ];

        $fullClusterNames = [
            'STEM'    => 'Science, Technology, Engineering, and Mathematics (STEM)',
            'BE'      => 'Business and Entrepreneurship (BE)',
            'ASSH'    => 'Arts, Social Sciences, and Humanities (ASSH)',
            'TechPro' => 'Technical-Vocational-Livelihood (TVL)'
        ];

        foreach ($filters as $requestKey => $keys) {
            if ($request->filled($requestKey)) {
                $val = $request->$requestKey;
                $query->where(function($q) use ($keys, $val, $requestKey, $fullClusterNames) {
                    $q->where($keys['kiosk'], '=', $val);
                    $q->orWhere(function($sq) use ($keys, $val, $requestKey, $fullClusterNames) {
                        $searchString = ($requestKey === 'cluster' && isset($fullClusterNames[$val])) 
                            ? $fullClusterNames[$val] 
                            : $val;

                        $sq->whereNull($keys['kiosk'])
                        ->where('pre_enrollments.responses', 'like', '%"' . $keys['json'] . '":"' . $searchString . '"%');
                    });
                });
            }
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'Registered') {
                $query->whereNull('kiosk_enrollments.grade_level');
            } elseif ($status === 'Document Verified') {
                $query->whereNotNull('kiosk_enrollments.grade_level');
            } elseif ($status === 'Officially Enrolled') {
                $query->where('kiosk_enrollments.academic_status', 'Officially Enrolled');
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
            $raw = json_decode($student->responses, true) ?? [];
            $details = [];
            // This helps catch keys even with accidental leading/trailing spaces
            foreach ($raw as $key => $value) {
                $details[trim($key)] = $value;
            }

            // Check multiple possible key names from Google Forms
            $jsonCluster = $details['Cluster of Electives'] 
                        ?? $details['Cluster'] 
                        ?? $details['Elective Cluster'] 
                        ?? '—';

            $acronyms = [
                'Science, Technology, Engineering, and Mathematics (STEM)' => 'STEM',
                'Business and Entrepreneurship (BE)' => 'BE',
                'Arts, Social Sciences, and Humanities (ASSH)' => 'ASSH',
                'Technical-Vocational-Livelihood (TVL)' => 'TechPro',
                'STEM' => 'STEM', // Add short versions just in case
                'BE' => 'BE',
                'ASSH' => 'ASSH',
                'TVL' => 'TechPro'
            ];

            // Helper to ensure we get a string even if the value is an array
            $asString = function($val) {
                return is_array($val) ? implode(', ', $val) : $val;
            };

            $student->display_grade   = $asString($student->kiosk_grade   ?? ($details['Grade Level to Enroll'] ?? '—'));
            $student->display_track   = $asString($student->kiosk_track   ?? ($details['Track'] ?? '—'));
            $student->display_status  = $asString(($details['Academic Status'] ?? null) ?? ($student->kiosk_status ?? '—'));
            $student->display_cluster = $asString($student->kiosk_cluster ?? ($acronyms[$asString($jsonCluster)] ?? $jsonCluster));

            if ($student->kiosk_status === 'Officially Enrolled') {
                $student->enrollment_category = 'Officially Enrolled';
                $student->status_style = 'bg-[#003918] text-white border-green-900';
            } elseif (!empty($student->kiosk_grade)) {
                $student->enrollment_category = 'Document Verified';
                $student->status_style = 'bg-[#00923F] text-white border-green-200';
            } else {
                $student->enrollment_category = 'Registered';
                $student->status_style = 'bg-[#048F81] text-white border-[#048F81]';
            }

            // Check verified scans for this student (using lrn or user_id)
            $verifiedCount = DB::table('scans')
                ->where(function($q) use ($student) {
                    $q->where('lrn', $student->lrn)
                      ->orWhere('user_id', $student->user_primary_id);
                })
                ->where('status', 'verified')
                ->count();

            $student->requirement_display = $verifiedCount > 0 ? 'Verified' : 'Pending';
            $student->requirement_style = $verifiedCount > 0 ? 'text-green-600 font-bold' : 'text-gray-600';

            return $student;
        });

        if ($request->ajax()) return view('admin.studentpage.partials.student-table', compact('students'))->render();
        return view('admin.studentpage.students', compact('students', 'selectedYear', 'activeSY'));
    }

    // 3. STUDENT PROFILE LOGIC
    public function profilepage($id)
    {
        // 1. Fetch the student - Using lrn as the identifier ($id)
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('pre_enrollments', 'students.id', '=', 'pre_enrollments.student_id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id') 
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->select([
                'students.lrn as id', // Alias for consistency
                'students.*', 
                'sections.name as section_name',
                'users.first_name', 'users.last_name', 'users.extension_name', 'users.middle_name', 'users.birthday', 
                'pre_enrollments.responses',
                'kiosk_enrollments.grade_level as kiosk_grade', 
                'kiosk_enrollments.track as kiosk_track',
                'kiosk_enrollments.cluster as kiosk_cluster', 
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

        // 3. Mapping Logic
        $getMappedValue = function($aliases) use ($details) {
            foreach ($aliases as $alias) {
                if (isset($details[strtolower($alias)])) {
                    return $details[strtolower($alias)];
                }
            }
            return null;
        };

        $finalGrade   = $student->kiosk_grade   ?? ($getMappedValue(['Grade Level to Enroll', 'grade']) ?? '—');
        $finalTrack   = $student->kiosk_track   ?? ($getMappedValue(['Track']) ?? '—');
        $finalStatus  = $student->kiosk_status  ?? ($getMappedValue(['Academic Status']) ?? '—');
        $finalCluster = $student->kiosk_cluster ?? ($getMappedValue(['Cluster of Electives', 'cluster', 'elective cluster']) ?? '—');

        $fixedKeys = [
            'first name', 'given name', 'fname', 'pangalan', 'last name', 'surname', 'family name', 'apelyido',
            'middle name', 'mname', 'extension name', 'ext', 'suffix', 'birthday', 'date of birth', 'dob',
            'grade level to enroll', 'track', 'cluster of electives', 'academic status', 'timestamp'
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
        $settings = \App\Models\CustomForm::latest()->first();
        $activeSY = $settings ? $settings->school_year : '2025-2026';
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