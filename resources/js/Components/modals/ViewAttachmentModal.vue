<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal ios-modal-maximized" :style="dragStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">{{ attachment?.file_name }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click="downloadAttachment(attachment)">
                        <i class="pi pi-download"></i>
                    </button>
                </div>
                <div class="ios-body" style="padding: 0;">
                    <div class="flex items-center justify-center bg-gray-100 relative overflow-hidden"
                        style="min-height: 80vh;">
                        <!-- PDF Viewer -->
                        <iframe v-if="attachment && attachment.file_type?.includes('pdf')"
                            :src="getAttachmentUrl(attachment)" class="w-full h-full" style="min-height: 80vh;"
                            frameborder="0">
                        </iframe>

                        <!-- Image Viewer with Zoom -->
                        <div v-else-if="attachment && attachment.file_type?.includes('image')"
                            class="w-full h-full flex items-center justify-center relative" style="min-height: 80vh;"
                            @wheel="handleWheel" @mousedown="handleMouseDown" @mousemove="handleMouseMove"
                            @mouseup="handleMouseUp" @mouseleave="handleMouseUp"
                            :style="{ cursor: imageZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }">
                            <img :src="getAttachmentUrl(attachment)" :alt="attachment.file_name"
                                class="max-w-full max-h-[80vh] object-contain select-none" draggable="false" :style="{
                                    transform: `scale(${imageZoom}) translate(${imagePosition.x / imageZoom}px, ${imagePosition.y / imageZoom}px)`,
                                    transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                                }" />

                            <!-- Zoom Controls -->
                            <div class="absolute bottom-4 right-4 flex gap-1"
                                style="background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); padding: 4px;">
                                <button class="ios-icon-btn" @click="zoomOut" :disabled="imageZoom <= 0.5">
                                    <i class="pi pi-minus"></i>
                                </button>
                                <span style="padding: 6px 10px; font-size: 13px; font-weight: 600; color: #000;">{{
                                    Math.round(imageZoom * 100) }}%</span>
                                <button class="ios-icon-btn" @click="zoomIn" :disabled="imageZoom >= 5">
                                    <i class="pi pi-plus"></i>
                                </button>
                                <button class="ios-icon-btn" @click="resetZoom" v-tooltip.top="'Reset Zoom'">
                                    <i class="pi pi-refresh"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Fallback -->
                        <div v-else class="text-center p-8">
                            <i class="pi pi-file text-6xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600">Unable to preview this file type</p>
                            <button class="ios-icon-btn" style="margin-top: 12px;"
                                @click="downloadAttachment(attachment)">
                                <i class="pi pi-download"></i> Download Instead
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';

const props = defineProps({
    visible: { type: Boolean, default: false },
    attachment: { type: Object, default: null }
});

const emit = defineEmits(['update:visible']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Reset zoom when modal opens/closes
watch(() => props.visible, (val) => {
    if (val) {
        imageZoom.value = 1;
        imagePosition.value = { x: 0, y: 0 };
    }
});

const close = () => {
    resetDrag();
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
    const routeName = attachment.download_route || 'scholarship.records.attachments.download';
    window.open(route(routeName, attachment.attachment_id), '_blank');
};

const getAttachmentUrl = (attachment) => {
    if (!attachment) return '';
    const routeName = attachment.view_route || 'scholarship.records.attachments.view';
    return route(routeName, attachment.attachment_id);
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
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

.ios-modal-maximized {
    width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh;
    border-radius: 0;
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
