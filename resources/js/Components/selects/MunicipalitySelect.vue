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
const localValue = ref(props.multiple ? (props.modelValue || []) : props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
}, { deep: true });

// Emit changes to parent
watch(localValue, (val) => {
    emit('update:modelValue', val);
});


// Watch for changes in municipalities data and update localValue
watch(
    () => municipalities.value,
    (newMunicipalities) => {
        if (localValue.value && newMunicipalities.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                localValue.value = localValue.value.map(val => {
                    if (typeof val == 'object' && val != null) {
                        // Try matching by ID first (most reliable)
                        if (val.id) {
                            return newMunicipalities.find(municipality => municipality.id == val.id) || val;
                        }
                        // Fall back to name matching
                        if (val.name) {
                            return newMunicipalities.find(municipality => municipality.name == val.name) || val;
                        }
                    }
                    // Handle string/number values
                    return newMunicipalities.find(municipality =>
                        municipality.name?.toLowerCase() == String(val).toLowerCase() ||
                        municipality.id == val
                    ) || val;
                });
            } else {
                let val = localValue.value;
                if (typeof val == 'object' && val !== null) {
                    // Try matching by ID first (most reliable)
                    if (val.id) {
                        const matchedMunicipality = newMunicipalities.find(municipality => municipality.id == val.id);
                        if (matchedMunicipality) {
                            localValue.value = matchedMunicipality;
                            return;
                        }
                    }
                    // Fall back to name matching
                    if (val.name) {
                        const matchedMunicipality = newMunicipalities.find(municipality => municipality.name == val.name);
                        if (matchedMunicipality) {
                            localValue.value = matchedMunicipality;
                            return;
                        }
                    }
                    // Keep the original value if no match found
                    localValue.value = val;
                } else {
                    // Handle string/number values
                    const selected = newMunicipalities.find(municipality =>
                        municipality.name?.toLowerCase() == String(val).toLowerCase() ||
                        municipality.id == val
                    );
                    if (selected) localValue.value = selected;
                }
            }
        }
    },
    { immediate: true }
);

onMounted(fetchMunicipalities);</script>

<template>
    <!-- Use MultiSelect when multiple is true -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="municipalities" filter :filterFields="['name']"
        optionLabel="name" :placeholder="customPlaceholder" :loading="loading" class="w-full" :maxSelectedLabels="3"
        :selectedItemsLabel="'{0} municipalities selected'" showSelectAll showClear>
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
        showClear optionLabel="name" :placeholder="customPlaceholder" :loading="loading" class="w-full">
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
