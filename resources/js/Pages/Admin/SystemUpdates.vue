<template>
    <AdminLayout>
        <template #header>
            System Updates Management
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header with Create Button -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">System Updates</h2>
                    <Button v-if="hasRole('administrator')" @click="showCreateModal = true" label="Create Update"
                        icon="pi pi-plus" severity="info" size="small" />
                </div>

                <!-- Updates List -->
                <Panel header="System Updates" class="w-full">
                    <div v-if="updates.length === 0" class="text-center py-8">
                        <Message severity="info" :closable="false">
                            <p>No system updates found.</p>
                        </Message>
                    </div>

                    <div v-else class="space-y-4">
                        <Card v-for="update in updates" :key="update.id" class="w-full">
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
                                                icon="pi pi-file-edit" class="text-xs" />
                                        </div>
                                        <p class="text-gray-600 mb-3">{{ update.content }}</p>
                                        <div class="text-sm text-gray-500">
                                            Created {{ update.created_at }} by {{ update.created_by_name }}
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2 ml-4">
                                        <Tag :value="update.is_active ? 'Active' : 'Inactive'"
                                            :severity="update.is_active ? 'success' : 'danger'" />
                                        <div v-if="hasRole('administrator')" class="flex items-center space-x-1">
                                            <Button v-if="update.is_active" @click="deactivateUpdate(update)"
                                                label="Deactivate" severity="warning" size="small" outlined />
                                            <Button v-else @click="reactivateUpdate(update)" label="Reactivate"
                                                severity="success" size="small" outlined />
                                            <Button @click="deleteUpdate(update)" label="Delete" severity="danger"
                                                size="small" outlined />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </Panel>
            </div>
        </div>

        <!-- Create Update Dialog -->
        <Dialog v-model:visible="showCreateModal" modal header="Create System Update" :style="{ width: '56rem' }"
            :breakpoints="{ '960px': '90vw', '640px': '95vw' }" class="create-update-dialog">

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

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button @click="showCreateModal = false" label="Cancel" severity="secondary" outlined
                        size="small" />
                    <Button @click="createUpdate" :label="isCreating ? 'Creating...' : 'Create Update'" severity="info"
                        :disabled="isCreating" size="small" />
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" modal header="Confirm Deletion" :style="{ width: '28rem' }"
            :breakpoints="{ '960px': '90vw', '640px': '95vw' }" class="delete-confirmation-dialog">

            <div class="flex items-start space-x-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl mt-1"></i>
                <div class="flex-1">
                    <p class="text-gray-700 mb-3">
                        Are you sure you want to permanently delete this system update?
                    </p>
                    <div class="bg-gray-50 p-3 rounded-md mb-3">
                        <h4 class="font-medium text-gray-900 mb-1">{{ updateToDelete?.title }}</h4>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ updateToDelete?.content }}</p>
                    </div>
                    <p class="text-sm text-red-600 font-medium">
                        This action cannot be undone and will permanently remove the update and all associated data.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button @click="cancelDelete" label="Cancel" severity="secondary" outlined size="small" />
                    <Button @click="confirmDelete" :label="isDeleting ? 'Deleting...' : 'Delete'" severity="danger"
                        icon="pi pi-trash" :disabled="isDeleting" size="small" />
                </div>
            </template>
        </Dialog>

        <!-- Deactivate Confirmation Dialog -->
        <Dialog v-model:visible="showDeactivateDialog" modal header="Confirm Deactivation" :style="{ width: '28rem' }"
            :breakpoints="{ '960px': '90vw', '640px': '95vw' }" class="deactivate-confirmation-dialog">

            <div class="flex items-start space-x-3">
                <i class="pi pi-exclamation-triangle text-orange-500 text-2xl mt-1"></i>
                <div class="flex-1">
                    <p class="text-gray-700 mb-3">
                        Are you sure you want to deactivate this system update?
                    </p>
                    <div class="bg-gray-50 p-3 rounded-md mb-3">
                        <h4 class="font-medium text-gray-900 mb-1">{{ updateToDeactivate?.title }}</h4>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ updateToDeactivate?.content }}</p>
                    </div>
                    <p class="text-sm text-orange-600">
                        This update will no longer be visible to users, but can be reactivated later.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button @click="cancelDeactivate" label="Cancel" severity="secondary" outlined size="small" />
                    <Button @click="confirmDeactivate" :label="isDeactivating ? 'Deactivating...' : 'Deactivate'"
                        severity="warning" icon="pi pi-eye-slash" :disabled="isDeactivating" size="small" />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePermission } from '@/composable/permissions'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Panel from 'primevue/panel'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import { MdEditor } from 'md-editor-v3'
import 'md-editor-v3/lib/style.css'

// Composables
const { hasRole, hasPermission } = usePermission()

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

const fetchUpdates = async () => {
    try {
        const response = await axios.get('/api/system-updates')

        // Since is_active field is undefined from backend, set all updates as active by default
        // TODO: Backend needs to include is_active field in the response
        updates.value = response.data.updates.map(update => ({
            ...update,
            is_active: true // Default to active since backend doesn't provide this field
        }))

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
            // Optionally provide a plain text summary
            payload.content = form.value.content || form.value.markdown_content.substring(0, 200)
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
        // Since backend doesn't support update endpoints, update client-side state
        // Note: This will reset when page refreshes until backend endpoints are implemented
        const updateIndex = updates.value.findIndex(u => u.id === updateToDeactivate.value.id)
        if (updateIndex !== -1) {
            updates.value[updateIndex].is_active = false
        }

        showDeactivateDialog.value = false
        updateToDeactivate.value = null

        console.log('Update deactivated (client-side only - will reset on page refresh)')
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
        // Since backend doesn't support update endpoints, update client-side state
        // Note: This will reset when page refreshes until backend endpoints are implemented
        const updateIndex = updates.value.findIndex(u => u.id === update.id)
        if (updateIndex !== -1) {
            updates.value[updateIndex].is_active = true
        }

        console.log('Update reactivated (client-side only - will reset on page refresh)')
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

// Lifecycle
onMounted(() => {
    fetchUpdates()
})
</script>

<style scoped>
/* PrimeVue Dialog custom styles */
:deep(.create-update-dialog .p-dialog-header) {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
}

:deep(.create-update-dialog .p-dialog-content) {
    padding: 0 1.5rem 1rem 1.5rem;
}

:deep(.create-update-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}

/* Confirmation dialogs */
:deep(.delete-confirmation-dialog .p-dialog-content),
:deep(.deactivate-confirmation-dialog .p-dialog-content) {
    padding: 1.5rem;
}

:deep(.delete-confirmation-dialog .p-dialog-footer),
:deep(.deactivate-confirmation-dialog .p-dialog-footer) {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}

/* Custom spacing for form elements */
:deep(.p-inputtext),
:deep(.p-dropdown),
:deep(.p-textarea) {
    width: 100%;
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>