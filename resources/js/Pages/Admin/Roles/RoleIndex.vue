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
import { SquaresPlusIcon } from "@heroicons/vue/20/solid";
defineProps(["roles"]);

const form = useForm({});

const showConfirmDeleteRoleModal = ref(false);
const modalRoleData = ref({ id: null, name: null });

const confirmDeleteRole = (roleID, roleName) => {
    showConfirmDeleteRoleModal.value = true;
    modalRoleData.value.id = roleID;
    modalRoleData.value.name = roleName;
};
const closeModal = () => {
    showConfirmDeleteRoleModal.value = false;
};
const deleteRole = (roleID) => {
    form.delete(route("roles.destroy", roleID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Roles" />

    <AdminLayout>
        <template #header>Roles</template>

        <div class="max-w-xl mx-auto py-4">
            <div class="flex justify-end">
                <Link
                    :href="route('roles.create')"
                    class="text-emerald-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                    ><SquaresPlusIcon class="h-5 w-5" />New Role</Link
                >
            </div>
            <div class="mt-6">
                <Table class="border-collapse border border-slate-400">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="-indent-1"
                                >#</TableHeaderCell
                            >
                            <TableHeaderCell class="-indent-2 w-[70%]"
                                >Role</TableHeaderCell
                            >
                            <TableHeaderCell class="-indent-2 w-[25%]"
                                >Action</TableHeaderCell
                            >
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(role, index) in roles" :key="role.id">
                            <TableDataCell
                                class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1"
                                >{{ index + 1 }}</TableDataCell
                            >
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 indent-4"
                                >{{ role.name }}</TableDataCell
                            >

                            <TableDataCell
                                class="space-x-6 border-collapse border-t border-l border-slate-400 indent-4"
                            >
                                <Link
                                    :href="route('roles.edit', role.id)"
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
                                        confirmDeleteRole(role.id, role.name)
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
            :show="showConfirmDeleteRoleModal"
            @close="closeModal"
        >
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this role?
                </h2>
                <p
                    class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold"
                >
                    "{{ modalRoleData.name }}"
                </p>

                <div class="mt-6 flex space-x-4">
                    <DangerButton @click="deleteRole(modalRoleData.id)">
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
