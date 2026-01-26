<template>

    <Head title="Your Activity Logs" />

    <AdminLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Your Activity Logs</h1>
                        <p class="text-sm text-gray-600 mt-1">Complete history of your actions in the system</p>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-refresh" text rounded @click="fetchActivities" v-tooltip.left="'Refresh'" />
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search by Name</label>
                        <InputText v-model="searchName" type="text" placeholder="Enter applicant name..." class="w-full"
                            @input="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Activity Type</label>
                        <Select v-model="selectedActivityType" :options="activityTypeOptions" optionLabel="label"
                            optionValue="value" placeholder="All activities" showClear fluid @change="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <DatePicker v-model="dateRange" selectionMode="range" showIcon dateFormat="yy-mm-dd"
                            @change="applyFilters" />
                    </div>
                    <div class="flex items-end">
                        <Button icon="pi pi-times" rounded severity="secondary" @click="resetFilters"
                            label="Clear Filters" v-tooltip.top="'Reset all filters'" />
                    </div>
                </div>
            </div>

            <!-- Activities Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <DataTable :value="filteredActivities" stripedRows showGridlines paginator :rows="20"
                    :loading="isLoading" responsiveLayout="scroll" :rowsPerPageOptions="[10, 20, 50]">

                    <!-- Activity Type Column -->
                    <Column field="activity_type" header="Activity Type" class="w-40">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div
                                    :class="['w-8 h-8 rounded-full flex items-center justify-center text-white text-xs', getActivityColorClass(data.activity_type)]">
                                    <i :class="getActivityIcon(data.activity_type)"></i>
                                </div>
                                <span class="text-sm font-medium">{{ data.activity_type }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Details Column -->
                    <Column field="remarks" header="Details" class="w-64">
                        <template #body="{ data }">
                            <div class="text-sm">
                                <p class="text-gray-900 font-medium">{{ data.remarks || data.action || 'N/A' }}</p>
                                <p v-if="data.profile_name || extractNameFromActivity(data)"
                                    class="text-blue-600 font-semibold text-xs mt-1">
                                    <i class="pi pi-user text-xs mr-1"></i>{{ data.profile_name ||
                                        extractNameFromActivity(data) }}
                                </p>
                                <p class="text-gray-600 text-xs mt-1">{{ data.description }}</p>
                            </div>
                        </template>
                    </Column>

                    <!-- Changes Column -->
                    <Column field="old_value" header="Status Changes" class="w-48">
                        <template #body="{ data }">
                            <div v-if="data.old_value || data.new_value" class="text-xs space-y-1">
                                <div v-if="data.old_value" class="text-gray-600">
                                    <span class="font-medium">From:</span> {{ data.old_value }}
                                </div>
                                <div v-if="data.new_value" class="text-gray-900">
                                    <span class="font-medium">To:</span> {{ data.new_value }}
                                </div>
                            </div>
                            <span v-else class="text-gray-400 text-xs">No changes</span>
                        </template>
                    </Column>

                    <!-- Timestamp Column -->
                    <Column field="performed_at" header="Timestamp" class="w-40" sortable>
                        <template #body="{ data }">
                            <div class="text-sm">
                                <p class="text-gray-900 font-medium">{{ formatDate(data.performed_at) }}</p>
                                <p class="text-gray-600 text-xs">{{ formatTime(data.performed_at) }}</p>
                            </div>
                        </template>
                    </Column>

                    <!-- Actions Column -->
                    <Column field="id" header="Actions" class="w-32" :exportable="false">
                        <template #body="{ data }">
                            <Button v-if="data.profile_id" icon="pi pi-external-link" text rounded
                                @click="viewProfile(data.profile_id)" v-tooltip.left="'View Related Profile'" />
                        </template>
                    </Column>

                    <!-- Empty State -->
                    <template #empty>
                        <div class="py-12 text-center">
                            <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-900">No activities found</p>
                            <p class="text-sm text-gray-600 mt-1">Try adjusting your filters</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import logger from '@/utils/logger';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';

const allActivities = ref([]);
const filteredActivities = ref([]);
const isLoading = ref(false);
const selectedActivityType = ref(null);
const dateRange = ref(null);
const searchName = ref('');

const activityTypeOptions = [
    { label: 'Profile Updated', value: 'Profile Updated' },
    { label: 'Attachment Uploaded', value: 'Attachment Uploaded' },
    { label: 'Record Created', value: 'Record Created' },
    { label: 'Record Updated', value: 'Record Updated' },
    { label: 'Record Deleted', value: 'Record Deleted' },
    { label: 'Status Changed', value: 'Status Changed' },
    { label: 'Profile Created', value: 'Profile Created' }
];

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
}

function formatTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(date);
}

function getActivityIcon(type) {
    const icons = {
        'Profile Updated': 'pi pi-user',
        'Attachment Uploaded': 'pi pi-upload',
        'Record Created': 'pi pi-plus-circle',
        'Record Updated': 'pi pi-pencil',
        'Record Deleted': 'pi pi-trash',
        'Status Changed': 'pi pi-arrow-right-arrow-left',
        'Profile Created': 'pi pi-user-plus'
    };
    return icons[type] || 'pi pi-history';
}

