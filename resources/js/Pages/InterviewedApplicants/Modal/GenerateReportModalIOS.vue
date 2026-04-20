<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- iOS Navigation Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close" v-tooltip.bottom="'Close'">
                        <AppIcon name="x" :size="16" />
                    </button>
                    <span class="ios-nav-title">Generate Report</span>
                    <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="isDateToInvalid"
                        v-tooltip.bottom="'Generate Report'">
                        <AppIcon name="check" :size="16" />
                    </button>
                </div>

                <div class="ios-body">
                    <!-- REPORT TYPE — iOS Segmented Control -->
                    <div class="ios-section">
                        <div class="ios-section-label">Report Type</div>
                        <div class="ios-segmented-control">
                            <button :class="['ios-segment', reportType === 'list' && 'ios-segment-active']"
                                @click="reportType = 'list'">
                                <AppIcon name="list" :size="13" />
                                Detailed List
                            </button>
                            <button :class="['ios-segment', reportType === 'summary' && 'ios-segment-active']"
                                @click="reportType = 'summary'">
                                <AppIcon name="bar-chart-3" :size="13" />
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
                                    <AppIcon name="star-fill" :size="13" style="color: #FF9500;" />
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
                                    <AppIcon name="bookmark-fill" :size="13" style="color: #007AFF;" />
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
                                    <AppIcon name="building-2" :size="13" style="color: #34C759;" />
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
                                    <AppIcon name="graduation-cap" :size="13" style="color: #AF52DE;" />
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
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    From
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="dateFrom" placeholder="Any" showButtonBar dateFormat="M dd, yy"
                                        class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
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
                                    <AppIcon name="objects-column" :size="13" style="color: #5856D6;" />
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
                                    <AppIcon name="file" :size="13" style="color: #8E8E93;" />
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
                                    <AppIcon name="desktop" :size="13" style="color: #FF9500;" />
                                    Orientation
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                                        optionValue="value" class="ios-select" />
                                </div>
                            </div>

                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="panel-right-open" :size="13" style="color: #34C759;" />
                                    Layout
                                </div>
                                <div class="text-xs text-gray-500">
                                    Use landscape so all report columns stay on one row.
                                </div>
                            </div>
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

    <PdfPreviewModal v-model:show="showPdfPreview" :htmlDoc="pdfPreviewHtml" :title="pdfPreviewTitle"
        :paperSize="pdfPaperSize" />
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import AppIcon from '@/Components/ui/AppIcon.vue';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';
import InterviewedApplicantsTemplate from '../Pdf/InterviewedApplicantsTemplate.vue';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';

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

// PDF Preview
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref('');
const pdfPreviewTitle = ref('');
const pdfPaperSize = ref('a4');
const lastParams = ref({});

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
    const filtered = filterApplicants();
    const fl = {
        recommendation: selectedRecommendation.value ? recommendationOptions.find(o => o.value === selectedRecommendation.value)?.label : '',
        program: selectedProgram.value?.shortname || '',
        school: selectedSchool.value?.shortname || '',
        course: selectedCourse.value?.name || selectedCourse.value?.shortname || '',
        date_from: dateFrom.value ? moment(dateFrom.value).format('MMM DD, YYYY') : '',
        date_to: dateTo.value ? moment(dateTo.value).format('MMM DD, YYYY') : '',
    };
    lastParams.value = fl;

    const previewPaperSizeMap = {
        A4: {
            portrait: 'a4',
            landscape: 'a4-landscape',
        },
        Letter: {
            portrait: 'letter',
            landscape: 'letter-landscape',
        },
        Legal: {
            portrait: 'long',
            landscape: 'landscape',
        },
    };
    const ps = previewPaperSizeMap[paperSize.value]?.[orientation.value] || 'a4-landscape';
    pdfPaperSize.value = ps;

    const authUser = usePage().props.auth?.user;
    const bodyHtml = renderVueTemplate(InterviewedApplicantsTemplate, {
        records: filtered,
        reportType: reportType.value,
        groupBy: groupBy.value,
        filterLabels: fl,
        today: moment().format('MMMM D, YYYY'),
        preparedBy: authUser?.name ?? '',
    });

    const { buildHtmlDoc } = usePdfPrint();
    pdfPreviewTitle.value = reportType.value === 'summary' ? 'Interview-Summary-Report' : 'Interviewed-Applicants-Report';
    pdfPreviewHtml.value = buildHtmlDoc(bodyHtml, pdfPreviewTitle.value, ps);
    showPdfPreview.value = true;
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

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   PREVIEW & REPORT STYLES (Component-Specific)
   iOS Design System classes are in resources/css/ios-design-system.css
   ═══════════════════════════════════════════════ */

.ios-preview-body {
    background: #F2F2F7;
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
    padding: 4px 40px 4px 8px;
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
}
</style>
