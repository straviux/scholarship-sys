<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';
import { exportSelectedApplicantsExcel, printSelectedApplicantsReport } from '../Reports/selectedApplicantsExport';
import { GROUP_BY_OPTIONS } from '../Reports/reportGrouping';
import { stripHtml } from '@/utils/sanitize';
import AppIcon from '@/Components/ui/AppIcon.vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';

const props = defineProps({
    show: Boolean,
    selectedRows: {
        type: Array,
        default: () => []
    },
    // 'selected' = checked rows, 'all' = full filtered report set
    mode: {
        type: String,
        default: 'selected'
    },
    // Optional pre-filled report title. When provided, it seeds the title editor
    // (and refreshes it as long as the user hasn't typed their own).
    defaultTitle: {
        type: String,
        default: ''
    },
    // When true, exposes a "Show signatories" toggle that appends the fixed
    // Prepared-by / Approved-by block to the exported report.
    enableSignatories: {
        type: Boolean,
        default: false
    },
    // When true, exposes the "Show projected fields" toggle.
    enableProjected: {
        type: Boolean,
        default: false
    },
    // When true, exposes the "Show JPM column" and "Highlight JPM members" toggles.
    enableJpm: {
        type: Boolean,
        default: false
    },
    // When true, exposes the "Show grant provision" toggle (grant type + amount).
    enableGrantProvision: {
        type: Boolean,
        default: false
    },
    // Default row ordering for the report: 'name' (alphabetical) or 'date_filed'.
    defaultSort: {
        type: String,
        default: 'date_filed'
    }
});

// JPM highlighting is restricted to staff roles that handle JPM tagging.
// Even with enableJpm set by the parent, the toggle stays hidden for others.
const page = usePage();
const canUseJpmToggle = computed(() => {
    const userRoles = page.props.auth?.user?.roles || [];
    const allowedRoles = ['administrator', 'program_manager', 'jpm_admin', 'screening_officer'];
    return userRoles.some(role => allowedRoles.includes(role.name || role));
});
const jpmEnabled = computed(() => props.enableJpm && canUseJpmToggle.value);

const enableReportColumns = computed(() => props.enableProjected || jpmEnabled.value || props.enableGrantProvision);

const isAllMode = computed(() => props.mode === 'all');
const modalTitle = computed(() => isAllMode.value ? 'Generate Report' : 'Export Selected');
const countLabel = computed(() => isAllMode.value ? 'applicant(s) in report' : 'selected');

const emit = defineEmits(['update:show', 'export']);

// State
const reportType = ref('list');
const paperSize = ref('A4');
const orientation = ref('landscape');
// 'none' = no column, 'values' = remarks with data, 'blank' = empty column to fill in by hand
const remarksMode = ref('none');
// Rich-text (Quill) HTML; empty = use the default report title
const customTitle = ref('');
// Append the fixed signatory block at the bottom of the report
const showSignatories = ref(true);
// Optional report columns / behaviours (only shown when enableReportColumns)
const showProjected = ref(false);
const highlightJpm = ref(false);
const showGrantProvision = ref(false);
// Grouping — two levels only (main group + optional sub-group), no custom names.
const groupBy = ref('none');
const groupBySub = ref('none');
const groupByOptions = GROUP_BY_OPTIONS;
const subGroupByOptions = computed(() =>
    GROUP_BY_OPTIONS.filter(o => o.value !== 'none' && o.value !== groupBy.value)
);
// Row ordering within the report (and within each group when grouping is on)
const sortBy = ref(props.defaultSort);
const sortByOptions = [
    { label: 'Alphabetical (Name)', value: 'name' },
    { label: 'Date Filed (Oldest First)', value: 'date_filed' },
];
// Reset the sub-group whenever the main group is cleared or collides with it.
watch(groupBy, (value) => {
    if (value === 'none' || value === groupBySub.value) {
        groupBySub.value = 'none';
    }
});
const generating = ref(false);

// Seed the title editor from `defaultTitle` when the modal opens — but only
// while the user hasn't customised it (so their edits are never clobbered).
let lastSeededTitle = '';
watch(() => props.show, (visible) => {
    if (!visible || !props.defaultTitle) return;
    const current = stripHtml(customTitle.value || '').trim();
    if (current === '' || current === stripHtml(lastSeededTitle).trim()) {
        customTitle.value = props.defaultTitle;
        lastSeededTitle = props.defaultTitle;
    }
});

