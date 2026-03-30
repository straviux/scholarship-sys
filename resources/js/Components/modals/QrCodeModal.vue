<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '30vw', minWidth: '400px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Mobile Upload</span>
                </div>
                <div class="ios-body" style="padding: 16px;">
                    <div v-if="qrData" class="text-center" style="display: flex; flex-direction: column; gap: 16px;">
                        <!-- QR Code -->
                        <div
                            class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 inline-block mx-auto">
                            <div v-html="qrData.qrCode"></div>
                        </div>

                        <!-- Instructions -->
                        <div class="text-left" style="display: flex; flex-direction: column; gap: 12px;">
                            <div class="ios-info-card" style="border-left: 4px solid #007AFF;">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    <i class="pi pi-info-circle mr-2"></i>How to use:
                                </p>
                                <ol class="text-sm text-gray-700 dark:text-gray-300 list-decimal list-inside"
                                    style="display: flex; flex-direction: column; gap: 4px;">
                                    <li>Scan this QR code with your mobile device</li>
                                    <li>Take a photo or select from gallery</li>
                                    <li>Upload will be automatically optimized</li>
                                </ol>
                            </div>

                            <div class="ios-info-card bg-amber-50 dark:bg-amber-900/15"
                                style="border-left: 4px solid #FF9500;">
                                <p class="text-xs text-amber-800 dark:text-amber-300">
                                    <i class="pi pi-exclamation-triangle mr-2"></i>
                                    <strong>Expires in:</strong>
                                    <span :class="{
                                        'text-yellow-600': countdown.includes('min') && !countdown.includes('0 min'),
                                        'text-orange-600': countdown.includes('0 min') && parseInt(countdown) >= 5,
                                        'text-red-600 font-bold': countdown.includes('0 min') && parseInt(countdown) < 5 || countdown === 'EXPIRED'
                                    }">
                                        {{ countdown || 'Loading...' }}
                                    </span>
                                </p>
                            </div>

                            <!-- Mobile URL (for copying) -->
                            <div>
                                <label class="ios-label">Or copy this link:</label>
                                <div class="flex gap-2">
                                    <InputText type="text" :value="qrData.url" readonly class="flex-1 text-xs" />
                                    <button class="ios-icon-btn" @click="copyToClipboard(qrData.url)"
                                        v-tooltip.top="'Copy link'">
                                        <i class="pi pi-copy"></i>
                                    </button>
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
import { ref, watch, onUnmounted } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import { toast } from 'vue3-toastify';

const props = defineProps({
    visible: { type: Boolean, default: false },
    qrData: { type: Object, default: null }
});

const emit = defineEmits(['update:visible']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const countdown = ref('');
const countdownInterval = ref(null);

const close = () => {
    resetDrag();
    emit('update:visible', false);
};

const startCountdown = () => {
    if (countdownInterval.value) clearInterval(countdownInterval.value);

    const updateCountdown = () => {
        if (!props.qrData) return;
        const now = new Date();
        const expiresAt = new Date(props.qrData.expiresAt);
        const diff = expiresAt - now;
        if (diff <= 0) {
            countdown.value = 'EXPIRED';
            clearInterval(countdownInterval.value);
            return;
        }
        const totalMinutes = Math.floor(diff / 1000 / 60);
        const seconds = Math.floor((diff / 1000) % 60);
        countdown.value = `${totalMinutes} min ${seconds} sec`;
    };

    updateCountdown();
    countdownInterval.value = setInterval(updateCountdown, 1000);
};

watch(() => props.visible, (val) => {
    if (val && props.qrData) {
        startCountdown();
    } else if (!val && countdownInterval.value) {
        clearInterval(countdownInterval.value);
        countdownInterval.value = null;
    }
});

onUnmounted(() => {
    if (countdownInterval.value) clearInterval(countdownInterval.value);
});

const copyToClipboard = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            toast.success('Link copied to clipboard!');
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const successful = document.execCommand('copy');
            textArea.remove();
            if (successful) {
                toast.success('Link copied to clipboard!');
            } else {
                throw new Error('Copy failed');
            }
        }
    } catch (error) {
        toast.error('Failed to copy automatically. Please copy manually:');
        prompt('Copy this URL:', text);
    }
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

.ios-label {
    font-size: 13px;
    font-weight: 500;
    color: #3C3C43;
    padding-left: 2px;
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

:deep(.p-inputtext) {
    border-radius: 10px;
}

/* Dark mode */
:global(.dark) .ios-modal {
    background: #222831;
}

:global(.dark) .ios-nav-bar {
    background: #2a3040;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-nav-title {
    color: #d1d5db;
}

:global(.dark) .ios-nav-cancel {
    color: #9ca3af;
}

:global(.dark) .ios-nav-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-body {
    color: #d1d5db;
}

:global(.dark) .ios-info-card {
    background: #1e2433;
    border-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-label {
    color: #d1d5db;
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
