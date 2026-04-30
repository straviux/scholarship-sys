<template>

    <Head title="Return of Service Batches" />

    <AdminLayout>

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon name="graduation-cap" class="text-blue-600 text-[2rem] short:text-[1.5rem]" />
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Return of Service</h1>
                            <p class="text-sm text-gray-600">Manage ROS batches and track scholar service obligations
                            </p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <AppButton v-if="hasPermission('return-of-service.export')" icon="file-text" label="Report"
                            severity="secondary" outlined rounded size="small" @click="openReportDialog()" />
                        <AppButton v-if="hasPermission('return-of-service.create')" icon="plus" outlined
                            severity="success" rounded size="large" @click="openNewBatchDialog" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-4 short:mb-2 !rounded-4xl overflow-hidden">
                <div class="flex items-end gap-3 -mt-6 flex-wrap">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Batch Name</label>
                        <IconField iconPosition="left">
                            <InputIcon>
                                <AppIcon name="search" :size="14" class="text-gray-400" />
                            </InputIcon>
                            <InputText v-model="batchSearch" placeholder="Search batch name..." size="small" />
                        </IconField>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Created By</label>
                        <InputText v-model="batchCreatedByFilter" placeholder="Filter by creator..." size="small" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Year</label>
                        <InputNumber v-model="batchYearFilter" placeholder="e.g., 2025" :useGrouping="false"
                            size="small" :inputStyle="{ width: '120px' }" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Description</label>
                        <InputText v-model="batchDescriptionFilter" placeholder="Search description..." size="small" />
                    </div>
                    <AppButton severity="secondary" outlined rounded size="small" icon="history"
                        @click="clearBatchFilters" v-tooltip.bottom="`Clear Filters`" />
                </div>
            </Panel>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 short:mb-2">
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-blue-600">{{ batches.length }}</div>
                    <div class="text-xs text-gray-500">Total Batches</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-indigo-600">{{ filteredBatches.length }}</div>
                    <div class="text-xs text-gray-500">Filtered Results</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-green-600">{{batches.reduce((s, b) => s +
                        (b.total_scholars ||
                            0), 0)}}
                    </div>
                    <div class="text-xs text-gray-500">Total Scholars</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-gray-400">{{batches.filter(b =>
                        b.result_date).length}}</div>
                    <div class="text-xs text-gray-500">With Results</div>
                </div>
            </div>

            <!-- Batch Cards -->
            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <div class="text-sm text-gray-500 mb-4 -mt-2">{{ filteredBatches.length }} batch(es)</div>

                <!-- Batch Cards Grid -->
                <div class="grid grid-cols-1 gap-4">
                    <div v-for="batch in filteredBatches" :key="batch.id"
                        class="bg-white !rounded-4xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="p-4 short:p-3">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <AppIcon name="folder" class="text-blue-600 text-sm" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 text-base leading-tight">{{
                                            batch.batch_name
                                        }}</h4>
                                        <div class="text-xs text-gray-500 space-y-0.5 mt-1">
                                            <p v-if="batch.exam_date_from || batch.exam_date_to">
                                                <span class="font-medium">Exam:</span>
                                                {{ formatDateLong(batch.exam_date_from) }} –
                                                {{ formatDateLong(batch.exam_date_to) }}
                                            </p>
                                            <p v-if="batch.result_date">
                                                <span class="font-medium">Result:</span>
                                                {{ formatDateLong(batch.result_date) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold">
                                        <AppIcon name="users" :size="12" />
                                        {{ batch.total_scholars }}
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1.5">by {{ batch.created_by }}</p>
                                </div>
                            </div>

                            <div v-if="batch.description" class="bg-gray-50 rounded-2xl px-4 py-2.5 mb-4">
                                <p class="text-sm text-gray-600">{{ batch.description }}</p>
                            </div>

                            <div class="flex gap-1.5 flex-wrap border-t border-gray-100 pt-3">
                                <AppButton icon="eye" label="View Batch" severity="secondary" text size="small" rounded
                                    @click="openViewBatchDialog(batch)" />
                                <AppButton v-if="hasPermission('return-of-service.edit')" icon="pencil" label="Edit"
                                    severity="warning" text size="small" rounded @click="openEditBatchDialog(batch)" />
                                <AppButton v-if="hasPermission('return-of-service.create')" icon="plus"
                                    label="Add Scholar" severity="success" text size="small" rounded
                                    @click="openAddScholarDialog(batch)" />
                                <AppButton v-if="hasPermission('return-of-service.export')" icon="file-text"
                                    label="Report" severity="info" text size="small" rounded
                                    @click="openReportDialog(batch)" />
                                <AppButton v-if="hasPermission('return-of-service.delete')" icon="trash"
                                    severity="danger" text size="small" rounded @click="confirmDeleteBatch(batch)"
                                    v-tooltip.top="`Delete`" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="batches.length === 0" class="text-center py-16">
                    <div class="w-16 h-16 rounded-3xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <AppIcon name="inbox" class="text-2xl text-gray-400" />
                    </div>
                    <p class="text-gray-500 mb-4">No ROS batches created yet.</p>
                    <AppButton v-if="hasPermission('return-of-service.create')" icon="plus" label="Create First Batch"
                        severity="success" rounded @click="openNewBatchDialog" />
                </div>

                <!-- No Results State -->
                <div v-else-if="filteredBatches.length === 0" class="text-center py-16">
                    <div class="w-16 h-16 rounded-3xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <AppIcon name="search" class="text-2xl text-gray-400" />
                    </div>
                    <p class="text-gray-500 mb-4">No batches match your filters.</p>
                    <AppButton icon="x" label="Clear Filters" severity="secondary" outlined rounded
                        @click="clearBatchFilters" />
                </div>

            </Panel>
        </div>


        <ReturnOfServiceDialogs :show-batch-dialog="showBatchDialog" :show-scholar-dialog="showScholarDialog"
            :show-view-scholar-dialog="showViewScholarDialog" :show-view-batch-dialog="showViewBatchDialog"
            :show-delete-batch-dialog="showDeleteBatchDialog" :show-delete-scholar-dialog="showDeleteScholarDialog"
            :show-preview-scholar-dialog="showPreviewScholarDialog" :show-report-dialog="showReportDialog"
            :batch-mode="batchMode" :scholar-mode="scholarMode" :batch-form="batchForm" :scholar-form="scholarForm"
            :completion-options="props.completionOptions" :scholarship-records="scholarshipRecords"
            :is-end-date-invalid="isEndDateInvalid" :viewing-scholar="viewingScholar" :viewing-batch="viewingBatch"
            :filtered-scholars="filteredScholars" :scholar-search="scholarSearch" :batch-to-delete="batchToDelete"
            :scholar-to-delete="scholarToDelete" :can-create="hasPermission('return-of-service.create')"
            :can-edit="hasPermission('return-of-service.edit')" :can-delete="hasPermission('return-of-service.delete')"
            :can-export="hasPermission('return-of-service.export')" :report-batch-options="reportBatchOptions"
            :report-context-batch="reportContextBatch" :filtered-batch-count="filteredBatches.length"
            :total-batch-count="batches.length" :format-date-long="formatDateLong"
            :get-completion-severity="getCompletionSeverity" @close-batch="closeBatchDialog"
            @submit-batch="submitBatchForm" @close-scholar="closeScholarDialog" @submit-scholar="submitScholarForm"
            @close-view-scholar="closeViewScholarDialog" @close-view-batch="closeViewBatchDialog"
            @close-delete-batch="closeDeleteBatchDialog" @delete-batch="deleteBatch"
            @close-delete-scholar="closeDeleteScholarDialog" @delete-scholar="deleteScholar"
            @close-preview-scholar="showPreviewScholarDialog = false" @update:scholarSearch="scholarSearch = $event"
            @profile-filter="onProfileFilter" @open-add-scholar="openAddScholarDialog(viewingBatch)"
            @open-edit-scholar="openEditScholarDialog(viewingBatch, $event)" @view-scholar="viewScholar"
            @confirm-delete-scholar="confirmDeleteScholar" @open-report="openReportDialog($event)"
            @close-report="closeReportDialog" @generate-report="generateReport" />

        <!-- Toast Notifications with higher z-index than drawer -->
        <Toast position="top-right" :life="3500" :baseZIndex="20000" />
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed, reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import ReturnOfServiceDialogs from '@/Pages/ReturnOfService/Components/ReturnOfServiceDialogs.vue';
import ReturnOfServiceReportTemplate from '@/Pages/ReturnOfService/Pdf/ReturnOfServiceReportTemplate.vue';
import { returnOfServiceReportCss } from '@/Pages/ReturnOfService/Pdf/report-styles.js';
import Toolbar from 'primevue/toolbar';
import Panel from 'primevue/panel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import { usePermission } from '@/composable/permissions';
import { renderVueTemplate, usePdfPrint } from '@/composables/usePdfPrint';
import * as XLSX from 'xlsx';

const props = defineProps({
    batches: Array,
    courses: Array,
    completionOptions: Array,
});

const { hasPermission } = usePermission();
const toast = useToast();
const { printHtml } = usePdfPrint();

// Dialog states
const showBatchDialog = ref(false);
const showScholarDialog = ref(false);
const showViewScholarDialog = ref(false);
const showViewBatchDialog = ref(false);
const showDeleteBatchDialog = ref(false);
const showDeleteScholarDialog = ref(false);
const showPreviewScholarDialog = ref(false);
const showReportDialog = ref(false);

// Modes
const batchMode = ref('add');
const scholarMode = ref('add');

// Data
const batches = ref(props.batches || []);
const scholarshipRecords = ref([]);
const viewingScholar = ref(null);
const viewingBatch = ref(null);
const batchToDelete = ref(null);
const scholarToDelete = ref(null);
const currentBatch = ref(null);
const currentScholar = ref(null);
const reportContextBatch = ref(null);
const scholarSearch = ref('');
const batchSearch = ref('');
const batchCreatedByFilter = ref('');
const batchDescriptionFilter = ref('');
const batchYearFilter = ref(null);

// Watch for changes in props.batches and update local ref
watch(
    () => props.batches,
    (newBatches) => {
        batches.value = newBatches || [];
    }
);

// Forms
const batchForm = useForm({
    batch_name: '',
    description: '',
    exam_date_from: null,
    exam_date_to: null,
    result_date: null,
    course_id: null,
});

const scholarForm = reactive({
    batch_id: null,
    profile_id: null,
    selectedProfile: null,
    lastname: '',
    firstname: '',
    middlename: '',
    ext: '',
    years_of_service: null,
    service_start_date: null,
    service_end_date: null,
    completion_status: 'pending',
    remarks: '',
    record_label: '',
    errors: {},
    processing: false,
    reset() {
        this.batch_id = null;
        this.profile_id = null;
        this.selectedProfile = null;
        this.lastname = '';
        this.firstname = '';
        this.middlename = '';
        this.ext = '';
        this.years_of_service = null;
        this.service_start_date = null;
        this.service_end_date = null;
        this.completion_status = 'pending';
        this.remarks = '';
        this.record_label = '';
        this.errors = {};
    },
    clearErrors() {
        this.errors = {};
    },
});

// Computed property to validate end date is not before start date
const isEndDateInvalid = computed(() => {
    if (!scholarForm.service_start_date || !scholarForm.service_end_date) {
        return false;
    }

    let startDate = scholarForm.service_start_date;
    let endDate = scholarForm.service_end_date;

    if (typeof startDate === 'string') {
        startDate = new Date(startDate);
    }
    if (typeof endDate === 'string') {
        endDate = new Date(endDate);
    }

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        return false;
    }

    return endDate < startDate;
});
const computedYearsOfService = computed(() => {
    if (!scholarForm.service_start_date || !scholarForm.service_end_date) {
        return null;
    }

    let startDate = scholarForm.service_start_date;
    let endDate = scholarForm.service_end_date;

    // Convert strings to Date objects if needed
    if (typeof startDate === 'string') {
        startDate = new Date(startDate);
    }
    if (typeof endDate === 'string') {
        endDate = new Date(endDate);
    }

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        return null;
    }

    // Calculate difference in days and convert to years with decimal places
    const diffTime = endDate - startDate;
    const diffDays = diffTime / (1000 * 60 * 60 * 24);

    // Convert days to months (average 30.44 days per month), then to years
    const monthsDecimal = diffDays / 30.44;
    const yearsDecimal = monthsDecimal / 12;

    // Round to 1 decimal place (e.g., 3.5 for 3 years 6 months)
    const rounded = Math.round(yearsDecimal * 10) / 10;

    return rounded >= 0 ? rounded : null;
});

