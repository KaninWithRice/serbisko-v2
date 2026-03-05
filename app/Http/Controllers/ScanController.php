<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    public function processDocument(Request $request)
    {
        try {
            set_time_limit(0); 
            
            $imageData = $request->input('image_data');
            $docType = $request->input('document_type', 'Report Card');
            $userId = session('user_id', 1);

            if (!$imageData || strpos($imageData, ';base64,') === false) {
                return response()->json(['status' => 'error', 'message' => 'Image data is invalid.']);
            }

            // Decode and Save Image
            $imageParts = explode(";base64,", $imageData);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1] ?? 'jpeg';
            $imageBase64 = base64_decode($imageParts[1]);
            
            $fileName = 'scan_' . $userId . '_' . time() . '.' . $imageType;
            $filePath = 'scans/' . $fileName;

            Storage::disk('public')->put($filePath, $imageBase64);
            $imageFullPath = storage_path('app/public/' . $filePath);

            $scanId = DB::table('scans')->insertGetId([
                'user_id'       => $userId,
                'document_type' => $docType,
                'file_path'     => $filePath,
                'grade_level'   => session('grade_level'),
                'status'        => 'pending',
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            // --- HELPER: Handles failures and checks for 3rd strike ---
            $handleFailure = function($remarks) use ($userId, $docType, $scanId) {
                // Count any attempt that wasn't successfully verified by AI
                $previousAttempts = DB::table('scans')
                    ->where('user_id', $userId)
                    ->where('document_type', $docType)
                    ->whereIn('status', ['failed', 'manual_verification', 'manual_approved', 'manual_declined'])
                    ->where('id', '!=', $scanId)
                    ->count();

                $totalAttempts = $previousAttempts + 1;

                if ($totalAttempts >= 3) { 
                    DB::table('scans')->where('id', $scanId)->update([
                        'status' => 'manual_verification',
                        'remarks' => 'Sent to Admin for Manual Verification.'
                    ]);
                    return ['is_strike_3' => true, 'count' => $totalAttempts];
                } else {
                    DB::table('scans')->where('id', $scanId)->update([
                        'status' => 'failed',
                        'remarks' => $remarks
                    ]);
                    return ['is_strike_3' => false, 'count' => $totalAttempts];
                }
            };

            // --- 1. Dynamic Document Classification ---
            $lowerDoc = strtolower($docType);
            if (str_contains($lowerDoc, 'report') || str_contains($lowerDoc, 'sf9')) $pythonDocType = 'report_card';
            elseif (str_contains($lowerDoc, 'birth') || str_contains($lowerDoc, 'psa')) $pythonDocType = 'birth_certificate';
            elseif (str_contains($lowerDoc, 'enrollment') || str_contains($lowerDoc, 'form')) $pythonDocType = 'enrollment_form';
            elseif (str_contains($lowerDoc, 'als') || str_contains($lowerDoc, 'alternative')) $pythonDocType = 'als_certificate';
            elseif (str_contains($lowerDoc, 'affidavit') || str_contains($lowerDoc, 'sworn')) $pythonDocType = 'affidavit';
            elseif (str_contains($lowerDoc, 'moral')) $pythonDocType = 'good_moral';
            elseif (str_contains($lowerDoc, '137') || str_contains($lowerDoc, 'sf10')) $pythonDocType = 'form_137';
            else $pythonDocType = 'generic_name_check'; 

            $user = DB::table('users')->where('id', $userId)->first();
            $expectedFirstName = $user->first_name ?? 'Unknown';
            $expectedLastName = $user->last_name ?? 'Unknown';

            try {
                $ocrResponse = Http::timeout(180)
                    ->attach('image', file_get_contents($imageFullPath), $fileName)
                    ->post('http://127.0.0.1:9001/ocr', [
                        'doc_type'   => $pythonDocType,
                        'scan_id'    => $scanId,
                        'first_name' => $expectedFirstName,
                        'last_name'  => $expectedLastName
                    ]);

                if ($ocrResponse->failed()) {
                    $failure = $handleFailure('OCR Server Error');
                    if ($failure['is_strike_3']) {
                        return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);
                    }
                    return response()->json(['status' => 'error', 'message' => 'OCR Server Error', 'attempts' => $failure['count']]);
                }

                $ocrResult = $ocrResponse->json();
                
                if (isset($ocrResult['success']) && $ocrResult['success'] === false) {
                    $failure = $handleFailure($ocrResult['error'] ?? 'Document Rejected.');
                    if ($failure['is_strike_3']) {
                        return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);
                    }
                    return response()->json(['status' => 'error', 'message' => $ocrResult['error'] ?? 'Invalid Type', 'attempts' => $failure['count']]);
                }

                if (isset($ocrResult['success']) && $ocrResult['success'] === true) {
                    if ($pythonDocType === 'report_card') {
                        $lrn = $ocrResult['lrn'] ?? null;
                        if ($lrn) {
                            DB::table('scans')->where('id', $scanId)->update(['lrn' => $lrn, 'remarks' => 'Sending to LIS...']);
                            $enrollingGrade = session('grade_level', '11'); 
                            $expectedGrade = ($enrollingGrade == '12') ? 'Grade 11' : 'Grade 10';

                            try {
                                Http::timeout(10)->post('http://127.0.0.1:5001/verify', [
                                    'lrn' => $lrn,
                                    'expected_grade' => $expectedGrade,
                                    'webhook_url' => url('/api/lis-callback'), 
                                    'scan_id' => $scanId
                                ]);
                            } catch (\Exception $e) {
                                $failure = $handleFailure('LIS Verifier is offline.');
                                if ($failure['is_strike_3']) {
                                    return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);
                                }
                                return response()->json(['status' => 'error', 'message' => 'LIS Verifier is offline.', 'attempts' => $failure['count']]);
                            }
                        } else {
                            $failure = $handleFailure('Report Card verified, but no LRN found.');
                            if ($failure['is_strike_3']) {
                                return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);
                            }
                            return response()->json(['status' => 'error', 'message' => 'Report Card verified, but no LRN found.', 'attempts' => $failure['count']]);
                        }
                    } else {
                        DB::table('scans')->where('id', $scanId)->update(['status' => 'verified', 'remarks' => 'Verified']);
                    }
                }

            } catch (\Exception $e) {
                $failure = $handleFailure('AI Engine Offline');
                if ($failure['is_strike_3']) {
                    return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);
                }
                return response()->json(['status' => 'error', 'message' => 'AI Engine Offline', 'attempts' => $failure['count']]);
            }

            return response()->json(['status' => 'success', 'redirect' => '/student/verifying']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'System Error.']);
        }
    }

    public function lisCallback(Request $request)
    {
        $scanId = $request->input('scan_id');
        $status = $request->input('result'); 
        
        if ($scanId && $status) {
            // If LIS fails, we also need to route it through the 3-strike check
            if ($status === 'failed') {
                $scan = DB::table('scans')->where('id', $scanId)->first();
                if($scan) {
                    $failedCount = DB::table('scans')
                        ->where('user_id', $scan->user_id)
                        ->where('document_type', $scan->document_type)
                        ->where('status', 'failed')
                        ->count();
                    
                    if ($failedCount >= 2) {
                        $status = 'manual_verification';
                    }
                }
            }

            DB::table('scans')->where('id', $scanId)->update(['status' => $status, 'updated_at' => now()]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }
    
    public function checkScanStatus()
    {
        $userId = session('user_id', 1);
        $studentStatus = session('student_status', 'Regular'); 
        $currentDoc = session('current_doc', 'Report Card (SF9)');

        $tracks = [
            'Regular'    => ['Report Card (SF9)', 'Birth Certificate', 'Enrollment Form'],
            'ALS'        => ['ALS Certificate', 'Enrollment Form', 'Birth Certificate', 'Affidavit'],
            'Transferee' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
            'Balik-Aral' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
        ];

        $docList = $tracks[$studentStatus] ?? $tracks['Regular'];
        $currentIndex = array_search($currentDoc, $docList);
        $nextUrl = '/student/thankyou'; 

        if ($currentIndex !== false && isset($docList[$currentIndex + 1])) {
            $nextDoc = $docList[$currentIndex + 1];
            $nextUrl = '/student/capture?doc=' . urlencode($nextDoc);
        }

        $latestScan = DB::table('scans')->where('user_id', $userId)->orderBy('id', 'desc')->first();

        if (!$latestScan) return response()->json(['status' => 'pending']);

        return response()->json([
            'status' => $latestScan->status,
            'remarks' => $latestScan->remarks,
            'next_url' => $nextUrl,
            'current_doc' => $currentDoc
        ]);
    }
}