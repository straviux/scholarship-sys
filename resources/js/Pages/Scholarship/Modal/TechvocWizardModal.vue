<template>
    <IosModal :visible="show" width="520px" max-width="92vw" body-style="padding: 0;"
        @update:visible="handleClose">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleClose">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">TechVoc — Approval List</span>
        </template>

        <template #header-right>
            <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="generating">
                <AppIcon v-if="generating" name="spinner" :size="16" class="animate-spin" />
                <template v-else>Generate</template>
            </button>
        </template>

        <div class="rw-body">
            <div class="rw-field-group">
                <label class="rw-label">Report Title</label>
                <InputText v-model="customTitle" class="w-full" placeholder="Custom title (optional)" size="small" />
            </div>
            <div class="rw-field-group">
                <label class="rw-label">Status</label>
                <Select v-model="selectedStatus" :options="statusOptions" optionLabel="label" optionValue="value"
                    placeholder="Active" class="w-full" />
            </div>
            <div class="rw-field-group">
                <label class="rw-label">School</label>
                <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All Schools"
                    class="w-full" :multiple="false" />
            </div>
            <div class="rw-field-group">
                <label class="rw-label">Course</label>
                <CourseSelect v-model="selectedCourses" label="name" custom-placeholder="All Courses"
                    class="w-full" :load-all-when-no-program="true" />
            </div>
            <div class="rw-field-group">
                <label class="rw-label">Start Date (from)</label>
                <DatePicker v-model="dateFrom" placeholder="Start date" showButtonBar dateFormat="M dd, yy"
                    class="w-full" showIcon iconDisplay="input" />
            </div>
            <div class="rw-field-group">
                <label class="rw-label">End Date (to)</label>
                <DatePicker v-model="dateTo" placeholder="End date" showButtonBar dateFormat="M dd, yy"
                    class="w-full" showIcon iconDisplay="input" />
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref } from 'vue';
import moment from 'moment';
import axios from 'axios';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    program: { type: Object, default: null },
});

const emit = defineEmits(['update:show', 'report-generated']);

const generating = ref(false);
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedSchools = ref(null);
const selectedCourses = ref(null);
const customTitle = ref('');
const selectedStatus = ref('active');

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'All Statuses', value: null },
    { label: 'Pending', value: 'pending' },
    { label: 'Interviewed', value: 'interviewed' },
    { label: 'Approved', value: 'approved_history' },
    { label: 'Completed', value: 'completed' },
];

function handleClose() {
    emit('update:show', false);
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        const params = {};
        if (props.program) params.program = props.program.id;
        if (selectedSchools.value) {
            params.school = selectedSchools.value.shortname;
        }
        if (selectedCourses.value) {
            params.courses = selectedCourses.value.name;
        }
        if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
        if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');
        if (selectedStatus.value) params.unified_status = selectedStatus.value;

        const response = await axios.get(route('profile.generateReport'), { params });
        let records = [];
        if (Array.isArray(response.data)) records = response.data;
        else if (Array.isArray(response.data?.data)) records = response.data.data;

        emit('report-generated', { records, program: props.program, title: customTitle.value?.trim(), status: selectedStatus.value });
        handleClose();
    } catch (error) {
        console.error('TechVoc report failed:', error);
    } finally {
        generating.value = false;
    }
}
</script>

<style scoped>
.rw-body { padding: 12px 16px; display: flex; flex-direction: column; gap: 12px; }
.rw-field-group { display: flex; flex-direction: column; gap: 4px; }
.rw-label { font-size: 12px; font-weight: 600; color: #555; }
</style>
