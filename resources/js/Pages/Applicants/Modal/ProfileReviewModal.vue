<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { usePermission } from '@/composable/permissions';
import { Tag } from 'primevue';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const { hasRole, hasPermission } = usePermission();

const props = defineProps({
    visible: Boolean,
    applicant: Object,
    applicants: Array,
});

const emit = defineEmits(['update:visible', 'interview', 'edit-profile', 'edit-requirements', 'closed']);

const reviewRequirements = ref([]);
const currentProfileIndex = ref(-1);
const currentApplicant = ref(null);
const showPreviewModal = ref(false);
const previewFile = ref(null);
const actionPopover = ref(null);

const hasPreviousProfile = computed(() => currentProfileIndex.value > 0);
const hasNextProfile = computed(() => currentProfileIndex.value < (props.applicants?.length || 0) - 1);
const canViewFullProfile = computed(() => hasPermission('scholarships.view'));
const canEditRequirements = computed(() => hasPermission('applicants.view'));
const canInterview = computed(() => hasRole('administrator') || hasRole('program_manager') || hasRole('screening_officer'));
const canEditProfile = computed(() => hasPermission('applicants.edit'));
const hasActionMenu = computed(() => canViewFullProfile.value || canEditRequirements.value || canInterview.value || canEditProfile.value);

function hideActionPopover() {
    actionPopover.value?.hide();
}

watch(() => props.applicant, (newApplicant) => {
    if (newApplicant && props.visible) {
        currentApplicant.value = newApplicant;
        currentProfileIndex.value = props.applicants?.findIndex(a => a.profile_id === newApplicant.profile_id) ?? -1;
        loadRequirements(newApplicant.profile_id);
    }
}, { immediate: true });

watch(() => props.visible, (val) => {
    if (!val) {
        hideActionPopover();
        currentApplicant.value = null;
        reviewRequirements.value = [];
        currentProfileIndex.value = -1;
        emit('closed');
    }
});

function close() {
    hideActionPopover();
    emit('update:visible', false);
}

const loadRequirements = async (profileId) => {
    try {
        const response = await axios.get(
            route('scholarship.profile.requirements-checklist', profileId)
        );
        reviewRequirements.value = response.data.requirements || [];
    } catch (error) {
        console.error('Error loading requirements:', error);
        reviewRequirements.value = [];
    }
};

const getApplicantInitials = (applicant) => {
    if (!applicant) return '';
    const firstInitial = applicant.first_name?.charAt(0) || '';
    const lastInitial = applicant.last_name?.charAt(0) || '';
    return `${firstInitial}${lastInitial}`.toUpperCase();
};

const getApplicantFullName = (applicant) => {
    if (!applicant) return '';
    const parts = [
        applicant.last_name,
        ',',
        applicant.first_name,
        applicant.middle_name,
        applicant.extension_name
    ].filter(Boolean);
    return parts.join(' ').replace(' ,', ',');
};

const getApplicantFullAddress = (applicant) => {
    if (!applicant) return '';
    const parts = [applicant.barangay, applicant.municipality, applicant.province].filter(Boolean);
    return parts.join(', ') || 'N/A';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return moment(date).format('MMM DD, YYYY');
};

const previewRequirementFile = (requirement) => {
    if (!requirement.file_path) return;
    previewFile.value = {
        file_name: requirement.file_name || requirement.name,
        file_url: requirement.file_path,
    };
    showPreviewModal.value = true;
};

const downloadRequirementFile = (requirement) => {
    if (!requirement.file_path) return;
    const link = document.createElement('a');
    link.href = requirement.file_path;
    link.download = requirement.file_name || requirement.name;
    link.style.display = 'none';
    document.body.appendChild(link);
    setTimeout(() => {
        link.click();
        document.body.removeChild(link);
    }, 0);
};

const navigateTo = (index) => {
    if (index < 0 || index >= (props.applicants?.length || 0)) return;
    hideActionPopover();
    currentProfileIndex.value = index;
    currentApplicant.value = props.applicants[index];
    loadRequirements(currentApplicant.value.profile_id);
};

const goToPreviousProfile = () => navigateTo(currentProfileIndex.value - 1);
const goToNextProfile = () => navigateTo(currentProfileIndex.value + 1);

