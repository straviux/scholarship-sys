<template>

    <Head title="Application Records" />

    <AdminLayout>
        <template #header>Scholarship Application Records</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-database text-xl text-blue-600"></i>
                        <span class="font-semibold text-lg">Application Records</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Complete database of all scholarship applications, approvals, renewals, and status history
                    </div>
                    <Tag :value="`${applications.total} applications`" severity="info" />
                </div>
            </Panel>

            <!-- Filters Panel -->
            <Panel class="mt-6">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-filter text-lg"></i>
                        <span class="font-medium">Filters</span>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <!-- Global Search -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search by name..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Approval Status Filter -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Approval Status</label>
                        <Select v-model="selectedApprovalStatus" :options="approvalStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="All Statuses" showClear class="w-full" />
                    </div>

                    <!-- Program Filter -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <Select v-model="selectedProgram" :options="programs" optionLabel="name" optionValue="id"
                            placeholder="All Programs" showClear class="w-full" />
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-between items-center">
                        <Button label="Apply Filters" icon="pi pi-check" severity="success" @click="applyFilters" />
                        <Button label="Clear All" icon="pi pi-times" severity="secondary" outlined
                            @click="clearFilters" />
                    </div>
                </template>
            </Panel>

            <!-- Applications DataTable -->
            <div class="mt-6">
                <DataTable :value="applications.data" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No applications found. Try adjusting your filters.'"
                    :globalFilterFields="['profile.first_name', 'profile.last_name', 'profile.middle_name']"
                    v-model:filters="filters" paginator :rows="rows" v-model:first="first"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                    :totalRecords="applications.total" :lazy="true" @page="onPage">
                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Applications</h3>
                            <div class="flex gap-2">
                                <Tag :value="`${applications.total} total`" severity="info" />
                                <Tag v-if="hasActiveFilters" :value="'Filtered'" severity="warning" />
                            </div>
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 60px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="profile" header="Applicant" sortable>
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <div>
                                    <div class="font-semibold text-gray-800">
                                        {{ getFullName(slotProps.data.profile) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: {{ slotProps.data.profile?.profile_id || 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="program" header="Program">
                        <template #body="slotProps">
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ slotProps.data.program?.name || 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ slotProps.data.program?.shortname || '' }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="course" header="Course/School">
                        <template #body="slotProps">
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ slotProps.data.course?.name || 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ slotProps.data.school?.name || 'N/A' }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="approval_status" header="Approval Status" style="width: 140px">
                        <template #body="slotProps">
                            <Chip :label="getApprovalStatusLabel(slotProps.data.approval_status)"
                                :severity="getApprovalStatusSeverity(slotProps.data.approval_status)" />
                        </template>
                    </Column>

                    <Column field="created_at" header="Applied Date" sortable style="width: 120px">
                        <template #body="slotProps">
                            <span class="text-gray-600">
                                {{ formatDate(slotProps.data.created_at) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 180px">
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center">
                                <Button icon="pi pi-eye" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'View Application'" @click="viewApplication(slotProps.data)" />
                                <Button icon="pi pi-clipboard-check" severity="success" size="small" rounded outlined
                                    v-tooltip.top="'Review Application'" @click="reviewApplication(slotProps.data)" />
                                <Button v-if="canEdit(slotProps.data)" icon="pi pi-pen-to-square" severity="warning"
                                    size="small" rounded outlined v-tooltip.top="'Edit Application'"
                                    @click="editApplication(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Approval Workflow Dialog -->
        <Dialog v-model:visible="showApprovalDialog" modal header="Application Review & Approval"
            :style="{ width: '90vw', maxWidth: '1200px' }" class="p-fluid" :closable="true" :dismissableMask="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-clipboard-check text-xl text-blue-600"></i>
                        <span class="font-semibold text-lg">Application Review & Approval</span>
                    </div>
                    <div v-if="selectedApplication" class="text-sm text-gray-600">
                        {{ getFullName(selectedApplication.profile) }} - {{ selectedApplication.program?.shortname }}
                    </div>
                </div>
            </template>

            <ApprovalWorkflow v-if="selectedApplication" :application="selectedApplication"
                :approval-statuses="approvalStatuses || []" :decline-reasons="declineReasons || {}"
                :show-applicant-name="true" @approved="handleApprovalAction" @declined="handleApprovalAction"
                @conditionalApproval="handleApprovalAction" @refresh="refreshData" />
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment';

// PrimeVue Components
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';

// Custom Components
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';

const props = defineProps({
    applications: Object,
    filters: Object,
    programs: Array,
    approvalStatuses: Array,
    declineReasons: Object,
});

// DataTable state
const globalFilter = ref('');
const first = ref(0);
const rows = ref(15);
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Filter state
const selectedApprovalStatus = ref(props.filters?.approval_status || null);
const selectedProgram = ref(props.filters?.program_id || null);

// Approval workflow state
const showApprovalDialog = ref(false);
const selectedApplication = ref(null);

// Computed filter options
const approvalStatusOptions = computed(() => [
    { label: 'All Statuses', value: null },
    ...(Array.isArray(props.approvalStatuses) ? props.approvalStatuses : [])
]);

const hasActiveFilters = computed(() => {
    return selectedApprovalStatus.value ||
        selectedProgram.value ||
        globalFilter.value;
});

// Watch for changes in globalFilter and update filters
watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});

// Helper methods
const getFullName = (profile) => {
    if (!profile) return 'N/A';
    const parts = [
        profile.first_name,
        profile.middle_name,
        profile.last_name,
        profile.extension_name
    ].filter(Boolean);
    return parts.join(' ');
};

const getApprovalStatusLabel = (status) => {
    if (!Array.isArray(props.approvalStatuses)) return status || 'Unknown';
    const statusObj = props.approvalStatuses.find(s => s.value === status);
    return statusObj?.label || status || 'Unknown';
};

const getApprovalStatusSeverity = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'declined':
            return 'danger';
        case 'auto_approved':
            return 'info';
        default:
            return 'secondary';
    }
};

