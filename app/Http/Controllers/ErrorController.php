<?php

namespace App\Http\Controllers;

class ErrorController extends Controller
{
    /**
     * Show the 403 Forbidden page
     */
    public function forbidden()
    {
        return response('Access Forbidden', 403);
    }

    /**
     * Show the 404 Not Found page
     */
    public function notFound()
    {
        return response('Page Not Found', 404);
    }

    /**
     * Show the 500 Server Error page
     */
    public function serverError()
    {
        return response('Internal Server Error', 500);
    }

    /**
     * Show the 429 Too Many Requests page
     */
    public function tooManyRequests()
    {
        return response('Too Many Requests', 429);
    }
}
