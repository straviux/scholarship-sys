<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import moment from 'moment'
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import Table from '@/Components/Table.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import { ChevronUpDownIcon } from '@heroicons/vue/20/solid';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import Pagination from '@/Components/Pagination.vue';
import ScholarshipModal from './Modal/ScholarshipModal.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';

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

// console.log(props.records)
const filter = useForm({
    course: props.filter?.course || "",
    municipality: props.filter?.municipality || "",
    name: props.filter?.name || "",
    per_page: props.per_page || 10,
    sort: {},
    show_all_status: false, // default: do not show pending
});

const searchInput = ref(null);

const filterList = () => {
    // Prepare filter values
    const course = filter.course?.name || "";
    const municipality = filter.municipality?.name || "";
    const name = filter.name || "";
    const per_page = filter.per_page;
    const sort = filter.sort;
    const show_all_status = filter.show_all_status;

    // Persist show_all_status on filter and pagination
    const params = {
        course,
        municipality,
        name,
        per_page,
        sort,
        show_all_status,
        // page: props.records?.meta?.current_page || 1
    };
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
        1: 'bg-green-200 text-green-700 font-bold capitalize',
        2: 'bg-blue-200 text-blue-700 font-bold capitalize',
        3: 'bg-red-200 text-red-700 font-bold capitalize',
        4: 'bg-red-200 text-red-700 font-bold capitalize',
        5: 'bg-red-200 text-red-700 font-bold capitalize',
    }[status] || 'bg-orange-600 text-white'
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    // console.log(props.profiles.data)
});


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});


// watch(filter, debounce(
//     () =>
//         filterList(),
//     500
// ))

const deleteRecord = (record) => {
    if (!record || !record.id) return;
    if (confirm('Are you sure you want to delete this scholarship record?')) {
        router.delete(route('scholarship_records.destroy', record.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Optionally show a toast here
            },
            onError: () => {
                // Optionally show a toast here
            }
        });
    }
};

