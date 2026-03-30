<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- iOS Navigation Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close" v-tooltip.bottom="'Close'"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Generate Report</span>
                    <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="isDateToInvalid"
                        v-tooltip.bottom="'Generate Report'"><i class="pi pi-check"></i></button>
                </div>

                <div class="ios-body">
                    <!-- REPORT TYPE — iOS Segmented Control -->
                    <div class="ios-section">
                        <div class="ios-section-label">Report Type</div>
                        <div class="ios-segmented-control">
                            <button :class="['ios-segment', reportType === 'list' && 'ios-segment-active']"
                                @click="reportType = 'list'">
                                <i class="pi pi-list" style="font-size: 13px;"></i>
                                Detailed List
                            </button>
                            <button :class="['ios-segment', reportType === 'summary' && 'ios-segment-active']"
                                @click="reportType = 'summary'">
                                <i class="pi pi-chart-bar" style="font-size: 13px;"></i>
                                Summary
                            </button>
                        </div>
                    </div>

                    <!-- FILTERS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Filters</div>
                        <div class="ios-card">
                            <!-- Status -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-circle-fill" style="color: #FF9500; font-size: 13px;"></i>
                                    Status
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedUnifiedStatus" :options="unifiedStatusOptions"
                                        optionLabel="label" optionValue="value" placeholder="All" showClear
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Program -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-bookmark-fill" style="color: #007AFF; font-size: 13px;"></i>
                                    Program
                                </div>
                                <div class="ios-row-control">
                                    <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All"
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- School -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-building" style="color: #34C759; font-size: 13px;"></i>
                                    School
                                </div>
                                <div class="ios-row-control">
                                    <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All"
                                        class="ios-select" :multiple="true" />
                                </div>
                            </div>

                            <!-- Course -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-graduation-cap" style="color: #AF52DE; font-size: 13px;"></i>
                                    Course
                                </div>
                                <div class="ios-row-control">
                                    <CourseSelect v-model="selectedCourses"
                                        :scholarship-program-id="selectedProgram?.id" label="shortname"
                                        custom-placeholder="All" :multiple="true" class="ios-select" />
                                </div>
                            </div>

                            <!-- Municipality -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-map-marker" style="color: #FF2D55; font-size: 13px;"></i>
                                    Municipality
                                </div>
                                <div class="ios-row-control">
                                    <MunicipalitySelect v-model="selectedMunicipality" custom-placeholder="All"
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Year Level -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-sort-numeric-up" style="color: #5856D6; font-size: 13px;"></i>
                                    Year Level
                                </div>
                                <div class="ios-row-control">
                                    <YearLevelSelect v-model="selectedYearLevel" custom-placeholder="All"
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Grant Provision -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-wallet" style="color: #FF9500; font-size: 13px;"></i>
                                    Grant Provision
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedGrantProvision" :options="grantProvisionOptions"
                                        optionLabel="label" optionValue="value" placeholder="All" showClear
                                        class="ios-select" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATE RANGE -->
                    <div class="ios-section">
                        <div class="ios-section-label">Date Range</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last ios-row-dates">
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    From
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateFrom" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                                <span class="ios-date-separator">—</span>
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    To
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateTo" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                        </div>
                        <div v-if="dateTo && dateFrom && isDateToInvalid" class="ios-section-footer ios-error">
                            Date To must be after Date From
                        </div>
                    </div>

                    <!-- REPORT OPTIONS -->
                    <div class="ios-section">
                        <div class="ios-section-label">Options</div>
                        <div class="ios-card">
                            <!-- Group By -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-objects-column" style="color: #5856D6; font-size: 13px;"></i>
                                    Group By
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Sub-Group By -->
                            <div class="ios-row" v-if="groupBy && groupBy !== 'none'">
                                <div class="ios-row-label">
                                    <i class="pi pi-objects-column" style="color: #8E8E93; font-size: 13px;"></i>
                                    Sub-Group
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="groupBySecondary" :options="secondaryGroupByOptions"
                                        optionLabel="label" optionValue="value" placeholder="None" showClear
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Tertiary Group By -->
                            <div class="ios-row" v-if="groupBySecondary && groupBySecondary !== 'none'">
                                <div class="ios-row-label">
                                    <i class="pi pi-objects-column" style="color: #C7C7CC; font-size: 13px;"></i>
                                    3rd Group
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="groupByTertiary" :options="tertiaryGroupByOptions"
                                        optionLabel="label" optionValue="value" placeholder="None" showClear
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Paper Size -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-file" style="color: #8E8E93; font-size: 13px;"></i>
                                    Paper Size
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Orientation -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-desktop" style="color: #FF9500; font-size: 13px;"></i>
                                    Orientation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Sequence Numbers Toggle -->
                            <div class="ios-row" v-if="reportType === 'list'">
                                <div class="ios-row-label">
                                    <i class="pi pi-hashtag" style="color: #007AFF; font-size: 13px;"></i>
                                    Sequence Numbers
                                </div>
                                <div>
                                    <ToggleSwitch v-model="showSequenceNumbers" />
                                </div>
                            </div>

                            <!-- Include Remarks Toggle -->
                            <div class="ios-row" v-if="reportType === 'list'">
                                <div class="ios-row-label">
                                    <i class="pi pi-comment" style="color: #FF9500; font-size: 13px;"></i>
                                    Include Remarks
                                </div>
                                <div>
                                    <ToggleSwitch v-model="includeRemarks" />
                                </div>
                            </div>

                            <!-- Include Grant Provision Toggle -->
                            <div class="ios-row" v-if="reportType === 'list'">
                                <div class="ios-row-label">
                                    <i class="pi pi-wallet" style="color: #34C759; font-size: 13px;"></i>
                                    Grant Provision
                                </div>
                                <div>
                                    <ToggleSwitch v-model="includeGrantProvision" />
                                </div>
                            </div>

                            <!-- JPM Highlighting Toggle -->
                            <div class="ios-row" v-if="reportType === 'list' && canEnableJpmHighlighting">
                                <div class="ios-row-label">
                                    <i class="pi pi-highlight" style="color: #34C759; font-size: 13px;"></i>
                                    JPM Highlighting
                                </div>
                                <div>
                                    <ToggleSwitch v-model="enableJpmHighlighting" />
                                </div>
                            </div>

                            <!-- JPM Filter -->
                            <div class="ios-row" v-if="canEnableJpmHighlighting && enableJpmHighlighting">
                                <div class="ios-row-label">
                                    <i class="pi pi-filter" style="color: #8E8E93; font-size: 13px;"></i>
                                    JPM Filter
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="jpmFilter" :options="jpmFilterOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            Configure how the report is organized and what details to include.
                        </div>
                    </div>

                    <!-- Bottom spacer -->
                    <div style="height: 24px;"></div>
                </div>

                <!-- Sticky Footer -->
                <div v-if="activeFiltersCount > 0" class="ios-footer">
                    <button class="ios-destructive-btn" @click="clearAllFilters">
                        Clear All Filters ({{ activeFiltersCount }})
                    </button>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Report Preview Modal — full screen iOS style -->
    <Dialog :visible="showReportModal" @update:visible="val => showReportModal = val" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal ios-modal-full" :style="previewModalStyle">
                <!-- Preview Nav Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onPreviewDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="showReportModal = false">
                        <i class="pi pi-chevron-left" style="font-size: 13px;"></i> Back
                    </button>
                    <span class="ios-nav-title">Report Preview</span>
                    <div class="ios-nav-actions">
                        <button class="ios-icon-btn ios-icon-btn-red" @click="downloadPdf" title="Download PDF">
                            <i class="pi pi-file-pdf"></i>
                        </button>
                        <button class="ios-icon-btn ios-icon-btn-green" @click="downloadExcel" title="Download Excel">
                            <i class="pi pi-file-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="ios-body ios-preview-body">
                    <component :is="ReportView" v-if="ReportView" :params="lastParams"
                        @close="showReportModal = false" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, shallowRef, markRaw, watch, onBeforeUnmount } from 'vue';
