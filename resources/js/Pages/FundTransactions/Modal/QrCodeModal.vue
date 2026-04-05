<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 450px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Mobile Upload QR Code</span>
                    <span class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
                </div>
                <div class="ios-body">
                    <div v-if="modelValue" style="padding-top: 16px;">
                        <!-- QR Code -->
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 24px 16px; text-align: center;">
                                <div v-if="modelValue.qrCode" v-safe-html="modelValue.qrCode"
                                    style="display: inline-block;">
                                </div>
                                <div v-else style="padding: 32px 0; color: #8E8E93;">
                                    <i class="pi pi-exclamation-triangle text-2xl"></i>
                                    <p style="font-size: 13px; margin-top: 8px;">QR code could not be generated</p>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="ios-section">
                            <p class="ios-section-label">How to Use</p>
                            <div class="ios-card" style="padding: 14px 16px;">
                                <ol style="font-size: 13px; color: #3c3c43; padding-left: 20px; line-height: 1.8;">
                                    <li>Scan this QR code with your mobile device</li>
                                    <li>Select document type (OBR, DV/Payroll, LOS, or Cheque)</li>
                                    <li>Take a photo or select from gallery</li>
                                    <li>Upload the document</li>
                                    <li>Document appears in the system automatically</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Expiration -->
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px; background: #fffbeb;">
                                <p style="font-size: 13px; color: #92400e;">
                                    <i class="pi pi-clock" style="margin-right: 6px;"></i>
                                    <strong>Expires in:</strong> {{ countdown }}
                                </p>
                            </div>
                        </div>

                        <!-- Copy URL -->
                        <div class="ios-section" style="margin-bottom: 16px;">
                            <p class="ios-section-label">Upload Link</p>
                            <div class="ios-card" style="padding: 12px 16px;">
                                <div class="flex gap-2">
                                    <InputText type="text" :value="modelValue.url" readonly class="flex-1" />
                                    <Button icon="pi pi-copy" size="small" @click="copyToClipboard(modelValue.url)"
                                        v-tooltip="'Copy upload link'" />
                                </div>
                            </div>
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
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

defineProps({
    show: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Object,
        default: null
    },
    countdown: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:show']);
const toast = useToast();
const elModal = ref(null);
const copyMessage = ref('');
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

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.add({
        severity: 'success',
        summary: 'Copied',
        detail: 'Link copied to clipboard',
        life: 2000
    });
};
</script>
