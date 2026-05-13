<template>
    <IosModal :visible="show" title="Update Transaction Status" width="500px" max-width="90vw"
        body-style="padding: 16px;" :show-action="true" action-label="Update" :loading="isSaving"
        @action="saveStatus" @update:visible="val => emit('update:show', val)">
        <div v-if="modelValue" class="pt-6 pb-12">
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px;">
                                <p style="font-size: 14px; font-weight: 500; color: #3c3c43;">Transaction ID: {{
                                    modelValue.transaction_id }}</p>
                                <p style="font-size: 12px; color: #8E8E93; margin-top: 2px;">Change the transaction
                                    status for this voucher</p>
                            </div>
                        </div>
                        <div class="ios-section">
                            <p class="ios-section-label">OBR Status</p>
                            <Select v-model="status" :options="statusOptions" placeholder="Select a status"
                                class="w-full" />
                        </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Select from 'primevue/select';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Object,
        default: null
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    isSaving: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'save']);

const status = ref('on process');

watch(() => props.modelValue, (newVal) => {
    if (newVal?.obr_status) {
        status.value = newVal.obr_status;
    }
}, { deep: true });

const saveStatus = () => {
    emit('save', {
        status: status.value
    });
};
</script>
