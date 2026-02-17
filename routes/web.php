<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- 1. PUBLIC ROUTES (Login) ---

Route::get('/', function () {
    // If already logged in, redirect based on role
    if (session()->has('user_id')) {
        if (session('user_role') === 'admin') {
            return redirect('/admin/dashboard');
        }
        return redirect('/student/grade-selection');
    }
    return view('login');
});

// Handle Login Form Submission
Route::post('/login', [AuthController::class, 'login']);

// Logout Logic
Route::get('/logout', [AuthController::class, 'logout']);


// --- 2. ADMIN ROUTES (Protected) ---

Route::get('/admin/dashboard', function () {
    // Security Check: Must be logged in AND be an admin
    if (!session()->has('user_id') || session('user_role') !== 'admin') {
        return redirect('/');
    }
    return view('admin.dashboard');
});


// --- 3. STUDENT ENROLLMENT FLOW (Protected) ---

// STEP 1: Grade Selection (Grade 11 or 12)
Route::get('/student/grade-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.selection');
});

Route::post('/student/save-grade', function (Request $request) {
    session(['grade_level' => $request->input('grade_level')]);
    return redirect('/student/status-selection');
});

// STEP 2: Student Status (Regular, Transferee, etc.)
Route::get('/student/status-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.status');
});

Route::post('/student/save-status', function (Request $request) {
    session(['student_status' => $request->input('student_status')]);
    return redirect('/student/track-selection');
});

// STEP 3: Track Selection (Academic vs TechPro)
Route::get('/student/track-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.track');
});

Route::post('/student/save-track', function (Request $request) {
    session(['track' => $request->input('track')]);
    return redirect('/student/cluster-selection');
});

// STEP 4: Cluster Selection (Dynamic based on Track)
Route::get('/student/cluster-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.cluster');
});

Route::post('/student/save-cluster', function (Request $request) {
    session(['cluster' => $request->input('cluster')]);
    // Redirect to the Checklist Page
    return redirect('/student/checklist');
});

// STEP 5: Documents Checklist
Route::get('/student/checklist', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.checklist');
});

Route::post('/student/save-checklist', function (Request $request) {
    // Set the first document to capture (e.g., Report Card)
    session(['current_doc' => 'Report Card (SF9)']);
    
    // Redirect to the Camera Capture Page
    return redirect('/student/capture-document');
});

// STEP 6: Capture Document UI (Camera)
Route::get('/student/capture-document', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.capture');
});

Route::post('/student/save-image', function (Request $request) {
    // Here is where you would save the base64 image to storage
    // $imageData = $request->input('image_data');
    
    // After capturing, go to Verification
    return redirect('/student/verifying');
});

// STEP 7: Verification / Loading Screen
Route::get('/student/verifying', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.loading');
});

// FINAL STEP: Student Dashboard (Post-Verification)
Route::get('/student/dashboard', function () {
    if (!session()->has('user_id')) return redirect('/');
    return "<h1>Enrollment Data Saved! Welcome to your Dashboard.</h1>";
});