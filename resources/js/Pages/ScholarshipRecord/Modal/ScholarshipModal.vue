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
                            class="w-full max-w-xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                Scholarship Application Form
                                <Link class="-mr-2" :href="route('scholarship_records.index')">
                                <XMarkIcon class="h-6 w-6 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <div class="px-6 pt-2 pb-4 bg-white">
                                <div class="mt-4">
                                    <div class="w-full">
                                        <InputLabel for="scholar" value="Name of scholar" />
                                        <div class="mt-1 font-medium">{{ props.record.profile.last_name }}, {{
                                            props.record.profile.first_name }}
                                            {{ props.record.profile.middle_name }}
                                        </div>
                                    </div>
                                </div>
                                <form @submit.prevent="submit">

                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="school_name" value="School" />
                                            <SchoolSelect v-model="form.school_name" class="mt-1" />

                                            <InputError class="mt-2" :message="form.errors.school_name" />
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="company" value="Company" />

                                            <TextInput id="company" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.company_name" />

                                            <InputError class="mt-2" :message="form.errors.company_name" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-4">
                                        <div class="w-full">
                                            <InputLabel for="program" value="Program" />
                                            <VueMultiselect v-model="form.program" :options="scholarshipProgramsOptions"
                                                :close-on-select="true" :taggable="true" placeholder="Select Program"
                                                label="name" @select="resetCourse" track-by="name" class="mt-1" />
                                            <InputError class="mt-2" :message="form.errors.scholarship_program_id"
                                                v-if="!form.scholarship_program_id" />
                                        </div>

                                    </div>

                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="courses" value="Course" />
                                            <VueMultiselect v-model="form.course" :options="coursesOptions"
                                                :close-on-select="true" placeholder="Select Course" label="shortname"
                                                track-by="shortname" class="mt-1" />
                                            <!-- <VueSelect v-model="form.course_id" placeholder="Select Course" id="courses"
                                            :options="coursesOptions" /> -->
                                            <InputError class="mt-2" :message="form.errors.course_id"
                                                v-if="!form.course_id" />
                                        </div>
                                        <div class="w-1/2">
                                            <InputLabel for="yearlevel" value="Year/Grade Level" />

                                            <!-- <VueMultiselect v-model="form.year_level" :options="yearlevels"
                                                :close-on-select="true" placeholder="" label="label" track-by="label"
                                                class="mt-1" /> -->
                                            <YearLevelSelect v-model="form.year_level" class="mt-1" />

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
                                            <AcademicYearSelect v-model="form.academic_year" :options="academic_years"
                                                class="mt-1" />
                                            <InputError class="mt-2" :message="form.errors.academic_year" />
                                        </div>

                                        <div class="w-1/2">
                                            <InputLabel for="term" value="Term" />
                                            <TermSelect v-model="form.term" :options="terms" class="mt-1" />
                                            <InputError class="mt-2" :message="form.errors.term" />
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="startdate" value="Start Date" />

                                            <DateInput id="startdate" type="date" class="mt-1 block w-full uppercase"
                                                v-model="form.startdate" />

                                            <InputError class="mt-2" :message="form.errors.startdate" />
                                        </div>
                                        <div class="w-1/2">
                                            <InputLabel for="enddate" value="End Date" />

                                            <DateInput id="enddate" type="date" class="mt-1 block w-full uppercase"
                                                v-model="form.enddate" />

                                            <InputError class="mt-2" :message="form.errors.enddate" />
                                        </div>
                                    </div>

                                    <div class="mt-6 w-full flex gap-4">
                                        <div class="w-3/4">
                                            <InputLabel for="remarks" value="Remarks" />

                                            <TextInput id="remarks" type="text" class="mt-1 block w-full"
                                                v-model="form.remarks" />

                                            <InputError class="mt-2" :message="form.errors.remarks" />
                                        </div>
                                        <div class="w-1/4">
                                            <InputLabel for="date_filed" value="File Date" />

                                            <DateInput id="date_filed" type="date" class="mt-1 block w-full uppercase"
                                                v-model="form.date_filed" />

                                            <InputError class="mt-2" :message="form.errors.date_filed" />
                                        </div>
                                    </div>

                                    <div class="flex items-center mt-8 justify-end">
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
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import DateInput from "@/Components/DateInput.vue";
import SchoolSelect from "@/Components/selects/SchoolSelect.vue";
import YearLevelSelect from "@/Components/selects/YearLevelSelect.vue";
import AcademicYearSelect from "@/Components/selects/AcademicYearSelect.vue";
import TermSelect from "@/Components/selects/TermSelect.vue";
import { useApi } from '@/composable/api';

