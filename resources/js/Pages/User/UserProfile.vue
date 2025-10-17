<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import FileUpload from 'primevue/fileupload';
import { ref, computed, nextTick } from 'vue';
import { useDateUtils } from '@/composables/dateUtils.js';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/ui/inputs/InputLabel.vue';
import InputError from '@/Components/ui/inputs/InputError.vue';
import { toast } from 'vue3-toastify';

const { formatDate } = useDateUtils();

const props = defineProps({
    reportData: Object
});

// Reactive data
const showCurrentMonthOnly = ref(false);
const showChangePasswordModal = ref(false);
const showEditProfileModal = ref(false);
const showProfilePhotoModal = ref(false);
const photoPreviewUrl = ref(null);
const fileInput = ref(null);

// Image editor state
const showImageEditor = ref(false);
const selectedImageFile = ref(null);
const editorCanvas = ref(null);
const editorImage = ref(null);
const imageScale = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const canvasSize = 300; // Square canvas for profile photo
const minScale = 0.5;
const maxScale = 3;

// Forms
const changePasswordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const editProfileForm = useForm({
    name: props.reportData?.user_name || '',
});

const profilePhotoForm = useForm({
    photo: null,
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
            console.error('Profile update failed:', errors);
        }
    });
};

// Profile photo methods
const openProfilePhotoModal = () => {
    profilePhotoForm.photo = null;
    profilePhotoForm.clearErrors();
    showProfilePhotoModal.value = true;
    console.log('Photo modal opened');
};

const closeProfilePhotoModal = () => {
    showProfilePhotoModal.value = false;
    // Clean up preview URL to prevent memory leaks
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
        photoPreviewUrl.value = null;
    }
    // Clean up image editor state
    showImageEditor.value = false;
    if (selectedImageFile.value) {
        selectedImageFile.value = null;
    }
    if (editorImage.value) {
        URL.revokeObjectURL(editorImage.value.src);
        editorImage.value = null;
    }
    // Clear file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    profilePhotoForm.reset();
    profilePhotoForm.clearErrors();
};

const onPhotoSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedImageFile.value = file;
        // Create preview URL for editor
        const imageUrl = URL.createObjectURL(file);

        // Initialize image editor
        const img = new Image();
        img.onload = () => {
            editorImage.value = img;
            // Calculate initial scale to fit image in canvas
            const scaleX = canvasSize / img.width;
            const scaleY = canvasSize / img.height;
            imageScale.value = Math.min(scaleX, scaleY);

            // Center the image
            imagePosition.value = {
                x: (canvasSize - img.width * imageScale.value) / 2,
                y: (canvasSize - img.height * imageScale.value) / 2
            };

            showImageEditor.value = true;

            // Use nextTick to ensure the canvas is rendered before drawing
            nextTick(() => {
                // Add a small delay to ensure canvas is fully ready
                setTimeout(() => {
                    drawCanvas();
                }, 100);
            });
        };
        img.src = imageUrl;

        console.log('Photo selected for editing:', file.name, file.size, file.type);
    } else {
        console.error('No file selected');
    }
};

const submitPhotoUpdate = () => {
    console.log('Submit photo update called');
    console.log('Photo file:', profilePhotoForm.photo);

    if (!profilePhotoForm.photo) {
        toast.error('Please select a photo first!');
        return;
    }

    console.log('Starting photo upload...');
    profilePhotoForm.post(route('profile.photo.update'), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Photo upload successful');
            closeProfilePhotoModal();
            toast.success('Profile photo updated successfully!');
            // Refresh the page to show updated data using Inertia
            router.reload({ only: ['reportData'] });
        },
        onError: (errors) => {
            console.error('Photo upload failed:', errors);
            toast.error('Photo upload failed. Please try again.');
        }
    });
};

// Image Editor Functions
const drawCanvas = () => {
    if (!editorCanvas.value || !editorImage.value) return;

    const canvas = editorCanvas.value;
    const ctx = canvas.getContext('2d');
    const radius = canvasSize / 2;
    const centerX = radius;
    const centerY = radius;

    // Clear canvas
    ctx.clearRect(0, 0, canvasSize, canvasSize);

    // Save context for clipping
    ctx.save();

    // Create circular clipping path
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.clip();

    // Fill with white background
    ctx.fillStyle = '#ffffff';
    ctx.fill();

    // Draw image within the circular clip
    ctx.drawImage(
        editorImage.value,
        imagePosition.value.x,
        imagePosition.value.y,
        editorImage.value.width * imageScale.value,
        editorImage.value.height * imageScale.value
    );

    // Restore context
    ctx.restore();
};

const handleZoomIn = () => {
    if (imageScale.value < maxScale) {
        imageScale.value = Math.min(imageScale.value * 1.1, maxScale);
        drawCanvas();
    }
};

const handleZoomOut = () => {
    if (imageScale.value > minScale) {
        imageScale.value = Math.max(imageScale.value / 1.1, minScale);
        drawCanvas();
    }
};

const handleMouseDown = (event) => {
    isDragging.value = true;
    const rect = editorCanvas.value.getBoundingClientRect();
    dragStart.value = {
        x: event.clientX - rect.left - imagePosition.value.x,
        y: event.clientY - rect.top - imagePosition.value.y
    };
};

const handleMouseMove = (event) => {
    if (!isDragging.value) return;

    const rect = editorCanvas.value.getBoundingClientRect();
    imagePosition.value = {
        x: event.clientX - rect.left - dragStart.value.x,
        y: event.clientY - rect.top - dragStart.value.y
    };

    drawCanvas();
};

const handleMouseUp = () => {
    isDragging.value = false;
};

