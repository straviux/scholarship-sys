<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { usePermission } from '@/composable/permissions';
import { Tag } from 'primevue';

const { hasRole } = usePermission();

const props = defineProps({
    visible: Boolean,
    applicant: Object,
    applicants: Array,
});

const emit = defineEmits(['update:visible', 'interview', 'closed']);

const reviewRequirements = ref([]);
const currentProfileIndex = ref(-1);
const currentApplicant = ref(null);

const hasPreviousProfile = computed(() => currentProfileIndex.value > 0);
const hasNextProfile = computed(() => currentProfileIndex.value < (props.applicants?.length || 0) - 1);

// Sync when applicant prop changes
watch(() => props.applicant, (newApplicant) => {
    if (newApplicant && props.visible) {
        currentApplicant.value = newApplicant;
        currentProfileIndex.value = props.applicants?.findIndex(a => a.profile_id === newApplicant.profile_id) ?? -1;
        loadRequirements(newApplicant.profile_id);
    }
}, { immediate: true });

watch(() => props.visible, (val) => {
    if (!val) {
        currentApplicant.value = null;
        reviewRequirements.value = [];
        currentProfileIndex.value = -1;
        emit('closed');
    }
});

const dialogVisible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

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
    window.open(requirement.file_path, '_blank');
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
    currentProfileIndex.value = index;
    currentApplicant.value = props.applicants[index];
    loadRequirements(currentApplicant.value.profile_id);
};

const goToPreviousProfile = () => navigateTo(currentProfileIndex.value - 1);
const goToNextProfile = () => navigateTo(currentProfileIndex.value + 1);

const markAsInterviewed = () => {
    if (!currentApplicant.value) return;
    emit('interview', currentApplicant.value);
};

const visitProfile = () => {
    if (!currentApplicant.value) return;
    router.visit(route('scholarship.profile.show', currentApplicant.value.profile_id));
};
</script>