import { defineAsyncComponent } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';

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
    const allowedRoles = ['administrator', 'jpm_admin', 'program_manager'];
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
const includeRemarks = ref(true);
const includeGrantProvision = ref(false);
const paperSize = ref('A4');
const orientation = ref('landscape');

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

// Paper Size & Orientation Options
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

// JPM Filter Options
const jpmFilterOptions = [
    { label: 'Show All', value: 'all' },
    { label: 'JPM Only', value: 'jpm_only' },
    { label: 'Hide JPM', value: 'hide_jpm' },
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
        jpmFilter.value = 'all';
    }
});

// Watch for primary grouping changes
watch(groupBy, (newValue) => {
    if (newValue === 'none' || newValue === groupBySecondary.value) {
        groupBySecondary.value = 'none';
        groupByTertiary.value = 'none';
    }
});

// Watch for secondary grouping changes
watch(groupBySecondary, (newValue) => {
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
    includeRemarks.value = false;
    includeGrantProvision.value = true;
}

function generateReport() {
    if (isDateToInvalid.value) {
        return;
    }

    const date_from = dateFrom.value ? moment(dateFrom.value).format('YYYY-MM-DD') : '';
    const date_to = dateTo.value ? moment(dateTo.value).format('YYYY-MM-DD') : '';

    const courseNames = selectedCourses.value && selectedCourses.value.length > 0
        ? selectedCourses.value.map(course => course.name).join(',')
        : '';

    const schoolShortnames = Array.isArray(selectedSchool.value) && selectedSchool.value.length > 0
        ? selectedSchool.value.map(school => school.shortname).join(',')
        : selectedSchool.value?.shortname || '';

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
        include_remarks: includeRemarks.value ? 1 : 0,
        include_grant_provision: includeGrantProvision.value ? 1 : 0,
        paper_size: paperSize.value,
        orientation: orientation.value,
        enable_jpm_highlighting: enableJpmHighlighting.value ? 1 : 0,
        show_jpm_only: (enableJpmHighlighting.value && jpmFilter.value === 'jpm_only') ? 1 : '',
        hide_jpm: (enableJpmHighlighting.value && jpmFilter.value === 'hide_jpm') ? 1 : '',
    };

    lastParams.value = { ...params };

    if (!ReportView.value) {
        ReportView.value = markRaw(defineAsyncComponent(() => import('./ReportView.vue')));
    }

    showReportModal.value = true;
}

