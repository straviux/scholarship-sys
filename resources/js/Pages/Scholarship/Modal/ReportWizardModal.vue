<template>
    <IosModal :visible="show" width="520px" body-style="padding: 0;" @update:visible="handleWizardVisibleUpdate">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleWizardBack">
                <AppIcon :name="step > 1 ? 'chevron-left' : 'x'" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">
                {{ stepTitles[step - 1] }}
            </span>
        </template>

        <template #header-right>
            <button v-if="step < 3" class="ios-nav-btn ios-nav-action" @click="handleWizardAdvance"
                :disabled="!canProceed">
                <AppIcon name="chevron-right" :size="16" />
            </button>
            <button v-else class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="generating"
                v-tooltip.bottom="'Generate Report'">
                <AppIcon v-if="generating" name="spinner" :size="16" class="animate-spin" />
                <template v-else>Generate</template>
            </button>
        </template>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div v-for="s in 3" :key="s"
                :class="['step-dot', s === step && 'step-dot-active', s < step && 'step-dot-done']">
            </div>
        </div>

        <div class="report-wizard-content">
            <!-- STEP 1: Report Type & Status -->
            <div v-show="step === 1">
                <div class="ios-section">
                    <div class="ios-section-label">Report Type</div>
                    <div class="ios-segmented-control">
                        <button :class="['ios-segment', reportType === 'list' && 'ios-segment-active']"
                            @click="reportType = 'list'">
                            <AppIcon name="list" :size="13" />
                            Detailed List
                        </button>
                        <button :class="['ios-segment', reportType === 'summary' && 'ios-segment-active']"
                            @click="reportType = 'summary'">
                            <AppIcon name="bar-chart-3" :size="13" />
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
                                <span v-if="opt.color" class="status-dot" :style="{ background: opt.color }"></span>
                                <AppIcon v-else name="list" :size="13" style="color: #8E8E93;" />
                                {{ opt.label }}
                            </div>
                            <AppIcon v-if="selectedStatus === opt.value" name="check" :size="13"
                                style="color: #007AFF;" />
                        </div>
                    </div>
                    <div class="ios-section-footer">
                        Choose a status, or "All Statuses" for a comprehensive report.
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
                                <AppIcon name="bookmark-fill" :size="13" style="color: #007AFF;" />
                                Program
                            </div>
                            <div class="ios-row-control">
                                <ProgramSelect v-model="selectedPrograms" label="shortname" custom-placeholder="All"
                                    class="ios-select" :multiple="true" />
                            </div>
                        </div>

                        <!-- School -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="building-2" :size="13" style="color: #34C759;" />
                                School
                            </div>
                            <div class="ios-row-control">
                                <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All"
                                    class="ios-select" :multiple="true" />
                            </div>
                        </div>

                        <!-- Course -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="graduation-cap" :size="13" style="color: #AF52DE;" />
                                Course
                            </div>
                            <div class="ios-row-control">
                                <CourseSelect v-model="selectedCourses"
                                    :scholarship-program-id="selectedPrograms?.[0]?.id" label="shortname"
                                    custom-placeholder="All" :multiple="true" class="ios-select" />
                            </div>
                        </div>

                        <!-- Municipality -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="map-pin" :size="13" style="color: #FF2D55;" />
                                Municipality
                            </div>
                            <div class="ios-row-control">
                                <MunicipalitySelect v-model="selectedMunicipalities" custom-placeholder="All"
                                    class="ios-select" :multiple="true" />
                            </div>
                        </div>

                        <!-- Year Level -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="sort-numeric-up" :size="13" style="color: #5856D6;" />
                                Year Level
                            </div>
                            <div class="ios-row-control">
                                <YearLevelSelect v-model="selectedYearLevels" custom-placeholder="All"
                                    class="ios-select" :multiple="true" />
                            </div>
                        </div>

                        <!-- Grant Provision -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="wallet" :size="13" style="color: #FF9500;" />
                                Grant Provision
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="selectedGrantProvisions" :options="grantProvisionOptions"
                                    optionLabel="label" optionValue="value" placeholder="All" showClear
                                    class="ios-select" :multiple="true" />
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
                                <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                From
                            </div>
                            <div class="ios-row-control">
                                <DatePicker v-model="dateFrom" placeholder="Select date" showButtonBar
                                    dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                            </div>
                            <span class="ios-date-separator">—</span>
                            <div class="ios-row-label">
                                <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
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
                    <div class="ios-section-label">Title</div>
                    <div class="ios-card">
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="type" :size="13" style="color: #007AFF;" />
                                Report Title
                            </div>
                            <div class="ios-row-control">
                                <InputText v-model="customReportTitle" class="ios-select"
                                    :placeholder="defaultReportTitle" />
                            </div>
                        </div>
                    </div>
                    <div class="ios-section-footer">
                        Leave blank to use {{ defaultReportTitle }}.
                    </div>
                </div>

                <div class="ios-section">
                    <div class="ios-section-label">Layout</div>
                    <div class="ios-card">
                        <!-- Paper Size -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="file" :size="13" style="color: #8E8E93;" />
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
                                <AppIcon name="desktop" :size="13" style="color: #FF9500;" />
                                Orientation
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                                    optionValue="value" class="ios-select" />
                            </div>
                        </div>

                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="user" :size="13" style="color: #34C759;" />
                                Prepared By
                            </div>
                            <div class="ios-row-control">
                                <InputText v-model="preparedBy" class="ios-select" placeholder="Optional" />
                            </div>
                        </div>
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="briefcase" :size="13" style="color: #8E8E93;" />
                                Designation
                            </div>
                            <div class="ios-row-control">
                                <InputText v-model="preparedByTitle" class="ios-select" placeholder="Optional" />
                            </div>
                        </div>
                    </div>
                    <div class="ios-section-footer">
                        Leave both fields blank to omit the prepared-by section.
                    </div>
                </div>

                <div class="ios-section">
                    <div class="ios-section-label">Signatory</div>
                    <div class="ios-card">
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="user-check" :size="13" style="color: #007AFF;" />
                                Noted By
                            </div>
                            <div class="ios-row-control">
                                <InputText v-model="signatoryName" class="ios-select" placeholder="Optional" />
                            </div>
                        </div>
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="briefcase" :size="13" style="color: #8E8E93;" />
                                Designation
                            </div>
                            <div class="ios-row-control">
                                <InputText v-model="signatoryTitle" class="ios-select" placeholder="Optional" />
                            </div>
                        </div>
                    </div>
                    <div class="ios-section-footer">
                        Leave both fields blank to omit the noted-by section.
                    </div>
                </div>

                <div class="ios-section">
                    <div class="ios-section-label">{{ reportType === 'summary' ? 'Summary Grouping' : 'Grouping'
                        }}</div>
                    <div class="ios-card">
                        <!-- Group By -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="objects-column" :size="13" style="color: #5856D6;" />
                                Group By
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                    optionValue="value" class="ios-select" />
                            </div>
                        </div>

                        <!-- Sub-Group By -->
                        <div class="ios-row" v-if="reportType === 'list' && groupBy && groupBy !== 'none'">
                            <div class="ios-row-label">
                                <AppIcon name="objects-column" :size="13" style="color: #8E8E93;" />
                                Sub-Group
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="groupBySecondary" :options="secondaryGroupByOptions"
                                    optionLabel="label" optionValue="value" placeholder="None" showClear
                                    class="ios-select" />
                            </div>
                        </div>

                        <!-- Tertiary Group By -->
                        <div class="ios-row"
                            v-if="reportType === 'list' && groupBySecondary && groupBySecondary !== 'none'">
                            <div class="ios-row-label">
                                <AppIcon name="objects-column" :size="13" style="color: #C7C7CC;" />
                                3rd Group
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="groupByTertiary" :options="tertiaryGroupByOptions" optionLabel="label"
                                    optionValue="value" placeholder="None" showClear class="ios-select" />
                            </div>
                        </div>
                    </div>
                    <div class="ios-section-footer" v-if="reportType === 'summary'">
                        Summary mode uses the selected group for the breakdown table on the report summary page.
                    </div>
                </div>

                <div class="ios-section">
                    <div class="ios-section-label">Options</div>
                    <div class="ios-card">
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="wallet" :size="13" style="color: #34C759;" />
                                Include Projected Expense
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="includeProjectedExpense" />
                            </div>
                        </div>

                        <div v-if="reportType === 'list' && canEnableJpmHighlighting" class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="user" :size="13" style="color: #16A34A;" />
                                Highlight JPM Members
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="enableJpmHighlighting" />
                            </div>
                        </div>

                        <!-- Sort By -->
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="arrow-up-down" :size="13" style="color: #5856D6;" />
                                Sort By
                            </div>
                            <div class="ios-row-control">
                                <Select v-model="sortBy" :options="sortByOptions" optionLabel="label"
                                    optionValue="value" class="ios-select" />
                            </div>
                        </div>
                    </div>

                    <div v-if="reportType === 'list' && canEnableJpmHighlighting" class="ios-section-footer">
                        JPM-tagged members are highlighted with a green row background in detailed list reports.
                    </div>
                </div>

                <div class="ios-section">
                    <div class="ios-section-label">Show/Hide Fields</div>
                    <div class="ios-card">
                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="map-pin" :size="13" style="color: #FF2D55;" />
                                Address
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="showAddress" />
                            </div>
                        </div>

                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="bookmark-fill" :size="13" style="color: #007AFF;" />
                                Program
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="showProgram" />
                            </div>
                        </div>

                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="building-2" :size="13" style="color: #34C759;" />
                                School
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="showSchool" />
                            </div>
                        </div>

                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="graduation-cap" :size="13" style="color: #AF52DE;" />
                                Course
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="showCourse" />
                            </div>
                        </div>

                        <div class="ios-row">
                            <div class="ios-row-label">
                                <AppIcon name="message-square" :size="13" style="color: #FF9500;" />
                                Remarks
                            </div>
                            <div class="ios-row-control" style="justify-content: flex-end;">
                                <InputSwitch v-model="showRemarks" />
                            </div>
                        </div>
                    </div>
                    <div class="ios-section-footer">
                        Toggle individual fields to show or hide them from the report.
                    </div>
                </div>
            </div>
            <div style="height: 24px;"></div>
        </div>

        <!-- Sticky Footer -->
        <div v-if="activeFiltersCount > 0" class="ios-footer">
            <button class="ios-destructive-btn" @click="clearAllFilters">
                Clear All Filters ({{ activeFiltersCount }})
            </button>
        </div>
    </IosModal>

    <!-- Report Preview Modal -->
    <IosModal :visible="showPreview" width="95vw" max-width="95vw" :modal-content-style="{ height: '90vh' }"
        body-style="padding: 0; flex: 1; display: flex; flex-direction: column; overflow: hidden;"
        @update:visible="showPreview = $event">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="showPreview = false">
                <AppIcon name="chevron-left" :size="13" /> Back
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">Report Preview</span>
        </template>

        <template #header-right>
            <div class="ios-nav-actions">
                <button class="ios-icon-btn" @click="doPrint" title="Print / Save as PDF">
                    <AppIcon name="printer" :size="16" style="color: #007AFF;" />
                </button>
            </div>
        </template>

        <!-- Zoom Toolbar -->
        <div class="flex items-center justify-between px-4 py-2
                    bg-[#f2f2f7] dark:bg-[#1e242b]
                    border-b border-[#e5e5ea] dark:border-white/10">
            <div class="flex items-center gap-1.5">
                <button @click="zoomLevel = Math.max(40, zoomLevel - 10)"
                    class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                    :disabled="zoomLevel <= 40">
                    <AppIcon name="minus" :size="10" />
                </button>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 w-12 text-center">{{ zoomLevel
                }}%</span>
                <button @click="zoomLevel = Math.min(200, zoomLevel + 10)"
                    class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                    :disabled="zoomLevel >= 200">
                    <AppIcon name="plus" :size="10" />
                </button>
            </div>
            <span class="text-xs text-gray-400">{{ orientation === 'landscape' ? 'Landscape' : 'Portrait' }} ·
                {{ paperSize }}</span>
        </div>

        <!-- Preview Body -->
        <div class="overflow-auto bg-[#d1d1d6] dark:bg-[#1c1c1e]" style="flex: 1; min-height: 0; padding: 16px 0;">
            <div style="display: flex; justify-content: center;">
                <iframe v-if="previewHtml" :srcdoc="previewHtml"
                    :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', border: 'none', background: '#fff', boxShadow: '0 2px 20px rgba(0,0,0,0.15)', width: iframeWidth + 'px', height: iframeHeight + 'px' }"
                    frameborder="0"></iframe>
                <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <AppIcon name="spinner" class="w-8 h-8 mb-3" />
                    <p>Generating report...</p>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import axios from 'axios';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { useSystemOptions } from '@/composables/useSystemOptions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { renderVueTemplate } from '@/composables/usePdfPrint';
