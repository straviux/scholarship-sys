<template>

    <Head title="Profiles" />

    <AdminLayout>
        <template #header>Profiles</template>

        <div class="max-w-8xl mx-auto py-2">
            <!-- Header Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-users text-lg text-blue-600"></i>
                        <span class="font-semibold">Profiles</span>
                    </div>
                </template>

                <div class="flex justify-between items-center py-2">
                    <div class="text-gray-600 text-sm">
                        Directory of scholarship profiles with their basic information and latest scholarship status
                    </div>
                    <Tag :value="`${profiles.total} profiles`" severity="info" />
                </div>
            </Panel>

            <!-- Filters Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-filter text-sm text-gray-600"></i>
                        <span class="font-medium text-sm">Filters</span>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 py-2">
                    <!-- Global Search -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Search</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search by name, ID..."
                                class="w-full h-9 text-sm" />
                        </IconField>
                    </div>

                    <!-- Approval Status Filter -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Latest Status</label>
                        <Select v-model="selectedApprovalStatus" :options="approvalStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="All Statuses" showClear class="w-full h-9 text-sm" />
                    </div>

                    <!-- Program Filter -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Latest Program</label>
                        <Select v-model="selectedProgram" :options="programs" optionLabel="name" optionValue="id"
                            placeholder="All Programs" showClear class="w-full h-9 text-sm" />
                    </div>

                    <!-- Filter Actions -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">&nbsp;</label>
                        <div class="flex gap-2">
                            <Button label="Apply" icon="pi pi-check" size="small" @click="applyFilters"
                                :disabled="!hasActiveFilters" />
                            <Button label="Clear" icon="pi pi-times" severity="secondary" outlined size="small"
                                @click="clearFilters" :disabled="!hasActiveFilters" />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Profiles Table -->
            <div class="bg-white rounded-lg shadow">
                <DataTable :value="profiles.data" paginator showGridlines stripedRows :rows="rows" :first="first"
                    :totalRecords="profiles.total" lazy :loading="loading" @page="onPage" :filters="filters"
                    :globalFilterFields="['first_name', 'last_name', 'unique_id']" tableStyle="min-width: 50rem"
                    size="small" class="compact-table">

                    <template #header>
                        <div class="flex justify-between items-center py-2">
                            <h3 class="font-semibold">Profile Directory</h3>
                            <Button icon="pi pi-refresh" @click="refreshData" outlined size="small" />
                        </div>
                    </template>

                    <Column field="profile_info" header="Profile Information" style="width: 200px">
                        <template #body="slotProps">
                            <div class="flex items-center gap-2 py-1">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-600 text-sm"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800 text-sm">
                                        {{ getFullName(slotProps.data) }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ slotProps.data.total_scholarships }} scholarship(s)
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="contact_info" header="Contact" style="width: 160px">
                        <template #body="slotProps">
                            <div class="py-1 space-y-1">
                                <div class="text-xs text-gray-700" v-if="slotProps.data.contact_no">
                                    {{ slotProps.data.contact_no }}
                                </div>
                                <div class="text-xs text-gray-600" v-if="slotProps.data.municipality">
                                    {{ slotProps.data.municipality }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="latest_program" header="Program" style="width: 120px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.latest_scholarship_record" class="py-1">
                                <div class="font-medium text-gray-800 text-sm">
                                    {{ slotProps.data.latest_scholarship_record.program?.shortname ||
                                        slotProps.data.latest_scholarship_record.program?.name || 'N/A' }}
                                </div>
                            </div>
                            <div v-else class="text-gray-400 text-sm py-1">No program</div>
                        </template>
                    </Column>

                    <Column field="latest_course" header="Course" style="width: 140px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.latest_scholarship_record" class="py-1">
                                <div class="font-medium text-gray-800 text-sm">
                                    {{ slotProps.data.latest_scholarship_record.course?.shortname ||
                                        slotProps.data.latest_scholarship_record.course?.name || 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ slotProps.data.latest_scholarship_record.school?.shortname ||
                                        slotProps.data.latest_scholarship_record.school?.name || 'N/A' }}
                                </div>
                            </div>
                            <div v-else class="text-gray-400 text-sm py-1">No course</div>
                        </template>
                    </Column>

                    <Column field="latest_status" header="Status" style="width: 120px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.latest_scholarship_record" class="py-1">
                                <Chip size="small"
                                    :label="getApprovalStatusLabel(slotProps.data.latest_scholarship_record.approval_status)"
                                    :severity="getApprovalStatusSeverity(slotProps.data.latest_scholarship_record.approval_status)" />
                            </div>
                            <div v-else class="py-1">
                                <Chip label="No Status" severity="secondary" size="small" />
                            </div>
                        </template>
                    </Column>

                    <Column field="latest_applied" header="Applied" sortable style="width: 100px">
                        <template #body="slotProps">
                            <span class="text-xs text-gray-600" v-if="slotProps.data.latest_scholarship_record">
                                {{ formatDate(slotProps.data.latest_scholarship_record.created_at) }}
                            </span>
                            <span v-else class="text-xs text-gray-400">N/A</span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 150px">
                        <template #body="slotProps">
                            <div class="flex gap-1 justify-center">
                                <Button icon="pi pi-history" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'View History'" @click="viewFullHistory(slotProps.data)" />
                                <Button v-if="slotProps.data.latest_scholarship_record" icon="pi pi-eye"
                                    severity="success" size="small" rounded outlined v-tooltip.top="'Review'"
                                    @click="reviewApplication(slotProps.data.latest_scholarship_record)" />
                                <Button icon="pi pi-pen-to-square" severity="warning" size="small" rounded outlined
                                    v-tooltip.top="'Edit'" @click="editProfile(slotProps.data)" />
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
                        <i class="pi pi-clipboard-check text-lg text-blue-600"></i>
                        <span class="font-semibold">Application Review & Approval</span>
                    </div>
                    <div v-if="selectedApplication" class="text-sm text-gray-600">
                        {{ getFullName(selectedApplication.profile || selectedApplication) }} - {{
                            selectedApplication.program?.shortname }}
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
import { ref, reactive, computed, watch, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
import moment from 'moment';

// PrimeVue Components
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';

// Props
const props = defineProps({
    profiles: Object,
    filters: Object,
    programs: Array,
    approvalStatuses: Array,
    declineReasons: Object,
});

// Reactive state
const globalFilter = ref('');
const selectedApprovalStatus = ref(props.filters?.approval_status || null);
const selectedProgram = ref(props.filters?.program_id || null);
const loading = ref(false);
const first = ref(0);
const rows = ref(15);

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

// Helper functions
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

    router.get(route('scholarship.profiles'), filterParams, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    selectedApprovalStatus.value = null;
    selectedProgram.value = null;
    globalFilter.value = '';

    router.get(route('scholarship.profiles'), {}, {
        preserveState: true,
        replace: true,
    });
};

const exportFilteredData = () => {
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

    router.get(route('scholarship.profiles'), filterParams, {
        preserveState: true,
        replace: true,
    });
};

// Action methods
const viewFullHistory = (profile) => {
    router.visit(route('scholarship.profile.history', profile.profile_id));
};

const reviewApplication = (scholarshipRecord) => {
    selectedApplication.value = scholarshipRecord;
    showApprovalDialog.value = true;
};

const editProfile = (profile) => {
    // Navigate to profile edit page - you can implement this route
    router.visit(route('profile.edit', profile.profile_id));
};

const handleApprovalAction = (result) => {
    if (result.success) {
        showApprovalDialog.value = false;
        selectedApplication.value = null;
        refreshData();
    }
};

const refreshData = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
    });
};

// DataTable pagination
const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};

// Watch for changes in globalFilter
watch(globalFilter, (newValue) => {
    // Auto-apply search after typing stops for 500ms
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        if (newValue !== (props.filters?.search || '')) {
            applyFilters();
        }
    }, 500);
});

// Component lifecycle
onMounted(() => {
    // Set initial filter values from URL
    globalFilter.value = props.filters?.search || '';
});
</script>

<style scoped>
:deep(.compact-table .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.75rem;
}

:deep(.compact-table .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

:deep(.compact-table .p-chip) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

:deep(.compact-table .p-button) {
    padding: 0.25rem 0.5rem;
}
</style>