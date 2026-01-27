<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoucherWizard from '@/Components/Obligations/VoucherWizard.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
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
            // TODO: Add DV endpoint
            toast.add({
                severity: 'warn',
                summary: 'Not Implemented',
                detail: 'DV document generation coming soon',
                life: 3000
            });
            return;
        } else if (docType === 'LOS') {
            // TODO: Add LOS endpoint
            toast.add({
                severity: 'warn',
                summary: 'Not Implemented',
                detail: 'LOS document generation coming soon',
                life: 3000
            });
            return;
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

// Fetch on mount
onMounted(() => {
    fetchVouchers();
});
</script>

<template>

    <Head title="Vouchers" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
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
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <i class="pi pi-spin pi-spinner mr-2"></i> Loading vouchers...
                                </td>
                            </tr>
                            <tr v-else-if="vouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No vouchers created yet.</p>
                                    <p class="text-xs text-gray-400 mt-1">Click the "Create Voucher" button to get
                                        started</p>
                                </td>
                            </tr>
                            <tr v-else-if="filteredVouchers.length === 0" class="hover:bg-gray-50">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No vouchers match your search.</p>
                                    <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria</p>
                                </td>
                            </tr>
                            <tr v-for="voucher in filteredVouchers" :key="voucher.id"
                                class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-blue-600">{{ voucher.voucher_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span :class="{
                                        'px-3 py-1 rounded-full text-xs font-medium': true,
                                        'bg-blue-100 text-blue-800': voucher.voucher_type === 'disbursements',
                                        'bg-green-100 text-green-800': voucher.voucher_type === 'payroll'
                                    }">
                                        {{ voucher.voucher_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ voucher.payee_name }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{
                                    formatAmount(calculateTotalAmount(voucher))
                                    }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ voucher.creator?.name || '---' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(voucher.created_at) }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <button @click="viewVoucher(voucher.id)"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-xs cursor-pointer"
                                            title="View Voucher">
                                            <i class="pi pi-eye"></i>
                                        </button>
                                        <button v-if="isAdmin" @click="deleteVoucher(voucher.id)"
                                            :disabled="deletingId === voucher.id"
                                            class="text-red-600 hover:text-red-800 font-medium text-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Delete Voucher">
                                            <i
                                                :class="deletingId === voucher.id ? 'pi pi-spin pi-spinner' : 'pi pi-trash'"></i>
                                        </button>
                                    </div>
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
                        <p class="text-xs font-semibold text-gray-600 uppercase">Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.voucher_type }}</p>
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
                        <Button label="DV" @click="generateDocument('DV')" class="flex-1" severity="success">
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
    </AdminLayout>
</template>

<style scoped></style>
