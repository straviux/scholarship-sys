<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { toast } from '@/utils/toast';

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

    const profileIds = props.selectedRows.map(row => row.profile_id).join(',');
    const params = new URLSearchParams({
        profile_ids: profileIds,
        report_type: reportType.value,
        paper_size: paperSize.value,
        orientation: orientation.value,
        include_remarks: includeRemarks.value ? 1 : 0,
        include_grant_provision: includeGrantProvision.value ? 1 : 0
    });

    if (format === 'pdf') {
        window.open(`/api/export-selected/pdf?${params.toString()}`, '_blank');
    } else if (format === 'excel') {
        window.open(`/api/export-selected/excel?${params.toString()}`, '_blank');
    }

    close();
    toast.success(`Exporting ${props.selectedRows.length} applicant(s) as ${format.toUpperCase()}...`);
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '680px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-toggleswitch')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}
function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}
function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
</script>

<template>
    <Dialog :visible="show" modal @update:visible="val => !val && close()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Export Selected</span>
                    <span class="ios-nav-btn" style="right: 16px; visibility: hidden;">.</span>
                </div>

                <!-- Body -->
                <div class="ios-body">
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
                                <div class="ios-row-control ios-select">
                                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label"
                                        optionValue="value" class="w-full" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Orientation</span>
                                <div class="ios-row-control ios-select">
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
                            <button class="ios-export-btn ios-export-pdf" @click="exportAs('pdf')">
                                <AppIcon name="file-pdf" :size="16" /> Export as PDF
                            </button>
                            <button class="ios-export-btn ios-export-excel" @click="exportAs('excel')">
                                <AppIcon name="file-excel" :size="16" /> Export as Excel
                            </button>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    font-size: 14px;
    color: #8E8E93;
    letter-spacing: -0.4px;
    font-weight: 500;
}

.ios-row-control.ios-select {
    width: 140px;
}

.ios-row-control.ios-select :deep(.p-select) {
    border: none;
    background: transparent;
    box-shadow: none;
    font-size: 13px;
    color: #8E8E93;
    text-align: right;
    padding: 0;
}

.ios-export-buttons {
    display: flex;
    gap: 10px;
}

.ios-export-btn {
    flex: 1;
    padding: 12px 16px;
    border-radius: 10px;
    border: none;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: opacity 0.15s;
}

.ios-export-btn:hover {
    opacity: 0.8;
}

.ios-export-pdf {
    background: #FF3B30;
    color: #FFFFFF;
}

.ios-export-excel {
    background: #34C759;
    color: #FFFFFF;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
