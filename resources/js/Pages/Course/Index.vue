<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import CreateModal from '@/Pages/Course/Modal/CreateModal.vue';
import { TrashIcon, PencilSquareIcon, IdentificationIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";
import { useStorage } from '@vueuse/core';

const props = defineProps({
    action: String,
    courses: Object,
    scholarshipPrograms: Object,
});

const gridview = useStorage('gridview', false);
const scholarshipProgramsOptions = ref([]);
onMounted(() => {
    // console.log(props.courses);
    scholarshipProgramsOptions.value = props.scholarshipPrograms.map(program => ({
        value: program.id,
        label: program.name
    }));
    console.log(props.courses);
});

</script>

<template>

    <Head title="Courses" />

    <AdminLayout>
        <template #header>Manage Course</template>

        <div class="max-w-8xl mx-auto py-4">
            <div class="flex justify-end">
                <Link :href="route('courses.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-emerald-500  text-white  ml-auto flex items-center justify-center uppercase">
                <SquaresPlusIcon class="h-4 w-4" />
                New Course</Link>

                <!-- {{ users }} -->
            </div>

            <div class="mt-6">
                <Table class="border-collapse border border-slate-400 bg-[#eeeeee]">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="-indent-1 w-[2%]">#</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[35%]">Course</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[35%]">Program</TableHeaderCell>
                            <TableHeaderCell class="-indent-2">Date Effectivity</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[10%]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(course, index) in courses" :key="course.id">
                            <TableDataCell class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                course.name }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                course.program }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-4">{{
                                course.start_date }} to {{ course.end_date }}</TableDataCell>
                            <TableDataCell class="space-x-6 border-collapse border-t border-l border-slate-400 px-4">
                                <Link :href="route('courses.edit', course.id)"
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
            </div>
        </div>

        <!-- CREATE SCHOLARSHIP PROGRAM MODAL -->
        <CreateModal v-if="props.action == 'create'" :action="props.action"
            :scholarshipProgramsOptions="scholarshipProgramsOptions" />
    </AdminLayout>
</template>
