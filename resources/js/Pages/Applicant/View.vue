<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Applicant Profile</template>

        <div class="p-4">

            <!-- {{ applicant }} -->

            <div class="grid grid-cols-3 grid-rows-3 gap-4">
                <div class="card bg-base-100 shadow-xl border row-span-1 col-span-2">
                    <div class="card-body">
                        <div class="flex justify-between">
                            <h2 class="card-title">Profile</h2>
                            <div>
                                <button class="btn btn-sm px-4 shadow rounded bg-purple-600 text-white"
                                    v-if="hasPermission('edit-scholar-profile')"
                                    @click="toggleApplicantProfileModal = !toggleApplicantProfileModal">UPDATE</button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <figure>
                                <img v-if="applicant.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                    class="rounded-xl w-[200px]" />

                                <img v-if="applicant.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                                    class="rounded-xl w-[200px]" />
                            </figure>
                            <table class="table table-zebra">
                                <!-- head -->

                                <tbody>
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Name</th>
                                        <td colspan="3"><span class="text-normal font-medium text-gray-900 mb-4">
                                                {{ applicant.last_name + ', ' + applicant.first_name + ' ' +
                                                    (applicant.middle_name || '') +
                                                    ' ' +
                                                    (applicant.extension_name || '') }}
                                            </span></td>

                                    </tr>
                                    <!-- row 1 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Birthdate</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900 mb-4">{{
                                            applicant.birthdate }}
                                            </span></td>
                                        <th class="font-normal text-gray-500" colspan="1">Civil Status</th>
                                        <td colspan="1"><span
                                                class="text-normal font-medium text-gray-900 mb-4 uppercase">{{
                                                    applicant.civil_status }}
                                            </span></td>

                                    </tr>
                                    <!-- row 2 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Permananent Address</th>
                                        <td colspan="3"><span
                                                class="text-normal font-medium text-gray-900  mb-4 uppercase">{{
                                                    applicant.municipality }}, {{
                                                    applicant.barangay }}, {{ applicant.address }}
                                            </span></td>
                                    </tr>
                                    <!-- row 3 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Temporary Address</th>
                                        <td colspan="3"><span
                                                class="text-normal font-medium text-gray-900  mb-4 uppercase">{{
                                                    applicant.temporary_municipality }}, {{
                                                    applicant.temporary_barangay }}, {{ applicant.temporary_address }}
                                            </span></td>
                                    </tr>
                                    <!-- row 4 -->
                                    <tr>
                                        <th class="font-normal text-gray-500" colspan="1">Contact #</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900  mb-4">{{
                                            applicant.contact_no }}
                                            </span></td>
                                        <th class="font-normal text-gray-500" colspan="1">Email Address</th>
                                        <td colspan="1"><span class="text-normal font-medium text-gray-900  mb-4">{{
                                            applicant.email }}
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
                                                    {{ applicant.father_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Occupation</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ applicant.father_occupation }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Contact #</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ applicant.father_contact_no || 'No data' }}
                                                </span></td>
                                        </tr>
                                        <tr>
                                            <th class="font-normal text-gray-500">Mother</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ applicant.mother_name }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Occupation</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ applicant.mother_occupation }}
                                                </span></td>
                                            <th class="font-normal text-gray-500">Contact #</th>
                                            <td><span class="text-normal font-medium text-gray-900 mb-4 uppercase">
                                                    {{ applicant.mother_contact_no || 'No data' }}
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
                                    <button class="btn btn-sm px-4 shadow rounded bg-slate-600 text-white"
                                        v-if="hasPermission('edit-scholar-profile')"
                                        @click="openEducationalBackgroundModal({ action: 'add' })">ADD</button>

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
                                        <tr v-for="(education, index) in applicant.educational_backgrounds" :key="index"
                                            v-if="applicant.educational_backgrounds.length > 0">

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
                                                <div v-if="hasPermission('edit-scholar-profile')"><button
                                                        class="btn btn-xs btn-link" @click="openEducationalBackgroundModal({
                                                            action: 'edit',
                                                            educationalBackground: education
                                                        })">edit</button> <button
                                                        class="btn btn-xs btn-link text-red-700"
                                                        @click="confirmDeleteEdBack(education.id, education.school_name, education.level)">delete</button>
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
                                        v-if="hasPermission('edit-scholar-profile')">
                                        <button class="btn btn-sm  rounded btn-success text-white shadow"
                                            v-if="!applicant.ongoing_scholarship_grant"
                                            @click="toggleApplyScholarshipModal = !toggleApplyScholarshipModal">GRANT
                                            SCHOLARSHIP
                                        </button>
                                        <div class="flex items-stretch" v-else>
                                            <div class="dropdown dropdown-end">
                                                <div tabindex="0" role="button"
                                                    class="btn btn-sm bg-slate-700 text-white rounded">
                                                    ACTION</div>
                                                <ul tabindex="0"
                                                    class="menu dropdown-content bg-slate-700 border rounded z-[1] mt-1 p-2 shadow-lg">
                                                    <li> <button
                                                            @click="toggleApplyScholarshipModal = !toggleApplyScholarshipModal"
                                                            class="text-emerald-400 text-shadow text-shadow-white font-medium underline underline-offset-2">Accept
                                                        </button></li>
                                                    <li> <button
                                                            @click="toggleApplyScholarshipModal = !toggleApplyScholarshipModal"
                                                            class="text-orange-400 text-shadow text-shadow-white font-medium underline underline-offset-2">Suspend
                                                        </button></li>
                                                    <li> <button
                                                            @click="toggleApplyScholarshipModal = !toggleApplyScholarshipModal"
                                                            class="text-red-400 text-shadow text-shadow-white font-medium underline underline-offset-2">End
                                                        </button></li>
                                                </ul>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <p class="text-gray-600 font-light" v-if="!applicant.ongoing_scholarship_grant">No
                                    active
                                    scholarship
                                    program</p>
                                <div v-else>
                                    <table class="table table-zebra">
                                        <!-- head -->

                                        <tbody>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500  w-[25%]">Status</th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4 italic">
                                                        {{
                                                            checkStatus(applicant.ongoing_scholarship_grant?.scholarship_status_id)
                                                        }}
                                                    </span></td>
                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500  w-[25%]">Program</th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4">
                                                        {{ applicant.ongoing_scholarship_grant?.program_name }}
                                                    </span></td>
                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">Course</th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4">
                                                        {{ applicant.ongoing_scholarship_grant?.course_name }}
                                                    </span></td>

                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">
                                                    Academic Year
                                                </th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4">
                                                        {{ applicant.ongoing_scholarship_grant?.academic_year }}
                                                    </span></td>


                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">
                                                    Year Level
                                                </th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4">
                                                        {{ applicant.ongoing_scholarship_grant?.year_level }}
                                                    </span></td>


                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">
                                                    Term
                                                </th>
                                                <td><span class="text-xs font-medium text-gray-900 mb-4">
                                                        {{ applicant.ongoing_scholarship_grant?.term }}
                                                    </span></td>


                                            </tr>

                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">School</th>
                                                <td colspan="5"><span
                                                        class="text-xs font-medium text-gray-900 mb-4 uppercase">
                                                        {{ applicant.ongoing_scholarship_grant?.school_name }}
                                                    </span></td>
                                            </tr>
                                            <tr>
                                                <th class="text-xs font-normal text-gray-500">Company</th>
                                                <td colspan="5"><span
                                                        class="text-xs font-medium text-gray-900 mb-4 uppercase">
                                                        {{ applicant.ongoing_scholarship_grant?.company_name }}
                                                    </span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="w-full flex mt-12">
                                        <div class="text-xs text-gray-500">
                                            <span class="underline">Requirements:</span>
                                            <div class="mt-4 flex items-center gap-4 w-full bg-grey-lighter">
                                                <p class="font-semibold text-gray-600">1. Letter of Intent:</p>
                                                <label v-if="hasPermission('edit-scholar-profile')">
                                                    <p
                                                        class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                                                        <CloudArrowUpIcon class="h-4 w-4" /> upload file
                                                    </p>
                                                    <input type='file' class="hidden" />
                                                </label>
                                            </div>
                                            <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                                                <p class="font-semibold text-gray-600">2. Certificate of Indigency:</p>
                                                <label v-if="hasPermission('edit-scholar-profile')">
                                                    <p
                                                        class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                                                        <CloudArrowUpIcon class="h-4 w-4" /> upload file
                                                    </p>
                                                    <input type='file' class="hidden" />
                                                </label>
                                            </div>
                                            <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                                                <p class="font-semibold text-gray-600">3. Certificate of Residency:</p>
                                                <label v-if="hasPermission('edit-scholar-profile')">
                                                    <p
                                                        class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                                                        <CloudArrowUpIcon class="h-4 w-4" /> upload file
                                                    </p>
                                                    <input type='file' class="hidden" />
                                                </label>
                                            </div>
                                            <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                                                <p class="font-semibold text-gray-600">4. Copy of Grades:</p>
                                                <label v-if="hasPermission('edit-scholar-profile')">
                                                    <p
                                                        class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                                                        <CloudArrowUpIcon class="h-4 w-4" /> upload file
                                                    </p>
                                                    <input type='file' class="hidden" />
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex">
                    Disbursement History
                </div>

            </div>


        </div>

        <ApplyScholarsipModal v-if="toggleApplyScholarshipModal" :action="'open'" :applicant_id="props.applicant.id" />

        <EducationalBackgroundModal v-if="toggleEducationalBackgroundpModal" :action="educationModalAction"
            :education="education" :applicant_id="props.applicant.id" />

        <ApplicantProfileModal v-if="toggleApplicantProfileModal && hasPermission('edit-scholar-profile')"
            :applicant="props.applicant" :errors="props.errors" :action="'update'" />

        <Modal marginTop="md" maxWidth="lg" :show="showConfirmDeleteEdBackModal" @close="closeModal">
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
import { ref } from 'vue';
import { usePermission } from '@/composable/permissions';
import ApplyScholarsipModal from './Modal/ApplyScholarsipModal.vue';
import { CloudArrowUpIcon } from "@heroicons/vue/20/solid";
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import EducationalBackgroundModal from './Modal/EducationalBackgroundModal.vue';
import ApplicantProfileModal from './Modal/ApplicantProfileModal.vue';

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
    applicant: Object,
    action: String,
    message: Object,
});

