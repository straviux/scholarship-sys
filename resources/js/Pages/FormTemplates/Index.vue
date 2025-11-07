<template>

    <Head title="Forms and Templates" />

    <AdminLayout>
        <template #header>
            Forms and Templates
        </template>

        <div class="space-y-6">
            <!-- Header Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-file text-xl"></i>
                        <span class="font-semibold text-lg">Forms and Templates Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Upload and manage downloadable forms and template files
                    </div>
                    <Button v-if="hasPermission('forms-templates.upload')" icon="pi pi-upload" label="Upload File"
                        severity="success" raised @click="openUploadDialog" />
                </div>
            </Panel>

            <!-- Tabs for Categories -->
            <Card>
                <template #content>
                    <TabView v-model:activeIndex="activeTabIndex">
                        <TabPanel header="All Files">
                            <DataTable :value="getAllTemplates()" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
                                paginator :rowHover="true" stripedRows showGridlines>
                                <Column field="title" header="Title" sortable style="min-width: 200px">
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-2">
                                            <i :class="getFileIcon(slotProps.data.file_type)" class="text-xl"></i>
                                            <span class="font-semibold">{{ slotProps.data.title }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="description" header="Description" style="min-width: 250px">
                                    <template #body="slotProps">
                                        <span class="text-sm text-gray-600">{{ slotProps.data.description || '-'
                                        }}</span>
                                    </template>
                                </Column>

                                <Column field="category" header="Category" sortable style="width: 150px">
                                    <template #body="slotProps">
                                        <Tag v-if="slotProps.data.category" :value="slotProps.data.category"
                                            severity="info" />
                                        <span v-else class="text-gray-400">Uncategorized</span>
                                    </template>
                                </Column>

                                <Column field="file_size" header="Size" sortable style="width: 100px">
                                    <template #body="slotProps">
                                        {{ formatFileSize(slotProps.data.file_size) }}
                                    </template>
                                </Column>

                                <Column field="download_count" header="Downloads" sortable style="width: 120px">
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-download text-sm text-gray-500"></i>
                                            <span>{{ slotProps.data.download_count }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="is_active" header="Status" style="width: 100px">
                                    <template #body="slotProps">
                                        <Tag :value="slotProps.data.is_active ? 'Active' : 'Inactive'"
                                            :severity="slotProps.data.is_active ? 'success' : 'danger'" />
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 250px">
                                    <template #body="slotProps">
                                        <div class="flex gap-2">
                                            <Button v-if="hasPermission('forms-templates.view')" icon="pi pi-eye"
                                                severity="secondary" text size="small"
                                                @click="viewTemplate(slotProps.data)" v-tooltip.top="'View'" />
                                            <Button v-if="hasPermission('forms-templates.download')"
                                                icon="pi pi-download" severity="info" text size="small"
                                                @click="downloadTemplate(slotProps.data)" v-tooltip.top="'Download'" />
                                            <Button v-if="hasPermission('forms-templates.edit')" icon="pi pi-pencil"
                                                severity="warning" text size="small"
                                                @click="openEditDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                            <Button v-if="hasPermission('forms-templates.delete')" icon="pi pi-trash"
                                                severity="danger" text size="small"
                                                @click="confirmDelete(slotProps.data)" v-tooltip.top="'Delete'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                        <TabPanel v-for="category in categories" :key="category.value" :header="category.label">
                            <DataTable :value="getTemplatesByCategory(category.value)" :rows="10"
                                :rowsPerPageOptions="[5, 10, 20, 50]" paginator :rowHover="true" stripedRows
                                showGridlines>
                                <Column field="title" header="Title" sortable style="min-width: 200px">
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-2">
                                            <i :class="getFileIcon(slotProps.data.file_type)" class="text-xl"></i>
                                            <span class="font-semibold">{{ slotProps.data.title }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="description" header="Description" style="min-width: 250px">
                                    <template #body="slotProps">
                                        <span class="text-sm text-gray-600">{{ slotProps.data.description || '-'
                                        }}</span>
                                    </template>
                                </Column>

                                <Column field="file_size" header="Size" sortable style="width: 100px">
                                    <template #body="slotProps">
                                        {{ formatFileSize(slotProps.data.file_size) }}
                                    </template>
                                </Column>

                                <Column field="download_count" header="Downloads" sortable style="width: 120px">
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-download text-sm text-gray-500"></i>
                                            <span>{{ slotProps.data.download_count }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="is_active" header="Status" style="width: 100px">
                                    <template #body="slotProps">
                                        <Tag :value="slotProps.data.is_active ? 'Active' : 'Inactive'"
                                            :severity="slotProps.data.is_active ? 'success' : 'danger'" />
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 250px">
                                    <template #body="slotProps">
                                        <div class="flex gap-2">
                                            <Button v-if="hasPermission('forms-templates.view')" icon="pi pi-eye"
                                                severity="secondary" text size="small"
                                                @click="viewTemplate(slotProps.data)" v-tooltip.top="'View'" />
                                            <Button v-if="hasPermission('forms-templates.download')"
                                                icon="pi pi-download" severity="info" text size="small"
                                                @click="downloadTemplate(slotProps.data)" v-tooltip.top="'Download'" />
                                            <Button v-if="hasPermission('forms-templates.edit')" icon="pi pi-pencil"
                                                severity="warning" text size="small"
                                                @click="openEditDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                            <Button v-if="hasPermission('forms-templates.delete')" icon="pi pi-trash"
                                                severity="danger" text size="small"
                                                @click="confirmDelete(slotProps.data)" v-tooltip.top="'Delete'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>
                    </TabView>
                </template>
            </Card>
        </div>

        <!-- Upload/Edit Dialog -->
        <Dialog v-model:visible="showDialog"
            :header="dialogMode === 'upload' ? 'Upload Form/Template' : 'Edit Form/Template'" :modal="true"
            :style="{ width: '600px' }">
            <div class="space-y-4 pt-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <InputText id="title" v-model="form.title" class="w-full"
                        :class="{ 'p-invalid': form.errors.title }" />
                    <small class="text-red-500" v-if="form.errors.title">{{ form.errors.title }}</small>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Textarea id="description" v-model="form.description" rows="3" class="w-full"
                        :class="{ 'p-invalid': form.errors.description }" />
                    <small class="text-red-500" v-if="form.errors.description">{{ form.errors.description }}</small>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <Select id="category" v-model="form.category" :options="categories" optionLabel="label"
                        optionValue="value" placeholder="Select a category" class="w-full" showClear
                        :class="{ 'p-invalid': form.errors.category }" />
                    <small class="text-red-500" v-if="form.errors.category">{{ form.errors.category }}</small>
                </div>

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                        File {{ dialogMode === 'edit' ? '(Leave empty to keep current file)' : '*' }}
                    </label>
                    <input type="file" id="file" @change="handleFileChange" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100" />
                    <small class="text-gray-500">Max file size: 10MB</small>
                    <small class="text-red-500 block" v-if="form.errors.file">{{ form.errors.file }}</small>
                </div>

                <div v-if="dialogMode === 'edit' && editingTemplate">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current File</label>
                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                        <i :class="getFileIcon(editingTemplate.file_type)" class="text-2xl"></i>
                        <span class="font-mono text-sm">{{ editingTemplate.file_name }}</span>
                    </div>
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <InputNumber id="sort_order" v-model="form.sort_order" class="w-full"
                        :class="{ 'p-invalid': form.errors.sort_order }" />
                    <small class="text-red-500" v-if="form.errors.sort_order">{{ form.errors.sort_order }}</small>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox id="is_active" v-model="form.is_active" :binary="true" />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDialog = false" />
                <Button :label="dialogMode === 'upload' ? 'Upload' : 'Update'" severity="primary" @click="submitForm"
                    :loading="form.processing" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" header="Confirm Deletion" :modal="true" :style="{ width: '450px' }">
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
                <div>
                    <p class="mb-2">Are you sure you want to delete this template?</p>
                    <p class="text-sm text-gray-600 font-semibold">{{ templateToDelete?.title }}</p>
                    <p class="text-sm text-red-600 mt-2">This will permanently delete the file.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeleteDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteTemplate" :loading="deleteForm.processing" />
            </template>
        </Dialog>

        <!-- View Dialog -->
        <Dialog v-model:visible="showViewDialog" header="View File" :modal="true"
            :style="{ width: '90vw', maxHeight: '90vh' }" :maximizable="true"
            :contentStyle="{ height: '75vh', overflow: 'hidden' }">
            <div v-if="viewingTemplate" class="h-full flex flex-col overflow-hidden">
                <!-- File Info Header -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 flex-shrink-0">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <i :class="getFileIcon(viewingTemplate.file_type)" class="text-3xl"></i>
                            <div>
                                <h3 class="font-semibold text-lg">{{ viewingTemplate.title }}</h3>
                                <p class="text-sm text-gray-600" v-if="viewingTemplate.description">{{
                                    viewingTemplate.description }}</p>
                                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                    <span><i class="pi pi-file mr-1"></i>{{ viewingTemplate.file_name }}</span>
                                    <span><i class="pi pi-database mr-1"></i>{{
                                        formatFileSize(viewingTemplate.file_size)
                                    }}</span>
                                    <span v-if="viewingTemplate.category">
                                        <i class="pi pi-tag mr-1"></i>{{ viewingTemplate.category }}
                                    </span>
                                    <span><i class="pi pi-download mr-1"></i>{{ viewingTemplate.download_count }}
                                        downloads</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Zoom Controls (for images only) -->
                            <div v-if="viewingTemplate.file_type?.includes('image')"
                                class="flex items-center gap-2 mr-4 border rounded-lg p-1">
                                <Button icon="pi pi-minus" size="small" outlined @click="zoomOut"
                                    :disabled="zoomLevel <= minZoom" />
                                <span class="text-sm font-medium px-2 min-w-[60px] text-center">{{ zoomLevel }}%</span>
                                <Button icon="pi pi-plus" size="small" outlined @click="zoomIn"
                                    :disabled="zoomLevel >= maxZoom" />
                                <Button icon="pi pi-refresh" size="small" outlined @click="resetZoom"
                                    :disabled="zoomLevel === 100" />
                            </div>
                            <Button v-if="hasPermission('forms-templates.download')" icon="pi pi-download"
                                label="Download" severity="info" @click="downloadTemplate(viewingTemplate)" />
                        </div>
                    </div>
                </div>

                <!-- File Preview -->
                <div ref="imageContainerRef"
                    class="flex-1 border rounded-lg overflow-auto bg-white min-h-0 flex items-center justify-center"
                    :class="{ 'select-none': isDragging }"
                    :style="{ cursor: viewingTemplate.file_type?.includes('image') && zoomLevel > 100 ? 'grab' : 'default' }"
                    @wheel="handleWheel" @mousedown="handleMouseDown" @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp" @mouseleave="handleMouseUp">
                    <!-- Image Preview -->
                    <img v-if="viewingTemplate.file_type?.includes('image')" :src="getFileUrl(viewingTemplate)"
                        :alt="viewingTemplate.title"
                        class="object-contain transition-transform duration-200 pointer-events-none" :style="{
                            width: zoomLevel === 100 ? 'auto' : `${zoomLevel}%`,
                            height: zoomLevel === 100 ? 'auto' : `${zoomLevel}%`,
                            maxWidth: zoomLevel === 100 ? '100%' : 'none',
                            maxHeight: zoomLevel === 100 ? '100%' : 'none'
                        }">

                    <!-- PDF and Text Preview -->
                    <iframe v-else-if="canPreview(viewingTemplate.file_type)" :src="getFileUrl(viewingTemplate)"
                        class="w-full h-full" frameborder="0">
                    </iframe>

                    <!-- No Preview Available -->
                    <div v-else class="flex flex-col items-center justify-center h-full text-gray-500 p-8">
                        <i class="pi pi-eye-slash text-6xl mb-4"></i>
                        <p class="text-lg font-semibold">Preview not available</p>
                        <p class="text-sm mb-4">This file type cannot be previewed in the browser</p>
                        <Button v-if="hasPermission('forms-templates.download')" icon="pi pi-download"
                            label="Download to View" severity="info" @click="downloadTemplate(viewingTemplate)" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showViewDialog = false" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Panel from 'primevue/panel';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    templates: Object,
    categories: Array,
});

const { hasPermission } = usePermission();
const activeTabIndex = ref(0);
const showDialog = ref(false);
const showDeleteDialog = ref(false);
const showViewDialog = ref(false);
const dialogMode = ref('upload');
const templateToDelete = ref(null);
const editingTemplate = ref(null);
const viewingTemplate = ref(null);
const zoomLevel = ref(100);
const minZoom = 10;
const maxZoom = 300;

// Dragging state
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const scrollStart = ref({ left: 0, top: 0 });
const imageContainerRef = ref(null);

const form = useForm({
    title: '',
    description: '',
    category: null,
    file: null,
    sort_order: 0,
    is_active: true,
});

const deleteForm = useForm({});

const getAllTemplates = () => {
    const allTemplates = [];
    Object.values(props.templates).forEach(categoryTemplates => {
        allTemplates.push(...categoryTemplates);
    });
    return allTemplates;
};

const getTemplatesByCategory = (category) => {
    return props.templates[category] || [];
};

const openUploadDialog = () => {
    dialogMode.value = 'upload';
    editingTemplate.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showDialog.value = true;
};

const openEditDialog = (template) => {
    dialogMode.value = 'edit';
    editingTemplate.value = template;
    form.clearErrors();
    form.title = template.title;
    form.description = template.description;
    form.category = template.category;
    form.sort_order = template.sort_order;
    form.is_active = template.is_active;
    form.file = null;
    form.id = template.id;
    showDialog.value = true;
};

const handleFileChange = (event) => {
    form.file = event.target.files[0];
};

const submitForm = () => {
    if (dialogMode.value === 'upload') {
        form.post(route('form-templates.store'), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('form-templates.update', form.id), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
            forceFormData: true,
            _method: 'put',
        });
    }
};

