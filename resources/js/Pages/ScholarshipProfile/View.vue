<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Scholar's Profile</template>

        <div class="px-4">
            <div class="flex justify-between">
                <div class="breadcrumbs text-sm mb-4">
                    <ul>
                        <li><a :href="route('dashboard')">Home</a></li>
                        <li><a :href="route('profile.index')">Profiles</a></li>
                        <li>View Profile</li>
                    </ul>
                </div>
                <a :href="route('profile.index')" class="text-blue-800 underline">Go Back</a>
            </div>
            <!-- {{ profile }} -->

            <div class="grid grid-cols-3 grid-rows-1 gap-4">
                <div class="card bg-base-100 shadow-xl border col-span-2">
                    <div class="card-body">
                        <div class="flex justify-between">
                            <h2 class="card-title">Profile</h2>
                            <div class="flex gap-4">
                                <Button icon="pi pi-user-edit" severity="secondary" variant="text" raised rounded
                                    aria-label="Print" />
                                <Button icon="pi pi-pen-to-square" severity="help" variant="text" raised rounded
                                    aria-label="Update" v-if="hasPermission('edit-scholar-profile')"
                                    @click="toggleScholarProfileModal = !toggleScholarProfileModal" />
                                <!-- <button class="btn btn-sm px-4 shadow-sm rounded-sm bg-slate-700 text-white"
                                    v-if="hasPermission('edit-scholar-profile')"
                                    @click="toggleScholarProfileModal = !toggleScholarProfileModal">UPDATE</button> -->
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <figure>
                                <img v-if="profile.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                    class="rounded-xl w-[200px]" />

                                <img v-if="profile.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                                    class="rounded-xl w-[200px]" />
                            </figure>
                            <table class="table table-zebra">
                                <!-- head -->

                                <tbody>
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Name</th>
                                        <td colspan="3"><span class="text-normal font-medium text-gray-900 mb-4">
                                                {{ profile.last_name + ', ' + profile.first_name + ' ' +
                                                    (profile.middle_name || '') +
                                                    ' ' +
                                                    (profile.extension_name || '') }}
                                            </span></td>

                                    </tr>
                                    <!-- row 1 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Birthdate</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900 mb-4">{{
                                            profile.birthdate }}
                                            </span></td>
                                        <th class="font-normal text-gray-500" colspan="1">Civil Status</th>
                                        <td colspan="1"><span
                                                class="text-normal font-medium text-gray-900 mb-4 uppercase">{{
                                                    profile.civil_status }}
                                            </span></td>

                                    </tr>
                                    <!-- row 2 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Permananent Address</th>
                                        <td colspan="3"><span
                                                class="text-normal font-medium text-gray-900  mb-4 uppercase">{{
                                                    profile.municipality }} {{ profile.barangay ? `, ${profile.barangay}` :
                                                    '' }} {{ profile.address ? `, ${profile.address}` :
                                                    '' }}
                                            </span></td>
                                    </tr>
                                    <!-- row 3 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Temporary Address</th>
                                        <td colspan="3"><span
                                                class="text-normal font-medium text-gray-900  mb-4 uppercase">{{
                                                    profile.temporary_municipality }} {{ profile.temporary_barangay ? `,
                                                ${profile.temporary_barangay}` :
                                                    '' }} {{ profile.temporary_address ? `, ${profile.temporary_address}` :
                                                    '' }}
                                            </span></td>
                                    </tr>
                                    <!-- row 4 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Contact #</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900  mb-4">{{
                                            profile.contact_no }}
                                            </span></td>
                                        <th class="font-normal text-gray-500" colspan="1">Email Address</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900  mb-4">{{
                                            profile.email }}
                                            </span></td>
                                    </tr>


                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 mb-2 border-t pt-4">
                            <p class="text-lg font-thin text-gray-500">Parents Information</p>
                            <div class="mt-4">
                                <table class="table table-xs">
                                    <!-- head -->
                                    <tbody>
                                        <tr>
                                            <th class="font-normal text-gray-500">Father</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.father_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Occupation</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.father_occupation }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Contact #</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.father_contact_no || 'No data' }}
                                                </span></td>
                                        </tr>
                                        <tr>
                                            <th class="font-normal text-gray-500">Mother</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.mother_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Occupation</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.mother_occupation }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Contact #</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ profile.mother_contact_no || 'No data' }}
                                                </span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4 mb-2 border-t pt-4">
                            <div class="flex justify-between">
                                <p class="text-lg font-thin text-gray-500">Educational Background</p>
                                <div>
                                    <!-- <button class="btn btn-sm px-4 shadow-sm rounded-sm bg-slate-700 text-white"
                                        v-if="hasPermission('edit-scholar-profile')"
                                        @click="openEducationalBackgroundModal({ action: 'add' })">ADD</button> -->
                                    <Button icon="pi pi-pen-to-square" severity="help" variant="text" raised rounded
                                        aria-label="Update" v-if="hasPermission('edit-scholar-profile')"
                                        @click="openEducationalBackgroundModal({ action: 'add' })" />

                                </div>
                            </div>
                            <div class="mt-4">
                                <table class="table table-xs">
                                    <!-- head -->
                                    <tbody>
                                        <tr>

                                            <th class="font-normal text-gray-500">Level</th>
                                            <th class="font-normal text-gray-500">Name of School</th>
                                            <th class="font-normal text-gray-500">Course</th>
                                            <th class="font-normal text-gray-500">From</th>
                                            <th class="font-normal text-gray-500">To</th>
                                            <th class="font-normal text-gray-500">Academic Honors</th>
                                            <th></th>
                                        </tr>
                                        <tr v-for="(education, index) in profile.educational_backgrounds" :key="index"
                                            v-if="profile.educational_backgrounds.length > 0">

                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.level || 'No data' }}
                                                </span></td>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.school_name || 'No data' }}
                                                </span></td>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.course || '' }}
                                                </span></td>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.start_date || '' }}
                                                </span></td>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.end_date || '' }}
                                                </span></td>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ education.academic_honors || '' }}
                                                </span></td>
                                            <td>
                                                <div v-if="hasPermission('edit-scholar-profile')">
                                                    <button class="btn btn-xs btn-link" @click="openEducationalBackgroundModal({
                                                        action: 'edit',
                                                        educationalBackground: education
                                                    })">
                                                        <PencilSquareIcon class="h-4 w-4" />
                                                    </button> <button class="btn btn-xs btn-link text-red-700"
                                                        @click="confirmDeleteEdBack(education.id, education.school_name, education.level)">
                                                        <TrashIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-else>
                                            <td colspan="6" class="text-gray-500 text-center">No data available</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-span-1 row-span-2">
                    <div class="card bg-base-100 w-full shadow-xl border">
                        <div class="card-body flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between mb-6">
                                    <h2 class="card-title">Scholarship</h2>
                                    <div class="flex flex-1 justify-end px-2"
                                        v-if="hasPermission('edit-scholar-profile') && (!profile.is_on_waiting_list || profile.is_on_waiting_list === 0)">
                                        <button class="btn btn-sm  rounded-sm btn-success text-white shadow-sm"
                                            v-if="!profile.ongoing_scholarship_grant && !profile.pending_scholarship_grant"
                                            @click="toggleApplyScholarshipModal = !toggleApplyScholarshipModal">ADD
                                            SCHOLARSHIP
                                        </button>
                                        <div class="flex items-stretch" v-else>
                                            <div class="dropdown dropdown-end">
                                                <div tabindex="0" role="button"
                                                    class="btn btn-sm bg-slate-700 text-white rounded-sm">
                                                    CHANGE STATUS</div>
                                                <ul tabindex="0"
                                                    class="menu dropdown-content bg-slate-600 border rounded-sm z-1 mt-1 p-2 shadow-lg">
                                                    <li> <button @click="updatescholarshipstatus(1)"
                                                            class="text-emerald-400 text-shadow-xs font-medium underline underline-offset-2">Approve
                                                        </button></li>
                                                    <li> <button @click="updatescholarshipstatus(2)"
                                                            class="text-blue-400 text-shadow-xs font-medium underline underline-offset-2">Complete
                                                        </button></li>
                                                    <li> <button @click="updatescholarshipstatus(3)"
                                                            class="text-orange-400 text-shadow-xs font-medium underline underline-offset-2">Suspend
                                                        </button></li>
                                                    <li> <button @click="updatescholarshipstatus(4)"
                                                            class="text-red-400 text-shadow-xs font-medium underline underline-offset-2">End
                                                        </button></li>
                                                </ul>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <p class="text-gray-600 font-light"
                                    v-if="!profile.ongoing_scholarship_grant && !profile.pending_scholarship_grant">No
                                    active
                                    scholarship
                                    program</p>
                                <div v-else-if="profile.pending_scholarship_grant">
                                    <ScholarshipTable :profile="profile.pending_scholarship_grant" />
                                </div>
                                <div v-else>
                                    <ScholarshipTable :profile="profile.ongoing_scholarship_grant" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow-xl border col-span-3 p-4">
                    <h2 class="card-title">Scholarship Records</h2>
                    <table class="table table-zebra">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>School</th>
                                <th>Program</th>
                                <th>Course</th>
                                <th>Academic Year</th>
                                <th>Term</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <tr v-for="(rec, i) in profile.scholarship_grant" :key="'sc_' + i">
                                <td>{{ i + 1 }}</td>
                                <td class="uppercase">{{ rec.school_name }}</td>
                                <td class="uppercase">{{ rec.program.shortname }}</td>
                                <td class="uppercase">{{ rec.course.shortname }}</td>
                                <td>{{ rec.academic_year }}</td>
                                <td>{{ rec.term }}</td>
                                <td><span
                                        :class="{ 'text-orange-500': rec.scholarship_status == 0 || rec.scholarship_status == 3, 'text-emerald-500': rec.scholarship_status == 1, 'text-blue-500': rec.scholarship_status == 2, 'text-red-500': rec.scholarship_status == 4 || rec.scholarship_status == 5 }">{{
                                            rec.scholarship_status
                                                == 0 ? 'Pending' : rec.scholarship_status == 1 ?
                                                'Active'
                                                :
                                                rec.scholarship_status == 2 ? 'Completed' : rec.scholarship_status == 3 ?
                                                    'Suspended' :
                                                    rec.scholarship_status == 4 ? 'Terminated' : rec.scholarship_status == 5 ?
                                                        'Denied'
                                                        : '' }}</span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a class="btn btn-xs btn-ghost text-blue-600 underline"
                                            :href="route('scholarship_records.index', { id: rec.id, action: 'view' })">View</a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
                <div class="bg-gray-50 col-span-3 row-span-1 p-4">
                    <div class="w-full">Disbursement History</div>

                    <div class="w-full text-gray-500 text-center p-4">
                        coming soon...
                    </div>

                </div>

            </div>


        </div>

        <ApplyScholarsipModal v-if="toggleApplyScholarshipModal" :action="'open'"
            :profile_id="props.profile.profile_id" />

        <EducationalBackgroundModal v-if="toggleEducationalBackgroundpModal" :action="educationModalAction"
            :education="education" :profile_id="props.profile.profile_id" />

        <ProfileModal v-if="toggleScholarProfileModal && hasPermission('edit-scholar-profile')" :profile="profile"
            :errors="props.errors" :action="'update'" />


        <ScholarshipModal v-if="toggleScholarshipModal" :action="'view-scholarship'" :profile_id="profile.profile_id"
            :profile_name="`${profile.last_name}, ${profile.first_name} ${profile.middle_name || ''} ${profile.extension_name || ''}`"
            :scholarship_record="selectedScholarshipRecord" />

        <Modal marginTop=" md" maxWidth="lg" :show="showConfirmDeleteEdBackModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this data?
                </h2>

                <p class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold">
                    "{{ modalEdBackData.level }} / {{ modalEdBackData.school_name }}"
                </p>
                <div class="mt-6 flex space-x-4">
                    <DangerButton @click="deleteEductation(modalEdBackData.id)">
                        Delete</DangerButton>
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { usePermission } from '@/composable/permissions';
import ApplyScholarsipModal from './Modal/ApplyScholarsipModal.vue';
import { PencilSquareIcon, TrashIcon } from "@heroicons/vue/20/solid";
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import EducationalBackgroundModal from './Modal/EducationalBackgroundModal.vue';
import ProfileModal from './Modal/ProfileModal.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import ScholarshipTable from './Components/ScholarshipTable.vue';
import ScholarshipModal from './Modal/ScholarshipModal.vue';

