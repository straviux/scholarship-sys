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
        :budget-allocation="budgetAllocation"
        :report-title="resolvedReportTitle"
        :include-interview-columns="includeInterviewColumns" />
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
    budgetAllocation: {
        type: Object,
        default: null,
    },
    reportTitle: {
        type: String,
        default: 'RECOMMENDATION LIST FOR APPROVAL',
    },
    includeInterviewColumns: {
        type: Boolean,
        default: true,
    },
});

const resolvedReportTitle = computed(() => {
    return props.reportTitle?.trim() || 'RECOMMENDATION LIST FOR APPROVAL';
});
</script>