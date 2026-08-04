<template>
    <IosConfirmDialog
        :visible="show"
        title="Confirm Delete"
        width="420px"
        message="Delete this transaction? This action cannot be undone."
        data-label="Transaction"
        :data="data"
        :loading="isDeleting"
        @accept="confirmDelete"
        @update:visible="val => emit('update:show', val)"
    />
</template>

<script setup>
import { computed } from 'vue';
import IosConfirmDialog from '@/Components/ui/IosConfirmDialog.vue';

const props = defineProps({
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

const data = computed(() => {
    const rows = [{ label: 'Transaction ID', value: `FTR-${props.voucherNumber || 'N/A'}`, color: '#FF3B30' }];
    if (props.payeeName) rows.push({ label: 'Payee', value: props.payeeName });
    if (props.date) rows.push({ label: 'Date Filed', value: props.date });
    return rows;
});

const confirmDelete = () => {
    emit('confirm-delete');
};
</script>
