<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import axios from "axios";
import ChangePasswordModal from "@/Pages/Admin/Users/ChangePasswordModal.vue";
import CreateUserModal from "@/Pages/Admin/Users/CreateUserModal.vue";
import EditUserModal from "@/Pages/Admin/Users/EditUserModal.vue";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// PrimeVue Components
import Panel from 'primevue/panel';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tab from 'primevue/tab';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Checkbox from 'primevue/checkbox';

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
// ROLES & PERMISSIONS TAB (UNIFIED)
// ============================================
const roleSearchFilter = ref('');
const selectedRole = ref(null);
const rolePermissions = ref({});
const savingPermissions = ref(null);
const showCreateRoleModal = ref(false);
const newRoleForm = useForm({ name: '' });

// Computed filtered roles
const filteredRoles = computed(() => {
    if (!roleSearchFilter.value.trim()) {
        return props.roles || [];
    }
    const query = roleSearchFilter.value.toLowerCase();
    return (props.roles || []).filter(role => role.name.toLowerCase().includes(query));
});

// Initialize role-permission mapping
const initializeRolePermissions = (role) => {
    if (!rolePermissions.value[role.id]) {
        rolePermissions.value[role.id] = {};
        (props.permissions || []).forEach(permission => {
            rolePermissions.value[role.id][permission.id] =
                role.permissions?.some(p => p.id === permission.id) || false;
        });
    }
};

// Select role and initialize permissions
const selectRole = async (role) => {
    selectedRole.value = role;
    initializeRolePermissions(role);
};

// Toggle permission for role
const togglePermission = async (role, permissionId) => {
    if (!rolePermissions.value[role.id]) return;

    rolePermissions.value[role.id][permissionId] = !rolePermissions.value[role.id][permissionId];

    // Auto-save permission change
    savingPermissions.value = `${role.id}-${permissionId}`;

    try {
        const hasPermission = rolePermissions.value[role.id][permissionId];

        if (hasPermission) {
            // Add permission to role
            await axios.post(route('roles.permissions.attach'), {
                role_id: role.id,
                permission_id: permissionId
            });
            toast.success('Permission assigned');
        } else {
            // Remove permission from role
            await axios.delete(route('roles.permissions.detach', {
                role: role.id,
                permission: permissionId
            }));
            toast.success('Permission revoked');
        }
    } catch (error) {
        // Revert on error
        rolePermissions.value[role.id][permissionId] = !rolePermissions.value[role.id][permissionId];
        toast.error('Failed to update permission');
    } finally {
        savingPermissions.value = null;
    }
};

// Delete role
const confirmDeleteRole = (role) => {
    showConfirmDeleteRoleModal.value = true;
    modalRoleData.value.id = role.id;
    modalRoleData.value.name = role.name;
};

const deleteRole = () => {
    const roleForm = useForm({});
    roleForm.delete(route("roles.destroy", modalRoleData.value.id), {
        onSuccess: () => {
            toast.success('Role deleted successfully!');
            closeRoleModal();
            selectedRole.value = null;
            router.reload({ only: ['roles'] });
        },
        onError: () => {
            toast.error('Failed to delete role');
        }
    });
};

// Create role modal
const openCreateRoleModal = () => {
    newRoleForm.reset();
    showCreateRoleModal.value = true;
};

const createRole = () => {
    if (!newRoleForm.name.trim()) {
        toast.error('Role name is required');
        return;
    }

    newRoleForm.post(route('roles.store'), {
        onSuccess: () => {
            toast.success('Role created successfully!');
            closeCreateRoleModal();
            router.reload({ only: ['roles'] });
        },
        onError: (errors) => {
            toast.error(errors.name || 'Failed to create role');
        }
    });
};

const closeCreateRoleModal = () => {
    showCreateRoleModal.value = false;
    newRoleForm.reset();
};

// Delete role modal state
const showConfirmDeleteRoleModal = ref(false);
const modalRoleData = ref({ id: null, name: null });

const closeRoleModal = () => {
    showConfirmDeleteRoleModal.value = false;
};

