<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import draggable from 'vuedraggable';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/ui/buttons/PrimaryButton.vue';
import SecondaryButton from '@/Components/ui/buttons/SecondaryButton.vue';
import DangerButton from '@/Components/ui/buttons/DangerButton.vue';
import InputError from '@/Components/ui/inputs/InputError.vue';
import InputLabel from '@/Components/ui/inputs/InputLabel.vue';
import TextInput from '@/Components/ui/inputs/TextInput.vue';
import TextArea from '@/Components/ui/inputs/TextArea.vue';

// PrimeVue
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import Card from 'primevue/card';
import { useToast } from 'primevue/usetoast';

const $page = usePage();
const toast = useToast();

const props = defineProps({
    menus: Array,
    permissions: Object,
});

const showDialog = ref(false);
const dialogMode = ref('item'); // 'group' or 'item'
const editingMenu = ref(null);
const savingOrder = ref(false);
const showDeleteConfirm = ref(false);
const menuToDelete = ref(null);
const showAssignDialog = ref(false);
const selectedItemsToAssign = ref([]);
const assigningToGroup = ref(null); // The group we're assigning items to
const expandedGroups = ref({}); // Track which groups are expanded
const menusData = ref([...props.menus]); // Local copy of menu data

// Fetch fresh menu data from server via dedicated API endpoint
const refreshMenus = async () => {
    try {
        console.log('Fetching menus from API...');
        const response = await axios.get(route('api.menu-items.index'));
        console.log('API response:', response.data);

        if (!Array.isArray(response.data)) {
            console.error('Expected array response from API, got:', typeof response.data);
            throw new Error('Invalid API response format');
        }

        menusData.value = response.data;
        console.log('Menus updated successfully:', menusData.value.length, 'items');
    } catch (error) {
        console.error('Error refreshing menus:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to refresh menu data: ${error.message}`,
            life: 3000
        });
    }
};

// Transform all menus into tree structure (no category filter)
const buildTreeNodes = (menus) => {
    // Get all top-level items (both groups and regular items with no parent)
    const topLevelItems = menus.filter(m => !m.parent_id);

    return topLevelItems.map(item => {
        // Only groups can have children
        const children = item.is_group ? menus.filter(m => m.parent_id === item.id) : [];

        return {
            key: item.id.toString(),
            label: item.name,
            icon: item.icon,
            data: item,
            children: children.length > 0 ? children.map(child => ({
                key: child.id.toString(),
                label: child.name,
                icon: child.icon,
                data: child,
            })) : []
        };
    }).sort((a, b) => a.data.order - b.data.order);
};

const treeNodes = computed(() => buildTreeNodes(menusData.value));

// allTreeNodes as ref - can be modified by drag operations
const allTreeNodes = ref([]);

// Initialize and sync when treeNodes changes (from API or optimistic updates)
// Watch treeNodes to update allTreeNodes when menusData changes
watch(treeNodes, (newTreeNodes) => {
    console.log('treeNodes changed, syncing to allTreeNodes');
    allTreeNodes.value = JSON.parse(JSON.stringify(newTreeNodes));
}, { immediate: true });

// Get flat list of all items for dragging
const flatItemsList = computed(() => {
    const items = [];
    allTreeNodes.value.forEach(topItem => {
        // Use the actual is_group flag from database, not assumed from position
        items.push({ ...topItem, isGroup: topItem.data?.is_group || false });
        if (topItem.children && topItem.children.length > 0) {
            topItem.children.forEach(item => {
                items.push({ ...item, isGroup: false, parentId: topItem.data.id });
            });
        }
    });
    return items;
});

// Sync flat list back to tree structure (no longer needed since allTreeNodes is computed)
const syncFlatToTree = () => {
    // allTreeNodes is now a computed property that automatically syncs
    // This function is kept for backwards compatibility but does nothing
};

// Get all groups (parent items only)
const groupOptions = computed(() => {
    return menusData.value
        .filter(m => m.is_group && m.id !== editingMenu.value?.id)
        .map(m => ({ label: m.name, value: m.id }));
});

