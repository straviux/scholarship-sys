<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="religionOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" variant="filled" fluid showClear filter
        :loading="loading" />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useCachedData } from '@/composable/useCachedData';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    inputId: {
        type: String,
        default: 'religion'
    },
    placeholder: {
        type: String,
        default: 'Select Religion'
    }
});

const emit = defineEmits(['update:modelValue']);

const religionOptions = ref([]);
const loading = ref(false);

// Original comprehensive religion options as fallback
const fallbackReligionOptions = [
    { label: 'Roman Catholic', value: 'Roman Catholic' },
    { label: 'Islam', value: 'Islam' },
    { label: 'Iglesia ni Cristo', value: 'Iglesia ni Cristo' },
    { label: 'Protestant', value: 'Protestant' },
    { label: 'Born Again Christian', value: 'Born Again Christian' },
    { label: 'Seventh-day Adventist', value: 'Seventh-day Adventist' },
    { label: 'Jehovah\'s Witnesses', value: 'Jehovah\'s Witnesses' },
    { label: 'Buddhism', value: 'Buddhism' },
    { label: 'Hinduism', value: 'Hinduism' },
    { label: 'Life Church', value: 'Life Church' },
    { label: 'Pentecostal', value: 'Pentecostal' },
    { label: 'Evangelical', value: 'Evangelical' },
    { label: 'Baptist', value: 'Baptist' },
    { label: 'Methodist', value: 'Methodist' },
    { label: 'Foursquare Gospel Church', value: 'Foursquare Gospel Church' },
    { label: 'The Church of Jesus Christ of Latter-day Saints (Mormon)', value: 'The Church of Jesus Christ of Latter-day Saints (Mormon)' },
    { label: 'Bible Baptist Church', value: 'Bible Baptist Church' },
    { label: 'Philippine Independent Church (Aglipayan)', value: 'Philippine Independent Church' },
    { label: 'United Church of Christ in the Philippines', value: 'United Church of Christ in the Philippines' },
    { label: 'Church of Christ', value: 'Church of Christ' },
    { label: 'Other Christian', value: 'Other Christian' },
    { label: 'No Religion', value: 'No Religion' },
    { label: 'None', value: 'None' },
    { label: 'Others', value: 'Others' },
];

// Use cached data composable for religion options
const { data: cachedReligionOptions, loading: cacheLoading, fetchData: fetchReligionOptions } = useCachedData(
    'religion',
    async () => {
        const response = await axios.get(route('api.system-options.category', 'religion'));

        if (response.data && response.data.length > 0) {
            const backendOptions = response.data.map(option => ({
                label: option.label,
                value: option.value
            }));

            // Merge backend options with fallback options, removing duplicates
            const allOptions = [...backendOptions];
            fallbackReligionOptions.forEach(fallback => {
                if (!allOptions.find(opt => opt.value === fallback.value)) {
                    allOptions.push(fallback);
                }
            });

            return allOptions;
        }

        return fallbackReligionOptions;
    }
);

// Watch cached data and sync to local options
watch(
    () => cachedReligionOptions.value,
    (newData) => {
        religionOptions.value = newData || fallbackReligionOptions;
    },
    { immediate: true }
);

// Watch cache loading state
watch(
    () => cacheLoading.value,
    (isLoading) => {
        loading.value = isLoading;
    }
);

onMounted(() => {
    fetchReligionOptions();
});

const handleUpdate = (value) => {
    emit('update:modelValue', value);
};
</script>
