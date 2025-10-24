<template>
    <Dialog :visible="visible" modal header="Add Existing Scholar" :style="{ width: '90vw', maxWidth: '980px' }"
        @update:visible="handleVisibilityChange" :maximizable="true">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-user-edit text-blue-400" style="font-size: 1.2rem;"></i>
                <span class="font-semibold text-xl">Existing Scholar</span>
            </div>
        </template>

        <div class="space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded p-3">
                <p class="text-sm text-amber-800 font-medium">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    All academic information must be filled out to add an existing scholar.
                </p>
            </div>

            <Stepper v-model:value="activeStep" linear>
                <StepList>
                    <Step value="1">Personal Information</Step>
                    <Step value="2">Family Information</Step>
                    <Step value="3">Academic Information</Step>
                </StepList>
                <StepPanels>
                    <StepPanel value="1">
                        <div class="flex flex-col h-full">
                            <div class="flex-auto">
                                <PersonalInformationFields v-model="personalInfo" :show-header="false" />
                                <div v-if="validationError" class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                    <p class="text-sm text-red-800 font-medium">
                                        <i class="pi pi-exclamation-triangle mr-2"></i>
                                        {{ validationError }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex pt-6 justify-end">
                                <Button label="Next" icon="pi pi-arrow-right" iconPos="right" @click="handleNextStep1"
                                    :loading="isValidating" :disabled="!canProceedStep1" />
                            </div>
                        </div>
                    </StepPanel>
                    <StepPanel value="2">
                        <div class="flex flex-col h-full">
                            <div class="flex-auto">
                                <FamilyInformationFields v-model="familyInfo" />
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
                                    <h4 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="pi pi-book text-blue-600"></i>
                                        Academic Information (Required)
                                    </h4>
                                    <div class="space-y-4">
                                        <AcademicInformationFields v-model="academicInfo" :show-header="false" />

                                        <!-- Date Fields and Remarks (Only for Add Existing) -->
                                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 mt-10">
                                            <FloatLabel class="md:col-span-4">
                                                <Textarea v-model="form.remarks" inputId="remarks" variant="filled"
                                                    placeholder="&nbsp;" rows="1" fluid autoResize />
                                                <label class="text-sm" for="remarks">Remarks</label>
                                            </FloatLabel>
                                            <FloatLabel>
                                                <DatePicker v-model="form.date_filed" type="date" inputId="date_filed"
                                                    variant="filled" placeholder="mm/dd/yyyy" showIcon fluid
                                                    iconDisplay="input" :manualInput="true" @input="formatDateInput" />
                                                <label class="text-sm" for="date_filed">Date Filed</label>
                                            </FloatLabel>

                                            <FloatLabel>
                                                <DatePicker v-model="form.date_approved" type="date"
                                                    inputId="date_approved" variant="filled" placeholder="mm/dd/yyyy"
                                                    showIcon fluid iconDisplay="input" :manualInput="true"
                                                    @input="formatDateInput" />
                                                <label class="text-sm" for="date_approved">Date Approved</label>
                                            </FloatLabel>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex pt-6 justify-between">
                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                    @click="activeStep = '2'" />
                                <Button label="Add Existing Scholar" icon="pi pi-check" severity="info"
                                    @click="handleSubmit" :loading="form.processing" :disabled="!isFormValid" />
                            </div>
                        </div>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import Step from 'primevue/step';
import StepPanels from 'primevue/steppanels';
import StepPanel from 'primevue/steppanel';
import FloatLabel from 'primevue/floatlabel';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';
import AcademicInformationFields from '@/Components/forms/fields/AcademicInformationFields.vue';
import FamilyInformationFields from '@/Components/forms/fields/FamilyInformationFields.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'success']);

const activeStep = ref('1');
const isValidating = ref(false);
const validationError = ref('');

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

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    contact_no: '',
    contact_no_2: '',
    email: '',
    date_of_birth: '',
    gender: '',
    place_of_birth: '',
    civil_status: '',
    religion: '',
    indigenous_group: '',
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
    program: null,
    school: null,
    course: null,
    year_level: null,
    term: null,
    academic_year: null,
    date_filed: '',
    date_approved: '',
    remarks: '',
    municipality: null,
    barangay: null,
    address: '',
    temporary_municipality: null,
    temporary_barangay: null,
    temporary_address: '',
});

// Computed property for two-way binding with PersonalInformationFields
const personalInfo = computed({
    get: () => ({
        first_name: form.first_name,
        middle_name: form.middle_name,
        last_name: form.last_name,
        extension_name: form.extension_name,
        contact_no: form.contact_no,
        contact_no_2: form.contact_no_2,
        email: form.email,
        date_of_birth: form.date_of_birth,
        gender: form.gender,
        place_of_birth: form.place_of_birth,
        civil_status: form.civil_status,
        religion: form.religion,
        indigenous_group: form.indigenous_group,
        municipality: form.municipality,
        barangay: form.barangay,
        address: form.address,
        temporary_municipality: form.temporary_municipality,
        temporary_barangay: form.temporary_barangay,
        temporary_address: form.temporary_address,
    }),
    set: (value) => {
        form.first_name = value.first_name;
        form.middle_name = value.middle_name;
        form.last_name = value.last_name;
        form.extension_name = value.extension_name;
        form.contact_no = value.contact_no;
        form.contact_no_2 = value.contact_no_2;
        form.email = value.email;
        form.date_of_birth = value.date_of_birth;
        form.gender = value.gender;
        form.place_of_birth = value.place_of_birth;
        form.civil_status = value.civil_status;
        form.religion = value.religion;
        form.indigenous_group = value.indigenous_group;
        form.municipality = value.municipality;
        form.barangay = value.barangay;
        form.address = value.address;
        form.temporary_municipality = value.temporary_municipality;
        form.temporary_barangay = value.temporary_barangay;
        form.temporary_address = value.temporary_address;
    }
});