const showConfirmDeleteEdBackModal = ref(false);
const modalEdBackData = ref({ id: null, school_name: null, level: null });
const confirmDeleteEdBack = (id, schoolName, level) => {
    modalEdBackData.value.id = id;
    modalEdBackData.value.school_name = schoolName;
    modalEdBackData.value.level = level;
    showConfirmDeleteEdBackModal.value = true;
};
const closeModal = () => {
    showConfirmDeleteEdBackModal.value = false;
};
const { hasPermission } = usePermission();
// CloudUploadIcon
const props = defineProps({
    record: Object,
    profile: Object,
    action: String,
    message: Object
});

console.log(props.profile)

const toggleApplyScholarshipModal = ref(false);
const toggleEducationalBackgroundpModal = ref(false);
const toggleScholarProfileModal = ref(false);
const toggleScholarshipModal = ref(false);

// const scholarshipRecords = ref([]);
const education = ref({});
const educationModalAction = ref();
const openEducationalBackgroundModal = ({ educationalBackground = null, action = null, params = null }) => {

    educationModalAction.value = action;
    if (action === 'edit') {
        education.value = educationalBackground
    }
    toggleEducationalBackgroundpModal.value = true;
};

const selectedScholarshipRecord = ref({});
const openScholarshipModal = (record) => {
    selectedScholarshipRecord.value = record;
    toggleScholarshipModal.value = true;
}
const deleteEductation = (id) => {
    // console.log(props.profile.id)
    axios.delete(`/profiles/delete-educational-background/${id}`)
        .then(response => {
            router.visit(route('profile.index', { id: props.profile.profile_id, action: 'view' }))
            // console.log('Resource deleted successfully:', response.data);
        })
        .catch(error => {
            console.error('Error deleting resource:', error);
        });
}



const updatescholarshipstatus = (status_id, scholarhip = null) => {
    if (props.profile.ongoing_scholarship_grant || props.profile.pending_scholarship_grant || scholarhip) {
        axios.put(route('scholarship_records-api.updatestatus', props.profile.ongoing_scholarship_grant || props.profile.pending_scholarship_grant || scholarhip), {
            status_id: status_id // Assuming 1 is the ID for 'Active' status
        })
            .then(() => {
                toast.success("Scholarship status has been updated!");
                router.visit(route('profile.index', { id: props.profile.profile_id, action: 'view' }));
            })
            .catch(error => {
                console.error('Error approving scholarship:', error);
            });
    }
};


</script>

<style lang="scss" scoped></style>