// ─── Drag logic (main modal) ───
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const modalStyle = computed(() => ({
    width: '520px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}

function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}

function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}

// ─── Drag logic (preview modal) ───
const previewDragOffset = ref({ x: 0, y: 0 });
const previewDragStart = ref(null);

const previewModalStyle = computed(() => ({
    width: '95vw',
    height: '90vh',
    transform: `translate(${previewDragOffset.value.x}px, ${previewDragOffset.value.y}px)`,
}));

function onPreviewDragStart(e) {
    if (e.target.closest('button')) return;
    previewDragStart.value = { x: e.clientX - previewDragOffset.value.x, y: e.clientY - previewDragOffset.value.y };
    document.addEventListener('pointermove', onPreviewDragMove);
    document.addEventListener('pointerup', onPreviewDragEnd);
}

function onPreviewDragMove(e) {
    if (!previewDragStart.value) return;
    previewDragOffset.value = { x: e.clientX - previewDragStart.value.x, y: e.clientY - previewDragStart.value.y };
}

function onPreviewDragEnd() {
    previewDragStart.value = null;
    document.removeEventListener('pointermove', onPreviewDragMove);
    document.removeEventListener('pointerup', onPreviewDragEnd);
}

// ─── Download functions ───
function downloadPdf() {
    const url = route('report.scholarship.pdf', {
        ...lastParams.value,
        paper_size: paperSize.value,
        orientation: orientation.value,
    }, false);
    window.open(url, '_blank');
}

function downloadExcel() {
    const url = route('report.scholarship.excel', {
        ...lastParams.value,
    }, false);
    window.open(url, '_blank');
}

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
    document.removeEventListener('pointermove', onPreviewDragMove);
    document.removeEventListener('pointerup', onPreviewDragEnd);
});
</script>

<style scoped>
/* iOS Modal Shell */
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-modal-full {
    max-height: 90vh;
}

/* Navigation Bar */
.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #6B7280;
    font-weight: 400;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-nav-actions {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 6px;
}

/* Icon Buttons in Preview Nav */
.ios-icon-btn {
    background: none;
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.15s;
}

.ios-icon-btn:hover {
    background: rgba(118, 118, 128, 0.12);
}

