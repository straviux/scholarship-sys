<template>
    <IosModal
        :visible="visible"
        :title="mode === 'edit' ? 'Edit Scholar' : 'Add Existing Scholar'"
        :draggable="!isMaximized"
        :modal-class="{ 'ios-modal-maximized': isMaximized }"
        :modal-content-style="wizardModalContentStyle"
        :body-style="{ padding: '0', display: 'flex', flexDirection: 'column', minHeight: 0 }"
        @update:visible="val => emit('update:visible', val)"
    >
        <template #header-right>
            <div class="ios-nav-right">
                <span class="ios-nav-step-text">{{ activeStep }} of 3</span>
                <button class="ios-nav-maximize" @click="isMaximized = !isMaximized"
                    v-tooltip.bottom="isMaximized ? 'Restore' : 'Maximize'">
                    <AppIcon :name="isMaximized ? 'window-minimize' : 'window-maximize'" :size="14" />
                </button>
            </div>
        </template>

        <div class="ios-body">
            <div class="mt-8">
                        <!-- ═══ STEPPER ═══ -->
                        <div class="flex items-center justify-center gap-0 pb-4">
                            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                                :class="{ 'opacity-40 cursor-default': activeStep !== '1' && Number(activeStep) < 1 }"
                                @click="activeStep = '1'">
                                <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                                    :class="Number(activeStep) > 1 ? 'bg-green-500' : 'bg-blue-500'">
                                    <AppIcon v-if="Number(activeStep) > 1" name="check" :size="10" />
                                    <span v-else>1</span>
                                </div>
                                <span class="text-xs font-medium whitespace-nowrap"
                                    :class="activeStep === '1' ? 'text-blue-500 font-semibold' : Number(activeStep) > 1 ? 'text-green-500' : 'text-gray-400'">Personal</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2 transition-colors"
                                :class="Number(activeStep) > 1 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                                :class="{ 'opacity-40 cursor-default': !canProceedStep1 && mode !== 'edit' }"
                                @click="goToStep(2)">
                                <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                                    :class="Number(activeStep) > 2 ? 'bg-green-500' : activeStep === '2' ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                                    <AppIcon v-if="Number(activeStep) > 2" name="check" :size="10" />
                                    <span v-else>2</span>
                                </div>
                                <span class="text-xs font-medium whitespace-nowrap"
                                    :class="activeStep === '2' ? 'text-blue-500 font-semibold' : Number(activeStep) > 2 ? 'text-green-500' : 'text-gray-400'">Family</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2 transition-colors"
                                :class="Number(activeStep) > 2 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                            <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                                :class="{ 'opacity-40 cursor-default': !canProceedStep1 && mode !== 'edit' }"
                                @click="goToStep(3)">
                                <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                                    :class="activeStep === '3' ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                                    <span>3</span>
                                </div>
                                <span class="text-xs font-medium whitespace-nowrap"
                                    :class="activeStep === '3' ? 'text-blue-500 font-semibold' : 'text-gray-400'">Academic</span>
                            </div>
                        </div>

                        <!-- Panel: Step 1 - Personal -->
                        <div v-show="activeStep === '1'">
                            <PersonalInformationFields v-model:first_name="form.first_name"
                                v-model:middle_name="form.middle_name"
                                v-model:last_name="form.last_name"
                                v-model:extension_name="form.extension_name"
                                v-model:contact_no="form.contact_no"
                                v-model:contact_no_2="form.contact_no_2" v-model:email="form.email"
                                v-model:date_of_birth="form.date_of_birth" v-model:gender="form.gender"
                                v-model:place_of_birth="form.place_of_birth"
                                v-model:civil_status="form.civil_status"
                                v-model:religion="form.religion"
                                v-model:indigenous_group="form.indigenous_group"
                                v-model:municipality="form.municipality"
                                v-model:barangay="form.barangay" v-model:address="form.address"
                                v-model:temporary_municipality="form.temporary_municipality"
                                v-model:temporary_barangay="form.temporary_barangay"
                                v-model:temporary_address="form.temporary_address"
                                :show-header="false" />

                            <!-- Validation Warning -->
                            <div v-if="validationError"
                                class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                                <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                                    <AppIcon name="exclamation-triangle" :size="14" class="mr-2" />
                                    {{ validationError }}
                                </p>
                            </div>
                        </div>

                        <!-- Panel: Step 2 - Family -->
                        <div v-show="activeStep === '2'">
                            <FamilyInformationFields v-model:father_name="form.father_name"
                                v-model:father_occupation="form.father_occupation"
                                v-model:father_contact_no="form.father_contact_no"
                                v-model:mother_name="form.mother_name"
                                v-model:mother_occupation="form.mother_occupation"
                                v-model:mother_contact_no="form.mother_contact_no"
                                v-model:guardian_name="form.guardian_name"
                                v-model:guardian_occupation="form.guardian_occupation"
                                v-model:guardian_relationship="form.guardian_relationship"
                                v-model:guardian_contact_no="form.guardian_contact_no"
                                v-model:parents_guardian_gross_monthly_income="form.parents_guardian_gross_monthly_income"
                                :show-header="false" />
                        </div>

                        <!-- Panel: Step 3 - Academic -->
                        <div v-show="activeStep === '3'">
                            <div class="space-y-4">
                                <AcademicInformationFields v-model:program="form.program"
                                    v-model:school="form.school" v-model:course="form.course"
                                    v-model:year_level="form.year_level" v-model:term="form.term"
                                    v-model:academic_year="form.academic_year"
                                    v-model:no_of_hours="form.no_of_hours"
                                    v-model:no_of_days="form.no_of_days"
                                    v-model:start_date="form.start_date"
                                    v-model:end_date="form.end_date"
                                    v-model:remarks="form.remarks"
                                    :is-tech-voc-program="isTechVocProgram"
                                    :show-tech-voc-fields="true"
                                    :show-header="false" />

                                <!-- Date Fields (for backlog encoding) -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-6">
                                    <FloatLabel>
                                        <DatePicker v-model="form.date_filed" type="date"
                                            inputId="date_filed" variant="filled"
                                            placeholder="mm/dd/yyyy" showIcon fluid iconDisplay="input"
                                            :manualInput="true" @input="formatDateInput" />
                                        <label class="text-sm" for="date_filed">Date Filed</label>
                                    </FloatLabel>
                                    <FloatLabel>
                                        <DatePicker v-model="form.date_approved" type="date"
                                            inputId="date_approved" variant="filled"
                                            placeholder="mm/dd/yyyy" showIcon fluid iconDisplay="input"
                                            :manualInput="true" @input="formatDateInput" />
                                        <label class="text-sm" for="date_approved">Date Approved</label>
                                    </FloatLabel>
                                </div>

                                <div
                                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                                    <p class="text-sm text-blue-800 dark:text-blue-300">
                                        <AppIcon name="info-circle" :size="14" class="mr-2" />
                                        <strong>Note:</strong>
                                        <span v-if="isTechVocProgram">
                                            Program, course, school, and academic year are required.
                                            Year level and term are optional for Tech-Voc scholars.
                                        </span>
                                        <span v-else>
                                            All academic fields are required for scholars.
                                        </span>
                                    </p>
                                </div>

                                <!-- Academic Validation Messages -->
                                <div v-if="academicValidationError"
                                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                                    <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                                        <AppIcon name="exclamation-triangle" :size="14" class="mr-2" />
                                        {{ academicValidationError }}
                                    </p>
                                </div>
                            </div>
                        </div>
            </div>
        </div>

        <div class="ios-footer mt-4">
            <button v-if="activeStep !== '1'" class="ios-footer-btn ios-footer-back"
                @click="activeStep = String(Number(activeStep) - 1)">
                <AppIcon name="arrow-left" :size="12" /> Back
            </button>
            <span v-else></span>
            <button v-if="activeStep === '1'" class="ios-footer-btn ios-footer-next" @click="handleNextStep1"
                :disabled="!canProceedStep1 || isValidating" v-tooltip.top="step1TooltipMessage">
                {{ isValidating ? 'Checking...' : 'Next' }}
                <AppIcon name="arrow-right" :size="12" />
            </button>
            <button v-else-if="activeStep === '2'" class="ios-footer-btn ios-footer-next"
                @click="activeStep = '3'">
                Next
                <AppIcon name="arrow-right" :size="12" />
            </button>
            <button v-else class="ios-footer-btn ios-footer-submit" @click="handleSubmit"
                :disabled="form.processing || !canSubmit" v-tooltip.top="submitTooltipMessage">
                <AppIcon name="check" :size="12" />
                {{ form.processing ? 'Saving...' : (mode === 'edit' ? 'Update' : 'Add Scholar') }}
            </button>
        </div>
    </IosModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';