// Get all non-group items that can be assigned to groups
const assignableItems = computed(() => {
    return menusData.value
        .filter(m => !m.is_group && !m.parent_id) // Only unassigned items (no parent)
        .map(m => ({ label: m.name, value: m.id }));
});

// Dialog header for assign dialog
const assignDialogHeader = computed(() => {
    return assigningToGroup.value ? `Add Items to "${assigningToGroup.value.name}"` : 'Add Items to Group';
});

const form = useForm({
    name: '',
    icon: 'pi pi-home',
    route: '',
    permission: '',
    order: 0,
    parent_id: null,
    category: 'main', // Default category for backward compatibility
    is_active: true,
    is_group: false, // Flag for group/parent items
    description: '',
});

const iconOptions = [
    'pi pi-home',
    'pi pi-chart-bar',
    'pi pi-file',
    'pi pi-graduation-cap',
    'pi pi-clipboard',
    'pi pi-check-circle',
    'pi pi-users',
    'pi pi-credit-card',
    'pi pi-bell',
    'pi pi-question-circle',
    'pi pi-table',
    'pi pi-book',
    'pi pi-list',
    'pi pi-building',
    'pi pi-code',
    'pi pi-sliders-h',
    'pi pi-shield',
    'pi pi-lock',
    'pi pi-trash',
    'pi pi-download',
    'pi pi-megaphone',
    'pi pi-cog',
    'pi pi-folder',
    'pi pi-inbox',
    'pi pi-search',
    'pi pi-filter',
    'pi pi-star',
    'pi pi-heart',
    'pi pi-thumbs-up',
    'pi pi-comments',
    'pi pi-share-alt',
    'pi pi-upload',
];

const openGroupDialog = () => {
    editingMenu.value = null;
    dialogMode.value = 'group';
    form.reset();
    form.is_active = true;
    form.parent_id = null; // Groups never have parents
    form.route = ''; // Groups don't need routes
    form.is_group = true; // Automatically flag as group
    showDialog.value = true;
};

const openItemDialog = (parentId = null) => {
    editingMenu.value = null;
    dialogMode.value = 'item';
    form.reset();
    form.is_active = true;
    form.parent_id = parentId; // Set parent if creating under a group
    form.is_group = false; // Regular items are not groups
    showDialog.value = true;
};

const openDialog = (menu = null) => {
    if (menu) {
        editingMenu.value = menu;
        dialogMode.value = menu.parent_id ? 'item' : 'group';
        form.name = menu.name;
        form.icon = menu.icon;
        form.route = menu.route || '';
        form.permission = menu.permission || '';
        form.order = menu.order;
        form.parent_id = menu.parent_id;
        form.category = menu.category || 'main';
        form.is_active = menu.is_active;
        form.is_group = menu.is_group || false;
        form.description = menu.description || '';
    } else {
        editingMenu.value = null;
        form.reset();
        form.is_active = true;
        form.is_group = false;
    }
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
    editingMenu.value = null;
    dialogMode.value = 'item';
    form.reset();
};

const openAssignDialog = (group) => {
    assigningToGroup.value = group.data;
    selectedItemsToAssign.value = [];
    showAssignDialog.value = true;
};

const closeAssignDialog = () => {
    showAssignDialog.value = false;
    selectedItemsToAssign.value = [];
    assigningToGroup.value = null;
};

