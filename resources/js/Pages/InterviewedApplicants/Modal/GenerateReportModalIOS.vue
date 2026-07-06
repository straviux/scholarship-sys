<template>
    <Dialog :visible="show" :modal="true" :draggable="true" :closable="false"
        :style="{ width: '620px' }" :breakpoints="{ '640px': '90vw' }"
        @update:visible="val => emit('update:show', val)">
        <template #header>
            <div class="flex items-center gap-2">
                <button class="p-1 !rounded-full !bg-transparent hover:!bg-gray-100 !border-none cursor-pointer" @click="close">
                    <AppIcon name="x" :size="16" />
                </button>
                <span class="text-lg font-semibold">Generate Report</span>
            </div>
        </template>

        <!-- ═══ STEPPER ═══ -->
        <div class="flex items-center justify-center gap-0 pt-2 pb-4">
            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                :class="{ 'opacity-40 cursor-default': currentStep !== 1 }" @click="currentStep = 1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white transition-colors"
                    :class="currentStep > 1 ? 'bg-green-500' : 'bg-blue-500'">
                    <AppIcon v-if="currentStep > 1" name="check" :size="10" />
                    <span v-else>1</span>
                </div>
                <span class="text-xs font-medium whitespace-nowrap"
                    :class="currentStep === 1 ? 'text-blue-500 font-semibold' : currentStep > 1 ? 'text-green-500' : 'text-gray-400'">Filters</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 transition-colors"
                :class="currentStep > 1 ? 'bg-green-500' : 'bg-gray-200'" />
            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                :class="{ 'opacity-40 cursor-default': currentStep !== 2 }" @click="currentStep = 2">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white transition-colors"
                    :class="currentStep > 2 ? 'bg-green-500' : currentStep === 2 ? 'bg-blue-500' : 'bg-gray-300'">
                    <AppIcon v-if="currentStep > 2" name="check" :size="10" />
                    <span v-else>2</span>
                </div>
                <span class="text-xs font-medium whitespace-nowrap"
                    :class="currentStep === 2 ? 'text-blue-500 font-semibold' : currentStep > 2 ? 'text-green-500' : 'text-gray-400'">Profiles</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 transition-colors"
                :class="currentStep > 2 ? 'bg-green-500' : 'bg-gray-200'" />
            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                :class="{ 'opacity-40 cursor-default': currentStep !== 3 }" @click="currentStep = 3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white transition-colors"
                    :class="currentStep > 3 ? 'bg-green-500' : currentStep === 3 ? 'bg-blue-500' : 'bg-gray-300'">
                    <AppIcon v-if="currentStep > 3" name="check" :size="10" />
                    <span v-else>3</span>
                </div>
                <span class="text-xs font-medium whitespace-nowrap"
                    :class="currentStep === 3 ? 'text-blue-500 font-semibold' : currentStep > 3 ? 'text-green-500' : 'text-gray-400'">Details</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 transition-colors"
                :class="currentStep > 3 ? 'bg-green-500' : 'bg-gray-200'" />
            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                :class="{ 'opacity-40 cursor-default': currentStep !== 4 }" @click="currentStep = 4">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white transition-colors"
                    :class="currentStep === 4 ? 'bg-blue-500' : 'bg-gray-300'">
                    <span>4</span>
                </div>
                <span class="text-xs font-medium whitespace-nowrap"
                    :class="currentStep === 4 ? 'text-blue-500 font-semibold' : 'text-gray-400'">Layout</span>
            </div>
        </div>

        <div class="max-h-[60vh] overflow-y-auto">
            <!-- ═══ STEP 1: FILTERS ═══ -->
            <div v-show="currentStep === 1">
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Type</label>
                    <div class="flex border border-gray-300 rounded-lg overflow-hidden">
                        <button :class="['flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium border-none cursor-pointer transition-colors',
                            reportType === 'list' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50']"
                            @click="reportType = 'list'">
                            <AppIcon name="list" :size="13" /> Detailed List
                        </button>
                        <button :class="['flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium border-none cursor-pointer transition-colors',
                            reportType === 'summary' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50']"
                            @click="reportType = 'summary'">
                            <AppIcon name="bar-chart-3" :size="13" /> Summary
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-2 gap-2.5 mb-4">
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Recommendation</label>
                        <Select v-model="selectedRecommendation" :options="recommendationOptions"
                            optionLabel="label" optionValue="value" placeholder="All" showClear
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Program</label>
                        <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All"
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">School</label>
                        <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All"
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" :multiple="false" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Course</label>
                        <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id"
                            label="shortname" custom-placeholder="All"
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                </div>

                <!-- Date Range -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Interview Date Range</label>
                    <div class="flex items-center gap-2">
                        <DatePicker v-model="dateFrom" placeholder="From date" showButtonBar dateFormat="M dd, yy"
                            class="flex-1 [&_.p-datepicker]:w-full [&_.p-datepicker]:text-xs" showIcon iconDisplay="input" />
                        <span class="text-gray-400 text-sm">—</span>
                        <DatePicker v-model="dateTo" placeholder="To date" showButtonBar dateFormat="M dd, yy"
                            class="flex-1 [&_.p-datepicker]:w-full [&_.p-datepicker]:text-xs" showIcon iconDisplay="input" />
                    </div>
                    <div v-if="dateTo && dateFrom && isDateToInvalid" class="text-[11px] text-red-500 mt-1">Date To must be after Date From</div>
                </div>

                <div v-if="activeFiltersCount > 0" class="pt-2 border-t border-gray-100">
                    <button class="inline-flex items-center gap-1 text-[11px] font-medium text-red-500 bg-red-50 border border-red-200 rounded-md px-2.5 py-1 cursor-pointer hover:bg-red-100"
                        @click="clearAllFilters">Clear {{ activeFiltersCount }} filter(s)</button>
                </div>
            </div>

            <!-- ═══ STEP 2: PROFILES ═══ -->
            <div v-show="currentStep === 2 && reportType === 'list'">
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Profiles To Print</label>
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <InputText v-model="profileSearchQuery" placeholder="Search name, program, school…" class="flex-1 mr-2 [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        <div class="flex gap-1">
                            <button class="px-3 py-1 text-xs font-medium rounded-full bg-blue-500 text-white border-none cursor-pointer disabled:opacity-40"
                                :disabled="visibleProfiles.length === 0" @click="selectAllFilteredProfiles">{{ selectAllButtonLabel }}</button>
                            <button class="px-3 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-600 border-none cursor-pointer disabled:opacity-40"
                                :disabled="selectedDetailedRecords.length === 0" @click="clearSelectedProfiles">Clear</button>
                        </div>
                    </div>
                    <div v-if="visibleProfiles.length" class="max-h-60 overflow-y-auto space-y-1">
                        <label v-for="record in visibleProfiles" :key="record.id"
                            class="flex items-start gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer">
                            <Checkbox v-model="selectedProfileIds" :value="record.id" />
                            <div class="text-xs leading-snug">
                                <div class="font-semibold text-gray-800">{{ record.profile.last_name }}, {{ record.profile.first_name }}</div>
                                <div class="text-gray-500">{{ record.program?.shortname || 'N/A' }}
                                    <span v-if="record.school?.shortname">· {{ record.school.shortname }}</span>
                                    <span v-if="record.course?.shortname">· {{ record.course.shortname }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div v-else class="text-center py-6 text-xs text-gray-400">
                        {{ filteredApplicants.length === 0 ? 'No interviewed applicants match the current filters.' : 'No profiles match the current search.' }}
                    </div>
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                    {{ selectedDetailedRecords.length }} of {{ filteredApplicants.length }} profile(s) selected.
                    <span v-if="profileSearchQuery.trim()">· {{ visibleProfiles.length }} search result(s).</span>
                </div>
            </div>

            <!-- ═══ STEP 3: DETAILS ═══ -->
            <div v-show="currentStep === 3">
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title</label>
                    <InputText v-model="reportTitleInput" placeholder="INTERVIEWED APPLICANTS REPORT"
                        class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                    <span class="block text-[10px] text-gray-400 mt-1">Leave blank to use the default title.</span>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Budget Allocation</label>
                        <ToggleSwitch v-model="showBudgetAllocation" />
                    </div>
                    <template v-if="showBudgetAllocation">
                        <Select v-model="selectedBudgetAllocationKey" :options="budgetAllocationOptions"
                            optionLabel="label" optionValue="value"
                            :placeholder="budgetAllocationOptions.length ? 'Select allocation' : 'No allocation available'"
                            :disabled="budgetAllocationOptions.length === 0" showClear appendTo="body"
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5">
                            <template #value="{ value, placeholder }">
                                <div v-if="findBudgetAllocationOption(value)" class="leading-tight">
                                    <div class="font-medium text-gray-700">{{ findBudgetAllocationOption(value)?.label }}</div>
                                    <div v-if="findBudgetAllocationOption(value)?.description" class="text-[11px] text-gray-500">{{ findBudgetAllocationOption(value)?.description }}</div>
                                </div>
                                <span v-else class="text-gray-400">{{ placeholder }}</span>
                            </template>
                        </Select>
                        <div v-if="selectedBudgetAllocation" class="mt-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                            <span class="font-semibold text-gray-700">{{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's are' : ' is' }} included in the cumulative count.</span>
                            <span class="text-gray-400 ml-2">This list follows the selected allocation's calendar year.</span>
                        </div>
                    </template>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Signatories</label>
                        <ToggleSwitch v-model="showSignatories" />
                    </div>
                    <template v-if="showSignatories">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-medium text-gray-500 mb-1">Prepared By</label>
                                <InputText v-model="preparedBy" placeholder="Name"
                                    class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                                <InputText v-model="preparedByPosition" placeholder="Position"
                                    class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                                <InputText v-model="preparedByOffice" placeholder="Office"
                                    class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-[10px] font-medium text-gray-500 mb-1">Approved By</label>
                                <InputText v-model="approvedBy" placeholder="Name"
                                    class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                                <InputText v-model="approvedByPosition" placeholder="Position"
                                    class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                            </div>
                        </div>
                        <span class="block text-[10px] text-gray-400 mt-1">Leave all fields blank to hide signature block.</span>
                    </template>
                </div>
            </div>

            <!-- ═══ STEP 4: LAYOUT ═══ -->
            <div v-show="currentStep === 4">
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Group By</label>
                    <Select v-model="groupBy" :options="groupByOptions" optionLabel="label" optionValue="value"
                        class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Paper &amp; Orientation</label>
                    <div class="flex gap-2">
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Options</label>
                    <div class="flex flex-col gap-0.5">
                        <label v-if="reportType === 'list'" class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Show Interview Columns</span>
                            <ToggleSwitch v-model="includeInterviewColumns" />
                        </label>
                        <label v-if="reportType === 'list'" class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Highlight JPM Names</span>
                            <ToggleSwitch v-model="highlightJpmMembers" />
                        </label>
                        <label v-if="reportType === 'list'" class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer hover:text-gray-900">
                            <span>Show Remarks</span>
                            <ToggleSwitch v-model="showRemarks" />
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ NAVIGATION ═══ -->
        <div class="flex items-center justify-between pt-3 mt-2 border-t border-gray-100">
            <button v-if="currentStep > 1"
                class="inline-flex items-center gap-1 text-sm font-semibold text-blue-500 bg-transparent px-4 py-2 rounded-lg cursor-pointer transition-colors hover:bg-blue-50 border-none"
                @click="currentStep--">
                <AppIcon name="chevron-left" :size="13" /> Back
            </button>
            <div v-else></div>
            <button v-if="currentStep < 4"
                class="inline-flex items-center gap-1 text-sm font-semibold text-white bg-blue-500 px-4 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-blue-600"
                @click="currentStep++">
                Next <AppIcon name="chevron-right" :size="13" />
            </button>
            <button v-else
                class="inline-flex items-center gap-1 text-sm font-bold text-white bg-green-500 px-5 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="generateReport" :disabled="!canGenerateReport">
                <AppIcon name="printer" :size="14" /> Generate Report
            </button>
        </div>
    </Dialog>

    <PdfPreviewModal v-model:show="showPdfPreview" :htmlDoc="pdfPreviewHtml" :title="pdfPreviewTitle"
        :paperSize="pdfPaperSize" />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import moment from 'moment';
import AppIcon from '@/Components/ui/AppIcon.vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';

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
const reportTitleInput = ref('');
const profileSearchQuery = ref('');
const cumulativeScholarSearchQuery = ref('');
const showBudgetAllocation = ref(false);
const showSignatories = ref(false);
// Steps
const currentStep = ref(1);

// Report Options
const reportType = ref('list');
const groupBy = ref('none');
const paperSize = ref('Legal');
const orientation = ref('landscape');
const includeInterviewColumns = ref(false);
const highlightJpmMembers = ref(false);
const showRemarks = ref(false);

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
    return allocation?.description?.trim()
        || allocation?.particular_name?.trim()
        || 'Unnamed Allocation';
}

