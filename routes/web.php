<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\RegistrationSyncController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\Admin\SyncConflictController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {return view('login');})->name('home');
Route::get('/login', function () {return view('login');})->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (Protected via CheckAdmin)
|--------------------------------------------------------------------------
*/

Route::middleware([CheckAdmin::class])->group(function () {

    Route::get('/check-user-status/{id}', [DashboardController::class, 'checkUserStatus']);

    // Direct Dashboard Access
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Prefixed Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/students', [StudentController::class, 'students'])->name('students');
        Route::get('/students/profile/{id}', [StudentController::class, 'profilepage'])->name('studentpage.profilepage');
        Route::get('/systemsync', [RegistrationSyncController::class, 'systemsync'])->name('systemsync');
        Route::get('/verification', [VerificationController::class, 'verification'])->name('verification');
        Route::get('/accountsettings', [AccessController::class, 'accountsettings'])->name('accountsettings');
        Route::get('/accessmanagement', [AccessController::class, 'accessManagement'])->name('accessmanagement');
        Route::get('/settings/security', [AuthController::class, 'showSecurity'])->name('admin.security');
        Route::post('/systemsync/perform', [RegistrationSyncController::class, 'performSync'])->name('sync.perform');
        Route::post('/verification/action', [VerificationController::class, 'handleVerificationAction'])->name('verification.action');
        Route::post('/verification/collect', [VerificationController::class, 'collectRejectedPaper'])->name('collect-rejected-paper');
        Route::post('/accessmanagement/store', [AccessController::class, 'storeUser'])->name('accessmanagement.store');
        Route::delete('/users/{id}', [AccessController::class, 'destroy'])->name('destroyUser');
        Route::patch('/users/{id}/restore', [AccessController::class, 'restoreUser'])->name('restoreUser');
        Route::patch('/users/{id}/update-role', [AccessController::class, 'updateRole'])->name('updateRole');
        Route::put('/account/update-password', [AuthController::class, 'updatePassword'])->name('account.update-password');
        Route::put('/students/update/{id}', [StudentController::class, 'updateStudentProfile'])->name('students.update');
        Route::get('/settings', [SettingsController::class, 'showSettings'])->name('settings.show');
        Route::post('/settings/update', [SettingsController::class, 'updateSettings'])->name('settings.save');
        Route::post('/settings/refresh-headers', [SettingsController::class, 'refreshHeaders'])->name('settings.refresh');
        Route::get('/settings/mapping', [SettingsController::class, 'showMapping'])->name('settings.mapping');
        Route::post('/settings/mapping/update', [SettingsController::class, 'updateMapping'])->name('settings.mapping.save');
        Route::get('/conflicts', [SyncConflictController::class, 'index'])->name('syncconflict');
        Route::post('/conflicts/{id}/resolve', [SyncConflictController::class, 'resolve'])->name('admin.conflicts.resolve');

        // Sections Management
        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::delete('/sections/{id}', [SectionController::class, 'destroy'])->name('sections.destroy');
        Route::get('/api/sections', [SectionController::class, 'getSections'])->name('api.sections');

    });
});

// ==========================================
// STUDENT ENROLLMENT FLOW (Protected)
// ==========================================
Route::get('/student/grade-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.selection');
});

Route::post('/student/save-grade', [EnrollmentController::class, 'saveGrade']);

Route::get('/student/status-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.status');
});

Route::post('/student/save-status', [EnrollmentController::class, 'saveStatus']);

Route::get('/student/track-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.track');
});

Route::post('/student/save-track', [EnrollmentController::class, 'saveTrack']);

Route::get('/student/cluster-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.cluster');
});

// JUST THIS ONE LINE - The Controller handles the Arduino logic now!
Route::post('/student/save-cluster', [EnrollmentController::class, 'saveCluster']);

Route::get('/student/cluster-loading', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.cluster_loading');
});

Route::get('/student/checklist', [EnrollmentController::class, 'showChecklist']);

// Replace the old checklist closure
Route::post('/student/save-checklist', [EnrollmentController::class, 'saveChecklist']);

// Replace the old capture closure
Route::get('/student/capture', [EnrollmentController::class, 'showCapture']);

Route::post('/student/save-image', [ScanController::class, 'processDocument']);

Route::get('/student/verifying', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.verifying');
});

Route::get('/student/check-scan-status', [ScanController::class, 'checkScanStatus']);
Route::get('/student/check-rejection', [ScanController::class, 'checkRejection']);

Route::get('/api/check-completion', function () {
    $userId = session('user_id', 1);
    $record = DB::table('kiosk_enrollments')
                ->join('students', 'kiosk_enrollments.student_id', '=', 'students.id')
                ->where('students.user_id', $userId) 
                ->first();
    
    if (!$record) {
        return response()->json(['status' => 'not_found']);
    }
    return response()->json(['status' => $record->latest_scan_status]);
});

Route::get('/student/mismatch', function () {
    if (!session()->has('user_id')) return redirect('/');
    return view('student.mismatch'); 
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
