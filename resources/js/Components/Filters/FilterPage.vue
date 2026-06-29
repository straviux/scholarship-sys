<template>
    <div class="filter-page flex flex-wrap gap-3 items-center">
        <!-- Record Filter Radio Buttons -->
        <template v-if="showRecordFilter">
            <label class="flex items-center gap-2 cursor-pointer">
                <RadioButton v-model="internalUserFilter" name="userFilter" value="all" inputId="uf-all" />
                <span class="text-sm text-gray-700 dark:text-gray-300">All Records</span>
                <Badge :value="totalRecordsCount" severity="secondary" />
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <RadioButton v-model="internalUserFilter" name="userFilter" value="my-records" inputId="uf-my" />
                <span class="text-sm text-gray-700 dark:text-gray-300">My Records</span>
                <Badge :value="myRecordsCount" severity="secondary" />
            </label>
            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
        </template>

        <!-- Filter Dropdowns -->
        <template v-for="filter in filters" :key="filter.key">
            <Select
                v-model="internalFilters[filter.key]"
                :options="filter.options"
                optionLabel="label"
                optionValue="value"
                :placeholder="filter.placeholder"
                size="small"
                :class="filter.class || 'w-40'"
                showClear
            />
        </template>

        <!-- Clear All Button -->
        <AppButton
            v-if="hasActiveFilters"
            icon="funnel-x"
            severity="danger"
            text
            rounded
            @click="clearAll"
            v-tooltip.bottom="'Clear all filters'"
        />

        <!-- Per Page Selector -->
        <div v-if="showPerPage" class="ml-auto flex items-center gap-2">
            <RecordsSelect v-model="internalPerPage" size="small" class="w-auto" />
            <span class="text-sm text-gray-600 dark:text-gray-400">/ <strong>{{ filteredTotal }}</strong></span>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';

const props = defineProps({
    filters: {
        type: Array,
        default: () => [],
    },
    showRecordFilter: {
        type: Boolean,
        default: false,
    },
    showPerPage: {
        type: Boolean,
        default: true,
    },
    totalRecordsCount: {
        type: [Number, String],
        default: 0,
    },
    myRecordsCount: {
        type: [Number, String],
        default: 0,
    },
    filteredTotal: {
        type: [Number, String],
        default: 0,
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    userFilter: {
        type: String,
        default: 'all',
    },
    perPage: {
        type: Number,
        default: 10,
    },
});

const emit = defineEmits([
    'update:modelValue',
    'update:userFilter',
    'update:perPage',
    'clear',
]);

const internalFilters = reactive({ ...props.modelValue });
const internalUserFilter = ref(props.userFilter);
const internalPerPage = ref(props.perPage);

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return Object.values(internalFilters).some(val => val !== null && val !== undefined && val !== '');
});

// Watch for changes and propagate
watch(internalFilters, (newVal) => {
    emit('update:modelValue', { ...newVal });
}, { deep: true });

watch(internalUserFilter, (newVal) => {
    emit('update:userFilter', newVal);
});

watch(internalPerPage, (newVal) => {
    emit('update:perPage', newVal);
});

// Watch external changes
watch(() => props.modelValue, (newVal) => {
    Object.assign(internalFilters, newVal);
}, { deep: true });

watch(() => props.userFilter, (newVal) => {
    internalUserFilter.value = newVal;
});

watch(() => props.perPage, (newVal) => {
    internalPerPage.value = newVal;
});

const clearAll = () => {
    // Reset all filter values to null
    for (const key of Object.keys(internalFilters)) {
        internalFilters[key] = null;
    }
    emit('clear');
};
</script>