const formatDate = (date) => {
    return moment(date).format('MMM DD, YYYY');
};

// Filter methods
const applyFilters = () => {
    const filterParams = {};

    if (selectedApprovalStatus.value) {
        filterParams.approval_status = selectedApprovalStatus.value;
    }
    if (selectedProgram.value) {
        filterParams.program_id = selectedProgram.value;
    }
    if (globalFilter.value) {
        filterParams.search = globalFilter.value;
    }

    router.get(route('scholarship.applications'), filterParams, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    selectedApprovalStatus.value = null;
    selectedProgram.value = null;
    globalFilter.value = '';

    router.get(route('scholarship.applications'), {}, {
        preserveState: true,
        replace: true,
    });
};

// Pagination methods
const onPage = (event) => {
    const page = event.page + 1; // PrimeVue uses 0-based pagination
    const filterParams = {
        page: page,
        per_page: event.rows
    };

    // Add current filters
    if (selectedApprovalStatus.value) {
        filterParams.approval_status = selectedApprovalStatus.value;
    }
    if (selectedProgram.value) {
        filterParams.program_id = selectedProgram.value;
    }
    if (globalFilter.value) {
        filterParams.search = globalFilter.value;
    }

    router.get(route('scholarship.applications'), filterParams, {
        preserveState: true,
        replace: true,
    });
};

// Action methods
const viewApplication = (application) => {
    router.visit(route('scholarship_records.index', { id: application.id, action: 'view' }));
};

const editApplication = (application) => {
    router.visit(route('scholarship_records.index', { id: application.id, action: 'edit' }));
};

const reviewApplication = (application) => {
    selectedApplication.value = application;
    showApprovalDialog.value = true;
};

const handleApprovalAction = () => {
    showApprovalDialog.value = false;
    selectedApplication.value = null;
    refreshData();
};

const refreshData = () => {
    applyFilters();
};

const refreshApplications = () => {
    applyFilters();
};

// Permission checks
const canEdit = (application) => {
    return application.approval_status === 'pending';
};

// Initialize filters on mount
onMounted(() => {
    if (props.filters?.search) {
        globalFilter.value = props.filters.search;
    }
});
</script>