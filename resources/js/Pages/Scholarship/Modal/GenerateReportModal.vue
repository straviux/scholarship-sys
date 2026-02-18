<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal :closable="true"
        :style="{ width: '900px' }" header="Generate Scholarship Report">

        <form @submit.prevent="generateReport" class="px-4 pb-2">
            <!-- Two Column Layout -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <!-- LEFT COLUMN: Filters -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Filters</h4>

                    <!-- Date Range -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <DatePicker v-model="dateFrom" placeholder="From" showButtonBar class="w-full"
                                dateFormat="M dd, yy" size="small" />
                            <DatePicker v-model="dateTo" placeholder="To" showButtonBar class="w-full"
                                dateFormat="M dd, yy" size="small" />
                        </div>
                        <small v-if="dateTo && dateFrom && isDateToInvalid" class="text-red-500 text-xs mt-1">
                            Date To must be after Date From
                        </small>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Status</label>
                        <Select v-model="selectedUnifiedStatus" :options="unifiedStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="All Statuses" class="w-full" showClear />
                        <small class="text-xs text-gray-500 mt-1">Filter by unified status</small>
                    </div>

                    <!-- Program -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Program</label>
                        <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All Programs"
                            class="w-full" />
                    </div>

                    <!-- School -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">School</label>
                        <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All Schools"
                            class="w-full" :multiple="true" />
                    </div>

                    <!-- Course -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Course(s)</label>
                        <CourseSelect v-model="selectedCourses" :scholarship-program-id="selectedProgram?.id"
                            label="shortname" custom-placeholder="All Courses" :multiple="true" class="w-full" />
                    </div>

                    <!-- Municipality -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Municipality</label>
                        <MunicipalitySelect v-model="selectedMunicipality" custom-placeholder="All Municipalities"
                            class="w-full" />
                    </div>

                    <!-- Year Level -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Year Level</label>
                        <YearLevelSelect v-model="selectedYearLevel" custom-placeholder="All Year Levels"
                            class="w-full" />
                    </div>

                    <!-- Grant Provision -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Grant Provision</label>
                        <Select v-model="selectedGrantProvision" :options="grantProvisionOptions" optionLabel="label"
                            optionValue="value" placeholder="All Provisions" class="w-full" showClear />
                    </div>

                    <!-- Clear Filters Button -->
                    <div v-if="activeFiltersCount > 0"
                        class="flex items-center justify-between text-sm p-2 bg-blue-50 rounded">
                        <span class="text-gray-600">{{ activeFiltersCount }} filter(s) applied</span>
                        <Button label="Clear All" size="small" text severity="danger" @click="clearAllFilters"
                            icon="pi pi-times" />
                    </div>
                </div>

                <!-- RIGHT COLUMN: Report Options -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Report Options</h4>

                    <!-- Report Type -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Report Type</label>
                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="list" value="list" />
                                <label for="list" class="ml-2 text-sm">Detailed List</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="summary" value="summary" />
                                <label for="summary" class="ml-2 text-sm">Summary</label>
                            </div>
                        </div>
                    </div>

                    <!-- Group By Option -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Group By</label>
                        <Select v-model="groupBy" :options="groupByOptions" optionLabel="label" optionValue="value"
                            placeholder="Select grouping" class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">How records should be organized in the report</small>
                    </div>

                    <!-- Sub-Group By Option (only shown when primary grouping is selected) -->
                    <div v-if="groupBy && groupBy !== 'none'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Sub-Group By (Optional)</label>
                        <Select v-model="groupBySecondary" :options="secondaryGroupByOptions" optionLabel="label"
                            optionValue="value" placeholder="No sub-grouping" showClear class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">Further organize records within each group</small>
                    </div>

                    <!-- Another Sub-Group By Option (only shown when secondary grouping is selected) -->
                    <div v-if="groupBySecondary && groupBySecondary !== 'none'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Another Sub-Group By
                            (Optional)</label>
                        <Select v-model="groupByTertiary" :options="tertiaryGroupByOptions" optionLabel="label"
                            optionValue="value" placeholder="No third-level grouping" showClear class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">Organize records within sub-groups</small>
                    </div>

                    <!-- Show Sequence Numbers Toggle -->
                    <div class="mb-4 py-3 px-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Show Sequence Numbers</label>
                            <ToggleSwitch v-model="showSequenceNumbers" />
                        </div>
                        <p class="text-xs text-gray-500">Display queue position numbers for Program, School, and Course
                        </p>
                    </div>

                    <!-- JPM Highlighting Toggle -->
                    <div v-if="canEnableJpmHighlighting"
                        class="mb-4 py-3 px-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Enable JPM Highlighting</label>
                            <ToggleSwitch v-model="enableJpmHighlighting" />
                        </div>
                        <p class="text-xs text-gray-500">Highlight JPM members with green background</p>
                    </div>

                    <!-- JPM Filter -->
                    <div v-if="canEnableJpmHighlighting" class="mb-4 px-2">
                        <label class="block mb-2 text-sm font-medium text-gray-700">JPM Filter</label>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center">
                                <RadioButton v-model="jpmFilter" inputId="jpm_all" value="all"
                                    :disabled="!enableJpmHighlighting" />
                                <label for="jpm_all" class="ml-2 text-sm"
                                    :class="{ 'text-gray-400': !enableJpmHighlighting }">Show All</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="jpmFilter" inputId="jpm_only" value="jpm_only"
                                    :disabled="!enableJpmHighlighting" />
                                <label for="jpm_only" class="ml-2 text-sm"
                                    :class="{ 'text-gray-400': !enableJpmHighlighting }">JPM Only</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="jpmFilter" inputId="hide_jpm" value="hide_jpm"
                                    :disabled="!enableJpmHighlighting" />
                                <label for="hide_jpm" class="ml-2 text-sm"
                                    :class="{ 'text-gray-400': !enableJpmHighlighting }">Hide JPM</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <Button type="button" label="Cancel" severity="secondary" @click="close" outlined />
                <Button type="submit" label="Generate Report" icon="pi pi-file-export" severity="success"
                    :disabled="isDateToInvalid" />
            </div>
        </form>
    </Dialog>

    <!-- Report Preview Modal -->
    <Dialog v-if="showReportModal" :visible="showReportModal" modal :closable="true" :maximizable="true"
        :style="{ width: '95vw', height: '90vh' }" @update:visible="val => showReportModal = val"
        header="Report Preview">

        <component :is="ReportView" v-if="ReportView" :params="lastParams" @close="showReportModal = false" />
    </Dialog>
