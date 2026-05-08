<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Student;
use App\Mail\ReceiptMail;

class EnrollmentController extends Controller
{
    private function getUserId() {
        return Auth::id();
    }

    private function getStudent($userId) {
        if (!$userId) return null;
        return Student::where('user_id', $userId)->first();
    }

    public function sendReceiptEmail(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $student = $this->getStudent($userId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student record not found.'], 404);
        }

        $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        if (!$enrollment || !$enrollment->receipt_number) {
            return response()->json(['success' => false, 'message' => 'No completed enrollment found.'], 404);
        }

        $user = Auth::user();

        try {
            Mail::to($request->email)->send(new ReceiptMail($user, $student, $enrollment));
            return response()->json(['success' => true, 'message' => 'Receipt sent successfully!']);
        } catch (\Exception $e) {
            Log::error("Failed to send receipt email: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email. Please check your SMTP configuration.'], 500);
        }
    }

    public function saveGrade(Request $request) {
        $request->validate(['grade_level' => 'required|in:11,12']);
        $userId = $this->getUserId();
        
        if (!$userId) return redirect('/login')->withErrors(['message' => 'Session expired. Please log in again.']);

        $student = $this->getStudent($userId);
        if (!$student) {
            Log::error("Student record not found for User ID: " . $userId);
            return redirect('/login')->withErrors(['message' => 'Your student profile is incomplete. Please contact the administrator.']);
        }

        session(['grade_level' => $request->grade_level]);
        
        DB::table('kiosk_enrollments')->updateOrInsert(
            ['student_id' => $student->id],
            [
                'student_lrn' => $student->lrn,
                'grade_level' => $request->grade_level, 
                'updated_at' => now(),
                'started_at' => DB::raw('IFNULL(started_at, NOW())')
            ]
        );

        return redirect('/student/status-selection');
    }

    public function saveStatus(Request $request) {
        $userId = $this->getUserId();
        if (!$userId) return redirect('/login')->withErrors(['message' => 'Session expired.']);
        
        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login')->withErrors(['message' => 'Student record not found.']);

        session(['student_status' => $request->student_status]);
        
        DB::table('kiosk_enrollments')->where('student_id', $student->id)
            ->update(['academic_status' => $request->student_status]);

        return redirect('/student/track-selection');
    }

    public function saveTrack(Request $request) {
        $userId = $this->getUserId();
        if (!$userId) return redirect('/login')->withErrors(['message' => 'Session expired.']);
        
        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login')->withErrors(['message' => 'Student record not found.']);

        session(['track' => $request->track]);
        
        DB::table('kiosk_enrollments')->where('student_id', $student->id)
            ->update(['track' => $request->track]);

        return redirect('/student/cluster-selection');
    }

    public function saveCluster(Request $request) {
        $cluster = $request->input('cluster');
        $userId = $this->getUserId();
        if (!$userId) return redirect('/login')->withErrors(['message' => 'Session expired.']);
        
        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login')->withErrors(['message' => 'Student record not found.']);

        session(['cluster' => $cluster]);

        // Update Database
        DB::table('kiosk_enrollments')->where('student_id', $student->id)
            ->update(['cluster' => $cluster]);

        // Arduino Physical Triggers
        try {
            Http::timeout(3)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/strand/' . $cluster);
            Http::timeout(3)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/door', ['action' => 'close']);
        } catch (\Exception $e) {
            Log::error("Arduino offline (Sorting Trigger): " . $e->getMessage());
        }

        return redirect('/student/cluster-loading');
    }

    public function getRequiredDocs($status) {
        $status = strtolower($status ?? 'regular');
        if ($status === 'als') {
            return [
                'ALS Certificate of Rating' => 'als_cert',
                'Enrollment Form' => 'enroll_form',
                'PSA Birth Certificate' => 'psa'
            ];
        } elseif ($status === 'transferee' || $status === 'balik_aral') {
            return [
                'Report Card (SF9)' => 'sf9',
                'PSA Birth Certificate' => 'psa',
                'Enrollment Form' => 'enroll_form'
            ];
        } else {
            return [
                'Report Card (SF9)' => 'sf9',
                'PSA Birth Certificate' => 'psa',
                'Enrollment Form' => 'enroll_form'
            ];
        }
    }

    public function showChecklist() {
        $userId = $this->getUserId();
        if (!$userId) return redirect('/login');

        // Safety Fallback: Close the slot door whenever they land back on the checklist
        try {
            Http::timeout(2)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/door', ['action' => 'close']);
        } catch (\Exception $e) {
            // Silently fail if Arduino is offline
        }

        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login')->withErrors(['message' => 'Student profile incomplete.']);

        $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        if (!$enrollment) {
            Log::warning("Checklist reached without enrollment record", ['userId' => $userId]);
            return redirect('/student/grade-selection');
        }

        $status = $enrollment->academic_status ?? 'regular';
        $requiredDocs = $this->getRequiredDocs($status);

        // Check if all required docs are already verified
        $allVerified = true;
        foreach ($requiredDocs as $label => $prefix) {
            $statusCol = $prefix . '_status';
            $docStatus = $enrollment->$statusCol ?? 'pending';
            if ($docStatus !== 'verified' && $docStatus !== 'manual_verification') {
                $allVerified = false;
                break;
            }
        }

        if ($allVerified) {
            Log::info("Enrollment Complete - Redirecting to Thank You", ['userId' => $userId]);
            return redirect('/student/thankyou');
        }
        
        Log::info("Showing Checklist", ['userId' => $userId, 'status' => $status]);
        
        return view('student.checklist', compact('enrollment', 'requiredDocs'));
    }

    public function showThankYou() {
        $userId = Auth::id();
        if (!$userId) return redirect('/login');

        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login');

        $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        if (!$enrollment) return redirect('/student/grade-selection');

        // Check if all docs verified
        $status = $enrollment->academic_status ?? 'regular';
        $requiredDocs = $this->getRequiredDocs($status);
        $allVerified = true;
        foreach ($requiredDocs as $label => $prefix) {
            $statusCol = $prefix . '_status';
            $docStatus = $enrollment->$statusCol ?? 'pending';
            if ($docStatus !== 'verified' && $docStatus !== 'manual_verification') {
                $allVerified = false;
                break;
            }
        }

        if (!$allVerified) {
            return redirect('/student/checklist');
        }

        // Generate receipt number if not exists
        if (!$enrollment->receipt_number) {
            $receiptNumber = 'RCT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            // Ensure uniqueness
            while (DB::table('kiosk_enrollments')->where('receipt_number', $receiptNumber)->exists()) {
                $receiptNumber = 'RCT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            }
            
            DB::table('kiosk_enrollments')->where('student_id', $student->id)->update([
                'receipt_number' => $receiptNumber,
                'completed_at' => now(),
                'academic_status' => 'For Enrollment' // Update status to For Enrollment once complete
            ]);
            
            // Refresh enrollment object
            $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        }

        $user = Auth::user();

        return view('student.thankyou', compact('student', 'enrollment', 'user'));
    }

    public function saveChecklist(Request $request) {
        $selectedDocs = $request->input('documents', []);
        $userId = $this->getUserId();
        if (!$userId) return redirect('/login')->withErrors(['message' => 'Session expired.']);
        
        $student = $this->getStudent($userId);
        if (!$student) return redirect('/login')->withErrors(['message' => 'Student record not found.']);
        
        Log::info("Checklist Submitted", ['userId' => $userId, 'selected' => $selectedDocs]);

        if (empty($selectedDocs)) {
            return back()->withErrors(['error' => 'Please select at least one document.']);
        }

        $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        
        // Filter out docs that are already verified or pending manual review
        $toScan = [];
        foreach ($selectedDocs as $docName) {
            $prefix = $this->getPrefix($docName);
            $statusCol = $prefix . '_status';
            $status = $enrollment->$statusCol ?? 'pending';
            
            if ($status !== 'verified' && $status !== 'manual_verification') {
                $toScan[] = $docName;
            } elseif ($prefix === 'sf9') {
                // SPECIAL CASE: SF9 FRONT IS DONE, CHECK IF BACK IS DONE
                $backStatus = $enrollment->sf9_back_status ?? 'pending';
                if ($backStatus !== 'verified' && $backStatus !== 'manual_verification') {
                    // Start from the back
                    $toScan[] = 'Report Card (SF9 Back)';
                }
            }
        }

        if (empty($toScan)) {
            return back()->withErrors(['error' => 'All selected documents are already submitted.']);
        }

        // Store only the NEW docs to scan in session
        session(['docs_to_scan' => $toScan]);
        session(['current_doc' => $toScan[0]]);
        
        return redirect('/student/capture');
    }

    private function getPrefix($docType) {
        $lowerDoc = strtolower($docType);
        if (str_contains($lowerDoc, 'sf9 back') || str_contains($lowerDoc, 'report card back')) return 'sf9_back';
        if (str_contains($lowerDoc, 'report') || str_contains($lowerDoc, 'sf9')) return 'sf9';
        if (str_contains($lowerDoc, 'birth') || str_contains($lowerDoc, 'psa')) return 'psa';
        if (str_contains($lowerDoc, 'enrollment') || str_contains($lowerDoc, 'form')) return 'enroll_form';
        if (str_contains($lowerDoc, 'als') || str_contains($lowerDoc, 'alternative')) return 'als_cert';
        if (str_contains($lowerDoc, 'affidavit') || str_contains($lowerDoc, 'sworn')) return 'affidavit';
        if (str_contains($lowerDoc, 'moral')) return 'good_moral';
        if (str_contains($lowerDoc, '137') || str_contains($lowerDoc, 'sf10')) return 'sf10';
        return 'sf9'; // Fallback
    }

    public function showCapture(Request $request) {
        if (!Auth::check()) return redirect('/');
        
        try {
            Http::post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/door', ['action' => 'open']);
        } catch (\Exception $e) {
            Log::error("Arduino Offline (Slot Open): " . $e->getMessage());
        }

        if ($request->has('doc')) {
            session(['current_doc' => $request->query('doc')]);
        }

        return view('student.capture');
    }
}