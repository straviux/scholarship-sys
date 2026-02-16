<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="civilStatusOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" variant="filled" fluid />
</template>

<script setup>

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    inputId: {
        type: String,
        default: 'civil_status'
    },
    placeholder: {
        type: String,
        default: 'Select Civil Status'
    }
});

const emit = defineEmits(['update:modelValue']);

const civilStatusOptions = [
    { label: 'Single', value: 'Single' },
    { label: 'Married', value: 'Married' },
    { label: 'Widowed', value: 'Widowed' },
    { label: 'Separated', value: 'Separated' },
    { label: 'N/A', value: 'N/A' },
];

const handleUpdate = (value) => {
    emit('update:modelValue', value);
};
</script>
