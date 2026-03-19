<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="genderOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start">
                <div>{{genderOptions.find(o => o.value === slotProps.value)?.label || slotProps.value}}</div>
            </div>
            <span v-else>{{ slotProps.placeholder }}</span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start">
                <div>{{ slotProps.option.label }}</div>
            </div>
        </template>
    </Select>
</template>

<script setup>

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
