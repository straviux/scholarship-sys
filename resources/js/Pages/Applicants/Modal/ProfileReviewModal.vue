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
    // profile_id → array of tracking-list keys the profile belongs to
    listMembership: {
        type: Object,
        default: () => ({}),
    },
    activeListTab: {
        type: String,
        default: 'all',
    },
});

const emit = defineEmits([
    'update:visible',
    'interview',
    'edit-profile',
    'edit-requirements',
    'edit-yakap',
    'edit-remarks',
    'assign-priority',
    'remove-priority',
    'add-to-list',
    'remove-from-list',
    'delete',
    'closed',
]);

const reviewRequirements = ref([]);
const currentProfileIndex = ref(-1);
const currentApplicant = ref(null);
const showPreviewModal = ref(false);
const previewFile = ref(null);
const actionPopover = ref(null);

const hasPreviousProfile = computed(() => currentProfileIndex.value > 0);
const hasNextProfile = computed(() => currentProfileIndex.value < (props.applicants?.length || 0) - 1);
const canEditRequirements = computed(() => hasPermission('applicants.view'));
const canInterview = computed(() => (hasRole('administrator') || hasRole('program_manager') || hasRole('screening_officer'))
    && props.activeListTab === 'interview');
const canManagePriority = computed(() => hasPermission('priority.manage'));
const canRemovePriority = computed(() => Boolean(currentApplicant.value?.priority_level) && currentApplicant.value.priority_level !== 'normal');
const canDelete = computed(() => hasRole('administrator'));

// Tracking lists — mirrors the row context menu on the Applicants page
const TRACKING_LISTS = [
    { key: 'waiting', label: 'Waiting', icon: 'clock' },
    { key: 'interview', label: 'Interview', icon: 'comments' },
    { key: 'endorsed', label: 'Endorsed', icon: 'share-2' },
    { key: 'personal', label: 'My List', icon: 'bookmark' },
];
const currentLists = computed(() => props.listMembership?.[currentApplicant.value?.profile_id] || []);
const addableLists = computed(() => TRACKING_LISTS.filter(list => !currentLists.value.includes(list.key)));
const removableLists = computed(() => TRACKING_LISTS.filter(list => currentLists.value.includes(list.key)));
// The Edit group is always available, so the menu always renders
const hasActionMenu = computed(() => true);

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

const requirementsSummary = computed(() => {
    const total = reviewRequirements.value.length;
    const submitted = reviewRequirements.value.filter(req => req.is_checked).length;
    return {
        total,
        submitted,
        percent: total > 0 ? Math.round((submitted / total) * 100) : 0,
    };
});

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

const editYakap = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('edit-yakap', currentApplicant.value);
};

const editRemarks = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('edit-remarks', currentApplicant.value);
};

const assignPriority = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('assign-priority', currentApplicant.value);
};

const removePriority = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('remove-priority', currentApplicant.value);
};

const addToList = (listType) => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('add-to-list', currentApplicant.value, listType);
};

const removeFromList = (listType) => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('remove-from-list', currentApplicant.value, listType);
};

const deleteApplicant = () => {
    if (!currentApplicant.value) return;
    hideActionPopover();
    emit('delete', currentApplicant.value);
};

const visitProfile = () => {
    if (!currentApplicant.value) return;
    router.visit(route('scholarship.profile.show', currentApplicant.value.profile_id));
};
</script>

