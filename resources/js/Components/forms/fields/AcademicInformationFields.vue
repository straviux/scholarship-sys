<template>
    <div>
        <h4 v-if="showHeader" class="text-base font-semibold text-gray-700 mb-4">
            <slot name="header">Academic Information</slot>
        </h4>
        <div class="space-y-4">
            <!-- Program and School Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <ProgramSelect :modelValue="program" @update:modelValue="$emit('update:program', $event)"
                        placeholder="&nbsp;" inputId="program" />
                    <label class="text-sm" for="program">Program</label>
                </FloatLabel>

                <FloatLabel>
                    <CourseSelect :modelValue="course" @update:modelValue="$emit('update:course', $event)"
                        placeholder="&nbsp;" :scholarship-program-id="program?.id || ''" inputId="course" />
                    <label class="text-sm" for="course">Course</label>
                </FloatLabel>

                <FloatLabel>
                    <SchoolSelect :modelValue="school" @update:modelValue="$emit('update:school', $event)"
                        placeholder="&nbsp;" inputId="school" />
                    <label class="text-sm" for="school">School</label>
                </FloatLabel>
            </div>

            <!-- Year Level, Term, and Academic Year Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <YearLevelSelect :modelValue="year_level" placeholder="&nbsp;"
                        @update:modelValue="$emit('update:year_level', $event)" inputId="year_level" />
                    <label class="text-sm" for="year_level">Year/Grade Level</label>
                </FloatLabel>

                <FloatLabel>
                    <TermSelect :modelValue="term" @update:modelValue="$emit('update:term', $event)"
                        placeholder="&nbsp;" inputId="term" />
                    <label class="text-sm" for="term">Term</label>
                </FloatLabel>

                <FloatLabel>
                    <AcademicYearSelect :modelValue="academic_year"
                        @update:modelValue="$emit('update:academic_year', $event)" inputId="academic_year"
                        placeholder="&nbsp;" />
                    <label class="text-sm" for="academic_year">Academic Year</label>
                </FloatLabel>
            </div>

            <!-- YAKAP Category and Location Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <Select :key="yakap_category" :modelValue="yakap_category"
                        @update:modelValue="$emit('update:yakap_category', $event)" :options="yakapCategoryOptions"
                        optionLabel="label" optionValue="value" placeholder="&nbsp;" inputId="yakap_category"
                        class="w-full" />
                    <label class="text-sm" for="yakap_category">YAKAP Category</label>
                </FloatLabel>

                <FloatLabel v-if="showYakapLocationField" :key="'location-' + yakap_category">
                    <InputText :modelValue="yakap_location" @update:modelValue="$emit('update:yakap_location', $event)"
                        :placeholder="yakap_category === 'yakap-school' ? 'School name' : 'Field location'"
                        inputId="yakap_location" class="w-full" />
                    <label class="text-sm" for="yakap_location">Location</label>
                </FloatLabel>
            </div>

            <!-- Remarks Field -->
            <div class="grid grid-cols-1 gap-3 mt-10">
                <FloatLabel>
                    <Textarea :modelValue="remarks" @update:modelValue="$emit('update:remarks', $event)"
                        inputId="remarks" variant="filled" rows="3" fluid autoResize />
                    <label class="text-sm" for="remarks">Remarks (Optional)</label>
                </FloatLabel>
            </div>
        </div>
    </div>
</template>

<script setup>
import FloatLabel from 'primevue/floatlabel';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import { computed, watch } from 'vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' }
];

const props = defineProps({
    program: [Object, String],
    school: [Object, String],
    course: [Object, String],
    year_level: [String, Object],
    term: [String, Object],
    academic_year: [String, Object],
    remarks: String,
    yakap_category: {
        type: String,
        default: 'yakap-capitol'
    },
    yakap_location: {
        type: String,
        default: ''
    },
    showHeader: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits([
    'update:program',
    'update:school',
    'update:course',
    'update:year_level',
    'update:term',
    'update:academic_year',
    'update:remarks',
    'update:yakap_category',
    'update:yakap_location'
]);

// Computed property to check if location field should be shown
const showYakapLocationField = computed(() => {
    console.log('Computing showYakapLocationField with yakap_category:', props.yakap_category);
    return props.yakap_category !== 'yakap-capitol';
});

// Watch yakap_category for debugging
watch(() => props.yakap_category, (newVal, oldVal) => {
    console.log('yakap_category changed from', oldVal, 'to', newVal);
});
</script>

<style>
.p-inputtext.p-variant-filled,
.p-textarea.p-variant-filled,
.p-select.p-variant-filled .p-select-label,
.p-datepicker.p-variant-filled .p-datepicker-input {
    background-color: #ffffff !important;
}
</style>
