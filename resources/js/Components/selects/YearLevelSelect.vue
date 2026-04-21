<script setup>
import { ref, watch, computed } from 'vue';
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
        default: 'Select Year Level'
    },
    iosCompact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const year_levels = [
    { label: "G12", value: "G12" },
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
const normalizeYearLevelToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toLowerCase();
};

const resolveSingleYearLevel = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        return year_levels.find((option) => option.value === value.value)
            || year_levels.find((option) => normalizeYearLevelToken(option.label) === normalizeYearLevelToken(value.label))
            || value;
    }

    const normalized = normalizeYearLevelToken(value);
    if (!normalized) {
        return null;
    }

    return year_levels.find((option) => {
        return normalizeYearLevelToken(option.value) === normalized
            || normalizeYearLevelToken(option.label) === normalized;
    }) || value;
};

const resolveYearLevelValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value.map((entry) => resolveSingleYearLevel(entry)).filter(Boolean);
    }

    return resolveSingleYearLevel(value);
};

// Local value for v-model
const localValue = ref(resolveYearLevelValue(props.modelValue));

watch(
    () => props.modelValue,
    () => {
        localValue.value = resolveYearLevelValue(props.modelValue);
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    emit('update:modelValue', val);
}, { deep: true });

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'year-level-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'year-level-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});

</script>

<template>
    <Select v-model="localValue" :options="year_levels" filter autoFilterFocus showClear optionLabel="label"
        :placeholder="customPlaceholder" class="w-full" :size="iosCompact ? 'small' : undefined" :pt="selectPt">
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

<style>
.year-level-select-overlay {
    border-radius: 12px;
}

.year-level-select-root--compact {
    border-radius: 0.875rem;
}
</style>
