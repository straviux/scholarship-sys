<template>
    <div class="h-full flex flex-col bg-white">
        <!-- Minimalist Toolbar -->
        <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-gray-200">
            <div class="flex items-center gap-3">
                <Button label="Back" icon="pi pi-arrow-left" @click="goBack" severity="secondary" text size="small" />
                <Button label="Refresh" icon="pi pi-refresh" @click="regenerate" severity="secondary" text size="small"
                    :loading="loading" />
            </div>

            <div class="flex items-center gap-6">
                <!-- Paper Settings -->
                <div class="flex items-center gap-2 text-sm">
                    <label class="text-gray-600">Paper:</label>
                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                        class="w-32" size="small" />
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label class="text-gray-600">Orientation:</label>
                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                        class="w-36" size="small" />
                </div>

                <!-- Export Buttons -->
                <div class="flex items-center gap-2">
                    <Button label="PDF" icon="pi pi-file-pdf" @click="saveAsPdf" severity="danger" outlined
                        size="small" />
                    <Button label="Excel" icon="pi pi-file-excel" @click="saveAsExcel" severity="success" outlined
                        size="small" />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto p-8 bg-gray-50">
            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center h-full">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" animationDuration="1s" />
                <p class="mt-4 text-gray-600 font-medium">Loading report...</p>
            </div>

            <!-- Report Content -->
            <div v-else class="max-w-7xl mx-auto">
                <!-- Minimalist Report Header -->
                <div class="mb-8 pb-6 border-b border-gray-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl font-light text-gray-900 mb-2">
                                {{ reportType === 'list' ? 'Waiting List' : 'Summary Report' }}
                            </h1>
                        </div>
                        <div class="text-right text-sm text-gray-500">
                            <p>{{ moment().format('MMM DD, YYYY') }}</p>
                            <p>{{ moment().format('hh:mm A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Minimalist Applied Filters -->
                <div v-if="hasActiveFilters" class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs uppercase tracking-wide text-gray-600 mb-3 font-medium">
                        Active Filters
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="(filter, key) in activeFilters" :key="key"
                            class="inline-flex items-center px-3 py-1 bg-white border border-gray-200 rounded-full text-sm text-gray-700">
                            <span class="font-medium text-gray-900">{{ key }}:</span>
                            <span class="ml-1">{{ filter }}</span>
                        </span>
                    </div>
                </div>

                <!-- Minimalist JPM Legend -->
                <div v-if="reportType === 'list' && canViewJpm"
                    class="mb-8 p-4 bg-emerald-50 rounded-lg border border-emerald-100">
                    <p class="text-xs uppercase tracking-wide text-gray-600 mb-3 font-medium">
                        Legend
                    </p>
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <div class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300"></div>
                        <span>JPM Member</span>
                    </div>
                </div>

                <!-- List Report -->
                <div v-if="reportType === 'list'">
                    <div v-for="(school, schoolIdx) in groupedBySchool" :key="schoolIdx" class="mb-12">
                        <!-- Group Header (hide if "All Records" and no grouping) -->
                        <div v-if="params.group_by && params.group_by !== 'none'"
                            class="mb-4 pb-3 border-b border-gray-300">
                            <div class="flex justify-between items-baseline">
                                <h2 class="text-xl font-medium text-gray-900">
                                    {{ school.name.toUpperCase() }}
                                </h2>
                                <span class="text-sm text-gray-500">
                                    {{ school.count }} records
                                </span>
                            </div>
                        </div>

                        <!-- Minimalist Table -->
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left font-medium text-gray-700">
                                            Name</th>
                                        <th v-if="!params.municipality && params.group_by !== 'municipality'"
                                            class="px-4 py-3 text-left font-medium text-gray-700">
                                            Address</th>
                                        <th v-if="!params.program && params.group_by !== 'program'"
                                            class="px-4 py-3 text-left font-medium text-gray-700">
                                            Program</th>
                                        <th v-if="!params.school && params.group_by !== 'school'"
                                            class="px-4 py-3 text-left font-medium text-gray-700">
                                            School</th>
                                        <th v-if="!params.courses && params.group_by !== 'course'"
                                            class="px-4 py-3 text-left font-medium text-gray-700">
                                            Course</th>
                                        <th v-if="!params.year_level && params.group_by !== 'year_level'"
                                            class="px-4 py-3 text-left font-medium text-gray-700">
                                            Year Level</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-700">
                                            Date Filed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in school.items" :key="`${schoolIdx}-${idx}`" :class="[
                                        'border-b border-gray-100 hover:bg-gray-50 transition-colors',
                                        isJpm(item) ? 'bg-emerald-50' : ''
                                    ]">
                                        <td class="px-4 py-3 text-gray-900">
                                            <div>
                                                {{ (item.last_name + ', ' + item.first_name + ' ' +
                                                    item.middle_name).toUpperCase() }}
                                            </div>
                                            <div v-if="params.show_sequence_numbers == 1"
                                                class="text-xs text-gray-500 mt-1">
                                                【Prog.<span class="font-semibold">#{{ item._sequenceProgram }}</span> |
                                                Sch.<span class="font-semibold">#{{ item._sequenceSchool }}</span> |
                                                Course<span class="font-semibold">#{{ item._sequenceCourse }}</span>】
                                            </div>
                                        </td>
                                        <td v-if="!params.municipality && params.group_by !== 'municipality'"
                                            class="px-4 py-3 text-gray-600">
                                            <span v-if="item.municipality">{{ item.municipality.toUpperCase() }}</span>
                                            <span v-if="item.municipality && item.barangay">, </span>
                                            <span v-if="item.barangay">{{ item.barangay.toUpperCase() }}</span>
                                            <span v-if="!item.municipality && !item.barangay"
                                                class="text-gray-400">-</span>
                                        </td>
                                        <td v-if="!params.program && params.group_by !== 'program'"
                                            class="px-4 py-3 text-gray-600">
                                            {{ item.scholarship_grant[0]?.program?.shortname?.toUpperCase() || '-' }}
                                        </td>
                                        <td v-if="!params.school && params.group_by !== 'school'"
                                            class="px-4 py-3 text-gray-600">
                                            {{ item.scholarship_grant[0]?.school?.shortname?.toUpperCase() || '-' }}
                                        </td>
                                        <td v-if="!params.courses && params.group_by !== 'course'"
                                            class="px-4 py-3 text-gray-900 font-medium">
                                            {{ item.scholarship_grant[0]?.course?.shortname?.toUpperCase() || '-' }}
                                        </td>
                                        <td v-if="!params.year_level && params.group_by !== 'year_level'"
                                            class="px-4 py-3 text-gray-600">
                                            {{ item.scholarship_grant[0]?.year_level?.toString().toUpperCase() || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ item.scholarship_grant[0]?.date_filed ?
                                                moment(item.scholarship_grant[0]?.date_filed).format('MMM DD, YYYY') : '-'
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Report -->
                <div v-else>
                    <!-- Total Count Card -->
                    <div class="mb-8 bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total Records</p>
                                <p class="text-4xl font-light text-gray-900">{{ report.summary?.total || 0 }}</p>
                            </div>
                            <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center">
                                <i class="pi pi-users text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Groups -->
                    <div class="grid grid-cols-1 gap-8">
                        <div v-for="(group, idx) in visibleSummaryGroups" :key="idx">
                            <div class="mb-8">
                                <!-- Minimalist Group Header -->
                                <div class="mb-4 pb-3 border-b border-gray-300 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i :class="['text-xl text-gray-600', getSummaryIcon(group.key)]"></i>
                                        <h2 class="text-xl font-medium text-gray-900">
                                            {{ group.label }}
                                        </h2>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ group.data.length }} {{ group.data.length === 1 ? 'item' : 'items' }}
                                    </span>
                                </div>

                                <!-- Minimalist Summary Table -->
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                <th class="px-4 py-3 text-left font-medium text-gray-700">
                                                    {{ group.label }}
                                                </th>
                                                <th class="px-4 py-3 text-right font-medium text-gray-700 w-32">
                                                    Count
                                                </th>
                                                <th class="px-4 py-3 text-right font-medium text-gray-700 w-32">
                                                    Percentage
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, i) in group.data" :key="i"
                                                class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-gray-900">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-sm font-bold text-gray-500 min-w-[1.5rem]">{{
                                                                    i + 1 }}.</span>
                                                        </div>
                                                        <span>{{ item.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                        {{ item.count }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-gray-600 text-xs">
                                                        {{ calculatePercentage(item.count) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot v-if="group.data.length > 1">
                                            <tr class="bg-gray-50 font-medium">
                                                <td class="px-4 py-3 text-gray-900">Total</td>
                                                <td class="px-4 py-3 text-right">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-900">
                                                        {{group.data.reduce((sum, item) => sum + item.count, 0)}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-gray-600 text-xs font-medium">100%</span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimalist Empty State -->
                <div v-if="!loading && isReportEmpty" class="text-center py-20">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="pi pi-inbox text-3xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600 text-lg mb-1">No data found</p>
                    <p class="text-gray-400 text-sm">Try adjusting your filters</p>
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
import Select from 'primevue/select';

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

// Paper Settings - get from params if available
const paperSize = ref(props.params.paper_size || 'A4');
const orientation = ref(props.params.orientation || 'landscape');

// Options for dropdowns
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal/Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

// Summary Groups Configuration
const summaryGroups = reactive([
    { key: 'by_program', label: 'Program', data: [], filterKey: 'program' },
    { key: 'by_school', label: 'School', data: [], filterKey: 'school' },
    { key: 'by_course', label: 'Course', data: [], filterKey: 'courses' },
    { key: 'by_year_level', label: 'Year Level', data: [], filterKey: 'year_level' },
]);

// Computed property to filter visible summary groups
const visibleSummaryGroups = computed(() => {
    return summaryGroups.filter(group => {
        // Hide if no data
        if (!group.data || group.data.length === 0) return false;

        // Hide if the corresponding filter is applied, BUT allow school and course to show their breakdowns
        // even when filtered (to show distribution of selected schools/courses)
        const allowedEvenWhenFiltered = ['school', 'courses'];
        if (group.filterKey && params[group.filterKey] && !allowedEvenWhenFiltered.includes(group.filterKey)) {
            return false;
        }

        // If group_by is set (and not 'none'), only show that specific group
        if (params.group_by && params.group_by !== 'none') {
            // Match group_by value to the summary group
            const groupByMapping = {
                'program': 'by_program',
                'school': 'by_school',
                'course': 'by_course',
                'year_level': 'by_year_level',
                'municipality': 'by_municipality'
            };

            const targetGroupKey = groupByMapping[params.group_by];
            if (targetGroupKey && group.key !== targetGroupKey) {
                return false; // Hide groups that don't match the group_by selection
            }
        }

        return true;
    });
});

// Computed Properties
const isReportEmpty = computed(() => {
    if (reportType.value === 'list') {
        // For list reports, check if there's data
        return !report.value.data || report.value.data.length === 0;
    } else {
        // For summary reports, check if there's a total count
        return !report.value.summary || report.value.summary.total === 0;
    }
});

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

// Helper function to pre-calculate sequence numbers for all items
const calculateSequenceNumbers = (items) => {
    const counters = {
        program: {},
        school: {},
        course: {}
    };

    items.forEach(item => {
        // Program sequence
        const programKey = item.scholarship_grant?.[0]?.program?.shortname ||
            item.scholarship_grant?.[0]?.program?.name || 'no_program';
        if (!programKey.includes('no_')) {
            if (!counters.program[programKey]) {
                counters.program[programKey] = 0;
            }
            counters.program[programKey]++;
            item._sequenceProgram = counters.program[programKey];
        } else {
            item._sequenceProgram = '-';
        }

        // School sequence
        const schoolKey = item.scholarship_grant?.[0]?.school?.shortname ||
            item.scholarship_grant?.[0]?.school?.name || 'no_school';
        if (!schoolKey.includes('no_')) {
            if (!counters.school[schoolKey]) {
                counters.school[schoolKey] = 0;
            }
            counters.school[schoolKey]++;
            item._sequenceSchool = counters.school[schoolKey];
        } else {
            item._sequenceSchool = '-';
        }

        // Course sequence
        const courseKey = item.scholarship_grant?.[0]?.course?.shortname ||
            item.scholarship_grant?.[0]?.course?.name || 'no_course';
        if (!courseKey.includes('no_')) {
            if (!counters.course[courseKey]) {
                counters.course[courseKey] = 0;
            }
            counters.course[courseKey]++;
            item._sequenceCourse = counters.course[courseKey];
        } else {
            item._sequenceCourse = '-';
        }
    });
};

// Group data dynamically based on group_by parameter
function groupData() {

    if (!report.value.data || !Array.isArray(report.value.data)) {
        groupedBySchool.value = [];
        return;
    }

    // Pre-calculate sequence numbers for all items
    calculateSequenceNumbers(report.value.data);

    const groupBy = params.group_by || 'none';

    // If no grouping, just show data sorted by date filed
    if (groupBy === 'none') {
        groupedBySchool.value = [{
            name: 'All Records',
            count: report.value.data.length,
            items: report.value.data
        }];
        return;
    }

    const grouped = {};

    report.value.data.forEach(item => {
        let groupKey;

        switch (groupBy) {
            case 'school':
                groupKey = item.scholarship_grant?.[0]?.school?.name || 'No School';
                break;
            case 'program':
                groupKey = item.scholarship_grant?.[0]?.program?.name || 'No Program';
                break;
            case 'course':
                groupKey = item.scholarship_grant?.[0]?.course?.name || 'No Course';
                break;
            case 'year_level':
                groupKey = item.scholarship_grant?.[0]?.year_level || 'No Year Level';
                break;
            case 'municipality':
                groupKey = item.municipality || 'No Municipality';
                break;
            default:
                groupKey = 'All Records';
        }

        if (!grouped[groupKey]) {
            grouped[groupKey] = [];
        }

        grouped[groupKey].push(item);
    });

    // Convert to array and sort by group name
    groupedBySchool.value = Object.keys(grouped)
        .sort()
        .map(groupName => ({
            name: groupName,
            count: grouped[groupName].length,
            items: grouped[groupName]
        }));
}

// Methods
function goBack() {
    emit('close');
}

function regenerate() {
    fetchReport();
}

// Helper function to calculate percentage
const calculatePercentage = (count) => {
    const total = report.value.summary?.total || 0;
    if (total === 0) return 0;
    return ((count / total) * 100).toFixed(1);
};

// Helper function to get icon for summary groups
const getSummaryIcon = (key) => {
    const iconMap = {
        'by_program': 'pi pi-book',
        'by_school': 'pi pi-building',
        'by_course': 'pi pi-graduation-cap',
        'by_year_level': 'pi pi-chart-bar'
    };
    return iconMap[key] || 'pi pi-chart-bar';
};

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
                groupData();
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
    const queryObj = {
        ...params,
        paper_size: paperSize.value,
        orientation: orientation.value
    };
    const query = new URLSearchParams(queryObj).toString();
    window.open(`/api/report/pdf?${query}`, '_blank');
}

function saveAsExcel() {
    const queryObj = {
        ...params,
        paper_size: paperSize.value,
        orientation: orientation.value
    };
    const query = new URLSearchParams(queryObj).toString();
    window.open(`/api/report/excel?${query}`, '_blank');
}

// Lifecycle
onMounted(() => {
    fetchReport();
});
</script>
