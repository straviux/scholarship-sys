<script setup>
import { ref, watch } from 'vue';
const props = defineProps({

    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    label: {
        type: String,
        default: 'name'
    },
    customPlaceholder: {
        type: String,
        default: 'Select Year Level'
    },
});

const emit = defineEmits(['update:modelValue']);
const year_levels = [
    { label: "1ST YEAR", value: "1ST" },
    { label: "2ND YEAR", value: "2ND" },
    { label: "3RD YEAR", value: "3RD" },
    { label: "4TH YEAR", value: "4TH" },
    { label: "5TH YEAR", value: "5TH" },
    { label: "6TH YEAR", value: "6TH" },
    { label: "GRADUATE", value: "GRADUATE" },
    { label: "PGI", value: "PGI" },
    { label: "REVIEW", value: "REVIEW" },
];
// Local value for v-model
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
    if (localValue.value) {
        if (props.multiple && Array.isArray(localValue.value)) {
            localValue.value = year_levels.filter(m =>
                localValue.value.some(val => val === m.value || val === m)
            );
        } else {
            const selected = year_levels.find(m => m.value === localValue.value);
            if (selected) localValue.value = selected;
        }
    }
    emit('update:modelValue', val);
});
watch(
    () => year_levels,
    (newOptions) => {
        if (localValue.value && newOptions.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                localValue.value = newOptions.filter(m =>
                    localValue.value.some(val => val === m.value || val === m)
                );
            } else {
                const selected = newOptions.find(m => m.value === localValue.value);
                if (selected) localValue.value = selected;
            }
        }
    },
    { immediate: true }
);

</script>

<template>
    <Select v-model="localValue" :options="year_levels" filter autoFilterFocus showClear optionLabel="label"
        :placeholder="customPlaceholder" class="w-full">
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
