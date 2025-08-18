<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/20/solid";

defineProps(["voterprofiles"]);

const form = useForm({});

const showConfirmDeleteVoterProfileModal = ref(false);
const modalVoterProfileData = ref({ id: null, name: null });

const confirmDeleteVoterProfile = (profileID, profileName) => {
    showConfirmDeleteVoterProfileModal.value = true;
    modalVoterProfileData.value.id = profileID;
    modalVoterProfileData.value.name = profileName;
};
const closeModal = () => {
    showConfirmDeleteVoterProfileModal.value = false;
};
const deleteRole = (voterProfileID) => {
    form.delete(route("votersprofile.destroy", voterProfileID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Roles" />

    <AdminLayout>
        <template #header>Voter's Profile</template>

        <div class="max-w-full mx-auto py-4">
            <div
                class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700"
            >
                <ul class="flex flex-wrap -mb-px">
                    <li class="me-2">
                        <a
                            :href="route('votersprofile.index')"
                            class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                            >Summary</a
                        >
                    </li>
                    <li class="me-2">
                        <a
                            :href="route('votersprofile.coordinator')"
                            class="inline-block p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg dark:text-blue-500 dark:border-blue-500"
                            >Coordinator</a
                        >
                    </li>
                    <li class="me-2">
                        <a
                            href="#"
                            class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                            >Leader</a
                        >
                    </li>
                    <li class="me-2">
                        <a
                            href="#"
                            class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                            >SubLeader</a
                        >
                    </li>
                    <li>
                        <a
                            class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                            >Member</a
                        >
                    </li>
                </ul>
            </div>

            <div class="flex justify-between mt-12">
                <!--search bar -->
                <div hidden class="md:block w-1/3">
                    <div
                        class="relative flex items-center text-gray-400 focus-within:text-sky-500"
                    >
                        <span
                            class="absolute left-4 h-6 flex items-center pr-3 border-r border-gray-300"
                        >
                            <MagnifyingGlassIcon class="h-5 w-5" />
                        </span>
                        <input
                            type="search"
                            name="leadingIcon"
                            id="leadingIcon"
                            placeholder="Search voter"
                            class="w-full pl-14 pr-4 py-2.5 rounded-xl text-sm text-gray-600 outline-none border border-gray-300 focus:border-cyan-300 transition"
                        />
                    </div>
                </div>
                <!--/search bar -->
                <Link
                    :href="route('votersprofile.create')"
                    class="text-white font-semibold px-3 py-2 bg-sky-600 hover:bg-sky-700 rounded"
                    >New Profile</Link
                >
            </div>
            <div class="mt-6">
                <Table>
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell>#</TableHeaderCell>
                            <TableHeaderCell>Name</TableHeaderCell>
                            <TableHeaderCell>Position</TableHeaderCell>
                            <TableHeaderCell>Barangay</TableHeaderCell>
                            <TableHeaderCell>Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow
                            v-for="(voter, index) in voterprofiles"
                            :key="'voter_' + voter.id"
                        >
                            <TableDataCell>{{ index + 1 }}</TableDataCell>
                            <TableDataCell>{{
                                voter.lastname +
                                ", " +
                                voter.firstname +
                                " " +
                                (voter.middlename || "")
                            }}</TableDataCell>
                            <TableDataCell>{{ voter.position }}</TableDataCell>
                            <TableDataCell>{{ voter.barangay }}</TableDataCell>
                            <TableDataCell class="space-x-6">
                                <Link
                                    :href="
                                        route('votersprofile.edit', voter.id)
                                    "
                                    class="text-green-500 hover:text-green-600"
                                    >Edit</Link
                                >

                                <!-- <Link
                                    :href="route('roles.destroy', role.id)"
                                    class="text-red-500 hover:text-red-600"
                                    method="DELETE"
                                    as="button"
                                    >Delete</Link
                                > -->
                                <button
                                    class="text-red-500 hover:text-red-600"
                                    @click="
                                        confirmDeleteVoterProfile(
                                            voter.id,
                                            voter.name
                                        )
                                    "
                                >
                                    Delete
                                </button>
                                <Modal
                                    maxWidth="lg"
                                    :show="showConfirmDeleteVoterProfileModal"
                                    @close="closeModal"
                                >
                                    <div class="p-6">
                                        <h2
                                            class="text-lg font-semibold text-slate-800"
                                        >
                                            Are you sure you want to delete this
                                            profile?
                                        </h2>
                                        <p
                                            class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold"
                                        >
                                            "{{ modalVoterProfileData.name }}"
                                        </p>

                                        <div class="mt-6 flex space-x-4">
                                            <DangerButton
                                                @click="
                                                    deleteRole(
                                                        modalVoterProfileData.id
                                                    )
                                                "
                                            >
                                                Delete</DangerButton
                                            >
                                            <SecondaryButton @click="closeModal"
                                                >Cancel</SecondaryButton
                                            >
                                        </div>
                                    </div>
                                </Modal>
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>
    </AdminLayout>
</template>
