<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Skip checks for public routes
        if ($request->is('/') || $request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        // 2. Auth Check
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['message' => 'Please login first.']);
        }

        $user = Auth::user();
        $userRole = strtolower($user->role);

        // Check if account is revoked (Soft Deleted)
        if ($user->trashed()) {
            Auth::logout();
            Session::flush();
            return redirect('/login')->withErrors(['message' => 'Your access has been revoked.']);
        }

        // 3. Path-Based Permission Check
        if ($request->is('student/*') || $request->is('api/*')) {
            // Students, Admins, and Facilitators can all access the kiosk
            if (!in_array($userRole, ['student', 'admin', 'super_admin', 'facilitator'])) {
                return redirect('/login')->withErrors(['message' => 'Unauthorized student access.']);
            }
        } else {
            // STRICT: Only Staff can access non-student routes (Dashboard, etc.)
            if (!in_array($userRole, ['admin', 'super_admin', 'facilitator'])) {
                // If a student tries to go to /dashboard, send them back to the kiosk
                return redirect('/student/grade-selection')->withErrors(['message' => 'Access Denied.']);
            }

            // Role-based Restrictions
            if ($userRole === 'facilitator') {
                // Facilitator can only access dashboard, students, sections, and notifications
                $allowedPaths = [
                    'dashboard',
                    'admin/dashboard',
                    'admin/students*',
                    'admin/sections*',
                    'admin/notifications*',
                    'admin/accountsettings', 
                    'admin/settings/security', 
                    'admin/account/update-password',
                    'check-user-status/*',
                    'admin/api/sections*'
                ];

                $isAllowed = false;
                foreach ($allowedPaths as $path) {
                    if ($request->is($path)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return redirect('/dashboard')->withErrors(['message' => 'Facilitators only have access to Dashboard, Students, and Sections.']);
                }
            } elseif ($userRole === 'admin') {
                // Admin can access everything except Access Management
                if ($request->is('admin/accessmanagement*') || $request->is('admin/users/*')) {
                    return redirect('/dashboard')->withErrors(['message' => 'Admins do not have access to Access Management.']);
                }
            }
            // super_admin has no restrictions
        }

        return $next($request);
    }   
}