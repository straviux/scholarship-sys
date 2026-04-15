<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- iOS Navigation Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="step > 1 ? step-- : close()">
                        <i :class="step > 1 ? 'pi pi-chevron-left' : 'pi pi-times'"></i>
                        {{ step > 1 ? 'Back' : '' }}
                    </button>
                    <span class="ios-nav-title">
                        {{ stepTitles[step - 1] }}
                    </span>
                    <button v-if="step < 3" class="ios-nav-btn ios-nav-action" @click="step++" :disabled="!canProceed">
                        Next
                    </button>
                    <button v-else class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="generating"
                        v-tooltip.bottom="'Generate Report'">
                        <i v-if="generating" class="pi pi-spin pi-spinner"></i>
                        <template v-else>Generate</template>
                    </button>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div v-for="s in 3" :key="s"
                        :class="['step-dot', s === step && 'step-dot-active', s < step && 'step-dot-done']">
                    </div>
                </div>

                <div class="ios-body">
                    <!-- STEP 1: Report Type & Status -->
                    <div v-show="step === 1">
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

                        <div class="ios-section">
                            <div class="ios-section-label">Status</div>
                            <div class="ios-card">
                                <div v-for="(opt, idx) in statusChoices" :key="opt.value"
                                    :class="['ios-row', 'ios-row-selectable', selectedStatus === opt.value && 'ios-row-selected']"
                                    @click="selectedStatus = opt.value">
                                    <div class="ios-row-label">
                                        <span v-if="opt.color" class="status-dot"
                                            :style="{ background: opt.color }"></span>
                                        <i v-else class="pi pi-list" style="color: #8E8E93; font-size: 13px;"></i>
                                        {{ opt.label }}
                                    </div>
                                    <i v-if="selectedStatus === opt.value" class="pi pi-check"
                                        style="color: #007AFF; font-size: 13px;"></i>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                Choose a status to generate a report specific to that category, or "All Statuses" for a
                                comprehensive
                                report.
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Filters -->
                    <div v-show="step === 2">
                        <div class="ios-section">
                            <div class="ios-section-label">Filters</div>
                            <div class="ios-card">
                                <!-- Program -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-bookmark-fill" style="color: #007AFF; font-size: 13px;"></i>
                                        Program
                                    </div>
                                    <div class="ios-row-control">
                                        <ProgramSelect v-model="selectedProgram" label="shortname"
                                            custom-placeholder="All" class="ios-select" />
                                    </div>
                                </div>

                                <!-- School -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-building" style="color: #34C759; font-size: 13px;"></i>
                                        School
                                    </div>
                                    <div class="ios-row-control">
                                        <SchoolSelect v-model="selectedSchool" label="shortname"
                                            custom-placeholder="All" class="ios-select" :multiple="true" />
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

                        <!-- Date Range -->
                        <div class="ios-section">
                            <div class="ios-section-label">Date Range</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-dates">
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
                            <div v-if="isDateToInvalid" class="ios-section-footer ios-error">
                                Date To must be after Date From
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Options -->
                    <div v-show="step === 3">
                        <div class="ios-section">
                            <div class="ios-section-label">Layout</div>
                            <div class="ios-card">
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
                            </div>
                        </div>

                        <div class="ios-section" v-if="reportType === 'list'">
                            <div class="ios-section-label">Grouping</div>
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
                            </div>
                        </div>

                        <div class="ios-section" v-if="reportType === 'list'">
                            <div class="ios-section-label">Display Options</div>
                            <div class="ios-card">
                                <!-- Sequence Numbers -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-hashtag" style="color: #007AFF; font-size: 13px;"></i>
                                        Sequence Numbers
                                    </div>
                                    <div>
                                        <ToggleSwitch v-model="showSequenceNumbers" size="small" />
                                    </div>
                                </div>

                                <!-- Include Remarks -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-comment" style="color: #FF9500; font-size: 13px;"></i>
                                        Include Remarks
                                    </div>
                                    <div>
                                        <ToggleSwitch v-model="includeRemarks" size="small" />
                                    </div>
                                </div>

                                <!-- Include Grant Provision -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-wallet" style="color: #34C759; font-size: 13px;"></i>
                                        Grant Provision
                                    </div>
                                    <div>
                                        <ToggleSwitch v-model="includeGrantProvision" size="small" />
                                    </div>
                                </div>

                                <!-- JPM Highlighting -->
                                <div class="ios-row" v-if="canEnableJpmHighlighting">
                                    <div class="ios-row-label">
                                        <i class="pi pi-highlight" style="color: #34C759; font-size: 13px;"></i>
                                        JPM Highlighting
                                    </div>
                                    <div>
                                        <ToggleSwitch v-model="enableJpmHighlighting" size="small" />
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
                                Configure what details to include in the report.
                            </div>
                        </div>

                        <!-- Summary: Breakdown Axes -->
                        <div class="ios-section" v-if="reportType === 'summary'">
                            <div class="ios-section-label">Summary Breakdown</div>
                            <div class="ios-card">
                                <!-- Primary Axis -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-chart-bar" style="color: #5856D6; font-size: 13px;"></i>
                                        Primary Axis
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                            optionValue="value" class="ios-select" />
                                    </div>
                                </div>

                                <!-- Secondary Axis -->
                                <div class="ios-row" v-if="groupBy && groupBy !== 'none'">
                                    <div class="ios-row-label">
                                        <i class="pi pi-objects-column" style="color: #8E8E93; font-size: 13px;"></i>
                                        Secondary Axis
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="groupBySecondary" :options="secondaryGroupByOptions"
                                            optionLabel="label" optionValue="value" placeholder="None" showClear
                                            class="ios-select" />
                                    </div>
                                </div>

                                <!-- Tertiary Axis -->
                                <div class="ios-row" v-if="groupBySecondary && groupBySecondary !== 'none'">
                                    <div class="ios-row-label">
                                        <i class="pi pi-objects-column" style="color: #C7C7CC; font-size: 13px;"></i>
                                        3rd Axis
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="groupByTertiary" :options="tertiaryGroupByOptions"
                                            optionLabel="label" optionValue="value" placeholder="None" showClear
                                            class="ios-select" />
                                    </div>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                Choose how to break down the summary counts.
                            </div>
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

    <!-- Report Preview Modal -->
    <Dialog :visible="showPreview" @update:visible="val => showPreview = val" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal ios-modal-full" :style="previewModalStyle">
                <div class="ios-nav-bar" @pointerdown="onPreviewDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="showPreview = false">
                        <i class="pi pi-chevron-left" style="font-size: 13px;"></i> Back
                    </button>
                    <span class="ios-nav-title">Report Preview</span>
                    <div class="ios-nav-actions">
                        <button class="ios-icon-btn" @click="doPrint" title="Print / Save as PDF">
                            <i class="pi pi-print" style="color: #007AFF;"></i>
                        </button>
                    </div>
                </div>

                <!-- Zoom Toolbar -->
                <div class="flex items-center justify-between px-4 py-2
                            bg-[#f2f2f7] dark:bg-[#1e242b]
                            border-b border-[#e5e5ea] dark:border-white/10">
                    <div class="flex items-center gap-1.5">
                        <button @click="zoomLevel = Math.max(40, zoomLevel - 10)"
                            class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                            :disabled="zoomLevel <= 40">
                            <i class="pi pi-minus" style="font-size: 10px;"></i>
                        </button>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 w-12 text-center">{{ zoomLevel
                            }}%</span>
                        <button @click="zoomLevel = Math.min(200, zoomLevel + 10)"
                            class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                            :disabled="zoomLevel >= 200">
                            <i class="pi pi-plus" style="font-size: 10px;"></i>
                        </button>
                    </div>
                    <span class="text-xs text-gray-400">{{ orientation === 'landscape' ? 'Landscape' : 'Portrait' }} ·
                        {{ paperSize }}</span>
                </div>

                <!-- Preview Body -->
                <div class="overflow-auto bg-[#d1d1d6] dark:bg-[#1c1c1e]"
                    style="flex: 1; min-height: 0; padding: 16px 0;">
                    <div style="display: flex; justify-content: center;">
                        <iframe v-if="previewHtml" :srcdoc="previewHtml"
                            :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', border: 'none', background: '#fff', boxShadow: '0 2px 20px rgba(0,0,0,0.15)', width: iframeWidth + 'px', height: iframeHeight + 'px' }"
                            frameborder="0"></iframe>
                        <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <i class="pi pi-spin pi-spinner text-3xl mb-3"></i>
                            <p>Generating report...</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import axios from 'axios';
