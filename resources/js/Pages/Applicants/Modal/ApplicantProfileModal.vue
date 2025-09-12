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
                                        <Step value="2">Academic Information</Step>
                                        <Step value="3">Remarks</Step>
                                    </StepList>
                                    <StepPanels>
                                        <StepPanel v-slot="{ activateCallback }" value="1">
                                            <div class="flex flex-col min-h-48">
                                                <div
                                                    class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 p-8 font-medium">
                                                    <div v-if="action == 'add-existing'">
                                                        <div class="w-full mb-2">
                                                            <InputLabel for="profile" value="Search Profile"
                                                                class="mb-1" />
                                                            <ProfileSelect v-model="form.selectedProfile" />
                                                            <div v-if="errorMessage" class="text-red-500 text-sm mt-2">
                                                                {{ errorMessage }}</div>
                                                        </div>
                                                        ``
                                                    </div>
                                                    <div v-if="action === 'create' || action === 'update'">
                                                        <div class="mt-4 flex justify-between gap-4">

                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="lastname"
                                                                    value="Last Name" />

                                                                <TextInput autofocus id="lastname" type="text"
                                                                    class="w-full block" custom-class="uppercase"
                                                                    v-model="form.last_name" :required="true" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.last_name" />
                                                            </div>
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="firstname"
                                                                    value="First Name" />

                                                                <TextInput id="firstname" type="text"
                                                                    class="w-full block" custom-class="uppercase"
                                                                    v-model="form.first_name" :required="true" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.first_name" />
                                                            </div>
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="middlename"
                                                                    value="Middle Name" />

                                                                <TextInput id="middlename" type="text"
                                                                    class="w-full block" custom-class="uppercase"
                                                                    v-model="form.middle_name" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.middle_name" />
                                                            </div>
                                                            <div class="w-1/18">
                                                                <InputLabel class="mb-1" for="extension"
                                                                    value="Extension" />

                                                                <TextInput id="extension" type="text"
                                                                    class="w-full block" custom-class="uppercase"
                                                                    v-model="form.extension_name" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.extension_name" />
                                                            </div>
                                                        </div>
                                                        <div class="mt-4 flex justify-start gap-4">
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="contact"
                                                                    value="Contact No." />
                                                                <TextInput id="contact" type="text" class="w-full block"
                                                                    custom-class="uppercase"
                                                                    v-model="form.contact_no" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.contact_no" />
                                                            </div>
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="email" value="Email" />
                                                                <TextInput id="email" type="email" class="w-full block"
                                                                    v-model="form.email" />

                                                                <InputError class="mt-2" :message="form.errors.email" />
                                                            </div>
                                                            <div class="w-1/6">
                                                                <InputLabel class="mb-1" for="gender" value="Gender" />

                                                                <div class="flex gap-4 mt-4 ml-4">
                                                                    <div class="flex items-center mb-4 cursor-pointer"
                                                                        @click="selectGender('M')">
                                                                        <input v-model="form.gender" type="radio"
                                                                            name="gender" value="M"
                                                                            class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                                            aria-labelledby="country-option-1"
                                                                            aria-describedby="country-option-1" />
                                                                        <label for="country-option-1"
                                                                            class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                                            Male
                                                                        </label>
                                                                    </div>

                                                                    <div class="flex items-center mb-4"
                                                                        @click="selectGender('F')">
                                                                        <input v-model="form.gender" type="radio"
                                                                            name="gender" value="F"
                                                                            class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                                            aria-labelledby="country-option-2"
                                                                            aria-describedby="country-option-2" />
                                                                        <label for="country-option-2"
                                                                            class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                                            Female
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <InputError class="mt-2"
                                                                    :message="form.errors.gender" />
                                                            </div>

                                                        </div>
                                                        <hr class="mt-8 mb-4" />
                                                        <div class="flex justify-between gap-4 mt-8">
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="street"
                                                                    value="Purok/Street/Landmark" />
                                                                <TextInput id="street" type="text" class="w-full block"
                                                                    custom-class="uppercase" v-model="form.address" />
                                                                <InputError class="mt-2" :message="form.errors.address"
                                                                    v-if="!form.barangay" />
                                                            </div>
                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="municipality"
                                                                    value="Municipality" />
                                                                <VueMultiselect v-model="form.selected_municipality"
                                                                    :options="municipalitiesOptions"
                                                                    :close-on-select="true" :show-labels="false"
                                                                    placeholder="Select Municipality" label="name"
                                                                    @select="resetBarangay" track-by="name"
                                                                    class="mt-1" />
                                                                <InputError class="mt-2"
                                                                    :message="form.errors.municipality"
                                                                    v-if="!form.municipality" />
                                                            </div>

                                                            <div class="w-1/3">
                                                                <InputLabel class="mb-1" for="barangay"
                                                                    value="Barangay" />
                                                                <VueMultiselect v-model="form.selected_barangay"
                                                                    :options="barangayOptions" :close-on-select="true"
                                                                    :show-labels="false" placeholder="Select Barangay"
                                                                    label="name" track-by="name" class="mt-1" />
                                                                <InputError class="mt-2" :message="form.errors.barangay"
                                                                    v-if="!form.barangay" />
                                                            </div>

                                                        </div>
                                                        <hr class="mt-8 mb-4" />
                                                        <div class="w-full mt-4">
                                                            <h3 class="text-gray-600">Parent's Information</h3>

                                                            <div class="mt-4 flex gap-4">

                                                                <div class="w-1/3">
                                                                    <InputLabel class="mb-1" for="father_name"
                                                                        value="Father" />

                                                                    <TextInput id="father_name" type="text"
                                                                        class="w-full block" custom-class="uppercase"
                                                                        v-model="form.father_name" />

                                                                    <InputError class="mt-2"
                                                                        :message="form.errors.father_name" />
                                                                </div>

                                                                <div class="w-1/3">
                                                                    <InputLabel class="mb-1" for="father_contact_no"
                                                                        value="Contact #" />

                                                                    <TextInput id="father_contact_no" type="text"
                                                                        class="w-full block" custom-class="uppercase"
                                                                        v-model="form.father_contact_no" />

                                                                    <InputError class="mt-2"
                                                                        :message="form.errors.father_contact_no" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-full mt-4">
                                                            <div class="mt-2 flex gap-4">

                                                                <div class="w-1/3">
                                                                    <InputLabel class="mb-1" for="mother_name"
                                                                        value="Mother" />

                                                                    <TextInput id="mother_name" type="text"
                                                                        class="w-full block" custom-class="uppercase"
                                                                        v-model="form.mother_name" />

                                                                    <InputError class="mt-2"
                                                                        :message="form.errors.mother_name" />
                                                                </div>

                                                                <div class="w-1/3">
                                                                    <InputLabel class="mb-1" for="mother_contact_no"
                                                                        value="Contact #" />

                                                                    <TextInput id="mother_contact_no" type="text"
                                                                        class="w-full block" custom-class="uppercase"
                                                                        v-model="form.mother_contact_no" />

                                                                    <InputError class="mt-2"
                                                                        :message="form.errors.mother_contact_no" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex pt-6 justify-between">
                                                <Button label="Quick Save" icon="pi pi-save" iconPos="right"
                                                    v-if="props.action == 'update'" @click.prevent="submit(true)"
                                                    severity="info" />
                                                <Button label="Next" severity="secondary" icon="pi pi-arrow-right"
                                                    @click="activeStep = '2'" :disabled="hasPendingOrOngoing" />
                                            </div>
                                        </StepPanel>

                                        <StepPanel v-slot="{ activateCallback }" value="2">
                                            <div class="flex flex-col min-h-48">
                                                <div
                                                    class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 p-8 font-medium">
                                                    <div class="w-full mt-2">

                                                        <div class="flex flex-col mx-auto gap-4 mt-2 w-1/2">
                                                            <div class="w-full">
                                                                <InputLabel class="mb-1" for="school" value="School" />
                                                                <!-- <TextInput id="school" type="text" class="w-full block"
                                                                    custom-class="uppercase"
                                                                    v-model="form.applied_school" /> -->
                                                                <SchoolSelect v-model="form.applied_school"
                                                                    label="name" />
                                                                <InputError class="mt-2"
                                                                    :message="form.errors.applied_school"
                                                                    v-if="!form.applied_school" />
                                                            </div>

                                                            <div class="w-full">
                                                                <InputLabel class="mb-1" for="course" value="Course" />

                                                                <CourseSelect v-model="form.applied_course"
                                                                    :scholarship-program-id="''" label="shortname" />
                                                                <InputError class="mt-2"
                                                                    :message="form.errors.applied_course"
                                                                    v-if="!form.applied_course" />
                                                            </div>
                                                            <div class="w-full">
                                                                <InputLabel for="yearlevel" class="mb-1"
                                                                    value="Year/Grade Level" />

                                                                <YearLevelSelect v-model="form.applied_year_level"
                                                                    label="label" />

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.applied_year_level" />
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

                                                                <InputError class="mt-2"
                                                                    :message="form.errors.academic_year" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex pt-6 justify-between">
                                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                                    @click="activeStep = '1'" />
                                                <Button label="Quick Save" icon="pi pi-save" iconPos="right"
                                                    v-if="props.action == 'update'" @click.prevent="submit(true)"
                                                    severity="info" />
                                                <Button label="Next" severity="secondary" icon="pi pi-arrow-right"
                                                    iconPos="right" @click="activeStep = '3'"
                                                    :disabled="hasPendingOrOngoing" />
                                            </div>
                                        </StepPanel>
                                        <StepPanel v-slot="{ activateCallback }" value="3">
                                            <div class="flex flex-col min-h-48">
                                                <div
                                                    class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex flex-col font-medium p-8">

                                                    <div class="flex gap-4">
                                                        <div class="w-3/4">
                                                            <InputLabel class="mb-1" for="street" value="Remarks" />
                                                            <TextInput id="street" type="text" class="w-full block"
                                                                custom-class="uppercase" v-model="form.remarks" />

                                                            <InputError class="mt-2" :message="form.errors.remarks"
                                                                v-if="!form.remarks" />
                                                        </div>
                                                        <div class="w-1/4">
                                                            <InputLabel for="end_date" value="Date Filed" />

                                                            <TextInput id="date_filed" type="date"
                                                                class="mt-1 block w-full" v-model="form.date_filed"
                                                                autocomplete="date_filed" />

                                                            <InputError class="mt-2"
                                                                :message="form.errors.date_filed" />
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                            <div class="pt-6 flex justify-between">
                                                <Button label="Back" severity="secondary" icon="pi pi-arrow-left"
                                                    @click="activateCallback('2')" />
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


