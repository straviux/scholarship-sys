<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    modelValue: Boolean,
    applicant: Object,
    recordId: [Number, String],
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
    { label: 'Recommended for Approval', value: 'recommended' },
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
        academic_potential: null,
        financial_need_level: null,
        communication_skills: null,
        recommendation: null,
        interview_remarks: '',
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
    try {
        await axios.post(`/api/scholarship/${props.recordId}/interview`, form.value);
        toast.success('Interview assessment submitted successfully.');
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
                        {{ submitting ? 'Submitting...' : 'Submit' }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="applicant">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">{{ applicant.last_name }}, {{ applicant.first_name }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label" style="color: #8E8E93; font-weight: 400;">
                                    {{ applicant.scholarship_grant?.[0]?.program?.shortname }} — {{
                                        applicant.scholarship_grant?.[0]?.course?.shortname }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Potential -->
                    <div class="ios-section">
                        <div class="ios-section-label">Academic Potential</div>
                        <div class="ios-card">
                            <div v-for="(option, index) in academicPotentialOptions" :key="option.value" class="ios-row"
                                :class="{ 'ios-row-last': index === academicPotentialOptions.length - 1 }"
                                style="cursor: pointer;" @click="form.academic_potential = option.value">
                                <span class="ios-row-label">{{ option.label }}</span>
                                <i v-if="form.academic_potential === option.value" class="pi pi-check"
                                    style="color: #007AFF; font-size: 14px;"></i>
                            </div>
                        </div>
                        <div v-if="errors.academic_potential" class="ios-section-footer ios-error">{{
                            errors.academic_potential }}</div>
                    </div>

                    <!-- Financial Need -->
                    <div class="ios-section">
                        <div class="ios-section-label">Financial Need</div>
                        <div class="ios-card">
                            <div v-for="(option, index) in financialNeedOptions" :key="option.value" class="ios-row"
                                :class="{ 'ios-row-last': index === financialNeedOptions.length - 1 }"
                                style="cursor: pointer;" @click="form.financial_need_level = option.value">
                                <span class="ios-row-label">{{ option.label }}</span>
                                <i v-if="form.financial_need_level === option.value" class="pi pi-check"
                                    style="color: #007AFF; font-size: 14px;"></i>
                            </div>
                        </div>
                        <div v-if="errors.financial_need_level" class="ios-section-footer ios-error">{{
                            errors.financial_need_level }}</div>
                    </div>

                    <!-- Communication Skills -->
                    <div class="ios-section">
                        <div class="ios-section-label">Communication Skills</div>
                        <div class="ios-card">
                            <div v-for="(option, index) in communicationSkillsOptions" :key="option.value"
                                class="ios-row"
                                :class="{ 'ios-row-last': index === communicationSkillsOptions.length - 1 }"
                                style="cursor: pointer;" @click="form.communication_skills = option.value">
                                <span class="ios-row-label">{{ option.label }}</span>
                                <i v-if="form.communication_skills === option.value" class="pi pi-check"
                                    style="color: #007AFF; font-size: 14px;"></i>
                            </div>
                        </div>
                        <div v-if="errors.communication_skills" class="ios-section-footer ios-error">{{
                            errors.communication_skills }}</div>
                    </div>

                    <!-- Recommendation -->
                    <div class="ios-section">
                        <div class="ios-section-label">Recommendation</div>
                        <div class="ios-card">
                            <div v-for="(option, index) in recommendationOptions" :key="option.value" class="ios-row"
                                :class="{ 'ios-row-last': index === recommendationOptions.length - 1 }"
                                style="cursor: pointer;" @click="form.recommendation = option.value">
                                <span class="ios-row-label">{{ option.label }}</span>
                                <i v-if="form.recommendation === option.value" class="pi pi-check"
                                    style="color: #007AFF; font-size: 14px;"></i>
                            </div>
                        </div>
                        <div v-if="errors.recommendation" class="ios-section-footer ios-error">{{ errors.recommendation
                            }}</div>
                    </div>

                    <!-- Remarks -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
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

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-section-footer {
    font-size: 13px;
    color: #6D6D72;
    padding: 6px 16px 0;
    line-height: 1.3;
}

.ios-error {
    color: #FF3B30;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    font-weight: 500;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
