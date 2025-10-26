<template>
    <div class="p-6">
        <!-- Header with Add Button -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Disbursements & Cheques</h3>
                <p class="text-sm text-gray-500 mt-1">Manage disbursements and cheque processing</p>
            </div>
            <Button icon="pi pi-plus" label="Add Disbursement" @click="showAddModal = true" />
        </div>

        <!-- Disbursements List -->
        <DataView :value="disbursements" :loading="loading">
            <template #empty>
                <div class="text-center py-12">
                    <i class="pi pi-money-bill text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No disbursements found</p>
                    <Button label="Add First Disbursement" class="mt-4" @click="showAddModal = true" />
                </div>
            </template>

            <template #list="slotProps">
                <div class="space-y-3">
                    <div v-for="(item, index) in slotProps.items" :key="index"
                        class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">

                        <!-- Compact Header with OBR, Type, Status, and Amount -->
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <!-- Left: OBR Number (Prominent) -->

                                <div class="flex flex-wrap items-center gap-6">
                                    <div v-if="item.obr_no" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">OBR No.</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow bg-slate-100">{{
                                            item.obr_no }}
                                        </p>
                                    </div>
                                    <div v-if="item.disbursement_type" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">Type</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow"
                                            :class="getDisbursementTypeClass(item.disbursement_type)">{{
                                                formatDisbursementType(item.disbursement_type)
                                            }}</p>
                                    </div>
                                    <div v-if="item.obr_status" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">Status</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow"
                                            :class="getObrStatusClass(item.obr_status)">{{
                                                formatDisbursementType(item.obr_status)
                                            }}</p>
                                    </div>
                                </div>

                                <!-- Right: Amount -->
                                <div v-if="item.amount" class="text-right">
                                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(item.amount) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Content Section -->
                        <div class="p-4">
                            <div class="flex flex-col lg:flex-row gap-4">
                                <!-- Main Details -->
                                <div class="flex-1 space-y-3">
                                    <!-- Payee and Date -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                        <div v-if="item.payee" class="flex items-center">
                                            <i class="pi pi-user text-gray-400 mr-2 text-xs"></i>
                                            <span class="text-gray-600 mr-2">Payee:</span>
                                            <span class="font-semibold text-gray-900">{{ item.payee }}</span>
                                        </div>
                                        <div v-if="item.date_obligated" class="flex items-center">
                                            <i class="pi pi-calendar text-gray-400 mr-2 text-xs"></i>
                                            <span class="text-gray-600 mr-2">Obligated:</span>
                                            <span class="font-medium text-gray-900">{{ formatDate(item.date_obligated)
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Academic Information (Compact Inline) -->
                                    <div v-if="item.year_level || item.semester || item.academic_year"
                                        class="flex flex-wrap items-center gap-3 text-xs bg-gray-50 rounded px-3 py-2 border border-gray-200">
                                        <div v-if="item.year_level" class="flex items-center">
                                            <span class="text-gray-500 mr-1">Year:</span>
                                            <span class="font-medium text-gray-900">{{ item.year_level }}</span>
                                        </div>
                                        <span v-if="item.year_level && item.semester" class="text-gray-300">•</span>
                                        <div v-if="item.semester" class="flex items-center">
                                            <span class="text-gray-500 mr-1">Term:</span>
                                            <span class="font-medium text-gray-900">{{ item.semester }}</span>
                                        </div>
                                        <span v-if="item.semester && item.academic_year" class="text-gray-300">•</span>
                                        <div v-if="item.academic_year" class="flex items-center">
                                            <span class="text-gray-500 mr-1">AY:</span>
                                            <span class="font-medium text-gray-900">{{ item.academic_year }}</span>
                                        </div>
                                    </div>

                                    <!-- Cheque Information (Compact) -->
                                    <div v-if="item.cheques && item.cheques.length > 0"
                                        class="flex flex-wrap items-center gap-3 text-xs bg-green-50 rounded px-3 py-2 border border-green-200">
                                        <i class="pi pi-check-circle text-green-600"></i>
                                        <div class="flex items-center">
                                            <span class="text-gray-600 mr-1">Cheque:</span>
                                            <span class="font-semibold text-gray-900">{{ item.cheques[0].cheque_no
                                                }}</span>
                                        </div>
                                        <span v-if="item.cheques[0].date_released" class="text-gray-300">•</span>
                                        <div v-if="item.cheques[0].date_released" class="flex items-center">
                                            <span class="text-gray-600 mr-1">Released:</span>
                                            <span class="font-medium text-gray-900">{{
                                                formatDate(item.cheques[0].date_released) }}</span>
                                        </div>
                                    </div>

                                    <!-- Remarks (Compact) -->
                                    <div v-if="item.remarks"
                                        class="text-xs bg-yellow-50 border-l-2 border-yellow-400 px-3 py-2">
                                        <span class="font-semibold text-yellow-700">Note: </span>
                                        <span class="text-gray-700">{{ item.remarks }}</span>
                                    </div>

                                    <!-- Attachments (Compact) -->
                                    <div v-if="item.attachments && item.attachments.length > 0"
                                        class="flex flex-wrap items-center gap-2 text-xs bg-blue-50 rounded px-3 py-2 border border-blue-200">
                                        <i class="pi pi-paperclip text-blue-600"></i>
                                        <span class="text-gray-600 mr-2">Attachments:</span>
                                        <div v-for="attachment in item.attachments" :key="attachment.attachment_id"
                                            class="flex items-center gap-1 bg-white px-2 py-1 rounded border border-blue-200">
                                            <i :class="getFileIcon(attachment.file_type)" class="text-blue-600"></i>
                                            <span class="font-medium text-gray-900">{{ attachment.attachment_type
                                            }}</span>
                                            <Button icon="pi pi-eye" size="small" text rounded v-tooltip.top="'View'"
                                                @click="viewAttachment(attachment)" />
                                            <Button icon="pi pi-download" size="small" text rounded
                                                v-tooltip.top="'Download'" @click="downloadAttachment(attachment)" />
                                            <Button icon="pi pi-times" size="small" text rounded severity="danger"
                                                v-tooltip.top="'Delete'" @click="deleteAttachment(attachment)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Section (Compact) -->
                                <div class="flex lg:flex-col gap-2 lg:border-l lg:border-gray-200 lg:pl-4">
                                    <Button icon="pi pi-paperclip" size="small" severity="secondary" outlined rounded
                                        v-tooltip.top="'Manage Attachments'" @click="manageAttachments(item)" />
                                    <Button icon="pi pi-file" size="small" severity="info" outlined rounded
                                        v-tooltip.top="'Manage Cheque'" @click="manageCheque(item)" />
                                    <Button icon="pi pi-pencil" size="small" severity="warning" outlined rounded
                                        v-tooltip.top="'Edit'" @click="editDisbursement(item)" />
                                    <Button icon="pi pi-trash" size="small" severity="danger" outlined rounded
                                        v-tooltip.top="'Delete'" @click="confirmDelete(item)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </DataView>

        <!-- Add/Edit Disbursement Modal -->
        <Dialog v-model:visible="showAddModal" modal :header="editMode ? 'Edit Disbursement' : 'Add Disbursement'"
            :style="{ width: '50vw' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <Select v-model="form.disbursement_type" :options="disbursementTypes" optionLabel="label"
                            optionValue="value" placeholder="Select type" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payee {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputText v-model="form.payee" placeholder="Enter payee name" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OBR No.</label>
                        <InputText v-model="form.obr_no" placeholder="Enter OBR number" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OBR Status</label>
                        <Select v-model="form.obr_status" :options="obrStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="Select OBR status" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Obligated</label>
                        <DatePicker v-model="form.date_obligated" dateFormat="mm/dd/yy" placeholder="Select date"
                            class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                        <YearLevelSelect v-model="form.year_level" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <TermSelect v-model="form.semester" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                        <AcademicYearSelect v-model="form.academic_year" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputNumber v-model="form.amount" mode="currency" currency="PHP" locale="en-PH"
                            placeholder="Enter amount" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <Textarea v-model="form.remarks" rows="3" placeholder="Enter remarks" class="w-full" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeModal" />
                <Button :label="editMode ? 'Update' : 'Create'" @click="saveDisbursement" :loading="saving" />
            </template>
        </Dialog>

        <!-- Manage Cheque Modal -->
        <Dialog v-model:visible="showChequeModal" modal header="Manage Cheque" :style="{ width: '40vw' }">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cheque No. *</label>
                    <InputText v-model="chequeForm.cheque_no" placeholder="Enter cheque number" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Released</label>
                    <DatePicker v-model="chequeForm.date_released" dateFormat="mm/dd/yy" placeholder="Select date"
                        class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <Textarea v-model="chequeForm.remarks" rows="3" placeholder="Enter remarks" class="w-full" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showChequeModal = false" />
                <Button :label="chequeEditMode ? 'Update' : 'Add Cheque'" @click="saveCheque" :loading="saving" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" modal header="Confirm Delete" :style="{ width: '30vw' }">
            <p>Are you sure you want to delete this disbursement?</p>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteDisbursement" :loading="deleting" />
            </template>
        </Dialog>

        <!-- Manage Attachments Modal -->
        <Dialog v-model:visible="showAttachmentsModal" modal header="Manage Attachments" :style="{ width: '50vw' }">
            <div class="space-y-4">
                <!-- Existing Attachments -->
                <div
                    v-if="selectedDisbursement && selectedDisbursement.attachments && selectedDisbursement.attachments.length > 0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Existing Attachments</h4>
                    <div class="space-y-2">
                        <div v-for="attachment in selectedDisbursement.attachments" :key="attachment.attachment_id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                            <div class="flex items-center gap-3">
                                <i :class="getFileIcon(attachment.file_type)" class="text-2xl text-blue-600"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ attachment.file_name }}</p>
                                    <p class="text-xs text-gray-500">{{ attachment.attachment_type }} • {{
                                        formatFileSize(attachment.file_size) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button icon="pi pi-eye" size="small" outlined label="View"
                                    @click="viewAttachment(attachment)" />
                                <Button icon="pi pi-download" size="small" outlined
                                    @click="downloadAttachment(attachment)" />
                                <Button icon="pi pi-trash" size="small" severity="danger" outlined
                                    @click="deleteAttachment(attachment)" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload New Attachment -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Upload New Attachment</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attachment Type *</label>
                            <Select v-model="attachmentForm.attachment_type" :options="attachmentTypes"
                                optionLabel="label" optionValue="value" placeholder="Select type" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File (PDF or Image) *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (Max 10MB)</p>
                        </div>
                        <div v-if="attachmentForm.file">
                            <p class="text-sm text-gray-700">Selected: <span class="font-medium">{{
                                attachmentForm.file.name
                                    }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeAttachmentsModal" />
                <Button label="Upload" @click="uploadAttachment" :loading="uploading"
                    :disabled="!attachmentForm.file || !attachmentForm.attachment_type" />
            </template>
        </Dialog>

        <!-- View Attachment Modal -->
        <Dialog v-model:visible="showViewerModal" modal :header="viewerAttachment?.file_name"
            :style="{ width: '80vw', maxWidth: '1200px' }" :maximizable="true">
            <div class="flex items-center justify-center bg-gray-100 rounded" style="min-height: 500px;">
                <!-- PDF Viewer -->
                <iframe v-if="viewerAttachment && viewerAttachment.file_type?.includes('pdf')"
                    :src="getAttachmentUrl(viewerAttachment)" class="w-full h-full rounded" style="min-height: 600px;"
                    frameborder="0">
                </iframe>

                <!-- Image Viewer -->
                <img v-else-if="viewerAttachment && viewerAttachment.file_type?.includes('image')"
                    :src="getAttachmentUrl(viewerAttachment)" :alt="viewerAttachment.file_name"
                    class="max-w-full max-h-[600px] object-contain rounded" />

                <!-- Fallback -->
                <div v-else class="text-center p-8">
                    <i class="pi pi-file text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">Unable to preview this file type</p>
                    <Button label="Download Instead" icon="pi pi-download" class="mt-4"
                        @click="downloadAttachment(viewerAttachment)" />
                </div>
            </div>

            <template #footer>
                <Button label="Download" icon="pi pi-download" @click="downloadAttachment(viewerAttachment)" />
                <Button label="Close" severity="secondary" @click="showViewerModal = false" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import Button from 'primevue/button';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Chip from 'primevue/chip';
import TermSelect from '@/Components/selects/TermSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

const props = defineProps({
    profileId: [Number, String],
});

// State
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const uploading = ref(false);
const disbursements = ref([]);
const showAddModal = ref(false);
const showChequeModal = ref(false);
const showDeleteDialog = ref(false);
const showAttachmentsModal = ref(false);
const showViewerModal = ref(false);
const editMode = ref(false);
const chequeEditMode = ref(false);
const selectedDisbursement = ref(null);
const viewerAttachment = ref(null);
const fileInput = ref(null);

// Form data
const form = ref({
    disbursement_type: null,
    payee: '',
    obr_no: '',
    obr_status: null,
    date_obligated: null,
    year_level: '',
    semester: null,
    academic_year: '',
    amount: '',
    remarks: '',
});

const chequeForm = ref({
    cheque_no: '',
    date_released: null,
    remarks: '',
});

const attachmentForm = ref({
    attachment_type: null,
    file: null,
});

// Options
const disbursementTypes = [
    { label: 'Regular', value: 'regular' },
    { label: 'Reimbursement', value: 'reimbursement' },
    { label: 'Financial Assistance', value: 'financial_assistance' },
];

const attachmentTypes = [
    { label: 'Voucher', value: 'voucher' },
    { label: 'Cheque', value: 'cheque' },
];

const obrStatusOptions = [
    { label: 'LOA', value: 'LOA' },
    { label: 'IRREGULAR', value: 'IRREGULAR' },
    { label: 'TRANSFERRED', value: 'TRANSFERRED' },
    { label: 'CLAIMED', value: 'CLAIMED' },
    { label: 'PAID', value: 'PAID' },
    { label: 'ON PROCESS', value: 'ON PROCESS' },
    { label: 'DENIED', value: 'DENIED' },
];

// Methods
const loadDisbursements = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('disbursements.index', props.profileId));
        disbursements.value = response.data;
    } catch (error) {
        console.error('Error loading disbursements:', error);
        toast.error('Failed to load disbursements');
    } finally {
        loading.value = false;
    }
};

const saveDisbursement = async () => {
    // Check if OBR Status exempts Type, Payee, and Amount requirement
    const exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
    const isExempt = exemptStatuses.includes(form.value.obr_status);

    // Validate required fields based on OBR Status
    if (!isExempt) {
        if (!form.value.disbursement_type || !form.value.payee || !form.value.amount) {
            toast.error('Please fill in Type, Payee, and Amount fields');
            return;
        }
    }

    saving.value = true;
    try {
        const data = {
            ...form.value,
            profile_id: props.profileId,
            // Extract string values from select components if they return objects
            year_level: typeof form.value.year_level === 'object' ? form.value.year_level?.value : form.value.year_level,
            semester: typeof form.value.semester === 'object' ? form.value.semester?.value : form.value.semester,
            academic_year: typeof form.value.academic_year === 'object' ? form.value.academic_year?.value : form.value.academic_year,
        };

        if (editMode.value) {
            await axios.put(route('disbursements.update', selectedDisbursement.value.disbursement_id), data);
            toast.success('Disbursement updated successfully');
        } else {
            await axios.post(route('disbursements.store'), data);
            toast.success('Disbursement created successfully');
        }

        closeModal();
        loadDisbursements();
    } catch (error) {
        console.error('Error saving disbursement:', error);
        toast.error('Failed to save disbursement');
    } finally {
        saving.value = false;
    }
};

const editDisbursement = (disbursement) => {
    editMode.value = true;
    selectedDisbursement.value = disbursement;
    form.value = {
        disbursement_type: disbursement.disbursement_type,
        payee: disbursement.payee,
        obr_no: disbursement.obr_no || '',
        date_obligated: disbursement.date_obligated ? new Date(disbursement.date_obligated) : null,
        year_level: disbursement.year_level || '',
        semester: disbursement.semester || null,
        academic_year: disbursement.academic_year || '',
        amount: parseFloat(disbursement.amount) || 0,
        remarks: disbursement.remarks || '',
    };
    showAddModal.value = true;
};

const manageCheque = (disbursement) => {
    selectedDisbursement.value = disbursement;
    if (disbursement.cheques && disbursement.cheques.length > 0) {
        const cheque = disbursement.cheques[0];
        chequeEditMode.value = true;
        chequeForm.value = {
            cheque_no: cheque.cheque_no,
            date_released: cheque.date_released ? new Date(cheque.date_released) : null,
            remarks: cheque.remarks || '',
        };
    } else {
        chequeEditMode.value = false;
        chequeForm.value = {
            cheque_no: '',
            date_released: null,
            remarks: '',
        };
    }
    showChequeModal.value = true;
};

const saveCheque = async () => {
    if (!chequeForm.value.cheque_no) {
        toast.error('Please fill in cheque number');
        return;
    }

    saving.value = true;
    try {
        if (chequeEditMode.value) {
            const chequeId = selectedDisbursement.value.cheques[0].cheque_id;
            await axios.put(route('cheques.update', chequeId), chequeForm.value);
            toast.success('Cheque updated successfully');
        } else {
            await axios.post(route('disbursements.cheques.store', selectedDisbursement.value.disbursement_id), chequeForm.value);
            toast.success('Cheque added successfully');
        }

        showChequeModal.value = false;
        loadDisbursements();
    } catch (error) {
        console.error('Error saving cheque:', error);
        toast.error('Failed to save cheque');
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (disbursement) => {
    selectedDisbursement.value = disbursement;
    showDeleteDialog.value = true;
};

const deleteDisbursement = async () => {
    deleting.value = true;
    try {
        await axios.delete(route('disbursements.destroy', selectedDisbursement.value.disbursement_id));
        toast.success('Disbursement deleted successfully');
        showDeleteDialog.value = false;
        loadDisbursements();
    } catch (error) {
        console.error('Error deleting disbursement:', error);
        toast.error('Failed to delete disbursement');
    } finally {
        deleting.value = false;
    }
};

const closeModal = () => {
    showAddModal.value = false;
    editMode.value = false;
    selectedDisbursement.value = null;
    form.value = {
        disbursement_type: null,
        payee: '',
        obr_no: '',
        date_obligated: null,
        year_level: '',
        semester: null,
        academic_year: '',
        amount: '',
        remarks: '',
    };
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
    if (!amount) return '';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const formatDisbursementType = (type) => {
    const types = {
        'regular': 'Regular',
        'reimbursement': 'Reimbursement',
        'financial_assistance': 'Financial Assistance',
    };
    return types[type] || type;
};

const getDisbursementTypeClass = (type) => {
    const classes = {
        'regular': 'bg-blue-100 text-blue-800',
        'reimbursement': 'bg-orange-100 text-orange-800',
        'financial_assistance': 'bg-purple-100 text-purple-800',
    };
    return classes[type] || '';
};

const getChequeStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'released': 'bg-blue-100 text-blue-800',
        'cleared': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
        'bounced': 'bg-red-100 text-red-800',
    };
    return classes[status] || '';
};

const getObrStatusClass = (status) => {
    const classes = {
        'LOA': 'bg-orange-100 text-orange-800',
        'IRREGULAR': 'bg-yellow-100 text-yellow-800',
        'TRANSFERRED': 'bg-blue-100 text-blue-800',
        'CLAIMED': 'bg-purple-100 text-purple-800',
        'PAID': 'bg-green-100 text-green-800',
        'ON PROCESS': 'bg-cyan-100 text-cyan-800',
        'DENIED': 'bg-red-100 text-red-800',
    };
    return classes[status] || '';
};

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'pi pi-file-pdf';
    if (fileType?.includes('image')) return 'pi pi-image';
    return 'pi pi-file';
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const manageAttachments = (disbursement) => {
    selectedDisbursement.value = disbursement;
    attachmentForm.value = {
        attachment_type: null,
        file: null,
    };
    showAttachmentsModal.value = true;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            toast.error('File size must be less than 10MB');
            event.target.value = '';
            return;
        }
        attachmentForm.value.file = file;
    }
};

