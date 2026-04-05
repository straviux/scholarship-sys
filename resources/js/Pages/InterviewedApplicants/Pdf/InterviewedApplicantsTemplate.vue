<template>
    <div>
        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p class="bold t-11">Republic of the Philippines</p>
            <p class="bold t-11">Provincial Government of Palawan</p>
            <p class="t-10">Akbay sa Mag-aaral Yaman ng kinabukasan</p>
            <p class="t-10">(Programang Pang-Edukasyon para sa Palaweño)</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <!-- REPORT TITLE -->
        <div class="center" style="padding:8pt 0 4pt;">
            <p class="bold" style="font-size:13pt;">
                {{ reportType === 'summary' ? 'INTERVIEW SUMMARY REPORT' : 'INTERVIEWED APPLICANTS REPORT' }}
            </p>
            <p class="t-9" style="margin-top:3pt;">As of {{ today }}</p>
        </div>

        <!-- ACTIVE FILTER SUMMARY -->
        <div v-if="hasFilters"
            style="margin:6pt 0;padding:5pt 8pt;border:0.5pt solid #ccc;border-radius:4pt;font-size:8pt;">
            <span class="bold" style="margin-right:6pt;">Filters:</span>
            <span v-if="filterLabels.recommendation" style="margin-right:10pt;">
                Recommendation: <strong>{{ filterLabels.recommendation }}</strong>
            </span>
            <span v-if="filterLabels.program" style="margin-right:10pt;">
                Program: <strong>{{ filterLabels.program }}</strong>
            </span>
            <span v-if="filterLabels.school" style="margin-right:10pt;">
                School: <strong>{{ filterLabels.school }}</strong>
            </span>
            <span v-if="filterLabels.course" style="margin-right:10pt;">
                Course: <strong>{{ filterLabels.course }}</strong>
            </span>
            <span v-if="filterLabels.date_from || filterLabels.date_to">
                Date: <strong>{{ filterLabels.date_from || '…' }} — {{ filterLabels.date_to || '…' }}</strong>
            </span>
        </div>

        <!-- NO RECORDS -->
        <div v-if="records.length === 0" class="center italic" style="padding:24pt;color:#888;font-size:10pt;">
            No records match the selected filters.
        </div>

        <!-- ═══════════════ LIST VIEW ═══════════════ -->
        <template v-else-if="reportType === 'list'">

            <!-- GROUPED LIST -->
            <template v-if="groupBy !== 'none'">
                <div v-for="(group, groupName) in groupedData" :key="groupName" style="margin-bottom:14pt;">
                    <!-- Group header -->
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                border-bottom:1pt solid #000;padding:3pt 0;margin-bottom:4pt;">
                        <span class="bold" style="font-size:10pt;">{{ groupName }}</span>
                        <span style="font-size:8pt;color:#555;">{{ group.length }} record{{ group.length !== 1 ? 's' :
                            '' }}</span>
                    </div>
                    <!-- Group table -->
                    <table style="width:100%;border-collapse:collapse;font-size:8pt;">
                        <thead>
                            <tr>
                                <th :style="TH" style="width:18pt;">#</th>
                                <th :style="TH">Name</th>
                                <th :style="TH">Program</th>
                                <th :style="TH">Course</th>
                                <th :style="TH">Recommendation</th>
                                <th v-if="includeAssessment" :style="TH">Academic</th>
                                <th v-if="includeAssessment" :style="TH">Financial</th>
                                <th v-if="includeAssessment" :style="TH">Comm. Skills</th>
                                <th :style="TH">Interview Date</th>
                                <th :style="TH">Interviewer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(rec, idx) in group" :key="rec.id">
                                <td :style="TD + 'text-align:center;'">{{ idx + 1 }}</td>
                                <td :style="TD + 'font-weight:600;'">
                                    {{ rec.profile.last_name }}, {{ rec.profile.first_name }}
                                </td>
                                <td :style="TD">{{ rec.program?.shortname || '—' }}</td>
                                <td :style="TD">{{ rec.course?.shortname || '—' }}</td>
                                <td :style="TD">
                                    <span :style="recStyle(rec.recommendation)">{{ recLabel(rec.recommendation)
                                        }}</span>
                                </td>
                                <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                    {{ cap(rec.academic_potential) }}
                                </td>
                                <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                    {{ cap(rec.financial_need_level) }}
                                </td>
                                <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                    {{ cap(rec.communication_skills) }}
                                </td>
                                <td :style="TD + 'text-align:center;white-space:nowrap;'">
                                    {{ fmtDate(rec.interviewed_at) }}
                                </td>
                                <td :style="TD">{{ rec.interviewer?.name || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- FLAT LIST (no grouping) -->
            <template v-else>
                <table style="width:100%;border-collapse:collapse;font-size:8pt;margin-top:6pt;">
                    <thead>
                        <tr>
                            <th :style="TH" style="width:18pt;">#</th>
                            <th :style="TH">Name</th>
                            <th :style="TH">Program</th>
                            <th :style="TH">Course</th>
                            <th :style="TH">Recommendation</th>
                            <th v-if="includeAssessment" :style="TH">Academic</th>
                            <th v-if="includeAssessment" :style="TH">Financial</th>
                            <th v-if="includeAssessment" :style="TH">Comm. Skills</th>
                            <th :style="TH">Interview Date</th>
                            <th :style="TH">Interviewer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(rec, idx) in records" :key="rec.id">
                            <td :style="TD + 'text-align:center;'">{{ idx + 1 }}</td>
                            <td :style="TD + 'font-weight:600;'">
                                {{ rec.profile.last_name }}, {{ rec.profile.first_name }}
                            </td>
                            <td :style="TD">{{ rec.program?.shortname || '—' }}</td>
                            <td :style="TD">{{ rec.course?.shortname || '—' }}</td>
                            <td :style="TD">
                                <span :style="recStyle(rec.recommendation)">{{ recLabel(rec.recommendation) }}</span>
                            </td>
                            <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                {{ cap(rec.academic_potential) }}
                            </td>
                            <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                {{ cap(rec.financial_need_level) }}
                            </td>
                            <td v-if="includeAssessment" :style="TD + 'text-align:center;'">
                                {{ cap(rec.communication_skills) }}
                            </td>
                            <td :style="TD + 'text-align:center;white-space:nowrap;'">
                                {{ fmtDate(rec.interviewed_at) }}
                            </td>
                            <td :style="TD">{{ rec.interviewer?.name || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <!-- TOTAL ROW (list view) -->
            <div style="margin-top:8pt;text-align:right;font-size:8pt;font-weight:700;">
                Total Records: {{ records.length }}
            </div>

        </template>

        <!-- ═══════════════ SUMMARY VIEW ═══════════════ -->
        <template v-else>
            <div style="display:flex;gap:16pt;margin-top:10pt;">

                <!-- By Recommendation -->
                <div style="flex:1;border:0.5pt solid #ccc;">
                    <div :style="SUMMARY_HDR">By Recommendation</div>
                    <table style="width:100%;border-collapse:collapse;font-size:9pt;">
                        <tbody>
                            <tr v-for="(count, rec) in summaryByRecommendation" :key="rec">
                                <td :style="SUMMARY_TD">
                                    <span :style="recStyle(rec)">{{ recLabel(rec) }}</span>
                                </td>
                                <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- By Program -->
                <div style="flex:1;border:0.5pt solid #ccc;">
                    <div :style="SUMMARY_HDR">By Program</div>
                    <table style="width:100%;border-collapse:collapse;font-size:9pt;">
                        <tbody>
                            <tr v-for="(count, prog) in summaryByProgram" :key="prog">
                                <td :style="SUMMARY_TD">{{ prog }}</td>
                                <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- TOTAL BANNER -->
            <div
                style="margin-top:12pt;padding:8pt;border:0.5pt solid #ccc;text-align:center;font-size:11pt;font-weight:700;">
                Total Interviewed Applicants: {{ records.length }}
            </div>
        </template>

        <!-- PREPARED BY FOOTER -->
        <div style="margin-top:24pt;font-size:8pt;">
            <span>Prepared by: </span>
            <span class="bold">{{ preparedBy }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import moment from 'moment';

const props = defineProps({
    records: { type: Array, default: () => [] },
    reportType: { type: String, default: 'list' },
    groupBy: { type: String, default: 'none' },
    includeAssessment: { type: Boolean, default: true },
    filterLabels: { type: Object, default: () => ({}) },
    today: { type: String, default: '' },
    preparedBy: { type: String, default: '' },
});

// ─── Style constants (all inline) ───────────────────────────────────────────
const TH = 'border:1px solid #000;padding:5px 4px;font-weight:700;font-size:9px;text-transform:uppercase;text-align:center;background:#f0f0f0;';
const TD = 'border:1px solid #000;padding:4px 5px;font-size:9px;vertical-align:middle;';
const SUMMARY_HDR = 'background:#f0f0f0;font-weight:700;font-size:9pt;padding:5pt 8pt;text-transform:uppercase;border-bottom:0.5pt solid #ccc;';
const SUMMARY_TD = 'padding:5pt 8pt;border-top:0.5pt solid #e5e5e5;';

// ─── Helpers ─────────────────────────────────────────────────────────────────
const REC_LABELS = {
    recommended: 'Recommended for Approval',
    further_evaluation: 'For Further Evaluation',
    not_recommended: 'Not Recommended',
};

const REC_COLORS = {
    recommended: '#16a34a',
    further_evaluation: '#d97706',
    not_recommended: '#dc2626',
};

function recLabel(val) {
    return REC_LABELS[val] || val || '—';
}

function recStyle(val) {
    const color = REC_COLORS[val] || '#000';
    return `font-weight:700;color:${color};`;
}

function cap(str) {
    if (!str) return '—';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function fmtDate(d) {
    return d ? moment(d).format('MMM DD, YYYY') : '—';
}

// ─── Computed ────────────────────────────────────────────────────────────────
const hasFilters = computed(() => {
    const fl = props.filterLabels || {};
    return !!(fl.recommendation || fl.program || fl.school || fl.course || fl.date_from || fl.date_to);
});

const groupedData = computed(() => {
    const groups = {};
    for (const rec of props.records) {
        let key;
        if (props.groupBy === 'program') key = rec.program?.shortname || 'N/A';
        else if (props.groupBy === 'course') key = rec.course?.shortname || 'N/A';
        else if (props.groupBy === 'recommendation') key = recLabel(rec.recommendation);
        else if (props.groupBy === 'interviewer') key = rec.interviewer?.name || 'N/A';
        else key = 'All';
        if (!groups[key]) groups[key] = [];
        groups[key].push(rec);
    }
    return groups;
});

const summaryByRecommendation = computed(() => {
    const map = {};
    for (const r of props.records) {
        const key = r.recommendation || 'unknown';
        map[key] = (map[key] || 0) + 1;
    }
    return map;
});

const summaryByProgram = computed(() => {
    const map = {};
    for (const r of props.records) {
        const key = r.program?.shortname || 'N/A';
        map[key] = (map[key] || 0) + 1;
    }
    return map;
});
</script>
