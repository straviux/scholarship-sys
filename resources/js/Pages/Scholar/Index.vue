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

import CreateModal from '@/Pages/Applicant/Modal/ApplicantProfileModal.vue';
// import EditModal from '@/Pages/Scholarship/Modal/EditModal.vue';
import Pagination from '@/Components/Pagination.vue';

import { isAndroid, isIOS, isMobile } from '@basitcodeenv/vue3-device-detect'
import MobileTabLink from '@/Components/MobileTabLink.vue';

const { hasPermission } = usePermission();
const form = useForm({});

const props = defineProps({
    action: String,
    message: Object,
    scholars: Array,
});


onMounted(() => {
    // console.log(props.voterprofiles);
    console.log(props);

    if (props.message.error) {
        alert(props.message.message)
    }
    // console.log(props.precincts);
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


            <Table class="border-collapse border border-slate-100 bg-[#eeeeee]" :loading="form.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="w-[10px]">#</TableHeaderCell>
                        <TableHeaderCell>Name</TableHeaderCell>

                        <TableHeaderCell>Program/Course</TableHeaderCell>
                        <TableHeaderCell>Term</TableHeaderCell>
                        <TableHeaderCell>Effectivity</TableHeaderCell>
                        <TableHeaderCell>Status</TableHeaderCell>
                        <TableHeaderCell>Remarks</TableHeaderCell>
                        <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                    </TableRow>
                </template>
                <template #default>
                    <TableRow class="hover:bg-gray-200" v-for="(scholar, index) in scholars" :key="'schl_' + index"
                        v-if="scholars">
                        <TableDataCell class="px-6 w-[10px] border-collapse border-t border-slate-400">{{ index + 1 }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                            <div>
                                {{ scholar.last_name + ', ' + scholar.first_name + ' ' + (scholar.middle_name || '') }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                            <p>{{ scholar.ongoing_scholarship_grant?.program_name }}</p> {{
                                scholar.ongoing_scholarship_grant?.course_name }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                            <p>{{ scholar.ongoing_scholarship_grant?.academic_year }} - {{
                                scholar.ongoing_scholarship_grant?.year_level }}</p>
                            {{ scholar.ongoing_scholarship_grant?.term }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                            {{ scholar.ongoing_scholarship_grant?.start_date }} to {{
                                scholar.ongoing_scholarship_grant?.end_date
                            }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-1">
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-l border-slate-400">
                            <div class="flex space-x-6 justify-center">
                                <!-- <Link v-if="hasPermission('edit voterprofile')" :href="route('votersprofile.showposition', {
                                    position: currentVoterPosition,
                                    action: 'edit',
                                    id: voter.id,
                                })
                                    " preserve-state preserve-scroll class="text-blue-400 hover:text-blue-600 flex">
                                <PencilSquareIcon class="h-5 w-5 text-blue-400" />
                                Edit
                                </Link>

                                <button v-if="hasPermission('delete voterprofile')"
                                    class="text-red-500 hover:text-red-600 flex"
                                    @click="confirmDeleteVoterProfile(voter.id, voter.name)">
                                    <TrashIcon class="h-5 w-5 text-red-400" />
                                    Delete
                                </button> -->
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="9">No data to be displayed</TableDataCell>
                    </TableRow>
                </template>
            </Table>

        </div>


        <!-- CREATE PROFILE MODAL -->
        <CreateModal v-if="props.action == 'create' && hasPermission('create-scholar-profile')"
            :action="props.action" />
    </AdminLayout>

</template>