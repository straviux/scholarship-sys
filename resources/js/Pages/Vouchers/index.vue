<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoucherWizard from '@/Components/Obligations/VoucherWizard.vue';
import Dialog from 'primevue/dialog';
import Drawer from 'primevue/drawer';
import Button from 'primevue/button';
import ContextMenu from 'primevue/contextmenu';
import { useToast } from 'primevue/usetoast';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import axios from 'axios';

const toast = useToast();

const page = usePage();
const showWizard = ref(false);
const currentStep = ref(1);
const voucherType = ref('obligations');
const selectedScholars = ref([]);
const vouchers = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const showDeleteConfirmDialog = ref(false);
const voucherToDelete = ref(null);
const searchQuery = ref('');
const showViewDialog = ref(false);
const selectedVoucher = ref(null);
const scholarsDetails = ref([]);
const loadingScholars = ref(false);
const showEditDialog = ref(false);
const editStep = ref(1);
const editingId = ref(null);
const editFormData = ref(null);
const editScholars = ref([]);
const editSearchQuery = ref('');
const editSelectAll = ref(false);
const editSearchLoading = ref(false);
const editLoading = ref(false);
const showEditPreviewDrawer = ref(false);
const responsibilityCenters = ref([]);
const contextMenu = ref();
const showRemarksDialog = ref(false);
const selectedVoucherForRemarks = ref(null);
const remarksForm = reactive({
    remarks: ''
});
const savingRemarks = ref(false);
const contextMenuItems = ref([]);
const quillToolbar = [
    [{ align: [] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link']
];

const handleCreateVoucher = () => {
    showWizard.value = true;
    currentStep.value = 1;
    selectedScholars.value = [];
    voucherType.value = 'obligations';
};

const handleWizardClose = () => {
    showWizard.value = false;
    currentStep.value = 1;
    selectedScholars.value = [];
    fetchVouchers();
};

const handleScholarSelection = (scholars, type) => {
    selectedScholars.value = scholars;
    voucherType.value = type;
};

// Fetch vouchers from API
const fetchVouchers = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/vouchers');
        vouchers.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching vouchers:', error);
        vouchers.value = [];
    } finally {
        loading.value = false;
    }
};

// Computed stats
const totalVouchers = computed(() => vouchers.value.length);
const filteredVouchers = computed(() => {
    if (!searchQuery.value.trim()) {
        return vouchers.value;
    }

    const search = searchQuery.value.toLowerCase();
    return vouchers.value.filter(v =>
        v.voucher_number?.toLowerCase().includes(search) ||
        v.payee_name?.toLowerCase().includes(search) ||
        v.voucher_type?.toLowerCase().includes(search) ||
        v.creator?.name?.toLowerCase().includes(search)
    );
});
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Roles is an array of strings like ['administrator']
    return user.roles?.includes('administrator') ?? false;
});

// Delete voucher
const deleteVoucher = (voucherId) => {
    voucherToDelete.value = voucherId;
    showDeleteConfirmDialog.value = true;
};

// View voucher
const viewVoucher = async (voucherId) => {
    const voucher = vouchers.value.find(v => v.id === voucherId);
    if (voucher) {
        selectedVoucher.value = voucher;
        showViewDialog.value = true;

        // Fetch scholar details
        await fetchScholarsDetails(voucher.scholar_ids || []);
    }
};

// Edit voucher
const editVoucher = async (voucherId) => {
    try {
        // Fetch fresh voucher data from API to ensure we have all fields
        const response = await axios.get(`/api/vouchers/${voucherId}`);
        const voucher = response.data.data;

        // Ensure all fields are properly initialized
        editFormData.value = {
            ...voucher,
            responsibility_center: voucher.responsibility_center || ''
        };
        editStep.value = 1;
        editSearchQuery.value = '';
        editSelectAll.value = false;
        showEditDialog.value = true;
        showEditPreviewDrawer.value = true;

        // Wait for Vue to update the DOM before fetching scholars
        await nextTick();
        await fetchScholarsForEdit();
    } catch (error) {
        console.error('Error fetching voucher:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load voucher data',
            life: 3000
        });
    }
};

