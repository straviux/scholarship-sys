<template>
    <IosModal :visible="show" width="640px" max-width="92vw" body-style="padding: 0;"
        @update:visible="emit('update:show', $event)">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="close">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">Graduate List Report</span>
        </template>

        <template #header-right>
            <button class="ios-nav-btn ios-nav-cancel" @click="close">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <div class="p-4 max-h-[75vh] overflow-y-auto">
            <!-- ── REPORT TITLE ── -->
            <div class="mb-4">
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title <span class="text-gray-400 font-normal">(optional)</span></label>
                <InputText v-model="customReportTitle" placeholder="Leave blank for default title"
                    class="w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
            </div>

            <!-- ── FILTERS ── -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Program</label>
                    <ProgramSelect v-model="selectedPrograms" label="shortname"
                        custom-placeholder="All Programs" :multiple="true"
                        class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">School</label>
                    <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All Schools"
                        :multiple="true"
                        class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Course</label>
                    <CourseSelect v-model="selectedCourses" label="shortname"
                        custom-placeholder="All Courses" :multiple="true" :load-all-when-no-program="true"
                        class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Year Graduated</label>
                    <Select v-model="selectedYearGraduated" :options="yearGraduatedOptions"
                        optionLabel="label" optionValue="value" placeholder="All Years" showClear
                        class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                </div>
            </div>

            <!-- ── LAYOUT OPTIONS ── -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Paper &amp; Orientation</label>
                    <div class="flex gap-1.5">
                        <Select v-model="paperSize" :options="paperSizeOptions"
                            optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <Select v-model="orientation" :options="orientationOptions"
                            optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Remarks Options</label>
                    <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800">
                        <span>Show Remarks Column</span>
                        <ToggleSwitch v-model="showRemarks" />
                    </label>
                    <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                        <span>Blank Remarks</span>
                        <ToggleSwitch v-model="blankRemarks" :disabled="!showRemarks" />
                    </label>
                </div>
            </div>

            <!-- ── SIGNATORY ── -->
            <div class="mb-4">
                <div class="flex gap-2" style="align-items: flex-start;">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Prepared By</label>
                        <InputText v-model="preparedBy" placeholder="Name (optional)"
                            class="w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        <InputText v-model="preparedByPosition" placeholder="Position (optional)"
                            class="mt-1.5 w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Approved By</label>
                        <InputText v-model="approvedBy" placeholder="Name (optional)"
                            class="w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        <InputText v-model="approvedByPosition" placeholder="Position (optional)"
                            class="mt-1.5 w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                    </div>
                </div>
                <span class="block text-[10px] text-gray-400 mt-1.5">Leave all signatory fields blank to hide the signature block.</span>
            </div>

            <!-- ── ACTIONS ── -->
            <div class="flex items-center justify-end gap-2 pt-3 mt-2 border-t border-gray-100 dark:border-gray-700">
                <button class="inline-flex items-center gap-1 text-sm font-semibold text-white bg-green-500 px-5 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="generateReport" :disabled="generating">
                    <AppIcon v-if="generating" name="spinner" :size="14" class="animate-spin" />
                    <template v-else>Generate</template>
                </button>
            </div>
        </div>
    </IosModal>

    <!-- Preview Modal -->
    <IosModal :visible="showPreview" width="95vw" max-width="95vw"
        :modal-content-style="{ height: '90vh' }"
        body-style="padding: 0; flex: 1; display: flex; direction: column; overflow: hidden;"
        @update:visible="showPreview = $event">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="showPreview = false">
                <AppIcon name="chevron-left" :size="13" /> Back
            </button>
        </template>
        <template #title>
            <span class="ios-nav-title">Graduate List Preview</span>
        </template>
        <template #header-right>
            <div class="ios-nav-actions flex items-center gap-2">
                <div class="flex items-center gap-1.5 mr-2">
                    <button @click="zoomLevel = Math.max(40, zoomLevel - 10)"
                        class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                        :disabled="zoomLevel <= 40">
                        <AppIcon name="minus" :size="10" />
                    </button>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 w-10 text-center">{{ zoomLevel }}%</span>
                    <button @click="zoomLevel = Math.min(200, zoomLevel + 10)"
                        class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                        :disabled="zoomLevel >= 200">
                        <AppIcon name="plus" :size="10" />
                    </button>
                </div>
                <button class="ios-icon-btn" @click="doExportExcel" title="Export to Excel">
                    <AppIcon name="file-spreadsheet" :size="16" style="color: #34C759;" />
                </button>
                <button class="ios-icon-btn" @click="doPrint" title="Print / Save as PDF">
                    <AppIcon name="printer" :size="16" style="color: #007AFF;" />
                </button>
            </div>
        </template>
        <div class="overflow-auto bg-[#d1d1d6] dark:bg-[#1c1c1e]" style="flex: 1; min-height: 0; padding: 16px 0;">
            <div style="display: flex; justify-content: center;">
                <iframe v-if="previewHtml" :srcdoc="previewHtml"
                    :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', border: 'none', background: '#fff', boxShadow: '0 2px 20px rgba(0,0,0,0.15)', width: iframeWidth + 'px', height: iframeHeight + 'px' }"
                    frameborder="0"></iframe>
                <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <AppIcon name="spinner" class="w-8 h-8 mb-3" />
                    <p>Generating report...</p>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import moment from 'moment';
