<template>
    <AdminLayout title="Data Export">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Data Export
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Info Section -->
                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm">
                                        Export scholarship data to JSON format for importing into a standalone
                                        application.
                                        The export includes scholarship records with their profiles, grants,
                                        requirements,
                                        approval history, and disbursements.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Export Filters</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Program Filter -->
                                <div>
                                    <label for="program" class="block text-sm font-medium text-gray-700 mb-1">
                                        Scholarship Program
                                    </label>
                                    <select id="program" v-model="filters.program_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">All Programs</option>
                                        <option v-for="program in programs" :key="program.id" :value="program.id">
                                            {{ program.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- School Filter -->
                                <div>
                                    <label for="school" class="block text-sm font-medium text-gray-700 mb-1">
                                        School
                                    </label>
                                    <select id="school" v-model="filters.school_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">All Schools</option>
                                        <option v-for="school in schools" :key="school.id" :value="school.id">
                                            {{ school.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select id="status" v-model="filters.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="declined">Declined</option>
                                        <option value="conditional">Conditional</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div>
                                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">
                                        Date From
                                    </label>
                                    <input id="date_from" v-model="filters.date_from" type="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>

                                <!-- Date To -->
                                <div>
                                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">
                                        Date To
                                    </label>
                                    <input id="date_to" v-model="filters.date_to" type="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end space-x-3">
                                <button @click="clearFilters" type="button"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Clear Filters
                                </button>
                                <button @click="loadSummary" type="button"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Preview Export
                                </button>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div v-if="summary" class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Export Summary</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white p-4 rounded-lg shadow">
                                        <div class="text-sm text-gray-500">Total Records</div>
                                        <div class="text-2xl font-bold text-gray-900">{{ summary.total_records }}</div>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg shadow">
                                        <div class="text-sm text-gray-500">Profiles Included</div>
                                        <div class="text-2xl font-bold text-gray-900">{{ summary.total_profiles }}</div>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg shadow">
                                        <div class="text-sm text-gray-500">Requirements</div>
                                        <div class="text-2xl font-bold text-gray-900">{{ summary.total_requirements }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Breakdown -->
                                <div v-if="summary.status_breakdown" class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Status Breakdown</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                        <div v-for="(count, status) in summary.status_breakdown" :key="status"
                                            class="bg-white p-3 rounded border">
                                            <div class="text-xs text-gray-500 capitalize">{{ status }}</div>
                                            <div class="text-lg font-semibold text-gray-900">{{ count }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Applied Filters -->
                                <div v-if="hasActiveFilters" class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Applied Filters</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-if="filters.program_id"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            Program: {{ getProgramName(filters.program_id) }}
                                        </span>
                                        <span v-if="filters.school_id"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            School: {{ getSchoolName(filters.school_id) }}
                                        </span>
                                        <span v-if="filters.status"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                            Status: {{ filters.status }}
                                        </span>
                                        <span v-if="filters.date_from"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            From: {{ filters.date_from }}
                                        </span>
                                        <span v-if="filters.date_to"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            To: {{ filters.date_to }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Button -->
                        <div v-if="summary" class="flex justify-end">
                            <button @click="downloadExport" :disabled="exporting || summary.total_records === 0"
                                type="button"
                                class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg v-if="exporting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="-ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download JSON Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    programs: Array,
    schools: Array,
});

const filters = ref({
    program_id: null,
    school_id: null,
    status: null,
    date_from: null,
    date_to: null,
});

const summary = ref(null);
const loading = ref(false);
const exporting = ref(false);

const hasActiveFilters = computed(() => {
    return filters.value.program_id || filters.value.school_id || filters.value.status || filters.value.date_from || filters.value.date_to;
});

const getProgramName = (id) => {
    const program = props.programs.find(p => p.id === id);
    return program ? program.name : '';
};

const getSchoolName = (id) => {
    const school = props.schools.find(s => s.id === id);
    return school ? school.name : '';
};

const clearFilters = () => {
    filters.value = {
        program_id: null,
        school_id: null,
        status: null,
        date_from: null,
        date_to: null,
    };
    summary.value = null;
};

const loadSummary = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.program_id) params.append('program_id', filters.value.program_id);
        if (filters.value.school_id) params.append('school_id', filters.value.school_id);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.date_from) params.append('date_from', filters.value.date_from);
        if (filters.value.date_to) params.append('date_to', filters.value.date_to);

        const response = await axios.get(`/admin/data-export/summary?${params.toString()}`);
        summary.value = response.data;
        toast.success('Export preview loaded successfully');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to load export summary');
    } finally {
        loading.value = false;
    }
};

const downloadExport = async () => {
    exporting.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.program_id) params.append('program_id', filters.value.program_id);
        if (filters.value.school_id) params.append('school_id', filters.value.school_id);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.date_from) params.append('date_from', filters.value.date_from);
        if (filters.value.date_to) params.append('date_to', filters.value.date_to);

        const response = await axios.get(`/admin/data-export/download?${params.toString()}`, {
            responseType: 'blob'
        });

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        // Generate filename with timestamp
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
        link.setAttribute('download', `scholarship_data_export_${timestamp}.json`);

        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        toast.success('Export downloaded successfully');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to download export');
    } finally {
        exporting.value = false;
    }
};
</script>
