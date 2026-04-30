<template>
    <Dialog :visible="show" @update:visible="value => { if (!value) close(); }" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal assessment-modal" :style="[drag.dragStyle.value, { width: modalWidth }]">
                <div class="ios-nav-bar" @pointerdown="drag.onDragStart">
                    <button type="button" class="ios-nav-btn ios-nav-cancel" @click="backOrClose"
                        v-tooltip.bottom="activeMode === 'view' ? `Close` : `Back`">
                        <AppIcon :name="activeMode === 'view' ? 'x' : 'arrow-left'" :size="16" />
                    </button>
                    <span class="ios-nav-title">{{ modalTitle }}</span>
                    <button v-if="activeMode === 'edit'" type="button"
                        class="ios-nav-btn ios-nav-action assessment-nav-action" @click="submitEdit"
                        :disabled="editSubmitting" v-tooltip.bottom="'Save Changes'">
                        <AppIcon v-if="editSubmitting" name="spinner" :size="16" />
                        <AppIcon v-else name="check" :size="16" style="color: #2563eb;" />
                    </button>
                    <button v-else-if="activeMode === 'approve'" type="button"
                        class="ios-nav-btn ios-nav-action assessment-nav-action" @click="$emit('confirm-approve')"
                        :disabled="approvalForm?.processing" v-tooltip.bottom="'Approve Application'">
                        <AppIcon v-if="approvalForm?.processing" name="spinner" :size="16" />
                        <AppIcon v-else name="check" :size="16" style="color: #16a34a;" />
                    </button>
                    <button v-else-if="activeMode === 'deny'" type="button"
                        class="ios-nav-btn ios-nav-action assessment-nav-action" @click="$emit('confirm-deny')"
                        :disabled="denyForm?.processing" v-tooltip.bottom="'Confirm Deny'">
                        <AppIcon v-if="denyForm?.processing" name="spinner" :size="16" />
                        <AppIcon v-else name="x" :size="16" style="color: #dc2626;" />
                    </button>
                    <div v-else style="width: 48px;"></div>
                </div>

                <div class="ios-body assessment-body" v-if="localRecord">
                    <div class="assessment-content">
                        <template v-if="activeMode === 'view'">
                            <div class="assessment-grid  py-6">
                                <div class="assessment-column">
                                    <div class="ios-section ">
                                        <div class="ios-section-label">Applicant</div>
                                        <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                                            <div class="ios-row">
                                                <div class="ios-row-label assessment-name">
                                                    {{ localRecord.profile.last_name }}, {{
                                                        localRecord.profile.first_name
                                                    }}
                                                </div>
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="book-open" :size="13" style="color: #007AFF;" />
                                                    Program
                                                </div>
                                                <span class="text-sm text-gray-700">{{ localRecord.program?.shortname ||
                                                    'N/A' }}</span>
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="graduation-cap" :size="13" style="color: #34C759;" />
                                                    Course
                                                </div>
                                                <span class="text-sm text-gray-700">{{ localRecord.course?.shortname ||
                                                    'N/A' }}</span>
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="wallet" :size="13" style="color: #007AFF;" />
                                                    Grant Provision
                                                </div>
                                                <span class="text-sm text-gray-700">{{
                                                    getSystemOptionLabel('grant_provision', localRecord.grant_provision,
                                                        'N/A')
                                                }}</span>
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="calendar" :size="13" style="color: #8E8E93;" />
                                                    Interview Date
                                                </div>
                                                <span class="text-sm text-gray-600">{{
                                                    formatDate(localRecord.interviewed_at) }}</span>
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="user" :size="13" style="color: #8E8E93;" />
                                                    Interviewed By
                                                </div>
                                                <span class="text-sm text-gray-600">{{ localRecord.interviewer?.name ||
                                                    'N/A' }}</span>
                                            </div>
                                            <div class="ios-row ios-row-last">
                                                <div class="ios-row-label">
                                                    <AppIcon name="user" :size="13" style="color: #34C759;" />
                                                    Endorsed By
                                                </div>
                                                <span class="text-sm text-gray-600">{{ localRecord.endorsed_by ||
                                                    'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ios-section">
                                        <div class="ios-section-label">Recommendation</div>
                                        <div class="ios-card">
                                            <div class="ios-row assessment-row-tall">
                                                <div class="ios-row-label">
                                                    <AppIcon name="check-circle" :size="13" style="color: #34C759;" />
                                                    Decision
                                                </div>
                                                <Tag :value="formatRecommendation(localRecord.recommendation)"
                                                    :severity="getRecommendationSeverity(localRecord.recommendation)" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="assessment-column">
                                    <div class="ios-section">
                                        <div class="ios-section-label">Interviewer's Assessment</div>
                                        <div class="ios-card">
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="star" :size="13" style="color: #FF9500;" />
                                                    Academic Potential
                                                </div>
                                                <Tag :value="capitalize(localRecord.academic_potential)"
                                                    :severity="getRatingSeverity(localRecord.academic_potential)" />
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="wallet" :size="13" style="color: #FF3B30;" />
                                                    Financial Need
                                                </div>
                                                <Tag :value="capitalize(localRecord.financial_need_level)"
                                                    :severity="getRatingSeverity(localRecord.financial_need_level)" />
                                            </div>
                                            <div class="ios-row">
                                                <div class="ios-row-label">
                                                    <AppIcon name="message-square-more" :size="13"
                                                        style="color: #5856D6;" />
                                                    Communication
                                                </div>
                                                <Tag :value="capitalize(localRecord.communication_skills)"
                                                    :severity="getRatingSeverity(localRecord.communication_skills)" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ios-section">
                                        <div class="ios-section-label">Remarks</div>
                                        <div class="ios-card assessment-remarks-card">
                                            <p v-if="localRecord.interview_remarks" class="text-sm text-gray-700">
                                                {{ localRecord.interview_remarks }}
                                            </p>
                                            <p v-else class="text-sm text-gray-400 italic">No remarks provided.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="activeMode === 'edit'">
                            <InterviewAssessmentForm :subject-name="editSubjectName"
                                :subject-subtitle="editSubjectSubtitle" :form="editForm" :errors="editErrors"
                                :interviewers="props.interviewers" />
                        </template>

                        <template v-else-if="activeMode === 'approve'">
                            <div v-if="approvalValidationSummary" class="ios-section ios-section-tight">
                                <div class="ios-card ios-validation-summary">
                                    <div class="ios-validation-summary-icon">
                                        <AppIcon name="exclamation-triangle" :size="16" />
                                    </div>
                                    <div>
                                        <div class="ios-validation-summary-title">Review Before Approval</div>
                                        <p class="ios-validation-summary-text">{{ approvalValidationSummary }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="ios-section ios-section-tight">
                                <div class="ios-card" style="background: #F8FAFC; border-color: #CBD5E1;">
                                    <p class="text-sm text-slate-700">
                                        Approval uses the saved assessment and academic details below. If anything is
                                        incorrect,
                                        go back and use Edit Assessment before approving.
                                    </p>
                                </div>
                            </div>

                            <div class="ios-section">
                                <div class="ios-section-label">Applicant Summary</div>
                                <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                                    <div class="ios-row">
                                        <div class="ios-row-label assessment-name">
                                            {{ localRecord.profile.last_name }}, {{ localRecord.profile.first_name }}
                                        </div>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="phone" :size="13" style="color: #34C759;" />
                                            Contact
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.profile.contact_no || 'N/A'
                                            }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="message-square-more" :size="13" style="color: #6366F1;" />
                                            Recommendation
                                        </div>
                                        <Tag :value="formatRecommendation(localRecord.recommendation)"
                                            :severity="getRecommendationSeverity(localRecord.recommendation)" />
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="calendar" :size="13" style="color: #8E8E93;" />
                                            Interview Date
                                        </div>
                                        <span class="text-sm text-gray-700">{{ formatDate(localRecord.interviewed_at)
                                            }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="user" :size="13" style="color: #007AFF;" />
                                            Interviewed By
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.interviewer?.name || 'N/A'
                                            }}</span>
                                    </div>
                                    <div class="ios-row ios-row-last">
                                        <div class="ios-row-label">
                                            <AppIcon name="user" :size="13" style="color: #34C759;" />
                                            Endorsed By
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.endorsed_by || 'N/A'
                                            }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="ios-section">
                                <div class="ios-section-label">Academic Summary</div>
                                <div class="ios-card">
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="book-open" :size="13" style="color: #007AFF;" />
                                            Program
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.program?.shortname || 'N/A'
                                            }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="graduation-cap" :size="13" style="color: #34C759;" />
                                            Course
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.course?.shortname ||
                                            localRecord.course?.name || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="building-2" :size="13" style="color: #FF9500;" />
                                            School
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.school?.shortname ||
                                            localRecord.school?.name || 'N/A' }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="list-checks" :size="13" style="color: #5856D6;" />
                                            Year Level
                                        </div>
                                        <span class="text-sm text-gray-700">{{ getSystemOptionLabel('year_level',
                                            localRecord.year_level, 'N/A') }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="calendar" :size="13" style="color: #AF52DE;" />
                                            Term
                                        </div>
                                        <span class="text-sm text-gray-700">{{ getSystemOptionLabel('term',
                                            localRecord.term, 'N/A') }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="calendar" :size="13" style="color: #0EA5E9;" />
                                            Academic Year
                                        </div>
                                        <span class="text-sm text-gray-700">{{ localRecord.academic_year || 'N/A'
                                            }}</span>
                                    </div>
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="wallet" :size="13" style="color: #007AFF;" />
                                            Grant Provision
                                        </div>
                                        <span class="text-sm text-gray-700">{{ getSystemOptionLabel('grant_provision',
                                            localRecord.grant_provision, 'N/A') }}</span>
                                    </div>
                                    <div class="ios-row ios-row-last">
                                        <div class="ios-row-label">
                                            <AppIcon name="calendar" :size="13" style="color: #8E8E93;" />
                                            Date Filed
                                        </div>
                                        <span class="text-sm text-gray-700">{{ formatDate(localRecord.date_filed)
                                            }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="ios-section">
                                <div class="ios-section-label">Approval Date</div>
                                <div class="ios-card">
                                    <div class="ios-row">
                                        <div class="ios-row-label">
                                            <AppIcon name="calendar-plus" :size="13" style="color: #34C759;" />
                                            Approval Date <span class="ios-required-mark">*</span>
                                        </div>
                                        <div class="ios-row-control ios-row-control-validation">
                                            <div
                                                :class="['ios-input-stack', { 'has-error': approvalForm?.errors?.date_approved }]">
                                                <Calendar v-model="approvalForm.date_approved" showIcon
                                                    iconDisplay="input" :maxDate="new Date()" class="ios-full-input" />
                                                <small v-if="approvalForm?.errors?.date_approved"
                                                    class="ios-field-error">
                                                    {{ approvalForm.errors.date_approved }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </template>

                        <template v-else-if="activeMode === 'deny'">
                            <div class="ios-section">
                                <div class="ios-card" style="background: #FFF5F5; border-color: #FECACA;">
                                    <div class="ios-row assessment-warning-row">
                                        <div class="assessment-warning-icon">
                                            <AppIcon name="exclamation-triangle" :size="16" class="text-red-500" />
                                        </div>
                                        <div class="assessment-warning-content">
                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ localRecord.profile.last_name }}, {{ localRecord.profile.first_name
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-600 mt-0.5">
                                                {{ localRecord.program?.shortname }} - {{ localRecord.course?.shortname
                                                }}
                                            </p>
                                            <p class="text-xs text-red-600 mt-1">
                                                This action will permanently deny the application.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ios-section">
                                <div class="ios-section-label">Denial Reason <span class="ios-required-mark">*</span>
                                </div>
                                <div class="ios-card">
                                    <div class="ios-row ios-row-stacked" style="padding: 10px 16px;">
                                        <Select v-model="denyForm.reason" :options="declineReasons" optionLabel="label"
                                            optionValue="value" placeholder="Select a reason" class="ios-full-input" />
                                    </div>
                                </div>
                                <div v-if="denyForm?.errors?.reason" class="ios-section-footer ios-error">
                                    {{ denyForm.errors.reason }}
                                </div>
                            </div>

                            <div class="ios-section">
                                <div class="ios-section-label">Details <span class="ios-required-mark">*</span></div>
                                <div class="ios-card">
                                    <div class="ios-row ios-row-stacked" style="padding: 10px 16px;">
                                        <Textarea v-model="denyForm.details" rows="3"
                                            placeholder="Provide specific details..." class="ios-full-input" />
                                    </div>
                                </div>
                                <div v-if="denyForm?.errors?.details" class="ios-section-footer ios-error">
                                    {{ denyForm.errors.details }}
                                </div>
                            </div>

                        </template>
                    </div>

                    <div v-if="showDialogFooter" class="assessment-dialog-footer">
                        <div class="assessment-dialog-footer-actions">
                            <template v-if="activeMode === 'view'">
                                <AppButton icon="pencil" label="Edit" severity="secondary" text size="xsmall"
                                    iconColor="#2563eb" @click="openEditMode" />
                                <AppButton v-if="canManage" icon="check" label="Approve" severity="secondary" text
                                    size="xsmall" iconColor="#16a34a" @click="openApproveMode" />
                                <AppButton v-if="canManage" icon="x" label="Deny" severity="secondary" text
                                    size="xsmall" iconColor="#dc2626" @click="openDenyMode" />
                                <AppButton v-if="canRevert" icon="arrow-left" label="Revert to Pending"
                                    severity="secondary" text size="xsmall" iconColor="#d97706"
                                    @click="$emit('revert')" />
                            </template>


                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, toRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { toast } from '@/utils/toast';
import AppButton from '@/Components/ui/AppButton.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import InterviewAssessmentForm from '@/Components/forms/InterviewAssessmentForm.vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    record: {
        type: Object,
        default: null,
    },
    initialMode: {
        type: String,
        default: 'view',
    },
    canManage: {
        type: Boolean,
        default: false,
    },
    canRevert: {
        type: Boolean,
        default: false,
    },
    approvalForm: {
        type: Object,
        required: true,
    },
    denyForm: {
        type: Object,
        required: true,
    },
    declineReasons: {
        type: Array,
        default: () => [],
    },
    interviewers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show', 'updated', 'confirm-approve', 'confirm-deny', 'revert']);

const show = toRef(props, 'show');
const record = toRef(props, 'record');
const initialMode = toRef(props, 'initialMode');
const approvalForm = toRef(props, 'approvalForm');
const denyForm = toRef(props, 'denyForm');
const declineReasons = toRef(props, 'declineReasons');
const drag = useDraggableModal();
const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);

const activeMode = ref('view');
const localRecord = ref(null);
const editSubmitting = ref(false);
const editErrors = ref({});
const editForm = ref({
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

const formatDateForPayload = (value) => {
    if (!value) return null;
    return moment(value).format('YYYY-MM-DD');
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

const editInterviewerOptions = computed(() => buildInterviewerOptions(props.interviewers));

const resolveEditInterviewerId = (...candidateIds) => {
    return candidateIds.find((candidateId) => editInterviewerOptions.value.some((option) => option.id === candidateId)) || null;
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

const selectedEditInterviewer = computed(() => {
    return editInterviewerOptions.value.find((option) => option.id === editForm.value.interviewer_id) || null;
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

const approvalValidationLabels = {
    program_id: 'Program',
    course_id: 'Course',
    school_id: 'School',
    year_level: 'Year Level',
    term: 'Term',
    academic_year: 'Academic Year',
    date_approved: 'Approval Date',
};

const approvalValidationSummary = computed(() => {
    const activeLabels = Object.entries(approvalValidationLabels)
        .filter(([field]) => Boolean(approvalForm.value?.errors?.[field]))
        .map(([, label]) => label);

    if (!activeLabels.length) {
        return '';
    }

    return `This application cannot be approved until these saved details are complete: ${activeLabels.join(', ')}. Use Edit Assessment to correct them.`;
});

const editValidationLabels = {
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

const createEditFormState = () => ({
    program: localRecord.value?.program || null,
    course: localRecord.value?.course || null,
    school: localRecord.value?.school || null,
    year_level: localRecord.value?.year_level || null,
    term: localRecord.value?.term || null,
    academic_year: localRecord.value?.academic_year || null,
    academic_potential: localRecord.value?.academic_potential || null,
    financial_need_level: localRecord.value?.financial_need_level || null,
    communication_skills: localRecord.value?.communication_skills || null,
    recommendation: localRecord.value?.recommendation || null,
    grant_provision: localRecord.value?.grant_provision || null,
    interview_date: formatDateForPicker(localRecord.value?.interviewed_at) || new Date(),
    interviewer_id: resolveEditInterviewerId(
        localRecord.value?.interviewer?.id,
        localRecord.value?.interviewed_by,
        currentUser.value?.id,
    ),
    endorsed_by: localRecord.value?.endorsed_by || '',
    interview_remarks: localRecord.value?.interview_remarks || '',
});

const syncLocalRecord = () => {
    localRecord.value = record.value ? { ...record.value } : null;
    editForm.value = createEditFormState();
    editErrors.value = {};
};

const normalizeMode = (mode) => {
    return ['view', 'edit', 'approve', 'deny'].includes(mode) ? mode : 'view';
};

const resetApprovalFormState = () => {
    approvalForm.value.reset();
    approvalForm.value.clearErrors();
    approvalForm.value.date_approved = localRecord.value?.date_approved
        ? new Date(localRecord.value.date_approved)
        : new Date();
};

const resetDenyFormState = () => {
    denyForm.value.reset();
    denyForm.value.clearErrors();
};

watch(record, () => {
    syncLocalRecord();
}, { immediate: true });

watch(show, (value) => {
    if (value) {
        activeMode.value = normalizeMode(initialMode.value);
        syncLocalRecord();

        if (activeMode.value === 'approve') {
            resetApprovalFormState();
        }

        if (activeMode.value === 'deny') {
            resetDenyFormState();
        }
    } else {
        activeMode.value = 'view';
        editErrors.value = {};
    }
});

watch(initialMode, (value) => {
    if (show.value) {
        activeMode.value = normalizeMode(value);
    }
});

watch(() => approvalForm.value?.date_approved, (value) => {
    if (value) {
        approvalForm.value?.clearErrors('date_approved');
    }
});

const modalTitle = computed(() => {
    return {
        view: 'Interview Assessment',
        edit: 'Edit Assessment',
        approve: 'Approve Application',
        deny: 'Deny Application',
    }[activeMode.value] || 'Interview Assessment';
});

const modalWidth = computed(() => {
    return activeMode.value === 'view' ? 'min(760px, 96vw)' : 'min(680px, 96vw)';
});

const showDialogFooter = computed(() => {
    if (activeMode.value === 'view') {
        return props.canManage || props.canRevert;
    }

    return true;
});

const editSubjectName = computed(() => {
    const profile = localRecord.value?.profile;

    if (!profile) {
        return 'N/A';
    }

    return `${profile.last_name || 'N/A'}, ${profile.first_name || 'N/A'}`;
});

const editSubjectSubtitle = computed(() => {
    const program = editForm.value?.program || localRecord.value?.program || null;
    const course = editForm.value?.course || localRecord.value?.course || null;
    const programLabel = program?.shortname || program?.name || 'N/A';
    const courseLabel = course?.shortname || course?.name || 'N/A';

    return `${programLabel} - ${courseLabel}`;
});

const close = () => emit('update:show', false);

const backOrClose = () => {
    if (activeMode.value === 'view') {
        close();
        return;
    }

    activeMode.value = 'view';
};

const formatDate = (date) => {
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
};

const formatRecommendation = (value) => {
    const recommendationMap = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };

    return recommendationMap[value] || 'Unknown';
};

const getRecommendationSeverity = (value) => {
    const recommendationSeverityMap = {
        recommended: 'success',
        further_evaluation: 'warn',
        not_recommended: 'danger',
    };

    return recommendationSeverityMap[value] || 'secondary';
};

const getRatingSeverity = (value) => {
    const ratingSeverityMap = {
        excellent: 'success',
        good: 'info',
        fair: 'warn',
        high: 'danger',
        moderate: 'warn',
        low: 'success',
    };

    return ratingSeverityMap[value] || 'secondary';
};

const capitalize = (value) => {
    if (!value) return 'N/A';
    return value.charAt(0).toUpperCase() + value.slice(1);
};

const openEditMode = () => {
    editForm.value = createEditFormState();
    editErrors.value = {};
    activeMode.value = 'edit';
};

const openApproveMode = () => {
    if (!props.canManage) return;

    resetApprovalFormState();
    activeMode.value = 'approve';
};

const openDenyMode = () => {
    if (!props.canManage) return;

    resetDenyFormState();
    activeMode.value = 'deny';
};

const validateEditForm = () => {
    const errs = {};

    if (!editForm.value.interview_date || !formatDateForPayload(editForm.value.interview_date)) errs.interview_date = 'Please select interview date.';
    if (!editForm.value.interviewer_id) errs.interviewer_id = 'Please select interviewer.';
    if (!extractOptionValue(editForm.value.academic_year)) errs.academic_year = 'Please select academic year.';
    if (!editForm.value.academic_potential) errs.academic_potential = 'Please select academic potential.';
    if (!editForm.value.financial_need_level) errs.financial_need_level = 'Please select financial need level.';
    if (!editForm.value.communication_skills) errs.communication_skills = 'Please select communication skills.';
    if (!editForm.value.recommendation) errs.recommendation = 'Please select a recommendation.';

    editErrors.value = errs;

    return Object.keys(errs).length === 0;
};

const submitEdit = async () => {
    if (!localRecord.value?.id) {
        toast.error('No scholarship record selected.');
        return;
    }

    if (!validateEditForm()) {
        return;
    }

    editSubmitting.value = true;
    const payload = {
        program_id: extractOptionId(editForm.value.program),
        course_id: extractOptionId(editForm.value.course),
        school_id: extractOptionId(editForm.value.school),
        year_level: extractOptionValue(editForm.value.year_level),
        term: extractOptionValue(editForm.value.term),
        academic_year: extractOptionValue(editForm.value.academic_year),
        academic_potential: editForm.value.academic_potential,
        financial_need_level: editForm.value.financial_need_level,
        communication_skills: editForm.value.communication_skills,
        recommendation: editForm.value.recommendation,
        grant_provision: editForm.value.grant_provision,
        interview_date: formatDateForPayload(editForm.value.interview_date),
        interviewer_id: editForm.value.interviewer_id,
        endorsed_by: editForm.value.endorsed_by?.trim() || null,
        interview_remarks: editForm.value.interview_remarks,
    };

    try {
        await axios.post(route('scholarship.record.update-interview', localRecord.value.id), payload);

        const interviewer = selectedEditInterviewer.value || localRecord.value?.interviewer || null;
        const changes = {
            program_id: payload.program_id,
            course_id: payload.course_id,
            school_id: payload.school_id,
            year_level: payload.year_level,
            term: payload.term,
            academic_year: payload.academic_year,
            program: editForm.value.program || null,
            course: editForm.value.course || null,
            school: editForm.value.school || null,
            academic_potential: payload.academic_potential,
            financial_need_level: payload.financial_need_level,
            communication_skills: payload.communication_skills,
            recommendation: payload.recommendation,
            grant_provision: payload.grant_provision,
            interview_remarks: payload.interview_remarks,
            interviewed_at: payload.interview_date,
            interviewed_by: payload.interviewer_id,
            endorsed_by: payload.endorsed_by,
            interviewer: interviewer ? { id: interviewer.id, name: interviewer.name } : null,
        };

        localRecord.value = {
            ...localRecord.value,
            ...changes,
        };

        editErrors.value = {};
        activeMode.value = 'view';
        toast.success('Assessment updated successfully.');
        emit('updated', changes);
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors) {
            editErrors.value = {};
            for (const [key, messages] of Object.entries(error.response.data.errors)) {
                editErrors.value[key] = messages[0];
            }
        } else {
            toast.error(error.response?.data?.message || 'Failed to update interview assessment.');
        }
    } finally {
        editSubmitting.value = false;
    }
};
</script>

<style scoped>
.assessment-modal {
    display: flex;
    flex-direction: column;
    max-height: min(88vh, 920px);
    overflow: hidden;
}

.assessment-body {
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 0 !important;
    overflow: hidden;
}

.assessment-content {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 0 16px 8px;
}

.assessment-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    align-items: start;
}

.assessment-column {
    display: flex;
    flex-direction: column;
    gap: 22px;
    min-width: 0;
}

.assessment-column .ios-section {
    margin-top: 0;
}

.assessment-name {
    font-size: 15px;
    font-weight: 600;
    color: #1d4ed8;
}

.assessment-row-tall {
    min-height: 52px;
}

.assessment-remarks-card {
    padding: 10px 16px;
    min-height: 96px;
}

.assessment-nav-action {
    right: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
}

.assessment-nav-action:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.assessment-dialog-footer {
    display: flex;
    justify-content: center;
    width: 100%;
    padding: 12px 18px calc(14px + env(safe-area-inset-bottom, 0px));
    border-top: 1px solid rgba(203, 213, 225, 0.72);
    background: rgba(255, 255, 255, 0.86);
    backdrop-filter: blur(22px) saturate(180%);
    box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.06);
}

.assessment-dialog-footer-actions {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(0, 1fr);
    align-items: stretch;
    gap: 8px;
    width: 100%;
    max-width: 100%;
}

.assessment-dialog-footer-actions :deep(.p-button) {
    width: 100%;
    min-width: 0;
    border-radius: 9999px;
    justify-content: center;
}

.assessment-dialog-footer-actions :deep(.p-button-label) {
    white-space: nowrap;
}

.assessment-warning-row {
    gap: 12px;
    padding: 14px 16px;
    min-height: 60px;
    align-items: flex-start;
}

.assessment-warning-icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    background: #fee2e2;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
}

.assessment-warning-content {
    flex: 1;
    min-width: 0;
}

.assessment-check-slot {
    width: 16px;
    height: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ios-section-tight {
    margin-top: 16px;
}

.ios-validation-summary {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: #fff7ed;
    border-color: #fed7aa;
}

.ios-validation-summary-icon {
    width: 32px;
    height: 32px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #c2410c;
    background: #ffedd5;
    flex-shrink: 0;
}

.ios-validation-summary-title {
    font-size: 13px;
    font-weight: 600;
    color: #9a3412;
    letter-spacing: -0.15px;
}

.ios-validation-summary-text {
    margin: 2px 0 0;
    font-size: 12px;
    line-height: 1.4;
    color: #9a3412;
}

.ios-row-control-validation {
    overflow: visible;
}

.ios-input-stack {
    width: 100%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.assessment-editor {
    width: 100%;
    min-width: 0;
    display: block;
}

.ios-required-mark {
    color: #ff3b30;
    margin-left: 2px;
}

.ios-field-hint {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.25;
    color: #8e8e93;
    letter-spacing: -0.2px;
}

.ios-field-error {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.25;
    color: #ff3b30;
    letter-spacing: -0.2px;
}

:deep(.ios-input-stack.has-error .p-inputtext),
:deep(.ios-input-stack.has-error .p-select),
:deep(.ios-input-stack.has-error .p-datepicker),
:deep(.ios-input-stack.has-error .p-editor-container) {
    border-color: #ff3b30 !important;
    box-shadow: 0 0 0 3px rgba(255, 59, 48, 0.08);
}

:deep(.assessment-editor.p-editor),
:deep(.assessment-editor .p-editor-container) {
    width: 100%;
}

@media (max-width: 720px) {

    .assessment-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .assessment-grid {
        gap: 22px;
    }

    .assessment-dialog-footer {
        padding: 10px 12px calc(14px + env(safe-area-inset-bottom, 0px));
    }

    .assessment-dialog-footer-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-auto-flow: row;
        grid-auto-columns: unset;
        gap: 8px;
    }

    .assessment-dialog-footer-actions :deep(.p-button) {
        width: 100%;
        justify-content: center;
    }
}
</style>