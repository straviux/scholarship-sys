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
    },
    multiple: {
        type: Boolean,
        default: false,
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

const normalizeTermToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toUpperCase();
};

const resolveSingleTerm = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value.label && value.value) {
        return value;
    }

    const rawValue = typeof value === 'object'
        ? value.value ?? value.label ?? null
        : value;

    const normalized = normalizeTermToken(rawValue);
    if (!normalized) {
        return null;
    }

    return terms.value.find((option) => {
        return normalizeTermToken(option.value) === normalized
            || normalizeTermToken(option.label) === normalized;
    }) || {
        label: typeof rawValue === 'string' ? rawValue : String(rawValue),
        value: rawValue,
    };
};

const resolveTermValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value
            .map((entry) => resolveSingleTerm(entry))
            .filter(Boolean);
    }

    return resolveSingleTerm(value);
};

const emitTermValue = (value) => {
    if (Array.isArray(value)) {
        emit('update:modelValue', value.map((entry) => entry?.value ?? entry).filter(Boolean));
        return;
    }

    emit('update:modelValue', value?.value ?? value ?? null);
};

const localValue = ref(resolveTermValue(props.modelValue));

watch(
    [() => props.modelValue, () => terms.value],
    () => {
        localValue.value = resolveTermValue(props.modelValue);
    },
    { immediate: true, deep: true }
);

watch(localValue, (value) => {
    emitTermValue(value);
}, { deep: true });

</script>

<template>
    <Select v-model="localValue" :options="terms" filter :filterFields="['label', 'value']" autoFilterFocus showClear
        optionLabel="label" placeholder="Select Term" class="w-full" :loading="loading"
        :pt="{ overlay: { style: 'border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.label ?? slotProps.value }}</div>
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
