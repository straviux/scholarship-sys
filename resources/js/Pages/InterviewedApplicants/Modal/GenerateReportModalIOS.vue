<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- iOS Navigation Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close" v-tooltip.bottom="'Close'"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Generate Report</span>
                    <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="isDateToInvalid"
                        v-tooltip.bottom="'Generate Report'"><i class="pi pi-check"></i></button>
                </div>

                <div class="ios-body">
                    <!-- REPORT TYPE — iOS Segmented Control -->
                    <div class="ios-section">
                        <div class="ios-section-label">Report Type</div>
                        <div class="ios-segmented-control">
                            <button :class="['ios-segment', reportType === 'list' && 'ios-segment-active']"
                                @click="reportType = 'list'">
                                <i class="pi pi-list" style="font-size: 13px;"></i>
                                Detailed List
                            </button>
                            <button :class="['ios-segment', reportType === 'summary' && 'ios-segment-active']"
                                @click="reportType = 'summary'">
                                <i class="pi pi-chart-bar" style="font-size: 13px;"></i>
                                Summary
                            </button>
                        </div>
                    </div>

                    <!-- FILTERS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Filters</div>
                        <div class="ios-card">
                            <!-- Recommendation -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-star-fill" style="color: #FF9500; font-size: 13px;"></i>
                                    Recommendation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="selectedRecommendation" :options="recommendationOptions"
                                        optionLabel="label" optionValue="value" placeholder="All" showClear
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- Program -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-bookmark-fill" style="color: #007AFF; font-size: 13px;"></i>
                                    Program
                                </div>
                                <div class="ios-row-control">
                                    <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All"
                                        class="ios-select" />
                                </div>
                            </div>

                            <!-- School -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-building" style="color: #34C759; font-size: 13px;"></i>
                                    School
                                </div>
                                <div class="ios-row-control">
                                    <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All"
                                        class="ios-select" :multiple="false" />
                                </div>
                            </div>

                            <!-- Course -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-graduation-cap" style="color: #AF52DE; font-size: 13px;"></i>
                                    Course
                                </div>
                                <div class="ios-row-control">
                                    <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id"
                                        label="shortname" custom-placeholder="All" class="ios-select" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATE RANGE -->
                    <div class="ios-section">
                        <div class="ios-section-label">Interview Date Range</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    From
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateFrom" placeholder="Any" showButtonBar dateFormat="M dd, yy"
                                        class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    To
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateTo" placeholder="Any" showButtonBar dateFormat="M dd, yy"
                                        class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                        </div>
                        <div v-if="dateTo && dateFrom && isDateToInvalid" class="ios-section-footer ios-error">
                            Date To must be after Date From
                        </div>
                    </div>

                    <!-- REPORT OPTIONS -->
                    <div class="ios-section">
                        <div class="ios-section-label">Options</div>
                        <div class="ios-card">
                            <!-- Group By -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-objects-column" style="color: #5856D6; font-size: 13px;"></i>
                                    Group By
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="groupBy" :options="groupByOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Paper Size -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-file" style="color: #8E8E93; font-size: 13px;"></i>
                                    Paper Size
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Orientation -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-desktop" style="color: #FF9500; font-size: 13px;"></i>
                                    Orientation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <!-- Assessment Toggle -->
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <i class="pi pi-clipboard" style="color: #007AFF; font-size: 13px;"></i>
                                    Assessment Details
                                </div>
                                <div>
                                    <ToggleSwitch v-model="includeAssessment" />
                                </div>
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            Include academic potential, financial need, and communication skills columns.
                        </div>
                    </div>



                    <!-- Clear Filters Banner -->
                    <div v-if="activeFiltersCount > 0" class="ios-section">
                        <button class="ios-destructive-btn" @click="clearAllFilters">
                            Clear All Filters ({{ activeFiltersCount }})
                        </button>
                    </div>

                    <!-- Bottom spacer -->
                    <div style="height: 24px;"></div>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Report Preview Modal — full screen iOS style -->
    <Dialog :visible="showPreview" @update:visible="val => showPreview = val" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal ios-modal-full" :style="previewModalStyle">
                <!-- Preview Nav Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onPreviewDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="showPreview = false">
                        <i class="pi pi-chevron-left" style="font-size: 13px;"></i> Back
                    </button>
                    <span class="ios-nav-title">Report Preview</span>
                    <div class="ios-nav-actions">
                        <button class="ios-icon-btn ios-icon-btn-red" @click="downloadReport('pdf')"
                            title="Download PDF">
                            <i class="pi pi-file-pdf"></i>
                        </button>
                        <button class="ios-icon-btn ios-icon-btn-green" @click="downloadReport('excel')"
                            title="Download Excel">
                            <i class="pi pi-file-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="ios-body ios-preview-body">
                    <div class="ios-report-container">
                        <!-- Report Header -->
                        <div class="ios-report-header">
                            <h1>{{ reportType === 'list' ? 'Interviewed Applicants' : 'Interview Summary' }}</h1>
                            <p>{{ moment().format('MMMM D, YYYY · h:mm A') }}</p>
                        </div>

                        <!-- Active Filters Pills -->
                        <div v-if="activeFiltersCount > 0" class="ios-filter-pills">
                            <span v-if="lastParams.recommendation" class="ios-pill">{{ lastParams.recommendation
                                }}</span>
                            <span v-if="lastParams.program" class="ios-pill">{{ lastParams.program }}</span>
                            <span v-if="lastParams.school" class="ios-pill">{{ lastParams.school }}</span>
                            <span v-if="lastParams.course" class="ios-pill">{{ lastParams.course }}</span>
                            <span v-if="lastParams.date_from || lastParams.date_to" class="ios-pill">
                                {{ lastParams.date_from || '…' }} — {{ lastParams.date_to || '…' }}
                            </span>
                        </div>

                        <!-- Empty State -->
                        <div v-if="reportData.length === 0" class="ios-empty-state">
                            <i class="pi pi-inbox"></i>
                            <p>No records match the selected filters</p>
                        </div>

                        <!-- LIST VIEW -->
                        <template v-else-if="reportType === 'list'">
                            <template v-if="groupBy !== 'none'">
                                <div v-for="(group, groupName) in groupedData" :key="groupName"
                                    class="ios-report-group">
                                    <div class="ios-group-header">
                                        <span>{{ groupName }}</span>
                                        <span class="ios-group-count">{{ group.length }}</span>
                                    </div>
                                    <div class="ios-table-wrap">
                                        <table class="ios-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Program</th>
                                                    <th>Course</th>
                                                    <th>Recommendation</th>
                                                    <th v-if="includeAssessment">Academic</th>
                                                    <th v-if="includeAssessment">Financial</th>
                                                    <th v-if="includeAssessment">Communication</th>
                                                    <th>Date</th>
                                                    <th>Interviewer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(record, idx) in group" :key="record.id">
                                                    <td>{{ idx + 1 }}</td>
                                                    <td class="ios-cell-name">{{ record.profile.last_name }}, {{
                                                        record.profile.first_name }}</td>
                                                    <td>{{ record.program?.shortname || '—' }}</td>
                                                    <td>{{ record.course?.shortname || '—' }}</td>
                                                    <td><span :class="'ios-rec-' + record.recommendation">{{
                                                        formatRecommendation(record.recommendation) }}</span></td>
                                                    <td v-if="includeAssessment">{{
                                                        capitalize(record.academic_potential) }}</td>
                                                    <td v-if="includeAssessment">{{
                                                        capitalize(record.financial_need_level) }}</td>
                                                    <td v-if="includeAssessment">{{
                                                        capitalize(record.communication_skills) }}</td>
                                                    <td>{{ formatDate(record.interviewed_at) }}</td>
                                                    <td>{{ record.interviewer?.name || '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <div class="ios-table-wrap">
                                    <table class="ios-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Program</th>
                                                <th>Course</th>
                                                <th>Recommendation</th>
                                                <th v-if="includeAssessment">Academic</th>
                                                <th v-if="includeAssessment">Financial</th>
                                                <th v-if="includeAssessment">Communication</th>
                                                <th>Date</th>
                                                <th>Interviewer</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(record, idx) in reportData" :key="record.id">
                                                <td>{{ idx + 1 }}</td>
                                                <td class="ios-cell-name">{{ record.profile.last_name }}, {{
                                                    record.profile.first_name }}</td>
                                                <td>{{ record.program?.shortname || '—' }}</td>
                                                <td>{{ record.course?.shortname || '—' }}</td>
                                                <td><span :class="'ios-rec-' + record.recommendation">{{
                                                    formatRecommendation(record.recommendation) }}</span></td>
                                                <td v-if="includeAssessment">{{ capitalize(record.academic_potential) }}
                                                </td>
                                                <td v-if="includeAssessment">{{ capitalize(record.financial_need_level)
                                                    }}</td>
                                                <td v-if="includeAssessment">{{ capitalize(record.communication_skills)
                                                    }}</td>
                                                <td>{{ formatDate(record.interviewed_at) }}</td>
                                                <td>{{ record.interviewer?.name || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </template>

                        <!-- SUMMARY VIEW -->
                        <template v-else>
                            <div class="ios-summary-grid">
                                <div class="ios-summary-card">
                                    <div class="ios-summary-card-title">By Recommendation</div>
                                    <div v-for="(count, rec) in summaryByRecommendation" :key="rec"
                                        class="ios-summary-row">
                                        <span :class="'ios-rec-' + rec">{{ formatRecommendation(rec) }}</span>
                                        <span class="ios-summary-count">{{ count }}</span>
                                    </div>
                                </div>
                                <div class="ios-summary-card">
                                    <div class="ios-summary-card-title">By Program</div>
                                    <div v-for="(count, prog) in summaryByProgram" :key="prog" class="ios-summary-row">
                                        <span>{{ prog }}</span>
                                        <span class="ios-summary-count">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ios-total-banner">
                                Total Records: <strong>{{ reportData.length }}</strong>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import moment from 'moment';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';

const props = defineProps({
    show: Boolean,
    interviewedApplicants: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:show']);

// Filter States
const selectedRecommendation = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourse = ref(null);
const dateFrom = ref(null);
const dateTo = ref(null);

// Report Options
const reportType = ref('list');
const groupBy = ref('none');
const paperSize = ref('A4');
const orientation = ref('landscape');
const includeAssessment = ref(true);

// Preview
const showPreview = ref(false);
const lastParams = ref({});
const reportData = ref([]);

// Options
const recommendationOptions = [
    { label: 'Recommended for Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' }
];

const groupByOptions = [
    { label: 'No Grouping', value: 'none' },
    { label: 'By Program', value: 'program' },
    { label: 'By Course', value: 'course' },
    { label: 'By Recommendation', value: 'recommendation' },
    { label: 'By Interviewer', value: 'interviewer' },
];

const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

// Computed
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedRecommendation.value) count++;
    if (selectedProgram.value) count++;
    if (selectedSchool.value) count++;
    if (selectedCourse.value) count++;
    if (dateFrom.value || dateTo.value) count++;
    return count;
});

const previewCount = computed(() => filterApplicants().length);

const groupedData = computed(() => {
    const groups = {};
    for (const record of reportData.value) {
        let key;
        if (groupBy.value === 'program') key = record.program?.shortname || 'N/A';
        else if (groupBy.value === 'course') key = record.course?.shortname || 'N/A';
        else if (groupBy.value === 'recommendation') key = formatRecommendation(record.recommendation);
        else if (groupBy.value === 'interviewer') key = record.interviewer?.name || 'N/A';
        else key = 'All';
        if (!groups[key]) groups[key] = [];
        groups[key].push(record);
    }
    return groups;
});

const summaryByRecommendation = computed(() => {
    const map = {};
    for (const r of reportData.value) {
        map[r.recommendation || 'unknown'] = (map[r.recommendation || 'unknown'] || 0) + 1;
    }
    return map;
});

const summaryByProgram = computed(() => {
    const map = {};
    for (const r of reportData.value) {
        const key = r.program?.shortname || 'N/A';
        map[key] = (map[key] || 0) + 1;
    }
    return map;
});

// Methods
function filterApplicants() {
    let list = [...(props.interviewedApplicants || [])];
    if (selectedRecommendation.value) list = list.filter(r => r.recommendation === selectedRecommendation.value);
    if (selectedProgram.value) list = list.filter(r => r.program?.id === selectedProgram.value.id);
    if (selectedSchool.value) list = list.filter(r => r.school?.id === selectedSchool.value.id);
    if (selectedCourse.value) list = list.filter(r => r.course?.id === selectedCourse.value.id);
    if (dateFrom.value) list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrAfter(moment(dateFrom.value), 'day'));
    if (dateTo.value) list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrBefore(moment(dateTo.value), 'day'));
    return list;
}

function close() {
    emit('update:show', false);
}

function clearAllFilters() {
    selectedRecommendation.value = null;
    selectedProgram.value = null;
    selectedSchool.value = null;
    selectedCourse.value = null;
    dateFrom.value = null;
    dateTo.value = null;
}

function generateReport() {
    if (isDateToInvalid.value) return;
    reportData.value = filterApplicants();
    lastParams.value = {
        recommendation: selectedRecommendation.value ? recommendationOptions.find(o => o.value === selectedRecommendation.value)?.label : '',
        program: selectedProgram.value?.shortname || '',
        school: selectedSchool.value?.shortname || '',
        course: selectedCourse.value?.shortname || '',
        date_from: dateFrom.value ? moment(dateFrom.value).format('MMM DD, YYYY') : '',
        date_to: dateTo.value ? moment(dateTo.value).format('MMM DD, YYYY') : '',
    };
    showPreview.value = true;
}

function downloadReport(format) {
    const ids = reportData.value.map(r => r.id).join(',');
    const params = new URLSearchParams({
        ids,
        report_type: reportType.value,
        group_by: groupBy.value,
        paper_size: paperSize.value,
        orientation: orientation.value,
        include_assessment: includeAssessment.value ? '1' : '0',
    });
    window.open(`/api/interviewed-applicants/export/${format}?${params.toString()}`, '_blank');
}

function formatRecommendation(value) {
    const labels = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };
    return labels[value] || 'N/A';
}