// Watch for date changes to auto-populate years of service
watch(
    () => [scholarForm.service_start_date, scholarForm.service_end_date],
    () => {
        // Auto-populate years_of_service if both dates are set and years_of_service is empty
        if (computedYearsOfService.value !== null && !scholarForm.years_of_service) {
            scholarForm.years_of_service = computedYearsOfService.value;
        }
    },
    { deep: true }
);

// Watch for profile selection
watch(() => scholarForm.selectedProfile, (selected) => {
    if (Array.isArray(selected) && selected.length > 0) {
        // MultiSelect returns an array, get the first (and only) selected item
        scholarForm.profile_id = selected[0].id;
        scholarForm.record_label = selected[0].label;
        // Populate name fields from profile
        scholarForm.lastname = selected[0].lastname || '';
        scholarForm.firstname = selected[0].firstname || '';
        scholarForm.middlename = selected[0].middlename || '';
        scholarForm.ext = selected[0].ext || '';
        // Open preview drawer
        showPreviewScholarDialog.value = true;
    } else if (selected && selected.id) {
        // Fallback for single object
        scholarForm.profile_id = selected.id;
        scholarForm.record_label = selected.label;
        // Populate name fields from profile
        scholarForm.lastname = selected.lastname || '';
        scholarForm.firstname = selected.firstname || '';
        scholarForm.middlename = selected.middlename || '';
        scholarForm.ext = selected.ext || '';
        // Open preview drawer
        showPreviewScholarDialog.value = true;
    } else {
        // Clear if nothing selected
        scholarForm.profile_id = null;
        scholarForm.record_label = '';
        scholarForm.lastname = '';
        scholarForm.firstname = '';
        scholarForm.middlename = '';
        scholarForm.ext = '';
        // Close preview drawer
        showPreviewScholarDialog.value = false;
    }
}, { deep: true });