import { useSystemOptions } from '@/composables/useSystemOptions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { renderVueTemplate } from '@/composables/usePdfPrint';
import { getReportCss } from '@/Pages/Scholarship/Reports/report-styles';

// Custom Select Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

// Report Templates
import AllStatusReport from '@/Pages/Scholarship/Reports/AllStatusReport.vue';
import PendingReport from '@/Pages/Scholarship/Reports/PendingReport.vue';
import InterviewedReport from '@/Pages/Scholarship/Reports/InterviewedReport.vue';
import ApprovedReport from '@/Pages/Scholarship/Reports/ApprovedReport.vue';
import DeniedReport from '@/Pages/Scholarship/Reports/DeniedReport.vue';
import ActiveReport from '@/Pages/Scholarship/Reports/ActiveReport.vue';
import CompletedReport from '@/Pages/Scholarship/Reports/CompletedReport.vue';
import WithdrawnReport from '@/Pages/Scholarship/Reports/WithdrawnReport.vue';
import LoaReport from '@/Pages/Scholarship/Reports/LoaReport.vue';
import SuspendedReport from '@/Pages/Scholarship/Reports/SuspendedReport.vue';
import SummaryReport from '@/Pages/Scholarship/Reports/SummaryReport.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show']);

// ─── State ───
const step = ref(1);
const generating = ref(false);
const showPreview = ref(false);
const previewHtml = ref('');
const zoomLevel = ref(80);

