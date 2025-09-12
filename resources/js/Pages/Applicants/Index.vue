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
    ChevronUpDownIcon,
    UserPlusIcon,
} from '@heroicons/vue/20/solid';
import ApplicantProfileModal from '@/Pages/Applicants/Modal/ApplicantProfileModal.vue';

import ViewProfileModal from './Modal/ViewProfileModal.vue';
// COURSE MULTISELECT COMPONENT
// How to use: 1. import component, 2. define model, 3. define scholarshipProgramId (set to null if fetching all course)
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import Pagination from '@/Components/Pagination.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

const { hasPermission } = usePermission();

// console.log(route)
const props = defineProps({
    profile: Object,
    profiles: Object,
    action: String,
    per_page: Number,
    filter: Object,
    message: Object,
    sort: {
        date_filed: { type: String },
        last_name: { type: String },
        applied_course: { type: String },
        applied_year_level: { type: String },
    },

});

const form = useForm({
    applied_course: props.filter.applied_course || "",
    municipality: props.filter.municipality || "",
    name: props.filter.name || "",
    per_page: props.per_page || 10,
    sort: {
        date_filed: props.sort.date_filed || "",
        last_name: props.sort.last_name || "",
        applied_school: props.sort.applied_school || "",
        applied_course: props.sort.applied_course || "",
        applied_year_level: props.sort.applied_year_level || "",
    },

});
const filter = useForm({
    name: props.filter.name || "",
    program: props.filter.program || "",
    course: props.filter.course || "",
    municipality: props.filter.municipality || "",

})

const searchInput = ref(null);
const selectedProfile = ref({});
const toggleViewModal = ref(false);
const viewProfile = (profile) => {
    toggleViewModal.value = !toggleViewModal.value;
    selectedProfile.value = profile;
}

// const editProfile = (profile) => {

//     // console.log(toggleViewModal.value);
//     selectedProfile.value = profile;
// }

