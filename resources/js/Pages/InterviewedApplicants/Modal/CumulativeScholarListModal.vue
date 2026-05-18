<template>
    <IosModal
        :visible="show"
        width="min(720px, calc(100vw - 24px))"
        title="Cumulative Scholars"
        close-icon="x"
        :icon-size="16"
        bodyClass="space-y-4"
        @update:visible="value => emit('update:show', value)"
        @close="emit('update:show', false)"
    >
        <div class="py-4">
            <div class="rounded-4xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Cumulative List</div>
                    <div class="mt-1 text-sm text-slate-600">
                        Review the names behind the calendar-year cumulative count before generating any report.
                    </div>
                </div>

                <div class="rounded-3xl bg-slate-100 px-4 py-3 text-left sm:min-w-[132px] sm:text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Counted</div>
                    <div class="mt-1 text-xl font-semibold text-slate-800">{{ selectedBudgetAllocationApprovedCount.toLocaleString() }}</div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-5">
                <div class="md:col-span-3">
                    <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <AppIcon name="wallet" :size="12" style="color: #34C759;" />
                        Budget Allocation
                    </div>
                    <Select
                        v-model="selectedBudgetAllocationKey"
                        :options="budgetAllocationOptions"
                        optionLabel="label"
                        optionValue="value"
                        :placeholder="budgetAllocationOptions.length ? 'Select allocation' : 'No allocation available'"
                        :disabled="budgetAllocationOptions.length === 0"
                        appendTo="body"
                        class="ios-select w-full"
                        showClear
                    >
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

                <div class="md:col-span-2">
                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Search</div>
                    <InputText
                        v-model="cumulativeScholarSearchQuery"
                        class="w-full"
                        placeholder="Search cumulative scholars"
                        :disabled="!selectedBudgetAllocation"
                    />
                </div>
            </div>

            <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-600">
                <template v-if="selectedBudgetAllocation">
                    {{ formatBudgetAllocationSelectionMessage(selectedBudgetAllocation) }}
                </template>
                <template v-else-if="budgetAllocationOptions.length">
                    Select a budget allocation to inspect the calendar-year cumulative scholar list.
                </template>
                <template v-else>
                    No budget allocation is available for monitoring.
                </template>
            </div>
        </div>

        <div class="overflow-hidden rounded-4xl border border-slate-200 bg-white shadow-sm mt-2">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Cumulative Scholar Names</div>
                        <div class="mt-1 text-sm text-slate-600">
                            <template v-if="showCumulativeScholarMissingDetails">
                                Detailed rows are unavailable on this calendar-year snapshot.
                            </template>
                            <template v-else-if="selectedBudgetAllocation">
                                Showing {{ filteredCumulativeScholars.length.toLocaleString() }} of {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }}.
                            </template>
                            <template v-else>
                                Select a budget allocation to load the names for its calendar year.
                            </template>
                        </div>
                    </div>

                    <div v-if="selectedBudgetAllocation" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        {{ filteredCumulativeScholars.length.toLocaleString() }} visible
                    </div>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto bg-slate-50/70">
                <div v-if="showCumulativeScholarMissingDetails" class="px-4 py-10 text-center text-xs text-slate-500">
                    Detailed scholar rows are unavailable on this calendar-year snapshot. Refresh the page and re-open this modal to load the latest cumulative list.
                </div>

                <div v-else-if="filteredCumulativeScholars.length" class="divide-y divide-slate-200">
                    <div
                        v-for="scholar in filteredCumulativeScholars"
                        :key="scholar.profile_id"
                        class="bg-white/90 px-4 py-3"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-slate-800">{{ scholar.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">Program: {{ scholar.program || 'N/A' }}</div>
                            </div>

                            <div class="shrink-0 text-right text-[11px] text-slate-500">
                                <div>{{ formatDate(scholar.date_approved) }}</div>
                                <div class="mt-1 font-medium text-slate-700">{{ formatScholarStatus(scholar.status) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="px-4 py-10 text-center text-xs text-slate-500">
                    <template v-if="selectedBudgetAllocation && selectedBudgetAllocationApprovedCount">
                        No scholars match the current search.
                    </template>
                    <template v-else-if="selectedBudgetAllocation">
                        No approved scholars are currently counted for this calendar year.
                    </template>
                    <template v-else>
                        Select a budget allocation to view the cumulative scholar names.
                    </template>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500">
                <template v-if="showCumulativeScholarMissingDetails">
                    {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }} counted, but detailed rows are not available in this response.
                </template>
                <template v-else>
                    Showing {{ filteredCumulativeScholars.length.toLocaleString() }} of {{ selectedBudgetAllocationApprovedCount.toLocaleString() }} scholar{{ selectedBudgetAllocationApprovedCount !== 1 ? 's' : '' }}.
                </template>
            </div>
        </div>
        </div>
    </IosModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    budgetAllocations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show']);

const selectedBudgetAllocationKey = ref(null);
const cumulativeScholarSearchQuery = ref('');

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
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

const budgetAllocationOptions = computed(() => {
    return (props.budgetAllocations || []).map((allocation) => ({
        label: formatBudgetAllocationLabel(allocation),
        description: formatBudgetAllocationDescription(allocation),
        value: allocation.key,
    }));
});

const selectedBudgetAllocation = computed(() => {
    return (props.budgetAllocations || []).find((allocation) => allocation.key === selectedBudgetAllocationKey.value) || null;
});

const selectedBudgetAllocationApprovedCount = computed(() => {
    return Number(selectedBudgetAllocation.value?.approved_scholars_to_date ?? 0) || 0;
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

const filteredCumulativeScholars = computed(() => {
    const searchTerm = cumulativeScholarSearchQuery.value.trim().toLowerCase();

    if (!searchTerm) {
        return cumulativeScholars.value;
    }

    return cumulativeScholars.value.filter((scholar) => {
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

function findBudgetAllocationOption(value) {
    return budgetAllocationOptions.value.find((option) => option.value === value) || null;
}

watch(() => props.show, (value) => {
    if (!value) {
        return;
    }

    cumulativeScholarSearchQuery.value = '';

    const allocationExists = (props.budgetAllocations || []).some((allocation) => allocation.key === selectedBudgetAllocationKey.value);

    if (!allocationExists) {
        selectedBudgetAllocationKey.value = props.budgetAllocations.length === 1
            ? props.budgetAllocations[0].key
            : null;
    }
});

watch(selectedBudgetAllocationKey, () => {
    cumulativeScholarSearchQuery.value = '';
});
</script>