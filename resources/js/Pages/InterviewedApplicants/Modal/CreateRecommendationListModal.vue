<template>
    <IosModal
        :visible="show"
        width="540px"
        :title="modalTitle"
        :show-action="true"
        :action-disabled="isSubmitDisabled || loading"
        :loading="loading"
        close-icon="x"
        icon-size="16"
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

                            <div v-if="budgetAllocationOptions.length" class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="wallet" :size="13" style="color: #F59E0B;" />
                                    Budget Allocation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedBudgetAllocation" :options="budgetAllocationOptions"
                                        optionLabel="label" optionValue="value" class="ios-select"
                                        placeholder="Select allocation" :invalid="showBudgetError" />
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
                        <div v-if="showBudgetError" class="ios-section-footer ios-error">
                            {{ budgetErrorMessage }}
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
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import AppIcon from '@/Components/ui/AppIcon.vue';
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
    prepared_by: '',
    prepared_by_position: DEFAULT_PREPARED_BY_POSITION,
    prepared_by_office: DEFAULT_PREPARED_BY_OFFICE,
    approved_by: DEFAULT_APPROVED_BY,
    approved_by_position: DEFAULT_APPROVED_BY_POSITION,
});
const selectedBudgetAllocation = ref(null);
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

function formatBudgetAllocationLabel(allocation) {
    return `${allocation?.program || 'N/A'} · ${allocation?.rc_name || allocation?.rc_code || 'N/A'} · FY ${allocation?.fiscal_year || 'N/A'}`;
}

function sameBudgetAllocation(left, right) {
    if (!left && !right) {
        return true;
    }

    if (!left || !right) {
        return false;
    }

    return String(left.key ?? '') === String(right.key ?? '')
        && String(left.program ?? '') === String(right.program ?? '')
        && String(left.rc_code ?? '') === String(right.rc_code ?? '')
        && String(left.rc_name ?? '') === String(right.rc_name ?? '')
        && String(left.fiscal_year ?? '') === String(right.fiscal_year ?? '');
}

const budgetAllocationOptions = computed(() => {
    const options = (props.budgetAllocations || []).map((allocation) => ({
        label: formatBudgetAllocationLabel(allocation),
        value: allocation,
    }));

    const currentBudgetAllocation = props.initialData?.budget_allocation;

    if (currentBudgetAllocation && !options.some((option) => sameBudgetAllocation(option.value, currentBudgetAllocation))) {
        options.unshift({
            label: formatBudgetAllocationLabel(currentBudgetAllocation),
            value: currentBudgetAllocation,
        });
    }

    return options;
});

const requiresBudgetAllocationSelection = computed(() => budgetAllocationOptions.value.length > 0);
const isSubmitDisabled = computed(() => props.selectedCount === 0);

function resetForm() {
    const initialData = props.initialData;

    form.value = {
        report_title: initialData?.report_title ?? 'RECOMMENDATION LIST FOR APPROVAL',
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
        prepared_by: form.value.prepared_by,
        prepared_by_position: form.value.prepared_by_position,
        prepared_by_office: form.value.prepared_by_office,
        approved_by: form.value.approved_by,
        approved_by_position: form.value.approved_by_position,
        budget_allocation: selectedBudgetAllocation.value,
    });
}

watch(() => props.show, (value) => {
    if (value) {
        resetForm();
    }
});

watch(selectedBudgetAllocation, () => {
    if (selectedBudgetAllocation.value) {
        showBudgetError.value = false;
    }
});
</script>

<style scoped>
.ios-error {
    color: #dc2626;
}

:deep(.ios-select) {
    width: 100%;
}
</style>