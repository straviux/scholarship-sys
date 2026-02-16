<template>
    <AdminLayout>

        <Head title="Reviewed Applicants - Approval Management" />

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-check-circle text-green-600" style="font-size:2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Reviewed Applicants</h1>
                            <p class="text-sm text-gray-600">Manage applicants marked as approved or denied</p>
                        </div>
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6">
                <div class="space-y-3 -mt-6">
                    <!-- Filters Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Status</label>
                            <Select v-model="filters.status" :options="statusOptions" optionLabel="label"
                                optionValue="value" placeholder="All Statuses" size="small" class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Name</label>
                            <InputText v-model="filters.name" placeholder="Search by name" size="small" class="w-full"
                                @keyup.enter="applyFilters" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                            <ProgramSelect v-model="filters.program" size="small" class="w-full" />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Approved Pending Tab -->
            <Card class="mb-6">
                <template #header>
                    <div class="flex items-center gap-2 p-4">
                        <i class="pi pi-check-circle text-green-600 text-xl"></i>
                        <span class="font-semibold">Marked as Approved ({{ approvedList.length }})</span>
                    </div>
                </template>
                <template #content>
                    <div v-if="approvedList.length === 0" class="text-center py-8 text-gray-500">
                        No applicants marked as approved yet
                    </div>
                    <DataTable v-else :value="approvedList" responsiveLayout="scroll" class="text-sm">
                        <Column field="profile.last_name" header="Name" sortable>
                            <template #body="slotProps">
                                <div class="font-medium">
                                    {{ slotProps.data.profile.last_name }}, {{ slotProps.data.profile.first_name }}
                                </div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.profile.contact_no }}</div>
                            </template>
                        </Column>
                        <Column field="program.shortname" header="Program" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.program?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="course.shortname" header="Course" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.course?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="year_level" header="Year" sortable />
                        <Column field="date_filed" header="Date Filed" sortable>
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.date_filed) }}
                            </template>
                        </Column>
                        <Column header="Actions" :style="{ width: '300px' }">
                            <template #body="slotProps">
                                <div class="flex gap-2 items-center">
                                    <Button v-if="hasRole('administrator') || hasRole('program_manager')"
                                        label="Approve" icon="pi pi-check" size="small" severity="success"
                                        @click="approveApplication(slotProps.data)" />
                                    <Button icon="pi pi-arrow-left" rounded text severity="warning" size="small"
                                        @click="revertStatus(slotProps.data)" v-tooltip.top="'Mark as Pending'" />
                                    <Button icon="pi pi-eye" rounded text size="small"
                                        @click="viewProfile(slotProps.data)" v-tooltip.top="'View Profile'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Denied Tab -->
            <Card>
                <template #header>
                    <div class="flex items-center gap-2 p-4">
                        <i class="pi pi-times-circle text-red-600 text-xl"></i>
                        <span class="font-semibold">Marked as Denied ({{ deniedList.length }})</span>
                    </div>
                </template>
                <template #content>
                    <div v-if="deniedList.length === 0" class="text-center py-8 text-gray-500">
                        No applicants marked as denied yet
                    </div>
                    <DataTable v-else :value="deniedList" responsiveLayout="scroll" class="text-sm">
                        <Column field="profile.last_name" header="Name" sortable>
                            <template #body="slotProps">
                                <div class="font-medium">
                                    {{ slotProps.data.profile.last_name }}, {{ slotProps.data.profile.first_name }}
                                </div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.profile.contact_no }}</div>
                            </template>
                        </Column>
                        <Column field="program.shortname" header="Program" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.program?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="course.shortname" header="Course" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.course?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="year_level" header="Year" sortable />
                        <Column field="date_filed" header="Date Filed" sortable>
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.date_filed) }}
                            </template>
                        </Column>
                        <Column header="Actions" :style="{ width: '300px' }">
                            <template #body="slotProps">
                                <div class="flex gap-2 items-center">
                                    <Button v-if="hasRole('administrator') || hasRole('program_manager')"
                                        label="Confirm" icon="pi pi-times" size="small" severity="danger"
                                        @click="denyApplication(slotProps.data)" />
                                    <Button icon="pi pi-arrow-left" rounded text severity="warning" size="small"
                                        @click="revertStatus(slotProps.data)" v-tooltip.top="'Mark as Pending'" />
                                    <Button icon="pi pi-eye" rounded text size="small"
                                        @click="viewProfile(slotProps.data)" v-tooltip.top="'View Profile'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApprovalDialog" modal header="Approve Application" :style="{ width: '600px' }">
            <div v-if="selectedRecord" class="space-y-4">
                <!-- Profile Information -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded">
                    <div class="font-semibold text-blue-900 mb-2">
                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">
                        Contact: {{ selectedRecord.profile.contact_no }}
                    </div>
                </div>

                <!-- Academic Details -->
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 border border-gray-200 rounded">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Program</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.program?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Course</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.course?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Year Level</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.year_level || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date Filed</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ formatDate(selectedRecord.date_filed) }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Approval Date</label>
                    <Calendar v-model="approvalForm.date_approved" showIcon class="w-full" :maxDate="new Date()" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks (Optional)</label>
                    <Textarea v-model="approvalForm.remarks" rows="3" placeholder="Add any remarks..." class="w-full" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showApprovalDialog = false" />
                <Button label="Approve" severity="success" @click="confirmApprove" :loading="approvalForm.processing" />
            </template>
        </Dialog>

        <!-- Deny Dialog -->
        <Dialog v-model:visible="showDenyDialog" modal header="Confirm Deny Application" :style="{ width: '500px' }">
            <div v-if="selectedRecord" class="space-y-4">
                <div class="p-4 bg-red-50 border border-red-200 rounded">
                    <div class="font-semibold text-red-900">
                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ selectedRecord.program?.shortname }} - {{ selectedRecord.course?.shortname }}
                    </div>
                </div>

                <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    This action will permanently deny the application.
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Denial Reason</label>
                    <Select v-model="denyForm.reason" :options="declineReasons" optionLabel="label" optionValue="value"
                        placeholder="Select a reason" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Details</label>
                    <Textarea v-model="denyForm.details" rows="3" placeholder="Provide specific details..." />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDenyDialog = false" />
                <Button label="Confirm Deny" severity="danger" @click="confirmDeny" :loading="denyForm.processing" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';

