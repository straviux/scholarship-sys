<template>
    <AdminLayout>
        <AdminPageShell title="Data Export"
            description="Preview and export scholarship records, related profiles, requirements, and approval history into a portable JSON package."
            icon="download" eyebrow="Admin Utilities">
            <template #meta>
                <span>{{ summary ? `${summary.total_records} records ready` : 'Preview the dataset before export'
                    }}</span>
            </template>

            <section class="ios-section">
                <div class="ios-section-label">Export Controls</div>
                <div class="max-w-7xl">
                    <div class="ios-card overflow-hidden">
                        <div class="p-4 short:p-3 bg-white border-b border-gray-200">
                            <!-- Info Section -->
                            <div class="mb-4 short:mb-2 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <AppIcon name="info-circle" class="text-blue-400" />
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

                            <div v-if="canImportJpmCsv" class="mb-6 short:mb-4 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="max-w-2xl">
                                        <h3 class="text-lg font-medium text-gray-900">Import JPM CSV</h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            Upload a CSV to update scholarship profile JPM tags by
                                            <span class="font-semibold text-gray-900">unique_id</span>.
                                            If all four JPM member columns are false or blank, the matched profile is
                                            automatically tagged as
                                            <span class="font-semibold text-gray-900">is_not_jpm</span>.
                                        </p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <span v-for="column in expectedJpmColumns" :key="column"
                                                class="inline-flex items-center rounded-full bg-white px-3 py-1 font-mono text-xs text-gray-700 ring-1 ring-emerald-200">
                                                {{ column }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="w-full max-w-md rounded-lg border border-emerald-100 bg-white p-4 shadow-sm">
                                        <label for="jpm_csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                                            CSV File
                                        </label>
                                        <input id="jpm_csv_file" ref="csvFileInput" type="file" accept=".csv,text/csv"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700"
                                            @change="handleCsvFileChange" />
                                        <p class="mt-2 text-xs text-gray-500">
                                            Accepts `.csv` files with the exact header names shown above.
                                        </p>

                                        <div v-if="selectedCsvFileName" class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                            Selected: <span class="font-medium">{{ selectedCsvFileName }}</span>
                                        </div>

                                        <div class="mt-4 flex justify-end gap-3">
                                            <Button label="Clear File" severity="secondary" outlined
                                                :disabled="!selectedCsvFileName || importingJpmCsv"
                                                @click="clearCsvSelection" />
                                            <AppButton label="Import JPM CSV" icon="upload" :loading="importingJpmCsv"
                                                :disabled="!selectedCsvFileName"
                                                @click="importJpmCsv" />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="jpmImportSummary" class="mt-4 rounded-lg border border-emerald-200 bg-white p-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Last Import Summary</h4>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <div class="text-xs uppercase tracking-wide text-gray-500">Processed</div>
                                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ jpmImportSummary.processed_rows }}</div>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <div class="text-xs uppercase tracking-wide text-gray-500">Matched</div>
                                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ jpmImportSummary.matched_profiles }}</div>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <div class="text-xs uppercase tracking-wide text-gray-500">Updated</div>
                                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ jpmImportSummary.updated_profiles }}</div>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <div class="text-xs uppercase tracking-wide text-gray-500">Missing IDs</div>
                                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ jpmImportSummary.missing_profiles }}</div>
                                        </div>
                                    </div>

                                    <div v-if="jpmImportSummary.missing_unique_ids?.length" class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                        Missing unique IDs: {{ formatMissingUniqueIds(jpmImportSummary.missing_unique_ids) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Section -->
                            <div class="mb-4 short:mb-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Export Filters</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Program Filter -->
                                    <div>
                                        <label for="program" class="block text-sm font-medium text-gray-700 mb-1">
                                            Scholarship Program
                                        </label>
                                        <Select id="program" v-model="filters.program_id"
                                            :options="[{ id: null, name: 'All Programs' }, ...programs]"
                                            optionLabel="name" optionValue="id" placeholder="All Programs"
                                            class="w-full" />
                                    </div>

                                    <!-- School Filter -->
                                    <div>
                                        <label for="school" class="block text-sm font-medium text-gray-700 mb-1">
                                            School
                                        </label>
                                        <Select id="school" v-model="filters.school_id"
                                            :options="[{ id: null, name: 'All Schools' }, ...schools]"
                                            optionLabel="name" optionValue="id" placeholder="All Schools"
                                            class="w-full" />
                                    </div>

                                    <!-- Status Filter -->
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                            Status
                                        </label>
                                        <Select id="status" v-model="filters.status"
                                            :options="[{ value: null, label: 'All Statuses' }, { value: 'pending', label: 'Pending' }, { value: 'interviewed', label: 'Interviewed' }, { value: 'active', label: 'Active' }, { value: 'denied', label: 'Denied' }, { value: 'completed', label: 'Completed' }, { value: 'withdrawn', label: 'Withdrawn' }, { value: 'loa', label: 'Leave of Absence' }, { value: 'suspended', label: 'Suspended' }]"
                                            optionLabel="label" optionValue="value" placeholder="All Statuses"
                                            class="w-full" />
                                    </div>

                                    <!-- Date From -->
                                    <div>
                                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">
                                            Date From
                                        </label>
                                        <InputText id="date_from" v-model="filters.date_from" type="date"
                                            class="w-full" />
                                    </div>

                                    <!-- Date To -->
                                    <div>
                                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">
                                            Date To
                                        </label>
                                        <InputText id="date_to" v-model="filters.date_to" type="date" class="w-full" />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end space-x-3">
                                    <Button label="Clear Filters" severity="secondary" outlined @click="clearFilters" />
                                    <AppButton label="Preview Export" icon="search" :loading="loading"
                                        @click="loadSummary" />
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div v-if="summary" class="mb-4 short:mb-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Export Summary</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-sm text-gray-500">Total Records</div>
                                            <div class="text-2xl short:text-xl font-bold text-gray-900">{{
                                                summary.total_records }}
                                            </div>
                                        </div>
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-sm text-gray-500">Profiles Included</div>
                                            <div class="text-2xl short:text-xl font-bold text-gray-900">{{
                                                summary.total_profiles }}
                                            </div>
                                        </div>
                                        <div class="bg-white p-4 rounded-lg shadow">
                                            <div class="text-sm text-gray-500">Requirements</div>
                                            <div class="text-2xl short:text-xl font-bold text-gray-900">{{
                                                summary.total_requirements }}
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
                                <AppButton label="Download JSON Export" icon="download" severity="success"
                                    :loading="exporting" :disabled="summary.total_records === 0"
                                    @click="downloadExport" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </AdminPageShell>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageShell from '@/Components/admin/AdminPageShell.vue';
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    programs: Array,
    schools: Array,
    canImportJpmCsv: Boolean,
});

