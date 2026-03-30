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
        class="w-screen h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 flex flex-col items-center justify-center px-4 py-8 short:py-4 fixed top-0 left-0 overflow-auto">

        <Head title="Page Not Found" />

        <!-- Main Container -->
        <div class="w-full max-w-lg">
            <!-- Error Icon/Animation -->
            <div class="text-center mb-6 short:mb-3">
                <div
                    class="inline-flex items-center justify-center w-24 short:w-16 h-24 short:h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full mb-4 short:mb-2">
                    <i class="pi pi-face-smile text-yellow-600 dark:text-yellow-400 text-[3rem] short:text-[2rem]"></i>
                </div>

                <!-- Status Code -->
                <h1
                    class="text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-600 to-yellow-800 dark:from-yellow-400 dark:to-yellow-600 mb-2">
                    {{ status }}
                </h1>

                <!-- Title -->
                <h2 class="text-3xl short:text-xl font-bold text-gray-900 dark:text-white mb-4">
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
                class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4 short:p-3 mb-6 short:mb-3 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="pi pi-exclamation-triangle h-5 w-5 text-amber-500"></i>
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
                    <i class="pi pi-home w-5 h-5 mr-2"></i>
                    Go to Home
                </Link>

                <Button @click="window.history.back()" label="Go Back" icon="pi pi-arrow-left" severity="secondary" />
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
