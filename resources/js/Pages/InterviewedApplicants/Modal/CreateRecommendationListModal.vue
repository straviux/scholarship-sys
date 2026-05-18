<template>
    <IosModal
        :visible="show"
        width="540px"
        :title="modalTitle"
        :show-action="true"
        :action-disabled="isSubmitDisabled || loading"
        :loading="loading"
        close-icon="x"
        :icon-size="16"
        @update:visible="value => emit('update:show', value)"
        @close="close"
        @action="submitForm"
    >
                    <div class="ios-section">
                        <div class="ios-section-label">Selection</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="users" :size="13" style="color: #007AFF;" />
                                    Selected Applicants
                                </div>
                                <div class="text-sm font-semibold text-slate-700">{{ selectedCount }}</div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="check-circle" :size="13" style="color: #16A34A;" />
                                    Recommendation Status
                                </div>
                                <div class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                    Recommended for Approval
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Report Details</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="file-text" :size="13" style="color: #8E8E93;" />
                                    Report Title
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.report_title" class="ios-select"
                                        placeholder="Recommendation list title" maxlength="255" />
                                </div>
                            </div>

                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="bookmark-fill" :size="13" style="color: #007AFF;" />
                                    Program
                                </div>
                                <div class="ios-row-control">
                                    <ProgramSelect v-model="form.budget_program" class="ios-select"
                                        custom-placeholder="Select program" />
                                </div>
                            </div>

                            <div v-if="budgetAllocationOptions.length" class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="wallet" :size="13" style="color: #F59E0B;" />
                                    Budget Allocation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedBudgetAllocation" :options="budgetAllocationOptions"
                                        optionLabel="label" optionValue="value" class="ios-select"
                                        placeholder="Select allocation" :invalid="showBudgetError">
                                        <template #value="{ value, placeholder }">
                                            <div v-if="value" class="leading-tight">
                                                <div class="font-medium text-slate-700">{{ formatBudgetAllocationLabel(value) }}</div>
                                                <div v-if="formatBudgetAllocationDescription(value)" class="text-[11px] text-slate-500">
                                                    {{ formatBudgetAllocationDescription(value) }}
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

                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="users" :size="13" style="color: #16A34A;" />
                                    Highlight JPM Names
                                </div>
                                <div class="ios-row-control flex items-center justify-end gap-3">
                                    <span class="text-xs text-slate-500">Mark JPM-related applicants in print</span>
                                    <ToggleSwitch v-model="form.highlight_jpm_members" />
                                </div>
                            </div>

                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="panel-right-open" :size="13" style="color: #34C759;" />
                                    Print Layout
                                </div>
                                <div class="text-xs text-slate-500">
                                    Saved as A4 landscape using the interviewed applicants printable layout.
                                </div>
                            </div>
                        </div>
                        <div v-if="showBudgetFooter" class="ios-section-footer" :class="{ 'ios-error': showBudgetError }">
                            {{ budgetFooterMessage }}
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
                                    This list follows the selected allocation's program coverage and approval-date window.
                                </div>
                            </div>

                            <InputText
                                v-model="cumulativeScholarSearchQuery"
                                class="ios-select"
                                placeholder="Search cumulative scholars"
                            />

                            <div v-if="showCumulativeScholarMissingDetails" class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                Detailed scholar rows are unavailable on this saved allocation. Re-select a current allocation to refresh the cumulative list.
                            </div>

                            <div v-else-if="filteredCumulativeScholars.length" class="max-h-72 space-y-2 overflow-y-auto pr-1">
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
                                    No approved scholars currently match this allocation.
                                </template>
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            <template v-if="showCumulativeScholarMissingDetails">
                                {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }} counted, but detailed rows are not available on this saved allocation.
                            </template>
                            <template v-else>
                                Showing {{ filteredCumulativeScholars.length.toLocaleString() }} of {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }}.
                            </template>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Prepared By</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="user" :size="13" style="color: #34C759;" />
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.prepared_by" class="ios-select"
                                        placeholder="Prepared by" maxlength="255" />
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="briefcase" :size="13" style="color: #8E8E93;" />
                                    Position
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.prepared_by_position" class="ios-select"
                                        placeholder="Prepared by position" maxlength="255" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="building-2" :size="13" style="color: #007AFF;" />
                                    Office
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.prepared_by_office" class="ios-select"
                                        placeholder="Prepared by office" maxlength="255" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Approved By</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="user-check" :size="13" style="color: #007AFF;" />
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.approved_by" class="ios-select"
                                        placeholder="Approved by" maxlength="255" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="briefcase" :size="13" style="color: #8E8E93;" />
                                    Position
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.approved_by_position" class="ios-select"
                                        placeholder="Approved by position" maxlength="255" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 24px;"></div>
    </IosModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

import AppIcon from '@/Components/ui/AppIcon.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import IosModal from '@/Components/ui/IosModal.vue';

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
    budget_program: null,
    highlight_jpm_members: false,
    prepared_by: '',
    prepared_by_position: DEFAULT_PREPARED_BY_POSITION,
    prepared_by_office: DEFAULT_PREPARED_BY_OFFICE,
    approved_by: DEFAULT_APPROVED_BY,
    approved_by_position: DEFAULT_APPROVED_BY_POSITION,
});
const selectedBudgetAllocation = ref(null);
const cumulativeScholarSearchQuery = ref('');
const showBudgetError = ref(false);

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

function formatBudgetAllocationSelectionMessage(allocation) {
    const label = formatBudgetAllocationLabel(allocation);
    const description = formatBudgetAllocationDescription(allocation);

    if (description && description !== label) {
        return `Monitoring ${label} · ${description}.`;
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
const selectedBudgetAllocationApprovedCount = computed(() => Number(selectedBudgetAllocation.value?.approved_scholars_to_date ?? 0) || 0);
const hasCumulativeScholarDetails = computed(() => Array.isArray(selectedBudgetAllocation.value?.approved_scholars));
const cumulativeScholars = computed(() => {
    const scholars = hasCumulativeScholarDetails.value
        ? [...selectedBudgetAllocation.value.approved_scholars]
        : [];

    return scholars.sort((left, right) => (left?.name || '').localeCompare(right?.name || '', undefined, {
        sensitivity: 'base',
    }));
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
const showCumulativeScholarMissingDetails = computed(() => {
    return selectedBudgetAllocationApprovedCount.value > 0 && !hasCumulativeScholarDetails.value;
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
        budget_program: initialData?.budget_program ?? initialData?.budget_allocation?.program ?? null,
        highlight_jpm_members: Boolean(initialData?.highlight_jpm_members),
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
        paper_size: 'A4',
        orientation: 'landscape',
        budget_program: normalizeBudgetProgram(form.value.budget_program),
        highlight_jpm_members: form.value.highlight_jpm_members,
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

