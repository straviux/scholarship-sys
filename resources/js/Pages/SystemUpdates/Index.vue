<template>
    <AdminLayout>
        <template #header>
            System Updates
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">System Updates</h2>
                        <p class="text-sm text-gray-600 mt-1">Stay informed about important announcements and changes
                        </p>
                    </div>
                    <Button v-if="unreadCount > 0" @click="markAllAsRead" label="Mark All as Read" icon="pi pi-check"
                        severity="secondary" outlined size="small" />
                </div>

                <!-- Filter Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button @click="currentFilter = 'all'" :class="[
                                currentFilter === 'all'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]">
                                All Updates
                                <span :class="[
                                    currentFilter === 'all' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-900',
                                    'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
                                ]">
                                    {{ filteredUpdates.length }}
                                </span>
                            </button>
                            <button @click="currentFilter = 'unread'" :class="[
                                currentFilter === 'unread'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]">
                                Unread
                                <span v-if="unreadCount > 0" :class="[
                                    currentFilter === 'unread' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600',
                                    'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
                                ]">
                                    {{ unreadCount }}
                                </span>
                            </button>
                            <button @click="currentFilter = 'read'" :class="[
                                currentFilter === 'read'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]">
                                Read
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Updates List -->
                <div v-if="loading" class="text-center py-12">
                    <i class="pi pi-spinner pi-spin text-4xl text-blue-500"></i>
                    <p class="mt-4 text-gray-600">Loading updates...</p>
                </div>

                <div v-else-if="filteredUpdates.length === 0" class="text-center py-12">
                    <i class="pi pi-inbox text-6xl text-gray-300"></i>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No updates found</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ currentFilter === 'unread' ? 'You\'re all caught up!' : 'There are no updates to display.' }}
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <Card v-for="update in filteredUpdates" :key="update.id"
                        :class="['w-full transition-all duration-200 cursor-pointer hover:shadow-lg', !update.is_read ? 'ring-2 ring-blue-200 bg-blue-50' : 'hover:ring-2 hover:ring-gray-200']"
                        @click="viewUpdate(update.id)">
                        <template #content>
                            <div class="relative">
                                <!-- Unread Indicator -->
                                <div v-if="!update.is_read"
                                    class="absolute -left-4 top-0 w-1.5 h-full bg-blue-500 rounded-r"></div>

                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <!-- Header -->
                                        <div class="flex items-start gap-3 mb-3">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                                    <h3 class="text-lg font-semibold text-gray-900">{{ update.title }}
                                                    </h3>
                                                    <Tag v-if="!update.is_read" value="New" severity="info"
                                                        icon="pi pi-circle-fill" class="text-xs" />
                                                    <Tag :value="update.priority"
                                                        :severity="getPrioritySeverity(update.priority)"
                                                        class="text-xs" />
                                                    <Tag :value="update.type" :severity="getTypeSeverity(update.type)"
                                                        class="text-xs" />
                                                    <Tag v-if="update.is_markdown" value="Markdown" severity="secondary"
                                                        icon="pi pi-file-edit" class="text-xs" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Content Preview (truncated) -->
                                        <div class="mb-4">
                                            <!-- Markdown Content Preview -->
                                            <div v-if="update.is_markdown && update.markdown_content"
                                                class="text-gray-700 line-clamp-3">
                                                {{ stripMarkdown(update.markdown_content) }}
                                            </div>
                                            <!-- Plain Text Content Preview -->
                                            <div v-else class="text-gray-700 line-clamp-3">
                                                {{ update.content }}
                                            </div>
                                            <p class="text-sm text-blue-600 mt-2 font-medium">Click to read more →</p>
                                        </div>

                                        <!-- Footer -->
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <div class="flex items-center gap-4">
                                                <span class="flex items-center gap-1">
                                                    <i class="pi pi-user text-xs"></i>
                                                    {{ update.created_by }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="pi pi-calendar text-xs"></i>
                                                    {{ update.created_at }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Indicator -->
                                    <div class="flex-shrink-0" @click.stop>
                                        <Button v-if="!update.is_read" @click="markAsRead(update)" label="Mark as Read"
                                            icon="pi pi-check" severity="secondary" size="small" outlined />
                                        <div v-else class="flex items-center gap-2 text-green-600 text-sm font-medium">
                                            <i class="pi pi-check-circle"></i>
                                            <span>Read</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import { marked } from 'marked'

// Configure marked options for security
marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: false,
    mangle: false
})

