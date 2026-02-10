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
const scholarsCache = ref(new Map()); // Cache for scholar details by ID
const editingId = ref(null);
const editFormData = ref(null);
const responsibilityCenters = ref([]);
const contextMenu = ref();
const showRemarksDialog = ref(false);
const selectedVoucherForRemarks = ref(null);
const remarksForm = reactive({
    remarks: ''
});
const savingRemarks = ref(false);
const contextMenuItems = ref([]);
const showStatusDialog = ref(false);
const selectedVoucherForStatus = ref(null);
const statusForm = reactive({
    transaction_status: 'pending',
    remarks: ''
});
const savingStatus = ref(false);
const transactionStatuses = ['pending', 'suspended', 'completed'];
const quillToolbar = [
    [{ align: [] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link']
];

const handleCreateVoucher = () => {
    editingId.value = null;
    editFormData.value = null;
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

        // Fetch and cache scholars for school payees
        for (const voucher of vouchers.value) {
            if (isPayeeSchool(voucher) && voucher.scholar_ids?.length > 0) {
                fetchAndCacheScholarDetails(voucher.scholar_ids);
            }
        }
    } catch (error) {
        console.error('Error fetching vouchers:', error);
        vouchers.value = [];
    } finally {
        loading.value = false;
    }
};

// Fetch and cache scholar details
const fetchAndCacheScholarDetails = async (scholarIds) => {
    if (!scholarIds || scholarIds.length === 0) return;

    try {
        for (const scholar of scholarIds) {
            const profileId = typeof scholar === 'object' ? scholar.profile_id : scholar;

            // Skip if already in cache
            if (scholarsCache.value.has(profileId)) continue;

            try {
                const response = await axios.get(`/api/scholarships/profile/${profileId}`);
                if (response.data.data) {
                    scholarsCache.value.set(profileId, response.data.data);
                }
            } catch (error) {
                console.error(`Error fetching scholar ${profileId}:`, error);
            }
        }
    } catch (error) {
        console.error('Error in fetchAndCacheScholarDetails:', error);
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

        // Set up edit data
        editFormData.value = {
            ...voucher,
            responsibility_center: voucher.responsibility_center || ''
        };
        editingId.value = voucherId;
        showWizard.value = true;
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

        showWizard.value = false;
        editFormData.value = null;
        editingId.value = null;
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
        },
        {
            label: 'Change Status',
            icon: 'pi pi-sync',
            command: () => openStatusModal(voucher)
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
            remarks: remarksForm.remarks,
            transaction_status: voucherData.transaction_status
        });

        // Update the voucher in the list
        const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForRemarks.value.id);
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].remarks = remarksForm.remarks;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForRemarks.value.id) {
            selectedVoucher.value.remarks = remarksForm.remarks;
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

// Open transaction status modal
const openStatusModal = (voucher) => {
    selectedVoucherForStatus.value = voucher;
    statusForm.transaction_status = voucher.transaction_status || 'pending';
    statusForm.remarks = voucher.remarks || '';
    showStatusDialog.value = true;
};

// Save transaction status
const saveStatus = async () => {
    if (!selectedVoucherForStatus.value) return;

    savingStatus.value = true;
    try {
        // GET the current voucher data
        const currentVoucher = await axios.get(`/api/vouchers/${selectedVoucherForStatus.value.id}`);
        const voucherData = currentVoucher.data.data;

        // PUT with all required fields plus updated transaction_status and remarks
        await axios.put(`/api/vouchers/${selectedVoucherForStatus.value.id}`, {
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
            remarks: statusForm.remarks,
            transaction_status: statusForm.transaction_status
        });

        // Update the voucher in the list
        const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForStatus.value.id);
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].transaction_status = statusForm.transaction_status;
            vouchers.value[voucherIndex].remarks = statusForm.remarks;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForStatus.value.id) {
            selectedVoucher.value.transaction_status = statusForm.transaction_status;
            selectedVoucher.value.remarks = statusForm.remarks;
        }

        showStatusDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Transaction status updated successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error saving transaction status:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update transaction status: ' + errorMsg,
            life: 5000
        });
    } finally {
        savingStatus.value = false;
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

// Get status color for badge
const getStatusColor = (status) => {
    const statusColors = {
        'pending': 'bg-blue-100 text-blue-800',
        'suspended': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800'
    };
    return statusColors[status] || 'bg-gray-100 text-gray-800';
};

// Check if payee is school
const isPayeeSchool = (voucher) => {
    return voucher?.payee_type?.toLowerCase() === 'school' ||
        voucher?.payee_name?.toLowerCase().includes('school');
};

// Get first scholar name from cache (for table display)
const getFirstScholarNameFromCache = (voucher) => {
    if (!voucher?.scholar_ids || voucher.scholar_ids.length === 0) {
        return '';
    }

    // Get first scholar ID  
    const firstScholarId = typeof voucher.scholar_ids[0] === 'object'
        ? voucher.scholar_ids[0].profile_id
        : voucher.scholar_ids[0];

    // Look up in cache
    const scholar = scholarsCache.value.get(firstScholarId);
    if (scholar) {
        const name = `${scholar.first_name} ${scholar.last_name}`;
        return name.length > 25 ? name.substring(0, 25) + '...' : name;
    }
    return '';
};

// Get first scholar name truncated (for view modal)
const getFirstScholarName = (voucher) => {
    if (!voucher?.scholar_ids || voucher.scholar_ids.length === 0) {
        return '';
    }
    // If scholarsDetails has the first scholar, use its name
    const firstScholar = scholarsDetails.value?.[0];
    if (firstScholar) {
        const name = `${firstScholar.first_name} ${firstScholar.last_name}`;
        return name.length > 25 ? name.substring(0, 25) + '...' : name;
    }
    return '';
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

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <!-- Header -->
            <Toolbar class="border-0 bg-transparent p-0">
                <template #start>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-16 h-16">
                            <i class="pi pi-credit-card text-indigo-900" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Vouchers Management</h1>
                            <p class="text-gray-600 mt-1">Create and manage obligations and vouchers for scholars
                                disbursements
                                and payroll</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <button @click="handleCreateVoucher"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                        <i class="pi pi-plus mr-2 text-sm"></i>
                        <span>Create Voucher</span>
                    </button>
                </template>
            </Toolbar>


            <!-- List/Summary Section -->
            <div class="bg-white rounded-lg shadow p-6 mt-8">
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
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payee</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    OBR Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Disbursement Type</th>


                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
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
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="pi pi-spin pi-spinner mr-2"></i> Loading vouchers...
                                </td>
                            </tr>
                            <tr v-else-if="vouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No vouchers created yet.</p>
                                    <p class="text-xs text-gray-400 mt-1">Click the "Create Voucher" button to get
                                        started</p>
                                </td>
                            </tr>
                            <tr v-else-if="filteredVouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
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
                                    <div>{{ voucher.payee_name }}</div>
                                    <div v-if="isPayeeSchool(voucher)"
                                        class="text-xs font-bold italic text-gray-600 mt-1">
                                        {{ getFirstScholarNameFromCache(voucher) || '---' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="{
                                        'px-3 py-1 rounded-full text-xs font-medium': true,
                                        ' text-gray-800': voucher.obr_type === 'REGULAR',
                                        ' text-yellow-800': voucher.obr_type === 'FINANCIAL ASSISTANCE',
                                        ' text-red-800': voucher.obr_type === 'REIMBURSEMENT'
                                    }">
                                        {{ voucher.obr_type || '---' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span :class="{
                                        'px-3 py-1 rounded-full text-xs font-medium': true,
                                        ' text-blue-800': voucher.voucher_type === 'disbursements',
                                        ' text-green-800': voucher.voucher_type === 'payroll'
                                    }">
                                        {{ voucher.voucher_type === 'disbursements' ? 'Disbursement Voucher' :
                                            (voucher.voucher_type === 'payroll' ? 'Payroll' : voucher.voucher_type) }}
                                    </span>
                                </td>


                                <td class="px-6 py-4 text-sm">
                                    <span
                                        :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(voucher.transaction_status)]">
                                        {{ (voucher.transaction_status || 'pending').charAt(0).toUpperCase() +
                                            (voucher.transaction_status || 'pending').slice(1) }}
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

        <!-- Voucher Wizard (Create & Edit) -->
        <VoucherWizard v-if="showWizard" :visible="showWizard" :mode="editingId ? 'edit' : 'create'"
            :voucherId="editingId" :initialData="editFormData" @close="handleWizardClose"
            @scholar-selected="handleScholarSelection" />

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
                        <div>
                            <p class="text-sm text-gray-900">{{ selectedVoucher.payee_name }}</p>
                            <p v-if="isPayeeSchool(selectedVoucher)" class="text-xs text-gray-600 mt-1">
                                Scholar: {{ getFirstScholarName(selectedVoucher) || '---' }}
                            </p>
                        </div>
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
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Transaction Status</p>
                        <span
                            :class="['px-3 py-1 rounded-full text-xs font-medium inline-block', getStatusColor(selectedVoucher.transaction_status)]">
                            {{ (selectedVoucher.transaction_status || 'pending').charAt(0).toUpperCase() +
                                (selectedVoucher.transaction_status || 'pending').slice(1) }}
                        </span>
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

        <!-- Transaction Status Dialog -->
        <Dialog v-model:visible="showStatusDialog" modal header="Update Transaction Status" :style="{ width: '500px' }">
            <div v-if="selectedVoucherForStatus" class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Voucher: {{
                        selectedVoucherForStatus.voucher_number }}
                    </p>
                    <p class="text-xs text-gray-500">Change the transaction status for this voucher</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Transaction Status</label>
                    <div class="flex gap-3">
                        <div v-for="status in transactionStatuses" :key="status" class="flex items-center">
                            <input type="radio" :id="`status-${status}`" :value="status"
                                v-model="statusForm.transaction_status" :name="status"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                            <label :for="`status-${status}`"
                                class="ml-2 block text-sm text-gray-900 capitalize cursor-pointer">
                                {{ status }}
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Remarks (Optional)</label>
                    <textarea v-model="statusForm.remarks" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="Add or update remarks..." />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showStatusDialog = false" outlined />
                <Button label="Update" severity="success" @click="saveStatus" :loading="savingStatus" />
            </template>
        </Dialog>

    </AdminLayout>
</template>

<style scoped></style>
