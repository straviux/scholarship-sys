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
    }
});

const emit = defineEmits(['update:modelValue']);
const terms = [
    { label: "1ST SEMESTER", value: "1ST SEMESTER" },
    { label: "2ND SEMESTER", value: "2ND SEMESTER" },
    { label: "3RD SEMESTER", value: "3RD SEMESTER" },
    { label: "1ST TRIMESTER", value: "1ST TRIMESTER" },
    { label: "2ND TRIMESTER", value: "2ND TRIMESTER" },
    { label: "3RD TRIMESTER", value: "3RD TRIMESTER" },
    { label: "SUMMER", value: "SUMMER" },
]
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
            localValue.value = terms.filter(m =>
                localValue.value.some(val => val === m.value || val === m)
            );
        } else {
            const selected = terms.find(m => m.value === localValue.value);
            if (selected) localValue.value = selected;
        }
    }
    emit('update:modelValue', val);
});
watch(
    () => terms,
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
    <Select v-model="localValue" :options="terms" filter :filterFields="['label', 'value']" autoFilterFocus showClear
        optionLabel="label" placeholder="Select Term" class="w-full">
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
