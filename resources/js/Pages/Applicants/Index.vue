<script setup>

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import moment from 'moment'
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { usePermission } from '@/composable/permissions';
import Table from '@/Components/Table.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import {
    ChevronUpDownIcon
} from '@heroicons/vue/20/solid';
import ApplicantProfileModal from '@/Pages/Applicants/Modal/ApplicantProfileModal.vue';
import ViewProfileModal from './Modal/ViewProfileModal.vue';
import GenerateReportModal from './Modal/GenerateReportModal.vue';
const showReportModal = ref(false);
const openReportModal = () => { showReportModal.value = true; };

// COURSE MULTISELECT COMPONENT
// How to use: 1. import component, 2. define model, 3. define scholarshipProgramId (set to null if fetching all course)
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import Pagination from '@/Components/Pagination.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const { hasPermission } = usePermission();
// import { usePage } from '@inertiajs/vue3'
// const page = usePage()
// console.log(route)
const props = defineProps({
    profile: Object,
    profiles: Object,
    profiles_total: [String, Number],
    action: String,
    records: Number,
    filter: Object,
    message: Object,
    sort: {
        date_filed: { type: String },
        last_name: { type: String },
        course: { type: String },
        applied_year_level: { type: String },
    },

});

const form = useForm({
    course: props.filter.course || "",
    municipality: props.filter.municipality || "",
    name: props.filter.name || "",
    records: props.records || 10,
    sort: {
        date_filed: props.sort.date_filed || "",
        last_name: props.sort.last_name || "",
        school: props.sort.school || "",
        course: props.sort.course || "",
        year_level: props.sort.year_level || "",
    },

});

const toDate = (val) => val ? new Date(val) : null;
const filter = useForm({
    records: props.records || 10,
    name: props.filter.name || "",
    parent_name: props.filter.parent_name || "",
    program: props.filter.program || "",
    school: props.filter.school || "",
    course: props.filter.course || "",
    municipality: props.filter.municipality || "",
    year_level: props.filter.year_level || "",
    date_from: props.filter.date_from ? toDate(props.filter.date_from) : null,
    date_to: props.filter.date_to ? toDate(props.filter.date_to) : null,
    remarks: props.filter.remarks || "",
})

// console.log(props.filter)

const searchInput = ref(null);
const selectedProfile = ref({});
const isViewProfileOpen = ref(false);
const viewProfile = (profile) => {
    selectedProfile.value = profile;
    isViewProfileOpen.value = true;
}
const closeViewProfile = () => {
    isViewProfileOpen.value = false;
}


const filterList = () => {
    // Prepare filter values
    const program = filter.program?.shortname?.toLowerCase() || "";
    const parent_name = filter.parent_name.toLowerCase() || "";
    const course = filter.course?.shortname?.toLowerCase() || "";
    const municipality = filter.municipality?.name?.toLowerCase() || "";
    const name = filter.name.toLowerCase() || "";
    const school = filter.school?.shortname?.toLowerCase() || "";
    const year_level = filter.year_level?.value?.toLowerCase() || "";
    const remarks = filter.remarks.toLowerCase() || "";
    const records = filter.records;
    const sort = form.sort;

    // Use date_from and date_to directly
    let date_from = filter.date_from ? moment(filter.date_from).format('YYYY-MM-DD') : "";
    let date_to = filter.date_to ? moment(filter.date_to).format('YYYY-MM-DD') : "";

    // Preserve current page if available
    let currentPage = (props.profiles && props.profiles.meta && props.profiles.meta.current_page) ? props.profiles.meta.current_page : 1;

    const params = {};
    if (program) params.program = program;
    if (course) params.course = course;
    if (municipality) params.municipality = municipality;
    if (name) params.name = name;
    if (parent_name) params.parent_name = parent_name;
    if (school) params.school = school;
    if (year_level) params.year_level = year_level;
    if (date_from) params.date_from = date_from;
    if (date_to) params.date_to = date_to;
    if (remarks) params.remarks = remarks;
    if (records) params.records = records;
    if (sort && Object.values(sort).some(v => v)) params.sort = sort;
    params.page = currentPage;
    router.get(route('profile.waitinglist'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const clearFilter = () => {
    filter.name = null;
    filter.parent_name = null;
    filter.program = null;
    filter.school = null;
    filter.course = null;
    filter.municipality = null;
    filter.year_level = null;
    filter.remarks = null;
    filter.date_from = null;
    filter.date_to = null;
    filter.records = 10;
    // Clear URL params by reloading the page with no query params
    router.get(route('profile.waitinglist'), {}, {
        replace: true,
        preserveScroll: true,
    });
}
const sortBy = (column) => {
    if (column == 'name') {
        form.sort.last_name = form.sort.last_name == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'date_filed') {
        form.sort.date_filed = form.sort.date_filed == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'school') {
        form.sort.school = form.sort.school == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'course') {
        form.sort.course = form.sort.course == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'year_level') {
        form.sort.year_level = form.sort.year_level == 'desc' ? 'asc' : 'desc';
    }
}

const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
}


const userEncodedCount = ref({ total: 0, today: 0 });

onMounted(async () => {
    window.addEventListener('keydown', handleKeydown);
    // Fetch user encoded records count
    try {
        const res = await fetch(route('profile.getuserencodedrecords'));
        if (res.ok) {
            userEncodedCount.value = await res.json();
        }
    } catch (e) {
        userEncodedCount.value = { total: 0, today: 0 };
    }
    // console.log(props.profiles.data)
});


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Only trigger filterList from filter changes, not both form and filter
let filterListTimeout = null;
watch(filter, () => {
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        filterList();
        filterListTimeout = null;
    }, 500);
});

