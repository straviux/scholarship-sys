<template>
    <AdminLayout>
        <Toast position="top-right" :life="3500" :baseZIndex="20000" />
        <AdminPageShell title="Role Menu Management"
            description="Assign available menu items to roles, review role-specific navigation, and save ordering changes from a shared iOS-styled workspace."
            icon="shield" eyebrow="Administration">
            <template #meta>
                <span>{{ roles.length }} roles</span>
                <span>{{ menuItems.length }} menu items</span>
                <span v-if="selectedRole">Selected: {{ selectedRole.name }}</span>
            </template>

            <section class="ios-section">
                <div class="ios-section-label">Role Assignment Workspace</div>
                <div class="grid grid-cols-1 gap-4 short:gap-2 xl:grid-cols-12">
                <!-- Roles List -->
                <div class="col-span-1 xl:col-span-3">
                    <Card class="ios-page-panel">
                        <template #title>
                            Roles
                        </template>
                        <template #content>
                            <div class="space-y-2">
                                <div v-for="role in roles" :key="role.id" @click="selectRole(role)" :class="[
                                    'p-3 rounded cursor-pointer transition-colors',
                                    selectedRole?.id === role.id
                                        ? 'bg-blue-100 border border-blue-500'
                                        : 'bg-gray-50 hover:bg-gray-100 border border-gray-200'
                                ]">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ role.name }}</span>
                                        <Badge v-if="selectedRole?.id === role.id" value="Selected" severity="info"
                                            size="small" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Available Menus -->
                <div class="col-span-1 xl:col-span-4">
                    <Card class="ios-page-panel">
                        <template #title>
                            Available Menu Items
                        </template>
                        <template #content>
                            <div v-if="!selectedRole" class="text-center text-gray-500 py-8">
                                <AppIcon name="arrow-left" class="text-4xl mb-2" />
                                <p>Select a role to manage menus</p>
                            </div>
                            <div v-else>
                                <div class="mb-4">
                                    <InputText v-model="searchQuery" placeholder="Search menus..." class="w-full">
                                        <template #prefix>
                                            <AppIcon name="search" />
                                        </template>
                                    </InputText>
                                </div>

                                <div class="max-h-[60vh] overflow-y-auto rounded border xl:max-h-[500px]">
                                    <div v-for="menu in filteredAvailableMenus" :key="menu.id"
                                        class="p-3 border-b hover:bg-gray-50 cursor-pointer"
                                        @click="toggleMenuSelection(menu)">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <Checkbox
                                                    :modelValue="selectedMenuIds.includes(menu.id) || assignedMenus.some(m => m.id === menu.id)"
                                                    @update:modelValue="toggleMenuSelection(menu)" binary />
                                                <AppIcon name="link" class="text-gray-600" />
                                                <div>
                                                    <div class="font-medium">{{ menu.name }}</div>
                                                    <div v-if="menu.permission" class="text-xs text-gray-500">
                                                        Permission: {{ menu.permission }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Child menus -->
                                        <div v-if="menu.children && menu.children.length > 0"
                                            class="ml-8 mt-2 space-y-2">
                                            <div v-for="child in menu.children" :key="child.id"
                                                class="flex items-center space-x-3 p-2 rounded hover:bg-gray-100"
                                                @click.stop="toggleMenuSelection(child)">
                                                <Checkbox
                                                    :modelValue="selectedMenuIds.includes(child.id) || assignedMenus.some(m => m.id === child.id)"
                                                    @update:modelValue="toggleMenuSelection(child)" binary />
                                                <AppIcon name="link" class="text-gray-500" />
                                                <div>
                                                    <div class="text-sm">{{ child.name }}</div>
                                                    <div v-if="child.permission" class="text-xs text-gray-400">
                                                        Permission: {{ child.permission }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <PrimaryButton @click="assignMenusToRole"
                                        :disabled="selectedMenuIds.length === 0 || saving || (selectedRole.name.toLowerCase() === 'administrator' && selectedMenuIds.length === getAllMenusWithChildren(menuItems).length)">
                                        <AppIcon name="arrow-right" class="mr-2" />
                                        Assign to {{ selectedRole.name }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Assigned Menus with Drag & Drop -->
                <div class="col-span-1 xl:col-span-5">
                    <Card class="ios-page-panel">
                        <template #title>
                            <div class="flex items-center justify-between">
                                <span>Assigned Menus for {{ selectedRole?.name || 'Role' }}</span>
                                <AppButton v-if="assignedMenus.length > 0" label="Save Order" icon="save" size="small"
                                    @click="saveMenuOrder" :disabled="saving" />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="!selectedRole" class="text-center text-gray-500 py-8">
                                <AppIcon name="list" class="text-4xl mb-2" />
                                <p>No role selected</p>
                            </div>
                            <div v-else-if="assignedMenus.length === 0" class="text-center text-gray-500 py-8">
                                <AppIcon name="inbox" class="text-4xl mb-2" />
                                <p>No menus assigned to this role</p>
                            </div>
                            <div v-else>
                                <OrderList v-model="assignedMenus" dataKey="id" class="border-none">
                                    <template #item="{ item, index }">
                                        <div class="flex items-center justify-between w-full p-2">
                                            <div class="flex items-center space-x-3 flex-1">
                                                <AppIcon name="link" class="text-gray-600" />
                                                <div class="flex-1">
                                                    <div class="font-medium">{{ item.name }}</div>
                                                    <div v-if="item.route" class="text-xs text-gray-500">
                                                        Route: {{ item.route }}
                                                    </div>
                                                    <div v-if="item.permission" class="text-xs text-blue-600">
                                                        Permission: {{ item.permission }}
                                                    </div>

                                                    <!-- Child menus -->
                                                    <div v-if="item.children && item.children.length > 0"
                                                        class="mt-2 ml-4 space-y-1">
                                                        <div v-for="child in item.children" :key="child.id"
                                                            class="flex items-center space-x-2 text-sm p-1 bg-gray-50 rounded">
                                                            <AppIcon name="link" class="text-gray-500" />
                                                            <span>{{ child.name }}</span>
                                                            <Badge v-if="child.permission" :value="child.permission"
                                                                severity="info" size="small" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <AppButton icon="times" severity="danger" text rounded
                                                @click="removeMenu(item.id)" />
                                        </div>
                                    </template>
                                </OrderList>
                            </div>
                        </template>
                    </Card>
                </div>
                </div>
            </section>
        </AdminPageShell>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageShell from '@/Components/admin/AdminPageShell.vue';
import PrimaryButton from '@/Components/ui/buttons/PrimaryButton.vue';

const props = defineProps({
    roles: Array,
    menuItems: Array,
});

const toast = useToast();
const selectedRole = ref(null);
const selectedMenuIds = ref([]);
const assignedMenus = ref([]);
const searchQuery = ref('');
const saving = ref(false);

// Helper function to safely get CSRF token
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
        console.warn('CSRF token not found in meta tag');
        return '';
    }
    return token;
}

// Filter available menus based on search
const filteredAvailableMenus = computed(() => {
    if (!searchQuery.value) {
        return props.menuItems;
    }

    const query = searchQuery.value.toLowerCase();
    return props.menuItems.filter(menu => {
        const nameMatch = menu.name.toLowerCase().includes(query);
        const childMatch = menu.children?.some(child =>
            child.name.toLowerCase().includes(query)
        );
        return nameMatch || childMatch;
    });
});

// Select a role
async function selectRole(role) {
    selectedRole.value = role;
    selectedMenuIds.value = [];

    try {
        const response = await fetch(`/admin/role-menus/${role.id}/menus`);
        const result = await response.json();

        if (result.success) {
            const menuIds = result.data;
            // Get full menu objects
            assignedMenus.value = getAllMenusWithChildren(props.menuItems)
                .filter(menu => menuIds.includes(menu.id));
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load role menus',
            life: 3000
        });
    }
}

