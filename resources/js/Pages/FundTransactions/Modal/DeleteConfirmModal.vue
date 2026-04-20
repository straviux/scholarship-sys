<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal w-[90vw] max-w-[420px]" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Confirm Delete</span>
                    <button class="ios-nav-btn ios-nav-action text-red-500" @click="confirmDelete"
                        :disabled="isDeleting">
                        <AppIcon v-if="isDeleting" name="spinner" :size="12" class="mr-[3px]" />Delete
                    </button>
                </div>
                <div class="ios-body">
                    <div class="ios-section mt-4">
                        <div class="ios-card px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <AppIcon name="exclamation-triangle" :size="24" class="text-red-500" />
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">Delete Transaction</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">This action cannot be undone</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ios-section mb-4">
                        <div class="ios-card px-4 py-3.5 bg-red-50 dark:bg-red-950/30">
                            <p class="text-sm text-red-800 dark:text-red-300 mb-1">
                                <strong>Transaction ID:</strong> FTR-{{ voucherNumber || 'N/A' }}
                            </p>
                            <p v-if="payeeName" class="text-sm text-red-800 dark:text-red-300 mb-1">
                                <strong>Payee:</strong> {{ payeeName }}
                            </p>
                            <p v-if="date" class="text-sm text-red-800 dark:text-red-300">
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