const showDeleteConfirm = ref(false);
const showAddRemarksConfirm = ref(false);

const deleteProfile = (profile) => {
    selectedProfile.value = profile;
    showDeleteConfirm.value = true;
};

const addRemarks = (profile) => {
    selectedProfile.value = profile;
    showAddRemarksConfirm.value = true;
};

const confirmDelete = () => {
    if (!selectedProfile.value) return;
    router.delete(route('profile.destroy', selectedProfile.value.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            selectedProfile.value = null;
            // Optionally show a toast here
        },
        onError: () => {
            showDeleteConfirm.value = false;
            selectedProfile.value = null;
        }
    });
};

const confirmAddRemarks = () => {
    if (!selectedProfile.value) return;
    router.put(route('applicants.updateJpmRemarks', selectedProfile.value.profile_id), { jpm_remarks: selectedProfile.value.jpm_remarks }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showAddRemarksConfirm.value = false;
            selectedProfile.value = null;
            toast.success('JPM remarks updated successfully');
            // Optionally show a toast here
        },
        onError: () => {
            showAddRemarksConfirm.value = false;
            selectedProfile.value = null;
        }
    });
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    selectedProfile.value = null;
};

const cancelAddRemarks = () => {
    showAddRemarksConfirm.value = false;
    selectedProfile.value = null;
};


// Inline JPM status update
const updateJpmStatus = ({ id = null, is_jpm_member = null, is_father_jpm = null, is_mother_jpm = null, is_guardian_jpm = null }) => {
    // Only send fields that are not null
    const payload = {};
    if (is_jpm_member !== null) payload.is_jpm_member = is_jpm_member;
    if (is_father_jpm !== null) payload.is_father_jpm = is_father_jpm;
    if (is_mother_jpm !== null) payload.is_mother_jpm = is_mother_jpm;
    if (is_guardian_jpm !== null) payload.is_guardian_jpm = is_guardian_jpm;
    // router.put(route('applicants.updateJpmStatus', payload), {
    //     preserveState: true,
    //     preserveScroll: true,
    //     replace: false,
    //     onSuccess: page => { console.log(page) },
    // });
    router.put(route('applicants.updateJpmStatus', id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { toast.success('status updated successfully'); },
    });

};


// Persist showJpmColumns state in localStorage
const showJpmColumns = ref(false);
onMounted(() => {
    const stored = localStorage.getItem('showJpmColumns');
    if (stored !== null) {
        showJpmColumns.value = stored === 'true';
    }
});
watch(showJpmColumns, (val) => {
    localStorage.setItem('showJpmColumns', val ? 'true' : 'false');
});

