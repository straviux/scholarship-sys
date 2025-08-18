<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import { router } from '@inertiajs/vue3';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { ref, onMounted, computed, watch } from 'vue';
import { usePermission } from '@/composable/permissions';
import Table from '@/Components/Table.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TabLink from '@/Components/TabLink.vue';
import VueMultiselect from 'vue-multiselect';
import VueSelect from 'vue3-select-component';
import {
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    IdentificationIcon,
    UserPlusIcon,
} from '@heroicons/vue/20/solid';
import { DynamicHeroicon } from 'vue-dynamic-heroicons';

import ApplicantProfileModal from '@/Pages/Applicant/Modal/ApplicantProfileModal.vue';
// import EditModal from '@/Pages/Scholarship/Modal/EditModal.vue';
import Pagination from '@/Components/Pagination.vue';

import { isAndroid, isIOS, isMobile } from '@basitcodeenv/vue3-device-detect'
import MobileTabLink from '@/Components/MobileTabLink.vue';
// import ViewProfileModal from './Modal/ViewProfileModal.vue';

const { hasPermission } = usePermission();
const form = useForm({});

const props = defineProps({
    applicant: Object,
    applicants: Array,
    action: String,
    message: Object,
});


onMounted(() => {

    if (props.message.error) {
        alert(props.message.message)
    }
    console.log(props.applicants);
});


</script>
<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Scholar Profile Records</template>

        <div class="p-4">

            <div
                class="text-normal font-medium text-center text-gray-500  border-gray-200 flex justify-center items-center mb-4">
                <h1>Welcome {{ $page.props.auth.user.name }}</h1>
                <!-- <h3>{{ props }}</h3> -->
                <!-- {{ props.action }} -->

                <Link v-if="hasPermission('create-scholar-profile')" :href="route('applicants.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-emerald-500  text-white  ml-auto flex items-center justify-center uppercase">
                <UserPlusIcon class="h-4 w-4" />New Record</Link>
            </div>


            <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]" :loading="form.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="px-3">#</TableHeaderCell>
                        <TableHeaderCell>Name</TableHeaderCell>

                        <TableHeaderCell>Address</TableHeaderCell>
                        <TableHeaderCell>Contact #</TableHeaderCell>
                        <TableHeaderCell>Status</TableHeaderCell>
                        <TableHeaderCell>Date Filed</TableHeaderCell>
                        <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                    </TableRow>
                </template>
                <template #default>
                    <TableRow class="hover:bg-gray-200" v-for="(applicant, index) in applicants"
                        :key="'applicant_' + applicant.id" v-if="applicants.length > 0">
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1 }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <div>
                                {{ applicant.last_name + ', ' + applicant.first_name + ' ' + (applicant.middle_name ||
                                    '') + ' ' + (applicant.extension_name || '') }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ applicant.municipality + ', ' + applicant.barangay }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ applicant.contact_no }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <span class="text-emerald-500"
                                v-if="applicant.is_active && applicant.is_approve">Active</span>
                            <span class="text-orange-500"
                                v-if="applicant.is_active && !applicant.is_approve">Pending</span>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ applicant.created_at }}
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="flex space-x-6 justify-center">
                                <Link :href="route('applicants.index', { id: applicant.id, action: 'view' })"
                                    class="text-blue-500 hover:text-blue-600 flex underline underline-offset-2">
                                <!-- <IdentificationIcon class="h-5 w-5 text-blue-400" /> -->
                                View</Link>
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="9">No data to be displayed</TableDataCell>
                    </TableRow>
                </template>
            </Table>

        </div>


        <!-- CREATE PROFILE MODAL -->
        <ApplicantProfileModal v-if="props.action == 'create' && hasPermission('create-scholar-profile')"
            :action="props.action" :errors="props.errors" />

        <!-- VIEW PROFILE MODAL -->
        <!-- <ViewProfileModalModal v-if="props.action == 'view' && hasPermission('create-scholar-profile')"
            :action="props.action" :errors="props.errors" :scholarshipProgramsOptions="scholarshipProgramsOptions" /> -->

    </AdminLayout>

</template>