function formatDate(date) {
    return date ? moment(date).format('MMM DD, YYYY') : '—';
}

function capitalize(str) {
    if (!str) return '—';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ─── Drag logic (main modal) ───
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const modalStyle = computed(() => ({
    width: '520px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button')) return;
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

// ─── Drag logic (preview modal) ───
const previewDragOffset = ref({ x: 0, y: 0 });
const previewDragStart = ref(null);

const previewModalStyle = computed(() => ({
    width: '95vw',
    height: '90vh',
    transform: `translate(${previewDragOffset.value.x}px, ${previewDragOffset.value.y}px)`,
}));

function onPreviewDragStart(e) {
    if (e.target.closest('button')) return;
    previewDragStart.value = { x: e.clientX - previewDragOffset.value.x, y: e.clientY - previewDragOffset.value.y };
    document.addEventListener('pointermove', onPreviewDragMove);
    document.addEventListener('pointerup', onPreviewDragEnd);
}

function onPreviewDragMove(e) {
    if (!previewDragStart.value) return;
    previewDragOffset.value = { x: e.clientX - previewDragStart.value.x, y: e.clientY - previewDragStart.value.y };
}

function onPreviewDragEnd() {
    previewDragStart.value = null;
    document.removeEventListener('pointermove', onPreviewDragMove);
    document.removeEventListener('pointerup', onPreviewDragEnd);
}

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
    document.removeEventListener('pointermove', onPreviewDragMove);
    document.removeEventListener('pointerup', onPreviewDragEnd);
});
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   iOS DESIGN SYSTEM — PrimeVue Dialog Override
   ═══════════════════════════════════════════════ */

