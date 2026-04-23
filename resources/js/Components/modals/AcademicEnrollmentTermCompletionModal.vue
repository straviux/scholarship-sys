<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '520px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">{{ dialogTitle }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click.stop="submitCompletion" :disabled="processing">
                        <AppIcon name="check" :size="14" />
                    </button>
                </div>

                <div class="ios-body">
                    <div style="display: flex; flex-direction: column; gap: 12px; padding: 16px 0;">
                        <div v-if="term" class="ios-info-card">
                            <p class="text-sm font-semibold text-gray-900">{{ term.year_level || 'Year level not set' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ term.academic_year || 'No academic year' }}<span v-if="term.term"> · {{ term.term }}</span></p>
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Completion Date</label>
                            <DatePicker v-model="form.completion_date" dateFormat="yy-mm-dd" showIcon fluid />
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Completion Remarks</label>
                            <Textarea v-model="form.completion_remarks" rows="4" autoResize fluid
                                placeholder="Add completion notes for the semester" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { useDraggableModal } from '@/composables/useDraggableModal';

const props = defineProps({
    visible: { type: Boolean, default: false },
    term: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const processing = ref(false);
const form = ref(getDefaultForm());

function getDefaultForm() {
    return {
        completion_date: null,
        completion_remarks: '',
    };
}

const dialogTitle = computed(() => {
    return props.term?.unified_status === 'completed'
        ? 'Update Semester Completion'
        : 'Complete Semester';
});

watch(() => props.visible, (val) => {
    if (!val) {
        return;
    }

    form.value = {
        completion_date: props.term?.date_approved ? new Date(props.term.date_approved) : new Date(),
        completion_remarks: props.term?.remarks ?? '',
    };
});

const formatDateForApi = (value) => {
    if (!value) {
        return null;
    }

    const date = new Date(value);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const close = () => {
    resetDrag();
    emit('update:visible', false);
    form.value = getDefaultForm();
};

const extractErrorMessage = (error, fallback) => {
    const errors = error?.response?.data?.errors;
    if (errors && typeof errors === 'object') {
        const firstError = Object.values(errors).flat()[0];
        if (firstError) {
            return firstError;
        }
    }

    return error?.response?.data?.message || fallback;
};

const submitCompletion = async () => {
    if (!props.term?.id) {
        toast.error('Select an academic term first.');
        return;
    }

    processing.value = true;

    try {
        const payload = {
            completion_date: formatDateForApi(form.value.completion_date),
            completion_remarks: String(form.value.completion_remarks ?? '').trim() || null,
        };

        const response = await axios.put(route('academic-enrollment-terms.complete', props.term.id), payload);

        toast.success(response.data?.message || 'Academic term completed successfully.');
        close();
        emit('success');
    } catch (error) {
        console.error('Failed to complete academic term:', error);
        toast.error(extractErrorMessage(error, 'Failed to complete academic term.'));
    } finally {
        processing.value = false;
    }
};
</script>