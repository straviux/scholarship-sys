<template>
    <Dialog :visible="visible" modal @update:visible="val => !val && closeModal()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :class="{ 'ios-modal-maximized': isMaximized }" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">{{ mode === 'edit' ? 'Edit Application' : 'Application Form' }}</span>
                    <div class="ios-nav-right">
                        <span class="ios-nav-step-text">{{ activeStep }} of 3</span>
                        <button class="ios-nav-maximize" @click="isMaximized = !isMaximized"
                            v-tooltip.bottom="isMaximized ? 'Restore' : 'Maximize'">
                            <i :class="isMaximized ? 'pi pi-window-minimize' : 'pi pi-window-maximize'"
                                style="font-size: 14px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="ios-body">


                    <div class="mt-8">
                        <Stepper v-model:value="activeStep" :linear="mode !== 'edit' || !canProceedStep1">
                            <StepList>
                                <Step value="1" asChild>
                                    <template #default="{ activateCallback, active }">
                                        <button class="ios-step-btn" :class="{ 'ios-step-active': active }"
                                            @click="activateCallback">
                                            <i class="pi pi-user"></i>
                                            <span>Personal</span>
                                        </button>
                                    </template>
                                </Step>
                                <div class="ios-step-separator"></div>
                                <Step value="2" asChild>
                                    <template #default="{ activateCallback, active }">
                                        <button class="ios-step-btn"
                                            :class="{ 'ios-step-active': active, 'ios-step-disabled': !canProceedStep1 && mode !== 'edit' }"
                                            :disabled="!canProceedStep1 && mode !== 'edit'" @click="activateCallback">
                                            <i class="pi pi-users"></i>
                                            <span>Family</span>
                                        </button>
                                    </template>
                                </Step>
                                <div class="ios-step-separator"></div>
                                <Step value="3" asChild>
                                    <template #default="{ activateCallback, active }">
                                        <button class="ios-step-btn"
                                            :class="{ 'ios-step-active': active, 'ios-step-disabled': !canProceedStep1 && mode !== 'edit' }"
                                            :disabled="!canProceedStep1 && mode !== 'edit'" @click="activateCallback">
                                            <i class="pi pi-graduation-cap"></i>
                                            <span>Academic</span>
                                        </button>
                                    </template>
                                </Step>
                            </StepList>
                            <StepPanels>
                                <StepPanel value="1">
                                    <div class="flex flex-col h-full">
                                        <div class="flex-auto">
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
                                                class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                                <p class="text-sm text-red-800 font-medium">
                                                    <i class="pi pi-exclamation-triangle mr-2"></i>
                                                    {{ validationError }}
                                                </p>
                                            </div>

                                            <!-- Duplicate Name Confirmation Dialog -->
                                            <Dialog v-model:visible="showDuplicateDialog" modal
                                                :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
                                                <template #container>
                                                    <div class="ios-dup-modal" :style="dupModalStyle">
                                                        <div class="ios-dup-nav" @pointerdown="onDupDragStart">
                                                            <button class="ios-nav-btn ios-nav-cancel"
                                                                @click="showDuplicateDialog = false"><i
                                                                    class="pi pi-times"></i></button>
                                                            <span class="ios-nav-title"
                                                                style="font-size: 15px;">Possible Duplicate</span>
                                                            <button class="ios-nav-btn ios-dup-proceed"
                                                                @click="proceedDespiteDuplicate">Proceed</button>
                                                        </div>
                                                        <div class="ios-dup-body">
                                                            <div class="ios-section" style="margin-top: 12px;">
                                                                <div class="ios-section-footer" style="padding: 0;">
                                                                    <i class="pi pi-exclamation-triangle"
                                                                        style="color: #FF9500;"></i>
                                                                    The following record(s) match
                                                                    <strong>{{ form.first_name }} {{ form.last_name
                                                                        }}</strong>:
                                                                </div>
                                                            </div>
                                                            <div class="ios-section">
                                                                <div class="ios-section-label">Matches Found</div>
                                                                <div class="ios-card">
                                                                    <div v-for="(match, idx) in duplicateMatches"
                                                                        :key="match.profile_id" class="ios-row"
                                                                        :class="{ 'ios-row-last': idx === duplicateMatches.length - 1 }">
                                                                        <div
                                                                            style="display: flex; align-items: center; gap: 10px;">
                                                                            <i class="pi pi-user"
                                                                                style="color: #8E8E93; font-size: 16px;"></i>
                                                                            <div>
                                                                                <div class="ios-row-label">
                                                                                    {{ match.last_name }}, {{
                                                                                        match.first_name }}
                                                                                    {{ match.middle_name || '' }} {{
                                                                                        match.extension_name || '' }}
                                                                                </div>
                                                                                <div
                                                                                    style="font-size: 12px; color: #8E8E93;">
                                                                                    {{ match.municipality || `No
                                                                                    address` }}{{ match.barangay ? `,
                                                                                    ${match.barangay}` : '' }}
                                                                                    <span v-if="match.contact_no"> · {{
                                                                                        match.contact_no }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="ios-section">
                                                                <div class="ios-section-footer" style="padding: 0;">
                                                                    Are you sure this is a different person? You may
                                                                    proceed or close to review.
                                                                </div>
                                                            </div>
                                                            <div style="height: 16px;"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </Dialog>
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
                                                        v-model:academic_year="form.academic_year"
                                                        v-model:remarks="form.remarks"
                                                        v-model:yakap_category="form.yakap_category"
                                                        v-model:yakap_location="form.yakap_location"
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

                                                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                                        <p class="text-sm text-blue-800">
                                                            <i class="pi pi-info-circle mr-2"></i>
                                                            Academic information is optional. You can complete it now or
                                                            update it later.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </StepPanel>
                            </StepPanels>
                        </Stepper>
                    </div>
                </div>

                <!-- Footer -->
                <div class="ios-footer">
                    <button v-if="activeStep !== '1'" class="ios-footer-btn ios-footer-back"
                        @click="activeStep = String(Number(activeStep) - 1)">
                        <i class="pi pi-arrow-left" style="font-size: 12px;"></i> Back
                    </button>
                    <span v-else></span>
                    <button v-if="activeStep === '1'" class="ios-footer-btn ios-footer-next" @click="handleNextStep1"
                        :disabled="!canProceedStep1 || isValidating" v-tooltip.top="step1TooltipMessage">
                        {{ isValidating ? 'Checking...' : 'Next' }} <i class="pi pi-arrow-right"
                            style="font-size: 12px;"></i>
                    </button>
                    <button v-else-if="activeStep === '2'" class="ios-footer-btn ios-footer-next"
                        @click="activeStep = '3'">
                        Next <i class="pi pi-arrow-right" style="font-size: 12px;"></i>
                    </button>
                    <button v-else class="ios-footer-btn ios-footer-submit" @click="handleSubmit"
                        :disabled="form.processing">
                        <i class="pi pi-check" style="font-size: 12px;"></i>
                        {{ form.processing ? 'Saving...' : (mode === 'edit' ? 'Update' : 'Submit') }}
                    </button>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue';
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
        resetForm();
    }

    // Reset drag position when modal opens
    if (newValue) {
        dragOffset.value = { x: 0, y: 0 };
    }
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
        academic_year: form.academic_year?.value || form.academic_year || null,
        course_id: form.course?.id || null,
        school_id: form.school?.id || null,
        program_id: form.program?.id || null,
        course: form.course?.shortname || form.course?.name || form.course || null,
        school: form.school?.shortname || form.school?.name || form.school || null,
        program: form.program?.name || form.program || null,
        year_level: form.year_level?.value || form.year_level || null,
        term: form.term?.value || form.term || null,
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

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const isMaximized = ref(false);

