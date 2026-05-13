<template>
    <IosModal :visible="show" title="Fund Transaction Details" width="700px" max-width="90vw"
        body-style="padding: 16px;" @update:visible="val => emit('update:show', val)">
        <slot></slot>
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';

defineProps({
    show: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['update:show']);
</script>
