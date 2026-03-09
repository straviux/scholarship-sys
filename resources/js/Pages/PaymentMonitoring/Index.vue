<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Toolbar from 'primevue/toolbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Panel from 'primevue/panel';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';

const props = defineProps({
    paymentData: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', transaction_status: '', academic_year: '', semester: '' }),
    },
});

// Reactive state
const searchInput = ref(props.filters.search);
const selectedStatus = ref(props.filters.transaction_status);
const selectedAcademicYear = ref(props.filters.academic_year);
const selectedSemester = ref(props.filters.semester);

// Watch for changes and apply filters
watch(searchInput, () => applyFilters());
watch(selectedStatus, () => applyFilters());
watch(selectedAcademicYear, () => applyFilters());
watch(selectedSemester, () => applyFilters());

// Computed property for status options including "No OBR assigned"
const statusOptionsWithNoOBR = computed(() => {
    return [
        { label: 'Show All', value: '' },
        { label: 'No OBR Assigned', value: 'no-obr' },
        { label: 'On Process', value: 'on process' },
        { label: 'Suspend', value: 'suspend' },
        { label: 'Completed', value: 'completed' }
    ];
});

// Computed property for filtered data
const filteredData = computed(() => {
    let data = props.paymentData;

    if (searchInput.value) {
        data = data.filter((item) =>
            item.scholar_name.toLowerCase().includes(searchInput.value.toLowerCase())
        );
    }

    if (selectedStatus.value && selectedStatus.value !== '') {
        if (selectedStatus.value === 'no-obr') {
            data = data.filter((item) => !item.transaction_status);
        } else {
            data = data.filter((item) => item.transaction_status === selectedStatus.value);
        }
    }

    if (selectedAcademicYear.value && selectedAcademicYear.value !== '') {
        data = data.filter((item) => item.academic_year === selectedAcademicYear.value);
    }

    if (selectedSemester.value) {
        data = data.filter((item) => item.term === selectedSemester.value);
    }

    // Sort alphabetically by scholar name
    data = data.sort((a, b) => a.scholar_name.localeCompare(b.scholar_name));

    return data;
});

// Methods
const applyFilters = () => {
    router.get(route('payment-monitoring.index'), {
        search: searchInput.value,
        transaction_status: selectedStatus.value,
        academic_year: selectedAcademicYear.value,
        semester: selectedSemester.value,
    }, {
        preserveState: false,
        replace: true,
    });
};

const clearFilters = () => {
    searchInput.value = '';
    selectedStatus.value = '';
    selectedAcademicYear.value = '';
    selectedSemester.value = '';
    router.get(route('payment-monitoring.index'), {}, {
        preserveState: false,
        replace: true,
    });
};

