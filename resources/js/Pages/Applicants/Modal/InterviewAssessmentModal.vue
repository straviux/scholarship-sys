<template>
    <Dialog v-model:visible="visible" modal header="Interviewer's Assessment" :style="{ width: '650px' }"
        @hide="onHide">
        <div v-if="applicant" class="space-y-5">
            <!-- Applicant Info -->
            <div class="p-3 bg-blue-50 border border-blue-200 rounded">
                <div class="font-semibold text-blue-900">
                    {{ applicant.last_name }}, {{ applicant.first_name }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ applicant.scholarship_grant?.[0]?.program?.shortname }} -
                    {{ applicant.scholarship_grant?.[0]?.course?.shortname }}
                </div>
            </div>

            <!-- INTERVIEWER'S ASSESSMENT -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3 pb-2 border-b">INTERVIEWER'S ASSESSMENT</h3>

                <!-- Academic Potential -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Academic Potential</label>
                    <div class="flex gap-4">
                        <div v-for="option in academicPotentialOptions" :key="option.value"
                            class="flex items-center gap-2">
                            <RadioButton v-model="form.academic_potential" :inputId="`ap_${option.value}`"
                                :value="option.value" />
                            <label :for="`ap_${option.value}`" class="text-sm cursor-pointer">{{ option.label }}</label>
                        </div>
                    </div>
                    <small v-if="errors.academic_potential" class="text-red-500">{{ errors.academic_potential }}</small>
                </div>

                <!-- Financial Need -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Financial Need</label>
                    <div class="flex gap-4">
                        <div v-for="option in financialNeedOptions" :key="option.value" class="flex items-center gap-2">
                            <RadioButton v-model="form.financial_need_level" :inputId="`fn_${option.value}`"
                                :value="option.value" />
                            <label :for="`fn_${option.value}`" class="text-sm cursor-pointer">{{ option.label }}</label>
                        </div>
                    </div>
                    <small v-if="errors.financial_need_level" class="text-red-500">{{ errors.financial_need_level
                    }}</small>
                </div>

                <!-- Communication Skills -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Communication Skills</label>
                    <div class="flex gap-4">
                        <div v-for="option in communicationSkillsOptions" :key="option.value"
                            class="flex items-center gap-2">
                            <RadioButton v-model="form.communication_skills" :inputId="`cs_${option.value}`"
                                :value="option.value" />
                            <label :for="`cs_${option.value}`" class="text-sm cursor-pointer">{{ option.label }}</label>
                        </div>
                    </div>
                    <small v-if="errors.communication_skills" class="text-red-500">{{ errors.communication_skills
                    }}</small>
                </div>
            </div>

            <!-- RECOMMENDATION -->
            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3 pb-2 border-b">RECOMMENDATION</h3>

                <div class="mb-4 space-y-2">
                    <div v-for="option in recommendationOptions" :key="option.value" class="flex items-center gap-2">
                        <RadioButton v-model="form.recommendation" :inputId="`rec_${option.value}`"
                            :value="option.value" />
                        <label :for="`rec_${option.value}`" class="text-sm cursor-pointer">{{ option.label }}</label>
                    </div>
                    <small v-if="errors.recommendation" class="text-red-500">{{ errors.recommendation }}</small>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <Textarea v-model="form.interview_remarks" rows="3" class="w-full"
                        placeholder="Additional remarks or notes..." />
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="visible = false" />
            <Button label="Submit Assessment" icon="pi pi-check" severity="success" @click="submitAssessment"
                :loading="submitting" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
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

const onHide = () => {
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
</script>
