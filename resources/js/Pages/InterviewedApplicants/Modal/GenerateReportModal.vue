<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal :closable="true"
        :style="{ width: '900px' }" header="Generate Interview Report">

        <form @submit.prevent="generateReport" class="px-4 pb-2">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <!-- LEFT COLUMN: Filters -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Filters</h4>

                    <!-- Recommendation -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Recommendation</label>
                        <Select v-model="selectedRecommendation" :options="recommendationOptions" optionLabel="label"
                            optionValue="value" placeholder="All Recommendations" class="w-full" showClear />
                    </div>

                    <!-- Program -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Program</label>
                        <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All Programs"
                            class="w-full" />
                    </div>

                    <!-- School -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">School</label>
                        <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All Schools"
                            class="w-full" :multiple="false" />
                    </div>

                    <!-- Course -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Course</label>
                        <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id"
                            label="shortname" custom-placeholder="All Courses" class="w-full" />
                    </div>

                    <!-- Date Range -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Interview Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <DatePicker v-model="dateFrom" placeholder="From" showButtonBar class="w-full"
                                dateFormat="M dd, yy" size="small" showIcon iconDisplay="input" />
                            <DatePicker v-model="dateTo" placeholder="To" showButtonBar class="w-full"
                                dateFormat="M dd, yy" size="small" showIcon iconDisplay="input" />
                        </div>
                        <small v-if="dateTo && dateFrom && isDateToInvalid" class="text-red-500 text-xs mt-1">
                            Date To must be after Date From
                        </small>
                    </div>

                    <!-- Clear Filters Button -->
                    <div v-if="activeFiltersCount > 0"
                        class="flex items-center justify-between text-sm p-2 bg-blue-50 rounded">
                        <span class="text-gray-600">{{ activeFiltersCount }} filter(s) applied</span>
                        <Button label="Clear All" size="small" text severity="danger" @click="clearAllFilters"
                            icon="pi pi-times" />
                    </div>
                </div>

                <!-- RIGHT COLUMN: Report Options -->
                <div>
                    <h4 class="text-base font-semibold text-gray-700 mb-3 pb-2 border-b">Report Options</h4>

                    <!-- Report Type -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Report Type</label>
                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="ia_list" value="list" />
                                <label for="ia_list" class="ml-2 text-sm">Detailed List</label>
                            </div>
                            <div class="flex items-center">
                                <RadioButton v-model="reportType" inputId="ia_summary" value="summary" />
                                <label for="ia_summary" class="ml-2 text-sm">Summary</label>
                            </div>
                        </div>
                    </div>

                    <!-- Group By Option -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Group By</label>
                        <Select v-model="groupBy" :options="groupByOptions" optionLabel="label" optionValue="value"
                            placeholder="Select grouping" class="w-full" />
                        <small class="text-xs text-gray-500 mt-1">How records should be organized in the report</small>
                    </div>

                    <!-- Paper Size -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Paper Size</label>
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            class="w-full" />
                    </div>

                    <!-- Orientation -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Orientation</label>
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label"
                            optionValue="value" class="w-full" />
                    </div>

                    <!-- Include Assessment Details -->
                    <div class="mb-4 py-3 px-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Include Assessment Details</label>
                            <ToggleSwitch v-model="includeAssessment" />
                        </div>
                        <p class="text-xs text-gray-500">Include academic potential, financial need, and communication
                            skills columns</p>
                    </div>

                    <!-- Preview Summary -->
                    <div class="p-3 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs uppercase tracking-wide text-gray-600 mb-2 font-medium">Report Preview</p>
                        <div class="text-xs text-gray-700 space-y-1">
                            <div><strong>Records:</strong> {{ previewCount }}</div>
                            <div><strong>Type:</strong> {{ reportType === 'list' ? 'Detailed List' : 'Summary' }}</div>
                            <div><strong>Group By:</strong> {{groupByOptions.find(o => o.value === groupBy)?.label}}
                            </div>
                            <div><strong>Paper:</strong> {{ paperSize }} / {{ orientation }}</div>
                        </div>
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
    <Dialog v-if="showPreview" :visible="showPreview" modal :closable="true" :maximizable="true"
        :style="{ width: '95vw', height: '90vh' }" @update:visible="val => showPreview = val"
        header="Interview Report Preview">

        <div class="h-full flex flex-col bg-white">
            <!-- Toolbar -->
            <div class="bg-white px-6 py-4 flex items-center justify-between border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <Button label="Back" icon="pi pi-arrow-left" @click="showPreview = false" severity="secondary" text
                        size="small" />
                </div>
                <div class="flex items-center gap-2">
                    <Button label="PDF" icon="pi pi-file-pdf" @click="downloadReport('pdf')" severity="danger" outlined
                        size="small" />
                    <Button label="Excel" icon="pi pi-file-excel" @click="downloadReport('excel')" severity="success"
                        outlined size="small" />
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-auto p-8 bg-gray-50">
                <div class="max-w-7xl mx-auto">
                    <!-- Report Header -->
                    <div class="mb-8 pb-6 border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-3xl font-light text-gray-900 mb-2">
                                    {{ reportType === 'list' ? 'Interviewed Applicants' : 'Interview Summary Report' }}
                                </h1>
                            </div>
                            <div class="text-right text-sm text-gray-500">
                                <p>{{ moment().format('MMM DD, YYYY') }}</p>
                                <p>{{ moment().format('hh:mm A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Summary -->
                    <div v-if="activeFiltersCount > 0" class="mb-6 p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-xs font-medium text-blue-800 mb-1">Applied Filters:</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-if="lastParams.recommendation"
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Recommendation:
                                {{ lastParams.recommendation }}</span>
                            <span v-if="lastParams.program"
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Program:
                                {{ lastParams.program }}</span>
                            <span v-if="lastParams.school"
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">School:
                                {{ lastParams.school }}</span>
                            <span v-if="lastParams.course"
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Course:
                                {{ lastParams.course }}</span>
                            <span v-if="lastParams.date_from || lastParams.date_to"
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                Date: {{ lastParams.date_from || '...' }} — {{ lastParams.date_to || '...' }}
                            </span>
                        </div>
                    </div>

                    <!-- Report Data Table -->
                    <div v-if="reportData.length === 0" class="text-center py-12 text-gray-500">
                        <i class="pi pi-info-circle text-4xl mb-3 block"></i>
                        <p>No records match the selected filters</p>
                    </div>

                    <!-- Detailed List View -->
                    <template v-else-if="reportType === 'list'">
                        <template v-if="groupBy !== 'none'">
                            <div v-for="(group, groupName) in groupedData" :key="groupName" class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">{{ groupName }}
                                    <span class="text-sm font-normal text-gray-500">({{ group.length }})</span>
                                </h3>
                                <table class="w-full text-sm border-collapse border border-gray-200">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-200 p-2 text-left">#</th>
                                            <th class="border border-gray-200 p-2 text-left">Name</th>
                                            <th class="border border-gray-200 p-2 text-left">Program</th>
                                            <th class="border border-gray-200 p-2 text-left">Course</th>
                                            <th class="border border-gray-200 p-2 text-left">Recommendation</th>
                                            <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                                Academic</th>
                                            <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                                Financial</th>
                                            <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                                Communication</th>
                                            <th class="border border-gray-200 p-2 text-left">Interviewed</th>
                                            <th class="border border-gray-200 p-2 text-left">Interviewer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(record, idx) in group" :key="record.id">
                                            <td class="border border-gray-200 p-2">{{ idx + 1 }}</td>
                                            <td class="border border-gray-200 p-2">{{ record.profile.last_name }},
                                                {{ record.profile.first_name }}</td>
                                            <td class="border border-gray-200 p-2">
                                                {{ record.program?.shortname || 'N/A' }}</td>
                                            <td class="border border-gray-200 p-2">
                                                {{ record.course?.shortname || 'N/A' }}</td>
                                            <td class="border border-gray-200 p-2">
                                                {{ formatRecommendation(record.recommendation) }}</td>
                                            <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                                {{ capitalize(record.academic_potential) }}</td>
                                            <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                                {{ capitalize(record.financial_need_level) }}</td>
                                            <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                                {{ capitalize(record.communication_skills) }}</td>
                                            <td class="border border-gray-200 p-2">
                                                {{ formatDate(record.interviewed_at) }}</td>
                                            <td class="border border-gray-200 p-2">
                                                {{ record.interviewer?.name || 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template v-else>
                            <table class="w-full text-sm border-collapse border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-200 p-2 text-left">#</th>
                                        <th class="border border-gray-200 p-2 text-left">Name</th>
                                        <th class="border border-gray-200 p-2 text-left">Program</th>
                                        <th class="border border-gray-200 p-2 text-left">Course</th>
                                        <th class="border border-gray-200 p-2 text-left">Recommendation</th>
                                        <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                            Academic</th>
                                        <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                            Financial</th>
                                        <th v-if="includeAssessment" class="border border-gray-200 p-2 text-left">
                                            Communication</th>
                                        <th class="border border-gray-200 p-2 text-left">Interviewed</th>
                                        <th class="border border-gray-200 p-2 text-left">Interviewer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(record, idx) in reportData" :key="record.id">
                                        <td class="border border-gray-200 p-2">{{ idx + 1 }}</td>
                                        <td class="border border-gray-200 p-2">{{ record.profile.last_name }},
                                            {{ record.profile.first_name }}</td>
                                        <td class="border border-gray-200 p-2">
                                            {{ record.program?.shortname || 'N/A' }}</td>
                                        <td class="border border-gray-200 p-2">
                                            {{ record.course?.shortname || 'N/A' }}</td>
                                        <td class="border border-gray-200 p-2">
                                            {{ formatRecommendation(record.recommendation) }}</td>
                                        <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                            {{ capitalize(record.academic_potential) }}</td>
                                        <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                            {{ capitalize(record.financial_need_level) }}</td>
                                        <td v-if="includeAssessment" class="border border-gray-200 p-2">
                                            {{ capitalize(record.communication_skills) }}</td>
                                        <td class="border border-gray-200 p-2">
                                            {{ formatDate(record.interviewed_at) }}</td>
                                        <td class="border border-gray-200 p-2">
                                            {{ record.interviewer?.name || 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                    </template>

                    <!-- Summary View -->
                    <template v-else>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="bg-white p-6 rounded-lg border">
                                <h3 class="text-lg font-semibold mb-4">By Recommendation</h3>
                                <div class="space-y-3">
                                    <div v-for="(count, rec) in summaryByRecommendation" :key="rec"
                                        class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span class="text-sm">{{ formatRecommendation(rec) }}</span>
                                        <span class="font-bold">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-6 rounded-lg border">
                                <h3 class="text-lg font-semibold mb-4">By Program</h3>
                                <div class="space-y-3">
                                    <div v-for="(count, prog) in summaryByProgram" :key="prog"
                                        class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span class="text-sm">{{ prog }}</span>
                                        <span class="font-bold">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 p-4 bg-white rounded-lg border">
                            <h3 class="text-lg font-semibold mb-2">Total Records: {{ reportData.length }}</h3>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
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
    { label: 'Legal/Long', value: 'Legal' },
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

const previewCount = computed(() => {
    return filterApplicants().length;
});

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
        const key = r.recommendation || 'unknown';
        map[key] = (map[key] || 0) + 1;
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

    if (selectedRecommendation.value) {
        list = list.filter(r => r.recommendation === selectedRecommendation.value);
    }
    if (selectedProgram.value) {
        list = list.filter(r => r.program?.id === selectedProgram.value.id);
    }
    if (selectedSchool.value) {
        list = list.filter(r => r.school?.id === selectedSchool.value.id);
    }
    if (selectedCourse.value) {
        list = list.filter(r => r.course?.id === selectedCourse.value.id);
    }
    if (dateFrom.value) {
        list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrAfter(moment(dateFrom.value), 'day'));
    }
    if (dateTo.value) {
        list = list.filter(r => r.interviewed_at && moment(r.interviewed_at).isSameOrBefore(moment(dateTo.value), 'day'));
    }

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
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
}

function capitalize(str) {
    if (!str) return 'N/A';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
