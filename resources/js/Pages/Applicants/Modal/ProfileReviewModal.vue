<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { usePermission } from '@/composable/permissions';
import { Tag } from 'primevue';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';

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
const canInterview = computed(() => hasRole('administrator') || hasRole('program_manager') || hasRole('screening-officer'));
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

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '720px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-tag, .p-avatar, .p-tabs')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}
function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}
function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
</script>

<template>
    <Dialog :visible="visible" modal @update:visible="val => { if (!val) close(); }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Profile Review</span>
                    <template v-if="hasActionMenu">
                        <button class="ios-nav-btn ios-nav-action ios-nav-dropdown"
                            @click="toggleActionPopover($event)">
                            Actions
                            <AppIcon name="chevron-down" :size="11" />
                        </button>
                        <Popover ref="actionPopover">
                            <div class="profile-review-action-menu">
                                <button v-if="canViewFullProfile" class="profile-review-action-item"
                                    @click="visitProfile">
                                    <AppIcon name="eye" :size="14" class="profile-review-action-icon" />
                                    <span>View Full Profile</span>
                                </button>
                                <button v-if="canEditRequirements" class="profile-review-action-item"
                                    @click="editRequirements">
                                    <AppIcon name="book-check" :size="14" class="profile-review-action-icon" />
                                    <span>Edit Requirements</span>
                                </button>
                                <button v-if="canInterview" class="profile-review-action-item"
                                    @click="markAsInterviewed">
                                    <AppIcon name="comments" :size="14" class="profile-review-action-icon" />
                                    <span>Interview</span>
                                </button>
                                <button v-if="canEditProfile" class="profile-review-action-item" @click="editProfile">
                                    <AppIcon name="pencil" :size="14" class="profile-review-action-icon" />
                                    <span>Edit Profile</span>
                                </button>
                            </div>
                        </Popover>
                    </template>
                    <span v-else class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="currentApplicant">
                    <!-- Applicant Header Card -->
                    <div class="ios-section" style="margin-top: 16px;">
                        <div class="ios-card" style="padding: 14px 16px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <Avatar :label="getApplicantInitials(currentApplicant)" size="large" shape="circle"
                                    style="background: #007AFF; color: white; flex-shrink: 0;" />
                                <div style="flex: 1; min-width: 0;">
                                    <div class="profile-name"
                                        style="font-size: 16px; font-weight: 600; cursor: pointer; letter-spacing: -0.4px;"
                                        @click="visitProfile">
                                        {{ getApplicantFullName(currentApplicant) }}
                                    </div>
                                    <div class="profile-meta"
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
                    <div class="ios-section">
                        <Tabs value="requirements">
                            <TabList>
                                <Tab value="requirements">Requirements</Tab>
                                <Tab value="profileInformation">Profile</Tab>
                            </TabList>
                            <TabPanels>
                                <!-- Requirements Tab -->
                                <TabPanel value="requirements">
                                    <div v-if="reviewRequirements.length === 0" class="req-empty"
                                        style="padding: 32px 0; text-align: center;">
                                        <AppIcon name="inbox" :size="28" style="display: block; margin-bottom: 8px;" />
                                        No requirements found
                                    </div>
                                    <div v-else class="ios-card" style="margin-top: 8px;">
                                        <div v-for="(req, idx) in reviewRequirements" :key="req.id" class="ios-row"
                                            :class="{ 'ios-row-last': idx === reviewRequirements.length - 1 }"
                                            :style="{ opacity: req.is_checked ? 1 : 0.55 }">
                                            <div
                                                style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                                <span v-if="req.is_checked" class="req-check-dot"
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;">
                                                    <AppIcon name="check" :size="11" style="color: #34C759;" />
                                                </span>
                                                <span v-else class="req-uncheck-dot"
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;">
                                                    <AppIcon name="circle" :size="11" style="color: #C7C7CC;" />
                                                </span>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div class="req-item-name"
                                                        style="font-size: 14px; font-weight: 500;">{{
                                                            req.name }}</div>
                                                    <div v-if="req.file_path"
                                                        style="font-size: 11px; color: #007AFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        {{ req.file_name }}
                                                    </div>
                                                    <div v-else class="req-no-file" style="font-size: 11px;">No file
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
                                    <div class="ios-section" style="margin-top: 12px;">
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
            </div>
        </template>
    </Dialog>

    <ViewAttachmentModal v-model:visible="showPreviewModal" :attachment="previewFile" />