function formatBudgetAllocationLabel(allocation) {
    const baseName = getBudgetAllocationBaseName(allocation);
    const rcCode = allocation?.rc_code?.trim();

    return rcCode ? `${baseName} [${rcCode}]` : baseName;
}

function formatBudgetAllocationDescription(allocation) {
    const baseName = getBudgetAllocationBaseName(allocation);
    const particularName = allocation?.particular_name?.trim();
    const altName = particularName && particularName !== baseName ? particularName : null;

    return [
        altName,
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
        && (!requiresBudgetAllocationSelection.value || !showBudgetAllocation.value || Boolean(selectedBudgetAllocation.value));
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

const close = () => emit('update:show', false);

// Reset on open
watch(() => props.show, (value) => {
    if (value) {
        currentStep.value = 1;
        preparedBy.value = DEFAULT_PREPARED_BY;
        preparedByPosition.value = DEFAULT_PREPARED_BY_POSITION;
        preparedByOffice.value = DEFAULT_PREPARED_BY_OFFICE;
        approvedBy.value = DEFAULT_APPROVED_BY;
        approvedByPosition.value = DEFAULT_APPROVED_BY_POSITION;
        highlightJpmMembers.value = false;
        showRemarks.value = false;
        selectedBudgetAllocationKey.value = props.budgetAllocations.length === 1 ? props.budgetAllocations[0].key : null;
        reportTitleInput.value = '';
        profileSearchQuery.value = '';
        if (reportType.value === 'list') {
            selectAllFilteredProfiles();
        }
    }
});

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
        showRemarks.value = false;
        selectedBudgetAllocationKey.value = props.budgetAllocations.length === 1
            ? props.budgetAllocations[0].key
            : null;
        currentStep.value = 1;
        reportTitleInput.value = '';
        profileSearchQuery.value = '';
        if (reportType.value === 'list') {
            selectAllFilteredProfiles();
        }
    }
});

watch(reportType, (value) => {
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
        preparedBy: showSignatories.value ? (preparedBy.value?.trim() || DEFAULT_PREPARED_BY) : '',
        preparedByPosition: showSignatories.value ? (preparedByPosition.value?.trim() || DEFAULT_PREPARED_BY_POSITION) : '',
        preparedByOffice: showSignatories.value ? (preparedByOffice.value?.trim() || DEFAULT_PREPARED_BY_OFFICE) : '',
        approvedBy: showSignatories.value ? (approvedBy.value?.trim() || DEFAULT_APPROVED_BY) : '',
        approvedByPosition: showSignatories.value ? (approvedByPosition.value?.trim() || DEFAULT_APPROVED_BY_POSITION) : '',
        budgetAllocation: showBudgetAllocation.value ? buildBudgetAllocationPayload(selectedBudgetAllocation.value) : null,
        reportTitle,
        includeInterviewColumns: includeInterviewColumns.value,
        highlightJpmMembers: reportType.value === 'list' ? highlightJpmMembers.value : false,
        showRemarks: reportType.value === 'list' ? showRemarks.value : false,
        reviewedBy: showSignatories.value ? (approvedBy.value?.trim() || DEFAULT_APPROVED_BY) : '',
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