// ============================================
// PERMISSIONS BY ROLE GROUP
// ============================================
// Group permissions by resource for easier display
const rolePermissionsByGroup = computed(() => {
    const result = {};

    if (!selectedRole.value?.id) return result;

    const groups = {};

    (props.permissions || []).forEach(permission => {
        const resourcePrefix = permission.name.split('.')[0];

        if (!groups[resourcePrefix]) {
            groups[resourcePrefix] = [];
        }
        groups[resourcePrefix].push(permission);
    });

    result[selectedRole.value.id] = groups;
    return result;
});

// ============================================
// PERMISSIONS MANAGEMENT TAB
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

// Permissions data
const allPermissions = ref(props.permissions || []);

// Permission form
const showPermissionModal = ref(false);
const isEditingPermission = ref(false);
const permissionForm = useForm({
    id: null,
    name: '',
    description: ''
});

const openCreatePermissionModal = () => {
    isEditingPermission.value = false;
    permissionForm.reset();
    showPermissionModal.value = true;
};

const openEditPermissionModal = (permission) => {
    isEditingPermission.value = true;
    permissionForm.id = permission.id;
    permissionForm.name = permission.name;
    permissionForm.description = permission.description || '';
    showPermissionModal.value = true;
};

const closePermissionModal = () => {
    showPermissionModal.value = false;
    permissionForm.reset();
};

const savePermission = async () => {
    if (!permissionForm.name.trim()) {
        toast.error('Permission name is required');
        return;
    }

    try {
        if (isEditingPermission.value) {
            // Update permission
            await axios.put(route('permissions.update', permissionForm.id), {
                name: permissionForm.name,
                description: permissionForm.description
            });
            toast.success('Permission updated successfully');
        } else {
            // Create permission
            const response = await axios.post(route('permissions.store'), {
                name: permissionForm.name,
                description: permissionForm.description
            });
            if (response.data.success) {
                allPermissions.value.push(response.data.permission);
                toast.success('Permission created successfully');
            }
        }
        closePermissionModal();
        router.reload({ only: ['permissions'] });
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to save permission');
    }
};

// Delete permission
const showConfirmDeletePermissionModal = ref(false);
const permissionToDelete = ref(null);

const confirmDeletePermission = (permission) => {
    permissionToDelete.value = permission;
    showConfirmDeletePermissionModal.value = true;
};

const closeDeletePermissionModal = () => {
    showConfirmDeletePermissionModal.value = false;
    permissionToDelete.value = null;
};