/* Dialog root & mask overrides are in the unscoped <style> block below
   because PrimeVue teleports Dialog to <body>, outside scoped reach */

/* iOS Modal Shell — rendered inside headless #container */
.ios-modal {
    background: rgba(242, 242, 247, 0.72);
    backdrop-filter: blur(40px) saturate(180%);
    -webkit-backdrop-filter: blur(40px) saturate(180%);
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow:
        0 25px 60px rgba(0, 0, 0, 0.25),
        0 0 0 0.5px rgba(255, 255, 255, 0.3);
    overflow: hidden;
    margin: 0 auto;
}

.ios-modal-full {
    max-height: 90vh;
}

/* Navigation Bar */
.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: rgba(255, 255, 255, 0.45);
    border-bottom: 0.5px solid rgba(255, 255, 255, 0.3);
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
    color: #6B7280;
    font-weight: 400;
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

.ios-nav-actions {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 6px;
}

/* Scrollable Body */
.ios-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

/* Sections */
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

/* iOS Grouped Card */
.ios-card {
    background: rgba(255, 255, 255, 0.55);
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid rgba(255, 255, 255, 0.4);
}

/* iOS Row */
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
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.ios-row-control {
    flex: 0 0 200px;
    width: 200px;
    display: flex;
    justify-content: flex-end;
    overflow: hidden;
}

