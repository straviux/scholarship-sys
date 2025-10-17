<!-- 
    EXAMPLE: How to use ScholarFormModal in your pages
    This shows the complete implementation pattern
-->

<template>
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Scholars Management</h1>
            <Button label="Add Scholar" icon="pi pi-plus" @click="openCreateModal" />
        </div>

        <!-- Your scholars table here -->
        <DataTable :value="scholars">
            <!-- ... table columns ... -->
            <Column header="Actions">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" @click="openEditModal(data)" text rounded />
                </template>
            </Column>
        </DataTable>

        <!-- Scholar Form Modal -->
        <ScholarFormModal v-model:visible="showScholarModal" :mode="scholarMode" :profile="selectedScholar"
            @success="handleSuccess" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ScholarFormModal from '@/Components/ScholarFormModal.vue';

// Props from backend
const props = defineProps({
    scholars: Array,
});

// Modal state
const showScholarModal = ref(false);
const scholarMode = ref('create'); // 'create' or 'edit'
const selectedScholar = ref(null);

// Open modal for creating new scholar
const openCreateModal = () => {
    scholarMode.value = 'create';
    selectedScholar.value = null;
    showScholarModal.value = true;
};

// Open modal for editing existing scholar
const openEditModal = (scholar) => {
    scholarMode.value = 'edit';
    selectedScholar.value = scholar;
    showScholarModal.value = true;
};

// Handle successful form submission
const handleSuccess = () => {
    // Reload only the scholars data (partial reload)
    router.reload({
        only: ['scholars'],
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
