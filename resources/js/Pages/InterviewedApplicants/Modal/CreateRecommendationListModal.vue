<template>
    <IosModal :visible="show" :title="modalTitle" width="calc(100vw - 2rem)"
        max-width="960px" body-style="padding: 16px;" modal-class="rl-approval-modal"
        @update:visible="val => emit('update:show', val)">
        <template #header-left>
            <button v-if="steps.length > 1 && activeStep > 0" class="ios-nav-btn ios-nav-cancel text-nav" :disabled="loading"
                @click="prevStep" v-tooltip.bottom="'Back'">
                <AppIcon name="chevron-left" :size="16" />
            </button>
            <button v-else class="ios-nav-btn ios-nav-cancel text-nav" @click="emit('update:show', false)">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #header-right>
            <button v-if="activeStep < steps.length - 1" class="ios-nav-btn ios-nav-action text-nav" :disabled="loading || !canAdvancePast(activeStep + 1)"
                @click="nextStep" v-tooltip.bottom="'Next'">
                <AppIcon name="chevron-right" :size="16" style="color: #007aff;" />
            </button>
            <button v-else class="ios-nav-btn ios-nav-action text-nav" :disabled="isSubmitDisabled || loading"
                @click="submitForm"
                v-tooltip.bottom="isUpdateListIntent ? 'Update Request' : (isEditMode ? 'Save Changes' : 'Create Request')">
                <AppIcon v-if="loading" name="loader-circle" :size="16" class="animate-spin" />
                <AppIcon v-else name="check" :size="16" style="color: #16a34a;" />
            </button>
        </template>

        <!-- ═══ STEPPER INDICATOR ═══ -->
        <div v-if="steps.length > 1" class="flex items-center justify-center gap-1 mb-5">
            <template v-for="(step, idx) in steps" :key="step.key">
                <button
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium transition-colors border-none cursor-pointer"
                    :class="stepClass(idx)"
                    :disabled="idx > activeStep && !canAdvancePast(idx)"
                    @click="goToStep(idx)"
                >
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-2xs font-bold"
                        :class="stepNumberClass(idx)">
                        {{ idx + 1 }}
                    </span>
                    <span class="hidden sm:inline">{{ step.label }}</span>
                </button>
                <div v-if="idx < steps.length - 1" class="w-8 h-px" :class="idx < activeStep ? 'bg-green-400' : 'bg-gray-200'" />
            </template>
        </div>

        <div class="max-h-[70vh] overflow-y-auto">
            <!-- Selection summary (only relevant while the applicants step is active) -->
            <div v-if="showApplicantsStep && activeStep === applicantsStepIndex" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                <span class="font-semibold text-gray-700">{{ selectedCount }} applicant(s) selected</span>
                <span class="text-gray-400 ml-2">· Recommended for Approval</span>
            </div>

            <!-- ═══ STEP: Select Applicants ═══ -->
            <div v-if="showApplicantsStep" v-show="activeStep === applicantsStepIndex">
                <div class="flex items-end gap-3 mb-1">
                    <InputGroup class="w-80 shrink-0" >
                        <InputGroupAddon>
                            <AppIcon name="search" :size="14" class="text-gray-400" />
                        </InputGroupAddon>
                        <InputText v-model="applicantSearch" placeholder="Search by name..." size="small" />
                    </InputGroup>
                    <ProgramSelect v-model="applicantProgramFilter" custom-placeholder="Program" ios-compact
                        class="!w-48 shrink-0" />
                    <div class="flex items-end gap-2 ml-auto shrink-0">
                        <Select :modelValue="mainGrantProvision" @update:modelValue="onMainGrantProvisionChange"
                            :options="grantProvisionOptions" optionLabel="label" optionValue="value"
                            placeholder="Main grant amount" size="small" class="w-56" />
                        <button type="button" class="ios-footer-btn text-2xs px-3 shrink-0" :disabled="!mainGrantAmount || selectedCount === 0"
                            @click="applyMainGrantToAllSelected">
                            Apply to All
                        </button>
                    </div>
                </div>
                <div class="text-3xs text-gray-400 mb-3">
                    Main Grant Amount is the default per-scholar amount used for "Total amount for this request". Each scholar's amount below can still be edited individually.
                </div>

                <div v-if="eligibleApplicants.length === 0" class="py-6 text-center text-xs text-gray-400">
                    No recommended applicants are available to select.
                </div>
                <div v-else-if="filteredApplicants.length === 0" class="py-6 text-center text-xs text-gray-400">
                    No applicants match the current filters.
                </div>
                <div v-else class="rounded-lg border border-gray-200 overflow-hidden">
                    <div style="overflow-y: hidden; scrollbar-gutter: stable;">
                        <table class="w-full text-xs">
                            <colgroup>
                                <col style="width:40px" />
                                <col style="width:40%" />
                                <col style="width:12%" />
                                <col />
                                <col style="width:155px" />
                            </colgroup>
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-center px-3 py-2 font-medium text-gray-500">
                                        <Checkbox :modelValue="allFilteredApplicantsSelected" binary
                                            :indeterminate="someFilteredApplicantsSelected"
                                            @update:modelValue="toggleAllFilteredApplicants" />
                                    </th>
                                    <th class="text-left px-3 py-2 font-medium text-gray-500">Name</th>
                                    <th class="text-left px-3 py-2 font-medium text-gray-500">Program</th>
                                    <th class="text-left px-3 py-2 font-medium text-gray-500">School</th>
                                    <th class="text-right px-3 py-2 font-medium text-gray-500">Amount</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="max-h-[25rem] overflow-y-auto" style="scrollbar-gutter: stable;">
                        <table class="w-full text-xs ">
                            <colgroup>
                                <col style="width:40px" />
                                <col style="width:40%" />
                                <col style="width:12%" />
                                <col />
                                <col style="width:155px" />
                            </colgroup>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="record in filteredApplicants" :key="record.id">
                                    <td class="px-3 py-2 text-center"
                                        v-tooltip.top="!isApplicantSelectable(record) && !isApplicantSelected(record) ? 'An approval request can only include applicants from the same program' : null">
                                        <Checkbox :modelValue="isApplicantSelected(record)" binary
                                            :disabled="!isApplicantSelectable(record) && !isApplicantSelected(record)"
                                            @update:modelValue="(checked) => toggleApplicant(record, checked)" />
                                    </td>
                                    <td class="px-3 py-2 font-medium text-gray-800 truncate">{{ formatApplicantName(record) }}</td>
                                    <td class="px-3 py-2 text-gray-600 truncate">{{ record.program?.shortname || 'N/A' }}</td>
                                    <td class="px-3 py-2 text-gray-600 truncate">{{ record.school?.shortname || record.school?.name || 'N/A' }}</td>
                                    <td class="px-3 py-2">
                                        <InputGroup v-if="isApplicantSelected(record)" class="w-full">
                                            <InputGroupAddon class="text-2xs text-gray-500">₱</InputGroupAddon>
                                            <InputNumber v-model="scholarAmounts[record.id]"
                                                mode="decimal" locale="en-PH" :minFractionDigits="2" size="small"
                                                class="[&_.p-inputnumber-input]:text-right" />
                                        </InputGroup>
                                        <span v-else class="block text-right text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══ STEP: Report Details ═══ -->
            <div v-show="activeStep === detailsStepIndex">
                <!-- Report Title -->
                <div class="mb-4 w-1/2 mx-auto">
                    <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title</label>
                    <Editor v-model="form.report_title" editorStyle="font-size: 0.75rem;" class="ios-card h-24">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>

                <!-- Request Date & Program / Budget -->
                <div class="flex gap-4 mb-4 w-1/2 mx-auto">
                    <div class="w-32 shrink-0">
                        <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Request Date</label>
                        <DatePicker v-model="form.request_date" showButtonBar showIcon iconDisplay="input"
                            dateFormat="M dd, yy" placeholder="Select request date" size="small" class="w-full" />
                    </div>

                    <div class="flex-1">
                    <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Program &amp; Budget</label>
                    <div class="space-y-2 w-80">
                        <ProgramSelect v-model="form.budget_program" custom-placeholder="Select program" ios-compact class="w-full" />
                        <Select v-if="budgetAllocationOptions.length" v-model="selectedBudgetAllocation"
                            :options="budgetAllocationOptions" optionLabel="label" optionValue="value"
                            placeholder="Select allocation" size="small" class="w-full">
                            <template #value="{ value, placeholder }">
                                <div v-if="value" class="leading-tight">
                                    <div class="font-medium text-gray-700">{{ formatBudgetAllocationLabel(value) }}</div>
                                    <div v-if="formatBudgetAllocationDescription(value)" class="text-2xs text-gray-500">{{ formatBudgetAllocationDescription(value) }}</div>
                                </div>
                                <span v-else class="text-gray-400">{{ placeholder }}</span>
                            </template>
                            <template #option="{ option }">
                                <div class="leading-tight">
                                    <div class="font-medium text-gray-700">{{ option.label }}</div>
                                    <div v-if="option.description" class="text-2xs text-gray-500">{{ option.description }}</div>
                                </div>
                            </template>
                        </Select>
                    </div>
                    <div v-if="showBudgetFooter" class="text-3xs mt-1" :class="showBudgetError ? 'text-red-500' : 'text-gray-400'">
                        {{ budgetFooterMessage }}
                    </div>
                    </div>
                </div>
            </div>

            <!-- ═══ STEP: Signatories ═══ -->
            <div v-show="activeStep === signatoriesStepIndex">
                <div class="mb-4 w-1/2 mx-auto">
                    <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Signatories</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-3xs font-medium text-gray-500 mb-1">Prepared By</label>
                            <InputText v-model="form.prepared_by" placeholder="Name" size="small" class="w-full mb-1.5" />
                            <InputText v-model="form.prepared_by_position" placeholder="Position" size="small" class="w-full mb-1.5" />
                            <InputText v-model="form.prepared_by_office" placeholder="Office" size="small" class="w-full" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-3xs font-medium text-gray-500 mb-1">Approved By</label>
                            <InputText v-model="form.approved_by" placeholder="Name" size="small" class="w-full mb-1.5" />
                            <InputText v-model="form.approved_by_position" placeholder="Position" size="small" class="w-full" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </IosModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Editor from 'primevue/editor';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import { useSystemOptions } from '@/composables/useSystemOptions';

