<script setup>
import { computed } from 'vue';
import { formatName, formatDate, formatGrantProvision, formatStatus, getReportStatus, isJpm, groupRecords, normalizeStatus } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

const grouped = computed(() => {
    const { groupBy, groupBySecondary, groupByTertiary } = props.options;
    return groupRecords(props.records, groupBy, groupBySecondary, groupByTertiary);
});

const showCol = (col) => {
    // Hide columns that are already filtered (single-value)
    const filterMap = {
        program: 'Program', school: 'School', course: 'Course',
        municipality: 'Municipality', year_level: 'Year Level',
    };
    return !props.filters[filterMap[col]];
};

const statusClass = (rec) => normalizeStatus(getReportStatus(rec));
const statusLabel = (rec) => formatStatus(getReportStatus(rec));
</script>

<template>
    <div>
        <!-- Header -->
        <div class="report-header">
            <div class="report-header-logos">
                <img src="/images/pgp-logo.png" alt="PGP Logo" class="report-logo" />
            </div>
            <div class="report-header-text">
                <h1>SCHOLARSHIP REPORT</h1>
                <h2>All Statuses — Detailed List</h2>
            </div>
            <div class="report-header-logos">
                <img src="/images/yakap-logo.png" alt="YAKAP Logo" class="report-logo" />
            </div>
        </div>

        <!-- Filters -->
        <div v-if="Object.keys(filters).length" class="filter-bar">
            <span v-for="(val, key) in filters" :key="key" class="filter-badge">
                <strong>{{ key }}:</strong> {{ val }}
            </span>
        </div>

        <div class="report-total-line">Total: <strong>{{ records.length }}</strong> record(s)</div>

        <!-- Ungrouped -->
        <template v-if="!grouped">
            <table class="report-table">
                <thead>
                    <tr>
                        <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th v-if="showCol('municipality')">Municipality</th>
                        <th>Status</th>
                        <th v-if="showCol('program')">Program</th>
                        <th v-if="showCol('school')">School</th>
                        <th v-if="showCol('course')">Course</th>
                        <th v-if="showCol('year_level')">Level</th>
                        <th v-if="options.includeGrantProvision">Grant</th>
                        <th class="nowrap">Date Filed</th>
                        <th v-if="options.includeRemarks">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(rec, idx) in records" :key="idx"
                        :class="{ 'jpm-row': options.enableJpmHighlighting && isJpm(rec) }">
                        <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                        <td class="nowrap">{{ formatName(rec) }}</td>
                        <td>{{ rec.contact_no || '—' }}</td>
                        <td v-if="showCol('municipality')">{{ rec.municipality || '—' }}</td>
                        <td><span :class="'status-badge status-' + statusClass(rec)">{{ statusLabel(rec) }}</span></td>
                        <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                        <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                        <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                        <td v-if="showCol('year_level')">{{ rec.year_level || '—' }}</td>
                        <td v-if="options.includeGrantProvision">{{ formatGrantProvision(rec.grant_provision) }}</td>
                        <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                        <td v-if="options.includeRemarks" v-safe-html="rec.remarks || ''"></td>
                    </tr>
                </tbody>
            </table>
            <div class="group-total">Total Records: {{ records.length }}</div>
        </template>

        <!-- Grouped -->
        <template v-else>
            <template v-for="group in grouped" :key="group.key">
                <div class="group-header">{{ group.key }} ({{ group.count }})</div>

                <!-- No sub-groups -->
                <template v-if="group.records">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th v-if="showCol('municipality')">Municipality</th>
                                <th>Status</th>
                                <th v-if="showCol('program')">Program</th>
                                <th v-if="showCol('school')">School</th>
                                <th v-if="showCol('course')">Course</th>
                                <th v-if="showCol('year_level')">Level</th>
                                <th v-if="options.includeGrantProvision">Grant</th>
                                <th class="nowrap">Date Filed</th>
                                <th v-if="options.includeRemarks">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(rec, idx) in group.records" :key="idx"
                                :class="{ 'jpm-row': options.enableJpmHighlighting && isJpm(rec) }">
                                <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                                <td class="nowrap">{{ formatName(rec) }}</td>
                                <td>{{ rec.contact_no || '—' }}</td>
                                <td v-if="showCol('municipality')">{{ rec.municipality || '—' }}</td>
                                <td><span :class="'status-badge status-' + statusClass(rec)">{{ statusLabel(rec)
                                        }}</span></td>
                                <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                                <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                                <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                                <td v-if="showCol('year_level')">{{ rec.year_level || '—' }}</td>
                                <td v-if="options.includeGrantProvision">{{ formatGrantProvision(rec.grant_provision) }}
                                </td>
                                <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                                <td v-if="options.includeRemarks" v-safe-html="rec.remarks || ''"></td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <!-- Sub-groups -->
                <template v-if="group.subGroups">
                    <template v-for="sub in group.subGroups" :key="sub.key">
                        <div class="sub-group-header">{{ sub.key }} ({{ sub.count }})</div>

                        <template v-if="sub.records">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th v-if="showCol('municipality')">Municipality</th>
                                        <th>Status</th>
                                        <th v-if="showCol('program')">Program</th>
                                        <th v-if="showCol('school')">School</th>
                                        <th v-if="showCol('course')">Course</th>
                                        <th v-if="showCol('year_level')">Level</th>
                                        <th v-if="options.includeGrantProvision">Grant</th>
                                        <th class="nowrap">Date Filed</th>
                                        <th v-if="options.includeRemarks">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(rec, idx) in sub.records" :key="idx"
                                        :class="{ 'jpm-row': options.enableJpmHighlighting && isJpm(rec) }">
                                        <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}</td>
                                        <td class="nowrap">{{ formatName(rec) }}</td>
                                        <td>{{ rec.contact_no || '—' }}</td>
                                        <td v-if="showCol('municipality')">{{ rec.municipality || '—' }}</td>
                                        <td><span :class="'status-badge status-' + statusClass(rec)">{{ statusLabel(rec)
                                                }}</span></td>
                                        <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                                        <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                                        <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                                        <td v-if="showCol('year_level')">{{ rec.year_level || '—' }}</td>
                                        <td v-if="options.includeGrantProvision">{{
                                            formatGrantProvision(rec.grant_provision) }}</td>
                                        <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                                        <td v-if="options.includeRemarks" v-safe-html="rec.remarks || ''"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>

                        <!-- Tertiary groups -->
                        <template v-if="sub.subGroups">
                            <template v-for="ter in sub.subGroups" :key="ter.key">
                                <div class="tertiary-group-header">{{ ter.key }} ({{ ter.count }})</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th v-if="options.showSequenceNumbers" class="text-center">#</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th v-if="showCol('program')">Program</th>
                                            <th v-if="showCol('school')">School</th>
                                            <th v-if="showCol('course')">Course</th>
                                            <th class="nowrap">Date Filed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(rec, idx) in ter.records" :key="idx">
                                            <td v-if="options.showSequenceNumbers" class="text-center">{{ idx + 1 }}
                                            </td>
                                            <td class="nowrap">{{ formatName(rec) }}</td>
                                            <td>{{ rec.contact_no || '—' }}</td>
                                            <td><span :class="'status-badge status-' + statusClass(rec)">{{
                                                    statusLabel(rec) }}</span>
                                            </td>
                                            <td v-if="showCol('program')">{{ rec.program_name || '—' }}</td>
                                            <td v-if="showCol('school')">{{ rec.school_name || '—' }}</td>
                                            <td v-if="showCol('course')">{{ rec.course_name || '—' }}</td>
                                            <td class="nowrap">{{ formatDate(rec.date_filed) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                        </template>
                    </template>
                </template>
            </template>
        </template>

        <!-- Footer -->
        <div class="report-footer">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total: {{ records.length }} records</span>
        </div>
    </div>
</template>
