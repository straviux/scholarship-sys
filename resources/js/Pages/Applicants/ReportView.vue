<template>
    <div class="px-12 mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">Generated Report</h1>
        <div class="mb-4 flex gap-2">
            <Button label="Back" @click="goBack" />
            <Button label="Regenerate" @click="regenerate" severity="info" />
            <Button label="Save as PDF" @click="saveAsPdf" severity="secondary" />
        </div>
        <div v-if="loading" class="text-center py-8">
            <span>Loading report...</span>
        </div>
        <div v-else>
            <div v-if="reportType === 'list'">
                <h2 class="text-lg font-semibold mb-2">List of Results ({{ report.count }})</h2>
                <table class="min-w-full border text-sm">
                    <thead>
                        <tr>
                            <th class="border px-2 py-1">Name</th>
                            <th class="border px-2 py-1">Program</th>
                            <th class="border px-2 py-1">School</th>
                            <th class="border px-2 py-1">Course</th>
                            <th class="border px-2 py-1">Year Level</th>
                            <th class="border px-2 py-1">Date Filed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in report.data" :key="item.id">
                            <td class="border px-2 py-1">{{ item.first_name }} {{ item.last_name }}</td>
                            <td class="border px-2 py-1">{{ item.scholarship_grant[0]?.program?.name || '-' }}</td>
                            <td class="border px-2 py-1">{{ item.scholarship_grant[0]?.school?.name || '-' }}</td>
                            <td class="border px-2 py-1">{{ item.scholarship_grant[0]?.course?.name || '-' }}</td>
                            <td class="border px-2 py-1">{{ item.scholarship_grant[0]?.year_level || '-' }}</td>
                            <td class="border px-2 py-1">{{ item.scholarship_grant[0]?.date_filed ?
                                moment(item.scholarship_grant[0]?.date_filed).format('YYYY-MM-DD') : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="reportType === 'summary'">
                <h2 class="text-lg font-semibold mb-2">Summary</h2>
                <div v-for="(group, key) in summaryGroups" :key="key" class="mb-4">

                    <div v-if="group.label && group.data.length">
                        <h3 class="font-semibold capitalize">{{ group.label }}</h3>
                        <table class="min-w-full border text-sm">
                            <thead>
                                <tr>
                                    <th class="border px-2 py-1">{{ group.label }}</th>
                                    <th class="border px-2 py-1">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in group.data" :key="item.name">
                                    <td class="border px-2 py-1">{{ item.name }}</td>
                                    <td class="border px-2 py-1">{{ item.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else-if="!group.label || !group.data.length">
                        <span v-if="report.parameters && report.parameters[group.key.replace('by_', '')]">
                            Filtered by: <b>{{ report.parameters[group.key.replace('by_', '')] }}</b>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
function saveAsPdf() {
    const query = new URLSearchParams(params).toString();
    window.open(`/profiles/report/pdf?${query}`, '_blank');
}

import { ref, onMounted, toRefs } from 'vue';
import axios from 'axios';
import moment from 'moment';
const props = defineProps({
    params: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const loading = ref(true);
const report = ref({});
const reportType = ref(props.params.report_type || 'list');
const params = { ...props.params };

const summaryGroups = [
    { key: 'by_program', label: 'Program', data: [] },
    { key: 'by_school', label: 'School', data: [] },
    { key: 'by_course', label: 'Course', data: [] },
    { key: 'by_year_level', label: 'Year Level', data: [] },
];


function goBack() {
    emit('close');
}

function regenerate() {
    fetchReport();
}

function fetchReport() {
    loading.value = true;
    axios.get('/profiles/generate-report', { params })
        .then(res => {
            console.log(res.data);
            report.value = res.data;
            if (reportType.value === 'summary' && res.data.summary) {
                summaryGroups.forEach(group => {
                    group.data = [];
                    if (res.data.summary[group.key]) {
                        // If summary is an object of {name: count}, convert to array
                        if (Array.isArray(res.data.summary[group.key])) {
                            group.data = res.data.summary[group.key];
                        } else {
                            group.data = Object.entries(res.data.summary[group.key]).map(([name, count]) => ({ name, count }));
                        }
                    }
                });
            }
        })
        .finally(() => loading.value = false);
}

onMounted(fetchReport);
</script>

<style scoped>
.container {
    max-width: 900px;
}
</style>
