<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import moment from "moment";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
// import CreateModal from '@/Pages/ScholarshipProgram/Modal/ProgramModal.vue';
import { TrashIcon, PencilSquareIcon, IdentificationIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";
import { useStorage } from '@vueuse/core';
import SchoolModal from "@/Pages/School/Modal/SchoolModal.vue";
// import { router } from '@inertiajs/vue3';

const props = defineProps({
    action: String,
    schools: Object,
    school: Object
});

onMounted(() => {
    // console.log(props.schools);
});

const editSchool = (schoolId) => {
    // Navigate to the edit school page
    router.get(route("school.index", {
        id: schoolId,
        action: 'edit'
    }))
};

const deleteSchool = (schoolId) => {
    if (confirm('Are you sure you want to delete this school?')) {
        router.delete(route('school.destroy', schoolId), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

</script>

<template>

    <Head title="Scholar Programs" />

    <AdminLayout>
        <template #header>Manage Schools</template>

        <div class="max-w-8xl mx-auto py-4">
            <div class="flex justify-end">
                <Link :href="route('school.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-emerald-500  text-white  ml-auto flex items-center justify-center uppercase">
                <SquaresPlusIcon class="h-4 w-4" />ADD SCHOOL</Link>

                <!-- {{ users }} -->
            </div>

            <div class="mt-6">



                <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]">
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
                        <TableRow v-for="(school, index) in schools" :key="school.id">
                            <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                <span class="font-medium"> {{ school.name }}</span>
                                <span class="font-bold"> [{{ school.shortname }}]</span>
                            </TableDataCell>
                            <!-- <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                                <span v-if="school.start_date && school.end_date">
                                    {{ school.start_date }} to {{ school.end_date }}
                                </span>
                            </TableDataCell> -->
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600"><span
                                    class="text-gray-600">{{
                                        school.start_date ? moment(school.start_date).format('MMM DD, YYYY') : ''
                                    }}</span>
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600"><span
                                    class="text-gray-600">{{
                                        school.start_date ? moment(school.end_date).format('MMM DD, YYYY') : ''
                                    }}</span>
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">{{
                                    school.remarks }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                {{ school.is_active ? 'active' : 'inactive' }}
                            </TableDataCell>
                            <TableDataCell class="space-x-2 border-collapse border-t border-l border-slate-400 px-4">
                                <Button icon="pi pi-pen-to-square" severity="info" variant="text" rounded
                                    aria-label="Edit" @click="editSchool(school.id)" />

                                <Button icon="pi pi-trash" severity="danger" variant="text" rounded aria-label="Delete"
                                    @click="deleteSchool(school.id)" />
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>


        <!-- CREATE/EDIT SCHOOL MODAL -->
        <SchoolModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :program="props.action === 'edit' ? props.school : null" />



    </AdminLayout>
</template>