const props = defineProps({
    record: Object,
    action: String,
    msg: String
});

// console.log(props.record);

// console.log(form.municipality)
const isOpen = computed(() => props.action == 'open');

// const yearlevels = [
//     { label: "1ST YEAR", value: "1ST YEAR" },
//     { label: "2ND YEAR", value: "2ND YEAR" },
//     { label: "3RD YEAR", value: "3RD YEAR" },
//     { label: "4TH YEAR", value: "4TH YEAR" },
//     { label: "5TH YEAR", value: "5TH YEAR" },
//     { label: "6TH YEAR", value: "6TH YEAR" },
//     { label: "GRADUATE", value: "GRADUATE" },
//     { label: "PGI", value: "PGI" },
//     { label: "REVIEW", value: "REVIEW" },
// ]

const terms = [
    { label: "1ST SEMESTER", value: "1ST SEMESTER" },
    { label: "2ND SEMESTER", value: "2ND SEMESTER" },
    { label: "1ST TRIMESTER", value: "1ST TRIMESTER" },
    { label: "2ND TRIMESTER", value: "2ND TRIMESTER" },
    { label: "3RD TRIMESTER", value: "3RD TRIMESTER" },
]

const scholarship_status = [
    { label: "Pending", value: 0 },
    { label: "Approved/Ongoing", value: 1 },
    { label: "Completed", value: 2 },
    { label: "Suspended", value: 3 },
    { label: "Cancelled", value: 4 },
]
//'0: Pending, 1: Approved/Ongoing, 2: Completed, 3: Suspended, 4: Cancelled');

const currentYear = new Date().getFullYear();
const startYear = currentYear - 3; // Define your desired starting year


const academic_years = computed(() => {
    const years = [];
    for (let year = startYear; year <= currentYear; year++) {
        years.push({
            label: `${year}-${year + 1}`,
            value: `${year}-${year + 1}`
        });
    }
    return years;
});

const form = useForm({
    profile_id: props.record.profile_id || '',
    course: props.record.course || {},
    course_id: "",
    course_name: "",
    program: props.record.program || {},
    program_id: "",
    program_name: "",
    school_name: props.record.school_name || "",
    company_name: props.record.company_name || "",
    year_level: props.record.year_level || {},
    academic_year: props.record.academic_year || {},
    term: props.record.term || {},
    startdate: props.record.startdate || "",
    enddate: props.record.enddate || "",
    date_filed: props.record.date_filed || "",
    remarks: props.record.remarks || "",
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
    form.course_id = form.course.id;
    form.program_id = form.program.id;
    form.program_name = form.program.name;
    form.course_name = form.course.name;
    form.year_level = form.year_level.value;
    form.academic_year = form.academic_year.value;
    form.school_name = form.school_name.name;
    form.company_name = form.company_name.toUpperCase();
    form.term = form.term.value;

    form.put(route("scholarship_records.update", props.record.id), {
        onSuccess: () => {
            form.reset();
            toast.success("Scholarship has been added", {
                position: toast.POSITION.TOP_RIGHT,
            });
            router.visit(route('scholarship_records.index'))
        },
        onError: (err) => {
            console.log(err.name)
        }
    });
};


const coursesOptions = ref([]);
const scholarshipProgramsOptions = ref([]);
const { data: profileData, loading: profileLoading, fetchData: fetchProfile } = useApi('/api/profiles');
const selectedProfile = ref(null);

const onProfileFilter = async (query) => {
    if (!query || query.length < 2) {
        profileData.value = [];
        return;
    }
    await fetchProfile({ q: query });
};

watchEffect(() => {
    if (form.profile_id) {
        axios.get(route('scholarshipprograms.getactivelist')).then(response => {
            // console.log(response.data);
            scholarshipProgramsOptions.value = response.data;
        });
    }
    if (form.program) {
        axios.get(route('courses-api.findbyprogram'), {
            params: { program_id: form.program.id }
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