function getActivityColorClass(type) {
    const colors = {
        'Profile Updated': 'bg-blue-500',
        'Attachment Uploaded': 'bg-green-500',
        'Record Created': 'bg-purple-500',
        'Record Updated': 'bg-orange-500',
        'Record Deleted': 'bg-red-500',
        'Status Changed': 'bg-indigo-500',
        'Profile Created': 'bg-teal-500'
    };
    return colors[type] || 'bg-gray-500';
}

function extractNameFromActivity(data) {
    if (!data) return null;

    // Try to extract name from remarks first - most reliable
    if (data.remarks && typeof data.remarks === 'string') {
        // Pattern for "... profile: John Doe" or "... applicant: Jane Smith"
        const colonMatch = data.remarks.match(/:\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*(?:\s+[A-Z][a-zA-Z]+)?)(?:\s|$|!|-|\.)/);
        if (colonMatch) {
            logger.log('✓ Found name in remarks:', colonMatch[1]);
            return colonMatch[1].trim();
        }
    }

    // Try to extract from snapshot_before or snapshot_after (old/new data)
    if (data.snapshot_before) {
        try {
            const before = typeof data.snapshot_before === 'string' ? JSON.parse(data.snapshot_before) : data.snapshot_before;
            if (before && before.first_name && before.last_name) {
                const fullName = `${before.first_name} ${before.last_name}`;
                logger.log('✓ Found name in snapshot_before:', fullName);
                return fullName;
            }
            if (before && before.name) {
                logger.log('✓ Found name in snapshot_before.name:', before.name);
                return before.name;
            }
            if (before && before.title) {
                logger.log('✓ Found title in snapshot_before:', before.title);
                return before.title;
            }
        } catch (e) {
            logger.error('Error parsing snapshot_before:', e);
        }
    }

    if (data.snapshot_after) {
        try {
            const after = typeof data.snapshot_after === 'string' ? JSON.parse(data.snapshot_after) : data.snapshot_after;
            if (after && after.first_name && after.last_name) {
                const fullName = `${after.first_name} ${after.last_name}`;
                logger.log('✓ Found name in snapshot_after:', fullName);
                return fullName;
            }
            if (after && after.name) {
                logger.log('✓ Found name in snapshot_after.name:', after.name);
                return after.name;
            }
            if (after && after.title) {
                logger.log('✓ Found title in snapshot_after:', after.title);
                return after.title;
            }
        } catch (e) {
            logger.error('Error parsing snapshot_after:', e);
        }
    }

    // Try to extract from details JSON
    if (data.details) {
        try {
            const details = typeof data.details === 'string' ? JSON.parse(data.details) : data.details;
            if (details && typeof details === 'object') {
                if (details.first_name && details.last_name) {
                    const fullName = `${details.first_name} ${details.last_name}`;
                    logger.log('✓ Found name in details:', fullName);
                    return fullName;
                }
                if (details.name) {
                    logger.log('✓ Found name in details.name:', details.name);
                    return details.name;
                }
                if (details.title) {
                    logger.log('✓ Found title in details:', details.title);
                    return details.title;
                }
            }
        } catch (e) {
            logger.error('Error parsing details:', e);
        }
    }

    // Try old_value/new_value if they contain name-like data
    if (data.old_value && typeof data.old_value === 'string') {
        const nameMatch = data.old_value.match(/[A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)+/);
        if (nameMatch) {
            logger.log('✓ Found name in old_value:', nameMatch[0]);
            return nameMatch[0];
        }
    }

    if (data.new_value && typeof data.new_value === 'string') {
        const nameMatch = data.new_value.match(/[A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)+/);
        if (nameMatch) {
            logger.log('✓ Found name in new_value:', nameMatch[0]);
            return nameMatch[0];
        }
    }

    logger.log('⚠ No name found in activity:', data);
    return null;
}

async function fetchActivities() {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/user/activity-logs', {
            params: {
                per_page: 100
            }
        });
        allActivities.value = response.data.data || [];
        logger.log('📋 Fetched activities:', allActivities.value);
        applyFilters();
    } catch (error) {
        logger.error('Error fetching activity logs:', error);
        allActivities.value = [];
    } finally {
        isLoading.value = false;
    }
}

function applyFilters() {
    let filtered = [...allActivities.value];

    // Filter by name search
    if (searchName.value.trim()) {
        const searchTerm = searchName.value.toLowerCase();
        filtered = filtered.filter(a => {
            const name = a.profile_name || extractNameFromActivity(a) || '';
            return name.toLowerCase().includes(searchTerm);
        });
    }

    // Filter by activity type
    if (selectedActivityType.value) {
        filtered = filtered.filter(a => a.activity_type === selectedActivityType.value);
    }

    // Filter by date range
    if (dateRange.value && dateRange.value.length === 2) {
        const [startDate, endDate] = dateRange.value;
        filtered = filtered.filter(a => {
            const actDate = new Date(a.performed_at);
            return actDate >= startDate && actDate <= endDate;
        });
    }

    filteredActivities.value = filtered;
}

function resetFilters() {
    searchName.value = '';
    selectedActivityType.value = null;
    dateRange.value = null;
    applyFilters();
}

function viewProfile(profileId) {
    router.visit(`/scholarship/profile/${profileId}`);
}

onMounted(() => {
    fetchActivities();
});
</script>
