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
                                <Link class="-mr-2 " :href="prevPage">
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
                                                <div class="flex gap-3 mb-2 items-end">
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
                                                        <VueMultiselect v-model="form.selected_municipality"
                                                            :options="municipalitiesOptions" :close-on-select="true"
                                                            :show-labels="false" placeholder="Select Municipality"
                                                            label="name" @select="resetBarangay" track-by="name"
                                                            class="mt-1" />
                                                        <InputError class="mt-1" :message="form.errors.municipality" />
                                                    </div>
                                                    <div>
                                                        <InputLabel class="mb-1" for="barangay" value="Barangay" />
                                                        <VueMultiselect v-model="form.selected_barangay"
                                                            :options="barangayOptions" :close-on-select="true"
                                                            :show-labels="false" placeholder="Select Barangay"
                                                            label="name" track-by="name" class="mt-1" />
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
                                                    @click="activeStep = '3'" :disabled="hasPendingOrOngoing" /><Button
                                                    label="Next" severity="secondary" icon="pi pi-arrow-right"
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
                                                <Button label="Next" severity="secondary" icon="pi pi-arrow-right"
                                                    @click="activeStep = '3'" :disabled="hasPendingOrOngoing" />
                                            </div>
                                        </StepPanel>
                                        <StepPanel v-slot="{ activateCallback }" value="3">
                                            <div class="bg-white rounded-lg shadow p-4 border border-gray-200 mb-4">
                                                <h2
                                                    class="text-base font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                    <span
                                                        class="inline-block w-2 h-5 bg-indigo-500 rounded mr-2"></span>Academic
                                                    Information
                                                </h2>
                                                <div class="grid grid-cols-2 gap-3 mb-2">

                                                    <div class="w-full">
                                                        <InputLabel class="mb-1" for="school" value="School" />
                                                        <!-- <TextInput id="school" type="text" class="w-full block"
                                                            custom-class="uppercase"
                                                            v-model="form.applied_school" /> -->
                                                        <SchoolSelect v-model="form.school" label="name" />
                                                        <InputError class="mt-2" :message="form.errors.school"
                                                            v-if="!form.applied_school" />
                                                    </div>

                                                    <div class="w-full">
                                                        <InputLabel class="mb-1" for="course" value="Course" />

                                                        <CourseSelect v-model="form.course" :scholarship-program-id="''"
                                                            label="shortname" />
                                                        <InputError class="mt-2" :message="form.errors.course"
                                                            v-if="!form.course" />
                                                    </div>
                                                    <div class="w-full">
                                                        <InputLabel for="yearlevel" class="mb-1"
                                                            value="Year/Grade Level" />

                                                        <YearLevelSelect v-model="form.year_level" label="label" />

                                                        <InputError class="mt-2" :message="form.errors.year_level" />
                                                    </div>
                                                    <div class="w-full">
                                                        <InputLabel for="term" class="mb-1" value="Term" />

                                                        <TermSelect v-model="form.term" label="label" />

                                                        <InputError class="mt-2" :message="form.errors.term" />
                                                    </div>
                                                    <div class="w-full">
                                                        <InputLabel for="acadedmic_year" class="mb-1"
                                                            value="Academic Year" />

                                                        <AcademicYearSelect v-model="form.academic_year"
                                                            label="label" />

                                                        <InputError class="mt-2" :message="form.errors.academic_year" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-end gap-3 mt-4">
                                                <div class="flex-1">
                                                    <InputLabel class="mb-1" for="remarks" value="Remarks" />
                                                    <TextInput id="remarks" type="text" class="w-full block uppercase"
                                                        v-model="form.remarks" />
                                                    <InputError class="mt-1" :message="form.errors.remarks" />
                                                </div>
                                                <div style="min-width:110px;max-width:140px;">
                                                    <InputLabel for="date_approved" value="Date Approved" />

                                                    <TextInput id="date_approved" type="date" class="mt-1 block w-full"
                                                        v-model="form.date_approved" autocomplete="date_approved" />

                                                    <InputError class="mt-1" :message="form.errors.date_approved" />
                                                </div>
                                            </div>
                                            <div class="mt-12 flex justify-between">
                                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                                    @click="activeStep = '2'" />
                                                <Button label="Save" icon="pi pi-save" iconPos="right"
                                                    @click.prevent="submit" severity="success" />
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

import { ref, computed, watch, onMounted } from "vue";
import { Link, useForm, router, usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import VueMultiselect from "vue-multiselect";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import municipalities from '@/Data/municipalities.json';

// COURSE MULTISELECT COMPONENT
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from "@/Components/selects/YearLevelSelect.vue";
import TermSelect from "@/Components/selects/TermSelect.vue";
import ProfileSelect from "@/Components/selects/ProfileSelect.vue";
import AcademicYearSelect from "@/Components/selects/AcademicYearSelect.vue";
import SchoolSelect from "@/Components/selects/SchoolSelect.vue";

const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object,
    page: [Number, String],
});

