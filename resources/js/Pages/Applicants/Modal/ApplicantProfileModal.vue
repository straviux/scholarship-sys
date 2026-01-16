<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-10">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/75" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex justify-center p-4 text-center items-center min-h-screen">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-5xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                <span v-if="action == 'create'">New Applicant Form</span>
                                <span v-if="action == 'update'">Update Applicant Form</span>
                                <span v-if="action == 'add-existing'">Applicant Form</span>

                                <!-- Close button - handle both inline modal and route navigation -->
                                <button v-if="isInlineModal" class="-mr-2 cursor-pointer" @click="emit('close')">
                                    <XMarkIcon class="h-6 w-6 text-red-500" />
                                </button>
                                <Link v-else class="-mr-2" :href="prevPage">
                                    <XMarkIcon class="h-6 w-6 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-6 bg-white">
                                <Stepper :value="activeStep" linear>
                                    <StepList>
                                        <Step value="1">Personal Information</Step>
                                        <Step value="2">Family Information</Step>
                                        <Step value="3">Academic Information</Step>
                                    </StepList>
                                    <StepPanels>
                                        <StepPanel v-slot="{ activateCallback }" value="1">
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <h2
                                                    class="text-base font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                    <span
                                                        class="inline-block w-2 h-5 bg-blue-500 rounded mr-2"></span>Personal
                                                    Information
                                                </h2>
                                                <div class="flex gap-3 mb-2 items-end"
                                                    v-if="action == 'create' || action == 'update'">
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="lastname" value="Last Name" />
                                                        <TextInput autofocus id="lastname" type="text"
                                                            class="w-full block uppercase" v-model="form.last_name"
                                                            :required="true" />
                                                        <InputError class="mt-1" :message="form.errors.last_name" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="firstname" value="First Name" />
                                                        <TextInput id="firstname" type="text"
                                                            class="w-full block uppercase" v-model="form.first_name"
                                                            :required="true" />
                                                        <InputError class="mt-1" :message="form.errors.first_name" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="middlename" value="Middle Name" />
                                                        <TextInput id="middlename" type="text"
                                                            class="w-full block uppercase" v-model="form.middle_name" />
                                                        <InputError class="mt-1" :message="form.errors.middle_name" />
                                                    </div>
                                                    <div style="max-width: 110px;">
                                                        <InputLabel class="mb-1" for="extension" value="Extension" />
                                                        <TextInput id="extension" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.extension_name" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.extension_name" />
                                                    </div>
                                                </div>
                                                <div class="flex gap-3 mb-2 items-end" v-else>
                                                    <ProfileSelect v-model="form.selectedProfile" />
                                                </div>
                                                <div class="flex gap-3 mb-2 items-end">
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="contact" value="Contact No." />
                                                        <TextInput id="contact" type="text"
                                                            class="w-full block uppercase" v-model="form.contact_no" />
                                                        <InputError class="mt-1" :message="form.errors.contact_no" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="contact_no_2"
                                                            value="Contact No. 2" />
                                                        <TextInput id="contact_no_2" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.contact_no_2" />
                                                        <InputError class="mt-1" :message="form.errors.contact_no_2" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <InputLabel class="mb-1" for="email" value="Email" />
                                                        <TextInput id="email" type="email" class="w-full block"
                                                            v-model="form.email" />
                                                        <InputError class="mt-1" :message="form.errors.email" />
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3 mb-8 mt-8">
                                                    <div>
                                                        <InputLabel class="mb-1" for="gender" value="Gender" />
                                                        <div class="flex gap-2 mt-1">
                                                            <label class="flex items-center cursor-pointer">
                                                                <input v-model="form.gender" type="radio" name="gender"
                                                                    value="M"
                                                                    class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300" />
                                                                <span class="ml-2">Male</span>
                                                            </label>
                                                            <label class="flex items-center cursor-pointer">
                                                                <input v-model="form.gender" type="radio" name="gender"
                                                                    value="F"
                                                                    class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300" />
                                                                <span class="ml-2">Female</span>
                                                            </label>
                                                        </div>
                                                        <InputError class="mt-1" :message="form.errors.gender" />
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3 mb-2">

                                                    <div>
                                                        <InputLabel class="mb-1" for="municipality"
                                                            value="Municipality" />
                                                        <MunicipalitySelect v-model="form.municipality"
                                                            custom-placeholder="Select Municipality" />
                                                        <InputError class="mt-1" :message="form.errors.municipality" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="barangay" value="Barangay" />
                                                        <BarangaySelect v-model="form.barangay"
                                                            :municipalityId="form.municipality"
                                                            custom-placeholder="Select Barangay" />
                                                        <InputError class="mt-1" :message="form.errors.barangay" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="street"
                                                            value="Purok/Street/Landmark" />
                                                        <TextInput id="street" type="text"
                                                            class="w-full block uppercase" v-model="form.address" />
                                                        <InputError class="mt-1" :message="form.errors.address" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex pt-4 justify-between gap-8">
                                                <Button label="Skip to Academic Info" severity="secondary"
                                                    @click="activeStep = '3'" :disabled="hasPendingOrOngoing" />
                                                <Button label="Save and Close" icon="pi pi-save" iconPos="right"
                                                    @click.prevent="submit" severity="success" size="small" />
                                                <Button label="Next" severity="secondary" icon="pi pi-arrow-right"
                                                    @click="activeStep = '2'" :disabled="hasPendingOrOngoing" />

                                            </div>
                                        </StepPanel>

                                        <StepPanel v-slot="{ activateCallback }" value="2">
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <h2
                                                    class="text-base font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                    <span
                                                        class="inline-block w-2 h-5 bg-green-500 rounded mr-2"></span>Parent's
                                                    Information
                                                </h2>
                                                <div class="grid grid-cols-3 gap-3 mb-2">
                                                    <div>
                                                        <InputLabel class="mb-1" for="father_name" value="Father" />
                                                        <TextInput id="father_name" type="text"
                                                            class="w-full block uppercase" v-model="form.father_name" />
                                                        <InputError class="mt-1" :message="form.errors.father_name" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="father_occupation"
                                                            value="Occupation" />
                                                        <TextInput id="father_occupation" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.father_occupation" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.father_occupation" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="father_contact_no"
                                                            value="Contact #" />
                                                        <TextInput id="father_contact_no" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.father_contact_no" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.father_contact_no" />
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-3 gap-3 mb-2">
                                                    <div>
                                                        <InputLabel class="mb-1" for="mother_name" value="Mother" />
                                                        <TextInput id="mother_name" type="text"
                                                            class="w-full block uppercase" v-model="form.mother_name" />
                                                        <InputError class="mt-1" :message="form.errors.mother_name" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="mother_occupation"
                                                            value="Occupation" />
                                                        <TextInput id="mother_occupation" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.mother_occupation" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.mother_occupation" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="mother_contact_no"
                                                            value="Contact #" />
                                                        <TextInput id="mother_contact_no" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.mother_contact_no" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.mother_contact_no" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <h2
                                                    class="text-base font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                    <span
                                                        class="inline-block w-2 h-5 bg-purple-500 rounded mr-2"></span>Guardian
                                                    Information
                                                </h2>
                                                <div class="grid grid-cols-2 gap-3 mb-2">
                                                    <div>
                                                        <InputLabel class="mb-1" for="guardian_name"
                                                            value="Guardian Name" />
                                                        <TextInput id="guardian_name" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.guardian_name" />
                                                        <InputError class="mt-1" :message="form.errors.guardian_name" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="guardian_relationship"
                                                            value="Relationship" />
                                                        <TextInput id="guardian_relationship" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.guardian_relationship" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.guardian_relationship" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="guardian_contact_no"
                                                            value="Contact #" />
                                                        <TextInput id="guardian_contact_no" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.guardian_contact_no" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.guardian_contact_no" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="guardian_occupation"
                                                            value="Guardian Occupation" />
                                                        <TextInput id="guardian_occupation" type="text"
                                                            class="w-full block uppercase"
                                                            v-model="form.guardian_occupation" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.guardian_occupation" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <h2
                                                    class="text-base font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                    <span
                                                        class="inline-block w-2 h-5 bg-yellow-500 rounded mr-2"></span>Parents/Guardian
                                                    Gross Monthly Income
                                                </h2>
                                                <div class="grid grid-cols-1 gap-3">
                                                    <div>
                                                        <InputLabel class="mb-1"
                                                            for="parents_guardian_gross_monthly_income"
                                                            value="Gross Monthly Income" />
                                                        <TextInput id="parents_guardian_gross_monthly_income"
                                                            type="number" class="w-1/3 block"
                                                            v-model="form.parents_guardian_gross_monthly_income" />
                                                        <InputError class="mt-1"
                                                            :message="form.errors.parents_guardian_gross_monthly_income" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex pt-4 justify-between">
                                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                                    @click="activeStep = '1'" />
                                                <Button label="Save and Close" icon="pi pi-save" iconPos="right"
                                                    @click.prevent="submit" severity="success" size="small" />
                                                <Button label="Next" severity="secondary" icon="pi pi-arrow-right"
                                                    @click="activeStep = '3'" :disabled="hasPendingOrOngoing" />

                                            </div>
                                        </StepPanel>
                                        <StepPanel v-slot="{ activateCallback }" value="3">
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <AcademicInformationFields v-model:program="form.program"
                                                    v-model:school="form.school" v-model:course="form.course"
                                                    v-model:year_level="form.year_level" v-model:term="form.term"
                                                    v-model:academic_year="form.academic_year"
                                                    v-model:remarks="form.remarks" />
                                            </div>
                                            <div class="mt-12 flex justify-between">
                                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                                    @click="activeStep = '2'" />
                                                <Button label="Save and Close" icon="pi pi-save" iconPos="right"
                                                    @click.prevent="submit" severity="success" size="small" />
                                                <div class="hidden"></div>
                                            </div>
                                        </StepPanel>

                                    </StepPanels>
                                </Stepper>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>

