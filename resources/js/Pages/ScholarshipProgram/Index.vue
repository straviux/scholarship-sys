<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import moment from "moment";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import CreateModal from '@/Pages/ScholarshipProgram/Modal/ProgramModal.vue';
import { TrashIcon, PencilSquareIcon, IdentificationIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";
import { useStorage } from '@vueuse/core';
import ProgramModal from "@/Pages/ScholarshipProgram/Modal/ProgramModal.vue";
import RequirementModal from "./Modal/RequirementModal.vue";

const props = defineProps({
    action: String,
    scholarshipPrograms: Object,
    program: Object,
    requirements: Array
});

const gridview = useStorage('gridview', false);
const editProgram = (programId) => {
    // Navigate to the edit program page
    router.get(route("scholarshipprograms.index", {
        id: programId,
        action: 'edit'
    }))
};
onMounted(() => {
    console.log(props);
});

</script>

<template>

    <Head title="Scholar Programs" />

    <AdminLayout>
        <template #header>Manage Programs</template>

        <div class="max-w-8xl mx-auto py-4">
            <div class="flex justify-end">
                <Link :href="route('scholarshipprograms.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-emerald-500  text-white  ml-auto flex items-center justify-center uppercase">
                <SquaresPlusIcon class="h-4 w-4" />New Program</Link>

                <!-- {{ users }} -->
            </div>
            <div class="flex items-center gap-2 ml-auto px-2 mr-2">
                <label class="relative inline-flex cursor-pointer items-center">
                    <input id="switch" type="checkbox" class="peer sr-only" v-model="gridview" />
                    <label for="switch" class="hidden"></label>
                    <div
                        class="peer h-6 w-11 rounded-full border bg-slate-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-slate-800 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-green-300">
                    </div>
                </label>
                <p class="text-xs text-gray-600" v-if="gridview">Grid</p>
                <p class="text-xs text-gray-600" v-else>Table</p>
            </div>
            <div class="mt-6">



                <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]" v-if="!gridview">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="px-3">#</TableHeaderCell>
                            <TableHeaderCell>Program</TableHeaderCell>
                            <TableHeaderCell>From</TableHeaderCell>
                            <TableHeaderCell>To</TableHeaderCell>
                            <TableHeaderCell>Remarks</TableHeaderCell>
                            <TableHeaderCell>Status</TableHeaderCell>
                            <!-- <TableHeaderCell class="-indent-2">Status</TableHeaderCell> -->
                            <TableHeaderCell>Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(program, index) in scholarshipPrograms" :key="program.id">
                            <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                <span class="font-medium"> {{ program.name }}</span>
                                <span class="font-bold"> [{{ program.shortname }}]</span>
                            </TableDataCell>
                            <!-- <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                <span v-if="program.start_date && program.end_date">
                                    {{ program.start_date }} to {{ program.end_date }}
                                </span>
                            </TableDataCell> -->
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600"><span
                                    class="text-gray-600">{{
                                        program.start_date ? moment(program.start_date).format('MMM DD, YYYY') : ''
                                    }}</span>
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600"><span
                                    class="text-gray-600">{{
                                        program.start_date ? moment(program.end_date).format('MMM DD, YYYY') : ''
                                    }}</span>
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">{{
                                    program.remarks }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                {{ program.is_active ? 'active' : 'inactive' }}
                            </TableDataCell>
                            <TableDataCell class="space-x-6 border-collapse border-t border-l border-slate-400 px-4">
                                <!-- <Link :href="route('scholarshipprograms.index', {
                                    id: program.id,
                                    action: 'edit'
                                })" class="text-purple-500 hover:text-purple-600 underline font-medium">Edit</Link> -->


                                <Button icon="pi pi-pen-to-square" severity="info" variant="text" rounded
                                    aria-label="Edit" @click="editProgram(program.id)" />
                                <Link :href="route('scholarshipprograms.index', {
                                    id: program.id,
                                    action: 'update-requirement'
                                })" class="text-blue-500 hover:text-blue-600 underline  font-medium">Requirement</Link>

                                <!-- <button class="text-red-500 hover:text-red-600" @click="
                                    confirmDeleteProgram(
                                        user.id,
                                        user.name,
                                        user.username
                                    )
                                    ">
                                    Delete
                                </button> -->
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>

                <ul role="list" class="grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-2" v-else>
                    <li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow-sm border border-gray-300 transition hover:-translate-y-2 duration-150 hover:shadow-lg"
                        v-for="(program, index) in scholarshipPrograms" :key="'program_list_' + program.id">
                        <div class="flex w-full items-start justify-between space-x-6 px-2 py-2.5 min-h-40">
                            <p class="text-gray-400 text-xs absolute mt-1">#{{ index + 1 }}</p>
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <p class="text-lg font-medium text-gray-600">
                                        {{ program.name }}
                                    </p>
                                </div>
                                <p class="mt-4 font-semibold text-[12px] text-gray-500">
                                    DATE EFFECTIVITY: {{ program.start_date }} to {{ program.end_date }}
                                </p>
                                <p class="mt-2 font-semibold text-[12px] text-gray-500">
                                    # of Scholars: 232
                                </p>
                            </div>

                        </div>

                        <div>
                            <div class="-mt-px flex divide-x divide-gray-200">
                                <div class="flex w-0 flex-1 text-red-400 hover:text-red-600">
                                    <button
                                        class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-bl-lg border border-transparent py-4 text-xs font-semibold"
                                        @click="confirmDeleteVoterProfile(program.id, program.name)">
                                        <TrashIcon class="h-5 w-5 text-red-400" />
                                        Delete
                                    </button>
                                </div>
                                <div class="flex w-0 flex-1 text-blue-400 hover:text-blue-600">
                                    <Link :href="route('scholarship_records.index')"
                                        class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-bl-lg border border-transparent py-4 text-xs font-semibold"
                                        preserve-state preserve-scroll>
                                    <PencilSquareIcon class="h-5 w-5" />
                                    Edit
                                    </Link>
                                </div>

                                <div class="-ml-px flex w-0 flex-1 text-purple-500 hover:text-purple-600">
                                    <Link :href="route('scholarship_records.index')"
                                        class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-br-lg border border-transparent py-4 text-xs font-semibold">
                                    <IdentificationIcon class="h-5 w-5" />
                                    Courses Offered
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- CREATE SCHOLARSHIP PROGRAM MODAL -->
        <ProgramModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :program="props.program" />

        <RequirementModal v-if="props.action == 'update-requirement'" :action="props.action" :program="props.program"
            :requirements="props.requirements" />


    </AdminLayout>
</template>
