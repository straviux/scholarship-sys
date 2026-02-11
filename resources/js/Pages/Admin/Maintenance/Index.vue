<template>
    <AdminLayout>
        <div class="maintenance-panel">
            <div class="container mx-auto p-6">
                <h1 class="text-3xl font-bold mb-6">Maintenance Management</h1>

                <!-- Current Status -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Current Status</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded border-l-4"
                        :class="[
                            isActive
                                ? 'bg-red-50 border-red-500'
                                : 'bg-green-50 border-green-500'
                        ]">
                        <p class="text-sm text-gray-600">Status</p>
                        <p class="text-2xl font-bold"
                            :class="[
                                isActive ? 'text-red-600' : 'text-green-600'
                            ]">
                            {{ isActive ? 'UNDER MAINTENANCE' : 'OPERATIONAL' }}
                        </p>
                    </div>

                    <div v-if="countdown" class="p-4 rounded bg-blue-50 border-l-4 border-blue-500">
                        <p class="text-sm text-gray-600">{{ countdown.message }}</p>
                        <p class="text-2xl font-bold text-blue-600">{{ countdownDisplay }}</p>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Control Panel</h2>

                <form @submit.prevent="saveMaintenance" class="space-y-4">
                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded">
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="w-5 h-5 rounded"
                            />
                            <span class="ml-3 font-semibold">Enable Maintenance Mode</span>
                        </label>
                        <button
                            v-if="isActive"
                            type="button"
                            @click="deactivateMaintenance"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Deactivate Now
                        </button>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="System Maintenance"
                        />
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Message</label>
                        <textarea
                            v-model="form.message"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="4"
                            placeholder="We are performing scheduled maintenance..."></textarea>
                    </div>

                    <!-- Start Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Start Time</label>
                            <input
                                v-model="form.start_time"
                                type="datetime-local"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-sm font-medium mb-2">End Time</label>
                            <input
                                v-model="form.end_time"
                                type="datetime-local"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Alert Type</label>
                        <select
                            v-model="form.type"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <!-- Admin Access -->
                    <div class="flex items-center p-4 bg-blue-50 rounded">
                        <input
                            type="checkbox"
                            v-model="form.allow_admin_access"
                            class="w-5 h-5 rounded"
                            disabled
                        />
                        <label class="ml-3 font-semibold text-blue-900">
                            Allow admin access during maintenance (Always enabled)
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Save Changes
                        </button>
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Banner -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Preview</h2>
                <div v-if="form.is_active" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <h3 class="font-bold text-yellow-600">{{ form.title }}</h3>
                    <p class="mt-2 text-sm">{{ form.message }}</p>
                </div>
                <div v-else class="text-gray-500 text-center py-8">
                    Maintenance mode is disabled. Banner preview will appear when enabled.
                </div>
            </div>

            <!-- History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">History</h2>
                <div v-if="history.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Title</th>
                                <th class="text-left py-2">Start Time</th>
                                <th class="text-left py-2">End Time</th>
                                <th class="text-left py-2">Type</th>
                                <th class="text-left py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in history" :key="record.id" class="border-b hover:bg-gray-50">
                                <td class="py-2">{{ record.title }}</td>
                                <td class="py-2">{{ formatDateTime(record.start_time) }}</td>
                                <td class="py-2">{{ formatDateTime(record.end_time) }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded text-xs font-semibold"
                                        :class="{
                                            'bg-blue-100 text-blue-800': record.type === 'info',
                                            'bg-yellow-100 text-yellow-800': record.type === 'warning',
                                            'bg-red-100 text-red-800': record.type === 'critical',
                                        }">
                                        {{ record.type.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <span v-if="record.is_active" class="text-red-600 font-semibold">ACTIVE</span>
                                    <span v-else class="text-gray-600">Inactive</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-gray-500 text-center py-8">
                    No maintenance records yet.
                </div>
            </div>
        </div>
    </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const $page = usePage();

const form = ref({
    is_active: false,
    title: 'System Maintenance',
    message: 'We are performing scheduled maintenance. Please try again later.',
    start_time: '',
    end_time: '',
    type: 'warning',
    allow_admin_access: true,
});
const isActive = ref(false);
const countdown = ref(null);
const countdownDisplay = ref('');
const history = ref([]);
const countdownInterval = ref(null);

const getCsrfToken = () => {
    // Try to get from page props (Inertia way)
    if ($page.props.csrf_token) {
        return $page.props.csrf_token;
    }
    // Fallback to meta tag
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
};

const fetchStatus = async () => {
    try {
        const response = await fetch('/api/admin/maintenance/status');
        if (!response.ok) {
            const text = await response.text();
            console.error('Status response:', response.status, text);
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

const fetchHistory = async () => {
    try {
        const response = await fetch('/api/admin/maintenance/list');
        if (!response.ok) {
            const text = await response.text();
            console.error('List response:', response.status, text);
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
                start_time: active.start_time.replace('T', ' ').slice(0, 16),
                end_time: active.end_time.replace('T', ' ').slice(0, 16),
                type: active.type,
                allow_admin_access: active.allow_admin_access,
            };
        }
    } catch (error) {
        console.error('Error fetching maintenance history:', error);
    }
};

const saveMaintenance = async () => {
    try {
        const csrfToken = getCsrfToken();
        const response = await fetch('/api/admin/maintenance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(form.value),
        });

        if (response.ok) {
            const data = await response.json();
            alert('Maintenance announcement saved successfully!');
            fetchStatus();
            fetchHistory();
        } else {
            alert('Error saving maintenance announcement');
        }
    } catch (error) {
        console.error('Error saving maintenance:', error);
        alert('Error saving maintenance announcement');
    }
};

const deactivateMaintenance = async () => {
    if (!confirm('Are you sure you want to deactivate maintenance mode?')) {
        return;
    }

    try {
        const csrfToken = getCsrfToken();
        const response = await fetch('/api/admin/maintenance/deactivate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (response.ok) {
            alert('Maintenance mode deactivated');
            form.value.is_active = false;
            isActive.value = false;
            fetchStatus();
            fetchHistory();
        }
    } catch (error) {
        console.error('Error deactivating maintenance:', error);
    }
};

const resetForm = () => {
    form.value = {
        is_active: false,
        title: 'System Maintenance',
        message: 'We are performing scheduled maintenance. Please try again later.',
        start_time: '',
        end_time: '',
        type: 'warning',
        allow_admin_access: true,
    };
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    return new Date(dateTime).toLocaleString();
};

const updateCountdownDisplay = () => {
    if (!countdown.value || countdown.value.status !== 'upcoming') {
        countdownDisplay.value = '';
        return;
    }

    const secondsRemaining = countdown.value.seconds_remaining;
    const hours = Math.floor(secondsRemaining / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    const seconds = secondsRemaining % 60;

    countdownDisplay.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

onMounted(() => {
    fetchStatus();
    fetchHistory();

    // Update countdown every second
    countdownInterval.value = setInterval(() => {
        fetchStatus();
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
    background-color: #f9fafb;
    min-height: 100vh;
    padding: 2rem 0;
}
</style>
