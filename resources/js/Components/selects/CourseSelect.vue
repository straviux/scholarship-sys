<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { selectAnimation } from '@/utils/animations';

const props = defineProps({
    scholarshipProgramId: {
        type: [String, Number],
        default: null,
    },
    loadAllWhenNoProgram: {
        type: Boolean,
        default: false,
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
    iosCompact: {
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
let latestCourseRequestId = 0;

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

const normalizeCourseToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return String(value).trim().toLowerCase();
};

const resolveSingleCourse = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        return courseOptions.value.find((course) => course.id == value.id)
            || courseOptions.value.find((course) => normalizeCourseToken(course.shortname) === normalizeCourseToken(value.shortname))
            || courseOptions.value.find((course) => normalizeCourseToken(course.name) === normalizeCourseToken(value.name))
            || value;
    }

    const normalized = normalizeCourseToken(value);
    if (!normalized) {
        return null;
    }

    return courseOptions.value.find((course) => {
        return normalizeCourseToken(course.shortname) === normalized
            || normalizeCourseToken(course.name) === normalized
            || String(course.id) === String(value);
    }) || value;
};

const resolveCourseValue = (value) => {
    if (props.multiple && Array.isArray(value)) {
        return value.map((entry) => resolveSingleCourse(entry)).filter(Boolean);
    }

    return resolveSingleCourse(value);
};

const isSameCourseSelection = (left, right) => {
    if (Array.isArray(left) || Array.isArray(right)) {
        if (!Array.isArray(left) || !Array.isArray(right)) {
            return false;
        }

        if (left.length !== right.length) {
            return false;
        }

        return left.every((entry, index) => entry === right[index]);
    }

    return left === right;
};

const getCoursePrimaryLabel = (course) => {
    if (!course) {
        return '';
    }

    if (typeof course === 'string' || typeof course === 'number') {
        return String(course);
    }

    return course.shortname || course.name || course.field_of_study || '';
};

const getCourseSecondaryLabel = (course) => {
    if (!course || typeof course !== 'object') {
        return '';
    }

    if (!course.shortname) {
        return course.field_of_study || '';
    }

    if (course.field_of_study) {
        return course.field_of_study;
    }

    return course.name && course.name !== course.shortname ? course.name : '';
};

const getCourseOptionTitle = (course) => {
    if (!course || course.isNullOption) {
        return '';
    }

    return [course.shortname, course.name, course.field_of_study ? `(${course.field_of_study})` : null]
        .filter(Boolean)
        .join(' — ')
        .replace(' — (', ' (');
};

// Local value for v-model
const localValue = ref(resolveCourseValue(props.modelValue));

watch(
    [() => props.modelValue, () => courseOptions.value],
    () => {
        const resolvedValue = resolveCourseValue(props.modelValue);

        if (!isSameCourseSelection(localValue.value, resolvedValue)) {
            localValue.value = resolvedValue;
        }
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    if (!isSameCourseSelection(val, props.modelValue)) {
        emit('update:modelValue', val);
    }
}, { deep: true });

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'course-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'course-select-root--compact', style: 'min-height: 2.25rem;' },
        labelContainer: { style: 'padding: 0.4375rem 0.75rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});

// Watch for changes in data and update courses

watch(
    () => props.scholarshipProgramId,
    (newProgramId, oldProgramId) => {
        const requestId = ++latestCourseRequestId;

        const shouldLoadAllCourses = props.loadAllWhenNoProgram
            && (newProgramId === '' || newProgramId === null || newProgramId === undefined);

        if (!shouldLoadAllCourses && (newProgramId === '' || newProgramId === null || newProgramId === undefined)) {
            loading.value = false;
            courses.value = [];
            return;
        }

        loading.value = true;

        axios.get(
            shouldLoadAllCourses
                ? route('courses-api.list')
                : route('courses-api.findbyprogram'),
            shouldLoadAllCourses
                ? undefined
                : {
                    params: { program_id: newProgramId }
                }
        ).then(response => {
            if (requestId !== latestCourseRequestId) {
                return;
            }

            courses.value = Array.isArray(response.data) ? response.data : [];
        }).catch(error => {
            if (requestId !== latestCourseRequestId) {
                return;
            }

            console.error('Error loading courses:', error);
            courses.value = [];
        }).finally(() => {
            if (requestId === latestCourseRequestId) {
                loading.value = false;
            }
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

        if (!shouldLoadAllCourses && newProgramId !== '' && newProgramId !== null && oldProgramId !== undefined && !hasExistingSelection) {
            if (props.multiple) {
                localValue.value = [];
            } else {
                localValue.value = null;
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
        :selectedItemsLabel="'{0} courses selected'" :loading="loading" showSelectAll showClear
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #option="slotProps">
            <div class="uppercase" :title="getCourseOptionTitle(slotProps.option)">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <div class="text-[12px] font-bold leading-snug">{{ getCoursePrimaryLabel(slotProps.option) }}</div>
                    <div class="text-[10px] text-gray-600 leading-snug"
                        v-if="slotProps.option.name && slotProps.option.name !== getCoursePrimaryLabel(slotProps.option)">
                        {{ slotProps.option.name }}</div>
                    <div v-if="slotProps.option.field_of_study" class="text-[10px] text-gray-400 leading-snug">{{
                        slotProps.option.field_of_study }}</div>
                </div>
            </div>
        </template>
        <template #chip="slotProps">
            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase mr-1">
                {{ getCoursePrimaryLabel(slotProps.value) }}
            </div>
        </template>
    </MultiSelect>

    <!-- Use Select when multiple is false -->
    <Select ref="select" v-else v-model="localValue" :options="courseOptions" filter @show="onSelectShow"
        @hide="onSelectHide" :filterFields="['name', 'shortname', 'field_of_study']" autoFilterFocus showClear
        :loading="loading" optionLabel="name" :placeholder="customPlaceholder" class="w-full"
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="uppercase truncate">
                {{ getCoursePrimaryLabel(slotProps.value) }}<span v-if="getCourseSecondaryLabel(slotProps.value)"
                    class="text-gray-500 font-normal text-[11px]"> — {{ getCourseSecondaryLabel(slotProps.value)
                    }}</span>
            </div>
            <span v-else>{{ slotProps.placeholder }}</span>
        </template>
        <template #option="slotProps">
            <div class="uppercase" :title="getCourseOptionTitle(slotProps.option)">
                <div v-if="slotProps.option.isNullOption">
                    <span class="text-[12px]">{{ slotProps.option.name }}</span>
                </div>
                <div v-else>
                    <div class="text-[12px] font-bold leading-snug">{{ getCoursePrimaryLabel(slotProps.option) }}</div>
                    <div class="text-[10px] text-gray-600 leading-snug"
                        v-if="slotProps.option.name && slotProps.option.name !== getCoursePrimaryLabel(slotProps.option)">
                        {{ slotProps.option.name }}</div>
                    <div v-if="slotProps.option.field_of_study" class="text-[10px] text-gray-400 leading-snug">{{
                        slotProps.option.field_of_study }}</div>
                </div>
            </div>
        </template>
    </Select>
</template>

<style>
.course-select-overlay {
    max-width: 400px;
    border-radius: 12px;
}

.course-select-root--compact {
    border-radius: 0.875rem;
}
</style>
