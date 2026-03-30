<script setup>
import { ref, computed, shallowRef, markRaw, watch, onBeforeUnmount } from 'vue';
import { defineAsyncComponent } from 'vue';
import moment from 'moment';

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
const groupBy = ref('none');
const showSequenceNumbers = ref(true);
const enableJpmHighlighting = ref(true);
const jpmFilter = ref('all');

const groupByOptions = [
    { label: 'No Grouping (By Date Filed)', value: 'none' },
    { label: 'By School', value: 'school' },
    { label: 'By Program', value: 'program' },
    { label: 'By Course', value: 'course' },
    { label: 'By Year Level', value: 'year_level' },
    { label: 'By Municipality', value: 'municipality' },
];

watch(enableJpmHighlighting, (newValue) => {
    if (!newValue) {
        jpmFilter.value = 'all';
    }
});

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
    if (Array.isArray(selectedSchool.value) ? selectedSchool.value.length > 0 : selectedSchool.value) count++;
    if (selectedCourses.value && selectedCourses.value.length > 0) count++;
    if (selectedMunicipality.value) count++;
    if (selectedYearLevel.value) count++;
    return count;
});

function close() {
    emit('update:show', false);
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedProgram.value = null;
    selectedSchool.value = [];
    selectedCourses.value = [];
    selectedMunicipality.value = null;
    selectedYearLevel.value = null;
    groupBy.value = 'none';
    showSequenceNumbers.value = true;
    enableJpmHighlighting.value = true;
    jpmFilter.value = 'all';
}

