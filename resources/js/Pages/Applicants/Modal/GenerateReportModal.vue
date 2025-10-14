<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal :closable="true"
        :style="{ width: '500px' }" header="Generate Report">


        <form @submit.prevent="generateReport" class="p-4">
            <!-- Filters Section -->
            <div class="mb-6">
                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date From</label>
                        <DatePicker v-model="dateFrom" placeholder="Start date" showButtonBar class="w-full"
                            dateFormat="M dd, yy" />
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date To</label>
                        <DatePicker v-model="dateTo" placeholder="End date" showButtonBar class="w-full"
                            dateFormat="M dd, yy" />
                        <small v-if="dateTo && dateFrom && isDateToInvalid" class="text-red-500 text-xs mt-1">
                            Date To must be after Date From
                        </small>
                    </div>
                </div>

                <!-- Academic Filters -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Program</label>
                        <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All Programs"
                            class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">School</label>
                        <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All Schools"
                            class="w-full" />
                    </div>
                </div>

                <!-- Course Selection -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Course(s)</label>
                    <CourseSelect v-model="selectedCourses" :scholarship-program-id="selectedProgram?.id"
                        label="shortname" custom-placeholder="All Courses" :multiple="true" class="w-full" />
                </div>

                <!-- Location & Year Level -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Municipality</label>
                        <MunicipalitySelect v-model="selectedMunicipality" custom-placeholder="All Municipalities"
                            class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Year Level</label>
                        <YearLevelSelect v-model="selectedYearLevel" custom-placeholder="All Year Levels"
                            class="w-full" />
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div v-if="activeFiltersCount > 0" class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ activeFiltersCount }} filter(s) applied</span>
                    <Button label="Clear All" size="small" text severity="danger" @click="clearAllFilters"
                        icon="pi pi-times" />
                </div>
            </div>

            <!-- Report Options Section -->
            <div class="mb-6">
                <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Report Options</h4>

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
                </div>

                <!-- Paper Settings -->
                <div class="grid grid-cols-2 gap-4">
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
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <Button type="button" label="Cancel" severity="secondary" @click="close" outlined />
                <Button type="submit" label="Generate Report" icon="pi pi-file-export" severity="success"
                    :disabled="isDateToInvalid" />
            </div>
        </form>
    </Dialog>

    <!-- Report Preview Modal -->
    <Dialog v-if="showReportModal" :visible="showReportModal" modal :closable="true" :maximizable="true"
        :style="{ width: '95vw', height: '90vh' }" @update:visible="val => showReportModal = val"
        header="Report Preview">

        <component :is="ReportView" v-if="ReportView" :params="lastParams" @close="showReportModal = false" />
    </Dialog>
</template>

<script setup>
import { ref, computed, shallowRef, markRaw } from 'vue';
import { defineAsyncComponent } from 'vue';
import moment from 'moment';

// PrimeVue Components
import RadioButton from 'primevue/radiobutton';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';

// Custom Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show']);

// State Management
const showReportModal = ref(false);
const lastParams = ref({});
const ReportView = shallowRef(null);

// Filter States
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourses = ref([]);
const selectedMunicipality = ref(null);
const selectedYearLevel = ref(null);

// Report Configuration
const reportType = ref('list');
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

// Computed Properties
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (dateFrom.value || dateTo.value) count++;
    if (selectedProgram.value) count++;
    if (selectedSchool.value) count++;
    if (selectedCourses.value && selectedCourses.value.length > 0) count++;
    if (selectedMunicipality.value) count++;
    if (selectedYearLevel.value) count++;
    return count;
});

// Methods
function close() {
    emit('update:show', false);
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedProgram.value = null;
    selectedSchool.value = null;
    selectedCourses.value = [];
    selectedMunicipality.value = null;
    selectedYearLevel.value = null;
}

function generateReport() {
    // Validate dates one more time
    if (isDateToInvalid.value) {
        return;
    }

    // Format dates
    const date_from = dateFrom.value ? moment(dateFrom.value).format('YYYY-MM-DD') : '';
    const date_to = dateTo.value ? moment(dateTo.value).format('YYYY-MM-DD') : '';

    // Convert selected courses array to comma-separated shortnames
    const courseShortnames = selectedCourses.value && selectedCourses.value.length > 0
        ? selectedCourses.value.map(course => course.shortname).join(',')
        : '';

    // Build params object
    const params = {
        date_from,
        date_to,
        program: selectedProgram.value?.id || '',
        school: selectedSchool.value?.shortname || '',
        courses: courseShortnames,
        municipality: selectedMunicipality.value?.name || '',
        year_level: selectedYearLevel.value?.value || '',
        report_type: reportType.value,
        paper_size: paperSize.value,
        orientation: orientation.value,
    };

    // Store params and load report view
    lastParams.value = { ...params };

    // Lazy load ReportView component
    if (!ReportView.value) {
        ReportView.value = markRaw(defineAsyncComponent(() => import('./ReportView.vue')));
    }

    // Show report modal
    showReportModal.value = true;
}
</script>
