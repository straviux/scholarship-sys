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

// Local value for v-model
const localValue = ref(resolveCourseValue(props.modelValue));

watch(
    [() => props.modelValue, () => courseOptions.value],
    () => {
        localValue.value = resolveCourseValue(props.modelValue);
    },
    { immediate: true, deep: true }
);

watch(localValue, (val) => {
    emit('update:modelValue', val);
}, { deep: true });

// Watch for changes in data and update courses

watch(
    () => props.scholarshipProgramId,
    (newProgramId, oldProgramId) => {
        const requestId = ++latestCourseRequestId;

        if (newProgramId === '' || newProgramId === null || newProgramId === undefined) {
            loading.value = false;
            courses.value = [];
            return;
        }

        loading.value = true;

        axios.get(route('courses-api.findbyprogram'), {
            params: { program_id: newProgramId }
        }).then(response => {
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
        :loading="loading" optionLabel="name" :placeholder="customPlaceholder" class="w-full"
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
