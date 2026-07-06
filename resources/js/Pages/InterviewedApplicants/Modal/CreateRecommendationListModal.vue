<template>
    <Dialog :visible="show" :modal="true" :closable="false"
        :style="{ width: '620px' }" :breakpoints="{ '640px': '90vw' }"
        @update:visible="val => emit('update:show', val)">
        <template #header>
            <div class="flex items-center gap-2">
                <button class="p-1 !rounded-full !bg-transparent hover:!bg-gray-100 !border-none cursor-pointer" @click="close">
                    <AppIcon name="x" :size="16" />
                </button>
                <span class="text-lg font-semibold">{{ modalTitle }}</span>
            </div>
        </template>

        <!-- ═══ STEPPER INDICATOR ═══ -->
        <div class="flex items-center justify-center gap-1 mb-5">
            <template v-for="(step, idx) in steps" :key="step.key">
                <button
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium transition-colors border-none cursor-pointer"
                    :class="stepClass(idx)"
                    :disabled="idx > activeStep && !canAdvancePast(idx)"
                    @click="goToStep(idx)"
                >
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] font-bold"
                        :class="stepNumberClass(idx)">
                        {{ idx + 1 }}
                    </span>
                    <span class="hidden sm:inline">{{ step.label }}</span>
                </button>
                <div v-if="idx < steps.length - 1" class="w-8 h-px" :class="idx < activeStep ? 'bg-green-400' : 'bg-gray-200'" />
            </template>
        </div>

        <div class="max-h-[50vh] overflow-y-auto">
            <!-- Selection summary (always visible) -->
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                <span class="font-semibold text-gray-700">{{ selectedCount }} applicant(s) selected</span>
                <span class="text-gray-400 ml-2">· Recommended for Approval</span>
            </div>

            <!-- ═══ STEP 1: Report Details ═══ -->
            <div v-show="activeStep === 0">
                <!-- Report Title -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title</label>
                    <InputText v-model="form.report_title" placeholder="Recommendation list title"
                        class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                </div>

                <!-- Request Date -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Request Date</label>
                    <DatePicker v-model="form.request_date" showButtonBar showIcon iconDisplay="input"
                        dateFormat="M dd, yy" placeholder="Select request date"
                        class="[&_.p-datepicker]:w-full [&_.p-datepicker]:text-xs" />
                </div>

                <!-- Program & Budget -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Program &amp; Budget</label>
                    <div class="space-y-2">
                        <ProgramSelect v-model="form.budget_program" custom-placeholder="Select program"
                            class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <Select v-if="budgetAllocationOptions.length" v-model="selectedBudgetAllocation"
                            :options="budgetAllocationOptions" optionLabel="label" optionValue="value"
                            placeholder="Select allocation" class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5">
                            <template #value="{ value, placeholder }">
                                <div v-if="value" class="leading-tight">
                                    <div class="font-medium text-gray-700">{{ formatBudgetAllocationLabel(value) }}</div>
                                    <div v-if="formatBudgetAllocationDescription(value)" class="text-[11px] text-gray-500">{{ formatBudgetAllocationDescription(value) }}</div>
                                </div>
                                <span v-else class="text-gray-400">{{ placeholder }}</span>
                            </template>
                        </Select>
                    </div>
                    <div v-if="showBudgetFooter" class="text-[10px] mt-1" :class="showBudgetError ? 'text-red-500' : 'text-gray-400'">
                        {{ budgetFooterMessage }}
                    </div>
                </div>
            </div>

            <!-- ═══ STEP 2: Cumulative Count ═══ -->
            <div v-show="activeStep === 1">
                <div v-if="selectedBudgetAllocation" class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Cumulative Count</label>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-700 font-semibold">
                            {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }} counted.
                        </div>
                        <InputText v-model="cumulativeScholarSearchQuery" placeholder="Search cumulative scholars"
                            class="w-full mt-2 [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        <div v-if="filteredCumulativeScholars.length" class="max-h-40 overflow-y-auto mt-2 space-y-1">
                            <div v-for="scholar in filteredCumulativeScholars" :key="scholar.profile_id" class="text-xs text-gray-600">
                                <span class="font-medium">{{ scholar.name }}</span>
                                <span class="text-gray-400">· {{ scholar.program || 'N/A' }}</span>
                            </div>
                        </div>
                        <div v-else-if="selectedBudgetAllocationApprovedCount" class="text-xs text-gray-400 mt-2">No scholars match search.</div>
                    </div>
                </div>
                <div v-else class="text-xs text-gray-400 italic">Select a budget allocation in Step 1 to view cumulative scholars.</div>
            </div>

            <!-- ═══ STEP 3: Signatories ═══ -->
            <div v-show="activeStep === 2">
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Signatories</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-[10px] font-medium text-gray-500 mb-1">Prepared By</label>
                            <InputText v-model="form.prepared_by" placeholder="Name"
                                class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                            <InputText v-model="form.prepared_by_position" placeholder="Position"
                                class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                            <InputText v-model="form.prepared_by_office" placeholder="Office"
                                class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-medium text-gray-500 mb-1">Approved By</label>
                            <InputText v-model="form.approved_by" placeholder="Name"
                                class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5 mb-1.5" />
                            <InputText v-model="form.approved_by_position" placeholder="Position"
                                class="w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ STEP 4: Options & Paper ═══ -->
            <div v-show="activeStep === 3">
                <!-- Options -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Options</label>
                    <div class="flex flex-col gap-0.5">
                        <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Highlight JPM Names</span>
                            <ToggleSwitch v-model="form.highlight_jpm_members" />
                        </label>
                        <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Include Endorsed By</span>
                            <ToggleSwitch v-model="form.include_endorsed_by" />
                        </label>
                        <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Show Remarks</span>
                            <ToggleSwitch v-model="form.show_remarks" />
                        </label>
                        <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer border-b border-gray-50 hover:text-gray-900">
                            <span>Include Projected Columns</span>
                            <ToggleSwitch v-model="includeProjectedColumns" />
                        </label>
                        <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 cursor-pointer hover:text-gray-900">
                            <span>Include Interview Columns</span>
                            <ToggleSwitch v-model="includeInterviewColumns" />
                        </label>
                    </div>
                </div>

                <!-- Paper & Orientation -->
                <div class="mb-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Paper &amp; Orientation</label>
                    <div class="flex gap-2">
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ NAVIGATION ═══ -->
        <div class="flex items-center justify-between pt-3 mt-2 border-t border-gray-100">
            <button v-if="activeStep > 0 && !loading"
                class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 bg-gray-100 px-4 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-gray-200"
                @click="prevStep">
                <AppIcon name="chevron-left" :size="14" />
                Back
            </button>
            <div v-else />

            <div class="flex items-center gap-2">
                <button v-if="activeStep < steps.length - 1 && !loading"
                    class="inline-flex items-center gap-1 text-sm font-bold text-white bg-blue-500 px-5 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-blue-600"
                    @click="nextStep">
                    Next
                    <AppIcon name="chevron-right" :size="14" />
                </button>
                <button v-if="activeStep === steps.length - 1"
                    class="inline-flex items-center gap-2 text-sm font-bold text-white bg-green-500 px-5 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isSubmitDisabled || loading" @click="submitForm">
                    <AppIcon v-if="loading" name="loader-circle" :size="14" class="animate-spin" />
                    <AppIcon v-else-if="isPrintIntent" name="printer" :size="14" />
                    {{ loading ? 'Saving...' : (isPrintIntent ? 'Save & Print' : (isEditMode ? 'Save Changes' : 'Create List')) }}
                </button>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

