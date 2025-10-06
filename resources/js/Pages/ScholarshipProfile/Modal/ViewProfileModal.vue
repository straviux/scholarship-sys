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
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Name</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span v-if="profile.last_name">{{ `${profile.last_name}, ` }}</span>
                                                <span v-if="profile.first_name">{{ `${profile.first_name} ` }}</span>
                                                <span v-if="profile.middle_name">{{ `${profile.middle_name} ` }}</span>
                                                <span v-if="profile.extension_name">{{ profile.extension_name }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Date Filed
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span v-if="!profile.date_filed"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ moment(profile.date_filed).format('MMMM DD, YYYY')
                                                    }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-row gap-4 min-w-[540px] items-start">
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div
                                                    class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                    Contact #
                                                </div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.contact_no"
                                                        class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                        data
                                                        provided</span>
                                                    <span v-else>{{ profile.contact_no }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div
                                                    class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                    Contact
                                                    No.
                                                    2</div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.contact_no_2"
                                                        class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                        data
                                                        provided</span>
                                                    <span v-else>{{ profile.contact_no_2 }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col min-w-[160px] justify-start">
                                                <div
                                                    class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                    Email
                                                    Address</div>
                                                <div class="font-semibold text-gray-800 text-xs">
                                                    <span v-if="!profile.email"
                                                        class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                        data
                                                        provided</span>
                                                    <span v-else>{{ profile.email }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Address</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.municipality && !profile.barangay && !profile.address"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
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
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Program</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].program"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else
                                                    v-tooltip.top="profile.scholarship_grant[0].program.name">{{
                                                        profile.scholarship_grant[0].program.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                School</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].school || !profile.scholarship_grant[0].school.name"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else v-tooltip.top="profile.scholarship_grant[0].school.name">{{
                                                    profile.scholarship_grant[0].school.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[180px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Course</div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].course || !profile.scholarship_grant[0].course.name"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else v-tooltip.top="profile.scholarship_grant[0].course.name">{{
                                                    profile.scholarship_grant[0].course.shortname }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[120px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Year Level
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].year_level"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.scholarship_grant[0].year_level }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[120px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Academic Year
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs uppercase">
                                                <span
                                                    v-if="!profile.scholarship_grant || !profile.scholarship_grant.length || !profile.scholarship_grant[0].academic_year"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
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
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Father's Name
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_name"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.father_name }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Father's
                                                Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_occupation"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.father_occupation }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Father's
                                                Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.father_contact_no"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.father_contact_no }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Mother's Name
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_name"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.mother_name }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Mother's
                                                Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_occupation"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data provided</span>
                                                <span v-else>{{ profile.mother_occupation }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Mother's
                                                Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.mother_contact_no"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.mother_contact_no }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Guardian Name
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_name"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_name }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Relationship</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_relationship"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_relationship }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Guardian
                                                Occupation
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_occupation"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_occupation }}</span>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2 mt-2">
                                                Guardian
                                                Contact #
                                            </div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.guardian_contact_no"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
                                                <span v-else>{{ profile.guardian_contact_no }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row gap-6 items-start mt-2">
                                        <div class="flex flex-col min-w-[220px] justify-start">
                                            <div
                                                class="text-[11px] text-gray-600 font-medium capitalized underline underline-offset-2">
                                                Estimated
                                                Gross
                                                Monthly
                                                Income</div>
                                            <div class="font-semibold text-gray-800 text-xs">
                                                <span v-if="!profile.parents_guardian_gross_monthly_income"
                                                    class="text-gray-400 text-[10px] lowercase font-normal italic">No
                                                    data
                                                    provided</span>
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
                            <div class="w-full bg-white px-4 pb-2 flex gap-2 items-center -mt-4 justify-end">
                                <Button @click="handleCloseModal" label="Close" variant="text" severity="secondary"
                                    raised class="mt-12" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

</template>

<script setup>

import { useForm } from "@inertiajs/vue3";
import moment from 'moment'
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
// import municipalities from '@/Data/municipalities.json';

const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object,
    isOpen: Boolean
});

// console.log(props.profile);
const form = useForm({
    date_approved: '',
    remarks: ''
})

const emit = defineEmits(['close']);
const handleCloseModal = () => {
    emit('close');
};





</script>
<style></style>
