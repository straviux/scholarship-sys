<script setup>
import { ref, watch, watchEffect } from 'vue';
import { useApi } from '@/composable/api';
const props = defineProps({
    scholarshipProgramId: {
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
        default: 'Select Course'
    },
});

const emit = defineEmits(['update:modelValue']);
// const { data, error, fetchData } = useApi(route('courses-api.list', { scholarship_program_id: props.scholarshipProgramId }));
const courses = ref([]);
const loading = ref(false);

// Local value for v-model
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
    // console.log('CourseSelect localValue changed:', localValue.value);
    emit('update:modelValue', val);
});

// Watch for changes in data and update courses

watch(
    () => props.scholarshipProgramId,
    (newProgramId) => {
        // console.log('scholarshipProgramId changed:', newProgramId);
        axios.get(route('courses-api.findbyprogram'), {
            params: { program_id: newProgramId }
        }).then(response => {
            courses.value = response.data;
        });
        // localValue.value = "";
        // fetchData({ scholarship_program_id: newProgramId });
    },
    { immediate: true }
);

watchEffect(() => {
    // console.log('courses', localValue.value);
    if (localValue.value && courses.value.length) {
        const selected = courses.value.find(course => course.shortname == localValue.value || course.name == localValue.value);
        if (selected) localValue.value = selected;
    }


});
// onMounted(fetchData);
</script>

<template>
    <Select v-model="localValue" :options="courses" filter autoFilterFocus showClear optionLabel="name"
        :placeholder="customPlaceholder" class="w-full">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.shortname }}</div>
            </div>
            <span v-else>
                <div class="flex items-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.shortname }}</div>
            </div>
        </template>
    </Select>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>