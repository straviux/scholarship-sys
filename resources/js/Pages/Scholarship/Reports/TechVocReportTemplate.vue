<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { formatName, getPriorityReportSortBucket } from './report-helpers';
import TechVocReportTable from './TechVocReportTable.vue';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

const sortBy = computed(() => props.options?.sortBy ?? 'default');

function compareByName(left, right) {
    return formatName(left).localeCompare(formatName(right), undefined, {
        sensitivity: 'base',
        numeric: true,
    });
}

const sortedRecords = computed(() => {
    const records = [...props.records];
    return records.sort((left, right) => compareByName(left, right));
});

const reportTitle = computed(() => props.options?.reportTitle?.trim() || 'Tech-Voc Approval List');
const asOfLabel = computed(() => {
    if (!props.generatedAt) return moment().format('MMMM D, YYYY');
    return props.generatedAt.split(' — ')[0] || props.generatedAt;
});

const dateRangeText = computed(() => {
    const dateFrom = props.filters?.['Date From'];
    const dateTo = props.filters?.['Date To'];
    if (dateFrom && dateTo) return `${dateFrom} — ${dateTo}`;
    if (dateFrom) return `From: ${dateFrom}`;
    if (dateTo) return `To: ${dateTo}`;
    return '';
});

const signatoryName = computed(() => props.options?.signatoryName?.trim() || '');
const signatoryTitle = computed(() => props.options?.signatoryTitle?.trim() || '');
const preparedBy = computed(() => props.options?.preparedBy?.trim() || '');
const preparedByTitle = computed(() => props.options?.preparedByTitle?.trim() || '');
const defaultAmount = computed(() => props.options?.defaultAmount ?? null);
</script>

<template>
    <div class="report-page">
        <div>
            <!-- Header -->
            <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
                <img src="/images/pgp-logo.svg" alt="PGP Logo"
                    style="position:absolute;left:30%;top:50%;transform:translateY(-50%);width:64pt;height:auto;" />
                <img src="/images/yakap-logo.svg" alt="YAKAP Logo"
                    style="position:absolute;right:30%;top:50%;transform:translateY(-50%);width:64pt;height:auto;" />
                <p style="font-weight:700;font-size:11pt;">Republic of the Philippines</p>
                <p style="font-weight:700;font-size:11pt;">Provincial Government of Palawan</p>
                <p style="font-size:10pt;">Yakap sa Edukasyon</p>
                <p style="font-size:10pt;">Scholarship Program</p>
                <p style="font-size:10pt;">Puerto Princesa City, Palawan</p>
            </div>

            <!-- Title -->
            <div style="text-align:center;padding:8pt 0 4pt;">
                <p style="font-weight:700;font-size:13pt;">{{ reportTitle }}</p>
                <p style="font-size:9pt;">As of {{ asOfLabel }}</p>
                <p v-if="dateRangeText" style="font-size:9pt;">{{ dateRangeText }}</p>
            </div>

            <!-- Empty state -->
            <div v-if="records.length === 0" style="text-align:center;padding:24pt;color:#888;font-size:10pt;font-style:italic;">
                No records match the selected filters.
            </div>

            <!-- Table -->
            <template v-else>
                <TechVocReportTable :records="sortedRecords" :filters="filters" :options="options" :default-amount="defaultAmount" />
            </template>
        </div>

        <!-- Signatories -->
        <div v-if="preparedBy || signatoryName" style="margin-top:36pt;page-break-inside:avoid;">
            <div :style="{ display: 'flex', justifyContent: preparedBy && signatoryName ? 'space-between' : 'center', gap: '32pt' }">
                <div v-if="preparedBy" style="flex:0 1 240pt;text-align:center;">
                    <p style="font-size:8pt;margin-bottom:32pt;">Prepared by:</p>
                    <div style="border-top:0.75pt solid #000;padding-top:3pt;">
                        <p style="font-weight:700;font-size:9pt;text-transform:uppercase;letter-spacing:0.3pt;">{{ preparedBy }}</p>
                        <p v-if="preparedByTitle" style="font-size:8pt;color:#444;">{{ preparedByTitle }}</p>
                    </div>
                </div>
                <div v-if="signatoryName" style="flex:0 1 240pt;text-align:center;">
                    <p style="font-size:8pt;margin-bottom:32pt;">Noted by:</p>
                    <div style="border-top:0.75pt solid #000;padding-top:3pt;">
                        <p style="font-weight:700;font-size:9pt;text-transform:uppercase;letter-spacing:0.3pt;">{{ signatoryName }}</p>
                        <p v-if="signatoryTitle" style="font-size:8pt;color:#444;">{{ signatoryTitle }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-footer">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total Records: {{ records.length }}</span>
        </div>
    </div>
</template>
