<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AppIcon from "@/Components/ui/AppIcon.vue";
import AppButton from "@/Components/ui/AppButton.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import axios from "axios";
import ChangePasswordModal from "@/Pages/Admin/Users/ChangePasswordModal.vue";
import CreateUserModal from "@/Pages/Admin/Users/CreateUserModal.vue";
import EditUserModal from "@/Pages/Admin/Users/EditUserModal.vue";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// PrimeVue Components

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

const isProtectedRole = (roleName) => roleName === 'administrator';

// ============================================
// ROLES & PERMISSIONS TAB (UNIFIED)
// ============================================
const roleSearchFilter = ref('');
const selectedRole = ref(null);
const rolePermissions = ref({});
const savingPermissionAssignments = ref(false);
const rolePermissionSnapshots = ref({});
const showPermissionAssignmentModal = ref(false);
const showRoleModal = ref(false);
const isEditingRole = ref(false);
const roleForm = useForm({
    id: null,
    name: '',
});

// Computed filtered roles
const filteredRoles = computed(() => {
    if (!roleSearchFilter.value.trim()) {
        return props.roles || [];
    }
    const query = roleSearchFilter.value.toLowerCase();
    return (props.roles || []).filter(role => role.name.toLowerCase().includes(query));
});

// Initialize role-permission mapping
const initializeRolePermissions = (role, force = false) => {
    if (force || !rolePermissions.value[role.id]) {
        rolePermissions.value[role.id] = {};
        (props.permissions || []).forEach(permission => {
            rolePermissions.value[role.id][permission.id] =
                role.permissions?.some(p => p.id === permission.id) || false;
        });
    }
};

const syncRolePermissionSnapshot = (role) => {
    if (!role?.id || !rolePermissions.value[role.id]) {
        return;
    }

    rolePermissionSnapshots.value[role.id] = {
        ...rolePermissions.value[role.id]
    };
};

// Select role and initialize permissions
const selectRole = (role, force = false) => {
    selectedRole.value = role;
    initializeRolePermissions(role, force);
};

const openPermissionAssignmentModal = (role) => {
    selectRole(role, true);
    syncRolePermissionSnapshot(role);
    clearRolePermissionFilters();
    showPermissionAssignmentModal.value = true;
};

const closePermissionAssignmentModal = () => {
    if (selectedRole.value) {
        initializeRolePermissions(selectedRole.value, true);
        syncRolePermissionSnapshot(selectedRole.value);
    }

    savingPermissionAssignments.value = false;
    showPermissionAssignmentModal.value = false;
};

watch(() => props.roles, (roles) => {
    if (!selectedRole.value?.id) {
        return;
    }

    const refreshedRole = (roles || []).find(role => role.id === selectedRole.value.id);

    if (!refreshedRole) {
        selectedRole.value = null;
        showPermissionAssignmentModal.value = false;
        return;
    }

    selectedRole.value = refreshedRole;
    initializeRolePermissions(refreshedRole, true);
    syncRolePermissionSnapshot(refreshedRole);
});

// Toggle permission for role
const togglePermission = (role, permissionId) => {
    if (!rolePermissions.value[role.id] || savingPermissionAssignments.value) return;

    rolePermissions.value[role.id][permissionId] = !rolePermissions.value[role.id][permissionId];
};

const hasPendingPermissionChanges = computed(() => {
    const roleId = selectedRole.value?.id;

    if (!roleId) {
        return false;
    }

    const currentRolePermissions = rolePermissions.value[roleId] || {};
    const snapshot = rolePermissionSnapshots.value[roleId] || {};

    return (props.permissions || []).some(permission => {
        return Boolean(currentRolePermissions[permission.id]) !== Boolean(snapshot[permission.id]);
    });
});

const savePermissionAssignments = () => {
    if (!selectedRole.value?.id || savingPermissionAssignments.value || !hasPendingPermissionChanges.value) {
        return;
    }

    const currentRolePermissions = rolePermissions.value[selectedRole.value.id] || {};
    const selectedPermissions = (props.permissions || [])
        .filter(permission => Boolean(currentRolePermissions[permission.id]))
        .map(permission => ({ name: permission.name }));

    savingPermissionAssignments.value = true;

    router.put(route('roles.update', selectedRole.value.id), {
        name: selectedRole.value.name,
        permissions: selectedPermissions,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            syncRolePermissionSnapshot(selectedRole.value);
            toast.success('Permissions updated successfully!');
        },
        onError: (errors) => {
            toast.error(errors.name || 'Failed to update permissions');
        },
        onFinish: () => {
            savingPermissionAssignments.value = false;
        }
    });
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
    isEditingRole.value = false;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.id = null;
    showRoleModal.value = true;
};