// Save edited voucher
const saveVoucher = async () => {
    if (!editFormData.value) return;

    editingId.value = editFormData.value.id;
    try {
        await axios.put(`/api/vouchers/${editFormData.value.id}`, editFormData.value);

        // Update the voucher in the list
        const index = vouchers.value.findIndex(v => v.id === editFormData.value.id);
        if (index !== -1) {
            vouchers.value[index] = editFormData.value;
        }

        showEditDialog.value = false;
        editFormData.value = null;
        editStep.value = 1;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Voucher updated successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error updating voucher:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update voucher: ' + errorMsg,
            life: 5000
        });
    } finally {
        editingId.value = null;
    }
};

// Navigate edit steps
const nextEditStep = () => {
    if (editStep.value < 4) {
        editStep.value++;
    }
};

const prevEditStep = () => {
    if (editStep.value > 1) {
        editStep.value--;
    }
};

// Cancel edit
const cancelEdit = () => {
    showEditDialog.value = false;
    showEditPreviewDrawer.value = false;
    editFormData.value = null;
    editingId.value = null;
    editStep.value = 1;
    editScholars.value = [];
    editSearchQuery.value = '';
    editSelectAll.value = false;
};

// Fetch scholars for editing
const fetchScholarsForEdit = async () => {
    editLoading.value = true;
    try {
        const response = await axios.get('/api/scholars');
        const data = Array.isArray(response.data) ? response.data : (response.data.data || []);
        if (data && data.length > 0) {
            editScholars.value = data.map(s => ({
                ...s,
                selected: editFormData.value?.scholar_ids?.some(sid =>
                    (typeof sid === 'object' ? sid.profile_id : sid) === s.profile_id
                ) || false
            }));
        }
    } catch (error) {
        console.error('Error fetching scholars:', error);
    } finally {
        editLoading.value = false;
    }
};

// Filter scholars for edit search
const filteredEditScholars = computed(() => {
    if (!editSearchQuery.value.trim()) {
        return editScholars.value;
    }
    const search = editSearchQuery.value.toLowerCase();
    return editScholars.value.filter(s =>
        (s.first_name?.toLowerCase() || '').includes(search) ||
        (s.last_name?.toLowerCase() || '').includes(search) ||
        (s.middle_name?.toLowerCase() || '').includes(search) ||
        (s.email?.toLowerCase() || '').includes(search)
    );
});

// Toggle select all for edit
const toggleEditSelectAll = () => {
    editSelectAll.value = !editSelectAll.value;
    filteredEditScholars.value.forEach(s => {
        s.selected = editSelectAll.value;
    });
    updateEditSelectedScholars();
};

// Count selected scholars for edit
const editSelectedCount = computed(() => {
    return editScholars.value.filter(s => s.selected).length;
});

// Update edit form with selected scholars
const updateEditSelectedScholars = () => {
    const selected = editScholars.value.filter(s => s.selected);
    editFormData.value.scholar_ids = selected.map(s => ({
        profile_id: s.profile_id
    }));
};

// Remove scholar from edit
const removeEditScholar = (profileId) => {
    const scholar = editScholars.value.find(s => s.profile_id === profileId);
    if (scholar) {
        scholar.selected = false;
        updateEditSelectedScholars();
    }
};

// Fetch voucher details for editing
const fetchVoucherDetails = async (voucherId) => {
    const voucher = vouchers.value.find(v => v.id === voucherId);
    if (voucher) {
        editFormData.value = { ...voucher };
    }
};

// Fetch scholar details by profile IDs
const fetchScholarsDetails = async (scholarIds) => {
    if (!scholarIds || scholarIds.length === 0) {
        scholarsDetails.value = [];
        return;
    }

    loadingScholars.value = true;
    try {
        const scholars = [];
        // Fetch each scholar's details
        for (const scholar of scholarIds) {
            try {
                // Extract profile_id - handle both old format (string) and new format (object)
                const profileId = typeof scholar === 'object' ? scholar.profile_id : scholar;
                const response = await axios.get(`/api/scholarships/profile/${profileId}`);
                if (response.data.data) {
                    scholars.push(response.data.data);
                }
            } catch (error) {
                console.error(`Error fetching scholar details:`, error);
            }
        }
        scholarsDetails.value = scholars;
    } catch (error) {
        console.error('Error fetching scholars:', error);
        scholarsDetails.value = [];
    } finally {
        loadingScholars.value = false;
    }
};

