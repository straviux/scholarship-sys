<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import Modal from "@/Components/Modal.vue";
import ChangePasswordModal from "@/Pages/Admin/Users/ChangePasswordModal.vue";
import { UserPlusIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

defineProps(["users"]);

const showChangePasswordModal = ref(false);
const selectedUser = ref(null);
const openChangePasswordModal = (user) => {
    selectedUser.value = user;
    showChangePasswordModal.value = true;
};
const closeChangePasswordModal = () => {
    showChangePasswordModal.value = false;
    selectedUser.value = null;
};
const handlePasswordChangeSuccess = () => {
    toast.success('Password changed successfully!');
    closeChangePasswordModal();
};

const form = useForm({});

const editUser = (userId) => {
    // Navigate to the edit user page
    router.get(route("users.edit", userId));
};

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
        onError: (errors) => {
            if (errors.delete) {
                toast.error(errors.delete);
            } else {
                toast.error('Unable to delete user. This user may be referenced in other records.');
            }
        },
    });
};
</script>

<template>

    <Head title="Users" />

    <AdminLayout>
        <template #header>Users</template>

        <div class="max-w-4xl mx-auto py-4">
            <div class="flex justify-end">
                <Link :href="route('users.create')"
                    class="text-emerald-500 underline font-bold px-3 py-2 bg-none rounded-sm flex items-center justify-center gap-1">
                <UserPlusIcon class="h-5 w-5" />New User</Link>

                <!-- {{ users }} -->
            </div>
            <div class="mt-6">
                <Table class="border-collapse border border-slate-400">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="-indent-1">#</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[35%]">Name</TableHeaderCell>
                            <TableHeaderCell class="-indent-2">Username</TableHeaderCell>
                            <TableHeaderCell class="-indent-2 w-[20%]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(user, index) in users" :key="user.id">
                            <TableDataCell class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 indent-4">{{
                                user.name }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 indent-4">{{
                                user.username }}</TableDataCell>
                            <TableDataCell
                                class="space-x-2 border-collapse border-t border-l border-slate-400 indent-4">




                                <Button icon="pi pi-pen-to-square" severity="info" variant="text" rounded
                                    aria-label="Edit" @click="editUser(user.id)" />

                                <Button icon="pi pi-shield" severity="warn" variant="text" rounded
                                    aria-label="Change Password" @click="openChangePasswordModal(user)" />


                                <Button icon="pi pi-trash" severity="danger" variant="text" rounded aria-label="Delete"
                                    @click="
                                        confirmDeleteUser(
                                            user.id,
                                            user.name,
                                            user.username
                                        )
                                        " />
                            </TableDataCell>

                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>
        <Modal marginTop="md" maxWidth="lg" :show="showConfirmDeleteUserModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this user?
                </h2>

                <p class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold">
                    "{{ modalUserData.name }} / {{ modalUserData.username }}"
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <!-- <DangerButton @click="deleteRole(modalRoleData.id)">
                        Delete</DangerButton> -->
                    <Button label="Delete" severity="danger" raised @click="deleteUser(modalUserData.id)"
                        size="small" />
                    <Button label="Cancel" severity="secondary" @click="closeModal" size="small" />
                    <!-- <SecondaryButton @click="closeModal">Cancel</SecondaryButton> -->
                </div>
            </div>
        </Modal>

        <ChangePasswordModal :show="showChangePasswordModal" :user="selectedUser" @close="closeChangePasswordModal"
            @success="handlePasswordChangeSuccess" />
    </AdminLayout>
</template>