const openEditRoleModal = (role) => {
    isEditingRole.value = true;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.id = role.id;
    roleForm.name = role.name;
    showRoleModal.value = true;
};

const saveRole = () => {
    if (!roleForm.name.trim()) {
        toast.error('Role name is required');
        return;
    }

    const trimmedName = roleForm.name.trim();
    const requestOptions = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (selectedRole.value?.id === roleForm.id) {
                selectedRole.value = {
                    ...selectedRole.value,
                    name: trimmedName,
                };
            }

            toast.success(isEditingRole.value ? 'Role updated successfully!' : 'Role created successfully!');
            closeRoleEditorModal();
            router.reload({ only: ['roles'], preserveScroll: true, preserveState: true });
        },
        onError: (errors) => {
            toast.error(errors.name || `Failed to ${isEditingRole.value ? 'update' : 'create'} role`);
        }
    };

    roleForm.name = trimmedName;

    if (isEditingRole.value) {
        roleForm.put(route('roles.update', roleForm.id), requestOptions);
        return;
    }

    roleForm.post(route('roles.store'), requestOptions);
};

const closeRoleEditorModal = () => {
    showRoleModal.value = false;
    isEditingRole.value = false;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.id = null;
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
const rolePermissionSearchFilter = ref('');
const showAssignedRolePermissionsOnly = ref(false);
const activePermissionGroup = ref('all');

const formatPermissionGroupName = (groupName) => {
    return formatRoleName((groupName || '').replace(/[.-]/g, '_'));
};

const formatPermissionAction = (permissionName) => {
    const actionSegments = (permissionName || '').split('.').slice(1);

    if (!actionSegments.length) {
        return formatPermissionGroupName(permissionName);
    }

    return actionSegments
        .map(segment => formatRoleName(segment.replace(/[.-]/g, '_')))
        .join(' / ');
};

const clearRolePermissionFilters = () => {
    rolePermissionSearchFilter.value = '';
    showAssignedRolePermissionsOnly.value = false;
    activePermissionGroup.value = 'all';
};

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

const selectedRolePermissionGroups = computed(() => {
    if (!selectedRole.value?.id) {
        return [];
    }

    const query = rolePermissionSearchFilter.value.trim().toLowerCase();
    const currentRolePermissions = rolePermissions.value[selectedRole.value.id] || {};

    return Object.entries(rolePermissionsByGroup.value[selectedRole.value.id] || {})
        .map(([groupKey, permissions]) => {
            const assignedCount = permissions.filter(permission => Boolean(currentRolePermissions[permission.id])).length;

            const filteredPermissions = permissions
                .filter((permission) => {
                    const isAssigned = Boolean(currentRolePermissions[permission.id]);
                    const matchesAssignedState = !showAssignedRolePermissionsOnly.value || isAssigned;

                    if (!matchesAssignedState) {
                        return false;
                    }

                    if (!query) {
                        return true;
                    }

                    return [
                        permission.name,
                        permission.description || '',
                        formatPermissionAction(permission.name),
                        formatPermissionGroupName(groupKey),
                    ].some(value => value.toLowerCase().includes(query));
                })
                .sort((left, right) => {
                    const leftAssigned = Boolean(currentRolePermissions[left.id]);
                    const rightAssigned = Boolean(currentRolePermissions[right.id]);

                    if (leftAssigned !== rightAssigned) {
                        return Number(rightAssigned) - Number(leftAssigned);
                    }

                    return left.name.localeCompare(right.name);
                });

            return {
                key: groupKey,
                label: formatPermissionGroupName(groupKey),
                permissions: filteredPermissions,
                totalCount: permissions.length,
                assignedCount,
                matchingCount: filteredPermissions.length,
            };
        })
        .sort((left, right) => left.label.localeCompare(right.label));
});

const visibleRolePermissionGroups = computed(() => {
    const groups = selectedRolePermissionGroups.value;

    if (activePermissionGroup.value === 'all') {
        return groups.filter(group => group.matchingCount > 0);
    }

    return groups.filter(group => group.key === activePermissionGroup.value && group.matchingCount > 0);
});

const rolePermissionOverview = computed(() => {
    const groups = selectedRolePermissionGroups.value;

    return {
        groupCount: groups.length,
        matchingGroups: groups.filter(group => group.matchingCount > 0).length,
        totalPermissions: groups.reduce((total, group) => total + group.totalCount, 0),
        assignedPermissions: groups.reduce((total, group) => total + group.assignedCount, 0),
        visiblePermissions: visibleRolePermissionGroups.value.reduce((total, group) => total + group.matchingCount, 0),
    };
});

watch(() => selectedRole.value?.id, () => {
    clearRolePermissionFilters();
});

watch(selectedRolePermissionGroups, (groups) => {
    if (activePermissionGroup.value !== 'all' && !groups.some(group => group.key === activePermissionGroup.value)) {
        activePermissionGroup.value = 'all';
    }
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

// ============================================
// PERMISSION CLEANUP
// ============================================
const showConfirmCleanupModal = ref(false);
const isCleaningUp = ref(false);
const cleanupResults = ref(null);
const showCleanupResults = ref(false);

const openConfirmCleanupModal = () => {
    showConfirmCleanupModal.value = true;
};

const closeConfirmCleanupModal = () => {
    showConfirmCleanupModal.value = false;
};

const closeCleanupResultsModal = () => {
    showCleanupResults.value = false;
    cleanupResults.value = null;
    router.reload({ only: ['permissions'] });
};

const runCleanup = async () => {
    isCleaningUp.value = true;
    closeConfirmCleanupModal();

    try {
        const response = await axios.post(route('permissions.cleanup'));

        if (response.data.success) {
            cleanupResults.value = response.data.results;
            showCleanupResults.value = true;
            toast.success('Permission cleanup completed successfully!');
        } else {
            toast.error('Cleanup completed with warnings');
            cleanupResults.value = response.data.results;
            showCleanupResults.value = true;
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to run permission cleanup');
        console.error('Cleanup error:', error);
    } finally {
        isCleaningUp.value = false;
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
                        <AppIcon name="lock" :size="20" />
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
                                <AppIcon name="users" />
                                <span>Users</span>
                                <Tag :value="`${users.length}`" severity="info" rounded />
                            </div>
                        </Tab>
                        <Tab value="1">
                            <div class="flex items-center gap-2">
                                <AppIcon name="shield" />
                                <span>Roles & Permissions</span>
                                <Tag :value="`${roles.length} roles`" severity="success" rounded />
                            </div>
                        </Tab>
                        <Tab value="2">
                            <div class="flex items-center gap-2">
                                <AppIcon name="key" />
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
                                        <InputIcon>
                                            <AppIcon name="search" :size="14" />
                                        </InputIcon>
                                        <InputText v-model="userGlobalFilter" placeholder="Search users..."
                                            class="w-full" />
                                    </IconField>
                                </div>
                                <AppButton label="New User" icon="user-plus" severity="success" raised
                                    @click="openCreateUserModal" />
                            </div>

                            <!-- Users DataTable -->
                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="users"
                                stripedRows showGridlines responsiveLayout="scroll" :emptyMessage="'No users found'"
                                :globalFilterFields="['name', 'username']" v-model:filters="userFilters" paginator
                                :rows="userRows" v-model:first="userFirst" :rowsPerPageOptions="[5, 10, 20, 50]"
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
                                            <AppButton icon="pen-to-square" severity="info" size="small" rounded
                                                outlined v-tooltip.top="'Edit User'"
                                                @click="editUser(slotProps.data.id)" />
                                            <AppButton icon="shield" severity="warn" size="small" rounded outlined
                                                v-tooltip.top="'Change Password'"
                                                @click="openChangePasswordModal(slotProps.data)" />
                                            <AppButton icon="trash" severity="danger" size="small" rounded outlined
                                                v-tooltip.top="'Delete User'"
                                                @click="confirmDeleteUser(slotProps.data.id, slotProps.data.name, slotProps.data.username)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                        <!-- ROLES & PERMISSIONS TAB -->
                        <TabPanel value="1">
                            <div class="space-y-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-700">Available Roles</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Manage roles from the table. Permission assignment and role create/edit open
                                            in
                                            modals.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                        <div class="w-full sm:w-72">
                                            <IconField iconPosition="left">
                                                <InputIcon>
                                                    <AppIcon name="search" :size="14" />
                                                </InputIcon>
                                                <InputText v-model="roleSearchFilter" placeholder="Search roles..."
                                                    class="w-full text-sm" />
                                            </IconField>
                                        </div>

                                        <AppButton label="New Role" icon="plus" severity="success" raised
                                            @click="openCreateRoleModal" />
                                    </div>
                                </div>

                                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                    :value="filteredRoles" stripedRows showGridlines responsiveLayout="scroll"
                                    :emptyMessage="'No roles found'">
                                    <Column field="id" header="#" style="width: 50px">
                                        <template #body="slotProps">
                                            <div class="text-center font-mono text-sm text-gray-500">
                                                {{ slotProps.index + 1 }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="name" header="Role" style="min-width: 260px">
                                        <template #body="slotProps">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-semibold text-gray-800">
                                                        {{ formatRoleName(slotProps.data.name) }}
                                                    </span>
                                                    <Tag v-if="isProtectedRole(slotProps.data.name)" value="Protected"
                                                        severity="contrast" size="small" rounded />
                                                </div>
                                                <p class="mt-1 text-xs font-mono text-gray-500">
                                                    {{ slotProps.data.name }}
                                                </p>
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Assigned Permissions" style="width: 180px">
                                        <template #body="slotProps">
                                            <div class="flex items-center gap-2">
                                                <Tag :value="`${slotProps.data.permissions?.length || 0}`"
                                                    severity="info" rounded />
                                                <span class="text-sm text-gray-600">assigned</span>
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Actions" style="min-width: 80px">
                                        <template #body="slotProps">
                                            <div class="flex flex-wrap gap-2">
                                                <AppButton icon="shield-check" v-tooltip="'Assign Permissions'"
                                                    severity="info" rounded
                                                    @click="openPermissionAssignmentModal(slotProps.data)" />
                                                <AppButton v-if="!isProtectedRole(slotProps.data.name)"
                                                    icon="pen-to-square" severity="warn" outlined rounded
                                                    v-tooltip="'Edit Role'"
                                                    @click="openEditRoleModal(slotProps.data)" />
                                                <AppButton v-if="!isProtectedRole(slotProps.data.name)" icon="trash"
                                                    severity="danger" outlined rounded
                                                    @click="confirmDeleteRole(slotProps.data)" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <!-- PERMISSIONS MANAGEMENT TAB -->
                        <TabPanel value="2">
                            <!-- Permissions Search and Actions -->
                            <div class="flex justify-between items-center mb-4 gap-4">
                                <div class="flex-1 max-w-md">
                                    <IconField iconPosition="left">
                                        <InputIcon>
                                            <AppIcon name="search" :size="14" />
                                        </InputIcon>
                                        <InputText v-model="permissionGlobalFilter" placeholder="Search permissions..."
                                            class="w-full" />
                                    </IconField>
                                </div>
                                <div class="flex gap-2">
                                    <AppButton label="Cleanup Permissions" icon="wrench" severity="warning" raised
                                        @click="openConfirmCleanupModal"
                                        v-tooltip.top="'Fix orphaned and duplicate permission records'" />
                                    <AppButton label="New Permission" icon="plus" severity="success" raised
                                        @click="openCreatePermissionModal" />
                                </div>
                            </div>

                            <!-- Permissions DataTable -->
                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="permissions"
                                stripedRows showGridlines responsiveLayout="scroll"
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
                                            <AppButton icon="pencil" severity="warning" size="small" rounded text
                                                @click="openEditPermissionModal(slotProps.data)"
                                                v-tooltip.top="'Edit permission'" />
                                            <AppButton icon="trash" severity="danger" size="small" rounded text
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
        <Dialog v-model:visible="showConfirmDeleteUserModal" :style="{ width: 'calc(100vw - 2rem)', maxWidth: '450px' }"
            header="Confirm Deletion" :modal="true" :closable="false">
            <div class="flex items-start gap-4">
                <AppIcon name="exclamation-triangle" class="text-3xl text-red-500 mt-1" />
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
                            <AppIcon name="exclamation-circle" class="text-amber-600" />
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
                    <AppButton label="Delete User" severity="danger" @click="deleteUser(modalUserData.id)"
                        :loading="userForm.processing" icon="trash" />
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

        <!-- Permission Assignment Modal -->
        <Dialog v-model:visible="showPermissionAssignmentModal" modal
            :header="selectedRole ? `Assign Permissions · ${formatRoleName(selectedRole.name)}` : 'Assign Permissions'"
            :style="{ width: 'min(1200px, 96vw)' }" @hide="closePermissionAssignmentModal">
            <div v-if="selectedRole && rolePermissions[selectedRole.id]"
                class="space-y-4 max-h-[76vh] overflow-y-auto pr-1">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600">Review changes before saving</p>
                            <p class="mt-1 text-sm text-gray-500">
                                Toggle permissions below, then save once you are ready to apply them to {{
                                    formatRoleName(selectedRole.name)
                                }}.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Tag v-if="hasPendingPermissionChanges" value="Unsaved changes" severity="warn" rounded />
                            <Tag :value="`${rolePermissionOverview.assignedPermissions}/${rolePermissionOverview.totalPermissions} assigned`"
                                severity="success" rounded />
                            <Tag :value="`${rolePermissionOverview.groupCount} groups`" severity="secondary" rounded />
                        </div>
                    </div>
                </div>

                <div v-if="selectedRolePermissionGroups.length > 0" class="space-y-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1 max-w-2xl">
                                <IconField iconPosition="left">
                                    <InputIcon>
                                        <AppIcon name="search" :size="14" />
                                    </InputIcon>
                                    <InputText v-model="rolePermissionSearchFilter"
                                        placeholder="Search permission key, action, or description..." class="w-full" />
                                </IconField>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div
                                    class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2">
                                    <span class="text-sm text-gray-600">Assigned only</span>
                                    <ToggleSwitch v-model="showAssignedRolePermissionsOnly" size="small" />
                                </div>
                                <AppButton icon="x-circle" label="Clear" severity="secondary" text
                                    @click="clearRolePermissionFilters"
                                    :disabled="!rolePermissionSearchFilter && !showAssignedRolePermissionsOnly && activePermissionGroup === 'all'" />
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                            <Tag :value="`${rolePermissionOverview.visiblePermissions} visible`" severity="info"
                                rounded />
                            <Tag :value="`${rolePermissionOverview.assignedPermissions}/${rolePermissionOverview.totalPermissions} assigned`"
                                severity="success" rounded />
                            <Tag :value="`${rolePermissionOverview.matchingGroups}/${rolePermissionOverview.groupCount} groups`"
                                severity="secondary" rounded />
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <div class="rounded-xl border border-gray-200 bg-white p-3 lg:sticky lg:top-0">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-gray-700">
                                    <AppIcon name="map" class="mr-2" />Navigate Groups
                                </h3>
                            </div>

                            <div class="flex gap-4">
                                <div class="space-y-2  pr-1 w-1/2">
                                    <button type="button" @click="activePermissionGroup = 'all'" :class="[
                                        'w-full rounded-lg border px-3 py-2.5 text-left transition-all',
                                        activePermissionGroup === 'all'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-white'
                                    ]">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium">All Groups</span>
                                            <Tag :value="`${rolePermissionOverview.matchingGroups}`" severity="info"
                                                size="small" rounded />
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ rolePermissionOverview.assignedPermissions }} assigned permissions
                                        </p>
                                    </button>

                                    <button v-for="group in selectedRolePermissionGroups" :key="group.key" type="button"
                                        @click="activePermissionGroup = group.key" :class="[
                                            'w-full rounded-lg border px-3 py-2.5 text-left transition-all',
                                            activePermissionGroup === group.key
                                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-white'
                                        ]">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium truncate">{{ group.label }}</span>
                                            <Tag :value="`${group.matchingCount}`" severity="secondary" size="small"
                                                rounded />
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ group.assignedCount }}/{{ group.totalCount }} assigned
                                        </p>
                                    </button>
                                </div>

                                <div class="space-y-4 w-1/2">
                                    <div v-if="visibleRolePermissionGroups.length === 0"
                                        class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                        <AppIcon name="search" class="text-3xl text-gray-300 mb-3" />
                                        <p class="text-gray-600 font-medium mb-1">No permissions match the current
                                            filters</p>
                                        <p class="text-sm text-gray-500 mb-4">
                                            Try a different keyword, switch back to all groups, or show all permissions.
                                        </p>
                                        <AppButton icon="x-circle" label="Reset Filters" severity="secondary" outlined
                                            @click="clearRolePermissionFilters" />
                                    </div>

                                    <div v-for="group in visibleRolePermissionGroups" :key="group.key"
                                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div
                                            class="mb-4 flex flex-col gap-2 border-b border-gray-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-800 inline-flex items-center gap-2">
                                                    <AppIcon name="bookmark" class="text-amber-500" />
                                                    {{ group.label }}
                                                </h4>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ group.assignedCount }} assigned of {{ group.totalCount }}
                                                    permissions
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <Tag :value="`${group.matchingCount} visible`" severity="info"
                                                    size="small" rounded />
                                                <Tag :value="`${group.totalCount} total`" severity="secondary"
                                                    size="small" rounded />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 2xl:grid-cols-2 gap-3">
                                            <div v-for="permission in group.permissions" :key="permission.id"
                                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 transition-colors hover:border-blue-200 hover:bg-blue-50/40">
                                                <div class="flex items-start gap-3">
                                                    <Checkbox
                                                        :modelValue="rolePermissions[selectedRole.id][permission.id]"
                                                        :binary="true" :disabled="savingPermissionAssignments"
                                                        @update:modelValue="togglePermission(selectedRole, permission.id)"
                                                        class="mt-1 flex-shrink-0" />

                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="font-medium text-gray-800">
                                                                {{ formatPermissionAction(permission.name) }}
                                                            </span>
                                                            <Tag :value="rolePermissions[selectedRole.id][permission.id] ? 'Assigned' : 'Not Assigned'"
                                                                :severity="rolePermissions[selectedRole.id][permission.id] ? 'success' : 'contrast'"
                                                                size="small" rounded />
                                                        </div>

                                                        <p class="mt-1 break-all text-xs font-mono text-gray-500">
                                                            {{ permission.name }}
                                                        </p>

                                                        <p v-if="permission.description"
                                                            class="mt-2 text-xs text-gray-600">
                                                            {{ permission.description }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

                <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <AppIcon name="inbox" class="text-3xl text-gray-300 mb-3" />
                    <p class="text-gray-500 font-medium mb-1">No permissions available</p>
                </div>
            </div>

            <div v-else class="text-center text-gray-400 py-12">
                <AppIcon name="circle" class="text-2xl mb-2" />
                <p class="text-sm">Loading permissions...</p>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" outlined @click="closePermissionAssignmentModal"
                    :disabled="savingPermissionAssignments" />
                <Button label="Save Changes" severity="success" @click="savePermissionAssignments"
                    :loading="savingPermissionAssignments" :disabled="!hasPendingPermissionChanges" />
            </template>
        </Dialog>

        <!-- Create/Edit Role Modal -->
        <Dialog v-model:visible="showRoleModal" modal :header="isEditingRole ? 'Edit Role' : 'Create New Role'"
            :style="{ width: 'calc(100vw - 2rem)', maxWidth: '450px' }" :closable="false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Role Name
                        <span class="text-red-500 font-bold">*</span>
                    </label>
                    <InputText v-model="roleForm.name" placeholder="e.g., data_manager" class="w-full"
                        :disabled="roleForm.processing" />
                    <p class="text-xs text-gray-500 mt-1">Use underscores for multi-word roles</p>
                    <p v-if="roleForm.errors.name" class="text-sm text-red-600 mt-2">
                        {{ roleForm.errors.name }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeRoleEditorModal" outlined
                    :disabled="roleForm.processing" />
                <Button :label="isEditingRole ? 'Update Role' : 'Create Role'" severity="success" @click="saveRole"
                    :loading="roleForm.processing" :disabled="!roleForm.name.trim()" />
            </template>
        </Dialog>

        <!-- Delete Role Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteRoleModal" :style="{ width: 'calc(100vw - 2rem)', maxWidth: '450px' }"
            header="Confirm Deletion" :modal="true">
            <div class="flex items-center gap-4">
                <AppIcon name="exclamation-triangle" class="text-3xl text-red-500" />
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
                        <AppIcon name="exclamation-triangle" class="mr-2" />Confirm Deletion
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
                <AppButton label="Delete Permission" severity="danger" @click="deletePermission" icon="trash" />
            </template>
        </Dialog>

        <!-- ============================================ -->
        <!-- PERMISSION CLEANUP DIALOGS -->
        <!-- ============================================ -->

        <!-- Confirm Cleanup Dialog -->
        <Dialog v-model:visible="showConfirmCleanupModal" :style="{ width: 'calc(100vw - 2rem)', maxWidth: '500px' }"
            header="Confirm Permission Cleanup" :modal="true" :closable="false">
            <div class="flex items-start gap-4">
                <AppIcon name="wrench" class="text-3xl text-amber-500 mt-1" />
                <div class="flex-1">
                    <p class="text-lg font-semibold text-gray-800 mb-3">
                        Run Permission System Cleanup?
                    </p>
                    <p class="text-gray-600 mb-4">
                        This will fix permission system issues by:
                    </p>
                    <ul class="space-y-2 mb-4 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <AppIcon name="check" class="text-green-500 text-xs" />
                            <span>Removing orphaned role-permission records</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <AppIcon name="check" class="text-green-500 text-xs" />
                            <span>Removing orphaned user-permission records</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <AppIcon name="check" class="text-green-500 text-xs" />
                            <span>Removing duplicate permission assignments</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <AppIcon name="check" class="text-green-500 text-xs" />
                            <span>Clearing permission cache</span>
                        </li>
                    </ul>
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-md">
                        <div class="flex items-center gap-2 mb-1">
                            <AppIcon name="info-circle" class="text-amber-600 text-sm" />
                            <span class="font-medium text-sm text-amber-800">Safe Operation</span>
                        </div>
                        <p class="text-xs text-amber-700">
                            This is a maintenance operation that won't delete user accounts or active permissions.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeConfirmCleanupModal" outlined
                    :disabled="isCleaningUp" />
                <AppButton label="Run Cleanup" severity="warning" icon="wrench" @click="runCleanup"
                    :loading="isCleaningUp" />
            </template>
        </Dialog>

        <!-- Cleanup Results Dialog -->
        <Dialog v-model:visible="showCleanupResults" :style="{ width: 'calc(100vw - 2rem)', maxWidth: '500px' }"
            header="Permission Cleanup Results" :modal="true">
            <div v-if="cleanupResults" class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <AppIcon name="check-circle" class="text-2xl text-green-600" />
                    <div>
                        <p class="font-semibold text-green-800">Cleanup Completed</p>
                        <p class="text-sm text-green-700">Permission system maintenance finished successfully</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="trash" class="text-sm text-red-600" />
                            <span class="text-sm text-gray-700">Orphaned role permissions removed:</span>
                        </div>
                        <span class="font-semibold text-lg text-gray-800 min-w-[50px] text-right">
                            {{ cleanupResults.orphaned_role_permissions || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="trash" class="text-sm text-red-600" />
                            <span class="text-sm text-gray-700">Orphaned user permissions removed:</span>
                        </div>
                        <span class="font-semibold text-lg text-gray-800 min-w-[50px] text-right">
                            {{ cleanupResults.orphaned_user_permissions || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="copy" class="text-sm text-orange-600" />
                            <span class="text-sm text-gray-700">Duplicate role assignments removed:</span>
                        </div>
                        <span class="font-semibold text-lg text-gray-800 min-w-[50px] text-right">
                            {{ cleanupResults.duplicate_role_permissions || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="copy" class="text-sm text-orange-600" />
                            <span class="text-sm text-gray-700">Duplicate user assignments removed:</span>
                        </div>
                        <span class="font-semibold text-lg text-gray-800 min-w-[50px] text-right">
                            {{ cleanupResults.duplicate_user_permissions || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded border border-blue-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="refresh" class="text-sm text-blue-600" />
                            <span class="text-sm text-gray-700 font-medium">Total records cleaned:</span>
                        </div>
                        <span class="font-bold text-lg text-blue-800">
                            {{ (cleanupResults.orphaned_role_permissions || 0) +
                                (cleanupResults.orphaned_user_permissions || 0) +
                                (cleanupResults.duplicate_role_permissions || 0) +
                                (cleanupResults.duplicate_user_permissions || 0) }}
                        </span>
                    </div>
                </div>

                <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex items-center gap-2 mb-1">
                        <AppIcon name="info-circle" class="text-blue-600 text-sm" />
                        <span class="font-medium text-sm text-blue-800">Permissions Refreshed</span>
                    </div>
                    <p class="text-xs text-blue-700">
                        The permission cache has been cleared. Changes take effect immediately.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="success" @click="closeCleanupResultsModal" />
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