// Step 1
const reportType = ref('list');
const selectedStatus = ref(null); // null = All

// Step 2
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourses = ref([]);
const selectedMunicipality = ref(null);
const selectedYearLevel = ref(null);
const selectedGrantProvision = ref(null);

// Step 3
const paperSize = ref('A4');
const orientation = ref('landscape');
const groupBy = ref('none');
const groupBySecondary = ref('none');
const groupByTertiary = ref('none');
const showSequenceNumbers = ref(true);
const includeRemarks = ref(false);
const includeGrantProvision = ref(true);
const enableJpmHighlighting = ref(false);
const jpmFilter = ref('all');

// ─── Composables ───
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const { statusOptions, getStatusConfig } = useScholarshipStatus();

const canEnableJpmHighlighting = computed(() => {
    if (!currentUser.value) return false;
    const userRoles = currentUser.value.roles || [];
    const allowedRoles = ['administrator', 'jpm_admin', 'program_manager'];
    return userRoles.some(role => allowedRoles.includes(role.name || role));
});

// ─── Step titles ───
const stepTitles = ['Report Type', 'Filters', 'Options'];

// ─── Status choices ───
const statusChoices = computed(() => {
    const configs = [
        { value: null, label: 'All Statuses', color: null },
        { value: 'pending', label: 'Pending', color: '#F59E0B' },
        { value: 'interviewed', label: 'Interviewed', color: '#6366F1' },
        { value: 'approved', label: 'Approved', color: '#3B82F6' },
        { value: 'denied', label: 'Denied', color: '#EF4444' },
        { value: 'active', label: 'Active', color: '#10B981' },
        { value: 'completed', label: 'Completed', color: '#6B7280' },
        { value: 'withdrawn', label: 'Withdrawn', color: '#8B5CF6' },
        { value: 'loa', label: 'Leave of Absence', color: '#D97706' },
        { value: 'suspended', label: 'Suspended', color: '#DC2626' },
    ];
    return configs;
});

// ─── Options ───
const _grantProvisionRaw = useSystemOptions('grant_provision');
const grantProvisionOptions = computed(() => [
    { label: 'All Provisions', value: null },
    ..._grantProvisionRaw.value,
]);

