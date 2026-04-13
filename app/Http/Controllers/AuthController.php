<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
        {
        // 1. Validation (remains the same)
        $request->validate([
            'last_name' => 'required|string',
            'given_name' => 'required|string',
            'dob' => 'required|date',
            'password' => 'required',
        ], [
            'last_name.required' => 'Required.',
            'given_name.required' => 'Required.',
            'dob.required' => 'Required.',
            'password.required' => 'Required.',
        ]);

        // 2. Find user including soft-deleted accounts
        // We use withTrashed() so we can still "see" revoked accounts for verification
        $query = User::withTrashed()
            ->where('last_name', $request->last_name)
            ->where('first_name', $request->given_name)
            ->where('birthday', $request->dob);

        if ($request->filled('middle_name')) {
            $query->where('middle_name', $request->middle_name);
        } else {
            $query->where(function($q) {
                $q->whereNull('middle_name')->orWhere('middle_name', '');
            });
        }

        $user = $query->first();

        // 3. Identity Verification Fail
        if (!$user) {
            // IMPORTANT: Add withTrashed() here too!
            $partialMatch = User::withTrashed() 
                ->where('last_name', $request->last_name)
                ->where('first_name', $request->given_name)
                ->where('birthday', $request->dob)
                ->exists();

            if ($partialMatch) {
                return back()->withErrors(['middle_name' => 'Please enter your middle name.'])
                            ->withInput($request->except('password'));
            }

            return back()->withErrors(['message' => 'The information provided does not match our records.'])
                        ->withInput($request->except('password'));
        }

        // 4. Password Verification (Verify credentials before checking if revoked)
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.'])->withInput($request->except('password'));
        }

        // 5. Check if Account is Revoked (Soft Deleted)
        if ($user->trashed()) {
            return back()
                ->withErrors(['message' => 'Your account access has been REVOKED. Please contact the system administrator.'])
                ->withInput($request->except('password'));
        }

        // 6. Success - Authenticate and Session management
        Auth::login($user);
        $role = strtolower($user->role); 

        Session::put('user_id', $user->id);
        Session::put('user_role', $role); 
        Session::put('user_name', $user->first_name);

        if (in_array($role, ['admin', 'super_admin', 'facilitator'])) {
            return redirect('/dashboard'); 
        }
        
        // Check for existing enrollment to determine redirect
        $student = $user->student;
        $hasEnrollment = false;
        if ($student) {
            $hasEnrollment = \Illuminate\Support\Facades\DB::table('kiosk_enrollments')
                ->where('student_id', $student->id)
                ->exists();
        }

        return $hasEnrollment 
            ? redirect('/student/checklist') 
            : redirect('/student/grade-selection');
    }

    public function logout(Request $request)
    {
        // Safety: Try to close the slot door on logout
        try {
            Http::timeout(2)->post('http://127.0.0.1:51234/api/door', ['action' => 'close']);
        } catch (\Exception $e) {
            Log::error("Arduino Offline (Logout Close): " . $e->getMessage());
        }

        $request->session()->forget(['user_id', 'user_role', 'user_name']);

        // Standard Laravel logout
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function updatePassword(Request $request)
    {
        // 1. Backend Validation
        // We use the same rules as your frontend to ensure security if JS is disabled.
        $request->validate([
            'current_password' => ['required'],
            'new_password' => [
                'required',
                'confirmed', // Automatically looks for new_password_confirmation
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            // Custom messages to match your specific UI requirements
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.confirmed' => 'Passwords do not match.',
        ]);

        $user = Auth::user();

        // 2. Verify the "Current Password" field matches the actual DB password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match our records.'
            ])->withInput();
        }

        // 3. Hash and Save the new password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 4. Redirect with a success notification
        return back()->with('success', 'Your password has been successfully updated.');
    }
}