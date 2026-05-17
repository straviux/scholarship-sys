<template>

    <Head title="Documents and Forms" />

    <AdminLayout>
        <template #header>
            Documents and Forms
        </template>

        <!-- Toolbar -->
        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <AppIcon name="file" class="text-blue-600 w-8 h-8 short:w-6 short:h-6" />
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Documents &amp; Forms</h1>
                        <p class="text-sm text-gray-600">Upload and manage downloadable documents and forms</p>
                    </div>
                </div>
            </template>
            <template #end>
                <AppButton v-if="hasPermission('documents.upload')" icon="upload" label="Upload File" severity="success"
                    raised rounded size="small" @click="openUploadDialog" />
            </template>
        </Toolbar>

        <div class="py-2">
            <!-- Search + count -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon>
                        <AppIcon name="search" :size="16" class="text-gray-400" />
                    </InputIcon>
                    <InputText v-model="globalFilter" placeholder="Search documents..."
                        class="w-full ios-search-input-rounded" />
                </IconField>
                <Tag :value="`${getAllDocuments().length} file${getAllDocuments().length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Tabs + DataTable -->
            <TabView v-model:activeIndex="activeTabIndex" class="ios-tabview-rounded">
                <!-- All Files -->
                <TabPanel header="All Files">
                    <DataTable :value="getAllDocuments()" v-model:filters="filters" class="ios-datatable-clean"
                        :globalFilterFields="['title', 'description', 'category']" :rows="10"
                        :rowsPerPageOptions="[5, 10, 20, 50]" paginator :rowHover="true" stripedRows showGridlines
                        scrollable>
                        <Column field="title" header="Title" sortable style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <AppIcon :name="getFileIcon(slotProps.data.file_type)" :size="20" />
                                    <span class="font-semibold">{{ slotProps.data.title }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="description" header="Description" style="min-width: 250px">
                            <template #body="slotProps">
                                <span class="text-sm text-gray-600">{{ slotProps.data.description || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="category" header="Category" sortable style="width: 150px">
                            <template #body="slotProps">
                                <Tag v-if="slotProps.data.category" :value="slotProps.data.category" severity="info" />
                                <span v-else class="text-gray-400">Uncategorized</span>
                            </template>
                        </Column>
                        <Column field="file_size" header="Size" sortable style="width: 100px">
                            <template #body="slotProps">{{ formatFileSize(slotProps.data.file_size) }}</template>
                        </Column>
                        <Column field="download_count" header="Downloads" sortable style="width: 120px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="download" :size="14" class="text-gray-500" />
                                    <span>{{ slotProps.data.download_count }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="is_active" header="Status" style="width: 100px">
                            <template #body="slotProps">
                                <span
                                    class="text-[11px] font-semibold px-[9px] py-[3px] rounded-[20px] inline-block whitespace-nowrap"
                                    :style="slotProps.data.is_active
                                        ? 'background: #d1f5e0; color: #187a3c;'
                                        : 'background: #fee2e2; color: #991b1b;'">
                                    {{ slotProps.data.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Actions" style="width: 160px">
                            <template #body="slotProps">
                                <div class="flex gap-1.5 justify-center">
                                    <AppButton v-if="hasPermission('documents.view')" icon="eye" severity="secondary"
                                        size="small" rounded outlined @click="viewDocument(slotProps.data)"
                                        v-tooltip.top="'View'" />
                                    <AppButton v-if="hasPermission('documents.download')" icon="download"
                                        severity="info" size="small" rounded outlined
                                        @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                    <AppButton v-if="hasPermission('documents.edit')" icon="pencil" severity="warn"
                                        size="small" rounded outlined @click="openEditDialog(slotProps.data)"
                                        v-tooltip.top="'Edit'" />
                                    <AppButton v-if="hasPermission('documents.delete')" icon="trash" severity="danger"
                                        size="small" rounded outlined @click="confirmDelete(slotProps.data)"
                                        v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <!-- Per-category tabs -->
                <TabPanel v-for="category in categories" :key="category.value" :header="category.label">
                    <DataTable :value="getDocumentsByCategory(category.value)" v-model:filters="filters"
                        class="ios-datatable-clean"
                        :globalFilterFields="['title', 'description']" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
                        paginator :rowHover="true" stripedRows showGridlines scrollable>
                        <Column field="title" header="Title" sortable style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <AppIcon :name="getFileIcon(slotProps.data.file_type)" :size="20" />
                                    <span class="font-semibold">{{ slotProps.data.title }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="description" header="Description" style="min-width: 250px">
                            <template #body="slotProps">
                                <span class="text-sm text-gray-600">{{ slotProps.data.description || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="file_size" header="Size" sortable style="width: 100px">
                            <template #body="slotProps">{{ formatFileSize(slotProps.data.file_size) }}</template>
                        </Column>
                        <Column field="download_count" header="Downloads" sortable style="width: 120px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="download" :size="14" class="text-gray-500" />
                                    <span>{{ slotProps.data.download_count }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="is_active" header="Status" style="width: 100px">
                            <template #body="slotProps">
                                <span
                                    class="text-[11px] font-semibold px-[9px] py-[3px] rounded-[20px] inline-block whitespace-nowrap"
                                    :style="slotProps.data.is_active
                                        ? 'background: #d1f5e0; color: #187a3c;'
                                        : 'background: #fee2e2; color: #991b1b;'">
                                    {{ slotProps.data.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Actions" style="width: 160px">
                            <template #body="slotProps">
                                <div class="flex gap-1.5 justify-center">
                                    <AppButton v-if="hasPermission('documents.view')" icon="eye" severity="secondary"
                                        size="small" rounded outlined @click="viewDocument(slotProps.data)"
                                        v-tooltip.top="'View'" />
                                    <AppButton v-if="hasPermission('documents.download')" icon="download"
                                        severity="info" size="small" rounded outlined
                                        @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                    <AppButton v-if="hasPermission('documents.edit')" icon="pencil" severity="warn"
                                        size="small" rounded outlined @click="openEditDialog(slotProps.data)"
                                        v-tooltip.top="'Edit'" />
                                    <AppButton v-if="hasPermission('documents.delete')" icon="trash" severity="danger"
                                        size="small" rounded outlined @click="confirmDelete(slotProps.data)"
                                        v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <!-- ── Upload / Edit iOS Modal ── -->
        <IosModal :visible="showDialog" :title="dialogMode === 'upload' ? 'Upload File' : 'Edit Document'"
            width="560px" max-width="95vw" body-style="padding: 0 16px;" :show-action="true"
            :action-label="dialogMode === 'upload' ? 'Upload' : 'Save'" :loading="form.processing"
            @action="submitForm" @update:visible="val => !val && (showDialog = false)">

                        <!-- File Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">File Details</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Title</span>
                                    <div class="ios-row-control">
                                        <InputText v-model="form.title" placeholder="Document title"
                                            class="ios-row-input" />
                                    </div>
                                </div>
                                <small v-if="form.errors.title"
                                    style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                    {{ form.errors.title }}
                                </small>
                                <div class="ios-row ios-row-last">
                                    <span class="ios-row-label">Category</span>
                                    <div class="ios-row-control ios-select">
                                        <Select v-model="form.category" :options="categories" optionLabel="label"
                                            optionValue="value" placeholder="Select category" showClear />
                                    </div>
                                </div>
                                <small v-if="form.errors.category"
                                    style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                    {{ form.errors.category }}
                                </small>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="ios-section">
                            <div class="ios-section-label">Description</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-last" style="align-items: flex-start; padding: 10px 16px;">
                                    <Textarea v-model="form.description" placeholder="Optional description..."
                                        autoResize rows="3"
                                        style="flex: 1; border: none; outline: none; resize: none; font-size: 14px; color: #1c1c1e; background: transparent; box-shadow: none; padding: 2px 0;" />
                                </div>
                                <small v-if="form.errors.description"
                                    style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                    {{ form.errors.description }}
                                </small>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="ios-section">
                            <div class="ios-section-label">
                                File {{ dialogMode === 'edit' ? '(leave empty to keep current)' : '*' }}
                            </div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-last"
                                    style="align-items: flex-start; padding: 12px 16px; flex-direction: column; gap: 8px;">
                                    <input type="file" id="file" @change="handleFileChange" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100" />
                                    <span style="font-size: 12px; color: #8E8E93;">Max file size: 10MB</span>
                                </div>
                                <small v-if="form.errors.file"
                                    style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                    {{ form.errors.file }}
                                </small>
                            </div>
                        </div>

                        <!-- Current File (edit mode) -->
                        <div class="ios-section" v-if="dialogMode === 'edit' && editingDocument">
                            <div class="ios-section-label">Current File</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-last" style="padding: 12px 16px; gap: 10px;">
                                    <AppIcon :name="getFileIcon(editingDocument.file_type)" :size="22"
                                        style="color: #8E8E93; flex-shrink: 0;" />
                                    <span style="font-size: 13px; color: #1c1c1e; font-family: monospace;">
                                        {{ editingDocument.file_name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="ios-section">
                            <div class="ios-section-label">Settings</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Sort Order</span>
                                    <div class="ios-row-control">
                                        <InputNumber v-model="form.sort_order" class="ios-row-input" :min="0" />
                                    </div>
                                </div>
                                <small v-if="form.errors.sort_order"
                                    style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                    {{ form.errors.sort_order }}
                                </small>
                                <div class="ios-row ios-row-last">
                                    <span class="ios-row-label">Active</span>
                                    <ToggleSwitch v-model="form.is_active" :trueValue="true" :falseValue="false"
                                        size="small" style="--p-toggleswitch-checked-background: #34C759;" />
                                </div>
                            </div>
                        </div>

                        <div style="height: 20px;"></div>
        </IosModal>

        <!-- ── Delete Confirmation iOS Modal ── -->
        <IosModal :visible="showDeleteDialog" title="Confirm Deletion" width="460px" max-width="95vw"
            body-style="padding: 0 16px;" :show-action="true" action-label="Delete"
            :loading="deleteForm.processing" action-class="ios-nav-destructive" @action="deleteDocument"
            @update:visible="val => !val && (showDeleteDialog = false)">
            <div v-if="documentToDelete">
                        <div class="ios-section">
                            <div class="ios-card">
                                <div class="ios-row" style="padding: 12px 16px; gap: 12px;">
                                    <AppIcon name="exclamation-triangle" :size="24"
                                        style="color: #FF3B30; flex-shrink: 0;" />
                                    <div>
                                        <div
                                            style="font-size: 15px; font-weight: 600; color: #000; margin-bottom: 4px;">
                                            Permanently delete this file?
                                        </div>
                                        <div style="font-size: 13px; color: #8E8E93; line-height: 1.4;">
                                            This action cannot be undone and the file will be removed from storage.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ios-section">
                            <div class="ios-section-label">Document</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Title</span>
                                    <span style="font-size: 14px; color: #FF3B30; font-weight: 600;">
                                        {{ documentToDelete.title }}
                                    </span>
                                </div>
                                <div class="ios-row ios-row-last">
                                    <span class="ios-row-label">File</span>
                                    <span style="font-size: 13px; color: #8E8E93; font-family: monospace;">
                                        {{ documentToDelete.file_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div style="height: 20px;"></div>
            </div>
        </IosModal>

        <!-- ── View Dialog ── -->
        <IosModal :visible="showViewDialog" title="View File" width="90vw" max-width="90vw"
            body-style="padding: 16px; height: 75vh; overflow: hidden;" @update:visible="showViewDialog = $event">
            <div v-if="viewingDocument" class="h-full flex flex-col overflow-hidden">
                <!-- File Info Header -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 flex-shrink-0">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <AppIcon :name="getFileIcon(viewingDocument.file_type)" :size="30" />
                            <div>
                                <h3 class="font-semibold text-lg">{{ viewingDocument.title }}</h3>
                                <p class="text-sm text-gray-600" v-if="viewingDocument.description">
                                    {{ viewingDocument.description }}
                                </p>
                                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                    <span class="inline-flex items-center">
                                        <AppIcon name="file" :size="14" class="mr-1" />{{ viewingDocument.file_name }}
                                    </span>
                                    <span class="inline-flex items-center">
                                        <AppIcon name="database" :size="14" class="mr-1" />{{
                                            formatFileSize(viewingDocument.file_size)
                                        }}
                                    </span>
                                    <span v-if="viewingDocument.category">
                                        <span class="inline-flex items-center">
                                            <AppIcon name="tag" :size="14" class="mr-1" />{{ viewingDocument.category }}
                                        </span>
                                    </span>
                                    <span class="inline-flex items-center">
                                        <AppIcon name="download" :size="14" class="mr-1" />{{
                                            viewingDocument.download_count }}
                                        downloads
                                    </span>
                                </div>
                            </div>
                        </div>
                        <AppButton v-if="hasPermission('documents.download')" icon="download" label="Download"
                            severity="info" @click="downloadDocument(viewingDocument)" />
                    </div>
                </div>

                <!-- File Preview -->
                <div class="flex-1 flex items-center justify-center bg-gray-100 rounded relative overflow-hidden"
                    style="min-height: 500px;">
                    <iframe v-if="viewingDocument.file_type?.includes('pdf')" :src="getFileUrl(viewingDocument)"
                        class="w-full h-full rounded" style="min-height: 600px;" frameborder="0" />

                    <div v-else-if="viewingDocument.file_type?.includes('image')"
                        class="w-full h-full flex items-center justify-center relative" style="min-height: 600px;"
                        @wheel="handleWheel" @mousedown="handleMouseDown" @mousemove="handleMouseMove"
                        @mouseup="handleMouseUp" @mouseleave="handleMouseUp"
                        :style="{ cursor: imageZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }">
                        <img :src="getFileUrl(viewingDocument)" :alt="viewingDocument.title"
                            class="max-w-full max-h-[600px] object-contain rounded select-none" draggable="false"
                            :style="{
                                transform: `scale(${imageZoom}) translate(${imagePosition.x / imageZoom}px, ${imagePosition.y / imageZoom}px)`,
                                transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                            }" />
                        <div class="absolute bottom-4 right-4 flex gap-2 bg-white rounded-lg shadow-lg p-2">
                            <AppButton icon="minus" @click="zoomOut" size="small" severity="secondary" rounded
                                :disabled="imageZoom <= 0.5" />
                            <span class="px-3 py-2 text-sm font-semibold">{{ Math.round(imageZoom * 100) }}%</span>
                            <AppButton icon="plus" @click="zoomIn" size="small" severity="secondary" rounded
                                :disabled="imageZoom >= 5" />
                            <AppButton icon="refresh" @click="resetZoom" size="small" severity="secondary" rounded
                                v-tooltip.top="'Reset Zoom'" />
                        </div>
                    </div>

                    <iframe v-else-if="viewingDocument.file_type?.includes('text')" :src="getFileUrl(viewingDocument)"
                        class="w-full h-full rounded" style="min-height: 600px;" frameborder="0" />

                    <div v-else class="text-center p-8">
                        <AppIcon name="file" class="text-gray-400 w-16 h-16 mx-auto mb-4" />
                        <p class="text-gray-600">Unable to preview this file type</p>
                        <AppButton label="Download Instead" icon="download" class="mt-4"
                            @click="downloadDocument(viewingDocument)" />
                    </div>
                </div>

                <div class="flex justify-end pt-4 mt-4 border-t border-gray-200 dark:border-white/10 flex-shrink-0">
                    <Button label="Close" severity="secondary" @click="showViewDialog = false" />
                </div>
            </div>
        </IosModal>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    documents: Object,
    categories: Array,
});

const { hasPermission } = usePermission();
const activeTabIndex = ref(0);
const showDialog = ref(false);
const showDeleteDialog = ref(false);
const showViewDialog = ref(false);
const dialogMode = ref('upload');
const documentToDelete = ref(null);
const editingDocument = ref(null);
const viewingDocument = ref(null);
const editingDocumentId = ref(null);

const globalFilter = ref('');
const filters = ref({ global: { value: null, matchMode: 'contains' } });
watch(globalFilter, (v) => { filters.value.global.value = v; });

// Image zoom state (same as disbursement viewer)
const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

const form = useForm({
    title: '',
    description: '',
    category: null,
    file: null,
    sort_order: 0,
    is_active: true,
});

const deleteForm = useForm({});

const getAllDocuments = () => {
    const allDocuments = [];
    Object.values(props.documents).forEach(categoryDocuments => {
        allDocuments.push(...categoryDocuments);
    });
    return allDocuments;
};

const getDocumentsByCategory = (category) => {
    return props.documents[category] || [];
};

const openUploadDialog = () => {
    dialogMode.value = 'upload';
    editingDocument.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showDialog.value = true;
};

const openEditDialog = (document) => {
    dialogMode.value = 'edit';
    editingDocument.value = document;
    editingDocumentId.value = document.id;
    form.clearErrors();
    form.title = document.title;
    form.description = document.description;
    form.category = document.category;
    form.sort_order = document.sort_order;
    form.is_active = document.is_active;
    form.file = null;
    showDialog.value = true;
};

const handleFileChange = (event) => {
    form.file = event.target.files[0];
};

const submitForm = () => {
    if (dialogMode.value === 'upload') {
        form.post(route('documents.store'), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    } else {
        form.put(route('documents.update', editingDocumentId.value), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (document) => {
    documentToDelete.value = document;
    showDeleteDialog.value = true;
};

const viewDocument = (document) => {
    viewingDocument.value = document;
    showViewDialog.value = true;
    imageZoom.value = 1;
    imagePosition.value = { x: 0, y: 0 };
};

// Image zoom functions (same as disbursement viewer)
const handleWheel = (event) => {
    if (!viewingDocument.value?.file_type?.includes('image')) return;
    event.preventDefault();
    const delta = event.deltaY > 0 ? -0.1 : 0.1;
    imageZoom.value = Math.max(0.5, Math.min(5, imageZoom.value + delta));
};

const handleMouseDown = (event) => {
    if (!viewingDocument.value?.file_type?.includes('image')) return;
    if (imageZoom.value > 1) {
        isDragging.value = true;
        dragStart.value = {
            x: event.clientX - imagePosition.value.x,
            y: event.clientY - imagePosition.value.y
        };
    }
};

const handleMouseMove = (event) => {
    if (isDragging.value) {
        imagePosition.value = {
            x: event.clientX - dragStart.value.x,
            y: event.clientY - dragStart.value.y
        };
    }
};

const handleMouseUp = () => {
    isDragging.value = false;
};

const resetZoom = () => {
    imageZoom.value = 1;
    imagePosition.value = { x: 0, y: 0 };
};

const zoomIn = () => {
    imageZoom.value = Math.min(5, imageZoom.value + 0.25);
};

const zoomOut = () => {
    imageZoom.value = Math.max(0.5, imageZoom.value - 0.25);
};

const deleteDocument = () => {
    deleteForm.delete(route('documents.destroy', documentToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            documentToDelete.value = null;
        },
    });
};

const downloadDocument = (document) => {
    window.location.href = route('documents.download', document.id);
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
    if (!mimeType) return 'file';

    if (mimeType.includes('pdf')) return 'file-type';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'file-text';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'file-spreadsheet';
    if (mimeType.includes('image')) return 'image';
    if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'file-down';

    return 'file';
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

const getFileUrl = (document) => {
    return `/storage/${document.file_path}`;
};
</script>
