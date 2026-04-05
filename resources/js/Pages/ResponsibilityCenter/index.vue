<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import logger from '@/utils/logger';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';

defineOptions({
    layout: AdminLayout,
});

const page = usePage();
const toast = useToast();

const responsibilityCenters = ref([]);
const loading = ref(false);
const processing = ref(false);
const error = ref('');
const showModal = ref(false);
const showParticularsModal = ref(false);
const showDeleteConfirmModal = ref(false);
const deleteConfirmType = ref(''); // 'rc' or 'particular'
const deleteConfirmId = ref(null);
const editingRC = ref(null);
const editingParticular = ref(null);
const rcMenuRef = ref(null);
const activeRCId = ref(null);

const formData = ref({
    code: '',
    name: '',
    fiscal_year: ''
});

const particularFormData = ref({
    name: '',
    account_code: '',
    allotment: '',
    program: null,
    date_approved: null,
    date_expired: null,
});

// Fetch responsibility centers
const fetchResponsibilityCenters = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get('/api/responsibility-centers');
        responsibilityCenters.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
        logger.info('Responsibility centers loaded:', responsibilityCenters.value.length);
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.message || 'Unknown error occurred';
        error.value = errorMsg;
        logger.error('Failed to fetch responsibility centers:', err);
    } finally {
        loading.value = false;
    }
};

// Open create/edit modal
const openRCModal = (rc = null) => {
    if (rc) {
        editingRC.value = rc;
        formData.value = { code: rc.code, name: rc.name, fiscal_year: rc.fiscal_year };
    } else {
        editingRC.value = null;
        formData.value = { code: '', name: '', fiscal_year: '' };
    }
    showModal.value = true;
};

// Save responsibility center
const saveRC = async () => {
    if (!formData.value.code || !formData.value.name) {
        error.value = 'Please fill in all fields';
        toast.add({ severity: 'warn', summary: 'Validation', detail: 'Please fill in all fields', life: 3000 });
        return;
    }

    processing.value = true;
    try {
        const url = editingRC.value
            ? `/api/responsibility-centers/${editingRC.value.id}`
            : '/api/responsibility-centers';

        if (editingRC.value) {
            await axios.put(url, formData.value);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Responsibility Center updated', life: 3000 });
        } else {
            await axios.post(url, formData.value);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Responsibility Center created', life: 3000 });
        }

        await fetchResponsibilityCenters();
        showModal.value = false;
        error.value = '';
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.message || 'Failed to save responsibility center';
        error.value = errorMsg;
        toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
        logger.error('Failed to save responsibility center:', err);
    } finally {
        processing.value = false;
    }
};

// Get RC menu items
const getRCMenuItems = (rc) => [
    {
        label: 'Edit',
        icon: 'pi pi-pencil',
        command: () => {
            activeRCId.value = null;
            openRCModal(rc);
        }
    },
    {
        label: 'Delete',
        icon: 'pi pi-trash',
        command: () => {
            activeRCId.value = null;
            deleteRC(rc.id);
        },
        class: 'text-red-600'
    }
];

// Toggle RC menu
const toggleRCMenu = (rcId) => {
    activeRCId.value = activeRCId.value === rcId ? null : rcId;
};

// Delete responsibility center
const deleteRC = (id) => {
    deleteConfirmType.value = 'rc';
    deleteConfirmId.value = id;
    showDeleteConfirmModal.value = true;
};

// Confirm RC deletion
const confirmDeleteRC = async () => {
    processing.value = true;
    try {
        logger.info('Attempting to delete RC ID:', deleteConfirmId.value);
        await axios.delete(`/api/responsibility-centers/${deleteConfirmId.value}`);

        toast.add({ severity: 'success', summary: 'Success', detail: 'Responsibility Center deleted', life: 3000 });
        await fetchResponsibilityCenters();
        showDeleteConfirmModal.value = false;
        error.value = '';
        logger.info('RC deleted successfully');
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.message || 'Failed to delete responsibility center';
        error.value = errorMsg;
        toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
        logger.error('Failed to delete responsibility center:', err);
        showDeleteConfirmModal.value = false;
    } finally {
        processing.value = false;
    }
};

// Open particulars modal
const openParticularsModal = (rc, particular = null) => {
    editingRC.value = rc;
    if (particular) {
        editingParticular.value = particular;
        particularFormData.value = {
            name: particular.name,
            account_code: particular.account_code,
            allotment: particular.allotment ?? '',
            program: particular.program ?? null,
            date_approved: particular.date_approved ? new Date(particular.date_approved) : null,
            date_expired: particular.date_expired ? new Date(particular.date_expired) : null,
        };
    } else {
        editingParticular.value = null;
        particularFormData.value = { name: '', account_code: '', allotment: '', program: null, date_approved: null, date_expired: null };
    }
    showParticularsModal.value = true;
};

