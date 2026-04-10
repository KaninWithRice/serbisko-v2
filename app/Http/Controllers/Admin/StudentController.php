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
        // Fetch the Global Active Year from your existing SystemSetting model
        $settings = \App\Models\SystemSetting::first();
        $activeSY = $settings ? $settings->active_school_year : '2025-2026';

        // Allow manual override via request (for viewing old archives)
        $selectedYear = $request->get('school_year', $activeSY);

        // Subquery to get only the LATEST version per student
        $latestPreEnrollments = DB::table('pre_enrollments')
            ->select('student_id', DB::raw('MAX(submission_version) as max_version'))
            ->groupBy('student_id');

        $query = DB::table('users')
            ->leftJoin('students', 'users.id', '=', 'students.user_id')
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $selectedYear) 
            ->where('users.role', 'student')

        // 1.  the subquery first to find the max version
        ->leftJoinSub($latestPreEnrollments, 'latest_version_map', function ($join) {
            $join->on('students.id', '=', 'latest_version_map.student_id');
        })
        // 2. the actual table matching both student_id AND that max version
        ->leftJoin('pre_enrollments', function ($join) {
            $join->on('students.id', '=', 'pre_enrollments.student_id')
                 ->on('pre_enrollments.submission_version', '=', 'latest_version_map.max_version');
        })
        
        ->leftJoin('kiosk_enrollments', 'users.id', '=', 'kiosk_enrollments.id')
        ->select(
            'students.id',
            'users.first_name', 'users.last_name', 'users.middle_name',
            'users.created_at', 'users.id as user_primary_id',
            'students.lrn', 'users.extension_name',
            'pre_enrollments.responses', 
            'kiosk_enrollments.grade_level as kiosk_grade',
            'kiosk_enrollments.track as kiosk_track',
            'kiosk_enrollments.cluster as kiosk_cluster',
            'kiosk_enrollments.academic_status as kiosk_status',
            'kiosk_enrollments.sf9_status',
            'kiosk_enrollments.psa_status',
            'kiosk_enrollments.enroll_form_status',
            'kiosk_enrollments.als_cert_status',
            'kiosk_enrollments.affidavit_status',
            'kiosk_enrollments.good_moral_status',
            'kiosk_enrollments.sf10_status'
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

        if ($request->filled('requirements_status')) {
            $status = $request->requirements_status;
            $query->where(function($q) use ($status) {
                $cols = ['sf9_status', 'psa_status', 'enroll_form_status', 'als_cert_status', 'affidavit_status', 'good_moral_status', 'sf10_status'];
                if ($status === 'Complete') {
                    foreach ($cols as $col) {
                        $q->orWhere("kiosk_enrollments.$col", 'verified');
                    }
                } else {
                    foreach ($cols as $col) {
                        $q->where("kiosk_enrollments.$col", '!=', 'verified');
                    }
                }
            });
        }

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

            $student->display_grade   = $student->kiosk_grade   ?? ($details['Grade Level to Enroll'] ?? '—');
            $student->display_track   = $student->kiosk_track   ?? ($details['Track'] ?? '—');
            $student->display_status  = ($details['Academic Status'] ?? null) ?? ($student->kiosk_status ?? '—');
            $student->display_cluster = $student->kiosk_cluster ?? ($acronyms[$jsonCluster] ?? $jsonCluster);

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

            // Requirement Status Logic
            $verifiedCount = 0;
            $cols = ['sf9_status', 'psa_status', 'enroll_form_status', 'als_cert_status', 'affidavit_status', 'good_moral_status', 'sf10_status'];
            foreach ($cols as $col) {
                if ($student->$col === 'verified') $verifiedCount++;
            }
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
        // 1. Get latest version first
        $latestVersion = DB::table('pre_enrollments')
            ->where('student_id', $id)
            ->max('submission_version');

        // 2. Fetch the student - Using students.* to avoid "Unknown Column" crashes if you add more later
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('pre_enrollments', function($join) use ($latestVersion) {
                $join->on('students.id', '=', 'pre_enrollments.student_id')
                    ->where('pre_enrollments.submission_version', '=', $latestVersion);
            })
            ->leftJoin('kiosk_enrollments', 'users.id', '=', 'kiosk_enrollments.id') 
            ->leftJoin('users as editors', 'students.manually_edited_by', '=', 'editors.id') 
            ->select([
                'students.id', 
                'students.*', // This ensures all address/parental columns are included
                'users.first_name', 'users.last_name', 'users.extension_name', 'users.middle_name', 'users.birthday', 
                'pre_enrollments.responses',
                'kiosk_enrollments.grade_level as kiosk_grade', 
                'kiosk_enrollments.track as kiosk_track',
                'kiosk_enrollments.cluster as kiosk_cluster', 
                'kiosk_enrollments.academic_status as kiosk_status',
                'editors.first_name as editor_name',
                'editors.last_name as editor_lastname'
            ])
            ->where('students.id', $id)
            ->whereNull('users.deleted_at')
            ->first();

        if (!$student) abort(404);

        // 3. JSON Parsing & Key Normalization
        $rawDetails = json_decode($student->responses, true) ?: [];
        $details = [];
        foreach ($rawDetails as $key => $value) {
            $details[strtolower(trim($key))] = $value;
        }

        // 4. Mapping Logic (Using the keys you provided)
        // This looks for any of your aliases in the JSON responses
        $getMappedValue = function($aliases) use ($details) {
            foreach ($aliases as $alias) {
                if (isset($details[strtolower($alias)])) {
                    return $details[strtolower($alias)];
                }
            }
            return null;
        };

        // 5. Dynamic Assignment using your mapping
        $finalGrade   = $student->kiosk_grade   ?? ($getMappedValue(['Grade Level to Enroll', 'grade']) ?? '—');
        $finalTrack   = $student->kiosk_track   ?? ($getMappedValue(['Track']) ?? '—');
        $finalStatus  = $student->kiosk_status  ?? ($getMappedValue(['Academic Status']) ?? '—');
        
        // Dynamic Cluster check
        $finalCluster = $student->kiosk_cluster 
                    ?? $getMappedValue(['Cluster of Electives', 'cluster', 'elective cluster']) 
                    ?? '—';

        // 6. Filter out the core keys for the "Dynamic Details" section
        // This prevents showing info twice (once in the header, once in the list)
        $fixedKeys = [
            'first name', 'given name', 'fname', 'pangalan', 'last name', 'surname', 'family name', 'apelyido',
            'middle name', 'mname', 'extension name', 'ext', 'suffix', 'birthday', 'date of birth', 'dob',
            'grade level to enroll', 'track', 'cluster of electives', 'academic status', 'timestamp'
        ];

        $dynamicDetails = [];
        foreach ($details as $key => $value) {
            if (!in_array($key, $fixedKeys)) {
                // Capitalize keys for better UI display (e.g., "mother_tongue" -> "Mother Tongue")
                $displayKey = ucwords(str_replace(['_', '-'], ' ', $key));
                $dynamicDetails[$displayKey] = $value;
            }
        }

        // 7. Fetch Scanned Documents from kiosk_enrollments
        $verifiedScans = [];
        $docTypes = [
            'sf9' => 'Report Card (SF9)',
            'psa' => 'Birth Certificate',
            'enroll_form' => 'Enrollment Form',
            'als_cert' => 'ALS Certificate',
            'affidavit' => 'Affidavit',
            'good_moral' => 'Good Moral Certificate',
            'sf10' => 'Form 137 / SF10'
        ];

        $enrollment = DB::table('kiosk_enrollments')->where('id', $student->user_id)->first();
        if ($enrollment) {
            foreach ($docTypes as $prefix => $label) {
                $statusCol = "{$prefix}_status";
                $pathCol = "{$prefix}_path";
                if (isset($enrollment->$statusCol) && $enrollment->$statusCol === 'verified' && !empty($enrollment->$pathCol)) {
                    $verifiedScans[] = (object)[
                        'document_type' => $label,
                        'file_path' => $enrollment->$pathCol,
                        'created_at' => $enrollment->updated_at
                    ];
                }
            }
        }

        return view('admin.studentpage.profilepage', compact(
            'student', 'details', 'dynamicDetails', 
            'finalGrade', 'finalTrack', 'finalCluster', 'finalStatus',
            'verifiedScans'
        ));
    }

    // 4. STUDENT PROFILE UPDATE LOGIC
    public function updateStudentProfile(Request $request, $id)
    {
        // 1. Basic Validation
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $student = DB::table('students')->where('id', $id)->first();
            if (!$student) throw new \Exception("Student record not found.");

            // 2. Update Master Identity (Users Table)
            DB::table('users')->where('id', $student->user_id)->update([
                'first_name'     => trim($request->first_name),
                'last_name'      => trim($request->last_name),
                'middle_name'    => trim($request->middle_name),
                'extension_name' => trim($request->extension_name),
                'birthday'       => $request->birthday,
                'updated_at'     => now(),
            ]);

            // 3. Prepare Student Data - GET EVERYTHING FROM THE REQUEST
            // This ensures fields like father_contact_number and address fields are captured
            $studentFields = $request->only([
                'lrn', 'sex', 'place_of_birth', 'mother_tongue',
                'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city', 'curr_province', 'curr_zip_code',
                'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city', 'perm_province', 'perm_zip_code',
                'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
                'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
                'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number'
            ]);

            // Add the lock and timestamp
            $studentFields['is_manually_edited'] = 1;
            $studentFields['manually_edited_by'] = Auth::id();
            $studentFields['updated_at'] = now();

            // 4. Update the Students Table
            DB::table('students')->where('id', $id)->update($studentFields);

            // 5. Update JSON Responses (Extra Fields)
            if ($request->has('responses')) {
                $preEnrollment = DB::table('pre_enrollments')
                    ->where('student_id', $id)
                    ->orderBy('submission_version', 'desc')
                    ->first();

                if ($preEnrollment) {
                    $currentResponses = json_decode($preEnrollment->responses, true) ?? [];
                    // Merge new inputs into existing JSON
                    $updatedResponses = array_merge($currentResponses, $request->responses);

                    DB::table('pre_enrollments')
                        ->where('id', $preEnrollment->id)
                        ->update([
                            'responses'  => json_encode($updatedResponses),
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Profile updated and locked from Google Sync.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteStudent($id)
    {
        DB::beginTransaction();
        try {
            $student = DB::table('students')->where('id', $id)->first();
            if (!$student) return back()->with('error', 'Student not found.');

            // 1. Mark who deleted it in the students table
            DB::table('students')->where('id', $id)->update([
                'deleted_by' => Auth::id(),
                'updated_at' => now()
            ]);

            // 2. Soft delete the user record
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