// Get all menus including children as flat array
function getAllMenusWithChildren(menus) {
    const result = [];
    menus.forEach(menu => {
        result.push(menu);
        if (menu.children && menu.children.length > 0) {
            menu.children.forEach(child => result.push(child));
        }
    });
    return result;
}

// Toggle menu selection
function toggleMenuSelection(menu) {
    const isSelected = selectedMenuIds.value.includes(menu.id);

    if (isSelected) {
        // Remove from selection
        selectedMenuIds.value = selectedMenuIds.value.filter(id => id !== menu.id);
    } else {
        // Add to selection
        selectedMenuIds.value.push(menu.id);
    }
}

// Assign menus to role
async function assignMenusToRole() {
    if (!selectedRole.value || selectedMenuIds.value.length === 0) return;

    saving.value = true;

    try {
        // Combine newly selected menus with already assigned menus to preserve existing assignments
        const allMenuIds = [
            ...new Set([
                ...selectedMenuIds.value,
                ...assignedMenus.value.map(m => m.id)
            ])
        ];

        const response = await fetch(`/admin/role-menus/${selectedRole.value.id}/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                menu_ids: allMenuIds,
            }),
        });

        const result = await response.json();

        if (result.success) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `Menus assigned to ${selectedRole.value.name}`,
                life: 3000
            });

            // Reload assigned menus
            await selectRole(selectedRole.value);
            selectedMenuIds.value = [];
        } else {
            throw new Error(result.message || 'Failed to assign menus');
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message,
            life: 3000
        });
    } finally {
        saving.value = false;
    }
}

// Remove menu from role
async function removeMenu(menuId) {
    if (!selectedRole.value) return;

    const updatedMenuIds = assignedMenus.value
        .filter(m => m.id !== menuId)
        .map(m => m.id);

    saving.value = true;

    try {
        const response = await fetch(`/admin/role-menus/${selectedRole.value.id}/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                menu_ids: updatedMenuIds,
            }),
        });

        const result = await response.json();

        if (result.success) {
            assignedMenus.value = assignedMenus.value.filter(m => m.id !== menuId);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Menu removed successfully',
                life: 3000
            });
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to remove menu',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
}

// Save menu order
async function saveMenuOrder() {
    if (!selectedRole.value || assignedMenus.value.length === 0) return;

    saving.value = true;

    const menuOrders = assignedMenus.value.map((menu, index) => ({
        id: menu.id,
        order: index + 1,
    }));

    try {
        const response = await fetch(`/admin/role-menus/${selectedRole.value.id}/order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                menu_orders: menuOrders,
            }),
        });

        const result = await response.json();

        if (result.success) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Menu order saved successfully',
                life: 3000
            });
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save menu order',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
}
</script>
