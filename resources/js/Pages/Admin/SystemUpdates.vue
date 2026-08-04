<template>
    <AdminLayout>
        <AdminPageShell title="System Updates"
            description="Create, publish, deactivate, and retire system-wide announcements from the shared iOS-styled communications feed."
            icon="megaphone" eyebrow="Communications">
            <template #meta>
                <span>{{ updates.length }} updates</span>
            </template>
            <template #actions>
                <AppButton v-if="hasRole('administrator')" @click="showCreateModal = true" label="Create Update"
                    icon="plus" severity="info" rounded size="small" />
            </template>

            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <!-- Stats strip -->
                <div class="flex items-end gap-6 short:gap-3 short:mb-2 px-3 py-2 text-sm opacity-75">
                    <span class="font-semibold text-blue-600">{{ updates.length }} updates</span>
                    <span class="text-gray-300">|</span>
                    <span class="font-semibold text-green-600">{{updates.filter(u => u.is_active).length}}
                        active</span>
                </div>

                <div class="px-2 pb-3">
                        <div v-if="updates.length === 0" class="text-center py-8 text-gray-500">
                            No system updates found
                        </div>

                        <div v-else class="space-y-4">
                            <Card v-for="update in updates" :key="update.id"
                                class="w-full cursor-pointer hover:shadow-lg transition-shadow"
                                @click="viewUpdate(update.id)">
                                <template #content>
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-3">
                                                <h3 class="text-lg font-medium text-gray-900">{{ update.title }}</h3>
                                                <Tag :value="update.priority"
                                                    :severity="getPrioritySeverity(update.priority)" class="text-xs" />
                                                <Tag :value="update.type" :severity="getTypeSeverity(update.type)"
                                                    class="text-xs" />
                                                <Tag v-if="update.is_markdown" value="Markdown" severity="info"
                                                    icon="file-edit" class="text-xs" />
                                            </div>
                                            <!-- Content Preview (truncated) -->
                                            <div v-if="update.is_markdown && update.markdown_content"
                                                class="text-gray-600 mb-3 line-clamp-2">
                                                {{ stripMarkdown(update.markdown_content) }}
                                            </div>
                                            <p v-else class="text-gray-600 mb-3 line-clamp-2">{{ update.content }}</p>
                                            <div class="text-sm text-gray-500">
                                                Created {{ update.created_at }} by {{ update.created_by_name }}
                                            </div>
                                            <p class="text-sm text-blue-600 mt-2 font-medium">Click to view details ➔
                                            </p>
                                        </div>

                                        <div class="flex items-center space-x-2 ml-4" @click.stop>
                                            <Tag :value="update.is_active ? 'Active' : 'Inactive'"
                                                :severity="update.is_active ? 'success' : 'danger'" />
                                            <div v-if="hasRole('administrator')" class="flex items-center gap-1">
                                                <AppButton v-if="update.is_active" @click="deactivateUpdate(update)"
                                                    icon="power-off" label="Deactivate" severity="warning" outlined
                                                    rounded size="small" />
                                                <AppButton v-else @click="reactivateUpdate(update)" icon="rotate-ccw"
                                                    label="Reactivate" severity="success" outlined rounded
                                                    size="small" />
                                                <AppButton @click="deleteUpdate(update)" icon="trash" severity="danger"
                                                    outlined rounded size="small" v-tooltip.top="'Delete'" />
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>
                </div>
            </Panel>
        </AdminPageShell>

        <!-- Create Update Dialog -->
        <IosModal :visible="showCreateModal" title="Create System Update" width="56rem" max-width="95vw"
            body-style="padding: 16px;" @update:visible="showCreateModal = $event">

            <form @submit.prevent="createUpdate">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <InputText v-model="form.title" type="text" required class="w-full"
                            placeholder="Enter update title" />
                    </div>

                    <!-- Markdown Toggle -->
                    <div>
                        <label class="flex items-center">
                            <Checkbox v-model="form.is_markdown" binary @change="onMarkdownToggle" />
                            <span class="ml-2 text-sm text-gray-700">Use Markdown Format</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            Enable rich text formatting with headings, lists, code blocks, and more
                        </p>
                    </div>

                    <!-- Markdown Editor -->
                    <div v-if="form.is_markdown">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Markdown Content</label>
                        <MdEditor v-model="form.markdown_content" :language="'en-US'" :preview-theme="'github'"
                            :code-theme="'github'" :toolbars-exclude="['github', 'save', 'htmlPreview', 'catalog']"
                            :placeholder="'Enter your markdown content here...'" style="height: 400px;" />
                        <p class="text-xs text-gray-500 mt-1">
                            Preview on the right shows how the content will appear to users
                        </p>
                    </div>

                    <!-- Plain Text Editor -->
                    <div v-else>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <Textarea v-model="form.content" required rows="6" class="w-full"
                            placeholder="Enter update content" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <Select v-model="form.type" :options="typeOptions" option-label="label" option-value="value"
                                class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <Select v-model="form.priority" :options="priorityOptions" option-label="label"
                                option-value="value" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <Checkbox v-model="form.is_global" binary />
                            <span class="ml-2 text-sm text-gray-700">Visible to all users</span>
                        </label>
                    </div>
                </div>
            </form>

            <div class="flex justify-end space-x-2 pt-4 mt-4 border-t border-gray-200 dark:border-white/10">
                <Button @click="showCreateModal = false" label="Cancel" severity="secondary" outlined
                    size="small" />
                <Button @click="createUpdate" :label="isCreating ? 'Creating...' : 'Create Update'" severity="info"
                    :disabled="isCreating" size="small" />
            </div>
        </IosModal>

        <!-- Delete Confirmation Dialog -->
        <IosConfirmDialog
            :visible="showDeleteDialog"
            title="Confirm Deletion"
            width="450px"
            message="Are you sure you want to permanently delete this system update? This action cannot be undone and will permanently remove the update and all associated data."
            :data="updateToDelete ? [{ label: 'Title', value: updateToDelete.title, color: '#FF3B30' }] : []"
            :loading="isDeleting"
            @accept="confirmDelete"
            @close="cancelDelete"
        />

        <!-- Deactivate Confirmation Dialog -->
        <IosConfirmDialog
            :visible="showDeactivateDialog"
            title="Confirm Deactivation"
            width="450px"
            message="Are you sure you want to deactivate this system update? This update will no longer be visible to users, but can be reactivated later."
            icon-color="#f97316"
            action-class=""
            :data="updateToDeactivate ? [{ label: 'Title', value: updateToDeactivate.title }] : []"
            :loading="isDeactivating"
            @accept="confirmDeactivate"
            @close="cancelDeactivate"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePermission } from '@/composable/permissions'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AdminPageShell from '@/Components/admin/AdminPageShell.vue'