import AppIcon from '@/Components/ui/AppIcon.vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';

const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';

const props = defineProps({
    show: Boolean,
    selectedCount: {
        type: Number,
        default: 0,
    },
    budgetAllocations: {
        type: Array,
        default: () => [],
    },
    defaultPreparedBy: {
        type: String,
        default: '',
    },
    submitIntent: {
        type: String,
        default: 'save',
    },
    mode: {
        type: String,
        default: 'create',
    },
    initialData: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:show', 'submit']);

const form = ref({
    report_title: 'RECOMMENDATION LIST FOR APPROVAL',
    request_date: null,
    budget_program: null,
    highlight_jpm_members: false,
    include_endorsed_by: false,
    show_remarks: false,
    prepared_by: '',
    prepared_by_position: DEFAULT_PREPARED_BY_POSITION,
    prepared_by_office: DEFAULT_PREPARED_BY_OFFICE,
    approved_by: DEFAULT_APPROVED_BY,
    approved_by_position: DEFAULT_APPROVED_BY_POSITION,
});
const selectedBudgetAllocation = ref(null);
const cumulativeScholarSearchQuery = ref('');
const showBudgetError = ref(false);
const includeProjectedColumns = ref(true);
const includeInterviewColumns = ref(true);
const paperSize = ref('A4');
const orientation = ref('landscape');
const activeStep = ref(0);

const steps = [
    { key: 'details', label: 'Report Details' },
    { key: 'cumulative', label: 'Cumulative Count' },
    { key: 'signatories', label: 'Signatories' },
    { key: 'options', label: 'Options & Paper' },
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

const isEditMode = computed(() => props.mode === 'edit');
const isPrintIntent = computed(() => props.submitIntent === 'print');
const modalTitle = computed(() => {
    if (isEditMode.value && isPrintIntent.value) {
        return 'Print Recommendation List';
    }

    return isEditMode.value ? 'Edit Recommendation List' : 'Create Recommendation List';
});
const submitTooltip = computed(() => {
    if (isEditMode.value && isPrintIntent.value) {
        return 'Save and Print';
    }

    return isEditMode.value ? 'Save Changes' : 'Create List';
});
const budgetErrorMessage = computed(() => isEditMode.value
    ? 'Select a budget allocation before updating the recommendation list.'
    : 'Select a budget allocation before creating the recommendation list.');

const budgetAllocationCurrencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});
const shortDateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
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

    return {
        key: allocation.key ?? null,
        particular_id: allocation.particular_id ?? null,
        particular_name: allocation.particular_name ?? null,
        description: allocation.description ?? null,
        program_id: allocation.program_id ?? null,
        program: allocation.program ?? null,
        programs: Array.isArray(allocation.programs) ? allocation.programs : [],
        program_ids: Array.isArray(allocation.program_ids) ? allocation.program_ids : [],
        calendar_year: allocation.calendar_year ?? null,
        rc_code: allocation.rc_code ?? null,
        rc_name: allocation.rc_name ?? null,
        fiscal_year: allocation.fiscal_year ?? null,
        total_allotment: allocation.total_allotment ?? null,
        disbursed: allocation.disbursed ?? null,
        remaining: allocation.remaining ?? null,
        approved_scholars_current_ay_estimated_total: allocation.approved_scholars_current_ay_estimated_total ?? null,
        date_start: allocation.date_start ?? null,
        date_end: allocation.date_end ?? null,
        approved_scholars_to_date: allocation.approved_scholars_to_date ?? null,
        approved_scholars: Array.isArray(allocation.approved_scholars)
            ? allocation.approved_scholars.map((scholar) => ({
                profile_id: normalizeNullableInteger(scholar?.profile_id),
                program_id: normalizeNullableInteger(scholar?.program_id),
                name: normalizeNullableString(scholar?.name),
                program: normalizeNullableString(scholar?.program),
                program_name: normalizeNullableString(scholar?.program_name),
                program_shortname: normalizeNullableString(scholar?.program_shortname),
                date_approved: normalizeNullableString(scholar?.date_approved),
                status: normalizeNullableString(scholar?.status),
            }))
            : [],
    };
}

