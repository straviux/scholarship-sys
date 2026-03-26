<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '60vw', minWidth: '500px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Manage Attachments</span>
                    <button v-if="hasEditPermission" class="ios-nav-btn ios-nav-action" @click="uploadAttachment"
                        :disabled="uploading || !attachmentForm.file || !attachmentForm.attachment_name || (attachmentForm.attachment_name === 'others' && !attachmentForm.custom_attachment_name)">
                        <i class="pi pi-check"></i>
                    </button>
                </div>
                <div class="ios-body" style="padding: 16px;">
                    <!-- Record Info -->
                    <div v-if="record" class="ios-info-card" style="margin-bottom: 16px;">
                        <p class="text-sm font-semibold text-gray-900">{{ record.program?.name || 'N/A' }}</p>
                        <p class="text-xs text-gray-600">{{ record.academic_year }} - {{ record.term }}</p>
                    </div>

                    <!-- Existing Attachments -->
                    <div v-if="record && record.attachments && record.attachments.length > 0">
                        <h4 class="ios-section-label">Existing Attachments</h4>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div v-for="attachment in record.attachments" :key="attachment.attachment_id"
                                class="ios-list-item">
                                <div class="flex items-center gap-3">
                                    <i :class="getFileIcon(attachment.file_type)" class="text-2xl"
                                        style="color: #007AFF;"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ attachment.attachment_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ attachment.file_name }} – {{
                                            formatFileSize(attachment.file_size) }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button class="ios-icon-btn" @click="viewAttachment(attachment)"
                                        v-tooltip.top="'View'">
                                        <i class="pi pi-eye"></i>
                                    </button>
                                    <button class="ios-icon-btn" @click="downloadAttachment(attachment)"
                                        v-tooltip.top="'Download'">
                                        <i class="pi pi-download"></i>
                                    </button>
                                    <button v-if="hasEditPermission" class="ios-icon-btn ios-icon-btn-danger"
                                        @click="deleteAttachment(attachment)" v-tooltip.top="'Delete'">
                                        <i class="pi pi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload New Attachment -->
                    <div v-if="hasEditPermission" style="margin-top: 16px;">
                        <h4 class="ios-section-label">Upload New Attachment</h4>
                        <div class="ios-form-group">
                            <label class="ios-label">Attachment Type *</label>
                            <Select v-model="attachmentForm.attachment_name" :options="attachmentTypeOptions"
                                optionLabel="label" optionValue="value" placeholder="Select attachment type"
                                class="w-full" />
                            <p class="ios-hint">Select the type of attachment you're uploading</p>
                        </div>
                        <div v-if="attachmentForm.attachment_name === 'others'" class="ios-form-group">
                            <label class="ios-label">Specify Attachment Type *</label>
                            <InputText v-model="attachmentForm.custom_attachment_name"
                                placeholder="e.g., Medical Certificate, ID Photo" class="w-full" />
                        </div>
                        <div v-if="attachmentForm.attachment_name === 'contract'" class="ios-form-group">
                            <label class="ios-label">Page Number (Optional)</label>
                            <InputNumber v-model="attachmentForm.page_number" :min="1" placeholder="e.g., 1, 2, 3"
                                class="w-full" />
                        </div>
                        <div class="ios-form-group">
                            <label class="ios-label">File (PDF or Image) *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                                class="ios-file-input" />
                            <p class="ios-hint">Accepted formats: PDF, JPG, PNG (Max 25MB)</p>
                        </div>
                        <div v-if="attachmentForm.file" class="ios-info-card">
                            <p class="text-sm text-gray-700">Selected: <span class="font-medium">{{
                                attachmentForm.file.name }}</span></p>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </Dialog>

    <ViewAttachmentModal v-model:visible="showViewerModal" :attachment="viewerAttachment" />
</template>

<script setup>
import { ref } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    visible: { type: Boolean, default: false },
    record: { type: Object, default: null },
    hasEditPermission: { type: Boolean, default: false }
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const showViewerModal = ref(false);
const viewerAttachment = ref(null);

const viewAttachment = (attachment) => {
    viewerAttachment.value = attachment;
    showViewerModal.value = true;
};

const uploading = ref(false);
const fileInput = ref(null);
const attachmentForm = ref({
    attachment_name: '',
    custom_attachment_name: '',
    page_number: null,
    file: null
});

const attachmentTypeOptions = [
    { label: 'Contract', value: 'contract' },
    { label: 'Copy of Grades', value: 'copy_of_grades' },
    { label: 'Certificate of Enrollment', value: 'certificate_of_enrollment' },
    { label: 'Certificate of Registration', value: 'certificate_of_registration' },
    { label: 'Others', value: 'others' }
];

const close = () => {
    resetDrag();
    emit('update:visible', false);
    attachmentForm.value = { attachment_name: '', custom_attachment_name: '', page_number: null, file: null };
    if (fileInput.value) fileInput.value.value = '';
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 25 * 1024 * 1024) {
            toast.error('File size must not exceed 25MB');
            event.target.value = '';
            return;
        }
        attachmentForm.value.file = file;
    }
};