import IosModal from '@/Components/ui/IosModal.vue'
import IosConfirmDialog from '@/Components/ui/IosConfirmDialog.vue'
import axios from 'axios'










import { MdEditor } from 'md-editor-v3'
import 'md-editor-v3/lib/style.css'
import { marked } from 'marked'

// Configure marked options for security
marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: false,
    mangle: false
})

// Composables
const { hasRole } = usePermission()

// Data
const updates = ref([])
const showCreateModal = ref(false)
const isCreating = ref(false)

// Delete confirmation dialog
const showDeleteDialog = ref(false)
const updateToDelete = ref(null)
const isDeleting = ref(false)

// Deactivate confirmation dialog
const showDeactivateDialog = ref(false)
const updateToDeactivate = ref(null)
const isDeactivating = ref(false)

const form = ref({
    title: '',
    content: '',
    markdown_content: '',
    is_markdown: false,
    type: 'info',
    priority: 'normal',
    is_global: true
})

// Dropdown options
const typeOptions = ref([
    { label: 'Info', value: 'info' },
    { label: 'Warning', value: 'warning' },
    { label: 'Success', value: 'success' },
    { label: 'Error', value: 'error' }
])

const priorityOptions = ref([
    { label: 'Low', value: 'low' },
    { label: 'Normal', value: 'normal' },
    { label: 'High', value: 'high' },
    { label: 'Urgent', value: 'urgent' }
])

// Methods
const viewUpdate = (id) => {
    router.visit(route('admin.system-updates.show', { id }))
}

