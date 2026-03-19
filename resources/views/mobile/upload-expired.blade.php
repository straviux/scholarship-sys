<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Link Expired</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-6xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Upload Link Expired</h1>
            <p class="text-gray-600 mb-6">
                This upload link is either invalid or has expired. Please contact the administrator to request a new
                upload link.
            </p>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded text-left">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> Upload links typically expire after 30 days for security reasons.
                </p>
            </div>
        </div>
    </div>
</body>

</html>