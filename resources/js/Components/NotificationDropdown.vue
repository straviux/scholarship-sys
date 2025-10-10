<template>
    <div class="relative">
        <!-- Notification Bell Button -->
        <button @click.stop="toggleDropdown"
            class="relative p-2 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 rounded-full transition-colors duration-200"
            :class="{ 'text-yellow-400': hasUnreadNotifications }">
            <BellIcon class="h-6 w-6" />
            <!-- Notification Badge -->
            <span v-if="unreadCount > 0"
                class="absolute right-0 -top-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full animate-pulse">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div v-if="isOpen" v-click-outside="closeDropdown"
            class="absolute right-0 z-50 mt-2 w-80 bg-white rounded-lg shadow-xl max-h-96 overflow-hidden transform transition-all duration-200 ease-out flex flex-col"
            style="z-index: 9999;">

            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <BellIcon class="h-4 w-4 text-blue-600" />
                        <h3 class="text-base font-semibold text-gray-900">Notifications</h3>
                    </div>
                    <button v-if="unreadCount > 0" @click="markAllAsRead" :disabled="isMarkingAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-800 focus:outline-none disabled:opacity-50 font-medium transition-colors duration-200">
                        <span v-if="isMarkingAllAsRead">Marking...</span>
                        <span v-else>Mark all</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ unreadCount }} unread
                </p>
            </div>

            <!-- Notifications List -->
            <div class="flex-1 overflow-y-auto pb-4 min-h-0">
                <!-- Loading State -->
                <div v-if="isLoading" class="px-4 py-6 text-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-xs text-gray-500 mt-2">Loading...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center">
                    <BellIcon class="h-8 w-8 text-gray-300 mx-auto mb-2" />
                    <h4 class="text-sm font-medium text-gray-900 mb-1">No notifications</h4>
                    <p class="text-xs text-gray-500">You're all caught up!</p>
                </div>

                <!-- Notifications -->
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="notification in notifications" :key="notification.id"
                        class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors duration-200" :class="{
                            'bg-blue-50': !notification.is_read,
                            'bg-white': notification.is_read
                        }" @click="openNotificationModal(notification)">

                        <!-- Notification Content -->
                        <div>
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-xs font-medium text-gray-900 line-clamp-2 leading-4">
                                            {{ notification.title }}
                                        </h4>
                                        <!-- Simple Priority Dot -->
                                        <div v-if="notification.priority === 'urgent' || notification.priority === 'high'"
                                            class="w-2 h-2 rounded-full flex-shrink-0" :class="{
                                                'bg-red-500': notification.priority === 'urgent',
                                                'bg-orange-500': notification.priority === 'high'
                                            }">
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2 leading-4">
                                        {{ notification.content }}
                                    </p>
                                </div>

                                <div class="flex items-center ml-2 flex-shrink-0">
                                    <!-- Mark as read button -->
                                    <button v-if="!notification.is_read" @click.stop="markAsRead(notification)"
                                        class="p-1 text-gray-400 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full transition-colors duration-200"
                                        title="Mark as read">
                                        <CheckCircleIcon class="h-4 w-4" />
                                    </button>
                                    <!-- Type Icon -->
                                    <component :is="getTypeIcon(notification.type)" class="h-3 w-3 ml-1"
                                        :class="getTypeIconClass(notification.type)" />
                                    <!-- Unread Indicator -->
                                    <div v-if="!notification.is_read" class="w-1.5 h-1.5 bg-blue-600 rounded-full ml-1">
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between mt-2 text-xs text-gray-400">
                                <span class="truncate">{{ notification.created_by }}</span>
                                <span class="text-xs">{{ notification.created_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="notifications.length > 0" class="px-4 py-2 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                <button @click="viewAllNotifications"
                    class="w-full text-center text-xs text-blue-600 hover:text-blue-800 focus:outline-none font-medium transition-colors duration-200">
                    View all
                </button>
            </div>
        </div>

        <!-- Notification Detail Dialog -->
        <Dialog v-model:visible="showModal" modal :header="selectedNotification?.title" :style="{ width: '32rem' }"
            :breakpoints="{ '960px': '90vw', '640px': '95vw' }" class="notification-dialog">

            <template #header>
                <div class="flex items-center space-x-3 w-full">
                    <component :is="getTypeIcon(selectedNotification?.type)" class="h-6 w-6"
                        :class="getTypeIconClass(selectedNotification?.type)" />
                    <div class="flex items-center space-x-2 flex-1">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ selectedNotification?.title }}
                        </h3>
                        <!-- Priority indicator -->
                        <div v-if="selectedNotification?.priority === 'urgent' || selectedNotification?.priority === 'high'"
                            class="w-3 h-3 rounded-full" :class="{
                                'bg-red-500': selectedNotification?.priority === 'urgent',
                                'bg-orange-500': selectedNotification?.priority === 'high'
                            }">
                        </div>
                    </div>
                </div>
            </template>

            <!-- Modal Content -->
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                        {{ selectedNotification?.content }}
                    </p>
                </div>

                <!-- Metadata -->
                <div class="pt-4 border-t border-gray-200">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 space-y-2 sm:space-y-0">
                        <div class="flex items-center space-x-4">
                            <!-- <span>Type: <span class="font-medium">{{ selectedNotification?.type }}</span></span> -->
                            <span>Priority: <span class="font-medium capitalize">{{ selectedNotification?.priority
                            }}</span></span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span>@<span class="font-medium">{{ selectedNotification?.created_by }}</span></span>
                            <span>{{ selectedNotification?.created_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button v-if="!selectedNotification?.is_read" @click="markAsReadAndClose(selectedNotification)"
                        label="Mark as Read" severity="info" icon="pi pi-check" />
                    <Button @click="closeModal" label="Close" severity="secondary" outlined />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import {
    BellIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
    unreadCount: {
        type: Number,
        default: 0
    }
})

// Reactive data
const isOpen = ref(false) // Reset to normal behavior
const notifications = ref([])
const isLoading = ref(false)
const isMarkingAllAsRead = ref(false)
const unreadCount = ref(props.unreadCount)
const selectedNotification = ref(null)
const showModal = ref(false)

// Computed
const hasUnreadNotifications = computed(() => unreadCount.value > 0)
const page = usePage()

// Watch for prop changes
watch(() => props.unreadCount, (newValue) => {
    unreadCount.value = newValue
})

// Methods
const toggleDropdown = async () => {
    console.log('Toggle dropdown clicked, current state:', isOpen.value)
    isOpen.value = !isOpen.value
    console.log('New state:', isOpen.value)
    if (isOpen.value && notifications.value.length === 0) {
        console.log('Fetching notifications...')
        await fetchNotifications()
    }
}

const closeDropdown = () => {
    console.log('Closing dropdown')
    isOpen.value = false
}

const fetchNotifications = async () => {
    isLoading.value = true
    try {
        const response = await axios.get('/api/system-updates')
        // Sort notifications by creation date (newest first)
        notifications.value = response.data.updates.sort((a, b) => {
            return new Date(b.created_at) - new Date(a.created_at)
        })
        console.log('Fetched and sorted notifications:', notifications.value)
    } catch (error) {
        console.error('Error fetching notifications:', error)
    } finally {
        isLoading.value = false
    }
}

const fetchUnreadCount = async () => {
    try {
        const response = await axios.get('/api/system-updates/unread-count')
        unreadCount.value = response.data.unread_count
        console.log('Fetched unread count:', unreadCount.value)
    } catch (error) {
        console.error('Error fetching unread count:', error)
    }
}

const markAsRead = async (notification) => {
    if (notification.is_read) return

    try {
        await axios.post(`/api/system-updates/${notification.id}/mark-read`)
        notification.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)

        // Update the global unread count
        if (page.props.auth?.user) {
            page.props.auth.user.unread_notifications_count = unreadCount.value
        }
    } catch (error) {
        console.error('Error marking notification as read:', error)
    }
}

