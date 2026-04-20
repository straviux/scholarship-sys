<script setup>
import { ref, watch, onMounted } from 'vue';
import { useCachedData } from '@/composable/useCachedData';
import axios from 'axios';

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
        default: 'Select Municipality'
    },
    customPlaceholderClass: {
        type: String,
        default: ''
    },
});

const emit = defineEmits(['update:modelValue']);
const municipalities = ref([]);
const loading = ref(false);

const normalizeMunicipalityToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toLowerCase();
};

const resolveSingleMunicipality = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        return municipalities.value.find((municipality) => municipality.id == value.id)
            || municipalities.value.find((municipality) => normalizeMunicipalityToken(municipality.name) === normalizeMunicipalityToken(value.name))
            || value;
    }

    const normalized = normalizeMunicipalityToken(value);
    if (!normalized) {
        return null;
    }

    return municipalities.value.find((municipality) => {
        return normalizeMunicipalityToken(municipality.name) === normalized
            || String(municipality.id) === String(value);
    }) || value;
};

const resolveMunicipalityValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value.map((entry) => resolveSingleMunicipality(entry)).filter(Boolean);
    }

    return resolveSingleMunicipality(value);
};

// Use cached data composable for municipalities
const { data: cachedMunicipalities, loading: cacheLoading, fetchData: fetchMunicipalities } = useCachedData(
    'municipalities',
    async () => {
        const response = await axios.get(route('api.municipalities.index'));
        return response.data;
    }
);

// Watch cached data and sync to local municipalities
watch(
    () => cachedMunicipalities.value,
    (newData) => {
        municipalities.value = newData || [];
        loading.value = false;
    }
);

watch(
    () => cacheLoading.value,
    (isLoading) => {
        loading.value = isLoading;
    }
);

// Local value for v-model
const localValue = ref(resolveMunicipalityValue(props.modelValue));

watch(
    [() => props.modelValue, () => municipalities.value],
    () => {
        localValue.value = resolveMunicipalityValue(props.modelValue);
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    emit('update:modelValue', val);
}, { deep: true });


onMounted(fetchMunicipalities);</script>

<template>
    <!-- Use MultiSelect when multiple is true -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="municipalities" filter :filterFields="['name']"
        optionLabel="name" :placeholder="customPlaceholder" :loading="loading" class="w-full" :maxSelectedLabels="3"
        :selectedItemsLabel="'{0} municipalities selected'" showSelectAll showClear
        :pt="{ overlay: { style: 'border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ slotProps.value.name }}
            </div>
        </template>
    </MultiSelect>

    <!-- Use Select when multiple is false -->
    <Select v-else v-model="localValue" :options="municipalities" filter :filterFields="['name']" autoFilterFocus
        showClear optionLabel="name" :placeholder="customPlaceholder" :loading="loading" class="w-full"
        :pt="{ overlay: { style: 'border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.name }}</div>
            </div>
            <span v-else>
                <div class="flex w-full" :class="customPlaceholderClass">{{
                    slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
    </Select>
</template>