import { pagedjsPolyfillScript } from '@/utils/pagedjsPolyfill';
import { getProfileReportTitle } from '@/Pages/Scholarship/Reports/report-helpers';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';

// Custom Select Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

import ProfileReportTemplate from '@/Pages/Scholarship/Reports/ProfileReportTemplate.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show']);

// ─── State ───
const step = ref(1);
const generating = ref(false);
const showPreview = ref(false);
const previewHtml = ref('');
const zoomLevel = ref(130);

// Step 1
const reportType = ref('list');
const selectedStatus = ref(null); // null = All

// Step 2
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedPrograms = ref([]);
const selectedSchools = ref([]);
const selectedCourses = ref([]);
const selectedMunicipalities = ref([]);
const selectedYearLevels = ref([]);
const selectedGrantProvisions = ref([]);

// Step 3
const paperSize = ref('A4');
const orientation = ref('landscape');
const customReportTitle = ref('');
const preparedByTitle = ref('');
const signatoryName = ref('');
const signatoryTitle = ref('');
const groupBy = ref('none');
const groupBySecondary = ref('none');
const groupByTertiary = ref('none');
const showSequenceNumbers = ref(true);
const includeRemarks = ref(false);
const includeGrantProvision = ref(true);
const includeProjectedExpense = ref(true);
const enableJpmHighlighting = ref(false);
const jpmFilter = ref('all');
const sortBy = ref('default');
const showAddress = ref(true);
const showProgram = ref(true);
const showSchool = ref(true);
const showCourse = ref(true);
const showRemarks = ref(false);

