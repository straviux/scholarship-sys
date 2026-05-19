<template>
    <IosModal
        :visible="show"
        width="520px"
        :title="currentPageTitle"
        :show-action="true"
        :action-disabled="isPrimaryActionDisabled"
        :action-icon="primaryActionIcon"
        close-icon="x"
        :icon-size="16"
        @update:visible="val => emit('update:show', val)"
        @close="handleLeadingAction"
        @action="handlePrimaryAction"
    >
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleLeadingAction"
                v-tooltip.bottom="leadingActionTooltip">
                <AppIcon :name="leadingActionIcon" :size="16" />
            </button>
        </template>

                    <template v-if="isSetupPage">
                    <!-- REPORT TYPE — iOS Segmented Control -->
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

                    <!-- FILTERS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Filters</div>
                        <div class="ios-card">
                            <!-- Recommendation -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="star-fill" :size="13" style="color: #FF9500;" />
                                    Recommendation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedRecommendation" :options="recommendationOptions"
                                        optionLabel="label" optionValue="value" placeholder="All" showClear
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Program -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="bookmark-fill" :size="13" style="color: #007AFF;" />
                                    Program
                                </div>
                                <div class="ios-row-control">
                                    <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All"
                                        :ios-compact="true"
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- School -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="building-2" :size="13" style="color: #34C759;" />
                                    School
                                </div>
                                <div class="ios-row-control">
                                    <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All"
                                        class="ios-select" :multiple="false" :ios-compact="true" />
                                </div>
                            </div>

                            <!-- Course -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="graduation-cap" :size="13" style="color: #AF52DE;" />
                                    Course
                                </div>
                                <div class="ios-row-control">
                                    <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id"
                                        label="shortname" custom-placeholder="All" class="ios-select"
                                        :ios-compact="true" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATE RANGE -->
                    <div class="ios-section">
                        <div class="ios-section-label">Interview Date Range</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-dates">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    From
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateFrom" placeholder="Any" showButtonBar dateFormat="M dd, yy"
                                        class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                                <span class="ios-date-separator">—</span>
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    To
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateTo" placeholder="Any" showButtonBar dateFormat="M dd, yy"
                                        class="ios-datepicker" showIcon iconDisplay="input" />
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
                                    <AppIcon name="objects-column" :size="13" style="color: #5856D6;" />
                                    Group By
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

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

                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="panel-right-open" :size="13" style="color: #34C759;" />
                                    Layout
                                </div>
                                <div class="text-xs text-gray-500">
                                    Use landscape so all report columns stay on one row.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clear Filters Banner -->
                    <div v-if="activeFiltersCount > 0" class="ios-section">
                        <button class="ios-destructive-btn" @click="clearAllFilters">
                            Clear All Filters ({{ activeFiltersCount }})
                        </button>
                    </div>

                    <!-- Bottom spacer -->
                    <div style="height: 24px;"></div>
                    </template>

                    <template v-else-if="isProfileSelectionPage">
                        <div class="ios-section">
                            <div class="ios-section-label">Search Profiles</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="search" :size="13" style="color: #007AFF;" />
                                        Search
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="profileSearchQuery" class="ios-input"
                                            placeholder="Name, program, school, or course" maxlength="255" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">Profiles To Print</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="users" :size="13" style="color: #007AFF;" />
                                        Selection
                                    </div>
                                    <div class="ios-row-control ios-selection-actions">
                                        <button class="ios-inline-btn" type="button" @click="selectAllFilteredProfiles"
                                            :disabled="visibleProfiles.length === 0">
                                            {{ selectAllButtonLabel }}
                                        </button>
                                        <button class="ios-inline-btn ios-inline-btn-secondary" type="button"
                                            @click="clearSelectedProfiles" :disabled="selectedDetailedRecords.length === 0">
                                            Clear
                                        </button>
                                    </div>
                                </div>

                                <div v-if="visibleProfiles.length" class="ios-profile-picker">
                                    <label v-for="record in visibleProfiles" :key="record.id" class="ios-profile-option">
                                        <Checkbox v-model="selectedProfileIds" :value="record.id" />
                                        <div class="ios-profile-copy">
                                            <div class="ios-profile-name">{{ record.profile.last_name }}, {{
                                                record.profile.first_name }}</div>
                                            <div class="ios-profile-meta">
                                                {{ record.program?.shortname || 'N/A' }}
                                                <template v-if="record.school?.shortname || record.school?.name">
                                                    · {{ record.school?.shortname || record.school?.name }}
                                                </template>
                                                <template v-if="record.course?.shortname || record.course?.name">
                                                    · {{ record.course?.shortname || record.course?.name }}
                                                </template>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div v-else class="ios-profile-empty">
                                    {{ filteredApplicants.length === 0
                                        ? 'No interviewed applicants match the current filters.'
                                        : 'No interviewed applicants match the current search.' }}
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                {{ selectedDetailedRecords.length }} of {{ filteredApplicants.length }} profile(s) selected.
                                <template v-if="profileSearchQuery.trim()">
                                    Showing {{ visibleProfiles.length }} search result(s).
                                </template>
                            </div>
                        </div>

                        <div style="height: 24px;"></div>
                    </template>

                    <template v-else>
                        <div class="ios-section">
                            <div class="ios-section-label">Report Details</div>
                            <div class="ios-card">
                                    <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="type-outline" :size="13" style="color: #007AFF;" />
                                        Report Title
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="reportTitleInput" class="ios-input"
                                            placeholder="INTERVIEWED APPLICANTS REPORT" maxlength="255" />
                                    </div>
                                </div>

                                <div v-if="reportType === 'list'" class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="message-square" :size="13" style="color: #007AFF;" />
                                        Show Interview Columns
                                    </div>
                                    <div class="ios-row-control" style="display:flex;justify-content:flex-end;">
                                        <ToggleSwitch v-model="includeInterviewColumns" />
                                    </div>
                                </div>

                                <div v-if="reportType === 'list'" class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="users" :size="13" style="color: #16A34A;" />
                                        Highlight JPM Names
                                    </div>
                                    <div class="ios-row-control" style="display:flex;justify-content:flex-end;align-items:center;gap:12px;">
                                        <span class="text-[11px] text-slate-500">Mark JPM-related applicants in print</span>
                                        <ToggleSwitch v-model="highlightJpmMembers" />
                                    </div>
                                </div>

                                <div v-else class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="file-text" :size="13" style="color: #8E8E93;" />
                                        Report Type
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Summary report
                                    </div>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                Leave blank to use INTERVIEWED APPLICANTS REPORT.
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">Signatories</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="user" :size="13" style="color: #34C759;" />
                                        Prepared By
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="preparedBy" class="ios-select" placeholder="Enter preparer name"
                                            maxlength="255" />
                                    </div>
                                </div>

                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="file" :size="13" style="color: #5856D6;" />
                                        Preparer Position
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="preparedByPosition" class="ios-select"
                                            placeholder="Enter preparer position" maxlength="255" />
                                    </div>
                                </div>

                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="building-2" :size="13" style="color: #34C759;" />
                                        Preparer Office
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="preparedByOffice" class="ios-select"
                                            placeholder="Enter preparer office" maxlength="255" />
                                    </div>
                                </div>

                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="user" :size="13" style="color: #007AFF;" />
                                        Approved By
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="approvedBy" class="ios-select"
                                            placeholder="Enter approver name" maxlength="255" />
                                    </div>
                                </div>

                                <div class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="file" :size="13" style="color: #FF9500;" />
                                        Approver Position
                                    </div>
                                    <div class="ios-row-control">
                                        <InputText v-model="approvedByPosition" class="ios-select"
                                            placeholder="Enter approver position" maxlength="255" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">Budget Allocation</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="wallet" :size="13" style="color: #34C759;" />
                                        Allocation
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="selectedBudgetAllocationKey" :options="budgetAllocationOptions"
                                            optionLabel="label" optionValue="value"
                                            :placeholder="budgetAllocationOptions.length ? 'Select allocation' : 'No allocation available'"
                                            :disabled="budgetAllocationOptions.length === 0" appendTo="body" class="ios-select"
                                                showClear>
                                                <template #value="{ value, placeholder }">
                                                    <div v-if="findBudgetAllocationOption(value)" class="leading-tight">
                                                        <div class="font-medium text-slate-700">{{ findBudgetAllocationOption(value)?.label }}</div>
                                                        <div v-if="findBudgetAllocationOption(value)?.description" class="text-[11px] text-slate-500">
                                                            {{ findBudgetAllocationOption(value)?.description }}
                                                        </div>
                                                    </div>
                                                    <span v-else class="text-slate-400">{{ placeholder }}</span>
                                                </template>
                                                <template #option="{ option }">
                                                    <div class="py-1 leading-tight">
                                                        <div class="font-medium text-slate-700">{{ option.label }}</div>
                                                        <div v-if="option.description" class="text-[11px] text-slate-500">
                                                            {{ option.description }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </Select>
                                    </div>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                <template v-if="selectedBudgetAllocation">
                                        {{ formatBudgetAllocationSelectionMessage(selectedBudgetAllocation) }}
                                </template>
                                <template v-else-if="budgetAllocationOptions.length">
                                    Select the budget allocation where the Current AY Estimated Grant will be monitored.
                                </template>
                                <template v-else>
                                    No budget allocation is available for monitoring.
                                </template>
                            </div>
                        </div>

                        <div v-if="selectedBudgetAllocation" class="ios-section">
                            <div class="ios-section-label">Cumulative Count Details</div>
                            <div class="ios-card space-y-3">
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-700">
                                        {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's are' : ' is' }} included in the cumulative count.
                                    </div>
                                    <div class="mt-1">
                                        This list follows the selected allocation's calendar year, regardless of program coverage or allocation window.
                                    </div>
                                </div>

                                <InputText
                                    v-model="cumulativeScholarSearchQuery"
                                    class="w-full"
                                    placeholder="Search cumulative scholars"
                                />

                                <div v-if="filteredCumulativeScholars.length" class="max-h-72 space-y-2 overflow-y-auto pr-1">
                                    <div
                                        v-for="scholar in filteredCumulativeScholars"
                                        :key="scholar.profile_id"
                                        class="rounded-3xl border border-slate-200 bg-white px-3 py-2"
                                    >
                                        <div class="text-sm font-semibold text-slate-800">{{ scholar.name }}</div>
                                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-600">
                                            <span>Program: {{ scholar.program || 'N/A' }}</span>
                                            <span>Approved: {{ formatDate(scholar.date_approved) }}</span>
                                            <span>Status: {{ formatScholarStatus(scholar.status) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                    <template v-if="selectedBudgetAllocationApprovedCount">
                                        No scholars match the current search.
                                    </template>
                                    <template v-else>
                                        No approved scholars are currently counted for this calendar year.
                                    </template>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                Showing {{ filteredCumulativeScholars.length.toLocaleString() }} of {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }}.
                            </div>
                        </div>

                        <div style="height: 24px;"></div>
                    </template>
    </IosModal>

    <PdfPreviewModal v-model:show="showPdfPreview" :htmlDoc="pdfPreviewHtml" :title="pdfPreviewTitle"
        :paperSize="pdfPaperSize" />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import moment from 'moment';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';
import InterviewedApplicantsTemplate from '../Pdf/InterviewedApplicantsTemplate.vue';
import { buildInterviewedApplicantsPdfDoc } from '../Pdf/pdf-styles';
import { renderVueTemplate } from '@/composables/usePdfPrint';

const props = defineProps({
    show: Boolean,
    interviewedApplicants: {
        type: Array,
        default: () => []
    },
    budgetAllocations: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show']);
const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';

// Filter States
const selectedRecommendation = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourse = ref(null);
const dateFrom = ref(null);
const dateTo = ref(null);
const preparedBy = ref(DEFAULT_PREPARED_BY);
const preparedByPosition = ref(DEFAULT_PREPARED_BY_POSITION);
const preparedByOffice = ref(DEFAULT_PREPARED_BY_OFFICE);
const approvedBy = ref(DEFAULT_APPROVED_BY);
const approvedByPosition = ref(DEFAULT_APPROVED_BY_POSITION);
const selectedBudgetAllocationKey = ref(null);
const selectedProfileIds = ref([]);
const currentPage = ref('setup');
const reportTitleInput = ref('');
const profileSearchQuery = ref('');
const cumulativeScholarSearchQuery = ref('');

// Report Options
const reportType = ref('list');
const groupBy = ref('none');
const paperSize = ref('A4');
const orientation = ref('landscape');
const includeInterviewColumns = ref(true);
const highlightJpmMembers = ref(false);

// PDF Preview
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref('');
const pdfPreviewTitle = ref('');
const pdfPaperSize = ref('a4');

// Options
const recommendationOptions = [
    { label: 'Recommended for Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' }
];

const groupByOptions = [
    { label: 'No Grouping', value: 'none' },
    { label: 'By Program', value: 'program' },
    { label: 'By School', value: 'school' },
    { label: 'By Course', value: 'course' },
    { label: 'By Recommendation', value: 'recommendation' },
    { label: 'By Interviewer', value: 'interviewer' },
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

// Computed
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedRecommendation.value) count++;
    if (selectedProgram.value) count++;
    if (selectedSchool.value) count++;
    if (selectedCourse.value) count++;
    if (dateFrom.value || dateTo.value) count++;
    return count;
});

const isSetupPage = computed(() => currentPage.value === 'setup');
const isProfileSelectionPage = computed(() => {
    return reportType.value === 'list' && currentPage.value === 'profiles';
});
const isSignatoriesPage = computed(() => currentPage.value === 'signatories');

const currentPageTitle = computed(() => {
    if (isProfileSelectionPage.value) {
        return 'Profiles To Print';
    }

    if (isSignatoriesPage.value) {
        return 'Report Details';
    }

    return 'Generate Report';
});

const leadingActionIcon = computed(() => {
    return currentPage.value === 'setup' ? 'x' : 'arrow-left';
});

const leadingActionTooltip = computed(() => {
    return currentPage.value === 'setup' ? 'Close' : 'Back';
});

const primaryActionIcon = computed(() => {
    return isSignatoriesPage.value ? 'check' : 'arrow-right';
});

const primaryActionTooltip = computed(() => {
    return isSignatoriesPage.value ? 'Generate Report' : 'Next';
});

const compareApplicantsByName = (left, right) => {
    const leftLastName = left?.profile?.last_name || '';
    const rightLastName = right?.profile?.last_name || '';
    const lastNameComparison = leftLastName.localeCompare(rightLastName, undefined, { sensitivity: 'base' });

    if (lastNameComparison !== 0) {
        return lastNameComparison;
    }

    const leftFirstName = left?.profile?.first_name || '';
    const rightFirstName = right?.profile?.first_name || '';
    return leftFirstName.localeCompare(rightFirstName, undefined, { sensitivity: 'base' });
};

// Methods
function filterApplicants() {
    let list = [...(props.interviewedApplicants || [])];
    if (selectedRecommendation.value) list = list.filter(r => r.recommendation === selectedRecommendation.value);
    if (selectedProgram.value) list = list.filter(r => r.program?.id === selectedProgram.value.id);
    if (selectedSchool.value) list = list.filter(r => r.school?.id === selectedSchool.value.id);
    if (selectedCourse.value) list = list.filter(r => r.course?.id === selectedCourse.value.id);
    if (dateFrom.value) list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrAfter(moment(dateFrom.value), 'day'));
    if (dateTo.value) list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrBefore(moment(dateTo.value), 'day'));
    return list.sort(compareApplicantsByName);
}