// Computed property for two-way binding with FamilyInformationFields
const familyInfo = computed({
    get: () => ({
        father_name: form.father_name,
        father_occupation: form.father_occupation,
        father_contact_no: form.father_contact_no,
        mother_name: form.mother_name,
        mother_occupation: form.mother_occupation,
        mother_contact_no: form.mother_contact_no,
        guardian_name: form.guardian_name,
        guardian_occupation: form.guardian_occupation,
        guardian_relationship: form.guardian_relationship,
        guardian_contact_no: form.guardian_contact_no,
        parents_guardian_gross_monthly_income: form.parents_guardian_gross_monthly_income,
    }),
    set: (value) => {
        form.father_name = value.father_name;
        form.father_occupation = value.father_occupation;
        form.father_contact_no = value.father_contact_no;
        form.mother_name = value.mother_name;
        form.mother_occupation = value.mother_occupation;
        form.mother_contact_no = value.mother_contact_no;
        form.guardian_name = value.guardian_name;
        form.guardian_occupation = value.guardian_occupation;
        form.guardian_relationship = value.guardian_relationship;
        form.guardian_contact_no = value.guardian_contact_no;
        form.parents_guardian_gross_monthly_income = value.parents_guardian_gross_monthly_income;
    }
});

// Computed property for two-way binding with AcademicInformationFields
const academicInfo = computed({
    get: () => ({
        program: form.program,
        school: form.school,
        course: form.course,
        year_level: form.year_level,
        term: form.term,
        academic_year: form.academic_year,
    }),
    set: (value) => {
        form.program = value.program;
        form.school = value.school;
        form.course = value.course;
        form.year_level = value.year_level;
        form.term = value.term;
        form.academic_year = value.academic_year;
    }
});

const isFormValid = computed(() => {
    return form.first_name &&
        form.last_name &&
        form.program &&
        form.school &&
        form.course &&
        form.year_level &&
        form.municipality;
});

const canProceedStep1 = computed(() => {
    return form.first_name && form.last_name && form.municipality && form.contact_no;
});

const handleNextStep1 = async () => {
    // Validate required fields
    const missingFields = [];
    if (!form.first_name) missingFields.push('First Name');
    if (!form.last_name) missingFields.push('Last Name');
    if (!form.municipality) missingFields.push('Municipality');
    if (!form.contact_no) missingFields.push('Contact Number');

    if (missingFields.length > 0) {
        validationError.value = `Please fill in the following required fields: ${missingFields.join(', ')}.`;
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
            validationError.value = `A record with this exact name (${[form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ')}) already exists in the system. Please verify if this is a different person before proceeding.`;
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

const handleVisibilityChange = (value) => {
    emit('update:visible', value);
    if (!value) {
        form.reset();
        activeStep.value = '1';
        validationError.value = '';
        isValidating.value = false;
    }
};

const handleCancel = () => {
    emit('update:visible', false);
    form.reset();
    activeStep.value = '1';
    validationError.value = '';
    isValidating.value = false;
};

const handleSubmit = () => {
    // Prepare the data with proper IDs and names
    const submitData = {
        // Personal Information
        first_name: form.first_name,
        middle_name: form.middle_name,
        last_name: form.last_name,
        extension_name: form.extension_name,
        contact_no: form.contact_no,
        contact_no_2: form.contact_no_2,
        email: form.email,
        date_of_birth: form.date_of_birth,
        gender: form.gender,
        place_of_birth: form.place_of_birth,
        civil_status: form.civil_status,
        religion: form.religion,
        indigenous_group: form.indigenous_group,

        // Address Information (using names as the database expects)
        municipality: form.municipality?.name || null,
        barangay: form.barangay?.name || null,
        address: form.address,
        temporary_municipality: form.temporary_municipality?.name || null,
        temporary_barangay: form.temporary_barangay?.name || null,
        temporary_address: form.temporary_address,

        // Family Information
        father_name: form.father_name,
        father_occupation: form.father_occupation,
        father_contact_no: form.father_contact_no,
        mother_name: form.mother_name,
        mother_occupation: form.mother_occupation,
        mother_contact_no: form.mother_contact_no,
        guardian_name: form.guardian_name,
        guardian_occupation: form.guardian_occupation,
        guardian_relationship: form.guardian_relationship,
        guardian_contact_no: form.guardian_contact_no,
        parents_guardian_gross_monthly_income: form.parents_guardian_gross_monthly_income,

        // Academic Information (Required for scholars) - send both IDs and names
        program_id: form.program?.id || null,
        program: form.program?.shortname || form.program?.name || null,
        school_id: form.school?.id || null,
        school: form.school?.shortname || form.school?.name || null,
        course_id: form.course?.id || null,
        course: form.course?.shortname || form.course?.name || null,
        year_level: form.year_level?.value || form.year_level,
        term: form.term?.value || form.term,
        academic_year: form.academic_year?.value || form.academic_year,

        // Date fields and remarks
        date_filed: form.date_filed,
        date_approved: form.date_approved,
        remarks: form.remarks,
    };

    form.post(route('scholars.store'), {
        data: submitData,
        preserveScroll: true,
        onSuccess: () => {
            emit('update:visible', false);
            emit('success');
            form.reset();
            activeStep.value = '1';
            validationError.value = '';
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
        }
    });
};
</script>
