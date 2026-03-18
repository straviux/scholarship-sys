<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Return a success JSON response
     * 
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $code HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response
     * 
     * @param string $message Error message
     * @param array $errors Validation errors (optional)
     * @param int $code HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message, $errors = [], int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Return a 404 Not Found response
     * 
     * @param string $message Error message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(string $message = 'Not found')
    {
        return $this->error($message, code: 404);
    }

    /**
     * Return a 403 Unauthorized response
     * 
     * @param string $message Error message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, code: 403);
    }

    /**
     * Return a 422 Unprocessable Entity response
     * 
     * @param string $message Error message
     * @param array $errors Validation errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unprocessable(string $message = 'Validation failed', array $errors = [])
    {
        return $this->error($message, $errors, 422);
    }
}
