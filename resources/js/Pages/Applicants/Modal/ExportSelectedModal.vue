<script setup>
import { ref, computed } from 'vue';
import { toast } from '@/utils/toast';
import { exportSelectedApplicantsExcel, printSelectedApplicantsReport } from '../Reports/selectedApplicantsExport';
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
    }
});

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
const generating = ref(false);

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
        :style="{ width: '420px' }" :breakpoints="{ '640px': '90vw' }"
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
                    <p><span class="font-semibold">Based on current filters.</span> This report includes all applicants matching the filters applied on the table.</p>
                    <p class="mt-1 text-blue-600">To export specific records only, tick their checkboxes in the table and use <span class="font-semibold">Export Selected</span> instead.</p>
                </div>
            </div>
        </div>

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