const submitAssignToGroup = () => {
    if (!assigningToGroup.value || selectedItemsToAssign.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Select at least one item', life: 3000 });
        return;
    }

    // Get current children count to set proper order for new items
    const targetGroup = allTreeNodes.value.find(node => node.data.id === assigningToGroup.value.id);
    const childrenCount = targetGroup?.children?.length || 0;

    // Update each selected item sequentially using Inertia router
    const itemIds = selectedItemsToAssign.value;
    let currentIndex = 0;
    const originalStates = []; // Track original states for potential revert

    const updateNextItem = async () => {
        if (currentIndex >= itemIds.length) {
            // All items updated successfully
            toast.add({ severity: 'success', summary: 'Success', detail: `${itemIds.length} item(s) assigned to "${assigningToGroup.value.name}"`, life: 3000 });
            closeAssignDialog();
            await refreshMenus();
            return;
        }

        const itemId = itemIds[currentIndex];
        const item = menusData.value.find(m => m.id === itemId);

        if (!item) {
            toast.add({ severity: 'error', summary: 'Error', detail: `Item ${itemId} not found`, life: 3000 });
            currentIndex++;
            await updateNextItem();
            return;
        }

        // Store original state in case we need to revert
        const originalIndex = menusData.value.findIndex(m => m.id === itemId);
        if (originalIndex !== -1) {
            originalStates.push({
                index: originalIndex,
                data: { ...menusData.value[originalIndex] }
            });

            // Optimistically update local data immediately
            menusData.value[originalIndex] = {
                ...menusData.value[originalIndex],
                parent_id: assigningToGroup.value.id,
                order: childrenCount + currentIndex + 1
            };
            console.log('Optimistically assigned item to group:', item.name);
        }

        router.put(
            route('admin.menu-items.update', itemId),
            {
                name: item.name,
                icon: item.icon,
                route: item.route || '',
                permission: item.permission || '',
                category: item.category || 'main',
                order: childrenCount + currentIndex + 1,
                parent_id: assigningToGroup.value.id,
                is_active: item.is_active !== undefined ? item.is_active : true,
                is_group: false,
                description: item.description || ''
            },
            {
                onSuccess: async () => {
                    console.log('Server confirmed item assignment to group:', item.name);
                    currentIndex++;
                    await updateNextItem();
                },
                onError: (errors) => {
                    console.error('Error updating item:', errors);

                    // Revert the optimistic update on error
                    const stateToRevert = originalStates.find(s => s.index === originalIndex);
                    if (stateToRevert) {
                        menusData.value[stateToRevert.index] = stateToRevert.data;
                        console.log('Reverted optimistic update due to error');
                    }

                    const errorMsg = Object.values(errors).join(', ') || 'Failed to update item';
                    toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
                }
            }
        );
    };

    updateNextItem();
};

const submit = () => {
    // For groups, route should be empty and is_group should be true
    if (dialogMode.value === 'group') {
        form.route = '';
        form.parent_id = null;
        form.is_group = true;
    } else {
        // For items, is_group should be false
        form.is_group = false;
    }

    if (editingMenu.value) {
        form.put(route('admin.menu-items.update', editingMenu.value.id), {
            onSuccess: async () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Menu updated', life: 3000 });
                closeDialog();
                // Reload page to refresh data from server
                await refreshMenus();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update', life: 3000 });
            },
        });
    } else {
        form.post(route('admin.menu-items.store'), {
            onSuccess: async () => {
                const typeMsg = dialogMode.value === 'group' ? 'Menu group' : 'Menu item';
                toast.add({ severity: 'success', summary: 'Success', detail: `${typeMsg} created`, life: 3000 });
                closeDialog();
                // Reload page to refresh data from server
                await refreshMenus();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create', life: 3000 });
            },
        });
    }
};