// ─── Composables ───
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const preparedBy = ref('');
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
        { value: 'approved_history', label: 'Approved', color: '#3B82F6' },
        { value: 'denied_history', label: 'Denied', color: '#EF4444' },
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
const grantProvisionOptions = computed(() => _grantProvisionRaw.value);

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

const sortByOptions = [
    { label: 'Default Order', value: 'default' },
    { label: 'Date Filed (Oldest First)', value: 'date_filed_asc' },
    { label: 'Date Filed (Newest First)', value: 'date_filed_desc' },
    { label: 'Date Approved (Oldest First)', value: 'date_approved_asc' },
    { label: 'Date Approved (Newest First)', value: 'date_approved_desc' },
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
    if (selectedPrograms.value?.length > 0) count++;
    if (selectedSchools.value?.length > 0) count++;
    if (selectedCourses.value?.length > 0) count++;
    if (selectedMunicipalities.value?.length > 0) count++;
    if (selectedYearLevels.value?.length > 0) count++;
    if (selectedGrantProvisions.value?.length > 0) count++;
    return count;
});

const defaultReportTitle = computed(() => getProfileReportTitle(selectedStatus.value, reportType.value));
const resolvedReportTitle = computed(() => customReportTitle.value?.trim() || defaultReportTitle.value);

