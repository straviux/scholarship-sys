<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransformCssPreloadToLinks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only transform HTML responses
        if ($response->headers->get('content-type') && str_contains($response->headers->get('content-type'), 'text/html')) {
            $content = $response->getContent();

            // Convert CSS preload to prefetch to avoid browser warnings about unused preloaded resources
            // These CSS files are for dynamically loaded components and will be used as they render
            $transformed = preg_replace(
                '/<link rel="preload" as="style" ([^>]*?)href="([^"]*?\.css)">/i',
                '<link rel="prefetch" as="style" $1href="$2">',
                $content
            );

            $response->setContent($transformed);
        }

        return $response;
    }
}
