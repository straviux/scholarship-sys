<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal :closable="true"
        :style="{ width: '900px' }" header="Export Selected Applicants">

        <form @submit.prevent="handleExport" class="px-4 pb-2">
            <!-- Two Column Layout -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <!-- LEFT COLUMN: Export Options -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Export Format</h4>

                    <!-- Selection Summary -->
                    <div class="mb-4 p-3 bg-blue-50 rounded border border-blue-200">
                        <div class="font-semibold text-blue-900">{{ selectedRows.length }} applicant(s) selected</div>
                        <div class="text-sm text-blue-700 mt-1">Export all selected applicants</div>
                    </div>

                    <!-- Report Type -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Report Type</label>
                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="list" value="list" />
                                <label for="list" class="ml-2 text-sm">Detailed List</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="summary" value="summary" />
                                <label for="summary" class="ml-2 text-sm">Summary</label>
                            </div>
                        </div>
                        <small class="text-xs text-gray-500 mt-1">Choose format for your export</small>
                    </div>

                    <!-- Selected Applicants Preview -->
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-2">Selected Applicants</label>
                        <div class="bg-gray-50 rounded p-3 max-h-48 overflow-y-auto border border-gray-200">
                            <div v-if="selectedRows.length > 0" class="space-y-1">
                                <div v-for="(row, idx) in selectedRows.slice(0, 10)" :key="idx"
                                    class="text-xs text-gray-600 py-1 px-2 bg-white rounded border">
                                    {{ row.last_name }}, {{ row.first_name }}
                                </div>
                                <div v-if="selectedRows.length > 10" class="text-xs text-gray-500 italic px-2 py-1">
                                    ... and {{ selectedRows.length - 10 }} more
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Document Settings -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Document Settings</h4>

                    <!-- Paper Size -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Paper Size</label>
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">Choose paper size for your export</small>
                    </div>

                    <!-- Orientation -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Orientation</label>
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                            optionValue="value" class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">Choose portrait or landscape layout</small>
                    </div>

                    <!-- Export Summary -->
                    <div class="mb-4 p-3 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs uppercase tracking-wide text-gray-600 mb-2 font-medium">
                            Export Summary
                        </p>
                        <div class="text-xs text-gray-700 space-y-1">
                            <div><strong>Records:</strong> {{ selectedRows.length }}</div>
                            <div><strong>Type:</strong> {{ reportType === 'list' ? 'Detailed List' : 'Summary' }}</div>
                            <div><strong>Paper:</strong> {{ paperSize }} - {{ orientation }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <Button type="button" label="Cancel" severity="secondary" @click="close" outlined />
                <Button type="button" label="PDF" icon="pi pi-file-pdf" severity="danger" @click="exportAs('pdf')" />
                <Button type="button" label="Excel" icon="pi pi-file-excel" severity="success"
                    @click="exportAs('excel')" />
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { toast } from 'vue3-toastify';

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

// Methods
const close = () => {
    emit('update:show', false);
};

const exportAs = (format) => {
    if (props.selectedRows.length === 0) {
        toast.error('No applicants selected');
        return;
    }

    // Build query parameters
    const profileIds = props.selectedRows.map(row => row.profile_id).join(',');
    const params = new URLSearchParams({
        profile_ids: profileIds,
        report_type: reportType.value,
        paper_size: paperSize.value,
        orientation: orientation.value
    });

    if (format === 'pdf') {
        window.open(`/api/export-selected/pdf?${params.toString()}`, '_blank');
    } else if (format === 'excel') {
        window.open(`/api/export-selected/excel?${params.toString()}`, '_blank');
    }

    close();
    toast.success(`Exporting ${props.selectedRows.length} applicant(s) as ${format.toUpperCase()}...`);
};

const handleExport = (e) => {
    e.preventDefault();
    // Default to PDF if form submitted
    exportAs('pdf');
};
</script>
