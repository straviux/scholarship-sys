<template>
    <IosModal :visible="visible" title="Budget Monitoring" width="99vw" max-width="1520px"
        body-style="padding: 20px;" @update:visible="val => emit('update:visible', val)">
        <template #header-right>
            <button class="ios-nav-btn" style="right:58px;color:#34C759" @click="exportExcel"
                :disabled="summary.length === 0" v-tooltip.bottom="'Download Excel'">
                <AppIcon name="file-excel" />
            </button>
            <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="summary.length === 0"
                v-tooltip.bottom="'Print Report'">
                <AppIcon name="print" />
            </button>
        </template>

                    <!-- Fiscal Year Filter row -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-5">
                        <div class="flex items-center gap-2 shrink-0">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Fiscal
                                Year</label>
                            <Select v-model="activeFiscalYear" :options="fiscalYears" placeholder="All fiscal years"
                                showClear
                                :pt="{ root: { style: 'border-radius: 1rem; min-width: 180px; font-size: 0.875rem' } }" />
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Program</label>
                            <Select v-model="activeProgram" :options="programOptions" placeholder="All programs"
                                showClear
                                :pt="{ root: { style: 'border-radius: 1rem; min-width: 180px; font-size: 0.875rem' } }" />
                        </div>
                        <span class="text-xs text-gray-400 ml-auto">
                            Only <strong class="text-gray-600">Paid</strong> &amp;
                            <strong class="text-gray-600">Claimed</strong> transactions are deducted
                        </span>
                    </div>

                    <!-- No data -->
                    <div v-if="summary.length === 0" class="py-12 text-center text-gray-400">
                        <AppIcon name="inbox" class="text-4xl mb-3 block" />
                        <p class="text-sm">No budget particulars found
                            <template v-if="activeFiscalYear || activeProgram">
                                for
                                <span v-if="activeProgram" class="font-semibold">{{ activeProgram }}</span>
                                <span v-if="activeFiscalYear && activeProgram"> · </span>
                                <span v-if="activeFiscalYear">FY {{ activeFiscalYear }}</span>
                            </template>.
                        </p>
                    </div>

                    <!-- Table -->
                    <DataTable v-else :value="summary" showGridlines stripedRows scrollable
                        class="text-sm budget-table">
                        <Column field="program" header="Program" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ data.program }}</span>
                            </template>
                        </Column>
                        <Column header="Description / Account Code / R. Center" style="min-width: 280px">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ data.description || '—' }}</div>
                                    <div v-if="data.account_code" class="text-xs text-gray-500 mt-1">
                                        Account Code: {{ data.account_code }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ data.rc_name || 'N/A' }}
                                        <span v-if="data.rc_code">({{ data.rc_code }})</span>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="fiscal_year" header="Fiscal Year" style="min-width: 120px">
                            <template #body="{ data }">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-lg">{{
                                    data.fiscal_year || '—' }}</span>
                            </template>
                        </Column>
                        <Column header="Date Start" style="min-width: 130px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ formatDate(data.date_start) }}</span>
                            </template>
                        </Column>
                        <Column header="Date End" style="min-width: 130px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ formatDate(data.date_end) }}</span>
                            </template>
                        </Column>
                        <Column header="Allotment" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">₱{{
                                    fmt(data.total_allotment) }}</span>
                            </template>
                        </Column>
                        <Column header="Disbursed" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="font-semibold text-orange-500">₱{{ fmt(data.disbursed) }}</span>
                            </template>
                        </Column>
                        <Column header="Remaining" style="min-width: 150px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="data.overBudget ? 'font-bold text-red-600' : 'font-bold text-green-600'">
                                        ₱{{ fmt(data.remaining) }}
                                    </span>
                                    <Tag v-if="data.overBudget" value="Over budget" severity="danger" size="small"
                                        rounded />
                                </div>
                            </template>
                        </Column>
                        <Column header="Usage" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden shrink-0">
                                        <div class="h-full rounded-full transition-all duration-300"
                                            :class="data.overBudget ? 'bg-red-500' : ((data.disbursed / data.total_allotment) * 100) >= 80 ? 'bg-orange-400' : 'bg-green-500'"
                                            :style="{ width: Math.min((data.disbursed / data.total_allotment) * 100, 100) + '%' }">
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold w-10 shrink-0 tabular-nums"
                                        :class="data.overBudget ? 'text-red-600' : data.pct >= 80 ? 'text-orange-500' : 'text-green-600'">
                                        {{ ((data.disbursed / data.total_allotment) * 100).toFixed(2) }}%
                                    </span>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Grand totals -->
                    <div v-if="summary.length > 0"
                        class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 grid grid-cols-3 gap-4 text-sm">
                        <div class="bg-gray-50 dark:bg-[#2a3441] rounded-2xl p-3 text-center">
                            <div class="text-xs text-gray-500 mb-1">Total Allotment</div>
                            <div class="font-bold text-gray-700 dark:text-gray-200">₱{{ fmt(totals.allotment) }}</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-[#2a3441] rounded-2xl p-3 text-center">
                            <div class="text-xs text-gray-500 mb-1">Total Disbursed</div>
                            <div class="font-bold text-orange-500">₱{{ fmt(totals.disbursed) }}</div>
                        </div>
                        <div class="rounded-2xl p-3 text-center"
                            :class="totals.remaining >= 0 ? 'bg-green-50 dark:bg-[#1e2e20]' : 'bg-red-50 dark:bg-[#2e1e1e]'">
                            <div class="text-xs text-gray-500 mb-1">Total Remaining</div>
                            <div class="font-bold" :class="totals.remaining >= 0 ? 'text-green-600' : 'text-red-600'">
                                ₱{{ fmt(totals.remaining) }}
                            </div>
                        </div>
                    </div>
    </IosModal>
</template>

<script setup>
import AppIcon from '@/Components/ui/AppIcon.vue';
import { ref, computed } from 'vue';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';
import BudgetMonitoringTemplate from '@/Pages/PaymentMonitoring/Pdf/BudgetMonitoringTemplate.vue';
import { exportBudgetMonitoringExcel } from '@/Pages/PaymentMonitoring/Excel/BudgetMonitoringExcel.js';
import IosModal from '@/Components/ui/IosModal.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: { type: Boolean, default: false },
    budgetParticulars: { type: Array, default: () => [] },
    disbursedByAllocation: { type: Object, default: () => ({}) },
    fiscalYears: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:visible', 'preview']);
const { buildHtmlDoc } = usePdfPrint();

const activeFiscalYear = ref(null);
const activeProgram = ref(null);

const programOptions = computed(() =>
    [...new Set(props.budgetParticulars.flatMap(bp => Array.isArray(bp.programs) ? bp.programs : (bp.program ? [bp.program] : [])))]
        .filter(Boolean)
        .sort()
);

const exportExcel = () => {
    const today = new Date().toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
    exportBudgetMonitoringExcel({
        rows: summary.value,
        totals: totals.value,
        filterProgram: activeProgram.value ?? null,
        filterFiscalYear: activeFiscalYear.value ?? null,
        today,
    });
};

const generateReport = () => {
    const today = new Date().toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
    const bodyHtml = renderVueTemplate(BudgetMonitoringTemplate, {
        rows: summary.value,
        totals: totals.value,
        filterProgram: activeProgram.value ?? null,
        filterFiscalYear: activeFiscalYear.value ?? null,
        today,
    });
    const titleParts = ['Budget-Monitoring-Report'];
    if (activeProgram.value) titleParts.push(activeProgram.value.replace(/\s+/g, '-'));
    if (activeFiscalYear.value) titleParts.push(activeFiscalYear.value);
    const title = titleParts.join('-');
    const htmlDoc = buildHtmlDoc(bodyHtml, title, 'a4');
    emit('update:visible', false);
    emit('preview', { htmlDoc, title, paperSize: 'a4' });
};

const fmt = (val) =>
    parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formatDate = (value) => {
    if (!value) return '—';

    const [year, month, day] = String(value).split('-').map(Number);
    if (!year || !month || !day) {
        return value;
    }

    return new Date(year, month - 1, day).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const summary = computed(() => {
    return props.budgetParticulars
        .filter(bp => !activeFiscalYear.value || bp.fiscal_year === activeFiscalYear.value)
        .filter(bp => !activeProgram.value || (Array.isArray(bp.programs) ? bp.programs.includes(activeProgram.value) : bp.program === activeProgram.value))
        .map(bp => {
            const disbursed = props.disbursedByAllocation[bp.key] ?? 0;
            const remaining = bp.total_allotment - disbursed;
            const pct = bp.total_allotment > 0
                ? Math.round((disbursed / bp.total_allotment) * 100)
                : 0;
            return { ...bp, disbursed, remaining, pct, overBudget: remaining < 0 };
        });
});

const totals = computed(() => ({
    allotment: summary.value.reduce((s, b) => s + b.total_allotment, 0),
    disbursed: summary.value.reduce((s, b) => s + b.disbursed, 0),
    remaining: summary.value.reduce((s, b) => s + b.remaining, 0),
}));
</script>

<style scoped>
:deep(.budget-table .p-datatable) {
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color);
}

:deep(.budget-table .p-datatable-table-container) {
    border-radius: 0;
}

:deep(.budget-table .p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}
</style>
