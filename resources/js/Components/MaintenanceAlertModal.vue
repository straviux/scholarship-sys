<template>
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-in">
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-500 to-red-600 px-4 short:px-3 py-4 short:py-3 text-center">
                    <i class="pi pi-exclamation-circle text-white text-[3rem] short:text-[2rem] mb-3"></i>
                    <h2 class="text-xl font-bold text-white">Scheduled Maintenance</h2>
                </div>

                <!-- Content -->
                <div class="px-4 short:px-3 py-4 short:py-3">
                    <!-- Maintenance Title -->
                    <div class="mb-4">
                        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ announcement.title }}</p>
                    </div>

                    <!-- Message -->
                    <div class="mb-4 short:mb-2">
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            {{ announcement.message }}
                        </p>
                    </div>

                    <!-- Countdown -->
                    <div
                        class="bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-lg p-4 mb-4 short:mb-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400 text-center mb-2 font-semibold">MAINTENANCE
                            STARTS IN</p>
                        <div
                            class="text-3xl short:text-xl font-mono font-bold text-center text-orange-600 dark:text-orange-400">
                            {{ countdownDisplay }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-500 text-center mt-2">{{ startTimeFormatted }}
                        </p>
                    </div>

                    <!-- Alert Type Badge -->
                    <div class="flex justify-center mb-4 short:mb-2">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold" :class="{
                            'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300': announcement.type === 'info',
                            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300': announcement.type === 'warning',
                            'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': announcement.type === 'critical',
                        }">
                            {{ announcement.type.toUpperCase() }}
                        </span>
                    </div>

                    <!-- Info Message -->
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3 mb-4 short:mb-2">
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            ℹ️ Maintenance begins in 5 minutes. Please save your work immediately and log out if
                            possible.
                        </p>
                    </div>

                    <!-- Actions -->
                    <button @click="dismissModal"
                        class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded transition-colors">
                        I Understand, Continue
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const $page = usePage();
const showModal = ref(false);
const announcement = ref(null);
const countdownDisplay = ref('00:00:00');
const startTimeFormatted = ref('');
const countdownInterval = ref(null);
const statusCheckInterval = ref(null);
const lastCheckTime = ref(0);

const isAdministrator = () => {
    const user = $page.props.auth?.user;
    if (!user) return false;

    // Check if user has admin or administrator role
    if (user.roles && Array.isArray(user.roles)) {
        return user.roles.some(role => role.name === 'admin' || role.name === 'administrator');
    }

    return false;
};

const updateCountdown = () => {
    if (!announcement.value || !announcement.value.start_time) return;

    const now = new Date().getTime();
    const startTime = new Date(announcement.value.start_time).getTime();
    const secondsRemaining = Math.floor((startTime - now) / 1000);

    // Hide modal if time has passed
    if (secondsRemaining <= 0) {
        showModal.value = false;
        return;
    }

    // Hide modal if more than 5 minutes remaining
    if (secondsRemaining > 300) {
        showModal.value = false;
        return;
    }

    // Show modal if within 15 minutes
    showModal.value = true;

    // Format countdown
    const hours = Math.floor(secondsRemaining / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    const seconds = secondsRemaining % 60;

    countdownDisplay.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const getCheckInterval = () => {
    // Determine polling interval based on time to maintenance
    if (!announcement.value || !announcement.value.start_time) {
        return 5 * 60 * 1000; // 5 minutes for no announcement
    }

    const now = new Date().getTime();
    const startTime = new Date(announcement.value.start_time).getTime();
    const secondsRemaining = Math.floor((startTime - now) / 1000);

    // Within 5 minutes: check every 1 minute
    if (secondsRemaining > 0 && secondsRemaining <= 300) {
        return 60 * 1000;
    }

    // More than 5 minutes away: check every 10 minutes
    return 10 * 60 * 1000;
};

const fetchMaintenanceStatus = async () => {
    try {
        const now = new Date().getTime();

        // Throttle: don't check more than once per 5 seconds
        if (now - lastCheckTime.value < 5000) {
            return;
        }

        lastCheckTime.value = now;

        const response = await fetch('/api/maintenance/status');
        if (!response.ok) return;

        const data = await response.json();

        if (data.announcement && data.announcement.countdown) {
            const countdown = data.announcement.countdown;

            // Only process if maintenance is upcoming
            if (countdown.status === 'upcoming') {
                announcement.value = {
                    title: data.announcement.title,
                    message: data.announcement.message,
                    type: data.announcement.type,
                    start_time: countdown.start_time,
                };

                // Format start time
                const startDate = new Date(countdown.start_time);
                startTimeFormatted.value = startDate.toLocaleString();

                updateCountdown();

                // Smart polling: adjust interval based on time to maintenance (5 min threshold)
                adjustCheckInterval();
            } else {
                showModal.value = false;
            }
        } else {
            showModal.value = false;
        }
    } catch (error) {
        console.error('Error fetching maintenance status:', error);
    }
};

const adjustCheckInterval = () => {
    // Clear existing status check interval
    if (statusCheckInterval.value) {
        clearInterval(statusCheckInterval.value);
    }

    // Set new interval based on time to maintenance
    const newInterval = getCheckInterval();
    statusCheckInterval.value = setInterval(fetchMaintenanceStatus, newInterval);
};

const dismissModal = () => {
    showModal.value = false;
    // Store dismissal time to not show again for 30 minutes
    const dismissalTime = new Date().getTime();
    localStorage.setItem('maintenanceAlertDismissed', dismissalTime);
};

onMounted(() => {
    // Don't show modal for administrators
    if (isAdministrator()) {
        return;
    }

    // Check if alert was dismissed less than 30 minutes ago
    const dismissedTime = localStorage.getItem('maintenanceAlertDismissed');
    const thirtyMinutesInMs = 30 * 60 * 1000;

    if (dismissedTime && new Date().getTime() - parseInt(dismissedTime) < thirtyMinutesInMs) {
        return;
    }

    // Update countdown every second when modal is visible (shows when 5 min remaining)
    countdownInterval.value = setInterval(() => {
        if (showModal.value && announcement.value) {
            updateCountdown();
        }
    }, 1000);

    // Smart polling: check every 10 minutes initially, check every 1 minute when 5 min away
    adjustCheckInterval();
});

onBeforeUnmount(() => {
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
    }
    if (statusCheckInterval.value) {
        clearInterval(statusCheckInterval.value);
    }
});
</script>

<style scoped>
@keyframes slideIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-in {
    animation: slideIn 0.3s ease-out;
}
</style>
