<script setup>
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Select from 'primevue/select';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'selected']);

const selectedCategory = ref('yakap-capitol');

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' }
];

const handleConfirm = () => {
    emit('selected', selectedCategory.value);
    emit('update:visible', false);
    selectedCategory.value = 'yakap-capitol'; // Reset for next use
};

const handleCancel = () => {
    emit('update:visible', false);
    selectedCategory.value = 'yakap-capitol'; // Reset for next use
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
        </div>

        <template #footer>
            <Button label="Cancel" icon="pi pi-times" @click="handleCancel" class="p-button-text" />
            <Button label="Continue" icon="pi pi-check" @click="handleConfirm" severity="success" />
        </template>
    </Dialog>
</template>
