<template>
    <div class="h-full flex flex-col bg-gray-100">
        <!-- Toolbar -->
        <div class="bg-white border-b border-gray-300 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Button label="Back" icon="pi pi-arrow-left" @click="goBack" severity="secondary" outlined
                    size="small" />
                <Button label="Refresh" icon="pi pi-refresh" @click="regenerate" severity="info" outlined size="small"
                    :loading="loading" />
            </div>

            <div class="flex items-center gap-4">
                <!-- Export Buttons -->
                <div class="flex items-center gap-2">
                    <Button label="PDF" icon="pi pi-file-pdf" @click="saveAsPdf" severity="danger" size="small" />
                    <Button label="Excel" icon="pi pi-file-excel" @click="saveAsExcel" severity="success"
                        size="small" />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto p-4 bg-white">
            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center h-full">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" animationDuration="1s" />
                <p class="mt-4 text-gray-600 font-medium">Loading report...</p>
            </div>

            <!-- Report Content -->
            <div v-else>
                <!-- Grand Total Header -->
                <div class="mb-4 p-4 bg-blue-700 text-white rounded-lg shadow-md">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold">
                            {{ reportType === 'list' ? 'WAITING LIST REPORT' : 'SUMMARY REPORT' }}
                        </h2>
                        <div class="text-right">
                            <p class="text-sm font-medium">GRAND TOTAL</p>
                            <p class="text-2xl font-bold">{{ report.count || 0 }} RECORD(S)</p>
                        </div>
                    </div>
                </div>

                <!-- Report Info -->
                <div class="mb-4 pb-3 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">
                                {{ reportType === 'list' ? 'Applicants List' : 'Summary Report' }}
                            </h2>
                            <p class="text-sm text-gray-600">
                                Total: {{ report.count || 0 }} record(s)
                            </p>
                        </div>
                        <div class="text-sm text-gray-600 text-right">
                            <p>{{ moment().format('MMM DD, YYYY') }}</p>
                            <p>{{ moment().format('hh:mm A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Applied Filters -->
                <div v-if="hasActiveFilters" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Applied Filters:</p>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p v-for="(filter, key) in activeFilters" :key="key">
                            <span class="font-medium">{{ key }}:</span> {{ filter }}
                        </p>
                    </div>
                </div>

                <!-- JPM Legend (only show if user has permission) -->
                <div v-if="reportType === 'list' && canViewJpm"
                    class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Legend:</p>
                    <div class="flex items-center gap-2 text-xs">
                        <div class="w-4 h-4 rounded" style="background-color: #d1fae5; border: 1px solid #10b981;">
                        </div>
                        <span class="text-gray-600">JPM Member (Applicant/Parent/Guardian)</span>
                    </div>
                </div>

                <!-- List Report -->
                <div v-if="reportType === 'list'">
                    <div v-for="(school, schoolIdx) in groupedBySchool" :key="schoolIdx" class="mb-6">
                        <!-- School Header -->
                        <div class="bg-sky-100 border-2 border-sky-600 px-4 py-3 mb-0 rounded-t-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-sky-900 text-base">{{ school.name.toUpperCase()
                                }}</span>
                                <span class="text-sm text-sky-700 font-medium">Total: {{ school.count }}
                                    record(s)</span>
                            </div>
                        </div>

                        <!-- School Table -->
                        <table class="w-full border-collapse border border-gray-300 text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Name</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Address</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Program</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        School</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Course</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Year Level</th>
                                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                        Date Filed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in school.items" :key="`${schoolIdx}-${idx}`"
                                    :style="isJpm(item) ? 'background-color: #d1fae5;' : ''" class="hover:bg-gray-100">
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ (item.last_name + ', ' + item.first_name + ' ' +
                                            item.middle_name).toUpperCase() }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        <span v-if="item.municipality">{{ item.municipality.toUpperCase() }}</span>
                                        <span v-if="item.municipality && item.barangay">, </span>
                                        <span v-if="item.barangay">{{ item.barangay.toUpperCase() }}</span>
                                        <span v-if="!item.municipality && !item.barangay" class="text-gray-400">-</span>
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ item.scholarship_grant[0]?.program?.shortname?.toUpperCase() || '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ item.scholarship_grant[0]?.school?.shortname?.toUpperCase() || '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2 font-semibold">
                                        {{ item.scholarship_grant[0]?.course?.shortname?.toUpperCase() || '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ item.scholarship_grant[0]?.year_level?.toString().toUpperCase() || '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2">
                                        {{ item.scholarship_grant[0]?.date_filed ?
                                            moment(item.scholarship_grant[0]?.date_filed).format('MMM DD, YYYY') : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Report -->
                <div v-else>
                    <div v-for="(group, idx) in summaryGroups" :key="idx">
                        <div v-if="group.label && group.data.length" class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                {{ group.label }} ({{ group.data.length }} items)
                            </h3>
                            <table class="w-full border-collapse border border-gray-300 text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th
                                            class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                            {{ group.label }}
                                        </th>
                                        <th
                                            class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700 w-24">
                                            Count
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, i) in group.data" :key="i" class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-3 py-2">{{ item.name }}</td>
                                        <td class="border border-gray-300 px-3 py-2 text-center font-semibold">
                                            {{ item.count }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && (!report.data || report.data.length === 0)" class="text-center py-12">
                    <p class="text-gray-500">No data found matching the selected filters.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import axios from 'axios';
import moment from 'moment';

// PrimeVue Components
import ProgressSpinner from 'primevue/progressspinner';

const props = defineProps({
    params: { type: Object, required: true },
});

const emit = defineEmits(['close']);

// State Management
const loading = ref(true);
const report = ref({});
const reportType = ref(props.params.report_type || 'list');
const params = reactive({ ...props.params });
const canViewJpm = ref(false);
const groupedBySchool = ref([]);

// Summary Groups Configuration
const summaryGroups = reactive([
    { key: 'by_program', label: 'Program', data: [] },
    { key: 'by_school', label: 'School', data: [] },
    { key: 'by_course', label: 'Course', data: [] },
    { key: 'by_year_level', label: 'Year Level', data: [] },
]);

// Computed Properties
const hasActiveFilters = computed(() => {
    return params.date_from || params.date_to || params.program ||
        params.school || params.courses || params.municipality || params.year_level;
});

const activeFilters = computed(() => {
    const filters = {};
    if (params.date_from || params.date_to) {
        const dateRange = params.date_from && params.date_to
            ? `${moment(params.date_from).format('MMM DD, YYYY')} - ${moment(params.date_to).format('MMM DD, YYYY')}`
            : params.date_from
                ? `From ${moment(params.date_from).format('MMM DD, YYYY')}`
                : `Until ${moment(params.date_to).format('MMM DD, YYYY')}`;
        filters['Date Range'] = dateRange;
    }
    if (params.program) filters['Program'] = params.program;
    if (params.school) filters['School'] = params.school;
    if (params.courses) filters['Courses'] = params.courses;
    if (params.municipality) filters['Municipality'] = params.municipality;
    if (params.year_level) filters['Year Level'] = params.year_level;
    return filters;
});

// Helper function to check if JPM (only if user has permission)
const isJpm = (item) => {
    return canViewJpm.value && (item.is_jpm_member || item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm);
};

// Group data by school
function groupDataBySchool() {
    if (!report.value.data || !Array.isArray(report.value.data)) {
        groupedBySchool.value = [];
        return;
    }

    const grouped = {};

    report.value.data.forEach(item => {
        const schoolName = item.scholarship_grant?.[0]?.school?.name || 'No School';

        if (!grouped[schoolName]) {
            grouped[schoolName] = [];
        }

        grouped[schoolName].push(item);
    });

    // Convert to array and sort by school name
    groupedBySchool.value = Object.keys(grouped)
        .sort()
        .map(schoolName => ({
            name: schoolName,
            count: grouped[schoolName].length,
            items: grouped[schoolName]
        }));
}

// Methods
function goBack() {
    emit('close');
}

function regenerate() {
    fetchReport();
}

function fetchReport() {
    loading.value = true;

    axios.get('/profiles/generate-report', { params })
        .then(res => {
            report.value = res.data;
            canViewJpm.value = res.data.canViewJpm || false;

            // Sort list report data by date filed (oldest first)
            if (reportType.value === 'list' && res.data.data && Array.isArray(res.data.data)) {
                report.value.data = res.data.data.sort((a, b) => {
                    const dateA = a.scholarship_grant?.[0]?.date_filed;
                    const dateB = b.scholarship_grant?.[0]?.date_filed;

                    // Handle null/undefined dates - put them at the end
                    if (!dateA && !dateB) return 0;
                    if (!dateA) return 1;
                    if (!dateB) return -1;

                    // Sort ascending (oldest first)
                    return moment(dateA).valueOf() - moment(dateB).valueOf();
                });

                // Group by school for display
                groupDataBySchool();
            }

            // Process summary data if available
            if (reportType.value === 'summary' && res.data.summary) {
                summaryGroups.forEach(group => {
                    group.data = [];
                    if (res.data.summary[group.key]) {
                        // Convert to array format if not already
                        if (Array.isArray(res.data.summary[group.key])) {
                            group.data = res.data.summary[group.key];
                        } else {
                            group.data = Object.entries(res.data.summary[group.key])
                                .map(([name, count]) => ({ name, count }))
                                .sort((a, b) => b.count - a.count); // Sort by count descending
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error fetching report:', error);
            // You could add a toast notification here
        })
        .finally(() => {
            loading.value = false;
        });
}

function saveAsPdf() {
    const queryObj = { ...params };
    const query = new URLSearchParams(queryObj).toString();
    window.open(`/api/report/pdf?${query}`, '_blank');
}

function saveAsExcel() {
    const queryObj = { ...params };
    const query = new URLSearchParams(queryObj).toString();
    window.open(`/api/report/excel?${query}`, '_blank');
}

function getSummaryIcon(key) {
    const icons = {
        'by_program': 'pi pi-book',
        'by_school': 'pi pi-building',
        'by_course': 'pi pi-list',
        'by_year_level': 'pi pi-chart-line',
    };
    return icons[key] || 'pi pi-info-circle';
}

// Lifecycle
onMounted(() => {
    fetchReport();
});
</script>
