<script setup>
import { ref, watch, computed, onMounted } from 'vue';
const props = defineProps({

    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    label: {
        type: String,
        default: 'name'
    }
});

const emit = defineEmits(['update:modelValue']);
const currentYear = new Date().getFullYear();
const startYear = 2018; // Define your desired starting year


const acad_year = computed(() => {
    const years = [];
    // Add all range year options first
    for (let year = currentYear; year >= startYear; year--) {
        years.push({
            label: `${year}-${year + 1}`,
            value: `${year}-${year + 1}`
        });
    }
    // Add all single year options
    for (let year = currentYear; year >= startYear; year--) {
        years.push({
            label: year.toString(),
            value: year.toString()
        });
    }
    return years;
});
// Local value for v-model
const defaultYearValue = `${currentYear}-${currentYear + 1}`;
const defaultYearObj = computed(() => acad_year.value.find(y => y.value === defaultYearValue));

// Helper function to find the year object from a value (string or object)
const findYearObject = (val) => {
    if (!val) return null;
    // If val is already an object with a value property, find by that value
    if (typeof val === 'object' && val.value) {
        return acad_year.value.find(y => y.value === val.value);
    }
    // If val is a string, find by value
    if (typeof val === 'string') {
        return acad_year.value.find(y => y.value === val);
    }
    return null;
};

const localValue = ref(
    props.modelValue
        ? findYearObject(props.modelValue) || defaultYearObj.value
        : defaultYearObj.value
);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    // Always select the correct year object from acad_year
    if (val) {
        const selected = findYearObject(val);
        localValue.value = selected || defaultYearObj.value;
    } else {
        // Default to current academic year if not set
        localValue.value = defaultYearObj.value;
    }
}, { immediate: true });

// Emit changes to parent - emit only the value string
watch(localValue, (val) => {
    emit('update:modelValue', val?.value || val);
}, { deep: true });

// Emit initial value on mount to ensure parent gets the preselected value
onMounted(() => {
    if (localValue.value) {
        emit('update:modelValue', localValue.value?.value || localValue.value);
    }
});

</script>

<template>
    <Select v-model="localValue" :options="acad_year" filter autoFilterFocus showClear optionLabel="label"
        placeholder="Select Academic Year" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.label }}</div>
            </div>
            <span v-else>
                <div class="flex itesm-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.label }}</div>
            </div>
        </template>
    </Select>
</template>
