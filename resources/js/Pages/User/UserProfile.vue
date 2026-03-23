<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import logger from '@/utils/logger';
import { ref, computed, nextTick, watch, onUnmounted } from 'vue';
import { useDateUtils } from '@/composable/dateUtils.js';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/ui/inputs/InputLabel.vue';
import InputError from '@/Components/ui/inputs/InputError.vue';
import { toast } from 'vue3-toastify';
import axios from 'axios';

const { formatDate } = useDateUtils();

const props = defineProps({
    reportData: Object
});

// Reactive data
const showCurrentMonthOnly = ref(false);
const showEncodingCalendarModal = ref(false);
const selectedCalendarDate = ref(null);
const calendarRecords = ref([]);
const recordsByDate = ref({});
const loadingRecords = ref(false);
const showChangePasswordModal = ref(false);
const showEditProfileModal = ref(false);
const showProfilePhotoModal = ref(false);
const showViewPhotoModal = ref(false);
const showQrModal = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const photoPreviewUrl = ref(null);
const fileInput = ref(null);

// Forms
const changePasswordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const editProfileForm = useForm({
    name: props.reportData?.user_name || '',
});

// Computed properties for statistics
const encodingStats = computed(() => {
    return props.reportData?.user_summary?.encoding_statistics || {};
});

const userApplications = computed(() => {
    return encodingStats.value?.applications || {};
});

const userSummary = computed(() => {
    return encodingStats.value?.summary || {};
});

const filteredProgramData = computed(() => {
    const breakdowns = encodingStats.value?.breakdowns || {};
    return showCurrentMonthOnly.value
        ? (breakdowns.by_program_current_month || [])
        : (breakdowns.by_program || []);
});

const filteredCourseData = computed(() => {
    const breakdowns = encodingStats.value?.breakdowns || {};
    return showCurrentMonthOnly.value
        ? (breakdowns.by_course_current_month || [])
        : (breakdowns.by_course || []);
});

const filteredSchoolData = computed(() => {
    const breakdowns = encodingStats.value?.breakdowns || {};
    return showCurrentMonthOnly.value
        ? (breakdowns.by_school_current_month || [])
        : (breakdowns.by_school || []);
});

const overallTotal = computed(() => {
    if (!filteredProgramData.value || !Array.isArray(filteredProgramData.value)) {
        return 0;
    }
    return filteredProgramData.value.reduce((sum, item) => sum + (item.count || 0), 0);
});

const latestActivity = computed(() => {
    const latestActivityDate = encodingStats.value?.latest_activity_date;
    const lastLogin = props.reportData?.user_summary?.basic_info?.last_login;
    return latestActivityDate || lastLogin;
});

const activityLabel = computed(() => {
    const latestActivityDate = encodingStats.value?.latest_activity_date;
    return latestActivityDate ? 'Latest encoding activity' : 'Last login';
});

const encodedToday = computed(() => {
    return userApplications.value?.today || 0;
});

const encodedExistingApproved = computed(() => {
    return userApplications.value?.existing_approved || 0;
});

// Change password methods
const openChangePasswordModal = () => {
    changePasswordForm.reset();
    changePasswordForm.clearErrors();
    showChangePasswordModal.value = true;
};

const closeChangePasswordModal = () => {
    showChangePasswordModal.value = false;
    changePasswordForm.reset();
    changePasswordForm.clearErrors();
};

const submitPasswordChange = () => {
    changePasswordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            closeChangePasswordModal();
            toast.success('Password changed successfully!');
        }
    });
};

// Profile editing methods
const openEditProfileModal = () => {
    editProfileForm.name = props.reportData?.user_name || '';
    editProfileForm.clearErrors();
    showEditProfileModal.value = true;
};

const closeEditProfileModal = () => {
    showEditProfileModal.value = false;
    editProfileForm.reset();
    editProfileForm.clearErrors();
};

const submitProfileUpdate = () => {
    editProfileForm.put(route('user.profile.update'), {
        preserveScroll: true,
        onSuccess: (page) => {
            closeEditProfileModal();
            toast.success('Profile updated successfully!');
            // Refresh the page to get updated data using Inertia
            router.reload({ only: ['reportData'] });
        },
        onError: (errors) => {
            logger.error('Profile update failed:', errors);
        }
    });
};

// Profile photo methods
const openProfilePhotoModal = () => {
    showProfilePhotoModal.value = true;
    logger.log('Photo modal opened');
};

const openViewPhotoModal = () => {
    if (props.reportData?.has_profile_photo) {
        showViewPhotoModal.value = true;
    } else {
        // If no photo exists, open upload modal instead
        openProfilePhotoModal();
    }
};

