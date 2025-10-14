<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
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
import VueSelect from 'vue3-select-component';
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import TextArea from "@/Components/TextArea.vue";
const props = defineProps({
    action: String,
    scholarshipProgramsOptions: Object,
    course: Object,
    msg: String
});



// console.log(props.course)
const isOpen = computed(() => props.action == 'create' || props.action == 'edit');



const form = useForm({
    name: props.course?.name || "",
    shortname: props.course?.shortname || "",
    description: props.course?.description || "",
    remarks: props.course?.remarks || "",
    start_date: props.course?.start_date || "",
    end_date: props.course?.end_date || "",
    requirements: props.course?.requirements || "",
    scholarship_program_id: props.course?.scholarship_program_id || "",
    is_active: props.course?.is_active || "",
});

const resetForm = () => {
    form.reset();
}

// const emit = defineEmits(['refreshParentData']);
const submit = () => {
    if (props.action == 'create') {
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
                // Error will be handled by form.errors
            }
        });
    }

    if (props.action == 'edit') {
        form.put(route("courses.update", props.course.id), {
            onSuccess: (response) => {
                // console.log(response);
                toast.success("data has been updated", {
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
const selectStatus = (status) => {
    form.is_active = status;
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
                            class="w-full max-w-xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                <span v-if="props.action == 'create'">Create Course Form</span>
                                <span v-if="props.action == 'edit'">Edit Course Form</span>
                                <Link class="-mr-2 " :href="route('courses.index')">
                                <XMarkIcon class="h-6 w-6 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-4 bg-white">
                                <form @submit.prevent="submit" class="mt-8">

                                    <div class="mt-4">
                                        <div class="w-full">
                                            <InputLabel for="program" value="Program" />
                                            <VueSelect v-model="form.scholarship_program_id"
                                                placeholder="Select Program" id="program"
                                                :options="props.scholarshipProgramsOptions" />
                                            <InputError class="mt-2" :message="form.errors.scholarship_program_id"
                                                v-if="!form.scholarship_program_id" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-between gap-4">

                                        <div class="w-3/4">
                                            <InputLabel for="name" value="Course" />

                                            <TextInput id="name" type="text" class="mt-1 block w-full"
                                                v-model="form.name" required autofocus autocomplete="name" />

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

                                        <TextArea id="remarks" type="text" class="mt-1 block w-full"
                                            v-model="form.remarks" autocomplete="remarks" />

                                        <InputError class="mt-2" :message="form.errors.remarks" />
                                    </div>
                                    <div class="w-1/6 mt-6">
                                        <InputLabel class="mb-1" for="is_active" value="Is Active?" />

                                        <div class="flex gap-4 mt-4 ml-4">
                                            <div class="flex items-center mb-4 cursor-pointer" @click="selectStatus(1)">
                                                <input v-model="form.is_active" type="radio" name="is_active" value="1"
                                                    class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                    aria-labelledby="country-option-1"
                                                    aria-describedby="country-option-1" />
                                                <label for="country-option-1"
                                                    class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                    Yes
                                                </label>
                                            </div>

                                            <div class="flex items-center mb-4" @click="selectStatus(0)">
                                                <input v-model="form.is_active" type="radio" name="is_active" value="0"
                                                    class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                                    aria-labelledby="country-option-2"
                                                    aria-describedby="country-option-2" />
                                                <label for="country-option-2"
                                                    class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer">
                                                    No
                                                </label>
                                            </div>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.is_active" />
                                    </div>

                                    <div class="flex items-center justify-end mt-6">
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