const confirmDelete = (template) => {
    templateToDelete.value = template;
    showDeleteDialog.value = true;
};

const viewTemplate = (template) => {
    viewingTemplate.value = template;
    showViewDialog.value = true;
    zoomLevel.value = 100; // Reset zoom when opening
};

const zoomIn = () => {
    if (zoomLevel.value < maxZoom) {
        zoomLevel.value = Math.min(zoomLevel.value + 25, maxZoom);
    }
};

const zoomOut = () => {
    if (zoomLevel.value > minZoom) {
        zoomLevel.value = Math.max(zoomLevel.value - 25, minZoom);
    }
};

const resetZoom = () => {
    zoomLevel.value = 100;
};

// Scroll to zoom
const handleWheel = (event) => {
    if (!viewingTemplate.value?.file_type?.includes('image')) return;

    event.preventDefault();
    const delta = event.deltaY > 0 ? -25 : 25;
    const newZoom = Math.max(minZoom, Math.min(maxZoom, zoomLevel.value + delta));
    zoomLevel.value = newZoom;
};

// Drag to pan
const handleMouseDown = (event) => {
    if (!viewingTemplate.value?.file_type?.includes('image')) return;
    if (zoomLevel.value <= 100) return; // Only enable dragging when zoomed in

    isDragging.value = true;
    dragStart.value = { x: event.clientX, y: event.clientY };

    if (imageContainerRef.value) {
        scrollStart.value = {
            left: imageContainerRef.value.scrollLeft,
            top: imageContainerRef.value.scrollTop
        };
        imageContainerRef.value.style.cursor = 'grabbing';
    }
};

