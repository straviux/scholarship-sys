<template>
    <div>
        <!-- Notification Bell Button -->
        <Button type="button" label="System Updates" icon="pi pi-bell" @click="togglePopover"
            :severity="unreadCount > 0 ? 'info' : 'contrast'"
            :badge="(unreadCount > 99 ? '99+' : unreadCount).toString() || ''" size="small" />

        <!-- Popover Menu -->
        <Popover ref="popoverRef" class="w-96">
            <div class="max-h-96 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="pi pi-bell text-blue-600"></i>
                            <h3 class="text-base font-semibold text-gray-900">System Updates</h3>
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
                        <i class="pi pi-bell text-gray-300" style="font-size: 2rem"></i>
                        <h4 class="text-sm font-medium text-gray-900 mb-1 mt-2">No notifications</h4>
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
                                            <i class="pi pi-check-circle" style="font-size: 1rem"></i>
                                        </button>
                                        <!-- Type Icon -->
                                        <i :class="[getTypeIcon(notification.type), getTypeIconClass(notification.type)]"
                                            style="font-size: 0.75rem; margin-left: 0.25rem"></i>
                                        <!-- Unread Indicator -->
                                        <div v-if="!notification.is_read"
                                            class="w-1.5 h-1.5 bg-blue-600 rounded-full ml-1">
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
                <div v-if="notifications.length > 0"
                    class="px-4 py-2 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    <button @click="viewAllNotifications"
                        class="w-full text-center text-xs text-blue-600 hover:text-blue-800 focus:outline-none font-medium transition-colors duration-200">
                        View all
                    </button>
                </div>
            </div>
        </Popover>

        <!-- Notification Detail Dialog -->
        <Dialog v-model:visible="showModal" modal :header="selectedNotification?.title"
            :style="{ width: 'auto', minWidth: '28rem', maxWidth: '56rem' }"
            :breakpoints="{ '1200px': '75vw', '960px': '85vw', '640px': '95vw' }"
            :contentStyle="{ maxHeight: '70vh', overflow: 'auto' }" class="notification-dialog" :maximizable="true">

            <template #header>
                <div class="flex items-center space-x-3 w-full">
                    <i :class="[getTypeIcon(selectedNotification?.type), getTypeIconClass(selectedNotification?.type)]"
                        style="font-size: 1.5rem"></i>
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
                    <!-- Markdown content with v-html -->
                    <div v-if="isMarkdownContent" class="markdown-content text-sm text-gray-700 leading-relaxed"
                        v-html="renderedContent">
                    </div>
                    <!-- Regular text content -->
                    <p v-else class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                        {{ renderedContent }}
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
                        label="Mark as Read" severity="info" icon="pi pi-check" size="small" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useMarkdown } from '@/composable/useMarkdown'

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
        info: 'pi pi-info-circle',
        warning: 'pi pi-exclamation-triangle',
        success: 'pi pi-check-circle',
        error: 'pi pi-times-circle'
    }
    return icons[type] || 'pi pi-info-circle'
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

/* Dialog dynamic width adjustments */
.notification-dialog :deep(.p-dialog) {
    width: auto !important;
    max-width: 90vw;
}

.notification-dialog :deep(.p-dialog-content) {
    padding: 1.5rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.notification-dialog :deep(.p-dialog-header) {
    padding: 1.25rem 1.5rem;
}

.notification-dialog :deep(.p-dialog-footer) {
    padding: 1rem 1.5rem;
}

/* Custom scrollbar for dialog content */
.notification-dialog :deep(.p-dialog-content)::-webkit-scrollbar {
    width: 6px;
}

.notification-dialog :deep(.p-dialog-content)::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.notification-dialog :deep(.p-dialog-content)::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.notification-dialog :deep(.p-dialog-content)::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Custom scrollbar for the dropdown */
.max-h-96::-webkit-scrollbar {
    width: 3px;
}

.max-h-96::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.max-h-96::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* PrimeVue Popover customizations */
:deep(.p-popover .p-popover-content) {
    padding: 0;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
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

/* Markdown content styling */
.markdown-content {
    line-height: 1.6;
}

.markdown-content :deep(h1),
.markdown-content :deep(h2),
.markdown-content :deep(h3),
.markdown-content :deep(h4),
.markdown-content :deep(h5),
.markdown-content :deep(h6) {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    font-weight: 600;
    line-height: 1.25;
    color: #1f2937;
}

.markdown-content :deep(h1) {
    font-size: 1.5em;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.3em;
}

.markdown-content :deep(h2) {
    font-size: 1.25em;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.3em;
}

.markdown-content :deep(h3) {
    font-size: 1.125em;
}

.markdown-content :deep(h4) {
    font-size: 1em;
}

.markdown-content :deep(h5),
.markdown-content :deep(h6) {
    font-size: 0.875em;
}

.markdown-content :deep(p) {
    margin-bottom: 1em;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
    margin-bottom: 1em;
    padding-left: 2em;
}

.markdown-content :deep(li) {
    margin-bottom: 0.25em;
}

.markdown-content :deep(code) {
    background-color: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: 'Courier New', Courier, monospace;
}

.markdown-content :deep(pre) {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1em;
}

.markdown-content :deep(pre code) {
    background-color: transparent;
    padding: 0;
    color: inherit;
}

.markdown-content :deep(blockquote) {
    border-left: 4px solid #3b82f6;
    padding-left: 1rem;
    margin-left: 0;
    margin-bottom: 1em;
    color: #6b7280;
    font-style: italic;
}

.markdown-content :deep(a) {
    color: #3b82f6;
    text-decoration: underline;
}

.markdown-content :deep(a:hover) {
    color: #2563eb;
}

.markdown-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1em 0;
}

.markdown-content :deep(hr) {
    border: none;
    border-top: 2px solid #e5e7eb;
    margin: 2em 0;
}

.markdown-content :deep(table) {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 1em;
}

.markdown-content :deep(table th),
.markdown-content :deep(table td) {
    border: 1px solid #e5e7eb;
    padding: 0.5rem;
    text-align: left;
}

.markdown-content :deep(table th) {
    background-color: #f3f4f6;
    font-weight: 600;
}

.markdown-content :deep(table tr:nth-child(even)) {
    background-color: #f9fafb;
}

.markdown-content :deep(strong),
.markdown-content :deep(b) {
    font-weight: 600;
}

.markdown-content :deep(em),
.markdown-content :deep(i) {
    font-style: italic;
}
</style>