// PrimeVue Components

// Custom Components
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';

// Permission composable
const { hasRole } = usePermission();

const props = defineProps({
    reviewed_applicants: Array,
    decline_reasons: Object,
});

// State
const filters = ref({
    status: null,
    name: '',
    program: ''
});

const approvalForm = useForm({
    date_approved: new Date(),
    remarks: ''
});

const denyForm = useForm({
    reason: '',
    details: ''
});

const selectedRecord = ref(null);
const showApprovalDialog = ref(false);
const showDenyDialog = ref(false);

// Computed properties
const statusOptions = [
    { label: 'All Statuses', value: null },
    { label: 'Approved', value: 'approved' },
    { label: 'Denied', value: 'denied' }
];

const declineReasons = computed(() => {
    if (!props.decline_reasons) return [];
    return Object.entries(props.decline_reasons).map(([value, label]) => ({
        value,
        label
    }));
});

const approvedList = computed(() => {
    return filterApplicants('approved');
});

const deniedList = computed(() => {
    return filterApplicants('denied');
});

const stats = computed(() => {
    const all = props.reviewed_applicants || [];
    return {
        total: all.length,
        approved: all.filter(r => r.unified_status === 'approved').length,
        denied: all.filter(r => r.unified_status === 'denied').length
    };
});

// Methods
const filterApplicants = (status) => {
    let list = (props.reviewed_applicants || [])
        .filter(r => !filters.value.status || r.unified_status === filters.value.status);

    if (status) {
        list = list.filter(r => r.unified_status === status);
    }

    if (filters.value.name) {
        const name = filters.value.name.toLowerCase();
        list = list.filter(r => {
            const fullName = `${r.profile.first_name} ${r.profile.last_name}`.toLowerCase();
            return fullName.includes(name);
        });
    }

    if (filters.value.program) {
        list = list.filter(r => r.program && r.program.id == filters.value.program);
    }

    return list;
};

const applyFilters = () => {
    // Filters are reactive, so no need to do anything else
};

const openApprovalDialog = (record) => {
    selectedRecord.value = record;
    approvalForm.reset();
    showApprovalDialog.value = true;
};

const openDenyDialog = (record) => {
    selectedRecord.value = record;
    denyForm.reset();
    showDenyDialog.value = true;
};

const approveApplication = (record) => {
    openApprovalDialog(record);
};

const denyApplication = (record) => {
    openDenyDialog(record);
};

const confirmApprove = () => {
    if (!selectedRecord.value) return;

    approvalForm.post(route('scholarship.record.approve', selectedRecord.value.id), {
        onSuccess: () => {
            showApprovalDialog.value = false;
            toast.success('Application approved successfully');
            refreshPage();
        },
        onError: (errors) => {
            toast.error('Failed to approve application');
            console.error(errors);
        }
    });
};

const confirmDeny = () => {
    if (!selectedRecord.value || !denyForm.reason || !denyForm.details) {
        toast.error('Please fill in all required fields');
        return;
    }

    denyForm.post(route('scholarship.record.decline', selectedRecord.value.id), {
        onSuccess: () => {
            showDenyDialog.value = false;
            toast.success('Application denied successfully');
            refreshPage();
        },
        onError: (errors) => {
            toast.error('Failed to deny application');
            console.error(errors);
        }
    });
};

const revertStatus = (record) => {
    router.patch(route('scholarship.record.update-status', record.id), {
        unified_status: 'pending'
    }, {
        onSuccess: () => {
            toast.success('Status reverted to pending');
            refreshPage();
        },
        onError: () => {
            toast.error('Failed to revert status');
        }
    });
};

const viewProfile = (record) => {
    router.visit(route('scholarship.profile.show', record.profile.profile_id));
};

const formatDate = (date) => {
    return moment(date).format('MMM DD, YYYY');
};

const refreshPage = () => {
    router.reload({
        only: ['reviewed_applicants'],
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
}
</style>