function normalizeNullableInteger(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const normalizedValue = Number.parseInt(String(value), 10);

    return Number.isNaN(normalizedValue) ? null : normalizedValue;
}

function normalizeNullableString(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const normalizedValue = String(value).trim();

    return normalizedValue || null;
}

function sameBudgetAllocation(left, right) {
    if (!left && !right) {
        return true;
    }

    if (!left || !right) {
        return false;
    }

    const leftKey = String(left.key ?? '');
    const rightKey = String(right.key ?? '');

    if (leftKey && rightKey) {
        return leftKey === rightKey;
    }

    return String(left.key ?? '') === String(right.key ?? '')
        && String(left.particular_id ?? '') === String(right.particular_id ?? '')
        && String(left.particular_name ?? '') === String(right.particular_name ?? '')
        && String(left.program_id ?? '') === String(right.program_id ?? '')
        && String(left.program ?? '') === String(right.program ?? '')
        && String(left.rc_code ?? '') === String(right.rc_code ?? '')
        && String(left.rc_name ?? '') === String(right.rc_name ?? '')
        && String(left.fiscal_year ?? '') === String(right.fiscal_year ?? '');
}

const budgetAllocationOptions = computed(() => {
    const options = (props.budgetAllocations || []).map((allocation) => ({
        label: formatBudgetAllocationLabel(allocation),
        description: formatBudgetAllocationDescription(allocation),
        value: allocation,
    }));

    const currentBudgetAllocation = props.initialData?.budget_allocation;

    if (currentBudgetAllocation && !options.some((option) => sameBudgetAllocation(option.value, currentBudgetAllocation))) {
        options.unshift({
            label: formatBudgetAllocationLabel(currentBudgetAllocation),
            description: formatBudgetAllocationDescription(currentBudgetAllocation),
            value: currentBudgetAllocation,
        });
    }

    return options;
});

