<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventGuestPageCaching
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent browser caching of guest pages (login, register) to avoid stale CSRF tokens
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }
}
