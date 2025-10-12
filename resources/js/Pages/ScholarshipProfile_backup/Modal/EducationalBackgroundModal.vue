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
                            class="w-full max-w-lg transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                <span v-if="props.action == 'add'">Educational Background Form</span>
                                <span v-if="props.action == 'edit'">Edit Educational Background</span>
                                <Link class="-mr-2"
                                    :href="route('profile.index', { id: props.profile_id, action: 'view' })">
                                <XMarkIcon class="h-6 w-6 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <div class="px-6 pt-2 pb-4 bg-white">
                                <form @submit.prevent="submit" @keydown.enter.prevent>
                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="yearlevel" value="Academic Level" />

                                            <VueMultiselect v-model="form.level" :options="academic_levels"
                                                @select="checkLevel" :close-on-select="true" class="mt-1" />

                                            <InputError class="mt-2" :message="form.errors.level" />
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="school_name" value="School" />

                                            <TextInput autofocus id="school_name" class="mt-1 block w-full"
                                                :custom-class="'uppercase'" v-model="form.school_name" />

                                            <InputError class="mt-2" :message="form.errors.school_name" />
                                        </div>
                                    </div>




                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-full">
                                            <InputLabel for="course" value="Course" />
                                            <TextInput id="course" type="text" class="mt-1 block w-full uppercase"
                                                v-model="form.course"
                                                :disabled="form.level === 'ELEMENTARY' || form.level === 'SECONDARY'" />
                                            <InputError class="mt-2" :message="form.errors.course" />
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-1/2">
                                            <InputLabel for="startdate" value="From" />

                                            <VueDatePicker v-model="form.start_date" year-picker text-input
                                                class="mt-1 block w-full uppercase" />
                                            <InputError class="mt-2" :message="form.errors.startdate" />
                                        </div>
                                        <div class="w-1/2">
                                            <InputLabel for="enddate" value="To" />
                                            <VueDatePicker v-model="form.end_date" year-picker text-input
                                                class="mt-1 block w-full uppercase" />
                                            <!-- <TextInput autofocus id="enddate" type="month"
                                            class="mt-1 block w-full uppercase" v-model="form.end_date" /> -->

                                            <InputError class="mt-2" :message="form.errors.enddate" />
                                        </div>
                                    </div>

                                    <div class="mt-6 w-full">
                                        <InputLabel for="remarks" value="Academic Honors" />

                                        <TextInput id="academic_honors" type="text" class="mt-1 block w-full"
                                            v-model="form.academic_honors" />

                                        <InputError class="mt-2" :message="form.errors.academic_honors" />
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
import { ref, computed, onMounted } from "vue";
import { Link, useForm, } from "@inertiajs/vue3";
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
import { XMarkIcon, ExclamationCircleIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    profile_id: [String, Number],
    education: Object,
    action: String,
    msg: String
});


// console.log(props.education)
const isOpen = computed(() => props.action == 'add' || 'edit' ? true : false);

const academic_levels = [
    "ELEMENTARY",
    "SECONDARY",
    "COLLEGE",
];

const form = useForm({
    id: props.education?.id || "",
    profile_id: props.education?.profile_id || props.profile_id,
    school_name: props.education?.school_name || "",
    course: props.education?.course || "",
    level: props.education?.level || "",
    academic_year: props.education?.academic_year || "",
    start_date: props.education?.start_date || "",
    end_date: props.education?.end_date || "",
    academic_honors: props.education?.academic_honors || "",
});

// console.log(form.profile_id)
const checkLevel = () => {

    if (form.level === 'ELEMENTARY' || form.level === 'SECONDARY') {
        form.course = '';
    }
};

const submit = () => {
    if (props.action == 'add') {
        axios.post(route("profile-api.addeducation"), { ...form })
            .then(response => {
                if (response.data.message === 'success') {
                    toast.success("Educational background added successfully!");
                    form.reset();
                    form.applicant_id = props.applicant_id;
                }
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.errors;
                    for (const key in errors) {
                        form.setError(key, errors[key][0]);
                    }
                } else {
                    toast.error("An error occurred while adding educational background.");
                }
            });
    }

    if (props.action == 'edit') {
        axios.put(route("profile-api.updateeducation", form.id), form).then(response => {
            if (response.data.message === 'success') {
                toast.success("Educational background updated successfully!");
            }
        }).catch(error => {
            if (error.response && error.response.status === 422) {
                const errors = error.response.data.errors;
                for (const key in errors) {
                    form.setError(key, errors[key][0]);
                }
            } else {
                toast.error("An error occurred while updating educational background.");
            }
        });
    }
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