const requiresBudgetAllocationSelection = computed(() => budgetAllocationOptions.value.length > 0);
const isSubmitDisabled = computed(() => props.selectedCount === 0);
const selectedBudgetProgramId = computed(() => {
    const value = form.value.budget_program;

    return typeof value === 'object' && value !== null
        ? value.id ?? null
        : null;
});
const selectedBudgetProgramLabel = computed(() => {
    const value = form.value.budget_program;

    if (!value) {
        return '';
    }

    if (typeof value === 'object') {
        return String(value.shortname || value.name || '').trim();
    }

    return String(value).trim();
});
const hasCumulativeScholarDetails = computed(() => Array.isArray(selectedBudgetAllocation.value?.approved_scholars));
const cumulativeScholars = computed(() => {
    const scholars = hasCumulativeScholarDetails.value
        ? [...selectedBudgetAllocation.value.approved_scholars]
        : [];

    return scholars.sort((left, right) => (left?.name || '').localeCompare(right?.name || '', undefined, {
        sensitivity: 'base',
    }));
});

function scholarMatchesSelectedBudgetProgram(scholar) {
    const selectedProgramId = selectedBudgetProgramId.value;
    const selectedProgramLabel = selectedBudgetProgramLabel.value.trim().toLowerCase();

    if (!selectedProgramId && !selectedProgramLabel) {
        return true;
    }

    if (selectedProgramId && String(scholar?.program_id ?? '') === String(selectedProgramId)) {
        return true;
    }

    if (!selectedProgramLabel) {
        return false;
    }

    return [
        scholar?.program,
        scholar?.program_name,
        scholar?.program_shortname,
    ]
        .filter(Boolean)
        .some((programValue) => String(programValue).trim().toLowerCase() === selectedProgramLabel);
}

