<template>
    <IosModal :visible="show" title="Update OBR Info" width="500px" max-width="90vw"
        body-style="padding: 16px;" :show-action="!isComplete" action-label="Save" :loading="isSaving"
        @action="saveOBRTracking" @update:visible="val => emit('update:show', val)">
        <div class="p-4">
                        <!-- Voucher Header -->
                        <div v-if="modelValue" class="ios-section">
                            <div class="ios-card px-4 py-2">
                                <p class="flex justify-between"><span class="text-xs">Payee:</span> <span
                                        class="font-medium text-gray-700 dark:text-gray-100"> {{
                                            modelValue.payee_name }}</span></p>
                                <p class="flex justify-between"><span class="text-xs">OBR No:</span> <span
                                        class="font-medium text-gray-700 dark:text-gray-100">
                                        {{
                                            modelValue.obr_no }}</span></p>
                                <p class="flex justify-between"><span class="text-xs">OBR Type:</span> <span
                                        class="font-medium text-gray-700 dark:text-gray-100">
                                        {{
                                            formatObrTypeLabel(modelValue.obr_type) }}</span></p>
                                <p class="flex justify-between"><span class="text-xs">OBR Status:</span> <span
                                        class="font-medium text-gray-700 dark:text-gray-100">
                                        {{
                                            modelValue.obr_status }}</span></p>
                                <p class="flex justify-between"><span class="text-xs">Disbursement Type:</span> <span
                                        class="font-medium text-gray-700 dark:text-gray-100 capitalize">
                                        {{
                                            modelValue.disbursement_type }}</span></p>
                            </div>
                        </div>

                        <div class="ios-section" v-if="!isComplete">
                            <div class="ios-card px-4 py-2">
                                <p class="text-sm text-gray-500">Enter OBR details to update this
                                    voucher's tracking information</p>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div v-if="!isComplete" class="ios-section">
                            <p class="ios-section-label">OBR Details</p>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Fiscal Year *</span>
                                    <InputText v-model.number="formData.fiscal_year" type="number"
                                        placeholder="e.g., 2025" class="w-48 text-right" />
                                </div>
                                <div class="ios-row">
                                    <span class="ios-row-label">OBR Number *</span>
                                    <InputText v-model="formData.obr_no" type="text" placeholder="e.g., 200-25-12-24188"
                                        class="w-48 text-right" />
                                </div>
                                <div class="ios-row" style="border-bottom: none;">
                                    <span class="ios-row-label">Date Obligated</span>
                                    <InputText v-model="formData.date_obligated" type="date" class="w-48" />
                                </div>
                            </div>
                        </div>

                        <!-- Success Result -->
                        <div v-if="isComplete" class="ios-section" style="margin-bottom: 16px;">
                            <div class="ios-card" style="padding: 14px 16px; background: #f0fdf4;">
                                <p style="font-size: 14px; font-weight: 600; color: #166534; margin-bottom: 8px;">✓
                                    Saved Successfully</p>
                                <div style="font-size: 13px; color: #15803d; line-height: 1.8;">
                                    <p><strong>Fiscal Year:</strong> {{ formData.fiscal_year }}</p>
                                    <p><strong>OBR Number:</strong> {{ formData.obr_no }}</p>
                                    <p v-if="formData.date_obligated"><strong>Date Obligated:</strong> {{
                                        formData.date_obligated }}</p>
                                </div>
                            </div>
                        </div>
        </div>
    </IosModal>
</template>

<script setup>
import { reactive, watch } from 'vue';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import IosModal from '@/Components/ui/IosModal.vue';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';

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
    },
    isComplete: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'save']);

const toast = useToast();
const formData = reactive({
    fiscal_year: new Date().getFullYear(),
    obr_no: '',
    date_obligated: null
});

const normalizeObrTypeValue = (value) => {
    const text = String(value ?? '').trim();
    if (!text) return '';

    return text.toLowerCase().replace(/[\s-]+/g, '_');
};

const formatObrTypeLabel = (value, fallback = '---') => {
    const normalized = normalizeObrTypeValue(value);
    if (!normalized) return fallback;

    return getSystemOptionLabel(
        'disbursement_type',
        normalized,
        normalized
            .split('_')
            .map(segment => segment.charAt(0).toUpperCase() + segment.slice(1))
            .join(' ')
    );
};

// Pre-populate form when dialog opens with voucher data
watch(() => props.show, (visible) => {
    if (visible && props.modelValue) {
        const v = props.modelValue;
        formData.fiscal_year = v.fiscal_year || new Date().getFullYear();
        formData.obr_no = v.obr_no || '';
        formData.date_obligated = v.date_obligated ? v.date_obligated.substring(0, 10) : null;
    }
});

const saveOBRTracking = () => {
    if (!formData.fiscal_year || !formData.obr_no?.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Missing Fields',
            detail: 'Fiscal Year and OBR Number are required.',
            life: 3000
        });
        return;
    }
    emit('save', formData);
};
</script>
