<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import moment from 'moment'
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { usePermission } from '@/composable/permissions';
import Table from '@/Components/Table.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import {
    UserPlusIcon, ChevronUpDownIcon
} from '@heroicons/vue/20/solid';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import Pagination from '@/Components/Pagination.vue';
import ScholarshipModal from './Modal/ScholarshipModal.vue';
// import ProfileModal from '@/Pages/ScholarshipProfile/Modal/ProfileModal.vue';

const { hasPermission } = usePermission();


const props = defineProps({
    records: Object, // scholarship records
    record: Object, // single scholarship record for view/edit
    action: String,
    per_page: Number,
    filter: Object,
    message: Object,
    sort: {
        last_name: { type: String },
    },
});

console.log(props.record)
const filter = useForm({
    course: props.filter?.course || "",
    municipality: props.filter?.municipality || "",
    name: props.filter?.name || "",
    per_page: props.per_page || 10,
    sort: {},
});

const searchInput = ref(null);

const filterList = () => {
    // Prepare filter values
    const course = filter.course?.name || "";
    const municipality = filter.municipality?.name || "";
    const name = filter.name || "";
    const per_page = filter.per_page;
    const sort = filter.sort;

    // Send request to waiting list API
    // You can add per_page or other params as needed
    const params = {
        // applied_course,
        course,
        municipality,
        name,
        per_page, sort
    };
    // Use Inertia visit to update the page with filtered results
    // The route name should match your web.php
    // This will update the profiles prop with paginated results
    router.get(route('scholarship_records.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const clearFilter = () => {
    filter.reset();
}




const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
}

const statusClass = (status) => {
    return {
        1: 'bg-green-600 text-white',
        2: 'bg-blue-600 text-white',
        3: 'bg-red-600 text-white',
        4: 'bg-red-600 text-white',
    }[status] || 'bg-orange-600 text-white'
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    // console.log(props.profiles.data)
});


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});


watch(filter, debounce(
    () =>
        filterList(),
    500
))


</script>
<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Scholar Profile Records</template>

        <div class="p-4">
            <div
                class="text-normal font-medium text-center text-gray-500  border-gray-200 flex items-center mb-4 gap-4">
                <div class="flex shadow-xs">
                    <div class="bg-gray-700 rounded-l-lg text-white p-4">Show</div>
                    <select v-model="filter.per_page" class="w-[60px] border rounded-r-lg cursor-pointer text-center">
                        <option value="200">200</option>
                        <option value="100">100</option>
                        <option value="50">50</option>
                        <option value="25">25</option>
                        <option value="10">10</option>
                        <option value="5">5</option>
                    </select>

                </div>

                <div class="w-2/3 flex shadow-xs">
                    <div class="bg-gray-700 rounded-l-lg text-white p-4">Filter</div>
                    <div class="px-4 border  bg-gray-50 flex flex-1 items-center gap-2">

                        <div class="w-1/3">
                            <CourseSelect v-model="filter.course" :scholarship-program-id="''" label="shortname" />
                        </div>
                        <div class="w-1/3">
                            <MunicipalitySelect v-model="filter.municipality" />
                        </div>
                        <div class="w-1/3">
                            <label
                                class="input focus-within:outline-none focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 flex items-center gap-2">

                                <input type="search" class="grow uppercase" placeholder="Search name"
                                    v-model="filter.name" ref="searchInput" />
                                <kbd class="kbd kbd-sm">ctrl</kbd>
                                <kbd class="kbd kbd-sm">K</kbd>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-r-lg border flex items-center cursor-pointer"
                        @click="clearFilter" as="button">
                        <button class="cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]" :loading="filter.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="px-3">#</TableHeaderCell>
                        <TableHeaderCell @click="sortBy('name')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>Name</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell>Program</TableHeaderCell>
                        <TableHeaderCell>Course</TableHeaderCell>
                        <TableHeaderCell>School</TableHeaderCell>
                        <TableHeaderCell>Academic Info</TableHeaderCell>
                        <TableHeaderCell>Status</TableHeaderCell>
                        <TableHeaderCell @click="sortBy('date_filed')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>Date Filed</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                    </TableRow>
                </template>
                <template #default>
                    <TableRow class="hover:bg-gray-200" v-for="(record, index) in records.data"
                        :key="'record' + record.id" v-if="records.data?.length > 0">
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1 }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <div>
                                {{ record.profile.last_name + ', ' + record.profile.first_name + ' ' +
                                    (record.profile.middle_name ||
                                        '') + ' ' + (record.profile.extension_name || '') }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ record.program.shortname }}
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ record.course.shortname }}
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700 uppercase">
                            {{ record.school_name }}
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700 uppercase">


                            <div class="flex gap-2">
                                <div class="badge badge-neutral badge-outline badge-sm">{{ record.academic_year }}</div>
                                <div class="badge badge-neutral badge-outline badge-sm">{{ record.term }}</div>
                                <div class="badge badge-neutral badge-outline badge-sm">{{ record.year_level }}</div>
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <span :class="statusClass(record.scholarship_status)" class="p-1 rounded text-xs">{{
                                record.scholarship_status_remarks }}</span>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ record.date_filed ? moment(record.date_filed).format('MMM DD, YYYY') : '' }}
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="flex space-x-6 justify-center">
                                <Link :href="route('scholarship_records.index', { id: record.id, action: 'view' })"
                                    class="text-gray-500 hover:text-gray-600 underline font-medium">
                                <i class="pi pi-search"></i></Link>
                                <Link :href="route('scholarship_records.index', { id: record.id, action: 'edit' })"
                                    class="text-purple-500 hover:text-purple-600 underline font-medium">
                                <i class="pi pi-pen-to-square"></i></Link>
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="9">No data to be displayed</TableDataCell>
                    </TableRow>
                </template>
            </Table>
            <Pagination :links="props.records?.links" class="mt-8" />
        </div>
        <ScholarshipModal v-if="props.action == 'edit'" :action="'open'" :record="props.record" />
    </AdminLayout>

</template>