<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from 'primevue/card';
import { ref } from 'vue';
import { useDateUtils } from '@/composables/dateUtils.js';

const { formatDate } = useDateUtils();

const props = defineProps({
    reportData: Object
});

const downloadAsFile = () => {
    const dataStr = JSON.stringify(props.reportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `user-profile-${props.reportData.user_name}-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>

<template>

    <Head title="User Profile" />

    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">User Profile</h1>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- User Profile Header -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex items-center space-x-6">
                        <!-- Avatar -->
                        <div
                            class="w-20 h-20 px-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold text-white">
                                {{ reportData.user_name.charAt(0).toUpperCase() }}
                            </span>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 mb-1">
                                {{ reportData.user_name }}
                            </h2>
                            <p class="text-gray-600 mb-2">@{{ reportData.user_summary.basic_info.username }}</p>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Profile updated {{ formatDate(reportData.generated_at) }}
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="text-right">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-2 h-2 mr-1.5 fill-current" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Account Information Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                    <!-- Account Details Card -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Account Details</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-600 font-medium">Username</span>
                                </div>
                                <span class="text-gray-900 font-semibold">{{ reportData.user_summary.basic_info.username
                                }}</span>
                            </div>

                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0v-4h8v4z"></path>
                                    </svg>
                                    <span class="text-gray-600 font-medium">Member since</span>
                                </div>
                                <span class="text-gray-900 font-semibold">{{
                                    formatDate(reportData.user_summary.basic_info.created_at) }}</span>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600 font-medium">Last activity</span>
                                </div>
                                <span class="text-gray-900 font-semibold">{{
                                    formatDate(reportData.user_summary.basic_info.last_login) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats Card -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Quick Overview</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="text-2xl font-bold text-gray-900 mb-1">Active</div>
                                <div class="text-sm text-gray-600">Account Status</div>
                            </div>

                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="text-2xl font-bold text-indigo-600 mb-1">User</div>
                                <div class="text-sm text-gray-600">Access Level</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Encoding Statistics Dashboard -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">My Encoding Statistics</h3>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Total Encoded -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-blue-700 mb-1">
                                {{ reportData.user_summary.encoding_statistics.summary.total_encoded }}
                            </div>
                            <div class="text-sm text-blue-600 font-medium">Total Applications Encoded</div>
                        </div>

                        <!-- Recent Activity -->
                        <div
                            class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-green-700 mb-1">
                                {{ reportData.user_summary.encoding_statistics.summary.recent_activity }}
                            </div>
                            <div class="text-sm text-green-600 font-medium">This Month</div>
                        </div>
                    </div>

                    <!-- Activity Summary -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-6">Activity Summary</h4>

                        <!-- Created vs Updated Applications -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Applications Created -->
                            <div class="bg-white rounded-lg p-6 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                        <h5 class="text-lg font-semibold text-gray-900">Created</h5>
                                    </div>
                                    <span class="text-2xl font-bold text-blue-600">
                                        {{ reportData.user_summary.encoding_statistics.applications.total_created }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">New scholarship applications you've encoded</p>
                            </div>

                            <!-- Applications Updated -->
                            <div class="bg-white rounded-lg p-6 border-l-4 border-green-500">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </div>
                                        <h5 class="text-lg font-semibold text-gray-900">Updated</h5>
                                    </div>
                                    <span class="text-2xl font-bold text-green-600">
                                        {{ reportData.user_summary.encoding_statistics.applications.total_updated }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">Existing applications you've modified</p>
                            </div>
                        </div>

                        <!-- Additional Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Recent Activity -->
                            <div class="bg-white rounded-lg p-4 text-center">
                                <div
                                    class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-xl font-bold text-purple-600 mb-1">
                                    {{ reportData.user_summary.encoding_statistics.applications.recent_created }}
                                </div>
                                <div class="text-sm text-gray-600">This Month Activity</div>
                            </div>

                            <!-- First Encoding -->
                            <div class="bg-white rounded-lg p-4 text-center">
                                <div
                                    class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0v-4h8v4z"></path>
                                    </svg>
                                </div>
                                <div v-if="reportData.user_summary.encoding_statistics.first_encoding_date"
                                    class="text-sm font-semibold text-indigo-600 mb-1">
                                    {{ formatDate(reportData.user_summary.encoding_statistics.first_encoding_date) }}
                                </div>
                                <div v-else class="text-sm font-semibold text-gray-500 mb-1">Not Started</div>
                                <div class="text-sm text-gray-600">First Encoding</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Breakdown Statistics -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Application Breakdown</h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- By Program -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <div class="w-6 h-6 bg-blue-500 rounded-md flex items-center justify-center mr-2">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                </div>
                                By Program
                            </h4>
                            <div v-if="reportData.user_summary.encoding_statistics.breakdowns.by_program.length > 0"
                                class="space-y-3 max-h-60 overflow-y-auto">
                                <div v-for="item in reportData.user_summary.encoding_statistics.breakdowns.by_program"
                                    :key="item.program_name"
                                    class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full bg-blue-500 mr-3"></div>
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ item.program_name }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-blue-600">{{ item.count }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-4">
                                No applications encoded yet
                            </div>
                        </div>

                        <!-- By Course -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center mr-2">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253">
                                        </path>
                                    </svg>
                                </div>
                                By Course
                            </h4>
                            <div v-if="reportData.user_summary.encoding_statistics.breakdowns.by_course.length > 0"
                                class="space-y-3 max-h-60 overflow-y-auto">
                                <div v-for="item in reportData.user_summary.encoding_statistics.breakdowns.by_course"
                                    :key="item.course_name"
                                    class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full bg-green-500 mr-3"></div>
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ item.course_name }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-green-600">{{ item.count }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-4">
                                No applications encoded yet
                            </div>
                        </div>

                        <!-- By School -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <div class="w-6 h-6 bg-purple-500 rounded-md flex items-center justify-center mr-2">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                        </path>
                                    </svg>
                                </div>
                                By School
                            </h4>
                            <div v-if="reportData.user_summary.encoding_statistics.breakdowns.by_school.length > 0"
                                class="space-y-3 max-h-60 overflow-y-auto">
                                <div v-for="item in reportData.user_summary.encoding_statistics.breakdowns.by_school"
                                    :key="item.school_name"
                                    class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full bg-purple-500 mr-3"></div>
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ item.school_name }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-purple-600">{{ item.count }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-4">
                                No applications encoded yet
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Statistics (Admin/Moderator Only) -->
                <div v-if="reportData.user_summary.privileged_access"
                    class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">System Analytics</h3>
                        <span
                            class="ml-2 px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Admin
                            View</span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                        <div
                            class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253">
                                    </path>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-blue-600 mb-1">
                                {{ reportData.user_summary.privileged_access.total_scholarship_records }}
                            </div>
                            <div class="text-sm text-blue-700 font-medium">Scholarship Records</div>
                        </div>

                        <div
                            class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
                            <div
                                class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-green-600 mb-1">
                                {{ reportData.user_summary.privileged_access.total_profiles }}
                            </div>
                            <div class="text-sm text-green-700 font-medium">User Profiles</div>
                        </div>

                        <div
                            class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                            <div
                                class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-purple-600 mb-1">
                                {{ reportData.user_summary.privileged_access.total_programs }}
                            </div>
                            <div class="text-sm text-purple-700 font-medium">Active Programs</div>
                        </div>

                        <div
                            class="text-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200">
                            <div
                                class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                                    </path>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-orange-600 mb-1">
                                {{ reportData.user_summary.privileged_access.system_users }}
                            </div>
                            <div class="text-sm text-orange-700 font-medium">System Users</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}

code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}
</style>