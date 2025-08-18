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
                            class="w-full max-w-xl transform overflow-hidden rounded  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-xl font-medium leading-6 bg-[#222831] text-white flex justify-between p-4">
                                Scholarship Application Form
                                <Link class="-mr-4 -mt-4"
                                    :href="route('applicants.index', { id: props.applicant_id, action: 'view' })">
                                <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <div class="px-6 pt-2 pb-4 bg-white">
                                <form @submit.prevent="submit">

                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="school_name" value="School" />

                                            <TextInput autofocus id="school_name" type="text"
                                                class="mt-1 block w-full uppercase" v-model="form.school_name" />

                                            <InputError class="mt-2" :message="form.errors.school_name" />
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="company" value="Company" />

                                            <TextInput autofocus id="company" type="text"
                                                class="mt-1 block w-full uppercase" v-model="form.company_name" />

                                            <InputError class="mt-2" :message="form.errors.company_name" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-4">
                                        <div class="w-full">
                                            <InputLabel for="program" value="Program" />
                                            <VueMultiselect v-model="form.scholarship_program_id"
                                                :options="scholarshipProgramsOptions" :close-on-select="true"
                                                :taggable="true" placeholder="Select Program" label="name"
                                                @select="resetCourse" track-by="name" class="mt-1" />
                                            <!-- <VueSelect v-model="form.scholarship_program_id" placeholder="Select Program"
                                            id="program" :options="props.scholarshipProgramsOptions" /> -->
                                            <InputError class="mt-2" :message="form.errors.scholarship_program_id"
                                                v-if="!form.scholarship_program_id" />
                                        </div>

                                    </div>

                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="courses" value="Course" />
                                            <VueMultiselect v-model="form.course_id" :options="coursesOptions"
                                                :close-on-select="true" placeholder="Select Course" label="shortname"
                                                track-by="shortname" class="mt-1" />
                                            <!-- <VueSelect v-model="form.course_id" placeholder="Select Course" id="courses"
                                            :options="coursesOptions" /> -->
                                            <InputError class="mt-2" :message="form.errors.course_id"
                                                v-if="!form.course_id" />
                                        </div>
                                        <div class="w-1/2">
                                            <InputLabel for="yearlevel" value="Year/Grade Level" />

                                            <VueMultiselect v-model="form.year_level" :options="yearlevels"
                                                :close-on-select="true" placeholder="" label="label" track-by="label"
                                                class="mt-1" />

                                            <InputError class="mt-2" :message="form.errors.year_level" />
                                        </div>

                                        <!-- <div class="w-1/2 flex gap-2 items-center py-3">
                                        <InputLabel for="yearlevel" value="Student Leave" />

                                        <input type="checkbox" name="" id="">
                                    </div> -->
                                    </div>



                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="acadyear" value="Academic Year" />

                                            <TextInput autofocus id="acadyear" type="text"
                                                class="mt-1 block w-full uppercase" v-model="form.academic_year" />

                                            <InputError class="mt-2" :message="form.errors.academic_year" />
                                        </div>

                                        <div class="w-1/2">
                                            <InputLabel for="term" value="Term" />

                                            <TextInput autofocus id="term" type="text"
                                                class="mt-1 block w-full uppercase" v-model="form.term" />

                                            <InputError class="mt-2" :message="form.errors.term" />
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="startdate" value="Start Date" />

                                            <TextInput autofocus id="startdate" type="date"
                                                class="mt-1 block w-full uppercase" v-model="form.startdate" />

                                            <InputError class="mt-2" :message="form.errors.startdate" />
                                        </div>
                                        <div class="w-1/2">
                                            <InputLabel for="enddate" value="End Date" />

                                            <TextInput autofocus id="enddate" type="date"
                                                class="mt-1 block w-full uppercase" v-model="form.enddate" />

                                            <InputError class="mt-2" :message="form.errors.enddate" />
                                        </div>
                                    </div>

                                    <div class="mt-6 w-full">
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
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted, watchEffect } from "vue";
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

const props = defineProps({
    applicant_id: [Number, String],
    action: String,
    msg: String
});


// console.log(form.municipality)
const isOpen = computed(() => props.action == 'open');

const yearlevels = [
    { label: "1ST YEAR", value: "1ST YEAR" },
    { label: "2ND YEAR", value: "2ND YEAR" },
    { label: "3RD YEAR", value: "3RD YEAR" },
    { label: "4TH YEAR", value: "4TH YEAR" },
    { label: "5TH YEAR", value: "5TH YEAR" },
    { label: "6TH YEAR", value: "6TH YEAR" },
    { label: "GRADUATE", value: "GRADUATE" },
    { label: "PGI", value: "PGI" },
    { label: "REVIEW", value: "REVIEW" },
]

const terms = [
    { label: "1ST SEMESTER", value: "1ST SEMESTER" },
    { label: "2ND SEMESTER", value: "2ND SEMESTER" },
    { label: "1ST TRIMESTER", value: "1ST TRIMESTER" },
    { label: "2ND TRIMESTER", value: "2ND TRIMESTER" },
    { label: "3RD TRIMESTER", value: "3RD TRIMESTER" },
]

const form = useForm({
    applicant_id: "",
    scholarship_program_id: "",
    course_id: "",
    program_name: "",
    course_name: "",
    course_id: "",
    school_name: "",
    company_name: "",
    year_level: "",
    academic_year: "",
    term: "",
    startdate: "",
    enddate: "",
    remarks: "",
});

// const currentScholarshipProgram = route().params.scholarship_program || null;


const resetForm = () => {
    form.reset();
}

const resetCourse = () => {
    form.course_id = "";
}

// const emit = defineEmits(['refreshParentData']);
const submit = () => {
    // console.log(form);
    form.program_name = form.scholarship_program_id.name;
    form.course_name = form.course_id.name;
    form.scholarship_program_id = form.scholarship_program_id.id;
    form.course_id = form.course_id.id;
    form.year_level = form.year_level.value;

    form.post(route("scholars.store"), {
        onSuccess: () => {
            form.reset();
            toast.success("Scholarship has been added", {
                position: toast.POSITION.TOP_RIGHT,
            });
        },
        onError: (err) => {
            console.log(err.name)
        }
    });
};


const coursesOptions = ref([]);
const scholarshipProgramsOptions = ref([]);
onMounted(() => {
    form.applicant_id = props.applicant_id;



});
watchEffect(() => {
    if (form.applicant_id) {
        axios.get(route('scholarshipprograms.getactivelist')).then(response => {
            console.log(response.data);
            scholarshipProgramsOptions.value = response.data;
        });
    }
    if (form.scholarship_program_id) {
        axios.get(route('courses-api.findbyprogram'), {
            params: { program_id: form.scholarship_program_id }
        }).then(response => {
            coursesOptions.value = response.data;
        });
    } else {
        form.course_id = "";
    }
});

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