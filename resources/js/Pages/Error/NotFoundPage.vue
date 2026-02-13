<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    status: {
        type: Number,
        default: 404,
    },
});
</script>

<template>
    <div
        class="w-screen h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 flex flex-col items-center justify-center px-4 py-12 fixed top-0 left-0 overflow-auto">

        <Head title="Page Not Found" />

        <!-- Main Container -->
        <div class="w-full max-w-lg">
            <!-- Error Icon/Animation -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-24 h-24 bg-yellow-100 dark:bg-yellow-900/30 rounded-full mb-6">
                    <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>

                <!-- Status Code -->
                <h1
                    class="text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-600 to-yellow-800 dark:from-yellow-400 dark:to-yellow-600 mb-2">
                    {{ status }}
                </h1>

                <!-- Title -->
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Page Not Found
                </h2>

                <!-- Description -->
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">
                    The page you're looking for doesn't exist or has been moved.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    Check the URL and try again, or navigate back to the home page.
                </p>
            </div>

            <!-- Info Card -->
            <div
                class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-6 mb-8 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            What could have happened?
                        </h3>
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-400">
                            <p>The page might have been:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Deleted or archived</li>
                                <li>Moved to a different location</li>
                                <li>Temporarily unavailable</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Link :href="route('home')"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4V3">
                        </path>
                    </svg>
                    Go to Home
                </Link>

                <button @click="window.history.back()"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-900 dark:text-white font-medium rounded-lg transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </button>
            </div>

            <!-- Support Text -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Need help?
                    <Link :href="route('help.index')"
                        class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                        Contact Support
                    </Link>
                </p>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div
            class="absolute top-0 right-0 w-96 h-96 bg-blue-200 dark:bg-blue-900/20 rounded-full mix-blend-multiply filter blur-3xl opacity-20 -z-10 pointer-events-none">
        </div>
        <div
            class="absolute -bottom-8 left-20 w-96 h-96 bg-yellow-200 dark:bg-yellow-900/20 rounded-full mix-blend-multiply filter blur-3xl opacity-20 -z-10 pointer-events-none">
        </div>
    </div>
</template>