const uploadAttachment = async () => {
    if (!attachmentForm.value.attachment_name || !attachmentForm.value.file) {
        toast.error('Please provide attachment name and select a file');
        return;
    }
    if (attachmentForm.value.attachment_name === 'others' && !attachmentForm.value.custom_attachment_name) {
        toast.error('Please specify the attachment type');
        return;
    }

    uploading.value = true;
    const formData = new FormData();
    const finalAttachmentName = attachmentForm.value.attachment_name === 'others'
        ? attachmentForm.value.custom_attachment_name
        : attachmentForm.value.attachment_name;
    formData.append('attachment_name', finalAttachmentName);
    formData.append('file', attachmentForm.value.file);
    if (attachmentForm.value.attachment_name === 'contract' && attachmentForm.value.page_number) {
        formData.append('page_number', attachmentForm.value.page_number);
    }

    try {
        await axios.post(route('scholarship.records.attachments.upload', props.record.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Attachment uploaded successfully');
        attachmentForm.value = { attachment_name: '', custom_attachment_name: '', page_number: null, file: null };
        if (fileInput.value) fileInput.value.value = '';
        emit('success');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to upload attachment');
    } finally {
        uploading.value = false;
    }
};

const downloadAttachment = (attachment) => {
    const routeName = attachment.download_route || 'scholarship.records.attachments.download';
    window.open(route(routeName, attachment.attachment_id), '_blank');
};

const deleteAttachment = async (attachment) => {
    if (!confirm('Are you sure you want to delete this attachment?')) return;
    try {
        await axios.delete(route('scholarship.records.attachments.delete', attachment.attachment_id));
        toast.success('Attachment deleted successfully');
        emit('success');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete attachment');
    }
};

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'pi-file-pdf';
    if (fileType?.includes('image')) return 'pi-image';
    return 'pi-file';
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.ios-nav-bar {
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 48px;
    cursor: grab;
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
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.ios-nav-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}


.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}


.ios-info-card {
    background: #FFFFFF;
    border-radius: 10px;
    padding: 12px;
    border: 0.5px solid #E5E5EA;
}

.ios-list-item {
    background: #FFFFFF;
    border-radius: 10px;
    padding: 12px;
    border: 0.5px solid #E5E5EA;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.ios-icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    color: #007AFF;
    font-size: 14px;
    transition: background 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ios-icon-btn:hover {
    background: rgba(0, 122, 255, 0.1);
}

.ios-icon-btn-danger {
    color: #FF3B30;
}

.ios-icon-btn-danger:hover {
    background: rgba(255, 59, 48, 0.1);
}

.ios-section-label {
    font-size: 13px;
    font-weight: 600;
    color: #8E8E93;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 0 4px 0;
}

.ios-form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ios-label {
    font-size: 13px;
    font-weight: 500;
    color: #3C3C43;
    padding-left: 2px;
}

.ios-hint {
    font-size: 11px;
    color: #8E8E93;
    padding-left: 2px;
}

.ios-file-input {
    border: 2px dashed #C7C7CC;
    border-radius: 10px;
    padding: 12px;
    background: #FFFFFF;
    cursor: pointer;
    transition: border-color 0.2s;
}

.ios-file-input:hover {
    border-color: #007AFF;
}

:deep(.p-inputtext),
:deep(.p-select) {
    border-radius: 10px;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.ios-dialog-root .p-dialog-content {
    padding: 0 !important;
    background: transparent !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
