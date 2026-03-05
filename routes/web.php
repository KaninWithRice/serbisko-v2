<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ScanController;
use App\Http\Middleware\CheckAdmin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('login');
});

Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

// ==========================================
// ADMIN ROUTES (Protected via CheckAdmin)
// ==========================================
Route::middleware([CheckAdmin::class])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/students', [AdminController::class, 'students'])->name('students');
        Route::get('/students/profile/{lrn}', [AdminController::class, 'profilepage'])->name('studentpage.profilepage'); // Merged from backup
        Route::get('/systemsync', [AdminController::class, 'systemsync'])->name('systemsync');
        Route::get('/verification', [AdminController::class, 'verification'])->name('verification');
        Route::get('/requirementhub', [AdminController::class, 'requirementhub'])->name('requirementhub');
        Route::get('/accountsettings', [AdminController::class, 'accountsettings'])->name('accountsettings');
        Route::post('/systemsync/perform', [AdminController::class, 'performSync'])->name('sync.perform');
        Route::post('/verification/action', [AdminController::class, 'handleVerificationAction'])->name('verification.action');
    });
});

// ==========================================
// STUDENT ENROLLMENT FLOW (Protected)
// ==========================================
Route::get('/student/grade-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.selection');
});

Route::post('/student/save-grade', function (Request $request) {
    session(['grade_level' => $request->input('grade_level')]);
    return redirect('/student/status-selection'); 
});

Route::get('/student/status-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.status');
});

Route::post('/student/save-status', function (Request $request) {
    session(['student_status' => $request->input('student_status')]);
    return redirect('/student/track-selection');
});

Route::get('/student/track-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.track');
});

Route::post('/student/save-track', function (Request $request) {
    session(['track' => $request->input('track')]);
    return redirect('/student/cluster-selection');
});

Route::get('/student/cluster-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.cluster');
});

Route::post('/student/save-cluster', function (Request $request) {
    $cluster = $request->input('cluster');
    session(['cluster' => $cluster]);
    
    try {
        Http::post('http://127.0.0.1:51234/api/strand/' . $cluster);
        Http::post('http://127.0.0.1:51234/api/door', ['action' => 'close']);
    } catch (\Exception $e) {
        \Log::error("Arduino offline (Sorting Trigger): " . $e->getMessage());
    }
    
    return redirect('/student/cluster-loading');
});

Route::get('/student/cluster-loading', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.cluster_loading');
});

Route::get('/student/checklist', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.checklist');
});

Route::post('/student/save-checklist', function (Request $request) {
    $studentStatus = session('student_status', 'Regular'); 
    
    // Normalize status for consistency
    $statusKey = strtolower($studentStatus);
    
    $firstDocs = [
        'regular'    => 'Report Card (SF9)',
        'als'        => 'ALS Certificate',
        'transferee' => 'Report Card (SF9)',
        'balik-aral' => 'Report Card (SF9)'
    ];
    
    $currentDoc = $firstDocs[$statusKey] ?? 'Report Card (SF9)';
    session(['current_doc' => $currentDoc]);
    
    return redirect('/student/capture');
});

Route::get('/student/capture', function (Request $request) {
    if (!session()->has('user_id')) return redirect('/');
    
    try {
        Http::post('http://127.0.0.1:51234/api/door', ['action' => 'open']);
    } catch (\Exception $e) {
        \Log::error("Arduino Offline (Slot Open): " . $e->getMessage());
    }

    if ($request->has('doc')) {
        session(['current_doc' => $request->query('doc')]);
    }

    return view('student.capture');
});

Route::post('/student/save-image', [ScanController::class, 'processDocument']);

Route::get('/student/verifying', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.verifying');
});

Route::get('/student/check-scan-status', [ScanController::class, 'checkScanStatus']);

Route::get('/api/check-completion', function () {
    $record = DB::table('scans')
                ->where('user_id', session('user_id', 1)) 
                ->latest()
                ->first();
    
    if (!$record) {
        return response()->json(['status' => 'not_found']);
    }
    return response()->json(['status' => $record->status]);
});

Route::get('/student/mismatch', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.mismatch'); 
});

Route::get('/student/dashboard', function () {
    if (!session()->has('user_id')) return redirect('/');
    return "<h1>Enrollment Data Saved! Welcome to your Dashboard.</h1>";
});

Route::get('/student/thankyou', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.thankyou');
});

// ==========================================
// PYTHON WEBHOOKS (CSRF Exempt)
// ==========================================
Route::post('/api/lis-callback', [ScanController::class, 'lisCallback'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
