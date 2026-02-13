<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    status: {
        type: Number,
        default: 403,
    },
});
</script>

<template>
    <div
        class="w-screen h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 flex flex-col items-center justify-center px-4 py-12 fixed top-0 left-0 overflow-auto">

        <Head title="Access Forbidden" />

        <!-- Main Container -->
        <div class="w-full max-w-lg">
            <!-- Error Icon/Animation -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-24 h-24 bg-red-100 dark:bg-red-900/30 rounded-full mb-6">
                    <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4v2m0 0a9 9 0 11-9-9m9 9a9 9 0 1-9 9m2.25-9a6.75 6.75 0 1113.5 0H12z"></path>
                    </svg>
                </div>

                <!-- Status Code -->
                <h1
                    class="text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-red-800 dark:from-red-400 dark:to-red-600 mb-2">
                    {{ status }}
                </h1>

                <!-- Title -->
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Access Forbidden
                </h2>

                <!-- Description -->
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">
                    You don't have permission to access this resource.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    If you believe this is an error, please contact your system administrator.
                </p>
            </div>

            <!-- Info Card -->
            <div
                class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-6 mb-8 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 8a1 1 0 000 2h6a1 1 0 000-2H8zm0 4a1 1 0 000 2h3a1 1 0 000-2H8z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            What does this mean?
                        </h3>
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-400">
                            <p>You lack the necessary permissions to view or interact with this page. This could be due
                                to:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Insufficient user role permissions</li>
                                <li>Resource access restrictions</li>
                                <li>Expired or invalid access credentials</li>
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
            class="absolute -bottom-8 left-20 w-96 h-96 bg-red-200 dark:bg-red-900/20 rounded-full mix-blend-multiply filter blur-3xl opacity-20 -z-10 pointer-events-none">
        </div>
    </div>
</template>