<template>
    <IosModal :visible="visible" title="Profile Review" width="min(1150px, 96vw)" max-width="96vw"
        :body-style="{ padding: '0', display: 'flex', flexDirection: 'column', minHeight: 0 }"
        @update:visible="val => { if (!val) close(); }">
        <template #header-right>
            <template v-if="hasActionMenu">
                <button class="ios-nav-btn ios-nav-action ios-nav-dropdown text-nav" @click="toggleActionPopover($event)">
                    Actions
                    <AppIcon name="chevron-down" :size="11" />
                </button>
                <Popover ref="actionPopover">
                    <div class="ios-action-menu">
                        <button v-if="canInterview" class="ios-action-item text-compact" @click="markAsInterviewed">
                            <AppIcon name="comments" :size="14" class="ios-action-icon" />
                            <span>Interview</span>
                        </button>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                        <div class="px-3 py-1 text-2xs font-semibold uppercase tracking-wide text-gray-400">Edit</div>
                        <button class="ios-action-item text-compact" @click="editProfile">
                            <AppIcon name="user-edit" :size="14" class="ios-action-icon" />
                            <span>Application</span>
                        </button>
                        <button v-if="canEditRequirements" class="ios-action-item text-compact"
                            @click="editRequirements">
                            <AppIcon name="book-check" :size="14" class="ios-action-icon" />
                            <span>Requirements</span>
                        </button>
                        <button class="ios-action-item text-compact" @click="editYakap">
                            <AppIcon name="heart" :size="14" class="ios-action-icon" />
                            <span>YAKAP Category</span>
                        </button>
                        <button class="ios-action-item text-compact" @click="editRemarks">
                            <AppIcon name="comment" :size="14" class="ios-action-icon" />
                            <span>Remarks</span>
                        </button>

                        <template v-if="addableLists.length">
                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                            <div class="px-3 py-1 text-2xs font-semibold uppercase tracking-wide text-gray-400">Add to</div>
                            <button v-for="list in addableLists" :key="`add-${list.key}`"
                                class="ios-action-item text-compact" @click="addToList(list.key)">
                                <AppIcon :name="list.icon" :size="14" class="ios-action-icon" />
                                <span>{{ list.label }}</span>
                            </button>
                        </template>

                        <template v-if="removableLists.length">
                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                            <div class="px-3 py-1 text-2xs font-semibold uppercase tracking-wide text-gray-400">Remove from</div>
                            <button v-for="list in removableLists" :key="`remove-${list.key}`"
                                class="ios-action-item text-compact" @click="removeFromList(list.key)">
                                <AppIcon name="times" :size="14" class="ios-action-icon" />
                                <span>{{ list.label }}</span>
                            </button>
                        </template>

                        <template v-if="canManagePriority">
                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                            <button class="ios-action-item text-compact" @click="assignPriority">
                                <AppIcon name="star" :size="14" class="ios-action-icon" />
                                <span>Assign Priority</span>
                            </button>
                            <button v-if="canRemovePriority" class="ios-action-item text-compact" @click="removePriority">
                                <AppIcon name="star-fill" :size="14" class="ios-action-icon" />
                                <span>Remove Priority</span>
                            </button>
                        </template>

                        <template v-if="canDelete">
                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                            <button class="ios-action-item text-compact !text-red-600" @click="deleteApplicant">
                                <AppIcon name="trash" :size="14" class="ios-action-icon" />
                                <span>Delete Applicant</span>
                            </button>
                        </template>
                    </div>
                </Popover>
            </template>
            <span v-else class="ios-nav-btn text-nav" style="visibility: hidden; right: 16px;">_</span>
        </template>

        <div class="ios-body" v-if="currentApplicant">
            <!-- Applicant Header Card -->
            <div class="ios-section ios-section-tight">
                <div class="ios-card px-4 py-3">
                    <div class="flex items-center gap-3">
                        <Avatar :label="getApplicantInitials(currentApplicant)" size="large" shape="circle"
                            class="shrink-0 !bg-blue-500 !text-white" />
                        <div class="min-w-0 flex-1">
                            <div class="ios-profile-name cursor-pointer text-xl font-semibold tracking-tight"
                                @click="visitProfile">
                                {{ getApplicantFullName(currentApplicant) }}
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                            <Tag severity="info">
                                <span class="text-xs">#{{ currentApplicant.sequence_number || '-'
                                    }} {{ currentApplicant.scholarship_grant?.[0]?.program?.shortname }}</span>
                            </Tag>
                            <Tag severity="warn">
                                <span class="text-xs">#{{
                                    currentApplicant.sequence_number_by_course || '-' }} {{
                                        currentApplicant.scholarship_grant?.[0]?.course?.shortname }}</span>
                            </Tag>
                            <Tag severity="success">
                                <span class="text-xs">#{{
                                    currentApplicant.sequence_number_by_school_course || '-' }} {{
                                        currentApplicant.scholarship_grant?.[0]?.school?.shortname }}</span>
                            </Tag>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard grid: profile details left, requirements right -->
            <div class="ios-section ios-section-tight">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">

                    <!-- LEFT (2/3): Personal, Academic, Family -->
                    <div class="space-y-3 lg:col-span-2">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Personal -->
                            <div class="ios-card px-4 py-3">
                                <div class="mb-2 flex items-center gap-1 text-sm font-semibold text-blue-600">
                                    <AppIcon name="user" :size="12" /> Personal
                                </div>
                                <div class="ios-info-grid">
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Gender</span>
                                        <span class="ios-info-value text-compact">{{ currentApplicant.gender === 'M' ?
                                            'Male' : currentApplicant.gender === 'F' ? 'Female' : 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Income</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.gross_monthly_income || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Contact</span>
                                        <span class="ios-info-value text-compact">{{ currentApplicant.contact_no || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Email</span>
                                        <span class="ios-info-value text-compact">{{ currentApplicant.email || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item" style="grid-column: 1 / -1;">
                                        <span class="ios-info-label text-2xs">Address</span>
                                        <span class="ios-info-value text-compact">{{
                                            getApplicantFullAddress(currentApplicant) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Academic -->
                            <div class="ios-card px-4 py-3">
                                <div class="mb-2 flex items-center gap-1 text-sm font-semibold text-green-600">
                                    <AppIcon name="graduation-cap" :size="12" /> Academic
                                </div>
                                <div class="ios-info-grid">
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Program</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.program?.shortname || 'N/A'
                                        }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">School</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.school?.shortname || 'N/A'
                                        }}</span>
                                    </div>
                                    <div class="ios-info-item" style="grid-column: 1 / -1;">
                                        <span class="ios-info-label text-2xs">Course</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.course?.name ||
                                            currentApplicant.scholarship_grant?.[0]?.course?.shortname || 'N/A'
                                        }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Year Level</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.year_level || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Academic Year</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.academic_year || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-info-item">
                                        <span class="ios-info-label text-2xs">Term</span>
                                        <span class="ios-info-value text-compact">{{
                                            currentApplicant.scholarship_grant?.[0]?.term || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="ios-card px-4 py-3">
                            <div class="mb-2 flex items-center gap-1 text-sm font-semibold text-amber-600">
                                <AppIcon name="comment" :size="12" /> Remarks
                            </div>
                            <div v-if="currentApplicant.remarks" class="prose prose-xs max-w-none text-xs"
                                v-safe-html="currentApplicant.remarks"></div>
                            <div v-else class="text-xs text-gray-400">No remarks</div>
                        </div>

                        <!-- Family -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="ios-card px-4 py-3">
                                <div class="mb-1.5 flex items-center gap-1 text-xs font-semibold text-blue-600">
                                    <AppIcon name="user" :size="11" /> Father
                                </div>
                                <div class="ios-family-info">
                                    <div><span class="ios-info-label text-2xs">Name</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.father_name || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Occupation</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.father_occupation || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Contact</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.father_contact_no || 'N/A' }}</span></div>
                                </div>
                            </div>
                            <div class="ios-card px-4 py-3">
                                <div class="mb-1.5 flex items-center gap-1 text-xs font-semibold text-rose-500">
                                    <AppIcon name="user" :size="11" /> Mother
                                </div>
                                <div class="ios-family-info">
                                    <div><span class="ios-info-label text-2xs">Name</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.mother_name || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Occupation</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.mother_occupation || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Contact</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.mother_contact_no || 'N/A' }}</span></div>
                                </div>
                            </div>
                            <div class="ios-card px-4 py-3">
                                <div class="mb-1.5 flex items-center gap-1 text-xs font-semibold text-purple-600">
                                    <AppIcon name="users" :size="11" /> Guardian
                                </div>
                                <div class="ios-family-info">
                                    <div><span class="ios-info-label text-2xs">Name</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.guardian_name || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Occupation</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.guardian_occupation || 'N/A' }}</span></div>
                                    <div><span class="ios-info-label text-2xs">Contact</span><span class="ios-info-value text-compact">{{
                                        currentApplicant.guardian_contact_no || 'N/A' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT (1/3): Requirements -->
                    <div class="ios-card flex min-h-0 flex-col px-4 py-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1 text-sm font-semibold text-indigo-600">
                                <AppIcon name="book-check" :size="12" /> Requirements
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                {{ requirementsSummary.submitted }}/{{ requirementsSummary.total }} submitted
                            </span>
                        </div>

                        <!-- Progress bar -->
                        <div v-if="requirementsSummary.total > 0" class="mb-3 h-1.5 w-full rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-indigo-500 transition-all"
                                :style="{ width: requirementsSummary.percent + '%' }"></div>
                        </div>

                        <div v-if="reviewRequirements.length === 0"
                            class="ios-req-empty py-8 text-center text-xs text-gray-400">
                            <AppIcon name="inbox" :size="24" class="mb-2 block w-full" />
                            No requirements found
                        </div>

                        <div v-else class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1"
                            style="max-height: 420px;">
                            <div v-for="req in reviewRequirements" :key="req.id"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50"
                                :class="{ 'opacity-55': !req.is_checked }">
                                <AppIcon v-if="req.is_checked" name="check" :size="11"
                                    class="shrink-0 text-green-500" />
                                <AppIcon v-else name="circle" :size="11" class="shrink-0 text-gray-300" />
                                <div class="min-w-0 flex-1">
                                    <div class="ios-req-item-name truncate text-xs font-medium">{{ req.name }}
                                    </div>
                                    <div v-if="req.file_path" class="truncate text-xs text-blue-600">
                                        {{ req.file_name }}
                                    </div>
                                </div>
                                <div class="ml-1 flex shrink-0 gap-0.5">
                                    <button v-if="req.file_path && req.is_checked" class="ios-icon-btn text-sm"
                                        @click="previewRequirementFile(req)" title="Preview">
                                        <AppIcon name="eye" :size="12" class="text-blue-600" />
                                    </button>
                                    <button v-if="req.file_path && req.is_checked" class="ios-icon-btn text-sm"
                                        @click="downloadRequirementFile(req)" title="Download">
                                        <AppIcon name="download" :size="12" class="text-green-600" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-2.5"></div>
        </div>

        <!-- Footer Navigation -->
        <div class="ios-footer" v-if="currentApplicant">
            <button class="ios-footer-btn text-sm" @click="goToPreviousProfile" :disabled="!hasPreviousProfile">
                <AppIcon name="chevron-left" :size="12" style="margin-right: 4px;" />Previous
            </button>
            <span class="ios-footer-counter">{{ currentProfileIndex + 1 }} / {{ applicants?.length || 0
                }}</span>
            <button class="ios-footer-btn text-sm" @click="goToNextProfile" :disabled="!hasNextProfile">
                Next
                <AppIcon name="chevron-right" :size="12" style="margin-left: 4px;" />
            </button>
        </div>
    </IosModal>

    <ViewAttachmentModal v-model:visible="showPreviewModal" :attachment="previewFile" />
</template>

