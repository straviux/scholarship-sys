<template>
    <IosModal :visible="show" title="Add/Edit Remarks" width="500px" max-width="90vw"
        body-style="padding: 16px;" :show-action="true" action-label="Save" :loading="isSaving"
        @action="saveRemarks" @update:visible="val => emit('update:show', val)">
        <div v-if="modelValue">
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px;">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Transaction ID: {{
                                    modelValue.transaction_id }}</p>
                                <p class="text-sm dark:text-gray-400 mt-4">Add or edit remarks for
                                    this voucher</p>
                            </div>
                        </div>
                        <div class="ios-section" style="margin-bottom: 16px;">
                            <p class="ios-section-label">Remarks</p>

                            <Editor :modelValue="remarks" @update:modelValue="remarks = $event"
                                class="ios-card h-48 text-lg">
                                <template #toolbar>
                                    <span class="ql-formats">
                                        <button class="ql-bold"></button>
                                        <button class="ql-italic"></button>
                                        <button class="ql-underline"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-list" value="ordered"></button>
                                        <button class="ql-list" value="bullet"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-clean"></button>
                                    </span>
                                </template>
                            </Editor>
                        </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Editor from 'primevue/editor';
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
    isSaving: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'save']);

const remarks = ref('');
watch(() => props.modelValue, (newVal) => {
    if (newVal?.remarks) {
        remarks.value = newVal.remarks;
    }
}, { deep: true });

const saveRemarks = () => {
    emit('save', remarks.value);
};
</script>