import * as XLSX from 'xlsx';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { renderVueTemplate } from '@/composables/usePdfPrint';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';

import GraduateListReportTemplate from '@/Pages/Scholarship/Reports/GraduateListReportTemplate.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show']);

// ─── State ───
const generating = ref(false);
const showPreview = ref(false);
const previewHtml = ref('');
const reportRecords = ref([]);
const zoomLevel = ref(100);

// Filters
const selectedPrograms = ref([]);
const selectedSchools = ref([]);
const selectedCourses = ref([]);
const selectedAcademicYear = ref(null);
const selectedYearGraduated = ref(null);
const customReportTitle = ref('');

// Layout
const paperSize = ref('A4');
const orientation = ref('landscape');
const showRemarks = ref(true);
const blankRemarks = ref(false);

// Signatories
const preparedBy = ref('');
const preparedByPosition = ref('');
const approvedBy = ref('');
const approvedByPosition = ref('');

// Year graduated options (generate from 2000 to current year)
const currentYear = new Date().getFullYear();
const yearGraduatedOptions = computed(() => {
    const options = [];
    for (let y = currentYear; y >= 2000; y--) {
        options.push({ label: String(y), value: y });
    }
    return options;
});

// Paper size options
const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const paperConfig = computed(() => getReportPaperConfig(paperSize.value, orientation.value));
const iframeWidth = computed(() => paperConfig.value.widthPx);
const iframeHeight = computed(() => paperConfig.value.heightPx);

function close() {
    showPreview.value = false;
    previewHtml.value = '';
    reportRecords.value = [];
    emit('update:show', false);
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        const params = {};
        if (selectedPrograms.value?.length > 0) {
            params.program = selectedPrograms.value.map(p => p.id).join(',');
        }
        if (selectedSchools.value?.length > 0) {
            params.school = selectedSchools.value.map(s => s.shortname).join(',');
        }
        if (selectedCourses.value?.length > 0) {
            params.course = selectedCourses.value.map(c => c.name).join(',');
        }
        if (selectedYearGraduated.value) {
            params.year_graduated = selectedYearGraduated.value;
        }

        const response = await axios.get(route('profile.graduateListReport'), { params });
        const records = response.data?.data || [];

        reportRecords.value = records;

        // Build filter summary
        const filters = {};
        if (selectedPrograms.value?.length)
            filters.Program = selectedPrograms.value.map(p => p.shortname || p.name).join(', ');
        if (selectedSchools.value?.length)
            filters.School = selectedSchools.value.map(s => s.shortname || s.name).join(', ');
        if (selectedCourses.value?.length)
            filters.Course = selectedCourses.value.map(c => c.shortname || c.name).join(', ');
        if (selectedYearGraduated.value)
            filters['Year Graduated'] = selectedYearGraduated.value;

        const templateProps = {
            records,
            filters,
            reportTitle: customReportTitle.value?.trim() || '',
            showRemarks: showRemarks.value,
            blankRemarks: blankRemarks.value,
            generatedAt: moment().format('MMMM DD, YYYY — h:mm A'),
            preparedBy: preparedBy.value?.trim() || '',
            preparedByPosition: preparedByPosition.value?.trim() || '',
            approvedBy: approvedBy.value?.trim() || '',
            approvedByPosition: approvedByPosition.value?.trim() || '',
        };

        const bodyHtml = renderVueTemplate(GraduateListReportTemplate, templateProps);
        const fullHtml = `<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Graduate List</title><style>body{margin:0;padding:0;}${getReportCss(paperConfig.value)}</style></head><body>${bodyHtml}</body></html>`;

        previewHtml.value = fullHtml;
        showPreview.value = true;
    } catch (error) {
        console.error('Failed to generate graduate list report:', error);
    } finally {
        generating.value = false;
    }
}

function doPrint() {
    if (!previewHtml.value) return;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(previewHtml.value);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 300);
}

function doExportExcel() {
    const records = reportRecords.value;
    if (!records || records.length === 0) return;

    const upper = (v) => String(v ?? '').toUpperCase();

    const headers = ['#', 'NAME'];
    if (showRemarks.value) headers.push('REMARKS');
    headers.push('SCHOOL', 'COURSE', 'YEAR GRADUATED');

    const rows = records.map((rec, idx) => {
        const row = [
            idx + 1,
            upper(rec.name || ''),
        ];
        if (showRemarks.value) row.push(upper(blankRemarks.value ? '' : (rec.remarks || '')));
        row.push(
            upper(rec.school || ''),
            upper(rec.course || ''),
            rec.year_graduated || '',
        );
        return row;
    });

    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Graduate List');

    const title = (customReportTitle.value?.trim() || 'Graduate_List').replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_');
    const filename = `${title}_${moment().format('YYYY-MM-DD')}.xlsx`;
    XLSX.writeFile(wb, filename);
}
</script>
