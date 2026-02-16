<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import logger from '@/utils/logger';

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
    account_code: ''
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
        particularFormData.value = { name: particular.name, account_code: particular.account_code };
    } else {
        editingParticular.value = null;
        particularFormData.value = { name: '', account_code: '' };
    }
    showParticularsModal.value = true;
};

// Save particular
const saveParticular = async () => {
    if (!particularFormData.value.name || !particularFormData.value.account_code) {
        error.value = 'Please fill in all particular fields';
        toast.add({ severity: 'warn', summary: 'Validation', detail: 'Please fill in all fields', life: 3000 });
        return;
    }

    processing.value = true;
    try {
        const url = editingParticular.value
            ? `/api/responsibility-centers/${editingRC.value.id}/particulars/${editingParticular.value.id}`
            : `/api/responsibility-centers/${editingRC.value.id}/particulars`;

        if (editingParticular.value) {
            await axios.put(url, particularFormData.value);
            toast.add({ severity: 'success', summary: 'Success', detail: 'Particular updated', life: 3000 });
        } else {
            await axios.post(url, particularFormData.value);
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

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        const rcMenus = document.querySelectorAll('[id^="rc-menu"]');
        const menuButtons = document.querySelectorAll('[aria-controls="rc-menu"]');

        let isClickInside = false;
        rcMenus.forEach(menu => {
            if (menu.contains(e.target)) isClickInside = true;
        });
        menuButtons.forEach(btn => {
            if (btn.contains(e.target)) isClickInside = true;
        });

        if (!isClickInside) {
            activeRCId.value = null;
        }
    });
});
</script>

<template>

    <Head title="Responsibility Centers" />
    <Toast />
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Responsibility Centers</h1>
                <p class="text-gray-600 mt-1">Manage responsibility centers and their particulars</p>
            </div>
            <button @click="openRCModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium cursor-pointer">
                <i class="pi pi-plus mr-2"></i>Add Responsibility Center
            </button>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            {{ error }}
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
            <p class="mt-3 text-gray-600">Loading responsibility centers...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="responsibilityCenters.length === 0" class="text-center py-12">
            <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No responsibility centers yet</p>
        </div>

        <!-- Responsibility Centers List -->
        <div v-else class="space-y-4">
            <div v-for="rc in responsibilityCenters" :key="rc.id"
                class="border border-gray-200 rounded-lg overflow-hidden">
                <!-- RC Header -->
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ rc.name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">Code: <span class="font-mono font-medium">{{ rc.code
                                }}</span></p>
                        <p v-if="rc.fiscal_year" class="text-sm text-gray-600 mt-1">Fiscal Year: <span
                                class="font-medium">{{ rc.fiscal_year }}</span></p>
                    </div>
                    <div class="relative">
                        <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-sm"
                            @click="toggleRCMenu(rc.id)" aria-haspopup="true" aria-controls="rc-menu" />
                        <Menu v-if="activeRCId === rc.id" ref="rcMenuRef" id="rc-menu" :model="getRCMenuItems(rc)"
                            @hide="activeRCId = null" class="absolute top-full right-0 z-50" />
                    </div>
                </div>

                <!-- Particulars Table -->
                <div class="p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-semibold text-gray-900">Particulars</h4>
                        <Button icon="pi pi-plus" label="Add Particular" class="p-button-sm p-button-success"
                            @click="openParticularsModal(rc)" />
                    </div>

                    <div v-if="rc.particulars && rc.particulars.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50">
                                    <th class="text-left p-3 font-semibold text-gray-700">Particulars Name</th>
                                    <th class="text-left p-3 font-semibold text-gray-700">Account Code</th>
                                    <th class="text-right p-3 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="particular in rc.particulars" :key="particular.id"
                                    class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="p-3 text-gray-900">{{ particular.name }}</td>
                                    <td class="p-3 text-gray-900"><span
                                            class="font-mono bg-gray-100 px-2 py-1 rounded">{{ particular.account_code
                                            }}</span></td>
                                    <td class="p-3 text-right space-x-2">
                                        <Button icon="pi pi-pencil"
                                            class="p-button-rounded p-button-text p-button-sm p-button-warning"
                                            @click="openParticularsModal(rc, particular)" v-tooltip="'Edit'" />
                                        <Button icon="pi pi-trash"
                                            class="p-button-rounded p-button-text p-button-sm p-button-danger"
                                            @click="confirmDeleteParticular(rc.id, particular.id)"
                                            v-tooltip="'Delete'" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-4 text-gray-500">
                        <p>No particulars added</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RC Modal -->
        <Dialog v-model:visible="showModal" :modal="true" :closable="true"
            :header="editingRC ? 'Edit Responsibility Center' : 'Add Responsibility Center'"
            :style="{ width: '90%', maxWidth: '500px' }" @hide="showModal = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input v-model="formData.code" type="text" placeholder="e.g., CC001"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input v-model="formData.name" type="text" placeholder="e.g., Scholarship Center"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year</label>
                    <input v-model="formData.fiscal_year" type="text" placeholder="e.g., 2024-2025"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
            </div>

            <template #footer>
                <div class="space-x-2">
                    <button @click="showModal = false" :disabled="processing"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button @click="saveRC" :disabled="processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ processing ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Particulars Modal -->
        <Dialog v-model:visible="showParticularsModal" :modal="true" :closable="true"
            :header="editingParticular ? 'Edit Particular' : 'Add Particular'"
            :style="{ width: '90%', maxWidth: '500px' }" @hide="showParticularsModal = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Particulars Name</label>
                    <input v-model="particularFormData.name" type="text" placeholder="e.g., Tuition Fee"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                    <input v-model="particularFormData.account_code" type="text" placeholder="e.g., 5010-001"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
            </div>

            <template #footer>
                <div class="space-x-2">
                    <button @click="showParticularsModal = false" :disabled="processing"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button @click="saveParticular" :disabled="processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ processing ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:visible="showDeleteConfirmModal" :modal="true" :closable="true" header="Confirm Delete"
            :style="{ width: '90%', maxWidth: '400px' }" @hide="showDeleteConfirmModal = false">
            <div class="space-y-4">
                <p class="text-gray-700">
                    {{ deleteConfirmType === 'rc'
                        ? 'Are you sure you want to delete this responsibility center? This action cannot be undone.'
                        : 'Are you sure you want to delete this particular? This action cannot be undone.' }}
                </p>
            </div>

            <template #footer>
                <div class="space-x-2">
                    <button @click="showDeleteConfirmModal = false" :disabled="processing"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button @click="deleteConfirmType === 'rc' ? confirmDeleteRC() : executeDeleteParticular()"
                        :disabled="processing"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ processing ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </div>
</template>
