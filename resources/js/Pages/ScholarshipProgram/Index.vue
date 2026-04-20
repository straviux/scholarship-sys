<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import moment from "moment";
import ProgramModal from "@/Pages/ScholarshipProgram/Modal/ProgramModal.vue";
import RequirementModal from "./Modal/RequirementModal.vue";
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    scholarshipPrograms: Array,
    requirements: Array,
});

const { hasPermission } = usePermission();

// Local reactive list
const programsList = ref([...props.scholarshipPrograms]);

// Search and pagination
const globalFilter = ref('');
const first = ref(0);
const rows = ref(10);
const filters = ref({ global: { value: null, matchMode: 'contains' } });

watch(globalFilter, (v) => { filters.value.global.value = v; });

// Create / Edit modal
const showModal = ref(false);
const editingProgram = ref(null);

const openCreate = () => {
    editingProgram.value = null;
    showModal.value = true;
};
const openEdit = (program) => {
    editingProgram.value = program;
    showModal.value = true;
};
const handleSaved = (program) => {
    showModal.value = false;
    const idx = programsList.value.findIndex(p => p.id === program.id);
    if (idx >= 0) programsList.value[idx] = program;
    else programsList.value.push(program);
};

// Requirements modal
const showRequirementModal = ref(false);
const editingProgramForReq = ref(null);

const openRequirements = (program) => {
    editingProgramForReq.value = program;
    showRequirementModal.value = true;
};
const handleRequirementSaved = (program) => {
    showRequirementModal.value = false;
    const idx = programsList.value.findIndex(p => p.id === program.id);
    if (idx >= 0) programsList.value[idx] = program;
};

// Delete confirmation
const showConfirmDeleteModal = ref(false);
const selectedProgram = ref(null);
const deleting = ref(false);

const confirmDeleteProgram = (program) => {
    selectedProgram.value = program;
    showConfirmDeleteModal.value = true;
};
const deleteProgram = () => {
    if (!selectedProgram.value || deleting.value) return;
    deleting.value = true;
    router.delete(route('scholarshipprograms.destroy', selectedProgram.value.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            programsList.value = programsList.value.filter(p => p.id !== selectedProgram.value.id);
            showConfirmDeleteModal.value = false;
            selectedProgram.value = null;
            deleting.value = false;
        },
        onError: () => {
            deleting.value = false;
        },
    });
};
const closeDeleteModal = () => {
    showConfirmDeleteModal.value = false;
    selectedProgram.value = null;
};
</script>

<template>

    <Head title="Scholar Programs" />

    <AdminLayout>

        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <AppIcon name="graduation-cap" class="text-blue-600 text-[2rem] short:text-[1.5rem]" />
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">Scholarship Programs</h1>
                        <p class="text-sm text-gray-600">Manage scholarship programs and requirements</p>
                    </div>
                </div>
            </template>
            <template #end>
                <AppButton v-if="hasPermission('programs.manage')" icon="plus" label="Add Program" severity="success"
                    raised rounded size="small" @click="openCreate" />
            </template>
        </Toolbar>

        <div class="py-2">

            <!-- Search -->
            <div class="flex gap-3 items-center mb-4">
                <IconField iconPosition="left" class="flex-1 max-w-sm">
                    <InputIcon>
                        <AppIcon name="search" :size="14" />
                    </InputIcon>
                    <InputText v-model="globalFilter" placeholder="Search programs..." class="w-full" />
                </IconField>
                <Tag :value="`${programsList.length} program${programsList.length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Programs DataTable -->
            <DataTable :value="programsList" stripedRows showGridlines scrollable
                :globalFilterFields="['name', 'shortname', 'remarks']" v-model:filters="filters" paginator :rows="rows"
                v-model:first="first" :rowsPerPageOptions="[10, 25, 50]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                :currentPageReportTemplate="'{first} - {last} of {totalRecords}'">

                <Column field="name" header="Program" sortable>
                    <template #body="{ data }">
                        <div class="font-semibold text-gray-800 text-sm">{{ data.name }}</div>
                        <div class="text-[11px] text-[#8e8e93] font-mono mt-0.5" v-if="data.shortname">
                            [{{ data.shortname }}]
                        </div>
                    </template>
                </Column>

                <Column field="start_date" header="Start Date" sortable style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600">
                            {{ data.start_date ? moment(data.start_date).format('MMM DD, YYYY') : '\u2014' }}
                        </span>
                    </template>
                </Column>

                <Column field="end_date" header="End Date" sortable style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600">
                            {{ data.end_date ? moment(data.end_date).format('MMM DD, YYYY') : '\u2014' }}
                        </span>
                    </template>
                </Column>

                <Column field="remarks" header="Remarks">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600" v-safe-html="data.remarks || '\u2014'"></span>
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

                <Column header="Actions" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1.5 justify-center">
                            <AppButton v-if="hasPermission('programs.manage')" icon="pencil" severity="info"
                                size="small" rounded outlined v-tooltip.top="'Edit'" @click="openEdit(data)" />
                            <AppButton v-if="hasPermission('programs.manage')" icon="list" severity="warn" size="small"
                                rounded outlined v-tooltip.top="'Requirements'" @click="openRequirements(data)" />
                            <AppButton v-if="hasPermission('programs.delete')" icon="trash" severity="danger"
                                size="small" rounded outlined v-tooltip.top="'Delete'"
                                @click="confirmDeleteProgram(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- iOS Delete Confirmation Dialog -->
        <Dialog :visible="showConfirmDeleteModal" modal @update:visible="val => !val && closeDeleteModal()"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" style="width: 460px;">
                    <!-- Nav Bar -->
                    <div class="ios-nav-bar">
                        <button class="ios-nav-btn ios-nav-cancel" @click="closeDeleteModal">
                            <AppIcon name="times" />
                        </button>
                        <span class="ios-nav-title">Confirm Deletion</span>
                        <button class="ios-nav-btn ios-nav-action ios-nav-destructive" @click="deleteProgram">
                            Delete
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="ios-body" v-if="selectedProgram">
                        <!-- Warning -->
                        <div class="ios-section">
                            <div class="ios-card">
                                <div class="ios-row" style="padding: 12px 16px; gap: 12px;">
                                    <AppIcon name="exclamation-triangle"
                                        style="font-size: 24px; color: #FF3B30; flex-shrink: 0;" />
                                    <div>
                                        <div
                                            style="font-size: 15px; font-weight: 600; color: #000; margin-bottom: 4px;">
                                            Permanently delete this program?
                                        </div>
                                        <div style="font-size: 13px; color: #8E8E93; line-height: 1.4;">
                                            This action cannot be undone and may affect associated courses and
                                            applications.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Program Info -->
                        <div class="ios-section">
                            <div class="ios-section-label">Program</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <span class="ios-row-label">Name</span>
                                    <span style="font-size: 14px; color: #FF3B30; font-weight: 600;">
                                        {{ selectedProgram.name }}
                                    </span>
                                </div>
                                <div class="ios-row ios-row-last">
                                    <span class="ios-row-label">Shortname</span>
                                    <span style="font-size: 13px; color: #8E8E93;">{{ selectedProgram.shortname
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div style="height: 20px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Program Create/Edit Modal -->
        <ProgramModal v-model:visible="showModal" :program="editingProgram" @saved="handleSaved" />

        <!-- Requirements Modal -->
        <RequirementModal v-model:visible="showRequirementModal" :program="editingProgramForReq"
            :requirements="props.requirements" @saved="handleRequirementSaved" />
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