const filterList = () => {
    // Prepare filter values
    const program = filter.program?.id || "";
    const applied_course = filter.course?.shortname || "";
    const municipality = filter.municipality?.name || "";
    const name = filter.name || "";
    const per_page = form.per_page;
    const sort = form.sort;

    // Send request to waiting list API
    // You can add per_page or other params as needed
    const params = {
        program,
        // course,
        applied_course,
        municipality,
        name,
        per_page, sort
    };
    // Use Inertia visit to update the page with filtered results
    // The route name should match your web.php
    // This will update the profiles prop with paginated results
    router.get(route('profile.waitinglist'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const clearFilter = () => {
    filter.reset();
}
const sortBy = (column) => {
    if (column == 'name') {
        form.sort.last_name = form.sort.last_name == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'date_filed') {
        form.sort.date_filed = form.sort.date_filed == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'applied_school') {
        form.sort.applied_school = form.sort.applied_school == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'applied_course') {
        form.sort.applied_course = form.sort.applied_course == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'applied_year_level') {
        form.sort.applied_year_level = form.sort.applied_year_level == 'desc' ? 'asc' : 'desc';
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
    console.log(props.profiles.data)
});


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});


watch(form, debounce(
    () =>
        filterList(),
    500
));
watch(filter, debounce(
    () =>
        filterList(),
    500
));


const showDeleteConfirm = ref(false);
const profileToDelete = ref(null);

const deleteProfile = (profile) => {
    profileToDelete.value = profile;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    if (!profileToDelete.value) return;
    router.delete(route('profile.destroy', profileToDelete.value.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            profileToDelete.value = null;
            // Optionally show a toast here
        },
        onError: () => {
            showDeleteConfirm.value = false;
            profileToDelete.value = null;
        }
    });
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    profileToDelete.value = null;
};

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
                <div class="flex gap-2 flex-1">
                    <div class="flex shadow-xs">
                        <div class="bg-gray-700 rounded-l-lg text-white p-4">Show</div>
                        <select v-model="form.per_page" class="w-[60px] border rounded-r-lg cursor-pointer text-center">
                            <option value="200">200</option>
                            <option value="100">100</option>
                            <option value="50">50</option>
                            <option value="25">25</option>
                            <option value="10">10</option>
                            <option value="5">5</option>
                        </select>
                    </div>

                    <!-- <div class="flex shadow-xs">
                        <div class="bg-gray-700 rounded-l-lg text-white p-4">Filter</div>
                        <div class="px-4 border  bg-gray-50 flex flex-1 items-center gap-2">
                            <div class="w-1/3">
                                <ProgramSelect v-model="filter.program" label="shortname" />
                            </div>
                            <div class="w-1/3">
                                <CourseSelect v-model="filter.course" :scholarship-program-id="filter.program?.id"
                                    label="shortname" />
                            </div>
                            <div class="w-1/3">
                                <MunicipalitySelect v-model="filter.municipality" class="w-1/3" />
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
                    </div> -->
                </div>
                <div class="flex justify-end gap-4">

                    <Button as="a" label="Existing" icon="pi pi-user" v-if="hasPermission('create-scholar-profile')"
                        :href="route('profile.waitinglist', {
                            action: 'add-existing'
                        })" raised size="small" />

                    <Button as="a" label="New" icon="pi pi-user-plus" v-if="hasPermission('create-scholar-profile')"
                        :href="route('profile.waitinglist', {
                            action: 'create'
                        })" raised size="small" />



                </div>
            </div>


            <div class="flex gap-4 justify-end items-end mb-2">
                <div class="text-normal text-gray-500 flex gap-2">Encoder: <p
                        class="font-bold font-mono text-emerald-600">{{
                            userEncodedCount.name
                        }}</p>
                </div>-
                <div class="text-normal text-gray-500 flex gap-2">Today: <p class="font-bold font-mono text-purple-600">
                        {{
                            userEncodedCount.today }}</p>
                </div>-
                <div class="text-normal text-gray-500 flex gap-2">Total: <p class="font-bold font-mono text-purple-600">
                        {{
                            userEncodedCount.total }}</p>
                </div>
            </div>

            <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]" :loading="form.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="px-3">#</TableHeaderCell>
                        <TableHeaderCell @click="sortBy('name')" class="cursor-pointer w-80">
                            <div class="flex items-center gap-2">
                                <h4>Name</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell class="w-42">Address</TableHeaderCell>

                        <TableHeaderCell @click="sortBy('applied_school')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>School</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('applied_course')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>Course</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell @click="sortBy('applied_year_level')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>Year Level</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell>Contact #</TableHeaderCell>
                        <TableHeaderCell @click="sortBy('date_filed')" class="cursor-pointer w-[110px]">
                            <div class="flex items-center gap-2">
                                <h4>Date Filed</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>
                        <TableHeaderCell>Remarks</TableHeaderCell>
                        <!-- <TableHeaderCell class="w-[160px]">Status</TableHeaderCell> -->
                        <TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
                    </TableRow>


                </template>
                <template #default>
                    <!-- filter row -->
                    <TableRow>
                        <TableDataCell class="px-3"></TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <IconField>
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="filter.name" placeholder="Search name" class="w-full" />
                                </IconField>
                            </div>
                        </TableDataCell>

                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <MunicipalitySelect v-model="filter.municipality" custom-placeholder="---" />
                            </div>
                        </TableDataCell>

                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="---" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <CourseSelect v-model="filter.course" :scholarship-program-id="filter.program?.id"
                                    custom-placeholder="---" label="shortname" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <YearLevelSelect v-model="filter.year_level" label="shortname"
                                    custom-placeholder="---" />
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400"></TableDataCell>
                        <TableDataCell class="border-l border-collapse border-slate-400">
                            <div class="px-2">
                                <div class="bg-gray-600 text-gray-200 p-2 rounded-lg border flex items-center cursor-pointer justify-center"
                                    @click="clearFilter" as="button">
                                    <button class="cursor-pointer">
                                        Clear Filter
                                    </button>
                                </div>
                            </div>
                        </TableDataCell>
                    </TableRow>

                    <TableRow class="hover:bg-gray-200" v-for="(profile, index) in profiles.data"
                        :key="'profile_' + profile.id" v-if="profiles.data && profiles.data.length">
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1 }}
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="flex items-center gap-2 px-2">
                                <figure>
                                    <img v-if="profile.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                        class="rounded-xl w-[28px]" />

                                    <img v-if="profile.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                                        class="rounded-xl w-[28px]" />
                                </figure>
                                <div>
                                    {{ profile.last_name + ', ' + profile.first_name + ' ' + (profile.middle_name ||
                                        '') + ' ' + (profile.extension_name || '') }}
                                </div>
                            </div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2"> {{ profile.municipality }} {{ profile.barangay ? `, ${profile.barangay}`
                                : '' }}</div>
                        </TableDataCell>

                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2">
                                {{ profile.scholarship_grant[0]?.school?.shortname }}
                            </div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700 uppercase">
                            <div class="px-2">
                                {{ profile.scholarship_grant[0]?.course?.shortname }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="px-2">{{ profile.scholarship_grant[0]?.year_level }}</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="px-2">{{ profile.contact_no }}</div>
                        </TableDataCell>
                        <TableDataCell
                            class="border-collapse border-t border-l border-slate-400 text-gray-700 uppercase">
                            <div class="px-2"> {{ profile.date_filed ? moment(profile.date_filed).format('MMM DD, YYYY')
                                :
                                moment(profile.created_at).format('MMM DD, YYYY') }}</div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="px-2">
                                {{ profile.remarks }}
                            </div>
                        </TableDataCell>
                        <!-- <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="px-2"><span :class="{ 'text-red-400': profile.application_status == 2 }">{{
                                profile.application_status == 0 ? 'Pending' : profile.application_status == 2 ?
                                    'Declined' : '' }}</span></div>
                        </TableDataCell> -->

                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="flex space-x-6 justify-center">
                                <button class="text-gray-400 hover:text-blue-600 flex cursor-pointer"
                                    @click="viewProfile(profile)">
                                    <!-- <IdentificationIcon class="h-5 w-5 text-blue-400" /> -->
                                    <i class="pi pi-search"></i></button>


                                <!-- <button class="text-purple-500 hover:text-blue-600 flex cursor-pointer"
                                    @click="editProfile(profile)">
                                    <i class="pi pi-pen-to-square"></i></button> -->
                                <Link :href="route('profile.waitinglist', {
                                    id: profile.profile_id,
                                    action: 'update'
                                })" preserve-state preserve-scroll
                                    class="text-purple-500 hover:text-purple-600 underline font-medium">
                                <i class="pi pi-pen-to-square"></i></Link>
                                <button class="text-red-500 hover:text-red-700 flex cursor-pointer"
                                    v-if="hasPermission('delete-scholar-profile')" @click="deleteProfile(profile)">
                                    <i class="pi pi-trash"></i>
                                </button>
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="9">No data to be displayed</TableDataCell>
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
        <ViewProfileModal v-if="toggleViewModal" :action="'view'" :errors="props.errors" :profile="selectedProfile" />

        <!-- Delete Confirmation Dialog -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white rounded shadow-xl border p-8 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
                <p>Are you sure you want to delete this profile?</p>
                <div class="font-semibold font-mono text-gray-800 p-2 rounded bg-gray-100 text-xl mt-2">{{
                    `${profileToDelete.last_name},
                    ${profileToDelete.first_name}` }}
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <button class="px-4 py-2 bg-gray-200 rounded cursor-pointer" @click="cancelDelete">Cancel</button>
                    <button class="px-4 py-2 bg-red-500 text-white rounded cursor-pointer"
                        @click="confirmDelete">Delete</button>
                </div>
            </div>
        </div>

    </AdminLayout>

</template>