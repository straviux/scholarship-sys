<template>
    <AdminLayout>
        <template #header>
            Update Details
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
                    <Button @click="$inertia.visit(route('admin.system-updates'))" label="Back to Management"
                        class="mt-4" />
                </div>

                <!-- Update Content -->
                <div v-else-if="update" class="space-y-6">
                    <!-- Back Button -->
                    <div>
                        <Button @click="goBack" label="Back to Management" icon="pi pi-arrow-left" text size="small" />
                    </div>

                    <!-- Update Card -->
                    <Card class="w-full">
                        <template #content>
                            <!-- Admin Info Banner -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-info-circle text-blue-600 text-xl mt-0.5"></i>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-blue-900 mb-1">Administrator View</h4>
                                        <div class="text-sm text-blue-800 space-y-1">
                                            <p><strong>ID:</strong> {{ update.id }}</p>
                                            <p><strong>Status:</strong>
                                                <Tag :value="update.is_active ? 'Active' : 'Inactive'"
                                                    :severity="update.is_active ? 'success' : 'danger'" class="ml-2" />
                                            </p>
                                            <p><strong>Global:</strong> {{ update.is_global ? 'Yes' : 'No' }}</p>
                                            <p v-if="update.expires_at"><strong>Expires:</strong> {{ update.expires_at
                                            }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Header Section -->
                            <div class="border-b border-gray-200 pb-6 mb-6">
                                <!-- Priority & Type Badges -->
                                <div class="flex items-center gap-2 mb-4 flex-wrap">
                                    <Tag :value="update.priority" :severity="getPrioritySeverity(update.priority)" />
                                    <Tag :value="update.type" :severity="getTypeSeverity(update.type)" />
                                    <Tag v-if="update.is_markdown" value="Markdown" severity="secondary"
                                        icon="pi pi-file-edit" />
                                </div>

                                <!-- Title -->
                                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ update.title }}</h1>

                                <!-- Meta Information -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-user"></i>
                                        <span>{{ update.created_by_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{ update.created_at }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Content</h3>
                                <div class="prose prose-lg max-w-none">
                                    <!-- Markdown Content -->
                                    <div v-if="update.is_markdown && update.markdown_content"
                                        v-html="renderMarkdown(update.markdown_content)"></div>

                                    <!-- Plain Text Content -->
                                    <div v-else class="whitespace-pre-wrap text-gray-700">
                                        {{ update.content }}
                                    </div>
                                </div>
                            </div>

                            <!-- Raw Content Section (for markdown) -->
                            <div v-if="update.is_markdown && update.markdown_content"
                                class="mt-8 pt-6 border-t border-gray-200">
                                <details>
                                    <summary
                                        class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-gray-900 mb-2">
                                        View Raw Markdown
                                    </summary>
                                    <pre
                                        class="bg-gray-50 p-4 rounded-lg text-sm overflow-x-auto">{{ update.markdown_content }}</pre>
                                </details>
                            </div>

                            <!-- Action Footer -->
                            <div
                                class="mt-8 pt-6 border-t border-gray-200 flex justify-between items-center flex-wrap gap-4">
                                <div class="flex gap-2 flex-wrap">
                                    <Button v-if="update.is_active" @click="deactivateUpdate" label="Deactivate"
                                        severity="warning" icon="pi pi-eye-slash" outlined size="small" />
                                    <Button v-else @click="reactivateUpdate" label="Reactivate" severity="success"
                                        icon="pi pi-eye" outlined size="small" />
                                    <Button @click="deleteUpdate" label="Delete" severity="danger" icon="pi pi-trash"
                                        outlined size="small" />
                                </div>

                                <Button @click="goBack" label="Back to Management" severity="secondary" outlined />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" modal header="Confirm Deletion" :style="{ width: '28rem' }">
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl mt-1"></i>
                <div>
                    <p class="text-gray-700 mb-2">Are you sure you want to delete this system update?</p>
                    <p class="text-sm text-red-600 font-medium">This action cannot be undone.</p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button @click="showDeleteDialog = false" label="Cancel" severity="secondary" outlined
                        size="small" />
                    <Button @click="confirmDelete" :label="isDeleting ? 'Deleting...' : 'Delete'" severity="danger"
                        icon="pi pi-trash" :disabled="isDeleting" size="small" />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
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
const showDeleteDialog = ref(false)
const isDeleting = ref(false)

// Methods
const fetchUpdate = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get('/api/admin/system-updates')
        const allUpdates = response.data.updates

        // Find the specific update by ID
        const foundUpdate = allUpdates.find(u => u.id === parseInt(props.id))

        if (!foundUpdate) {
            error.value = 'Update not found.'
        } else {
            update.value = foundUpdate
        }
    } catch (err) {
        console.error('Error fetching update:', err)
        error.value = 'Failed to load the update. Please try again.'
    } finally {
        loading.value = false
    }
}

const deactivateUpdate = async () => {
    try {
        await axios.delete(`/api/system-updates/${props.id}`)
        update.value.is_active = false
        alert('Update deactivated successfully.')
    } catch (err) {
        console.error('Error deactivating update:', err)
        alert('Failed to deactivate update.')
    }
}

const reactivateUpdate = async () => {
    // Note: This would require a new API endpoint
    alert('Reactivate functionality needs to be implemented in the backend.')
}

const deleteUpdate = () => {
    showDeleteDialog.value = true
}

const confirmDelete = async () => {
    isDeleting.value = true
    try {
        await axios.delete(`/api/system-updates/${props.id}`)
        alert('Update deleted successfully.')
        goBack()
    } catch (err) {
        console.error('Error deleting update:', err)
        alert('Failed to delete update.')
    } finally {
        isDeleting.value = false
        showDeleteDialog.value = false
    }
}

const goBack = () => {
    router.visit(route('admin.system-updates'))
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
/* Same prose styling as user view */
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

.prose :deep(p) {
    margin-top: 1em;
    margin-bottom: 1em;
    line-height: 1.75;
}

.prose :deep(code) {
    background-color: #f3f4f6;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
    font-family: ui-monospace, monospace;
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
}

.prose :deep(pre code) {
    background-color: transparent;
    padding: 0;
    color: inherit;
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

.prose :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
}
</style>