const toggleActionPopover = (event) => {
    actionPopover.value?.toggle(event);
};

const markAsInterviewed = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('interview', currentApplicant.value);
};

const editProfile = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('edit-profile', currentApplicant.value);
};

const editRequirements = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('edit-requirements', currentApplicant.value);
};

const visitProfile = () => {
    if (!currentApplicant.value) return;
    router.visit(route('scholarship.profile.show', currentApplicant.value.profile_id));
};
</script>

<template>
    <IosModal :visible="visible" title="Profile Review" width="720px" max-width="95vw"
        :body-style="{ padding: '0', display: 'flex', flexDirection: 'column', minHeight: 0 }"
        @update:visible="val => { if (!val) close(); }">
        <template #header-right>
            <template v-if="hasActionMenu">
                <button class="ios-nav-btn ios-nav-action ios-nav-dropdown" @click="toggleActionPopover($event)">
                    Actions
                    <AppIcon name="chevron-down" :size="11" />
                </button>
                <Popover ref="actionPopover">
                    <div class="ios-action-menu">
                        <button v-if="canViewFullProfile" class="ios-action-item" @click="visitProfile">
                            <AppIcon name="eye" :size="14" class="ios-action-icon" />
                            <span>View Full Profile</span>
                        </button>
                        <button v-if="canEditRequirements" class="ios-action-item" @click="editRequirements">
                            <AppIcon name="book-check" :size="14" class="ios-action-icon" />
                            <span>Edit Requirements</span>
                        </button>
                        <button v-if="canInterview" class="ios-action-item" @click="markAsInterviewed">
                            <AppIcon name="comments" :size="14" class="ios-action-icon" />
                            <span>Interview</span>
                        </button>
                        <button v-if="canEditProfile" class="ios-action-item" @click="editProfile">
                            <AppIcon name="pencil" :size="14" class="ios-action-icon" />
                            <span>Edit Profile</span>
                        </button>
                    </div>
                </Popover>
            </template>
            <span v-else class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
        </template>

        <div class="ios-body" v-if="currentApplicant">
                    <!-- Applicant Header Card -->
                    <div class="ios-section ios-section-tight">
                        <div class="ios-card" style="padding: 14px 16px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <Avatar :label="getApplicantInitials(currentApplicant)" size="large" shape="circle"
                                    style="background: #007AFF; color: white; flex-shrink: 0;" />
                                <div style="flex: 1; min-width: 0;">
                                    <div class="ios-profile-name"
                                        style="font-size: 16px; font-weight: 600; cursor: pointer; letter-spacing: -0.4px;"
                                        @click="visitProfile">
                                        {{ getApplicantFullName(currentApplicant) }}
                                    </div>
                                    <div class="ios-profile-meta"
                                        style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px; font-size: 12px;">
                                        <span>
                                            <AppIcon name="phone" :size="10" style="margin-right: 3px;" />{{
                                                currentApplicant.contact_no || 'N/A' }}
                                        </span>
                                        <span>
                                            <AppIcon name="envelope" :size="10" style="margin-right: 3px;" />{{
                                                currentApplicant.email || 'N/A' }}
                                        </span>
                                        <span>
                                            <AppIcon name="calendar" :size="10" style="margin-right: 3px;" />{{
                                                formatDate(currentApplicant.date_filed) }}
                                        </span>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                        <Tag severity="info">
                                            <span style="font-size: 10px;">#{{ currentApplicant.sequence_number || '-'
                                                }} {{ currentApplicant.scholarship_grant?.[0]?.program?.shortname
                                                }}</span>
                                        </Tag>
                                        <Tag severity="warn">
                                            <span style="font-size: 10px;">#{{
                                                currentApplicant.sequence_number_by_course || '-' }} {{
                                                    currentApplicant.scholarship_grant?.[0]?.course?.shortname }}</span>
                                        </Tag>
                                        <Tag severity="success">
                                            <span style="font-size: 10px;">#{{
                                                currentApplicant.sequence_number_by_school_course || '-' }} {{
                                                    currentApplicant.scholarship_grant?.[0]?.school?.shortname }}</span>
                                        </Tag>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="ios-section ios-section-tight">
                        <Tabs value="requirements">
                            <TabList>
                                <Tab value="requirements">Requirements</Tab>
                                <Tab value="profileInformation">Profile</Tab>
                            </TabList>
                            <TabPanels>
                                <!-- Requirements Tab -->
                                <TabPanel value="requirements">
                                    <div v-if="reviewRequirements.length === 0" class="ios-req-empty"
                                        style="padding: 32px 0; text-align: center;">
                                        <AppIcon name="inbox" :size="28" style="display: block; margin-bottom: 8px;" />
                                        No requirements found
                                    </div>
                                    <div v-else class="ios-card" style="margin-top: 8px;">
                                        <div v-for="(req, idx) in reviewRequirements" :key="req.id" class="ios-row ios-row-review"
                                            :class="{ 'ios-row-last': idx === reviewRequirements.length - 1 }"
                                            :style="{ opacity: req.is_checked ? 1 : 0.55 }">
                                            <div
                                                style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                                <span v-if="req.is_checked" class="ios-req-check-dot"
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;">
                                                    <AppIcon name="check" :size="11" style="color: #34C759;" />
                                                </span>
                                                <span v-else class="ios-req-uncheck-dot"
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;">
                                                    <AppIcon name="circle" :size="11" style="color: #C7C7CC;" />
                                                </span>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div class="ios-req-item-name"
                                                        style="font-size: 14px; font-weight: 500;">{{
                                                            req.name }}</div>
                                                    <div v-if="req.file_path"
                                                        style="font-size: 11px; color: #007AFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        {{ req.file_name }}
                                                    </div>
                                                    <div v-else class="ios-req-no-file" style="font-size: 11px;">No file
                                                        uploaded</div>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 4px; flex-shrink: 0; margin-left: 8px;">
                                                <button v-if="req.file_path && req.is_checked" class="ios-icon-btn"
                                                    @click="previewRequirementFile(req)" title="Preview">
                                                    <AppIcon name="eye" :size="13" style="color: #007AFF;" />
                                                </button>
                                                <button v-if="req.file_path && req.is_checked" class="ios-icon-btn"
                                                    @click="downloadRequirementFile(req)" title="Download">
                                                    <AppIcon name="download" :size="13" style="color: #34C759;" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- Profile Tab -->
                                <TabPanel value="profileInformation">
                                    <!-- Personal & Academic -->
                                    <div
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px;">
                                        <!-- Personal -->
                                        <div class="ios-card" style="padding: 12px 16px;">
                                            <div
                                                style="font-size: 13px; font-weight: 600; color: #007AFF; margin-bottom: 8px; display: flex; align-items: center; gap: 4px;">
                                                <AppIcon name="user" :size="12" /> Personal
                                            </div>
                                            <div class="ios-info-grid">
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Full Name</span>
                                                    <span class="ios-info-value">{{
                                                        getApplicantFullName(currentApplicant) }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Gender</span>
                                                    <span class="ios-info-value">{{ currentApplicant.gender === 'M' ?
                                                        'Male' : currentApplicant.gender === 'F' ? 'Female' : 'N/A'
                                                        }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Contact</span>
                                                    <span class="ios-info-value">{{ currentApplicant.contact_no || 'N/A'
                                                        }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Email</span>
                                                    <span class="ios-info-value">{{ currentApplicant.email || 'N/A'
                                                        }}</span>
                                                </div>
                                                <div class="ios-info-item" style="grid-column: 1 / -1;">
                                                    <span class="ios-info-label">Income</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.gross_monthly_income || 'N/A' }}</span>
                                                </div>
                                                <div class="ios-info-item" style="grid-column: 1 / -1;">
                                                    <span class="ios-info-label">Address</span>
                                                    <span class="ios-info-value">{{
                                                        getApplicantFullAddress(currentApplicant) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Academic -->
                                        <div class="ios-card" style="padding: 12px 16px;">
                                            <div
                                                style="font-size: 13px; font-weight: 600; color: #34C759; margin-bottom: 8px; display: flex; align-items: center; gap: 4px;">
                                                <AppIcon name="graduation-cap" :size="12" /> Academic
                                            </div>
                                            <div class="ios-info-grid">
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Program</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.program?.shortname ||
                                                        'N/A' }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">School</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.school?.shortname ||
                                                        'N/A' }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Course</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.course?.shortname ||
                                                        'N/A' }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Year Level</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.year_level || 'N/A'
                                                        }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Academic Year</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.academic_year || 'N/A'
                                                        }}</span>
                                                </div>
                                                <div class="ios-info-item">
                                                    <span class="ios-info-label">Term</span>
                                                    <span class="ios-info-value">{{
                                                        currentApplicant.scholarship_grant?.[0]?.term || 'N/A' }}</span>
                                                </div>
                                                <div class="ios-info-item" style="grid-column: 1 / -1;">
                                                    <span class="ios-info-label">Remarks</span>
                                                    <span v-if="currentApplicant.remarks" class="ios-info-value"
                                                        v-safe-html="currentApplicant.remarks"></span>
                                                    <span v-else class="ios-info-value">No remarks</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Family -->
                                    <div class="ios-section ios-section-tight">
                                        <div class="ios-section-label">Family Information</div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                                            <!-- Father -->
                                            <div class="ios-card" style="padding: 12px 16px;">
                                                <div
                                                    style="font-size: 12px; font-weight: 600; color: #007AFF; margin-bottom: 6px;">
                                                    <AppIcon name="user" :size="11" /> Father
                                                </div>
                                                <div class="ios-family-info">
                                                    <div><span class="ios-info-label">Name</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.father_name ||
                                                                'N/A' }}</span></div>
                                                    <div><span class="ios-info-label">Occupation</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.father_occupation
                                                                || 'N/A' }}</span></div>
                                                    <div><span class="ios-info-label">Contact</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.father_contact_no
                                                                || 'N/A' }}</span></div>
                                                </div>
                                            </div>
                                            <!-- Mother -->
                                            <div class="ios-card" style="padding: 12px 16px;">
                                                <div
                                                    style="font-size: 12px; font-weight: 600; color: #FF2D55; margin-bottom: 6px;">
                                                    <AppIcon name="user" :size="11" /> Mother
                                                </div>
                                                <div class="ios-family-info">
                                                    <div><span class="ios-info-label">Name</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.mother_name ||
                                                                'N/A' }}</span></div>
                                                    <div><span class="ios-info-label">Occupation</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.mother_occupation
                                                                || 'N/A' }}</span></div>
                                                    <div><span class="ios-info-label">Contact</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.mother_contact_no
                                                                || 'N/A' }}</span></div>
                                                </div>
                                            </div>
                                            <!-- Guardian -->
                                            <div class="ios-card" style="padding: 12px 16px;">
                                                <div
                                                    style="font-size: 12px; font-weight: 600; color: #AF52DE; margin-bottom: 6px;">
                                                    <AppIcon name="users" :size="11" /> Guardian
                                                </div>
                                                <div class="ios-family-info">
                                                    <div><span class="ios-info-label">Name</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.guardian_name ||
                                                                'N/A' }}</span></div>
                                                    <div><span class="ios-info-label">Occupation</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.guardian_occupation || 'N/A' }}</span>
                                                    </div>
                                                    <div><span class="ios-info-label">Contact</span><span
                                                            class="ios-info-value">{{
                                                                currentApplicant.guardian_contact_no || 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </div>

                    <div style="height: 10px;"></div>
                </div>

        <!-- Footer Navigation -->
        <div class="ios-footer" v-if="currentApplicant">
            <button class="ios-footer-btn" @click="goToPreviousProfile" :disabled="!hasPreviousProfile">
                <AppIcon name="chevron-left" :size="12" style="margin-right: 4px;" />Previous
            </button>
            <span class="ios-footer-counter">{{ currentProfileIndex + 1 }} / {{ applicants?.length || 0
                }}</span>
            <button class="ios-footer-btn" @click="goToNextProfile" :disabled="!hasNextProfile">
                Next
                <AppIcon name="chevron-right" :size="12" style="margin-left: 4px;" />
            </button>
        </div>
    </IosModal>

    <ViewAttachmentModal v-model:visible="showPreviewModal" :attachment="previewFile" />
</template>