const expectedJpmColumns = [
    'unique_id',
    'is_jpm_member',
    'is_father_jpm',
    'is_mother_jpm',
    'is_guardian_jpm',
    'jpm_remarks',
];

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
const csvFileInput = ref(null);
const selectedCsvFile = ref(null);
const selectedCsvFileName = ref('');
const importingJpmCsv = ref(false);
const jpmImportSummary = ref(null);

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

const handleCsvFileChange = (event) => {
    const [file] = event.target.files || [];
    selectedCsvFile.value = file || null;
    selectedCsvFileName.value = file?.name || '';
};

const clearCsvSelection = () => {
    selectedCsvFile.value = null;
    selectedCsvFileName.value = '';

    if (csvFileInput.value) {
        csvFileInput.value.value = '';
    }
};

const formatImportError = (error) => {
    const csvErrors = error.response?.data?.errors?.csv_file;

    if (Array.isArray(csvErrors) && csvErrors.length > 0) {
        return csvErrors[0];
    }

    return error.response?.data?.message || 'Failed to import JPM CSV';
};

const formatMissingUniqueIds = (missingUniqueIds) => {
    if (!Array.isArray(missingUniqueIds) || missingUniqueIds.length === 0) {
        return '';
    }

    const preview = missingUniqueIds.slice(0, 10).join(', ');

    if (missingUniqueIds.length <= 10) {
        return preview;
    }

    return `${preview}, and ${missingUniqueIds.length - 10} more`;
};

const importJpmCsv = async () => {
    if (!selectedCsvFile.value) {
        toast.error('Select a CSV file first');
        return;
    }

    importingJpmCsv.value = true;

    try {
        const formData = new FormData();
        formData.append('csv_file', selectedCsvFile.value);

        const response = await axios.post('/admin/data-export/import-jpm-csv', formData, {
            headers: {
                Accept: 'application/json',
            },
        });

        jpmImportSummary.value = response.data.summary;
        toast.success(response.data.message || 'JPM CSV import completed successfully');
        clearCsvSelection();
    } catch (error) {
        toast.error(formatImportError(error));
    } finally {
        importingJpmCsv.value = false;
    }
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
