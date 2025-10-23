<template>
    <Panel>
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-clipboard-check text-xl text-blue-600"></i>
                <span class="font-semibold text-lg">Application Review</span>
            </div>
        </template>

        <!-- Application Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card v-if="props.showApplicantName">
                <template #content>
                    <div class="flex items-center gap-3">
                        <Avatar :label="getInitials(application.profile)" size="large" shape="circle"
                            :style="{ backgroundColor: '#3B82F6', color: '#ffffff' }" />
                        <div>
                            <div class="font-semibold text-gray-800">
                                {{ getFullName(application.profile) }}
                            </div>
                            <div class="text-sm text-gray-600">
                                ID: {{ application.profile?.profile_id }}
                            </div>
                            <div class="text-sm text-green-600 font-medium"
                                v-if="application.profile?.gross_monthly_income">
                                Income: ₱{{ formatCurrency(application.profile.gross_monthly_income) }}
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-2 mb-2">
                            <i class="pi pi-flag text-blue-600"></i>
                            <div class="text-sm text-gray-600">Current Status</div>
                        </div>
                        <Chip :label="getApprovalStatusLabel(application.approval_status)"
                            :severity="getApprovalStatusSeverity(application.approval_status)" class="text-sm" />
                    </div>
                </template>
            </Card>

            <Card v-if="!props.showApplicantName">
                <template #content>
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-2 mb-2">
                            <i class="pi pi-wallet text-green-600"></i>
                            <div class="text-sm text-gray-600">Parent's Gross Monthly Income</div>
                        </div>
                        <div class="font-medium text-gray-800">
                            <div class="text-sm text-green-600 font-medium"
                                v-if="application.profile?.gross_monthly_income">
                                ₱{{ formatCurrency(application.profile.gross_monthly_income) }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium" v-else>
                                N/A
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Academic & Program Details -->
        <Card class="mb-6">
            <template #title>
                <div class="flex items-center gap-2 p-2">
                    <i class="pi pi-graduation-cap text-lg text-green-600"></i>
                    <span class="font-medium">Academic Information</span>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-600">Program</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.program?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">School</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.school?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Course</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.course?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Year Level</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.year_level || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Academic Year</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.academic_year || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Term</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.term || 'N/A' }}
                        </div>
                    </div>
                    <div v-if="application.gwa">
                        <label class="text-xs font-medium text-gray-600">GWA</label>
                        <div class="font-medium text-gray-800 text-sm">
                            {{ application.gwa }}
                            <Tag v-if="isEligibleForAutoApproval(application.gwa)" value="Auto" severity="success"
                                class="ml-1 text-xs" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Conditional Deadline Status (show if conditional approval) -->
        <Card v-if="application.approval_status === 'conditional'" class="mb-6">
            <template #header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-hourglass text-lg text-orange-600"></i>
                        <span class="font-medium">Conditional Approval Status</span>
                    </div>
                    <Button label="Edit" icon="pi pi-pencil" size="small" severity="secondary" outlined
                        @click="openEditConditionalDialog" />
                </div>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div class="p-4 border-l-4 border-yellow-500 bg-yellow-50">
                        <div class="flex items-center gap-2 text-yellow-800 mb-2">
                            <i class="pi pi-exclamation-triangle text-lg"></i>
                            <span class="font-medium">Conditional Approval Active</span>
                        </div>
                        <div class="text-sm text-yellow-700">
                            This application has been conditionally approved and is awaiting compliance with specified
                            requirements.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Deadline</label>
                            <div class="font-medium text-gray-800">
                                {{ application.conditional_deadline ? formatDateTime(application.conditional_deadline) :
                                    'No deadline set' }}
                                <Tag v-if="isDeadlineExpired(application.conditional_deadline)" value="Expired"
                                    severity="danger" class="ml-2" />
                                <Tag v-else-if="isDeadlineApproaching(application.conditional_deadline)"
                                    value="Approaching" severity="warning" class="ml-2" />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Time Remaining</label>
                            <div class="font-medium text-gray-800">
                                {{ getTimeRemaining(application.conditional_deadline) }}
                            </div>
                        </div>
                    </div>

                    <div v-if="application.conditional_requirements">
                        <label class="text-sm font-medium text-gray-600">Required Conditions</label>
                        <div class="mt-2 p-3 bg-gray-50 rounded border">
                            <div class="text-sm text-gray-800">
                                {{ application.conditional_requirements }}
                            </div>
                        </div>
                    </div>

                    <div v-if="application.conditional_deadline_notified_at" class="text-xs text-gray-500">
                        <i class="pi pi-info-circle mr-1"></i>
                        Reminder sent: {{ formatDateTime(application.conditional_deadline_notified_at) }}
                    </div>
                </div>
            </template>
        </Card>

        <!-- Requirements Status -->
        <Card class="mb-6" v-if="application.requirements && application.requirements.length > 0">
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-verified text-lg text-blue-600"></i>
                    <span class="font-medium">Requirements Status</span>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="requirement in application.requirements" :key="requirement.id"
                        class="flex items-center justify-between p-3 border rounded-lg">
                        <div class="flex items-center gap-3">
                            <i
                                :class="requirement.file_path ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500'"></i>
                            <span class="font-medium">{{ requirement.requirement?.name || 'Requirement' }}</span>
                        </div>
                        <Chip :label="requirement.file_path ? 'Submitted' : 'Missing'"
                            :severity="requirement.file_path ? 'success' : 'danger'" class="text-xs" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Approval History -->
        <Card class="mb-6" v-if="application.approvalHistory && application.approvalHistory.length > 0">
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-clock text-lg text-purple-600"></i>
                    <span class="font-medium">Approval History</span>
                </div>
            </template>
            <template #content>
                <Timeline :value="application.approvalHistory" class="w-full">
                    <template #content="slotProps">
                        <Card class="mt-3">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-800">
                                            {{ getStatusChangeDescription(slotProps.item) }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            By: {{ slotProps.item.performer?.name || 'System' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ formatDateTime(slotProps.item.performed_at) }}
                                        </div>
                                    </div>
                                    <Chip :label="slotProps.item.new_status"
                                        :severity="getApprovalStatusSeverity(slotProps.item.new_status)"
                                        class="text-xs" />
                                </div>
                                <div v-if="slotProps.item.remarks" class="mt-2 text-sm text-gray-600">
                                    <strong>Remarks:</strong> {{ slotProps.item.remarks }}
                                </div>
                            </template>
                        </Card>
                    </template>
                </Timeline>
            </template>
        </Card>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <div class="flex gap-3">
                <Button v-if="canApprove(application)" label="Approve" icon="pi pi-thumbs-up" severity="success"
                    @click="showApprovalDialog = true" />
                <Button v-if="canDecline(application)" label="Decline" icon="pi pi-thumbs-down" severity="danger"
                    outlined @click="showDeclineDialog = true" />
                <Button v-if="canSetConditional(application)" label="Conditional Approval"
                    icon="pi pi-exclamation-circle" severity="info" outlined @click="showConditionalDialog = true" />
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
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Approval Date
                    </label>
                    <Calendar v-model="approvalForm.date_approved" showIcon class="w-full" :maxDate="new Date()" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Remarks (Optional)
                    </label>
                    <Textarea v-model="approvalForm.remarks" rows="3" class="w-full"
                        placeholder="Add any additional comments..." />
                </div>

                <div v-if="isEligibleForAutoApproval(application.gwa)"
                    class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-800">
                        <i class="pi pi-info-circle"></i>
                        <span class="font-medium">Auto-Approval Eligible</span>
                    </div>
                    <div class="text-sm text-blue-700 mt-1">
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
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Details <span class="text-red-500">*</span>
                    </label>
                    <Textarea v-model="declineForm.details" rows="4" class="w-full"
                        placeholder="Please provide specific details about the decline decision..."
                        :class="{ 'p-invalid': declineForm.errors.details }" />
                    <small v-if="declineForm.errors.details" class="p-error">
                        {{ declineForm.errors.details }}
                    </small>
                </div>

                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 text-red-800">
                        <i class="pi pi-exclamation-triangle"></i>
                        <span class="font-medium">Important Notice</span>
                    </div>
                    <div class="text-sm text-red-700 mt-1">
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

        <!-- Conditional Approval Dialog -->
        <Dialog v-model:visible="showConditionalDialog" modal header="Conditional Approval" :style="{ width: '500px' }"
            class="p-fluid">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Conditions Required <span class="text-red-500">*</span>
                    </label>
                    <Textarea v-model="conditionalForm.conditions" rows="4" class="w-full"
                        placeholder="Specify the conditions that must be met for full approval..."
                        :class="{ 'p-invalid': conditionalForm.errors.conditions }" />
                    <small v-if="conditionalForm.errors.conditions" class="p-error">
                        {{ conditionalForm.errors.conditions }}
                    </small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Deadline for Compliance
                    </label>
                    <Calendar v-model="conditionalForm.deadline" showIcon class="w-full" :minDate="new Date()" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Approval Remarks (Optional)
                    </label>
                    <Textarea v-model="conditionalForm.remarks" rows="2" class="w-full"
                        placeholder="Add notes about the conditional approval..." />
                </div>

                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center gap-2 text-yellow-800">
                        <i class="pi pi-info-circle"></i>
                        <span class="font-medium">Conditional Approval</span>
                    </div>
                    <div class="text-sm text-yellow-700 mt-1">
                        The applicant will be notified of the conditions and deadline. The application will be
                        automatically
                        reviewed once conditions are met.
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showConditionalDialog = false" />
                <Button label="Set Conditional Approval" severity="info" @click="confirmConditional"
                    :loading="conditionalForm.processing" />
            </template>
        </Dialog>

        <!-- Edit Conditional Approval Dialog -->
        <Dialog v-model:visible="showEditConditionalDialog" modal header="Edit Conditional Approval"
            :style="{ width: '500px' }" class="p-fluid">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Update Conditions
                    </label>
                    <Textarea v-model="editConditionalForm.conditions" rows="4" class="w-full"
                        placeholder="Update the conditions that must be met for full approval..."
                        :class="{ 'p-invalid': editConditionalForm.errors.conditions }" />
                    <small v-if="editConditionalForm.errors.conditions" class="p-error">
                        {{ editConditionalForm.errors.conditions }}
                    </small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Update Deadline for Compliance
                    </label>
                    <Calendar v-model="editConditionalForm.deadline" showIcon class="w-full" :minDate="new Date()" />
                    <small class="text-gray-500">Current deadline: {{ application.conditional_deadline ?
                        formatDateTime(application.conditional_deadline) : 'Not set' }}</small>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-800">
                        <i class="pi pi-info-circle"></i>
                        <span class="font-medium">Update Information</span>
                    </div>
                    <div class="text-sm text-blue-700 mt-1">
                        Changes to the deadline will reset any previous reminder notifications. The applicant will be
                        notified
                        of the updates.
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showEditConditionalDialog = false" />
                <Button label="Update Conditional Approval" severity="primary" @click="confirmEditConditional"
                    :loading="editConditionalForm.processing" />
            </template>
        </Dialog>
    </Panel>
</template>

<script setup>
/**
 * ApprovalWorkflow Component
 * 
 * This component handles the approval workflow for scholarship applications.
 * 
 * Status Management:
 * - Uses `approval_status` for workflow display (pending, approved, declined, conditional)
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

const emit = defineEmits(['approved', 'declined', 'conditionalApproval', 'refresh']);

// Dialog state
const showApprovalDialog = ref(false);
const showDeclineDialog = ref(false);
const showConditionalDialog = ref(false);
const showEditConditionalDialog = ref(false);

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

const conditionalForm = useForm({
    conditions: '',
    deadline: null,
    remarks: '',
    errors: {}
});

const editConditionalForm = useForm({
    conditions: '',
    deadline: null,
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
        case 'conditional':
            return 'info';
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

const isDeadlineExpired = (deadline) => {
    if (!deadline) return false;
    return moment().isAfter(moment(deadline));
};

const isDeadlineApproaching = (deadline) => {
    if (!deadline) return false;
    const now = moment();
    const deadlineDate = moment(deadline);
    const daysUntilDeadline = deadlineDate.diff(now, 'days');
    return daysUntilDeadline <= 3 && daysUntilDeadline >= 0;
};

const getTimeRemaining = (deadline) => {
    if (!deadline) return 'No deadline set';

    const now = moment();
    const deadlineDate = moment(deadline);

    if (now.isAfter(deadlineDate)) {
        const duration = moment.duration(now.diff(deadlineDate));
        const days = duration.days();
        const hours = duration.hours();

        if (days > 0) {
            return `Expired ${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            return `Expired ${hours} hour${hours > 1 ? 's' : ''} ago`;
        }
    } else {
        const duration = moment.duration(deadlineDate.diff(now));
        const days = duration.days();
        const hours = duration.hours();

        if (days > 0) {
            return `${days} day${days > 1 ? 's' : ''} remaining`;
        } else if (hours > 0) {
            return `${hours} hour${hours > 1 ? 's' : ''} remaining`;
        } else {
            return 'Less than 1 hour remaining';
        }
    }
};

const getStatusChangeDescription = (historyItem) => {
    const statusChanges = {
        'approved': 'Application approved',
        'declined': 'Application declined',
        'conditional': 'Conditional approval set',
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
    return application.approval_status === 'pending' || application.approval_status === 'conditional';
};

const canDecline = (application) => {
    return application.approval_status === 'pending' || application.approval_status === 'conditional';
};

const canSetConditional = (application) => {
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

const confirmConditional = () => {
    // Validate required fields
    conditionalForm.errors = {};
    if (!conditionalForm.conditions) {
        conditionalForm.errors.conditions = 'Please specify the conditions required';
        return;
    }

    conditionalForm.post(route('scholarship.record.conditional', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConditionalDialog.value = false;
            emit('conditionalApproval', props.application);
            emit('refresh');
        },
        onError: (errors) => {
            console.error('Conditional approval failed:', errors);
        }
    });
};

const confirmEditConditional = () => {
    // Clear previous errors
    editConditionalForm.errors = {};

    // Check if at least one field is being updated
    if (!editConditionalForm.conditions && !editConditionalForm.deadline) {
        editConditionalForm.errors.conditions = 'Please update at least one field';
        return;
    }

    editConditionalForm.put(route('scholarship.record.conditional.update', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditConditionalDialog.value = false;
            emit('conditionalApproval', props.application);
            emit('refresh');
            // Reset form
            editConditionalForm.reset();
        },
        onError: (errors) => {
            console.error('Conditional approval update failed:', errors);
        }
    });
};

// Initialize edit form when dialog opens
const openEditConditionalDialog = () => {
    editConditionalForm.conditions = props.application.conditional_requirements || '';
    editConditionalForm.deadline = props.application.conditional_deadline ? new Date(props.application.conditional_deadline) : null;
    showEditConditionalDialog.value = true;
};

const viewFullProfile = () => {
    router.visit(route('profile.show', props.application.profile.profile_id));
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