// Options
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Long (8.5×13in)', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const remarksOptions = [
    { label: 'No Remarks', value: 'none' },
    { label: 'With Values', value: 'values' },
    { label: 'Blank Column', value: 'blank' },
];

const close = () => {
    emit('update:show', false);
};

const exportAs = async (format) => {
    if (props.selectedRows.length === 0) {
        toast.error('No applicants selected');
        return;
    }

    generating.value = true;

    try {
        if (format === 'pdf') {
            const opened = printSelectedApplicantsReport({
                selectedRows: props.selectedRows,
                reportType: reportType.value,
                paperSize: paperSize.value,
                orientation: orientation.value,
                remarksMode: remarksMode.value,
                customTitle: customTitle.value,
                showSignatories: props.enableSignatories && showSignatories.value,
                showProjected: props.enableProjected && showProjected.value,
                highlightJpm: jpmEnabled.value && highlightJpm.value,
                showGrantProvision: props.enableGrantProvision && showGrantProvision.value,
                groupBy: reportType.value === 'list' ? groupBy.value : 'none',
                groupBySub: reportType.value === 'list' ? groupBySub.value : 'none',
                sortBy: sortBy.value,
            });

            if (!opened) {
                toast.error('Pop-up blocked. Please allow pop-ups and try again.');
                return;
            }
        } else if (format === 'excel') {
            await exportSelectedApplicantsExcel({
                selectedRows: props.selectedRows,
                reportType: reportType.value,
                remarksMode: remarksMode.value,
                customTitle: customTitle.value,
                showSignatories: props.enableSignatories && showSignatories.value,
                showProjected: props.enableProjected && showProjected.value,
                highlightJpm: jpmEnabled.value && highlightJpm.value,
                showGrantProvision: props.enableGrantProvision && showGrantProvision.value,
                groupBy: reportType.value === 'list' ? groupBy.value : 'none',
                groupBySub: reportType.value === 'list' ? groupBySub.value : 'none',
                sortBy: sortBy.value,
            });
        }

        close();
        toast.success(`Exported ${props.selectedRows.length} applicant(s) as ${format.toUpperCase()}.`);
    } catch (error) {
        console.error('Failed to export selected applicants:', error);
        toast.error(`Failed to export applicant(s) as ${format.toUpperCase()}.`);
    } finally {
        generating.value = false;
    }
};
</script>