import { ref, computed, watch } from "vue";
import { Link, useForm, router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import VueMultiselect from "vue-multiselect";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// COURSE MULTISELECT COMPONENT
import AcademicInformationFields from '@/Components/forms/fields/AcademicInformationFields.vue';
import ProfileSelect from "@/Components/selects/ProfileSelect.vue";
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object,
    page: [Number, String],
    isInlineModal: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const isOpen = computed(() => props.action == 'create' || props.action == 'update' || props.action == 'add-existing');
// console.log(props.profile?.scholarship_grant[0].year_level);

// Helper function to format date for input[type="date"]
const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    // If already in YYYY-MM-DD format, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }
    // Otherwise, parse and format to YYYY-MM-DD
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "";
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// const selectedProfile = ref(null);
const form = useForm({
    scholarship_grant_id: props.profile?.scholarship_grant[0]?.id || null,
    program: props.profile?.scholarship_grant[0]?.scholarship_program || null,
    school: props.profile?.scholarship_grant[0]?.school?.name || "",
    course: props.profile?.scholarship_grant[0]?.course || null, // Changed: pass full object instead of just name
    year_level: props.profile?.scholarship_grant[0]?.year_level || "",
    term: props.profile?.scholarship_grant[0]?.term || "",
    academic_year: props.profile?.scholarship_grant[0]?.academic_year || "",
    first_name: props.profile?.first_name || "",
    last_name: props.profile?.last_name || "",
    middle_name: props.profile?.middle_name || "",
    extension_name: props.profile?.extension_name || "",
    father_name: props.profile?.father_name || "",
    father_occupation: props.profile?.father_occupation || "",
    father_contact_no: props.profile?.father_contact_no || "",
    mother_name: props.profile?.mother_name || "",
    mother_occupation: props.profile?.mother_occupation || "",
    mother_contact_no: props.profile?.mother_contact_no || "",
    municipality: props.profile?.municipality || null,
    barangay: props.profile?.barangay || null,
    address: props.profile?.address || "",
    contact_no: props.profile?.contact_no || "",
    email: props.profile?.email || "",
    gender: props.profile?.gender || "",
    remarks: props.profile?.remarks || "",
    date_filed: formatDateForInput(props.profile?.date_filed) || "", // Changed: format date properly
    date_approved: formatDateForInput(props.profile?.scholarship_grant[0]?.date_approved) || "",
    selectedProfile: "",
    contact_no_2: props.profile?.contact_no_2 || "",
    guardian_name: props.profile?.guardian_name || "",
    guardian_relationship: props.profile?.guardian_relationship || "",
    guardian_contact_no: props.profile?.guardian_contact_no || "",
    guardian_occupation: props.profile?.guardian_occupation || "",
    parents_guardian_gross_monthly_income: props.profile?.parents_guardian_gross_monthly_income || "",
    date_of_birth: props.profile?.date_of_birth || "",
    place_of_birth: props.profile?.place_of_birth || "",
    civil_status: props.profile?.civil_status || "",
    religion: props.profile?.religion || "",
    indigenous_group: props.profile?.indigenous_group || "",
    temporary_municipality: props.profile?.temporary_municipality || null,
    temporary_barangay: props.profile?.temporary_barangay || null,
    temporary_address: props.profile?.temporary_address || "",
    father_birthdate: props.profile?.father_birthdate || "",
    mother_birthdate: props.profile?.mother_birthdate || "",
});


const hasPendingOrOngoing = computed(() => {
    const profile = form.selectedProfile?.profile;
    if (profile && profile.scholarship_grant && profile.scholarship_grant.length > 0) {
        return profile.scholarship_grant.some(r => r.unified_status === 'pending' || r.unified_status === 'active' || r.unified_status === 'approved');
    }
    return false;
});

const errorMessage = ref("");

watch(() => form.selectedProfile, (profile) => {

    if (profile && profile.profile && props.action == 'add-existing') {
        // form.school = profile.profile?.scholarship_grant[0]?.school.name || '';
        // form.course = profile.profile?.scholarship_grant[0]?.course.name || '';
        // form.year_level = profile.profile?.scholarship_grant[0]?.year_level.name || '';
        form.first_name = profile.profile.first_name || '';
        form.last_name = profile.profile.last_name || '';
        form.middle_name = profile.profile.middle_name || '';
        form.extension_name = profile.profile.extension_name || '';
        form.father_name = profile.profile.father_name || '';
        form.father_contact_no = profile.profile.father_contact_no || '';
        form.father_occupation = profile.profile.father_occupation || '';
        form.mother_name = profile.profile.mother_name || '';
        form.mother_contact_no = profile.profile.mother_contact_no || '';
        form.mother_occupation = profile.profile.mother_occupation || '';
        form.parents_guardian_gross_monthly_income = profile.profile.parents_guardian_gross_monthly_income || '';
        form.guardian_occupation = profile.profile.guardian_occupation || '';
        form.guardian_relationship = profile.profile.guardian_relationship || '';
        form.guardian_name = profile.profile.guardian_name || '';
        form.guardian_contact_no = profile.profile.guardian_contact_no || '';
        form.municipality = profile.profile.municipality || null;
        form.barangay = profile.profile.barangay || null;
        form.address = profile.profile.address || '';
        form.contact_no = profile.profile.contact_no || '';
        form.email = profile.profile.email || '';
        form.gender = profile.profile.gender || '';
        form.remarks = profile.profile.remarks || '';
        // form.date_filed = profile.profile.date_filed || '';
    }
});

const page = usePage();
const prevPage = ref(page.props.urlPrev);

// Set up axios with CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || page.props.csrf_token;
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
}

