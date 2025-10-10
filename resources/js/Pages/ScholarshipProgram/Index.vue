<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import moment from "moment";
import { useStorage } from '@vueuse/core';
import ProgramModal from "@/Pages/ScholarshipProgram/Modal/ProgramModal.vue";
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
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    action: String,
    scholarshipPrograms: Object,
    program: Object,
    requirements: Array
});

const gridview = useStorage('gridview', false);

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

onMounted(() => {
    // console.log(props);
});

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

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Manage scholarship programs and their requirements
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- View Toggle -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Table</label>
                            <ToggleSwitch v-model="gridview" />
                            <label class="text-sm text-gray-600">Grid</label>
                        </div>
                        <Button label="New Program" icon="pi pi-plus" severity="success" raised
                            @click="router.get(route('scholarshipprograms.index', { action: 'create' }))" />
                    </div>
                </div>
            </Panel>

            <!-- Search Section (only shown in table view) -->
            <div class="mt-6" v-if="!gridview">
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
                <DataTable v-if="!gridview" :value="scholarshipPrograms" stripedRows showGridlines
                    responsiveLayout="scroll" :emptyMessage="'No data to be displayed'"
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
                                <Button icon="pi pi-pen-to-square" severity="info" size="small" rounded outlined
                                    v-tooltip.top="'Edit Program'" @click="editProgram(slotProps.data.id)" />
                                <Button icon="pi pi-list" severity="warn" size="small" rounded outlined
                                    v-tooltip.top="'Requirements'"
                                    @click="router.get(route('scholarshipprograms.index', { id: slotProps.data.id, action: 'update-requirement' }))" />
                                <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                    v-tooltip.top="'Delete Program'" @click="confirmDeleteProgram(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Grid View -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(program, index) in scholarshipPrograms" :key="'program_' + program.id"
                        class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">

                        <!-- Card Header -->
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-400 mb-2">#{{ index + 1 }}</div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ program.name }}</h3>
                                    <p class="text-sm text-gray-500 font-medium">[{{ program.shortname }}]</p>
                                </div>
                                <Chip :label="program.is_active ? 'Active' : 'Inactive'"
                                    :severity="program.is_active ? 'success' : 'secondary'" />
                            </div>

                            <!-- Program Details -->
                            <div class="mt-4 space-y-3">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-700">Duration:</span>
                                    <div class="text-gray-600 mt-1">
                                        {{ program.start_date ? moment(program.start_date).format('MMM DD, YYYY') : `Not
                                        set` }}
                                        <span class="mx-2">to</span>
                                        {{ program.end_date ? moment(program.end_date).format('MMM DD, YYYY') : `Not
                                        set` }}
                                    </div>
                                </div>

                                <div class="text-sm" v-if="program.remarks">
                                    <span class="font-medium text-gray-700">Remarks:</span>
                                    <p class="text-gray-600 mt-1">{{ program.remarks }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="border-t border-gray-100 px-6 py-4">
                            <div class="flex gap-2 justify-center">
                                <Button icon="pi pi-pen-to-square" severity="info" size="small" outlined
                                    v-tooltip.top="'Edit Program'" @click="editProgram(program.id)" />
                                <Button icon="pi pi-list" severity="warn" size="small" outlined
                                    v-tooltip.top="'Requirements'"
                                    @click="router.get(route('scholarshipprograms.index', { id: program.id, action: 'update-requirement' }))" />
                                <Button icon="pi pi-trash" severity="danger" size="small" outlined
                                    v-tooltip.top="'Delete Program'" @click="confirmDeleteProgram(program)" />
                            </div>
                        </div>
                    </div>
                </div>
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
