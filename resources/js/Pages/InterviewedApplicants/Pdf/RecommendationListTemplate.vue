<template>
    <InterviewedApplicantsTemplate
        :records="records"
        report-type="list"
        group-by="none"
        :today="today"
        :prepared-by="preparedBy"
        :prepared-by-position="preparedByPosition"
        :prepared-by-office="preparedByOffice"
        :approved-by="approvedBy"
        :approved-by-position="approvedByPosition"
        :budget-program="budgetProgram"
        :budget-allocation="budgetAllocation"
        :highlight-jpm-members="highlightJpmMembers"
        :include-endorsed-by="includeEndorsedBy"
        :report-title="resolvedReportTitle"
        :list-number="listNumber"
        :include-interview-columns="false" />
</template>

<script setup>
import { computed } from 'vue';

import InterviewedApplicantsTemplate from './InterviewedApplicantsTemplate.vue';

const props = defineProps({
    records: {
        type: Array,
        default: () => [],
    },
    today: {
        type: String,
        default: '',
    },
    preparedBy: {
        type: String,
        default: '',
    },
    preparedByPosition: {
        type: String,
        default: '',
    },
    preparedByOffice: {
        type: String,
        default: '',
    },
    approvedBy: {
        type: String,
        default: '',
    },
    approvedByPosition: {
        type: String,
        default: '',
    },
    budgetProgram: {
        type: String,
        default: '',
    },
    budgetAllocation: {
        type: Object,
        default: null,
    },
    highlightJpmMembers: {
        type: Boolean,
        default: false,
    },
    includeEndorsedBy: {
        type: Boolean,
        default: false,
    },
    reportTitle: {
        type: String,
        default: 'RECOMMENDATION LIST FOR APPROVAL',
    },
    listNumber: {
        type: String,
        default: '',
    },
});

const resolvedReportTitle = computed(() => {
    return props.reportTitle?.trim() || 'RECOMMENDATION LIST FOR APPROVAL';
});
</script>