const mncplt = ref(municipalities.municipalities);
const municipalitiesOptions = ref(mncplt.value.map(m => ({
    label: m.name,
    name: m.name,
    value: m.name,
})));

const barangayOptions = ref([]);

const isOpen = computed(() => props.action == 'create' || props.action == 'update' || props.action == 'add-existing');


// console.log(props.profile?.scholarship_grant[0].year_level);
const form = useForm({
    scholarship_grant_id: props.profile?.scholarship_grant[0]?.id || null,
    school: props.profile?.scholarship_grant[0]?.school?.name || "",
    course: props.profile?.scholarship_grant[0]?.course?.name || "",
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
    selected_municipality: { name: props.profile?.municipality || "" } || "",
    municipality: props.profile?.municipality || "",
    barangay: props.profile?.barangay || "",
    selected_barangay: { name: props.profile?.barangay || "" } || "",
    address: props.profile?.address || "",
    contact_no: props.profile?.contact_no || "",
    email: props.profile?.email || "",
    gender: props.profile?.gender || "",
    remarks: props.profile?.remarks || "",
    is_on_waiting_list: true,
    date_approved: props.profile?.scholarship_grant[0]?.date_approved || "",
    selectedProfile: "",
    contact_no_2: props.profile?.contact_no_2 || "",
    guardian_name: props.profile?.guardian_name || "",
    guardian_relationship: props.profile?.guardian_relationship || "",
    guardian_contact_no: props.profile?.guardian_contact_no || "",
    guardian_occupation: props.profile?.guardian_occupation || "",
    parents_guardian_gross_monthly_income: props.profile?.parents_guardian_gross_monthly_income || "",
});


watch(form, (newValue) => {
    if (newValue.selected_municipality.value) {
        barangayOptions.value = mncplt.value.find(m => m.name == newValue.selected_municipality.value).barangays.map(b => ({
            name: b,
            label: b,
            value: b
        }));
    } else {

        barangayOptions.value = [];
    }
    // console.log(form.selectedProfile)
}, { deep: true });




const hasPendingOrOngoing = computed(() => {
    const profile = form.selectedProfile?.profile;
    if (profile && profile.scholarship_grant && profile.scholarship_grant.length > 0) {
        return profile.scholarship_grant.some(r => r.scholarship_status === 0 || r.scholarship_status === 1);
    }
    return false;
});
const errorMessage = ref("");

const selectGender = (gender) => {
    form.gender = gender;
};

const resetBarangay = () => {
    form.selected_barangay = "";
};

const page = usePage();
const prevPage = ref(page.props.urlPrev);

const submit = (closeAfter = false) => {
    // console.log(form.applied_school);
    // return;
    form.last_name = form.last_name.toUpperCase();
    form.first_name = form.first_name.toUpperCase();
    form.middle_name = form.middle_name.toUpperCase();
    form.extension_name = form.extension_name.toUpperCase();
    form.municipality = form.selected_municipality.name.toUpperCase();
    form.barangay = form.selected_barangay?.name ? form.selected_barangay?.name.toUpperCase() : '';
    form.father_name = form.father_name.toUpperCase();
    form.mother_name = form.mother_name.toUpperCase();
    form.year_level = form.year_level.value;
    form.school = form.school.name;
    form.academic_year = form.academic_year.value;
    form.term = form.term.value;
    form.course = form.course.shortname;

    if (props.action == 'create') {
        form.is_on_waiting_list = 1;
        form.post(route("profile.storeapplicant"), {
            onSuccess: (response) => {
                form.reset();
                toast.success("Application has been submitted", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                setTimeout(() => {
                    router.visit(route('profile.waitinglist', { page: props.page }), { preserveState: true, preserveScroll: true });
                }, 1200);
            },
            onError: (err) => {
                form.errors = err;
            }
        });
    }
    if (props.action == 'update') {
        const profile_id = form.selectedProfile.profile_id || props.profile.profile_id;
        form.put(route("profile.updateapplicant", profile_id), {
            onSuccess: (response) => {
                toast.success("Profile has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                setTimeout(() => {
                    router.visit(route('profile.waitinglist', { id: profile_id, action: props.action }), { preserveState: true });
                    if (closeAfter) router.visit(JSON.parse(JSON.stringify(prevPage.value)));
                }, 1200);
            },
            onError: (err) => {
                form.errors = err;
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
    remarks: '3', date_approved: '3',
};

// Watch for form errors and set activeStep to the first error's step
watch(() => form.errors, (errors) => {
    if (errors && Object.keys(errors).length > 0) {
        const firstErrorField = Object.keys(errors)[0];
        const step = errorFieldToStep[firstErrorField] || '1';
        activeStep.value = step;
    }
}, { deep: true });

// Show toast for form errors
watch(() => form.errors, (errors) => {
    if (errors && Object.keys(errors).length > 0) {
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
