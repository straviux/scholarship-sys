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
import IosModal from "@/Components/ui/IosModal.vue";
import IosConfirmDialog from "@/Components/ui/IosConfirmDialog.vue";
import { toast } from '@/utils/toast';

const props = defineProps({
    users: Array,
    roles: Array,
    permissions: Array
});

// ============================================
// TABS
// ============================================
const activeTab = ref('users');

// ============================================
// CONTEXT MENU (row actions, guide-style)
// ============================================
const contextMenu = ref(null);
const contextMenuItems = ref([]);

const openUserContextMenu = (event, user) => {
    contextMenuItems.value = [
        { label: 'Edit User', icon: 'pen-to-square', command: () => editUser(user.id) },
        { label: 'Change Password', icon: 'shield', command: () => openChangePasswordModal(user) },
        { separator: true },
        { label: 'Delete User', icon: 'trash', command: () => confirmDeleteUser(user.id, user.name, user.username) },
    ];
    contextMenu.value.show(event);
};

const openRoleContextMenu = (event, role) => {
    const items = [
        { label: 'Assign Permissions', icon: 'shield-check', command: () => openPermissionAssignmentModal(role) },
    ];

    if (!isProtectedRole(role.name)) {
        items.push(
            { label: 'Edit Role', icon: 'pen-to-square', command: () => openEditRoleModal(role) },
            { separator: true },
            { label: 'Delete Role', icon: 'trash', command: () => confirmDeleteRole(role) },
        );
    }

    contextMenuItems.value = items;
    contextMenu.value.show(event);
};

const openPermissionContextMenu = (event, permission) => {
    contextMenuItems.value = [
        { label: 'Edit Permission', icon: 'pen-to-square', command: () => openEditPermissionModal(permission) },
        { separator: true },
        { label: 'Delete Permission', icon: 'trash', command: () => confirmDeletePermission(permission) },
    ];
    contextMenu.value.show(event);
};

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

const clearUserFilters = () => {
    userGlobalFilter.value = '';
};

const usersWithoutRole = computed(() =>
    (props.users || []).filter(user => !user.roles || user.roles.length === 0).length
);

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

const protectedRoleCount = computed(() =>
    (props.roles || []).filter(role => isProtectedRole(role.name)).length
);

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

const clearPermissionFilters = () => {
    permissionGlobalFilter.value = '';
};