const paperConfig = computed(() => getReportPaperConfig(paperSize.value, orientation.value));
const SCHOLARSHIP_REPORT_PAGED_JS_ENABLED = false;
const iframeWidth = computed(() => paperConfig.value.widthPx);
const iframePagedHeight = ref(null); // set by postMessage from Paged.js after render
const iframeHeight = computed(() => iframePagedHeight.value ?? paperConfig.value.heightPx);

function _onPagedMessage(e) {
    if (e.data?.type === 'pagedjs:rendered') {
        iframePagedHeight.value = e.data.height;
    }
}

function resetPreviewState() {
    window.removeEventListener('message', _onPagedMessage);
    showPreview.value = false;
    previewHtml.value = '';
    iframePagedHeight.value = null;
}

watch(showPreview, (v) => {
    if (v) {
        iframePagedHeight.value = null;
        window.addEventListener('message', _onPagedMessage);
    } else {
        window.removeEventListener('message', _onPagedMessage);
    }
});

// ─── Methods ───
function handleWizardVisibleUpdate(value) {
    if (!value) {
        close();
        return;
    }

    emit('update:show', value);
}

function handleWizardBack() {
    if (step.value > 1) {
        step.value -= 1;
        return;
    }

    close();
}

function handleWizardAdvance() {
    if (!canProceed.value || step.value >= 3) {
        return;
    }

    step.value += 1;
}