import FamilyInformationFields from '@/Components/forms/fields/FamilyInformationFields.vue';
import AcademicInformationFields from '@/Components/forms/fields/AcademicInformationFields.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { toast } from '@/utils/toast';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    profile: Object,
    mode: {
        type: String,
        default: 'create', // 'create' or 'edit'
        validator: (value) => ['create', 'edit'].includes(value)
    }
});

const emit = defineEmits(['update:visible', 'success']);

const activeStep = ref('1');
const isValidating = ref(false);
const validationError = ref('');
const academicValidationError = ref('');
const showDuplicateDialog = ref(false);
const duplicateMatches = ref([]);

const normalizeProgramToken = (value) => String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '');

const matchesTechVocProgram = (program) => {
    const valuesToCheck = [];

    if (typeof program === 'string') {
        valuesToCheck.push(program);
    } else if (program && typeof program === 'object') {
        valuesToCheck.push(program.name, program.shortname);
    }

    return valuesToCheck.some((value) => {
        const normalizedValue = normalizeProgramToken(value);
        return normalizedValue.includes('techvoc') || normalizedValue.includes('technicalvoc');
    });
};

// Helper function to format date for DatePicker
const formatDateForPicker = (dateString) => {
    if (!dateString) return null;
    if (dateString instanceof Date) return dateString;
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? null : date;
};

