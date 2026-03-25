<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 420px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Confirm Delete</span>
                    <button class="ios-nav-btn ios-nav-action" style="color: #ef4444;" @click="confirmDelete"
                        :disabled="isDeleting">
                        <i v-if="isDeleting" class="pi pi-spin pi-spinner"
                            style="font-size: 12px; margin-right: 3px;"></i>Delete
                    </button>
                </div>
                <div class="ios-body">
                    <div class="ios-section" style="margin-top: 16px;">
                        <div class="ios-card" style="padding: 14px 16px;">
                            <div class="flex items-center gap-3">
                                <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Delete Transaction</p>
                                    <p class="text-sm text-gray-600">This action cannot be undone</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ios-section" style="margin-bottom: 16px;">
                        <div class="ios-card" style="padding: 14px 16px; background: #fff1f2;">
                            <p class="text-sm text-red-800" style="margin-bottom: 4px;">
                                <strong>Transaction ID:</strong> FTR-{{ voucherNumber || 'N/A' }}
                            </p>
                            <p v-if="payeeName" class="text-sm text-red-800" style="margin-bottom: 4px;">
                                <strong>Payee:</strong> {{ payeeName }}
                            </p>
                            <p v-if="date" class="text-sm text-red-800">
                                <strong>Date Filed:</strong> {{ date }}
                            </p>
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
    voucherNumber: {
        type: String,
        default: 'N/A'
    },
    payeeName: {
        type: String,
        default: null
    },
    date: {
        type: String,
        default: null
    },
    isDeleting: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'confirm-delete']);

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

const confirmDelete = () => {
    emit('confirm-delete');
};
</script>
