<template>
    <AdminLayout>
        <div class="p-4 md:p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Settings</h1>
            </div>

            <!-- Settings List -->
            <div class="space-y-2 max-w-2xl">
                <!-- Profile Photo Item -->
                <button @click="showPhotoDialog = true"
                    class="w-full bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4">
                        <div
                            class="w-10 h-10 rounded-full overflow-hidden border-2 border-purple-300 flex items-center justify-center bg-purple-100">
                            <img v-if="props.user?.profile_photo_url" :src="props.user.profile_photo_url" alt="Profile"
                                class="w-full h-full object-cover" />
                            <i v-else class="pi pi-user text-purple-600 text-lg"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-gray-900">Profile Photo</h3>
                            <p class="text-sm text-gray-500">Update your avatar</p>
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-gray-400"></i>
                </button>

                <!-- Password Item -->
                <button @click="showPasswordDialog = true"
                    class="w-full bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="pi pi-lock text-blue-600"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-gray-900">Change Password</h3>
                            <p class="text-sm text-gray-500">Update your password</p>
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-gray-400"></i>
                </button>

                <!-- Profile Item -->
                <button @click="showProfileDialog = true"
                    class="w-full bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="pi pi-user text-green-600"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-gray-900">Profile Information</h3>
                            <p class="text-sm text-gray-500">{{ profileForm.name || 'Update your details' }}</p>
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-gray-400"></i>
                </button>

                <!-- Theme Item -->
                <button @click="showThemeDialog = true"
                    class="w-full bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="pi pi-palette text-purple-600"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-gray-900">Theme</h3>
                            <p class="text-sm text-gray-500 capitalize">{{ theme }}</p>
                        </div>
                    </div>
                    <i class="pi pi-chevron-right text-gray-400"></i>
                </button>
            </div>

            <!-- Profile Photo Dialog -->
            <Dialog v-model:visible="showPhotoDialog" header="Change Profile Photo" :modal="true"
                class="w-full md:w-[550px]" @hide="closePhotoDialog">
                <div class="space-y-4">
                    <!-- Current Profile Photo Display -->
                    <div class="text-center">
                        <div
                            class="w-24 h-24 mx-auto bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg mb-4 overflow-hidden">
                            <img v-if="props.has_profile_photo" :src="props.profile_photo_url"
                                :alt="props.user?.name || 'Profile Photo'" class="w-full h-full object-cover" />
                            <span v-else class="text-3xl font-bold text-white">
                                {{ (props.user?.name || 'U').charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Upload a new profile photo</p>
                    </div>

                    <!-- File Input / Image Editor -->
                    <div v-if="!showImageEditor">
                        <!-- Custom File Input -->
                        <div class="w-full">
                            <input type="file" accept="image/*" @change="onPhotoSelect" ref="photoInput" class="hidden"
                                id="photo-input" />
                            <label for="photo-input"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer">
                                Choose Photo
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-2">JPG, PNG, GIF, WebP (Max 10MB)</p>

                        <!-- QR Code Upload Option -->
                        <div class="relative my-3">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or quick upload via</span>
                            </div>
                        </div>

                        <button type="button" @click="showQrCode"
                            class="w-full px-4 py-2 border border-purple-300 text-purple-700 rounded-md shadow-sm bg-purple-50 text-sm font-medium hover:bg-purple-100 cursor-pointer">
                            <i class="pi pi-qrcode mr-2"></i> Upload via QR Code
                        </button>
                    </div>

                    <!-- Image Editor Canvas -->
                    <div v-if="showImageEditor" class="space-y-3">
                        <p class="text-sm text-gray-600 text-center mb-4">Zoom and drag to position your photo</p>
                        <div class="flex justify-center mb-4">
                            <canvas ref="editorCanvas" :width="canvasSize" :height="canvasSize"
                                class="border-2 border-purple-300 rounded-full cursor-move" @mousedown="handleMouseDown"
                                @mousemove="handleMouseMove" @mouseup="handleMouseUp" @mouseleave="handleMouseUp"
                                @wheel.prevent="handleWheel"></canvas>
                        </div>

                        <!-- Image Editor Controls -->
                        <div class="flex gap-2 justify-center">
                            <button type="button" @click="handleZoomOut"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-semibold cursor-pointer">
                                <i class="pi pi-minus"></i> Zoom Out
                            </button>
                            <span class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold">
                                {{ Math.round((imageScale.value || 1) * 100) }}%
                            </span>
                            <button type="button" @click="handleZoomIn"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-semibold cursor-pointer">
                                <i class="pi pi-plus"></i> Zoom In
                            </button>
                            <button type="button" @click="resetImage"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-semibold cursor-pointer">
                                <i class="pi pi-refresh"></i> Reset
                            </button>
                        </div>
                    </div>

                    <p v-if="successMsg.photo" class="text-green-600 text-sm font-semibold text-center">
                        ✓ Photo updated successfully
                    </p>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showPhotoDialog = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 cursor-pointer">
                            Cancel
                        </button>
                        <button v-if="showImageEditor" type="button" @click="cancelEditor"
                            class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 cursor-pointer">
                            Back
                        </button>
                        <button type="button" @click="submitPhotoForm" :disabled="!selectedImageFile || photoProcessing"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 disabled:bg-purple-400 disabled:cursor-not-allowed cursor-pointer">
                            {{ photoProcessing ? 'Uploading...' : 'Upload' }}
                        </button>
                    </div>
                </div>
            </Dialog>

            <!-- Change Password Dialog -->
            <Dialog v-model:visible="showPasswordDialog" header="Change Password" :modal="true" class="w-full md:w-96"
                @hide="passwordForm.reset()">
                <form @submit.prevent="submitPasswordForm" class="space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                        <input v-model="passwordForm.current_password" type="password"
                            placeholder="Enter current password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': passwordForm.errors.current_password }"
                            @input="clearError('password', 'current_password')" />
                        <p v-if="passwordForm.errors.current_password" class="text-red-500 text-sm mt-1">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                        <input v-model="passwordForm.new_password" type="password" placeholder="Enter new password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': passwordForm.errors.new_password }"
                            @input="clearError('password', 'new_password')" />
                        <p v-if="passwordForm.errors.new_password" class="text-red-500 text-sm mt-1">
                            {{ passwordForm.errors.new_password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                        <input v-model="passwordForm.new_password_confirmation" type="password"
                            placeholder="Confirm new password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': passwordForm.errors.new_password_confirmation }"
                            @input="clearError('password', 'new_password_confirmation')" />
                        <p v-if="passwordForm.errors.new_password_confirmation" class="text-red-500 text-sm mt-1">
                            {{ passwordForm.errors.new_password_confirmation }}
                        </p>
                    </div>

                    <p v-if="successMsg.password" class="text-green-600 text-sm font-semibold text-center">
                        ✓ Password updated successfully
                    </p>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showPasswordDialog = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="passwordForm.processing"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed cursor-pointer">
                            {{ passwordForm.processing ? 'Updating...' : 'Update' }}
                        </button>
                    </div>
                </form>
            </Dialog>

            <!-- Profile Dialog -->
            <Dialog v-model:visible="showProfileDialog" header="Profile Information" :modal="true"
                class="w-full md:w-96">
                <form @submit.prevent="submitProfileForm" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                        <input v-model="profileForm.name" type="text" placeholder="Enter full name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            :class="{ 'border-red-500': profileForm.errors.name }"
                            @input="clearError('profile', 'name')" />
                        <p v-if="profileForm.errors.name" class="text-red-500 text-sm mt-1">
                            {{ profileForm.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <input v-model="profileForm.email" type="email" placeholder="Enter email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            :class="{ 'border-red-500': profileForm.errors.email }"
                            @input="clearError('profile', 'email')" />
                        <p v-if="profileForm.errors.email" class="text-red-500 text-sm mt-1">
                            {{ profileForm.errors.email }}
                        </p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                        <input v-model="profileForm.phone" type="text" placeholder="Enter phone number"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            @input="clearError('profile', 'phone')" />
                    </div>

                    <!-- Office Designation -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Office Designation</label>
                        <input v-model="profileForm.office_designation" type="text" placeholder="Enter designation"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            @input="clearError('profile', 'office_designation')" />
                    </div>

                    <p v-if="successMsg.profile" class="text-green-600 text-sm font-semibold text-center">
                        ✓ Profile updated successfully
                    </p>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showProfileDialog = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="profileForm.processing"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 disabled:bg-green-400 disabled:cursor-not-allowed cursor-pointer">
                            {{ profileForm.processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </Dialog>

            <!-- Theme Dialog -->
            <Dialog v-model:visible="showThemeDialog" header="Theme Preference" :modal="true" class="w-full md:w-96">
                <div class="space-y-4">
                    <p class="text-gray-700 text-sm">Select your preferred theme</p>

                    <div class="space-y-2">
                        <label class="block p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="theme === 'light' ? 'border-purple-600 bg-purple-50' : 'border-gray-300 hover:border-gray-400'">
                            <div class="flex items-center space-x-3">
                                <input type="radio" v-model="theme" value="light" class="w-4 h-4" />
                                <div>
                                    <span class="font-semibold text-gray-900">Light Theme</span>
                                    <p class="text-sm text-gray-500">Bright and clean interface</p>
                                </div>
                            </div>
                        </label>

                        <label class="block p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="theme === 'dark' ? 'border-purple-600 bg-purple-50' : 'border-gray-300 hover:border-gray-400'">
                            <div class="flex items-center space-x-3">
                                <input type="radio" v-model="theme" value="dark" class="w-4 h-4" />
                                <div>
                                    <span class="font-semibold text-gray-900">Dark Theme</span>
                                    <p class="text-sm text-gray-500">Easy on the eyes</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <p class="text-xs text-gray-500 italic mt-4">
                        More theme options coming soon
                    </p>

                    <div class="flex gap-3 pt-4">
                        <button @click="showThemeDialog = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </Dialog>

            <!-- QR Code Modal -->
            <Dialog v-model:visible="showQrModal" header="Upload Photo via QR Code" :modal="true"
                class="w-full md:w-[550px]">
                <div v-if="qrCodeData" class="space-y-4">
                    <!-- QR Code Display -->
                    <div class="flex justify-center">
                        <div class="p-4 bg-white rounded-lg border-2 border-gray-200">
                            <div v-html="qrCodeData.qrCode" class="w-64 h-64"></div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-900">
                            <strong>📱 Mobile Upload:</strong> Open this page on your mobile device and scan this QR
                            code with your camera or a QR code scanner app to quickly upload a photo.
                        </p>
                    </div>

                    <!-- URL Option -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-600 mb-2">Or use this link on mobile:</p>
                        <div class="flex gap-2">
                            <input type="text" :value="qrCodeData.url" readonly
                                class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded text-xs text-gray-700"
                                @click="$event.target.select()" />
                            <button type="button" @click="copyToClipboard(qrCodeData.url)"
                                class="px-3 py-2 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 text-xs whitespace-nowrap transition-all cursor-pointer">
                                {{ copyButtonText }}
                            </button>
                        </div>
                    </div>

                    <!-- Expiration Timer -->
                    <div class="flex items-center justify-center">
                        <div :class="['text-lg font-bold rounded-lg px-4 py-2', getQrCountdownColor()]">
                            ⏱️ Expires in: {{ qrCountdown }}
                        </div>
                    </div>

                    <!-- Close Button -->
                    <button type="button" @click="showQrModal = false"
                        class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 cursor-pointer">
                        Close
                    </button>
                </div>
            </Dialog>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, nextTick, watch, onUnmounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const page = usePage();

const props = defineProps({
    user: Object,
    profile_photo_url: String,
    has_profile_photo: Boolean,
    preferences: Object,
});

// Dialog states
const showPhotoDialog = ref(false);
const showPasswordDialog = ref(false);
const showProfileDialog = ref(false);
const showThemeDialog = ref(false);

// Photo upload state
const photoInput = ref(null);
const selectedImageFile = ref(null);
const photoProcessing = ref(false);
const showImageEditor = ref(false);

// Image editor state
const editorCanvas = ref(null);
const editorImage = ref(null);
const imageScale = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStartPos = ref({ x: 0, y: 0 });
const canvasSize = 300;

// Password form
const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

// Profile form
const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
    office_designation: props.user?.office_designation || '',
});

// Photo form for upload
const photoForm = useForm({
    photo: null,
});

// Theme state
const theme = ref(props.preferences?.theme || 'light');

// QR code state
const showQrModal = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('00:00');
const qrCountdownInterval = ref(null);
const copyButtonText = ref('📋 Copy');

// Success messages
const successMsg = reactive({
    password: false,
    profile: false,
    photo: false,
});

const drawCanvas = () => {
    if (!editorCanvas.value || !editorImage.value) return;

    const canvas = editorCanvas.value;
    const ctx = canvas.getContext('2d', { alpha: true });
    const radius = canvasSize / 2;
    const centerX = radius;
    const centerY = radius;

    // Enable anti-aliasing for smoother edges
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';

    // Clear canvas
    ctx.clearRect(0, 0, canvasSize, canvasSize);

    // Save context for clipping
    ctx.save();

    // Create circular clipping path
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.closePath();
    ctx.clip();

    // Draw image with current scale and position
    ctx.drawImage(
        editorImage.value,
        imagePosition.value.x,
        imagePosition.value.y,
        editorImage.value.width * imageScale.value,
        editorImage.value.height * imageScale.value
    );

    // Restore context and draw circle border
    ctx.restore();
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.strokeStyle = '#a78bfa';
    ctx.lineWidth = 2;
    ctx.stroke();
};

const handleMouseDown = (e) => {
    if (!showImageEditor.value) return;
    isDragging.value = true;
    dragStartPos.value = {
        x: e.clientX,
        y: e.clientY,
    };
};

const handleMouseMove = (e) => {
    if (!isDragging.value || !editorImage.value) return;

    const deltaX = e.clientX - dragStartPos.value.x;
    const deltaY = e.clientY - dragStartPos.value.y;

    imagePosition.value = {
        x: imagePosition.value.x + deltaX,
        y: imagePosition.value.y + deltaY,
    };

    dragStartPos.value = {
        x: e.clientX,
        y: e.clientY,
    };

    drawCanvas();
};

const handleMouseUp = () => {
    isDragging.value = false;
};

const handleWheel = (e) => {
    if (!showImageEditor.value) return;
    e.preventDefault();

    const zoomSpeed = 0.1;
    const direction = e.deltaY > 0 ? -1 : 1;
    const newScale = imageScale.value + direction * zoomSpeed;

    if (newScale > 0.2 && newScale < 5) {
        imageScale.value = newScale;
        drawCanvas();
    }
};

const handleZoomIn = () => {
    const newScale = imageScale.value + 0.1;
    if (newScale <= 5) {
        imageScale.value = newScale;
        drawCanvas();
    }
};

const handleZoomOut = () => {
    const newScale = imageScale.value - 0.1;
    if (newScale >= 0.2) {
        imageScale.value = newScale;
        drawCanvas();
    }
};

const resetImage = () => {
    if (!editorImage.value) return;

    const scaleX = canvasSize / editorImage.value.width;
    const scaleY = canvasSize / editorImage.value.height;
    imageScale.value = Math.min(scaleX, scaleY);

    imagePosition.value = {
        x: (canvasSize - editorImage.value.width * imageScale.value) / 2,
        y: (canvasSize - editorImage.value.height * imageScale.value) / 2,
    };

    drawCanvas();
};

const onPhotoSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            return;
        }

        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
            return;
        }

        selectedImageFile.value = file;

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
                y: (canvasSize - img.height * imageScale.value) / 2,
            };

            showImageEditor.value = true;

            // Use nextTick to ensure the canvas is rendered before drawing
            nextTick(() => {
                setTimeout(() => {
                    drawCanvas();
                }, 100);
            });
        };

        img.src = URL.createObjectURL(file);
    }
};