// Format date input as user types (auto-insert slashes)
const formatDateInput = (event) => {
    const input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove non-digits

    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }

    input.value = value;
};

const isTechVocProgram = computed(() => matchesTechVocProgram(form.program));

// Get profile data if in edit mode
const p = props.profile;
const grant = p?.scholarship_grant?.[0];

const form = useForm({
    // Personal Information
    first_name: p?.first_name || '',
    middle_name: p?.middle_name || '',
    last_name: p?.last_name || '',
    extension_name: p?.extension_name || '',
    contact_no: p?.contact_no || '',
    contact_no_2: p?.contact_no_2 || '',
    email: p?.email || '',
    date_of_birth: formatDateForPicker(p?.date_of_birth),
    gender: p?.gender || '',
    place_of_birth: p?.place_of_birth ? (typeof p.place_of_birth === 'string' ? p.place_of_birth.toLowerCase() : p.place_of_birth) : null,
    civil_status: p?.civil_status || '',
    religion: p?.religion || '',
    indigenous_group: p?.indigenous_group || '',
    municipality: p?.municipality || null,
    barangay: p?.barangay || null,
    address: p?.address || '',
    temporary_municipality: p?.temporary_municipality || null,
    temporary_barangay: p?.temporary_barangay || null,
    temporary_address: p?.temporary_address || '',

    // Family Information
    father_name: p?.father_name || '',
    father_occupation: p?.father_occupation || '',
    father_contact_no: p?.father_contact_no || '',
    mother_name: p?.mother_name || '',
    mother_occupation: p?.mother_occupation || '',
    mother_contact_no: p?.mother_contact_no || '',
    guardian_name: p?.guardian_name || '',
    guardian_occupation: p?.guardian_occupation || '',
    guardian_relationship: p?.guardian_relationship || '',
    guardian_contact_no: p?.guardian_contact_no || '',
    parents_guardian_gross_monthly_income: p?.parents_guardian_gross_monthly_income || '',

    // Academic Information (ALL REQUIRED for scholars)
    scholarship_grant_id: grant?.scholarship_grant_id || null,
    program: grant?.program || null,
    school: grant?.school || null,
    course: grant?.course || null,
    year_level: grant?.year_level || null,
    term: grant?.term || null,
    academic_year: grant?.academic_year || null,
    no_of_hours: grant?.no_of_hours || null,
    no_of_days: grant?.no_of_days || null,
    start_date: formatDateForPicker(grant?.start_date) || null,
    end_date: formatDateForPicker(grant?.end_date) || null,
    date_filed: formatDateForPicker(grant?.date_filed) || null,
    date_approved: formatDateForPicker(grant?.date_approved) || null,
    remarks: grant?.remarks || p?.remarks || '',
});

// Step 1 validation - Scholars need more required fields
const canProceedStep1 = computed(() => {
    return form.first_name && form.last_name && form.municipality && form.contact_no;
});

// Navigate to step (with validation gate for step 2 & 3)
const goToStep = (step) => {
    if (step === 1) {
        activeStep.value = '1';
        return;
    }
    if (!canProceedStep1.value && mode !== 'edit') return;
    activeStep.value = String(step);
};

const step1TooltipMessage = computed(() => {
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');
    if (!form.municipality) missingFields.push('Municipality');
    if (!form.contact_no) missingFields.push('Contact Number');

    if (missingFields.length > 0) {
        return `Missing required fields: ${missingFields.join(', ')}`;
    }
    return '';
});

