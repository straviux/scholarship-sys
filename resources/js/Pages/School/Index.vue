<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import SchoolModal from "@/Pages/School/Modal/SchoolModal.vue";
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    schools: Array,
    action: String,
    school: Object,
});

const { hasPermission } = usePermission();

const showModal = ref(false);
const editingSchool = ref(null);
const showDeleteModal = ref(false);
const selectedSchool = ref(null);
const deleting = ref(false);

const globalFilter = ref('');
const filters = ref({ global: { value: null, matchMode: 'contains' } });
const first = ref(0);
const rows = ref(10);

watch(globalFilter, (v) => { filters.value.global.value = v; });

const openCreate = () => {
    editingSchool.value = null;
    showModal.value = true;
};

const openEdit = (school) => {
    editingSchool.value = school;
    showModal.value = true;
};

const handleSaved = () => {
    showModal.value = false;
    editingSchool.value = null;
};

const confirmDelete = (school) => {
    selectedSchool.value = school;
    showDeleteModal.value = true;
};

const deleteSchool = () => {
    if (!selectedSchool.value) return;
    deleting.value = true;
    router.delete(route('school.destroy', selectedSchool.value.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedSchool.value = null;
            deleting.value = false;
        },
        onError: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>

    <Head title="Schools" />

    <AdminLayout>

        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <i class="pi pi-building text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Schools</h1>
                        <p class="text-sm text-gray-600">Manage educational institutions</p>
                    </div>
                </div>
            </template>
            <template #end>
                <Button v-if="hasPermission('schools.manage')" icon="pi pi-plus" label="Add School" severity="success"
                    raised rounded size="small" @click="openCreate" />
            </template>
        </Toolbar>

        <div class="py-2">

            <!-- Search -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="globalFilter" placeholder="Search schools..." class="w-full" />
                </IconField>
                <Tag :value="`${props.schools.length} school${props.schools.length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Table -->
            <DataTable :value="props.schools" stripedRows showGridlines scrollable
                :globalFilterFields="['name', 'shortname', 'campus', 'address']" v-model:filters="filters" paginator
                :rows="rows" v-model:first="first" :rowsPerPageOptions="[10, 25, 50]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                :currentPageReportTemplate="'{first} - {last} of {totalRecords}'">

                <Column field="name" header="School" sortable>
                    <template #body="{ data }">
                        <div class="font-semibold text-gray-800 text-sm">{{ data.name }}</div>
                        <div class="text-[11px] text-[#8e8e93] font-mono mt-0.5" v-if="data.shortname">
                            [{{ data.shortname }}]
                        </div>
                    </template>
                </Column>

                <Column field="campus" header="Campus" sortable style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700">{{ data.campus || '\u2014' }}</span>
                    </template>
                </Column>

                <Column field="address" header="Address" style="min-width: 200px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 leading-snug">{{ data.address || '\u2014' }}</span>
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
                            <Button v-if="hasPermission('schools.manage')" icon="pi pi-pencil" severity="info"
                                size="small" rounded outlined v-tooltip.top="'Edit'" @click="openEdit(data)" />
                            <Button v-if="hasPermission('schools.delete')" icon="pi pi-trash" severity="danger"
                                size="small" rounded outlined v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

            </DataTable>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteModal" modal header="Delete School" :style="{ width: '420px' }">
            <div class="flex items-start gap-4 py-2">
                <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="pi pi-trash text-red-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700 mb-3">
                        Are you sure you want to delete this school? This cannot be undone.
                    </p>
                    <div v-if="selectedSchool" class="bg-[#f9f9fb] border border-[#e5e5ea] rounded-xl px-4 py-3">
                        <div class="font-semibold text-gray-800 text-sm">{{ selectedSchool.name }}</div>
                        <div class="text-xs text-[#8e8e93] mt-0.5">
                            {{ selectedSchool.shortname }}{{ selectedSchool.campus ? ` \u00b7 ${selectedSchool.campus}`
                            : '' }}
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined rounded size="small"
                    @click="showDeleteModal = false; selectedSchool = null" />
                <Button label="Delete" severity="danger" rounded size="small" :loading="deleting"
                    @click="deleteSchool" />
            </template>
        </Dialog>

        <!-- School Modal -->
        <SchoolModal v-model:visible="showModal" :school="editingSchool" @saved="handleSaved" />

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
