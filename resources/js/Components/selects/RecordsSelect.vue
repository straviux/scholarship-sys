<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Select from 'primevue/select';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: 10,
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

const localValue = ref(parseInt(props.modelValue) || 10);

console.log('RecordsSelect - Initial props.modelValue:', props.modelValue, 'Type:', typeof props.modelValue);
console.log('RecordsSelect - Initial localValue:', localValue.value);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    console.log('RecordsSelect - modelValue changed from parent:', val, 'Type:', typeof val);
    localValue.value = parseInt(val) || 10;
}, { immediate: true });

// Emit changes to parent
watch(localValue, (val) => {
    console.log('RecordsSelect - localValue changed, emitting:', val);
    emit('update:modelValue', val);
});

onMounted(() => {
    console.log('RecordsSelect mounted with modelValue:', props.modelValue);
    // Ensure localValue is initialized correctly
    localValue.value = props.modelValue;
});
</script>

<template>
    <Select v-model="localValue" :options="recordsOptions" optionLabel="label" optionValue="value"
        :placeholder="customPlaceholder" :size="size" :class="class" />
</template>