const groupByOptions = [
    { label: 'No Grouping', value: 'none' },
    { label: 'By Status', value: 'unified_status' },
    { label: 'By School', value: 'school' },
    { label: 'By Program', value: 'program' },
    { label: 'By Course', value: 'course' },
    { label: 'By Year Level', value: 'year_level' },
    { label: 'By Municipality', value: 'municipality' },
    { label: 'By Grant Provision', value: 'grant_provision' },
];

const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const jpmFilterOptions = [
    { label: 'Show All', value: 'all' },
    { label: 'JPM Only', value: 'jpm_only' },
    { label: 'Hide JPM', value: 'hide_jpm' },
];

const secondaryGroupByOptions = computed(() =>
    groupByOptions.filter(o => o.value !== 'none' && o.value !== groupBy.value)
);

const tertiaryGroupByOptions = computed(() =>
    groupByOptions.filter(o => o.value !== 'none' && o.value !== groupBy.value && o.value !== groupBySecondary.value)
);

// ─── Computed ───
const canProceed = computed(() => {
    if (step.value === 2 && isDateToInvalid.value) return false;
    return true;
});

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
    if (selectedCourses.value?.length > 0) count++;
    if (selectedMunicipality.value) count++;
    if (selectedYearLevel.value) count++;
    if (selectedGrantProvision.value) count++;
    return count;
});

// Paper dimensions for preview (in px @ 96 dpi)
const pageDimensions = computed(() => {
    const sizes = {
        A4: { w: 794, h: 1123 },
        Letter: { w: 816, h: 1056 },
        Legal: { w: 816, h: 1344 },
    };
    const s = sizes[paperSize.value] || sizes.A4;
    return orientation.value === 'landscape' ? { w: s.h, h: s.w } : s;
});

const iframeWidth = computed(() => pageDimensions.value.w);
const iframeHeight = computed(() => pageDimensions.value.h);

// ─── Template map ───
const templateMap = {
    null: AllStatusReport,
    pending: PendingReport,
    interviewed: InterviewedReport,
    approved: ApprovedReport,
    denied: DeniedReport,
    active: ActiveReport,
    completed: CompletedReport,
    withdrawn: WithdrawnReport,
    loa: LoaReport,
    suspended: SuspendedReport,
};

// ─── Methods ───
function close() {
    emit('update:show', false);
    // Reset to step 1 on close
    setTimeout(() => { step.value = 1; }, 300);
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedProgram.value = null;
    selectedSchool.value = [];
    selectedCourses.value = [];
    selectedMunicipality.value = null;
    selectedYearLevel.value = null;
    selectedGrantProvision.value = null;
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        // Build query params
        const params = {};
        if (selectedStatus.value) params.status = selectedStatus.value;
        if (selectedProgram.value?.id) params.program = selectedProgram.value.id;
        if (Array.isArray(selectedSchool.value) && selectedSchool.value.length > 0) {
            params.school = selectedSchool.value.map(s => s.shortname).join(',');
        } else if (selectedSchool.value?.shortname) {
            params.school = selectedSchool.value.shortname;
        }
        if (selectedCourses.value?.length > 0) {
            params.course = selectedCourses.value.map(c => c.name).join(',');
        }
        if (selectedMunicipality.value?.name) params.municipality = selectedMunicipality.value.name;
        if (selectedYearLevel.value?.value) params.year_level = selectedYearLevel.value.value;
        if (selectedGrantProvision.value) params.grant_provision = selectedGrantProvision.value;
        if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
        if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');

        // Fetch data
        const response = await axios.get(route('data-export.download'), { params });
        let records = [];
        if (Array.isArray(response.data)) {
            records = response.data;
        } else if (response.data?.scholars) {
            records = response.data.scholars;
        } else if (response.data?.applicants) {
            records = response.data.applicants;
        } else if (response.data?.data) {
            records = response.data.data;
        }

        // Build filter summary for template header
        const filterSummary = buildFilterSummary();

        // Build template props
        const templateProps = {
            records,
            filters: filterSummary,
            reportType: reportType.value,
            options: {
                showSequenceNumbers: showSequenceNumbers.value,
                includeRemarks: includeRemarks.value,
                includeGrantProvision: includeGrantProvision.value,
                enableJpmHighlighting: enableJpmHighlighting.value,
                jpmFilter: jpmFilter.value,
                groupBy: groupBy.value,
                groupBySecondary: groupBySecondary.value !== 'none' ? groupBySecondary.value : null,
                groupByTertiary: groupByTertiary.value !== 'none' ? groupByTertiary.value : null,
            },
            generatedAt: moment().format('MMMM DD, YYYY — h:mm A'),
        };

        // Pick template
        let TemplateComponent;
        if (reportType.value === 'summary') {
            TemplateComponent = SummaryReport;
        } else {
            TemplateComponent = templateMap[selectedStatus.value] || AllStatusReport;
        }

        // Render to HTML
        const bodyHtml = renderVueTemplate(TemplateComponent, templateProps);

        // Build full document
        const cssSize = getCssPageSize();
        const fullHtml = buildReportDoc(bodyHtml, getReportTitle(), cssSize);

        previewHtml.value = fullHtml;
        showPreview.value = true;
    } catch (error) {
        console.error('Failed to generate report:', error);
    } finally {
        generating.value = false;
    }
}

