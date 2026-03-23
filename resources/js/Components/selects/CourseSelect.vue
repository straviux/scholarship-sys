<script setup>
import { ref, watch, watchEffect, computed } from 'vue';
import { useApi } from '@/composable/api';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { selectAnimation } from '@/utils/animations';

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
const courses = ref([]);
const loading = ref(false);
const multiSelect = ref(null);
const select = ref(null);
const { animate } = useGSAPAnimation();

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

const onSelectShow = () => {
    const overlay = multiSelect.value?.overlayViewChild?.el || select.value?.overlayViewChild?.el;
    if (overlay) {
        animate(overlay, selectAnimation.open());
    }
};

const onSelectHide = () => {
    const overlay = multiSelect.value?.overlayViewChild?.el || select.value?.overlayViewChild?.el;
    if (overlay) {
        animate(overlay, selectAnimation.close());
    }
};
</script>

<template>
    <!-- Use MultiSelect when multiple is true -->
    <MultiSelect ref="multiSelect" v-if="multiple" v-model="localValue" :options="courseOptions" filter
        @show="onSelectShow" @hide="onSelectHide" :filterFields="['name', 'shortname', 'field_of_study']"
        optionLabel="name" :placeholder="customPlaceholder" class="w-full" :maxSelectedLabels="3"
        :selectedItemsLabel="'{0} courses selected'" showSelectAll showClear
        :pt="{ overlay: { style: 'max-width: 400px; border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
        <template #option="slotProps">
            <div class="uppercase"
                :title="slotProps.option.isNullOption ? '' : `${slotProps.option.shortname} — ${slotProps.option.name}${slotProps.option.field_of_study ? ' (' + slotProps.option.field_of_study + ')' : ''}`">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <div class="text-[12px] font-bold leading-snug">{{ slotProps.option.shortname }}</div>
                    <div class="text-[10px] text-gray-600 leading-snug">{{ slotProps.option.name }}</div>
                    <div v-if="slotProps.option.field_of_study" class="text-[10px] text-gray-400 leading-snug">{{
                        slotProps.option.field_of_study }}</div>
                </div>
            </div>
        </template>
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ slotProps.value.shortname }}
            </div>
        </template>
    </MultiSelect>

    <!-- Use Select when multiple is false -->
    <Select ref="select" v-else v-model="localValue" :options="courseOptions" filter @show="onSelectShow"
        @hide="onSelectHide" :filterFields="['name', 'shortname', 'field_of_study']" autoFilterFocus showClear
        optionLabel="name" :placeholder="customPlaceholder" class="w-full"
        :pt="{ overlay: { style: 'max-width: 400px; border-radius: 12px; overflow: hidden' }, pcFilter: { root: { class: '!rounded-lg !border-gray-300' } } }">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="uppercase truncate">
                {{ slotProps.value.shortname }}<span v-if="slotProps.value.field_of_study"
                    class="text-gray-500 font-normal text-[11px]"> — {{ slotProps.value.field_of_study }}</span>
            </div>
            <span v-else>{{ slotProps.placeholder }}</span>
        </template>
        <template #option="slotProps">
            <div class="uppercase"
                :title="slotProps.option.isNullOption ? '' : `${slotProps.option.shortname} — ${slotProps.option.name}${slotProps.option.field_of_study ? ' (' + slotProps.option.field_of_study + ')' : ''}`">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <div class="text-[12px] font-bold leading-snug">{{ slotProps.option.shortname }}</div>
                    <div class="text-[10px] text-gray-600 leading-snug">{{ slotProps.option.name }}</div>
                    <div v-if="slotProps.option.field_of_study" class="text-[10px] text-gray-400 leading-snug">{{
                        slotProps.option.field_of_study }}</div>
                </div>
            </div>
        </template>
    </Select>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
