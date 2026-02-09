<template>
    <Dialog :visible="visible" modal :header="mode === 'edit' ? 'Edit Application' : 'Application Form'"
        :style="{ width: '90vw', maxWidth: '980px' }" @update:visible="val => emit('update:visible', val)"
        :maximizable="true">
        <template #header>
            <div class="flex items-center gap-2">
                <i :class="mode === 'edit' ? 'pi pi-pencil text-lg text-orange-600' : 'pi pi-file-edit text-lg text-blue-600'"
                    style="font-size: 1.2rem;"></i>
                <span class="font-semibold text-xl">
                    {{ mode === 'edit' ? 'Edit Application' : 'Scholarship Application Form' }}
                </span>
            </div>
        </template>

        <div class="space-y-4">
            <p class="text-sm text-gray-600">
                {{ mode === `edit`
                    ? `Update the application information. Changes will be saved immediately.`
                    : `Complete all sections to submit a scholarship application. All information will be reviewed during
                the
                application process.` }}
            </p>

            <Stepper v-model:value="activeStep" :linear="mode !== 'edit' || !canProceedStep1">
                <StepList>
                    <Step value="1">Personal Information</Step>
                    <Step value="2">Family Information</Step>
                    <Step value="3">Academic Information</Step>
                </StepList>
                <StepPanels>
                    <StepPanel value="1">
                        <div class="flex flex-col h-full">
                            <div class="flex-auto">
                                <PersonalInformationFields v-model:first_name="form.first_name"
                                    v-model:middle_name="form.middle_name" v-model:last_name="form.last_name"
                                    v-model:extension_name="form.extension_name" v-model:contact_no="form.contact_no"
                                    v-model:contact_no_2="form.contact_no_2" v-model:email="form.email"
                                    v-model:date_of_birth="form.date_of_birth" v-model:gender="form.gender"
                                    v-model:place_of_birth="form.place_of_birth"
                                    v-model:civil_status="form.civil_status" v-model:religion="form.religion"
                                    v-model:indigenous_group="form.indigenous_group"
                                    v-model:municipality="form.municipality" v-model:barangay="form.barangay"
                                    v-model:address="form.address"
                                    v-model:temporary_municipality="form.temporary_municipality"
                                    v-model:temporary_barangay="form.temporary_barangay"
                                    v-model:temporary_address="form.temporary_address" :show-header="false" />

                                <!-- Duplicate Name Warning -->
                                <div v-if="validationError" class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                    <p class="text-sm text-red-800 font-medium">
                                        <i class="pi pi-exclamation-triangle mr-2"></i>
                                        {{ validationError }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex pt-6 justify-end">
                                <Button label="Next" icon="pi pi-arrow-right" iconPos="right" @click="handleNextStep1"
                                    :loading="isValidating" :disabled="!canProceedStep1"
                                    v-tooltip.top="step1TooltipMessage" />
                            </div>
                        </div>
                    </StepPanel>
                    <StepPanel value="2">
                        <div class="flex flex-col h-full">
                            <div class="flex-auto">
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
                            <div class="flex pt-6 justify-between">
                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                    @click="activeStep = '1'" />
                                <Button label="Next" icon="pi pi-arrow-right" iconPos="right"
                                    @click="activeStep = '3'" />
                            </div>
                        </div>
                    </StepPanel>
                    <StepPanel value="3">
                        <div class="flex flex-col h-full">
                            <div class="flex-auto">
                                <div>
                                    <div class="space-y-4">
                                        <AcademicInformationFields v-model:program="form.program"
                                            v-model:school="form.school" v-model:course="form.course"
                                            v-model:year_level="form.year_level" v-model:term="form.term"
                                            v-model:academic_year="form.academic_year" v-model:remarks="form.remarks"
                                            v-model:yakap_category="form.yakap_category"
                                            v-model:yakap_location="form.yakap_location" :show-header="false" />

                                        <!-- Date Filed -->
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-10">
                                            <FloatLabel>
                                                <DatePicker v-model="form.date_filed" type="date" inputId="date_filed"
                                                    variant="filled" placeholder="mm/dd/yyyy" showIcon fluid
                                                    iconDisplay="input" :manualInput="true" @input="formatDateInput" />
                                                <label class="text-sm" for="date_filed">Date Filed</label>
                                            </FloatLabel>
                                        </div>

                                        <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                            <p class="text-sm text-blue-800">
                                                <i class="pi pi-info-circle mr-2"></i>
                                                Academic information is optional. You can complete it now or update it
                                                later.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex pt-6 justify-between">
                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                    @click="activeStep = '2'" />
                                <Button :label="mode === 'edit' ? 'Update Application' : 'Submit Application'"
                                    icon="pi pi-check" severity="success" @click="handleSubmit"
                                    :loading="form.processing" />
                            </div>
                        </div>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';
import FamilyInformationFields from '@/Components/forms/fields/FamilyInformationFields.vue';
import AcademicInformationFields from '@/Components/forms/fields/AcademicInformationFields.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

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
        default: 'create', // 'create' or 'edit'
        validator: (value) => ['create', 'edit'].includes(value)
    },
    yakapCategory: {
        type: String,
        default: null // Changed from 'yakap-capitol' to null so new applicants don't have a default
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

// Helper function to format date for DatePicker
const formatDateForPicker = (dateString) => {
    if (!dateString) return null;
    // If already a Date object, return it
    if (dateString instanceof Date) return dateString;
    // Parse string to Date
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

    // Format as YYYY-MM-DD in local timezone
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
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
    scholarship_grant_id: grant?.scholarship_grant_id || null,
    program: grant?.program || null,
    school: grant?.school || null,
    course: grant?.course || null,
    year_level: grant?.year_level || null,
    term: grant?.term || null,
    academic_year: grant?.academic_year || null,
    date_filed: formatDateForPicker(grant?.date_filed),
    remarks: grant?.remarks || p?.remarks || '',
    yakap_category: props.mode === 'create' ? (props.yakapCategory || null) : (grant?.yakap_category || null),
    yakap_location: props.mode === 'create' ? props.yakapLocation : (grant?.yakap_location || ''),
});

