<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

// PrimeVue Components
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Panel from 'primevue/panel';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
defineProps(["permissions"]);

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
const closeModal = () => {
    showConfirmDeletePermissionModal.value = false;
};
const deletePermission = (permissionID) => {
    form.delete(route("permissions.destroy", permissionID), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>

    <Head title="Permissions" />

    <AdminLayout>
        <template #header>Permissions</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-key text-xl"></i>
                        <span class="font-semibold text-lg">Permission Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Manage system permissions and access controls
                    </div>
                    <Button label="New Permission" icon="pi pi-plus" severity="success" raised
                        @click="router.get(route('permissions.create'))" />
                </div>
            </Panel>

            <!-- Search Section -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search permissions..." class="w-full" />
                        </IconField>
                    </div>
                </div>
            </div>

            <!-- Permissions DataTable -->
            <div class="mt-6">
                <DataTable :value="permissions" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No data to be displayed'" :globalFilterFields="['name']" v-model:filters="filters"
                    paginator :rows="rows" v-model:first="first" :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">System Permissions</h3>
                            <Tag :value="`${permissions.length} permissions`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
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
                                    v-tooltip.top="'Edit Permission'" @click="editPermission(slotProps.data.id)" />
                                <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                    v-tooltip.top="'Delete Permission'"
                                    @click="confirmDeletePermission(slotProps.data.id, slotProps.data.name)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
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
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined />
                <Button label="Delete Permission" severity="danger" @click="deletePermission(modalPermissionData.id)" />
            </template>
        </Dialog>
    </AdminLayout>
</template>
