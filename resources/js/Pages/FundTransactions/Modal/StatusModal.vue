<template>
    <IosModal :visible="show" title="Update Transaction Status" width="500px" max-width="90vw"
        body-style="padding: 16px;" :show-action="true" :loading="isSaving"
        :action-disabled="!statusUpdatedAt" @action="saveStatus" @update:visible="val => emit('update:show', val)">
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
                <p class="ios-section-label text-compact">OBR Status</p>
                <Select v-model="status" :options="statusOptions" placeholder="Select a status" class="w-full">
                    <template #value="{ value }">
                        <div v-if="value" class="flex items-center gap-2">
                            <AppIcon :name="getStatusIcon(value)" :size="14" :class="getStatusTextClass(value)" />
                            <span>{{ value }}</span>
                        </div>
                    </template>
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <AppIcon :name="getStatusIcon(option)" :size="14" :class="getStatusTextClass(option)" />
                            <span>{{ option }}</span>
                        </div>
                    </template>
                </Select>
            </div>
            <div class="ios-section">
                <p class="ios-section-label text-compact">Status Updated Date <span class="text-red-500">*</span></p>
                <DatePicker v-model="statusUpdatedAt" placeholder="Select date" class="w-full" />
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import IosModal from '@/Components/ui/IosModal.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import { getStatusIcon, getStatusTextClass } from '@/Pages/FundTransactions/statusMeta';

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
const statusUpdatedAt = ref(new Date());

watch(() => props.modelValue, (newVal) => {
    if (newVal?.obr_status) {
        status.value = newVal.obr_status;
    }
    statusUpdatedAt.value = newVal?.status_updated_at ? new Date(newVal.status_updated_at) : new Date();
}, { deep: true });

// Send a plain YYYY-MM-DD string so the picked calendar day is preserved
// regardless of the browser's timezone (toISOString() would shift it).
const formatDateOnly = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const saveStatus = () => {
    emit('save', {
        status: status.value,
        status_updated_at: formatDateOnly(statusUpdatedAt.value)
    });
};
</script>