// Save particular
const saveParticular = async () => {
    if (!particularFormData.value.name || !particularFormData.value.account_code || !particularFormData.value.allotment || !particularFormData.value.program) {
        error.value = 'Please fill in all required particular fields (name, account code, allotment, program)';
        toast.add({ severity: 'warn', summary: 'Validation', detail: 'Name, account code, allotment, and program are required', life: 3000 });
        return;
    }

    processing.value = true;
    try {
        const url = editingParticular.value
            ? `/api/responsibility-centers/${editingRC.value.id}/particulars/${editingParticular.value.id}`
            : `/api/responsibility-centers/${editingRC.value.id}/particulars`;

        const payload = {
            name: particularFormData.value.name,
            account_code: particularFormData.value.account_code,
            scholarship_program_id: particularFormData.value.program?.id ?? particularFormData.value.program,
            allotment: particularFormData.value.allotment,
            date_approved: particularFormData.value.date_approved
                ? new Date(particularFormData.value.date_approved).toISOString().slice(0, 10)
                : null,
            date_expired: particularFormData.value.date_expired
                ? new Date(particularFormData.value.date_expired).toISOString().slice(0, 10)
                : null,
        };

        if (editingParticular.value) {
            await axios.put(url, payload);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Particular updated', life: 3000 });
        } else {
            await axios.post(url, payload);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Particular created', life: 3000 });
        }

        await fetchResponsibilityCenters();
        showParticularsModal.value = false;
        error.value = '';
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.message || 'Failed to save particular';
        error.value = errorMsg;
        toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
        logger.error('Failed to save particular:', err);
    } finally {
        processing.value = false;
    }
};

// Delete particular
// Confirm particular deletion
const confirmDeleteParticular = (rcId, particulerId) => {
    deleteConfirmType.value = 'particular';
    deleteConfirmId.value = particulerId;
    editingRC.value = responsibilityCenters.value.find(rc => rc.id === rcId);
    showDeleteConfirmModal.value = true;
};

// Execute particular deletion
const executeDeleteParticular = async () => {
    processing.value = true;
    try {
        logger.info('Deleting particular ID:', deleteConfirmId.value, 'from RC:', editingRC.value.id);
        await axios.delete(`/api/responsibility-centers/${editingRC.value.id}/particulars/${deleteConfirmId.value}`);

        toast.add({ severity: 'success', summary: 'Success', detail: 'Particular deleted', life: 3000 });
        await fetchResponsibilityCenters();
        showDeleteConfirmModal.value = false;
        error.value = '';
        logger.info('Particular deleted successfully');
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.message || 'Failed to delete particular';
        error.value = errorMsg;
        toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
        logger.error('Failed to delete particular:', err);
        showDeleteConfirmModal.value = false;
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    fetchResponsibilityCenters();
});
</script>

