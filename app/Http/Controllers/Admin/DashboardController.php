<?php

namespace App\Http\Controllers\Admin; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request) 
    {
        $activeSY = \App\Models\Student::activeYear();
        $grade = $request->grade_level;

        // Reusable filter
        $applyFilter = function($query) use ($grade) {
            if (!empty($grade)) {
                $query->where(function($q) use ($grade) {
                    // FALLBACK LOGIC: 1. Kiosk -> 2. Pre-Enrollment JSON
                    $q->where('kiosk_enrollments.grade_level', '=', $grade)
                    ->orWhere(function($sq) use ($grade) {
                        $sq->whereNull('kiosk_enrollments.grade_level')
                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.grade_level')) = ?", [$grade]);
                    });
                });
            }
            return $query;
        };

        // --- Core Enrollment Stats ---
        // Joined using subquery to get ONLY the latest pre_enrollment version
        $latestPre = \App\Models\Student::latestPreEnrollmentIds();

        $baseQuery = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id') 
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $activeSY)
            ->leftJoinSub($latestPre, 'lp', 'students.id', '=', 'lp.student_id')
            ->leftJoin('pre_enrollments', 'lp.latest_id', '=', 'pre_enrollments.id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id');

        $totalRegistrations = $applyFilter(clone $baseQuery)->count('students.id');

        $totalSubmissions = $applyFilter(clone $baseQuery)
            ->whereNotNull('kiosk_enrollments.student_id')
            ->count('students.lrn');

        $totalEnrolled = $applyFilter(clone $baseQuery)
            ->whereIn('kiosk_enrollments.academic_status', ['Officially Enrolled', 'Enrolled'])
            ->count('students.lrn');

        // --- Stats Calculation ---
        $max = $totalRegistrations > 0 ? $totalRegistrations : 1;
        $percVerified = ($totalSubmissions / $max) * 100;
        $percEnrolled = ($totalEnrolled / $max) * 100;

        // --- Elective Counting ---
        $categories = [
            'ASSH' => 'Arts, Social Sciences & Humanities',
            'BE'   => 'Business & Entrepreneurship',
            'STEM' => 'Science, Technology, Engineering & Math',
            'CSS'  => 'Computer System Servicing',
            'VGD'  => 'Visual Graphics & Design',
            'EIM'  => 'Electrical Installation & Maintenance',
            'EPAS' => 'Electronics Product Assembly & Servicing'
        ];

        // Combined data source for counting with fallback
        $rawCountsQuery = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $activeSY)
            ->leftJoinSub($latestPre, 'lp', 'students.id', '=', 'lp.student_id')
            ->leftJoin('pre_enrollments', 'lp.latest_id', '=', 'pre_enrollments.id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id')
            ->select(
                DB::raw("COALESCE(kiosk_enrollments.cluster, JSON_UNQUOTE(JSON_EXTRACT(pre_enrollments.responses, '$.cluster'))) as cluster_val")
            );

        if (!empty($grade)) {
            $rawCountsQuery = $applyFilter($rawCountsQuery);
        }

        $rawCountsResults = $rawCountsQuery->get();
        $rawCounts = [];

        foreach ($rawCountsResults as $row) {
            $val = trim($row->cluster_val ?? '');
            if (!$val) continue;

            foreach ($categories as $key => $name) {
                if (stripos($val, $key) !== false) {
                    $rawCounts[$key] = ($rawCounts[$key] ?? 0) + 1;
                    break;
                }
            }
        }

        $electiveCounts = [];
        foreach ($categories as $key => $name) {
            if (isset($rawCounts[$key]) && $rawCounts[$key] > 0) {
                $electiveCounts[$key] = [
                    'name'  => $name,
                    'count' => $rawCounts[$key]
                ];
            }
        }

        // --- Recent Submissions ---
        $recentKioskSubmissions = DB::table('kiosk_enrollments')
            ->join('students', 'kiosk_enrollments.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoinSub($latestPre, 'lp', 'students.id', '=', 'lp.student_id')
            ->leftJoin('pre_enrollments', 'lp.latest_id', '=', 'pre_enrollments.id')
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $activeSY)
            ->select(
                'students.lrn', 'users.id as user_primary_id',
                'users.first_name', 'users.middle_name', 'users.last_name',
                'users.extension_name', 'kiosk_enrollments.grade_level',
                'kiosk_enrollments.track', 'kiosk_enrollments.cluster',
                'kiosk_enrollments.completed_at', 'kiosk_enrollments.academic_status as status',
                'pre_enrollments.responses'
            )
            ->when(!empty($grade), function($q) use ($grade) {
                return $q->where('kiosk_enrollments.grade_level', $grade);
            })
            ->orderBy('kiosk_enrollments.completed_at', 'desc')
            ->limit(5)
            ->get()->map(function($submission) {
                // Calculate requirement status for each
                $verifiedCount = DB::table('scans')
                    ->where(function($q) use ($submission) {
                        $q->where('lrn', $submission->lrn)
                          ->orWhere('user_id', $submission->user_primary_id);
                    })
                    ->where('status', 'verified')
                    ->count();

                // Get Required Docs Count
                $raw = json_decode($submission->responses, true) ?? [];
                $academicStatus = strtolower($raw['Academic Status'] ?? $submission->status ?? 'regular');
                $requiredDocsCount = 3;
                if (str_contains($academicStatus, 'als')) {
                    $requiredDocsCount = 3;
                } elseif (str_contains($academicStatus, 'feree') || str_contains($academicStatus, 'balik')) {
                    $requiredDocsCount = 3;
                }

                if ($verifiedCount === 0) {
                    $submission->requirement_status = 'Pending';
                    $submission->requirement_color = '#6B7280'; // Gray
                } elseif ($verifiedCount < $requiredDocsCount) {
                    $submission->requirement_status = 'Incomplete';
                    $submission->requirement_color = '#D97706'; // Amber
                } else {
                    $submission->requirement_status = 'Complete';
                    $submission->requirement_color = '#009444'; // Green
                }

                return $submission;
            });

        // --- Sync & Gradient logic ---
        $lastSync = DB::table('sync_histories')
            ->where('school_year', $activeSY)
            ->where('status', 'Success')
            ->latest()
            ->first();
        $lastSyncTime = $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';

        $totalElectives = array_sum(array_column($electiveCounts, 'count')) ?: 1;

        $data = compact(
            'totalRegistrations', 'totalSubmissions', 'totalEnrolled',
            'percVerified', 'percEnrolled', 'electiveCounts',
            'lastSyncTime', 'recentKioskSubmissions', 'activeSY'
        );

        if ($request->ajax()) {
            return view('admin.dashboardpage.partials._dashboard_wrapper', $data)->render();
        }

        return view('admin.dashboardpage.dashboard', $data);
    }

    public function checkUserStatus($id)
    {
        $isOnline = Cache::has('user-is-online-' . $id);
        return response()->json(['online' => $isOnline]);
    }
}