<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Error</title>
    @vite(['resources/css/mobile.css'])
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="mb-6">
                <span class="icon icon-triangle-alert text-red-500 text-6xl"></span>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-4">Upload Error</h1>

            <p class="text-gray-600 mb-6">
                {{ $message ?? 'An error occurred. Please try again.' }}
            </p>

            <button type="button" onclick="closeTab()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <span class="icon icon-x mr-2"></span>
                Close This Page
            </button>
        </div>
    </div>

    <script>
        function closeTab() {
            // Try to close the tab/window
            window.close();

            // If window.close() doesn't work (modern browsers), show a message
            setTimeout(() => {
                if (!window.closed) {
                    alert('Please close this tab manually');
                }
            }, 100);
        }
    </script>
</body>

</html>