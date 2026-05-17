<script setup>
import { ref } from 'vue';
import { toast } from '@/utils/toast';
import { exportSelectedApplicantsExcel, printSelectedApplicantsReport } from '../Reports/selectedApplicantsExport';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    selectedRows: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show', 'export']);

// State
const reportType = ref('list');
const paperSize = ref('A4');
const orientation = ref('landscape');
const includeRemarks = ref(false);
const includeGrantProvision = ref(true);
const generating = ref(false);

// Options
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const close = () => {
    emit('update:show', false);
};

const exportAs = (format) => {
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
                includeRemarks: includeRemarks.value,
                includeGrantProvision: includeGrantProvision.value,
            });

            if (!opened) {
                toast.error('Pop-up blocked. Please allow pop-ups and try again.');
                return;
            }
        } else if (format === 'excel') {
            exportSelectedApplicantsExcel({
                selectedRows: props.selectedRows,
                reportType: reportType.value,
                includeRemarks: includeRemarks.value,
                includeGrantProvision: includeGrantProvision.value,
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
    <IosModal :visible="show" title="Export Selected" width="680px" max-width="95vw"
        body-style="padding: 0 16px;" @update:visible="val => !val && close()">
                    <!-- Selection Summary -->
                    <div class="ios-section">
                        <div class="ios-section-label">Selection</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Selected Applicants</span>
                                <span style="font-size: 15px; font-weight: 600; color: #007AFF;">{{ selectedRows.length
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Report Type -->
                    <div class="ios-section">
                        <div class="ios-section-label">Report Type</div>
                        <div class="ios-card">
                            <div class="ios-row" style="cursor: pointer;" @click="reportType = 'list'">
                                <span class="ios-row-label">Detailed List</span>
                                <AppIcon v-if="reportType === 'list'" name="check" :size="14" style="color: #007AFF;" />
                            </div>
                            <div class="ios-row ios-row-last" style="cursor: pointer;" @click="reportType = 'summary'">
                                <span class="ios-row-label">Summary</span>
                                <AppIcon v-if="reportType === 'summary'" name="check" :size="14"
                                    style="color: #007AFF;" />
                            </div>
                        </div>
                    </div>

                    <!-- Document Settings -->
                    <div class="ios-section">
                        <div class="ios-section-label">Document Settings</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Paper Size</span>
                                <div class="ios-row-control ios-row-control--compact ios-select">
                                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label"
                                        optionValue="value" class="w-full" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Orientation</span>
                                <div class="ios-row-control ios-row-control--compact ios-select">
                                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                                        optionValue="value" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="ios-section">
                        <div class="ios-section-label">Options</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Include Remarks</span>
                                <InputSwitch v-model="includeRemarks" />
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Include Grant Provision</span>
                                <InputSwitch v-model="includeGrantProvision" />
                            </div>
                        </div>
                    </div>

                    <!-- Preview List -->
                    <div class="ios-section" v-if="selectedRows.length > 0">
                        <div class="ios-section-label">Preview</div>
                        <div class="ios-card" style="max-height: 120px; overflow-y: auto;">
                            <div v-for="(row, idx) in selectedRows.slice(0, 10)" :key="idx" class="ios-row"
                                :class="{ 'ios-row-last': idx === Math.min(selectedRows.length, 10) - 1 }">
                                <span style="font-size: 13px; color: #000;">{{ row.last_name }}, {{ row.first_name
                                    }}</span>
                            </div>
                            <div v-if="selectedRows.length > 10" class="ios-row ios-row-last"
                                style="justify-content: center;">
                                <span style="font-size: 12px; color: #8E8E93; font-style: italic;">... and {{
                                    selectedRows.length - 10 }} more</span>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div class="ios-section">
                        <div class="ios-export-buttons">
                            <button class="ios-export-btn ios-export-pdf" @click="exportAs('pdf')" :disabled="generating">
                                <AppIcon name="file-pdf" :size="16" /> Export as PDF
                            </button>
                            <button class="ios-export-btn ios-export-excel" @click="exportAs('excel')" :disabled="generating">
                                <AppIcon name="file-excel" :size="16" /> Export as Excel
                            </button>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
    </IosModal>
</template>

