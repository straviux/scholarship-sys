<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '620px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">{{ mode === 'add' ? `Add Academic Enrollment` : `Edit Academic
                        Enrollment` }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click.stop="submitEnrollment" :disabled="processing">
                        <AppIcon name="check" :size="14" />
                    </button>
                </div>

                <div class="ios-body">
                    <div class="flex flex-col gap-4 pt-4 pb-8 px-2">
                        <div class="flex flex-col gap-4 p-2">
                            <div class="ios-form-group">
                                <label class="ios-label">Program</label>
                                <ProgramSelect v-model="form.program_id" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Course</label>
                                <CourseSelect v-model="form.course_id" :scholarship-program-id="selectedProgramId" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">School *</label>
                                <SchoolSelect v-model="form.school_id" />
                            </div>
                        </div>

                        <div class="ios-info-card">
                            <p class="text-sm font-medium text-gray-900">Enrollment-level details group multiple
                                academic terms under the same school and course.</p>
                            <p class="mt-1 text-xs text-gray-500">Use the enrollment actions to add terms or record
                                graduation details after saving this group.</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { useDraggableModal } from '@/composables/useDraggableModal';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    mode: { type: String, default: 'add' },
    enrollment: { type: Object, default: null },
    profileId: { type: [Number, String], required: true },
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const processing = ref(false);
const form = ref(getDefaultForm());

function getDefaultForm() {
    return {
        program_id: null,
        school_id: null,
        course_id: null,
    };
}

const selectedProgramId = computed(() => {
    return typeof form.value.program_id === 'object'
        ? form.value.program_id?.id ?? null
        : form.value.program_id;
});

watch(() => props.visible, async (val) => {
    if (!val) {
        return;
    }

    if (props.mode === 'edit' && props.enrollment?.id) {
        await loadEnrollment(props.enrollment.id);
        return;
    }

    form.value = getDefaultForm();
    await nextTick();
});

const normalizeId = (value) => {
    if (!value) {
        return null;
    }

    return typeof value === 'object' ? value.id ?? null : value;
};

const fillForm = (enrollment) => {
    form.value = {
        program_id: enrollment?.program ?? enrollment?.program_id ?? null,
        school_id: enrollment?.school ?? enrollment?.school_id ?? null,
        course_id: enrollment?.course ?? enrollment?.course_id ?? null,
    };
};

const loadEnrollment = async (enrollmentId) => {
    processing.value = true;

    try {
        const response = await axios.get(route('academic-enrollments.show', enrollmentId));
        fillForm(response.data?.data ?? props.enrollment);
        await nextTick();
    } catch (error) {
        console.error('Failed to load academic enrollment:', error);
        toast.error('Failed to load academic enrollment details.');
        close();
    } finally {
        processing.value = false;
    }
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

const submitEnrollment = async () => {
    const schoolId = normalizeId(form.value.school_id);

    if (!schoolId) {
        toast.error('School is required.');
        return;
    }

    processing.value = true;

    try {
        const payload = {
            program_id: normalizeId(form.value.program_id),
            school_id: schoolId,
            course_id: normalizeId(form.value.course_id),
        };

        const response = props.mode === 'add'
            ? await axios.post(route('academic-enrollments.store', props.profileId), payload)
            : await axios.put(route('academic-enrollments.update', props.enrollment.id), payload);

        toast.success(response.data?.message || 'Academic enrollment saved successfully.');
        close();
        emit('success');
    } catch (error) {
        console.error('Failed to save academic enrollment:', error);
        toast.error(extractErrorMessage(error, 'Failed to save academic enrollment.'));
    } finally {
        processing.value = false;
    }
};
</script>