const cancelEditor = () => {
    showImageEditor.value = false;
    selectedImageFile.value = null;
    editorImage.value = null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const closePhotoDialog = () => {
    showPhotoDialog.value = false;
    // Clean up preview URL to prevent memory leaks
    if (editorImage.value) {
        URL.revokeObjectURL(editorImage.value.src);
        editorImage.value = null;
    }
    // Clean up image editor state
    showImageEditor.value = false;
    selectedImageFile.value = null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
    photoForm.reset();
    photoForm.clearErrors();
};

const submitPhotoForm = async () => {
    if (!selectedImageFile.value || !showImageEditor.value) {
        // First click - move to editor
        if (!showImageEditor.value && selectedImageFile.value) {
            return; // Editor should be shown already
        }
        return;
    }

    // Submit the cropped image
    successMsg.photo = false;
    photoProcessing.value = true;

    try {
        // Convert canvas to blob
        editorCanvas.value.toBlob(
            (blob) => {
                const formData = new FormData();
                formData.append('photo', blob, 'profile-photo.png');

                // Get CSRF token from meta tag or Inertia props
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || page.props.csrf_token;

                if (!csrfToken) {
                    alert('Security token missing. Please reload the page and try again.');
                    photoProcessing.value = false;
                    return;
                }

                fetch(route('user.settings.photo'), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                })
                    .then((response) => {
                        if (response.ok) {
                            successMsg.photo = true;
                            setTimeout(() => {
                                successMsg.photo = false;
                                cancelEditor();
                                showPhotoDialog.value = false;
                            }, 2000);
                        } else {
                            response.json().then(data => {
                                alert(data.message || 'Failed to upload photo. Please try again.');
                            }).catch(() => {
                                alert('Failed to upload photo. Please try again.');
                            });
                        }
                    })
                    .catch((error) => {
                        console.error('Photo upload error:', error);
                        alert('Failed to upload photo. Please try again.');
                    })
                    .finally(() => {
                        photoProcessing.value = false;
                    });
            },
            'image/png'
        );
    } catch (error) {
        console.error('Canvas conversion error:', error);
        alert('Failed to process image. Please try again.');
        photoProcessing.value = false;
    }
};

