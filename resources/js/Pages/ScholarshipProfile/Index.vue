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
import ProfileModal from '@/Pages/ScholarshipProfile/Modal/ProfileModal.vue';

const { hasPermission } = usePermission();


const props = defineProps({
    profiles: Object,
    action: String,
    per_page: Number,
    filter: Object,
    message: Object,
    sort: {
        last_name: { type: String },
    },
});

const form = useForm({
    municipality: props.filter?.municipality || "",
    name: props.filter?.name || "",
    per_page: props.per_page || 10,
    sort: {},
});

const searchInput = ref(null);
const filterList = () => {
    // Prepare filter values
    const municipality = form.municipality?.name || "";
    const name = form.name || "";
    const per_page = form.per_page;
    const sort = form.sort;

    // Send request to waiting list API
    // You can add per_page or other params as needed
    const params = {
        // applied_course,
        municipality,
        name,
        per_page, sort
    };
    // Use Inertia visit to update the page with filtered results
    // The route name should match your web.php
    // This will update the profiles prop with paginated results
    router.get(route('profile.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const clearFilter = () => {
    form.reset();
}
const sortBy = (column) => {
    if (column == 'name') {
        form.sort.last_name = form.sort.last_name == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'date_filed') {
        form.sort.date_filed = form.sort.date_filed == 'desc' ? 'asc' : 'desc';
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


onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    // console.log(props.profiles.data)
});


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});


watch(form, debounce(
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
                class="text-normal font-medium text-center text-gray-500  border-gray-200 flex justify-center items-center mb-4 gap-4">
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

                <div class="w-2/3 flex shadow-xs">
                    <div class="bg-gray-700 rounded-l-lg text-white p-4">Filter</div>
                    <div class="px-4 border  bg-gray-50 flex flex-1 items-center gap-2">

                        <!-- <div class="w-1/3">
                            <CourseSelect v-model="form.course" :scholarship-program-id="''" label="shortname" />
                        </div> -->
                        <div class="w-1/3">
                            <MunicipalitySelect v-model="form.municipality" class="w-1/3" />
                        </div>
                        <div class="w-1/3">
                            <label
                                class="input focus-within:outline-none focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 flex items-center gap-2">

                                <input type="search" class="grow uppercase" placeholder="Search name"
                                    v-model="form.name" ref="searchInput" />
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

                <Link v-if="hasPermission('create-scholar-profile')" :href="route('profile.index', {
                    action: 'create'
                })"
                    class="btn btn-active bg-indigo-500 shadow text-white  ml-auto flex items-center justify-center uppercase">
                <UserPlusIcon class="h-4 w-4" />New Record</Link>
            </div>

            <Table class="border-collapse border border-slate-100 bg-[#f8f8f8]" :loading="form.processing">
                <template #header>
                    <TableRow>
                        <TableHeaderCell class="px-3">#</TableHeaderCell>
                        <TableHeaderCell @click="sortBy('name')" class="cursor-pointer">
                            <div class="flex items-center gap-2">
                                <h4>Name</h4>
                                <ChevronUpDownIcon class="h-4 w-4" />
                            </div>
                        </TableHeaderCell>

                        <TableHeaderCell>Address</TableHeaderCell>
                        <TableHeaderCell>Contact #</TableHeaderCell>
                        <TableHeaderCell>Email</TableHeaderCell>
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
                    <TableRow class="hover:bg-gray-200" v-for="(profile, index) in profiles.data"
                        :key="'profile_' + profile.id" v-if="profiles.data?.length > 0">
                        <TableDataCell class="px-3 w-[10px] border-collapse border-t border-slate-400">{{ index + 1 }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <div>
                                {{ profile.last_name + ', ' + profile.first_name + ' ' + (profile.middle_name ||
                                    '') + ' ' + (profile.extension_name || '') }}
                            </div>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ profile.municipality }} {{ profile.barangay ? `, ${profile.barangay}` : '' }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ profile.contact_no }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ profile.email }}
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            <span class="text-emerald-500" v-if="profile.ongoing_scholarship_grant">Granted</span>
                            <span class="text-orange-500" v-else-if="profile.pending_scholarship_grant">Pending</span>
                            <span class="text-orange-500" v-else-if="profile.is_on_waiting_list">Waiting List</span>

                            <span class="text-slate-500" v-else>No scholarship application</span>
                        </TableDataCell>
                        <TableDataCell class="border-collapse border-t border-l border-slate-400 pl-2 text-gray-700">
                            {{ profile.date_filed ? moment(profile.date_filed).format('MMM DD, YYYY') : '' }}
                        </TableDataCell>

                        <TableDataCell class="border-collapse border-t border-l border-slate-400 text-gray-700">
                            <div class="flex space-x-6 justify-center">
                                <Link :href="route('profile.index', { id: profile.profile_id, action: 'view' })"
                                    class="text-blue-500 hover:text-blue-600 flex underline underline-offset-2">
                                <!-- <IdentificationIcon class="h-5 w-5 text-blue-400" /> -->
                                View</Link>
                            </div>
                        </TableDataCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableDataCell class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
                            colspan="9">No data to be displayed</TableDataCell>
                    </TableRow>
                </template>
            </Table>
            <Pagination :links="props.profiles.meta?.links" v-if="props.profiles.data" class="mt-8" />
        </div>

        <!-- CREATE PROFILE MODAL -->
        <ProfileModal v-if="props.action == 'create' && hasPermission('create-scholar-profile')" :action="props.action"
            :errors="props.errors" />
    </AdminLayout>
</template>