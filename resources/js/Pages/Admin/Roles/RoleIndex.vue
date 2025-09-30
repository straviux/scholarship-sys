<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
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

const editRole = (roleID) => {
    // Navigate to the edit role page
    router.get(route("roles.edit", roleID));
};

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
                <Link :href="route('roles.create')"
                    class="text-emerald-500 underline font-bold px-3 py-2 bg-none rounded-sm flex items-center justify-center gap-1">
                <SquaresPlusIcon class="h-5 w-5" />New Role</Link>
            </div>
            <div class="mt-6">
                <Table class="border-collapse border border-slate-400">
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell class="px-1 w-[30px]">#</TableHeaderCell>
                            <TableHeaderCell class="px-1 w-[70%]">Role</TableHeaderCell>
                            <TableHeaderCell class="px-1 w-[25%]">Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(role, index) in roles" :key="role.id">
                            <TableDataCell class="border-collapse border-t border-slate-400 px-2">{{
                                index + 1 }}</TableDataCell>
                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-2">{{
                                role.name }}</TableDataCell>

                            <TableDataCell class="border-collapse border-t border-l border-slate-400 px-2">
                                <!-- <Link :href="route('roles.edit', role.id)" class="text-green-500 hover:text-green-600">
                                Edit</Link> -->

                                <Button icon="pi pi-pen-to-square" severity="info" variant="text" rounded
                                    aria-label="Edit" @click="editRole(role.id)" />

                                <!-- <Link
                                    :href="route('roles.destroy', role.id)"
                                    class="text-red-500 hover:text-red-600"
                                    method="DELETE"
                                    as="button"
                                    >Delete</Link
                                > -->
                                <Button icon="pi pi-trash" severity="danger" variant="text" rounded aria-label="Edit"
                                    @click="confirmDeleteRole(role.id, role.name)" />
                                <!-- <button class="text-red-500 hover:text-red-600  cursor-pointer" @click="
                                    confirmDeleteRole(role.id, role.name)
                                    ">
                                    Delete
                                </button> -->
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>
        <Modal marginTop="md" maxWidth="lg" :show="showConfirmDeleteRoleModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to delete this role?
                </h2>
                <p class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold">
                    "{{ modalRoleData.name }}"
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <!-- <DangerButton @click="deleteRole(modalRoleData.id)">
                        Delete</DangerButton> -->
                    <Button label="Delete" severity="danger" raised @click="deleteRole(modalRoleData.id)"
                        size="small" />
                    <Button label="Cancel" severity="secondary" @click="closeModal" size="small" />
                    <!-- <SecondaryButton @click="closeModal">Cancel</SecondaryButton> -->
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
