<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 500px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Tracking Timeline</span>
                    <span class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
                </div>
                <div class="ios-body">
                    <div style="padding-top: 16px;">
                        <div v-if="trackingData" class="ios-section" style="margin-bottom: 16px;">
                            <!-- Tracking Timeline -->
                            <div v-if="trackingData.tracking_information && trackingData.tracking_information.length > 0"
                                class="ios-card" style="padding: 0 16px;">
                                <div v-for="(entry, index) in trackingData.tracking_information" :key="index"
                                    style="display: flex; gap: 12px; padding: 12px 0;"
                                    :style="index < trackingData.tracking_information.length - 1 ? 'border-bottom: 0.5px solid #e5e5ea;' : ''">
                                    <div
                                        style="width: 28px; height: 28px; background: #007AFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                        <i class="pi pi-check" style="color: white; font-size: 11px;"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p style="font-size: 13px; color: #1c1c1e;">{{ entry.trn_remarks ||
                                            entry.transaction_description || entry.description }}</p>
                                        <p style="font-size: 12px; color: #8E8E93; margin-top: 3px;">{{
                                            formatDate(entry.trn_date || entry.transaction_date || entry.date)
                                        }}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- No Timeline -->
                            <div v-else class="ios-card"
                                style="padding: 32px 16px; text-align: center; color: #8E8E93;">
                                <p style="font-size: 14px;">No tracking information</p>
                            </div>
                        </div>

                        <div v-else style="padding: 48px; text-align: center; color: #8E8E93;">
                            <i class="pi pi-spin pi-spinner"
                                style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                            <p style="font-size: 13px;">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import Dialog from 'primevue/dialog';

defineProps({
    show: {
        type: Boolean,
        required: true
    },
    trackingData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:show']);
const elModal = ref(null);
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const modalStyle = computed(() => ({
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`
}));

function onDragStart(e) {
    if (e.target.closest('button')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}

function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}

function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
