<script setup>
import { ref, watch, watchEffect, computed, onMounted } from 'vue';
import { useApi } from '@/composable/api';

const props = defineProps({
    municipalityId: {
        type: [String, Number],
        default: null,
    },
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
        default: 'Select Barangay'
    },
    showNullOption: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const barangays = ref([]);
const loading = ref(false);
const lastMunicipalityId = ref(null);

// Helper function to match a value with barangays
const findMatchingBarangay = (value, barangayList) => {
    if (!value || !barangayList.length) return null;

    // If value is an object, try matching by id or name
    if (typeof value === 'object' && value !== null) {
        return barangayList.find(b => b.id === value.id || b.name === value.name) || null;
    }

    // If value is string/number, match by name (case-insensitive)
    const searchValue = String(value).toLowerCase();
    return barangayList.find(b => b.name?.toLowerCase() === searchValue) || null;
};

// Computed property to include null option when needed
const barangayOptions = computed(() => {
    const options = [...(barangays.value || [])];
    if (props.showNullOption) {
        options.unshift({
            id: null,
            name: 'No Barangay',
            isNullOption: true
        });
    }
    return options;
});

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

// Watch for changes in municipalityId and update barangays
watch(
    () => props.municipalityId,
    (newMunicipalityId, oldMunicipalityId) => {
        // Skip if municipalityId hasn't actually changed (prevents duplicate API calls)
        if (newMunicipalityId === lastMunicipalityId.value) {
            return;
        }

        lastMunicipalityId.value = newMunicipalityId;

        // If no municipalityId provided, clear barangays
        if (!newMunicipalityId) {
            barangays.value = [];
            return;
        }

        loading.value = true;
        axios.get(route('api.municipalities.barangays', newMunicipalityId))
            .then(response => {
                barangays.value = response.data;
            })
            .catch(error => {
                console.error('Error loading barangays:', error);
                barangays.value = [];
            })
            .finally(() => {
                loading.value = false;
            });

        // Only reset selection when:
        // 1. Municipality actually changes (not initial load) ? oldMunicipalityId !== undefined
        // 2. There's no existing valid selection ? !hasExistingSelection
        // 3. Municipality changes to a specific value ? newMunicipalityId !== '' && !== null
        const hasExistingSelection = props.modelValue && (
            typeof props.modelValue === 'object'
                ? props.modelValue.id
                : props.modelValue
        );

        if (newMunicipalityId !== '' && newMunicipalityId !== null && oldMunicipalityId !== undefined && !hasExistingSelection) {
            localValue.value = props.multiple ? [] : null;
        }
    },
    { immediate: true }
);
// Watch for changes in barangays and match with current value
watch(
    () => barangays.value,
    (newBarangays) => {
        if (!localValue.value || !newBarangays.length) return;

        if (props.multiple && Array.isArray(localValue.value)) {
            // For multiple selection, map each value to its matching barangay
            localValue.value = localValue.value
                .map(val => findMatchingBarangay(val, newBarangays))
                .filter(Boolean); // Remove null values
        } else {
            // For single selection, find the matching barangay
            const matched = findMatchingBarangay(localValue.value, newBarangays);
            if (matched) {
                localValue.value = matched;
            }
        }
    },
    { immediate: true }
);
onMounted(() => {
    console.log('BarangaySelect mounted with props:', props);
});
</script>

<template>
    <!-- Use MultiSelect when multiple is true -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="barangayOptions" filter :filterFields="['name']"
        optionLabel="name" :placeholder="customPlaceholder" class="w-full" :maxSelectedLabels="3"
        :selectedItemsLabel="'{0} barangays selected'" showSelectAll showClear>
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
    <Select v-else v-model="localValue" :options="barangayOptions" filter :filterFields="['name']" autoFilterFocus
        showClear optionLabel="name" :placeholder="customPlaceholder" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.name }}</div>
            </div>
            <span v-else>
                <div class="flex items-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
    </Select>
</template>
