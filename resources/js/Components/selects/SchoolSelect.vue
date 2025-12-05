<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useApi } from '@/composable/api';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
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
});

const emit = defineEmits(['update:modelValue']);
const { data, error, fetchData } = useApi(route('schools.getactivelist'));
const schools = ref([]);
const loading = ref(false);

// Fetch data immediately on creation
fetchData();

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
                    if (typeof val == 'object' && val != null && val.shortname) {
                        return schools.value.find(school => school.shortname == val.shortname) || val;
                    }
                    return schools.value.find(school =>
                        school.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        school.name?.toLowerCase() == String(val).toLowerCase()
                    ) || val;
                });
            } else {
                // Single value: find the matching school object
                let val = localValue.value;
                if (typeof val == 'object' && val != null && val.shortname) {
                    localValue.value = schools.value.find(school => school.shortname == val.shortname) || val;
                } else {
                    const selected = schools.value.find(school =>
                        school.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        school.name?.toLowerCase() == String(val).toLowerCase()
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
    <!-- MultiSelect for multiple selection -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="schoolOptions" filter autoFilterFocus showClear
        optionLabel="name" :placeholder="customPlaceholder" class="w-full" :filterFields="['name', 'shortname']"
        :maxSelectedLabels="3" :selectedItemsLabel="'{0} schools selected'" showSelectAll>
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
        :placeholder="customPlaceholder" class="w-full" :filterFields="['name', 'shortname']">
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