// Handle next step 1 with duplicate name check
const handleNextStep1 = async () => {
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');
    if (!form.municipality) missingFields.push('Municipality');
    if (!form.contact_no) missingFields.push('Contact Number');

    if (missingFields.length > 0) {
        validationError.value = `Please fill in the following required fields: ${missingFields.join(', ')}.`;
        toast.error(`Please fill in all required fields: ${missingFields.join(', ')}`, {
            position: toast.POSITION.TOP_RIGHT,
        });
        return;
    }

    if (props.mode === 'edit') {
        validationError.value = '';
        activeStep.value = '2';
        return;
    }

    isValidating.value = true;
    validationError.value = '';

    try {
        const response = await axios.post(route('api.profiles.validate-name'), {
            first_name: form.first_name,
            middle_name: form.middle_name || '',
            last_name: form.last_name
        });

        if (response.data.exists) {
            duplicateMatches.value = response.data.matches || [];
            showDuplicateDialog.value = true;
        } else {
            activeStep.value = '2';
        }
    } catch (error) {
        console.error('Validation error:', error);
        validationError.value = 'An error occurred while validating. Please try again.';
    } finally {
        isValidating.value = false;
    }
};

const proceedDespiteDuplicate = () => {
    showDuplicateDialog.value = false;
    activeStep.value = '2';
};

// Step 3 validation - ALL academic fields required for scholars
const canSubmit = computed(() => {
    return form.program && form.course && form.school && form.academic_year &&
        (isTechVocProgram.value || (form.year_level && form.term));
});

const submitTooltipMessage = computed(() => {
    const missingAcademicFields = [];
    if (!form.program) missingAcademicFields.push('Program');
    if (!form.school) missingAcademicFields.push('School');
    if (!form.course) missingAcademicFields.push('Course');
    if (!form.academic_year) missingAcademicFields.push('Academic Year');

    if (!isTechVocProgram.value) {
        if (!form.year_level) missingAcademicFields.push('Year Level');
        if (!form.term) missingAcademicFields.push('Term');
    }

    if (missingAcademicFields.length > 0) {
        return `Missing required academic fields: ${missingAcademicFields.join(', ')}`;
    }
    return '';
});

const closeModal = () => {
    emit('update:visible', false);
};

const isMaximized = ref(false);

const wizardModalContentStyle = computed(() => {
    return isMaximized.value
        ? { width: '100vw', height: '100vh' }
        : { width: '900px' };
});

// Reset form when modal is opened
watch(() => props.visible, async (newValue) => {
    if (newValue && props.mode === 'edit' && props.profile) {
        // In edit mode, reinitialize form with profile data
        const p = props.profile;
        const grant = p?.scholarship_grant?.[0];

        // Personal Information
        form.first_name = p?.first_name || '';
        form.middle_name = p?.middle_name || '';
        form.last_name = p?.last_name || '';
        form.extension_name = p?.extension_name || '';
        form.contact_no = p?.contact_no || '';
        form.contact_no_2 = p?.contact_no_2 || '';
        form.email = p?.email || '';
        form.date_of_birth = formatDateForPicker(p?.date_of_birth);
        form.gender = p?.gender || '';
        form.place_of_birth = p?.place_of_birth ? (typeof p.place_of_birth === 'string' ? p.place_of_birth.toLowerCase() : p.place_of_birth) : null;
        form.civil_status = p?.civil_status || '';
        form.religion = p?.religion || '';
        form.indigenous_group = p?.indigenous_group || '';
        form.municipality = p?.municipality || null;
        form.barangay = p?.barangay || null;
        form.address = p?.address || '';
        form.temporary_municipality = p?.temporary_municipality || null;
        form.temporary_barangay = p?.temporary_barangay || null;
        form.temporary_address = p?.temporary_address || '';

        // Family Information
        form.father_name = p?.father_name || '';
        form.father_occupation = p?.father_occupation || '';
        form.father_contact_no = p?.father_contact_no || '';
        form.mother_name = p?.mother_name || '';
        form.mother_occupation = p?.mother_occupation || '';
        form.mother_contact_no = p?.mother_contact_no || '';
        form.guardian_name = p?.guardian_name || '';
        form.guardian_occupation = p?.guardian_occupation || '';
        form.guardian_relationship = p?.guardian_relationship || '';
        form.guardian_contact_no = p?.guardian_contact_no || '';
        form.parents_guardian_gross_monthly_income = p?.parents_guardian_gross_monthly_income || '';

        // Academic Information
        form.program = grant?.program || null;
        form.school = grant?.school || null;
        form.course = grant?.course || null;
        form.year_level = grant?.year_level || null;
        form.term = grant?.term || null;
        form.academic_year = grant?.academic_year || null;
        form.no_of_hours = grant?.no_of_hours || null;
        form.no_of_days = grant?.no_of_days || null;
        form.start_date = formatDateForPicker(grant?.start_date) || null;
        form.end_date = formatDateForPicker(grant?.end_date) || null;
        form.date_filed = formatDateForPicker(grant?.date_filed) || new Date();
        form.date_approved = formatDateForPicker(grant?.date_approved) || null;
        form.remarks = grant?.remarks || p?.remarks || '';

        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        academicValidationError.value = '';
        isValidating.value = false;
        showDuplicateDialog.value = false;
    } else if (newValue && props.mode === 'create') {
        // In create mode, reset to empty form
        form.reset();
        form.no_of_hours = null;
        form.no_of_days = null;
        form.start_date = null;
        form.end_date = null;
        form.date_filed = null;
        form.date_approved = null;
        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        academicValidationError.value = '';
        isValidating.value = false;
        showDuplicateDialog.value = false;
    }
    if (newValue) {
        isMaximized.value = false;
    }
});

