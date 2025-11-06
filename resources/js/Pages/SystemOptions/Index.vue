<template>

    <Head title="Option Values" />

    <AdminLayout>
        <template #header>
            Option Values
        </template>

        <div class="space-y-6">
            <!-- Header Panel -->
            <Panel class="mb-4">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-sliders-h text-xl"></i>
                        <span class="font-semibold text-lg">Option Values Management</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        Configure and manage system-wide option values
                    </div>
                    <Button icon="pi pi-plus" label="Add Option" severity="success" raised @click="openAddDialog" />
                </div>
            </Panel>

            <!-- Tabs for Categories -->
            <Card>
                <template #content>
                    <Tabs v-model:value="activeTab">
                        <TabList>
                            <Tab v-for="(label, category) in categories" :key="`tab-${category}`" :value="category">
                                {{ label }}
                            </Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel v-for="(label, category) in categories" :key="`panel-${category}`"
                                :value="category">
                                <div class="space-y-4">
                                    <!-- Category Description -->
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <div class="flex items-start gap-3">
                                            <i class="pi pi-info-circle text-blue-600 mt-1"></i>
                                            <div>
                                                <h3 class="font-semibold text-blue-900">{{ label }}</h3>
                                                <p class="text-sm text-blue-700 mt-1">
                                                    {{ getCategoryDescription(category) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Options DataTable -->
                                    <DataTable :value="getOptionsForCategory(category)" :rows="10" :rowHover="true"
                                        stripedRows showGridlines class="mt-4">
                                        <Column header="Order" style="width: 80px">
                                            <template #body="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <Button icon="pi pi-chevron-up" severity="secondary" text
                                                        size="small" @click="moveUp(slotProps.data, category)"
                                                        :disabled="slotProps.index === 0" />
                                                    <Button icon="pi pi-chevron-down" severity="secondary" text
                                                        size="small" @click="moveDown(slotProps.data, category)"
                                                        :disabled="slotProps.index === getOptionsForCategory(category).length - 1" />
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="value" header="Value" style="min-width: 200px">
                                            <template #body="slotProps">
                                                <span class="font-mono text-sm">{{ slotProps.data.value }}</span>
                                            </template>
                                        </Column>

                                        <Column field="label" header="Label" style="min-width: 200px">
                                            <template #body="slotProps">
                                                {{ slotProps.data.label || '-' }}
                                            </template>
                                        </Column>

                                        <Column field="color" header="Color" style="width: 120px">
                                            <template #body="slotProps">
                                                <div v-if="slotProps.data.color" class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded border border-gray-300"
                                                        :style="{ backgroundColor: slotProps.data.color }"></div>
                                                    <span class="text-xs text-gray-600">{{ slotProps.data.color
                                                        }}</span>
                                                </div>
                                                <span v-else class="text-gray-400">-</span>
                                            </template>
                                        </Column>

                                        <Column field="is_active" header="Status" style="width: 100px">
                                            <template #body="slotProps">
                                                <Tag :value="slotProps.data.is_active ? 'Active' : 'Inactive'"
                                                    :severity="slotProps.data.is_active ? 'success' : 'danger'" />
                                            </template>
                                        </Column>

                                        <Column field="description" header="Description" style="min-width: 250px">
                                            <template #body="slotProps">
                                                <span class="text-sm text-gray-600">{{ slotProps.data.description || '-'
                                                    }}</span>
                                            </template>
                                        </Column>

                                        <Column header="Actions" style="width: 180px">
                                            <template #body="slotProps">
                                                <div class="flex gap-2">
                                                    <Button icon="pi pi-pencil" severity="info" text size="small"
                                                        @click="openEditDialog(slotProps.data)"
                                                        v-tooltip.top="'Edit'" />
                                                    <Button
                                                        :icon="slotProps.data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                                        :severity="slotProps.data.is_active ? 'warning' : 'success'"
                                                        text size="small" @click="toggleActive(slotProps.data)"
                                                        v-tooltip.top="slotProps.data.is_active ? 'Deactivate' : 'Activate'" />
                                                    <Button icon="pi pi-trash" severity="danger" text size="small"
                                                        @click="confirmDelete(slotProps.data)"
                                                        v-tooltip.top="'Delete'" />
                                                </div>
                                            </template>
                                        </Column>

                                        <template #empty>
                                            <div class="text-center py-8 text-gray-500">
                                                <i class="pi pi-inbox text-4xl mb-3"></i>
                                                <p>No options found for this category.</p>
                                                <Button label="Add First Option" icon="pi pi-plus" severity="secondary"
                                                    outlined class="mt-3" @click="openAddDialog(category)" />
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>
        </div>

        <!-- Add/Edit Dialog -->
        <Dialog v-model:visible="showDialog" :header="dialogMode === 'add' ? 'Add New Option' : 'Edit Option'"
            :modal="true" :style="{ width: '600px' }">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div v-if="dialogMode === 'add'" class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <Select v-model="form.category"
                        :options="Object.entries(categories).map(([value, label]) => ({ value, label }))"
                        optionLabel="label" optionValue="value" placeholder="Select category" class="w-full" />
                    <small v-if="form.errors.category" class="text-red-500 mt-1">{{ form.errors.category }}</small>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Value *</label>
                    <InputText v-model="form.value" placeholder="Enter option value" class="w-full" />
                    <small v-if="form.errors.value" class="text-red-500 mt-1">{{ form.errors.value }}</small>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Label</label>
                    <InputText v-model="form.label" placeholder="Display label (optional)" class="w-full" />
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Color</label>
                    <div class="flex gap-2">
                        <InputText v-model="form.color" placeholder="#000000 or color name" class="flex-1" />
                        <input type="color" v-model="form.color" class="w-12 h-10 border rounded cursor-pointer" />
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <InputNumber v-model="form.sort_order" placeholder="0" class="w-full" :min="0" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_active" :binary="true" inputId="is_active" />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Textarea v-model="form.description" placeholder="Optional description" rows="3" class="w-full" />
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button label="Cancel" severity="secondary" outlined @click="showDialog = false" type="button" />
                    <Button :label="dialogMode === 'add' ? 'Add Option' : 'Update Option'" severity="success"
                        type="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" header="Confirm Delete" :modal="true" :style="{ width: '450px' }">
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
                <div>
                    <p class="mb-2">Are you sure you want to delete this option?</p>
                    <div class="bg-gray-50 p-3 rounded border">
                        <p class="font-semibold">{{ optionToDelete?.value }}</p>
                        <p class="text-sm text-gray-600">{{ optionToDelete?.label }}</p>
                    </div>
                    <p class="text-sm text-orange-600 mt-3">
                        <i class="pi pi-info-circle mr-1"></i>
                        This action cannot be undone.
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeleteDialog = false" />
                <Button label="Delete" severity="danger" @click="deleteOption" :loading="deleteForm.processing" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Panel from 'primevue/panel';
import Card from 'primevue/card';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';

const props = defineProps({
    options: Object,
    categories: Object,
});

// Initialize activeTab with the first category key
const activeTab = ref(Object.keys(props.categories)[0] || 'status');
const showDialog = ref(false);
const showDeleteDialog = ref(false);
const dialogMode = ref('add');
const optionToDelete = ref(null);

const form = useForm({
    category: '',
    value: '',
    label: '',
    color: '',
    sort_order: 0,
    is_active: true,
    description: '',
});

const deleteForm = useForm({});

const getCategoryDescription = (category) => {
    const descriptions = {
        attachment_type: 'Types of attachments that can be uploaded for disbursements (e.g., voucher, cheque, receipt)',
        grant_provision: 'Types of grants that can be provided to scholars (e.g., Matriculation, RLE, Tuition)',
        obr_status: 'Status options for OBR (Obligation Request) processing',
        disbursement_type: 'Categories of disbursements (e.g., regular, reimbursement, financial assistance)',
        priority_level: 'Priority levels for scholarship applications (e.g., low, normal, high, urgent)',
        term: 'Academic terms or semesters (e.g., 1st Semester, 2nd Semester, Summer)',
        year_level: 'Student year levels (e.g., 1st Year, 2nd Year, 3rd Year, 4th Year)',
        academic_year: 'Academic year periods (e.g., 2024-2025, 2025-2026)',
        form_category: 'Categories for form templates and documents (e.g., Forms, Templates, Guidelines)',
        religion: 'Religious affiliations for applicant profiles (e.g., Roman Catholic, Islam, Protestant, Buddhism)',
    };
    return descriptions[category] || 'Manage options for this category';
};

const getOptionsForCategory = (category) => {
    return props.options[category] || [];
};

const openAddDialog = (category = null) => {
    dialogMode.value = 'add';
    form.reset();
    form.clearErrors();
    form.is_active = true;

    if (category) {
        form.category = category;
    } else {
        // Get current category from active tab
        const categoryKeys = Object.keys(props.categories);
        form.category = categoryKeys[activeTabIndex.value];
    }

    showDialog.value = true;
};

const openEditDialog = (option) => {
    dialogMode.value = 'edit';
    form.clearErrors();
    form.category = option.category;
    form.value = option.value;
    form.label = option.label;
    form.color = option.color;
    form.sort_order = option.sort_order;
    form.is_active = option.is_active;
    form.description = option.description;
    form.id = option.id;
    showDialog.value = true;
};

const submitForm = () => {
    if (dialogMode.value === 'add') {
        form.post(route('system-options.store'), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    } else {
        form.put(route('system-options.update', form.id), {
            onSuccess: () => {
                showDialog.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (option) => {
    optionToDelete.value = option;
    showDeleteDialog.value = true;
};

const deleteOption = () => {
    deleteForm.delete(route('system-options.destroy', optionToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            optionToDelete.value = null;
        },
    });
};

const toggleActive = (option) => {
    router.post(route('system-options.toggle-active', option.id));
};

const moveUp = (option, category) => {
    const categoryOptions = getOptionsForCategory(category);
    const index = categoryOptions.findIndex(o => o.id === option.id);
    if (index > 0) {
        const options = categoryOptions.map((opt, idx) => ({
            id: opt.id,
            sort_order: idx === index ? categoryOptions[index - 1].sort_order :
                idx === index - 1 ? categoryOptions[index].sort_order :
                    opt.sort_order
        }));

        router.post(route('system-options.reorder'), { options });
    }
};

const moveDown = (option, category) => {
    const categoryOptions = getOptionsForCategory(category);
    const index = categoryOptions.findIndex(o => o.id === option.id);
    if (index < categoryOptions.length - 1) {
        const options = categoryOptions.map((opt, idx) => ({
            id: opt.id,
            sort_order: idx === index ? categoryOptions[index + 1].sort_order :
                idx === index + 1 ? categoryOptions[index].sort_order :
                    opt.sort_order
        }));

        router.post(route('system-options.reorder'), { options });
    }
};
</script>