const closeProfilePhotoModal = () => {
    showProfilePhotoModal.value = false;
    // Clean up preview URL to prevent memory leaks
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
        photoPreviewUrl.value = null;
    }
    // Clear file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// QR Code methods
const showQrCode = async () => {
    try {
        const response = await axios.post(route('profile.generate-qr'));
        qrCodeData.value = {
            qrCode: response.data.qr_code_svg,
            url: response.data.url,
            expiresAt: response.data.expires_at
        };
        showQrModal.value = true;
        startCountdown();
    } catch (error) {
        logger.error('QR code generation error:', error);
        toast.error('Failed to generate QR code');
    }
};

const startCountdown = () => {
    // Clear any existing interval
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }

    const updateCountdown = () => {
        if (!qrCodeData.value) return;

        const now = new Date();
        const expiresAt = new Date(qrCodeData.value.expiresAt);
        const diff = expiresAt - now;

        if (diff <= 0) {
            qrCountdown.value = 'EXPIRED';
            clearInterval(qrCountdownInterval.value);
            return;
        }

        const totalMinutes = Math.floor(diff / 1000 / 60);
        const seconds = Math.floor((diff / 1000) % 60);
        qrCountdown.value = `${totalMinutes} min ${seconds} sec`;
    };

    updateCountdown();
    qrCountdownInterval.value = setInterval(updateCountdown, 1000);
};

// Watch for modal close to clear interval
watch(showQrModal, (newValue) => {
    if (!newValue && qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
        qrCountdownInterval.value = null;
    }
});

// Cleanup on component unmount
onUnmounted(() => {
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }
});

const copyToClipboard = async (text) => {
    try {
        // Try modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            toast.success('Link copied to clipboard!');
        } else {
            // Fallback for non-HTTPS contexts (like IP addresses)
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                toast.success('Link copied to clipboard!');
            } catch (err) {
                toast.error('Failed to copy link');
            }
            document.body.removeChild(textArea);
        }
    } catch (error) {
        toast.error('Failed to copy to clipboard');
    }
};

// Calendar methods
const openEncodingCalendarModal = async () => {
    showEncodingCalendarModal.value = true;
    selectedCalendarDate.value = new Date();
    await loadRecordsSummaryForMonth();
    // Also fetch records for today automatically
    await fetchRecordsForDate(selectedCalendarDate.value);
};

const closeEncodingCalendarModal = () => {
    showEncodingCalendarModal.value = false;
    calendarRecords.value = [];
    recordsByDate.value = {};
};

const onCalendarDateSelect = async (date) => {
    if (!date) return;

    selectedCalendarDate.value = date;
    await fetchRecordsForDate(date);
};

const fetchRecordsForDate = async (date) => {
    if (!date) return;

    loadingRecords.value = true;
    try {
        // Handle both Date objects and date strings
        let dateStr;
        if (typeof date === 'string') {
            dateStr = date; // Already in YYYY-MM-DD format
        } else {
            // Convert Date object to YYYY-MM-DD, using local date to avoid timezone issues
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            dateStr = `${year}-${month}-${day}`;
        }

        const response = await axios.get(route('api.records.bydate'), {
            params: { date: dateStr }
        });

        calendarRecords.value = response.data.records;
    } catch (error) {
        logger.error('Error fetching records for date:', error);
        toast.error('Failed to load records for this date');
        calendarRecords.value = [];
    } finally {
        loadingRecords.value = false;
    }
};

const selectDateFromList = async (dateStr) => {
    // Convert date string directly without creating intermediate Date object
    // This prevents timezone conversion issues
    selectedCalendarDate.value = new Date(dateStr);
    await fetchRecordsForDate(dateStr);
};

const loadRecordsSummaryForMonth = async () => {
    if (!selectedCalendarDate.value) return;

    try {
        const date = new Date(selectedCalendarDate.value);
        const year = date.getFullYear();
        const month = date.getMonth() + 1;

        const response = await axios.get(route('api.records.summary-month'), {
            params: { year, month }
        });

        recordsByDate.value = response.data.records_by_date;
    } catch (error) {
        logger.error('Error loading records summary:', error);
    }
};


</script>

