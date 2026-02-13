<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * 
     * Checks if the authenticated user is an administrator.
     * Menu-based access control is now handled through the menu items and sidebar system.
     * 
     * Usage in routes:
     *   Route::get('/users', [UserController::class, 'index'])
     *       ->middleware('check.role:admin');
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Always allow administrator
        if ($user->hasRole('administrator')) {
            return $next($request);
        }

        // Check if user has any of the specified roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // User doesn't have required role
        return response()->json([
            'message' => 'You do not have access to this resource.',
            'required_roles' => $roles
        ], 403);
    }
}