</template>

<style scoped>
/* Component-unique styles — standard ios-* classes handled by ios-design-system.css */

/* Override: this modal uses tighter spacing than global 22px */
.ios-section {
    margin-top: 16px;
}

/* Override: action button is blue (interactive) unlike global gray */
.ios-nav-action {
    color: #007AFF;
    font-size: 14px;
    display: flex;
    align-items: center;
}

.ios-nav-dropdown {
    gap: 4px;
}

/* Override: rows have larger padding than global 4px 16px */
.ios-row {
    padding: 8px 16px;
    min-height: 40px;
}

/* Component-unique classes */
.ios-icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.15s;
}

.ios-icon-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.ios-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}

.ios-info-item {
    display: flex;
    flex-direction: column;
}

.ios-info-label {
    font-size: 11px;
    color: #8E8E93;
    display: block;
}

.ios-info-value {
    font-size: 13px;
    font-weight: 500;
    color: #000;
    display: block;
}

.ios-family-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ios-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #FFFFFF;
    border-top: 0.5px solid #E5E5EA;
    flex-shrink: 0;
}

.ios-footer-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    font-size: 14px;
    color: #007AFF;
    font-weight: 500;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: opacity 0.15s;
}

.ios-footer-btn:hover {
    opacity: 0.6;
}

.ios-footer-btn:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-footer-counter {
    font-size: 13px;
    color: #8E8E93;
    font-weight: 500;
}

.profile-review-action-menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 168px;
}

.profile-review-action-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    border: none;
    background: none;
    cursor: pointer;
    padding: 10px 12px;
    border-radius: 10px;
    color: #1f2937;
    font-size: 13px;
    font-weight: 500;
    text-align: left;
    transition: background 0.15s ease;
}

.profile-review-action-item:hover {
    background: rgba(0, 0, 0, 0.05);
}

.profile-review-action-icon {
    color: #007AFF;
    flex-shrink: 0;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}

/* Dark mode — component-unique classes */
.dark .ios-nav-action {
    color: #60a5fa !important;
}

.dark .ios-icon-btn:hover {
    background: rgba(255, 255, 255, 0.08) !important;
}

.dark .ios-info-value {
    color: #d1d5db !important;
}

.dark .ios-info-label {
    color: #9ca3af !important;
}

.dark .ios-footer {
    background: #2a3040 !important;
    border-top-color: rgba(255, 255, 255, 0.08) !important;
}

.dark .ios-footer-btn {
    color: #60a5fa !important;
}

.dark .profile-review-action-item {
    color: #d1d5db !important;
}

.dark .profile-review-action-item:hover {
    background: rgba(255, 255, 255, 0.08) !important;
}

.dark .profile-review-action-icon {
    color: #60a5fa !important;
}

.dark .ios-footer-btn:disabled {
    color: #4b5563 !important;
}

.dark .ios-footer-counter {
    color: #9ca3af !important;
}

/* Header card */
.profile-name {
    color: #000;
}

.profile-meta {
    color: #8E8E93;
}

.dark .profile-name {
    color: #d1d5db !important;
}

.dark .profile-meta {
    color: #9ca3af !important;
}

/* Requirements tab */
.req-empty {
    color: #8E8E93;
}

.dark .req-empty {
    color: #9ca3af !important;
}

.req-check-dot {
    background: #E8F5E9;
}

.dark .req-check-dot {
    background: rgba(52, 199, 89, 0.15) !important;
}

.req-uncheck-dot {
    background: #F2F2F7;
}

.dark .req-uncheck-dot {
    background: rgba(255, 255, 255, 0.08) !important;
}

.req-item-name {
    color: #000;
}

.dark .req-item-name {
    color: #d1d5db !important;
}

.req-no-file {
    color: #8E8E93;
}

.dark .req-no-file {
    color: #6b7280 !important;
}
</style>