</template>

<script setup>
import { ref, computed, shallowRef, markRaw, watch } from 'vue';
import { defineAsyncComponent } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';

// PrimeVue Components

// Custom Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show']);

// Get current user from Inertia
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// Check if user has permission to enable JPM Highlighting
const canEnableJpmHighlighting = computed(() => {
    if (!currentUser.value) return false;
    const userRoles = currentUser.value.roles || [];
    const allowedRoles = ['administrator', 'jpm_moderator', 'program_manager'];
    return userRoles.some(role => allowedRoles.includes(role.name || role));
});

// State Management
const showReportModal = ref(false);
const lastParams = ref({});
const ReportView = shallowRef(null);

// Filter States
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourses = ref([]);
const selectedMunicipality = ref(null);
const selectedYearLevel = ref(null);
const selectedUnifiedStatus = ref(null);
const selectedGrantProvision = ref(null);

// Report Configuration
const reportType = ref('list');
const groupBy = ref('none');
const groupBySecondary = ref('none');
const groupByTertiary = ref('none');
const showSequenceNumbers = ref(true);
const enableJpmHighlighting = ref(false);
const jpmFilter = ref('all');

// Status composable
const { statusOptions } = useScholarshipStatus();

// Unified Status Options for report
const unifiedStatusOptions = computed(() => [
    { label: 'All Statuses', value: null },
    ...statusOptions.value
]);

// Grant Provision Options
const grantProvisionOptions = [
    { label: 'All Provisions', value: null },
    { label: 'Full', value: 'full' },
    { label: 'Partial', value: 'partial' },
    { label: 'Tuition Only', value: 'tuition_only' },
    { label: 'RLE and Tuition', value: 'rle_and_tuition' },
];

// Group By Options
const groupByOptions = [
    { label: 'No Grouping (By Date Filed)', value: 'none' },
    { label: 'By Status', value: 'unified_status' },
    { label: 'By School', value: 'school' },
    { label: 'By Program', value: 'program' },
    { label: 'By Course', value: 'course' },
    { label: 'By Year Level', value: 'year_level' },
    { label: 'By Municipality', value: 'municipality' },
    { label: 'By Grant Provision', value: 'grant_provision' },
];

// Secondary Group By Options (excludes the primary grouping)
const secondaryGroupByOptions = computed(() => {
    return groupByOptions.filter(option => option.value !== 'none' && option.value !== groupBy.value);
});

