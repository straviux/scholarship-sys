<template>
    <IosModal
        :visible="visible"
        width="520px"
        :title="dialogTitle"
        :show-action="true"
        :action-disabled="processing"
        @update:visible="val => emit('update:visible', val)"
        @close="close"
        @action="submitGraduation"
    >
        <div style="display: flex; flex-direction: column; gap: 12px; padding: 16px 0;">
            <div v-if="enrollment" class="ios-info-card">
                <p class="text-sm font-semibold text-gray-900">{{ enrollment.program?.name || 'Academic Enrollment' }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ enrollment.course?.name || 'No course selected' }}<span v-if="enrollment.school?.name"> · {{ enrollment.school.name }}</span></p>
            </div>

            <div class="ios-form-group">
                <label class="ios-label">Graduation Date *</label>
                <DatePicker v-model="form.graduation_date" dateFormat="yy-mm-dd" showIcon fluid />
            </div>

            <div class="ios-form-group">
                <label class="ios-label">Graduation Remarks</label>
                <Textarea v-model="form.graduation_remarks" rows="4" autoResize fluid
                    placeholder="Add graduation notes or supporting context" />
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    visible: { type: Boolean, default: false },
    enrollment: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'success']);

const processing = ref(false);
const form = ref(getDefaultForm());

function getDefaultForm() {
    return {
        graduation_date: null,
        graduation_remarks: '',
    };
}

const dialogTitle = computed(() => {
    return props.enrollment?.graduation_date ? 'Update Graduation' : 'Record Graduation';
});

watch(() => props.visible, (val) => {
    if (!val) {
        return;
    }

    form.value = {
        graduation_date: props.enrollment?.graduation_date ? new Date(props.enrollment.graduation_date) : new Date(),
        graduation_remarks: props.enrollment?.graduation_remarks ?? '',
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

const submitGraduation = async () => {
    if (!props.enrollment?.id) {
        toast.error('Select an academic enrollment first.');
        return;
    }

    if (!form.value.graduation_date) {
        toast.error('Graduation date is required.');
        return;
    }

    processing.value = true;

    try {
        const payload = {
            graduation_date: formatDateForApi(form.value.graduation_date),
            graduation_remarks: String(form.value.graduation_remarks ?? '').trim() || null,
        };

        const response = await axios.put(route('academic-enrollments.graduate', props.enrollment.id), payload);

        toast.success(response.data?.message || 'Graduation details saved successfully.');
        close();
        emit('success');
    } catch (error) {
        console.error('Failed to save graduation details:', error);
        toast.error(extractErrorMessage(error, 'Failed to save graduation details.'));
    } finally {
        processing.value = false;
    }
};
</script>