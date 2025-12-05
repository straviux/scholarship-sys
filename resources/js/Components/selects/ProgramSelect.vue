<script setup>
import { ref, watch, onMounted } from 'vue';
import { useApi } from '@/composable/api';
const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: 'name'
    },
    customPlaceholder: {
        type: String,
        default: 'Select Program'
    },
    customPlaceholderClass: {
        type: String,
        default: ''
    },
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('scholarshipprograms.getactivelist'));
const programs = ref([]);
const loading = ref(false);

// Local value for v-modelroute('scholarshipprograms.getactivelist')
const localValue = ref(props.modelValue);

// Fetch data immediately on creation
fetchData();

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
});


watch(localValue, (val) => {
    emit('update:modelValue', val);
});

// Watch for changes in data and update programs
watch(
    () => data.value,
    (newData) => {
        programs.value = newData || [];
        // If localValue is set, find and set the matching program object
        if (localValue.value && programs.value.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                // Map each value in localValue to the corresponding school object
                localValue.value = localValue.value.map(val => {
                    if (typeof val == 'object' && val != null && val.shortname) {
                        return programs.value.find(program => program.shortname == val.shortname) || val;
                    }
                    return programs.value.find(program =>
                        program.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        program.name?.toLowerCase() == String(val).toLowerCase()
                    ) || val;
                });
            } else {
                // Single value: find the matching program object
                let val = localValue.value;
                if (typeof val == 'object' && val != null && val.shortname) {
                    localValue.value = programs.value.find(program => program.shortname == val.shortname) || val;
                } else {
                    const selected = programs.value.find(program =>
                        program.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        program.name?.toLowerCase() == String(val).toLowerCase()
                    );
                    if (selected) localValue.value = selected;
                }
            }
        }
    },
    { immediate: true }
);

// Watch for changes in scholarshipProgramId and refetch
watch(
    () => props.scholarshipProgramId,
    () => {
        fetchData();
    }
);

onMounted(fetchData);
</script>

<template>
    <Select v-model="localValue" :options="programs" filter :filterFields="['name', 'shortname']" autoFilterFocus
        showClear optionLabel="name" :placeholder="customPlaceholder" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.shortname }}</div>
            </div>
            <span v-else>
                <div class="flex w-full" :class="customPlaceholderClass">{{
                    slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.shortname }}</div>
            </div>
        </template>
    </Select>
</template>