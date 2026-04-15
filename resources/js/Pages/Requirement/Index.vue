<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import RequirementModal from "./Modal/RequirementModal.vue";
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    requirements: Array,
});

const { hasPermission } = usePermission();

const showModal = ref(false);
const editingRequirement = ref(null);
const showDeleteModal = ref(false);
const selectedRequirement = ref(null);
const deleting = ref(false);

const globalFilter = ref('');
const filters = ref({ global: { value: null, matchMode: 'contains' } });
const first = ref(0);
const rows = ref(10);

watch(globalFilter, (v) => { filters.value.global.value = v; });

const openCreate = () => {
    editingRequirement.value = null;
    showModal.value = true;
};

const openEdit = (requirement) => {
    editingRequirement.value = requirement;
    showModal.value = true;
};

const handleSaved = () => {
    showModal.value = false;
    editingRequirement.value = null;
};

const confirmDelete = (requirement) => {
    selectedRequirement.value = requirement;
    showDeleteModal.value = true;
};

const deleteRequirement = () => {
    if (!selectedRequirement.value) return;
    deleting.value = true;
    router.delete(route('program_requirements.destroy', selectedRequirement.value.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedRequirement.value = null;
            deleting.value = false;
        },
        onError: () => { deleting.value = false; },
    });
};
</script>

<template>

    <Head title="Requirements" />

    <AdminLayout>

        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <i class="pi pi-file-check text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Requirements</h1>
                        <p class="text-sm text-gray-600">Manage program requirements and documentation</p>
                    </div>
                </div>
            </template>
            <template #end>
                <Button icon="pi pi-plus" label="Add Requirement" severity="success" raised rounded size="small"
                    @click="openCreate" />
            </template>
        </Toolbar>

        <div class="py-2">

            <!-- Search -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Search requirements..." class="w-full" />
                </IconField>
                <Tag :value="`${props.requirements?.length ?? 0} requirement${(props.requirements?.length ?? 0) !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Table -->
            <DataTable :value="props.requirements" stripedRows showGridlines scrollable
                :globalFilterFields="['name', 'description']" v-model:filters="filters" paginator :rows="rows"
                v-model:first="first" :rowsPerPageOptions="[10, 25, 50]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                :currentPageReportTemplate="'{first} - {last} of {totalRecords}'">

                <Column field="name" header="Requirement" sortable>
                    <template #body="{ data }">
                        <div class="font-semibold text-gray-800 text-sm">{{ data.name }}</div>
                    </template>
                </Column>

                <Column field="description" header="Description" sortable>
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600">{{ data.description || '\u2014' }}</span>
                    </template>
                </Column>

                <Column field="is_active" header="Status" style="width: 100px">
                    <template #body="{ data }">
                        <span
                            class="text-[11px] font-semibold px-[9px] py-[3px] rounded-[20px] inline-block whitespace-nowrap"
                            :style="data.is_active
                                ? 'background: #d1f5e0; color: #187a3c;'
                                : 'background: #fee2e2; color: #991b1b;'">
                            {{ data.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="width: 100px">
                    <template #body="{ data }">
                        <div class="flex gap-1.5 justify-center">
                            <Button icon="pi pi-pencil" severity="info" size="small" rounded outlined
                                v-tooltip.top="'Edit'" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

            </DataTable>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteModal" modal header="Delete Requirement" :style="{ width: '420px' }">
            <div class="flex items-start gap-4 py-2">
                <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="pi pi-trash text-red-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700 mb-3">
                        Are you sure you want to delete this requirement? This cannot be undone.
                    </p>
                    <div v-if="selectedRequirement" class="bg-[#f9f9fb] border border-[#e5e5ea] rounded-xl px-4 py-3">
                        <div class="font-semibold text-gray-800 text-sm">{{ selectedRequirement.name }}</div>
                        <div class="text-xs text-[#8e8e93] mt-0.5" v-if="selectedRequirement.description">
                            {{ selectedRequirement.description }}
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined rounded size="small"
                    @click="showDeleteModal = false; selectedRequirement = null" />
                <Button label="Delete" severity="danger" rounded size="small" :loading="deleting"
                    @click="deleteRequirement" />
            </template>
        </Dialog>

        <!-- Requirement Modal -->
        <RequirementModal v-model:visible="showModal" :requirement="editingRequirement" @saved="handleSaved" />

    </AdminLayout>
</template>

<style scoped>
:deep(.p-datatable) {
    border-radius: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color);
}

:deep(.p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

:deep(.p-datatable thead tr:first-child th:first-child) {
    border-left: none;
}

:deep(.p-datatable thead tr:first-child th:last-child) {
    border-right: none;
}

:deep(.p-datatable thead tr:first-child th) {
    border-top: none;
}

:deep(.p-datatable tbody tr:last-child td) {
    border-bottom: none;
}

:deep(.p-datatable tbody tr:last-child td:first-child) {
    border-left: none;
}

:deep(.p-datatable tbody tr:last-child td:last-child) {
    border-right: none;
}

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}

:deep(.p-iconfield .p-inputtext) {
    border-radius: 1rem;
}
</style>