<template>

    <Head title="Responsibility Centers" />
    <Toast position="top-right" :life="3500" :baseZIndex="20000" />

    <div>
        <!-- Toolbar -->
        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <i class="pi pi-building text-blue-500 text-[2rem]"></i>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-700">Responsibility Centers</h1>
                        <p class="text-sm text-gray-600">Manage responsibility centers and their particulars</p>
                    </div>
                </div>
            </template>
            <template #end>
                <Button icon="pi pi-plus" severity="success" rounded outlined @click="openRCModal()"
                    v-tooltip.bottom="'Add Responsibility Center'" />
            </template>
        </Toolbar>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <i class="pi pi-spin pi-spinner text-3xl text-blue-500"></i>
        </div>

        <!-- Empty -->
        <Panel v-else-if="responsibilityCenters.length === 0" class="!rounded-4xl overflow-hidden mt-8">
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="pi pi-inbox text-5xl mb-4"></i>
                <p class="text-base">No responsibility centers yet</p>
                <Button icon="pi pi-plus" label="Add Responsibility Center" severity="secondary" outlined rounded
                    class="mt-4" @click="openRCModal()" />
            </div>
        </Panel>

        <!-- RC Cards -->
        <div v-else class="space-y-4 mt-8">
            <Panel v-for="rc in responsibilityCenters" :key="rc.id" class="!rounded-4xl overflow-hidden">

                <!-- RC Info Bar -->
                <div
                    class="flex items-center justify-between gap-4 mb-4 p-3 bg-gray-50 dark:bg-[#1e242b] rounded-4xl -mt-2">
                    <div class="flex items-center gap-3">
                        <i class="pi pi-building text-blue-500 text-xl"></i>
                        <div>
                            <h3 class="font-bold text-gray-700 dark:text-gray-100">{{ rc.name }}</h3>
                            <div class="flex gap-3 text-xs text-gray-500 mt-0.5">
                                <span>Code: <span class="font-mono font-semibold text-gray-700">{{ rc.code
                                        }}</span></span>
                                <span v-if="rc.fiscal_year">FY: <span class="font-medium">{{ rc.fiscal_year
                                        }}</span></span>
                                <span class="text-gray-400">{{ rc.particulars?.length ?? 0 }} particular(s)</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-plus" size="small" severity="success" rounded text
                            @click="openParticularsModal(rc)" />
                        <Button icon="pi pi-pencil" severity="secondary" text rounded size="small"
                            @click="openRCModal(rc)" v-tooltip.bottom="'Edit RC'" />
                        <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="deleteRC(rc.id)"
                            v-tooltip.bottom="'Delete RC'" />
                    </div>
                </div>

                <!-- Particulars DataTable -->
                <DataTable v-if="rc.particulars && rc.particulars.length > 0" :value="rc.particulars" showGridlines
                    stripedRows scrollable class="text-sm">
                    <Column field="name" header="Particular" style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ data.name }}</span>
                        </template>
                    </Column>
                    <Column field="account_code" header="Account Code" style="min-width: 130px">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-lg">{{
                                data.account_code }}</span>
                        </template>
                    </Column>
                    <Column header="Program" style="min-width: 130px">
                        <template #body="{ data }">
                            <Tag v-if="data.program" :value="data.program.shortname ?? data.program" severity="info"
                                rounded />
                            <span v-else class="text-xs text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="Allotment" style="min-width: 130px">
                        <template #body="{ data }">
                            <span v-if="data.allotment" class="font-semibold text-green-700">
                                ₱{{ parseFloat(data.allotment).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="Date Approved" style="min-width: 130px">
                        <template #body="{ data }">
                            <span v-if="data.date_approved" class="text-xs text-gray-600">{{ data.date_approved
                                }}</span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="Date Expired" style="min-width: 130px">
                        <template #body="{ data }">
                            <span v-if="data.date_expired" class="text-xs text-gray-600">{{ data.date_expired }}</span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="" style="width: 90px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1 justify-end">
                                <Button icon="pi pi-pencil" severity="secondary" text rounded size="small"
                                    @click="openParticularsModal(rc, data)" v-tooltip.top="'Edit'" />
                                <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                                    @click="confirmDeleteParticular(rc.id, data.id)" v-tooltip.top="'Delete'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <i class="pi pi-inbox text-3xl mb-2"></i>
                    <p class="text-sm">No particulars yet</p>
                </div>

            </Panel>
        </div>

        <!-- RC Modal -->
        <Dialog v-model:visible="showModal" :modal="true" :closable="true"
            :header="editingRC ? 'Edit Responsibility Center' : 'Add Responsibility Center'"
            :style="{ width: '90%', maxWidth: '500px' }" @hide="showModal = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <InputText v-model="formData.code" placeholder="e.g., CC001" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <InputText v-model="formData.name" placeholder="e.g., Scholarship Center" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year</label>
                    <InputText v-model="formData.fiscal_year" placeholder="e.g., 2024-2025" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showModal = false" :disabled="processing" />
                <Button :label="processing ? 'Saving...' : 'Save'" icon="pi pi-check" @click="saveRC"
                    :disabled="processing" />
            </template>
        </Dialog>

        <!-- Particulars Modal -->
        <Dialog v-model:visible="showParticularsModal" :modal="true" :closable="true"
            :header="editingParticular ? 'Edit Particular' : 'Add Particular'"
            :style="{ width: '90%', maxWidth: '560px' }" @hide="showParticularsModal = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Particulars Name <span
                            class="text-red-500">*</span></label>
                    <InputText v-model="particularFormData.name" placeholder="e.g., Tuition Fee" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Code <span
                            class="text-red-500">*</span></label>
                    <InputText v-model="particularFormData.account_code" placeholder="e.g., 5010-001" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Program <span
                            class="text-red-500">*</span></label>
                    <ProgramSelect v-model="particularFormData.program" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Allotment <span
                            class="text-red-500">*</span></label>
                    <InputNumber v-model="particularFormData.allotment" placeholder="e.g., 15000.00"
                        :minFractionDigits="2" :maxFractionDigits="2" mode="decimal" class="w-full" inputClass="w-full"
                        prefix="₱" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Approved</label>
                        <DatePicker v-model="particularFormData.date_approved" class="w-full" date-format="M dd, yy"
                            showIcon iconDisplay="input" showButtonBar />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Expired</label>
                        <DatePicker v-model="particularFormData.date_expired" class="w-full" date-format="M dd, yy"
                            showIcon iconDisplay="input" showButtonBar />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showParticularsModal = false"
                    :disabled="processing" />
                <Button :label="processing ? 'Saving...' : 'Save'" icon="pi pi-check" @click="saveParticular"
                    :disabled="processing" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:visible="showDeleteConfirmModal" :modal="true" :closable="true" header="Confirm Delete"
            :style="{ width: '90%', maxWidth: '400px' }" @hide="showDeleteConfirmModal = false">
            <p class="text-gray-700">
                {{ deleteConfirmType === 'rc'
                    ? 'Are you sure you want to delete this responsibility center? This action cannot be undone.'
                    : 'Are you sure you want to delete this particular? This action cannot be undone.' }}
            </p>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteConfirmModal = false"
                    :disabled="processing" />
                <Button :label="processing ? 'Deleting...' : 'Delete'" severity="danger" icon="pi pi-trash"
                    @click="deleteConfirmType === 'rc' ? confirmDeleteRC() : executeDeleteParticular()"
                    :disabled="processing" />
            </template>
        </Dialog>
    </div>
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

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}

:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-datepicker .p-inputtext) {
    border-radius: 1rem;
}
</style>
