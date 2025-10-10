<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

// PrimeVue Components
import Button from 'primevue/button';
import Chip from 'primevue/chip';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Panel from 'primevue/panel';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
defineProps(["roles"]);

const form = useForm({});

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

const showConfirmDeleteRoleModal = ref(false);
const modalRoleData = ref({ id: null, name: null });

const editRole = (roleID) => {
    // Navigate to the edit role page
    router.get(route("roles.edit", roleID));
};

const confirmDeleteRole = (roleID, roleName) => {
    showConfirmDeleteRoleModal.value = true;
    modalRoleData.value.id = roleID;
    modalRoleData.value.name = roleName;
};
const closeModal = () => {
    showConfirmDeleteRoleModal.value = false;
};
const deleteRole = (roleID) => {
    form.delete(route("roles.destroy", roleID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>

    <Head title="Roles" />

    <AdminLayout>
        <template #header>Roles</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-shield text-xl"></i>
                        <span class="font-semibold text-lg">Role Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Manage user roles and access levels
                    </div>
                    <Button label="New Role" icon="pi pi-plus" severity="success" raised
                        @click="router.get(route('roles.create'))" />
                </div>
            </Panel>

            <!-- Search Section -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search roles..." class="w-full" />
                        </IconField>
                    </div>
                </div>
            </div>

            <!-- Roles DataTable -->
            <div class="mt-6">
                <DataTable :value="roles" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No data to be displayed'" :globalFilterFields="['name']" v-model:filters="filters"
                    paginator :rows="rows" v-model:first="first" :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">System Roles</h3>
                            <Tag :value="`${roles.length} roles`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
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
                                    severity="danger" size="small" rounded outlined v-tooltip.top="'Delete Role'"
                                    @click="confirmDeleteRole(slotProps.data.id, slotProps.data.name)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
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
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined />
                <Button label="Delete Role" severity="danger" @click="deleteRole(modalRoleData.id)" />
            </template>
        </Dialog>
    </AdminLayout>
</template>