/* ── Duplicate Dialog Drag ── */
const dupDragOffset = ref({ x: 0, y: 0 });
const dupDragStart = ref(null);
const dupModalStyle = computed(() => ({
    transform: `translate(${dupDragOffset.value.x}px, ${dupDragOffset.value.y}px)`,
}));
function onDupDragStart(e) {
    if (e.target.closest('button')) return;
    dupDragStart.value = { x: e.clientX - dupDragOffset.value.x, y: e.clientY - dupDragOffset.value.y };
    document.addEventListener('pointermove', onDupDragMove);
    document.addEventListener('pointerup', onDupDragEnd);
}
function onDupDragMove(e) {
    if (!dupDragStart.value) return;
    dupDragOffset.value = { x: e.clientX - dupDragStart.value.x, y: e.clientY - dupDragStart.value.y };
}
function onDupDragEnd() {
    dupDragStart.value = null;
    document.removeEventListener('pointermove', onDupDragMove);
    document.removeEventListener('pointerup', onDupDragEnd);
}
const modalStyle = computed(() => {
    if (isMaximized.value) {
        return {
            width: '100vw',
            height: '100vh',
            transform: 'none',
        };
    }
    return {
        width: '900px',
        transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
    };
});

function onDragStart(e) {
    if (isMaximized.value) return;
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-checkbox, .p-autocomplete, .p-stepper, .p-datepicker, .p-editor')) return;
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

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 90vh;
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

.ios-nav-right {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 10px;
}

.ios-nav-step-text {
    font-size: 13px;
    color: #8E8E93;
    font-weight: 400;
}

.ios-nav-maximize {
    background: none;
    border: none;
    color: #8E8E93;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    transition: opacity 0.15s;
}

.ios-nav-maximize:hover {
    opacity: 0.6;
}

.ios-modal-maximized {
    border-radius: 0;
    max-height: 100vh;
}

.ios-modal-maximized .ios-nav-bar {
    cursor: default;
}

.ios-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #FFFFFF;
    border-top: 0.5px solid #E5E5EA;
    flex-shrink: 0;
}

