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
                    <i class="pi pi-file text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Documents &amp; Forms</h1>
                        <p class="text-sm text-gray-600">Upload and manage downloadable documents and forms</p>
                    </div>
                </div>
            </template>
            <template #end>
                <Button v-if="hasPermission('documents.upload')" icon="pi pi-upload" label="Upload File"
                    severity="success" raised rounded size="small" @click="openUploadDialog" />
            </template>
        </Toolbar>

        <div class="py-2">
            <!-- Search + count -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Search documents..." class="w-full" />
                </IconField>
                <Tag :value="`${getAllDocuments().length} file${getAllDocuments().length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Tabs + DataTable -->
            <TabView v-model:activeIndex="activeTabIndex">
                <!-- All Files -->
                <TabPanel header="All Files">
                    <DataTable :value="getAllDocuments()" v-model:filters="filters"
                        :globalFilterFields="['title', 'description', 'category']" :rows="10"
                        :rowsPerPageOptions="[5, 10, 20, 50]" paginator :rowHover="true" stripedRows showGridlines
                        scrollable>
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
                                    <i class="pi pi-download text-sm text-gray-500"></i>
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
                                    <Button v-if="hasPermission('documents.view')" icon="pi pi-eye" severity="secondary"
                                        size="small" rounded outlined @click="viewDocument(slotProps.data)"
                                        v-tooltip.top="'View'" />
                                    <Button v-if="hasPermission('documents.download')" icon="pi pi-download"
                                        severity="info" size="small" rounded outlined
                                        @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                    <Button v-if="hasPermission('documents.edit')" icon="pi pi-pencil" severity="warn"
                                        size="small" rounded outlined @click="openEditDialog(slotProps.data)"
                                        v-tooltip.top="'Edit'" />
                                    <Button v-if="hasPermission('documents.delete')" icon="pi pi-trash"
                                        severity="danger" size="small" rounded outlined
                                        @click="confirmDelete(slotProps.data)" v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <!-- Per-category tabs -->
                <TabPanel v-for="category in categories" :key="category.value" :header="category.label">
                    <DataTable :value="getDocumentsByCategory(category.value)" v-model:filters="filters"
                        :globalFilterFields="['title', 'description']" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
                        paginator :rowHover="true" stripedRows showGridlines scrollable>
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
                                <span class="text-sm text-gray-600">{{ slotProps.data.description || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="file_size" header="Size" sortable style="width: 100px">
                            <template #body="slotProps">{{ formatFileSize(slotProps.data.file_size) }}</template>
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
                                    <Button v-if="hasPermission('documents.view')" icon="pi pi-eye" severity="secondary"
                                        size="small" rounded outlined @click="viewDocument(slotProps.data)"
                                        v-tooltip.top="'View'" />
                                    <Button v-if="hasPermission('documents.download')" icon="pi pi-download"
                                        severity="info" size="small" rounded outlined
                                        @click="downloadDocument(slotProps.data)" v-tooltip.top="'Download'" />
                                    <Button v-if="hasPermission('documents.edit')" icon="pi pi-pencil" severity="warn"
                                        size="small" rounded outlined @click="openEditDialog(slotProps.data)"
                                        v-tooltip.top="'Edit'" />
                                    <Button v-if="hasPermission('documents.delete')" icon="pi pi-trash"
                                        severity="danger" size="small" rounded outlined
                                        @click="confirmDelete(slotProps.data)" v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <!-- ── Upload / Edit iOS Modal ── -->
        <Dialog :visible="showDialog" modal @update:visible="val => !val && (showDialog = false)"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="formModalStyle">

                    <!-- Nav Bar -->
                    <div class="ios-nav-bar" @pointerdown="onFormDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" type="button" @click="showDialog = false">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">{{ dialogMode === 'upload' ? 'Upload File' : 'Edit Document'
                            }}</span>
                        <button class="ios-nav-btn ios-nav-action" type="button" @click="submitForm"
                            :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : (dialogMode === 'upload' ? 'Upload' : 'Save') }}
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="ios-body">

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
                                    <i :class="getFileIcon(editingDocument.file_type)"
                                        style="font-size: 22px; color: #8E8E93; flex-shrink: 0;"></i>
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
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ── Delete Confirmation iOS Modal ── -->
        <Dialog :visible="showDeleteDialog" modal @update:visible="val => !val && (showDeleteDialog = false)"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" style="width: 460px;">
                    <div class="ios-nav-bar">
                        <button class="ios-nav-btn ios-nav-cancel" type="button" @click="showDeleteDialog = false">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Confirm Deletion</span>
                        <button class="ios-nav-btn ios-nav-action ios-nav-destructive" type="button"
                            @click="deleteDocument" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                    <div class="ios-body" v-if="documentToDelete">
                        <div class="ios-section">
                            <div class="ios-card">
                                <div class="ios-row" style="padding: 12px 16px; gap: 12px;">
                                    <i class="pi pi-exclamation-triangle"
                                        style="font-size: 24px; color: #FF3B30; flex-shrink: 0;"></i>
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
                </div>
            </template>
        </Dialog>

        <!-- ── View Dialog (kept as standard Dialog — full screen previewer) ── -->
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
                                <p class="text-sm text-gray-600" v-if="viewingDocument.description">
                                    {{ viewingDocument.description }}
                                </p>
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
                            <Button icon="pi pi-minus" @click="zoomOut" size="small" severity="secondary" rounded
                                :disabled="imageZoom <= 0.5" />
                            <span class="px-3 py-2 text-sm font-semibold">{{ Math.round(imageZoom * 100) }}%</span>
                            <Button icon="pi pi-plus" @click="zoomIn" size="small" severity="secondary" rounded
                                :disabled="imageZoom >= 5" />
                            <Button icon="pi pi-refresh" @click="resetZoom" size="small" severity="secondary" rounded
                                v-tooltip.top="'Reset Zoom'" />
                        </div>
                    </div>

                    <iframe v-else-if="viewingDocument.file_type?.includes('text')" :src="getFileUrl(viewingDocument)"
                        class="w-full h-full rounded" style="min-height: 600px;" frameborder="0" />

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
import { ref, computed, watch, onBeforeUnmount } from 'vue';
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

