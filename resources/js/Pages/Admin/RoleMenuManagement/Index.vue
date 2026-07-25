<template>
    <AdminLayout>
        <AdminPageShell title="Role Menu Management"
            description="Choose which menu items each role sees and arrange their order" icon="shield"
            eyebrow="Administration">
            <template #meta>
                <span>{{ menuItems.length }} menu items</span>
            </template>
            <template #actions>
                <div class="flex flex-wrap gap-3" role="tablist" aria-label="Roles">
                    <button v-for="role in roles" :key="role.id" type="button" role="tab"
                        :aria-selected="selectedRole?.id === role.id"
                        class="cursor-pointer rounded-full px-4 py-[0.65rem] text-slate-700 transition-colors"
                        :class="selectedRole?.id === role.id
                            ? 'bg-blue-400 !text-slate-50'
                            : 'bg-white hover:border-blue-200'" @click="requestRoleSwitch(role)">
                        <div class="flex items-center gap-2">
                            <AppIcon name="shield" :size="14" />
                            <span>{{ formatRoleName(role.name) }}</span>
                            <span v-if="selectedRole?.id === role.id"
                                class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                {{ orderedDraft.length }}
                            </span>
                        </div>
                    </button>
                </div>
            </template>

            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <!-- Stats strip -->
                <div class="flex items-end gap-6 short:gap-3 short:mb-2 px-3 py-2 text-sm opacity-75">
                    <span class="font-semibold text-blue-600">{{ menuItems.length }} menu items</span>
                    <template v-if="selectedRole">
                        <span class="text-gray-300">|</span>
                        <span class="font-semibold text-green-600">
                            {{ orderedDraft.length }} in {{ formatRoleName(selectedRole.name) }} menu
                        </span>
                    </template>
                </div>

                <!-- Unsaved changes banner (single save point for assignment + order) -->
                <div v-if="hasUnsavedChanges" class="mx-2 mb-4 rounded-3xl border border-yellow-200 bg-yellow-50 p-3">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex items-center gap-3">
                            <AppIcon name="exclamation-circle" :size="18" class="text-yellow-600" />
                            <div>
                                <div class="font-semibold text-yellow-900 text-sm">Unsaved changes</div>
                                <div class="text-xs text-yellow-700">
                                    Menu assignment and order are applied together when you save.
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <AppButton icon="save" label="Save Changes" severity="info" rounded size="small"
                                :loading="saving" :disabled="saving" @click="saveChanges" />
                            <AppButton icon="history" label="Discard" severity="secondary" outlined rounded size="small"
                                :disabled="saving" @click="discardChanges" />
                        </div>
                    </div>
                </div>

                <!-- Empty state: no role selected -->
                <div v-if="!selectedRole"
                    class="m-2 mb-4 text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <AppIcon name="shield" class="text-3xl text-gray-300 mb-3" />
                    <p class="text-gray-600 font-medium mb-1">No role selected</p>
                    <p class="text-sm text-gray-500">
                        Pick a role from the pills above to manage its menu.
                    </p>
                </div>

                <!-- Workspace -->
                <div v-else class="grid grid-cols-1 gap-4 short:gap-2 px-2 pb-4 xl:grid-cols-2">

                    <!-- LEFT: assignment checklist -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-700 inline-flex items-center gap-2">
                                <AppIcon name="list-checks" :size="14" class="text-blue-500" />
                                Menu Assignment
                            </h3>
                            <div class="flex items-center gap-2">
                                <AppButton label="Select All" severity="secondary" text size="xsmall"
                                    @click="selectAllMenus" />
                                <AppButton label="Clear" severity="secondary" text size="xsmall"
                                    @click="clearAllMenus" />
                            </div>
                        </div>
                        <p class="mb-3 text-xs text-gray-500">
                            Checked items appear in this role's menu. Uncheck to remove.
                        </p>

                        <!-- Filter row -->
                        <div class="flex items-end gap-3 short:gap-2 flex-wrap pb-3">
                            <div class="flex flex-col flex-1">
                                <label class="text-xs font-medium text-gray-600 mb-1">Search</label>
                                <IconField iconPosition="left">
                                    <InputIcon>
                                        <AppIcon name="search" :size="14" class="text-gray-400" />
                                    </InputIcon>
                                    <InputText v-model="searchQuery" placeholder="Search menus..." size="small"
                                        class="w-full" />
                                </IconField>
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5">
                                <span class="text-xs text-gray-600">Assigned only</span>
                                <ToggleSwitch v-model="showAssignedOnly" />
                            </div>
                        </div>

                        <div class="max-h-[55vh] overflow-y-auto rounded-xl border border-slate-200 xl:max-h-[480px]">
                            <div v-for="menu in visibleMenus" :key="menu.id"
                                class="border-b border-slate-100 last:border-b-0">
                                <!-- Parent row -->
                                <label
                                    class="flex cursor-pointer items-center gap-3 p-3 transition-colors hover:bg-blue-50/40"
                                    :class="isChecked(menu.id) ? 'bg-blue-50/60' : ''">
                                    <Checkbox :modelValue="isChecked(menu.id)" binary
                                        @update:modelValue="toggleMenu(menu)" />
                                    <AppIcon :name="menu.icon || 'link'" :size="14" class="text-gray-500" />
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-800">{{ menu.name }}</div>
                                        <div v-if="menu.permission" class="truncate text-xs mono text-gray-400">
                                            {{ menu.permission }}
                                        </div>
                                    </div>
                                    <span v-if="isChecked(menu.id)"
                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        #{{ draftPosition(menu.id) }}
                                    </span>
                                </label>

                                <!-- Child rows -->
                                <label v-for="child in visibleChildren(menu)" :key="child.id"
                                    class="flex cursor-pointer items-center gap-3 py-2 pl-12 pr-3 transition-colors hover:bg-blue-50/40"
                                    :class="isChecked(child.id) ? 'bg-blue-50/40' : ''">
                                    <Checkbox :modelValue="isChecked(child.id)" binary
                                        @update:modelValue="toggleMenu(child)" />
                                    <AppIcon name="chevron-right" :size="12" class="text-gray-400" />
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm text-gray-700">{{ child.name }}</div>
                                        <div v-if="child.permission" class="truncate text-xs mono text-gray-400">
                                            {{ child.permission }}
                                        </div>
                                    </div>
                                    <span v-if="isChecked(child.id)"
                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        #{{ draftPosition(child.id) }}
                                    </span>
                                </label>
                            </div>

                            <div v-if="visibleMenus.length === 0" class="py-8 text-center text-sm text-gray-500">
                                No menu items match the current filters
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: live order preview -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-700 inline-flex items-center gap-2">
                                <AppIcon name="list" :size="14" class="text-emerald-500" />
                                Navigation Order
                            </h3>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                {{ orderedDraft.length }}
                            </span>
                        </div>
                        <p class="mb-3 text-xs text-gray-500">
                            This is how {{ formatRoleName(selectedRole.name) }}'s menu will be ordered. Drag to
                            rearrange.
                        </p>

                        <div v-if="orderedDraft.length === 0"
                            class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <AppIcon name="inbox" class="text-3xl text-gray-300 mb-3" />
                            <p class="text-gray-600 font-medium mb-1">Menu is empty</p>
                            <p class="text-sm text-gray-500">
                                Tick menu items on the left to build this role's navigation.
                            </p>
                        </div>

                        <draggable v-else v-model="orderedDraft" item-key="id" handle=".drag-handle"
                            class="max-h-[55vh] space-y-2 overflow-y-auto pr-1 xl:max-h-[520px]"
                            ghost-class="opacity-40">
                            <template #item="{ element, index }">
                                <div
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-gray-50 p-2.5 transition-colors hover:border-blue-200 hover:bg-blue-50/40">
                                    <button type="button"
                                        class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 active:cursor-grabbing"
                                        v-tooltip.top="'Drag to reorder'">
                                        <AppIcon name="grip-vertical" :size="16" />
                                    </button>
                                    <span
                                        class="w-6 shrink-0 text-center font-mono text-xs font-semibold text-gray-400">
                                        {{ index + 1 }}
                                    </span>
                                    <AppIcon :name="element.icon || 'link'" :size="14" class="shrink-0 text-gray-500" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-gray-800">{{ element.name }}</span>
                                            <span v-if="element.parentName"
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                                {{ element.parentName }}
                                            </span>
                                        </div>
                                        <div v-if="element.route" class="truncate text-xs mono text-gray-400">
                                            {{ element.route }}
                                        </div>
                                    </div>
                                    <AppButton icon="times" severity="danger" text rounded size="small"
                                        v-tooltip.top="'Remove from menu'" @click="toggleMenu(element)" />
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </Panel>
        </AdminPageShell>

        <!-- Unsaved changes guard when switching roles -->
        <IosModal v-model:visible="showSwitchConfirmModal" title="Unsaved Changes" width="calc(100vw - 2rem)"
            max-width="420px" :show-action="true" action-label="Discard & Switch" action-class="ios-nav-destructive"
            @action="confirmRoleSwitch">
            <div class="flex items-start gap-4">
                <AppIcon name="exclamation-circle" class="text-3xl text-yellow-500 mt-1" />
                <div>
                    <p class="font-semibold text-gray-800 mb-1">
                        You have unsaved changes for
                        <span class="capitalize">{{ formatRoleName(selectedRole?.name) }}</span>.
                    </p>
                    <p class="text-sm text-gray-600">
                        Switching roles now will discard them. Save first if you want to keep your edits.
                    </p>
                </div>
            </div>
        </IosModal>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageShell from '@/Components/admin/AdminPageShell.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import draggable from 'vuedraggable';
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    roles: Array,
    menuItems: Array,
});

