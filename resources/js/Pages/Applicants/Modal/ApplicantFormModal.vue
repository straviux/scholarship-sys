<template>
    <IosModal :visible="visible" :title="mode === 'edit' ? 'Edit Application' : 'Application Form'"
        width="calc(100vw - 24px)" max-width="900px" :draggable="!isMaximized"
        :modal-content-style="isMaximized
            ? { width: '100vw', maxWidth: '100vw', height: '100vh', borderRadius: '0', transform: 'none' }
            : { maxHeight: '90vh' }"
        :body-style="{ padding: '0', display: 'flex', flexDirection: 'column', minHeight: 0 }"
        @update:visible="val => !val && closeModal()">
        <template #header-right>
            <div class="ios-nav-right">
                <span class="ios-nav-step-text">{{ activeStep }} of 3</span>
                <button class="ios-nav-maximize" @click="isMaximized = !isMaximized"
                    v-tooltip.bottom="isMaximized ? 'Restore' : 'Maximize'">
                    <AppIcon :name="isMaximized ? 'window-minimize' : 'window-maximize'" :size="14" />
                </button>
            </div>
        </template>

        <!-- Body -->
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

                            <!-- Duplicate Name Warning -->
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
                            <div>
                                <div class="space-y-4">
                                    <AcademicInformationFields v-model:program="form.program"
                                        v-model:school="form.school" v-model:course="form.course"
                                        v-model:year_level="form.year_level" v-model:term="form.term"
                                        v-model:academic_year="form.academic_year"
                                        v-model:remarks="form.remarks"
                                        v-model:yakap_category="form.yakap_category"
                                        v-model:yakap_location="form.yakap_location"
                                        :is-tech-voc-program="isTechVocProgram"
                                        :show-header="false" />

                                    <!-- Date Filed -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-10">
                                        <FloatLabel>
                                            <DatePicker v-model="form.date_filed" type="date"
                                                inputId="date_filed" variant="filled"
                                                placeholder="mm/dd/yyyy" showIcon fluid
                                                iconDisplay="input" :manualInput="true"
                                                @input="formatDateInput" />
                                            <label class="text-sm" for="date_filed">Date Filed</label>
                                        </FloatLabel>
                                    </div>

                                    <div
                                        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                                        <p class="text-sm text-blue-800 dark:text-blue-300">
                                            <AppIcon name="info-circle" :size="14" class="mr-2" />
                                            Academic information is optional. You can complete it now or
                                            update it later.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Footer -->
        <div class="ios-footer">
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
            <button v-else-if="activeStep === '2'" class="ios-footer-btn ios-footer-next" @click="activeStep = '3'">
                Next
                <AppIcon name="arrow-right" :size="12" />
            </button>
            <button v-else class="ios-footer-btn ios-footer-submit" @click="handleSubmit" :disabled="form.processing">
                <AppIcon name="check" :size="12" />
                {{ form.processing ? 'Saving...' : (mode === 'edit' ? 'Update' : 'Submit') }}
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
    profiles: {
        type: Object,
        default: null
    },
    profile: Object,
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit'].includes(value)
    },
    yakapCategory: {
        type: String,
        default: null
    },
    yakapLocation: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:visible', 'success', 'applicant-created']);

const activeStep = ref('1');
const isValidating = ref(false);
const validationError = ref('');
const showDuplicateDialog = ref(false);
const duplicateMatches = ref([]);

// Helper function to format date for DatePicker
const formatDateForPicker = (dateString) => {
    if (!dateString) return null;
    if (dateString instanceof Date) return dateString;
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? null : date;
};

// Helper function to format date for backend (YYYY-MM-DD)
const formatDateForBackend = (date) => {
    if (!date) return null;
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    if (isNaN(date.getTime())) return null;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Format date input as user types (auto-insert slashes)
const formatDateInput = (event) => {
    const input = event.target;
    let value = input.value.replace(/\D/g, '');

    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }

    input.value = value;
};

const normalizeAcademicYearValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object') {
        return value.value ?? null;
    }

    return String(value).trim() || null;
};

const normalizeTermValue = (value) => {
    const normalized = normalizeAcademicYearValue(value);

    return typeof normalized === 'string' ? normalized.toUpperCase() : normalized;
};