const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';
const DEFAULT_REPORT_TITLE = '<p><strong>Request for Scholarship Approval</strong></p>';

const props = defineProps({
    show: Boolean,
    applicants: {
        type: Array,
        default: () => [],
    },
    budgetAllocations: {
        type: Array,
        default: () => [],
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
    report_title: DEFAULT_REPORT_TITLE,
    request_date: null,
    budget_program: null,
    prepared_by: '',
    prepared_by_position: DEFAULT_PREPARED_BY_POSITION,
    prepared_by_office: DEFAULT_PREPARED_BY_OFFICE,
    approved_by: DEFAULT_APPROVED_BY,
    approved_by_position: DEFAULT_APPROVED_BY_POSITION,
});
const selectedBudgetAllocation = ref(null);
const showBudgetError = ref(false);
const activeStep = ref(0);

// ═══ Select Applicants step ═══
const applicantProgramFilter = ref(null);
const applicantSearch = ref('');
const selectedApplicantIds = ref([]);

// ═══ Grant amounts ═══
// mainGrantAmount is the default per-scholar amount for this request;
// scholarAmounts holds each selected scholar's actual amount (seeded from
// mainGrantAmount, or the system-projected grant_amount as a last resort)
// and can be edited per row — this is what "Total amount for this request"
// sums, sidestepping ScholarshipExpenseProjectionService term-system
// mismatches (e.g. a "3rd Semester" record being miscounted as trimester).
// The main amount is picked from the same Grant Provision options used
// elsewhere in the app (System Options → grant_provision) rather than
// free-typed, so it stays anchored to a configured, known amount.
const grantProvisionRaw = useSystemOptions('grant_provision');
const mainGrantProvision = ref(null);
const mainGrantAmount = ref(null);
const scholarAmounts = ref({});

const grantProvisionOptions = computed(() => {
    const programCode = selectedBudgetProgramLabel.value;
    return grantProvisionRaw.value.filter((option) => !option.program || option.program === programCode);
});

function onMainGrantProvisionChange(value) {
    mainGrantProvision.value = value;
    const option = grantProvisionOptions.value.find((opt) => opt.value === value);
    const amount = Number(option?.amount);
    mainGrantAmount.value = Number.isFinite(amount) ? amount : null;
}

function applyMainGrantToAllSelected() {
    if (mainGrantAmount.value === null || mainGrantAmount.value === undefined) {
        return;
    }
    const next = { ...scholarAmounts.value };
    selectedApplicantIds.value.forEach((id) => {
        next[id] = mainGrantAmount.value;
    });
    scholarAmounts.value = next;
}

const isEditMode = computed(() => props.mode === 'edit');
const isUpdateListIntent = computed(() => props.submitIntent === 'update-list');

// The applicants step only makes sense when membership is actually being set:
// creating a new request, or adding/removing applicants from an existing one.
// Editing report details/signatories on an already-saved request leaves
// membership untouched.
const showApplicantsStep = computed(() => props.mode === 'create' || isUpdateListIntent.value);

// Updating list membership only touches record_ids/grant amounts (see
// submitForm's is_update_list branch) — Report Details/Signatories aren't
// even part of that payload, so they're dropped from the stepper entirely
// rather than shown and silently discarded.
const steps = computed(() => {
    if (isUpdateListIntent.value) {
        return [{ key: 'applicants', label: 'Select Applicants' }];
    }
    const list = [];
    if (showApplicantsStep.value) list.push({ key: 'applicants', label: 'Select Applicants' });
    list.push({ key: 'details', label: 'Report Details' });
    list.push({ key: 'signatories', label: 'Signatories' });
    return list;
});
const applicantsStepIndex = computed(() => (showApplicantsStep.value ? 0 : -1));
const detailsStepIndex = computed(() => {
    if (isUpdateListIntent.value) return -1;
    return showApplicantsStep.value ? 1 : 0;
});
const signatoriesStepIndex = computed(() => {
    if (isUpdateListIntent.value) return -1;
    return showApplicantsStep.value ? 2 : 1;
});

function formatApplicantName(record) {
    const lastName = record?.profile?.last_name || '';
    const firstName = record?.profile?.first_name || '';
    const middleInitial = record?.profile?.middle_name
        ? `${record.profile.middle_name.trim().charAt(0).toUpperCase()}.`
        : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
}

// Parent already scopes props.applicants to the correct eligible pool
// (Recommended for Approval, and not already in another approval request).
const eligibleApplicants = computed(() => props.applicants || []);

const filteredApplicants = computed(() => {
    const programId = applicantProgramFilter.value?.id ?? null;
    const query = applicantSearch.value.trim().toLowerCase();

    return eligibleApplicants.value.filter((record) => {
        if (programId && String(record?.program?.id ?? '') !== String(programId)) {
            return false;
        }
        if (query && !formatApplicantName(record).toLowerCase().includes(query)) {
            return false;
        }
        return true;
    });
});

// An approval request can only include applicants from a single program —
// once a selection has started, other-program rows are disabled.
const selectionAnchorProgramId = computed(() => {
    if (selectedApplicantIds.value.length === 0) return null;
    const anchor = eligibleApplicants.value.find((record) => selectedApplicantIds.value.includes(record.id));
    return anchor?.program?.id ?? null;
});

function isApplicantSelected(record) {
    return selectedApplicantIds.value.includes(record.id);
}

function isApplicantSelectable(record) {
    if (selectionAnchorProgramId.value === null) return true;
    return String(record?.program?.id ?? '') === String(selectionAnchorProgramId.value);
}

// Seeds a newly-selected scholar's amount: the list's main grant amount if
// set, otherwise the system-projected per-term grant_amount as a starting
// point. Never overwrites an amount already entered for that scholar.
function seedScholarAmount(record) {
    if (scholarAmounts.value[record.id] !== undefined) {
        return;
    }
    const fallback = mainGrantAmount.value ?? (Number.isFinite(Number(record?.grant_amount)) ? Number(record.grant_amount) : null);
    if (fallback !== null) {
        scholarAmounts.value = { ...scholarAmounts.value, [record.id]: fallback };
    }
}

function toggleApplicant(record, checked) {
    if (checked) {
        if (!isApplicantSelectable(record)) return;
        if (!selectedApplicantIds.value.includes(record.id)) {
            selectedApplicantIds.value = [...selectedApplicantIds.value, record.id];
        }
        seedScholarAmount(record);
        return;
    }

    selectedApplicantIds.value = selectedApplicantIds.value.filter((id) => id !== record.id);
}

const allFilteredApplicantsSelected = computed(() => {
    return filteredApplicants.value.length > 0
        && filteredApplicants.value.every((record) => isApplicantSelected(record));
});

const someFilteredApplicantsSelected = computed(() => {
    return !allFilteredApplicantsSelected.value
        && filteredApplicants.value.some((record) => isApplicantSelected(record));
});

function toggleAllFilteredApplicants(checked) {
    if (!checked) {
        const filteredIds = new Set(filteredApplicants.value.map((record) => record.id));
        selectedApplicantIds.value = selectedApplicantIds.value.filter((id) => !filteredIds.has(id));
        return;
    }

    const selectable = filteredApplicants.value.filter((record) => isApplicantSelectable(record));
    const selectedById = new Set(selectedApplicantIds.value);
    selectable.forEach((record) => {
        selectedById.add(record.id);
        seedScholarAmount(record);
    });
    selectedApplicantIds.value = Array.from(selectedById);
}

const selectedCount = computed(() => selectedApplicantIds.value.length);

const modalTitle = computed(() => {
    if (isUpdateListIntent.value) {
        return 'Update Approval Request List';
    }

    return isEditMode.value ? 'Edit Approval Request' : 'Create Approval Request';
});
const budgetErrorMessage = computed(() => isEditMode.value
    ? 'Select a budget allocation before updating the approval request.'
    : 'Select a budget allocation before creating the approval request.');

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
const isSubmitDisabled = computed(() => showApplicantsStep.value && selectedCount.value === 0);
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
function canAdvancePast(idx) {
    if (showApplicantsStep.value && idx > applicantsStepIndex.value && selectedCount.value === 0) {
        return false;
    }
    return idx <= activeStep.value + 1;
}
function goToStep(idx) { if (idx <= activeStep.value || canAdvancePast(idx)) activeStep.value = idx; }
function nextStep() { if (activeStep.value < steps.value.length - 1 && canAdvancePast(activeStep.value + 1)) activeStep.value++; }
function prevStep() { if (activeStep.value > 0) activeStep.value--; }

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
        report_title: initialData?.report_title ?? DEFAULT_REPORT_TITLE,
        request_date: parseRequestDate(initialData?.request_date ?? initialData?.created_at) ?? new Date(),
        budget_program: initialData?.budget_program ?? initialData?.budget_allocation?.program ?? null,
        prepared_by: initialData?.prepared_by ?? DEFAULT_PREPARED_BY,
        prepared_by_position: initialData?.prepared_by_position ?? DEFAULT_PREPARED_BY_POSITION,
        prepared_by_office: initialData?.prepared_by_office ?? DEFAULT_PREPARED_BY_OFFICE,
        approved_by: initialData?.approved_by ?? DEFAULT_APPROVED_BY,
        approved_by_position: initialData?.approved_by_position ?? DEFAULT_APPROVED_BY_POSITION,
    };

    if (initialData?.budget_allocation) {
        // Always start from the stored snapshot, never a freshly matched
        // live option — the request's scholar-count figures are frozen at
        // creation/last save and must not silently pick up recomputed
        // numbers just by reopening this modal. The user can still
        // explicitly pick a different allocation from the dropdown.
        selectedBudgetAllocation.value = initialData.budget_allocation;
    } else {
        selectedBudgetAllocation.value = budgetAllocationOptions.value.length === 1 && !isEditMode.value
            ? budgetAllocationOptions.value[0].value
            : null;
    }

    // Seed the applicant picker: empty for a fresh create, pre-checked with
    // the request's current members when updating one.
    selectedApplicantIds.value = Array.isArray(initialData?.records)
        ? initialData.records.map((record) => record.id)
        : [];
    applicantProgramFilter.value = null;
    applicantSearch.value = '';

    // Grant amounts: start from the stored main amount + per-scholar
    // overrides. A record without a stored override falls back to its own
    // system-projected grant_amount, so older requests (created before this
    // feature) still show sensible starting values instead of blanks.
    mainGrantAmount.value = initialData?.main_grant_amount ?? null;
    // Reverse-match the stored amount back to a Grant Provision option so
    // the select shows what was picked. If nothing matches (e.g. a value
    // entered before this feature existed), the amount itself is still
    // preserved above — only the dropdown's selection is left blank.
    mainGrantProvision.value = mainGrantAmount.value !== null
        ? (grantProvisionOptions.value.find((option) => Number(option.amount) === mainGrantAmount.value)?.value ?? null)
        : null;
    const storedOverrides = initialData?.grant_amount_overrides || {};
    const seededAmounts = {};
    (initialData?.records || []).forEach((record) => {
        const stored = storedOverrides[record.id] ?? storedOverrides[String(record.id)];
        const fallback = Number.isFinite(Number(record?.grant_amount)) ? Number(record.grant_amount) : null;
        const amount = stored ?? mainGrantAmount.value ?? fallback;
        if (amount !== null && amount !== undefined) {
            seededAmounts[record.id] = amount;
        }
    });
    scholarAmounts.value = seededAmounts;

    showBudgetError.value = false;
    activeStep.value = 0;
}