const markAllAsRead = async () => {
    if (unreadCount.value === 0) return

    isMarkingAllAsRead.value = true
    try {
        await axios.post('/api/system-updates/mark-all-read')

        // Mark all notifications as read
        notifications.value.forEach(notification => {
            notification.is_read = true
        })

        unreadCount.value = 0

        // Update the global unread count
        if (page.props.auth?.user) {
            page.props.auth.user.unread_notifications_count = 0
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error)
    } finally {
        isMarkingAllAsRead.value = false
    }
}

const getTypeIcon = (type) => {
    const icons = {
        info: InformationCircleIcon,
        warning: ExclamationTriangleIcon,
        success: CheckCircleIcon,
        error: XCircleIcon
    }
    return icons[type] || InformationCircleIcon
}

const getTypeIconClass = (type) => {
    const classes = {
        info: 'text-blue-500',
        warning: 'text-yellow-500',
        success: 'text-green-500',
        error: 'text-red-500'
    }
    return classes[type] || 'text-blue-500'
}

const viewAllNotifications = () => {
    // Navigate to full notifications page if needed
    closeDropdown()
}

const openNotificationModal = (notification) => {
    selectedNotification.value = notification
    showModal.value = true
    closeDropdown() // Close the dropdown when opening modal
}

const closeModal = () => {
    showModal.value = false
    selectedNotification.value = null
}

const markAsReadAndClose = async (notification) => {
    await markAsRead(notification)
    closeModal()
}

// Click outside directive
const vClickOutside = {
    beforeMount(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event)
            }
        }
        document.addEventListener('click', el.clickOutsideEvent)
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent)
    }
}

// Lifecycle
onMounted(() => {
    console.log('NotificationDropdown mounted')
    console.log('Props unreadCount:', props.unreadCount)
    console.log('Page props:', page.props.auth?.user)

    // Update unread count from page props if available
    if (page.props.auth?.user?.unread_notifications_count !== undefined) {
        unreadCount.value = page.props.auth.user.unread_notifications_count
        console.log('Set unread count from props:', unreadCount.value)
    } else {
        console.log('No unread_notifications_count in props, fetching...')
        // Fallback: fetch unread count
        fetchUnreadCount()
    }
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar for the dropdown */
.max-h-64::-webkit-scrollbar {
    width: 3px;
}

.max-h-64::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.max-h-64::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* PrimeVue Dialog custom styles */
:deep(.notification-dialog .p-dialog-header) {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
}

:deep(.notification-dialog .p-dialog-content) {
    padding: 0 1.5rem 1rem 1.5rem;
}

:deep(.notification-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}
</style>