.ios-icon-btn-red {
    color: #FF3B30;
}

.ios-icon-btn-green {
    color: #34C759;
}

/* Preview Body */
.ios-preview-body {
    background: #F2F2F7;
    padding: 0;
}

/* Scrollable Body */
.ios-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-footer {
    flex-shrink: 0;
    padding: 8px 16px;
    padding-bottom: max(8px, env(safe-area-inset-bottom));
    border-top: 0.5px solid rgba(60, 60, 67, 0.12);
    background: rgba(242, 242, 247, 0.95);
}

.ios-footer .ios-destructive-btn {
    margin: 0;
}

/* Sections */
.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-section-footer {
    font-size: 13px;
    color: #6D6D72;
    padding: 6px 16px 0;
    line-height: 1.3;
}

.ios-error {
    color: #FF3B30;
}

/* iOS Grouped Card */
.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

/* iOS Row */
.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.ios-row-control {
    flex: 0 0 200px;
    width: 200px;
    display: flex;
    justify-content: flex-end;
    overflow: hidden;
}

.ios-row-control>* {
    width: 100%;
    min-width: 0;
}

/* Dual date row: label + picker + separator + label + picker */
.ios-row-dates {
    gap: 4px;
}

.ios-row-dates .ios-row-control {
    flex: 0 0 150px;
    width: 150px;
}

.ios-date-separator {
    color: #8E8E93;
    font-size: 14px;
    flex-shrink: 0;
}

/* iOS Segmented Control */
.ios-segmented-control {
    display: flex;
    background: #E9E9EB;
    border-radius: 9px;
    padding: 2px;
    gap: 0;
    border: 0.5px solid #D1D1D6;
}

.ios-segment {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 7px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #000;
    cursor: pointer;
    transition: all 0.2s ease;
    letter-spacing: -0.08px;
}

.ios-segment-active {
    background: #FFFFFF;
    box-shadow:
        0 3px 8px rgba(0, 0, 0, 0.06),
        0 1px 1px rgba(0, 0, 0, 0.04);
}

/* Destructive Button */
.ios-destructive-btn {
    display: block;
    width: 100%;
    background: #FFFFFF;
    border: 0.5px solid #E5E5EA;
    border-radius: 10px;
    padding: 8px;
    text-align: center;
    font-size: 13px;
    color: #FF3B30;
    cursor: pointer;
    letter-spacing: -0.4px;
    transition: background 0.15s;
}

.ios-destructive-btn:hover {
    background: #F2F2F7;
}

/* PrimeVue overrides inside iOS card */
:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    text-align: left;
    padding: 0;
    width: 100%;
    max-width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #8E8E93 !important;
    text-align: left;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

:deep(.ios-datepicker .p-datepicker) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

:deep(.ios-datepicker .p-inputtext) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: right;
    color: #8E8E93;
    font-size: 13px;
    padding: 4px 24px 4px 8px;
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
}

/* Dark mode */
:global(.dark) .ios-modal {
    background: #222831;
}

:global(.dark) .ios-nav-bar {
    background: #2a3040;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-nav-title {
    color: #d1d5db;
}

:global(.dark) .ios-nav-cancel {
    color: #9ca3af;
}

:global(.dark) .ios-nav-action {
    color: #d1d5db;
}

:global(.dark) .ios-icon-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-preview-body {
    background: #1a1e27;
}

:global(.dark) .ios-body {
    color: #d1d5db;
}

:global(.dark) .ios-footer {
    background: #2a3040;
    border-top-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-section-label {
    color: #9ca3af;
}

:global(.dark) .ios-section-footer {
    color: #9ca3af;
}

:global(.dark) .ios-card {
    background: #1e2433;
    border-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-row {
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

:global(.dark) .ios-row-label {
    color: #d1d5db;
}

:global(.dark) .ios-segmented-control {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.06);
}

:global(.dark) .ios-segment {
    color: #d1d5db;
}

:global(.dark) .ios-segment-active {
    background: #2a3040;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3), 0 1px 1px rgba(0, 0, 0, 0.2);
}

:global(.dark) .ios-destructive-btn {
    background: #1e2433;
    border-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-destructive-btn:hover {
    background: #252d3d;
}
</style>

<!-- Unscoped: targets teleported Dialog elements at body level -->
<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
