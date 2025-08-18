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
import { UserPlusIcon } from "@heroicons/vue/20/solid";
defineProps(["users"]);

const form = useForm({});

const showConfirmDeleteUserModal = ref(false);
const modalUserData = ref({ id: null, username: null, name: null });

const confirmDeleteUser = (userID, userName, userUserName) => {
    showConfirmDeleteUserModal.value = true;
    modalUserData.value.id = userID;
    modalUserData.value.name = userName;
    modalUserData.value.username = userUserName;
};
const closeModal = () => {
    showConfirmDeleteUserModal.value = false;
};
const deleteUser = (userID) => {
    form.delete(route("users.destroy", userID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Users" />

    <AdminLayout>
        <template #header>Users</template>

        <div class="max-w-4xl mx-auto py-4">
            <div class="flex justify-end">
                <Link
                    :href="route('users.create')"
                    class="text-emerald-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                    ><UserPlusIcon class="h-5 w-5" />New User</Link
                >

                <!-- {{ users }} -->
            </div>
            <div class="mt-6">
                <Table class="border-collapse border border-slate-400">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="-indent-1"
                                >#</TableHeaderCell
                            >
                            <TableHeaderCell class="-indent-2 w-[35%]"
                                >Name</TableHeaderCell
                            >
                            <TableHeaderCell class="-indent-2"
                                >Username</TableHeaderCell
                            >
                            <TableHeaderCell class="-indent-2 w-[20%]"
                                >Action</TableHeaderCell
                            >
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(user, index) in users" :key="user.id">
                            <TableDataCell
                                class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1"
                                >{{ index + 1 }}</TableDataCell
                            >
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 indent-4"
                                >{{ user.name }}</TableDataCell
                            >
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 indent-4"
                                >{{ user.username }}</TableDataCell
                            >
                            <TableDataCell
                                class="space-x-6 border-collapse border-t border-l border-slate-400 indent-4"
                            >
                                <Link
                                    :href="route('users.edit', user.id)"
                                    class="text-green-500 hover:text-green-600"
                                    >Edit</Link
                                >

                                <button
                                    class="text-red-500 hover:text-red-600"
                                    @click="
                                        confirmDeleteUser(
                                            user.id,
                                            user.name,
                                            user.username
                                        )
                                    "
                                >
                                    Delete
                                </button>
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>
        <Modal
            marginTop="md"
            maxWidth="lg"
            :show="showConfirmDeleteUserModal"
            @close="closeModal"
        >
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this user?
                </h2>

                <p
                    class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold"
                >
                    "{{ modalUserData.name }} / {{ modalUserData.username }}"
                </p>
                <div class="mt-6 flex space-x-4">
                    <DangerButton @click="deleteUser(modalUserData.id)">
                        Delete</DangerButton
                    >
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
