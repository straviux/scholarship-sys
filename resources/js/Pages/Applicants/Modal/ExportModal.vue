<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal :closable="true"
        :style="{ width: '450px' }" header="Export Filtered Data">

        <form @submit.prevent="exportData" class="p-4">
            <!-- Export Configuration -->
            <div class="mb-6">
                <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Export Settings</h4>

                <!-- Paper Settings -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Paper Size</label>
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            placeholder="Select size" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Orientation</label>
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                            optionValue="value" placeholder="Select orientation" class="w-full" />
                    </div>
                </div>

                <!-- Export Type -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Export Format</label>
                    <div class="flex gap-4">
                        <div class="flex items-center">
                            <RadioButton v-model="exportFormat" inputId="pdf" value="pdf" />
                            <label for="pdf" class="ml-2 text-sm">PDF (.pdf)</label>
                        </div>
                        <div class="flex items-center">
                            <RadioButton v-model="exportFormat" inputId="xlsx" value="xlsx" />
                            <label for="xlsx" class="ml-2 text-sm">Excel (.xlsx)</label>
                        </div>

                    </div>
                </div>

                <!-- Info Message -->
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm text-blue-700">
                    <i class="pi pi-info-circle mr-2"></i>
                    <span>Export will use the current filter settings and display {{ totalRecords }} record(s).</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <Button type="button" label="Cancel" severity="secondary" @click="close" outlined />
                <Button type="submit" label="Export Now" icon="pi pi-download" severity="success"
                    :loading="isExporting" />
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';

// PrimeVue Components

const props = defineProps({
    show: Boolean,
    filters: {
        type: Object,
        default: () => ({})
    },
    totalRecords: {
        type: [Number, String],
        default: 0
    }
});

const emit = defineEmits(['update:show']);

// State Management
const isExporting = ref(false);
const exportFormat = ref('pdf');
const paperSize = ref('A4');
const orientation = ref('landscape');

// Options
const paperSizeOptions = [
    { label: 'A4 (210 × 297 mm)', value: 'A4' },
    { label: 'Letter (8.5 × 11 in)', value: 'Letter' },
    { label: 'Legal/Long (8.5 × 13 in)', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait (Vertical)', value: 'portrait' },
    { label: 'Landscape (Horizontal)', value: 'landscape' },
];

// Methods
function close() {
    emit('update:show', false);
}

function exportData() {
    isExporting.value = true;

    // Build query params from current filters
    const params = {
        // Filter parameters
        name: props.filters.name || '',
        parent_name: props.filters.parent_name || '',
        program: props.filters.program?.shortname?.toLowerCase() || '',
        // Handle school as array (multiselect) or single value
        school: Array.isArray(props.filters.school)
            ? props.filters.school.map(s => s.shortname?.toLowerCase() || s).join(',')
            : (props.filters.school?.shortname?.toLowerCase() || ''),
        course: props.filters.course?.shortname?.toLowerCase() || '',
        municipality: props.filters.municipality?.name?.toLowerCase() || '',
        year_level: props.filters.year_level?.value?.toLowerCase() || '',
        date_from: props.filters.date_from ? moment(props.filters.date_from).format('YYYY-MM-DD') : '',
        date_to: props.filters.date_to ? moment(props.filters.date_to).format('YYYY-MM-DD') : '',
        global_search: props.filters.global_search || '',

        // Handle JPM filter - convert to appropriate parameter
        show_jpm_only: props.filters.jpm_filter === 'jpm_only' ? 1 : '',
        hide_jpm: props.filters.jpm_filter === 'hide_jpm' ? 1 : '',

        // Export settings
        export_format: exportFormat.value,
        paper_size: paperSize.value,
        orientation: orientation.value,
    };

    // Remove empty parameters
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key];
        }
    });

    // Build URL with query parameters
    const queryString = new URLSearchParams(params).toString();
    const exportUrl = route('waitinglist.export') + (queryString ? '?' + queryString : '');

    // Open export URL in new tab (for download)
    window.open(exportUrl, '_blank');

    // Reset state and close modal
    setTimeout(() => {
        isExporting.value = false;
        close();
    }, 1000);
}
</script>
