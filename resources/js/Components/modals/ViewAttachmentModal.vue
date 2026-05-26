<template>
    <IosModal :visible="visible" width="min(900px, 92vw)" :title="attachment?.file_name || ''"
        title-class="ios-nav-title--truncate" :draggable="!isMaximized"
        :modal-class="{ 'ios-modal-maximized': isMaximized }" :modal-content-style="attachmentModalContentStyle"
        body-style="padding: 0; flex: 1; display: flex; flex-direction: column; overflow: hidden;"
        @update:visible="val => emit('update:visible', val)" @close="close">
        <template #header-right>
            <div class="ios-nav-actions">
                <button class="ios-nav-btn ios-nav-action ios-nav-btn--inline" @click="isMaximized = !isMaximized"
                    v-tooltip.bottom="isMaximized ? 'Restore' : 'Maximize'">
                    <AppIcon :name="isMaximized ? 'window-minimize' : 'window-maximize'" :size="14" />
                </button>
                <button class="ios-nav-btn ios-nav-action ios-nav-btn--inline" @click="downloadAttachment(attachment)">
                    <AppIcon name="download" :size="14" />
                </button>
            </div>
        </template>

        <div class="flex items-center justify-center bg-gray-100 dark:bg-gray-900 relative overflow-hidden"
            style="flex: 1;">
            <!-- PDF Viewer -->
            <iframe v-if="attachment && getFileType(attachment).includes('pdf')" :src="getAttachmentUrl(attachment)"
                class="w-full h-full" style="position: absolute; inset: 0;" frameborder="0">
            </iframe>

            <!-- Image Viewer with Zoom -->
            <div v-else-if="attachment && getFileType(attachment).includes('image')"
                class="w-full h-full flex items-center justify-center relative" @wheel="handleWheel"
                @mousedown="handleMouseDown" @mousemove="handleMouseMove" @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp"
                :style="{ cursor: imageZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }">
                <img :src="getAttachmentUrl(attachment)" :alt="attachment.file_name"
                    class="max-w-full max-h-full object-contain select-none" draggable="false" :style="{
                        transform: `scale(${imageZoom}) translate(${imagePosition.x / imageZoom}px, ${imagePosition.y / imageZoom}px)`,
                        transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                    }" />

                <!-- Zoom Controls -->
                <div
                    class="absolute bottom-4 right-4 flex gap-1 bg-white dark:bg-gray-800 rounded-[10px] shadow-lg p-1">
                    <button class="ios-icon-btn" @click="zoomOut" :disabled="imageZoom <= 0.5">
                        <AppIcon name="minus" :size="13" />
                    </button>
                    <span class="px-2.5 py-1.5 text-[13px] font-semibold text-black dark:text-gray-200">{{
                        Math.round(imageZoom * 100) }}%</span>
                    <button class="ios-icon-btn" @click="zoomIn" :disabled="imageZoom >= 5">
                        <AppIcon name="plus" :size="13" />
                    </button>
                    <button class="ios-icon-btn" @click="resetZoom" v-tooltip.top="'Reset Zoom'">
                        <AppIcon name="refresh" :size="13" />
                    </button>
                </div>
            </div>

            <!-- Fallback -->
            <div v-else class="text-center p-6 short:p-4">
                <AppIcon name="file" :size="64" class="text-gray-400 dark:text-gray-500 mb-4" />
                <p class="text-gray-600 dark:text-gray-400">Unable to preview this file type</p>
                <button class="ios-icon-btn" style="margin-top: 12px;" @click="downloadAttachment(attachment)">
                    <AppIcon name="download" :size="13" /> Download Instead
                </button>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    attachment: { type: Object, default: null }
});

const emit = defineEmits(['update:visible']);

const isMaximized = ref(false);

const attachmentModalContentStyle = computed(() => {
    return isMaximized.value
        ? {
            width: '100vw',
            maxWidth: '100vw',
            height: '100vh',
            maxHeight: '100vh',
            borderRadius: '0',
        }
        : {
            height: '85vh',
        };
});

const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Reset zoom when modal opens/closes
watch(() => props.visible, (val) => {
    if (val) {
        imageZoom.value = 1;
        imagePosition.value = { x: 0, y: 0 };
        isMaximized.value = false;
    }
});

const close = () => {
    emit('update:visible', false);
};

const handleWheel = (event) => {
    event.preventDefault();
    const delta = event.deltaY > 0 ? -0.1 : 0.1;
    imageZoom.value = Math.max(0.5, Math.min(5, imageZoom.value + delta));
};

const handleMouseDown = (event) => {
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

const downloadAttachment = (attachment) => {
    if (!attachment) return;
    if (attachment.file_url) {
        window.open(attachment.file_url, '_blank');
        return;
    }
    const routeName = attachment.download_route || 'scholarship.records.attachments.download';
    window.open(route(routeName, attachment.attachment_id), '_blank');
};

const getAttachmentUrl = (attachment) => {
    if (!attachment) return '';
    if (attachment.file_url) return attachment.file_url;
    const routeName = attachment.view_route || 'scholarship.records.attachments.view';
    return route(routeName, attachment.attachment_id);
};

const getFileType = (attachment) => {
    if (!attachment) return '';
    if (attachment.file_type) return attachment.file_type;
    const ext = attachment.file_name?.split('.').pop()?.toLowerCase() || '';
    if (ext === 'pdf') return 'application/pdf';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return 'image/jpeg';
    return '';
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>
