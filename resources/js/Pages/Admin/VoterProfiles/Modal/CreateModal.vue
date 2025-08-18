<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-10">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-xl font-medium leading-6 text-gray-900 flex justify-between">
                                Create Student Profile
                                <Link class="-mr-4 -mt-4"
                                    :href="route('votersprofile.showposition', currentVoterPosition)">
                                <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <form @submit.prevent="submit">
                                <div class="mt-8 flex gap-6">
                                    <div class="w-1/3">
                                        <InputLabel for="barangay" value="Program" />
                                        <VueSelect v-model="form.barangay" placeholder="Select Program"
                                            :options="cad_programs" @option-deselected="() => resetForm()" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="barangay" value="Course" />
                                        <VueSelect v-model="form.barangay" placeholder="Select Course"
                                            :options="courses" @option-deselected="() => resetForm()" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="barangay" value="Year Level" />
                                        <VueSelect v-model="form.barangay" placeholder="Select Year Level"
                                            :options="yearlevels" @option-deselected="() => resetForm()" />
                                        <InputError class="mt-2" :message="form.errors.barangay"
                                            v-if="!form.barangay" />
                                    </div>


                                </div>

                                <div class="mt-4 flex justify-between gap-4">
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

                                <div class="mt-6 flex justify-between gap-4">

                                    <!-- change values to address -->
                                    <div class="w-1/3">
                                        <InputLabel for="purok" value="Address" />

                                        <TextInput id="purok" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.purok" />

                                        <InputError class="mt-2" :message="form.errors.purok" />
                                    </div>

                                    <!-- change values to school -->
                                    <div class="w-1/3">
                                        <InputLabel for="purok" value="School" />

                                        <TextInput id="purok" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.purok" />

                                        <InputError class="mt-2" :message="form.errors.purok" />
                                    </div>

                                    <div class="w-1/3">
                                        <InputLabel for="contact_no" value="Contact Number" />

                                        <TextInput id="contact_no" type="text" class="mt-1 block w-full uppercase"
                                            v-model="form.contact_no" />

                                        <InputError class="mt-2" :message="form.errors.contact_no" />
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-start gap-8">

                                    <div class="w-1/3">
                                        <InputLabel for="birthdate" value="Date Admitted" />

                                        <TextInput id="birthdate" type="date" class="mt-1 block w-full uppercase"
                                            v-model="form.birthdate" />

                                        <InputError class="mt-2" :message="form.errors.birthdate" />
                                    </div>
                                    <div class="w-1/3">
                                        <InputLabel for="gender" value="Gender" />

                                        <div class="flex gap-4 mt-4 ml-4">
                                            <div class="flex items-center mb-4 cursor-pointer">
                                                <input v-model="form.gender" type="radio" name="gender" value="M"
                                                    class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                    aria-labelledby="country-option-1"
                                                    aria-describedby="country-option-1" />
                                                <label for="country-option-1"
                                                    class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                    Male
                                                </label>
                                            </div>

                                            <div class="flex items-center mb-4">
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

                                <div class="mt-6 w-1/2">
                                    <InputLabel for="remarks" value="Remarks" />

                                    <TextInput id="remarks" type="text" class="mt-1 block w-full"
                                        v-model="form.remarks" />

                                    <InputError class="mt-2" :message="form.errors.remarks" />
                                </div>
                                <div class="flex items-center mt-4 justify-end">
                                    <button :class="{
                                        'opacity-25': form.processing,
                                    }" class="py-2 w-32 text-white justify-center rounded bg-[rgb(75,203,167)] hover:shadow-lg hover:bg-[rgb(64,193,156)]"
                                        :disabled="form.processing">
                                        Submit
                                    </button>
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

import TextInput from "@/Components/TextInput.vue";
import VueMultiselect from "vue-multiselect";
import VueSelect from 'vue3-select-component';
import { XCircleIcon, ExclamationCircleIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
const props = defineProps({
    barangays: [Array, Object],
    coordinators: Array,
    leaders: Array,
    subleaders: Array,
    action: String
});


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
const barangayOptions = ref([]);
const coordinator = ref([]);
const leader = ref([]);
const subleader = ref([]);
const voter_name = ref([]);
// const frm = ref({ coordinator: null })
const form = useForm({
    parent_id: "",
    name: "",
    firstname: "",
    lastname: "",
    middlename: "",
    extension: "",
    barangay: "",
    birthdate: "",
    purok: "",
    contact_no: "",
    precinct_no: "",
    gender: "",
    position: "",
    remarks: "",
});

// const voters = ref([]);
const votersOptions = ref([]);
const customLabel = (voter) => { return `${voter.voter_name} - ${voter.precinct_no}` }
const searchVoterQuery = ref();

const searchVoter = (voter) => {
    searchVoterQuery.value = voter;
};
const onSelectVoter = (voter) => {
    // console.log(voter.voter_name.split(","));
    const lastname = voter.voter_name.split(",")[0];
    const name = voter.voter_name.split(" ").splice(1);
    const middlename = name[name.length - 1];
    const firstname = name.slice(0, -1);
    form.name = voter.voter_name;
    // form.barangay = voter.barangay_name;
    form.precinct_no = voter.precinct_no;
    form.name = name;
    form.lastname = lastname;
    form.firstname = firstname.join(" ");
    form.middlename = middlename;
    // console.log(voter);
    // console.log(name.length);
};

const onSelectParent = (c) => {
    form.parent_id = c?.id;
};
const currentVoterPosition = route().params.position || null;
watch(
    searchVoterQuery,
    debounce(
        (q) =>
            axios.get(
                route("voterslist.api", { searchname: q, barangay: form.barangay, municipality: `brooke's point` })
            ).then((res) => votersOptions.value = res.data),
        1000
    )
);
watch(props, () => {
    // console.log(props.barangays);
    barangayOptions.value = props.barangays.map((bgy) => ({
        label: bgy.label,
        value: bgy.value,
    }));
    // console.log(barangayOptions.value)
    // coordinators.value = props.coordinators;
});

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
</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>