<template>

    <Head title="My Reports" />
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Reports</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex items-center space-x-6">
                        <!-- Avatar -->
                        <div class="relative">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg cursor-pointer hover:opacity-80 transition-opacity overflow-hidden"
                                :class="{ 'bg-gradient-to-br from-indigo-500 to-purple-600': !reportData?.has_profile_photo }"
                                @click="openViewPhotoModal">
                                <!-- Profile Photo -->
                                <img v-if="reportData?.has_profile_photo" :src="reportData.profile_photo_url"
                                    :alt="reportData?.user_name || 'Profile Photo'"
                                    class="w-full h-full object-cover" />
                                <!-- Initials Fallback -->
                                <span v-else class="text-2xl font-bold text-white">
                                    {{ (reportData?.user_name || 'U').charAt(0).toUpperCase() }}

                                </span>
                            </div>
                            <button @click="openProfilePhotoModal"
                                class="absolute -bottom-1 -right-1 w-6 h-6 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center shadow-lg transition-colors cursor-pointer">
                                <i class="pi pi-camera text-white" style="font-size: 0.75rem"></i>
                            </button>
                            <!-- <div class="absolute -bottom-1 -right-1 w-6 h-6">
                                <Button @click="openProfilePhotoModal" icon="pi pi-user" severity="info" rounded
                                    size="small" />
                            </div> -->
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h2 class="text-2xl font-bold text-gray-900">{{ reportData?.user_name || 'User' }}</h2>
                            </div>
                            <p class="text-gray-600 mb-2">@{{ reportData?.user_summary?.basic_info?.username ||
                                'username' }}
                            </p>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="pi pi-clock w-4 h-4 mr-1"></i>
                                Profile updated {{ formatDate(reportData?.generated_at) }}
                            </div>
                        </div>

                        <!-- Profile Action Buttons -->
                        <div class="flex items-center gap-2">
                            <Button @click="openEncodingCalendarModal" icon="pi pi-calendar" size="small"
                                label="Encoding Records" class="w-full md:w-auto" severity="info" />
                            <Button @click="openEditProfileModal" icon="pi pi-pen-to-square" size="small"
                                label="Update Name" class="w-full md:w-auto" variant="outlined" />

                            <Button @click="openChangePasswordModal" icon="pi pi-key" size="small"
                                label="Change Password" class="w-full md:w-auto" variant="outlined" />


                            <!-- <form method="POST" :action="route('logout')" class="inline">
                                <input type="hidden" name="_token" :value="$page.props.csrf_token">
                                <button type="submit"
                                    class="px-3 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2 cursor-pointer"
                                    title="Logout">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span class="hidden md:inline">Logout</span>
                                </button>
                            </form> -->
                        </div>
                    </div>
                </div>


                <!-- Statistics Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">

                    <!-- Encoded Today -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Encoded Today</p>
                                <p class="text-3xl font-bold text-blue-600">
                                    {{ encodedToday }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-calendar text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Applications Encoded -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Encoded</p>
                                <p class="text-3xl font-bold text-indigo-600">
                                    {{ (userApplications.total_created || 0) }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-file text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Created Records -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Created Records</p>
                                <p class="text-3xl font-bold text-emerald-600">
                                    {{ userApplications.total_created || 0 }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-plus text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Updated Records -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Updated Records</p>
                                <p class="text-3xl font-bold text-amber-600">
                                    {{ userApplications.total_updated || 0 }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-pencil text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Encoded Existing/Approved Profiles -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Approved Profiles</p>
                                <p class="text-3xl font-bold text-rose-600">
                                    {{ encodedExistingApproved }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-rose-500 to-pink-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-check-circle text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Activity -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-600">{{ activityLabel }}</p>
                                <p class="text-xs font-bold text-gray-800 mt-1">
                                    {{ latestActivity ? formatDate(latestActivity) : 'No activity' }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                                <i class="pi pi-clock text-white" style="font-size: 1.5rem"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Breakdown -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Application Breakdown</h3>
                        <div class="flex items-center space-x-2">
                            <Checkbox v-model="showCurrentMonthOnly" :binary="true" inputId="currentMonthFilter" />
                            <label for="currentMonthFilter" class="text-sm font-medium text-gray-700">
                                Current Month Only
                            </label>
                        </div>
                    </div>

                    <!-- Overall Total -->
                    <div
                        class="mb-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg border border-indigo-200">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold text-gray-800">Total Applications</span>
                            <span class="text-2xl font-bold text-indigo-600">{{ overallTotal }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- By Program -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700 text-center">By Program</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="program in filteredProgramData" :key="program.program_name"
                                    class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ program.program_name || 'No Program' }}
                                        </span>
                                        <span
                                            class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs font-semibold">
                                            {{ program.count }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2 text-xs">
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">
                                            Pending: {{ program.pending }}
                                        </span>
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                            Approved: {{ program.approved }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- By Course -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700 text-center">By Course</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="course in filteredCourseData" :key="course.course_name"
                                    class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ course.course_name || 'No Course' }}
                                        </span>
                                        <span
                                            class="bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full text-xs font-semibold">
                                            {{ course.count }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2 text-xs">
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">
                                            Pending: {{ course.pending }}
                                        </span>
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                            Approved: {{ course.approved }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- By School -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700 text-center">By School</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="school in filteredSchoolData" :key="school.school_name"
                                    class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ school.school_name || 'No School' }}
                                        </span>
                                        <span
                                            class="bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-semibold">
                                            {{ school.count }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2 text-xs">
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">
                                            Pending: {{ school.pending }}
                                        </span>
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                            Approved: {{ school.approved }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Dialog -->
        <Dialog v-model:visible="showEditProfileModal" modal header="Edit Profile Name" class="w-96">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label for="profileName" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <InputText id="profileName" v-model="editProfileForm.name" class="w-full"
                        placeholder="Enter your full name" :invalid="!!editProfileForm.errors?.name" />
                    <small v-if="editProfileForm.errors?.name" class="text-red-500">
                        {{ editProfileForm.errors.name }}
                    </small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeEditProfileModal" outlined />
                    <Button label="Update Profile" @click="submitProfileUpdate" :loading="editProfileForm.processing"
                        :disabled="!editProfileForm.name || editProfileForm.processing" />
                </div>
            </template>
        </Dialog>

        <!-- Profile Photo Dialog -->
        <Dialog v-model:visible="showProfilePhotoModal" modal header="Change Profile Photo" class="w-96">
            <div class="space-y-4">
                <div class="text-center">
                    <div
                        class="w-24 h-24 mx-auto bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg mb-4 overflow-hidden">
                        <!-- Profile Photo -->
                        <img v-if="reportData?.has_profile_photo" :src="reportData.profile_photo_url"
                            :alt="reportData?.user_name || 'Profile Photo'" class="w-full h-full object-cover" />
                        <!-- Initials Fallback -->
                        <span v-else class="text-3xl font-bold text-white">
                            {{ (reportData?.user_name || 'U').charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Upload a new profile photo</p>
                </div>

                <div class="space-y-2">
                    <!-- QR Code Upload Button -->
                    <div class="w-full">
                        <button @click="showQrCode" type="button"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm bg-blue-50 text-sm font-medium text-blue-700 hover:bg-blue-100">
                            <i class="pi pi-qrcode mr-2"></i>
                            Upload via Mobile (QR Code)
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 text-center">For other upload options, go to Settings</p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeProfilePhotoModal" outlined />
                    <Button label="Go to Settings" severity="primary" @click="closeProfilePhotoModal" />
                </div>
            </template>
        </Dialog>

        <!-- View Profile Photo Modal -->
        <Dialog v-model:visible="showViewPhotoModal" modal header="Profile Photo"
            :style="{ width: '90vw', maxWidth: '600px' }">
            <div class="flex flex-col items-center justify-center space-y-4">
                <!-- Full Size Photo -->
                <div class="flex items-center justify-center rounded-full border-6 border-sky-500">
                    <img v-if="reportData?.has_profile_photo" :src="reportData.profile_photo_url"
                        :alt="reportData?.user_name || 'Profile Photo'"
                        class="max-w-full max-h-[70vh] object-contain rounded-full shadow-lg" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between w-full">
                    <Button label="Change Photo" severity="secondary"
                        @click="showViewPhotoModal = false; openProfilePhotoModal()" outlined />
                    <Button label="Close" @click="showViewPhotoModal = false" />
                </div>
            </template>
        </Dialog>

        <!-- QR Code Modal -->
        <Dialog v-model:visible="showQrModal" modal header="Mobile Upload QR Code"
            :style="{ width: '30vw', minWidth: '400px' }">
            <div v-if="qrCodeData" class="text-center space-y-4">
                <!-- QR Code -->
                <div class="bg-white p-6 rounded-lg border-2 border-gray-200 inline-block">
                    <div v-html="qrCodeData.qrCode"></div>
                </div>

                <!-- Instructions -->
                <div class="text-left space-y-3">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            <i class="pi pi-info-circle mr-2"></i>How to use:
                        </p>
                        <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                            <li>Scan this QR code with your mobile device</li>
                            <li>Take a photo or select from gallery</li>
                            <li>Photo will be automatically optimized and updated</li>
                        </ol>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <p class="text-xs text-yellow-800">
                            <i class="pi pi-exclamation-triangle mr-2"></i>
                            <strong>Expires in:</strong>
                            <span :class="{
                                'text-yellow-600': qrCountdown.includes('min') && !qrCountdown.includes('0 min'),
                                'text-orange-600': qrCountdown.includes('0 min') && parseInt(qrCountdown) >= 5,
                                'text-red-600 font-bold': qrCountdown.includes('0 min') && parseInt(qrCountdown) < 5 || qrCountdown === 'EXPIRED'
                            }">
                                {{ qrCountdown || 'Loading...' }}
                            </span>
                        </p>
                    </div>

                    <!-- Mobile URL (for copying) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Or copy this link:</label>
                        <div class="flex gap-2">
                            <InputText type="text" :value="qrCodeData.url" readonly class="flex-1 text-xs" />
                            <Button icon="pi pi-copy" size="small" @click="copyToClipboard(qrCodeData.url)"
                                v-tooltip.top="'Copy link'" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" @click="showQrModal = false" />
            </template>
        </Dialog>

        <!-- Change Password Dialog -->
        <Dialog v-model:visible="showChangePasswordModal" modal header="Change Password" class="w-96">
            <div class="space-y-4">
                <div class="space-y-2">
                    <InputLabel for="current_password" value="Current Password" />
                    <InputText id="current_password" v-model="changePasswordForm.current_password" type="password"
                        class="w-full" placeholder="Enter current password" />
                    <InputError :message="changePasswordForm.errors.current_password" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password" value="New Password" />
                    <InputText id="password" v-model="changePasswordForm.password" type="password" class="w-full"
                        placeholder="Enter new password" />
                    <InputError :message="changePasswordForm.errors.password" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <InputText id="password_confirmation" v-model="changePasswordForm.password_confirmation"
                        type="password" class="w-full" placeholder="Confirm new password" />
                    <InputError :message="changePasswordForm.errors.password_confirmation" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeChangePasswordModal" outlined />
                    <Button label="Change Password" @click="submitPasswordChange"
                        :loading="changePasswordForm.processing" />
                </div>
            </template>
        </Dialog>

        <!-- Encoding Records Calendar Dialog -->
        <Dialog v-model:visible="showEncodingCalendarModal" modal header="Encoding Records by Date" maximizable
            :style="{ width: '60vw' }">
            <div class="space-y-6">
                <!-- Calendar with Record Indicators -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="pi pi-info-circle mr-2"></i>
                            {{ Object.keys(recordsByDate).length > 0 ? `Found records on
                            ${Object.keys(recordsByDate).length}
                            dates this month. Click on a date to view records.` : 'Loading dates with records...' }}
                        </p>
                    </div>
                    <DatePicker v-model="selectedCalendarDate" inline date-format="yy-mm-dd"
                        @date-select="onCalendarDateSelect" class="w-full" />

                    <!-- Legend for dates with records -->
                    <div v-if="Object.keys(recordsByDate).length > 0"
                        class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-xs font-semibold text-gray-600 mb-2">Dates with records:</p>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="(count, date) in recordsByDate" :key="date"
                                class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-medium cursor-pointer hover:bg-indigo-200 transition"
                                @click="selectDateFromList(date)">
                                {{ new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}: {{
                                    count
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Records for Selected Date -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Records for {{ selectedCalendarDate ? new Date(selectedCalendarDate).toDateString() :
                                `Select a Date` }}
                        </h3>
                        <span v-if="selectedCalendarDate"
                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            {{ calendarRecords.length }} record(s)
                        </span>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loadingRecords" class="flex items-center justify-center p-8">
                        <div class="text-center">
                            <div class="inline-flex items-center space-x-2">
                                <i class="pi pi-spin pi-spinner text-2xl text-indigo-600"></i>
                                <span class="text-gray-600">Loading records...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Records Table -->
                    <DataTable v-else v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="calendarRecords"
                        class="w-full" :rows="10" paginator responsive-layout="scroll" striped-rows>
                        <Column field="applicant_name" header="Applicant Name" class="min-w-48" />
                        <Column field="program_name" header="Program" class="min-w-40" />
                        <Column field="record_type" header="Record Type" class="min-w-32">
                            <template #body="{ data }">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold',
                                    data.record_type === 'Created' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                                ]">
                                    {{ data.record_type }}
                                </span>
                            </template>
                        </Column>
                        <Column field="created_time" header="Time" class="min-w-24">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">{{ data.created_time }}</span>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-calendar text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No records found for this date</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <template #footer>
                <Button label="Close" @click="closeEncodingCalendarModal" />
            </template>
        </Dialog>
    </AdminLayout>
</template>
