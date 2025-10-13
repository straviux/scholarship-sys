<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import ChangePasswordModal from "@/Pages/Admin/Users/ChangePasswordModal.vue";
import CreateUserModal from "@/Pages/Admin/Users/CreateUserModal.vue";
import EditUserModal from "@/Pages/Admin/Users/EditUserModal.vue";
import { UserPlusIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// PrimeVue Components
import Panel from 'primevue/panel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps(["users", "roles"]);

// Search and pagination
const globalFilter = ref('');
const first = ref(0);
const rows = ref(10);
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Watch for changes in globalFilter and update filters
watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});

// Change Password Modal
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

// Create User Modal
const showCreateUserModal = ref(false);
const openCreateUserModal = () => {
    showCreateUserModal.value = true;
};
const closeCreateUserModal = () => {
    showCreateUserModal.value = false;
};
const handleCreateUserSuccess = () => {
    closeCreateUserModal();
    // Refresh the page to show new user
    router.reload({ only: ['users'] });
};

// Edit User Modal
const showEditUserModal = ref(false);
const selectedUserForEdit = ref(null);
const openEditUserModal = (user) => {
    selectedUserForEdit.value = user;
    showEditUserModal.value = true;
};
const closeEditUserModal = () => {
    showEditUserModal.value = false;
    selectedUserForEdit.value = null;
};
const handleEditUserSuccess = () => {
    closeEditUserModal();
    // Refresh the page to show updated user
    router.reload({ only: ['users'] });
};

const form = useForm({});

const editUser = (userId) => {
    // Find the user and open edit modal
    const user = props.users.find(u => u.id === userId);
    if (user) {
        openEditUserModal(user);
    }
};

// Delete User Modal
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
        onSuccess: () => {
            toast.success('User deleted successfully!');
            closeModal();
        },
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

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
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
                        @click="openCreateUserModal" />
                </div>
            </Panel>

            <!-- Search Section -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search users..." class="w-full" />
                        </IconField>
                    </div>
                </div>
            </div>

            <!-- Users DataTable -->
            <div class="mt-6">
                <DataTable :value="users" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No data to be displayed'" :globalFilterFields="['name', 'username']"
                    v-model:filters="filters" paginator :rows="rows" v-model:first="first"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">System Users</h3>
                            <Tag :value="`${users.length} users`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="User Details">
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

                    <Column field="roles" header="Role" style="min-width: 150px">
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

                    <Column header="Actions" style="width: 160px">
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
            :modal="true" :closable="false">
            <div class="flex items-start gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500 mt-1"></i>
                <div class="flex-1">
                    <p class="text-lg font-semibold text-gray-800 mb-3">
                        Are you sure you want to delete this user?
                    </p>
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
                        <div class="flex items-center gap-3 mb-2">
                            <Avatar :label="modalUserData.name?.charAt(0)?.toUpperCase()" class="bg-red-500 text-white"
                                shape="circle" size="normal" />
                            <div>
                                <div class="font-semibold text-red-800">{{ modalUserData.name }}</div>
                                <div class="text-sm text-red-600">@{{ modalUserData.username }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-md">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-exclamation-circle text-amber-600"></i>
                            <span class="text-sm text-amber-800 font-medium">Warning</span>
                        </div>
                        <p class="text-sm text-amber-700 mt-1">
                            This action cannot be undone. All data associated with this user will be permanently
                            removed.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <Button label="Cancel" severity="secondary" @click="closeModal" outlined
                        :disabled="form.processing" />
                    <Button label="Delete User" severity="danger" @click="deleteUser(modalUserData.id)"
                        :loading="form.processing" icon="pi pi-trash" />
                </div>
            </template>
        </Dialog>

        <!-- Change Password Modal -->
        <ChangePasswordModal :show="showChangePasswordModal" :user="selectedUser" @close="closeChangePasswordModal"
            @success="handlePasswordChangeSuccess" />

        <!-- Create User Modal -->
        <CreateUserModal :show="showCreateUserModal" :roles="roles" @update:show="showCreateUserModal = $event"
            @success="handleCreateUserSuccess" />

        <!-- Edit User Modal -->
        <EditUserModal :show="showEditUserModal" :user="selectedUserForEdit" :roles="roles"
            @update:show="showEditUserModal = $event" @success="handleEditUserSuccess" />
    </AdminLayout>
</template>

<style scoped>
/* Loading states */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>