.ios-row-control>* {
    width: 100%;
    min-width: 0;
}

/* iOS Segmented Control */
.ios-segmented-control {
    display: flex;
    background: rgba(118, 118, 128, 0.15);
    border-radius: 9px;
    padding: 2px;
    gap: 0;
    border: 0.5px solid rgba(255, 255, 255, 0.2);
}

.ios-segment {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 7px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #000;
    cursor: pointer;
    transition: all 0.2s ease;
    letter-spacing: -0.08px;
}

.ios-segment-active {
    background: rgba(255, 255, 255, 0.7);
    box-shadow:
        0 3px 8px rgba(0, 0, 0, 0.06),
        0 1px 1px rgba(0, 0, 0, 0.04),
        0 0 0 0.5px rgba(255, 255, 255, 0.5);
}

/* Preview Card */
.ios-preview-card {
    padding: 0;
}

.ios-preview-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 16px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-preview-row-last,
.ios-preview-row:last-child {
    border-bottom: none;
}

.ios-preview-label {
    font-size: 16px;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-preview-value {
    font-size: 16px;
    color: #8E8E93;
    letter-spacing: -0.4px;
}

.ios-preview-badge {
    background: #007AFF;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    padding: 1px 10px;
    border-radius: 20px;
    min-width: 28px;
    text-align: center;
}

/* Destructive Button */
.ios-destructive-btn {
    display: block;
    width: 100%;
    background: rgba(255, 255, 255, 0.45);
    border: 0.5px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 12px;
    text-align: center;
    font-size: 17px;
    color: #FF3B30;
    cursor: pointer;
    letter-spacing: -0.4px;
    transition: background 0.15s;
}

.ios-destructive-btn:hover {
    background: rgba(255, 255, 255, 0.6);
}

/* ═══════════════════════════════════════════════
   PREVIEW STYLES
   ═══════════════════════════════════════════════ */

.ios-preview-body {
    background: rgba(242, 242, 247, 0.5);
}

.ios-report-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px;
}

