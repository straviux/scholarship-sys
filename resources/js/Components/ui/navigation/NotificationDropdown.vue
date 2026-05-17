<template>
    <div>
        <!-- Notification Bell Button -->
        <AppButton type="button" icon="bell" @click="togglePopover" :severity="unreadCount > 0 ? 'info' : 'contrast'"
            :badge="(unreadCount > 99 ? '99+' : unreadCount).toString() || ''" size="small" text rounded
            v-tooltip.bottom="'System Updates Notifications'" />

        <!-- Popover Menu -->
        <Popover ref="popoverRef" class="w-96 !rounded-2xl ios-notification-popover">
            <div class="max-h-96 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <AppIcon name="bell" :size="14" class="text-blue-600" />
                            <h3 class="text-base font-semibold">System Updates</h3>
                        </div>
                        <Button v-if="unreadCount > 0" @click="markAllAsRead" :disabled="isMarkingAllAsRead"
                            :label="isMarkingAllAsRead ? 'Marking...' : 'Mark all'" severity="info" variant="text"
                            size="small" />
                    </div>
                    <p class="text-xs opacity-60 mt-1">
                        {{ unreadCount }} unread
                    </p>
                </div>

                <!-- Notifications List -->
                <div class="flex-1 overflow-y-auto pb-4 min-h-0">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="px-4 py-6 text-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="text-xs opacity-60 mt-2">Loading...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center">
                        <AppIcon name="bell" :size="32" class="opacity-30" />
                        <h4 class="text-sm font-medium mb-1 mt-2">No notifications</h4>
                        <p class="text-xs opacity-60">You're all caught up!</p>
                    </div>

                    <!-- Notifications -->
                    <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="notification in notifications" :key="notification.id"
                            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors duration-200"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/30': !notification.is_read,
                                'bg-white dark:bg-transparent': notification.is_read
                            }" @click="openNotificationModal(notification)">

                            <!-- Notification Content -->
                            <div>
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-xs font-medium line-clamp-2 leading-4">
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
                                        <p class="text-xs opacity-70 mt-1 line-clamp-2 leading-4">
                                            {{ notification.content }}
                                        </p>
                                    </div>

                                    <div class="flex items-center ml-2 flex-shrink-0">
                                        <!-- Mark as read button -->
                                        <AppButton v-if="!notification.is_read" @click.stop="markAsRead(notification)"
                                            icon="check-circle" severity="info" variant="text" rounded size="small"
                                            v-tooltip.top="'Mark as read'" />
                                        <!-- Type Icon -->
                                        <AppIcon :name="getTypeIcon(notification.type)"
                                            :class="getTypeIconClass(notification.type)" :size="12"
                                            style="margin-left: 0.25rem" />
                                        <!-- Unread Indicator -->
                                        <div v-if="!notification.is_read"
                                            class="w-1.5 h-1.5 bg-blue-600 rounded-full ml-1">
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-between mt-2 text-xs opacity-50">
                                    <span class="truncate">{{ notification.created_by }}</span>
                                    <span class="text-xs">{{ notification.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="notifications.length > 0"
                    class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                    <Button @click="viewAllNotifications" label="View all" severity="info" variant="text" size="small"
                        class="w-full" />
                </div>
            </div>
        </Popover>

        <!-- Notification Detail Dialog -->
        <IosModal :visible="showModal" width="56rem" min-width="28rem" max-width="95vw"
            body-style="padding: 16px; max-height: 70vh; overflow: auto;" @update:visible="showModal = $event">
            <template #title>
                <div class="flex items-center space-x-3 w-full">
                    <AppIcon :name="getTypeIcon(selectedNotification?.type)"
                        :class="getTypeIconClass(selectedNotification?.type)" :size="24" />
                    <div class="flex items-center space-x-2 flex-1">
                        <h3 class="text-lg font-medium">
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
                    <!-- Markdown content with v-html -->
                    <div v-if="isMarkdownContent" class="ios-markdown-prose text-sm leading-relaxed"
                        v-html="renderedContent">
                    </div>
                    <!-- Regular text content -->
                    <p v-else class="text-sm leading-relaxed whitespace-pre-wrap">
                        {{ renderedContent }}
                    </p>
                </div>

                <!-- Metadata -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs opacity-60 space-y-2 sm:space-y-0">
                        <div class="flex items-center space-x-4">
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

            <div v-if="!selectedNotification?.is_read"
                class="flex justify-end space-x-2 pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <AppButton @click="markAsReadAndClose(selectedNotification)" label="Mark as Read" severity="info"
                    icon="check" size="small" />
            </div>
        </IosModal>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useMarkdown } from '@/composable/useMarkdown'
import IosModal from '@/Components/ui/IosModal.vue'

// Markdown composable
const { renderMarkdown } = useMarkdown()

// Props
const props = defineProps({
    unreadCount: {
        type: Number,
        default: 0
    }
})

// Reactive data
const popoverRef = ref(null)
const notifications = ref([])
const isLoading = ref(false)
const isMarkingAllAsRead = ref(false)
const unreadCount = ref(props.unreadCount)
const selectedNotification = ref(null)
const showModal = ref(false)

// Computed
const hasUnreadNotifications = computed(() => unreadCount.value > 0)
const page = usePage()

// Computed property for rendered notification content
const renderedContent = computed(() => {
    if (!selectedNotification.value) return ''

    if (selectedNotification.value.is_markdown && selectedNotification.value.markdown_content) {
        return renderMarkdown(selectedNotification.value.markdown_content)
    }

    return selectedNotification.value.content
})

const isMarkdownContent = computed(() => {
    return selectedNotification.value?.is_markdown && selectedNotification.value?.markdown_content
})

// Watch for prop changes
watch(() => props.unreadCount, (newValue) => {
    unreadCount.value = newValue
})

// Methods
const togglePopover = async (event) => {
    popoverRef.value.toggle(event)
    if (notifications.value.length === 0) {
        await fetchNotifications()
    }
}

const fetchNotifications = async () => {
    isLoading.value = true
    try {
        const response = await axios.get('/api/system-updates')
        // Sort notifications by creation date (newest first)
        notifications.value = response.data.updates.sort((a, b) => {
            return new Date(b.created_at) - new Date(a.created_at)
        })
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
        info: 'info-circle',
        warning: 'exclamation-triangle',
        success: 'check-circle',
        error: 'times-circle'
    }
    return icons[type] || 'info-circle'
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
    // Navigate to full notifications page
    popoverRef.value.hide()
    router.visit(route('system-updates.index'))
}

const openNotificationModal = (notification) => {
    selectedNotification.value = notification
    showModal.value = true
    popoverRef.value.hide() // Close the popover when opening modal
}

const closeModal = () => {
    showModal.value = false
    selectedNotification.value = null
}

const markAsReadAndClose = async (notification) => {
    await markAsRead(notification)
    closeModal()
}

// Lifecycle
onMounted(() => {

    // Update unread count from page props if available
    if (page.props.auth?.user?.unread_notifications_count !== undefined) {
        unreadCount.value = page.props.auth.user.unread_notifications_count
    } else {
        // Fallback: fetch unread count
        fetchUnreadCount()
    }
})
</script>