</script>
<template>

    <Head title="Applicants" />

    <AdminLayout>
        <template #header> Applicant Records</template>

        <div class="p-4">
            <div
                class="text-normal font-medium text-center text-gray-500  border-gray-200 flex justify-between items-center mb-4 gap-4">
                <!-- <h1>Welcome {{ $page.props.auth.user.name }}</h1> -->
                <!-- <h3>{{ props }}</h3> -->
                <!-- {{ props.action }} -->

                <div class="flex gap-2 flex-1 items-center">
                    <div class="flex shadow-xs">
                        <div class="bg-gray-700 rounded-l-lg text-white p-2">Show</div>
                        <select v-model="filter.records"
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

                    <Divider layout="vertical" />
                    <div class="flex gap-4 items-center">
                        <div class="flex items-center gap-2">
                            <p>Program</p>
                            <ProgramSelect v-model="filter.program" label="shortname"
                                custom-placeholder="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
                                size="small" />
                        </div>
                        <p>Date Filed</p>

                        <div class="flex items-center gap-2 w-32">

                            <FloatLabel variant="on">
                                <DatePicker v-model="filter.date_from" :manualInput="true" showButtonBar
                                    @clear-click="filter.date_from = null" id="filter_from_date" size="small" />
                                <label for="filter_from_date">From</label>
                            </FloatLabel>
                        </div>
                        <div class="flex items-center gap-2 w-32">
                            <FloatLabel variant="on">
                                <DatePicker v-model="filter.date_to" :manualInput="true" showButtonBar
                                    @clear-click="filter.date_to = null" id="filter_to_date" size="small" />
                                <label for="filter_to_date">To</label>
                            </FloatLabel>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <Toolbar>
                        <template #start>
                            <!-- <InputText ref="searchInput" v-model="filter.name" placeholder="Search keyword" class="w-48"
                                size="small" /> -->
                        </template>
                        <template #end>
                            <div class="space-x-2">
                                <Button as="a" label="New" icon="pi pi-user-plus"
                                    v-if="hasPermission('create-scholar-profile')" severity="success" :href="route('profile.waitinglist', {
                                        action: 'create'
                                    })" raised size="small" />
                                <Button as="a" label="Existing" icon="pi pi-user"
                                    v-if="hasPermission('create-scholar-profile')" :href="route('profile.waitinglist', {
                                        action: 'add-existing'
                                    })" size="small" severity="secondary" />

                                <Button label="Export" icon="pi pi-upload" severity="secondary"
                                    v-if="hasPermission('create-scholar-profile')" size="small"
                                    @click="openReportModal" />
                            </div>
                        </template>
                    </Toolbar>
                </div>
            </div>


            <div class="flex gap-4 justify-between items-center mb-2 bg-gray-100 p-2 rounded">
                <div class="text-gray-500 p-2 font-semibold">{{ profiles_total }} record(s) found</div>
                <div class="flex gap-4 text-sm">
                    <div class="text-normal text-gray-500 flex gap-2">Encoder: <p
                            class="font-bold font-mono text-emerald-600 capitalize">{{
                                userEncodedCount.name
                            }}</p>
                    </div>-
                    <div class="text-normal text-gray-500 flex gap-2">Today: <p
                            class="font-bold font-mono text-purple-600">
                            {{
                                userEncodedCount.today }}</p>
                    </div>-
                    <div class="text-normal text-gray-500 flex gap-2">Total: <p
                            class="font-bold font-mono text-purple-600">
                            {{
                                userEncodedCount.total }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-2" v-if="hasPermission('can-view-jpm')">
                <button class="px-3 py-1 rounded bg-gray-700 text-white text-xs cursor-pointer"
                    @click="showJpmColumns = !showJpmColumns">
                    {{ showJpmColumns ? 'Hide JPM' : 'Show JPM' }}
                </button>
            </div>

            <Table class="border-collapse border border-slate-100 bg-[#f1f1f1]" :loading="form.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell
                            class="px-3 text-center bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600"
                            colspan="2">
                            <p class="text-[11px]">Sequence</p>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('name')"
                            class="cursor-pointer w-80 bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px]">Applicant Name</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell
                            class="w-[200px] bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <p class="text-[11px]">Parent/Guardian</p>
                        </TableHeaderCell>
                        <TableHeaderCell
                            class="w-[200px] bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <p class="text-[11px]">Address</p>
                        </TableHeaderCell>

                        <TableHeaderCell @click="sortBy('school')"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px]">School</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('course')"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px]">Course</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('year_level')"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600 w-[30px]">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px]">Year Level</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <p class="text-[11px]">Contact #</p>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('date_filed')"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600 w-[110px]">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px]">Date Filed</p>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600 w-[200px]">
                            <p class="text-[11px]">Remarks</p>
                        </TableHeaderCell>
                        <TableHeaderCell v-if="hasPermission('can-view-jpm') && showJpmColumns"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600">
                            <p class="text-[11px]">JPM MEMBER</p>
                        </TableHeaderCell>
                        <TableHeaderCell v-if="hasPermission('can-view-jpm') && showJpmColumns"
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600 w-[200px]">
                            <p class="text-[11px]">JPM Remarks</p>
                        </TableHeaderCell>
                        <!-- <TableHeaderCell class="w-[160px]">Status</TableHeaderCell> -->
                        <TableHeaderCell
                            class="cursor-pointer bg-[#f8f8f8] dark:bg-[#f8f8f8] text-gray-600 dark:text-gray-600 w-[160px]">
                            <p class="text-[11px]">Action</p>
                        </TableHeaderCell>
                    </TableRow>


                </template>
                <template #default>
                    <!-- filter row -->
                    <TableRow class="bg-white">
                        <TableDataCell class="px-3"><span class="text-[10px] text-gray-500">#</span>
                        </TableDataCell>
                        <TableDataCell class="px-3 border-collapse border-slate-400"><span
                                class="text-[10px] text-gray-500">By Date</span></TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <InputText v-model="filter.name" placeholder="Search name" class="w-full"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <!-- <InputText v-model="filter.name" placeholder="Search name" class="w-full" /> -->
                                <InputText v-model="filter.parent_name" placeholder="Search name" class="w-full"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <MunicipalitySelect v-model="filter.municipality" custom-placeholder="---"
                                    size="small" />
                            </div>
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="---"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <CourseSelect v-model="filter.course" :scholarship-program-id="filter.program?.id"
                                    custom-placeholder="---" label="shortname" size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">
                                <YearLevelSelect v-model="filter.year_level" label="shortname" custom-placeholder="---"
                                    size="small" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2">

                                <InputText v-model="filter.remarks" placeholder="Search remarks" class="w-full"
                                    size="small" />

                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400"
                            v-if="hasPermission('can-view-jpm') && showJpmColumns">
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400"
                            v-if="hasPermission('can-view-jpm') && showJpmColumns">
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-slate-400">
                            <div class="px-2 flex justify-end">
                                <!-- <div class="bg-gray-600 text-gray-200 p-2 rounded-lg border flex items-center cursor-pointer"
                                    @click="clearFilter" as="button">
                                    <button class="cursor-pointer">
                                        Clear Filter
                                    </button>
                                </div> -->
                                <Button label="Clear" icon="pi pi-refresh" @click="clearFilter" size="small" />
                            </div>
                        </TableDataCell>
                    </TableRow>

                    <TableRow class="hover:bg-gray-200 bg-white" v-for="(profile, index) in profiles.data"
                        :key="'profile_' + profile.id" v-if="profiles.data && profiles.data.length">
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-100 text-gray-500">{{
                            profile.sequence_number }}
                        </TableDataCell>
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-100 text-gray-500">{{
                            profile.daily_sequence_number }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700 uppercase">
                            <div class="flex items-center gap-2 px-2">
                                <figure>
                                    <img v-if="profile.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                        class="rounded-xl w-[28px]" />

                                    <img v-if="profile.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                                        class="rounded-xl w-[28px]" />
                                </figure>
                                <div class="text-[11px] font-semibold">
                                    {{ profile.last_name + ', ' + profile.first_name + ' ' + (profile.middle_name ||
                                        '') + ' ' + (profile.extension_name || '') }}
                                </div>
                            </div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-slate-100 text-gray-700 uppercase w-[200px]">
                            <div class="px-2"
                                v-if="profile.father_name || profile.mother_name || profile.guardian_name">
                                <div v-if="profile.father_name"><span class="font-semibold  text-[11px]">{{
                                    profile.father_name }}</span><span
                                        class="text-gray-700 italic text-[11px] lowercase">
                                        (father)</span>
                                </div>
                                <div v-if="profile.mother_name"><span class="font-semibold  text-[11px]">{{
                                    profile.mother_name }}</span><span
                                        class="text-gray-700 italic text-[11px] lowercase">
                                        (mother)</span>
                                </div>
                                <div v-if="profile.guardian_name"><span class="font-semibold  text-[11px]">{{
                                    profile.guardian_name }}</span><span
                                        class="text-gray-700 italic text-[11px] lowercase">
                                        (guardian)</span>
                                </div>

                            </div>
                            <div v-else class="text-center">-</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold" v-if="profile.municipality">
                                {{ profile.municipality }} {{ profile.barangay
                                    ? `, ${profile.barangay}`
                                    : '' }}</div>
                            <div class="text-center" v-else>-</div>
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold" v-if="profile.scholarship_grant[0]?.school">
                                {{ profile.scholarship_grant[0]?.school?.shortname }}
                            </div>
                            <div class="px-2" v-else>-</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 pl-2 text-gray-700 uppercase">
                            <div class="px-2 text-[10px] font-bold" v-if="profile.scholarship_grant[0]?.course">
                                <div>{{ profile.scholarship_grant[0]?.course?.shortname }}</div>
                                <div v-if="profile.scholarship_grant[0]?.program"
                                    class="text-[10px] font-medium text-slate-600">[{{
                                        profile.scholarship_grant[0]?.program.shortname }}]</div>
                            </div>
                            <div class="px-2" v-else>-</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700">
                            <div class="px-2 text-[11px] font-semibold">{{ profile.scholarship_grant[0]?.year_level ||
                                '-'
                            }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700">
                            <div class="px-2 text-[11px] font-semibold">{{ profile.contact_no || '-' }}</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700 uppercase">
                            <div class="px-2 text-[11px] font-semibold">
                                {{ profile.date_filed ? moment(profile.date_filed).format('MMM DD, YYYY')
                                    : '-' }}</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700">
                            <div class="px-2 text-[10px]">
                                {{ profile.remarks || '-' }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700"
                            v-if="hasPermission('can-view-jpm') && showJpmColumns">
                            <div class="px-2 flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <label class="label hover:text-gray-800">
                                        <input type="checkbox" :checked="profile.is_jpm_member"
                                            class="checkbox  checkbox-sm "
                                            @change="updateJpmStatus({ id: profile.profile_id, is_jpm_member: $event.target.checked })" />
                                        <span>applicant</span>
                                    </label>
                                    <label class="label hover:text-gray-800">
                                        <input type="checkbox" :checked="profile.is_father_jpm"
                                            class="checkbox  checkbox-sm"
                                            @change="updateJpmStatus({ id: profile.profile_id, is_father_jpm: $event.target.checked })" />
                                        <span>father</span>
                                    </label>

                                </div>
                                <div class="flex gap-2">
                                    <label class="label hover:text-gray-800">
                                        <input type="checkbox" :checked="profile.is_guardian_jpm"
                                            class="checkbox  checkbox-sm"
                                            @change="updateJpmStatus({ id: profile.profile_id, is_guardian_jpm: $event.target.checked })" />
                                        <span>guardian</span>
                                    </label>
                                    <label class="label ml-1 hover:text-gray-800">
                                        <input type="checkbox" :checked="profile.is_mother_jpm"
                                            class="checkbox  checkbox-sm"
                                            @change="updateJpmStatus({ id: profile.profile_id, is_mother_jpm: $event.target.checked })" />
                                        <span>mother</span>
                                    </label>

                                </div>
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700"
                            v-if="hasPermission('can-view-jpm') && showJpmColumns">
                            <div class="px-2 text-xs">{{ profile.jpm_remarks || '-' }}</div>
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-slate-100 text-gray-700">
                            <div class="flex gap-4 justify-center">
                                <button class="text-emerald-500 hover:text-emerald-400 flex cursor-pointer"
                                    @click="addRemarks(profile)" v-if="hasPermission('can-view-jpm')">
                                    <!-- <IdentificationIcon class="h-5 w-5 text-blue-400" /> -->
                                    <i class="pi pi-heart"></i></button>


                                <!-- <button class="text-purple-500 hover:text-blue-600 flex cursor-pointer"
                                    @click="editProfile(profile)">
                                    <i class="pi pi-pen-to-square"></i></button> -->
                                <Link :href="route('profile.waitinglist', {
                                    id: profile.profile_id,
                                    action: 'update'
                                })" preserve-state preserve-scroll
                                    class="text-purple-500 hover:text-purple-600 underline font-medium">
                                <i class="pi pi-pen-to-square"></i></Link>

                                <button class="text-gray-400 hover:text-blue-600 flex cursor-pointer"
                                    @click="viewProfile(profile)">
                                    <!-- <IdentificationIcon class="h-5 w-5 text-blue-400" /> -->
                                    <i class="pi pi-search"></i></button>
                                <button class="text-red-500 hover:text-red-700 flex cursor-pointer"
                                    v-if="hasPermission('delete-scholar-profile')" @click="deleteProfile(profile)">
                                    <i class="pi pi-trash"></i>
                                </button>
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-100 text-center"
                            colspan="14">
                            No data
                            to be displayed</TableDataCell>
                    </TableRow>

                </template>
            </Table>
            <Pagination :links="props.profiles.meta?.links" class="mt-8" />
        </div>


        <!-- CREATE PROFILE MODAL -->
        <ApplicantProfileModal
            v-if="props.action == 'create' || props.action == 'update' || props.action == 'add-existing' && hasPermission('create-scholar-profile')"
            :action="props.action" :errors="props.errors" :profile="props.profile" />

        <!-- VIEW PROFILE MODAL -->
        <ViewProfileModal :isOpen="isViewProfileOpen" :errors="props.errors" :profile="selectedProfile"
            @close="closeViewProfile" />

        <!-- Generate Report Modal -->
        <GenerateReportModal v-model:show="showReportModal" />

        <!-- Delete Confirmation Dialog -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white rounded shadow-xl border p-8 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
                <p>Are you sure you want to delete this profile?</p>
                <div class="font-semibold font-mono text-gray-800 p-2 rounded bg-gray-100 text-xl mt-2">{{
                    `${selectedProfile.last_name},
                    ${selectedProfile.first_name}` }}
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <button class="px-4 py-2 bg-gray-200 rounded cursor-pointer" @click="cancelDelete">Cancel</button>
                    <button class="px-4 py-2 bg-red-500 text-white rounded cursor-pointer"
                        @click="confirmDelete">Delete</button>
                </div>
            </div>
        </div>

        <!-- Add JPM remarks Dialog -->
        <div v-if="showAddRemarksConfirm" class="fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white rounded shadow-xl border p-8 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Add JPM Remarks</h2>
                <p>Currently adding remarks for:</p>
                <div class="font-semibold font-mono text-gray-700 p-1 rounded bg-gray-100 mt-1">{{
                    `${selectedProfile.last_name},
                    ${selectedProfile.first_name}` }}
                </div>
                <div class="mt-4">

                    <!-- <InputText fluid type="text" :value="selectedProfile.jpm_remarks" /> -->
                    <IftaLabel class="w-full">
                        <InputText fluid id="jpm_remarks" v-model="selectedProfile.jpm_remarks" />
                        <label for="jpm_remarks">JPM remarks</label>
                    </IftaLabel>
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <button class="px-4 py-2 bg-gray-200 rounded cursor-pointer"
                        @click="cancelAddRemarks">Cancel</button>
                    <button class="px-4 py-2 bg-emerald-500 text-white rounded cursor-pointer"
                        @click="confirmAddRemarks">Add
                        Remarks</button>
                </div>
            </div>
        </div>
    </AdminLayout>

</template>