<template>
    <Dialog v-model:visible="dialogVisible" modal :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" :maximizable="true" class="p-fluid">

        <template #header>
            <span>Application Review & Applicant Profile</span>
        </template>

        <div v-if="currentApplicant">
            <!-- Header Summary -->
            <div class="p-4 mb-4">
                <div class="flex items-start gap-4">
                    <Avatar :label="getApplicantInitials(currentApplicant)" size="xlarge" shape="circle"
                        class="bg-blue-600 text-white" />
                    <div class="flex-1 gap-2">
                        <h3 class="text-lg font-bold text-gray-900 cursor-pointer hover:text-blue-700"
                            @click="visitProfile">
                            {{ getApplicantFullName(currentApplicant) }}</h3>
                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                            <span><i class="pi pi-phone mr-1" style="font-size: 10px;"></i>{{
                                currentApplicant.contact_no ||
                                'N/A'
                            }}</span>
                            <span><i class="pi pi-envelope mr-1" style="font-size: 10px;"></i>{{ currentApplicant.email
                                || 'N/A'
                                }}</span>
                            <span><i class="pi pi-calendar mr-1" style="font-size: 10px;"></i>{{
                                formatDate(currentApplicant.date_filed)
                                }}</span>
                        </div>
                        <!-- Queue Numbers -->
                        <div class="flex items-center gap-2 mt-2">
                            <Tag severity="info">
                                <span class="text-xs">#{{ currentApplicant.sequence_number || '-' }} {{
                                    currentApplicant.scholarship_grant?.[0]?.program?.shortname }}</span>
                            </Tag>
                            <Tag severity="warn">
                                <span class="text-xs">#{{ currentApplicant.sequence_number_by_course || '-' }} {{
                                    currentApplicant.scholarship_grant?.[0]?.course?.shortname }}</span>
                            </Tag>
                            <Tag severity="success">
                                <span class="text-xs">#{{ currentApplicant.sequence_number_by_school_course || '-' }} {{
                                    currentApplicant.scholarship_grant?.[0]?.school?.shortname }}</span>
                            </Tag>
                        </div>
                    </div>
                    <!-- Quick Action Buttons -->
                    <div v-if="hasRole('administrator') || hasRole('program_manager') || hasRole('screening-officer')"
                        class="flex justify-end ">
                        <Button label="Mark as Interviewed" icon="pi pi-comments" severity="info" size="small"
                            @click="markAsInterviewed" />
                    </div>
                </div>
            </div>



            <!-- Tabbed Content -->
            <Tabs value="requirements">
                <TabList>
                    <Tab value="requirements">Requirements</Tab>
                    <Tab value="profileInformation">Profile</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="profileInformation" header="Profile Information">
                        <!-- Personal & Academic Information -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <!-- Personal Information -->
                            <div class="border rounded p-3">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                    <i class="pi pi-user text-blue-600 text-sm"></i>
                                    Personal
                                </h4>
                                <div class="space-y-2 text-xs">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-gray-600">Full Name</label>
                                            <div class="font-medium">{{ getApplicantFullName(currentApplicant) }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Gender</label>
                                            <div class="font-medium">{{ currentApplicant.gender === 'M' ? 'Male' :
                                                currentApplicant.gender === 'F' ? 'Female' : 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-gray-600">Contact</label>
                                            <div class="font-medium">{{ currentApplicant.contact_no || 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Email</label>
                                            <div class="font-medium">{{ currentApplicant.email || 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-gray-600">Income</label>
                                        <div class="font-medium">{{ currentApplicant.gross_monthly_income || 'N/A' }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-gray-600">Address</label>
                                        <div class="font-medium">{{ getApplicantFullAddress(currentApplicant) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Information -->
                            <div class="border rounded p-3">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                    <i class="pi pi-graduation-cap text-green-600 text-sm"></i>
                                    Academic
                                </h4>
                                <div class="space-y-2 text-xs">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-gray-600">Program</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.program?.shortname || 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">School</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.school?.shortname || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-gray-600">Course</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.course?.shortname || 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Year Level</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.year_level || 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-gray-600">Academic Year</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.academic_year || 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Term</label>
                                            <div class="font-medium">{{
                                                currentApplicant.scholarship_grant?.[0]?.term || 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-gray-600">Remarks</label>
                                        <div class="font-medium">{{ currentApplicant.remarks || 'No remarks' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family Information -->
                        <div class="border rounded p-3 mt-3">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Family</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                <!-- Father -->
                                <div>
                                    <h5 class="text-xs font-medium text-blue-600 mb-1 flex items-center gap-1">
                                        <i class="pi pi-user text-xs"></i> Father
                                    </h5>
                                    <div class="space-y-1">
                                        <div>
                                            <label class="text-gray-600">Name</label>
                                            <div class="font-medium">{{ currentApplicant.father_name || 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Occupation</label>
                                            <div class="font-medium">{{ currentApplicant.father_occupation || 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Contact</label>
                                            <div class="font-medium">{{ currentApplicant.father_contact_no || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mother -->
                                <div>
                                    <h5 class="text-xs font-medium text-pink-600 mb-1 flex items-center gap-1">
                                        <i class="pi pi-user text-xs"></i> Mother
                                    </h5>
                                    <div class="space-y-1">
                                        <div>
                                            <label class="text-gray-600">Name</label>
                                            <div class="font-medium">{{ currentApplicant.mother_name || 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Occupation</label>
                                            <div class="font-medium">{{ currentApplicant.mother_occupation || 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Contact</label>
                                            <div class="font-medium">{{ currentApplicant.mother_contact_no || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian -->
                                <div>
                                    <h5 class="text-xs font-medium text-purple-600 mb-1 flex items-center gap-1">
                                        <i class="pi pi-users text-xs"></i> Guardian
                                    </h5>
                                    <div class="space-y-1">
                                        <div>
                                            <label class="text-gray-600">Name</label>
                                            <div class="font-medium">{{ currentApplicant.guardian_name || 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Occupation</label>
                                            <div class="font-medium">{{ currentApplicant.guardian_occupation || 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Contact</label>
                                            <div class="font-medium">{{ currentApplicant.guardian_contact_no || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel value="requirements" header="Requirements">
                        <div v-if="currentApplicant" class="space-y-4">
                            <!-- Requirements List -->
                            <div class="border rounded-lg overflow-hidden">
                                <div class="p-3 font-semibold text-sm border-b">Requirements Checklist</div>
                                <div v-if="reviewRequirements.length === 0" class="p-6 text-center text-gray-500">
                                    <i class="pi pi-inbox text-3xl mb-2 block"></i>
                                    No requirements found
                                </div>
                                <div v-else class="divide-y">
                                    <div v-for="req in reviewRequirements" :key="req.id"
                                        class="px-3 py-1 flex items-center justify-between hover:bg-gray-50"
                                        :class="{ 'opacity-60': !req.is_checked }">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <div v-if="req.is_checked" class="flex-shrink-0">
                                                    <span
                                                        class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-100">
                                                        <i class="pi pi-check text-green-600"
                                                            style="font-size: 12px;"></i>
                                                    </span>
                                                </div>
                                                <div v-else class="flex-shrink-0">
                                                    <span
                                                        class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200">
                                                        <i class="pi pi-circle text-gray-400"
                                                            style="font-size: 12px;"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-sm text-gray-900">{{ req.name }}
                                                    <p v-if="req.file_path" class="text-[10px] text-gray-600 truncate">
                                                        [ <span class="italic text-blue-500">{{ req.file_name }}</span>
                                                        ]</p>
                                                    <Tag v-else-if="req.is_checked">
                                                        <span class="text-[10px]">No file uploaded</span>
                                                    </Tag>
                                                    <Tag v-else> <span class="text-[10px]">No
                                                            file uploaded</span>
                                                    </Tag>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 ml-3 flex-shrink-0">
                                            <Button v-if="req.file_path && req.is_checked" icon="pi pi-eye"
                                                severity="info" text rounded size="small"
                                                @click="previewRequirementFile(req)" v-tooltip="'Preview'" />
                                            <Button v-if="req.file_path && req.is_checked" icon="pi pi-download"
                                                severity="success" text rounded size="small"
                                                @click="downloadRequirementFile(req)" v-tooltip="'Download'" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full pt-4">
                <Button icon="pi pi-chevron-left" label="Previous" @click="goToPreviousProfile"
                    :disabled="!hasPreviousProfile" size="small" variant="text" />
                <div class="flex opacity-75">({{ currentProfileIndex + 1 }}/{{ applicants?.length || 0 }})</div>
                <Button icon="pi pi-chevron-right" iconPos="right" label="Next" @click="goToNextProfile"
                    :disabled="!hasNextProfile" size="small" variant="text" />
            </div>
        </template>
    </Dialog>
</template>
