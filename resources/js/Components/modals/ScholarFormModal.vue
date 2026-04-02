<template>
    <Dialog :visible="visible" modal @update:visible="val => !val && closeModal()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :class="{ 'ios-modal-maximized': isMaximized }" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">{{ mode === 'edit' ? 'Edit Scholar' : 'Add Existing Scholar' }}</span>
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
                                <!-- Step 1: Personal Information -->
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

                                            <!-- Validation Messages -->
                                            <div v-if="validationError"
                                                class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                                                <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                                                    <i class="pi pi-exclamation-triangle mr-2"></i>
                                                    {{ validationError }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </StepPanel>

                                <!-- Step 2: Family Information -->
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

                                <!-- Step 3: Academic Information (ALL REQUIRED for scholars) -->
                                <StepPanel value="3">
                                    <div class="flex flex-col h-full">
                                        <div class="flex-auto">
                                            <div>
                                                <div class="space-y-4">
                                                    <AcademicInformationFields v-model:program="form.program"
                                                        v-model:school="form.school" v-model:course="form.course"
                                                        v-model:year_level="form.year_level" v-model:term="form.term"
                                                        v-model:academic_year="form.academic_year"
                                                        v-model:remarks="form.remarks" :show-header="false" />

                                                    <!-- Date Fields (for backlog encoding) -->
                                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-6">
                                                        <FloatLabel>
                                                            <DatePicker v-model="form.date_filed" type="date"
                                                                inputId="date_filed" variant="filled"
                                                                placeholder="mm/dd/yyyy" showIcon fluid
                                                                iconDisplay="input" :manualInput="true"
                                                                @input="formatDateInput" />
                                                            <label class="text-sm" for="date_filed">Date Filed</label>
                                                        </FloatLabel>
                                                        <FloatLabel>
                                                            <DatePicker v-model="form.date_approved" type="date"
                                                                inputId="date_approved" variant="filled"
                                                                placeholder="mm/dd/yyyy" showIcon fluid
                                                                iconDisplay="input" :manualInput="true"
                                                                @input="formatDateInput" />
                                                            <label class="text-sm" for="date_approved">Date
                                                                Approved</label>
                                                        </FloatLabel>
                                                    </div>

                                                    <div
                                                        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                                                        <p class="text-sm text-blue-800 dark:text-blue-300">
                                                            <i class="pi pi-info-circle mr-2"></i>
                                                            <strong>Note:</strong> All academic fields are required for
                                                            scholars.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Academic Validation Messages -->
                                            <div v-if="academicValidationError"
                                                class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                                                <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                                                    <i class="pi pi-exclamation-triangle mr-2"></i>
                                                    {{ academicValidationError }}
                                                </p>
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
                        :disabled="!canProceedStep1" v-tooltip.top="step1TooltipMessage">
                        Next <i class="pi pi-arrow-right" style="font-size: 12px;"></i>
                    </button>
                    <button v-else-if="activeStep === '2'" class="ios-footer-btn ios-footer-next"
                        @click="activeStep = '3'">
                        Next <i class="pi pi-arrow-right" style="font-size: 12px;"></i>
                    </button>
                    <button v-else class="ios-footer-btn ios-footer-submit" @click="handleSubmit"
                        :disabled="form.processing || !canSubmit" v-tooltip.top="submitTooltipMessage">
                        <i class="pi pi-check" style="font-size: 12px;"></i>
                        {{ form.processing ? 'Saving...' : (mode === 'edit' ? 'Update' : 'Add Scholar') }}
                    </button>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    profile: Object,
    mode: {
        type: String,
        default: 'create', // 'create' or 'edit'
        validator: (value) => ['create', 'edit'].includes(value)
    }
});

const emit = defineEmits(['update:visible', 'success']);

const activeStep = ref('1');
const validationError = ref('');
const academicValidationError = ref('');

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
    date_filed: formatDateForPicker(grant?.date_filed) || null,
    date_approved: formatDateForPicker(grant?.date_approved) || null,
    remarks: grant?.remarks || p?.remarks || '',
});

// Step 1 validation - Scholars need more required fields
const canProceedStep1 = computed(() => {
    return form.first_name && form.last_name && form.municipality && form.contact_no;
});

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

// Handle next step 1
const handleNextStep1 = () => {
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

    validationError.value = '';
    activeStep.value = '2';
};

// Step 3 validation - ALL academic fields required for scholars
const canSubmit = computed(() => {
    return form.program && form.course && form.school &&
        form.year_level && form.term && form.academic_year;
});

const submitTooltipMessage = computed(() => {
    const missingAcademicFields = [];
    if (!form.program) missingAcademicFields.push('Program');
    if (!form.school) missingAcademicFields.push('School');
    if (!form.course) missingAcademicFields.push('Course');
    if (!form.year_level) missingAcademicFields.push('Year Level');
    if (!form.term) missingAcademicFields.push('Term');
    if (!form.academic_year) missingAcademicFields.push('Academic Year');

    if (missingAcademicFields.length > 0) {
        return `Missing required academic fields: ${missingAcademicFields.join(', ')}`;
    }
    return '';
});

const closeModal = () => {
    emit('update:visible', false);
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const isMaximized = ref(false);

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
        form.date_filed = formatDateForPicker(grant?.date_filed) || new Date();
        form.date_approved = formatDateForPicker(grant?.date_approved) || null;
        form.remarks = grant?.remarks || p?.remarks || '';

        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        academicValidationError.value = '';
    } else if (newValue && props.mode === 'create') {
        // In create mode, reset to empty form
        form.reset();
        form.date_filed = null; // Leave date_filed blank
        form.date_approved = null; // Leave date_approved empty for backlog encoding
        form.clearErrors();
        activeStep.value = '1';
        validationError.value = '';
        academicValidationError.value = '';
    }

    // Reset drag position when modal opens
    if (newValue) {
        dragOffset.value = { x: 0, y: 0 };
    }
});

const handleSubmit = () => {
    // Validate all required academic fields for scholars
    const missingAcademicFields = [];
    if (!form.program) missingAcademicFields.push('Program');
    if (!form.school) missingAcademicFields.push('School');
    if (!form.course) missingAcademicFields.push('Course');
    if (!form.year_level) missingAcademicFields.push('Year Level');
    if (!form.term) missingAcademicFields.push('Term');
    if (!form.academic_year) missingAcademicFields.push('Academic Year');

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
        console.log('Updating scholar with ID:', profileId);
        console.log('Submitting data:', submitData);

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
        console.log('Creating new scholar with data:', submitData);

        form.transform(() => submitData).post(route('scholars.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Scholar added successfully!', {
                    position: toast.POSITION.TOP_RIGHT,
                });
                // Reset form after successful creation
                form.reset();
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
