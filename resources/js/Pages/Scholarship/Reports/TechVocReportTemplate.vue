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
const preparedByOffice = computed(() => props.options?.preparedByOffice?.trim() || '');
const useInterviewedSignatories = computed(() => props.options?.useInterviewedSignatories === true);
const showPreparedBy = computed(() => Boolean(preparedBy.value));
const showNotedBy = computed(() => Boolean(signatoryName.value));
const showSignatoryBlock = computed(() => showPreparedBy.value || showNotedBy.value);
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

        <!-- ── Signatory Block ──────────────────────────────────── -->
        <!-- Interviewed-Applicants Style: Prepared by (left) | Approved by (right) -->
        <div v-if="showSignatoryBlock && useInterviewedSignatories"
            style="margin-top:28pt;display:flex;justify-content:space-between;font-size:8pt;page-break-inside:avoid;">
            <div style="flex:1;max-width:60%;margin-left:70pt;">
                <div style="font-weight:700;">Prepared by:</div>
                <div style="margin-top:40pt;text-align:center;width:200px;">
                    <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{ preparedBy }}
                    </div>
                    <div v-if="preparedByTitle" style="margin-top:4pt;">{{ preparedByTitle }}</div>
                    <div v-if="preparedByOffice">{{ preparedByOffice }}</div>
                </div>
            </div>
            <div style="flex:1;max-width:35%;margin-left:auto;">
                <div style="font-weight:700;text-align:left;">Approved by:</div>
                <div style="margin-top:40pt;text-align:center;width:200px;">
                    <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{ signatoryName }}
                    </div>
                    <div v-if="signatoryTitle" style="margin-top:4pt;">{{ signatoryTitle }}</div>
                </div>

                <div style="margin-top:40pt;text-align:center;width:200px;border-top:1px solid #000;">
                    Date
                </div>
            </div>
        </div>

        <!-- Default Style: Prepared by | Noted by -->
        <div v-else-if="showSignatoryBlock && !useInterviewedSignatories" style="margin-top:36pt;page-break-inside:avoid;">
            <div :style="{ display: 'flex', justifyContent: showPreparedBy && showNotedBy ? 'space-between' : 'center', gap: '32pt' }">
                <div v-if="showPreparedBy" style="flex:0 1 240pt;text-align:center;">
                    <p style="font-size:8pt;margin-bottom:32pt;">Prepared by:</p>
                    <div style="border-top:0.75pt solid #000;padding-top:3pt;">
                        <p style="font-weight:700;font-size:9pt;text-transform:uppercase;letter-spacing:0.3pt;">{{ preparedBy }}</p>
                        <p v-if="preparedByTitle" style="font-size:8pt;color:#444;">{{ preparedByTitle }}</p>
                        <p v-if="preparedByOffice" style="font-size:8pt;color:#444;">{{ preparedByOffice }}</p>
                    </div>
                </div>
                <div v-if="showNotedBy" style="flex:0 1 240pt;text-align:center;">
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