// Status badge styling
const getStatusBadgeClass = (status) => {
    const baseClass = 'px-2 sm:px-3 py-1 rounded-full text-xs font-semibold';
    const statusMap = {
        'LOA': 'bg-blue-100 text-blue-800',
        'IRREGULAR': 'bg-yellow-100 text-yellow-800',
        'TRANSFERRED': 'bg-purple-100 text-purple-800',
        'CLAIMED': 'bg-indigo-100 text-indigo-800',
        'PAID': 'bg-green-100 text-green-800',
        'ON PROCESS': 'bg-orange-100 text-orange-800',
        'DENIED': 'bg-red-100 text-red-800',
        '': 'bg-gray-100 text-gray-800',
    };
    return `${baseClass} ${statusMap[status] || statusMap['']}`;
};

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>

    <Head title="Payment Monitoring" />

    <AdminLayout>
        <template #header>Payment Monitoring</template>

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4 pb-8">
            <Toolbar class="mb-6 bg-white rounded-lg shadow-sm flex-col sm:flex-row gap-4">
                <template #start>
                    <div class="flex items-center gap-3 w-full">
                        <i class="pi pi-dollar text-lg sm:text-2xl text-blue-600 flex-shrink-0"
                            style="font-size: 1.5rem;"></i>
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-800">Payment Monitoring</h1>
                            <p class="text-xs sm:text-sm text-gray-500 truncate">Track OBR status for active scholarship
                                records</p>
                        </div>
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6" header="Filters" :toggleable="true" :collapsed="false">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Search by Scholar Name -->
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label for="search" class="text-xs sm:text-sm font-medium text-gray-700">Scholar Name</label>
                        <InputText id="search" v-model="searchInput" placeholder="Search by name"
                            class="w-full text-xs sm:text-sm" />
                    </div>


                    <!-- Filter by Academic Year -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-700">Academic Year</label>
                        <AcademicYearSelect v-model="selectedAcademicYear" />
                    </div>

                    <!-- Filter by Term/Semester -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-700">Term</label>
                        <TermSelect v-model="selectedSemester" />
                    </div>

                    <!-- Filter by Transaction Status -->
                    <div class="flex flex-col gap-3 md:col-span-3">
                        <label class="text-xs sm:text-sm font-medium text-gray-700">Transaction Status</label>
                        <div class="flex flex-wrap gap-2 sm:gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value=""
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">All</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="no-obr"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">No OBR</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="LOA"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">LOA</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="IRREGULAR"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">Irregular</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="TRANSFERRED"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">Transferred</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="CLAIMED"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">Claimed</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="PAID"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">Paid</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="ON PROCESS"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">On Process</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="selectedStatus" type="radio" value="DENIED"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <span class="text-xs sm:text-sm text-gray-700 whitespace-nowrap">Denied</span>
                            </label>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-2 md:col-span-1 items-end justify-end">
                        <label class="text-xs sm:text-sm font-medium text-gray-700">&nbsp;</label>
                        <Button label="Clear Filters" icon="pi pi-times" severity="secondary" @click="clearFilters"
                            class="w-1/2 text-xs sm:text-sm" size="small" />
                    </div>
                </div>
            </Panel>
            <!-- Summary -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                <div class="shadow rounded-lg p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600">Total Active Records</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-700 mt-1">{{ paymentData.length }}</p>
                </div>
                <div class="shadow rounded-lg p-3 sm:p-4 border-l-4 border-green-500">
                    <p class="text-xs sm:text-sm text-gray-600">With OBR Assigned</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-700 mt-1">
                        {{paymentData.filter(item => item.transaction_status).length}}
                    </p>
                </div>
                <div class="shadow rounded-lg p-3 sm:p-4 border-l-4 border-yellow-500">
                    <p class="text-xs sm:text-sm text-gray-600">Pending OBR Assignment</p>
                    <p class="text-xl sm:text-2xl font-bold text-yellow-700 mt-1">
                        {{paymentData.filter(item => !item.transaction_status).length}}
                    </p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow mt-6 overflow-auto">
                <DataTable :value="filteredData" :paginator="true" :rows="10" :rows-per-page-options="[5, 10, 20, 50]"
                    responsive-layout="scroll" class="w-full text-xs sm:text-sm">
                    <Column field="scholar_name" header="Scholar Name" :sortable="true" style="min-width: 160px" />
                    <Column field="voucher_type" header="Disbursement Type" :sortable="true" style="min-width: 100px">
                        <template #body="{ data }">
                            <span v-if="data.voucher_type"
                                class="px-2 sm:px-3 py-1 rounded-full text-xs font-semibold inline-block"
                                :class="data.voucher_type === 'disbursements' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                {{ data.voucher_type === 'disbursements' ? 'Disbursement' : (data.voucher_type ===
                                    'payroll' ? 'Payroll' : data.voucher_type) }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </template>
                    </Column>
                    <Column field="academic_year" header="Academic Year" :sortable="true" style="min-width: 100px" />
                    <Column field="year_level" header="Year Level" :sortable="true" style="min-width: 80px" />
                    <Column field="term" header="Term" :sortable="true" style="min-width: 80px" />

                    <!-- Transaction Status -->
                    <Column header="Transaction Status" style="min-width: 140px">
                        <template #body="{ data }">
                            <div v-if="data.transaction_status" :class="getStatusBadgeClass(data.transaction_status)">
                                {{ data.transaction_status }}
                            </div>
                            <div v-else class="text-gray-500 text-sm italic">
                                No OBR assigned
                            </div>
                        </template>
                    </Column>

                    <!-- Amount -->
                    <Column header="Amount" style="min-width: 100px">
                        <template #body="{ data }">
                            <span v-if="data.amount" class="font-semibold text-green-700 text-xs sm:text-sm">
                                ₱{{ parseFloat(data.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <!-- OBR Date -->
                    <Column header="OBR Date" style="min-width: 100px">
                        <template #body="{ data }">
                            <span v-if="data.date_obligated" class="text-xs sm:text-sm text-gray-700">
                                {{ formatDate(data.date_obligated) }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <!-- Remarks -->
                    <Column field="remarks" header="Remarks" style="min-width: 160px">
                        <template #body="{ data }">
                            <span v-if="data.remarks" class="text-xs sm:text-sm text-gray-600">{{ data.remarks }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <!-- OBR No -->
                    <Column field="obr_no" header="OBR No." style="min-width: 120px">
                        <template #body="{ data }">
                            <span v-if="data.obr_no" class="text-xs sm:text-sm font-mono text-blue-700">{{ data.obr_no
                                }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="p-8 text-center text-gray-500">
                            <i class="pi pi-inbox text-2xl mb-2 block"></i>
                            <p>No payment records found matching the current filters.</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
:deep(.p-datatable) {
    border-radius: 0.5rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f3f4f6;
    font-weight: 600;
    color: #374151;
}

:deep(.p-paginator) {
    border-top: 1px solid #e5e7eb;
}
</style>