const submit = (closeAfter = false) => {
    console.log('=== SUBMIT FUNCTION START ===');
    console.log('Form data at start:', form.data());

    // First, convert all empty/falsy values to null
    const fields = [
        'last_name', 'first_name', 'middle_name', 'extension_name',
        'father_name', 'father_occupation', 'father_contact_no', 'father_birthdate',
        'mother_name', 'mother_occupation', 'mother_contact_no', 'mother_birthdate',
        'guardian_name', 'guardian_relationship', 'guardian_contact_no', 'guardian_occupation',
        'parents_guardian_gross_monthly_income',
        'address', 'temporary_address', 'contact_no', 'contact_no_2', 'email',
        'date_of_birth', 'date_filed', 'place_of_birth',
        'gender', 'civil_status', 'religion', 'indigenous_group',
        'municipality', 'barangay', 'temporary_municipality', 'temporary_barangay',
        'remarks'
    ];

    fields.forEach(field => {
        if (!form[field] || form[field] === '') {
            form[field] = null;
        }
    });

    console.log('Form data after null conversion:', form.data());

    // Then apply uppercase to name fields that are not null
    if (form.last_name) form.last_name = form.last_name.toUpperCase();
    if (form.first_name) form.first_name = form.first_name.toUpperCase();
    if (form.middle_name) form.middle_name = form.middle_name.toUpperCase();
    if (form.extension_name) form.extension_name = form.extension_name.toUpperCase();
    if (form.father_name) form.father_name = form.father_name.toUpperCase();
    if (form.mother_name) form.mother_name = form.mother_name.toUpperCase();
    if (form.guardian_name) form.guardian_name = form.guardian_name.toUpperCase();
    if (form.address) form.address = form.address.toUpperCase();
    if (form.temporary_address) form.temporary_address = form.temporary_address.toUpperCase();

    // Handle municipality and barangay extraction from objects
    if (typeof form.municipality === 'object' && form.municipality?.name) {
        form.municipality = form.municipality.name.toUpperCase();
    } else if (typeof form.municipality === 'string' && form.municipality) {
        form.municipality = form.municipality.toUpperCase();
    }

    if (typeof form.barangay === 'object' && form.barangay?.name) {
        form.barangay = form.barangay.name.toUpperCase();
    } else if (typeof form.barangay === 'string' && form.barangay) {
        form.barangay = form.barangay.toUpperCase();
    }

    // Handle temporary municipality and barangay
    if (typeof form.temporary_municipality === 'object' && form.temporary_municipality?.name) {
        form.temporary_municipality = form.temporary_municipality.name.toUpperCase();
    } else if (typeof form.temporary_municipality === 'string' && form.temporary_municipality) {
        form.temporary_municipality = form.temporary_municipality.toUpperCase();
    }

    if (typeof form.temporary_barangay === 'object' && form.temporary_barangay?.name) {
        form.temporary_barangay = form.temporary_barangay.name.toUpperCase();
    } else if (typeof form.temporary_barangay === 'string' && form.temporary_barangay) {
        form.temporary_barangay = form.temporary_barangay.toUpperCase();
    }

    // Handle place_of_birth if it's an object
    if (typeof form.place_of_birth === 'object' && form.place_of_birth?.name) {
        form.place_of_birth = form.place_of_birth.name.toUpperCase();
    } else if (typeof form.place_of_birth === 'string' && form.place_of_birth) {
        form.place_of_birth = form.place_of_birth.toUpperCase();
    }

    // Handle empty academic info - only set if values exist
    form.year_level = form.year_level?.value || null;
    form.school = form.school?.name || null;
    form.academic_year = form.academic_year?.value || null;
    form.term = form.term?.value || null;
    form.course = form.course?.shortname || null;

    // Debug: log form data before submission
    console.log('Form data before defaults reset:', {
        father_name: form.father_name,
        mother_name: form.mother_name,
        guardian_name: form.guardian_name,
        guardian_relationship: form.guardian_relationship,
        guardian_contact_no: form.guardian_contact_no,
        guardian_occupation: form.guardian_occupation,
        parents_guardian_gross_monthly_income: form.parents_guardian_gross_monthly_income,
        father_occupation: form.father_occupation,
        mother_occupation: form.mother_occupation,
        father_contact_no: form.father_contact_no,
        mother_contact_no: form.mother_contact_no
    });

    if (props.action == 'create') {
        // Explicitly build the data object with all fields
        const submitData = {
            scholarship_grant_id: form.scholarship_grant_id,
            program: form.program,
            school: form.school,
            course: form.course,
            year_level: form.year_level,
            term: form.term,
            academic_year: form.academic_year,
            first_name: form.first_name,
            last_name: form.last_name,
            middle_name: form.middle_name,
            extension_name: form.extension_name,
            father_name: form.father_name,
            father_occupation: form.father_occupation,
            father_contact_no: form.father_contact_no,
            father_birthdate: form.father_birthdate,
            mother_name: form.mother_name,
            mother_occupation: form.mother_occupation,
            mother_contact_no: form.mother_contact_no,
            mother_birthdate: form.mother_birthdate,
            guardian_name: form.guardian_name,
            guardian_relationship: form.guardian_relationship,
            guardian_contact_no: form.guardian_contact_no,
            guardian_occupation: form.guardian_occupation,
            parents_guardian_gross_monthly_income: form.parents_guardian_gross_monthly_income,
            municipality: form.municipality,
            barangay: form.barangay,
            address: form.address,
            temporary_municipality: form.temporary_municipality,
            temporary_barangay: form.temporary_barangay,
            temporary_address: form.temporary_address,
            contact_no: form.contact_no,
            contact_no_2: form.contact_no_2,
            email: form.email,
            date_of_birth: form.date_of_birth,
            gender: form.gender,
            place_of_birth: form.place_of_birth,
            civil_status: form.civil_status,
            religion: form.religion,
            indigenous_group: form.indigenous_group,
            remarks: form.remarks,
            date_filed: form.date_filed,
        };

        console.log('Final data being sent to backend (CREATE):', submitData);

        // Use axios directly to ensure all fields are sent
        axios.post(route("waitinglist.store"), submitData)
            .then((response) => {
                form.reset();
                toast.success("Application has been submitted", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                setTimeout(() => {
                    router.visit(route('waitinglist.index', { page: props.page }), { preserveState: true, preserveScroll: true });
                }, 1200);
            })
            .catch((err) => {
                console.error('Error submitting application:', err);
                if (err.response?.status === 422) {
                    form.errors = err.response.data.errors || {};
                } else {
                    form.errors = { form: err.response?.data?.message || 'An error occurred' };
                }
            });
    }
    if (props.action == 'update' || props.action == 'add-existing') {
        const profile_id = form.selectedProfile.profile_id || props.profile.profile_id;

        // Explicitly build the data object with all fields to ensure nothing is missed
        const submitData = {
            scholarship_grant_id: form.scholarship_grant_id,
            program: form.program,
            school: form.school,
            course: form.course,
            year_level: form.year_level,
            term: form.term,
            academic_year: form.academic_year,
            first_name: form.first_name,
            last_name: form.last_name,
            middle_name: form.middle_name,
            extension_name: form.extension_name,
            father_name: form.father_name,
            father_occupation: form.father_occupation,
            father_contact_no: form.father_contact_no,
            father_birthdate: form.father_birthdate,
            mother_name: form.mother_name,
            mother_occupation: form.mother_occupation,
            mother_contact_no: form.mother_contact_no,
            mother_birthdate: form.mother_birthdate,
            guardian_name: form.guardian_name,
            guardian_relationship: form.guardian_relationship,
            guardian_contact_no: form.guardian_contact_no,
            guardian_occupation: form.guardian_occupation,
            parents_guardian_gross_monthly_income: form.parents_guardian_gross_monthly_income,
            municipality: form.municipality,
            barangay: form.barangay,
            address: form.address,
            temporary_municipality: form.temporary_municipality,
            temporary_barangay: form.temporary_barangay,
            temporary_address: form.temporary_address,
            contact_no: form.contact_no,
            contact_no_2: form.contact_no_2,
            email: form.email,
            date_of_birth: form.date_of_birth,
            gender: form.gender,
            place_of_birth: form.place_of_birth,
            civil_status: form.civil_status,
            religion: form.religion,
            indigenous_group: form.indigenous_group,
            remarks: form.remarks,
            date_filed: form.date_filed,
        };

        console.log('Final data being sent to backend:', submitData);

        // Use axios directly to send all fields
        axios.put(route("waitinglist.update", profile_id), submitData)
            .then((response) => {
                toast.success("Profile has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                setTimeout(() => {
                    if (props.isInlineModal) {
                        emit('close');
                        router.reload();
                    } else {
                        router.visit(route('waitinglist.index', { id: profile_id, action: props.action }), { preserveState: true });
                        if (closeAfter) router.visit(JSON.parse(JSON.stringify(prevPage.value)));
                    }
                }, 1200);
            })
            .catch((err) => {
                console.error('Error updating profile:', err);
                if (err.response?.status === 422) {
                    form.errors = err.response.data.errors || {};
                } else {
                    form.errors = { form: err.response?.data?.message || 'An error occurred' };
                }
            });
    }
};