const selectedRole = ref(null);
const serverIds = ref([]);          // ordered menu ids as saved on the server
const orderedDraft = ref([]);       // draft menu objects in draft navigation order
const searchQuery = ref('');
const showAssignedOnly = ref(false);
const saving = ref(false);
const showSwitchConfirmModal = ref(false);
const pendingRole = ref(null);

const formatRoleName = (roleName) => {
    if (!roleName) return 'Unknown Role';
    return roleName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};

// Flat lookup of every menu item (parents + children), children annotated with their parent's name
const flatMenus = computed(() => {
    const result = [];
    (props.menuItems || []).forEach(menu => {
        result.push({ ...menu, parentName: null });
        (menu.children || []).forEach(child => {
            result.push({ ...child, parentName: menu.name });
        });
    });
    return result;
});

const menuById = computed(() => {
    const map = {};
    flatMenus.value.forEach(menu => {
        map[menu.id] = menu;
    });
    return map;
});

const draftIds = computed(() => orderedDraft.value.map(menu => menu.id));

const isChecked = (id) => draftIds.value.includes(id);
const draftPosition = (id) => draftIds.value.indexOf(id) + 1;

// A change exists when the draft differs from the server in membership OR order
const hasUnsavedChanges = computed(() => {
    if (!selectedRole.value) return false;
    return draftIds.value.join(',') !== serverIds.value.join(',');
});

