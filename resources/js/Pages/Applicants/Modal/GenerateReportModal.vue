<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal header="Generate Report"
        :closable="true" :style="{ width: '400px' }">
        <form @submit.prevent="generateReport">
            <div class="flex gap-2">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Date Filed</label>
                    <DatePicker v-model="dateFrom" placeholder="From" showButtonBar />
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">&nbsp;</label>
                    <DatePicker v-model="dateTo" placeholder="To" showButtonBar />
                    <div v-if="dateTo && dateFrom && isDateToInvalid" class="text-red-500 text-xs mt-1">
                        Date To must be equal to or after Date From.
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Program</label>
                <ProgramSelect v-model="selectedProgram" label="shortname" custom-placeholder="All Programs" />
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">School</label>
                <SchoolSelect v-model="selectedSchool" label="shortname" custom-placeholder="All Schools" />
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Course</label>
                <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id" label="shortname"
                    custom-placeholder="All Courses" />
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Municipality</label>
                <MunicipalitySelect v-model="selectedMunicipality" custom-placeholder="All Municipalities" />
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Report Type</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" value="list" v-model="reportType" class="mr-1" />
                        List of Results
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" value="summary" v-model="reportType" class="mr-1" />
                        Summary
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <Button type="button" label="Cancel" @click="close" />
                <Button type="submit" label="Generate" severity="success" :disabled="isDateToInvalid" />
            </div>
        </form>
    </Dialog>
    <Dialog v-if="showReportModal" :visible="showReportModal" modal header="Report Results" :closable="true"
        :style="{ width: '90vw', minHeight: '80vh' }" @update:visible="val => showReportModal = val">
        <component :is="ReportView" v-if="ReportView" :params="lastParams" @close="showReportModal = false" />
    </Dialog>
</template>

<script setup>
import { ref, watch, computed, shallowRef, markRaw } from 'vue';
import { defineAsyncComponent } from 'vue';
const showReportModal = ref(false);
const lastParams = ref({});
const ReportView = shallowRef(null);
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';

const props = defineProps({
    show: Boolean,
});
const emit = defineEmits(['update:show']);

const dateFrom = ref(null);
const dateTo = ref(null);
const selectedProgram = ref(null);
const selectedSchool = ref(null);
const selectedCourse = ref(null);
const selectedMunicipality = ref(null);
const reportType = ref('list');

function close() {
    emit('update:show', false);
}

import axios from 'axios';

function generateReport() {
    let date_from = dateFrom.value ? moment(dateFrom.value).format('YYYY-MM-DD') : '';
    let date_to = dateTo.value ? moment(dateTo.value).format('YYYY-MM-DD') : '';
    // Real-time validation disables the button, but keep this as a safeguard
    if (date_from && date_to && moment(date_to).isBefore(date_from)) {
        return;
    }
    const params = {
        date_from,
        date_to,
        program: selectedProgram.value?.id || '',
        school: selectedSchool.value?.shortname || '',
        course: selectedCourse.value?.shortname || '',
        municipality: selectedMunicipality.value?.name || '',
        report_type: reportType.value,
    };
    // Instead of fetching here, show the report modal and pass params
    lastParams.value = { ...params };
    if (!ReportView.value) {
        ReportView.value = markRaw(defineAsyncComponent(() => import('./ReportView.vue')));
    }
    showReportModal.value = true;
}
</script>

<style scoped>
.mb-4 {
    margin-bottom: 1rem;
}
</style>
