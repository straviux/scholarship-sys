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
                                <span>Quick View</span>

                                <button class="-mr-2 cursor-pointer " @click="handleCloseModal">
                                    <XMarkIcon class="h-8 w-8 text-red-500" />
                                </button>
                            </DialogTitle>

                            <div class="space-y-4 bg-white p-4">
                                <!-- Profile Info -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-gray-800 mb-2">Applicant Information</h3>
                                    <div class="text-sm">
                                        <div class="font-mono text-lg text-gray-900">
                                            {{ `${selectedProfile.last_name}, ${selectedProfile.first_name}
                                            ${selectedProfile.middle_name ||
                                                ''}` }}
                                        </div>
                                        <div class="text-gray-600 mt-1">
                                            {{ selectedProfile.scholarship_grant?.[0]?.course?.shortname }} •
                                            {{ selectedProfile.scholarship_grant?.[0]?.school?.shortname }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Sequence Numbers -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ '#' + (selectedProfile.sequence_number || '-') }}
                                        </div>
                                        <div class="text-sm text-blue-800 font-medium">Program Sequence</div>
                                        <div class="text-xs text-blue-600 mt-1">Overall sequence in program</div>
                                    </div>

                                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-purple-600">
                                            {{ '#' + (selectedProfile.sequence_number_by_course || '-') }}
                                        </div>
                                        <div class="text-sm text-purple-800 font-medium">Course Sequence</div>
                                        <div class="text-xs text-purple-600 mt-1">Sequence within course</div>
                                    </div>

                                    <div class="bg-orange-50 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-orange-600">
                                            {{ '#' + (selectedProfile.daily_sequence_number || '-') }}
                                        </div>
                                        <div class="text-sm text-orange-800 font-medium">Daily Sequence</div>
                                        <div class="text-xs text-orange-600 mt-1">Sequence by date filed</div>
                                    </div>

                                    <div class="bg-green-50 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-green-600">
                                            {{ '#' + (selectedProfile.sequence_number_by_school_course || '-') }}
                                        </div>
                                        <div class="text-sm text-green-800 font-medium">School+Course Sequence</div>
                                        <div class="text-xs text-green-600 mt-1">Sequence within school & course</div>
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">Filing Details</h4>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div><span class="font-medium">Date Filed:</span> {{ selectedProfile.date_filed
                                            ?
                                            moment(selectedProfile.date_filed).format('MMMM DD, YYYY') : '-' }}</div>
                                        <div><span class="font-medium">Program:</span> {{
                                            selectedProfile.scholarship_grant?.[0]?.program?.shortname || '-' }}</div>
                                        <div><span class="font-medium">Year Level:</span> {{
                                            selectedProfile.scholarship_grant?.[0]?.year_level || '-' }}</div>
                                    </div>
                                </div>
                            </div>


                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

</template>

<script setup>

import { computed } from "vue";
import moment from 'moment'
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object,
    isOpen: Boolean
});

const emit = defineEmits(['close']);
const handleCloseModal = () => {
    emit('close');
};

const selectedProfile = computed(() => props.profile || {});
</script>
<style></style>