const openDeleteConfirm = (menu) => {
    menuToDelete.value = menu;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    if (menuToDelete.value) {
        router.delete(route('admin.menu-items.destroy', menuToDelete.value.id), {
            onSuccess: async () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Menu deleted', life: 3000 });
                showDeleteConfirm.value = false;
                menuToDelete.value = null;
                // Reload page to refresh data from server
                await refreshMenus();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 });
                showDeleteConfirm.value = false;
                menuToDelete.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    menuToDelete.value = null;
};

const editMenuItem = (node) => {
    openDialog(node.data);
};

const deleteMenuItem = (node) => {
    // If item is in a group (has parent_id), remove it from group instead of deleting
    if (node.data?.parent_id) {
        removeFromGroup(node);
    } else {
        // Only allow deletion of top-level items
        openDeleteConfirm(node.data);
    }
};

const removeFromGroup = (item) => {
    if (!item.data?.parent_id) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Item is not in a group', life: 3000 });
        return;
    }

    // Store original state in case we need to revert
    const originalParentId = item.data.parent_id;
    const itemIndex = menusData.value.findIndex(m => m.id === item.data.id);

    // Optimistically update local data immediately for instant UI feedback
    if (itemIndex !== -1) {
        menusData.value[itemIndex] = {
            ...menusData.value[itemIndex],
            parent_id: null
        };
        console.log('Optimistically removed item from group:', item.label);
    }

    // Show success toast immediately (before server confirmation)
    toast.add({ severity: 'success', summary: 'Success', detail: `"${item.label}" removed from group`, life: 3000 });

    // Sync the change with server
    router.put(
        route('admin.menu-items.update', item.data.id),
        {
            name: item.data.name,
            icon: item.data.icon,
            route: item.data.route || '',
            permission: item.data.permission || '',
            category: item.data.category || 'main',
            order: item.data.order,
            parent_id: null, // Remove from group
            is_active: item.data.is_active !== undefined ? item.data.is_active : true,
            is_group: false,
            description: item.data.description || ''
        },
        {
            onSuccess: async () => {
                console.log('Server confirmed item removal from group');
                // Refresh to ensure UI is in sync with server
                await refreshMenus();
            },
            onError: (errors) => {
                console.error('Error removing from group:', errors);

                // Revert the optimistic update on error
                if (itemIndex !== -1) {
                    menusData.value[itemIndex] = {
                        ...menusData.value[itemIndex],
                        parent_id: originalParentId
                    };
                    console.log('Reverted optimistic update due to error');
                }

                const errorMsg = Object.values(errors).join(', ') || 'Failed to remove item from group';
                toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 3000 });
            }
        }
    );
};

const saveOrder = async () => {
    savingOrder.value = true;

    const updates = [];

    // Process parent nodes (groups)
    allTreeNodes.value.forEach((node, index) => {
        updates.push({
            id: node.data.id,
            order: index + 1,
            parent_id: null
        });

        // Process children (items under groups)
        if (node.children && node.children.length > 0) {
            node.children.forEach((child, childIndex) => {
                updates.push({
                    id: child.data.id,
                    order: childIndex + 1,
                    parent_id: node.data.id
                });
            });
        }
    });

    try {
        console.log('Saving menu order:', updates);
        const response = await axios.post(route('admin.menu-items.reorder'), {
            menus: updates
        });

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Menu order updated',
            life: 3000
        });
        console.log('Server confirmed menu order update');
        // Reload page to refresh data from server
        await refreshMenus();
    } catch (error) {
        console.error('Save order error:', error);
        const errorMsg = error.response?.data?.message || `Failed to update order (${error.response?.status || 'unknown error'})`;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMsg,
            life: 5000
        });
    } finally {
        savingOrder.value = false;
    }
};

// Debounce auto-save on list changes
let saveOrderTimeout;
const debouncedSaveOrder = () => {
    clearTimeout(saveOrderTimeout);
    saveOrderTimeout = setTimeout(() => {
        saveOrder();
    }, 500); // Wait 500ms after last change before saving
};

// Note: Removed watch on flatItemsList since allTreeNodes is now a computed property
// that automatically syncs when menusData changes. Drag-drop save is handled explicitly.

</script>