const handleMouseMove = (event) => {
    if (!isDragging.value || !imageContainerRef.value) return;

    const dx = event.clientX - dragStart.value.x;
    const dy = event.clientY - dragStart.value.y;

    imageContainerRef.value.scrollLeft = scrollStart.value.left - dx;
    imageContainerRef.value.scrollTop = scrollStart.value.top - dy;
};

const handleMouseUp = () => {
    if (isDragging.value && imageContainerRef.value) {
        imageContainerRef.value.style.cursor = zoomLevel.value > 100 ? 'grab' : 'default';
    }
    isDragging.value = false;
};

const deleteTemplate = () => {
    deleteForm.delete(route('form-templates.destroy', templateToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            templateToDelete.value = null;
        },
    });
};

const downloadTemplate = (template) => {
    window.location.href = route('form-templates.download', template.id);
};

const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size > 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(2)} ${units[unitIndex]}`;
};

const getFileIcon = (mimeType) => {
    if (!mimeType) return 'pi pi-file';

    if (mimeType.includes('pdf')) return 'pi pi-file-pdf';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'pi pi-file-word';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'pi pi-file-excel';
    if (mimeType.includes('image')) return 'pi pi-image';
    if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'pi pi-file-arrow-down';

    return 'pi pi-file';
};

const canPreview = (mimeType) => {
    if (!mimeType) return false;

    // PDF files can be previewed
    if (mimeType.includes('pdf')) return true;

    // Images can be previewed
    if (mimeType.includes('image')) return true;

    // Text files can be previewed
    if (mimeType.includes('text')) return true;

    return false;
};

const getFileUrl = (template) => {
    return `/storage/${template.file_path}`;
};
</script>
