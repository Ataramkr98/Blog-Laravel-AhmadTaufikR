<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MaintenanceMode
{
    /**
     * If maintenance_mode is ON, return 503 to all public traffic.
     * Admins, login/logout, and health-check endpoints always pass through.
     * Gracefully degrades if the system_settings table does not yet exist
     * (e.g. during initial migration or tests that haven't run the migration).
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! app()->isDownForMaintenance()) {
            try {
                $setting = SystemSetting::current();

                if ($setting->maintenance_mode) {
                    $user = $request->user();

                    // Admins and contributors (penulis) always get full access.
                    if ($user?->isAdmin() || $user?->isPenulis()) {
                        return $next($request);
                    }

                    // Always allow auth, password recovery and health check.
                    if ($request->is('sign-in', 'join-free', 'login', 'logout', 'up', 'forgot-password', 'reset-password', 'reset-password/*', 'auth/google/*')) {
                        return $next($request);
                    }

                    abort(503, 'BioLearn sedang dalam pemeliharaan. Silakan coba kembali nanti.');
                }
            } catch (QueryException) {
                // Table doesn't exist yet — pass through silently.
            }
        }

        return $next($request);
    }
}