console.log(props.applicant);

const toggleApplyScholarshipModal = ref(false);
const toggleEducationalBackgroundpModal = ref(false);
const toggleApplicantProfileModal = ref(false);

const education = ref({});
const educationModalAction = ref();
const openEducationalBackgroundModal = ({ educationalBackground = null, action = null, params = null }) => {

    educationModalAction.value = action;
    if (action === 'edit') {
        education.value = educationalBackground
    }
    toggleEducationalBackgroundpModal.value = true;
};
const deleteEductation = (id) => {
    // console.log(props.applicant.id)
    axios.delete(`/applicants/delete-educational-background/${id}`)
        .then(response => {
            router.visit(route('applicants.index', { id: props.applicant.id, action: 'view' }))
            // console.log('Resource deleted successfully:', response.data);
        })
        .catch(error => {
            console.error('Error deleting resource:', error);
        });
}

const checkStatus = (status) => {
    //'0: Pending, 1: Active, 2: Completed, 3: Suspended, 4: Terminated, 5: Denied'
    switch (status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Active';
        case 2:
            return 'Completed';
        case 3:
            return 'Suspended';
        case 4:
            return 'Terminated';
        case 5:
            return 'Denied';
        default:
            return 'Unknown';
    }
};
</script>

<style lang="scss" scoped></style>