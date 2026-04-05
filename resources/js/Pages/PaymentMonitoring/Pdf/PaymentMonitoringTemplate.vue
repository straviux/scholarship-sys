<template>
    <!--
        Payment Monitoring Report  –  Landscape Long (13 × 8.5 in)
        Columns: # | Scholar | Program / Course | Acad Year | Term | OBR Type | Kind | Status | Amount | OBR No. | Date Obligated
    -->
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
        <div class="center" style="padding:8pt 0 6pt;">
            <p class="bold" style="font-size:13pt;">PAYMENT MONITORING REPORT</p>
            <p class="t-8" style="margin-top:2pt;">As of {{ today }}</p>
        </div>

        <!-- ACTIVE FILTERS BLOCK -->
        <div v-if="hasFilters" class="t-8"
            style="display:flex;flex-wrap:wrap;gap:8pt;margin-bottom:6pt;padding:5pt 6pt;background:#f8f8f8;border:0.5pt solid #ccc;border-radius:3pt;">
            <span v-if="filters.academicYear"><strong>Acad. Year:</strong> {{ filters.academicYear }}</span>
            <span v-if="filters.semester"><strong>Term:</strong> {{ filters.semester }}</span>
            <span v-if="filters.program"><strong>Program:</strong> {{ filters.program }}</span>
            <span v-if="filters.status && filters.status !== 'all'"><strong>Status:</strong> {{ filters.status }}</span>
            <span v-if="filters.school"><strong>School:</strong> {{ filters.school }}</span>
            <span v-if="filters.search"><strong>Search:</strong> "{{ filters.search }}"</span>
        </div>

        <!-- SUMMARY ROW -->
        <div class="t-8" style="display:flex;gap:16pt;margin-bottom:6pt;">
            <span>Total Scholars: <strong>{{ summary.scholarCount }}</strong></span>
            <span>Total Transactions: <strong>{{ summary.totalTransactions }}</strong></span>
            <span>Total Amount: <strong>₱{{ money(summary.totalAmount) }}</strong></span>
        </div>

        <!-- NO DATA -->
        <div v-if="!rows || !rows.length" class="center italic" style="padding:24pt;color:#888;">
            No transactions found for the selected filters.
        </div>

        <!-- REPORT TABLE -->
        <table v-else class="t-8" style="width:100%;border-collapse:collapse;">
            <colgroup>
                <col style="width:18pt" />
                <col style="width:130pt" />
                <col style="width:100pt" />
                <col style="width:70pt" />
                <col style="width:60pt" />
                <col style="width:90pt" />
                <col style="width:70pt" />
                <col style="width:70pt" />
                <col style="width:80pt" />
                <col style="width:90pt" />
                <col style="width:72pt" />
            </colgroup>
            <thead>
                <tr>
                    <th class="b-all center bold" style="padding:5pt;">#</th>
                    <th class="b-all center bold" style="padding:5pt;">Scholar</th>
                    <th class="b-all center bold" style="padding:5pt;">Program / Course</th>
                    <th class="b-all center bold" style="padding:5pt;">Acad. Year</th>
                    <th class="b-all center bold" style="padding:5pt;">Term</th>
                    <th class="b-all center bold" style="padding:5pt;">OBR Type</th>
                    <th class="b-all center bold" style="padding:5pt;">Kind</th>
                    <th class="b-all center bold" style="padding:5pt;">Status</th>
                    <th class="b-all right bold" style="padding:5pt;">Amount</th>
                    <th class="b-all center bold" style="padding:5pt;">OBR No.</th>
                    <th class="b-all center bold" style="padding:5pt;">Date Obligated</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(scholar, si) in rows" :key="scholar.profile_id">
                    <tr v-for="(tx, ti) in scholar.transactions" :key="ti"
                        :style="si % 2 === 0 ? 'background:#fff;' : 'background:#f7f7f7;'">
                        <!-- Row # — only on first transaction of scholar -->
                        <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">
                            <span v-if="ti === 0">{{ si + 1 }}</span>
                        </td>
                        <!-- Scholar name — only on first transaction -->
                        <td class="b-all bold" style="padding:3pt 5pt;vertical-align:top;">
                            <span v-if="ti === 0">{{ scholar.scholar_name }}</span>
                        </td>
                        <!-- Program / Course — only on first transaction -->
                        <td class="b-all" style="padding:3pt 5pt;vertical-align:top;font-size:7pt;">
                            <span v-if="ti === 0">
                                <span v-if="tx.program" class="bold">{{ tx.program }}</span>
                                <span v-if="tx.course"><br />{{ tx.course }}</span>
                            </span>
                        </td>
                        <!-- Per-transaction cells -->
                        <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">{{ tx.academic_year || '—'
                            }}</td>
                        <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">
                            {{ tx.term || '—' }}<span v-if="tx.year_level"
                                style="display:block;font-size:6.5pt;color:#666;">Yr {{ tx.year_level }}</span>
                        </td>
                        <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ tx.obr_type || '—' }}</td>
                        <td class="b-all" style="padding:3pt 5pt;vertical-align:top;text-transform:capitalize;">{{
                            tx.disbursement_type || '—' }}</td>
                        <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">
                            <span :style="statusStyle(tx.transaction_status)">{{ tx.transaction_status || 'No OBR'
                                }}</span>
                        </td>
                        <td class="b-all right nowrap" style="padding:3pt 5pt;vertical-align:top;">
                            <span v-if="tx.amount">₱{{ money(tx.amount) }}</span>
                            <span v-else>—</span>
                        </td>
                        <td class="b-all"
                            style="padding:3pt 5pt;vertical-align:top;font-size:7pt;font-family:monospace;">{{ tx.obr_no
                            || '—' }}</td>
                        <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">{{ tx.date_obligated ?
                            fmtDate(tx.date_obligated) : '—' }}</td>
                    </tr>
                    <!-- Scholar sub-total row if > 1 transaction -->
                    <tr v-if="scholar.transactions.length > 1"
                        :style="si % 2 === 0 ? 'background:#e8f4e8;' : 'background:#dff0df;'">
                        <td colspan="8" class="b-all right bold t-8" style="padding:3pt 8pt;">
                            Sub-total for {{ scholar.scholar_name }}
                        </td>
                        <td class="b-all right bold nowrap t-8" style="padding:3pt 8pt;">
                            ₱{{ money(scholar.totalAmount) }}
                        </td>
                        <td colspan="2" class="b-all" style="padding:3pt 5pt;"></td>
                    </tr>
                </template>

                <!-- Grand Total -->
                <tr style="border-top:1.5pt solid #000;">
                    <td colspan="8" class="b-all right bold t-9" style="padding:5pt 8pt;">GRAND TOTAL</td>
                    <td class="b-all right bold nowrap t-9" style="padding:5pt 8pt;">₱{{ money(summary.totalAmount) }}
                    </td>
                    <td colspan="2" class="b-all" style="padding:5pt 8pt;"></td>
                </tr>
            </tbody>
        </table>

        <!-- SIGNATURE BLOCK -->
        <div style="margin-top:24pt;display:flex;gap:32pt;">
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">Prepared by
                </div>
            </div>
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">Noted by</div>
            </div>
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">Approved by
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    summary: {
        type: Object,
        default: () => ({ scholarCount: 0, totalTransactions: 0, totalAmount: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    today: { type: String, default: '' },
});

const hasFilters = computed(() =>
    props.filters.academicYear || props.filters.semester || props.filters.program ||
    (props.filters.status && props.filters.status !== 'all') ||
    props.filters.school || props.filters.search
);

function money(val) {
    if (val === null || val === undefined || val === '') return '0.00';
    return parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function statusStyle(status) {
    const map = {
        'Paid': 'color:#27ae60;font-weight:bold;',
        'Claimed': 'color:#2980b9;font-weight:bold;',
        'On Process': 'color:#e67e22;font-weight:bold;',
        'Suspend': 'color:#f39c12;',
        'Denied': 'color:#c0392b;font-weight:bold;',
        'LOA': 'color:#3498db;',
        'Irregular': 'color:#e74c3c;',
        'Transferred': 'color:#8e44ad;',
    };
    return map[status] ?? 'color:#888;';
}
</script>
