<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
public function verification()
    { 
        $enrollments = DB::table('kiosk_enrollments')
            ->join('users', 'kiosk_enrollments.id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->leftJoin('students', 'users.id', '=', 'students.user_id')
            ->leftJoin('pre_enrollments as pe', 'students.id', '=', 'pe.student_id')
            ->select(
                'kiosk_enrollments.*', 
                'users.first_name', 
                'users.last_name',
                'pe.responses'
            )
            ->get();

        $pendingScans = collect();
        $rejectedPapers = collect();

        foreach ($enrollments as $row) {
            // 1. Check for Manual Verifications
            $docTypes = [
                'sf9' => 'Report Card (SF9)',
                'psa' => 'Birth Certificate',
                'enroll_form' => 'Enrollment Form',
                'als_cert' => 'ALS Certificate',
                'affidavit' => 'Affidavit',
                'good_moral' => 'Good Moral Certificate',
                'sf10' => 'Form 137 / SF10'
            ];

            foreach ($docTypes as $prefix => $docName) {
                $statusCol = "{$prefix}_status";
                if (isset($row->$statusCol) && $row->$statusCol === 'manual_verification') {
                    $pathCol = "{$prefix}_path";
                    
                    $details = json_decode($row->responses, true) ?? [];
                    $displayGrade = $row->grade_level ?? ($details['Grade Level to Enroll'] ?? '—');

                    $pendingScans->push((object)[
                        'id' => $row->id . ':' . $prefix, 
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'display_grade' => $displayGrade,
                        'document_type' => $docName,
                        'file_path' => $row->$pathCol,
                        'created_at' => $row->updated_at 
                    ]);
                }
            }

            // 2. Check for Rejected Papers (Physical Bin Rejections)
            if ($row->rejected_papers) {
                $rejections = json_decode($row->rejected_papers, true);
                $details = json_decode($row->responses, true) ?? [];
                $displayGrade = $row->grade_level ?? ($details['Grade Level to Enroll'] ?? '—');

                foreach ($rejections as $rej) {
                    $rejectedPapers->push((object)[
                        'user_id' => $row->id,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'display_grade' => $displayGrade,
                        'document_type' => $rej['document_type'],
                        'rejected_at' => $rej['rejected_at']
                    ]);
                }
            }
        }

        if (request()->ajax()) {
            return view('admin.partials.verification-table', compact('pendingScans'))->render();
        }

        return view('admin.verification', compact('pendingScans', 'rejectedPapers')); 
    }

    public function handleVerificationAction(Request $request) 
    {
        $idAndPrefix = $request->input('scan_id'); // Format: "userId:prefix"
        $action = $request->input('action'); 
        
        $parts = explode(':', $idAndPrefix);
        if (count($parts) !== 2) return back()->with('error', 'Invalid verification action.');
        
        $userId = $parts[0];
        $prefix = $parts[1];

        $finalStatus = ($action === 'approve') ? 'verified' : 'failed';

        DB::table('kiosk_enrollments')->where('id', $userId)->update([
            "{$prefix}_status" => $finalStatus,
            "{$prefix}_remarks" => 'Manually ' . $action . 'd by Admin',
            'latest_scan_status' => $finalStatus,
            'latest_scan_remarks' => 'Manually ' . $action . 'd by Admin'
        ]);

        return back()->with('success', 'Document has been ' . $action . 'd.');
    }

    public function collectRejectedPaper(Request $request)
    {
        $userId = $request->input('user_id');
        $rejectedAt = $request->input('rejected_at');

        $enrollment = DB::table('kiosk_enrollments')->where('id', $userId)->first();
        if ($enrollment && $enrollment->rejected_papers) {
            $rejectedPapers = json_decode($enrollment->rejected_papers, true);
            
            // Filter out the collected paper
            $updatedPapers = array_filter($rejectedPapers, function($rej) use ($rejectedAt) {
                return $rej['rejected_at'] !== $rejectedAt;
            });

            DB::table('kiosk_enrollments')->where('id', $userId)->update([
                'rejected_papers' => json_encode(array_values($updatedPapers))
            ]);

            return back()->with('success', 'Paper marked as collected.');
        }

        return back()->with('error', 'Rejection record not found.');
    }
}