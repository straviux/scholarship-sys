<script setup>
import { ref, watch, computed } from 'vue';
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
    for (let year = currentYear; year >= startYear; year--) {
        years.push({
            label: `${year}-${year + 1}`,
            value: `${year}-${year + 1}`
        });
    }
    return years;
});
// Local value for v-model
const defaultYearValue = `${currentYear}-${currentYear + 1}`;
const defaultYearObj = computed(() => acad_year.value.find(y => y.value === defaultYearValue));
const localValue = ref(
    props.modelValue
        ? acad_year.value.find(y => y.value === props.modelValue || y === props.modelValue) || props.modelValue
        : defaultYearObj.value
);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    // Always select the correct year object from acad_year
    if (val) {
        const selected = acad_year.value.find(y => y.value === val || y === val);
        localValue.value = selected || val;
    } else {
        // Default to current academic year if not set
        localValue.value = defaultYearObj.value;
    }
});

// Sync localValue with parent prop
// watch(() => props.preselect, (val) => {
//     localValue.value = val;
// });
// Emit changes to parent
watch(localValue, (val) => {

    if (localValue.value) {
        if (props.multiple && Array.isArray(localValue.value)) {
            localValue.value = acad_year.value.filter(m =>
                localValue.value.some(val => val == m.value || val == m)
            );
        } else {
            const selected = acad_year.value.find(m => m.value == localValue.value);
            if (selected) localValue.value = selected;
        }
    }
    emit('update:modelValue', val);
});
watch(
    () => acad_year,
    (newOptions) => {

        if (props.multiple && Array.isArray(localValue.value)) {
            localValue.value = newOptions.filter(m =>
                localValue.value.some(val => val === m.value || val === m)
            );
        } else {
            const selected = acad_year.value.find(m => m.value === localValue.value);
            if (selected) localValue.value = selected;
        }

    },
    { immediate: true });

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