const scopedCumulativeScholars = computed(() => cumulativeScholars.value.filter(scholarMatchesSelectedBudgetProgram));
const selectedBudgetAllocationApprovedCount = computed(() => {
    if (hasCumulativeScholarDetails.value) {
        return scopedCumulativeScholars.value.length;
    }

    return Number(selectedBudgetAllocation.value?.approved_scholars_to_date ?? 0) || 0;
});
const filteredCumulativeScholars = computed(() => {
    const searchTerm = cumulativeScholarSearchQuery.value.trim().toLowerCase();
    const scholars = scopedCumulativeScholars.value;

    if (!searchTerm) {
        return scholars;
    }

    return scholars.filter(scholar => {
        const haystack = [
            scholar?.name,
            scholar?.program,
            scholar?.program_name,
            scholar?.program_shortname,
            scholar?.date_approved,
            formatScholarStatus(scholar?.status),
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(searchTerm);
    });
});
const showCumulativeScholarMissingDetails = computed(() => {
    return selectedBudgetAllocationApprovedCount.value > 0 && !hasCumulativeScholarDetails.value;
});
const cumulativeScopeDescription = computed(() => {
    const calendarYear = getBudgetAllocationCalendarYear(selectedBudgetAllocation.value);
    const programLabel = selectedBudgetProgramLabel.value;

    if (calendarYear && programLabel) {
        return `This list follows calendar year ${calendarYear} for ${programLabel}.`;
    }

    if (calendarYear) {
        return `This list follows calendar year ${calendarYear} for the selected allocation program coverage.`;
    }

    if (programLabel) {
        return `This list follows the selected program (${programLabel}).`;
    }

    return 'This list follows the selected allocation program coverage.';
});
const emptyCumulativeScholarMessage = computed(() => {
    const calendarYear = getBudgetAllocationCalendarYear(selectedBudgetAllocation.value);
    const programLabel = selectedBudgetProgramLabel.value;

    if (calendarYear && programLabel) {
        return `No approved scholars are currently counted for calendar year ${calendarYear} under ${programLabel}.`;
    }

    if (calendarYear) {
        return 'No approved scholars are currently counted for this calendar year and allocation program coverage.';
    }

    if (programLabel) {
        return `No approved scholars are currently counted for ${programLabel}.`;
    }

    return 'No approved scholars are currently counted for this allocation.';
});
const budgetFooterMessage = computed(() => {
    if (showBudgetError.value) {
        return budgetErrorMessage.value;
    }

    if (selectedBudgetAllocation.value) {
        return formatBudgetAllocationSelectionMessage(selectedBudgetAllocation.value);
    }

    if (budgetAllocationOptions.value.length > 0) {
        return 'Select the budget allocation where the Current AY Estimated Grant will be monitored.';
    }

    return null;
});
const showBudgetFooter = computed(() => Boolean(budgetFooterMessage.value));

// ═══ STEPPER HELPERS ═══
function stepClass(idx) {
    if (idx === activeStep.value) return 'bg-blue-50 text-blue-700';
    if (idx < activeStep.value) return 'bg-green-50 text-green-700';
    return 'text-gray-400';
}
function stepNumberClass(idx) {
    if (idx === activeStep.value) return 'bg-blue-500 text-white';
    if (idx < activeStep.value) return 'bg-green-500 text-white';
    return 'bg-gray-200 text-gray-500';
}
function canAdvancePast(idx) { return idx <= activeStep.value; }
function nextStep() { if (activeStep.value < steps.length - 1) activeStep.value++; }
function prevStep() { if (activeStep.value > 0) activeStep.value--; }
function goToStep(idx) { if (idx <= activeStep.value) activeStep.value = idx; }

function normalizeBudgetProgram(value) {
    if (!value) {
        return null;
    }

    if (typeof value === 'object') {
        return value.name || value.shortname || null;
    }

    const normalized = String(value).trim();

    return normalized || null;
}

function resetForm() {
    const initialData = props.initialData;

    form.value = {
        report_title: initialData?.report_title ?? 'RECOMMENDATION LIST FOR APPROVAL',
        request_date: parseRequestDate(initialData?.request_date ?? initialData?.created_at) ?? new Date(),
        budget_program: initialData?.budget_program ?? initialData?.budget_allocation?.program ?? null,
        highlight_jpm_members: Boolean(initialData?.highlight_jpm_members),
        include_endorsed_by: Boolean(initialData?.include_endorsed_by),
        show_remarks: Boolean(initialData?.show_remarks),
        prepared_by: initialData?.prepared_by ?? (props.defaultPreparedBy?.trim() || DEFAULT_PREPARED_BY),
        prepared_by_position: initialData?.prepared_by_position ?? DEFAULT_PREPARED_BY_POSITION,
        prepared_by_office: initialData?.prepared_by_office ?? DEFAULT_PREPARED_BY_OFFICE,
        approved_by: initialData?.approved_by ?? DEFAULT_APPROVED_BY,
        approved_by_position: initialData?.approved_by_position ?? DEFAULT_APPROVED_BY_POSITION,
    };

    if (initialData?.budget_allocation) {
        selectedBudgetAllocation.value = budgetAllocationOptions.value
            .find((option) => sameBudgetAllocation(option.value, initialData.budget_allocation))?.value
            ?? initialData.budget_allocation;
    } else {
        selectedBudgetAllocation.value = budgetAllocationOptions.value.length === 1 && !isEditMode.value
            ? budgetAllocationOptions.value[0].value
            : null;
    }

    cumulativeScholarSearchQuery.value = '';
    showBudgetError.value = false;
    activeStep.value = 0;

    // Restore paper & orientation from initialData (or defaults)
    paperSize.value = initialData?.paper_size || 'A4';
    orientation.value = initialData?.orientation || 'landscape';
}

function close() {
    if (props.loading) {
        return;
    }

    emit('update:show', false);
}

function submitForm() {
    if (requiresBudgetAllocationSelection.value && !selectedBudgetAllocation.value) {
        showBudgetError.value = true;
        return;
    }

    showBudgetError.value = false;

    emit('submit', {
        report_title: form.value.report_title,
        request_date: formatRequestDateForPayload(form.value.request_date),
        paper_size: paperSize.value,
        orientation: orientation.value,
        budget_program: normalizeBudgetProgram(form.value.budget_program),
        highlight_jpm_members: form.value.highlight_jpm_members,
        include_endorsed_by: form.value.include_endorsed_by,
        show_remarks: form.value.show_remarks,
        include_projected_columns: includeProjectedColumns.value,
        include_interview_columns: includeInterviewColumns.value,
        prepared_by: form.value.prepared_by,
        prepared_by_position: form.value.prepared_by_position,
        prepared_by_office: form.value.prepared_by_office,
        approved_by: form.value.approved_by,
        approved_by_position: form.value.approved_by_position,
        budget_allocation: buildBudgetAllocationPayload(selectedBudgetAllocation.value),
    });
}

watch(() => props.show, (value) => {
    if (value) {
        resetForm();
    }
});

watch(selectedBudgetAllocation, () => {
    cumulativeScholarSearchQuery.value = '';

    if (selectedBudgetAllocation.value) {
        showBudgetError.value = false;
    }
});

function formatDate(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return shortDateFormatter.format(date);
}

function parseRequestDate(value) {
    if (!value) {
        return null;
    }

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    const normalizedValue = String(value).trim().slice(0, 10);
    const date = new Date(`${normalizedValue}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
}

function formatRequestDateForPayload(value) {
    const date = parseRequestDate(value);

    if (!date) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatScholarStatus(status) {
    const labels = {
        active: 'Active',
        completed: 'Completed',
        'completed-transferred': 'Completed - Transferred',
    };

    return labels[status] || String(status || '—')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, character => character.toUpperCase());
}
</script>