const globalFilter = ref('');
const filters = ref({ global: { value: null, matchMode: 'contains' } });
watch(globalFilter, (v) => { filters.value.global.value = v; });

// Form modal drag
const formDragOffset = ref({ x: 0, y: 0 });
const formDragStart = ref(null);
const formModalStyle = computed(() => ({
    width: '560px',
    transform: `translate(${formDragOffset.value.x}px, ${formDragOffset.value.y}px)`,
}));
function onFormDragStart(e) {
    if (e.target.closest('button, a, input, textarea, .p-select, .p-inputnumber')) return;
    formDragStart.value = { x: e.clientX - formDragOffset.value.x, y: e.clientY - formDragOffset.value.y };
    document.addEventListener('pointermove', onFormDragMove);
    document.addEventListener('pointerup', onFormDragEnd);
}
function onFormDragMove(e) {
    if (!formDragStart.value) return;
    formDragOffset.value = { x: e.clientX - formDragStart.value.x, y: e.clientY - formDragStart.value.y };
}
function onFormDragEnd() {
    formDragStart.value = null;
    document.removeEventListener('pointermove', onFormDragMove);
    document.removeEventListener('pointerup', onFormDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onFormDragMove);
    document.removeEventListener('pointerup', onFormDragEnd);
});

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
    formDragOffset.value = { x: 0, y: 0 };
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
    formDragOffset.value = { x: 0, y: 0 };
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

<style scoped>
/* ── TabView ── */
:deep(.p-tabview) {
    border-radius: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--p-content-border-color, #e5e7eb);
}
:deep(.p-tabview-tablist-container) {
    border-radius: 1.5rem 1.5rem 0 0;
}
:deep(.p-tabview-panels) {
    border-radius: 0 0 1.5rem 1.5rem;
    overflow: hidden;
}

/* ── DataTable ── */
:deep(.p-datatable) {
    border-radius: 0;
    overflow: hidden;
    border: none;
}

:deep(.p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

:deep(.p-datatable thead tr:first-child th:first-child) {
    border-left: none;
}

:deep(.p-datatable thead tr:first-child th:last-child) {
    border-right: none;
}

:deep(.p-datatable thead tr:first-child th) {
    border-top: none;
}

:deep(.p-datatable tbody tr:last-child td) {
    border-bottom: none;
}

:deep(.p-datatable tbody tr:last-child td:first-child) {
    border-left: none;
}

:deep(.p-datatable tbody tr:last-child td:last-child) {
    border-right: none;
}

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}

:deep(.p-iconfield .p-inputtext) {
    border-radius: 1rem;
}

/* ═══════════════════════════════════════════════
   iOS Modal Shell
   ═══════════════════════════════════════════════ */
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #6B7280;
    font-weight: 400;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-nav-destructive {
    color: #FF3B30 !important;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #8E8E93;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.ios-row-control {
    flex: 0 0 200px;
    width: 200px;
    display: flex;
    justify-content: flex-end;
    overflow: hidden;
}

.ios-row-control>* {
    width: 100%;
    min-width: 0;
}

/* ── PrimeVue tweaks inside ios-card ── */
:deep(.ios-row-input.p-inputtext),
:deep(.ios-row-input) {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    text-align: right;
    color: #1c1c1e !important;
    font-size: 13px;
    padding: 4px 2px 4px 8px;
    width: 100%;
}

:deep(.ios-row-input.p-inputtext:focus),
:deep(.ios-row-input:focus) {
    outline: none !important;
    box-shadow: none !important;
}

:deep(.ios-row-input .p-inputnumber-input) {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    text-align: right;
    color: #1c1c1e !important;
    font-size: 13px;
    width: 100%;
}

:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    padding: 0;
    width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #1c1c1e !important;
    text-align: right;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
}
</style>

<!-- Unscoped: targets teleported Dialog elements at body level -->
<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
}
</style>