const deletePermission = async () => {
    try {
        await axios.delete(route('permissions.destroy', permissionToDelete.value.id));
        allPermissions.value = allPermissions.value.filter(p => p.id !== permissionToDelete.value.id);
        toast.success('Permission deleted successfully');
        closeDeletePermissionModal();
        router.reload({ only: ['permissions'] });
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete permission');
    }
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
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users"></i>
                                <span>Users</span>
                                <Tag :value="`${users.length}`" severity="info" rounded />
                            </div>
                        </Tab>
                        <Tab value="1">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-shield"></i>
                                <span>Roles & Permissions</span>
                                <Tag :value="`${roles.length} roles`" severity="success" rounded />
                            </div>
                        </Tab>
                        <Tab value="2">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-key"></i>
                                <span>Permissions</span>
                                <Tag :value="`${permissions.length} perms`" severity="secondary" rounded />
                            </div>
                        </Tab>
                    </TabList>

                    <TabPanels>
                        <!-- USERS TAB -->
                        <TabPanel value="0">

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
                                                :image="slotProps.data.profile_photo_url"
                                                class="border-2 border-gray-200" shape="circle" size="large" />
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
                                            <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded
                                                outlined v-tooltip.top="'Edit User'"
                                                @click="editUser(slotProps.data.id)" />
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

                        <!-- ROLES & PERMISSIONS TAB -->
                        <TabPanel value="1">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Left Sidebar: Roles List -->
                                <div class="lg:col-span-1">
                                    <h3 class="font-semibold text-gray-700 mb-3">Available Roles</h3>
                                    <Button label="New Role" icon="pi pi-plus" severity="success" raised
                                        @click="openCreateRoleModal" class="w-full mb-4" />

                                    <!-- Role Search -->
                                    <div class="mb-4">
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-search" />
                                            <InputText v-model="roleSearchFilter" placeholder="Search roles..."
                                                class="w-full text-sm" />
                                        </IconField>
                                    </div>

                                    <!-- Roles List -->
                                    <div class="space-y-2 max-h-96 overflow-y-auto">
                                        <div v-if="filteredRoles.length === 0" class="text-center text-gray-400 py-8">
                                            <i class="pi pi-inbox text-2xl mb-2"></i>
                                            <p class="text-sm">No roles found</p>
                                        </div>

                                        <button v-for="role in filteredRoles" :key="role.id" @click="selectRole(role)"
                                            :class="[
                                                'w-full text-left px-4 py-3 rounded-lg border-2 transition-all',
                                                selectedRole?.id === role.id
                                                    ? 'border-blue-500 bg-blue-50'
                                                    : 'border-gray-200 hover:border-gray-300 bg-white'
                                            ]">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-gray-700 capitalize">{{ role.name
                                                    }}</span>
                                                <Tag v-if="role.permissions" :value="`${role.permissions.length}`"
                                                    severity="info" size="small" />
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Right Panel: Role Details & Permissions -->
                                <div class="lg:col-span-2">
                                    <div v-if="!selectedRole"
                                        class="flex flex-col items-center justify-center h-96 text-gray-400">
                                        <i class="pi pi-sliders-h text-4xl mb-2"></i>
                                        <p class="text-lg font-medium">Select a role to manage permissions</p>
                                    </div>

                                    <div v-else class="space-y-6">
                                        <!-- Role Header -->
                                        <div class="border-b pb-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <h2 class="text-xl font-semibold text-gray-800 capitalize">
                                                    {{ selectedRole.name }}
                                                </h2>
                                                <div class="flex gap-2">
                                                    <Button v-if="selectedRole.name !== 'administrator'"
                                                        icon="pi pi-trash" severity="danger" size="small" rounded
                                                        outlined @click="confirmDeleteRole(selectedRole)"
                                                        v-tooltip.top="'Delete role'" />
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                Manage page access and permissions for this role
                                            </p>
                                        </div>

                                        <!-- PERMISSIONS SECTION -->
                                        <div v-if="selectedRole.id && rolePermissions[selectedRole.id]">
                                            <!-- All Permissions -->
                                            <div v-if="Object.keys(rolePermissionsByGroup[selectedRole.id] || {}).length > 0"
                                                class="space-y-4">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800 mb-3 text-sm text-gray-700">
                                                        <i class="pi pi-shield-check mr-2"></i>Permissions
                                                    </h3>
                                                </div>

                                                <div v-for="(permissions, group) in rolePermissionsByGroup[selectedRole.id]"
                                                    :key="group"
                                                    class="bg-gray-50 border border-gray-200 p-4 rounded-lg hover:shadow-sm transition-shadow">
                                                    <h4
                                                        class="font-semibold text-gray-700 capitalize mb-3 text-sm inline-flex items-center gap-2">
                                                        <i class="pi pi-bookmark text-amber-500"></i>
                                                        {{ group }}
                                                        <Tag :value="`${permissions.length}`" severity="secondary"
                                                            size="small" rounded />
                                                    </h4>
                                                    <div class="space-y-3 pl-0">
                                                        <div v-for="permission in permissions" :key="permission.id"
                                                            class="flex items-center gap-3 group hover:bg-white p-2 rounded transition-colors">
                                                            <Checkbox
                                                                :modelValue="rolePermissions[selectedRole.id][permission.id]"
                                                                :binary="true"
                                                                :disabled="savingPermissions === `${selectedRole.id}-${permission.id}`"
                                                                @update:modelValue="togglePermission(selectedRole, permission.id)"
                                                                class="flex-shrink-0" />
                                                            <label
                                                                class="flex-1 cursor-pointer text-gray-700 text-sm font-mono">
                                                                {{ permission.name }}
                                                            </label>
                                                            <i v-if="savingPermissions === `${selectedRole.id}-${permission.id}`"
                                                                class="pi pi-spin pi-spinner text-blue-500 text-xs flex-shrink-0"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- NO PERMISSIONS MESSAGE -->
                                            <div v-else
                                                class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                                <i class="pi pi-inbox text-3xl text-gray-300 mb-3"></i>
                                                <p class="text-gray-500 font-medium mb-1">No permissions available</p>
                                            </div>
                                        </div>

                                        <div v-else class="text-center text-gray-400 py-12">
                                            <i class="pi pi-circle text-2xl mb-2"></i>
                                            <p class="text-sm">Loading permissions...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- PERMISSIONS MANAGEMENT TAB -->
                        <TabPanel value="2">
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
                                    @click="openCreatePermissionModal" />
                            </div>

                            <!-- Permissions DataTable -->
                            <DataTable :value="permissions" stripedRows showGridlines responsiveLayout="scroll"
                                :emptyMessage="'No permissions found'" :globalFilterFields="['name', 'description']"
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

                                <Column field="name" header="Permission Name">
                                    <template #body="slotProps">
                                        <div class="font-mono text-sm text-gray-700">
                                            {{ slotProps.data.name }}
                                        </div>
                                    </template>
                                </Column>

                                <Column field="description" header="Description">
                                    <template #body="slotProps">
                                        <div class="text-sm text-gray-600">
                                            {{ slotProps.data.description || '-' }}
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 100px">
                                    <template #body="slotProps">
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-pencil" severity="warning" size="small" rounded text
                                                @click="openEditPermissionModal(slotProps.data)"
                                                v-tooltip.top="'Edit permission'" />
                                            <Button icon="pi pi-trash" severity="danger" size="small" rounded text
                                                @click="confirmDeletePermission(slotProps.data)"
                                                v-tooltip.top="'Delete permission'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
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

        <!-- Create Role Modal -->
        <Dialog v-model:visible="showCreateRoleModal" modal header="Create New Role" :style="{ width: '450px' }"
            :closable="false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Role Name
                        <span class="text-red-500 font-bold">*</span>
                    </label>
                    <InputText v-model="newRoleForm.name" placeholder="e.g., data_manager" class="w-full"
                        :disabled="newRoleForm.processing" />
                    <p class="text-xs text-gray-500 mt-1">Use underscores for multi-word roles</p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeCreateRoleModal" outlined
                    :disabled="newRoleForm.processing" />
                <Button label="Create Role" severity="success" @click="createRole" :loading="newRoleForm.processing"
                    :disabled="!newRoleForm.name.trim()" />
            </template>
        </Dialog>

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
                <Button label="Delete Role" severity="danger" @click="deleteRole" />
            </template>
        </Dialog>

        <!-- ============================================ -->
        <!-- PERMISSION MODALS -->
        <!-- ============================================ -->

        <!-- Create/Edit Permission Modal -->
        <Dialog v-model:visible="showPermissionModal"
            :header="isEditingPermission ? 'Edit Permission' : 'Create Permission'" :modal="true"
            @hide="closePermissionModal">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permission Name *</label>
                    <InputText v-model="permissionForm.name" placeholder="e.g., users.create, reports.view"
                        class="w-full" />
                    <p class="text-xs text-gray-500 mt-1">Format: resource.action (e.g., users.create, reports.view)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <InputText v-model="permissionForm.description" placeholder="Optional description" class="w-full" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closePermissionModal" outlined />
                <Button :label="isEditingPermission ? 'Update' : 'Create'" severity="success" @click="savePermission"
                    :disabled="!permissionForm.name" />
            </template>
        </Dialog>

        <!-- Delete Permission Confirmation Modal -->
        <Dialog v-model:visible="showConfirmDeletePermissionModal" header="Delete Permission" :modal="true"
            @hide="closeDeletePermissionModal">
            <div v-if="permissionToDelete" class="space-y-4">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-2">
                        <i class="pi pi-exclamation-triangle mr-2"></i>Confirm Deletion
                    </h4>
                    <p class="text-sm text-red-800">
                        Are you sure you want to delete permission <strong>{{ permissionToDelete.name }}</strong>?
                    </p>
                    <p class="text-sm text-red-700 mt-2">
                        This will remove this permission from all roles that have it.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeDeletePermissionModal" outlined />
                <Button label="Delete Permission" severity="danger" @click="deletePermission" icon="pi pi-trash" />
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
