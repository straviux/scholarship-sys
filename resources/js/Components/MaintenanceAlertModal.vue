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
import { ref, inject, watch, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const $page = usePage();
const showModal = ref(false);
const announcement = ref(null);
const countdownDisplay = ref('00:00:00');
const startTimeFormatted = ref('');
const countdownInterval = ref(null);

// Injected from AdminLayout — avoids duplicate polling
const maintenanceStatus = inject('maintenanceStatus', null);

const isAdministrator = () => {
    const user = $page.props.auth?.user;
    if (!user) return false;
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

    // Show modal if within 5 minutes
    showModal.value = true;

    // Format countdown
    const hours = Math.floor(secondsRemaining / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    const seconds = secondsRemaining % 60;

    countdownDisplay.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const processMaintenanceData = (data) => {
    if (!data) return;
    if (isAdministrator()) return;

    // Don't re-show if recently dismissed
    const dismissedTime = localStorage.getItem('maintenanceAlertDismissed');
    if (dismissedTime && new Date().getTime() - parseInt(dismissedTime) < 30 * 60 * 1000) return;

    if (data.announcement?.countdown?.status === 'upcoming') {
        announcement.value = {
            title: data.announcement.title,
            message: data.announcement.message,
            type: data.announcement.type,
            start_time: data.announcement.countdown.start_time,
        };
        startTimeFormatted.value = new Date(data.announcement.countdown.start_time).toLocaleString();
        updateCountdown();
    } else {
        showModal.value = false;
    }
};

const dismissModal = () => {
    showModal.value = false;
    // Store dismissal time to not show again for 30 minutes
    localStorage.setItem('maintenanceAlertDismissed', new Date().getTime());
};

// React to maintenance data provided by AdminLayout — no independent polling needed
if (maintenanceStatus) {
    watch(maintenanceStatus, processMaintenanceData, { immediate: true, deep: true });
}

onMounted(() => {
    // Update countdown every second when modal is visible
    countdownInterval.value = setInterval(() => {
        if (showModal.value && announcement.value) {
            updateCountdown();
        }
    }, 1000);
});

onBeforeUnmount(() => {
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
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
