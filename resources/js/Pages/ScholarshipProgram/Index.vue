<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import moment from "moment";
import ProgramModal from "@/Pages/ScholarshipProgram/Modal/ProgramModal.vue";
import RequirementModal from "./Modal/RequirementModal.vue";
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    action: String,
    scholarshipPrograms: Object,
    program: Object,
    requirements: Array
});

const { hasPermission } = usePermission();


// Search and pagination
const globalFilter = ref('');
const first = ref(0);
const rows = ref(10);
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Delete confirmation modal
const showConfirmDeleteModal = ref(false);
const selectedProgram = ref(null);

// Watch for changes in globalFilter and update filters
watch(globalFilter, (newValue) => {
    filters.value.global.value = newValue;
});
const editProgram = (programId) => {
    // Navigate to the edit program page
    router.get(route("scholarshipprograms.index", {
        id: programId,
        action: 'edit'
    }))
};

const confirmDeleteProgram = (program) => {
    selectedProgram.value = program;
    showConfirmDeleteModal.value = true;
};

const deleteProgram = () => {
    if (selectedProgram.value) {
        router.delete(route('scholarshipprograms.destroy', selectedProgram.value.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showConfirmDeleteModal.value = false;
                selectedProgram.value = null;
            },
            onError: () => {
                showConfirmDeleteModal.value = false;
                selectedProgram.value = null;
            }
        });
    }
};

const closeDeleteModal = () => {
    showConfirmDeleteModal.value = false;
    selectedProgram.value = null;
};


</script>

<template>

    <Head title="Scholar Programs" />

    <AdminLayout>
        <template #header>Manage Programs</template>

        <div class="max-w-8xl mx-auto py-4">
            <!-- Header Panel -->
            <Panel>
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-graduation-cap text-xl"></i>
                        <span class="font-semibold text-lg">Scholarship Program Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center" v-if="hasPermission('programs.manage')">
                    <div class="text-gray-600">
                        Manage scholarship programs and their requirements
                    </div>
                    <div class="flex items-center gap-4">
                        <Button label="New Program" icon="pi pi-plus" severity="success" raised
                            @click="router.get(route('scholarshipprograms.index', { action: 'create' }))" />
                    </div>
                </div>
            </Panel>

            <!-- Search Section (only shown in table view) -->
            <div class="mt-6">
                <div class="flex gap-4 items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="globalFilter" placeholder="Search programs..." class="w-full" />
                        </IconField>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <!-- Table View -->
                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="scholarshipPrograms"
                    stripedRows showGridlines responsiveLayout="scroll" :emptyMessage="'No data to be displayed'"
                    :globalFilterFields="['name', 'shortname', 'remarks']" v-model:filters="filters" paginator
                    :rows="rows" v-model:first="first" :rowsPerPageOptions="[5, 10, 20, 50]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                    <template #header>
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Scholarship Programs</h3>
                            <Tag :value="`${scholarshipPrograms.length} programs`" severity="info" />
                        </div>
                    </template>

                    <Column field="id" header="#" style="width: 50px">
                        <template #body="slotProps">
                            <div class="text-center font-mono text-sm text-gray-500">
                                {{ first + slotProps.index + 1 }}
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="Program" sortable>
                        <template #body="slotProps">
                            <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                            <div class="text-sm text-gray-500 font-bold">[{{ slotProps.data.shortname }}]</div>
                        </template>
                    </Column>

                    <Column field="start_date" header="Start Date" sortable>
                        <template #body="slotProps">
                            <span class="text-gray-600">
                                {{ slotProps.data.start_date ? moment(slotProps.data.start_date).format('MMM DD, YYYY')
                                    : '-' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="end_date" header="End Date" sortable>
                        <template #body="slotProps">
                            <span class="text-gray-600">
                                {{ slotProps.data.end_date ? moment(slotProps.data.end_date).format('MMM DD, YYYY') :
                                    '-' }}
                            </span>
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

                    <Column header="Actions" style="width: 200px">
                        <template #body="slotProps">
                            <div class="flex gap-2 justify-center">
                                <Button v-if="hasPermission('programs.manage')" icon="pi pi-pen-to-square"
                                    severity="info" size="small" rounded outlined v-tooltip.top="'Edit Program'"
                                    @click="editProgram(slotProps.data.id)" />
                                <Button v-if="hasPermission('programs.manage')" icon="pi pi-list" severity="warn"
                                    size="small" rounded outlined v-tooltip.top="'Requirements'"
                                    @click="router.get(route('programs.index', { id: slotProps.data.id, action: 'update-requirement' }))" />
                                <Button v-if="hasPermission('programs.delete')" icon="pi pi-trash" severity="danger"
                                    size="small" rounded outlined v-tooltip.top="'Delete Program'"
                                    @click="confirmDeleteProgram(slotProps.data)" />
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
                        Are you sure you want to delete this program?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500" v-if="selectedProgram">
                        <div class="font-semibold text-red-700">{{ selectedProgram.name }}</div>
                        <div class="text-sm text-gray-600">{{ selectedProgram.shortname }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone and may affect associated courses and applications.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeDeleteModal" outlined />
                <Button label="Delete Program" severity="danger" @click="deleteProgram" />
            </template>
        </Dialog>

        <!-- CREATE SCHOLARSHIP PROGRAM MODAL -->
        <ProgramModal v-if="props.action == 'create' || props.action == 'edit'" :action="props.action"
            :program="props.program" />

        <RequirementModal v-if="props.action == 'update-requirement'" :action="props.action" :program="props.program"
            :requirements="props.requirements" />
    </AdminLayout>
</template>