function buildFilterSummary() {
    const summary = {};
    const statusChoice = statusChoices.value.find(s => s.value === selectedStatus.value);
    if (statusChoice) summary.Status = statusChoice.label;
    if (selectedProgram.value) summary.Program = selectedProgram.value.shortname || selectedProgram.value.name;
    if (Array.isArray(selectedSchool.value) && selectedSchool.value.length > 0) {
        summary.School = selectedSchool.value.map(s => s.shortname || s.name).join(', ');
    }
    if (selectedCourses.value?.length > 0) {
        summary.Course = selectedCourses.value.map(c => c.shortname || c.name).join(', ');
    }
    if (selectedMunicipality.value) summary.Municipality = selectedMunicipality.value.name;
    if (selectedYearLevel.value) summary['Year Level'] = selectedYearLevel.value.value;
    if (selectedGrantProvision.value) summary['Grant Provision'] = selectedGrantProvision.value;
    if (dateFrom.value) summary['Date From'] = moment(dateFrom.value).format('MMM DD, YYYY');
    if (dateTo.value) summary['Date To'] = moment(dateTo.value).format('MMM DD, YYYY');
    return summary;
}

function getReportTitle() {
    const statusChoice = statusChoices.value.find(s => s.value === selectedStatus.value);
    const statusLabel = statusChoice?.label || 'All Statuses';
    if (reportType.value === 'summary') return `Summary Report — ${statusLabel}`;
    return `${statusLabel} — Detailed List`;
}

function getCssPageSize() {
    const sizes = { A4: '210mm 297mm', Letter: '8.5in 11in', Legal: '8.5in 14in' };
    const size = sizes[paperSize.value] || sizes.A4;
    if (orientation.value === 'landscape') {
        const [w, h] = size.split(' ');
        return `${h} ${w}`;
    }
    return size;
}

function buildReportDoc(bodyHtml, title, cssPageSize) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>${title}</title>
  <style>${getReportCss(cssPageSize)}</style>
</head>
<body>${bodyHtml}</body>
</html>`;
}

function doPrint() {
    if (!previewHtml.value) return;
    const win = window.open('', '_blank');
    if (!win) {
        alert('Pop-up blocked. Please allow pop-ups for this site.');
        return;
    }
    win.document.write(previewHtml.value);
    win.document.close();
    win.onload = () => { win.focus(); win.print(); };
    setTimeout(() => { if (win && !win.closed) { win.focus(); win.print(); } }, 800);
}

// ─── Watchers ───
watch(enableJpmHighlighting, v => { if (!v) jpmFilter.value = 'all'; });
watch(groupBy, v => { if (v === 'none' || v === groupBySecondary.value) { groupBySecondary.value = 'none'; groupByTertiary.value = 'none'; } });
watch(groupBySecondary, v => { if (v === 'none' || v === groupByTertiary.value) groupByTertiary.value = 'none'; });

// Reset step when modal opens
watch(() => props.show, v => { if (v) step.value = 1; });

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

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
    document.removeEventListener('pointermove', onPreviewDragMove);
    document.removeEventListener('pointerup', onPreviewDragEnd);
});
</script>
