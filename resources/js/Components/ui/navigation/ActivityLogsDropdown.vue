<template>
    <div>
        <!-- Activity Logs Bell Button -->
        <Button type="button" icon="pi pi-history" @click="togglePopover"
            :severity="badgeCount > 0 ? 'info' : 'contrast'"
            :badge="(badgeCount > 99 ? '99+' : badgeCount).toString() || ''" size="small"
            v-tooltip.bottom="'Your Activity Logs'" />

        <!-- Popover Menu -->
        <Popover ref="popoverRef" class="w-80">
            <div class="max-h-96 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-history text-blue-600"></i>
                            <h3 class="text-base font-semibold text-gray-900">Your Activities</h3>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ badgeCount }} latest activities
                    </p>
                </div>

                <!-- Activities List -->
                <div class="flex-1 overflow-y-auto pb-4 min-h-0">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="px-4 py-6 text-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="text-xs text-gray-500 mt-2">Loading...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="activities.length === 0" class="px-4 py-8 text-center">
                        <i class="pi pi-history text-gray-300" style="font-size: 2rem"></i>
                        <h4 class="text-sm font-medium text-gray-900 mb-1 mt-2">No activities yet</h4>
                        <p class="text-xs text-gray-500">Your activities will appear here</p>
                    </div>

                    <!-- Activities List -->
                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="activity in activities" :key="activity.id" class="px-4 py-3">

                            <!-- Activity Content -->
                            <div>
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <!-- Activity Icon -->
                                            <div
                                                :class="['w-5 h-5 rounded-full flex items-center justify-center text-white text-xs flex-shrink-0', getActivityColorClass(activity.activity_type)]">
                                                <i :class="getActivityIcon(activity.activity_type)"
                                                    style="font-size: 0.65rem"></i>
                                            </div>
                                            <h4 class="text-xs font-medium text-gray-900 line-clamp-1">
                                                {{ activity.activity_type }}
                                            </h4>
                                        </div>
                                        <p v-if="activity.profile_name"
                                            class="text-xs text-blue-600 font-medium mt-1 ml-7">
                                            <i class="pi pi-user text-xs mr-1"></i>{{ activity.profile_name }}
                                        </p>
                                        <p class="text-xs text-gray-600 mt-1 ml-7 line-clamp-2">
                                            {{ activity.remarks || activity.description || 'No details' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-between mt-2 ml-7 text-xs text-gray-400">
                                    <span class="truncate">{{ activity.user?.name || 'System' }}</span>
                                    <span class="text-xs">{{ formatDate(activity.performed_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="activities.length > 0" class="px-4 py-2 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    <button @click="viewAllActivities"
                        class="w-full text-center text-xs text-blue-600 hover:text-blue-800 focus:outline-none font-medium transition-colors duration-200 cursor-pointer">
                        View all
                    </button>
                </div>
            </div>
        </Popover>
    </div>
</template>

<script setup>
import { ref, onMounted, defineExpose, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import logger from '@/utils/logger';

const popoverRef = ref(null);
const activities = ref([]);
const activitiesCount = ref(0);
const isLoading = ref(false);

// Computed badge value
const badgeCount = computed(() => {
    return activitiesCount.value;
});

function togglePopover(event) {
    if (popoverRef.value) {
        popoverRef.value.toggle(event);
    }
}

async function fetchRecentActivities() {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/user/activity-logs/recent?limit=10');
        activities.value = response.data.data || [];
        activitiesCount.value = response.data.unread_count || 0;
    } catch (error) {
        logger.error('Error fetching user activity logs:', error);
        activities.value = [];
    } finally {
        isLoading.value = false;
    }
}

// Expose refresh method for external components
defineExpose({
    refreshActivities: fetchRecentActivities
});

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

function formatRelativeTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;

    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' }).format(date);
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
}

async function viewAllActivities() {
    // Mark all activities as viewed on the backend and reset badge count
    await markAllActivitiesAsViewed();
    // Close popover
    if (popoverRef.value) {
        popoverRef.value.hide();
    }
    // Navigate to full activity logs page
    router.visit('/user/activity-logs');
}

async function markAllActivitiesAsViewed() {
    try {
        await axios.post('/api/user/activity-logs/mark-all-viewed');
        // Reset the counter
        activitiesCount.value = 0;
        logger.log('Activities marked as viewed');
    } catch (error) {
        logger.error('Error marking activities as viewed:', error);
    }
}

onMounted(() => {
    fetchRecentActivities();
});
</script>
