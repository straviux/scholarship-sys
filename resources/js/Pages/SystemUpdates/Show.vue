<template>
    <AdminLayout>
        <template #header>
            System Update Details
        </template>

        <div class="py-4 short:py-2">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <AppIcon name="spinner" class="text-4xl short:text-2xl text-blue-500" />
                    <p class="mt-4 text-gray-600">Loading update...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-12">
                    <AppIcon name="exclamation-circle" class="text-6xl text-red-500" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Error Loading Update</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ error }}</p>
                    <Button @click="$inertia.visit(route('system-updates.index'))" label="Back to Updates"
                        class="mt-4" />
                </div>

                <!-- Update Content -->
                <div v-else-if="update" class="space-y-4 short:space-y-2">
                    <!-- Back Button -->
                    <div>
                        <AppButton @click="goBack" label="Back" icon="arrow-left" text size="small" />
                    </div>

                    <!-- Update Card -->
                    <Card class="w-full">
                        <template #content>
                            <!-- Header Section -->
                            <div class="border-b border-gray-200 pb-4 short:pb-2 mb-4 short:mb-2">
                                <!-- Priority & Type Badges -->
                                <div class="flex items-center gap-2 mb-4 flex-wrap">
                                    <Tag :value="update.priority" :severity="getPrioritySeverity(update.priority)" />
                                    <Tag :value="update.type" :severity="getTypeSeverity(update.type)" />
                                    <Tag v-if="update.is_markdown" value="Markdown" severity="secondary"
                                        icon="file-edit" />
                                    <Tag v-if="!update.is_read" value="Unread" severity="info" icon="circle-fill" />
                                </div>

                                <!-- Title -->
                                <h1 class="text-3xl short:text-xl font-bold text-gray-900 mb-4">{{ update.title }}</h1>

                                <!-- Meta Information -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <AppIcon name="user" />
                                        <span>{{ update.created_by }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <AppIcon name="calendar" />
                                        <span>{{ update.created_at }}</span>
                                    </div>
                                    <div v-if="update.is_read" class="flex items-center gap-2 text-green-600">
                                        <AppIcon name="check-circle" />
                                        <span>Read</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="ios-markdown-prose ios-markdown-prose--detail max-w-none">
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
                                    <AppButton v-if="!update.is_read" @click="markAsRead" label="Mark as Read"
                                        icon="check" severity="success" />
                                    <div v-else class="flex items-center gap-2 text-green-600 font-medium">
                                        <AppIcon name="check-circle" />
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
import AppIcon from '@/Components/ui/AppIcon.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import axios from 'axios'



import { marked } from 'marked'
import { sanitizeHtml } from '@/utils/sanitize'

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
    return sanitizeHtml(marked.parse(markdown))
}

// Lifecycle
onMounted(() => {
    fetchUpdate()
})
</script>

