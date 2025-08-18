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
import TextArea from "@/Components/TextArea.vue";
const props = defineProps({
    action: String,
    scholarshipProgramsOptions: Object,
    msg: String
});



const isOpen = computed(() => props.action == 'create');



const form = useForm({
    name: "",
    shortname: "",
    description: "",
    remarks: "",
    start_date: "",
    end_date: "",
    requirements: "",
    "scholarship_program_id": ""
});

const resetForm = () => {
    form.reset();
}

// const emit = defineEmits(['refreshParentData']);
const submit = () => {
    form.post(route("courses.store"), {
        onSuccess: () => {
            form.reset('name');
            form.reset('shortname');
            form.reset('description');
            form.reset('remarks');
            form.reset('start_date');
            form.reset('end_date');
            form.reset('requirements');
            toast.success("Program has been added", {
                position: toast.POSITION.TOP_RIGHT,
            });

        },
        onError: (err) => {
            console.log(err.name)
        }
    });
};
</script>
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
                            class="w-full max-w-xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-xl font-medium leading-6 text-gray-900 flex justify-between">
                                New Course Form
                                <Link class="-mr-4 -mt-4" :href="route('courses.index')">
                                <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <form @submit.prevent="submit" class="mt-8">

                                <div class="mt-4">
                                    <div class="w-full">
                                        <InputLabel for="program" value="Program" />
                                        <VueSelect v-model="form.scholarship_program_id" placeholder="Select Program"
                                            id="program" :options="props.scholarshipProgramsOptions" />
                                        <InputError class="mt-2" :message="form.errors.scholarship_program_id"
                                            v-if="!form.scholarship_program_id" />
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between gap-4">

                                    <div class="w-3/4">
                                        <InputLabel for="name" value="Course" />

                                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name"
                                            required autofocus autocomplete="name" />

                                        <InputError class="mt-2" :message="form.errors.name" />
                                    </div>
                                    <div class="w-1/4">
                                        <InputLabel for="shortname" value="Shortname" />

                                        <TextInput id="shortname" type="text" class="mt-1 block w-full"
                                            v-model="form.shortname" required autocomplete="shortname" />

                                        <InputError class="mt-2" :message="form.errors.shortname" />
                                    </div>


                                </div>

                                <div class="mt-4">
                                    <InputLabel for="description" value="Description" />

                                    <TextArea id="description" type="text" class="mt-1 block w-full"
                                        v-model="form.description" autocomplete="description" />

                                    <InputError class="mt-2" :message="form.errors.description" />
                                </div>

                                <div class="mt-4 flex justify-between gap-4">

                                    <div class="w-1/2">
                                        <InputLabel for="start_date" value="Start Date" />

                                        <TextInput id="start_date" type="date" class="mt-1 block w-full"
                                            v-model="form.start_date" autocomplete="start_date" />

                                        <InputError class="mt-2" :message="form.errors.start_date" />
                                    </div>

                                    <div class="w-1/2">
                                        <InputLabel for="end_date" value="End Date" />

                                        <TextInput id="end_date" type="date" class="mt-1 block w-full"
                                            v-model="form.end_date" autocomplete="end_date" />

                                        <InputError class="mt-2" :message="form.errors.end_date" />
                                    </div>


                                </div>

                                <div class="mt-4">
                                    <InputLabel for="remarks" value="Remarks" />

                                    <TextInput id="remarks" type="text" class="mt-1 block w-full" v-model="form.remarks"
                                        autocomplete="remarks" />

                                    <InputError class="mt-2" :message="form.errors.remarks" />
                                </div>

                                <div class="flex items-center justify-end mt-6">
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