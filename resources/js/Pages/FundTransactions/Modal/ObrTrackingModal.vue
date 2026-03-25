<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 500px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Update OBR Info</span>
                    <button v-if="!isComplete" class="ios-nav-btn ios-nav-action" @click="saveOBRTracking"
                        :disabled="isSaving">
                        <i v-if="isSaving" class="pi pi-spin pi-spinner"
                            style="font-size: 12px; margin-right: 3px;"></i>Save
                    </button>
                    <span v-else class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
                </div>
                <div class="ios-body">
                    <div class="p-4">
                        <!-- Voucher Header -->
                        <div v-if="modelValue" class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px; background: #eff6ff;">
                                <p style="font-size: 12px; color: #3b82f6; margin-top: 2px;">{{
                                    modelValue.payee_name }}</p>
                            </div>
                        </div>

                        <div class="ios-section" v-if="!isComplete">
                            <div class="ios-card" style="padding: 12px 16px;">
                                <p style="font-size: 13px; color: #6b7280;">Enter OBR details to update this
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
                                        placeholder="e.g., 2025" style="width: 140px; text-align: right;" />
                                </div>
                                <div class="ios-row">
                                    <span class="ios-row-label">OBR Number *</span>
                                    <InputText v-model="formData.obr_no" type="text" placeholder="e.g., 200-25-12-24188"
                                        style="width: 200px; text-align: right;" />
                                </div>
                                <div class="ios-row" style="border-bottom: none;">
                                    <span class="ios-row-label">Date Obligated</span>
                                    <InputText v-model="formData.date_obligated" type="date"
                                        style="width: 160px; text-align: right;" />
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
                                    <p v-if="formData.date_obligated"><strong>Date Obligated:</strong> {{ formData.date_obligated }}</p>
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
import { ref, reactive, computed, onBeforeUnmount } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';

defineProps({
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
const elModal = ref(null);
const formData = reactive({
    fiscal_year: new Date().getFullYear(),
    obr_no: '',
    date_obligated: null
});
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
