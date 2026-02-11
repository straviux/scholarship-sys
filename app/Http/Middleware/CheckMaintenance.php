<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\MaintenanceAnnouncement;

class CheckMaintenance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow admin users during maintenance
        if (auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator'))) {
            return $next($request);
        }

        // Check if system is under maintenance
        if (MaintenanceAnnouncement::isUnderMaintenance()) {
            $maintenance = MaintenanceAnnouncement::getActive();

            // Return maintenance page if not an AJAX request
            if (!$request->expectsJson()) {
                return response()->view('maintenance.show', [
                    'announcement' => $maintenance,
                ], 503);
            }

            // Return JSON response for AJAX requests
            return response()->json([
                'message' => 'System is under maintenance',
                'announcement' => [
                    'title' => $maintenance->title,
                    'message' => $maintenance->message,
                    'type' => $maintenance->type,
                    'countdown' => $maintenance->getCountdownData(),
                ],
            ], 503);
        }

        return $next($request);
    }
}