const filteredApplicants = computed(() => filterApplicants());
const filteredApplicantIds = computed(() => filteredApplicants.value.map(record => record.id));

const budgetAllocationCurrencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatBudgetAllocationAmount(allocation) {
    const amount = Number(allocation?.total_allotment);

    return Number.isFinite(amount) ? budgetAllocationCurrencyFormatter.format(amount) : null;
}

function getBudgetAllocationBaseName(allocation) {
    return allocation?.particular_name?.trim()
        || allocation?.description?.trim()
        || 'Unnamed Allocation';
}

function formatBudgetAllocationLabel(allocation) {
    const baseName = getBudgetAllocationBaseName(allocation);
    const rcCode = allocation?.rc_code?.trim();

    return rcCode ? `${baseName} [${rcCode}]` : baseName;
}

function formatBudgetAllocationDescription(allocation) {
    const description = allocation?.description?.trim();
    const baseName = getBudgetAllocationBaseName(allocation);

    return [
        description && description !== baseName ? description : null,
        formatBudgetAllocationAmount(allocation),
    ].filter(Boolean).join(' - ');
}

function getBudgetAllocationCalendarYear(allocation) {
    const candidates = [
        allocation?.calendar_year,
        allocation?.fiscal_year,
        allocation?.date_start,
        allocation?.date_end,
    ];

    for (const candidate of candidates) {
        if (candidate === null || candidate === undefined || candidate === '') {
            continue;
        }

        const match = String(candidate).match(/\b(\d{4})\b/);

        if (match) {
            return match[1];
        }
    }

    return null;
}

