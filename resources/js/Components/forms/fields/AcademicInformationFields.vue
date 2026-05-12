<template>
    <div>
        <h4 v-if="showHeader" class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">
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
                    <label class="text-sm" for="year_level">{{ yearLevelLabel }}</label>
                </FloatLabel>

                <FloatLabel>
                    <TermSelect :modelValue="term" @update:modelValue="$emit('update:term', $event)"
                        placeholder="&nbsp;" inputId="term" />
                    <label class="text-sm" for="term">{{ termLabel }}</label>
                </FloatLabel>

                <FloatLabel>
                    <AcademicYearSelect :modelValue="academic_year"
                        @update:modelValue="$emit('update:academic_year', $event)" inputId="academic_year"
                        placeholder="&nbsp;" />
                    <label class="text-sm" for="academic_year">Academic Year</label>
                </FloatLabel>
            </div>

            <div v-if="showTechVocFields && isTechVocProgram" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-10">
                <FloatLabel>
                    <InputNumber :modelValue="no_of_hours" @update:modelValue="$emit('update:no_of_hours', $event)"
                        inputId="no_of_hours" :useGrouping="false" :min="1" class="w-full" />
                    <label class="text-sm" for="no_of_hours">No. of Hours</label>
                </FloatLabel>

                <FloatLabel>
                    <InputNumber :modelValue="no_of_days" @update:modelValue="$emit('update:no_of_days', $event)"
                        inputId="no_of_days" :useGrouping="false" :min="1" class="w-full" />
                    <label class="text-sm" for="no_of_days">No. of Days</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker :modelValue="start_date" @update:modelValue="$emit('update:start_date', $event)"
                        inputId="start_date" variant="filled" placeholder="mm/dd/yyyy" showIcon fluid
                        iconDisplay="input" :manualInput="true" />
                    <label class="text-sm" for="start_date">Start Date</label>
                </FloatLabel>

                <FloatLabel>
                    <DatePicker :modelValue="end_date" @update:modelValue="$emit('update:end_date', $event)"
                        inputId="end_date" variant="filled" placeholder="mm/dd/yyyy" showIcon fluid
                        iconDisplay="input" :manualInput="true" />
                    <label class="text-sm" for="end_date">End Date</label>
                </FloatLabel>
            </div>

            <!-- Remarks Field -->
            <div class="grid grid-cols-1 gap-3 mt-10">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Remarks (Optional)</label>
                <Editor :modelValue="remarks" @update:modelValue="$emit('update:remarks', $event)"
                    editorStyle="height: 120px">
                    <template #toolbar>
                        <span class="ql-formats">
                            <button class="ql-bold"></button>
                            <button class="ql-italic"></button>
                            <button class="ql-underline"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-list" value="ordered"></button>
                            <button class="ql-list" value="bullet"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-clean"></button>
                        </span>
                    </template>
                </Editor>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

const props = defineProps({
    program: [Object, String],
    school: [Object, String],
    course: [Object, String],
    year_level: [String, Object],
    term: [String, Object],
    academic_year: [String, Object],
    no_of_hours: Number,
    no_of_days: Number,
    start_date: [String, Date, Object],
    end_date: [String, Date, Object],
    remarks: String,
    yakap_category: String,
    yakap_location: [String, Object],
    isTechVocProgram: {
        type: Boolean,
        default: false,
    },
    showTechVocFields: {
        type: Boolean,
        default: false,
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
    'update:no_of_hours',
    'update:no_of_days',
    'update:start_date',
    'update:end_date',
    'update:remarks',
    'update:yakap_category',
    'update:yakap_location'
]);

const yearLevelLabel = computed(() => props.isTechVocProgram ? 'Year/Grade Level (Optional)' : 'Year/Grade Level');
const termLabel = computed(() => props.isTechVocProgram ? 'Term (Optional)' : 'Term');

</script>
