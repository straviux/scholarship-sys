<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    customPlaceholder: {
        type: String,
        default: 'Select Records'
    },
    size: {
        type: String,
        default: 'normal'
    },
    class: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

// Records options for dropdown
const recordsOptions = [
    { label: '500', value: 500 },
    { label: '200', value: 200 },
    { label: '100', value: 100 },
    { label: '50', value: 50 },
    { label: '25', value: 25 },
    { label: '10', value: 10 },
    { label: '5', value: 5 }
];

const localValue = ref(props.modelValue ? parseInt(props.modelValue) : null);



// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val ? parseInt(val) : null;
}, { immediate: true });

// Emit changes to parent
watch(localValue, (val) => {
    emit('update:modelValue', val);
});

onMounted(() => {
    // Ensure localValue is initialized correctly
    localValue.value = props.modelValue ? parseInt(props.modelValue) : null;
});
</script>

<template>
    <Select v-model="localValue" :options="recordsOptions" optionLabel="label" optionValue="value"
        :placeholder="customPlaceholder" :size="size" :class="class" showClear />
</template>