.ios-report-header {
    margin-bottom: 20px;
}

.ios-report-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #000;
    letter-spacing: -0.6px;
    margin: 0 0 4px;
}

.ios-report-header p {
    font-size: 15px;
    color: #8E8E93;
    margin: 0;
}

.ios-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 20px;
}

.ios-pill {
    display: inline-block;
    background: rgba(0, 122, 255, 0.1);
    color: #007AFF;
    font-size: 13px;
    font-weight: 500;
    padding: 4px 12px;
    border-radius: 20px;
}

.ios-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #8E8E93;
}

.ios-empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}

.ios-empty-state p {
    font-size: 17px;
}

/* Group Header */
.ios-report-group {
    margin-bottom: 24px;
}

.ios-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(60, 60, 67, 0.12);
    font-size: 20px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-group-count {
    font-size: 15px;
    font-weight: 500;
    color: #8E8E93;
    background: rgba(118, 118, 128, 0.12);
    padding: 2px 10px;
    border-radius: 20px;
}

/* Table */
.ios-table-wrap {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 0 0.5px rgba(0, 0, 0, 0.04);
}

.ios-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.ios-table thead tr {
    background: rgba(118, 118, 128, 0.06);
}

.ios-table th {
    padding: 10px 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-table td {
    padding: 10px 12px;
    color: #000;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.06);
    letter-spacing: -0.2px;
}

.ios-table tbody tr:last-child td {
    border-bottom: none;
}

.ios-cell-name {
    font-weight: 500;
}

/* Recommendation colors */
.ios-rec-recommended {
    color: #34C759;
    font-weight: 600;
}

.ios-rec-further_evaluation {
    color: #FF9500;
    font-weight: 600;
}

.ios-rec-not_recommended {
    color: #FF3B30;
    font-weight: 600;
}

/* Summary */
.ios-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.ios-summary-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
}

.ios-summary-card-title {
    font-size: 13px;
    font-weight: 600;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 12px 16px 8px;
}

.ios-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    border-top: 0.5px solid rgba(60, 60, 67, 0.08);
    font-size: 16px;
    color: #000;
}

.ios-summary-count {
    font-weight: 700;
    color: #007AFF;
    background: rgba(0, 122, 255, 0.08);
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 15px;
}

.ios-total-banner {
    margin-top: 16px;
    padding: 16px;
    text-align: center;
    background: #FFFFFF;
    border-radius: 10px;
    font-size: 17px;
    color: #000;
    letter-spacing: -0.4px;
}

/* Icon Buttons in Preview Nav */
.ios-icon-btn {
    background: none;
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.15s;
}

.ios-icon-btn:hover {
    background: rgba(118, 118, 128, 0.12);
}

.ios-icon-btn-red {
    color: #FF3B30;
}

.ios-icon-btn-green {
    color: #34C759;
}

/* ═══════════════════════════════════════════════
   STYLE OVERRIDES FOR PRIMEVUE INSIDE iOS CARD
   ═══════════════════════════════════════════════ */

:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    text-align: left;
    padding: 0;
    width: 100%;
    max-width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #8E8E93 !important;
    text-align: left;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

:deep(.ios-datepicker .p-datepicker) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

:deep(.ios-datepicker .p-inputtext) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: right;
    color: #8E8E93;
    font-size: 13px;
    padding: 4px 2px 4px 8px;
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
}
</style>

<!-- Unscoped: targets teleported Dialog elements at body level -->
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
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
</style>