// ── Left panel filtering ────────────────────────────────────────────
const menuMatchesFilters = (menu) => {
    if (showAssignedOnly.value && !isChecked(menu.id)) return false;
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return true;
    return [menu.name, menu.permission || ''].some(value => value.toLowerCase().includes(query));
};

const visibleMenus = computed(() =>
    (props.menuItems || []).filter(menu =>
        menuMatchesFilters(menu) || (menu.children || []).some(child => menuMatchesFilters(child))
    )
);

const visibleChildren = (menu) => (menu.children || []).filter(child =>
    menuMatchesFilters(child) || menuMatchesFilters(menu)
);

// ── Draft mutations ─────────────────────────────────────────────────
const toggleMenu = (menu) => {
    if (isChecked(menu.id)) {
        orderedDraft.value = orderedDraft.value.filter(item => item.id !== menu.id);
    } else {
        orderedDraft.value = [...orderedDraft.value, menuById.value[menu.id]];
    }
};

const selectAllMenus = () => {
    const missing = flatMenus.value.filter(menu => !isChecked(menu.id));
    orderedDraft.value = [...orderedDraft.value, ...missing];
};

const clearAllMenus = () => {
    orderedDraft.value = [];
};

// ── Role selection ──────────────────────────────────────────────────
const requestRoleSwitch = (role) => {
    if (role.id === selectedRole.value?.id) return;

    if (hasUnsavedChanges.value) {
        pendingRole.value = role;
        showSwitchConfirmModal.value = true;
        return;
    }

    loadRole(role);
};

const confirmRoleSwitch = () => {
    showSwitchConfirmModal.value = false;
    if (pendingRole.value) {
        loadRole(pendingRole.value);
        pendingRole.value = null;
    }
};

async function loadRole(role) {
    selectedRole.value = role;
    searchQuery.value = '';
    showAssignedOnly.value = false;

    try {
        const { data: result } = await axios.get(`/admin/role-menus/${role.id}/menus`);

        if (result.success) {
            // The endpoint returns ids in the role's saved navigation order — keep it.
            serverIds.value = result.data.filter(id => menuById.value[id]);
            orderedDraft.value = serverIds.value.map(id => menuById.value[id]);
        }
    } catch (error) {
        toast.error('Failed to load role menus');
        serverIds.value = [];
        orderedDraft.value = [];
    }
}

const discardChanges = () => {
    orderedDraft.value = serverIds.value.map(id => menuById.value[id]);
};

// ── Single save point: assignment sync + order in one action ────────
async function saveChanges() {
    if (!selectedRole.value || saving.value) return;

    saving.value = true;

    try {
        const ids = draftIds.value;

        const { data: assignResult } = await axios.post(`/admin/role-menus/${selectedRole.value.id}/assign`, {
            menu_ids: ids,
        });

        if (!assignResult.success) {
            throw new Error(assignResult.message || 'Failed to save menu assignment');
        }

        if (ids.length > 0) {
            const { data: orderResult } = await axios.post(`/admin/role-menus/${selectedRole.value.id}/order`, {
                menu_orders: ids.map((id, index) => ({ id, order: index + 1 })),
            });

            if (!orderResult.success) {
                throw new Error(orderResult.message || 'Failed to save menu order');
            }
        }

        serverIds.value = [...ids];
        toast.success(`Menu saved for ${formatRoleName(selectedRole.value.name)}`);
    } catch (error) {
        toast.error(error.message || 'Failed to save changes');
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    // Pre-select the first role so the workspace is immediately usable
    if (props.roles?.length) {
        loadRole(props.roles[0]);
    }
});
</script>