// console.log(props.profile?.scholarship_grant[0]);
const form = useForm({
    scholarship_grant_id: props.profile?.scholarship_grant[0]?.id || null,
    applied_school: props.profile?.applied_school || "",
    applied_course: props.profile?.applied_course || props.profile?.scholarship_grant[0]?.course?.name || "",
    applied_year_level: props.profile?.applied_year_level || "",
    term: props.profile?.scholarship_grant[0]?.term || "",
    academic_year: props.profile?.scholarship_grant[0]?.academic_year || "",
    first_name: props.profile?.first_name || "",
    last_name: props.profile?.last_name || "",
    middle_name: props.profile?.middle_name || "",
    extension_name: props.profile?.extension_name || "",
    father_name: props.profile?.father_name || "",
    father_contact_no: props.profile?.father_contact_no || "",
    mother_name: props.profile?.mother_name || "",
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
    date_filed: props.profile?.date_filed || "",
    selectedProfile: ""
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

watch(() => form.selectedProfile, (profile) => {
    if (hasPendingOrOngoing.value) {
        // console.log('has pending or ongoing scholarship application');
        errorMessage.value = "This profile has a pending or ongoing scholarship application.";
    } else {
        errorMessage.value = "";
    }

    if (profile && profile.profile && !hasPendingOrOngoing.value) {
        form.applied_school = profile.profile.applied_school || '';
        form.applied_course = profile.profile.applied_course || '';
        form.applied_year_level = profile.profile.applied_year_level || '';
        form.first_name = profile.profile.first_name || '';
        form.last_name = profile.profile.last_name || '';
        form.middle_name = profile.profile.middle_name || '';
        form.extension_name = profile.profile.extension_name || '';
        form.father_name = profile.profile.father_name || '';
        form.father_contact_no = profile.profile.father_contact_no || '';
        form.mother_name = profile.profile.mother_name || '';
        form.mother_contact_no = profile.profile.mother_contact_no || '';
        form.municipality = profile.profile.municipality || '';
        form.selected_municipality = { name: profile.profile.municipality || '' };
        form.barangay = profile.profile.barangay || '';
        form.selected_barangay = { name: profile.profile.barangay || '' };
        form.address = profile.profile.address || '';
        form.contact_no = profile.profile.contact_no || '';
        form.email = profile.profile.email || '';
        form.gender = profile.profile.gender || '';
        form.remarks = profile.profile.remarks || '';
        form.date_filed = profile.profile.date_filed || '';
    }
});


const selectGender = (gender) => {
    form.gender = gender;
};

const resetBarangay = () => {
    form.selected_barangay = "";
};
const resetTempBarangay = () => {
    form.selected_temporary_barangay = "";
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
    form.applied_year_level = form.applied_year_level.value;
    form.applied_school = form.applied_school.name;
    form.academic_year = form.academic_year.value;
    form.term = form.term.value;
    form.applied_course = form.applied_course.shortname;

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
    if (props.action == 'update' || props.action == 'add-existing') {
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
    applied_school: '2', applied_course: '2', applied_year_level: '2', term: '2', academic_year: '2',
    // Step 3 fields
    remarks: '3', date_filed: '3',
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