// Data
const updates = ref([])
const loading = ref(true)
const currentFilter = ref('all')

// Computed
const unreadCount = computed(() => {
    return updates.value.filter(update => !update.is_read).length
})

const filteredUpdates = computed(() => {
    if (currentFilter.value === 'unread') {
        return updates.value.filter(update => !update.is_read)
    } else if (currentFilter.value === 'read') {
        return updates.value.filter(update => update.is_read)
    }
    return updates.value
})

// Methods
const fetchUpdates = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/system-updates')
        updates.value = response.data.updates
    } catch (error) {
        console.error('Error fetching updates:', error)
        alert('Failed to load system updates. Please refresh the page.')
    } finally {
        loading.value = false
    }
}

const markAsRead = async (update) => {
    try {
        await axios.post(`/api/system-updates/${update.id}/mark-read`)
        update.is_read = true
    } catch (error) {
        console.error('Error marking update as read:', error)
        alert('Failed to mark update as read. Please try again.')
    }
}

const markAllAsRead = async () => {
    try {
        await axios.post('/api/system-updates/mark-all-read')
        updates.value.forEach(update => {
            update.is_read = true
        })
    } catch (error) {
        console.error('Error marking all updates as read:', error)
        alert('Failed to mark all updates as read. Please try again.')
    }
}

const getPrioritySeverity = (priority) => {
    const severities = {
        low: 'secondary',
        normal: 'info',
        high: 'warn',
        urgent: 'danger'
    }
    return severities[priority] || 'info'
}

const getTypeSeverity = (type) => {
    const severities = {
        info: 'info',
        warning: 'warn',
        success: 'success',
        error: 'danger'
    }
    return severities[type] || 'info'
}

const renderMarkdown = (markdown) => {
    if (!markdown) return ''
    return marked.parse(markdown)
}

const stripMarkdown = (markdown) => {
    if (!markdown) return ''
    // Remove markdown formatting for preview
    return markdown
        .replace(/[#*`~\[\]()]/g, '')
        .replace(/!\[.*?\]\(.*?\)/g, '')
        .replace(/\n+/g, ' ')
        .trim()
}

const viewUpdate = (id) => {
    router.visit(route('system-updates.show', { id }))
}

// Lifecycle
onMounted(() => {
    fetchUpdates()
})
</script>

<style scoped>
/* Markdown prose styling */
.prose {
    color: #374151;
    max-width: 65ch;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3),
.prose :deep(h4) {
    color: #111827;
    font-weight: 600;
    margin-top: 1em;
    margin-bottom: 0.5em;
}

.prose :deep(h1) {
    font-size: 1.5em;
}

.prose :deep(h2) {
    font-size: 1.25em;
}

.prose :deep(h3) {
    font-size: 1.125em;
}

.prose :deep(p) {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose :deep(ul),
.prose :deep(ol) {
    padding-left: 1.5em;
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose :deep(li) {
    margin-top: 0.25em;
    margin-bottom: 0.25em;
}

.prose :deep(code) {
    background-color: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: ui-monospace, monospace;
}

.prose :deep(pre) {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 0.375rem;
    overflow-x: auto;
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose :deep(pre code) {
    background-color: transparent;
    padding: 0;
    color: inherit;
}

.prose :deep(blockquote) {
    border-left: 4px solid #d1d5db;
    padding-left: 1em;
    font-style: italic;
    color: #6b7280;
    margin: 1em 0;
}

.prose :deep(a) {
    color: #3b82f6;
    text-decoration: underline;
}

.prose :deep(a:hover) {
    color: #2563eb;
}

.prose :deep(strong) {
    font-weight: 600;
    color: #111827;
}

.prose :deep(em) {
    font-style: italic;
}

.prose :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1em 0;
}

.prose :deep(table th),
.prose :deep(table td) {
    border: 1px solid #d1d5db;
    padding: 0.5em;
    text-align: left;
}

.prose :deep(table th) {
    background-color: #f3f4f6;
    font-weight: 600;
}

.prose :deep(hr) {
    border: 0;
    border-top: 1px solid #d1d5db;
    margin: 1.5em 0;
}

.prose :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin: 1em 0;
}

/* Unread indicator animation */
@keyframes pulse-blue {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.ring-blue-200 {
    animation: pulse-blue 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Line clamp for truncated previews */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