// Tertiary Group By Options (excludes primary and secondary grouping)
const tertiaryGroupByOptions = computed(() => {
    return groupByOptions.filter(option => option.value !== 'none' && option.value !== groupBy.value && option.value !== groupBySecondary.value);
});

// Watch for JPM highlighting toggle changes
watch(enableJpmHighlighting, (newValue) => {
    if (!newValue) {
        // Reset JPM filter to 'all' when highlighting is disabled
        jpmFilter.value = 'all';
    }
});

// Watch for primary grouping changes
watch(groupBy, (newValue) => {
    // Reset secondary grouping when primary grouping changes
    if (newValue === 'none' || newValue === groupBySecondary.value) {
        groupBySecondary.value = 'none';
        groupByTertiary.value = 'none';
    }
});

// Watch for secondary grouping changes
watch(groupBySecondary, (newValue) => {
    // Reset tertiary grouping when secondary grouping changes
    if (newValue === 'none' || newValue === groupByTertiary.value) {
        groupByTertiary.value = 'none';
    }
});

// Computed Properties
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (dateFrom.value || dateTo.value) count++;
    if (selectedProgram.value) count++;
    if (Array.isArray(selectedSchool.value) ? selectedSchool.value.length > 0 : selectedSchool.value) count++;
    if (selectedCourses.value && selectedCourses.value.length > 0) count++;
    if (selectedMunicipality.value) count++;
    if (selectedYearLevel.value) count++;
    if (selectedUnifiedStatus.value) count++;
    if (selectedGrantProvision.value) count++;
    return count;
});

// Methods
function close() {
    emit('update:show', false);
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedProgram.value = null;
    selectedSchool.value = [];
    selectedCourses.value = [];
    selectedMunicipality.value = null;
    selectedYearLevel.value = null;
    selectedUnifiedStatus.value = null;
    selectedGrantProvision.value = null;
    groupBy.value = 'none';
    groupBySecondary.value = 'none';
    groupByTertiary.value = 'none';
    showSequenceNumbers.value = true;
    enableJpmHighlighting.value = false;
    jpmFilter.value = 'all';
}

function generateReport() {
    // Validate dates one more time
    if (isDateToInvalid.value) {
        return;
    }

    // Format dates
    const date_from = dateFrom.value ? moment(dateFrom.value).format('YYYY-MM-DD') : '';
    const date_to = dateTo.value ? moment(dateTo.value).format('YYYY-MM-DD') : '';

    // Convert selected courses array to comma-separated names
    const courseNames = selectedCourses.value && selectedCourses.value.length > 0
        ? selectedCourses.value.map(course => course.name).join(',')
        : '';

    // Convert selected schools array to comma-separated shortnames
    const schoolShortnames = Array.isArray(selectedSchool.value) && selectedSchool.value.length > 0
        ? selectedSchool.value.map(school => school.shortname).join(',')
        : selectedSchool.value?.shortname || '';

    // Build params object
    const params = {
        date_from,
        date_to,
        program: selectedProgram.value?.id || '',
        school: schoolShortnames,
        courses: courseNames,
        municipality: selectedMunicipality.value?.name || '',
        year_level: selectedYearLevel.value?.value || '',
        unified_status: selectedUnifiedStatus.value || '',
        grant_provision: selectedGrantProvision.value || '',
        report_type: reportType.value,
        group_by: groupBy.value,
        group_by_secondary: groupBySecondary.value && groupBySecondary.value !== 'none' ? groupBySecondary.value : 'none',
        group_by_tertiary: groupByTertiary.value && groupByTertiary.value !== 'none' ? groupByTertiary.value : 'none',
        show_sequence_numbers: showSequenceNumbers.value ? 1 : 0,
        paper_size: 'A4',
        orientation: 'landscape',
        enable_jpm_highlighting: enableJpmHighlighting.value ? 1 : 0,
        show_jpm_only: (enableJpmHighlighting.value && jpmFilter.value === 'jpm_only') ? 1 : '',
        hide_jpm: (enableJpmHighlighting.value && jpmFilter.value === 'hide_jpm') ? 1 : '',
    };

    // Store params and load report view
    lastParams.value = { ...params };

    // Lazy load ReportView component
    if (!ReportView.value) {
        ReportView.value = markRaw(defineAsyncComponent(() => import('./ReportView.vue')));
    }

    // Show report modal
    showReportModal.value = true;
}
</script>
