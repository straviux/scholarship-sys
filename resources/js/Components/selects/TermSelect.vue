<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

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
const terms = ref([]);
const loading = ref(false);

// Fetch term options from backend
const fetchTermOptions = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('api.system-options.category', 'term'));

        if (response.data && response.data.length > 0) {
            terms.value = response.data.map(option => ({
                label: option.label,
                value: option.value
            }));
        }
    } catch (error) {
        console.error('Failed to fetch term options:', error);
        terms.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTermOptions();
});

// Local value for v-model
const localValue = ref(props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
});

// Emit changes to parent - emit only the value string
watch(localValue, (val) => {
    if (localValue.value && terms.value.length) {
        if (props.multiple && Array.isArray(localValue.value)) {
            localValue.value = terms.value.filter(m =>
                localValue.value.some(val => val === m.value || val === m)
            );
        } else {
            const selected = terms.value.find(m => m.value === localValue.value);
            if (selected) localValue.value = selected;
        }
    }
    emit('update:modelValue', val?.value || val);
});

watch(
    () => terms.value,
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
        optionLabel="label" placeholder="Select Term" class="w-full" :loading="loading">
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
