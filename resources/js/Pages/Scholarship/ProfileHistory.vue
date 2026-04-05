<template>

    <Head :title="`${getFullName(profile)} - Scholarship History`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Button icon="pi pi-arrow-left" @click="goBack" outlined rounded size="small" />
                <span>{{ getFullName(profile) }} - Scholarship History</span>
            </div>
        </template>

        <div class="max-w-8xl mx-auto py-2">
            <!-- Profile Header -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-user text-xl text-blue-600"></i>
                        <span class="font-semibold text-lg">Profile</span>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-800 border-b pb-2">Basic Information</h4>
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm text-gray-600">Full Name:</span>
                                <div class="font-medium">{{ getFullName(profile) }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Profile ID:</span>
                                <div class="font-medium">{{ profile.unique_id || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Gender:</span>
                                <div class="font-medium">{{ profile.gender || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Birthdate:</span>
                                <div class="font-medium">{{ formatDate(profile.birthdate) || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-800 border-b pb-2">Contact Information</h4>
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm text-gray-600">Phone:</span>
                                <div class="font-medium">{{ profile.contact_no || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Email:</span>
                                <div class="font-medium">{{ profile.email || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Address:</span>
                                <div class="font-medium">
                                    {{ [profile.address, profile.barangay, profile.municipality].filter(Boolean).join(`,
                                    `) ||
                                        `N/A` }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Statistics -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-800 border-b pb-2">Scholarship Summary</h4>
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm text-gray-600">Total Applications:</span>
                                <div class="font-medium">{{ scholarshipRecords.length }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Approved:</span>
                                <div class="font-medium text-green-600">{{ getStatusCount('approved') +
                                    getStatusCount('active')
                                }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Pending:</span>
                                <div class="font-medium text-yellow-600">{{ getStatusCount('pending') }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Denied:</span>
                                <div class="font-medium text-red-600">{{ getStatusCount('denied') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Scholarship Records Timeline -->
            <Panel class="mt-4">
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-clock text-lg text-blue-600"></i>
                            <span class="font-semibold">Scholarship History</span>
                        </div>
                        <Tag :value="`${scholarshipRecords.length} record(s)`" severity="info" />
                    </div>
                </template>

                <div class="space-y-6">
                    <div v-for="(record, index) in scholarshipRecords" :key="record.id"
                        class="border rounded-lg p-4 bg-gray-50 hover:bg-gray-100 transition-colors">

                        <!-- Record Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">{{ index + 1 }}</span>
                                </div>
                                <div>
                                    <h5 class="font-semibold text-gray-800">{{ record.program?.name || 'Unknown Program'
                                    }}</h5>
                                    <p class="text-sm text-gray-600">{{ record.program?.shortname || '' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Chip :label="record.unified_status"
                                    :severity="getStatusSeverity(record.unified_status)" />
                                <Button icon="pi pi-clipboard-check" severity="success" size="small" rounded outlined
                                    v-tooltip.top="'Review Application'" @click="reviewApplication(record)" />
                            </div>
                        </div>

                        <!-- Record Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Course:</span>
                                <div class="font-medium">{{ record.course?.name || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">School:</span>
                                <div class="font-medium">{{ record.school?.name || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Year Level:</span>
                                <div class="font-medium">{{ record.year_level || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Academic Year:</span>
                                <div class="font-medium">{{ record.academic_year || 'N/A' }}</div>
                            </div>
                        </div>

                        <!-- Timeline Info -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-sm text-gray-600">Applied:</span>
                                    <div class="font-medium">{{ formatDate(record.created_at) }}</div>
                                </div>
                                <div v-if="record.date_approved">
                                    <span class="text-sm text-gray-600">Approved:</span>
                                    <div class="font-medium text-green-600">{{ formatDate(record.date_approved) }}</div>
                                </div>
                                <div v-if="record.start_date">
                                    <span class="text-sm text-gray-600">Started:</span>
                                    <div class="font-medium">{{ formatDate(record.start_date) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks if any -->
                        <div v-if="record.remarks" class="mt-4 pt-4 border-t border-gray-200">
                            <span class="text-sm text-gray-600">Remarks:</span>
                            <div class="mt-1 text-sm text-gray-800 bg-yellow-50 p-2 rounded"
                                v-safe-html="record.remarks"></div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="scholarshipRecords.length === 0" class="text-center py-8">
                        <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">No Scholarship Records</h3>
                        <p class="text-gray-500">This profile has no scholarship application history.</p>
                    </div>
                </div>
            </Panel>
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
                        {{ getFullName(profile) }} - {{ selectedApplication.program?.shortname }}
                    </div>
                </div>
            </template>

            <ApprovalWorkflow v-if="selectedApplication" :application="selectedApplication"
                :decline-reasons="declineReasons || {}" :show-applicant-name="true" @approved="handleApprovalAction"
                @declined="handleApprovalAction" @conditionalApproval="handleApprovalAction" @refresh="refreshData" />
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
import moment from 'moment';

// PrimeVue Components

// Props
const props = defineProps({
    profile: Object,
    scholarshipRecords: Array,
    declineReasons: Object,
});

// Reactive state
const showApprovalDialog = ref(false);
const selectedApplication = ref(null);

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

const getStatusSeverity = (unifiedStatus) => {
    switch (unifiedStatus) {
        case 'approved':
            return 'success';
        case 'active':
            return 'success';
        case 'pending':
            return 'warning';
        case 'denied':
            return 'danger';
        case 'completed':
            return 'secondary';
        case 'withdrawn':
            return 'secondary';
        case 'loa':
            return 'warning';
        case 'suspended':
            return 'danger';
        case 'unknown':
            return 'secondary';
        default:
            return 'secondary';
    }
};

const formatDate = (date) => {
    if (!date) return null;
    return moment(date).format('MMM DD, YYYY');
};

const getStatusCount = (status) => {
    return props.scholarshipRecords.filter(record => {
        const grant = Array.isArray(record.scholarship_grant) ? record.scholarship_grant[0] : record.scholarship_grant;
        return grant?.unified_status === status;
    }).length;
};

// Action methods
const goBack = () => {
    router.visit(route('scholarship.profiles'));
};

const reviewApplication = (scholarshipRecord) => {
    selectedApplication.value = scholarshipRecord;
    showApprovalDialog.value = true;
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
</script>

<style scoped>
.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 15px;
    top: 50px;
    bottom: -30px;
    width: 2px;
    background: #e5e7eb;
}
</style>