function close() {
    resetPreviewState();
    emit('update:show', false);
    // Reset to step 1 on close
    setTimeout(() => { step.value = 1; }, 300);
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedPrograms.value = [];
    selectedSchools.value = [];
    selectedCourses.value = [];
    selectedMunicipalities.value = [];
    selectedYearLevels.value = [];
    selectedGrantProvisions.value = [];
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        // Build query params
        const params = {};
        if (selectedStatus.value) params.unified_status = selectedStatus.value;
        if (selectedPrograms.value?.length > 0) {
            params.program = selectedPrograms.value.map(p => p.id).join(',');
        }
        if (selectedSchools.value?.length > 0) {
            params.school = selectedSchools.value.map(s => s.shortname).join(',');
        }
        if (selectedCourses.value?.length > 0) {
            params.courses = selectedCourses.value.map(c => c.name).join(',');
        }
        if (selectedMunicipalities.value?.length > 0) {
            params.municipality = selectedMunicipalities.value.map(m => m.name).join(',');
        }
        if (selectedYearLevels.value?.length > 0) {
            params.year_level = selectedYearLevels.value.map(y => y.value).join(',');
        }
        if (selectedGrantProvisions.value?.length > 0) {
            params.grant_provision = selectedGrantProvisions.value.join(',');
        }
        if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
        if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');
        if (enableJpmHighlighting.value) params.enable_jpm_highlighting = 1;
        if (jpmFilter.value === 'jpm_only') params.show_jpm_only = 1;
        if (jpmFilter.value === 'hide_jpm') params.hide_jpm = 1;

        // Fetch data
        const response = await axios.get(route('profile.generateReport'), { params });
        let records = [];
        if (Array.isArray(response.data)) {
            records = response.data;
        } else if (Array.isArray(response.data?.data)) {
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
                includeProjectedExpense: includeProjectedExpense.value,
                enableJpmHighlighting: enableJpmHighlighting.value,
                jpmFilter: jpmFilter.value,
                selectedStatus: selectedStatus.value,
                reportTitle: customReportTitle.value?.trim() || '',
                preparedBy: preparedBy.value?.trim() || '',
                groupBy: groupBy.value,
                groupBySecondary: groupBySecondary.value !== 'none' ? groupBySecondary.value : null,
                groupByTertiary: groupByTertiary.value !== 'none' ? groupByTertiary.value : null,
                preparedByTitle: preparedByTitle.value?.trim() || '',
                signatoryName: signatoryName.value?.trim() || '',
                signatoryTitle: signatoryTitle.value?.trim() || '',
                sortBy: sortBy.value,
                showAddress: showAddress.value,
                showProgram: showProgram.value,
                showSchool: showSchool.value,
                showCourse: showCourse.value,
                showRemarks: showRemarks.value,
            },
            generatedAt: moment().format('MMMM DD, YYYY — h:mm A'),
        };

        // Pick template
        const TemplateComponent = ProfileReportTemplate;

        // Render to HTML
        const bodyHtml = renderVueTemplate(TemplateComponent, templateProps);

        // Build full document
        const fullHtml = buildReportDoc(bodyHtml, resolvedReportTitle.value, paperConfig.value);

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
    if (selectedPrograms.value?.length > 0) {
        summary.Program = selectedPrograms.value.map(p => p.shortname || p.name).join(', ');
    }
    if (selectedSchools.value?.length > 0) {
        summary.School = selectedSchools.value.map(s => s.shortname || s.name).join(', ');
    }
    if (selectedCourses.value?.length > 0) {
        summary.Course = selectedCourses.value.map(c => c.shortname || c.name).join(', ');
    }
    if (selectedMunicipalities.value?.length > 0) {
        summary.Municipality = selectedMunicipalities.value.map(m => m.name).join(', ');
    }
    if (selectedYearLevels.value?.length > 0) {
        summary['Year Level'] = selectedYearLevels.value.map(y => y.value).join(', ');
    }
    if (selectedGrantProvisions.value?.length > 0) {
        summary['Grant Provision'] = selectedGrantProvisions.value.join(', ');
    }
    if (dateFrom.value) summary['Date From'] = moment(dateFrom.value).format('MMM DD, YYYY');
    if (dateTo.value) summary['Date To'] = moment(dateTo.value).format('MMM DD, YYYY');
    return summary;
}

