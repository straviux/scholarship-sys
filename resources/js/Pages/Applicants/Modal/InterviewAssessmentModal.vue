<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import InterviewAssessmentForm from '@/Components/forms/InterviewAssessmentForm.vue';
import { toast } from '@/utils/toast';

const props = defineProps({
    modelValue: Boolean,
    applicant: Object,
    recordId: [Number, String],
    initialValues: Object,
    interviewers: {
        type: Array,
        default: () => [],
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
    successMessage: {
        type: String,
        default: 'Interview assessment submitted successfully.',
    },
});

const emit = defineEmits(['update:modelValue', 'submitted']);

const visible = ref(props.modelValue);
const submitting = ref(false);
const errors = ref({});
const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const applicantScholarshipGrant = computed(() => props.applicant?.scholarship_grant?.[0] ?? null);
const applicantDisplayName = computed(() => {
    if (!props.applicant) {
        return 'N/A';
    }

    return `${props.applicant.last_name || 'N/A'}, ${props.applicant.first_name || 'N/A'}`;
});

const applicantDisplaySubtitle = computed(() => {
    const program = form.value?.program || applicantScholarshipGrant.value?.program || props.applicant?.program || null;
    const course = form.value?.course || applicantScholarshipGrant.value?.course || props.applicant?.course || null;
    const programLabel = program?.shortname || program?.name || 'N/A';
    const courseLabel = course?.shortname || course?.name || 'N/A';

    return `${programLabel} - ${courseLabel}`;
});

const form = ref({
    program: null,
    course: null,
    school: null,
    year_level: null,
    term: null,
    academic_year: null,
    academic_potential: null,
    financial_need_level: null,
    communication_skills: null,
    recommendation: null,
    grant_provision: null,
    interview_date: null,
    interviewer_id: null,
    endorsed_by: '',
    interview_remarks: '',
});

const formatDateForPicker = (value) => {
    if (!value) return null;
    if (value instanceof Date) return value;

    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    const parsedDate = new Date(value);
    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
};

const formatDateForBackend = (value) => {
    if (!value) return null;

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return null;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const buildInterviewerOptions = (...users) => {
    const seen = new Set();

    return users
        .flatMap((user) => Array.isArray(user) ? user : [user])
        .filter((user) => user?.id && user?.name)
        .filter((user) => {
            if (seen.has(user.id)) {
                return false;
            }

            seen.add(user.id);
            return true;
        })
        .map((user) => ({
            id: user.id,
            name: user.name,
        }));
};

const interviewerOptions = computed(() => buildInterviewerOptions(props.interviewers));

const resolveInterviewerId = (...candidateIds) => {
    return candidateIds.find((candidateId) => interviewerOptions.value.some((option) => option.id === candidateId)) || null;
};

const extractOptionId = (value) => {
    if (value && typeof value === 'object') {
        return value.id ?? null;
    }

    return value || null;
};

const extractOptionValue = (value) => {
    if (value && typeof value === 'object') {
        return value.value ?? value.label ?? null;
    }

    return value || null;
};

watch(() => props.modelValue, (val) => {
    visible.value = val;
    if (val) resetForm();
});

watch(visible, (val) => {
    emit('update:modelValue', val);
});

const createFormState = () => ({
    program: props.initialValues?.program || applicantScholarshipGrant.value?.program || props.applicant?.program || null,
    course: props.initialValues?.course || applicantScholarshipGrant.value?.course || props.applicant?.course || null,
    school: props.initialValues?.school || applicantScholarshipGrant.value?.school || props.applicant?.school || null,
    year_level: props.initialValues?.year_level || applicantScholarshipGrant.value?.year_level || props.applicant?.year_level || null,
    term: props.initialValues?.term || applicantScholarshipGrant.value?.term || props.applicant?.term || null,
    academic_year: props.initialValues?.academic_year || applicantScholarshipGrant.value?.academic_year || props.applicant?.academic_year || null,
    academic_potential: props.initialValues?.academic_potential || null,
    financial_need_level: props.initialValues?.financial_need_level || null,
    communication_skills: props.initialValues?.communication_skills || null,
    recommendation: props.initialValues?.recommendation || null,
    grant_provision: props.initialValues?.grant_provision || applicantScholarshipGrant.value?.grant_provision || null,
    interview_date: formatDateForPicker(props.initialValues?.interview_date || props.initialValues?.interviewed_at)
        || new Date(),
    interviewer_id: resolveInterviewerId(
        props.initialValues?.interviewer_id,
        props.initialValues?.interviewed_by,
        props.initialValues?.interviewer?.id,
        currentUser.value?.id,
    ),
    endorsed_by: props.initialValues?.endorsed_by || '',
    interview_remarks: props.initialValues?.interview_remarks || '',
});

const resetForm = () => {
    form.value = createFormState();
    errors.value = {};
};

form.value = createFormState();

const close = () => {
    visible.value = false;
    resetForm();
};

const validate = () => {
    const errs = {};
    if (!extractOptionId(form.value.program)) errs.program_id = 'Please select program.';
    if (!extractOptionId(form.value.course)) errs.course_id = 'Please select course.';
    if (!extractOptionId(form.value.school)) errs.school_id = 'Please select school.';
    if (!extractOptionValue(form.value.year_level)) errs.year_level = 'Please select year level.';
    if (!extractOptionValue(form.value.term)) errs.term = 'Please select term.';
    if (!extractOptionValue(form.value.academic_year)) errs.academic_year = 'Please select academic year.';
    if (!form.value.academic_potential) errs.academic_potential = 'Please select academic potential.';
    if (!form.value.financial_need_level) errs.financial_need_level = 'Please select financial need level.';
    if (!form.value.communication_skills) errs.communication_skills = 'Please select communication skills.';
    if (!form.value.recommendation) errs.recommendation = 'Please select a recommendation.';
    if (!form.value.interview_date || !formatDateForBackend(form.value.interview_date)) errs.interview_date = 'Please select interview date.';
    if (!form.value.interviewer_id) errs.interviewer_id = 'Please select interviewer.';
    errors.value = errs;
    return Object.keys(errs).length === 0;
};

watch(() => form.value.grant_provision, () => {
    if (errors.value.grant_provision) {
        delete errors.value.grant_provision;
    }
});

watch(() => extractOptionId(form.value.program), (value) => {
    if (value && errors.value.program_id) {
        delete errors.value.program_id;
    }
});

watch(() => extractOptionId(form.value.course), (value) => {
    if (value && errors.value.course_id) {
        delete errors.value.course_id;
    }
});

watch(() => extractOptionId(form.value.school), (value) => {
    if (value && errors.value.school_id) {
        delete errors.value.school_id;
    }
});

watch(() => extractOptionValue(form.value.year_level), (value) => {
    if (value && errors.value.year_level) {
        delete errors.value.year_level;
    }
});

watch(() => extractOptionValue(form.value.term), (value) => {
    if (value && errors.value.term) {
        delete errors.value.term;
    }
});

watch(() => extractOptionValue(form.value.academic_year), (value) => {
    if (value && errors.value.academic_year) {
        delete errors.value.academic_year;
    }
});

watch(() => form.value.interview_date, () => {
    if (errors.value.interview_date) {
        delete errors.value.interview_date;
    }
});

watch(() => form.value.interviewer_id, () => {
    if (errors.value.interviewer_id) {
        delete errors.value.interviewer_id;
    }
});

watch(() => form.value.endorsed_by, () => {
    if (errors.value.endorsed_by) {
        delete errors.value.endorsed_by;
    }
});

watch(interviewerOptions, () => {
    if (!interviewerOptions.value.length || interviewerOptions.value.some((option) => option.id === form.value.interviewer_id)) {
        return;
    }

    form.value.interviewer_id = resolveInterviewerId(currentUser.value?.id);
});

const submitAssessment = async () => {
    if (!validate()) return;
    if (!props.recordId) {
        toast.error('No scholarship record selected.');
        return;
    }

    submitting.value = true;
    const endpoint = props.isEdit
        ? `/api/scholarship/${props.recordId}/update-interview`
        : `/api/scholarship/${props.recordId}/interview`;
    const payload = {
        ...form.value,
        program_id: extractOptionId(form.value.program),
        course_id: extractOptionId(form.value.course),
        school_id: extractOptionId(form.value.school),
        year_level: extractOptionValue(form.value.year_level),
        term: extractOptionValue(form.value.term),
        academic_year: extractOptionValue(form.value.academic_year),
        interview_date: formatDateForBackend(form.value.interview_date),
        endorsed_by: form.value.endorsed_by?.trim() || null,
    };

    try {
        await axios.post(endpoint, payload);
        toast.success(props.successMessage);
        visible.value = false;
        emit('submitted');
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors) {
            errors.value = {};
            for (const [key, messages] of Object.entries(error.response.data.errors)) {
                errors.value[key] = messages[0];
            }
        } else {
            toast.error(error.response?.data?.message || 'Failed to submit interview assessment.');
        }
    } finally {
        submitting.value = false;
    }
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: 'min(680px, 96vw)',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-radiobutton, .p-editor, .p-datepicker')) return;
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
</script>

<template>
    <Dialog :visible="visible" modal @update:visible="val => { if (!val) close(); }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button type="button" class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Interview Assessment</span>
                    <button type="button" class="ios-nav-btn ios-nav-action" @click="submitAssessment"
                        :disabled="submitting">
                        <AppIcon name="check" :size="18" style="color: #34C759;" />
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="applicant">
                    <InterviewAssessmentForm :subject-name="applicantDisplayName"
                        :subject-subtitle="applicantDisplaySubtitle" :form="form" :errors="errors"
                        :interviewers="props.interviewers" />

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style>
.applicant-name {
    color: #333;
}

.applicant-sub {
    color: #999;
    font-weight: 400;
}

.dark .applicant-name {
    color: #d1d5db !important;
}

.dark .applicant-sub {
    color: #9ca3af !important;
}
</style>
