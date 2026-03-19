<template>
    <div class="ios-report-root">
        <!-- Content Area -->
        <div class="ios-report-scroll pt-4">
            <!-- Loading State -->
            <div v-if="loading" class="ios-loading-state">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" animationDuration="1s" />
                <p>Loading report...</p>
            </div>

            <!-- Report Content -->
            <div v-else class="max-w-7xl mx-auto">
                <!-- Report Header -->
                <div class="mb-4 pb-3 border-b border-gray-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900 mb-0.5">
                                {{ reportType === 'list' ? 'Scholarship Profiles' : 'Summary Report' }}
                            </h1>
                            <p class="text-[11px] text-gray-500">
                                Total Records: <strong>{{ totalRecords }}</strong>
                            </p>
                        </div>
                        <div class="text-right text-[11px] text-gray-500">
                            <p>{{ moment().format('MMM DD, YYYY') }}</p>
                            <p>{{ moment().format('hh:mm A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Applied Filters -->
                <div v-if="hasActiveFilters" class="mb-4 px-3 py-2 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-[10px] uppercase tracking-wide text-gray-600 mb-1.5 font-medium">
                        Active Filters
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(filter, key) in activeFilters" :key="key"
                            class="inline-flex items-center px-2 py-0.5 bg-white border border-gray-200 rounded-full text-[11px] text-gray-700">
                            <span class="font-medium text-gray-900">{{ key }}:</span>
                            <span class="ml-1">{{ filter }}</span>
                        </span>
                    </div>
                </div>

                <!-- List Report -->
                <div v-if="reportType === 'list' && records.length > 0">
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-[11px]">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">#</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">Name</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">Contact
                                        No(s).</th>
                                    <th v-if="!params.municipality"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">
                                        Municipality</th>
                                    <th v-if="!params.program"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">
                                        Program</th>
                                    <th v-if="!params.school"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">
                                        School</th>
                                    <th v-if="!params.course && !params.courses"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">
                                        Course</th>
                                    <th v-if="!params.year_level"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">
                                        Level</th>
                                    <th v-if="!params.unified_status"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">Status
                                    </th>
                                    <th v-if="!params.grant_provision && isStatusActive"
                                        class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">Grant
                                        Provision</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-gray-700 text-[10px]">{{
                                        isShowingApproved ?
                                        'Date Approved' : 'Date Filed' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in records" :key="idx" :class="[
                                    'border-b border-gray-100 hover:bg-gray-50 transition-colors',
                                    isJpm(item) && params.enable_jpm_highlighting == 1 ? 'bg-emerald-50' : ''
                                ]">
                                    <td class="px-2 py-1 text-gray-600">{{ idx + 1 }}</td>
                                    <td class="px-2 py-1 text-gray-900 font-medium whitespace-nowrap">
                                        {{ formatName(item) }}
                                    </td>
                                    <td class="px-2 py-1 text-gray-600">
                                        {{ item.contact_no || '-' }}
                                    </td>
                                    <td v-if="!params.municipality" class="px-2 py-1 text-gray-600">
                                        {{ item.municipality || '-' }}
                                    </td>
                                    <td v-if="!params.program" class="px-2 py-1 text-gray-600">
                                        {{ item.program_name || '-' }}
                                    </td>
                                    <td v-if="!params.school" class="px-2 py-1 text-gray-600">
                                        {{ item.school_name || '-' }}
                                    </td>
                                    <td v-if="!params.course && !params.courses" class="px-2 py-1 text-gray-600">
                                        {{ item.course_name || '-' }}
                                    </td>
                                    <td v-if="!params.year_level" class="px-2 py-1 text-gray-600">
                                        {{ item.year_level || '-' }}
                                    </td>
                                    <td v-if="!params.unified_status" class="px-2 py-1">
                                        <Tag :value="formatApprovalStatus(item)" :severity="getStatusSeverity(item)" />
                                    </td>
                                    <td v-if="!params.grant_provision && isStatusActive"
                                        class="px-2 py-1 text-gray-600">
                                        {{ formatGrantProvision(item) }}
                                    </td>
                                    <td class="px-2 py-1 text-gray-600 whitespace-nowrap">
                                        {{ formatDate(isShowingApproved ? item.date_approved : item.date_filed) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Report -->
                <div v-if="reportType === 'summary'">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h3 class="text-[10px] uppercase tracking-wide text-gray-600 mb-1">Total Scholars</h3>
                            <p class="text-2xl font-light text-gray-900">{{ totalRecords }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h3 class="text-[10px] uppercase tracking-wide text-gray-600 mb-1">Programs</h3>
                            <p class="text-2xl font-light text-gray-900">{{ summaryData.programs }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h3 class="text-[10px] uppercase tracking-wide text-gray-600 mb-1">Schools</h3>
                            <p class="text-2xl font-light text-gray-900">{{ summaryData.schools }}</p>
                        </div>
                    </div>
                </div>

                <!-- No Records -->
                <div v-if="records.length === 0" class="text-center py-8 text-gray-500">
                    <i class="pi pi-inbox text-4xl mb-3"></i>
                    <p class="text-sm">No records found matching the selected filters.</p>
                </div>

                <!-- Bottom spacer -->
                <div class="pb-12"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    params: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close']);

// State
const loading = ref(true);
const records = ref([]);
const summaryData = ref({ programs: 0, schools: 0 });
const programNameCache = ref(null);
const paperSize = ref(props.params.paper_size || 'A4');
const orientation = ref(props.params.orientation || 'landscape');
const reportType = computed(() => props.params.report_type || 'list');
const totalRecords = computed(() => records.value.length);

// Options
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

// Computed
const hasActiveFilters = computed(() => {
    return Object.keys(activeFilters.value).length > 0;
});

const activeFilters = computed(() => {
    const filters = {};
    if (props.params.date_from) filters['Date From'] = moment(props.params.date_from).format('MMM DD, YYYY');
    if (props.params.date_to) filters['Date To'] = moment(props.params.date_to).format('MMM DD, YYYY');
    if (props.params.unified_status) filters['Status'] = formatUnifiedStatusText(props.params.unified_status);

    // Display program name instead of ID
    if (props.params.program) {
        if (is_numeric(props.params.program)) {
            // If program is numeric (ID), use cached program name from first record
            const programName = programNameCache.value || (records.value.length > 0 ? records.value[0].program_name : null);
            filters['Program'] = programName || `Program #${props.params.program}`;
        } else {
            filters['Program'] = props.params.program;
        }
    }

    if (props.params.school) filters['School'] = props.params.school;
    if (props.params.courses) filters['Course'] = props.params.courses;
    if (props.params.municipality) filters['Municipality'] = props.params.municipality;
    if (props.params.year_level) filters['Year Level'] = props.params.year_level;
    if (props.params.grant_provision) filters['Grant'] = formatGrantProvisionText(props.params.grant_provision);
    return filters;
});

// Determine if showing approved/active status (for Date Approved column header)
const isShowingApproved = computed(() => {
    if (!props.params.unified_status) return false;
    const status = props.params.unified_status;
    const statusArray = Array.isArray(status) ? status : [status];
    return statusArray.includes('approved') || statusArray.includes('active');
});

// Check if status filter is specifically "active"
const isStatusActive = computed(() => {
    if (!props.params.unified_status) return false;
    const status = props.params.unified_status;
    const statusArray = Array.isArray(status) ? status : [status];
    return statusArray.includes('active') && statusArray.length === 1;
});

// Methods
function goBack() {
    emit('close');
}

function regenerate() {
    loadData();
}

async function loadData() {
    loading.value = true;
    try {
        // Map filter parameters to API endpoint format
        const queryParams = {};

        // Map unified_status to status
        if (props.params.unified_status) {
            queryParams.status = props.params.unified_status;
        }

        // Map program name to program_id if available
        if (props.params.program) {
            // Try to find program by name
            queryParams.program = props.params.program;
        }

        // Map school name to school_id if available
        if (props.params.school) {
            queryParams.school = props.params.school;
        }

        // Map course
        if (props.params.courses) {
            queryParams.course = props.params.courses;
        }

        // Map other filters
        if (props.params.date_from) {
            queryParams.date_from = props.params.date_from;
        }

        if (props.params.date_to) {
            queryParams.date_to = props.params.date_to;
        }

        if (props.params.year_level) {
            queryParams.year_level = props.params.year_level;
        }

        if (props.params.municipality) {
            queryParams.municipality = props.params.municipality;
        }

        if (props.params.grant_provision) {
            queryParams.grant_provision = props.params.grant_provision;
        }

        console.log('Loading report with params:', queryParams);

        const response = await axios.get(route('data-export.download'), {
            params: queryParams
        });

        console.log('Report response:', response.data);

        // Extract records from response - backend returns { scholars, applicants, metadata }
        let data = [];
        if (response.data && Array.isArray(response.data)) {
            // Direct array response
            data = response.data;
        } else if (response.data.scholars && Array.isArray(response.data.scholars)) {
            // Backend returns { scholars, applicants, metadata }
            data = response.data.scholars;
        } else if (response.data.applicants && Array.isArray(response.data.applicants)) {
            // Use applicants if scholars not available
            data = response.data.applicants;
        } else if (response.data.data && Array.isArray(response.data.data)) {
            data = response.data.data;
        } else if (response.data.profiles && Array.isArray(response.data.profiles)) {
            data = response.data.profiles;
        }

        console.log('Extracted data:', data);
        records.value = data || [];

        // Cache the program name from first record if program filter is numeric
        if (is_numeric(props.params.program) && records.value.length > 0) {
            programNameCache.value = records.value[0].program_name;
        }

        // Calculate summary if needed
        if (reportType.value === 'summary') {
            calculateSummary();
        }
    } catch (error) {
        console.error('Failed to load report data:', error);
        records.value = [];
    } finally {
        loading.value = false;
    }
}

function calculateSummary() {
    const uniquePrograms = new Set(records.value.map(r => r.program_name).filter(Boolean));
    const uniqueSchools = new Set(records.value.map(r => r.school_name).filter(Boolean));
    summaryData.value = {
        programs: uniquePrograms.size,
        schools: uniqueSchools.size
    };
}

function saveAsPdf() {
    const url = route('report.scholarship.pdf', {
        ...props.params,
        paper_size: paperSize.value,
        orientation: orientation.value
    });
    window.open(url, '_blank');
}

function saveAsExcel() {
    const url = route('report.scholarship.excel', {
        ...props.params
    });
    window.open(url, '_blank');
}

// Helper function to check if value is numeric
function is_numeric(val) {
    return !isNaN(parseFloat(val)) && isFinite(val);
}

function formatName(item) {
    const parts = [item.last_name, item.first_name, item.middle_name].filter(Boolean);
    return parts.join(', ').toUpperCase() || (item.full_name ? item.full_name.toUpperCase() : '-');
}

function formatAddress(item) {
    const parts = [];
    if (item.municipality) parts.push(item.municipality);
    if (item.barangay) parts.push(item.barangay);
    return parts.join(', ').toUpperCase() || '-';
}

function formatDate(date) {
    return date ? moment(date).format('MMM DD, YYYY') : '-';
}

function formatApprovalStatus(item) {
    const status = item.approval_status;
    return formatUnifiedStatusText(status);
}

function formatUnifiedStatusText(status) {
    const statusMap = {
        'pending': 'Pending',
        'approved': 'Approved',
        'denied': 'Denied',
        'active': 'Active',
        'completed': 'Completed',
        'unknown': 'Unknown'
    };
    return statusMap[status] || status || 'N/A';
}

function getStatusSeverity(item) {
    const status = item.approval_status;
    const severityMap = {
        'pending': 'warning',
        'approved': 'info',
        'denied': 'danger',
        'active': 'success',
        'completed': 'secondary',
        'withdrawn': 'secondary',
        'loa': 'warning',
        'suspended': 'danger',
        'unknown': 'secondary'
    };
    return severityMap[status] || 'info';
}

function formatGrantProvision(item) {
    const provision = item.grant_provision;
    return formatGrantProvisionText(provision);
}

function formatGrantProvisionText(provision) {
    const provisionMap = {
        'full': 'Full',
        'partial': 'Partial',
        'tuition_only': 'Tuition Only',
        'rle_and_tuition': 'RLE and Tuition'
    };
    return provisionMap[provision] || provision || '-';
}

function isJpm(item) {
    return item.is_jpm_member || item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm;
}

// Lifecycle
onMounted(() => {
    loadData();
});

// Watch for paper size and orientation changes
watch([paperSize, orientation], () => {
    // Optionally reload or update view
});
</script>
