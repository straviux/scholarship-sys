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
        <DataTable :value="disbursements" :loading="loading" stripedRows showGridlines>
            <template #empty>
                <div class="text-center py-12">
                    <i class="pi pi-money-bill text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No disbursements found</p>
                    <Button label="Add First Disbursement" class="mt-4" @click="showAddModal = true" />
                </div>
            </template>

            <Column field="disbursement_type" header="Type" style="min-width: 140px">
                <template #body="slotProps">
                    <Chip :label="formatDisbursementType(slotProps.data.disbursement_type)"
                        :class="getDisbursementTypeClass(slotProps.data.disbursement_type)" />
                </template>
            </Column>

            <Column field="payee" header="Payee" style="min-width: 180px" />
            <Column field="obr_no" header="OBR No." style="min-width: 120px" />
            <Column field="date_obligated" header="Date Obligated" style="min-width: 140px">
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.date_obligated) }}
                </template>
            </Column>
            <Column field="year_level" header="Year Level" style="min-width: 120px" />
            <Column field="semester" header="Semester" style="min-width: 120px" />
            <Column field="academic_year" header="Academic Year" style="min-width: 140px" />
            <Column field="amount" header="Amount" style="min-width: 120px">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.amount) }}
                </template>
            </Column>

            <Column header="Cheque No." style="min-width: 140px">
                <template #body="slotProps">
                    <span v-if="slotProps.data.cheques && slotProps.data.cheques.length > 0">
                        {{ slotProps.data.cheques[0].cheque_no }}
                    </span>
                    <span v-else class="text-gray-400 text-sm">-</span>
                </template>
            </Column>

            <Column header="Cheque Status" style="min-width: 140px">
                <template #body="slotProps">
                    <Chip v-if="slotProps.data.cheques && slotProps.data.cheques.length > 0"
                        :label="slotProps.data.cheques[0].status"
                        :class="getChequeStatusClass(slotProps.data.cheques[0].status)" />
                    <span v-else class="text-gray-400 text-sm">No cheque</span>
                </template>
            </Column>

            <Column header="Actions" style="min-width: 180px">
                <template #body="slotProps">
                    <div class="flex gap-2">
                        <Button icon="pi pi-file" size="small" severity="info" outlined rounded
                            v-tooltip.top="'Manage Cheque'" @click="manageCheque(slotProps.data)" />
                        <Button icon="pi pi-pencil" size="small" severity="warning" outlined rounded
                            v-tooltip.top="'Edit'" @click="editDisbursement(slotProps.data)" />
                        <Button icon="pi pi-trash" size="small" severity="danger" outlined rounded
                            v-tooltip.top="'Delete'" @click="confirmDelete(slotProps.data)" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Add/Edit Disbursement Modal -->
        <Dialog v-model:visible="showAddModal" modal :header="editMode ? 'Edit Disbursement' : 'Add Disbursement'"
            :style="{ width: '50vw' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <Select v-model="form.disbursement_type" :options="disbursementTypes" optionLabel="label"
                            optionValue="value" placeholder="Select type" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payee *</label>
                        <InputText v-model="form.payee" placeholder="Enter payee name" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OBR No.</label>
                        <InputText v-model="form.obr_no" placeholder="Enter OBR number" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Obligated</label>
                        <DatePicker v-model="form.date_obligated" dateFormat="mm/dd/yy" placeholder="Select date"
                            class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                        <InputText v-model="form.year_level" placeholder="e.g. 1st Year" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <Select v-model="form.semester" :options="semesterOptions" optionLabel="label"
                            optionValue="value" placeholder="Select semester" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                        <InputText v-model="form.academic_year" placeholder="e.g. 2024-2025" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <Select v-model="chequeForm.status" :options="chequeStatuses" optionLabel="label"
                        optionValue="value" placeholder="Select status" class="w-full" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Issued</label>
                        <DatePicker v-model="chequeForm.date_issued" dateFormat="mm/dd/yy" placeholder="Select date"
                            class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Released</label>
                        <DatePicker v-model="chequeForm.date_released" dateFormat="mm/dd/yy" placeholder="Select date"
                            class="w-full" />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Cleared</label>
                        <DatePicker v-model="chequeForm.date_cleared" dateFormat="mm/dd/yy" placeholder="Select date"
                            class="w-full" />
                    </div>
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Chip from 'primevue/chip';

const props = defineProps({
    profileId: [Number, String],
});

// State
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const disbursements = ref([]);
const showAddModal = ref(false);
const showChequeModal = ref(false);
const showDeleteDialog = ref(false);
const editMode = ref(false);
const chequeEditMode = ref(false);
const selectedDisbursement = ref(null);

// Form data
const form = ref({
    disbursement_type: null,
    payee: '',
    obr_no: '',
    date_obligated: null,
    year_level: '',
    semester: null,
    academic_year: '',
    amount: 0,
    remarks: '',
});

const chequeForm = ref({
    cheque_no: '',
    status: 'pending',
    date_issued: null,
    date_released: null,
    date_cleared: null,
    remarks: '',
});

// Options
const disbursementTypes = [
    { label: 'Regular', value: 'regular' },
    { label: 'Reimbursement', value: 'reimbursement' },
    { label: 'Financial Assistance', value: 'financial_assistance' },
];

const semesterOptions = [
    { label: '1st Semester', value: '1st Semester' },
    { label: '2nd Semester', value: '2nd Semester' },
    { label: 'Summer', value: 'Summer' },
];

const chequeStatuses = [
    { label: 'Pending', value: 'pending' },
    { label: 'Released', value: 'released' },
    { label: 'Cleared', value: 'cleared' },
    { label: 'Cancelled', value: 'cancelled' },
    { label: 'Bounced', value: 'bounced' },
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
    if (!form.value.disbursement_type || !form.value.payee || !form.value.amount) {
        toast.error('Please fill in all required fields');
        return;
    }

    saving.value = true;
    try {
        const data = {
            ...form.value,
            profile_id: props.profileId,
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
            status: cheque.status,
            date_issued: cheque.date_issued ? new Date(cheque.date_issued) : null,
            date_released: cheque.date_released ? new Date(cheque.date_released) : null,
            date_cleared: cheque.date_cleared ? new Date(cheque.date_cleared) : null,
            remarks: cheque.remarks || '',
        };
    } else {
        chequeEditMode.value = false;
        chequeForm.value = {
            cheque_no: '',
            status: 'pending',
            date_issued: null,
            date_released: null,
            date_cleared: null,
            remarks: '',
        };
    }
    showChequeModal.value = true;
};

const saveCheque = async () => {
    if (!chequeForm.value.cheque_no || !chequeForm.value.status) {
        toast.error('Please fill in all required fields');
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
        amount: 0,
        remarks: '',
    };
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
    if (!amount) return '₱0.00';
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

// Load data on mount
onMounted(() => {
    loadDisbursements();
});
</script>
