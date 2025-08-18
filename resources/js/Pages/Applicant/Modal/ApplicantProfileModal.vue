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
                            class="w-full max-w-7xl transform overflow-hidden rounded  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-xl font-medium leading-6 bg-[#222831] text-white flex justify-between p-4">
                                <span v-if="action == 'create'">New Profile Form</span>
                                <span v-if="action == 'update'">Update Profile Form</span>
                                <Link class="-mr-2 " :href="route('applicants.index')" v-if="action == 'create'">
                                <XMarkIcon class="h-8 w-8 text-red-500" />
                                </Link>
                                <Link class="-mr-2 "
                                    :href="route('applicants.index', { id: applicant.id, action: 'view' })"
                                    v-if="action == 'update'">
                                <XMarkIcon class="h-8 w-8 text-red-500" />
                                </Link>

                            </DialogTitle>
                            <div class="p-6 bg-white">
                                <form @submit.prevent="submit">

                                    <div class="w-full text-lg uppercase  text-gray-600 underline underline-offset-2">
                                        Personal Information
                                    </div>
                                    <div class="mt-4 flex justify-between gap-4">

                                        <div class="w-1/3">
                                            <InputLabel for="lastname" value="Last Name" />

                                            <TextInput autofocus id="lastname" type="text"
                                                class="mt-1 block w-full uppercase" v-model="form.last_name" />

                                            <InputError class="mt-2" :message="form.errors.last_name" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="firstname" value="First Name" />

                                            <TextInput id="firstname" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.first_name" />

                                            <InputError class="mt-2" :message="form.errors.first_name" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="middlename" value="Middle Name" />

                                            <TextInput id="middlename" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.middle_name" />

                                            <InputError class="mt-2" :message="form.errors.middle_name" />
                                        </div>
                                        <div class="w-1/18">
                                            <InputLabel for="extension" value="Extension" />

                                            <TextInput id="extension" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.extension_name" />

                                            <InputError class="mt-2" :message="form.errors.extension_name" />
                                        </div>
                                    </div>
                                    <div class="mt-6 flex justify-start gap-8">

                                        <div class="w-1/6">
                                            <InputLabel for="birthdate" value="Date of Birth" />

                                            <TextInput id="birthdate" type="date" class="mt-1 block w-full uppercase"
                                                v-model="form.birthdate" />

                                            <InputError class="mt-2" :message="form.errors.birthdate" />
                                        </div>
                                        <div class="w-1/6">
                                            <InputLabel for="civil_status" value="Civil Status" />
                                            <TextInput id="civil_status" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.civil_status" />

                                            <InputError class="mt-2" :message="form.errors.civil_status" />
                                        </div>
                                        <div class="w-1/6">
                                            <InputLabel for="religion" value="Religion" />
                                            <TextInput id="religion" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.religion" />

                                            <InputError class="mt-2" :message="form.errors.religion" />
                                        </div>
                                        <div class="w-1/6">
                                            <InputLabel for="contact" value="Contact No." />
                                            <TextInput id="contact" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.contact_no" />

                                            <InputError class="mt-2" :message="form.errors.contact_no" />
                                        </div>
                                        <div class="w-1/6">
                                            <InputLabel for="email" value="Email" />
                                            <TextInput id="email" type="email" class="mt-1 block w-full"
                                                v-model="form.email" />

                                            <InputError class="mt-2" :message="form.errors.email" />
                                        </div>
                                        <div class="w-1/6">
                                            <InputLabel for="gender" value="Gender" />

                                            <div class="flex gap-4 mt-4 ml-4">
                                                <div class="flex items-center mb-4 cursor-pointer"
                                                    @click="selectGender('M')">
                                                    <input v-model="form.gender" type="radio" name="gender" value="M"
                                                        class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                        aria-labelledby="country-option-1"
                                                        aria-describedby="country-option-1" />
                                                    <label for="country-option-1"
                                                        class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                        Male
                                                    </label>
                                                </div>

                                                <div class="flex items-center mb-4" @click="selectGender('F')">
                                                    <input v-model="form.gender" type="radio" name="gender" value="F"
                                                        class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                        aria-labelledby="country-option-2"
                                                        aria-describedby="country-option-2" />
                                                    <label for="country-option-2"
                                                        class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                        Female
                                                    </label>
                                                </div>
                                            </div>
                                            <InputError class="mt-2" :message="form.errors.gender" />
                                        </div>

                                    </div>

                                    <div class="w-full mt-6">
                                        <h3 class="text-gray-600">Permanent Address</h3>
                                        <div class="flex justify-between gap-4 mt-2">
                                            <div class="w-1/3">
                                                <InputLabel for="municipality" value="Municipality" />
                                                <VueMultiselect v-model="form.selected_municipality"
                                                    :options="municipalitiesOptions" :close-on-select="true"
                                                    placeholder="Select Municipality" label="name"
                                                    @select="resetBarangay" track-by="name" class="mt-1" />
                                                <InputError class="mt-2" :message="form.errors.municipality"
                                                    v-if="!form.municipality" />
                                            </div>

                                            <div class="w-1/3">
                                                <InputLabel for="barangay" value="Barangay" />
                                                <VueMultiselect v-model="form.selected_barangay"
                                                    :options="barangayOptions" :close-on-select="true"
                                                    placeholder="Select Barangay" label="name" track-by="name"
                                                    class="mt-1" />
                                                <InputError class="mt-2" :message="form.errors.barangay"
                                                    v-if="!form.barangay" />
                                            </div>
                                            <div class="w-1/3">
                                                <InputLabel for="street" value="Purok/Street/Landmark" />
                                                <TextInput id="street" type="text" class="mt-1 block w-full uppercase"
                                                    v-model="form.address" />

                                                <InputError class="mt-2" :message="form.errors.address"
                                                    v-if="!form.barangay" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-full mt-6">
                                        <h3 class="text-gray-600">Current/Temporary Address</h3>
                                        <div class="flex justify-between gap-4 mt-2">
                                            <div class="w-1/3">
                                                <InputLabel for="municipality" value="Municipality" />
                                                <VueMultiselect v-model="form.selected_temporary_municipality"
                                                    :options="municipalitiesOptions" :close-on-select="true"
                                                    placeholder="Select Municipality" label="name"
                                                    @select="resetTempBarangay" track-by="name" class="mt-1" />
                                                <InputError class="mt-2" :message="form.errors.temporary_municipality"
                                                    v-if="!form.municipality" />
                                            </div>

                                            <div class="w-1/3">
                                                <InputLabel for="barangay" value="Barangay" />
                                                <VueMultiselect v-model="form.selected_temporary_barangay"
                                                    :options="tempBarangayOptions" :close-on-select="true"
                                                    placeholder="Select Barangay" label="name" track-by="name"
                                                    class="mt-1" />
                                                <InputError class="mt-2" :message="form.errors.temporary_barangay"
                                                    v-if="!form.temporary_barangay" />
                                            </div>
                                            <div class="w-1/3">
                                                <InputLabel for="street" value="Purok/Street/Landmark" />
                                                <TextInput id="street" type="text" class="mt-1 block w-full uppercase"
                                                    v-model="form.temporary_address" />

                                                <InputError class="mt-2" :message="form.errors.temporary_address"
                                                    v-if="!form.temporary_address" />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="w-full mt-8 text-lg uppercase  text-gray-600 underline underline-offset-2">
                                        Parent's Information
                                    </div>
                                    <div class="w-full mt-4">
                                        <h3 class="text-gray-600">Father's Info</h3>

                                        <div class="mt-2 flex justify-between gap-4">

                                            <div class="w-1/4">
                                                <InputLabel for="father_name" value="Name" />

                                                <TextInput id="father_name" type="text"
                                                    class="mt-1 block w-full uppercase" v-model="form.father_name" />

                                                <InputError class="mt-2" :message="form.errors.father_name" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="father_birthdate" value="Birthdate" />

                                                <TextInput id="father_birthdate" type="date"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.father_birthdate" />

                                                <InputError class="mt-2" :message="form.errors.father_birthdate" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="father_occupation" value="Occupation" />

                                                <TextInput id="father_occupation" type="text"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.father_occupation" />

                                                <InputError class="mt-2" :message="form.errors.father_occupation" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="father_contact_no" value="Contact #" />

                                                <TextInput id="father_contact_no" type="text"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.father_contact_no" />

                                                <InputError class="mt-2" :message="form.errors.father_contact_no" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-full mt-4">
                                        <h3 class="text-gray-600">Mother's Info</h3>

                                        <div class="mt-2 flex justify-between gap-4">

                                            <div class="w-1/4">
                                                <InputLabel for="mother_name" value="Name" />

                                                <TextInput id="mother_name" type="text"
                                                    class="mt-1 block w-full uppercase" v-model="form.mother_name" />

                                                <InputError class="mt-2" :message="form.errors.mother_name" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="mother_birthdate" value="Birthdate" />

                                                <TextInput id="mother_birthdate" type="date"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.mother_birthdate" />

                                                <InputError class="mt-2" :message="form.errors.mother_birthdate" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="mother_occupation" value="Occupation" />

                                                <TextInput id="mother_occupation" type="text"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.mother_occupation" />

                                                <InputError class="mt-2" :message="form.errors.mother_occupation" />
                                            </div>
                                            <div class="w-1/4">
                                                <InputLabel for="mother_contact_no" value="Contact #" />

                                                <TextInput id="mother_contact_no" type="text"
                                                    class="mt-1 block w-full uppercase"
                                                    v-model="form.mother_contact_no" />

                                                <InputError class="mt-2" :message="form.errors.mother_contact_no" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center mt-4 justify-end">
                                        <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                                            :disabled="form.processing">
                                            Submit
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>

                            <PrompNextStep v-if="show_next_form" :action="'open'" />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
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
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import VueMultiselect from "vue-multiselect";
import VueSelect from 'vue3-select-component';
import { XCircleIcon, XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import municipalities from '@/Data/municipalities.json';
import axios from "axios";
import PrompNextStep from "./PrompNextStep.vue";
const props = defineProps({
    scholarshipProgramsOptions: Array,
    applicant: Object,
    action: String,
    msg: String,
    errors: Object
});

const mncplt = ref(municipalities.municipalities);
const municipalitiesOptions = ref(mncplt.value.map(m => ({
    label: m.name,
    name: m.name,
    value: m.name,
})));

const barangayOptions = ref([]);
const tempBarangayOptions = ref([]);
const coursesOptions = ref([]);


const isOpen = computed(() => props.action == 'create' || props.action == 'update');


const yearlevels = [
    { label: "1ST YEAR", value: "1ST" },
    { label: "2ND YEAR", value: "2ND" },
    { label: "3RD YEAR", value: "3RD" },
    { label: "4TH YEAR", value: "4TH" },
    { label: "5TH YEAR", value: "5TH" },
    { label: "6TH YEAR", value: "6TH" },
    { label: "GRADUATE", value: "GRADUATE" },
    { label: "PGI", value: "PGI" },
    { label: "REVIEW", value: "REVIEW" },
]

const form = useForm({
    first_name: props.applicant?.first_name || "",
    last_name: props.applicant?.last_name || "",
    middle_name: props.applicant?.middle_name || "",
    extension_name: props.applicant?.extension_name || "",
    father_name: props.applicant?.father_name || "",
    father_occupation: props.applicant?.father_occupation || "",
    father_birthdate: props.applicant?.father_birthdate || "",
    father_contact_no: props.applicant?.father_contact_no || "",
    mother_name: props.applicant?.mother_name || "",
    mother_occupation: props.applicant?.mother_occupation || "",
    mother_birthdate: props.applicant?.mother_birthdate || "",
    mother_contact_no: props.applicant?.mother_contact_no || "",
    selected_municipality: { name: props.applicant?.municipality || "" } || "",
    municipality: props.applicant?.municipality || "",
    barangay: props.applicant?.barangay || "",
    temporary_municipality: props.applicant?.temporary_municipality || "",
    temporary_barangay: props.applicant?.temporary_barangay || "",
    selected_barangay: { name: props.applicant?.barangay || "" } || "",
    address: props.applicant?.address || "",
    selected_temporary_municipality: { name: props.applicant?.temporary_municipality || "" } || "",
    selected_temporary_barangay: { name: props.applicant?.temporary_barangay || "" } || "",
    temporary_address: props.applicant?.temporary_address || "",
    birthdate: props.applicant?.birthdate || "",
    civil_status: props.applicant?.civil_status || "",
    religion: props.applicant?.religion || "",
    contact_no: props.applicant?.contact_no || "",
    email: props.applicant?.email || "",
    gender: props.applicant?.gender || "",
    remarks: props.applicant?.remarks || "",
    id: props.applicant?.id || "",
});

const show_next_form = ref(false);

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

    if (newValue.selected_temporary_municipality.value) {
        tempBarangayOptions.value = mncplt.value.find(m => m.name == newValue.selected_temporary_municipality.value).barangays.map(b => ({
            name: b,
            label: b,
            value: b
        }));
    } else {
        tempBarangayOptions.value = [];
    }

    // if (newValue.program) {
    //     console.log(newValue.program);
    //     axios.get(route('courses-api.findbyprogram'), {
    //         params: { program_id: newValue.program }
    //     }).then(response => {
    //         coursesOptions.value = response.data.map(course => ({
    //             label: course.shortname,
    //             value: course.id
    //         }));
    //     });
    // } else {
    //     form.course = [];
    // }
}, { deep: true });