// Generate document
const generateDocument = async (docType) => {
    if (!selectedVoucher.value) return;

    try {
        let url = '';
        let fileType = '';

        if (docType === 'OBR') {
            url = `/api/vouchers/${selectedVoucher.value.id}/obr-pdf`;
            fileType = 'pdf';
        } else if (docType === 'DV') {
            url = `/api/vouchers/${selectedVoucher.value.id}/dv-pdf`;
            fileType = 'pdf';
        } else if (docType === 'PR') {
            url = `/api/vouchers/${selectedVoucher.value.id}/payroll-pdf`;
            fileType = 'pdf';
        } else if (docType === 'LOS') {
            url = `/api/vouchers/${selectedVoucher.value.id}/list-of-scholars-pdf`;
            fileType = 'pdf';
        }

        // Show loading toast
        toast.add({
            severity: 'info',
            summary: 'Generating',
            detail: `${docType} document generation in progress...`,
            life: 2000
        });

        // Open the file in a new tab
        window.open(url, '_blank');

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${docType} document generated successfully`,
            life: 3000
        });
    } catch (error) {
        console.error(`Error generating ${docType}:`, error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to generate ${docType} document`,
            life: 5000
        });
    }
};

// Confirm delete
const confirmDelete = async () => {
    if (!voucherToDelete.value) return;

    deletingId.value = voucherToDelete.value;
    try {
        await axios.delete(`/api/vouchers/${voucherToDelete.value}`);
        vouchers.value = vouchers.value.filter(v => v.id !== voucherToDelete.value);
        showDeleteConfirmDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Voucher deleted successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error deleting voucher:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete voucher: ' + errorMsg,
            life: 5000
        });
    } finally {
        deletingId.value = null;
        voucherToDelete.value = null;
    }
};

// Context Menu
const openContextMenu = (event, voucher) => {
    event.preventDefault();
    const items = [
        {
            label: 'View',
            icon: 'pi pi-eye',
            command: () => viewVoucher(voucher.id)
        },
        {
            label: 'Edit',
            icon: 'pi pi-pencil',
            command: () => editVoucher(voucher.id)
        },
        {
            label: 'Add/Edit Remarks',
            icon: 'pi pi-comment',
            command: () => openRemarksModal(voucher)
        }
    ];

    if (isAdmin.value) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Delete',
            icon: 'pi pi-trash',
            command: () => deleteVoucher(voucher.id),
            class: 'p-menuitem-danger'
        });
    }

    contextMenuItems.value = items;
    contextMenu.value.show(event);
};

// Open remarks modal
const openRemarksModal = (voucher) => {
    selectedVoucherForRemarks.value = voucher;
    remarksForm.remarks = voucher.remarks || '';
    showRemarksDialog.value = true;
};

// Save remarks
const saveRemarks = async () => {
    if (!selectedVoucherForRemarks.value) return;

    savingRemarks.value = true;
    try {
        // GET the current voucher data
        const currentVoucher = await axios.get(`/api/vouchers/${selectedVoucherForRemarks.value.id}`);
        const voucherData = currentVoucher.data.data;

        // PUT with all required fields plus updated remarks
        await axios.put(`/api/vouchers/${selectedVoucherForRemarks.value.id}`, {
            voucher_type: voucherData.voucher_type,
            explanation: voucherData.explanation,
            payee_type: voucherData.payee_type,
            payee_name: voucherData.payee_name,
            payee_address: voucherData.payee_address,
            responsibility_center: voucherData.responsibility_center,
            account_code: voucherData.account_code,
            particulars_name: voucherData.particulars_name,
            particulars_description: voucherData.particulars_description,
            amount: voucherData.amount,
            obr_type: voucherData.obr_type,
            scholar_ids: voucherData.scholar_ids,
            notes: voucherData.notes,
            remarks: remarksForm.remarks
        });

        // Update the voucher in the list
        const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForRemarks.value.id);
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].remarks = remarksForm.remarks;
        }

        showRemarksDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Remarks saved successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error saving remarks:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save remarks: ' + errorMsg,
            life: 5000
        });
    } finally {
        savingRemarks.value = false;
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '---';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount || 0);
};

// Calculate total amount (number of scholars * amount)
const calculateTotalAmount = (voucher) => {
    if (!voucher) return 0;
    const scholarsCount = voucher.scholar_ids?.length || 0;
    return (scholarsCount * (voucher.amount || 0));
};

// Get document button label based on voucher type
const getDocumentButtonLabel = () => {
    if (!selectedVoucher.value) return 'Document';
    return selectedVoucher.value.voucher_type === 'payroll' ? 'PR' : 'DV';
};