// Stepper logic, using PrimeVue Stepper component
// set default step to 1
const activeStep = ref('1');

// Map error fields to step panels
const errorFieldToStep = {
    // Step 1 fields
    last_name: '1', first_name: '1', middle_name: '1', extension_name: '1', contact_no: '1', email: '1', gender: '1', address: '1', municipality: '1', barangay: '1', father_name: '1', father_contact_no: '1', mother_name: '1', mother_contact_no: '1',
    // Step 2 fields
    school: '2', course: '2', year_level: '2', term: '2', academic_year: '2',
    // Step 3 fields
    remarks: '3', date_filed: '3',
};

// Watch for form errors and set activeStep to the first error's step
watch(() => form.errors, (errors) => {
    if (errors && Object.keys(errors).length > 0) {
        const firstErrorField = Object.keys(errors)[0];
        const step = errorFieldToStep[firstErrorField] || '1';
        activeStep.value = step;
        Object.values(errors).forEach(msg => {
            if (msg) toast.error(msg, { position: toast.POSITION.TOP_RIGHT });
        });
    }
}, { deep: true });

// Optionally, show a toast for other errorMessage
watch(() => errorMessage, (msg) => {
    if (msg) {
        toast.error(msg, { position: toast.POSITION.TOP_RIGHT });
    }
});

</script>