const selectGender = (gender) => {
    form.gender = gender;
};

const resetBarangay = () => {
    form.selected_barangay = "";
};
const resetTempBarangay = () => {
    form.selected_temporary_barangay = "";
};

// const emit = defineEmits(['refreshParentData']);
const submit = () => {
    form.last_name = form.last_name.toUpperCase();
    form.first_name = form.first_name.toUpperCase();
    form.middle_name = form.middle_name.toUpperCase();
    form.extension_name = form.extension_name.toUpperCase();
    form.municipality = form.selected_municipality.name.toUpperCase();
    form.barangay = form.selected_barangay.name.toUpperCase();
    form.temporary_municipality = form.selected_temporary_municipality.name.toUpperCase();
    form.temporary_barangay = form.selected_temporary_barangay.name.toUpperCase();
    form.father_name = form.father_name.toUpperCase();
    form.mother_name = form.mother_name.toUpperCase();
    if (props.action == 'create') {

        form.post(route("applicants.store"), {
            onSuccess: (response) => {
                form.reset();

                toast.success("Profile has been added", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                show_next_form.value = true;
                // console.log(response);
            },
            onError: (err) => {
                form.errors = err;
            }
        });
    }
    if (props.action == 'update') {
        form.put(route("applicants.update", props.applicant.id), {
            onSuccess: (response) => {
                toast.success("Profile has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                // show_next_form.value = true;
                // console.log(response);
            },
            onError: (err) => {
                form.errors = err;
            }
        });
    }
};

console.log(props.applicant);
</script>
<style>
.multiselect__input {
    min-height: 40px !important;
    text-transform: uppercase;
    border-radius: 8px !important;
}

.multiselect__input::placeholder {
    text-transform: none;
}

.vue-select input {
    min-height: 40px !important;
}

.vue-select .menu {
    max-height: 500px !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5s);
}
</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>