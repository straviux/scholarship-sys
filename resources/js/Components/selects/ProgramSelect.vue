<script setup>
import { ref, watch, onMounted, computed } from 'vue';
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
    iosCompact: {
        type: Boolean,
        default: false,
    },
    showClear: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('scholarshipprograms.getactivelist'));
const programs = ref([]);
const loading = ref(false);

const getProgramDisplayText = (program) => {
    if (!program || typeof program !== 'object') {
        return program || '';
    }

    return program.shortname || program.name || '';
};

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

const isSameProgramSelection = (left, right) => {
    if (Array.isArray(left) || Array.isArray(right)) {
        if (!Array.isArray(left) || !Array.isArray(right)) {
            return false;
        }

        if (left.length !== right.length) {
            return false;
        }

        return left.every((entry, index) => entry === right[index]);
    }

    return left === right;
};

// Local value for v-modelroute('scholarshipprograms.getactivelist')
const localValue = ref(resolveProgramValue(props.modelValue));

watch(
    [() => props.modelValue, () => programs.value],
    () => {
        const resolvedValue = resolveProgramValue(props.modelValue);

        if (!isSameProgramSelection(localValue.value, resolvedValue)) {
            localValue.value = resolvedValue;
        }
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    if (!isSameProgramSelection(val, props.modelValue)) {
        emit('update:modelValue', val);
    }
}, { deep: true });

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'program-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'program-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});

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
    <MultiSelect v-if="multiple" v-model="localValue" :options="programs" filter
        :filterFields="['name', 'shortname']" autoFilterFocus showSelectAll :showClear="showClear" optionLabel="name"
        :placeholder="customPlaceholder" class="w-full" display="chip" :maxSelectedLabels="3"
        :selectedItemsLabel="'{0} programs selected'" :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ getProgramDisplayText(slotProps.option) }}</div>
            </div>
        </template>
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ getProgramDisplayText(slotProps.value) }}
            </div>
        </template>
    </MultiSelect>

    <Select v-else v-model="localValue" :options="programs" filter :filterFields="['name', 'shortname']" autoFilterFocus
        :showClear="showClear" optionLabel="name" :placeholder="customPlaceholder" class="w-full"
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ getProgramDisplayText(slotProps.value) }}</div>
            </div>
            <span v-else>
                <div class="flex w-full" :class="customPlaceholderClass">{{
                    slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ getProgramDisplayText(slotProps.option) }}</div>
            </div>
        </template>
    </Select>
</template>

<style>
.program-select-overlay {
    border-radius: 12px;
}

.program-select-root--compact {
    border-radius: 0.875rem;
}
</style>
