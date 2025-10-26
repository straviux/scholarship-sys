<template>
    <div class="space-y-3">
        <!-- Application Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="border rounded p-3">
                <div class="text-xs text-gray-600 mb-1">Current Status</div>
                <Chip :label="getApprovalStatusLabel(application.approval_status)"
                    :severity="getApprovalStatusSeverity(application.approval_status)" class="text-sm" />
            </div>

            <div class="border rounded p-3" v-if="!props.showApplicantName">
                <div class="text-xs text-gray-600 mb-1">Parent's Gross Monthly Income</div>
                <div class="font-medium text-sm">
                    <span v-if="application.profile?.gross_monthly_income" class="text-green-600">
                        ₱{{ formatCurrency(application.profile.gross_monthly_income) }}
                    </span>
                    <span v-else class="text-gray-600">N/A</span>
                </div>
            </div>
        </div>

        <!-- Academic & Program Details -->
        <div class="border rounded p-3">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                <i class="pi pi-graduation-cap text-green-600 text-sm"></i>
                Academic
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="text-gray-600">Program</label>
                    <div class="font-medium">{{ application.program?.shortname || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">School</label>
                    <div class="font-medium">{{ application.school?.shortname || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Course</label>
                    <div class="font-medium">{{ application.course?.shortname || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Year Level</label>
                    <div class="font-medium">{{ application.year_level || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Academic Year</label>
                    <div class="font-medium">{{ application.academic_year || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Term</label>
                    <div class="font-medium">{{ application.term || 'N/A' }}</div>
                </div>
                <div v-if="application.gwa">
                    <label class="text-gray-600">GWA</label>
                    <div class="font-medium">
                        {{ application.gwa }}
                        <Tag v-if="isEligibleForAutoApproval(application.gwa)" value="Auto" severity="success"
                            class="ml-1 text-xs" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Requirements Status -->
        <div v-if="application.requirements && application.requirements.length > 0" class="border rounded p-3">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                <i class="pi pi-verified text-blue-600 text-sm"></i>
                Requirements Status
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="requirement in application.requirements" :key="requirement.id"
                    class="flex items-center justify-between p-2 border rounded">
                    <div class="flex items-center gap-2 text-xs">
                        <i
                            :class="requirement.file_path ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500'"></i>
                        <span class="font-medium">{{ requirement.requirement?.name || 'Requirement' }}</span>
                    </div>
                    <Chip :label="requirement.file_path ? 'Submitted' : 'Missing'"
                        :severity="requirement.file_path ? 'success' : 'danger'" class="text-xs" />
                </div>
            </div>
        </div>

        <!-- Approval History -->
        <div v-if="application.approvalHistory && application.approvalHistory.length > 0" class="border rounded p-3">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                <i class="pi pi-clock text-purple-600 text-sm"></i>
                Approval History
            </h4>
            <Timeline :value="application.approvalHistory" class="w-full">
                <template #content="slotProps">
                    <div class="mt-2 border rounded p-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-xs">
                                    {{ getStatusChangeDescription(slotProps.item) }}
                                </div>
                                <div class="text-xs text-gray-600">
                                    By: {{ slotProps.item.performer?.name || 'System' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ formatDateTime(slotProps.item.performed_at) }}
                                </div>
                            </div>
                            <Chip :label="slotProps.item.new_status"
                                :severity="getApprovalStatusSeverity(slotProps.item.new_status)" class="text-xs" />
                        </div>
                        <div v-if="slotProps.item.remarks" class="mt-2 text-xs text-gray-600">
                            <strong>Remarks:</strong> {{ slotProps.item.remarks }}
                        </div>
                    </div>
                </template>
            </Timeline>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-2">
            <div class="flex gap-2">
                <Button v-if="canApprove(application)" label="Approve" icon="pi pi-thumbs-up" severity="success"
                    @click="showApprovalDialog = true" />
                <Button v-if="canDecline(application)" label="Decline" icon="pi pi-thumbs-down" severity="danger"
                    outlined @click="showDeclineDialog = true" />
            </div>

            <div class="flex gap-2">
                <Button label="View Full Profile" icon="pi pi-id-card" severity="secondary" outlined
                    @click="viewFullProfile" />
                <Button label="Print Application" icon="pi pi-file-pdf" severity="secondary" outlined
                    @click="printApplication" />
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApprovalDialog" modal header="Approve Application" :style="{ width: '500px' }"
            class="p-fluid">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-700 mb-1">
                        Approval Date
                    </label>
                    <Calendar v-model="approvalForm.date_approved" showIcon class="w-full" :maxDate="new Date()" />
                </div>

                <div>
                    <label class="block text-xs text-gray-700 mb-1">
                        Remarks (Optional)
                    </label>
                    <Textarea v-model="approvalForm.remarks" rows="3" class="w-full text-xs"
                        placeholder="Add any additional comments..." />
                </div>

                <div v-if="isEligibleForAutoApproval(application.gwa)"
                    class="p-3 bg-blue-50 border border-blue-200 rounded">
                    <div class="flex items-center gap-2 text-blue-800 text-xs">
                        <i class="pi pi-info-circle"></i>
                        <span class="font-medium">Auto-Approval Eligible</span>
                    </div>
                    <div class="text-xs text-blue-700 mt-1">
                        This applicant meets the criteria for automatic approval based on their GWA of {{
                            application.gwa }}.
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showApprovalDialog = false" />
                <Button label="Approve Application" severity="success" @click="confirmApproval"
                    :loading="approvalForm.processing" />
            </template>
        </Dialog>

        <!-- Decline Dialog -->
        <Dialog v-model:visible="showDeclineDialog" modal header="Decline Application" :style="{ width: '500px' }"
            class="p-fluid">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-700 mb-1">
                        Reason for Decline <span class="text-red-500">*</span>
                    </label>
                    <Select v-model="declineForm.reason" :options="declineReasonsOptions" optionLabel="label"
                        optionValue="value" placeholder="Select a reason" class="w-full"
                        :class="{ 'p-invalid': declineForm.errors.reason }" />
                    <small v-if="declineForm.errors.reason" class="p-error">
                        {{ declineForm.errors.reason }}
                    </small>
                </div>

                <div>
                    <label class="block text-xs text-gray-700 mb-1">
                        Additional Details <span class="text-red-500">*</span>
                    </label>
                    <Textarea v-model="declineForm.details" rows="4" class="w-full text-xs"
                        placeholder="Please provide specific details about the decline decision..."
                        :class="{ 'p-invalid': declineForm.errors.details }" />
                    <small v-if="declineForm.errors.details" class="p-error">
                        {{ declineForm.errors.details }}
                    </small>
                </div>

                <div class="p-3 bg-red-50 border border-red-200 rounded">
                    <div class="flex items-center gap-2 text-red-800 text-xs">
                        <i class="pi pi-exclamation-triangle"></i>
                        <span class="font-medium">Important Notice</span>
                    </div>
                    <div class="text-xs text-red-700 mt-1">
                        This action will permanently decline the application. The applicant will be notified via email.
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeclineDialog = false" />
                <Button label="Decline Application" severity="danger" @click="confirmDecline"
                    :loading="declineForm.processing" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
/**
 * ApprovalWorkflow Component
 * 
 * This component handles the approval workflow for scholarship applications.
 * 
 * Status Management:
 * - Uses `approval_status` for workflow display (pending, approved, declined)
 * - When approved, backend also sets:
 *   - `scholarship_status = 1` (Active/Approved)
 *   - `scholarship_status_remarks = 'Active Scholar'`
 * - When declined, backend also sets:
 *   - `scholarship_status = 4` (Cancelled/Declined)
 *   - `scholarship_status_remarks = 'Application Declined'`
 * 
 * The dual status system ensures:
 * - approval_status: Tracks workflow state (for this component and approval history)
 * - scholarship_status: Primary status for filtering and business logic
 */
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import moment from 'moment';

// PrimeVue Components
import Panel from 'primevue/panel';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Chip from 'primevue/chip';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Timeline from 'primevue/timeline';

const props = defineProps({
    application: {
        type: Object,
        required: true
    },
    approvalStatuses: {
        type: Array,
        default: () => []
    },
    declineReasons: {
        type: Object,
        default: () => ({})
    },
    autoApprovalConfig: {
        type: Object,
        default: () => ({})
    },
    showApplicantName: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['approved', 'declined', 'refresh']);

// Dialog state
const showApprovalDialog = ref(false);
const showDeclineDialog = ref(false);

// Forms
const approvalForm = useForm({
    date_approved: new Date(),
    remarks: ''
});

const declineForm = useForm({
    reason: '',
    details: '',
    errors: {}
});

// Computed properties
const declineReasonsOptions = computed(() => {
    if (Array.isArray(props.declineReasons)) {
        return props.declineReasons;
    }

    // Convert object format to array format for dropdown
    return Object.entries(props.declineReasons).map(([value, label]) => ({
        value,
        label
    }));
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

const getInitials = (profile) => {
    if (!profile) return 'NA';
    const first = profile.first_name?.charAt(0) || '';
    const last = profile.last_name?.charAt(0) || '';
    return (first + last).toUpperCase() || 'NA';
};

const getApprovalStatusLabel = (status) => {
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
            return 'success';
        default:
            return 'secondary';
    }
};

const formatDate = (date) => {
    return moment(date).format('MMM DD, YYYY');
};

const formatDateTime = (datetime) => {
    return moment(datetime).format('MMM DD, YYYY HH:mm');
};

const formatCurrency = (amount) => {
    if (!amount) return '0.00';
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const getStatusChangeDescription = (historyItem) => {
    const statusChanges = {
        'approved': 'Application approved',
        'declined': 'Application declined',
        'auto_approved': 'Automatically approved',
        'pending': 'Status changed to pending'
    };
    return statusChanges[historyItem.new_status] || `Status changed to ${historyItem.new_status}`;
};

const isEligibleForAutoApproval = (gwa) => {
    if (!gwa || !props.autoApprovalConfig?.grade_threshold) return false;
    return parseFloat(gwa) >= parseFloat(props.autoApprovalConfig.grade_threshold);
};

// Permission checks
const canApprove = (application) => {
    return application.approval_status === 'pending';
};

const canDecline = (application) => {
    return application.approval_status === 'pending';
};

// Action methods
const confirmApproval = () => {
    approvalForm.post(route('scholarship.record.approve', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApprovalDialog.value = false;
            emit('approved', props.application);
            emit('refresh');
        },
        onError: (errors) => {
            console.error('Approval failed:', errors);
        }
    });
};

const confirmDecline = () => {
    // Validate required fields
    declineForm.errors = {};
    if (!declineForm.reason) {
        declineForm.errors.reason = 'Please select a reason for decline';
        return;
    }
    if (!declineForm.details) {
        declineForm.errors.details = 'Please provide additional details';
        return;
    }

    declineForm.post(route('scholarship.record.decline', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeclineDialog.value = false;
            emit('declined', props.application);
            emit('refresh');
        },
        onError: (errors) => {
            console.error('Decline failed:', errors);
        }
    });
};

const viewFullProfile = () => {
    router.visit(route('scholarship.profile.show', props.application.profile.profile_id));
};

const printApplication = () => {
    window.print();
};
</script>

<style scoped>
.p-fluid .p-field {
    margin-bottom: 1rem;
}

.p-error {
    color: #e24c4c;
    font-size: 0.875rem;
}

.p-invalid {
    border-color: #e24c4c;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>