const toTitleCase = (value) => {
    if (!value) return '';

    return String(value)
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const toAcademicYearOption = (value) => {
    const normalized = normalizeAcademicYearValue(value);
    if (!normalized) {
        return null;
    }

    const label = typeof value === 'object'
        ? normalizeAcademicYearValue(value.label ?? value.value)
        : normalized;

    return {
        label: label || normalized,
        value: normalized,
    };
};

const toTermOption = (value) => {
    const normalized = normalizeTermValue(value);
    if (!normalized) {
        return null;
    }

    const labelSource = typeof value === 'object'
        ? value.label ?? value.value ?? normalized
        : normalized;

    return {
        label: toTitleCase(labelSource),
        value: normalized,
    };
};

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

    // Academic Information
    scholarship_grant_id: grant?.id || grant?.scholarship_grant_id || null,
    program: grant?.program || null,
    school: grant?.school || null,
    course: grant?.course || null,
    year_level: grant?.year_level || null,
    term: toTermOption(grant?.term),
    academic_year: toAcademicYearOption(grant?.academic_year),
    date_filed: formatDateForPicker(grant?.date_filed),
    remarks: grant?.remarks || p?.remarks || '',
    yakap_category: props.mode === 'create' ? (props.yakapCategory || null) : (grant?.yakap_category || null),
    yakap_location: props.mode === 'create' ? props.yakapLocation : (grant?.yakap_location || ''),
});