const handleSubmit = () => {
    academicValidationError.value = '';
    // Validate all required academic fields for scholars
    const missingAcademicFields = [];
    if (!form.program) missingAcademicFields.push('Program');
    if (!form.school) missingAcademicFields.push('School');
    if (!form.course) missingAcademicFields.push('Course');
    if (!form.academic_year) missingAcademicFields.push('Academic Year');

    if (!isTechVocProgram.value) {
        if (!form.year_level) missingAcademicFields.push('Year Level');
        if (!form.term) missingAcademicFields.push('Term');
    }

    if (missingAcademicFields.length > 0) {
        academicValidationError.value = `Please fill in all required academic fields: ${missingAcademicFields.join(', ')}.`;
        toast.error(`Missing required academic fields: ${missingAcademicFields.join(', ')}`, {
            position: toast.POSITION.TOP_RIGHT,
        });
        return;
    }

    // Transform data before submitting
    const submitData = {
        ...form.data(),
        no_of_hours: isTechVocProgram.value ? form.no_of_hours : null,
        no_of_days: isTechVocProgram.value ? form.no_of_days : null,
        start_date: isTechVocProgram.value ? form.start_date : null,
        end_date: isTechVocProgram.value ? form.end_date : null,
        // Extract value from academic_year object if it exists
        academic_year: form.academic_year?.value || form.academic_year || null,
        // Extract name from municipality object if it exists
        municipality: form.municipality?.name || form.municipality || null,
        // Extract name from barangay object if it exists
        barangay: form.barangay?.name || form.barangay || null,
        // Extract name from temporary_municipality object if it exists
        temporary_municipality: form.temporary_municipality?.name || form.temporary_municipality || null,
        // Extract name from temporary_barangay object if it exists
        temporary_barangay: form.temporary_barangay?.name || form.temporary_barangay || null,
        // Extract name from place_of_birth if it's an object
        place_of_birth: form.place_of_birth?.name || form.place_of_birth || null,
        // Send IDs for course, school, and program
        course_id: form.course?.id || null,
        school_id: form.school?.id || null,
        program_id: form.program?.id || null,
        // Also send names for backward compatibility
        course: form.course?.shortname || form.course?.name || form.course || null,
        school: form.school?.shortname || form.school?.name || form.school || null,
        program: form.program?.name || form.program || null,
        // Extract value from year_level object if it exists
        year_level: form.year_level?.value || form.year_level || null,
        // Extract value from term object if it exists
        term: form.term?.value || form.term || null,
    };

    if (props.mode === 'edit' && props.profile) {
        // Update existing scholar
        const profileId = props.profile.profile_id;

        form.transform(() => submitData).put(route('scholars.update', profileId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Scholar updated successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                emit('success');
                closeModal();
            },
            onError: (errors) => {
                console.error('Update failed with errors:', errors);
                const errorMessage = Object.values(errors).flat().join(', ') || 'Failed to update scholar';
                toast.error(errorMessage, {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
        });
    } else {
        // Create new scholar

        form.transform(() => submitData).post(route('scholars.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Scholar added successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                // Reset form after successful creation
                form.reset();
                form.no_of_hours = null;
                form.no_of_days = null;
                form.start_date = null;
                form.end_date = null;
                form.date_filed = null;
                form.date_approved = null;
                form.clearErrors();
                activeStep.value = '1';
                validationError.value = '';
                academicValidationError.value = '';

                emit('success');
                closeModal();
            },
            onError: (errors) => {
                console.error('Create failed with errors:', errors);
                const errorMessage = Object.values(errors).flat().join(', ') || 'Failed to add scholar';
                toast.error(errorMessage, {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
        });
    }
};
</script>

