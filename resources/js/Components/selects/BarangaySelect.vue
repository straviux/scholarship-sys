<script setup>
import { ref, watch, computed } from 'vue';
import Select from 'primevue/select';
import municipalitiesData from '@/Data/municipalities.json';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    municipality: {
        type: [String, Number, Object],
        default: null,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    customPlaceholder: {
        type: String,
        default: 'Select Barangay'
    },
});

const emit = defineEmits(['update:modelValue']);
const localValue = ref(props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;
});

// Emit changes to parent
watch(localValue, (val) => {
    emit('update:modelValue', val);
});

// Get barangays based on selected municipality
const barangays = computed(() => {
    console.log('BarangaySelect - municipality prop received:', props.municipality);
    console.log('BarangaySelect - municipality type:', typeof props.municipality);

    if (!props.municipality) {
        return [];
    }

    // Find the municipality object
    let municipalityObj = null;

    if (typeof props.municipality === 'object' && props.municipality !== null) {
        console.log('Municipality is an object:', props.municipality);
        // If municipality is already the full object from MunicipalitySelect, use it directly
        if (props.municipality.barangays) {
            municipalityObj = props.municipality;
        } else {
            // Otherwise, find by name or id
            municipalityObj = municipalitiesData.municipalities.find(m =>
                m.name?.toLowerCase() === props.municipality.name?.toLowerCase() ||
                m.id === props.municipality.id
            );
        }
    } else if (typeof props.municipality === 'string') {
        // If municipality is a string, find by name
        municipalityObj = municipalitiesData.municipalities.find(m =>
            m.name?.toLowerCase() === props.municipality.toLowerCase()
        );
    } else if (typeof props.municipality === 'number') {
        // If municipality is a number, find by id
        municipalityObj = municipalitiesData.municipalities.find(m =>
            m.id === props.municipality
        );
    }

    console.log('Found municipality object:', municipalityObj);

    // Return barangays as objects with name property for consistency
    if (municipalityObj && municipalityObj.barangays) {
        const barangayList = municipalityObj.barangays.map(barangay => ({
            name: barangay
        }));
        console.log('Barangay list:', barangayList);
        return barangayList;
    }

    return [];
});

// Computed property to check if component should be disabled
const isDisabled = computed(() => {
    console.log('BarangaySelect - municipality prop:', props.municipality);
    console.log('BarangaySelect - isDisabled:', !props.municipality);
    // Only disable if there's no municipality selected
    return !props.municipality;
});

// Watch municipality changes and clear barangay selection if municipality changes
watch(() => props.municipality, (newMunicipality, oldMunicipality) => {
    // Clear selection when municipality changes
    if (newMunicipality !== oldMunicipality) {
        localValue.value = null;
        emit('update:modelValue', null);
    }
});
</script>

<template>
    <Select v-model="localValue" :options="barangays" filter autoFilterFocus showClear optionLabel="name"
        :placeholder="customPlaceholder" class="w-full">
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
        <template #empty>
            <div class="p-3 text-sm text-gray-500">
                {{ municipality ? 'No barangays found' : 'Please select a municipality first' }}
            </div>
        </template>
    </Select>
</template>