const uploadAttachment = async () => {
    if (!attachmentForm.value.file || !attachmentForm.value.attachment_type) {
        toast.error('Please select both attachment type and file');
        return;
    }

    uploading.value = true;
    try {
        const formData = new FormData();
        formData.append('attachment_type', attachmentForm.value.attachment_type);
        formData.append('file', attachmentForm.value.file);

        await axios.post(
            route('disbursements.attachments.upload', selectedDisbursement.value.disbursement_id),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        );

        toast.success('Attachment uploaded successfully');

        // Reset form
        attachmentForm.value = {
            attachment_type: null,
            file: null,
        };
        if (fileInput.value) {
            fileInput.value.value = '';
        }

        loadDisbursements();
    } catch (error) {
        console.error('Error uploading attachment:', error);
        toast.error('Failed to upload attachment');
    } finally {
        uploading.value = false;
    }
};

const viewAttachment = (attachment) => {
    viewerAttachment.value = attachment;
    showViewerModal.value = true;
};

const getAttachmentUrl = (attachment) => {
    if (!attachment) return '';
    // Use the view route for proper access control
    return route('disbursements.attachments.view', attachment.attachment_id);
};

const downloadAttachment = async (attachment) => {
    try {
        window.open(route('disbursements.attachments.download', attachment.attachment_id), '_blank');
    } catch (error) {
        console.error('Error downloading attachment:', error);
        toast.error('Failed to download attachment');
    }
};

const deleteAttachment = async (attachment) => {
    if (!confirm('Are you sure you want to delete this attachment?')) {
        return;
    }

    try {
        await axios.delete(route('disbursements.attachments.delete', attachment.attachment_id));
        toast.success('Attachment deleted successfully');
        loadDisbursements();
    } catch (error) {
        console.error('Error deleting attachment:', error);
        toast.error('Failed to delete attachment');
    }
};

const closeAttachmentsModal = () => {
    showAttachmentsModal.value = false;
    selectedDisbursement.value = null;
    attachmentForm.value = {
        attachment_type: null,
        file: null,
    };
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Load data on mount
onMounted(() => {
    loadDisbursements();
});
</script>
