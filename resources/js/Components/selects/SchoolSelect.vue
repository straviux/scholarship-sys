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
        default: 'Select school'
    },
    showNullOption: {
        type: Boolean,
        default: false,
    },
    iosCompact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('schools.getactivelist'));
const schools = ref([]);
const loading = ref(false);

// Computed property to include null option when needed
const schoolOptions = computed(() => {
    const options = [...(schools.value || [])];
    if (props.showNullOption) {
        options.unshift({
            id: null,
            name: 'No School',
            shortname: 'NO SCHOOL',
            isNullOption: true
        });
    }
    return options;
});

const normalizeSchoolToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toLowerCase();
};

const resolveSingleSchool = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value.id !== undefined) {
        return schoolOptions.value.find((school) => school.id == value.id)
            || schoolOptions.value.find((school) => normalizeSchoolToken(school.shortname) === normalizeSchoolToken(value.shortname))
            || schoolOptions.value.find((school) => normalizeSchoolToken(school.name) === normalizeSchoolToken(value.name))
            || value;
    }

    const normalized = normalizeSchoolToken(value);
    if (!normalized) {
        return null;
    }

    return schoolOptions.value.find((school) => {
        return normalizeSchoolToken(school.shortname) === normalized
            || normalizeSchoolToken(school.name) === normalized
            || String(school.id) === String(value);
    }) || value;
};

const resolveSchoolValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value.map((entry) => resolveSingleSchool(entry)).filter(Boolean);
    }

    return resolveSingleSchool(value);
};

const isSameSchoolSelection = (left, right) => {
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

// Local value for v-modelroute('scholarshipschools.getactivelist')
const localValue = ref(resolveSchoolValue(props.modelValue));

watch(
    [() => props.modelValue, () => schoolOptions.value],
    () => {
        const resolvedValue = resolveSchoolValue(props.modelValue);

        if (!isSameSchoolSelection(localValue.value, resolvedValue)) {
            localValue.value = resolvedValue;
        }
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    if (!isSameSchoolSelection(val, props.modelValue)) {
        emit('update:modelValue', val);
    }
}, { deep: true });

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'school-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'school-select-root--compact', style: 'min-height: 2.25rem;' },
        labelContainer: { style: 'padding: 0.4375rem 0.75rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});

// Watch for changes in data and update schools
watch(
    () => data.value,
    (newData) => {
        schools.value = newData || [];
    },
    { immediate: true }
);



onMounted(fetchData);
</script>

<template>
    <!-- MultiSelect for multiple selection -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="schoolOptions" filter autoFilterFocus showClear
        optionLabel="name" :placeholder="customPlaceholder" class="w-full" :filterFields="['name', 'shortname']"
        :maxSelectedLabels="3" :selectedItemsLabel="'{0} schools selected'" showSelectAll
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ slotProps.value.shortname }}
            </div>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <span class="text-[12px]">{{ slotProps.option.name }}</span><br>
                    <span class="text-[10px] font-bold">[{{ slotProps.option.shortname }}]</span>
                </div>
            </div>
        </template>
    </MultiSelect>

    <!-- Select for single selection -->
    <Select v-else v-model="localValue" :options="schoolOptions" filter autoFilterFocus showClear optionLabel="name"
        :placeholder="customPlaceholder" class="w-full" :filterFields="['name', 'shortname']"
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                {{ slotProps.value.shortname }}
            </div>
            <span v-else>
                <div class="flex items-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <span class="text-[12px]">{{ slotProps.option.name }}</span><br>
                    <span class="text-[10px] font-bold">[{{ slotProps.option.shortname }}]</span>
                </div>
            </div>
        </template>
    </Select>
</template>

<style>
.school-select-overlay {
    border-radius: 12px;
}

.school-select-root--compact {
    border-radius: 0.875rem;
}
</style>
