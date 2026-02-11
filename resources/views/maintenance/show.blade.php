<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-center">
                <div class="mb-4">
                    <svg class="w-16 h-16 mx-auto text-white animate-pulse-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">{{ $announcement->title }}</h1>
            </div>

            <!-- Content -->
            <div class="px-6 py-8">
                <!-- Message -->
                <div class="mb-6">
                    <p class="text-gray-700 text-center leading-relaxed">
                        {{ $announcement->message }}
                    </p>
                </div>

                @php
                $countdown = $announcement->getCountdownData();
                @endphp

                <!-- Countdown -->
                @if($countdown && $countdown['status'] === 'upcoming')
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 text-center mb-2">{{ $countdown['message'] }}</p>
                    <div id="countdown" class="text-3xl font-mono font-bold text-center text-blue-600">
                        --:--:--
                    </div>
                </div>
                @endif

                @if($countdown && $countdown['status'] === 'active')
                <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-6">
                    <div class="animate-pulse mb-3">
                        <p class="text-red-600 font-semibold text-lg">🔧 Maintenance in progress</p>
                    </div>

                    @if($countdown['end_time'] && $countdown['seconds_remaining'])
                    <div class="mt-4 pt-4 border-t border-red-200">
                        <p class="text-sm text-gray-600 text-center mb-2">Estimated time remaining</p>
                        <div id="duration-countdown" class="text-2xl font-mono font-bold text-center text-red-600">
                            --:--:--
                        </div>
                        @if($countdown['duration_minutes'])
                        <p class="text-xs text-gray-500 text-center mt-2">Total duration: {{ $countdown['duration_minutes'] }} minutes</p>
                        @endif
                    </div>
                    @else
                    <p class="text-red-600 text-sm text-center">We'll be back online soon</p>
                    @endif
                </div>
                @endif

                <!-- Type Badge -->
                <div class="flex justify-center mb-6">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $announcement->type === 'info' ? 'bg-blue-100 text-blue-800' : ($announcement->type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ strtoupper($announcement->type) }}
                    </span>
                </div>

                <!-- Footer -->
                <div class="text-center text-sm text-gray-500">
                    <p>Times may vary. Thank you for your patience.</p>
                    <p class="mt-2">Please check back shortly.</p>
                </div>
            </div>
        </div>

        <!-- Footer Text -->
        <div class="text-center mt-8 text-gray-600 text-sm">
            <p>Scholarship Management System</p>
        </div>
    </div>

    <script>
        @if($countdown && $countdown['status'] === 'upcoming')

        function updateCountdown() {
            const startTime = new Date(`{{ $countdown['start_time'] }}`).getTime();
            const now = new Date().getTime();
            const secondsRemaining = Math.floor((startTime - now) / 1000);

            if (secondsRemaining <= 0) {
                document.getElementById('countdown').textContent = 'Starting...';
                setTimeout(() => location.reload(), 1000);
                return;
            }

            const hours = Math.floor(secondsRemaining / 3600);
            const minutes = Math.floor((secondsRemaining % 3600) / 60);
            const seconds = secondsRemaining % 60;

            document.getElementById('countdown').textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Refresh page every 30 seconds to check if maintenance is complete
        setInterval(() => location.reload(), 30000);
        @endif

        @if($countdown && $countdown['status'] === 'active' && $countdown['end_time'])

        function updateDurationCountdown() {
            const endTime = new Date(`{{ $countdown['end_time'] }}`).getTime();
            const now = new Date().getTime();
            const secondsRemaining = Math.floor((endTime - now) / 1000);

            if (secondsRemaining <= 0) {
                document.getElementById('duration-countdown').textContent = 'Completed!';
                setTimeout(() => location.reload(), 2000);
                return;
            }

            const hours = Math.floor(secondsRemaining / 3600);
            const minutes = Math.floor((secondsRemaining % 3600) / 60);
            const seconds = secondsRemaining % 60;

            document.getElementById('duration-countdown').textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        updateDurationCountdown();
        setInterval(updateDurationCountdown, 1000);

        // Refresh page every 30 seconds to check if maintenance is complete
        setInterval(() => location.reload(), 30000);
        @endif
    </script>
</body>

</html>