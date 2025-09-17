<script setup>
import { ref, watch, onMounted } from 'vue';
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
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('schools.getactivelist'));
const schools = ref([]);
const loading = ref(false);

// Local value for v-modelroute('scholarshipschools.getactivelist')
const localValue = ref(props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    localValue.value = val;

});

// Sync localValue with parent prop
// watch(() => props.preselect, (val) => {
//     localValue.value = val;
// });
// Emit changes to parent
watch(localValue, (val) => {
    emit('update:modelValue', val);
});

// Watch for changes in data and update schools
watch(
    () => data.value,
    (newData) => {
        schools.value = newData || [];
        // If localValue is set, find and set the matching school object(s)
        if (localValue.value && schools.value.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                // Map each value in localValue to the corresponding school object
                localValue.value = localValue.value.map(val => {
                    if (typeof val === 'object' && val !== null && val.shortname) {
                        return schools.value.find(school => school.shortname === val.shortname) || val;
                    }
                    return schools.value.find(school =>
                        school.shortname?.toLowerCase() === String(val).toLowerCase() ||
                        school.name?.toLowerCase() === String(val).toLowerCase()
                    ) || val;
                });
            } else {
                // Single value: find the matching school object
                let val = localValue.value;
                if (typeof val === 'object' && val !== null && val.shortname) {
                    localValue.value = schools.value.find(school => school.shortname === val.shortname) || val;
                } else {
                    const selected = schools.value.find(school =>
                        school.shortname?.toLowerCase() === String(val).toLowerCase() ||
                        school.name?.toLowerCase() === String(val).toLowerCase()
                    );
                    if (selected) localValue.value = selected;
                }
            }
        }
    },
    { immediate: true }
);



onMounted(fetchData);
</script>

<template>
    <Select v-model="localValue" :options="schools" filter autoFilterFocus showClear optionLabel="name"
        :placeholder="customPlaceholder" class="w-full" :filterFields="['name', 'shortname']">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.shortname }}</div>
            </div>
            <span v-else>
                <div class="flex itesm-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.shortname }}</div>
            </div>
        </template>
    </Select>
</template>