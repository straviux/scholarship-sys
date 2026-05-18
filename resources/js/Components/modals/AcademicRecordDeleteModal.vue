<template>
    <IosModal
        :visible="visible"
        width="460px"
        title="Confirm Deletion"
        :show-action="true"
        action-icon="trash"
        :action-disabled="processing"
        action-class="ios-nav-destructive"
        body-style="padding: 16px;"
        @update:visible="val => emit('update:visible', val)"
        @close="close"
        @action="deleteTargetEntity"
    >
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: center; text-align: center;">
            <div
                style="width: 48px; height: 48px; border-radius: 50%; background: #FFF2F2; display: flex; align-items: center; justify-content: center;">
                <AppIcon name="exclamation-triangle" :size="24" style="color: #FF3B30;" />
            </div>
            <p style="font-size: 15px; font-weight: 600; color: #000;">{{ promptText }}</p>

            <div v-if="target" class="ios-info-card" style="width: 100%; text-align: left;">
                <template v-if="targetType === 'enrollment'">
                    <p style="font-size: 14px; font-weight: 500; color: #000;">{{ target.program?.name || 'Academic Enrollment' }}</p>
                    <p style="font-size: 12px; color: #8E8E93;">{{ target.course?.name || 'No course selected' }}<span v-if="target.school?.name"> · {{ target.school.name }}</span></p>
                    <p v-if="Array.isArray(target.terms)" style="font-size: 12px; color: #8E8E93; margin-top: 6px;">{{ target.terms.length }} linked term{{ target.terms.length === 1 ? '' : 's' }} will also be removed.</p>
                </template>
                <template v-else>
                    <p style="font-size: 14px; font-weight: 500; color: #000;">{{ target.year_level || 'Academic Term' }}</p>
                    <p style="font-size: 12px; color: #8E8E93;">{{ target.academic_year || 'No academic year' }}<span v-if="target.term"> · {{ target.term }}</span></p>
                </template>
            </div>

            <p style="font-size: 13px; color: #8E8E93;">This action cannot be undone.</p>
        </div>
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';
import { computed, ref } from 'vue';
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    visible: { type: Boolean, default: false },
    target: { type: Object, default: null },
    targetType: {
        type: String,
        default: 'term',
        validator: (value) => ['enrollment', 'term'].includes(value),
    },
});

const emit = defineEmits(['update:visible', 'success']);

const processing = ref(false);

const promptText = computed(() => {
    return props.targetType === 'enrollment'
        ? 'Are you sure you want to delete this academic enrollment and all of its terms?'
        : 'Are you sure you want to delete this academic term?';
});

const close = () => {
    emit('update:visible', false);
};

const deleteRoute = computed(() => {
    if (!props.target?.id) {
        return null;
    }

    return props.targetType === 'enrollment'
        ? route('academic-enrollments.destroy', props.target.id)
        : route('academic-enrollment-terms.destroy', props.target.id);
});

const deleteTargetEntity = async () => {
    if (!deleteRoute.value) {
        toast.error('Nothing is selected for deletion.');
        return;
    }

    processing.value = true;

    try {
        const response = await axios.delete(deleteRoute.value);
        toast.success(response.data?.message || 'Academic record deleted successfully.');
        emit('update:visible', false);
        emit('success');
    } catch (error) {
        console.error('Failed to delete academic record:', error);
        toast.error(error?.response?.data?.message || 'Failed to delete academic record.');
    } finally {
        processing.value = false;
    }
};
</script>