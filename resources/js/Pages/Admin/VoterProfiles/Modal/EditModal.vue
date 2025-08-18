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
                                Edit Voter's Profile
                                <Link class="-mr-4 -mt-4"
                                    :href="route('votersprofile.showposition', currentVoterPosition)">
                                <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <form @submit.prevent="submit">
                                <div class="mt-6">
                                    <div class="mt-4">
                                        <div class="w-1/2 pr-1">
                                            <div class="flex gap-4 items-center">
                                                <InputLabel for="position" value="Position" />
                                                <div v-if="profile.members.length > 0" class="flex items-center gap-1">
                                                    <ExclamationCircleIcon class="h-4 w-4 text-gray-500" />
                                                    <p class="text-gray-500 text-sm">This profile has dowline. view
                                                        <a class="text-blue-500 underline" :href="route('votersprofile.viewprofile', {
                                                            id: profile.id,
                                                        })
                                                            " target="_blank">here</a>
                                                    </p>
                                                </div>
                                                <div v-if="typeof profile.parent_id == 'number'"
                                                    class="flex items-center gap-1">
                                                    <ExclamationCircleIcon class="h-4 w-4 text-gray-500" />
                                                    <p class="text-gray-500 text-sm">This profile has an upline. view
                                                        <a class="text-blue-500 underline" :href="route('votersprofile.viewprofile', {
                                                            id: profile.parent_id,
                                                        })
                                                            " target="_blank">here</a>
                                                    </p>
                                                </div>
                                            </div>

                                            <VueMultiselect v-model="form.position" :options="positions"
                                                :close-on-select="true"
                                                :disabled="profile.members.length > 0 || typeof profile.parent_id == 'number'"
                                                class="uppercase mt-1" placeholder="Select position" />
                                            <InputError class="mt-2" :message="form.errors.position" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <div class="w-1/3">
                                            <InputLabel for="lastname" value="Last Name" />
                                            <!-- {{ typeof profile.parent_id }} -->
                                            <TextInput id="lastname" type="text" v-model="form.lastname" autofocus
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="firstname" value="First Name" />

                                            <TextInput id="firstname" v-model="form.firstname" type="text"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="middlename" value="Middle Name" />

                                            <TextInput id="middlename" v-model="form.middlename" type="text"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <div class="w-1/3 pr-1">
                                            <InputLabel for="barangay" value="Barangay" />

                                            <VueMultiselect v-model="form.barangay" :options="barangays"
                                                :close-on-select="true" placeholder="Select barangay" />

                                            <InputError class="mt-2" :message="form.errors.barangay" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="purok" value="Purok/Sitio" />

                                            <TextInput id="purok" v-model="form.purok" type="text"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="contact_no" value="Contact Number" />

                                            <TextInput id="contact_no" v-model="form.contact_no" type="text"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <div class="w-1/3">
                                            <InputLabel for="precint_no" value="Precinct Number" />

                                            <TextInput id="precint_no" disabled type="text" v-model="form.precinct_no"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                        <div class="w-1/3">
                                            <InputLabel for="birthdate" value="Birthdate" />

                                            <TextInput id="birthdate" v-model="form.birthdate" type="date"
                                                class="mt-1 block w-full uppercase" />
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
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="w-1/2">
                                            <InputLabel for="remarks" value="Remarks" />

                                            <TextInput id="remarks" v-model="form.remarks" type="text"
                                                class="mt-1 block w-full uppercase" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center mt-4 justify-end">
                                    <button :class="{
                                        'opacity-25': form.processing,
                                    }" class="py-2 w-32 text-white justify-center rounded bg-[rgb(146,81,220)] hover:shadow-lg hover:bg-[rgb(128,71,194)]"
                                        :disabled="form.processing">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                    py-4 text-white
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
// import { BaseTree, Draggable, pro, OpenIcon } from "@he-tree/vue";
// import "@he-tree/vue/style/default.css";
// import "@he-tree/vue/style/material-design.css";
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
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { XCircleIcon, ExclamationCircleIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    profile: Object,
    position: String,
    barangays: Array,
    urlPrev: String,
});
const isOpen = computed(() => !!props.profile);
// const treeData = ref([
//     {
//         id: props.profile?.id || "",
//         name: props.profile?.name || "",
//         position: props.profile?.position || "",
//         precinct_no: props.profile?.precinct_no || "",
//         children: props.profile?.members || "",
//     },
// ]);



const positions = ["COORDINATOR", "LEADER", "SUBLEADER", "MEMBER"];

const form = useForm({
    parent_id: props.profile?.parent_id || "",
    name: props.profile?.name || "",
    firstname: props.profile?.firstname || "",
    lastname: props.profile?.lastname || "",
    middlename: props.profile?.middlename || "",
    barangay: props.profile?.barangay || "",
    birthdate: props.profile?.birthdate || "",
    purok: props.profile?.purok || "",
    contact_no: props.profile?.contact_no || "",
    precinct_no: props.profile?.precinct_no || "",
    gender: props.profile?.gender || "",
    position: props.profile?.position || "",
    remarks: props.profile?.remarks || "",
    redirectUrl: `/votersprofile/position/${props.position}`,
});

const submit = () => {
    form.put(
        route(
            'votersprofile.update',
            props.profile.id
        ),
        {
            onSuccess: () => {
                toast.success("Profile has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
            onError: (err) => {
                console.log(err)
            },
            preserveScroll: true
        },
    )
}
const currentVoterPosition = route().params.position || null;
watch(
    () => props.profile,
    (profile) => {
        if (profile) {
            form.parent_id = profile.parent_id || "";
            form.name = profile.name || "";
            form.firstname = profile.firstname || "";
            form.lastname = profile.lastname || "";
            form.middlename = profile.middlename || "";
            form.barangay = profile.barangay || "";
            form.birthdate = profile.birthdate || "";
            form.purok = profile.purok || "";
            form.contact_no = profile.contact_no || "";
            form.precinct_no = profile.precinct_no || "";
            form.gender = profile.gender || "";
            form.position = profile.position || "";
            form.remarks = profile.remarks || "";
            form.redirectUrl = `/votersprofile/position/${props.position}`;
        }
    }
);
</script>
