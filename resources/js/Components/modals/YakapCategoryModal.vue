<script setup>
import { ref, watch } from 'vue';
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
    // Extract just the location name (school or municipality) instead of entire object
    let locationValue = selectedLocation.value;
    if (locationValue) {
        if (typeof locationValue === 'object' && locationValue?.name) {
            locationValue = locationValue.name;  // Extract just the name
        }
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
    <IosModal
        :visible="visible"
        width="50vw"
        title="Select YAKAP Category"
        :show-action="true"
        body-style="padding: 16px;"
        @update:visible="emit('update:visible', $event)"
        @close="handleCancel"
        @action="handleConfirm"
    >
        <div class="space-y-6">
            <p class="text-gray-700 dark:text-gray-300">Please select a YAKAP category for this new applicant:</p>

            <div class="flex flex-col gap-3">
                <label for="yakap-select" class="font-medium text-gray-700 dark:text-gray-300">YAKAP Category:</label>
                <Select v-model="selectedCategory" :options="yakapCategoryOptions" optionLabel="label"
                    optionValue="value" placeholder="Select YAKAP Category" class="w-full" inputId="yakap-select" />
            </div>

            <div v-if="selectedCategory === 'yakap-field'" class="flex flex-col gap-3">
                <label for="yakap-municipality"
                    class="font-medium text-gray-700 dark:text-gray-300">Municipality:</label>
                <MunicipalitySelect v-model="selectedLocation" placeholder="Select Municipality" class="w-full"
                    :clearable="false" inputId="yakap-municipality" />
            </div>

            <div v-if="selectedCategory === 'yakap-school'" class="flex flex-col gap-3">
                <label for="yakap-school" class="font-medium text-gray-700 dark:text-gray-300">School:</label>
                <SchoolSelect v-model="selectedLocation" placeholder="Select School" class="w-full" :clearable="false"
                    inputId="yakap-school" />
            </div>
        </div>
    </IosModal>
</template>
