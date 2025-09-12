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
    }
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('scholarshipprograms.getactivelist'));
const programs = ref([]);
const loading = ref(false);

// Local value for v-modelroute('scholarshipprograms.getactivelist')
const localValue = ref(props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
});

// Sync localValue with parent prop
// watch(() => props.preselect, (val) => {
//     localValue.value = val;
// });
// Emit changes to parent
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
                localValue.value = programs.value.filter(program =>
                    localValue.value.some(val => val === program.shortname || val === program.name || val === program)
                );
            } else {
                const selected = programs.value.find(program => program.shortname === localValue.value || program.name === localValue.value);
                if (selected) localValue.value = selected;
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
    <Select v-model="localValue" :options="programs" filter autoFilterFocus showClear optionLabel="name"
        placeholder="Select Program" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.shortname }}</div>
            </div>
            <span v-else>
                <div class="flex itesm-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.shortname }}</div>
            </div>
        </template>
    </Select>
</template>