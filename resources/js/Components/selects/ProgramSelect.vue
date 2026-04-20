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

const normalizeProgramToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toLowerCase();
};

const resolveSingleProgram = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        return programs.value.find((program) => program.id == value.id)
            || programs.value.find((program) => normalizeProgramToken(program.shortname) === normalizeProgramToken(value.shortname))
            || programs.value.find((program) => normalizeProgramToken(program.name) === normalizeProgramToken(value.name))
            || value;
    }

    const normalized = normalizeProgramToken(value);
    if (!normalized) {
        return null;
    }

    return programs.value.find((program) => {
        return normalizeProgramToken(program.shortname) === normalized
            || normalizeProgramToken(program.name) === normalized
            || String(program.id) === String(value);
    }) || value;
};

const resolveProgramValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value.map((entry) => resolveSingleProgram(entry)).filter(Boolean);
    }

    return resolveSingleProgram(value);
};

// Local value for v-modelroute('scholarshipprograms.getactivelist')
const localValue = ref(resolveProgramValue(props.modelValue));

watch(
    [() => props.modelValue, () => programs.value],
    () => {
        localValue.value = resolveProgramValue(props.modelValue);
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    emit('update:modelValue', val);
}, { deep: true });

// Watch for changes in data and update programs
watch(
    () => data.value,
    (newData) => {
        programs.value = newData || [];
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
        showClear optionLabel="name" :placeholder="customPlaceholder" class="w-full"
        :pt="{ overlay: { style: 'border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
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