const submitPasswordForm = () => {
    successMsg.password = false;
    passwordForm.post(route('user.settings.password'), {
        onSuccess: () => {
            passwordForm.reset();
            successMsg.password = true;
            setTimeout(() => {
                successMsg.password = false;
                showPasswordDialog.value = false;
            }, 2000);
        },
    });
};

const submitProfileForm = () => {
    successMsg.profile = false;
    profileForm.post(route('user.settings.profile'), {
        onSuccess: () => {
            successMsg.profile = true;
            setTimeout(() => {
                successMsg.profile = false;
                showProfileDialog.value = false;
            }, 2000);
        },
    });
};

const clearError = (form, field) => {
    if (form === 'password') {
        passwordForm.clearErrors(field);
    } else if (form === 'profile') {
        profileForm.clearErrors(field);
    }
};

const showQrCode = async () => {
    try {
        const response = await axios.post(route('profile.generate-qr'));
        const { qr_code_svg, url, expires_at } = response.data;
        console.log('QR Code Response:', { qr_code_svg: '...', url, expires_at });
        qrCodeData.value = {
            qrCode: qr_code_svg,
            url,
            expiresAt: expires_at,
        };
        showQrModal.value = true;
        startCountdown();
    } catch (error) {
        console.error('Error generating QR code:', error);
        toast.error('Failed to generate QR code. Please try again.');
    }
};

