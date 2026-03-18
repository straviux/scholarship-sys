<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ErrorController extends Controller
{
    /**
     * Show the 403 Forbidden page
     */
    public function forbidden(): JsonResponse
    {
        return $this->error('Access Forbidden', code: 403);
    }

    /**
     * Show the 404 Not Found page
     */
    public function notFound(string $message = 'Not found'): JsonResponse
    {
        return parent::notFound($message);
    }

    /**
     * Show the 500 Server Error page
     */
    public function serverError(): JsonResponse
    {
        return $this->error('Internal Server Error', code: 500);
    }

    /**
     * Show the 429 Too Many Requests page
     */
    public function tooManyRequests(): JsonResponse
    {
        return $this->error('Too Many Requests', code: 429);
    }
}
