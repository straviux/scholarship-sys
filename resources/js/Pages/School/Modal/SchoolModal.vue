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
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import PrimaryButton from "@/Components/ui/buttons/PrimaryButton.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import TextArea from "@/Components/ui/inputs/TextArea.vue";

const props = defineProps({
    action: String,
    program: Object,
    msg: String
});

const isOpen = computed(() => props.action == 'create' || props.action == 'edit');

const form = useForm({
    name: props.program?.name || "",
    shortname: props.program?.shortname || "",
    description: props.program?.description || "",
    remarks: props.program?.remarks || "",
    start_date: props.program?.start_date || "",
    end_date: props.program?.end_date || "",
    is_active: props.program?.is_active || 1, // set default status to active for new records
});

const resetForm = () => {
    form.reset();
}

const submit = () => {
    if (props.action == 'create') {
        form.post(route("school.store"), {
            onSuccess: () => {
                form.reset('name');
                form.reset('shortname');
                form.reset('description');
                form.reset('remarks');
                form.reset('start_date');
                form.reset('end_date');
                toast.success("Program has been added", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
            onError: (err) => {
                // Error will be handled by form.errors
            }
        });
    } if (props.action == 'edit') {
        form.put(route("school.update", props.program.id), {
            onSuccess: (response) => {
                toast.success("data has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
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
                            class="w-full max-w-2xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                <span>School Form</span>
                                <Link class="-mr-2 " :href="route('school.index')">
                                <XMarkIcon class="h-6 w-6 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-4 bg-white">
                                <form @submit.prevent="submit">
                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-3/4">
                                            <InputLabel for="name" value="Name" />
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
