<template>

    <Head title="Return of Service Batches" />

    <AdminLayout>
        <template #header>
            Return of Service (ROS) Management
        </template>

        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <i class="pi pi-graduation-cap text-2xl text-blue-600"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Return of Service (ROS) Management</h1>
                </div>
                <p class="text-gray-600">Manage return of service batches and add scholars to track their service
                    obligations.
                </p>
                <div class="flex gap-2 mt-4">
                    <Button v-if="hasPermission('return-of-service.export')" icon="pi pi-download" label="Export"
                        severity="info" outlined @click="exportRecords" />
                    <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus" label="New Batch"
                        severity="success" raised @click="openNewBatchDialog" />
                </div>
            </div>

            <!-- Batches Filters Panel -->
            <Panel>
                <div class="space-y-3 -mt-6">
                    <!-- Filter Controls Header -->
                    <div class="flex justify-between items-center py-1">
                        <div class="flex items-center gap-3">
                            <span class="opacity-60 text-sm">Search and filter batches</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Button severity="secondary" outlined size="small" icon="pi pi-history"
                                @click="clearBatchFilters" v-tooltip.bottom="'Clear Filters'" />
                        </div>
                    </div>

                    <!-- Batch Filters Row -->
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-4 lg:gap-4">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Batch Name</label>
                            <InputText v-model="batchSearch" placeholder="Search batch name..." class="w-full"
                                size="small" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Created By</label>
                            <InputText v-model="batchCreatedByFilter" placeholder="Filter by creator..." class="w-full"
                                size="small" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Year</label>
                            <InputNumber v-model="batchYearFilter" placeholder="e.g., 2025" :useGrouping="false"
                                class="w-full" size="small" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Description</label>
                            <InputText v-model="batchDescriptionFilter" placeholder="Search description..."
                                class="w-full" size="small" />
                        </div>
                    </div>
                </div>
            </Panel>


            <!-- Batches Cards Grid -->
            <div class="grid grid-cols-1 gap-4">
                <div v-for="batch in filteredBatches" :key="batch.id"
                    class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3 flex-grow">
                            <i class="pi pi-folder text-lg text-blue-500"></i>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-lg">{{ batch.batch_name }}</h4>
                                <div class="text-xs text-gray-500 space-y-1 mt-1">
                                    <p v-if="batch.exam_date_from || batch.exam_date_to">Exam: {{
                                        formatDateLong(batch.exam_date_from)
                                    }}
                                        to {{ formatDateLong(batch.exam_date_to) }}</p>
                                    <p v-if="batch.result_date">Result: {{ formatDateLong(batch.result_date) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-right">
                            <div>
                                <span
                                    class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold block">
                                    {{ batch.total_scholars }} Scholars
                                </span>
                                <p class="text-xs text-gray-500 mt-2">Created by {{ batch.created_by }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="batch.description" class="bg-gray-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-gray-700">{{ batch.description }}</p>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <Button icon="pi pi-eye" label="View Batch" severity="secondary" text size="small"
                            @click="openViewBatchDialog(batch)" />
                        <Button v-if="hasPermission('return-of-service.edit')" icon="pi pi-pencil" label="Edit"
                            severity="warning" text size="small" @click="openEditBatchDialog(batch)" />
                        <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus" label="Add Scholar"
                            severity="success" text size="small" @click="openAddScholarDialog(batch)" />
                        <Button v-if="hasPermission('return-of-service.delete')" icon="pi pi-trash" label="Delete"
                            severity="danger" text size="small" @click="confirmDeleteBatch(batch)" />
                        <Button icon="pi pi-download" label="Export" severity="info" text size="small"
                            @click="exportBatch(batch)" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="batches.length === 0" class="text-center py-12">
                <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">No ROS batches created yet.</p>
                <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus" label="Create First Batch"
                    severity="success" @click="openNewBatchDialog" />
            </div>

            <!-- No Results State -->
            <div v-else-if="filteredBatches.length === 0" class="text-center py-12">
                <i class="pi pi-search text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">No batches match your filters.</p>
                <Button icon="pi pi-times" label="Clear Filters" severity="secondary" @click="clearBatchFilters"
                    outlined />
            </div>
        </div>

        <!-- New/Edit Batch Dialog -->
        <Dialog v-model:visible="showBatchDialog" :header="batchMode === 'add' ? 'Create New Batch' : 'Edit Batch'"
            :modal="true" :style="{ width: '600px' }" @hide="resetBatchForm">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Batch Name *</label>
                    <InputText v-model="batchForm.batch_name" placeholder="e.g., Batch 2025-A" class="w-full"
                        :class="{ 'p-invalid': batchForm.errors.batch_name }" />
                    <small class="text-red-500" v-if="batchForm.errors.batch_name">{{ batchForm.errors.batch_name
                    }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Textarea v-model="batchForm.description" rows="3" class="w-full"
                        placeholder="Add description or notes about this batch" />
                </div>

                <div class="flex gap-2">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Examination Date From</label>
                        <DatePicker v-model="batchForm.exam_date_from" placeholder="mm/dd/yyyy" type="date"
                            :manualInput="true" @input="formatDateInput" showIcon iconDisplay="button" fluid
                            class="w-full" />
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Examination Date To</label>
                        <DatePicker v-model="batchForm.exam_date_to" placeholder="mm/dd/yyyy" type="date"
                            :manualInput="true" @input="formatDateInput" showIcon iconDisplay="button" fluid
                            class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result Date</label>
                    <DatePicker v-model="batchForm.result_date" placeholder="mm/dd/yyyy" type="date" :manualInput="true"
                        @input="formatDateInput" showIcon iconDisplay="button" fluid class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Course *</label>
                    <CourseSelect v-model="batchForm.course_id" customPlaceholder="Select course" />
                    <small class="text-red-500" v-if="batchForm.errors.course_id">{{ batchForm.errors.course_id
                    }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showBatchDialog = false" />
                <Button :label="batchMode === 'add' ? 'Create' : 'Update'" severity="primary" @click="submitBatchForm"
                    :loading="batchForm.processing" />
            </template>
        </Dialog>

        <!-- Add/Edit Scholar Dialog -->
        <Dialog v-model:visible="showScholarDialog"
            :header="scholarMode === 'add' ? 'Add Scholar to Batch' : 'Edit Scholar'" :modal="true"
            :style="{ width: '800px' }" @hide="resetScholarForm">
            <div class="space-y-4 max-h-[70vh] overflow-y-auto">
                <div v-if="scholarMode === 'edit'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scholar Name</label>
                    <InputText :value="scholarForm.record_label" readonly class="w-full" />
                </div>

                <div v-else>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scholar Name *</label>
                    <MultiSelect v-model="scholarForm.selectedProfile" :options="scholarshipRecords" optionLabel="label"
                        placeholder="Search completed scholars..." :filter="true" class="w-full"
                        @filter="onProfileFilter" />
                    <small class="text-gray-500 text-xs mt-1">Only completed scholarship records from Medicine and
                        Medical
                        Allied Courses program</small>
                    <small v-if="scholarForm.errors.profile_id" class="text-red-500">{{ scholarForm.errors.profile_id
                    }}</small>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Return of Service Details</h4>
                    <div class="grid grid-cols-2 gap-4">


                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service Start Date</label>
                            <DatePicker v-model="scholarForm.service_start_date" placeholder="mm/dd/yyyy" type="date"
                                :manualInput="false" showIcon iconDisplay="button" fluid class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service End Date</label>
                            <DatePicker v-model="scholarForm.service_end_date" placeholder="mm/dd/yyyy" type="date"
                                :manualInput="false" showIcon iconDisplay="button" fluid class="w-full"
                                :class="{ 'p-invalid': isEndDateInvalid }" />
                            <small class="text-red-500" v-if="isEndDateInvalid">End date cannot be earlier than start
                                date</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Years of Service</label>
                            <InputNumber v-model="scholarForm.years_of_service" :min="0" placeholder="Auto-calculated"
                                class="w-full" />
                            <small class="text-gray-500 text-xs mt-1">Auto-calculated from service dates
                                (editable)</small>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Completion Status *</label>
                    <div class="space-y-3">
                        <div v-for="option in completionOptions" :key="option.value" class="flex items-center">
                            <RadioButton v-model="scholarForm.completion_status" :value="option.value"
                                :inputId="'status_' + option.value" />
                            <label :for="'status_' + option.value" class="ml-2 text-sm text-gray-700 cursor-pointer">{{
                                option.label }}</label>
                        </div>
                    </div>
                    <small class="text-red-500 block mt-2" v-if="scholarForm.errors.completion_status">{{
                        scholarForm.errors.completion_status }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <Editor v-model="scholarForm.remarks" editorStyle="height: 120px">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showScholarDialog = false" />
                <Button :label="scholarMode === 'add' ? 'Add' : 'Update'" severity="primary" @click="submitScholarForm"
                    :loading="scholarForm.processing" :disabled="isEndDateInvalid" />
            </template>
        </Dialog>

        <!-- View Scholar Dialog -->
        <Dialog v-model:visible="showViewScholarDialog" header="Scholar Details" :modal="true"
            :style="{ width: '700px' }">
            <div v-if="viewingScholar" class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="font-semibold text-lg text-gray-800">{{ viewingScholar.scholar_name }}</h3>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Return of Service</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Years</p>
                            <p class="font-semibold text-lg text-blue-600">{{ viewingScholar.years_of_service || 0 }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <Tag :value="viewingScholar.completion_status"
                                :severity="getCompletionSeverity(viewingScholar.completion_status)" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Start</p>
                            <p class="font-semibold">{{ formatDateLong(viewingScholar.service_start_date) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Service End Date</p>
                            <p class="font-semibold">{{ formatDateLong(viewingScholar.service_end_date) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="viewingScholar.remarks" class="border-t pt-4">
                    <p class="text-xs text-gray-500 uppercase mb-2">Remarks</p>
                    <p class="text-gray-700">{{ viewingScholar.remarks }}</p>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" @click="showViewScholarDialog = false" />
            </template>
        </Dialog>

        <!-- Delete Batch Dialog -->
        <Dialog v-model:visible="showDeleteBatchDialog" header="Confirm Delete Batch" :modal="true"
            :style="{ width: '450px' }">
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
                <div>
                    <p class="mb-2">Are you sure you want to delete this batch?</p>
                    <p class="text-sm text-gray-600 font-semibold">{{ batchToDelete?.batch_name }}</p>
                    <p class="text-sm text-red-600 mt-2">This will delete all {{ batchToDelete?.total_scholars }}
                        scholars in
                        this batch. This action cannot be undone.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeleteBatchDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteBatch" />
            </template>
        </Dialog>

        <!-- View Batch Dialog -->
        <Dialog v-model:visible="showViewBatchDialog" :header="viewingBatch?.batch_name" :modal="true"
            :style="{ width: '90vw' }" @hide="viewingBatch = null; scholarSearch = '';">
            <div v-if="viewingBatch" class="space-y-4">
                <!-- Batch Information -->
                <div class="grid grid-cols-2 gap-4 pb-4 border-b md:grid-cols-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Course</p>
                        <p class="font-semibold text-gray-800">{{ viewingBatch.course_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Exam Date</p>
                        <p class="font-semibold text-gray-800">{{ formatDateLong(viewingBatch.exam_date_from) }} - {{
                            formatDateLong(viewingBatch.exam_date_to) }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Result Date</p>
                        <p class="font-semibold text-gray-800">{{ formatDateLong(viewingBatch.result_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Created By</p>
                        <p class="font-semibold text-gray-800">{{ viewingBatch.created_by }}</p>
                    </div>
                </div>

                <div v-if="viewingBatch.description" class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600"><span class="font-semibold">Description:</span> {{
                        viewingBatch.description
                        }}</p>
                </div>

                <!-- Scholars DataTable -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-search"></i>
                        <InputText v-model="scholarSearch" placeholder="Search scholar name or status..."
                            class="w-full" />
                    </div>
                    <h5 class="font-semibold text-gray-800 mb-3">Scholars in this Batch</h5>
                    <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredScholars"
                        :rows="10" paginator :rowHover="true" stripedRows responsiveLayout="scroll">

                        <Column field="scholar_name" header="Scholar Name" sortable style="min-width: 200px">
                            <template #body="slotProps">
                                <span class="font-semibold">{{ slotProps.data.scholar_name }}</span>
                            </template>
                        </Column>

                        <Column field="years_of_service" header="Years ROS" sortable style="width: 120px">
                            <template #body="slotProps">
                                <span class="font-semibold text-blue-600">{{ slotProps.data.years_of_service ||
                                    0 }}</span>
                            </template>
                        </Column>

                        <Column field="completion_status" header="Status" sortable style="width: 130px">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.completion_status"
                                    :severity="getCompletionSeverity(slotProps.data.completion_status)" />
                            </template>
                        </Column>

                        <Column field="service_start_date" header="Service Start" sortable style="min-width: 140px">
                            <template #body="slotProps">
                                <span class="font-mono text-sm">{{ formatDateLong(slotProps.data.service_start_date)
                                }}</span>
                            </template>
                        </Column>

                        <Column field="service_end_date" header="Service End" sortable style="min-width: 140px">
                            <template #body="slotProps">
                                <span class="font-mono text-sm">{{ formatDateLong(slotProps.data.service_end_date)
                                }}</span>
                            </template>
                        </Column>

                        <Column field="remarks" header="Remarks" style="min-width: 200px">
                            <template #body="slotProps">
                                <span class="text-sm text-gray-700">{{ slotProps.data.remarks || '-' }}</span>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 200px">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" severity="secondary" text size="small"
                                        @click="viewScholar(slotProps.data)" v-tooltip.top="'View'" />
                                    <Button v-if="hasPermission('return-of-service.edit')" icon="pi pi-pencil"
                                        severity="warning" text size="small"
                                        @click="openEditScholarDialog(viewingBatch, slotProps.data)"
                                        v-tooltip.top="'Edit'" />
                                    <Button v-if="hasPermission('return-of-service.delete')" icon="pi pi-trash"
                                        severity="danger" text size="small"
                                        @click="confirmDeleteScholar(slotProps.data)" v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-8">
                                <p class="text-gray-500">No scholars in this batch yet.</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <template #footer>
                <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus" label="Add Scholar"
                    severity="success" @click="openAddScholarDialog(viewingBatch)" />
                <Button label="Close" severity="secondary" @click="showViewBatchDialog = false" />
            </template>
        </Dialog>

        <!-- Delete Scholar Dialog -->
        <Dialog v-model:visible="showDeleteScholarDialog" header="Confirm Delete Scholar" :modal="true"
            :style="{ width: '450px' }">
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
                <div>
                    <p class="mb-2">Are you sure you want to remove this scholar from the batch?</p>
                    <p class="text-sm text-gray-600 font-semibold">{{ scholarToDelete?.scholar_name }}</p>
                    <p class="text-sm text-red-600 mt-2">This action cannot be undone.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeleteScholarDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteScholar" />
            </template>
        </Dialog>

        <!-- Preview Scholar Drawer -->
        <div v-if="showPreviewScholarDialog && Array.isArray(scholarForm.selectedProfile) && scholarForm.selectedProfile.length > 0"
            class="fixed right-0 top-0 h-screen w-80 bg-white border-l border-gray-300 shadow-2xl overflow-y-auto"
            style="z-index: 9999; background: #ffffff;">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Selected Scholars</h3>
                    <button @click="showPreviewScholarDialog = false" class="text-gray-400 hover:text-gray-700">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
                <div class="space-y-2">
                    <div v-for="(scholar, index) in scholarForm.selectedProfile" :key="scholar.id"
                        class="bg-blue-50 p-3 rounded border border-blue-200">
                        <p class="font-semibold text-gray-800 text-sm">{{ index + 1 }}. {{ scholar.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications with higher z-index than drawer -->
        <Toast position="top-right" style="z-index: 10000;" />
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import DatePicker from 'primevue/datepicker';
import Toast from 'primevue/toast';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    batches: Array,
    courses: Array,
    completionOptions: Array,
});

const { hasPermission } = usePermission();
const toast = useToast();

// Dialog states
const showBatchDialog = ref(false);
const showScholarDialog = ref(false);
const showViewScholarDialog = ref(false);
const showViewBatchDialog = ref(false);
const showDeleteBatchDialog = ref(false);
const showDeleteScholarDialog = ref(false);
const showPreviewScholarDialog = ref(false);

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
    console.log('Computing years of service...');
    console.log('Start date:', scholarForm.service_start_date, 'type:', typeof scholarForm.service_start_date);
    console.log('End date:', scholarForm.service_end_date, 'type:', typeof scholarForm.service_end_date);

    if (!scholarForm.service_start_date || !scholarForm.service_end_date) {
        console.log('Missing dates, returning null');
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

    console.log('Parsed start:', startDate);
    console.log('Parsed end:', endDate);

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        console.log('Invalid dates');
        return null;
    }

    // Calculate difference in days and convert to years with decimal places
    const diffTime = endDate - startDate;
    const diffDays = diffTime / (1000 * 60 * 60 * 24);

    console.log('Diff days:', diffDays);

    // Convert days to months (average 30.44 days per month), then to years
    const monthsDecimal = diffDays / 30.44;
    const yearsDecimal = monthsDecimal / 12;

    // Round to 1 decimal place (e.g., 3.5 for 3 years 6 months)
    const rounded = Math.round(yearsDecimal * 10) / 10;

    console.log('Computed years:', rounded);

    return rounded >= 0 ? rounded : null;
});

// Watch for date changes to auto-populate years of service
watch(
    () => [scholarForm.service_start_date, scholarForm.service_end_date],
    () => {
        console.log('Dates changed:', {
            start: scholarForm.service_start_date,
            end: scholarForm.service_end_date,
            computed: computedYearsOfService.value
        });
        // Auto-populate years_of_service if both dates are set and years_of_service is empty
        if (computedYearsOfService.value !== null && !scholarForm.years_of_service) {
            scholarForm.years_of_service = computedYearsOfService.value;
        }
    },
    { deep: true }
);

// Watch for profile selection
watch(() => scholarForm.selectedProfile, (selected) => {
    console.log('=== PROFILE SELECTION CHANGED ===');
    console.log('selected value:', selected);
    console.log('is array:', Array.isArray(selected));

    if (Array.isArray(selected) && selected.length > 0) {
        // MultiSelect returns an array, get the first (and only) selected item
        console.log('first item:', selected[0]);
        scholarForm.profile_id = selected[0].id;
        scholarForm.record_label = selected[0].label;
        // Populate name fields from profile
        scholarForm.lastname = selected[0].lastname || '';
        scholarForm.firstname = selected[0].firstname || '';
        scholarForm.middlename = selected[0].middlename || '';
        scholarForm.ext = selected[0].ext || '';
        console.log('Set profile_id to:', scholarForm.profile_id);
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
        console.log('Set profile_id (single object) to:', scholarForm.profile_id);
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
        console.log('Cleared profile_id');
        // Close preview drawer
        showPreviewScholarDialog.value = false;
    }
}, { deep: true });

// Batch management
const openViewBatchDialog = (batch) => {
    viewingBatch.value = batch;
    showViewBatchDialog.value = true;
};

const openNewBatchDialog = () => {
    batchMode.value = 'add';
    batchForm.reset();
    batchForm.clearErrors();
    showBatchDialog.value = true;
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

const exportBatch = (batch) => {
    window.location.href = route('return-of-service.export') + '?batch_id=' + batch.id;
};

const exportRecords = () => {
    window.location.href = route('return-of-service.export');
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