// Batch management
const openViewBatchDialog = (batch) => {
    viewingBatch.value = batch;
    showViewBatchDialog.value = true;
};

const closeViewBatchDialog = () => {
    showViewBatchDialog.value = false;
    viewingBatch.value = null;
    scholarSearch.value = '';
};

const closeViewScholarDialog = () => {
    showViewScholarDialog.value = false;
    viewingScholar.value = null;
};

const openNewBatchDialog = () => {
    batchMode.value = 'add';
    batchForm.reset();
    batchForm.clearErrors();
    showBatchDialog.value = true;
};

const closeBatchDialog = () => {
    showBatchDialog.value = false;
    resetBatchForm();
};

const openEditBatchDialog = (batch) => {
    batchMode.value = 'edit';
    batchForm.reset();
    batchForm.clearErrors();
    batchForm.batch_name = batch.batch_name;
    batchForm.description = batch.description;
    batchForm.exam_date_from = batch.exam_date_from ? new Date(batch.exam_date_from) : null;
    batchForm.exam_date_to = batch.exam_date_to ? new Date(batch.exam_date_to) : null;
    batchForm.result_date = batch.result_date ? new Date(batch.result_date) : null;
    batchForm.course_id = batch.course_id ? { id: batch.course_id, name: batch.course_name } : null;
    batchForm._method = 'PUT';
    currentBatch.value = batch;
    showBatchDialog.value = true;
};