// Validation for step 1
const canProceedStep1 = computed(() => {
    return form.first_name && form.last_name;
});

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
    console.log('handleNextStep1 called - Mode:', props.mode);

    // Validate required fields
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');
    // if (!form.municipality) missingFields.push('Municipality');

    if (missingFields.length > 0) {
        validationError.value = `Please fill in the following required fields: ${missingFields.join(', ')}.`;
        toast.error(`Please fill in all required fields: ${missingFields.join(', ')}`, {
            position: toast.POSITION.TOP_RIGHT,
        });
        return;
    }

    // Skip duplicate name validation in edit mode
    if (props.mode === 'edit') {
        console.log('Edit mode - skipping duplicate validation');
        activeStep.value = '2';
        return;
    }

    console.log('Starting duplicate name validation...');
    isValidating.value = true;
    validationError.value = '';

    try {
        console.log('Calling API with data:', {
            first_name: form.first_name,
            middle_name: form.middle_name || '',
            last_name: form.last_name
        });

        const response = await axios.post(route('api.profiles.validate-name'), {
            first_name: form.first_name,
            middle_name: form.middle_name || '',
            last_name: form.last_name
        });

        console.log('API response:', response.data);

        if (response.data.exists) {
            console.log('Duplicate name found - blocking progression');
            validationError.value = `A record with the name "${form.first_name} ${form.last_name}" already exists in the system. Please verify if this is a different person before proceeding.`;
            toast.warning('A record with this name already exists. Please verify before proceeding.', {
                position: toast.POSITION.TOP_RIGHT,
            });
            // Do NOT proceed to step 2
        } else {
            console.log('No duplicate found - proceeding to step 2');
            // Validation passed, proceed to next step
            activeStep.value = '2';
        }
    } catch (error) {
        console.error('Validation error:', error);
        console.error('Error response:', error.response?.data);
        validationError.value = 'An error occurred while validating. Please try again.';
        toast.error('An error occurred while validating. Please try again.', {
            position: toast.POSITION.TOP_RIGHT,
        });
    } finally {
        isValidating.value = false;
    }
};

const closeModal = () => {
    emit('update:visible', false);
    resetForm();
};

// Reset form when modal is opened - matching PriorityModal pattern
watch(() => props.visible, async (newValue) => {
    if (newValue && props.mode === 'edit' && props.profile) {
        // console.log('props.profile', props.profile);

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
        // console.log('barangay', form.barangay);

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
        form.date_filed = formatDateForPicker(grant?.date_filed);
        form.remarks = grant?.remarks || p?.remarks || '';

        form.yakap_category = grant?.yakap_category || null;
        form.yakap_location = grant?.yakap_location || '';

        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        isValidating.value = false;
    } else if (newValue && props.mode === 'create') {
        // In create mode, reset to empty form
        form.reset();
        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        isValidating.value = false;
    }
});

