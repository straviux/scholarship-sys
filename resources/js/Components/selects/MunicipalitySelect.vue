<script setup>
import { ref, watch } from 'vue';
import Multiselect from 'vue-multiselect';
import municipalitiesData from '@/Data/municipalities.json';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    customPlaceholder: {
        type: String,
        default: 'Select Municipality'
    },
});

const emit = defineEmits(['update:modelValue']);
const localValue = ref(props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
});

// Emit changes to parent
watch(localValue, (val) => {
    emit('update:modelValue', val);
});


const municipalities = municipalitiesData.municipalities;

// Ensure selected value matches localValue when options are loaded
watch(
    () => municipalities,
    (newOptions) => {
        // console.log(localValue.value)
        if (localValue.value && newOptions.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                localValue.value = newOptions.filter(m =>
                    localValue.value.some(val => val === m.name?.toLowerCase())
                );
            } else {
                const selected = newOptions.find(m => m.name?.toLowerCase() === localValue.value);
                if (selected) localValue.value = selected;
            }
        } else {
            localValue.value = "";
        }
    },
    { immediate: true }
);
</script>

<template>
    <!-- <Multiselect v-model="localValue" :options="municipalities" :multiple="props.multiple" label="name" track-by="id"
        :show-labels="false" placeholder="Select municipality" /> -->
    <Select v-model="localValue" :options="municipalities" filter autoFilterFocus showClear optionLabel="name"
        :placeholder="customPlaceholder" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.name }}</div>
            </div>
            <span v-else>
                <div class="flex itesm-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
    </Select>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>