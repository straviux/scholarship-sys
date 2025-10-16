<script setup>
import { ref, watch, watchEffect, computed } from 'vue';
import { useApi } from '@/composable/api';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
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
    showNullOption: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
// const { data, error, fetchData } = useApi(route('courses-api.list', { scholarship_program_id: props.scholarshipProgramId }));
const courses = ref([]);
const loading = ref(false);

// Computed property to include null option when needed
const courseOptions = computed(() => {
    const options = [...(courses.value || [])];
    if (props.showNullOption) {
        options.unshift({
            id: null,
            name: 'No Course',
            shortname: 'NO COURSE',
            isNullOption: true
        });
    }
    return options;
});

// Local value for v-model
const localValue = ref(props.multiple ? (props.modelValue || []) : props.modelValue);

// Sync localValue with parent prop
watch(() => props.modelValue, (val) => {
    // console.log('CourseSelect modelValue changed:', val);
    localValue.value = val;
}, { deep: true });

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
    (newProgramId, oldProgramId) => {
        // console.log('scholarshipProgramId changed:', newProgramId);
        axios.get(route('courses-api.findbyprogram'), {
            params: { program_id: newProgramId }
        }).then(response => {
            courses.value = response.data;
            // console.log('Courses loaded:', courses.value.length, 'courses');
        }).catch(error => {
            console.error('Error loading courses:', error);
            courses.value = [];
        });
        // Only reset selection when:
        // 1. Program actually changes (not initial load)
        // 2. There's no existing valid selection (modelValue)
        // 3. Program changes to a specific value (not empty)
        const hasExistingSelection = props.modelValue && (
            typeof props.modelValue === 'object'
                ? props.modelValue.id
                : props.modelValue
        );

        if (newProgramId !== '' && newProgramId !== null && oldProgramId !== undefined && !hasExistingSelection) {
            if (props.multiple) {
                localValue.value = [];
            } else {
                localValue.value = null;
            }
        }
    },
    { immediate: true }
);

watch(
    () => courses.value,
    (newCourses) => {
        if (localValue.value && newCourses.length) {
            if (props.multiple && Array.isArray(localValue.value)) {
                localValue.value = localValue.value.map(val => {
                    if (typeof val == 'object' && val != null) {
                        // Try matching by ID first (most reliable)
                        if (val.id) {
                            return newCourses.find(course => course.id == val.id) || val;
                        }
                        // Fall back to shortname matching
                        if (val.shortname) {
                            return newCourses.find(course => course.shortname == val.shortname) || val;
                        }
                    }
                    // Handle string values
                    return newCourses.find(course =>
                        course.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        course.name?.toLowerCase() == String(val).toLowerCase()
                    ) || val;
                });
            } else {
                let val = localValue.value;
                if (typeof val == 'object' && val !== null) {
                    // Try matching by ID first (most reliable)
                    if (val.id) {
                        const matchedCourse = newCourses.find(course => course.id == val.id);
                        if (matchedCourse) {
                            localValue.value = matchedCourse;
                            return;
                        }
                    }
                    // Fall back to shortname matching
                    if (val.shortname) {
                        const matchedCourse = newCourses.find(course => course.shortname == val.shortname);
                        if (matchedCourse) {
                            localValue.value = matchedCourse;
                            return;
                        }
                    }
                    // Keep the original value if no match found
                    localValue.value = val;
                } else {
                    // Handle string values
                    const selected = newCourses.find(course =>
                        course.shortname?.toLowerCase() == String(val).toLowerCase() ||
                        course.name?.toLowerCase() == String(val).toLowerCase()
                    );
                    if (selected) localValue.value = selected;
                }
            }
        }
    },
    { immediate: true }
);
// onMounted(fetchData);
</script>

<template>
    <!-- Use MultiSelect when multiple is true -->
    <MultiSelect v-if="multiple" v-model="localValue" :options="courseOptions" filter
        :filterFields="['name', 'shortname']" optionLabel="name" :placeholder="customPlaceholder" class="w-full"
        :maxSelectedLabels="3" :selectedItemsLabel="'{0} courses selected'" showSelectAll showClear>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.shortname }}</div>
            </div>
        </template>
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ slotProps.value.shortname }}
            </div>
        </template>
    </MultiSelect>

    <!-- Use Select when multiple is false -->
    <Select v-else v-model="localValue" :options="courseOptions" filter :filterFields="['name', 'shortname']"
        autoFilterFocus showClear optionLabel="name" :placeholder="customPlaceholder" class="w-full">
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