.ios-footer-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    padding: 6px 14px;
    border-radius: 8px;
    transition: opacity 0.15s, background 0.15s;
}

.ios-footer-btn:hover {
    background: rgba(0, 0, 0, 0.04);
}

.ios-footer-btn:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
    background: none;
}

.ios-footer-back {
    color: #6B7280;
    font-weight: 400;
}

.ios-footer-next {
    color: #007AFF;
}

.ios-footer-submit {
    color: #34C759;
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
    padding: 6px 0 0;
    line-height: 1.3;
}

/* Duplicate dialog */
.ios-dup-modal {
    width: 480px;
    max-height: 85vh;
    background: #F2F2F7;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.ios-dup-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 12px 16px;
    background: rgba(249, 249, 249, 0.94);
    backdrop-filter: blur(20px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    min-height: 44px;
    cursor: grab;
}

.ios-dup-nav:active {
    cursor: grabbing;
}

.ios-dup-nav .ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.ios-dup-nav .ios-nav-cancel {
    left: 16px;
}

.ios-dup-nav .ios-dup-proceed {
    right: 16px;
}

.ios-dup-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-dup-proceed {
    font-size: 15px;
    font-weight: 600;
    color: #FF9500;
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

/* Make Stepper blend with iOS background */
.ios-modal .p-stepper {
    background: transparent !important;
}

.ios-modal .p-steppanels {
    background: transparent !important;
}

.ios-modal .p-steppanel {
    background: transparent !important;
}

.ios-modal .p-steppanel-content {
    background: transparent !important;
}

/* Icon-based step buttons */
.ios-step-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 16px;
    border-radius: 10px;
    transition: all 0.2s ease;
    color: #8e8e93;
    font-size: 0.7rem;
    font-weight: 500;
}

.ios-step-btn i {
    font-size: 1.1rem;
}

.ios-step-btn:hover {
    color: #636366;
    background: rgba(0, 0, 0, 0.05);
}

.ios-step-btn.ios-step-active {
    color: #555;
}

.ios-step-btn.ios-step-active i {
    font-weight: 700;
}

.ios-step-btn.ios-step-disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.ios-step-separator {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    align-self: center;
    border-radius: 1px;
}
</style>