function close() {
    if (props.loading) {
        return;
    }

    emit('update:show', false);
}

// Only the currently-selected scholars' amounts are sent — entries for
// deselected/removed scholars are dropped rather than lingering as stale
// overrides.
function buildGrantAmountsPayload() {
    const payload = {};
    selectedApplicantIds.value.forEach((id) => {
        const amount = scholarAmounts.value[id];
        if (amount !== null && amount !== undefined) {
            payload[id] = amount;
        }
    });
    return payload;
}

function submitForm() {
    if (isSubmitDisabled.value) {
        return;
    }

    if (isUpdateListIntent.value) {
        emit('submit', {
            is_update_list: true,
            record_ids: selectedApplicantIds.value,
            main_grant_amount: mainGrantAmount.value,
            grant_amounts: buildGrantAmountsPayload(),
        });
        return;
    }

    if (requiresBudgetAllocationSelection.value && !selectedBudgetAllocation.value) {
        showBudgetError.value = true;
        return;
    }

    showBudgetError.value = false;

    emit('submit', {
        ...(showApplicantsStep.value ? { record_ids: selectedApplicantIds.value } : {}),
        main_grant_amount: mainGrantAmount.value,
        grant_amounts: buildGrantAmountsPayload(),
        report_title: form.value.report_title,
        request_date: formatRequestDateForPayload(form.value.request_date),
        paper_size: 'A4',
        orientation: 'landscape',
        budget_program: normalizeBudgetProgram(form.value.budget_program),
        highlight_jpm_members: false,
        include_endorsed_by: false,
        show_remarks: false,
        include_projected_columns: true,
        include_interview_columns: true,
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
</script>
