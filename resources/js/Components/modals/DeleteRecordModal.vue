<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '450px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Confirm Deletion</span>
                    <button class="ios-nav-btn ios-nav-action ios-nav-destructive" @click="deleteRecord"
                        :disabled="deleting">
                        <i class="pi pi-trash"></i>
                    </button>
                </div>
                <div class="ios-body" style="padding: 16px;">
                    <div
                        style="display: flex; flex-direction: column; gap: 12px; align-items: center; text-align: center;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 50%; background: #FFF2F2; display: flex; align-items: center; justify-content: center;">
                            <i class="pi pi-exclamation-triangle" style="font-size: 24px; color: #FF3B30;"></i>
                        </div>
                        <p style="font-size: 15px; font-weight: 600; color: #000;">Are you sure you want to delete this
                            scholarship record?</p>
                        <div v-if="record" class="ios-info-card" style="width: 100%; text-align: left;">
                            <p style="font-size: 14px; font-weight: 500; color: #000;">{{ record.program?.name || 'N/A'
                            }}</p>
                            <p style="font-size: 12px; color: #8E8E93;">{{ record.academic_year }} - {{ record.term }}
                            </p>
                        </div>
                        <p style="font-size: 13px; color: #8E8E93;">This action cannot be undone.</p>
                    </div>
                </div>

            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    visible: { type: Boolean, default: false },
    record: { type: Object, default: null }
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const deleting = ref(false);

const close = () => {
    resetDrag();
    emit('update:visible', false);
};

const deleteRecord = async () => {
    deleting.value = true;
    try {
        if (!props.record) throw new Error('No record selected for deletion');
        const recordId = props.record.id || props.record.grant_id;
        if (!recordId) throw new Error('Record does not have a valid ID');

        await axios.delete(route('scholarship_records.destroy', recordId));
        toast.success('Scholarship record deleted successfully');
        emit('update:visible', false);
        emit('success');
    } catch (error) {
        console.error('Error deleting scholarship record:', error);
        toast.error(error.response?.data?.message || 'Failed to delete scholarship record');
    } finally {
        deleting.value = false;
    }
};
</script>
