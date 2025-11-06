<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import ChangePasswordModal from "@/Pages/Admin/Users/ChangePasswordModal.vue";
import CreateUserModal from "@/Pages/Admin/Users/CreateUserModal.vue";
import EditUserModal from "@/Pages/Admin/Users/EditUserModal.vue";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// PrimeVue Components
import Panel from 'primevue/panel';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    users: Array,
    roles: Array,
    permissions: Array
});

// ============================================
// USERS TAB
// ============================================
const userGlobalFilter = ref('');
const userFirst = ref(0);
const userRows = ref(10);
const userFilters = ref({
    global: { value: null, matchMode: 'contains' }
});

watch(userGlobalFilter, (newValue) => {
    userFilters.value.global.value = newValue;
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
    router.reload({ only: ['users'] });
};

const userForm = useForm({});
const editUser = (userId) => {
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
const closeUserModal = () => {
    showConfirmDeleteUserModal.value = false;
};
const deleteUser = (userID) => {
    userForm.delete(route("users.destroy", userID), {
        onSuccess: () => {
            toast.success('User deleted successfully!');
            closeUserModal();
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

const formatRoleName = (roleName) => {
    if (!roleName) return 'Unknown Role';
    return roleName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};

// ============================================
// ROLES TAB
// ============================================
const roleGlobalFilter = ref('');
const roleFirst = ref(0);
const roleRows = ref(10);
const roleFilters = ref({
    global: { value: null, matchMode: 'contains' }
});

watch(roleGlobalFilter, (newValue) => {
    roleFilters.value.global.value = newValue;
});

const roleForm = useForm({});
const showConfirmDeleteRoleModal = ref(false);
const modalRoleData = ref({ id: null, name: null });

const editRole = (roleID) => {
    router.get(route("roles.edit", roleID));
};

const confirmDeleteRole = (roleID, roleName) => {
    showConfirmDeleteRoleModal.value = true;
    modalRoleData.value.id = roleID;
    modalRoleData.value.name = roleName;
};
const closeRoleModal = () => {
    showConfirmDeleteRoleModal.value = false;
};
const deleteRole = (roleID) => {
    roleForm.delete(route("roles.destroy", roleID), {
        onSuccess: () => closeRoleModal(),
    });
};

// ============================================
// PERMISSIONS TAB
// ============================================
const permissionGlobalFilter = ref('');
const permissionFirst = ref(0);
const permissionRows = ref(10);
const permissionFilters = ref({
    global: { value: null, matchMode: 'contains' }
});

watch(permissionGlobalFilter, (newValue) => {
    permissionFilters.value.global.value = newValue;
});

const permissionForm = useForm({});
const showConfirmDeletePermissionModal = ref(false);
const modalPermissionData = ref({ id: null, name: null });

const editPermission = (permissionId) => {
    router.get(route("permissions.edit", permissionId));
};

const confirmDeletePermission = (permissionID, permissionName) => {
    showConfirmDeletePermissionModal.value = true;
    modalPermissionData.value.id = permissionID;
    modalPermissionData.value.name = permissionName;
};
const closePermissionModal = () => {
    showConfirmDeletePermissionModal.value = false;
};
const deletePermission = (permissionID) => {
    permissionForm.delete(route("permissions.destroy", permissionID), {
        onSuccess: () => closePermissionModal(),
    });
};
</script>

<template>

    <Head title="Access Control" />

    <AdminLayout>
        <template #header>Access Control</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Main Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-lock text-xl"></i>
                        <span class="font-semibold text-lg">Access Control Management</span>
                    </div>
                </template>

                <div class="text-gray-600">
                    Manage users, roles, and permissions in one unified interface
                </div>
            </Panel>

            <!-- Tabs for Users, Roles, and Permissions -->
            <div class="mt-6">
                <TabView>
                    <!-- ============================================ -->
                    <!-- USERS TAB -->
                    <!-- ============================================ -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users"></i>
                                <span>Users</span>
                                <Tag :value="`${users.length}`" severity="info" rounded />
                            </div>
                        </template>

                        <!-- Users Search and Actions -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex-1 max-w-md">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="userGlobalFilter" placeholder="Search users..."
                                        class="w-full" />
                                </IconField>
                            </div>
                            <Button label="New User" icon="pi pi-user-plus" severity="success" raised
                                @click="openCreateUserModal" />
                        </div>

                        <!-- Users DataTable -->
                        <DataTable :value="users" stripedRows showGridlines responsiveLayout="scroll"
                            :emptyMessage="'No users found'" :globalFilterFields="['name', 'username']"
                            v-model:filters="userFilters" paginator :rows="userRows" v-model:first="userFirst"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                            :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                            <Column field="id" header="#" style="width: 50px">
                                <template #body="slotProps">
                                    <div class="text-center font-mono text-sm text-gray-500">
                                        {{ userFirst + slotProps.index + 1 }}
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
                    </TabPanel>

                    <!-- ============================================ -->
                    <!-- ROLES TAB -->
                    <!-- ============================================ -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-shield"></i>
                                <span>Roles</span>
                                <Tag :value="`${roles.length}`" severity="success" rounded />
                            </div>
                        </template>

                        <!-- Roles Search and Actions -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex-1 max-w-md">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="roleGlobalFilter" placeholder="Search roles..."
                                        class="w-full" />
                                </IconField>
                            </div>
                            <Button label="New Role" icon="pi pi-plus" severity="success" raised
                                @click="router.get(route('roles.create'))" />
                        </div>

                        <!-- Roles DataTable -->
                        <DataTable :value="roles" stripedRows showGridlines responsiveLayout="scroll"
                            :emptyMessage="'No roles found'" :globalFilterFields="['name']"
                            v-model:filters="roleFilters" paginator :rows="roleRows" v-model:first="roleFirst"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                            :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                            <Column field="id" header="#" style="width: 50px">
                                <template #body="slotProps">
                                    <div class="text-center font-mono text-sm text-gray-500">
                                        {{ roleFirst + slotProps.index + 1 }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="name" header="Role Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-800 capitalize">{{ slotProps.data.name }}</div>
                                </template>
                            </Column>

                            <Column header="Actions" style="width: 160px">
                                <template #body="slotProps">
                                    <div class="flex gap-2 justify-center">
                                        <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                            v-tooltip.top="'Edit Role'" @click="editRole(slotProps.data.id)" />
                                        <Button v-if="slotProps.data.name !== 'administrator'" icon="pi pi-trash"
                                            severity="danger" size="small" rounded outlined
                                            v-tooltip.top="'Delete Role'"
                                            @click="confirmDeleteRole(slotProps.data.id, slotProps.data.name)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- ============================================ -->
                    <!-- PERMISSIONS TAB -->
                    <!-- ============================================ -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-key"></i>
                                <span>Permissions</span>
                                <Tag :value="`${permissions.length}`" severity="warning" rounded />
                            </div>
                        </template>

                        <!-- Permissions Search and Actions -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex-1 max-w-md">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="permissionGlobalFilter" placeholder="Search permissions..."
                                        class="w-full" />
                                </IconField>
                            </div>
                            <Button label="New Permission" icon="pi pi-plus" severity="success" raised
                                @click="router.get(route('permissions.create'))" />
                        </div>

                        <!-- Permissions DataTable -->
                        <DataTable :value="permissions" stripedRows showGridlines responsiveLayout="scroll"
                            :emptyMessage="'No permissions found'" :globalFilterFields="['name']"
                            v-model:filters="permissionFilters" paginator :rows="permissionRows"
                            v-model:first="permissionFirst" :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                            :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                            <Column field="id" header="#" style="width: 50px">
                                <template #body="slotProps">
                                    <div class="text-center font-mono text-sm text-gray-500">
                                        {{ permissionFirst + slotProps.index + 1 }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="name" header="Permission Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                                </template>
                            </Column>

                            <Column header="Actions" style="width: 160px">
                                <template #body="slotProps">
                                    <div class="flex gap-2 justify-center">
                                        <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                            v-tooltip.top="'Edit Permission'"
                                            @click="editPermission(slotProps.data.id)" />
                                        <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                            v-tooltip.top="'Delete Permission'"
                                            @click="confirmDeletePermission(slotProps.data.id, slotProps.data.name)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>
                </TabView>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- USER MODALS -->
        <!-- ============================================ -->

        <!-- Delete User Confirmation Dialog -->
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
                    <Button label="Cancel" severity="secondary" @click="closeUserModal" outlined
                        :disabled="userForm.processing" />
                    <Button label="Delete User" severity="danger" @click="deleteUser(modalUserData.id)"
                        :loading="userForm.processing" icon="pi pi-trash" />
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

        <!-- ============================================ -->
        <!-- ROLE MODALS -->
        <!-- ============================================ -->

        <!-- Delete Role Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteRoleModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this role?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500">
                        <div class="font-semibold text-red-700 capitalize">{{ modalRoleData.name }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone and may affect users with this role.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeRoleModal" outlined />
                <Button label="Delete Role" severity="danger" @click="deleteRole(modalRoleData.id)" />
            </template>
        </Dialog>

        <!-- ============================================ -->
        <!-- PERMISSION MODALS -->
        <!-- ============================================ -->

        <!-- Delete Permission Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeletePermissionModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this permission?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500">
                        <div class="font-semibold text-red-700">{{ modalPermissionData.name }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone and may affect roles using this permission.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closePermissionModal" outlined />
                <Button label="Delete Permission" severity="danger" @click="deletePermission(modalPermissionData.id)" />
            </template>
        </Dialog>
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
