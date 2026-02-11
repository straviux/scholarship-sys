<template>
    <div class="maintenance-panel">
        <div class="container mx-auto p-6">
            <h1 class="text-3xl font-bold mb-6">Maintenance Management</h1>

            <!-- Current Status -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Current Status</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded border-l-4" :class="[
                        isActive
                            ? 'bg-red-50 border-red-500'
                            : 'bg-green-50 border-green-500'
                    ]">
                        <p class="text-sm text-gray-600">Status</p>
                        <p class="text-2xl font-bold" :class="[
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
                            <input type="checkbox" v-model="form.is_active" class="w-5 h-5 rounded" />
                            <span class="ml-3 font-semibold">Enable Maintenance Mode</span>
                        </label>
                        <button v-if="isActive" type="button" @click="deactivateMaintenance"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Deactivate Now
                        </button>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Title</label>
                        <input v-model="form.title" type="text"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="System Maintenance" />
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Message</label>
                        <textarea v-model="form.message"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="4" placeholder="We are performing scheduled maintenance..."></textarea>
                    </div>

                    <!-- Start Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Start Time</label>
                            <input v-model="form.start_time" type="datetime-local"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-sm font-medium mb-2">End Time</label>
                            <input v-model="form.end_time" type="datetime-local"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Alert Type</label>
                        <select v-model="form.type"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <!-- Admin Access -->
                    <div class="flex items-center p-4 bg-blue-50 rounded">
                        <input type="checkbox" v-model="form.allow_admin_access" class="w-5 h-5 rounded" disabled />
                        <label class="ml-3 font-semibold text-blue-900">
                            Allow admin access during maintenance (Always enabled)
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4 pt-4">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Save Changes
                        </button>
                        <button type="button" @click="resetForm"
                            class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Banner -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Preview</h2>
                <maintenance-banner v-if="form.is_active" :announcement="{
                    title: form.title,
                    message: form.message,
                    type: form.type,
                }" />
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
                                    <span class="px-2 py-1 rounded text-xs font-semibold" :class="{
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
</template>

<script>
import MaintenanceBanner from '@/components/MaintenanceBanner.vue';

export default {
    name: 'MaintenancePanel',
    components: {
        MaintenanceBanner,
    },
    data() {
        return {
            form: {
                is_active: false,
                title: 'System Maintenance',
                message: 'We are performing scheduled maintenance. Please try again later.',
                start_time: '',
                end_time: '',
                type: 'warning',
                allow_admin_access: true,
            },
            isActive: false,
            countdown: null,
            countdownDisplay: '',
            history: [],
            countdownInterval: null,
        };
    },
    methods: {
        async fetchStatus() {
            try {
                const response = await fetch('/api/admin/maintenance/status');
                const data = await response.json();

                this.isActive = data.is_under_maintenance;
                if (data.announcement) {
                    this.countdown = data.announcement.countdown;
                }
            } catch (error) {
                console.error('Error fetching maintenance status:', error);
            }
        },

        async fetchHistory() {
            try {
                const response = await fetch('/api/admin/maintenance/list');
                const data = await response.json();
                this.history = data.data;

                // Load the first active record into the form
                const active = data.data.find((r) => r.is_active);
                if (active) {
                    this.form = {
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
        },

        async saveMaintenance() {
            try {
                const response = await fetch('/api/admin/maintenance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                if (response.ok) {
                    const data = await response.json();
                    alert('Maintenance announcement saved successfully!');
                    this.fetchStatus();
                    this.fetchHistory();
                } else {
                    alert('Error saving maintenance announcement');
                }
            } catch (error) {
                console.error('Error saving maintenance:', error);
                alert('Error saving maintenance announcement');
            }
        },

        async deactivateMaintenance() {
            if (!confirm('Are you sure you want to deactivate maintenance mode?')) {
                return;
            }

            try {
                const response = await fetch('/api/admin/maintenance/deactivate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (response.ok) {
                    alert('Maintenance mode deactivated');
                    this.form.is_active = false;
                    this.isActive = false;
                    this.fetchStatus();
                    this.fetchHistory();
                }
            } catch (error) {
                console.error('Error deactivating maintenance:', error);
            }
        },

        resetForm() {
            this.form = {
                is_active: false,
                title: 'System Maintenance',
                message: 'We are performing scheduled maintenance. Please try again later.',
                start_time: '',
                end_time: '',
                type: 'warning',
                allow_admin_access: true,
            };
        },

        formatDateTime(dateTime) {
            if (!dateTime) return '-';
            return new Date(dateTime).toLocaleString();
        },

        updateCountdownDisplay() {
            if (!this.countdown || this.countdown.status !== 'upcoming') {
                this.countdownDisplay = '';
                return;
            }

            const secondsRemaining = this.countdown.seconds_remaining;
            const hours = Math.floor(secondsRemaining / 3600);
            const minutes = Math.floor((secondsRemaining % 3600) / 60);
            const seconds = secondsRemaining % 60;

            this.countdownDisplay = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },
    },
    mounted() {
        this.fetchStatus();
        this.fetchHistory();

        // Update countdown every second
        this.countdownInterval = setInterval(() => {
            this.fetchStatus();
            this.updateCountdownDisplay();
        }, 1000);
    },
    beforeUnmount() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    },
};
</script>

<style scoped>
.maintenance-panel {
    background-color: #f9fafb;
    min-height: 100vh;
    padding: 2rem 0;
}
</style>
