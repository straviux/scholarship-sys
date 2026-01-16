<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CheckRoles
{
    /**
     * Handle an incoming request.
     * Allows multiple roles separated by pipe (|)
     * Usage: ->middleware('check-roles:administrator|program_manager')
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        $user = auth()->user();

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (!method_exists($user, 'hasAnyRole')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        // Split roles by pipe and check if user has any of them
        $roleArray = explode('|', $roles);

        if (!$user->hasAnyRole($roleArray)) {
            throw UnauthorizedException::forRoles($roleArray);
        }

        return $next($request);
    }
}