<template>

    <Head title="Manage Menu Items" />

    <AdminLayout>
        <Toast />
        <ConfirmDialog></ConfirmDialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteConfirm" :header="'Confirm Delete'" :modal="true" class="w-full md:w-1/3">
            <div class="space-y-4">
                <p class="text-gray-700">
                    Are you sure you want to delete <strong>"{{ menuToDelete?.name }}"</strong>?
                </p>
                <p v-if="menuToDelete?.is_group" class="text-sm text-gray-600">
                    <i class="pi pi-exclamation-triangle text-red-500 mr-2"></i>
                    This will also delete all items under it. This action cannot be undone.
                </p>
                <p v-else class="text-sm text-gray-600">
                    <i class="pi pi-info-circle text-blue-500 mr-2"></i>
                    This will permanently delete this menu item. This action cannot be undone.
                </p>
            </div>
            <template #footer>
                <div class="flex gap-2 justify-end">
                    <SecondaryButton @click="cancelDelete">Cancel</SecondaryButton>
                    <DangerButton @click="confirmDelete">Delete</DangerButton>
                </div>
            </template>
        </Dialog>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Menu Management</h2>
                    <p class="text-gray-600 mt-1">Create menu groups, then add items. Drag & drop to reorder.</p>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton @click="openGroupDialog" class="flex items-center gap-2">
                        <i class="pi pi-plus"></i>
                        Create Group
                    </PrimaryButton>
                    <PrimaryButton @click="openItemDialog()" class="flex items-center gap-2"
                        :disabled="groupOptions.length === 0"
                        v-tooltip.bottom="groupOptions.length === 0 ? 'Create a group first' : ''">
                        <i class="pi pi-plus"></i>
                        Add Item
                    </PrimaryButton>
                </div>
            </div>

            <!-- Content Area -->
            <Card>
                <template #content>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Menu Items</h3>
                                <p class="text-sm text-gray-600 mt-1 flex items-center gap-2">
                                    <i class="pi pi-info-circle"></i>
                                    Click on groups to expand/collapse. Use ↑↓ buttons to reorder items within groups.
                                </p>
                            </div>
                        </div>

                        <!-- Tree View with Expandable Groups - Draggable -->
                        <draggable v-model="allTreeNodes" tag="div"
                            :options="{ animation: 150, handle: '.pi-grip-vertical' }" class="space-y-2" item-key="key"
                            @change="debouncedSaveOrder()">
                            <template #item="{ element: node }">
                                <div class="space-y-1">
                                    <!-- Group Header (if is_group) -->
                                    <div v-if="node.data.is_group"
                                        :class="['bg-white border border-gray-200 rounded-lg p-3 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition']"
                                        @click="expandedGroups[node.data.id] = !expandedGroups[node.data.id]">
                                        <div class="flex items-center gap-3 flex-1">
                                            <i :class="[
                                                'transition-transform',
                                                expandedGroups[node.data.id] ? 'pi pi-chevron-down' : 'pi pi-chevron-right'
                                            ]"></i>
                                            <i class="pi pi-grip-vertical text-blue-400 cursor-grab hover:text-blue-600 text-lg"
                                                @click.stop v-tooltip.bottom="'Drag to reorder'"></i>
                                            <i :class="[node.data.icon, 'text-lg text-blue-400 font-bold']"></i>
                                            <div>
                                                <div class="font-bold text-blue-700">{{ node.data.name }}</div>
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-1 mt-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                                                    <i class="pi pi-folder text-xs"></i>
                                                    {{ node.children?.length || 0 }} item(s)
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex gap-1" @click.stop>
                                            <Button icon="pi pi-plus" rounded text severity="success" size="small"
                                                @click="openAssignDialog(node)"
                                                v-tooltip.bottom="'Add items to this group'" />
                                            <Button icon="pi pi-pencil" rounded text severity="info" size="small"
                                                @click="editMenuItem(node)" />
                                            <Button icon="pi pi-trash" rounded text severity="danger" size="small"
                                                @click="deleteMenuItem(node)" />
                                        </div>
                                    </div>

                                    <!-- Group Children (items in group) - Draggable -->
                                    <draggable v-if="node.data.is_group && expandedGroups[node.data.id]"
                                        v-model="node.children" tag="div"
                                        :options="{ animation: 150, handle: '.pi-grip-vertical' }"
                                        class="ml-4 space-y-1 cursor-pointer" item-key="key"
                                        @change="debouncedSaveOrder()">
                                        <template #item="{ element: child }">
                                            <div
                                                class="bg-blue-50 border-l-4 border-blue-300 rounded p-3 flex items-center justify-between hover:bg-blue-100 transition">
                                                <div class="flex items-center gap-3 flex-1">
                                                    <i class="pi pi-grip-vertical text-blue-400 cursor-grab hover:text-blue-600 text-lg"
                                                        v-tooltip.bottom="'Drag to reorder'"></i>
                                                    <i :class="[child.data.icon, 'text-lg text-gray-600']"></i>
                                                    <div>
                                                        <div class="text-gray-800 font-medium">{{ child.data.name }}
                                                        </div>
                                                        <div v-if="child.data.route" class="text-xs text-gray-500">
                                                            {{ child.data.route }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex gap-1" @click.stop>
                                                    <Button icon="pi pi-unlink" rounded text severity="warning"
                                                        size="small" @click="removeFromGroup(child)"
                                                        v-tooltip.bottom="'Remove from this group'" />
                                                    <Button icon="pi pi-pencil" rounded text severity="info"
                                                        size="small" @click="editMenuItem(child)" />
                                                    <Button icon="pi pi-trash" rounded text severity="danger"
                                                        size="small" @click="deleteMenuItem(child)" />
                                                </div>
                                            </div>
                                        </template>
                                        <template #header v-if="!node.children || node.children.length === 0">
                                            <div
                                                class="bg-gray-50 border border-dashed border-gray-300 rounded p-3 text-center text-gray-500 text-sm">
                                                No items in this group. Click the + button to add items.
                                            </div>
                                        </template>
                                    </draggable>

                                    <!-- Top-level items (not in a group) -->
                                    <div v-if="!node.data.is_group"
                                        class="bg-gray-50 border-l-4 border-blue-200 p-3 rounded flex items-center justify-between hover:bg-gray-100 transition cursor-pointer">
                                        <div class="flex items-center gap-3 flex-1">
                                            <i class="pi pi-grip-vertical text-blue-400 cursor-grab hover:text-blue-600 text-lg"
                                                v-tooltip.bottom="'Drag to reorder'"></i>
                                            <i :class="[node.data.icon, 'text-lg text-gray-600']"></i>
                                            <div>
                                                <div class="text-gray-800 font-medium">{{ node.data.name }}</div>
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 text-xs text-gray-600 bg-gray-200 rounded">
                                                    Ungrouped
                                                </span>
                                                <div v-if="node.data.route" class="text-xs text-gray-500">
                                                    {{ node.data.route }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-1" @click.stop>
                                            <Button icon="pi pi-pencil" rounded text severity="info" size="small"
                                                @click="editMenuItem(node)" />
                                            <Button icon="pi pi-trash" rounded text severity="danger" size="small"
                                                @click="deleteMenuItem(node)" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template #header v-if="allTreeNodes.length === 0">
                                <div class="text-center py-12">
                                    <i class="pi pi-inbox text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No menu items yet. Create a group to get started!</p>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Dialog for Creating/Editing Groups and Items -->
        <Dialog v-model:visible="showDialog"
            :header="editingMenu ? 'Edit ' + (dialogMode === 'group' ? 'Group' : 'Item') : (dialogMode === 'group' ? 'Create Menu Group' : 'Add Menu Item')"
            :modal="true" class="w-full md:w-1/2">
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name *" />
                    <TextInput id="name" v-model="form.name" type="text" class="block w-full mt-1"
                        :placeholder="dialogMode === 'group' ? 'Group name' : 'Item name'" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Icon -->
                <div>
                    <InputLabel for="icon" value="Icon *" />
                    <Select id="icon" v-model="form.icon" :options="iconOptions" class="w-full" :showClear="true">
                        <template #value="{ value }">
                            <div v-if="value" class="flex items-center gap-2">
                                <i :class="[value, 'text-lg']"></i>
                            </div>
                        </template>
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <i :class="[option, 'text-lg']"></i>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.icon" class="mt-2" />
                </div>

                <!-- Route (only for items) -->
                <div v-if="dialogMode === 'item'">
                    <InputLabel for="route" value="Route *" />
                    <TextInput id="route" v-model="form.route" type="text" class="block w-full mt-1"
                        placeholder="e.g., dashboard" />
                    <InputError :message="form.errors.route" class="mt-2" />
                </div>

                <!-- Parent Group (only when adding items) -->
                <div v-if="dialogMode === 'item' && !editingMenu">
                    <InputLabel for="parent_id" value="Parent Group" />
                    <Select id="parent_id" v-model="form.parent_id"
                        :options="[{ label: 'Select a group...', value: null }, ...groupOptions]" optionLabel="label"
                        optionValue="value" class="w-full" />
                    <InputError :message="form.errors.parent_id" class="mt-2" />
                </div>

                <!-- Permission -->
                <div>
                    <InputLabel for="permission" value="Permission Required" />
                    <TextInput id="permission" v-model="form.permission" type="text" class="block w-full mt-1"
                        placeholder="e.g., dashboard.view" />
                    <small class="text-gray-500">Leave empty to show to all users</small>
                    <InputError :message="form.errors.permission" class="mt-2" />
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center justify-between">
                    <InputLabel value="Active" />
                    <ToggleSwitch v-model="form.is_active" />
                </div>

                <!-- Description -->
                <div>
                    <InputLabel for="description" value="Description" />
                    <TextArea id="description" v-model="form.description" placeholder="Optional description" rows="3" />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <!-- Buttons -->
                <div class="flex gap-2 justify-end">
                    <SecondaryButton @click="closeDialog">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        {{ editingMenu ? 'Update' : (dialogMode === 'group' ? 'Create Group' : 'Add Item') }}
                    </PrimaryButton>
                </div>
            </form>
        </Dialog>

        <!-- Assign Items to Group Dialog -->
        <Dialog v-model:visible="showAssignDialog" :header="assignDialogHeader" :modal="true" class="w-full md:w-1/2">
            <form @submit.prevent="submitAssignToGroup" class="space-y-4">
                <!-- Items Checkboxes -->
                <div>
                    <InputLabel value="Select Items to Add *" class="mb-3 block" />
                    <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-300 rounded-lg p-4">
                        <div v-if="assignableItems.length === 0" class="text-center py-8 text-gray-500">
                            <i class="pi pi-inbox text-2xl mb-2"></i>
                            <p>No items available to add</p>
                        </div>
                        <div v-for="item in assignableItems" :key="item.value" class="flex items-center">
                            <input type="checkbox" :id="`item_${item.value}`" :value="item.value"
                                v-model="selectedItemsToAssign"
                                class="rounded border-gray-300 text-blue-600 cursor-pointer" />
                            <label :for="`item_${item.value}`" class="ml-3 cursor-pointer flex-1">
                                {{ item.label }}
                            </label>
                        </div>
                    </div>
                    <small class="text-gray-500 mt-2 block">{{ selectedItemsToAssign.length }} item(s) selected</small>
                </div>

                <!-- Buttons -->
                <div class="flex gap-2 justify-end">
                    <SecondaryButton @click="closeAssignDialog">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitAssignToGroup" :disabled="selectedItemsToAssign.length === 0">Add
                        Selected
                        Items</PrimaryButton>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
:deep(.p-tree) {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.5rem;
}

:deep(.p-tree .p-tree-container .p-treenode) {
    padding: 0.25rem 0;
}

:deep(.p-tree .p-tree-container .p-treenode .p-treenode-content) {
    border-radius: 0.375rem;
    transition: all 0.2s;
}

:deep(.p-tree .p-tree-container .p-treenode .p-treenode-content:hover) {
    background-color: #f3f4f6;
}

:deep(.p-tree .p-tree-container .p-treenode .p-treenode-content.p-treenode-selectable:not(.p-highlight):hover) {
    background-color: #f3f4f6;
}

:deep(.p-tree .p-treenode-droppoint) {
    height: 4px;
    background-color: #3b82f6;
}
</style>