</script>
<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Scholar Profile Records</template>



        <div class="p-4">
            <div
                class="text-normal font-medium text-center text-gray-500  border-gray-200 flex justify-between items-center mb-4 gap-4">
                <!-- <h1>Welcome {{ $page.props.auth.user.name }}</h1> -->
                <!-- <h3>{{ props }}</h3> -->
                <!-- {{ props.action }} -->
                <div class="flex gap-2 flex-1 items-center">
                    <div class="flex shadow-xs">
                        <div class="bg-gray-700 rounded-l-lg text-white p-2">Show</div>
                        <select v-model="filter.per_page"
                            class="w-[60px] rounded-r-lg border cursor-pointer text-center">
                            <option value="500">500</option>
                            <option value="200">200</option>
                            <option value="100">100</option>
                            <option value="50">50</option>
                            <option value="25">25</option>
                            <option value="10">10</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <!-- <div class="text-gray-500 p-2">of {{ profiles_total }} result(s)</div> -->
                    <Divider layout="vertical" />
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2">
                            <p>Program</p>
                            <ProgramSelect v-model="filter.program" label="shortname" custom-placeholder="------" />
                        </div>
                        <div class="flex items-center gap-2">
                            <p>Date Filed</p>
                            <DatePicker v-model="filter.date_from" :manualInput="true" showButtonBar
                                @clear-click="filter.date_from = null" />
                        </div>
                        <div class="flex items-center gap-2">
                            <p>To</p>
                            <DatePicker v-model="filter.date_to" :manualInput="true" showButtonBar
                                @clear-click="filter.date_to = null" />
                        </div>
                    </div>


                </div>
                <!-- <GenerateReportModal v-model:show="showReportModal" /> -->
                <div class="flex justify-end gap-4">
                    <!-- <Button label="Generate Report" icon="pi pi-print" severity="info" raised size="small"
                        @click="openReportModal" /> -->

                </div>
            </div>


            <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="px-3 text-center">
                            <p class="text-[10px] text-white">#</p>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('name')" class="cursor-pointer w-80">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-white">Name</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell class="w-[160px]">
                            <p class="text-[10px] text-white">Status</p>
                        </TableHeaderCell>
                        <TableHeaderCell class="w-[200px]">
                            <p class="text-[10px] text-white">Program</p>
                        </TableHeaderCell>
                        <TableHeaderCell class="w-[200px]">
                            <p class="text-[10px] text-white">Course</p>
                        </TableHeaderCell>

                        <TableHeaderCell @click="sortBy('school')" class="cursor-pointer">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-white">School</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('course')" class="cursor-pointer">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-white">Year Level</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('year_level')" class="cursor-pointer w-[30px]">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-white">Academic Year</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('date_filed')" class="cursor-pointer w-[110px]">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-white">Date Approved</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>

                        <TableHeaderCell class="w-[160px]">
                            <p class="text-[10px] text-white">Action</p>
                        </TableHeaderCell>
                    </TableRow>


                </template>
                <template #default>
                    <!-- filter row -->
                    <TableRow>
                        <TableDataCell class="px-3 border-l border-collapse border-slate-400"><span
                                class="text-[10px] "></span>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <InputText v-model="filter.name" placeholder="Search name" class="w-full"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <!-- <InputText v-model="filter.name" placeholder="Search name" class="w-full" /> -->
                                <ProgramSelect v-model="filter.course" :scholarship-program-id="filter.program?.id"
                                    custom-placeholder="---" label="shortname" size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <CourseSelect v-model="filter.course" :scholarship-program-id="filter.program?.id"
                                    custom-placeholder="---" label="shortname" size="small" />
                            </div>
                        </TableDataCell>

                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="---"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <YearLevelSelect v-model="filter.year_level" label="shortname" custom-placeholder="---"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <AcademicYearSelect v-model="filter.academic_year" label="shortname"
                                    custom-placeholder="---" size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400"></TableDataCell>


                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <div class="bg-gray-600 text-gray-200 p-2 rounded-lg border flex items-center cursor-pointer"
                                    @click="clearFilter" as="button">
                                    <button class="cursor-pointer">
                                        Clear Filter
                                    </button>
                                </div>
                            </div>
                        </TableDataCell>
                    </TableRow>

                    <TableRow class="hover:bg-gray-200" v-for="(record, index) in records.data"
                        :key="'record_' + record.id" v-if="records.data && records.data.length">

                        <TableDataCell
                            class="px-3 w-[10px] border-collapse border-t border-l border-slate-400 text-gray-500">{{
                                index + 1 }}
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="flex items-center gap-2 px-2">
                                <figure>
                                    <img v-if="record.profile.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                        class="rounded-xl w-[28px]" />

                                    <img v-if="record.profile.gender == 'F'" src="/images/female-avatar.png"
                                        alt="avatar" class="rounded-xl w-[28px]" />
                                </figure>
                                <div class="text-[11px] font-semibold">
                                    {{ record.profile.last_name + ', ' + record.profile.first_name + ' ' +
                                        (record.profile.middle_name ||
                                            '') + ' ' + (record.profile.extension_name || '') }}
                                </div>
                            </div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2">

                                <span :class="statusClass(record.scholarship_status)"
                                    class="px-2 py-1 rounded-lg text-[10px]">{{
                                        record.scholarship_status_remarks }}</span>
                            </div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase w-[200px]">
                            <div class="px-2 text-[11px] font-semibold">{{ record.program?.shortname }}</div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold">{{ record.course?.name }}</div>
                        </TableDataCell>

                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold">{{ record.school?.name }}</div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold">{{ record.year_level }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="px-2 text-[11px] font-semibold">{{ record.academic_year }}
                            </div>
                        </TableDataCell>

                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold">
                                {{ record.date_approved ? moment(record.date_approved).format('MMM DD, YYYY')
                                    : '-' }}</div>
                        </TableDataCell>


                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="flex gap-4 justify-center">
                                <Link :href="route('scholarship_records.index', { id: record.id, action: 'view' })"
                                    class="text-gray-500 hover:text-gray-600 underline font-medium">
                                <i class="pi pi-search"></i></Link>
                                <button class="text-red-500 hover:text-red-700 flex cursor-pointer"
                                    @click="deleteRecord(record)">
                                    <i class="pi pi-trash"></i>
                                </button>
                            </div>
                        </TableDataCell>

                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="14">
                            No data
                            to be displayed</TableDataCell>
                    </TableRow>

                </template>
            </Table>
            <Pagination :links="props.records?.links" class="mt-8" />
        </div>
        <ScholarshipModal v-if="props.action == 'edit'" :action="'open'" :record="props.record" />


    </AdminLayout>

</template>