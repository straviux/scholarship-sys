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
    },
    iosCompact: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);
const currentYear = new Date().getFullYear();
const startYear = 2016;


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
const normalizeAcademicYearToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim();
};

const findYearObject = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value.label && value.value) {
        return value;
    }

    const rawValue = typeof value === 'object'
        ? value.value ?? value.label ?? null
        : value;

    const normalized = normalizeAcademicYearToken(rawValue);
    if (!normalized) {
        return null;
    }

    return acad_year.value.find((year) => {
        return normalizeAcademicYearToken(year.value) === normalized
            || normalizeAcademicYearToken(year.label) === normalized;
    }) || {
        label: typeof rawValue === 'string' ? rawValue : String(rawValue),
        value: rawValue,
    };
};

const isSameAcademicYearSelection = (left, right) => left === right;

const toAcademicYearModelValue = (value) => value?.value ?? value ?? null;

const localValue = ref(findYearObject(props.modelValue));

watch(
    [() => props.modelValue, () => acad_year.value],
    () => {
        const resolvedValue = findYearObject(props.modelValue);

        if (!isSameAcademicYearSelection(localValue.value, resolvedValue)) {
            localValue.value = resolvedValue;
        }
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    const emittedValue = toAcademicYearModelValue(val);

    if (emittedValue !== props.modelValue) {
        emit('update:modelValue', emittedValue);
    }
}, { deep: true });

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'academic-year-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'academic-year-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});

</script>

<template>
    <Select v-model="localValue" :options="acad_year" filter autoFilterFocus showClear optionLabel="label"
        placeholder="Select Academic Year" class="w-full" :size="iosCompact ? 'small' : undefined" :pt="selectPt">
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

<style>
.academic-year-select-overlay {
    border-radius: 12px;
}

.academic-year-select-root--compact {
    border-radius: 0.875rem;
}
</style>
