<template>

    <Head title="Documents and Forms" />

    <AdminLayout>
        <template #header>
            Documents and Forms
        </template>

        <div class="space-y-6">
            <!-- Header Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-file text-xl"></i>
                        <span class="font-semibold text-lg">Documents and Forms Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Upload and manage downloadable documents and forms
                    </div>
                    <Button v-if="hasPermission('documents.upload')" icon="pi pi-upload" label="Upload File"
                        severity="success" raised @click="openUploadDialog" />
                </div>
            </Panel>

            <!-- Tabs for Categories -->
            <Card>
                <template #content>
                    <TabView v-model:activeIndex="activeTabIndex">
                        <TabPanel header="All Files">
                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                :value="getAllDocuments()" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" paginator
                                :rowHover="true" stripedRows showGridlines>
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
                                            <Button v-if="hasPermission('documents.view')" icon="pi pi-eye"
                                                severity="secondary" text size="small"
                                                @click="viewDocument(slotProps.data)" v-tooltip.top="'View'" />
                                            <Button v-if="hasPermission('documents.download')" icon="pi pi-download"
                                                severity="info" text size="small"
                                                @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                            <Button v-if="hasPermission('documents.edit')" icon="pi pi-pencil"
                                                severity="warning" text size="small"
                                                @click="openEditDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                            <Button v-if="hasPermission('documents.delete')" icon="pi pi-trash"
                                                severity="danger" text size="small"
                                                @click="confirmDelete(slotProps.data)" v-tooltip.top="'Delete'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                        <TabPanel v-for="category in categories" :key="category.value" :header="category.label">
                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                :value="getDocumentsByCategory(category.value)" :rows="10"
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
                                            <Button v-if="hasPermission('documents.view')" icon="pi pi-eye"
                                                severity="secondary" text size="small"
                                                @click="viewDocument(slotProps.data)" v-tooltip.top="'View'" />
                                            <Button v-if="hasPermission('documents.download')" icon="pi pi-download"
                                                severity="info" text size="small"
                                                @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                            <Button v-if="hasPermission('documents.edit')" icon="pi pi-pencil"
                                                severity="warning" text size="small"
                                                @click="openEditDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                            <Button v-if="hasPermission('documents.delete')" icon="pi pi-trash"
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
            :header="dialogMode === 'upload' ? 'Upload Document/Form' : 'Edit Document/Form'" :modal="true"
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

                <div v-if="dialogMode === 'edit' && editingDocument">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current File</label>
                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded">
                        <i :class="getFileIcon(editingDocument.file_type)" class="text-2xl"></i>
                        <span class="font-mono text-sm">{{ editingDocument.file_name }}</span>
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
                    <p class="mb-2">Are you sure you want to delete this document?</p>
                    <p class="text-sm text-gray-600 font-semibold">{{ documentToDelete?.title }}</p>
                    <p class="text-sm text-red-600 mt-2">This will permanently delete the file.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeleteDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteDocument" :loading="deleteForm.processing" />
            </template>
        </Dialog>

        <!-- View Dialog -->
        <Dialog v-model:visible="showViewDialog" header="View File" :modal="true"
            :style="{ width: '90vw', maxHeight: '90vh' }" :maximizable="true"
            :contentStyle="{ height: '75vh', overflow: 'hidden' }">
            <div v-if="viewingDocument" class="h-full flex flex-col overflow-hidden">
                <!-- File Info Header -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 flex-shrink-0">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <i :class="getFileIcon(viewingDocument.file_type)" class="text-3xl"></i>
                            <div>
                                <h3 class="font-semibold text-lg">{{ viewingDocument.title }}</h3>
                                <p class="text-sm text-gray-600" v-if="viewingDocument.description">{{
                                    viewingDocument.description }}</p>
                                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                    <span><i class="pi pi-file mr-1"></i>{{ viewingDocument.file_name }}</span>
                                    <span><i class="pi pi-database mr-1"></i>{{
                                        formatFileSize(viewingDocument.file_size)
                                        }}</span>
                                    <span v-if="viewingDocument.category">
                                        <i class="pi pi-tag mr-1"></i>{{ viewingDocument.category }}
                                    </span>
                                    <span><i class="pi pi-download mr-1"></i>{{ viewingDocument.download_count }}
                                        downloads</span>
                                </div>
                            </div>
                        </div>
                        <Button v-if="hasPermission('documents.download')" icon="pi pi-download" label="Download"
                            severity="info" @click="downloadDocument(viewingDocument)" />
                    </div>
                </div>

                <!-- File Preview -->
                <div class="flex-1 flex items-center justify-center bg-gray-100 rounded relative overflow-hidden"
                    style="min-height: 500px;">
                    <!-- PDF Viewer -->
                    <iframe v-if="viewingDocument.file_type?.includes('pdf')" :src="getFileUrl(viewingDocument)"
                        class="w-full h-full rounded" style="min-height: 600px;" frameborder="0">
                    </iframe>

                    <!-- Image Viewer with Zoom (same as disbursement viewer) -->
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

                        <!-- Zoom Controls -->
                        <div class="absolute bottom-4 right-4 flex gap-2 bg-white rounded-lg shadow-lg p-2">
                            <Button icon="pi pi-minus" @click="zoomOut" size="small" severity="secondary" rounded
                                :disabled="imageZoom <= 0.5" />
                            <span class="px-3 py-2 text-sm font-semibold">{{ Math.round(imageZoom * 100) }}%</span>
                            <Button icon="pi pi-plus" @click="zoomIn" size="small" severity="secondary" rounded
                                :disabled="imageZoom >= 5" />
                            <Button icon="pi pi-refresh" @click="resetZoom" size="small" severity="secondary" rounded
                                v-tooltip.top="'Reset Zoom'" />
                        </div>
                    </div>

                    <!-- Text File Preview -->
                    <iframe v-else-if="viewingDocument.file_type?.includes('text')" :src="getFileUrl(viewingDocument)"
                        class="w-full h-full rounded" style="min-height: 600px;" frameborder="0">
                    </iframe>

                    <!-- Fallback -->
                    <div v-else class="text-center p-8">
                        <i class="pi pi-file text-6xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Unable to preview this file type</p>
                        <Button label="Download Instead" icon="pi pi-download" class="mt-4"
                            @click="downloadDocument(viewingDocument)" />
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

const getFileUrl = (document) => {
    return `/storage/${document.file_path}`;
};
</script>
