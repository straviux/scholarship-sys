<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="genderOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" variant="filled" fluid />
</template>

<script setup>
import Select from 'primevue/select';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    inputId: {
        type: String,
        default: 'gender'
    },
    placeholder: {
        type: String,
        default: 'Select Gender'
    }
});

const emit = defineEmits(['update:modelValue']);

const genderOptions = [
    { label: 'Male', value: 'M' },
    { label: 'Female', value: 'F' },
];

const handleUpdate = (value) => {
    emit('update:modelValue', value);
};
</script>
