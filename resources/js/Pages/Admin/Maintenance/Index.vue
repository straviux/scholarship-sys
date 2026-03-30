<template>
    <AdminLayout>
        <div class="maintenance-panel">
            <div class="container mx-auto p-4 short:p-3">
                <!-- Header -->
                <div class="mb-6 short:mb-3">
                    <h1 class="text-4xl short:text-2xl font-bold text-gray-900">Maintenance Management</h1>
                    <p class="text-gray-600 mt-2">Manage system maintenance schedules and alerts</p>
                </div>

                <!-- Status Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 short:gap-2 mb-6 short:mb-3">
                    <!-- Current Status Card -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div :class="[
                            'p-4 short:p-3 border-t-4',
                            isActive
                                ? 'bg-red-50 border-red-500'
                                : 'bg-green-50 border-green-500'
                        ]">
                            <p class="text-sm text-gray-600 font-semibold uppercase">Current Status</p>
                            <p :class="[
                                'text-3xl short:text-xl font-bold mt-2',
                                isActive ? 'text-red-600' : 'text-green-600'
                            ]">
                                {{ isActive ? '[MAINT] MAINTENANCE' : '[OPER] OPERATIONAL' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-3">Last updated: {{ lastUpdated }}</p>
                        </div>
                    </div>

                    <!-- Countdown Card -->
                    <div v-if="countdown && countdown.status === 'upcoming'"
                        class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 short:p-3 bg-blue-50 border-t-4 border-blue-500">
                            <p class="text-sm text-gray-600 font-semibold uppercase">Starting In</p>
                            <p class="text-3xl short:text-xl font-bold mt-2 text-blue-600 font-mono">{{ countdownDisplay
                                }}</p>
                            <p class="text-xs text-gray-500 mt-3">{{ formatTime(countdown.start_time) }}</p>
                        </div>
                    </div>

                    <!-- Remaining Time Card -->
                    <div v-if="countdown && countdown.status === 'active'"
                        class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 short:p-3 bg-orange-50 border-t-4 border-orange-500">
                            <p class="text-sm text-gray-600 font-semibold uppercase">Duration</p>
                            <p class="text-3xl short:text-xl font-bold mt-2 text-orange-600">{{
                                countdown.duration_minutes || '?' }}
                                min</p>
                            <p class="text-xs text-gray-500 mt-3 animate-pulse"> MAINTENANCE IN PROGRESS</p>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 short:gap-2">
                    <!-- Left Column - Configuration Form -->
                    <div class="lg:col-span-2">
                        <!-- Control Panel -->
                        <div class="bg-white rounded-lg shadow-md p-4 short:p-3 mb-4 short:mb-2">
                            <div class="flex items-center justify-between mb-4 short:mb-2">
                                <h2 class="text-2xl short:text-xl font-bold text-gray-900">Configuration</h2>
                                <div v-if="isActive" class="flex items-center gap-2 px-3 py-1 bg-red-100 rounded-full">
                                    <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span>
                                    <span class="text-sm font-semibold text-red-600">ACTIVE</span>
                                </div>
                            </div>

                            <form @submit.prevent="showConfirmDialog" class="space-y-5">
                                <!-- Quick Toggle -->
                                <div
                                    class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border-2 border-dashed border-blue-200">
                                    <div>
                                        <label class="flex items-center cursor-pointer gap-3">
                                            <Checkbox v-model="form.is_active" :binary="true" />
                                            <span class="font-semibold text-gray-900">Enable Maintenance Mode</span>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1 ml-8">Users will see a banner with your
                                            message</p>
                                    </div>
                                    <Button v-if="isActive" type="button" @click="showDeactivateDialog"
                                        label="Deactivate Now" severity="success" size="small" />
                                </div>

                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Banner Title</label>
                                    <InputText v-model="form.title" type="text" class="w-full"
                                        placeholder="e.g., Scheduled System Maintenance" maxlength="100" />
                                    <p class="text-xs text-gray-500 mt-1">{{ form.title.length }}/100 characters</p>
                                </div>

                                <!-- Message -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Message</label>
                                    <Textarea v-model="form.message" class="w-full" rows="4"
                                        placeholder="Inform users about what's happening..." maxlength="500" />
                                    <p class="text-xs text-gray-500 mt-1">{{ form.message.length }}/500 characters</p>
                                </div>

                                <!-- Schedule -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 mb-2">Start Time</label>
                                        <InputText v-model="form.start_time" type="datetime-local" class="w-full"
                                            :min="minStartTime" required />
                                        <p class="text-xs text-gray-500 mt-1">Minimum 10 minutes from now</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 mb-2">End Time</label>
                                        <InputText v-model="form.end_time" type="datetime-local" class="w-full"
                                            :min="form.start_time" required />
                                        <p class="text-xs text-gray-500 mt-1">Must be after start time</p>
                                    </div>
                                </div>

                                <!-- Alert Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-3">Alert Severity</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label class="relative flex cursor-pointer">
                                            <input type="radio" v-model="form.type" value="info" class="sr-only" />
                                            <div class="w-full px-4 py-3 border-2 rounded-lg text-center transition"
                                                :class="[
                                                    form.type === 'info'
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-300 hover:border-blue-300'
                                                ]">
                                                <span class="text-xl">⚙</span>
                                                <p class="font-semibold text-sm mt-1">Info</p>
                                            </div>
                                        </label>
                                        <label class="relative flex cursor-pointer">
                                            <input type="radio" v-model="form.type" value="warning" class="sr-only" />
                                            <div class="w-full px-4 py-3 border-2 rounded-lg text-center transition"
                                                :class="[
                                                    form.type === 'warning'
                                                        ? 'border-yellow-500 bg-yellow-50'
                                                        : 'border-gray-300 hover:border-yellow-300'
                                                ]">
                                                <span class="text-xl">⚙</span>
                                                <p class="font-semibold text-sm mt-1">Warning</p>
                                            </div>
                                        </label>
                                        <label class="relative flex cursor-pointer">
                                            <input type="radio" v-model="form.type" value="critical" class="sr-only" />
                                            <div class="w-full px-4 py-3 border-2 rounded-lg text-center transition"
                                                :class="[
                                                    form.type === 'critical'
                                                        ? 'border-red-500 bg-red-50'
                                                        : 'border-gray-300 hover:border-red-300'
                                                ]">
                                                <span class="text-xl">⚙</span>
                                                <p class="font-semibold text-sm mt-1">Critical</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Admin Access Notice -->
                                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg flex gap-3">
                                    <span class="text-xl">⚙</span>
                                    <div>
                                        <p class="font-semibold text-sm text-blue-900">Admin Access Protected</p>
                                        <p class="text-xs text-blue-700 mt-1">Admins can always access the system even
                                            during maintenance</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-3 pt-4 border-t">
                                    <Button type="submit" label="Save Configuration" class="flex-1" />
                                    <Button type="button" @click="resetForm" label="Reset" severity="secondary" />
                                </div>
                            </form>
                        </div>

                        <!-- Preview Banner -->
                        <div class="bg-white rounded-lg shadow-md p-4 short:p-3">
                            <h2 class="text-2xl short:text-xl font-bold text-gray-900 mb-5">Preview</h2>
                            <div v-if="form.is_active" class="rounded-lg overflow-hidden" :class="[
                                form.type === 'info' ? 'bg-blue-50 border-l-4 border-blue-500' : '',
                                form.type === 'warning' ? 'bg-yellow-50 border-l-4 border-yellow-500' : '',
                                form.type === 'critical' ? 'bg-red-50 border-l-4 border-red-500' : '',
                            ]">
                                <div class="p-5">
                                    <h3 class="font-bold text-lg" :class="[
                                        form.type === 'info' ? 'text-blue-600' : '',
                                        form.type === 'warning' ? 'text-yellow-600' : '',
                                        form.type === 'critical' ? 'text-red-600' : '',
                                    ]">
                                        {{ form.title || 'Your title here' }}
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-700">{{ form.message || `Your message will appear
                                        here` }}</p>
                                </div>
                            </div>
                            <div v-else
                                class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <p class="text-gray-500 font-semibold">Maintenance mode is disabled</p>
                                <p class="text-xs text-gray-400 mt-1">Enable it to preview the banner</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Maintenance History -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-md p-4 short:p-3 sticky top-6">
                            <h2 class="text-2xl short:text-xl font-bold text-gray-900 mb-4">History</h2>

                            <div v-if="history.length > 0" class="space-y-3 max-h-screen overflow-y-auto">
                                <div v-for="record in history" :key="record.id"
                                    class="p-3 bg-gray-50 rounded-lg border-l-4 border-gray-300 hover:border-blue-400 transition">
                                    <div class="flex items-start gap-2">
                                        <span class="text-lg mt-0.5">
                                            {{ record.type === 'info' ? '[INFO]' : record.type === 'warning' ? '[WARN]'
                                                : '[ERROR]'
                                            }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-sm text-gray-900 truncate">{{ record.title }}
                                            </p>
                                            <p class="text-xs text-gray-600 mt-0.5">{{ record.message.substring(0, 40)
                                                }}...</p>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold inline-block"
                                                    :class="{
                                                        'bg-blue-100 text-blue-800': record.type === 'info',
                                                        'bg-yellow-100 text-yellow-800': record.type === 'warning',
                                                        'bg-red-100 text-red-800': record.type === 'critical',
                                                    }">
                                                    {{ record.type.toUpperCase() }}
                                                </span>
                                                <span v-if="record.is_active"
                                                    class="inline-flex items-center gap-0.5 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                                    <span class="w-1 h-1 bg-red-600 rounded-full animate-pulse"></span>
                                                    ACTIVE
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400 mt-2">{{
                                                formatDateTime(record.start_time) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <p class="text-sm">No maintenance records yet</p>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <!-- Confirmation Dialog -->
        <Teleport to="body">
            <div v-if="showDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-in">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full mb-4" :class="[
                            dialogType === 'deactivate' ? 'bg-green-100' : 'bg-blue-100'
                        ]">
                            <span class="text-2xl">
                                {{ dialogType === 'deactivate' ? '[DEACTIVATE]' : '[ERROR]' }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 text-center">{{ dialogTitle }}</h3>
                        <p class="text-gray-600 text-sm mt-2 text-center">{{ dialogMessage }}</p>
                    </div>
                    <div class="flex gap-3 p-6 bg-gray-50 border-t">
                        <Button @click="closeDialog" label="Cancel" severity="secondary" class="flex-1" />
                        <Button @click="confirmDialog" :class="[
                            'flex-1',
                        ]" :severity="dialogType === 'deactivate' ? 'success' : 'primary'" :label="dialogAction" />
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notifications -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 space-y-3 z-50 pointer-events-none">
                <div v-for="toast in toasts" :key="toast.id"
                    class="pointer-events-auto animate-in slide-in-from-right px-6 py-4 bg-white rounded-lg shadow-lg border-l-4 flex items-start gap-3"
                    :class="[
                        toast.type === 'success' ? 'border-green-500 bg-green-50' : '',
                        toast.type === 'error' ? 'border-red-500 bg-red-50' : '',
                        toast.type === 'info' ? 'border-blue-500 bg-blue-50' : '',
                    ]">
                    <span class="text-lg mt-0.5">
                        {{ toast.type === 'success' ? '[SUCCESS]' : toast.type === 'error' ? '[ERROR]' : '[ERROR]' }}
                    </span>
                    <div>
                        <p class="font-semibold text-sm" :class="[
                            toast.type === 'success' ? 'text-green-800' : '',
                            toast.type === 'error' ? 'text-red-800' : '',
                            toast.type === 'info' ? 'text-blue-800' : '',
                        ]">{{ toast.title }}</p>
                        <p class="text-xs mt-1" :class="[
                            toast.type === 'success' ? 'text-green-700' : '',
                            toast.type === 'error' ? 'text-red-700' : '',
                            toast.type === 'info' ? 'text-blue-700' : '',
                        ]">{{ toast.message }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const $page = usePage();

// Form Data
const form = ref({
    is_active: false,
    title: 'System Maintenance',
    message: 'We are performing scheduled maintenance. Please try again later.',
    start_time: '',
    end_time: '',
    type: 'warning',
    allow_admin_access: true,
});

// State
const isActive = ref(false);
const countdown = ref(null);
const countdownDisplay = ref('');
const history = ref([]);
const countdownInterval = ref(null);
const lastUpdated = ref('Just now');
const minStartTime = ref('');

// Dialog State
const showDialog = ref(false);
const dialogType = ref('save');
const dialogTitle = ref('');
const dialogMessage = ref('');
const dialogAction = ref('');
const pendingAction = ref(null);

// Toast State
const toasts = ref([]);
let toastId = 0;

// Get CSRF Token
const getCsrfToken = () => {
    if ($page.props.csrf_token) {
        return $page.props.csrf_token;
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
};

// Toast Functions
const showToast = (type, title, message, duration = 4000) => {
    const id = toastId++;
    const toast = { id, type, title, message };
    toasts.value.push(toast);
    console.warn(`[DEBUG] Toast[${id}] (${type}):`, title, message);

    if (duration > 0) {
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, duration);
    }

    return id;
};



// Fetch Status
const fetchStatus = async () => {
    try {
        const response = await fetch('/api/maintenance/status', {
            method: 'GET',
            credentials: 'include',  // Include cookies for auth
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        isActive.value = data.is_under_maintenance;
        if (data.announcement) {
            countdown.value = data.announcement.countdown;
        }
    } catch (error) {
        console.error('Error fetching maintenance status:', error);
    }
};

// Fetch History
const fetchHistory = async () => {
    try {
        const response = await fetch('/api/admin/maintenance/list', {
            method: 'GET',
            credentials: 'include',  // Include cookies for auth
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        history.value = data.data;

        // Load the first active record into the form
        const active = data.data.find((r) => r.is_active);
        if (active) {
            form.value = {
                is_active: active.is_active,
                title: active.title,
                message: active.message,
                start_time: active.start_time.slice(0, 16),
                end_time: active.end_time.slice(0, 16),
                type: active.type,
                allow_admin_access: active.allow_admin_access,
            };
        } else {
            // Set defaults only if no active record
            setDefaultTimes();
        }
    } catch (error) {
        console.error('Error fetching maintenance history:', error);
        // Set defaults on error
        setDefaultTimes();
    }
};

// Set Default Times Helper
const setDefaultTimes = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 10);
    const startInput = formatDatetimeLocal(now);

    const end = new Date(now);
    end.setMinutes(end.getMinutes() + 30);
    const endInput = formatDatetimeLocal(end);

    form.value.start_time = startInput;
    form.value.end_time = endInput;
    minStartTime.value = startInput;
};
const showConfirmDialog = () => {
    console.warn(' showConfirmDialog() called');

    if (!form.value.start_time || !form.value.end_time) {
        console.warn(' Missing times');
        showToast('error', 'Validation Error', 'Please fill in start and end times');
        return;
    }

    const startTime = new Date(form.value.start_time);
    const endTime = new Date(form.value.end_time);

    if (endTime <= startTime) {
        console.warn(' End time not after start time');
        showToast('error', 'Validation Error', 'End time must be after start time');
        return;
    }

    console.warn(' Validation passed - showing dialog');
    dialogType.value = 'save';
    dialogTitle.value = 'Save Configuration?';
    dialogMessage.value = form.value.is_active
        ? 'You are about to enable maintenance mode. Users will see this banner.'
        : 'You are about to save these settings.';
    dialogAction.value = 'Save';
    pendingAction.value = 'save';
    showDialog.value = true;
    console.warn(' Dialog shown with pendingAction=save');
};

const showDeactivateDialog = () => {
    dialogType.value = 'deactivate';
    dialogTitle.value = 'Deactivate Maintenance?';
    dialogMessage.value = 'All users will immediately see the system as operational again.';
    dialogAction.value = 'Deactivate';
    pendingAction.value = 'deactivate';
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
    pendingAction.value = null;
};

const confirmDialog = async () => {
    console.warn(' confirmDialog() called - pendingAction:', pendingAction.value);

    // Save the action BEFORE closing the dialog
    const action = pendingAction.value;
    closeDialog();

    if (action === 'save') {
        console.warn(' Executing SAVE action');
        await saveMaintenance();
    } else if (action === 'deactivate') {
        console.warn(' Executing DEACTIVATE action');
        await deactivateMaintenance();
    } else {
        console.error(' Unknown action:', action);
    }
};

// Save Maintenance
const saveMaintenance = async () => {
    console.warn(' saveMaintenance() called - START');
    try {
        const csrfToken = getCsrfToken();
        console.warn(' CSRF Token obtained:', csrfToken ? 'exists' : 'MISSING');

        // Convert datetime-local format to Laravel datetime format (YYYY-MM-DD HH:mm:ss)
        const startStr = form.value.start_time ? form.value.start_time.replace('T', ' ') + ':00' : '';
        const endStr = form.value.end_time ? form.value.end_time.replace('T', ' ') + ':00' : '';

        const payload = {
            is_active: form.value.is_active,
            title: form.value.title,
            message: form.value.message,
            start_time: startStr,
            end_time: endStr,
            type: form.value.type,
            allow_admin_access: form.value.allow_admin_access,
        };

        console.warn(' Sending payload:', JSON.stringify(payload, null, 2));

        const response = await fetch('/api/admin/maintenance', {
            method: 'POST',
            credentials: 'include',  // Include cookies for auth
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        console.warn(' Response received - Status:', response.status, 'OK:', response.ok);

        let responseData;
        const contentType = response.headers.get('content-type');
        console.warn('Response Content-Type:', contentType);

        if (contentType && contentType.includes('application/json')) {
            responseData = await response.json();
            console.warn('Response JSON:', JSON.stringify(responseData, null, 2));
        } else {
            const text = await response.text();
            console.warn('Response Text:', text);
            responseData = {};
        }

        if (response.ok) {
            console.warn(' Save SUCCESS');
            showToast('success', 'Saved!', 'Maintenance configuration has been saved successfully');
            fetchStatus();
            fetchHistory();
        } else {
            const errorMessage = responseData.message
                || (responseData.errors ? JSON.stringify(responseData.errors) : null)
                || `HTTP ${response.status}: Failed to save configuration`;
            console.error(' Save FAILED:', errorMessage);
            console.error('Full response:', responseData);
            showToast('error', 'Save Failed', errorMessage);
        }
    } catch (error) {
        console.error(' EXCEPTION in saveMaintenance:', error.message);
        console.error('Stack:', error.stack);
        showToast('error', 'Error', error.message || 'An error occurred while saving');
    }
    console.warn(' saveMaintenance() - END\n');
};

// Deactivate Maintenance
const deactivateMaintenance = async () => {
    try {
        const csrfToken = getCsrfToken();
        const response = await fetch('/api/admin/maintenance/deactivate', {
            method: 'POST',
            credentials: 'include',  // Include cookies for auth
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (response.ok) {
            form.value.is_active = false;
            isActive.value = false;
            showToast('success', 'Deactivated!', 'Maintenance mode is now off');
            fetchStatus();
            fetchHistory();
        } else {
            showToast('error', 'Deactivation Failed', 'Could not deactivate maintenance mode');
        }
    } catch (error) {
        console.error('Error deactivating maintenance:', error);
        showToast('error', 'Error', 'An error occurred while deactivating');
    }
};

// Reset Form
const resetForm = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 10);
    const startInput = formatDatetimeLocal(now);

    const end = new Date(now);
    end.setMinutes(end.getMinutes() + 30);
    const endInput = formatDatetimeLocal(end);

    form.value = {
        is_active: false,
        title: 'System Maintenance',
        message: 'We are performing scheduled maintenance. Please try again later.',
        start_time: startInput,
        end_time: endInput,
        type: 'warning',
        allow_admin_access: true,
    };
    showToast('info', 'Reset', 'Form has been cleared');
};

// Format DateTime
const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    return new Date(dateTime).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Format Time
const formatTime = (dateTime) => {
    if (!dateTime) return '';
    return new Date(dateTime).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Update Countdown Display
const updateCountdownDisplay = () => {
    if (!countdown.value || countdown.value.status !== 'upcoming' || !countdown.value.start_time) {
        countdownDisplay.value = '';
        return;
    }

    // Calculate remaining seconds from NOW until start_time
    const startTime = new Date(countdown.value.start_time);
    const now = new Date();
    const secondsRemaining = Math.max(0, Math.floor((startTime - now) / 1000));

    const hours = Math.floor(secondsRemaining / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    const seconds = secondsRemaining % 60;

    countdownDisplay.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

// Helper to format date for datetime-local input (local time, not UTC)
const formatDatetimeLocal = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Lifecycle
onMounted(() => {
    console.warn(' Maintenance page mounted - fetching initial data');
    fetchStatus();
    fetchHistory();

    // Update countdown display every second (uses latest data from parent)
    countdownInterval.value = setInterval(() => {
        updateCountdownDisplay();
    }, 1000);
});

onBeforeUnmount(() => {
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
    }
});
</script>

<style scoped>
.maintenance-panel {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

@keyframes slideInFromRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }

    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-in {
    animation: slideInFromRight 0.3s ease-out;
}

.slide-in-from-right {
    animation: slideInFromRight 0.3s ease-out;
}

/* Scrollbar styling for activity logs */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