const handleSubmit = () => {
    // Helper function to convert string to uppercase
    const toUpperCase = (value) => {
        if (!value || typeof value !== 'string') return value;
        return value.toUpperCase();
    };

    // Transform data before submitting
    const submitData = {
        ...form.data(),
        // Convert all text fields to uppercase
        first_name: toUpperCase(form.first_name),
        middle_name: toUpperCase(form.middle_name),
        last_name: toUpperCase(form.last_name),
        extension_name: toUpperCase(form.extension_name),
        address: toUpperCase(form.address),
        temporary_address: toUpperCase(form.temporary_address),
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
        // Format dates properly to avoid timezone issues
        date_of_birth: formatDateForBackend(form.date_of_birth),
        date_filed: formatDateForBackend(form.date_filed),
        // Extract value from academic_year object if it exists
        academic_year: form.academic_year?.value || form.academic_year || null,
        // Extract name from municipality object if it exists
        municipality: form.municipality?.name || form.municipality || null,
        // Extract name from barangay object if it exists
        barangay: form.barangay?.name || form.barangay || null,
        // Extract name from temporary municipality object if it exists
        temporary_municipality: form.temporary_municipality?.name || form.temporary_municipality || null,
        // Extract name from temporary barangay object if it exists
        temporary_barangay: form.temporary_barangay?.name || form.temporary_barangay || null,
        // Send IDs for course, school, and program (backend expects IDs for scholarship records)
        course_id: form.course?.id || null,
        school_id: form.school?.id || null,
        program_id: form.program?.id || null,
        // Also send names for backward compatibility (backend uses these for lookup)
        course: form.course?.shortname || form.course?.name || form.course || null,
        school: form.school?.shortname || form.school?.name || form.school || null,
        program: form.program?.name || form.program || null,
        // Extract value from year_level object if it exists
        year_level: form.year_level?.value || form.year_level || null,
        // Extract value from term object if it exists (1ST SEM, 2ND SEM, etc.)
        term: form.term?.value || form.term || null,
        // YAKAP fields - only set yakap_category if it has a value, otherwise leave it as empty/null
        yakap_category: form.yakap_category || null,
        yakap_location: (() => {
            if (!form.yakap_location) return '';
            // If it's a JSON string, keep it as-is (backend will parse)
            if (typeof form.yakap_location === 'string') {
                return form.yakap_location;
            }
            // If it's an object, convert to JSON string
            if (typeof form.yakap_location === 'object') {
                return JSON.stringify(form.yakap_location);
            }
            return '';
        })(),
    };

    console.log('🔍 SUBMIT DATA DEBUG:', {
        form_yakap_category: form.yakap_category,
        form_yakap_location: form.yakap_location,
        submit_yakap_category: submitData.yakap_category,
        submit_yakap_location: submitData.yakap_location,
        props_yakap_category: props.yakapCategory,
        props_yakap_location: props.yakapLocation,
    });

    if (props.mode === 'edit' && props.profile) {
        // Update existing profile - use profile_id from the profile object
        const profileId = props.profile.profile_id;
        console.log('Updating profile with ID:', profileId);
        console.log('Submitting data:', submitData);

        form.transform(() => submitData).put(route('waitinglist.update', profileId), {
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
        // Create new profile
        console.log('Creating new profile with data:', submitData);

        form.transform(() => submitData).post(route('waitinglist.store'), {
            preserveScroll: true,
            onSuccess: (response) => {
                toast.success('Application submitted successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                // Get the created profile from the response
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
    // Personal Information
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
    // Family Information
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
    // Academic Information
    scholarship_grant_id: null,
    program: null,
    school: null,
    course: null,
    year_level: null,
    term: null,
    academic_year: null,
    date_filed: null,
    remarks: '',
    yakap_category: null,  // Changed from 'yakap-capitol' to null - will be set from props
    yakap_location: '',
};

// Watch yakap prop changes and sync with form
watch(() => props.yakapCategory, (newValue) => {
    console.log('yakapCategory prop changed to:', newValue);
    if (props.mode === 'create' && newValue) {
        form.yakap_category = newValue;
        console.log('Form yakap_category updated:', form.yakap_category);
    }
});

watch(() => props.yakapLocation, (newValue) => {
    console.log('yakapLocation prop changed to:', newValue);
    if (props.mode === 'create' && newValue) {
        form.yakap_location = newValue;
        console.log('Form yakap_location updated:', form.yakap_location);
    }
});

// Reset form to initial empty state
const resetForm = () => {
    // Reset to empty values for create mode
    Object.keys(emptyFormState).forEach(key => {
        // Use props for yakap fields instead of empty state defaults
        if (key === 'yakap_category') {
            form[key] = props.yakapCategory || null;
        } else if (key === 'yakap_location') {
            form[key] = props.yakapLocation || '';
        } else {
            form[key] = emptyFormState[key];
        }
    });
    form.clearErrors();
    activeStep.value = '1';
    validationError.value = null;
    console.log('Form reset with yakap values from props:', {
        yakap_category: form.yakap_category,
        yakap_location: form.yakap_location
    });
};

// Watch for modal visibility changes - reset form when opening in create mode
watch(() => props.visible, (newVal, oldVal) => {
    if (newVal && !oldVal && props.mode === 'create') {
        // Modal is opening in create mode - reset to empty state
        nextTick(() => {
            resetForm();
        });
    }
});

onMounted(() => {
    console.log('ApplicantFormModal mounted with props:', props);
});

// Add to form state
const yakapCategoryOptions = [
    { value: 'yakap-capitol', label: 'YAKAP Capitol' },
    { value: 'yakap-school', label: 'YAKAP School' },
    { value: 'yakap-field', label: 'YAKAP Field' }
];

// Allow yakap_category to be null/empty - user must select from modal
if (!form.yakap_location) form.yakap_location = '';
</script>