const onMarkdownToggle = () => {
    // When toggling markdown mode, preserve content between formats
    if (form.value.is_markdown) {
        // Switching to markdown: copy content to markdown_content if empty
        if (!form.value.markdown_content && form.value.content) {
            form.value.markdown_content = form.value.content
        }
    } else {
        // Switching to plain text: copy markdown_content to content if empty
        if (!form.value.content && form.value.markdown_content) {
            form.value.content = form.value.markdown_content
        }
    }
}

const stripMarkdown = (markdown) => {
    if (!markdown) return ''
    // Remove markdown formatting for preview
    return markdown
        .replace(/#{1,6}\s/g, '') // headers
        .replace(/\*\*(.+?)\*\*/g, '$1') // bold
        .replace(/\*(.+?)\*/g, '$1') // italic
        .replace(/\[(.+?)\]\(.+?\)/g, '$1') // links
        .replace(/`(.+?)`/g, '$1') // inline code
        .replace(/```[\s\S]*?```/g, '') // code blocks
        .trim()
}

const fetchUpdates = async () => {
    try {
        const response = await axios.get('/api/admin/system-updates')
        updates.value = response.data.updates
    } catch (error) {
        console.error('Error fetching updates:', error)
    }
}

const createUpdate = async () => {
    // Validate required fields
    if (!form.value.title) {
        return
    }

    // Validate content based on markdown mode
    if (form.value.is_markdown && !form.value.markdown_content) {
        return
    }
    if (!form.value.is_markdown && !form.value.content) {
        return
    }

    isCreating.value = true
    try {
        // Prepare payload based on markdown mode
        const payload = {
            title: form.value.title,
            type: form.value.type,
            priority: form.value.priority,
            is_global: form.value.is_global,
            is_markdown: form.value.is_markdown
        }

        if (form.value.is_markdown) {
            payload.markdown_content = form.value.markdown_content
            // Only send plain text summary, not the full markdown
            payload.content = form.value.markdown_content.replace(/[#*`\[\]()]/g, '').substring(0, 200) + '...'
        } else {
            payload.content = form.value.content
        }

        await axios.post('/api/system-updates', payload)
        showCreateModal.value = false

        // Reset form
        form.value = {
            title: '',
            content: '',
            markdown_content: '',
            is_markdown: false,
            type: 'info',
            priority: 'normal',
            is_global: true
        }

        await fetchUpdates()
        // Optionally show success message
    } catch (error) {
        console.error('Error creating update:', error)
        // Optionally show error message
    } finally {
        isCreating.value = false
    }
}

const deactivateUpdate = (update) => {
    updateToDeactivate.value = update
    showDeactivateDialog.value = true
}

const confirmDeactivate = async () => {
    isDeactivating.value = true
    try {
        await axios.put(`/api/system-updates/${updateToDeactivate.value.id}/deactivate`)
        await fetchUpdates()
        showDeactivateDialog.value = false
        updateToDeactivate.value = null
    } catch (error) {
        console.error('Error deactivating update:', error)
    } finally {
        isDeactivating.value = false
    }
}

const cancelDeactivate = () => {
    showDeactivateDialog.value = false
    updateToDeactivate.value = null
}

const reactivateUpdate = async (update) => {
    try {
        await axios.put(`/api/system-updates/${update.id}/reactivate`)
        await fetchUpdates()
    } catch (error) {
        console.error('Error reactivating update:', error)
    }
}

const deleteUpdate = (update) => {
    updateToDelete.value = update
    showDeleteDialog.value = true
}

const confirmDelete = async () => {
    isDeleting.value = true
    try {
        await axios.delete(`/api/system-updates/${updateToDelete.value.id}`)
        await fetchUpdates()
        showDeleteDialog.value = false
        updateToDelete.value = null
    } catch (error) {
        console.error('Error deleting update:', error)
    } finally {
        isDeleting.value = false
    }
}

const cancelDelete = () => {
    showDeleteDialog.value = false
    updateToDelete.value = null
}

const getPrioritySeverity = (priority) => {
    const severities = {
        low: 'info',
        normal: 'info',
        high: 'warning',
        urgent: 'danger'
    }
    return severities[priority] || 'info'
}

const getTypeSeverity = (type) => {
    const severities = {
        info: 'info',
        warning: 'warning',
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
    fetchUpdates()
})
</script>

