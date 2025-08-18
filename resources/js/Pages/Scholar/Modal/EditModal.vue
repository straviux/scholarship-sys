<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-10">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex justify-center p-4 text-center items-center min-h-screen">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-xl font-medium leading-6 text-gray-900 flex justify-between">
                                Scholar's Profile Form
                                <Link class="-mr-4 -mt-4"
                                    :href="route('scholars.showbyprogram', currentScholarshipProgram)">
                                <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <form @submit.prevent="submit">
                                <div class="w-full mt-6 text-lg text-gray-400">
                                    Educational Information
                                </div>
                                <div class="mt-2 flex gap-6">
                                    <div class="w-1/3">
                                        <InputLabel for="program" value="Program" />
                                        <VueSelect v-model="form.program" placeholder="Select Program" id="program"
                                            :options="cad_programs" />
                                        <InputError class="mt-2" :message="form.errors.program" v-if="!form.program" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="course" value="Course" />
                                        <VueSelect v-model="form.course" placeholder="Select Course"
                                            :options="courses" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="yearlevel" value="Year Level" />
                                        <VueSelect v-model="form.yearlevel" placeholder="Select Year Level"
                                            :options="yearlevels" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-6">
                                    <div class="w-1/2">
                                        <InputLabel for="school" value="School" />

                                        <TextInput autofocus id="school" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.school" />

                                        <InputError class="mt-2" :message="form.errors.school" />
                                    </div>
                                    <div class="w-1/2">
                                        <InputLabel for="company" value="Company" />

                                        <TextInput autofocus id="company" type="text"
                                            class="mt-1 block w-full uppercase" v-model="form.company" />

                                        <InputError class="mt-2" :message="form.errors.company" />
                                    </div>
                                </div>

                                <div class="w-full mt-6 pt-4 text-lg border-t  text-gray-400">
                                    Personal Information
                                </div>
                                <div class="mt-2 flex justify-between gap-4">

                                    <div class="w-1/4">
                                        <InputLabel for="lastname" value="Last Name" />

                                        <TextInput autofocus id="lastname" type="text"
                                            class="mt-1 block w-full uppercase" v-model="form.lastname" />

                                        <InputError class="mt-2" :message="form.errors.lastname" />
                                    </div>
                                    <div class="w-1/4">
                                        <InputLabel for="firstname" value="First Name" />

                                        <TextInput id="firstname" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.firstname" />

                                        <InputError class="mt-2" :message="form.errors.firstname" />
                                    </div>
                                    <div class="w-1/4">
                                        <InputLabel for="middlename" value="Middle Name" />

                                        <TextInput id="middlename" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.middlename" />

                                        <InputError class="mt-2" :message="form.errors.middlename" />
                                    </div>
                                    <div class="w-1/4">
                                        <InputLabel for="extension" value="Extension" />

                                        <TextInput id="extension" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.extension" />

                                        <InputError class="mt-2" :message="form.errors.extension" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-start gap-8">

                                    <div class="w-1/3">
                                        <InputLabel for="birthdate" value="Date of Birth" />

                                        <TextInput id="birthdate" type="date" class="mt-1 block w-full uppercase"
                                            v-model="form.birthdate" />

                                        <InputError class="mt-2" :message="form.errors.birthdate" />
                                    </div>
                                    <div class="w-1/3">
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

                                <!-- <div class="mt-6 flex justify-between gap-4">
                                    <div class="w-1/2">
                                        <InputLabel for="father" value="Father's Name" />

                                        <TextInput id="father" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.fathername" />

                                        <InputError class="mt-2" :message="form.errors.fathername" />
                                    </div>

                                    <div class="w-1/2">
                                        <InputLabel for="mother" value="Mother's Name" />

                                        <TextInput id="mother" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.mothername" />

                                        <InputError class="mt-2" :message="form.errors.mothername" />
                                    </div>
                                </div> -->

                                <div class="mt-6 flex justify-between gap-4">

                                    <div class="w-1/3">
                                        <InputLabel for="municipality" value="Municipality" />
                                        <VueSelect v-model="form.municipality" placeholder="Select Municipality"
                                            :options="municipalitiesOptions" class="uppercase mt-1" />
                                        <InputError class="mt-2" :message="form.errors.municipality"
                                            v-if="!form.municipality" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="barangay" value="Barangay" />
                                        <VueSelect v-model="form.barangay" placeholder="Select Barangay"
                                            :options="barangayOptions" class="uppercase mt-1" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="contact" value="Contact No." />

                                        <TextInput id="contact" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.contact_no" />

                                        <InputError class="mt-2" :message="form.errors.contact_no" />
                                    </div>


                                </div>


                                <div class="mt-6 w-1/2">
                                    <InputLabel for="remarks" value="Remarks" />

                                    <TextInput id="remarks" type="text" class="mt-1 block w-full"
                                        v-model="form.remarks" />

                                    <InputError class="mt-2" :message="form.errors.remarks" />
                                </div>
                                <div class="flex items-center mt-4 justify-end">
                                    <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                                        :disabled="form.processing">
                                        Submit
                                    </PrimaryButton>
                                </div>
                            </form>
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
import { XCircleIcon, ExclamationCircleIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import municipalities from '@/Data/municipalities.json';
const props = defineProps({
    action: String,
    msg: String
});

const mncplt = ref(municipalities.municipalities);
console.log(mncplt.value)
const municipalitiesOptions = ref(mncplt.value.map(m => ({
    label: m.name,
    value: m.name
})));

const barangayOptions = ref([]);


// console.log(form.municipality)
const isOpen = computed(() => props.action == 'create');

const cad_programs = [
    { label: "EFA", value: "EFA" },
    { label: "MED", value: "MED" },
    { label: "TECHVOC", value: "TECHVOC" },
    { label: "BAR EXAMINEES", value: "BAR EXAMINEES" }
];

const courses_per_program = {
    EFA: [
        { label: "BSIT", value: "BSIT" },
        { label: "BSED", value: "BSED" },
        { label: "BEED", value: "BEED" },
        { label: "BSBA", value: "BSBA" },
        { label: "BSA", value: "BSA" }
    ],
    MED: [
        { label: "MEDICINE", value: "MEDICINE" },
        { label: "MEDICINE(PSU)", value: "MED_PSU" },
        { label: "NURSING", value: "NURSING" },
        { label: "NURSING(PSU)", value: "NURSING_PSU" },
        { label: "NURSING(PPCI)", value: "NURSING_PPCI" },
        { label: "NURSING(HTU)", value: "NURSING_HTU" },
        { label: "NURSING(MSU)", value: "NURSING_MSU_BATARAZA" },
        { label: "DENTISTRY", value: "DENTISTRY" },
        { label: "MEDTECH", value: "MEDTECH" },
        { label: "PHARMACY", value: "PHARMACY" },
        { label: "RADTECH", value: "RADTECH" },
        { label: "OPTOMETRY", value: "OPTOMETRY" },
        { label: "RESPIRATORY THERAPY", value: "RESPIRATORY THERAPY" },
        { label: "OCCUPATIONAL THERAPY", value: "OCCUPATIONAL THERAPY" },
        { label: "PHYSICAL THERAPY", value: "PHYSICAL THERAPY" },
        { label: "SPEECH-LANGUAGE PATHOLOGY", value: "SPEECH-LANGUAGE PATHOLOGY" },
        { label: "NUTRITION & DIETETICS", value: "NUTRITION & DIETETICS" },

    ],
    TECHVOC: [
        { label: "TVL-ICT", value: "TVL-ICT" },
        { label: "TVL-HE", value: "TVL-HE" }
    ],
    'BAR EXAMINEES': [
        { label: "LAW", value: "LAW" }
    ]
};

const yearlevels = [
    { label: "1ST YEAR", value: "1ST" },
    { label: "2ND YEAR", value: "2ND" },
    { label: "3RD YEAR", value: "3RD" },
    { label: "4TH YEAR", value: "4TH" },
    { label: "5TH YEAR", value: "5TH" },
    { label: "6TH YEAR", value: "6TH" },
    { label: "6TH YEAR", value: "6TH" },
    { label: "GRADUATE", value: "GRADUATE" },
    { label: "PGI", value: "PGI" },
    { label: "REVIEW", value: "REVIEW" },
]

const courses = [
    { label: "BSIT", value: "BSIT" },
    { label: "BSED", value: "BSED" },
    { label: "BEED", value: "BEED" },
    { label: "BSBA", value: "BSBA" },
    { label: "BSA", value: "BSA" }];
// const barangayOptions = ref([]);
const coordinator = ref([]);
const leader = ref([]);
const subleader = ref([]);
const voter_name = ref([]);
// const frm = ref({ coordinator: null })
const form = useForm({
    applicant_id: "",
    name: "",
    firstname: "",
    lastname: "",
    middlename: "",
    extension: "",
    course: "",
    school: "",
    company: "",
    program: "",
    yearlevel: "",
    barangay: "",
    birthdate: "",
    municipality: "",
    barangay: "",
    contact_no: "",
    gender: "",
    remarks: "",
});

watch(form, (newValue) => {
    if (newValue.municipality) {
        barangayOptions.value = mncplt.value.find(m => m.name == newValue.municipality).barangays.map(b => ({
            label: b,
            value: b
        }));

    } else {
        barangayOptions.value = [];
    }
}, { deep: true });
// const voters = ref([]);
const votersOptions = ref([]);
// const customLabel = (voter) => { return `${voter.voter_name} - ${voter.precinct_no}` }
const searchVoterQuery = ref();


const selectGender = (gender) => {
    form.gender = gender;
};
const currentScholarshipProgram = route().params.scholarship_program || null;

// watch(props, () => {
//     // console.log(props.barangays);
//     barangayOptions.value = props.barangays.map((bgy) => ({
//         label: bgy.label,
//         value: bgy.value,
//     }));
//     // console.log(barangayOptions.value)
//     // coordinators.value = props.coordinators;
// });

const resetForm = () => {
    form.reset();
}

// const emit = defineEmits(['refreshParentData']);
const submit = () => {
    form.name = `${form.lastname}, ${form.firstname} ${form.middlename}`;
    form.lastname = form.lastname.toUpperCase();
    form.firstname = form.firstname.toUpperCase();
    form.middlename = form.middlename.toUpperCase();
    form.post(route("votersprofile.store"), {
        onSuccess: () => {
            form.reset('parent_id');
            form.reset('name');
            form.reset('firstname');
            form.reset('lastname');
            form.reset('middlename');
            form.reset('extension');
            form.reset('birthdate');
            form.reset('purok');
            form.reset('contact_no');
            form.reset('gender');
            form.reset('precinct_no');
            form.reset('remarks');
            voter_name.value = "";
            toast.success("Profile has been added", {
                position: toast.POSITION.TOP_RIGHT,
            });

        },
        onError: (err) => {
            console.log(err.name)
        }
    });
};
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