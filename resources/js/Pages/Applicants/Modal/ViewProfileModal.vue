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
                                <span>View Application</span>

                                <button class="-mr-2 cursor-pointer " @click="handleCloseModal">
                                    <XMarkIcon class="h-8 w-8 text-red-500" />
                                </button>
                            </DialogTitle>
                            <div class="px-4 py-2 bg-white">

                                <div class="rounded-lg border border-gray-200 px-4 py-2 mb-2 bg-white shadow-sm">
                                    <div
                                        class="text-base font-semibold text-slate-700 mb-2 uppercase tracking-wide border-b pb-1 flex items-center">
                                        <span class="inline-block w-2 h-5 bg-slate-600 rounded mr-2"></span> Applicant
                                        Information
                                    </div>
                                    <div class="flex flex-row flex-wrap gap-3 mb-2 items-start">
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Name</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span v-if="profile.last_name">{{ profile.last_name }}, </span>
                                                <span v-if="profile.first_name">{{ profile.first_name }} </span>
                                                <span v-if="profile.middle_name">{{ profile.middle_name }} </span>
                                                <span v-if="profile.extension_name">{{ profile.extension_name }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Date Filed</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span v-if="!profile.date_filed"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ moment(profile.date_filed).format('MMMM DD,YYYY')
                                                }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-row gap-4 min-w-[540px] items-start">
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div class="text-[10px] text-gray-500 uppercase">Contact #</div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.contact_no"
                                                        class="text-gray-400 font-normal italic">No
                                                        data
                                                        provided</span>
                                                    <span v-else>{{ profile.contact_no }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div class="text-[10px] text-gray-500 uppercase">Contact No. 2</div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.contact_no_2"
                                                        class="text-gray-400 font-normal italic">No
                                                        data
                                                        provided</span>
                                                    <span v-else>{{ profile.contact_no_2 }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div class="text-[10px] text-gray-500 uppercase">Email Address</div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.email"
                                                        class="text-gray-400 font-normal italic">No data
                                                        provided</span>
                                                    <span v-else>{{ profile.email }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Address</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.municipality && !profile.barangay && !profile.address"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else>{{ profile.municipality }}{{ profile.barangay ? ', ' +
                                                    profile.barangay : '' }}{{ profile.address ? ', ' + profile.address
                                                        : '' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 px-4 py-2 mb-2 bg-white shadow-sm">
                                    <div
                                        class="text-base font-semibold text-slate-700 mb-2 uppercase tracking-wide border-b pb-1 flex items-center">
                                        <span class="inline-block w-2 h-5 bg-slate-600 rounded mr-2"></span> Academic
                                        Information
                                    </div>
                                    <div class="flex flex-row flex-wrap gap-3 mb-2 items-start">
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Program</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].program"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else
                                                    v-tooltip.top="profile.scholarship_grant[0].program.name">{{
                                                        profile.scholarship_grant[0].program.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">School</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].school || !profile.scholarship_grant[0].school.name"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else v-tooltip.top="profile.scholarship_grant[0].school.name">{{
                                                    profile.scholarship_grant[0].school.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Course</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].course || !profile.scholarship_grant[0].course.name"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else v-tooltip.top="profile.scholarship_grant[0].course.name">{{
                                                    profile.scholarship_grant[0].course.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[120px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Year Level</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].year_level"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else>{{ profile.scholarship_grant[0].year_level }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[120px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Academic Year</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].academic_year"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else>{{ profile.scholarship_grant[0].academic_year }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 px-4 py-2 mb-2 bg-gray-50 shadow-sm">
                                    <div
                                        class="text-base font-semibold text-slate-700 mb-2 uppercase tracking-wide border-b pb-1 flex items-center">
                                        <span class="inline-block w-2 h-5 bg-slate-600 rounded mr-2"></span>Parent &
                                        Guardian Information
                                    </div>
                                    <div class="flex flex-row flex-wrap gap-2 mb-2 items-start">
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Father's Name</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_name"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.father_name }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Father's Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_occupation"
                                                    class="text-gray-400 font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.father_occupation }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Father's Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_contact_no"
                                                    class="text-gray-400 font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.father_contact_no }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Mother's Name</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_name"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.mother_name }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Mother's Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_occupation"
                                                    class="text-gray-400 font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.mother_occupation }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Mother's Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_contact_no"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.mother_contact_no }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Guardian Name</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_name"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_name }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Relationship</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_relationship"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_relationship }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Guardian Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_occupation"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_occupation }}</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase mt-2">Guardian Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_contact_no"
                                                    class="text-gray-400 font-normal italic">No data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_contact_no }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row gap-6 items-start mt-2">
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div class="text-[10px] text-gray-500 uppercase">Estimated Gross Monthly
                                                Income</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.parents_guardian_gross_monthly_income"
                                                    class="text-gray-400 font-normal italic">No data provided</span>
                                                <span v-else>{{ profile.parents_guardian_gross_monthly_income }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 px-4 py-2 mb-2 bg-white shadow-sm">
                                    <div
                                        class="text-base font-semibold text-slate-700 mb-2 uppercase tracking-wide border-b pb-1 flex items-center">
                                        <span class="inline-block w-2 h-5 bg-slate-600 rounded mr-2"></span>Remarks
                                    </div>
                                    <div class="p-2 rounded bg-gray-100 font-medium text-gray-700 text-sm">
                                        {{ profile.remarks ? profile.remarks : 'No remarks provided' }}
                                    </div>
                                </div>


                            </div>
                            <div class="w-full bg-white px-4 py-2 flex gap-2 items-center"
                                v-if="profile.application_status != 2">
                                <div class="flex gap-2">
                                    <button
                                        class="btn btn-sm bg-red-500 text-white shadow font-semibold px-4 py-2 rounded"
                                        @click="declineApplication">Decline</button>
                                    <button
                                        class="btn btn-sm bg-green-600 text-white shadow font-semibold px-4 py-2 rounded"
                                        @click="approveApplication">Approve</button>
                                </div>
                                <div class="flex-1"></div>
                                <button
                                    class="btn btn-sm bg-gray-300 text-gray-700 shadow ml-auto font-semibold px-4 py-2 rounded"
                                    @click="handleCloseModal">Cancel</button>
                            </div>


                            <Modal marginTop="md" maxWidth="lg" :show="showModal" @close="handleCloseModal">
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
                                        <SecondaryButton @click="handleCloseModal">Cancel</SecondaryButton>
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
    errors: Object,
    isOpen: Boolean
});

console.log(props.profile)
const form = useForm({
    first_name: props.profile.first_name,
    last_name: props.profile.last_name,
    is_on_waiting_list: '',
    application_status: '',
    application_status_remarks: '',
    application_status_date: '',
})

// Use isOpen from props for modal visibility
const showModal = ref(false);
const declineApplication = () => {
    showModal.value = true;
}
const emit = defineEmits(['close']);
const handleCloseModal = () => {
    showModal.value = false;
    emit('close');
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