// Validation for step 1
const canProceedStep1 = computed(() => {
    return form.first_name && form.last_name;
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

const matchesTechVocProgram = (value) => {
    if (value === null || value === undefined) {
        return false;
    }

    if (typeof value === 'object') {
        return matchesTechVocProgram(value.shortname) || matchesTechVocProgram(value.name);
    }

    const normalized = String(value).toLowerCase().replace(/[^a-z0-9]+/g, '');
    if (!normalized) {
        return false;
    }

    return normalized.includes('techvoc') || normalized.includes('technicalvoc');
};

const isTechVocProgram = computed(() => matchesTechVocProgram(form.program));

// Tooltip message for missing fields in step 1
const step1TooltipMessage = computed(() => {
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');
    if (!form.municipality) missingFields.push('Municipality');

    if (missingFields.length > 0) {
        return `Missing required fields: ${missingFields.join(', ')}`;
    }
    return '';
});

// Handle next step 1 with backend validation
const handleNextStep1 = async () => {
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');

    if (missingFields.length > 0) {
        validationError.value = `Please fill in the following required fields: ${missingFields.join(', ')}.`;
        toast.error(`Please fill in all required fields: ${missingFields.join(', ')}`, {
            position: toast.POSITION.TOP_RIGHT,
        });
        return;
    }

    // Skip duplicate name validation in edit mode
    if (props.mode === 'edit') {
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
            dupDragOffset.value = { x: 0, y: 0 };
            showDuplicateDialog.value = true;
        } else {
            activeStep.value = '2';
        }
    } catch (error) {
        console.error('Validation error:', error);
        validationError.value = 'An error occurred while validating. Please try again.';
        toast.error('An error occurred while validating. Please try again.', {
            position: toast.POSITION.TOP_RIGHT,
        });
    } finally {
        isValidating.value = false;
    }
};

const proceedDespiteDuplicate = () => {
    showDuplicateDialog.value = false;
    activeStep.value = '2';
};

const closeModal = () => {
    emit('update:visible', false);
    resetForm();
};

// Reset form when modal is opened
watch(() => props.visible, async (newValue) => {
    if (newValue && props.mode === 'edit' && props.profile) {
        const p = props.profile;
        const grant = p?.scholarship_grant?.[0];
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

        form.program = grant?.program || null;
        form.school = grant?.school || null;
        form.course = grant?.course || null;
        form.scholarship_grant_id = grant?.id || grant?.scholarship_grant_id || null;
        form.year_level = grant?.year_level || null;
        form.term = toTermOption(grant?.term);
        form.academic_year = toAcademicYearOption(grant?.academic_year);
        form.date_filed = formatDateForPicker(grant?.date_filed);
        form.remarks = grant?.remarks || p?.remarks || '';

        form.yakap_category = grant?.yakap_category || null;
        form.yakap_location = grant?.yakap_location || '';

        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        isValidating.value = false;
    } else if (newValue && props.mode === 'create') {
        resetForm();
    }

    // Reset drag position when modal opens
});

const handleSubmit = () => {
    const toUpperCase = (value) => {
        if (!value || typeof value !== 'string') return value;
        return value.toUpperCase();
    };

    const submitData = {
        ...form.data(),
        first_name: toUpperCase(form.first_name),
        middle_name: toUpperCase(form.middle_name),
        last_name: toUpperCase(form.last_name),
        extension_name: toUpperCase(form.extension_name),
        address: toUpperCase(form.address),
        temporary_address: toUpperCase(form.temporary_address),
        municipality: toUpperCase(form.municipality?.name || form.municipality) || null,
        barangay: toUpperCase(form.barangay?.name || form.barangay) || null,
        temporary_municipality: toUpperCase(form.temporary_municipality?.name || form.temporary_municipality) || null,
        temporary_barangay: toUpperCase(form.temporary_barangay?.name || form.temporary_barangay) || null,
        place_of_birth: toUpperCase(form.place_of_birth?.name || form.place_of_birth) || null,
        religion: toUpperCase(form.religion),
        indigenous_group: toUpperCase(form.indigenous_group),
        father_name: toUpperCase(form.father_name),
        mother_name: toUpperCase(form.mother_name),
        guardian_name: toUpperCase(form.guardian_name),
        father_occupation: toUpperCase(form.father_occupation),
        mother_occupation: toUpperCase(form.mother_occupation),
        guardian_occupation: toUpperCase(form.guardian_occupation),
        guardian_relationship: toUpperCase(form.guardian_relationship),
        remarks: toUpperCase(form.remarks),
        date_of_birth: formatDateForBackend(form.date_of_birth),
        date_filed: formatDateForBackend(form.date_filed),
        academic_year: normalizeAcademicYearValue(form.academic_year?.value || form.academic_year),
        course_id: form.course?.id || null,
        school_id: form.school?.id || null,
        program_id: form.program?.id || null,
        course: form.course?.shortname || form.course?.name || form.course || null,
        school: form.school?.shortname || form.school?.name || form.school || null,
        program: form.program?.name || form.program || null,
        year_level: form.year_level?.value || form.year_level || null,
        term: normalizeTermValue(form.term?.value || form.term),
        yakap_category: form.yakap_category || null,
        yakap_location: form.yakap_location || null,
    };

    if (props.mode === 'edit' && props.profile) {
        const profileId = props.profile.profile_id;

        form.transform(() => submitData).put(route('applicants.update', profileId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Application updated successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                emit('success');
                closeModal();
            },
            onError: (errors) => {
                console.error('Update failed with errors:', errors);
                const errorMessage = Object.values(errors).flat().join(', ') || 'Failed to update application';
                toast.error(errorMessage, {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
        });
    } else {
        form.transform(() => submitData).post(route('applicants.store'), {
            preserveScroll: true,
            onSuccess: (response) => {
                toast.success('Application submitted successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                const createdProfile = response.props.profile || response.props.newProfile;
                if (createdProfile) {
                    emit('applicant-created', createdProfile);
                }
                emit('success');
                resetForm();
                closeModal();
            },
            onError: (errors) => {
                console.error('Create failed with errors:', errors);
                const errorMessage = Object.values(errors).flat().join(', ') || 'Failed to submit application';
                toast.error(errorMessage, {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
        });
    }
};

// Define empty form state
const emptyFormState = {
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    contact_no: '',
    contact_no_2: '',
    email: '',
    date_of_birth: null,
    gender: '',
    place_of_birth: null,
    civil_status: '',
    religion: '',
    indigenous_group: '',
    municipality: null,
    barangay: null,
    address: '',
    temporary_municipality: null,
    temporary_barangay: null,
    temporary_address: '',
    father_name: '',
    father_occupation: '',
    father_contact_no: '',
    mother_name: '',
    mother_occupation: '',
    mother_contact_no: '',
    guardian_name: '',
    guardian_occupation: '',
    guardian_relationship: '',
    guardian_contact_no: '',
    parents_guardian_gross_monthly_income: '',
    scholarship_grant_id: null,
    program: null,
    school: null,
    course: null,
    year_level: null,
    term: null,
    academic_year: null,
    date_filed: null,
    remarks: '',
    yakap_category: null,
    yakap_location: '',
};

const resetForm = () => {
    Object.entries(emptyFormState).forEach(([key, val]) => {
        form[key] = val;
    });
    // Set yakap from props if in create mode
    if (props.mode === 'create') {
        form.yakap_category = props.yakapCategory || null;
        form.yakap_location = props.yakapLocation || '';
    }
    form.clearErrors();
    activeStep.value = '1';
    validationError.value = '';
    isValidating.value = false;
    showDuplicateDialog.value = false;
    duplicateMatches.value = [];
    dupDragOffset.value = { x: 0, y: 0 };
};

const isMaximized = ref(false);
</script>