<template>
    <Dialog :visible="show" :modal="true" :draggable="true" :closable="false"
        :style="{ width: '720px' }" :breakpoints="{ '960px': '90vw' }"
        @update:visible="val => !val && close()">
        <template #header>
            <div class="flex items-center gap-2">
                <button class="p-1 !rounded-full !bg-transparent hover:!bg-gray-100 !border-none cursor-pointer" @click="close">
                    <AppIcon name="x" :size="16" />
                </button>
                <div>
                    <div class="text-lg font-semibold leading-tight">{{ modalTitle }}</div>
                    <div class="text-2xs text-gray-400">{{ selectedRows.length }} {{ countLabel }}</div>
                </div>
            </div>
        </template>

        <!-- Filter note + how it works (Generate Report mode) -->
        <div v-if="isAllMode" class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5">
            <div class="flex items-start gap-2 text-2xs leading-relaxed text-blue-800">
                <AppIcon name="info-circle" :size="14" class="mt-px shrink-0" />
                <div>
                    <p><span class="font-semibold">Based on current filters.</span> This report includes all records matching the filters applied on the table.</p>
                    <p class="mt-1 text-blue-600">To export specific records only, tick their checkboxes in the table and use <span class="font-semibold">Export Selected</span> instead.</p>
                </div>
            </div>
        </div>

        <!-- Two-column layout: title→remarks on the left, column toggles on the right -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <!-- Left column -->
        <div>
        <!-- Report Title -->
        <div class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title</label>
            <Editor v-model="customTitle" editorStyle="height: 90px" class="report-title-editor">
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
            <p class="mt-1 text-2xs text-gray-400">Leave blank to use the default title.</p>
        </div>

        <!-- Report Type -->
        <div class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Type</label>
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

        <!-- Paper & Orientation -->
        <div class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Paper &amp; Orientation</label>
            <div class="flex gap-2">
                <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                    class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                    class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
            </div>
        </div>

        <!-- Options -->
        <div class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Remarks</label>
            <Select v-model="remarksMode" :options="remarksOptions" optionLabel="label" optionValue="value"
                class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
            <p v-if="remarksMode === 'blank'" class="mt-1 text-2xs text-gray-400">
                Adds an empty Remarks column to fill in by hand.
            </p>
        </div>
        </div>
        <!-- Right column -->
        <div>

        <!-- Grouping (Detailed List only) — two levels, no custom names -->
        <div v-if="reportType === 'list'" class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Grouping</label>
            <div class="flex flex-col gap-2">
                <Select v-model="groupBy" :options="groupByOptions" optionLabel="label" optionValue="value"
                    placeholder="Group by" class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                <Select v-if="groupBy !== 'none'" v-model="groupBySub" :options="subGroupByOptions"
                    optionLabel="label" optionValue="value" placeholder="Sub-group (optional)" showClear
                    class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
            </div>
        </div>

        <!-- Sort order (Detailed List only) — applies within groups too -->
        <div v-if="reportType === 'list'" class="mb-4">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Sort By</label>
            <Select v-model="sortBy" :options="sortByOptions" optionLabel="label" optionValue="value"
                class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
            <p class="mt-1 text-2xs text-gray-400">Rows are numbered in printed order.</p>
        </div>

        <!-- Report columns (Detailed List only) -->
        <div v-if="enableReportColumns && reportType === 'list'" class="mb-4 space-y-2">
            <label class="block text-2xs font-semibold uppercase tracking-wider text-gray-500">Columns</label>
            <label v-if="enableProjected" class="flex items-start gap-2 cursor-pointer select-none">
                <input type="checkbox" v-model="showProjected" class="mt-0.5 h-4 w-4 accent-blue-500 cursor-pointer" />
                <span>
                    <span class="block text-xs font-medium text-gray-700">Show projected fields</span>
                    <span class="block text-2xs text-gray-400">Adds Projected Expense, Terms and Completion columns.</span>
                </span>
            </label>
            <label v-if="enableGrantProvision" class="flex items-start gap-2 cursor-pointer select-none">
                <input type="checkbox" v-model="showGrantProvision" class="mt-0.5 h-4 w-4 accent-blue-500 cursor-pointer" />
                <span>
                    <span class="block text-xs font-medium text-gray-700">Show grant provision</span>
                    <span class="block text-2xs text-gray-400">Adds a Grant Provision column (grant type and amount).</span>
                </span>
            </label>
            <label v-if="jpmEnabled" class="flex items-start gap-2 cursor-pointer select-none">
                <input type="checkbox" v-model="highlightJpm" class="mt-0.5 h-4 w-4 accent-blue-500 cursor-pointer" />
                <span>
                    <span class="block text-xs font-medium text-gray-700">Highlight JPM members</span>
                    <span class="block text-2xs text-gray-400">Highlights JPM rows and lists them first.</span>
                </span>
            </label>
        </div>

        <!-- Signatories -->
        <div v-if="enableSignatories" class="mb-4">
            <label class="flex items-start gap-2 cursor-pointer select-none">
                <input type="checkbox" v-model="showSignatories" class="mt-0.5 h-4 w-4 accent-blue-500 cursor-pointer" />
                <span>
                    <span class="block text-xs font-medium text-gray-700">Show signatories</span>
                    <span class="block text-2xs text-gray-400">Adds NUR-AINA S. IBRAHIM (Prepared by) and AMY ROA ALVAREZ (Approved by) at the bottom.</span>
                </span>
            </label>
        </div>
        </div>
        </div>

        <!-- Export Buttons -->
        <div class="flex gap-2 pt-3 mt-2 border-t border-gray-100">
            <button class="flex-1 inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-white bg-red-500 px-4 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="exportAs('pdf')" :disabled="generating">
                <AppIcon name="file-pdf" :size="14" /> PDF
            </button>
            <button class="flex-1 inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-white bg-green-500 px-4 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="exportAs('excel')" :disabled="generating">
                <AppIcon name="file-excel" :size="14" /> Excel
            </button>
        </div>
    </Dialog>
</template>
