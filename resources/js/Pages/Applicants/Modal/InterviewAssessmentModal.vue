<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    modelValue: Boolean,
    applicant: Object,
    recordId: [Number, String],
    initialValues: Object,
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

const form = ref({
    academic_potential: null,
    financial_need_level: null,
    communication_skills: null,
    recommendation: null,
    interview_remarks: '',
});

const academicPotentialOptions = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good', value: 'good' },
    { label: 'Fair', value: 'fair' },
];

const financialNeedOptions = [
    { label: 'High', value: 'high' },
    { label: 'Moderate', value: 'moderate' },
    { label: 'Low', value: 'low' },
];

const communicationSkillsOptions = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good', value: 'good' },
    { label: 'Fair', value: 'fair' },
];

const recommendationOptions = [
    { label: 'For Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' },
];

watch(() => props.modelValue, (val) => {
    visible.value = val;
    if (val) resetForm();
});

watch(visible, (val) => {
    emit('update:modelValue', val);
});

const resetForm = () => {
    form.value = {
        academic_potential: props.initialValues?.academic_potential || null,
        financial_need_level: props.initialValues?.financial_need_level || null,
        communication_skills: props.initialValues?.communication_skills || null,
        recommendation: props.initialValues?.recommendation || null,
        interview_remarks: props.initialValues?.interview_remarks || '',
    };
    errors.value = {};
};

const close = () => {
    visible.value = false;
    resetForm();
};

const validate = () => {
    const errs = {};
    if (!form.value.academic_potential) errs.academic_potential = 'Please select academic potential.';
    if (!form.value.financial_need_level) errs.financial_need_level = 'Please select financial need level.';
    if (!form.value.communication_skills) errs.communication_skills = 'Please select communication skills.';
    if (!form.value.recommendation) errs.recommendation = 'Please select a recommendation.';
    errors.value = errs;
    return Object.keys(errs).length === 0;
};

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

    try {
        await axios.post(endpoint, form.value);
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
    width: '600px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-radiobutton, .p-editor')) return;
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
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Interview Assessment</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submitAssessment" :disabled="submitting">
                        <i class="pi pi-check" style="font-size: 18px; color: #34C759;"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="applicant">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-user" style="color: #007AFF; margin-right: 8px;"></i>
                            Applicant Information
                        </div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label applicant-name">{{ applicant.last_name }}, {{
                                    applicant.first_name }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label applicant-sub">
                                    {{ applicant.scholarship_grant?.[0]?.program?.shortname }} — {{
                                        applicant.scholarship_grant?.[0]?.course?.shortname }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Potential -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-graduation-cap" style="color: #007AFF; margin-right: 8px;"></i>
                            Academic Potential
                        </div>
                        <div class="ios-segmented-control">
                            <button v-for="option in academicPotentialOptions" :key="option.value"
                                :class="['ios-segment', form.academic_potential === option.value && 'ios-segment-active']"
                                @click="form.academic_potential = option.value">
                                <span>{{ option.label }}</span>
                                <span
                                    style="width: 16px; height: 13px; display: inline-flex; align-items: center; justify-content: center;">
                                    <transition name="fade-scale">
                                        <i v-if="form.academic_potential === option.value" class="pi pi-check"></i>
                                    </transition>
                                </span>
                            </button>
                        </div>
                        <div v-if="errors.academic_potential" class="ios-section-footer ios-error">{{
                            errors.academic_potential }}</div>
                    </div>

                    <!-- Financial Need -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-wallet" style="color: #34C759; margin-right: 8px;"></i>
                            Financial Need
                        </div>
                        <div class="ios-segmented-control">
                            <button v-for="option in financialNeedOptions" :key="option.value"
                                :class="['ios-segment', form.financial_need_level === option.value && 'ios-segment-active']"
                                @click="form.financial_need_level = option.value">
                                <span>{{ option.label }}</span>
                                <span
                                    style="width: 16px; height: 13px; display: inline-flex; align-items: center; justify-content: center;">
                                    <transition name="fade-scale">
                                        <i v-if="form.financial_need_level === option.value" class="pi pi-check"></i>
                                    </transition>
                                </span>
                            </button>
                        </div>
                        <div v-if="errors.financial_need_level" class="ios-section-footer ios-error">{{
                            errors.financial_need_level }}</div>
                    </div>

                    <!-- Communication Skills -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-comments" style="color: #FF9500; margin-right: 8px;"></i>
                            Communication Skills
                        </div>
                        <div class="ios-segmented-control">
                            <button v-for="option in communicationSkillsOptions" :key="option.value"
                                :class="['ios-segment', form.communication_skills === option.value && 'ios-segment-active']"
                                @click="form.communication_skills = option.value">
                                <span>{{ option.label }}</span>
                                <span
                                    style="width: 16px; height: 13px; display: inline-flex; align-items: center; justify-content: center;">
                                    <transition name="fade-scale">
                                        <i v-if="form.communication_skills === option.value" class="pi pi-check"></i>
                                    </transition>
                                </span>
                            </button>
                        </div>
                        <div v-if="errors.communication_skills" class="ios-section-footer ios-error">{{
                            errors.communication_skills }}</div>
                    </div>

                    <!-- Recommendation -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-thumbs-up" style="color: #5856D6; margin-right: 8px;"></i>
                            Recommendation
                        </div>
                        <div class="ios-segmented-control">
                            <button v-for="option in recommendationOptions" :key="option.value"
                                :class="['ios-segment', form.recommendation === option.value && 'ios-segment-active']"
                                @click="form.recommendation = option.value">
                                <span>{{ option.label }}</span>
                                <span
                                    style="width: 16px; height: 13px; display: inline-flex; align-items: center; justify-content: center;">
                                    <transition name="fade-scale">
                                        <i v-if="form.recommendation === option.value" class="pi pi-check"></i>
                                    </transition>
                                </span>
                            </button>
                        </div>
                        <div v-if="errors.recommendation" class="ios-section-footer ios-error">{{ errors.recommendation
                            }}</div>
                    </div>

                    <!-- Remarks -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            <i class="pi pi-pencil" style="color: #FF3B30; margin-right: 8px;"></i>
                            Remarks
                        </div>
                        <div class="ios-card">
                            <Editor v-model="form.interview_remarks" editorStyle="height: 120px">
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
