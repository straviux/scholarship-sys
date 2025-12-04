<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'selected']);

const selectedCategory = ref('yakap-capitol');
const selectedLocation = ref(null);

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' }
];

// Clear location when category is yakap-capitol
watch(selectedCategory, (newCategory) => {
    if (newCategory === 'yakap-capitol') {
        selectedLocation.value = null;
    }
});

const handleConfirm = () => {
    // Convert selected object to string if needed
    let locationValue = selectedLocation.value;
    if (locationValue && typeof locationValue === 'object') {
        locationValue = JSON.stringify(locationValue);
    }

    emit('selected', {
        category: selectedCategory.value,
        location: locationValue || ''
    });
    emit('update:visible', false);
    resetForm();
};

const handleCancel = () => {
    emit('update:visible', false);
    resetForm();
};

const resetForm = () => {
    selectedCategory.value = 'yakap-capitol';
    selectedLocation.value = null;
};
</script>

<template>
    <Dialog :visible="visible" modal header="Select YAKAP Category" :style="{ width: '50vw' }"
        @update:visible="emit('update:visible', $event)" @hide="handleCancel">
        <div class="space-y-6">
            <p class="text-gray-700">Please select a YAKAP category for this new applicant:</p>

            <div class="flex flex-col gap-3">
                <label for="yakap-select" class="font-medium text-gray-700">YAKAP Category:</label>
                <Select v-model="selectedCategory" :options="yakapCategoryOptions" optionLabel="label"
                    optionValue="value" placeholder="Select YAKAP Category" class="w-full" inputId="yakap-select" />
            </div>

            <!-- Municipality Selection for YAKAP Field -->
            <div v-if="selectedCategory === 'yakap-field'" class="flex flex-col gap-3">
                <label for="yakap-municipality" class="font-medium text-gray-700">Municipality:</label>
                <MunicipalitySelect v-model="selectedLocation" placeholder="Select Municipality" class="w-full"
                    :clearable="false" inputId="yakap-municipality" />
            </div>

            <!-- School Selection for YAKAP School -->
            <div v-if="selectedCategory === 'yakap-school'" class="flex flex-col gap-3">
                <label for="yakap-school" class="font-medium text-gray-700">School:</label>
                <SchoolSelect v-model="selectedLocation" placeholder="Select School" class="w-full" :clearable="false"
                    inputId="yakap-school" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" icon="pi pi-times" @click="handleCancel" class="p-button-text" />
            <Button label="Continue" icon="pi pi-check" @click="handleConfirm" severity="success" />
        </template>
    </Dialog>
</template>
