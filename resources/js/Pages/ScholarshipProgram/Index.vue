<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import moment from "moment";
import ProgramModal from "@/Pages/ScholarshipProgram/Modal/ProgramModal.vue";
import RequirementModal from "./Modal/RequirementModal.vue";
import IosConfirmDialog from '@/Components/ui/IosConfirmDialog.vue';
import { usePermission } from '@/composable/permissions';

const props = defineProps({
    scholarshipPrograms: Array,
    requirements: Array,
});

const { hasPermission, isAdmin } = usePermission();

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

        <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] !rounded-4xl !px-8">
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
                    <InputText v-model="globalFilter" placeholder="Search programs..." class="w-full ios-search-input-rounded" />
                </IconField>
                <Tag :value="`${programsList.length} program${programsList.length !== 1 ? 's' : ''}`"
                    severity="secondary" />
            </div>

            <!-- Programs DataTable -->
            <DataTable :value="programsList" class="ios-datatable-rounded" stripedRows showGridlines scrollable
                :globalFilterFields="['name', 'shortname', 'remarks']" v-model:filters="filters" paginator :rows="rows"
                v-model:first="first" :rowsPerPageOptions="[10, 25, 50]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                :currentPageReportTemplate="'{first} - {last} of {totalRecords}'">

                <Column field="name" header="Program" sortable>
                    <template #body="{ data }">
                        <div class="font-semibold text-gray-800 text-sm">{{ data.name }}</div>
                        <div class="text-2xs text-[#8e8e93] font-mono mt-0.5" v-if="data.shortname">
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
                            class="text-2xs font-semibold px-[9px] py-[3px] rounded-[20px] inline-block whitespace-nowrap"
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
                            <AppButton v-if="isAdmin()" icon="trash" severity="danger"
                                size="small" rounded outlined v-tooltip.top="'Delete'"
                                @click="confirmDeleteProgram(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- iOS Delete Confirmation Dialog -->
        <IosConfirmDialog
            :visible="showConfirmDeleteModal"
            title="Confirm Deletion"
            width="460px"
            message="Permanently delete this program? This action cannot be undone and may affect associated courses and applications."
            data-label="Program"
            :data="selectedProgram ? [
                { label: 'Name', value: selectedProgram.name, color: '#FF3B30' },
                { label: 'Shortname', value: selectedProgram.shortname },
            ] : []"
            @accept="deleteProgram"
            @update:visible="val => !val && closeDeleteModal()"
        />

        <!-- Program Create/Edit Modal -->
        <ProgramModal v-model:visible="showModal" :program="editingProgram" @saved="handleSaved" />

        <!-- Requirements Modal -->
        <RequirementModal v-model:visible="showRequirementModal" :program="editingProgramForReq"
            :requirements="props.requirements" @saved="handleRequirementSaved" />
    </AdminLayout>
</template>