function generateReport() {
    if (isDateToInvalid.value) return;

    const date_from = dateFrom.value ? moment(dateFrom.value).format('YYYY-MM-DD') : '';
    const date_to = dateTo.value ? moment(dateTo.value).format('YYYY-MM-DD') : '';

    const courseShortnames = selectedCourses.value && selectedCourses.value.length > 0
        ? selectedCourses.value.map(course => course.shortname).join(',')
        : '';

    const schoolShortnames = Array.isArray(selectedSchool.value) && selectedSchool.value.length > 0
        ? selectedSchool.value.map(school => school.shortname).join(',')
        : selectedSchool.value?.shortname || '';

    const params = {
        date_from,
        date_to,
        program: selectedProgram.value?.id || '',
        school: schoolShortnames,
        courses: courseShortnames,
        municipality: selectedMunicipality.value?.name || '',
        year_level: selectedYearLevel.value?.value || '',
        report_type: reportType.value,
        group_by: groupBy.value,
        show_sequence_numbers: showSequenceNumbers.value ? 1 : 0,
        paper_size: 'A4',
        orientation: 'landscape',
        enable_jpm_highlighting: enableJpmHighlighting.value ? 1 : 0,
        show_jpm_only: (enableJpmHighlighting.value && jpmFilter.value === 'jpm_only') ? 1 : '',
        hide_jpm: (enableJpmHighlighting.value && jpmFilter.value === 'hide_jpm') ? 1 : '',
    };

    lastParams.value = { ...params };

    if (!ReportView.value) {
        ReportView.value = markRaw(defineAsyncComponent(() => import('./ReportView.vue')));
    }

    showReportModal.value = true;
}

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '780px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-toggleswitch, .p-radiobutton, .p-datepicker')) return;
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
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Generate Report</span>
                    <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="isDateToInvalid">
                        Generate
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body">
                    <div class="ios-two-col">
                        <!-- LEFT: Filters -->
                        <div class="ios-col">
                            <!-- Date Range -->
                            <div class="ios-section" style="margin-top: 16px;">
                                <div class="ios-section-label">Date Range</div>
                                <div class="ios-card">
                                    <div class="ios-row">
                                        <span class="ios-row-label">From</span>
                                        <div class="ios-row-control ios-select">
                                            <DatePicker v-model="dateFrom" placeholder="Start date" showButtonBar
                                                dateFormat="M dd, yy" size="small" class="w-full" />
                                        </div>
                                    </div>
                                    <div class="ios-row ios-row-last">
                                        <span class="ios-row-label">To</span>
                                        <div class="ios-row-control ios-select">
                                            <DatePicker v-model="dateTo" placeholder="End date" showButtonBar
                                                dateFormat="M dd, yy" size="small" class="w-full" />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="dateTo && dateFrom && isDateToInvalid" class="ios-section-footer ios-error">
                                    Date To must be after Date From
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="ios-section">
                                <div class="ios-section-label">Filters</div>
                                <div class="ios-card">
                                    <div class="ios-row">
                                        <span class="ios-row-label">Program</span>
                                        <div class="ios-row-control ios-select">
                                            <ProgramSelect v-model="selectedProgram" label="shortname"
                                                custom-placeholder="All" class="w-full" />
                                        </div>
                                    </div>
                                    <div class="ios-row">
                                        <span class="ios-row-label">School</span>
                                        <div class="ios-row-control ios-select">
                                            <SchoolSelect v-model="selectedSchool" label="shortname"
                                                custom-placeholder="All" class="w-full" :multiple="true" />
                                        </div>
                                    </div>
                                    <div class="ios-row">
                                        <span class="ios-row-label">Course</span>
                                        <div class="ios-row-control ios-select">
                                            <CourseSelect v-model="selectedCourses"
                                                :scholarship-program-id="selectedProgram?.id" label="shortname"
                                                custom-placeholder="All" :multiple="true" class="w-full" />
                                        </div>
                                    </div>
                                    <div class="ios-row">
                                        <span class="ios-row-label">Municipality</span>
                                        <div class="ios-row-control ios-select">
                                            <MunicipalitySelect v-model="selectedMunicipality" custom-placeholder="All"
                                                class="w-full" />
                                        </div>
                                    </div>
                                    <div class="ios-row ios-row-last">
                                        <span class="ios-row-label">Year Level</span>
                                        <div class="ios-row-control ios-select">
                                            <YearLevelSelect v-model="selectedYearLevel" custom-placeholder="All"
                                                class="w-full" />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="activeFiltersCount > 0" class="ios-section-footer"
                                    style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>{{ activeFiltersCount }} filter(s) applied</span>
                                    <button
                                        style="color: #FF3B30; font-size: 13px; background: none; border: none; cursor: pointer;"
                                        @click="clearAllFilters">Clear All</button>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: Report Options -->
                        <div class="ios-col">
                            <!-- Report Type -->
                            <div class="ios-section" style="margin-top: 16px;">
                                <div class="ios-section-label">Report Type</div>
                                <div class="ios-card">
                                    <div class="ios-row" style="cursor: pointer;" @click="reportType = 'list'">
                                        <span class="ios-row-label">Detailed List</span>
                                        <i v-if="reportType === 'list'" class="pi pi-check"
                                            style="color: #007AFF; font-size: 14px;"></i>
                                    </div>
                                    <div class="ios-row ios-row-last" style="cursor: pointer;"
                                        @click="reportType = 'summary'">
                                        <span class="ios-row-label">Summary</span>
                                        <i v-if="reportType === 'summary'" class="pi pi-check"
                                            style="color: #007AFF; font-size: 14px;"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Group By -->
                            <div class="ios-section">
                                <div class="ios-section-label">Group By</div>
                                <div class="ios-card">
                                    <div class="ios-row ios-row-last">
                                        <span class="ios-row-label">Grouping</span>
                                        <div class="ios-row-control ios-select">
                                            <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                                optionValue="value" class="w-full" />
                                        </div>
                                    </div>
                                </div>
                                <div class="ios-section-footer">How records should be organized</div>
                            </div>

                            <!-- Display Options -->
                            <div class="ios-section">
                                <div class="ios-section-label">Display Options</div>
                                <div class="ios-card">
                                    <div class="ios-row">
                                        <span class="ios-row-label">Sequence Numbers</span>
                                        <ToggleSwitch v-model="showSequenceNumbers" />
                                    </div>
                                    <div class="ios-row ios-row-last">
                                        <span class="ios-row-label">JPM Highlighting</span>
                                        <ToggleSwitch v-model="enableJpmHighlighting" />
                                    </div>
                                </div>
                            </div>

                            <!-- JPM Filter -->
                            <div class="ios-section">
                                <div class="ios-section-label">JPM Filter</div>
                                <div class="ios-card">
                                    <div class="ios-row" style="cursor: pointer;"
                                        :style="{ opacity: enableJpmHighlighting ? 1 : 0.5 }"
                                        @click="enableJpmHighlighting && (jpmFilter = 'all')">
                                        <span class="ios-row-label">Show All</span>
                                        <i v-if="jpmFilter === 'all'" class="pi pi-check"
                                            style="color: #007AFF; font-size: 14px;"></i>
                                    </div>
                                    <div class="ios-row" style="cursor: pointer;"
                                        :style="{ opacity: enableJpmHighlighting ? 1 : 0.5 }"
                                        @click="enableJpmHighlighting && (jpmFilter = 'jpm_only')">
                                        <span class="ios-row-label">JPM Only</span>
                                        <i v-if="jpmFilter === 'jpm_only'" class="pi pi-check"
                                            style="color: #007AFF; font-size: 14px;"></i>
                                    </div>
                                    <div class="ios-row ios-row-last" style="cursor: pointer;"
                                        :style="{ opacity: enableJpmHighlighting ? 1 : 0.5 }"
                                        @click="enableJpmHighlighting && (jpmFilter = 'hide_jpm')">
                                        <span class="ios-row-label">Hide JPM</span>
                                        <i v-if="jpmFilter === 'hide_jpm'" class="pi pi-check"
                                            style="color: #007AFF; font-size: 14px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Report Preview Modal (stays standard - fullscreen preview) -->
    <Dialog v-if="showReportModal" :visible="showReportModal" modal :closable="true" :maximizable="true"
        :style="{ width: '95vw', height: '90vh' }" @update:visible="val => showReportModal = val"
        header="Report Preview">
        <component :is="ReportView" v-if="ReportView" :params="lastParams" @close="showReportModal = false" />
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

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.ios-col {
    min-width: 0;
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

.ios-section-footer {
    font-size: 13px;
    color: #6D6D72;
    padding: 6px 16px 0;
    line-height: 1.3;
}

.ios-error {
    color: #FF3B30;
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
    color: #000;
    letter-spacing: -0.4px;
    font-weight: 500;
    flex-shrink: 0;
}

.ios-row-control.ios-select {
    width: 160px;
}

.ios-row-control.ios-select :deep(.p-select),
.ios-row-control.ios-select :deep(.p-datepicker),
.ios-row-control.ios-select :deep(.p-multiselect) {
    border: none;
    background: transparent;
    box-shadow: none;
    font-size: 13px;
    color: #8E8E93;
    text-align: right;
    padding: 0;
}

/* Dark mode overrides */
:global(.dark) .ios-modal {
    background: #222831;
}
:global(.dark) .ios-nav-bar {
    background: #2a3040;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
:global(.dark) .ios-nav-title {
    color: #d1d5db;
}
:global(.dark) .ios-nav-cancel {
    color: #9ca3af;
}
:global(.dark) .ios-nav-action {
    color: #d1d5db;
}
:global(.dark) .ios-section-label {
    color: #9ca3af;
}
:global(.dark) .ios-section-footer {
    color: #6b7280;
}
:global(.dark) .ios-card {
    background: #222831;
    border-color: rgba(255, 255, 255, 0.08);
}
:global(.dark) .ios-row {
    border-bottom-color: rgba(255, 255, 255, 0.06);
}
:global(.dark) .ios-row-label {
    color: #d1d5db;
}
:global(.dark) .ios-body {
    scrollbar-color: transparent transparent;
}
:global(.dark) .ios-body:hover {
    scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
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
