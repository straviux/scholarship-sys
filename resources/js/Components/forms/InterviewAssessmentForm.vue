<script setup>
import { computed, watch } from 'vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import UserSelect from '@/Components/selects/UserSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import { useSystemOptions } from '@/composables/useSystemOptions';

const props = defineProps({
    subjectName: {
        type: String,
        default: 'N/A',
    },
    subjectSubtitle: {
        type: String,
        default: '',
    },
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    interviewers: {
        type: Array,
        default: () => [],
    },
    showValidationSummary: {
        type: Boolean,
        default: true,
    },
});

const grantProvisionRaw = useSystemOptions('grant_provision');

const validationLabels = {
    program_id: 'Program',
    course_id: 'Course',
    school_id: 'School',
    year_level: 'Year Level',
    term: 'Term',
    academic_year: 'Academic Year',
    interview_date: 'Interview Date',
    interviewer_id: 'Interviewer',
    endorsed_by: 'Endorsed By',
    academic_potential: 'Academic Potential',
    financial_need_level: 'Financial Need',
    communication_skills: 'Communication Skills',
    recommendation: 'Recommendation',
    grant_provision: 'Grant Provision',
    interview_remarks: 'Remarks',
};

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

const extractOptionId = (value) => {
    if (value && typeof value === 'object') {
        return value.id ?? null;
    }

    return value || null;
};

const buildInterviewerOptions = (...users) => {
    const seen = new Set();

    return users
        .flatMap((user) => Array.isArray(user) ? user : [user])
        .filter((user) => user?.id && user?.name)
        .filter((user) => {
            const key = String(user.id);

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        })
        .map((user) => ({
            id: user.id,
            name: user.name,
            email: user.email ?? '',
        }));
};

const interviewerOptions = computed(() => buildInterviewerOptions(props.interviewers));

const selectedProgramId = computed(() => extractOptionId(props.form?.program));

const selectedProgramCode = computed(() => {
    const program = props.form?.program;

    if (program && typeof program === 'object') {
        return program.shortname ?? null;
    }

    return null;
});

const validationSummary = computed(() => {
    const activeLabels = Object.entries(validationLabels)
        .filter(([field]) => Boolean(props.errors?.[field]))
        .map(([, label]) => label);

    if (!activeLabels.length) {
        return '';
    }

    return `Review the highlighted fields before saving: ${activeLabels.join(', ')}.`;
});

const grantProvisionOptions = computed(() => {
    if (!selectedProgramCode.value) {
        return [];
    }

    return grantProvisionRaw.value
        .filter((option) => !option.program || option.program === selectedProgramCode.value)
        .map((option) => ({
            ...option,
            label: option.label || option.value,
        }));
});

const coursePlaceholder = computed(() => {
    return selectedProgramId.value ? 'Select course' : 'Select program first';
});

const grantProvisionPlaceholder = computed(() => {
    if (!selectedProgramCode.value) {
        return 'Select program first';
    }

    if (!grantProvisionOptions.value.length) {
        return 'No grant provision available';
    }

    return 'Select grant provision';
});

const grantProvisionHint = computed(() => {
    if (!selectedProgramCode.value) {
        return 'Grant provision options appear after selecting a program.';
    }

    if (!grantProvisionOptions.value.length) {
        return `No grant provision options are configured for ${selectedProgramCode.value}.`;
    }

    return `Grant provision options are filtered for ${selectedProgramCode.value}.`;
});

const compactSelectPt = {
    root: { style: 'min-height: 2.25rem;' },
    label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
    dropdown: { style: 'width: 2.25rem;' },
};

watch(
    () => [selectedProgramCode.value, grantProvisionOptions.value.map((option) => option.value).join('|')],
    () => {
        const currentGrantProvision = props.form?.grant_provision;

        if (!props.form) {
            return;
        }

        if (!selectedProgramCode.value) {
            props.form.grant_provision = null;
            return;
        }

        if (currentGrantProvision && !grantProvisionOptions.value.some((option) => option.value === currentGrantProvision)) {
            props.form.grant_provision = null;
        }
    }
);
</script>

