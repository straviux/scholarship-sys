<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a request for the test endpoint
$request = \Illuminate\Http\Request::create('/test-add-applicants', 'POST', ['count' => 1]);
$request->setUserResolver(function () {
    return \App\Models\User::first();
});

// Handle the request
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response:\n";
echo $response->getContent();