const handleWheel = (event) => {
    event.preventDefault();

    if (event.deltaY < 0) {
        handleZoomIn();
    } else {
        handleZoomOut();
    }
};

const cancelImageEdit = () => {
    showImageEditor.value = false;
    // Clean up
    if (selectedImageFile.value) {
        selectedImageFile.value = null;
    }
    if (editorImage.value) {
        URL.revokeObjectURL(editorImage.value.src);
        editorImage.value = null;
    }
    // Clear file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const confirmImageEdit = () => {
    if (!editorCanvas.value) return;

    // Convert canvas to blob
    editorCanvas.value.toBlob((blob) => {
        if (blob) {
            // Create a new file from the cropped image
            const croppedFile = new File([blob], selectedImageFile.value.name, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });

            // Set the cropped image as the photo to upload
            profilePhotoForm.photo = croppedFile;

            // Create preview URL
            photoPreviewUrl.value = URL.createObjectURL(blob);

            // Close editor
            showImageEditor.value = false;

            // Clean up editor resources
            if (editorImage.value) {
                URL.revokeObjectURL(editorImage.value.src);
                editorImage.value = null;
            }

            console.log('Image edited and ready for upload');
        }
    }, 'image/jpeg', 0.9);
};
</script>

<template>

    <Head title="User Profile" />
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Profile</h2>
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
                                @click="openProfilePhotoModal">
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
                                class="absolute -bottom-1 -right-1 w-6 h-6 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center shadow-lg transition-colors">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h2 class="text-2xl font-bold text-gray-900">{{ reportData?.user_name || 'User' }}</h2>
                                <button @click="openEditProfileModal"
                                    class="p-1 text-gray-400 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-600 mb-2">@{{ reportData?.user_summary?.basic_info?.username ||
                                'username' }}
                            </p>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Profile updated {{ formatDate(reportData?.generated_at) }}
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="text-right">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-2 h-2 mr-1.5 fill-current" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">

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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
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
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Breakdown -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Application Breakdown</h3>
                        <div class="flex items-center space-x-2">
                            <input id="currentMonthFilter" type="checkbox" v-model="showCurrentMonthOnly"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
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
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="text-sm text-gray-700 font-medium">
                                        {{ program.program_name || 'No Program' }}
                                    </span>
                                    <span
                                        class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs font-semibold">
                                        {{ program.count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- By Course -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700 text-center">By Course</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="course in filteredCourseData" :key="course.course_name"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="text-sm text-gray-700 font-medium">
                                        {{ course.course_name || 'No Course' }}
                                    </span>
                                    <span
                                        class="bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full text-xs font-semibold">
                                        {{ course.count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- By School -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700 text-center">By School</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="school in filteredSchoolData" :key="school.school_name"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="text-sm text-gray-700 font-medium">
                                        {{ school.school_name || 'No School' }}
                                    </span>
                                    <span
                                        class="bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-semibold">
                                        {{ school.count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Settings</h3>
                    <div class="space-y-3">
                        <Button @click="openChangePasswordModal" icon="pi pi-lock" label="Change Password"
                            class="w-full" severity="secondary" outlined />
                        <form method="POST" :action="route('logout')" class="w-full">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token">
                            <Button type="submit" icon="pi pi-sign-out" label="Logout" class="w-full" severity="danger"
                                outlined />
                        </form>
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
                    <!-- Custom File Input -->
                    <div class="w-full">
                        <input type="file" accept="image/*" @change="onPhotoSelect" ref="fileInput" class="hidden"
                            id="photo-input" />
                        <label for="photo-input"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer">
                            Choose Photo
                        </label>
                    </div>

                    <!-- Photo Preview -->
                    <div v-if="photoPreviewUrl" class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">Preview:</p>
                        <div class="w-32 h-32 mx-auto border-2 border-gray-300 rounded-full overflow-hidden">
                            <img :src="photoPreviewUrl" alt="Photo preview" class="w-full h-full object-cover" />
                        </div>
                    </div>

                    <small v-if="profilePhotoForm.errors?.photo" class="text-red-500">
                        {{ profilePhotoForm.errors.photo }}
                    </small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeProfilePhotoModal" outlined />
                    <Button label="Upload Photo" @click="submitPhotoUpdate" :loading="profilePhotoForm.processing"
                        :disabled="!profilePhotoForm.photo || profilePhotoForm.processing" />
                </div>
            </template>
        </Dialog>

        <!-- Image Editor Dialog -->
        <Dialog v-model:visible="showImageEditor" modal header="Edit Photo" class="w-auto">
            <div class="space-y-4">
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-4">Zoom and drag to position your photo</p>

                    <!-- Canvas Editor -->
                    <div class="relative inline-block border-2 border-gray-600 rounded-full overflow-hidden">
                        <canvas ref="editorCanvas" :width="canvasSize" :height="canvasSize" class="cursor-move block"
                            @mousedown="handleMouseDown" @mousemove="handleMouseMove" @mouseup="handleMouseUp"
                            @mouseleave="handleMouseUp" @wheel="handleWheel"></canvas>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="flex justify-center gap-2 mt-4">
                        <Button label="Zoom Out" icon="pi pi-minus" @click="handleZoomOut" size="small"
                            :disabled="imageScale <= minScale" outlined />
                        <Button label="Zoom In" icon="pi pi-plus" @click="handleZoomIn" size="small"
                            :disabled="imageScale >= maxScale" outlined />
                    </div>

                    <p class="text-xs text-gray-500 mt-2">
                        Use mouse wheel to zoom, drag to move the image
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="cancelImageEdit" outlined />
                    <Button label="Use This Photo" @click="confirmImageEdit" />
                </div>
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
    </AdminLayout>
</template>
