<template>
    <IosModal :visible="show" title="Mobile Upload QR Code" width="450px" max-width="90vw"
        body-style="padding: 16px;" @update:visible="val => emit('update:show', val)">
        <div v-if="modelValue">
                        <!-- QR Code -->
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 24px 16px; text-align: center;">
                                <div v-if="modelValue.qrCode" v-safe-html="modelValue.qrCode"
                                    style="display: inline-block;">
                                </div>
                                <div v-else style="padding: 32px 0; color: #8E8E93;">
                                    <AppIcon name="exclamation-triangle" :size="24" />
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
                                    <AppIcon name="clock" :size="14" style="margin-right: 6px;" />
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
                                    <AppButton icon="copy" size="small" @click="copyToClipboard(modelValue.url)"
                                        v-tooltip="'Copy upload link'" />
                                </div>
                            </div>
                        </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import IosModal from '@/Components/ui/IosModal.vue';

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
const copyMessage = ref('');

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
