<script setup>
import { computed } from 'vue';
import { formatName, formatDate, isJpm, groupRecords } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

const grouped = computed(() => groupRecords(props.records, props.options.groupBy, props.options.groupBySecondary, props.options.groupByTertiary));
const showCol = (col) => !props.filters[{ program: 'Program', school: 'School', course: 'Course', municipality: 'Municipality', year_level: 'Year Level' }[col]];
</script>

<template>
    <div>
        <div class="report-header">
            <div class="report-header-logos">
                <img src="/images/pgp-logo.png" alt="PGP Logo" class="report-logo" />
            </div>
            <div class="report-header-text">
                <h1>DENIED APPLICANTS</h1>
                <h2>Detailed List</h2>
            </div>
            <div class="report-header-logos">
                <img src="/images/yakap-logo.png" alt="YAKAP Logo" class="report-logo" />
            </div>
        </div>

        <div v-if="Object.keys(filters).length" class="filter-bar">
            <span v-for="(val, key) in filters" :key="key" class="filter-badge">
                <strong>{{ key }}:</strong> {{ val }}
            </span>
        </div>
        <div class="report-total-line">Total: <strong>{{ records.length }}</strong> record(s)</div>

        <template v-if="!grouped">
            <table class="report-table">
                <thead>
                    <tr>
                        <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th v-if="showCol('municipality')">Municipality</th>
                        <th v-if="showCol('program')">Program</th>
                        <th v-if="showCol('school')">School</th>
                        <th v-if="showCol('course')">Course</th>
                        <th v-if="showCol('year_level')">Year Level</th>
                        <th class="nowrap">Date Filed</th>
                        <th>Reason / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(rec, idx) in records" :key="idx"
                        :class="{ 'jpm-row': options.enableJpmHighlighting && isJpm(rec) }">
                        <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                        <td class="nowrap">{{ formatName(rec) }}</td>
                        <td>{{ rec.contact_no || '—' }}</td>
                        <td v-if="showCol('municipality')">{{ rec.municipality || '—' }}</td>
                        <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                        <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                        <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                        <td v-if="showCol('year_level')">{{ rec.year_level || '—' }}</td>
                        <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                        <td v-safe-html="rec.decline_reason || rec.remarks || '—'"></td>
                    </tr>
                </tbody>
            </table>
            <div class="group-total">Total Denied: {{ records.length }}</div>
        </template>

        <template v-else>
            <template v-for="group in grouped" :key="group.key">
                <div class="group-header">{{ group.key }} ({{ group.count }})</div>
                <template v-if="group.records">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th v-if="showCol('program')">Program</th>
                                <th v-if="showCol('school')">School</th>
                                <th v-if="showCol('course')">Course</th>
                                <th v-if="showCol('year_level')">Year Level</th>
                                <th class="nowrap">Date Filed</th>
                                <th>Reason / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(rec, idx) in group.records" :key="idx">
                                <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                                <td class="nowrap">{{ formatName(rec) }}</td>
                                <td>{{ rec.contact_no || '—' }}</td>
                                <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                                <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                                <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                                <td v-if="showCol('year_level')">{{ rec.year_level || '—' }}</td>
                                <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                                <td v-safe-html="rec.decline_reason || rec.remarks || '—'"></td>
                            </tr>
                        </tbody>
                    </table>
                </template>
                <template v-if="group.subGroups">
                    <template v-for="sub in group.subGroups" :key="sub.key">
                        <div class="sub-group-header">{{ sub.key }} ({{ sub.count }})</div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                                    <th>Name</th>
                                    <th v-if="showCol('program')">Program</th>
                                    <th v-if="showCol('school')">School</th>
                                    <th class="nowrap">Date Filed</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(rec, idx) in (sub.records || [])" :key="idx">
                                    <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                                    <td class="nowrap">{{ formatName(rec) }}</td>
                                    <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                                    <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                                    <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                                    <td v-safe-html="rec.decline_reason || rec.remarks || '—'"></td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </template>
            </template>
        </template>

        <div class="report-footer">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total: {{ records.length }} denied applicants</span>
        </div>
    </div>
</template>
