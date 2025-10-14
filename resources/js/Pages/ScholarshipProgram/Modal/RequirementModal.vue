<script setup>
import { ref, computed } from "vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
const props = defineProps({
    action: String,
    program: Object,
    requirements: Array,
    msg: String
});

const isOpen = computed(() => props.action == 'update-requirement');
const selectedRequirements = ref([...props.program.requirements].map(r => r.id));
const form = useForm({
    scholarship_program_id: props.program.id,
    name: props.program?.name || "",
    requirements: selectedRequirements || [] // set default status to active for new records
});

const submit = () => {
    form.put(route("scholarshipprograms.update-requirement", props.program.id), {
        onSuccess: (response) => {
            toast.success("data has been updated", {
                position: toast.POSITION.TOP_RIGHT,
            });
        },
        onError: (err) => {
            form.errors = err;
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
                            class="w-full max-w-2xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-lg font-medium leading-6 bg-[#222831] text-white flex justify-between px-4 py-2 items-center">
                                <span>Update Requirements Form</span>
                                <Link class="-mr-2 " :href="route('scholarshipprograms.index')">
                                <XMarkIcon class="h-6 w-6 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-4 bg-white">
                                <form @submit.prevent="submit">


                                    <div class="mt-4 flex justify-between gap-4">
                                        <div class="w-3/4">
                                            <InputLabel for="name" value="Name" />
                                            <div class="p-2 bg-gray-100 mt-2 text-lg font-medium text-gray-700">{{
                                                props.program.name
                                                }}</div>

                                        </div>
                                        <div class="w-1/4">
                                            <InputLabel for="shortname" value="Shortname" />

                                            <div class="p-2 bg-gray-100 mt-2 text-lg font-medium text-gray-700">{{
                                                props.program.shortname }}</div>
                                        </div>
                                    </div>

                                    <fieldset
                                        class="fieldset bg-base-100 border-base-300 rounded-box w-full border p-4 mt-4 text-lg">
                                        <legend class="fieldset-legend text-gray-700">Requirements</legend>
                                        <label class="label my-2 text-gray-600" v-for="req in requirements">
                                            <input type="checkbox" class="checkbox" v-model="form.requirements"
                                                :value="req.id" />
                                            {{ req.name }}
                                        </label>
                                    </fieldset>
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
