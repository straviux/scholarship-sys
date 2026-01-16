<template>
    <div class="space-y-3">
        <!-- Application Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="border rounded p-3">
                <div class="text-xs text-gray-600 mb-1">Current Status</div>
                <Chip :label="getStatusLabel(application.unified_status)"
                    :severity="getStatusSeverity(application.unified_status)" class="text-sm" />
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

            <!-- <pre>{{ application }}</pre> -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="text-gray-600">Program</label>
                    <div class="font-medium">{{ application.program?.shortname ||
                        application.profile?.scholarship_grant?.[0]?.program?.shortname || 'N/A' }}
                    </div>
                </div>
                <div>
                    <label class="text-gray-600">School</label>
                    <div class="font-medium">{{ application.school?.shortname ||
                        application.profile?.scholarship_grant?.[0]?.school?.shortname || 'N/A' }}
                    </div>
                </div>
                <div>
                    <label class="text-gray-600">Course</label>
                    <div class="font-medium">{{ application.course?.shortname ||
                        application.profile?.scholarship_grant?.[0]?.course?.shortname || 'N/A' }}
                    </div>
                </div>
                <div>
                    <label class="text-gray-600">Year Level</label>
                    <div class="font-medium">{{ application.year_level ||
                        application.profile?.scholarship_grant?.[0]?.year_level || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Academic Year</label>
                    <div class="font-medium">{{ application.academic_year ||
                        application.profile?.scholarship_grant?.[0]?.academic_year || 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-gray-600">Term</label>
                    <div class="font-medium">{{ application.term || application.profile?.scholarship_grant?.[0]?.term ||
                        'N/A' }}</div>
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
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';

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

const emit = defineEmits(['refresh']);

// Permission composable
const { hasPermission } = usePermission();

// Status composable
const { getStatusLabel, getStatusSeverity } = useScholarshipStatus();

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