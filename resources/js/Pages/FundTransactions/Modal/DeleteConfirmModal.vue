<template>
    <IosModal :visible="show" title="Confirm Delete" width="420px" max-width="90vw"
        body-style="padding: 0 16px;" :show-action="true" action-label="Delete" :loading="isDeleting"
        action-class="text-red-500" @action="confirmDelete" @update:visible="val => emit('update:show', val)">
        <div>
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
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';

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

const confirmDelete = () => {
    emit('confirm-delete');
};
</script>