// Get document type to generate based on voucher type
const getDocumentType = () => {
    if (!selectedVoucher.value) return 'DV';
    return selectedVoucher.value.voucher_type === 'payroll' ? 'PR' : 'DV';
};

// Fetch responsibility centers and particulars
const fetchResponsibilityCentersAndParticulars = async () => {
    try {
        const response = await axios.get('/api/responsibility-centers');
        if (response.data && response.data.data) {
            responsibilityCenters.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching responsibility centers:', error);
    }
};

// Watch for edit step changes to auto-show drawer on step 1 and 2
watch(
    () => editStep.value,
    (newStep) => {
        if (newStep === 1 || newStep === 2) {
            showEditPreviewDrawer.value = true;
        } else {
            showEditPreviewDrawer.value = false;
        }
    }
);

// Watch for editFormData changes to ensure responsibility center is properly set
watch(
    () => editFormData.value?.responsibility_center,
    (newValue) => {
        if (editFormData.value && newValue !== undefined) {
            // Ensure the value is a string and matches one of the options
            const stringValue = String(newValue).trim();
            const validOption = responsibilityCenters.value.find(rc => rc.code === stringValue);
            if (validOption) {
                editFormData.value.responsibility_center = validOption.code;
            }
        }
    }
);

// Fetch on mount
onMounted(() => {
    fetchVouchers();
    fetchResponsibilityCentersAndParticulars();
});
</script>

<template>

    <Head title="Vouchers" />

    <AdminLayout>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Vouchers Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Create and manage obligations and vouchers for scholars
                        disbursements
                        and payroll</p>
                </div>
                <button @click="handleCreateVoucher"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                    <i class="pi pi-plus mr-2 text-sm"></i>
                    <span>Create Voucher</span>
                </button>
            </div>

            <!-- List/Summary Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between gap-4 mb-4 flex-col sm:flex-row">
                    <div class="flex-1 w-full">
                        <input v-model="searchQuery" type="text"
                            placeholder="Search by voucher #, payee, type, or creator..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="fetchVouchers" :disabled="loading"
                            class="inline-flex items-center px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 disabled:opacity-50 whitespace-nowrap">
                            <i class="pi pi-refresh mr-2" :class="{ 'animate-spin': loading }"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full divide-y divide-gray-200 px-6">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Voucher #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payee</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    OBR Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Amount</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created By</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="loading" class="hover:bg-gray-50">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="pi pi-spin pi-spinner mr-2"></i> Loading vouchers...
                                </td>
                            </tr>
                            <tr v-else-if="vouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No vouchers created yet.</p>
                                    <p class="text-xs text-gray-400 mt-1">Click the "Create Voucher" button to get
                                        started</p>
                                </td>
                            </tr>
                            <tr v-else-if="filteredVouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No vouchers match your search.</p>
                                    <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria</p>
                                </td>
                            </tr>
                            <tr v-for="voucher in filteredVouchers" :key="voucher.id"
                                class="hover:bg-gray-50 transition"
                                @contextmenu.prevent="openContextMenu($event, voucher)">
                                <td class="px-6 py-4 text-sm font-medium text-blue-600">{{ voucher.voucher_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span :class="{
                                        'px-3 py-1 rounded-full text-xs font-medium': true,
                                        'bg-blue-100 text-blue-800': voucher.voucher_type === 'disbursements',
                                        'bg-green-100 text-green-800': voucher.voucher_type === 'payroll'
                                    }">
                                        {{ voucher.voucher_type === 'disbursements' ? 'Disbursement Voucher' :
                                            (voucher.voucher_type === 'payroll' ? 'Payroll' : voucher.voucher_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ voucher.payee_name }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="{
                                        'px-3 py-1 rounded-full text-xs font-medium': true,
                                        'bg-gray-100 text-gray-800': voucher.obr_type === 'REGULAR',
                                        'bg-yellow-100 text-yellow-800': voucher.obr_type === 'FINANCIAL ASSISTANCE',
                                        'bg-red-100 text-red-800': voucher.obr_type === 'REIMBURSEMENT'
                                    }">
                                        {{ voucher.obr_type || '---' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{
                                    formatAmount(calculateTotalAmount(voucher))
                                }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ voucher.creator?.name || '---' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(voucher.created_at) }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <button @click="(e) => openContextMenu(e, voucher)"
                                        class="text-gray-500 hover:text-gray-700 font-medium text-lg cursor-pointer"
                                        title="Actions">
                                        <i class="pi pi-ellipsis-v"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Voucher Creation Wizard -->
        <VoucherWizard v-if="showWizard" @close="handleWizardClose" @scholar-selected="handleScholarSelection" />

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteConfirmDialog" modal header="Confirm Delete" :style="{ width: '500px' }">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Delete Voucher</p>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-3">
                    <p class="text-sm text-red-800">
                        <strong>Voucher #:</strong> {{vouchers.find(v => v.id === voucherToDelete)?.voucher_number ||
                            'N/A'}}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteConfirmDialog = false" outlined />
                <Button label="Delete" severity="danger" @click="confirmDelete"
                    :loading="deletingId === voucherToDelete" />
            </template>
        </Dialog>

        <!-- View Voucher Dialog -->
        <Dialog v-model:visible="showViewDialog" modal header="Voucher Details" :style="{ width: '700px' }">
            <div v-if="selectedVoucher" class="space-y-4">
                <!-- Voucher Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Voucher Number</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.voucher_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Disbursement Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.voucher_type === 'disbursements'
                            ?
                            'Disbursement Voucher' : (selectedVoucher.voucher_type === 'payroll' ? 'Payroll' :
                                selectedVoucher.voucher_type) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Payee</p>
                        <p class="text-sm text-gray-900">{{ selectedVoucher.payee_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Amount</p>
                        <p class="text-sm text-gray-900 font-medium">{{ formatAmount(selectedVoucher.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Created By</p>
                        <p class="text-sm text-gray-900">{{ selectedVoucher.creator?.name || '---' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Date</p>
                        <p class="text-sm text-gray-900">{{ formatDate(selectedVoucher.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">OBR Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.obr_type || '---' }}</p>
                    </div>
                </div>

                <!-- Remarks -->
                <div v-if="selectedVoucher.remarks" class=" border rounded p-3">
                    <p class="text-xs font-semibolduppercase mb-2">Remarks</p>
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ selectedVoucher.remarks }}</p>
                </div>

                <!-- Scholars List -->
                <div class="bg-white border border-gray-200 rounded p-4">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Scholars ({{ selectedVoucher.scholar_ids?.length
                        || 0
                        }})</p>
                    <div v-if="loadingScholars" class="text-center py-2">
                        <i class="pi pi-spin pi-spinner mr-2 text-xs"></i> <span class="text-xs">Loading...</span>
                    </div>
                    <div v-else-if="scholarsDetails && scholarsDetails.length > 0"
                        class="space-y-1 max-h-48 overflow-y-auto">
                        <div v-for="(scholar, index) in scholarsDetails" :key="index"
                            class="text-xs text-gray-700 py-1 px-2 bg-gray-50 rounded flex items-center justify-between gap-2">
                            <span class="font-medium">{{ index + 1 }}. {{ scholar.first_name }} {{ scholar.last_name
                                }}</span>
                            <span class="text-gray-600 whitespace-nowrap">
                                <span v-if="scholar.course_name">{{ scholar.course_name }}</span>
                                <span v-if="scholar.year_level" class="ml-1">| {{
                                    /^(1st|2nd|3rd|4th)$/i.test(scholar.year_level) ? scholar.year_level + ' YEAR' :
                                        scholar.year_level
                                }}</span>
                                <span v-if="scholar.academic_year" class="ml-1">| {{ scholar.academic_year
                                    }}</span>
                                <span v-if="scholar.term" class="ml-1">| {{ scholar.term }}</span>
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-500">No scholars</div>
                </div>

                <!-- Total Amount -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-900">Total Amount</p>
                    <p class="text-lg font-bold text-blue-600">{{ formatAmount(calculateTotalAmount(selectedVoucher)) }}
                    </p>
                </div>

                <!-- Generate Section -->
                <div class="border-t pt-4">
                    <p class="text-sm font-semibold text-gray-900 mb-3">Generate</p>
                    <div class="flex gap-2">
                        <Button label="OBR" @click="generateDocument('OBR')" class="flex-1" severity="info">
                            <template #icon>
                                <i class="pi pi-file-pdf"></i>
                            </template>
                        </Button>
                        <Button :label="getDocumentButtonLabel()" @click="generateDocument(getDocumentType())"
                            class="flex-1" severity="success">
                            <template #icon>
                                <i class="pi pi-money-bill"></i>
                            </template>
                        </Button>
                        <Button label="LOS" @click="generateDocument('LOS')" class="flex-1" severity="help">
                            <template #icon>
                                <i class="pi pi-users"></i>
                            </template>
                        </Button>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showViewDialog = false" outlined />
            </template>
        </Dialog>

        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body" />

        <!-- Remarks Dialog -->
        <Dialog v-model:visible="showRemarksDialog" modal header="Add/Edit Remarks" :style="{ width: '600px' }">
            <div v-if="selectedVoucherForRemarks" class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Voucher: {{
                        selectedVoucherForRemarks.voucher_number }}
                    </p>
                    <p class="text-xs text-gray-500">Add or edit remarks for this voucher</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Remarks</label>
                    <textarea v-model="remarksForm.remarks" rows="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="Enter remarks..." />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showRemarksDialog = false" outlined />
                <Button label="Save" severity="success" @click="saveRemarks" :loading="savingRemarks" />
            </template>
        </Dialog>

        <!-- Edit Voucher Dialog (4-Step Wizard) -->
        <Dialog v-model:visible="showEditDialog" modal :header="`Edit Voucher - Step ${editStep} of 4`"
            :style="{ width: '900px' }" @hide="cancelEdit">
            <div v-if="editFormData" class="space-y-6">
                <!-- Progress Bar -->
                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Step {{ editStep }} of 4</span>
                        <span>{{ Math.round((editStep / 4) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: (editStep / 4) * 100 + '%' }"></div>
                    </div>
                </div>

                <!-- Step 1: Scholar Selection -->
                <div v-if="editStep === 1" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-3">
                            Select Scholars
                        </label>

                        <div class="space-y-3">
                            <!-- Info Banner -->
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-900"><i class="pi pi-info-circle mr-2"></i>Only active
                                    scholars are
                                    displayed</p>
                            </div>

                            <!-- Search Input -->
                            <div class="relative">
                                <input v-model="editSearchQuery" type="text" placeholder="Search by name or email..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                <div v-if="editSearchLoading" class="absolute right-3 top-2.5">
                                    <i class="pi pi-spin pi-spinner text-blue-600"></i>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-if="editLoading" class="text-center py-8">
                                <i class="pi pi-spin pi-spinner text-3xl text-blue-600"></i>
                                <p class="mt-2 text-gray-600">Loading scholars...</p>
                            </div>

                            <!-- Select All Checkbox -->
                            <div v-if="!editLoading && editSearchQuery.trim()" class="flex items-center">
                                <input id="edit-select-all" v-model="editSelectAll" type="checkbox"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    @change="toggleEditSelectAll" />
                                <label for="edit-select-all" class="ml-3 text-sm font-medium text-gray-900">
                                    Select All ({{ filteredEditScholars.length }} found)
                                </label>
                            </div>

                            <!-- Scholar List (Only shown when searching) -->
                            <div v-if="!editLoading && editSearchQuery.trim()"
                                class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                                <div v-if="filteredEditScholars.length === 0" class="text-center py-8 text-gray-500">
                                    <p>No scholars match your search</p>
                                </div>
                                <!-- Available scholars -->
                                <div v-if="filteredEditScholars.filter(s => !s.selected).length > 0">
                                    <div v-for="scholar in filteredEditScholars.filter(s => !s.selected)"
                                        :key="scholar.profile_id"
                                        class="flex items-center hover:bg-gray-50 p-2 rounded">
                                        <input :id="`edit-scholar-${scholar.profile_id}`" v-model="scholar.selected"
                                            type="checkbox"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                            @change="updateEditSelectedScholars" />
                                        <label :for="`edit-scholar-${scholar.profile_id}`"
                                            class="ml-3 text-sm text-gray-700 flex-1 cursor-pointer">
                                            <div class="font-medium">{{ scholar.first_name }} {{ scholar.middle_name }}
                                                {{
                                                    scholar.last_name }}</div>
                                            <div class="text-gray-500 text-xs">
                                                <span v-if="scholar.year_level" class="uppercase">{{
                                                    /^(1st|2nd|3rd|4th)$/i.test(scholar.year_level) ? scholar.year_level
                                                        + ' YEAR' : scholar.year_level
                                                }}</span>
                                                <span v-else class="text-red-500">---</span>
                                                {{ scholar.course ? ' | ' + scholar.course : '' }}
                                                {{ scholar.school ? ' | ' + scholar.school : '' }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Search instruction (when no search) -->
                            <div v-if="!editSearchQuery.trim()" class="text-center py-8 text-gray-500">
                                <p class="text-sm">Search for scholars to add them</p>
                            </div>

                            <!-- Selected Count -->
                            <div class="text-sm text-gray-600">
                                {{ editSelectedCount }} scholar(s) selected
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Obligations -->
                <div v-if="editStep === 2" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                        <p class="text-sm text-blue-900"><i class="pi pi-info-circle mr-2"></i>Voucher: <span
                                class="font-semibold">{{ editFormData.voucher_number }}</span></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- OBR Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OBR Type</label>
                            <select v-model="editFormData.obr_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                                <option value="">Select OBR Type</option>
                                <option value="REGULAR">REGULAR</option>
                                <option value="FINANCIAL ASSISTANCE">FINANCIAL ASSISTANCE</option>
                                <option value="REIMBURSEMENT">REIMBURSEMENT</option>
                            </select>
                        </div>

                        <!-- Payee Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payee Type</label>
                            <select v-model="editFormData.payee_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                                <option value="scholar">Scholar</option>
                                <option value="school">School</option>
                                <option value="individual">Individual</option>
                            </select>
                        </div>

                        <!-- Payee Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payee Name</label>
                            <input v-model="editFormData.payee_name" type="text" placeholder="Enter payee name..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>

                        <!-- Payee Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payee Address</label>
                            <input v-model="editFormData.payee_address" type="text" placeholder="Enter payee address..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>

                        <!-- Responsibility Center -->
                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-1">Responsibility Center</label>
                            <select :key="`rc-${editFormData.id}`" v-model="editFormData.responsibility_center"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                                <!-- <option value="">Select Responsibility Center</option> -->
                                <option v-for="rc in responsibilityCenters" :key="rc.id" :value="rc.code">
                                    {{ rc.code }}
                                </option>
                            </select>
                        </div>

                        <!-- Particulars Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Particulars Name</label>
                            <input v-model="editFormData.particulars_name" type="text" placeholder="Particular name..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>

                        <!-- Account Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                            <input v-model="editFormData.account_code" type="text" placeholder="Account code..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-600 font-medium">₱</span>
                                <input v-model.number="editFormData.amount" type="number" placeholder="0.00" step="0.01"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <!-- Particulars Description Quill Editor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Particulars (Descriptions)</label>
                        <div class="border border-gray-300 rounded-lg overflow-hidden">
                            <QuillEditor v-model:content="editFormData.particulars_description" :toolbar="quillToolbar"
                                content-type="html" theme="snow" placeholder="Enter particulars description..."
                                style="height: 200px" />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Voucher Type & Explanation -->
                <div v-if="editStep === 3" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-3">Voucher Type</label>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input id="edit-disbursements" v-model="editFormData.voucher_type" type="radio"
                                    value="disbursements"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" />
                                <label for="edit-disbursements" class="ml-3 text-sm font-medium text-gray-900">
                                    Disbursement Voucher
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-payroll" v-model="editFormData.voucher_type" type="radio"
                                    value="payroll"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" />
                                <label for="edit-payroll" class="ml-3 text-sm font-medium text-gray-900">
                                    Payroll
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Explanation</label>
                        <div class="border border-gray-300 rounded-lg overflow-hidden">
                            <QuillEditor v-model:content="editFormData.explanation" :toolbar="quillToolbar"
                                content-type="html" theme="snow" placeholder="Enter explanation..."
                                style="height: 200px" />
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Save -->
                <div v-if="editStep === 4" class="space-y-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <h4 class="font-medium text-blue-900 mb-3">Edit Summary</h4>
                        <div class="space-y-2 text-sm text-blue-800">
                            <div class="flex justify-between">
                                <span class="font-medium">Voucher #:</span>
                                <span>{{ editFormData.voucher_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Payee:</span>
                                <span>{{ editFormData.payee_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Payee Type:</span>
                                <span class="capitalize">{{ editFormData.payee_type }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Disbursement Type:</span>
                                <span>{{ editFormData.voucher_type === 'disbursements' ? 'Disbursement Voucher' :
                                    (editFormData.voucher_type === 'payroll' ? 'Payroll' : editFormData.voucher_type)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Amount:</span>
                                <span>₱{{ parseFloat(editFormData.amount || 0).toFixed(2) }}</span>
                            </div>
                            <div v-if="editFormData.responsibility_center" class="flex justify-between">
                                <span class="font-medium">RC:</span>
                                <span>{{ editFormData.responsibility_center }}</span>
                            </div>
                            <div v-if="editFormData.particulars_name" class="flex justify-between">
                                <span class="font-medium">Particulars:</span>
                                <span>{{ editFormData.particulars_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 p-3 rounded-lg">
                        <p class="text-sm text-amber-900"><i class="pi pi-info-circle mr-2"></i>Please review all
                            details
                            before saving</p>
                    </div>
                </div>
            </div>

            <!-- Footer with Navigation -->
            <template #footer>
                <div class="flex justify-between items-center w-full">
                    <Button v-if="editStep > 1" label="Previous" severity="secondary" @click="prevEditStep" outlined />
                    <div v-else></div>
                    <div class="space-x-2">
                        <Button label="Cancel" severity="secondary" @click="cancelEdit" outlined />
                        <Button v-if="editStep < 4" label="Next" severity="primary" @click="nextEditStep" />
                        <Button v-if="editStep === 4" label="Save" severity="success" @click="saveVoucher"
                            :loading="editingId !== null" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Edit Preview Drawer -->
        <Drawer v-model:visible="showEditPreviewDrawer" header="Edit Preview" :modal="false" position="right"
            :closable="false" :style="{ width: '350px' }">
            <div v-if="editFormData" class="space-y-4 text-sm">
                <!-- Selected Scholars Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <h4 class="font-medium text-blue-900 mb-2">Selected Scholars ({{ editSelectedCount }})</h4>
                    <div v-if="editSelectedCount > 0" class="space-y-2 max-h-48 overflow-y-auto">
                        <div v-for="scholar in editScholars.filter(s => s.selected)" :key="scholar.profile_id"
                            class="text-xs text-blue-800 py-2 px-2 bg-white rounded border border-blue-100 flex items-center justify-between gap-2">
                            <span>• {{ scholar.first_name }} {{ scholar.last_name }}</span>
                            <button @click="removeEditScholar(scholar.profile_id)"
                                class="text-red-600 hover:text-red-800 font-medium text-xs cursor-pointer"
                                title="Remove Scholar">
                                <i class="pi pi-times"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-xs text-blue-600">No scholars selected</div>
                </div>

                <!-- Payee Information Section -->
                <div v-if="editStep >= 2" class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <h4 class="font-medium text-green-900 mb-2">Payee Information</h4>
                    <div class="space-y-1 text-green-800">
                        <div v-if="editFormData.payee_name" class="text-xs">
                            <span class="font-medium">Name:</span> {{ editFormData.payee_name }}
                        </div>
                        <div v-if="editFormData.payee_type" class="text-xs">
                            <span class="font-medium">Type:</span> <span class="capitalize">{{ editFormData.payee_type
                            }}</span>
                        </div>
                        <div v-if="editFormData.amount" class="text-xs">
                            <span class="font-medium">Amount:</span> ₱{{ parseFloat(editFormData.amount).toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Obligation Details Section -->
                <div v-if="editStep >= 2" class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                    <h4 class="font-medium text-purple-900 mb-2">Obligation Details</h4>
                    <div class="space-y-1 text-purple-800 text-xs">
                        <div v-if="editFormData.particulars_name">
                            <span class="font-medium">Particulars:</span> {{ editFormData.particulars_name }}
                        </div>
                        <div v-if="editFormData.account_code">
                            <span class="font-medium">Account Code:</span> {{ editFormData.account_code }}
                        </div>
                        <div v-if="editFormData.responsibility_center">
                            <span class="font-medium">RC:</span> {{ editFormData.responsibility_center }}
                        </div>
                    </div>
                </div>

                <!-- Voucher Type Section -->
                <div v-if="editStep >= 3" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                    <h4 class="font-medium text-orange-900 mb-2">Voucher Configuration</h4>
                    <div class="space-y-1 text-orange-800 text-xs">
                        <div>
                            <span class="font-medium">Disbursement Type:</span> <span>{{ editFormData.voucher_type ===
                                'disbursements' ? 'Disbursement Voucher' : (editFormData.voucher_type === 'payroll' ?
                                    'Payroll'
                                    : editFormData.voucher_type) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Drawer>
    </AdminLayout>
</template>

<style scoped></style>
