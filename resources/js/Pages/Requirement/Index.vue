<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import RequirementModal from "./Modal/RequirementModal.vue";

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

const props = defineProps({
    action: String,
    requirement: Object,
    requirements: Object
});

// Search and pagination
const globalFilter = ref('');
const first = ref(0);
const rows = ref(10);
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Delete confirmation modal
const showConfirmDeleteModal = ref(false);
const selectedRequirement = ref(null);

// Watch for changes in globalFilter and update filters
watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});

const editRequirement = (requirementId) => {
    // Navigate to the edit requirement page
    router.get(route("program_requirements.index", {
        id: requirementId,
        action: 'edit'
    }))
};

const confirmDeleteRequirement = (requirement) => {
    selectedRequirement.value = requirement;
    showConfirmDeleteModal.value = true;
};

const deleteRequirement = () => {
    if (selectedRequirement.value) {
        router.delete(route('program_requirements.destroy', selectedRequirement.value.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showConfirmDeleteModal.value = false;
                selectedRequirement.value = null;
            },
            onError: () => {
                showConfirmDeleteModal.value = false;
                selectedRequirement.value = null;
            }
        });
    }
};

const closeDeleteModal = () => {
    showConfirmDeleteModal.value = false;
    selectedRequirement.value = null;
};
</script>

<template>

    <Head title="Requirements" />

    <AdminLayout>
        <template #header>Manage Requirements</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-file-check text-xl"></i>
                        <span class="font-semibold text-lg">Requirement Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Manage program requirements and documentation
                    </div>
                    <Button label="New Requirement" icon="pi pi-plus" severity="success" raised
                        @click="router.get(route('program_requirements.index', { action: 'create' }))" />
                </div>
            </Panel>

            <!-- Search Section -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search requirements..." class="w-full" />
                        </IconField>
                    </div>
                </div>
            </div>

            <!-- Requirements DataTable -->
            <div class="mt-6">
                <DataTable :value="requirements" stripedRows showGridlines responsiveLayout="scroll"
                    :emptyMessage="'No data to be displayed'" :globalFilterFields="['name', 'description', 'remarks']"
                    v-model:filters="filters" paginator :rows="rows" v-model:first="first"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Requirements</h3>
                            <Tag :value="`${requirements.length} requirements`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="Requirement" sortable>
                        <template #body="slotProps">
                            <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                        </template>
                    </Column>

                    <Column field="description" header="Description" sortable>
                        <template #body="slotProps">
                            <span class="text-gray-600">{{ slotProps.data.description || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="remarks" header="Remarks">
                        <template #body="slotProps">
                            <span class="text-gray-600">{{ slotProps.data.remarks || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status" style="width: 120px">
                        <template #body="slotProps">
                            <Chip :label="slotProps.data.is_active ? 'Active' : 'Inactive'"
                                :severity="slotProps.data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 160px">
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center">
                                <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'Edit Requirement'" @click="editRequirement(slotProps.data.id)" />
                                <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                    v-tooltip.top="'Delete Requirement'"
                                    @click="confirmDeleteRequirement(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this requirement?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500" v-if="selectedRequirement">
                        <div class="font-semibold text-red-700">{{ selectedRequirement.name }}</div>
                        <div class="text-sm text-gray-600">{{ selectedRequirement.description }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeDeleteModal" outlined />
                <Button label="Delete Requirement" severity="danger" @click="deleteRequirement" />
            </template>
        </Dialog>

        <!-- CREATE REQUIREMENT MODAL -->
        <RequirementModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :requirement="props.requirement" />
    </AdminLayout>
</template>
