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
defineProps(["permissions"]);

const form = useForm({});

const showConfirmDeletePermissionModal = ref(false);
const modalPermissionData = ref({ id: null, name: null });

const confirmDeletePermission = (permissionID, permissionName) => {
    showConfirmDeletePermissionModal.value = true;
    modalPermissionData.value.id = permissionID;
    modalPermissionData.value.name = permissionName;
};
const closeModal = () => {
    showConfirmDeletePermissionModal.value = false;
};
const deletePermission = (permissionID) => {
    form.delete(route("permissions.destroy", permissionID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Permissions" />

    <AdminLayout>
        <template #header>Permissions</template>

        <div class="max-w-xl mx-auto py-4">
            <div class="flex justify-end">
                <Link
                    :href="route('permissions.create')"
                    class="text-emerald-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                    ><SquaresPlusIcon class="h-5 w-5" />New Permission</Link
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
                        <TableRow
                            v-for="(permission, index) in permissions"
                            :key="permission.id"
                        >
                            <TableDataCell
                                class="px-6 w-[10px] border-collapse border-t border-slate-400 -indent-1"
                                >{{ index + 1 }}</TableDataCell
                            >
                            <TableDataCell
                                class="border-collapse border-t border-l border-slate-400 indent-4"
                                >{{ permission.name }}</TableDataCell
                            >

                            <TableDataCell
                                class="space-x-6 border-collapse border-t border-l border-slate-400 indent-4"
                            >
                                <Link
                                    :href="
                                        route('permissions.edit', permission.id)
                                    "
                                    class="text-purple-500 hover:text-purple-600"
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
                                        confirmDeletePermission(
                                            permission.id,
                                            permission.name
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
            :show="showConfirmDeletePermissionModal"
            @close="closeModal"
        >
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this permission?
                </h2>
                <p
                    class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold"
                >
                    "{{ modalPermissionData.name }}"
                </p>
                <div class="mt-6 flex space-x-4">
                    <DangerButton
                        @click="deletePermission(modalPermissionData.id)"
                    >
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