function formatBudgetAllocationSelectionMessage(allocation) {
    const label = formatBudgetAllocationLabel(allocation);
    const description = formatBudgetAllocationDescription(allocation);
    const calendarYear = getBudgetAllocationCalendarYear(allocation);
    const details = [
        description && description !== label ? description : null,
        calendarYear ? `Calendar year ${calendarYear}` : null,
    ].filter(Boolean);

    if (details.length) {
        return `Monitoring ${label} · ${details.join(' · ')}.`;
    }

    return `Monitoring ${label}.`;
}

function buildBudgetAllocationPayload(allocation) {
    if (!allocation) {
        return null;
    }

    const { approved_scholars, ...payload } = allocation;

    return payload;
}

const budgetAllocationOptions = computed(() => {
    return (props.budgetAllocations || []).map(allocation => ({
        label: formatBudgetAllocationLabel(allocation),
        description: formatBudgetAllocationDescription(allocation),
        value: allocation.key,
    }));
});
const selectedBudgetAllocation = computed(() => {
    return (props.budgetAllocations || []).find(allocation => allocation.key === selectedBudgetAllocationKey.value) || null;
});
const selectedBudgetAllocationApprovedCount = computed(() => {
    return Number(selectedBudgetAllocation.value?.approved_scholars_to_date ?? 0) || 0;
});
const cumulativeScholars = computed(() => {
    return [...(selectedBudgetAllocation.value?.approved_scholars || [])].sort((left, right) => {
        return (left?.name || '').localeCompare(right?.name || '', undefined, { sensitivity: 'base' });
    });
});
const filteredCumulativeScholars = computed(() => {
    const searchTerm = cumulativeScholarSearchQuery.value.trim().toLowerCase();

    if (!searchTerm) {
        return cumulativeScholars.value;
    }

    return cumulativeScholars.value.filter(scholar => {
        const haystack = [
            scholar?.name,
            scholar?.program,
            scholar?.date_approved,
            formatScholarStatus(scholar?.status),
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(searchTerm);
    });
});
function findBudgetAllocationOption(value) {
    return budgetAllocationOptions.value.find(option => option.value === value) || null;
}
const visibleProfiles = computed(() => {
    const searchTerm = profileSearchQuery.value.trim().toLowerCase();

    if (!searchTerm) {
        return filteredApplicants.value;
    }

    return filteredApplicants.value.filter(record => {
        const haystack = [
            record?.profile?.last_name,
            record?.profile?.first_name,
            record?.profile?.middle_name,
            record?.program?.shortname,
            record?.program?.name,
            record?.school?.shortname,
            record?.school?.name,
            record?.course?.shortname,
            record?.course?.name,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(searchTerm);
    });
});
const visibleProfileIds = computed(() => visibleProfiles.value.map(record => record.id));
const selectedDetailedRecords = computed(() => {
    const selectedIds = new Set(selectedProfileIds.value);
    return filteredApplicants.value.filter(record => selectedIds.has(record.id));
});
const printableRecords = computed(() => {
    return reportType.value === 'list' ? selectedDetailedRecords.value : filteredApplicants.value;
});
const resolvedReportTitle = computed(() => {
    return reportTitleInput.value.trim() || 'INTERVIEWED APPLICANTS REPORT';
});
const canProceedFromProfiles = computed(() => {
    return selectedDetailedRecords.value.length > 0;
});
const requiresBudgetAllocationSelection = computed(() => {
    return (props.budgetAllocations || []).length > 0 && printableRecords.value.length > 0;
});
const canGenerateReport = computed(() => {
    return !isDateToInvalid.value
        && (reportType.value !== 'list' || printableRecords.value.length > 0)
        && (!requiresBudgetAllocationSelection.value || Boolean(selectedBudgetAllocation.value));
});

const isPrimaryActionDisabled = computed(() => {
    if (isSetupPage.value) {
        return isDateToInvalid.value;
    }

    if (isProfileSelectionPage.value) {
        return !canProceedFromProfiles.value;
    }

    return !canGenerateReport.value;
});

const selectAllButtonLabel = computed(() => {
    return profileSearchQuery.value.trim() ? 'Select Visible' : 'Select All';
});

function selectAllFilteredProfiles() {
    const nextSelection = new Set(selectedProfileIds.value);

    visibleProfileIds.value.forEach(id => {
        nextSelection.add(id);
    });

    selectedProfileIds.value = [...nextSelection];
}

function clearSelectedProfiles() {
    selectedProfileIds.value = [];
}

function handleLeadingAction() {
    if (isSignatoriesPage.value) {
        currentPage.value = reportType.value === 'list' ? 'profiles' : 'setup';
        return;
    }

    if (isProfileSelectionPage.value) {
        currentPage.value = 'setup';
        return;
    }

    close();
}

function handlePrimaryAction() {
    if (isSetupPage.value) {
        currentPage.value = reportType.value === 'list' ? 'profiles' : 'signatories';
        return;
    }

    if (isProfileSelectionPage.value) {
        currentPage.value = 'signatories';
        return;
    }

    if (isSignatoriesPage.value) {
        generateReport();
        return;
    }

    if (reportType.value === 'list') {
        currentPage.value = 'profiles';
        return;
    }
}

function close() {
    emit('update:show', false);
}

function clearAllFilters() {
    selectedRecommendation.value = null;
    selectedProgram.value = null;
    selectedSchool.value = null;
    selectedCourse.value = null;
    dateFrom.value = null;
    dateTo.value = null;
}

watch(() => props.show, (value) => {
    if (value) {
        preparedBy.value = DEFAULT_PREPARED_BY;
        preparedByPosition.value = DEFAULT_PREPARED_BY_POSITION;
        preparedByOffice.value = DEFAULT_PREPARED_BY_OFFICE;
        approvedBy.value = DEFAULT_APPROVED_BY;
        approvedByPosition.value = DEFAULT_APPROVED_BY_POSITION;
        highlightJpmMembers.value = false;
        selectedBudgetAllocationKey.value = props.budgetAllocations.length === 1
            ? props.budgetAllocations[0].key
            : null;
        currentPage.value = 'setup';
        reportTitleInput.value = '';
        profileSearchQuery.value = '';
        cumulativeScholarSearchQuery.value = '';
        if (reportType.value === 'list') {
            selectAllFilteredProfiles();
        }
    }
});

watch(reportType, (value) => {
    currentPage.value = 'setup';
    profileSearchQuery.value = '';

    if (value === 'list' && selectedProfileIds.value.length === 0) {
        selectAllFilteredProfiles();
    }
});

watch(selectedBudgetAllocationKey, () => {
    cumulativeScholarSearchQuery.value = '';
});

watch(filteredApplicantIds, (newIds, oldIds = []) => {
    const selectedSet = new Set(selectedProfileIds.value);
    const hadAllOldSelected = oldIds.length > 0 && oldIds.every(id => selectedSet.has(id));

    if (hadAllOldSelected) {
        selectedProfileIds.value = [...newIds];
        return;
    }

    const visibleIds = new Set(newIds);
    const nextSelection = selectedProfileIds.value.filter(id => visibleIds.has(id));

    if (nextSelection.length !== selectedProfileIds.value.length) {
        selectedProfileIds.value = nextSelection;
    }
});

function generateReport() {
    if (!canGenerateReport.value) return;
    const recordsToPrint = printableRecords.value;
    const reportTitle = resolvedReportTitle.value;

    const previewPaperSizeMap = {
        A4: {
            portrait: 'a4',
            landscape: 'a4-landscape',
        },
        Letter: {
            portrait: 'letter',
            landscape: 'letter-landscape',
        },
        Legal: {
            portrait: 'long',
            landscape: 'landscape',
        },
    };
    const ps = previewPaperSizeMap[paperSize.value]?.[orientation.value] || 'a4-landscape';
    pdfPaperSize.value = ps;

    const bodyHtml = renderVueTemplate(InterviewedApplicantsTemplate, {
        records: recordsToPrint,
        reportType: reportType.value,
        groupBy: groupBy.value,
        today: moment().format('MMMM D, YYYY'),
        preparedBy: preparedBy.value?.trim() || DEFAULT_PREPARED_BY,
        preparedByPosition: preparedByPosition.value?.trim() || DEFAULT_PREPARED_BY_POSITION,
        preparedByOffice: preparedByOffice.value?.trim() || DEFAULT_PREPARED_BY_OFFICE,
        approvedBy: approvedBy.value?.trim() || DEFAULT_APPROVED_BY,
        approvedByPosition: approvedByPosition.value?.trim() || DEFAULT_APPROVED_BY_POSITION,
        budgetAllocation: buildBudgetAllocationPayload(selectedBudgetAllocation.value),
        reportTitle,
        includeInterviewColumns: includeInterviewColumns.value,
        highlightJpmMembers: reportType.value === 'list' ? highlightJpmMembers.value : false,
    });

    pdfPreviewTitle.value = reportTitle;
    pdfPreviewHtml.value = buildInterviewedApplicantsPdfDoc(bodyHtml, pdfPreviewTitle.value, ps);
    showPdfPreview.value = true;
}

function formatRecommendation(value) {
    const labels = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };
    return labels[value] || 'N/A';
}

function formatDate(date) {
    return date ? moment(date).format('MMM DD, YYYY') : '—';
}

function capitalize(str) {
    if (!str) return '—';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatScholarStatus(status) {
    const labels = {
        active: 'Active',
        completed: 'Completed',
        'completed-transferred': 'Completed - Transferred',
    };

    return labels[status] || capitalize(String(status || '').replace(/-/g, ' '));
}

</script>