const submitBatchForm = async () => {
    const url = batchMode.value === 'add'
        ? route('return-of-service.batch.store')
        : route('return-of-service.batch.update', currentBatch.value.id);

    const formData = new FormData();
    formData.append('batch_name', batchForm.batch_name);
    if (batchForm.description) formData.append('description', batchForm.description);
    if (batchForm.exam_date_from) formData.append('exam_date_from', formatDate(batchForm.exam_date_from));
    if (batchForm.exam_date_to) formData.append('exam_date_to', formatDate(batchForm.exam_date_to));
    if (batchForm.result_date) formData.append('result_date', formatDate(batchForm.result_date));
    if (batchForm.course_id) {
        const courseId = typeof batchForm.course_id === 'object' ? batchForm.course_id.id : batchForm.course_id;
        formData.append('course_id', courseId);
    }

    if (batchMode.value === 'edit') {
        formData.append('_method', 'PUT');
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        const response = await axios.post(url, formData, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = response.data;

        showBatchDialog.value = false;
        batchForm.reset();
        batchForm.clearErrors();
        currentBatch.value = null;

        const actionMsg = batchMode.value === 'add' ? 'created' : 'updated';
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Batch ${actionMsg} successfully`,
            life: 3000
        });

        // Update local batches array directly
        if (batchMode.value === 'add') {
            // Add new batch to list
            const newBatch = {
                ...data.batch,
                scholars: [],
                total_scholars: 0
            };
            batches.value.push(newBatch);
        } else {
            // Update existing batch
            const batchIndex = batches.value.findIndex(b => b.id === data.batch.id);
            if (batchIndex !== -1) {
                batches.value[batchIndex] = {
                    ...batches.value[batchIndex],
                    ...data.batch
                };
            }
        }
    } catch (error) {
        console.error('Error submitting batch form:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save batch',
            life: 5000
        });
    }
};


const fetchBatches = async () => {
    // Function to reload batches if needed (not used currently)
    // Data updates are handled directly in submitBatchForm and deleteBatch
};

const resetBatchForm = () => {
    batchForm.reset();
    batchForm.clearErrors();
    currentBatch.value = null;
};

const clearBatchFilters = () => {
    batchSearch.value = '';
    batchCreatedByFilter.value = '';
    batchDescriptionFilter.value = '';
    batchYearFilter.value = null;
};

const confirmDeleteBatch = (batch) => {
    batchToDelete.value = batch;
    showDeleteBatchDialog.value = true;
};

const closeDeleteBatchDialog = () => {
    showDeleteBatchDialog.value = false;
};

const deleteBatch = async () => {
    try {
        const deletedBatchId = batchToDelete.value.id; // Store ID before clearing
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await axios.post(
            route('return-of-service.batch.destroy', deletedBatchId),
            { _method: 'DELETE' },
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }
        );

        const data = response.data;

        showDeleteBatchDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Batch deleted successfully',
            life: 3000
        });

        // Remove batch from local array
        const batchIndex = batches.value.findIndex(b => b.id === deletedBatchId);
        if (batchIndex !== -1) {
            batches.value.splice(batchIndex, 1);
        }

        batchToDelete.value = null;
    } catch (error) {
        console.error('Error:', error);
        const message = error.response?.data?.message || 'Failed to delete batch';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 5000
        });
    }
};

// Scholar management
const openAddScholarDialog = (batch) => {
    scholarMode.value = 'add';
    scholarForm.reset();
    scholarForm.clearErrors();
    scholarForm.batch_id = batch.id;
    scholarForm.completion_status = 'pending';
    currentBatch.value = batch;
    currentScholar.value = null;
    scholarshipRecords.value = [];
    showScholarDialog.value = true;
};

const closeScholarDialog = () => {
    showScholarDialog.value = false;
    resetScholarForm();
};

const openEditScholarDialog = (batch, scholar) => {
    scholarMode.value = 'edit';
    scholarForm.reset();
    scholarForm.clearErrors();
    scholarForm.batch_id = batch.id;
    scholarForm.profile_id = scholar.profile_id;
    scholarForm.years_of_service = scholar.years_of_service;
    scholarForm.service_start_date = scholar.service_start_date ? new Date(scholar.service_start_date) : null;
    scholarForm.service_end_date = scholar.service_end_date ? new Date(scholar.service_end_date) : null;
    scholarForm.completion_status = scholar.completion_status;
    scholarForm.remarks = scholar.remarks;
    scholarForm.record_label = scholar.scholar_name || 'N/A';
    currentBatch.value = batch;
    currentScholar.value = scholar;
    showScholarDialog.value = true;
};

const formatDate = (date) => {
    if (!date) return '';
    if (typeof date === 'string') {
        // If it's already a string, check if it's valid
        if (date.match(/^\d{4}-\d{2}-\d{2}$/)) return date;
        return '';
    }
    if (date instanceof Date && !isNaN(date)) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    return '';
};

const formatDateLong = (date) => {
    if (!date) return '-';
    let dateObj;
    if (typeof date === 'string') {
        dateObj = new Date(date);
    } else if (date instanceof Date) {
        dateObj = date;
    } else {
        return '-';
    }

    if (isNaN(dateObj)) return '-';

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const month = monthNames[dateObj.getMonth()];
    const day = dateObj.getDate();
    const year = dateObj.getFullYear();
    return `${month} ${day}, ${year}`;
};

const formatDateInput = (event) => {
    const input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove non-digits

    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }

    input.value = value;

    // Try to parse and emit the date if it's complete
    if (value.length === 10) {
        const [month, day, year] = value.split('/');
        const date = new Date(year, month - 1, day);
        if (!isNaN(date.getTime())) {
            // Update the appropriate form field based on which input triggered this
            const fieldName = input.id || (input.parentElement?.querySelector('[id]')?.id);
            // The input will automatically update via v-model
        }
    }
};

const submitScholarForm = async () => {
    scholarForm.processing = true;
    try {
        const updatedBatchId = scholarForm.batch_id;
        const isEditMode = scholarMode.value === 'edit';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        let successCount = 0;
        let errorCount = 0;
        let lastError = '';

        if (isEditMode) {
            // Edit mode - update single scholar
            const url = route('return-of-service.scholar.update', currentScholar.value.id);
            const formData = new FormData();
            if (currentBatch.value.course_id) {
                const courseId = typeof currentBatch.value.course_id === 'object' ? currentBatch.value.course_id.id : currentBatch.value.course_id;
                formData.append('course_id', courseId);
            }
            formData.append('years_of_service', Math.round(scholarForm.years_of_service));
            if (scholarForm.service_start_date) formData.append('service_start_date', formatDate(scholarForm.service_start_date));
            if (scholarForm.service_end_date) formData.append('service_end_date', formatDate(scholarForm.service_end_date));
            formData.append('completion_status', scholarForm.completion_status);
            if (scholarForm.remarks) formData.append('remarks', scholarForm.remarks);
            formData.append('_method', 'PUT');

            try {
                const response = await axios.post(url, formData, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = response.data;
                successCount = 1;
            } catch (error) {
                if (error.response?.data?.errors) {
                    scholarForm.errors = error.response.data.errors;
                }
                lastError = error.response?.data?.message || 'Failed to update scholar';
                errorCount = 1;
            }
        } else {
            // Add mode - handle multiple selected scholars
            const selectedProfiles = Array.isArray(scholarForm.selectedProfile) ? scholarForm.selectedProfile : [scholarForm.selectedProfile];

            for (const profile of selectedProfiles) {
                const url = route('return-of-service.scholar.store');
                const formData = new FormData();

                // Required fields
                formData.append('batch_id', updatedBatchId);
                formData.append('profile_id', profile.id);
                formData.append('completion_status', scholarForm.completion_status || 'pending');

                // Optional fields
                if (currentBatch.value.course_id) {
                    const courseId = typeof currentBatch.value.course_id === 'object' ? currentBatch.value.course_id.id : currentBatch.value.course_id;
                    formData.append('course_id', courseId);
                }
                if (scholarForm.years_of_service !== null && scholarForm.years_of_service !== '') {
                    formData.append('years_of_service', Math.round(scholarForm.years_of_service));
                }
                if (scholarForm.remarks) formData.append('remarks', scholarForm.remarks);

                const serviceStartDate = formatDate(scholarForm.service_start_date);
                if (serviceStartDate) formData.append('service_start_date', serviceStartDate);

                const serviceEndDate = formatDate(scholarForm.service_end_date);
                if (serviceEndDate) formData.append('service_end_date', serviceEndDate);

                try {
                    const response = await axios.post(url, formData, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    const data = response.data;
                    successCount++;
                } catch (error) {
                    errorCount++;
                    lastError = error.response?.data?.message || 'Failed to add scholar';
                }
            }
        }

        if (errorCount > 0 && successCount === 0) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: lastError,
                life: 5000
            });
            scholarForm.processing = false;
            return;
        }

        showScholarDialog.value = false;
        scholarForm.reset();
        scholarForm.clearErrors();
        currentScholar.value = null;

        // Show success toast
        const msg = isEditMode
            ? 'Scholar updated successfully'
            : successCount === 1
                ? 'Scholar added successfully'
                : `${successCount} scholars added successfully`;

        if (errorCount > 0) {
            toast.add({
                severity: 'warn',
                summary: 'Partial Success',
                detail: `${msg}. ${errorCount} failed.`,
                life: 5000
            });
        } else {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: msg,
                life: 3000
            });
        }

        // Refresh the current batch to show updated scholars
        if (viewingBatch.value && viewingBatch.value.id === updatedBatchId) {
            try {
                const res = await axios.get(route('return-of-service.batch.show', updatedBatchId));
                const batchData = res.data;
                if (batchData && batchData.scholars) {
                    viewingBatch.value.scholars = batchData.scholars;
                    // Update the batch in the batches array as well
                    const batchIndex = batches.value.findIndex(b => b.id === updatedBatchId);
                    if (batchIndex !== -1) {
                        batches.value[batchIndex].scholars = batchData.scholars;
                        batches.value[batchIndex].total_scholars = batchData.scholars.length;
                    }
                }
            } catch (error) {
                console.error('Error fetching updated batch:', error);
            }
        } else {
            // If batch is not being viewed, we still need to update the batches array
            const batchIndex = batches.value.findIndex(b => b.id === updatedBatchId);
            if (batchIndex !== -1) {
                try {
                    const res = await axios.get(route('return-of-service.batch.show', updatedBatchId));
                    const batchData = res.data;
                    if (batchData && batchData.scholars) {
                        batches.value[batchIndex].scholars = batchData.scholars;
                        batches.value[batchIndex].total_scholars = batchData.scholars.length;
                    }
                } catch (error) {
                    console.error('Error fetching updated batch:', error);
                }
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while submitting the form',
            life: 5000
        });
    } finally {
        scholarForm.processing = false;
    }
};

const resetScholarForm = () => {
    scholarForm.reset();
    scholarForm.clearErrors();
    currentBatch.value = null;
    currentScholar.value = null;
};

const confirmDeleteScholar = (scholar) => {
    scholarToDelete.value = scholar;
    showDeleteScholarDialog.value = true;
};

const closeDeleteScholarDialog = () => {
    showDeleteScholarDialog.value = false;
};

const deleteScholar = async () => {
    const scholarId = scholarToDelete.value.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        const response = await axios.post(
            route('return-of-service.scholar.destroy', scholarId),
            { _method: 'DELETE' },
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }
        );

        const data = response.data;

        showDeleteScholarDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Scholar deleted successfully',
            life: 3000
        });

        // Update viewing batch if open
        if (viewingBatch.value && viewingBatch.value.scholars) {
            const scholarIndex = viewingBatch.value.scholars.findIndex(s => s.id === scholarId);
            if (scholarIndex !== -1) {
                viewingBatch.value.scholars.splice(scholarIndex, 1);
                viewingBatch.value.total_scholars = viewingBatch.value.scholars.length;
            }
        }

        scholarToDelete.value = null;
    } catch (error) {
        console.error('Error:', error);
        const message = error.response?.data?.message || 'Failed to delete scholar';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 5000
        });
    }
};

const viewScholar = (scholar) => {
    viewingScholar.value = scholar;
    showViewScholarDialog.value = true;
};

const findBatchById = (batchId) => {
    return batches.value.find((batch) => String(batch.id) === String(batchId)) || null;
};

const openReportDialog = (batch = null) => {
    reportContextBatch.value = batch?.id ? (findBatchById(batch.id) || batch) : null;
    showReportDialog.value = true;
};

const closeReportDialog = () => {
    showReportDialog.value = false;
    reportContextBatch.value = null;
};

const searchProfiles = async (query) => {
    if (!query || query.length < 1) {
        scholarshipRecords.value = [];
        return;
    }
    try {
        const response = await axios.get(route('return-of-service.search-records'), {
            params: {
                q: query,
                status: 'completed',
                program: 'MEDICINE AND MEDICAL ALLIED COURSES'
            }
        });

        // Filter out profiles already in the current batch
        const filteredProfiles = response.data.filter(profile => {
            return !isScholarAlreadyInBatch(profile.id);
        });

        scholarshipRecords.value = filteredProfiles;
    } catch (error) {
        console.error('Error searching records:', error);
    }
};

const onProfileFilter = (event) => {
    searchProfiles(event.value);
};

const isScholarAlreadyInBatch = (profileId) => {
    if (!currentBatch.value || !currentBatch.value.scholars) {
        return false;
    }
    return currentBatch.value.scholars.some(scholar => scholar.profile_id === profileId);
};

const stripHtml = (html) => {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').trim();
};

const formatCompletionStatus = (status) => {
    if (!status) {
        return '-';
    }

    return String(status)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const reportBatchOptions = computed(() => {
    return batches.value.map((batch) => ({
        label: `${batch.batch_name} (${batch.total_scholars || 0} scholars)`,
        value: batch.id,
    }));
});

const exportBatchCollection = (sourceBatches, filenamePrefix = 'ROS_Report') => {
    const wb = XLSX.utils.book_new();

    const headers = ['Batch Name', 'Course', 'Exam Date From', 'Exam Date To', 'Result Date',
        'Scholar Name', 'Years of Service', 'Service Start', 'Service End', 'Status', 'Remarks'];
    const rows = [];

    for (const batch of sourceBatches) {
        const scholars = batch.scholars || [];

        if (scholars.length === 0) {
            rows.push([
                batch.batch_name,
                batch.course_name || '',
                formatDateLong(batch.exam_date_from),
                formatDateLong(batch.exam_date_to),
                formatDateLong(batch.result_date),
                '',
                '',
                '',
                '',
                '',
                '',
            ]);
            continue;
        }

        for (const scholar of scholars) {
            rows.push([
                batch.batch_name,
                batch.course_name || '',
                formatDateLong(batch.exam_date_from),
                formatDateLong(batch.exam_date_to),
                formatDateLong(batch.result_date),
                scholar.scholar_name || '',
                scholar.years_of_service ?? '',
                formatDateLong(scholar.service_start_date),
                formatDateLong(scholar.service_end_date),
                scholar.completion_status || '',
                stripHtml(scholar.remarks),
            ]);
        }
    }

    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    XLSX.utils.book_append_sheet(wb, ws, 'ROS Report');

    const safeFilenamePrefix = filenamePrefix.replace(/[^a-zA-Z0-9]+/g, '_');
    const filename = `${safeFilenamePrefix}_${new Date().toISOString().slice(0, 10)}.xlsx`;
    XLSX.writeFile(wb, filename);
};

const buildExamDateRange = (batch) => {
    const fromLabel = formatDateLong(batch.exam_date_from);
    const toLabel = formatDateLong(batch.exam_date_to);

    if (fromLabel === '-' && toLabel === '-') {
        return '-';
    }

    if (fromLabel === '-') {
        return toLabel;
    }

    if (toLabel === '-') {
        return fromLabel;
    }

    return `${fromLabel} - ${toLabel}`;
};

const buildRosReportPayload = (sourceBatches, scopeLabel) => {
    const normalizedBatches = sourceBatches.map((batch) => ({
        id: batch.id,
        batch_name: batch.batch_name,
        course_name: batch.course_name || 'N/A',
        description: batch.description || '',
        exam_date_range: buildExamDateRange(batch),
        result_date_label: formatDateLong(batch.result_date),
        created_by: batch.created_by || 'System',
        total_scholars: (batch.scholars || []).length,
        scholars: (batch.scholars || []).map((scholar, index) => ({
            index: index + 1,
            scholar_name: scholar.scholar_name || '',
            years_of_service: scholar.years_of_service ?? '',
            service_start_date_label: formatDateLong(scholar.service_start_date),
            service_end_date_label: formatDateLong(scholar.service_end_date),
            completion_status_label: formatCompletionStatus(scholar.completion_status),
            completion_status_key: scholar.completion_status || '',
            remarks: stripHtml(scholar.remarks),
        })),
    }));

    const summary = normalizedBatches.reduce((totals, batch) => {
        totals.totalBatches += 1;
        totals.totalScholars += batch.scholars.length;

        for (const scholar of batch.scholars) {
            if (scholar.completion_status_key in totals) {
                totals[scholar.completion_status_key] += 1;
            }
        }

        return totals;
    }, {
        totalBatches: 0,
        totalScholars: 0,
        pending: 0,
        ongoing: 0,
        suspended: 0,
        completed: 0,
    });

    return {
        title: 'Return of Service Report',
        scopeLabel,
        generatedAt: new Intl.DateTimeFormat('en-US', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date()),
        summary,
        batches: normalizedBatches,
    };
};

const resolveReportBatches = ({ scope, batchId }) => {
    if (reportContextBatch.value?.id) {
        const batch = findBatchById(reportContextBatch.value.id) || reportContextBatch.value;
        return batch ? [batch] : [];
    }

    if (scope === 'filtered') {
        return [...filteredBatches.value];
    }

    if (scope === 'batch' && batchId) {
        const batch = findBatchById(batchId);
        return batch ? [batch] : [];
    }

    return [...batches.value];
};

const buildReportScopeLabel = (scope, selectedBatches) => {
    if (reportContextBatch.value || scope === 'batch') {
        return `Batch: ${selectedBatches[0]?.batch_name || 'Selected Batch'}`;
    }

    if (scope === 'filtered') {
        return `Filtered Results (${selectedBatches.length} batch${selectedBatches.length === 1 ? '' : 'es'})`;
    }

    return `All Batches (${selectedBatches.length})`;
};

const generateReport = ({ format, scope, batchId }) => {
    const selectedBatches = resolveReportBatches({ scope, batchId });

    if (!selectedBatches.length) {
        toast.add({
            severity: 'warn',
            summary: 'No Data',
            detail: 'No batches are available for the selected report scope.',
            life: 3000,
        });
        return;
    }

    const scopeLabel = buildReportScopeLabel(scope, selectedBatches);

    if (format === 'excel') {
        if (selectedBatches.length === 1 && (scope === 'batch' || reportContextBatch.value)) {
            exportBatch(selectedBatches[0]);
        } else {
            exportBatchCollection(
                selectedBatches,
                scope === 'filtered' ? 'ROS_Filtered_Report' : 'ROS_All_Batches_Report'
            );
        }

        closeReportDialog();
        return;
    }

    const reportPayload = buildRosReportPayload(selectedBatches, scopeLabel);
    const reportHtml = renderVueTemplate(ReturnOfServiceReportTemplate, reportPayload);
    const reportName = (selectedBatches.length === 1
        ? `ROS_${selectedBatches[0].batch_name}`
        : (scope === 'filtered' ? 'ROS_Filtered_Report' : 'ROS_All_Batches_Report'))
        .replace(/[^a-zA-Z0-9]+/g, '_');

    printHtml(reportHtml, reportName, 'a4', returnOfServiceReportCss);
    closeReportDialog();
};

const exportBatch = (batch) => {
    const wb = XLSX.utils.book_new();

    const batchInfo = [
        ['Batch Name', batch.batch_name],
        ['Course', batch.course_name || ''],
        ['Description', batch.description || ''],
        ['Exam Date From', formatDateLong(batch.exam_date_from)],
        ['Exam Date To', formatDateLong(batch.exam_date_to)],
        ['Result Date', formatDateLong(batch.result_date)],
        ['Created By', batch.created_by || ''],
        ['Total Scholars', batch.total_scholars || 0],
        [],
    ];

    const headers = ['#', 'Scholar Name', 'Years of Service', 'Service Start', 'Service End', 'Status', 'Remarks'];
    const rows = (batch.scholars || []).map((s, i) => [
        i + 1,
        s.scholar_name || '',
        s.years_of_service ?? '',
        formatDateLong(s.service_start_date),
        formatDateLong(s.service_end_date),
        s.completion_status || '',
        stripHtml(s.remarks),
    ]);

    const ws = XLSX.utils.aoa_to_sheet([...batchInfo, headers, ...rows]);
    XLSX.utils.book_append_sheet(wb, ws, 'Scholars');

    const filename = `ROS_${batch.batch_name.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().slice(0, 10)}.xlsx`;
    XLSX.writeFile(wb, filename);
};

const exportRecords = () => {
    exportBatchCollection(batches.value, 'ROS_All_Records');
};

const getCompletionSeverity = (status) => {
    const severityMap = {
        'pending': 'secondary',
        'ongoing': 'info',
        'suspended': 'warning',
        'completed': 'success',
    };
    return severityMap[status] || 'info';
};

const filteredBatches = computed(() => {
    if (!batches.value || batches.value.length === 0) return [];

    const nameQuery = batchSearch.value.toLowerCase().trim();
    const creatorQuery = batchCreatedByFilter.value.toLowerCase().trim();
    const descQuery = batchDescriptionFilter.value.toLowerCase().trim();
    const selectedYear = batchYearFilter.value;

    return batches.value.filter(batch => {
        const matchesName = !nameQuery || batch.batch_name?.toLowerCase().includes(nameQuery);
        const matchesCreator = !creatorQuery || batch.created_by?.toLowerCase().includes(creatorQuery);
        const matchesDesc = !descQuery || batch.description?.toLowerCase().includes(descQuery);

        let matchesYear = true;
        if (selectedYear) {
            const examYear = batch.exam_date_from ? new Date(batch.exam_date_from).getFullYear() : null;
            matchesYear = examYear === selectedYear;
        }

        return matchesName && matchesCreator && matchesDesc && matchesYear;
    });
});

const filteredScholars = computed(() => {
    if (!viewingBatch.value || !viewingBatch.value.scholars) return [];

    const query = scholarSearch.value.toLowerCase().trim();
    if (!query) return viewingBatch.value.scholars;

    return viewingBatch.value.scholars.filter(scholar =>
        scholar.scholar_name?.toLowerCase().includes(query) ||
        scholar.course_name?.toLowerCase().includes(query) ||
        scholar.completion_status?.toLowerCase().includes(query)
    );
});
</script>