const permissionGroupCount = computed(() => {
    const groups = new Set();
    (props.permissions || []).forEach(permission => {
        groups.add(permission.name.split('.')[0]);
    });
    return groups.size;
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

const PERMISSION_NAME_PATTERN = /^[a-z0-9]+(-[a-z0-9]+)*\.[a-z0-9]+(-[a-z0-9]+)*$/;

const isPermissionNameValid = computed(() => PERMISSION_NAME_PATTERN.test(permissionForm.name.trim()));

const savePermission = async () => {
    if (!permissionForm.name.trim()) {
        toast.error('Permission name is required');
        return;
    }

    if (!isPermissionNameValid.value) {
        toast.error('Permission name must follow the "resource.action" format using lowercase letters and hyphens (e.g. reports.view, return-of-service.export).');
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
        toast.error(error.response?.data?.errors?.name?.[0] || error.response?.data?.message || 'Failed to save permission');
    }
};

// Delete permission
const showConfirmDeletePermissionModal = ref(false);
const permissionToDelete = ref({ id: null, name: null });

const confirmDeletePermission = (permission) => {
    permissionToDelete.value = permission;
    showConfirmDeletePermissionModal.value = true;
};

const closeDeletePermissionModal = () => {
    showConfirmDeletePermissionModal.value = false;
    permissionToDelete.value = { id: null, name: null };
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
    <AdminLayout>

        <Head title="Access Control" />

        <div class="ios-settings-form">
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] short:mb-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon name="lock" class="text-blue-600 text-[2rem] short:text-[1.5rem]" />
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Access Control</h1>
                            <p class="text-sm text-gray-600 short:text-xs">Manage users, roles, and permissions</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div class="flex flex-wrap gap-3" role="tablist" aria-label="Access control views">
                            <button type="button" role="tab" :aria-selected="activeTab === 'users'"
                                class="cursor-pointer rounded-full px-4 py-[0.65rem] text-slate-700 transition-colors"
                                :class="activeTab === 'users'
                                    ? 'bg-blue-400 !text-slate-50'
                                    : 'bg-white hover:border-blue-200'" @click="activeTab = 'users'">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="users" :size="14" />
                                    <span>Users</span>
                                    <span
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        {{ users.length }}
                                    </span>
                                </div>
                            </button>
                            <button type="button" role="tab" :aria-selected="activeTab === 'roles'"
                                class="cursor-pointer rounded-full px-4 py-[0.65rem] text-slate-700 transition-colors"
                                :class="activeTab === 'roles'
                                    ? 'bg-blue-400 !text-slate-50'
                                    : 'bg-white hover:border-blue-200'" @click="activeTab = 'roles'">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="shield" :size="14" />
                                    <span>Roles</span>
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                        {{ roles.length }}
                                    </span>
                                </div>
                            </button>
                            <button type="button" role="tab" :aria-selected="activeTab === 'permissions'"
                                class="cursor-pointer rounded-full px-4 py-[0.65rem] text-slate-700 transition-colors"
                                :class="activeTab === 'permissions'
                                    ? 'bg-blue-400 !text-slate-50'
                                    : 'bg-white hover:border-blue-200'" @click="activeTab = 'permissions'">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="key" :size="14" />
                                    <span>Permissions</span>
                                    <span
                                        class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        {{ permissions.length }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>
            </Toolbar>

            <Tabs v-model:value="activeTab" class="!bg-transparent">
                <TabPanels class="!bg-transparent !p-0">

                    <!-- USERS TAB -->
                    <TabPanel value="users" class="!bg-transparent !p-0">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                            <!-- Stats strip -->
                            <div class="flex items-end gap-6 short:gap-3 short:mb-2 px-3 py-2 text-sm opacity-75">
                                <span class="font-semibold text-blue-600">{{ users.length }} users</span>
                                <span class="text-gray-300">|</span>
                                <span class="font-semibold text-green-600">{{ roles.length }} roles</span>
                                <span class="text-gray-300">|</span>
                                <span class="font-semibold text-amber-600">{{ usersWithoutRole }} without role</span>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex items-end gap-3 short:gap-2 flex-wrap px-2 py-3 mb-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Search</label>
                                    <IconField iconPosition="left">
                                        <InputIcon>
                                            <AppIcon name="search" :size="14" class="text-gray-400" />
                                        </InputIcon>
                                        <InputText v-model="userGlobalFilter" placeholder="Search by name or username..."
                                            size="small" class="min-w-[260px]" />
                                    </IconField>
                                </div>
                                <div class="ml-auto flex flex-wrap justify-end gap-2">
                                    <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                        size="xsmall" @click="clearUserFilters" />
                                    <AppButton icon="user-plus" label="New User" severity="info" rounded size="small"
                                        @click="openCreateUserModal" />
                                </div>
                            </div>

                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="users"
                                class="text-sm ios-interviewed-table ios-datatable-clean" dataKey="id" showGridlines
                                stripedRows scrollable responsiveLayout="scroll" :emptyMessage="'No users found'"
                                :globalFilterFields="['name', 'username']" v-model:filters="userFilters" paginator
                                :rows="userRows" v-model:first="userFirst" :rowsPerPageOptions="[5, 10, 20, 50]"
                                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                                :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                                @rowContextmenu="(event) => openUserContextMenu(event.originalEvent, event.data)"
                                contextMenu>

                                <Column field="id" header="#" headerClass="w-12" bodyClass="w-12">
                                    <template #body="slotProps">
                                        <div class="text-center font-mono text-xs text-gray-500">
                                            {{ userFirst + slotProps.index + 1 }}
                                        </div>
                                    </template>
                                </Column>

                                <Column field="name" header="Name" sortable>
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-3">
                                            <Avatar v-if="slotProps.data.has_profile_photo"
                                                :image="slotProps.data.profile_photo_url"
                                                class="border-2 border-gray-200" shape="circle" />
                                            <Avatar v-else :label="slotProps.data.name.charAt(0).toUpperCase()"
                                                class="bg-blue-500 text-white" shape="circle" />
                                            <div>
                                                <div class="font-medium">{{ slotProps.data.name }}</div>
                                                <div class="text-xs mono text-gray-500">@{{ slotProps.data.username
                                                    }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="office_designation" header="Office" sortable>
                                    <template #body="slotProps">
                                        <span class="text-xs">{{ slotProps.data.office_designation || 'N/A' }}</span>
                                    </template>
                                </Column>

                                <Column field="roles" header="Role" headerClass="min-w-[150px]"
                                    bodyClass="min-w-[150px]">
                                    <template #body="slotProps">
                                        <span v-if="slotProps.data.roles && slotProps.data.roles.length > 0"
                                            class="text-xs font-semibold">
                                            {{ formatRoleName(slotProps.data.roles[0].name) }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">No Role</span>
                                    </template>
                                </Column>

                                <Column header="Actions" :style="{ width: '80px' }">
                                    <template #body="slotProps">
                                        <AppButton icon="ellipsis-vertical"
                                            @click="openUserContextMenu($event, slotProps.data)" text rounded
                                            size="small" v-tooltip.top="'Actions'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>

                    <!-- ROLES TAB -->
                    <TabPanel value="roles" class="!bg-transparent !p-0">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                            <!-- Stats strip -->
                            <div class="flex items-end gap-6 short:gap-3 short:mb-2 px-3 py-2 text-sm opacity-75">
                                <span class="font-semibold text-blue-600">{{ roles.length }} roles</span>
                                <span class="text-gray-300">|</span>
                                <span class="font-semibold text-green-600">{{ permissions.length }} permissions</span>
                                <span class="text-gray-300">|</span>
                                <span class="font-semibold text-amber-600">{{ protectedRoleCount }} protected</span>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex items-end gap-3 short:gap-2 flex-wrap px-2 py-3 mb-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Search</label>
                                    <IconField iconPosition="left">
                                        <InputIcon>
                                            <AppIcon name="search" :size="14" class="text-gray-400" />
                                        </InputIcon>
                                        <InputText v-model="roleSearchFilter" placeholder="Search roles..." size="small"
                                            class="min-w-[260px]" />
                                    </IconField>
                                </div>
                                <div class="ml-auto flex flex-wrap justify-end gap-2">
                                    <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                        size="xsmall" @click="roleSearchFilter = ''" />
                                    <AppButton icon="plus" label="New Role" severity="info" rounded size="small"
                                        @click="openCreateRoleModal" />
                                </div>
                            </div>

                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredRoles"
                                class="text-sm ios-interviewed-table ios-datatable-clean" dataKey="id" showGridlines
                                stripedRows scrollable responsiveLayout="scroll" :emptyMessage="'No roles found'"
                                @rowContextmenu="(event) => openRoleContextMenu(event.originalEvent, event.data)"
                                contextMenu>

                                <Column field="id" header="#" headerClass="w-12" bodyClass="w-12">
                                    <template #body="slotProps">
                                        <div class="text-center font-mono text-xs text-gray-500">
                                            {{ slotProps.index + 1 }}
                                        </div>
                                    </template>
                                </Column>

                                <Column field="name" header="Role" sortable headerClass="min-w-[260px]"
                                    bodyClass="min-w-[260px]">
                                    <template #body="slotProps">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium">{{ formatRoleName(slotProps.data.name) }}</span>
                                            <span v-if="isProtectedRole(slotProps.data.name)"
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                Protected
                                            </span>
                                        </div>
                                        <div class="text-xs mono text-gray-500">{{ slotProps.data.name }}</div>
                                    </template>
                                </Column>

                                <Column header="Assigned Permissions" headerClass="min-w-[180px]"
                                    bodyClass="min-w-[180px]">
                                    <template #body="slotProps">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                            {{ slotProps.data.permissions?.length || 0 }} of {{ permissions.length }}
                                        </span>
                                    </template>
                                </Column>

                                <Column header="Actions" :style="{ width: '140px' }">
                                    <template #body="slotProps">
                                        <div class="flex items-center gap-1">
                                            <AppButton icon="shield-check" severity="info" text rounded size="small"
                                                v-tooltip.top="'Assign Permissions'"
                                                @click="openPermissionAssignmentModal(slotProps.data)" />
                                            <AppButton icon="ellipsis-vertical" text rounded size="small"
                                                v-tooltip.top="'Actions'"
                                                @click="openRoleContextMenu($event, slotProps.data)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>

                    <!-- PERMISSIONS TAB -->
                    <TabPanel value="permissions" class="!bg-transparent !p-0">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                            <!-- Stats strip -->
                            <div class="flex items-end gap-6 short:gap-3 short:mb-2 px-3 py-2 text-sm opacity-75">
                                <span class="font-semibold text-blue-600">{{ permissions.length }} permissions</span>
                                <span class="text-gray-300">|</span>
                                <span class="font-semibold text-green-600">{{ permissionGroupCount }} groups</span>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex items-end gap-3 short:gap-2 flex-wrap px-2 py-3 mb-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Search</label>
                                    <IconField iconPosition="left">
                                        <InputIcon>
                                            <AppIcon name="search" :size="14" class="text-gray-400" />
                                        </InputIcon>
                                        <InputText v-model="permissionGlobalFilter"
                                            placeholder="Search permissions..." size="small" class="min-w-[260px]" />
                                    </IconField>
                                </div>
                                <div class="ml-auto flex flex-wrap justify-end gap-2">
                                    <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                        size="xsmall" @click="clearPermissionFilters" />
                                    <AppButton icon="wrench" label="Cleanup" severity="warning" outlined rounded
                                        size="small" @click="openConfirmCleanupModal"
                                        v-tooltip.top="'Fix orphaned and duplicate permission records'" />
                                    <AppButton icon="plus" label="New Permission" severity="info" rounded size="small"
                                        @click="openCreatePermissionModal" />
                                </div>
                            </div>

                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="permissions"
                                class="text-sm ios-interviewed-table ios-datatable-clean" dataKey="id" showGridlines
                                stripedRows scrollable responsiveLayout="scroll" :emptyMessage="'No permissions found'"
                                :globalFilterFields="['name', 'description']" v-model:filters="permissionFilters"
                                paginator :rows="permissionRows" v-model:first="permissionFirst"
                                :rowsPerPageOptions="[5, 10, 20, 50]"
                                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                                :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                                @rowContextmenu="(event) => openPermissionContextMenu(event.originalEvent, event.data)"
                                contextMenu>

                                <Column field="id" header="#" headerClass="w-12" bodyClass="w-12">
                                    <template #body="slotProps">
                                        <div class="text-center font-mono text-xs text-gray-500">
                                            {{ permissionFirst + slotProps.index + 1 }}
                                        </div>
                                    </template>
                                </Column>

                                <Column field="name" header="Permission" sortable headerClass="min-w-[220px]"
                                    bodyClass="min-w-[220px]">
                                    <template #body="slotProps">
                                        <div class="font-medium">{{ formatPermissionAction(slotProps.data.name) }}
                                        </div>
                                        <div class="text-xs mono text-gray-500">{{ slotProps.data.name }}</div>
                                    </template>
                                </Column>

                                <Column header="Group" headerClass="min-w-[140px]" bodyClass="min-w-[140px]">
                                    <template #body="slotProps">
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                            {{ formatPermissionGroupName(slotProps.data.name.split('.')[0]) }}
                                        </span>
                                    </template>
                                </Column>

                                <Column field="description" header="Description">
                                    <template #body="slotProps">
                                        <span class="text-xs text-gray-600">{{ slotProps.data.description || '-'
                                            }}</span>
                                    </template>
                                </Column>

                                <Column header="Actions" :style="{ width: '80px' }">
                                    <template #body="slotProps">
                                        <AppButton icon="ellipsis-vertical"
                                            @click="openPermissionContextMenu($event, slotProps.data)" text rounded
                                            size="small" v-tooltip.top="'Actions'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

        <!-- Context Menu (row actions) -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body">
            <template #item="{ item, props }">
                <a v-ripple v-bind="props.action" class="flex items-center gap-2 w-full">
                    <AppIcon v-if="item.icon" :name="item.icon" :size="14" />
                    <span>{{ item.label }}</span>
                </a>
            </template>
        </ContextMenu>

        <!-- ============================================ -->
        <!-- USER MODALS -->
        <!-- ============================================ -->

        <!-- Delete User Confirmation Dialog -->
        <IosConfirmDialog
            v-model:visible="showConfirmDeleteUserModal"
            title="Confirm Deletion"
            message="Are you sure you want to delete this user? This action cannot be undone. All data associated with this user will be permanently removed."
            data-label="User"
            :data="[
                { label: 'Name', value: modalUserData.name, color: '#FF3B30' },
                { label: 'Username', value: `@${modalUserData.username}` },
            ]"
            :loading="userForm.processing"
            @accept="deleteUser(modalUserData.id)"
        />

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
        <IosModal :visible="showPermissionAssignmentModal"
            :title="selectedRole ? `Assign Permissions Â· ${formatRoleName(selectedRole.name)}` : 'Assign Permissions'"
            width="min(1200px, 96vw)" :show-action="true" action-label="Save Changes"
            :loading="savingPermissionAssignments" :action-disabled="!hasPendingPermissionChanges"
            @action="savePermissionAssignments" @close="closePermissionAssignmentModal"
            @update:visible="showPermissionAssignmentModal = $event">
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
        </IosModal>

        <!-- Create/Edit Role Modal -->
        <IosModal :visible="showRoleModal" :title="isEditingRole ? 'Edit Role' : 'Create New Role'"
            width="calc(100vw - 2rem)" max-width="450px" :show-action="true"
             :loading="roleForm.processing"
            :action-disabled="!roleForm.name.trim()" @action="saveRole" @close="closeRoleEditorModal"
            @update:visible="showRoleModal = $event">
            <div class="space-y-4 py-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Role Name
                        <span class="text-red-500 font-bold">*</span>
                    </label>
                    <InputText v-model="roleForm.name" placeholder="e.g., data_manager" class="w-full"
                        :disabled="roleForm.processing" />
                    <p class="text-xs text-gray-500 mt-2">Use underscores for multi-word roles</p>
                    <p v-if="roleForm.errors.name" class="text-sm text-red-600 mt-2">
                        {{ roleForm.errors.name }}
                    </p>
                </div>
            </div>
        </IosModal>

        <!-- Delete Role Confirmation Dialog -->
        <IosConfirmDialog
            v-model:visible="showConfirmDeleteRoleModal"
            title="Confirm Deletion"
            message="Are you sure you want to delete this role? This action cannot be undone and may affect users with this role."
            data-label="Role"
            :data="[{ label: 'Name', value: modalRoleData.name, color: '#FF3B30' }]"
            @accept="deleteRole"
        />

        <!-- ============================================ -->
        <!-- PERMISSION MODALS -->
        <!-- ============================================ -->

        <!-- Create/Edit Permission Modal -->
        <IosModal :visible="showPermissionModal" :title="isEditingPermission ? 'Edit Permission' : 'Create Permission'"
            :show-action="true"
            :action-disabled="!permissionForm.name || !isPermissionNameValid" @action="savePermission" @close="closePermissionModal"
            @update:visible="showPermissionModal = $event">
            <div class="space-y-4 py-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permission Name *</label>
                    <InputText v-model="permissionForm.name" placeholder="e.g., users.create, reports.view"
                        class="w-full" />
                    <p v-if="permissionForm.name.trim() && !isPermissionNameValid" class="text-xs text-red-600 mt-1">
                        Must follow "resource.action" format using lowercase letters and hyphens (e.g. reports.view,
                        return-of-service.export).
                    </p>
                    <p v-else class="text-xs text-gray-500 mt-1">Format: resource.action (e.g., users.create,
                        reports.view)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <InputText v-model="permissionForm.description" placeholder="Optional description" class="w-full" />
                </div>
            </div>
        </IosModal>

        <!-- Delete Permission Confirmation Modal -->
        <IosConfirmDialog
            :visible="showConfirmDeletePermissionModal"
            title="Delete Permission"
            message="Are you sure you want to delete this permission? This will remove this permission from all roles that have it."
            data-label="Permission"
            :data="[{ label: 'Name', value: permissionToDelete.name, color: '#FF3B30' }]"
            @accept="deletePermission"
            @close="closeDeletePermissionModal"
            @update:visible="showConfirmDeletePermissionModal = $event"
        />

        <!-- ============================================ -->
        <!-- PERMISSION CLEANUP DIALOGS -->
        <!-- ============================================ -->

        <!-- Confirm Cleanup Dialog -->
        <IosModal v-model:visible="showConfirmCleanupModal" title="Confirm Permission Cleanup"
            width="calc(100vw - 2rem)" max-width="500px" :show-action="true" action-label="Run Cleanup"
            :loading="isCleaningUp" @action="runCleanup">
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
        </IosModal>

        <!-- Cleanup Results Dialog -->
        <IosModal :visible="showCleanupResults" title="Permission Cleanup Results" width="calc(100vw - 2rem)"
            max-width="500px" :show-action="true" action-label="Close" @action="closeCleanupResultsModal"
            @update:visible="showCleanupResults = $event">
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
                            <AppIcon name="copy" class="text-sm text-orange-600" />
                            <span class="text-sm text-gray-700">Duplicate role assignments removed:</span>
                        </div>
                        <span class="font-semibold text-lg text-gray-800 min-w-[50px] text-right">
                            {{ cleanupResults.duplicate_role_permissions || 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded border border-blue-200">
                        <div class="flex items-center gap-2">
                            <AppIcon name="refresh" class="text-sm text-blue-600" />
                            <span class="text-sm text-gray-700 font-medium">Total records cleaned:</span>
                        </div>
                        <span class="font-bold text-lg text-blue-800">
                            {{ (cleanupResults.orphaned_role_permissions || 0) +
                                (cleanupResults.duplicate_role_permissions || 0) }}
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
        </IosModal>
    </AdminLayout>
</template>
