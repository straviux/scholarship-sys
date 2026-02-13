<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * 
     * Checks if the authenticated user has a specific permission for an action.
     * Note: This checks if user's role has the permission, not directly assigned permissions.
     * 
     * Usage in routes:
     *   Route::post('/users', [UserController::class, 'store'])
     *       ->middleware('check.permission:users.create');
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has the specified permission
        if (!$user->hasPermission($permission)) {
            // Return JSON response for API requests or web requests
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
