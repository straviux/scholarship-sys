<template>

    <Head title="Role Permissions" />

    <AdminLayout>
        <template #header>
            Role Permissions
        </template>

        <div class="space-y-6">
            <!-- Header Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-shield text-xl"></i>
                        <span class="font-semibold text-lg">Role Permissions Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Assign and configure permissions for each role
                    </div>
                    <Button v-if="hasChanges" @click="saveAllChanges" :loading="saving" severity="success"
                        icon="pi pi-save" label="Save All Changes" raised />
                </div>
            </Panel>

            <!-- Tabs for Roles -->
            <Card>
                <template #content>
                    <TabView v-model:activeIndex="activeTabIndex">
                        <TabPanel v-for="role in roles" :key="role.id" :header="formatRoleName(role.name)">
                            <div class="space-y-6">
                                <!-- Role Info -->
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-start gap-3">
                                            <i class="pi pi-info-circle text-blue-600 mt-1"></i>
                                            <div>
                                                <h3 class="font-semibold text-blue-900">{{ formatRoleName(role.name) }}
                                                    Role</h3>
                                                <p class="text-sm text-blue-700 mt-1">
                                                    {{ getRoleDescription(role.name) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-if="role.name !== 'administrator'" class="flex gap-2">
                                            <Button @click="selectAll(role.name)" severity="secondary" outlined
                                                size="small" icon="pi pi-check-square" label="Select All" />
                                            <Button @click="clearAll(role.name)" severity="secondary" outlined
                                                size="small" icon="pi pi-times" label="Clear All" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Permissions by Module -->
                                <div v-for="(permissions, module) in groupedPermissions" :key="module"
                                    class="border rounded-lg">
                                    <div class="bg-gray-50 px-4 py-3 border-b flex items-center justify-between">
                                        <h3 class="text-lg font-semibold capitalize">{{ formatModuleName(module) }}</h3>
                                        <Tag
                                            :value="`${getModulePermissionCount(role.name, permissions)} / ${permissions.length}`" />
                                    </div>

                                    <div class="p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div v-for="permission in permissions" :key="permission.id"
                                                class="flex items-center justify-between p-3 bg-white border rounded-lg hover:shadow-md transition">
                                                <div class="flex-1">
                                                    <div class="font-medium text-sm">{{
                                                        getPermissionLabel(permission.name) }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        <code
                                                            class="bg-gray-100 px-1.5 py-0.5 rounded">{{ permission.name }}</code>
                                                    </div>
                                                </div>

                                                <Checkbox v-model="permissionStates[role.name][permission.name]"
                                                    :binary="true" :disabled="role.name === 'administrator'"
                                                    @change="markChanged(role.name, permission.name)" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Panel from 'primevue/panel';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    roles: Array,
    groupedPermissions: Object,
    rolePermissions: Object,
});

const activeTabIndex = ref(0);
const saving = ref(false);
const changedRoles = reactive(new Set());

// Initialize permission states
const permissionStates = reactive({});
props.roles.forEach(role => {
    permissionStates[role.name] = {};
    Object.values(props.groupedPermissions).flat().forEach(permission => {
        permissionStates[role.name][permission.name] =
            props.rolePermissions[role.name]?.includes(permission.name) || false;
    });
});

const hasChanges = computed(() => changedRoles.size > 0);

const formatRoleName = (name) => {
    return name.split('_').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const formatModuleName = (module) => {
    return module.split('-').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const getPermissionLabel = (permissionName) => {
    const parts = permissionName.split('.');
    const action = parts[1] || permissionName;
    return action.split('-').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const getRoleDescription = (roleName) => {
    const descriptions = {
        administrator: 'Full system access with all permissions. Cannot be modified.',
        moderator: 'Can manage most aspects of the system except critical settings.',
        user: 'Basic read-only access to view information.',
        jpm_admin: 'Job Placement & Monitoring administrator with specialized access.',
    };
    return descriptions[roleName] || 'Custom role with specific permissions.';
};

const getModulePermissionCount = (roleName, permissions) => {
    return permissions.filter(p => permissionStates[roleName][p.name]).length;
};

const markChanged = (roleName, permissionName) => {
    changedRoles.add(roleName);
};

const selectAll = (roleName) => {
    Object.values(props.groupedPermissions).flat().forEach(permission => {
        permissionStates[roleName][permission.name] = true;
    });
    changedRoles.add(roleName);
};

const clearAll = (roleName) => {
    Object.values(props.groupedPermissions).flat().forEach(permission => {
        permissionStates[roleName][permission.name] = false;
    });
    changedRoles.add(roleName);
};

const saveAllChanges = () => {
    saving.value = true;

    const updates = Array.from(changedRoles).map(roleName => {
        const permissions = Object.values(props.groupedPermissions)
            .flat()
            .filter(p => permissionStates[roleName][p.name])
            .map(p => p.name);

        return router.post(route('permissions.update-role'), {
            role_name: roleName,
            permissions: permissions,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                changedRoles.delete(roleName);
            },
        });
    });

    Promise.all(updates).finally(() => {
        saving.value = false;
    });
};
</script>
