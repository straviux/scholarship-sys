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
                            class="w-full max-w-3xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-normal font-medium leading-6 bg-[#222831] text-white flex items-center justify-between px-4 py-2">
                                <span v-if="action == 'view'">View Application</span>

                                <Link class="-mr-2 " :href="route('profile.waitinglist')">
                                <XMarkIcon class="h-8 w-8 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-4 bg-white">

                                <div class="flex justify-between items-center rounded-lg">

                                    <table class="table table-sm shadow-lg">
                                        <!-- head -->

                                        <tbody>
                                            <tr>
                                                <th class="font-normal bg-slate-500 text-white rounded-t" colspan="3">
                                                    Name
                                                </th>
                                            </tr>
                                            <tr>
                                                <td colspan="3"><span
                                                        class="text-normal font-medium text-gray-700 mb-4">
                                                        {{ profile.last_name + ', ' + profile.first_name + ' ' +
                                                            (profile.middle_name || '') +
                                                            ' ' +
                                                            (profile.extension_name || '') }}
                                                    </span></td>
                                            </tr>

                                            <tr>
                                                <th class="font-normal bg-slate-500 text-white" colspan="2">Address</th>
                                                <th class="font-normal bg-slate-500 text-white" colspan="1">Date Filed
                                                </th>
                                            </tr>
                                            <tr>
                                                <td colspan="2"><span
                                                        class="text-normal font-medium text-gray-700  mb-4 uppercase">{{
                                                            profile.municipality }}, {{
                                                            profile.barangay }} {{ profile.address ? ', ' +
                                                            profile.address : '' }}
                                                    </span></td>
                                                <td colspan="1"><span
                                                        class="text-normal font-medium text-gray-700  mb-4 uppercase">{{
                                                            moment(profile.date_filed).format('MMMM DD, YYYY') }}
                                                    </span></td>
                                            </tr>
                                            <tr>
                                                <th class="font-normal bg-slate-500 text-white" colspan="1">Contact #
                                                </th>
                                                <th class="font-normal bg-slate-500 text-white" colspan="2">Email
                                                    Address
                                                </th>
                                            </tr>
                                            <tr>
                                                <td colspan="1"><span
                                                        class="text-normal font-medium text-gray-700  mb-4">{{
                                                            profile.contact_no }}
                                                    </span></td>
                                                <td colspan="2"><span
                                                        class="text-normal font-medium text-gray-700  mb-4">{{
                                                            profile.email }}
                                                    </span></td>
                                            </tr>
                                            <!-- row 2 -->
                                            <tr>
                                                <th class="font-normal bg-slate-500 text-white" colspan="1">School
                                                </th>
                                                <th class="font-normal bg-slate-500 text-white" colspan="1">Course
                                                </th>
                                                <th class="font-normal bg-slate-500 text-white" colspan="1">Year Level
                                                </th>
                                            </tr>

                                            <tr>
                                                <td colspan="1"><span
                                                        class="text-normal font-medium text-gray-700  mb-4 uppercase">{{
                                                            profile.applied_school }}
                                                    </span></td>
                                                <td colspan="1"><span
                                                        class="text-normal font-medium text-gray-700  mb-4 uppercase">{{
                                                            profile.applied_course }}
                                                    </span></td>
                                                <td colspan="1"><span
                                                        class="text-normal font-medium text-gray-700  mb-4 uppercase">{{
                                                            profile.applied_year_level }}
                                                    </span></td>
                                            </tr>


                                        </tbody>
                                    </table>
                                </div>

                                <hr>
                                <div class="text-gray-600 mt-6 mb-2">Parent's Information:</div>
                                <table class="table table-sm">
                                    <!-- head -->

                                    <tbody>


                                        <tr>
                                            <th class="font-normal text-gray-500" colspan="1">Father's Name</th>
                                            <td colspan="1"><span class="text-normal font-medium text-gray-700  mb-4">{{
                                                profile.father_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500" colspan="1">Contact #:</th>
                                            <td colspan="3"><span class="text-normal font-medium text-gray-700  mb-4">{{
                                                profile.father_contact_no }}
                                                </span></td>
                                        </tr>

                                        <tr>
                                            <th class="font-normal text-gray-500" colspan="1">Mother's Name</th>
                                            <td colspan="1"><span class="text-normal font-medium text-gray-700  mb-4">{{
                                                profile.mother_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500" colspan="1">Contact #:</th>
                                            <td colspan="3"><span class="text-normal font-medium text-gray-700  mb-4">{{
                                                profile.mother_contact_no }}
                                                </span></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="mt-4">
                                    <div class="text-gray-700">
                                        Remarks:
                                    </div>
                                    <div class="p-4 rounded bg-gray-100 font-medium text-gray-700">
                                        {{ profile.remarks }}
                                    </div>
                                </div>


                            </div>
                            <div class="w-full bg-white px-4 py-2 flex justify-end gap-2"
                                v-if="profile.application_status != 2">
                                <button class="btn btn-sm bg-red-400 text-white shadow"
                                    @click="declineApplication">Decline</button>
                                <button class="btn btn-sm bg-slate-700 text-white shadow"
                                    @click="approveApplication">Approve</button>
                            </div>


                            <Modal marginTop="md" maxWidth="lg" :show="showModal" @close="closeModal">
                                <div class="p-2 bg-slate-700 text-left text-white">Decline Application</div>
                                <div class="p-4">
                                    <div class="mt-4 bg-slate-100 p-4 text-center text-red-700 font-semibold">
                                        <div class="w-full text-left">
                                            <InputLabel class="mb-1" for="remarks" value="Reason/Remarks" />
                                            <TextInput id="remarks" type="text" class="w-full block text-gray-700"
                                                v-model="form.application_status_remarks" />
                                            <!-- <InputError class="mt-2" :message="form.errors.applied_school" v-if="!form.applied_school" /> -->
                                        </div>

                                    </div>
                                    <div class="mt-6 flex justify-end space-x-4">
                                        <DangerButton @click="confirmDecline">
                                            Submit</DangerButton>
                                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                                    </div>
                                </div>
                            </Modal>
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
import moment from 'moment'
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";

import municipalities from '@/Data/municipalities.json';

const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object
});

// console.log(props.profile)
const form = useForm({
    first_name: props.profile.first_name,
    last_name: props.profile.last_name,
    is_on_waiting_list: '',
    application_status: '',
    application_status_remarks: '',
    application_status_date: '',
})

const isOpen = computed(() => props.action == 'view');
const showModal = ref(false);
const declineApplication = () => {
    showModal.value = true;
}
const closeModal = () => {
    showModal.value = false;
};


const confirmDecline = () => {
    form.application_status = 2;
    form.application_status_date = moment().format('YYYY-MM-DD');
    form.is_on_waiting_list = 1;
    form.put(route("profile.update", props.profile.profile_id), {
        onSuccess: (response) => {
            toast.success("Profile has been updated", {
                position: toast.POSITION.TOP_RIGHT,
            });
            router.visit(route('profile.waitinglist'))
            // show_next_form.value = true;
            // console.log(response);
        },
        onError: (err) => {
            form.errors = err;
            console.log(err)
        }
    });
}

const approveApplication = () => {
    form.application_status = 1;
    form.application_status_remarks = 'Application Approved';
    form.application_status_date = moment().format('YYYY-MM-DD');
    form.is_on_waiting_list = 0;
    form.put(route("profile.update", props.profile.profile_id), {
        onSuccess: (response) => {
            toast.success("Profile has been updated", {
                position: toast.POSITION.TOP_RIGHT,
            });
            router.visit(route('profile.index', { id: props.profile.profile_id, action: 'view' }))
            // show_next_form.value = true;
            // console.log(response);
        },
        onError: (err) => {
            form.errors = err;
            console.log(err)
        }
    });
}
</script>
<style></style>
