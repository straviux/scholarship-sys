<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import CreateModal from '@/Pages/ScholarshipProgram/Modal/CreateModal.vue';
import { TrashIcon, PencilSquareIcon, IdentificationIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";
import { useStorage } from '@vueuse/core';

const props = defineProps({
    action: String,
    scholarshipPrograms: Object,
});

const gridview = useStorage('gridview', false);
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



                <Table class="border-collapse border border-slate-400 bg-[#eeeeee]" v-if="!gridview">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="-indent-1 w-[2%]">#</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[35%]">Program</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[35%]">Description</TableHeaderCell>
                            <TableHeaderCell class="-indent-2">Date Effectivity</TableHeaderCell>
                            <!-- <TableHeaderCell class="-indent-2">Status</TableHeaderCell> -->
                            <TableHeaderCell class="-indent-2 w-[10%]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(program, index) in scholarshipPrograms" :key="program.id">
                            <TableDataCell class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                program.name }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                program.description }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                program.start_date }} to {{ program.end_date }}</TableDataCell>
                            <TableDataCell class="space-x-6 border-collapse border-t border-l border-slate-400 px-4">
                                <Link :href="route('scholarshipprograms.edit', program.id)"
                                    class="text-green-500 hover:text-green-600">
                                Edit</Link>

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
                    <li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow border border-gray-300 transition hover:-translate-y-2 duration-150 hover:shadow-lg"
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
                                    <Link :href="route('scholars.index')"
                                        class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-bl-lg border border-transparent py-4 text-xs font-semibold"
                                        preserve-state preserve-scroll>
                                    <PencilSquareIcon class="h-5 w-5" />
                                    Edit
                                    </Link>
                                </div>

                                <div class="-ml-px flex w-0 flex-1 text-purple-500 hover:text-purple-600">
                                    <Link :href="route('scholars.index')"
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
        <CreateModal v-if="props.action == 'create'" :action="props.action" />
    </AdminLayout>
</template>