function getReportTitle() {
    return resolvedReportTitle.value;
}

function getReportLifecycleScript() {
    const lines = [
        'function finalizeRender() {',
        '  try {',
        "    var splitTables = document.querySelectorAll('table[data-split-from]');",
        '',
        '    splitTables.forEach(function (table) {',
        "      var ref = table.getAttribute('data-ref');",
        '',
        '      if (!ref) {',
        '        return;',
        '      }',
        '',
        "      var originals = document.querySelectorAll('table[data-ref=\"' + ref + '\"]:not([data-split-from])');",
        '      var original = originals[0];',
        '',
        '      if (!original) {',
        '        return;',
        '      }',
        '',
        "      if (!table.querySelector(':scope > colgroup')) {",
        "        var originalColgroups = original.querySelectorAll(':scope > colgroup');",
        '',
        '        originalColgroups.forEach(function (colgroup) {',
        '          table.insertBefore(colgroup.cloneNode(true), table.firstChild);',
        '        });',
        '      }',
        '',
        "      if (table.querySelector(':scope > thead')) {",
        '        return;',
        '      }',
        '',
        "      var originalHead = original.querySelector(':scope > thead');",
        '',
        '      if (!originalHead) {',
        '        return;',
        '      }',
        '',
        '      table.insertBefore(originalHead.cloneNode(true), table.firstChild);',
        '    });',
        '  } catch (error) {',
        "    console.warn('Scholarship report split table normalization failed', error);",
        '  }',
        '',
        "  var pages = document.querySelector('.pagedjs_pages');",
        '  var h = pages ? pages.scrollHeight + 48 : document.documentElement.scrollHeight;',
        "  window.parent.postMessage({ type: 'pagedjs:rendered', height: h }, '*');",
        "  document.body.style.visibility = 'visible';",
        "  if (document.body.getAttribute('data-auto-print') === '1') {",
        '    window.print();',
        '  }',
        '}',
    ];

    if (SCHOLARSHIP_REPORT_PAGED_JS_ENABLED) {
        lines.push("window.PagedPolyfill.on('rendered', finalizeRender);");
    } else {
        lines.push('var scheduleFinalize = function () {');
        lines.push('  window.requestAnimationFrame(finalizeRender);');
        lines.push('};');
        lines.push("if (document.readyState === 'complete') {");
        lines.push('  scheduleFinalize();');
        lines.push('} else {');
        lines.push("  window.addEventListener('load', scheduleFinalize, { once: true });");
        lines.push('}');
    }

    return lines.join('\n');
}

function buildReportDoc(bodyHtml, title, paperSettings) {
    const pagedJsScriptTag = SCHOLARSHIP_REPORT_PAGED_JS_ENABLED
        ? `\n    <script>${pagedjsPolyfillScript}<\/script>`
        : '';
    const lifecycleScript = getReportLifecycleScript();

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>${title}</title>
  <style>
    body { visibility: hidden; margin: 0; padding: 0; }
    ${getReportCss(paperSettings)}
  </style>
${pagedJsScriptTag}
  <script>
${lifecycleScript}
  <\/script>
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
    // Inject data-auto-print so Paged.js triggers window.print() after rendering
    const htmlForPrint = previewHtml.value.replace('<body>', '<body data-auto-print="1">');
    win.document.write(htmlForPrint);
    win.document.close();
}

// ─── Watchers ───
watch(enableJpmHighlighting, v => { if (!v) jpmFilter.value = 'all'; });
watch(groupBy, v => { if (v === 'none' || v === groupBySecondary.value) { groupBySecondary.value = 'none'; groupByTertiary.value = 'none'; } });
watch(groupBySecondary, v => { if (v === 'none' || v === groupByTertiary.value) groupByTertiary.value = 'none'; });

// Reset step when modal opens
watch(() => props.show, v => {
    if (v) {
        nextTick(() => {
            step.value = 1;
            customReportTitle.value = '';
            preparedBy.value = '';
            preparedByTitle.value = '';
            signatoryName.value = '';
            signatoryTitle.value = '';
            sortBy.value = 'default';
            showAddress.value = true;
            showProgram.value = true;
            showSchool.value = true;
            showCourse.value = true;
            showRemarks.value = false;
        });
    } else {
        resetPreviewState();
    }
});

onBeforeUnmount(() => {
    resetPreviewState();
});
</script>
