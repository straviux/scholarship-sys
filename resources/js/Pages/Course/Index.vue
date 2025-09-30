<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import { ChevronUpDownIcon, SquaresPlusIcon } from "@heroicons/vue/20/solid";
import { useStorage } from '@vueuse/core';
import CourseModal from "@/Pages/Course/Modal/CourseModal.vue";
import { router } from '@inertiajs/vue3';

const props = defineProps({
    action: String,
    courses: Object,
    course: Object,
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
    // console.log(props.courses);
});

const editCourse = (courseId) => {
    // Navigate to the edit course page
    router.get(route("courses.index", {
        id: courseId,
        action: 'edit'
    }))
};
const deleteCourse = (courseId) => {
    if (confirm('Are you sure you want to delete this course?')) {
        router.delete(route('courses.destroy', courseId), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

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
                <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]">
                    <template #header>
                        <TableRow>
                            <TableHeaderCell class="px-3">#</TableHeaderCell>
                            <TableHeaderCell @click="sortBy('name')" class="cursor-pointer">
                                <div class="flex items-center gap-2">
                                    <h4>Course</h4>
                                    <ChevronUpDownIcon class="h-4 w-4" />
                                </div>
                            </TableHeaderCell>

                            <TableHeaderCell>Program</TableHeaderCell>
                            <TableHeaderCell>Remarks</TableHeaderCell>
                            <TableHeaderCell>Status</TableHeaderCell>
                            <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow class="hover:bg-gray-200" v-for="(c, index) in courses" :key="'r_' + c.id"
                            v-if="courses?.length">
                            <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1
                                }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                <span class="font-medium"> {{ c.name }}</span>
                                <span class="font-bold"> [{{ c.shortname }}]</span>

                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600 font-medium">
                                {{ c.program }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                {{ c.remarks }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600">
                                {{ c.is_active ? 'active' : 'inactive' }}
                            </TableDataCell>
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-600 space-x-2">
                                <!-- <Link :href="route('courses.index', {
                                    id: c.id,
                                    action: 'edit'
                                })" class="text-purple-500 hover:text-purple-600 underline font-medium">Edit</Link> -->

                                <Button icon="pi pi-pen-to-square" severity="info" variant="text" rounded
                                    aria-label="Edit" @click="editCourse(c.id)" />
                                <Button icon="pi pi-trash" severity="danger" variant="text" rounded aria-label="Delete"
                                    @click="deleteCourse(c.id)" />
                                <!-- <button
                                    class="ml-2 text-red-500 hover:text-red-700 underline font-medium cursor-pointer"
                                    @click="deleteCourse(c.id)">Delete</button> -->
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
        <CourseModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :course="props.course" :scholarshipProgramsOptions="scholarshipProgramsOptions" />
    </AdminLayout>
</template>
