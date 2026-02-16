<template>
    <AdminLayout>
        <template #header>
            System Update Details
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <i class="pi pi-spinner pi-spin text-4xl text-blue-500"></i>
                    <p class="mt-4 text-gray-600">Loading update...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-12">
                    <i class="pi pi-exclamation-circle text-6xl text-red-500"></i>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Error Loading Update</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ error }}</p>
                    <Button @click="$inertia.visit(route('system-updates.index'))" label="Back to Updates"
                        class="mt-4" />
                </div>

                <!-- Update Content -->
                <div v-else-if="update" class="space-y-6">
                    <!-- Back Button -->
                    <div>
                        <Button @click="goBack" label="Back" icon="pi pi-arrow-left" text size="small" />
                    </div>

                    <!-- Update Card -->
                    <Card class="w-full">
                        <template #content>
                            <!-- Header Section -->
                            <div class="border-b border-gray-200 pb-6 mb-6">
                                <!-- Priority & Type Badges -->
                                <div class="flex items-center gap-2 mb-4 flex-wrap">
                                    <Tag :value="update.priority" :severity="getPrioritySeverity(update.priority)" />
                                    <Tag :value="update.type" :severity="getTypeSeverity(update.type)" />
                                    <Tag v-if="update.is_markdown" value="Markdown" severity="secondary"
                                        icon="pi pi-file-edit" />
                                    <Tag v-if="!update.is_read" value="Unread" severity="info"
                                        icon="pi pi-circle-fill" />
                                </div>

                                <!-- Title -->
                                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ update.title }}</h1>

                                <!-- Meta Information -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-user"></i>
                                        <span>{{ update.created_by }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{ update.created_at }}</span>
                                    </div>
                                    <div v-if="update.is_read" class="flex items-center gap-2 text-green-600">
                                        <i class="pi pi-check-circle"></i>
                                        <span>Read</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="prose prose-lg max-w-none">
                                <!-- Markdown Content -->
                                <div v-if="update.is_markdown && update.markdown_content"
                                    v-html="renderMarkdown(update.markdown_content)"></div>

                                <!-- Plain Text Content -->
                                <div v-else class="whitespace-pre-wrap text-gray-700">
                                    {{ update.content }}
                                </div>
                            </div>

                            <!-- Action Footer -->
                            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between items-center">
                                <div>
                                    <Button v-if="!update.is_read" @click="markAsRead" label="Mark as Read"
                                        icon="pi pi-check" severity="success" />
                                    <div v-else class="flex items-center gap-2 text-green-600 font-medium">
                                        <i class="pi pi-check-circle"></i>
                                        <span>Already Read</span>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <Button @click="goBack" label="Back to List" severity="secondary" outlined />
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
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'



import { marked } from 'marked'

// Configure marked options
marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: false,
    mangle: false
})

const props = defineProps({
    id: {
        type: [String, Number],
        required: true
    }
})

// Data
const update = ref(null)
const loading = ref(true)
const error = ref(null)

// Methods
const fetchUpdate = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get('/api/system-updates')
        const allUpdates = response.data.updates

        // Find the specific update by ID
        const foundUpdate = allUpdates.find(u => u.id === parseInt(props.id))

        if (!foundUpdate) {
            error.value = 'Update not found or you do not have permission to view it.'
        } else {
            update.value = foundUpdate

            // Auto-mark as read if not already read
            if (!foundUpdate.is_read) {
                await markAsRead(false) // Don't navigate after marking as read
            }
        }
    } catch (err) {
        console.error('Error fetching update:', err)
        error.value = 'Failed to load the update. Please try again.'
    } finally {
        loading.value = false
    }
}

const markAsRead = async (showMessage = true) => {
    try {
        await axios.post(`/api/system-updates/${props.id}/mark-read`)
        if (update.value) {
            update.value.is_read = true
        }
    } catch (err) {
        console.error('Error marking update as read:', err)
        if (showMessage) {
            alert('Failed to mark update as read.')
        }
    }
}

const goBack = () => {
    router.visit(route('system-updates.index'))
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

// Lifecycle
onMounted(() => {
    fetchUpdate()
})
</script>

<style scoped>
/* Enhanced Markdown prose styling for detailed view */
.prose {
    color: #374151;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3),
.prose :deep(h4),
.prose :deep(h5),
.prose :deep(h6) {
    color: #111827;
    font-weight: 600;
    margin-top: 1.5em;
    margin-bottom: 0.75em;
    line-height: 1.25;
}

.prose :deep(h1) {
    font-size: 2em;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.3em;
}

.prose :deep(h2) {
    font-size: 1.5em;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.3em;
}

.prose :deep(h3) {
    font-size: 1.25em;
}

.prose :deep(h4) {
    font-size: 1em;
}

.prose :deep(p) {
    margin-top: 1em;
    margin-bottom: 1em;
    line-height: 1.75;
}

.prose :deep(ul),
.prose :deep(ol) {
    padding-left: 2em;
    margin-top: 1em;
    margin-bottom: 1em;
}

.prose :deep(li) {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose :deep(li>p) {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose :deep(code) {
    background-color: #f3f4f6;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: #e83e8c;
}

.prose :deep(pre) {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1.5rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-top: 1.5em;
    margin-bottom: 1.5em;
    line-height: 1.5;
}

.prose :deep(pre code) {
    background-color: transparent;
    padding: 0;
    color: inherit;
    font-size: 0.875em;
}

.prose :deep(blockquote) {
    border-left: 4px solid #3b82f6;
    padding-left: 1.5em;
    padding-top: 0.5em;
    padding-bottom: 0.5em;
    font-style: italic;
    color: #6b7280;
    margin: 1.5em 0;
    background-color: #f9fafb;
}

.prose :deep(blockquote p) {
    margin: 0.5em 0;
}

.prose :deep(a) {
    color: #3b82f6;
    text-decoration: underline;
    font-weight: 500;
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
    margin: 1.5em 0;
}

.prose :deep(table th),
.prose :deep(table td) {
    border: 1px solid #d1d5db;
    padding: 0.75em 1em;
    text-align: left;
}

.prose :deep(table th) {
    background-color: #f3f4f6;
    font-weight: 600;
}

.prose :deep(table tr:nth-child(even)) {
    background-color: #f9fafb;
}

.prose :deep(hr) {
    border: 0;
    border-top: 2px solid #e5e7eb;
    margin: 2em 0;
}

.prose :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.prose :deep(dl) {
    margin: 1em 0;
}

.prose :deep(dt) {
    font-weight: 600;
    margin-top: 1em;
}

.prose :deep(dd) {
    margin-left: 2em;
    margin-top: 0.5em;
}

/* Task lists */
.prose :deep(input[type="checkbox"]) {
    margin-right: 0.5em;
}
</style>
