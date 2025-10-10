<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
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

// Helper function to format role names for display
const formatRoleName = (roleName) => {
    if (!roleName) return 'Unknown Role';

    // Replace underscores with spaces and capitalize each word
    return roleName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};
</script>

<template>

    <Head title="Users" />

    <AdminLayout>
        <template #header>Users</template>

        <div class="max-w-6xl mx-auto py-4">
            <!-- Header with Add User Button -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-users text-xl"></i>
                        <span class="font-semibold text-lg">User Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Manage system users and their access levels
                    </div>
                    <Button label="New User" icon="pi pi-user-plus" severity="success" raised
                        @click="router.get(route('users.create'))" />
                </div>
            </Panel>

            <!-- Users DataTable -->
            <div class="mt-6">
                <DataTable :value="users" stripedRows showGridlines responsiveLayout="scroll" :paginator="true"
                    :rows="10"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="{first} to {last} of {totalRecords}"
                    :rowsPerPageOptions="[5, 10, 20, 50]">
                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">System Users</h3>
                            <Tag :value="`${users.length} users`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="Full Name">
                        <template #body="slotProps">
                            <div class="flex items-center gap-3">
                                <Avatar v-if="slotProps.data.has_profile_photo"
                                    :image="slotProps.data.profile_photo_url" class="border-2 border-gray-200"
                                    shape="circle" size="large" />
                                <Avatar v-else :label="slotProps.data.name.charAt(0).toUpperCase()"
                                    class="bg-blue-500 text-white" shape="circle" size="large" />
                                <div>
                                    <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">@{{ slotProps.data.username }}</div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="roles" header="Role">
                        <template #body="slotProps">
                            <span v-if="slotProps.data.roles && slotProps.data.roles.length > 0"
                                class="font-medium text-gray-700">
                                {{ formatRoleName(slotProps.data.roles[0].name) }}
                            </span>
                            <span v-else class="text-gray-400 italic">
                                No Role
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 200px">
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center">
                                <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'Edit User'" @click="editUser(slotProps.data.id)" />

                                <Button icon="pi pi-shield" severity="warn" size="small" rounded outlined
                                    v-tooltip.top="'Change Password'"
                                    @click="openChangePasswordModal(slotProps.data)" />

                                <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                    v-tooltip.top="'Delete User'"
                                    @click="confirmDeleteUser(slotProps.data.id, slotProps.data.name, slotProps.data.username)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteUserModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this user?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500">
                        <div class="font-semibold text-red-700">{{ modalUserData.name }}</div>
                        <div class="text-sm text-gray-600">Username: {{ modalUserData.username }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined />
                <Button label="Delete User" severity="danger" @click="deleteUser(modalUserData.id)"
                    :loading="form.processing" />
            </template>
        </Dialog>

        <!-- Change Password Modal -->
        <ChangePasswordModal :show="showChangePasswordModal" :user="selectedUser" @close="closeChangePasswordModal"
            @success="handlePasswordChangeSuccess" />
    </AdminLayout>
</template>