<template>
    <div class="interview-assessment-form">
        <div v-if="showValidationSummary && validationSummary" class="ios-section ios-section-tight">
            <div class="ios-card ios-validation-summary">
                <div class="ios-validation-summary-icon">
                    <AppIcon name="exclamation-triangle" :size="16" />
                </div>
                <div>
                    <div class="ios-validation-summary-title">Review Required Fields</div>
                    <p class="ios-validation-summary-text">{{ validationSummary }}</p>
                </div>
            </div>
        </div>

        <div class="ios-section">
            <div class="ios-section-label">Applicant</div>
            <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                <div class="ios-row">
                    <div class="ios-row-label interview-assessment-form__name">
                        {{ subjectName }}
                    </div>
                </div>
                <div class="ios-row ios-row-last">
                    <div class="ios-row-label text-sm text-gray-600 font-normal">
                        {{ subjectSubtitle || 'N/A - N/A' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="ios-section">
            <div class="ios-section-label">Academic Details</div>
            <div class="ios-card">
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="book-open" :size="13" style="color: #007AFF;" />
                        Program
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.program_id }]">
                            <ProgramSelect v-model="form.program" custom-placeholder="Select program"
                                class="ios-select" :show-clear="false" :ios-compact="true" />
                            <small v-if="errors.program_id" class="ios-field-error">{{ errors.program_id }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="graduation-cap" :size="13" style="color: #34C759;" />
                        Course
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.course_id }]">
                            <CourseSelect v-model="form.course" :scholarship-program-id="selectedProgramId"
                                :custom-placeholder="coursePlaceholder" class="ios-select" :show-clear="false"
                                :ios-compact="true" />
                            <small v-if="!selectedProgramId && !errors.course_id" class="ios-field-hint">
                                Select a program first to narrow the course list.
                            </small>
                            <small v-if="errors.course_id" class="ios-field-error">{{ errors.course_id }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="building-2" :size="13" style="color: #FF9500;" />
                        School
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.school_id }]">
                            <SchoolSelect v-model="form.school" custom-placeholder="Select school" class="ios-select"
                                :show-clear="false" :ios-compact="true" />
                            <small v-if="errors.school_id" class="ios-field-error">{{ errors.school_id }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="list-checks" :size="13" style="color: #5856D6;" />
                        Year Level
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.year_level }]">
                            <YearLevelSelect v-model="form.year_level" class="ios-select" :show-clear="false"
                                :ios-compact="true" />
                            <small v-if="errors.year_level" class="ios-field-error">{{ errors.year_level }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="calendar" :size="13" style="color: #AF52DE;" />
                        Term
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.term }]">
                            <TermSelect v-model="form.term" class="ios-select" :show-clear="false"
                                :ios-compact="true" />
                            <small v-if="errors.term" class="ios-field-error">{{ errors.term }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row ios-row-last">
                    <div class="ios-row-label">
                        <AppIcon name="calendar" :size="13" style="color: #0EA5E9;" />
                        Academic Year
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.academic_year }]">
                            <AcademicYearSelect v-model="form.academic_year" class="ios-select" :show-clear="false"
                                :ios-compact="true" />
                            <small v-if="errors.academic_year" class="ios-field-error">{{ errors.academic_year
                                }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ios-section">
            <div class="ios-section-label">Interview Details</div>
            <div class="ios-card" style="overflow: visible;">
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="calendar-1" :size="13" style="color: #353839" />
                        Interview Date
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.interview_date }]">
                            <DatePicker v-model="form.interview_date" placeholder="Select interview date" showButtonBar
                                dateFormat="M dd, yy" class="ios-datepicker" showIcon
                                iconDisplay="input" />
                            <small v-if="errors.interview_date" class="ios-field-error">{{ errors.interview_date }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="user-star" :size="13" style="color: #2e6f40" />
                        Interviewer
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.interviewer_id }]">
                            <UserSelect v-model="form.interviewer_id" :users="interviewerOptions" class="ios-select"
                                custom-placeholder="Select interviewer" :show-clear="false"
                                :ios-compact="true" />
                            <small v-if="errors.interviewer_id" class="ios-field-error">{{ errors.interviewer_id
                                }}</small>
                        </div>
                    </div>
                </div>
                <div class="ios-row ios-row-last">
                    <div class="ios-row-label">
                        <AppIcon name="person-standing" :size="13" style="color:#FA5053;" />
                        Endorsed By
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.endorsed_by }]">
                            <InputText v-model="form.endorsed_by" placeholder="Enter endorser name"
                                class="ios-select" maxlength="255" />
                            <small v-if="errors.endorsed_by" class="ios-field-error">{{ errors.endorsed_by }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ios-section">
            <div class="ios-section-label">Interview Assessment</div>
            <div class="ios-card" style="overflow: visible;">
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="graduation-cap" :size="13" style="color: #353839" />
                        Academic Potential
                    </div>
                    <div class="ios-segmented-control w-1/2">
                        <button v-for="option in academicPotentialOptions" :key="option.value" type="button"
                            :class="['ios-segment', form.academic_potential === option.value && 'ios-segment-active']"
                            @click="form.academic_potential = option.value">
                            <span class="text-xs">{{ option.label }}</span>
                            <span class="assessment-check-slot">
                                <transition name="fade-scale">
                                    <AppIcon v-if="form.academic_potential === option.value" name="check" :size="14" />
                                </transition>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="piggy-bank" :size="13" style="color: #353839" />
                        Financial Need
                    </div>
                    <div class="ios-segmented-control w-1/2">
                        <button v-for="option in financialNeedOptions" :key="option.value" type="button"
                            :class="['ios-segment', form.financial_need_level === option.value && 'ios-segment-active']"
                            @click="form.financial_need_level = option.value">
                            <span class="text-xs">{{ option.label }}</span>
                            <span class="assessment-check-slot">
                                <transition name="fade-scale">
                                    <AppIcon v-if="form.financial_need_level === option.value" name="check"
                                        :size="14" />
                                </transition>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="handshake" :size="13" style="color: #353839" />
                        Communication Skills
                    </div>
                    <div class="ios-segmented-control w-1/2">
                        <button v-for="option in communicationSkillsOptions" :key="option.value" type="button"
                            :class="['ios-segment', form.communication_skills === option.value && 'ios-segment-active']"
                            @click="form.communication_skills = option.value">
                            <span class="text-xs">{{ option.label }}</span>
                            <span class="assessment-check-slot">
                                <transition name="fade-scale">
                                    <AppIcon v-if="form.communication_skills === option.value" name="check"
                                        :size="14" />
                                </transition>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="ios-row">
                    <div class="ios-row-label">
                        <AppIcon name="thumbs-up" :size="13" style="color: #353839" />
                        Recommendation
                    </div>
                    <div class="ios-segmented-control w-1/2">
                        <button v-for="option in recommendationOptions" :key="option.value" type="button"
                            :class="['ios-segment', form.recommendation === option.value && 'ios-segment-active']"
                            @click="form.recommendation = option.value">
                            <span class="text-xs">{{ option.label }}</span>
                            <span class="assessment-check-slot">
                                <transition name="fade-scale">
                                    <AppIcon v-if="form.recommendation === option.value" name="check" :size="14" />
                                </transition>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="ios-row ios-row-last">
                    <div class="ios-row-label">
                        <AppIcon name="heart-handshake" :size="13" style="color: #353839" />
                        Grant Provision
                    </div>
                    <div class="ios-row-control ios-row-control-validation">
                        <div :class="['ios-input-stack', { 'has-error': errors.grant_provision }]">
                            <Select v-model="form.grant_provision" :options="grantProvisionOptions"
                                optionLabel="label" optionValue="value" :placeholder="grantProvisionPlaceholder"
                                class="ios-select" :pt="compactSelectPt" size="small"
                                :disabled="!selectedProgramCode || !grantProvisionOptions.length" :showClear="false" />
                            <small v-if="grantProvisionHint && !errors.grant_provision" class="ios-field-hint">
                                {{ grantProvisionHint }}
                            </small>
                            <small v-if="errors.grant_provision" class="ios-field-error">
                                {{ errors.grant_provision }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="errors.academic_potential" class="ios-section-footer ios-error">{{ errors.academic_potential }}
            </div>
            <div v-if="errors.financial_need_level" class="ios-section-footer ios-error">{{ errors.financial_need_level
                }}</div>
            <div v-if="errors.communication_skills" class="ios-section-footer ios-error">{{ errors.communication_skills
                }}</div>
            <div v-if="errors.recommendation" class="ios-section-footer ios-error">{{ errors.recommendation }}</div>
        </div>

        <div class="ios-section">
            <div class="ios-section-label">Remarks</div>
            <div class="ios-card" style="overflow: visible;">
                <div class="ios-row ios-row-stacked" style="gap: 0; padding: 0;">
                    <Editor v-model="form.interview_remarks" editorStyle="height: 120px"
                        class="interview-assessment-editor">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button type="button" class="ql-bold"></button>
                                <button type="button" class="ql-italic"></button>
                                <button type="button" class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button type="button" class="ql-list" value="ordered"></button>
                                <button type="button" class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button type="button" class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>
            <div v-if="errors.interview_remarks" class="ios-section-footer ios-error">{{ errors.interview_remarks }}
            </div>
        </div>
    </div>
</template>