const startCountdown = () => {
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
        qrCountdownInterval.value = null;
    }

    const updateCountdown = () => {
        if (!qrCodeData.value) return;

        try {
            const now = new Date().getTime();
            // Handle both ISO string and timestamp formats
            let expiresAt = qrCodeData.value.expiresAt;

            if (!expiresAt) {
                console.error('expiresAt is missing from QR code data');
                qrCountdown.value = 'Error';
                clearInterval(qrCountdownInterval.value);
                qrCountdownInterval.value = null;
                return;
            }

            let expiresTime;

            if (typeof expiresAt === 'number') {
                expiresTime = expiresAt;
            } else if (typeof expiresAt === 'string') {
                expiresTime = new Date(expiresAt).getTime();
            } else {
                console.error('Invalid expiresAt format:', expiresAt);
                qrCountdown.value = 'Error';
                clearInterval(qrCountdownInterval.value);
                qrCountdownInterval.value = null;
                return;
            }

            const distance = expiresTime - now;

            if (distance <= 0) {
                qrCountdown.value = 'Expired';
                clearInterval(qrCountdownInterval.value);
                qrCountdownInterval.value = null;
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            qrCountdown.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        } catch (error) {
            console.error('Countdown calculation error:', error);
            qrCountdown.value = 'Error';
            clearInterval(qrCountdownInterval.value);
            qrCountdownInterval.value = null;
        }
    };

    updateCountdown();

    qrCountdownInterval.value = setInterval(() => {
        updateCountdown();
    }, 1000);
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copyButtonText.value = '✓ Copied!';
        setTimeout(() => {
            copyButtonText.value = '📋 Copy';
        }, 2000);
    } catch (err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copyButtonText.value = '✓ Copied!';
        setTimeout(() => {
            copyButtonText.value = '📋 Copy';
        }, 2000);
    }
};

const getQrCountdownColor = () => {
    if (qrCountdown.value === 'Expired') return 'bg-red-100 text-red-700';

    const parts = qrCountdown.value?.split(':') || [];
    const minutes = parseInt(parts[0]) || 0;
    const seconds = parseInt(parts[1]) || 0;
    const totalSeconds = minutes * 60 + seconds;

    if (totalSeconds < 5) return 'bg-red-100 text-red-700';
    if (totalSeconds < 10) return 'bg-orange-100 text-orange-700';
    return 'bg-yellow-100 text-yellow-700';
};

// Watch for QR modal closing to clean up interval
watch(showQrModal, (newVal) => {
    if (!newVal && qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
        qrCountdownInterval.value = null;
    }
});

// Clean up interval on unmount
onUnmounted(() => {
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }
});
</script>
