<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
// import CreateModal from '@/Pages/Course/Modal/CreateModal.vue';
import RequirementModal from "./Modal/RequirementModal.vue";
import { ChevronUpDownIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    action: String,
    requirement: Object,
    requirements: Object
});

</script>

<template>

    <Head title="Courses" />

    <AdminLayout>
        <template #header>Manage Requirements</template>

        <div class="max-w-8xl mx-auto py-4">
            <div class="flex justify-end">
                <Link :href="route('program_requirements.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-emerald-500  text-white  ml-auto flex items-center justify-center uppercase">
                <SquaresPlusIcon class="h-4 w-4" />
                New Requirement</Link>

                <!-- {{ users }} -->
            </div>

            <div class="mt-6">
                <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]">
                    <template #header>
                        <TableRow>
                            <TableHeaderCell class="px-3">#</TableHeaderCell>
                            <TableHeaderCell @click="sortBy('name')" class="cursor-pointer">
                                <div class="flex items-center gap-2">
                                    <h4>Requirement</h4>
                                    <ChevronUpDownIcon class="h-4 w-4" />
                                </div>
                            </TableHeaderCell>

                            <TableHeaderCell>Description</TableHeaderCell>
                            <TableHeaderCell>Remarks</TableHeaderCell>
                            <TableHeaderCell>Status</TableHeaderCell>
                            <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow class="hover:bg-gray-200" v-for="(r, index) in requirements" :key="'r_' + r.id"
                            v-if="requirements?.length">
                            <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1
                            }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                {{ r.name }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                {{ r.description }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                {{ r.remarks }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                {{ r.is_active ? 'active' : 'inactive' }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">

                                <Link :href="route('program_requirements.index', {
                                    id: r.id,
                                    action: 'edit'
                                })" class="text-purple-500 hover:text-purple-600 underline font-medium">Edit</Link>
                            </TableDataCell>
                        </TableRow>
                        <TableRow v-else>
                            <TableDataCell
                                class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                                colspan="6">No data to be displayed</TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>

        <!-- CREATE SCHOLARSHIP PROGRAM MODAL -->
        <RequirementModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :requirement="props.requirement" />
    </AdminLayout>
</template>
