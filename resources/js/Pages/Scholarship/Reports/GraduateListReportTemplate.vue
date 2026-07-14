<script setup>
import { computed } from 'vue';
import moment from 'moment';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    reportTitle: { type: String, default: '' },
    showRemarks: { type: Boolean, default: true },
    blankRemarks: { type: Boolean, default: false },
    generatedAt: { type: String, default: '' },
    preparedBy: { type: String, default: '' },
    preparedByPosition: { type: String, default: '' },
    approvedBy: { type: String, default: '' },
    approvedByPosition: { type: String, default: '' },
});

const TH = 'border:1px solid #000;padding:4px 4px;font-weight:700;font-size:9px;line-height:1.2;text-transform:uppercase;text-align:center;background:#f5f5f5;vertical-align:middle;';
const TD = 'border:1px solid #000;padding:4px 6px;font-size:10px;line-height:1.3;vertical-align:middle;';

const showSignatoryBlock = computed(() => Boolean(props.preparedBy) || Boolean(props.approvedBy));

const filterEntries = computed(() => Object.entries(props.filters || {}));
</script>

<template>
    <div style="padding:6pt 12pt 10pt;">
        <!-- ── HEADER ── -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="YAKAP Logo"
                style="position:absolute;right:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <p style="font-weight:700;font-size:11pt;">Republic of the Philippines</p>
            <p style="font-weight:700;font-size:11pt;">Provincial Government of Palawan</p>
            <p style="font-size:10pt;">Yakap Sa Edukasyon</p>
            <p style="font-size:10pt;">Scholarship Program</p>
            <p style="font-size:10pt;">Puerto Princesa City, Palawan</p>
        </div>

        <!-- ── TITLE ── -->
        <div style="text-align:center;padding:6pt 0 4pt;">
            <p style="font-weight:700;font-size:14pt;">{{ reportTitle || 'GRADUATE LIST' }}</p>
        </div>

        <!-- ── FILTERS ── -->
        <div v-if="filterEntries.length" style="margin-bottom:8pt;font-size:9pt;">
            <span v-for="(entry, idx) in filterEntries" :key="entry[0]">
                <strong>{{ entry[0] }}:</strong> {{ entry[1] }}<span v-if="idx < filterEntries.length - 1"> &nbsp;|&nbsp; </span>
            </span>
        </div>

        <!-- ── EMPTY STATE ── -->
        <div v-if="records.length === 0" style="text-align:center;padding:24pt;color:#888;font-size:10pt;font-style:italic;">
            No graduated scholars match the current selection.
        </div>

        <!-- ── TABLE ── -->
        <table v-else style="width:100%;border-collapse:collapse;font-size:9pt;table-layout:fixed;">
            <colgroup>
                <col style="width:5%;" />
                <col style="width:37%;" />
                <col style="width:18%;" />
                <col style="width:18%;" />
                <col style="width:10%;" />
                <col v-if="showRemarks" style="width:12%;" />
            </colgroup>
            <thead>
                <tr>
                    <th :style="TH">#</th>
                    <th :style="TH">Name</th>
                    <th :style="TH">School</th>
                    <th :style="TH">Course</th>
                    <th :style="TH">Year Graduated</th>
                    <th v-if="showRemarks" :style="TH">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(record, index) in records" :key="index">
                    <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                    <td :style="TD + 'font-weight:600;text-transform:uppercase;'">{{ record.name || '—' }}</td>
                    <td :style="TD">{{ record.school || '—' }}</td>
                    <td :style="TD">{{ record.course || '—' }}</td>
                    <td :style="TD + 'text-align:center;'">{{ record.year_graduated || '—' }}</td>
                    <td v-if="showRemarks" :style="TD">{{ blankRemarks ? '' : (record.remarks || '—') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- ── SIGNATORY BLOCK ── -->
        <div v-if="showSignatoryBlock"
            style="margin-top:32pt;display:flex;justify-content:space-between;font-size:9pt;page-break-inside:avoid;">
            <!-- Prepared By -->
            <div v-if="preparedBy" style="flex:1;max-width:55%;margin-left:70pt;">
                <div style="font-weight:700;">Prepared by:</div>
                <div style="margin-top:36pt;text-align:center;width:200px;">
                    <div style="font-weight:700;border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{ preparedBy }}
                    </div>
                    <div v-if="preparedByPosition" style="margin-top:4pt;">{{ preparedByPosition }}</div>
                </div>
            </div>
            <!-- Approved By -->
            <div v-if="approvedBy" style="flex:1;max-width:35%;margin-left:auto;">
                <div style="font-weight:700;text-align:left;">Approved by:</div>
                <div style="margin-top:36pt;text-align:center;width:200px;">
                    <div style="font-weight:700;border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{ approvedBy }}
                    </div>
                    <div v-if="approvedByPosition" style="margin-top:4pt;">{{ approvedByPosition }}</div>
                </div>
                <div style="margin-top:36pt;text-align:center;width:200px;border-top:1px solid #000;">
                    Date
                </div>
            </div>
        </div>

        <!-- ── FOOTER ── -->
        <div style="margin-top:16pt;display:flex;justify-content:space-between;font-size:7pt;color:#888;border-top:0.5pt solid #ddd;padding-top:4pt;">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total Records: {{ records.length }}</span>
        </div>
    </div>
</template>
