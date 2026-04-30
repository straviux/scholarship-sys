<script setup>
import { computed } from 'vue';
import { formatStatus, getGroupValue, getReportStatus, normalizeStatus } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

/* ── Status overview cards ────── */
const statusCounts = computed(() => {
    const counts = {};
    for (const rec of props.records) {
        const s = normalizeStatus(getReportStatus(rec));
        counts[s] = (counts[s] || 0) + 1;
    }
    return counts;
});

const summaryCards = computed(() => {
    const order = ['pending', 'interviewed', 'approved_history', 'denied_history', 'active', 'denied', 'completed', 'withdrawn', 'loa', 'suspended'];
    return order.filter(s => statusCounts.value[s]).map(s => ({
        label: formatStatus(s), count: statusCounts.value[s], status: s,
    }));
});

/* ── Axes ────── */
const primaryAxis = computed(() => props.options.groupBy || 'program');
const secondaryAxis = computed(() => props.options.groupBySecondary || null);
const tertiaryAxis = computed(() => props.options.groupByTertiary || null);

const axisLabels = {
    program: 'Program', school: 'School', course: 'Course',
    municipality: 'Municipality', year_level: 'Year Level',
    grant_provision: 'Grant Provision', unified_status: 'Status',
};

const axisTitle = computed(() => {
    const parts = [primaryAxis.value, secondaryAxis.value, tertiaryAxis.value]
        .filter(Boolean)
        .map(a => axisLabels[a] || a);
    return parts.join(' › ');
});

/* ── Flat primary breakdown (for top-N right panel + breakdownData) ────── */
const breakdownData = computed(() => {
    const grouped = {};
    for (const rec of props.records) {
        const key = getGroupValue(rec, primaryAxis.value);
        if (!grouped[key]) grouped[key] = { total: 0, byStatus: {} };
        grouped[key].total++;
        const s = normalizeStatus(getReportStatus(rec));
        grouped[key].byStatus[s] = (grouped[key].byStatus[s] || 0) + 1;
    }
    return Object.entries(grouped)
        .sort(([, a], [, b]) => b.total - a.total)
        .map(([key, data]) => ({ key, ...data }));
});

/* ── Hierarchical flat rows for the detail table ────── */
const hierarchyRows = computed(() => {
    const rows = [];

    function statusOf(rec) { return normalizeStatus(getReportStatus(rec)); }

    function buildLevel(items, axis) {
        const g = {};
        for (const rec of items) {
            const k = getGroupValue(rec, axis);
            if (!g[k]) g[k] = { total: 0, byStatus: {}, items: [] };
            g[k].total++;
            g[k].items.push(rec);
            const s = statusOf(rec);
            g[k].byStatus[s] = (g[k].byStatus[s] || 0) + 1;
        }
        return Object.entries(g).sort(([, a], [, b]) => b.total - a.total);
    }

    for (const [pk, pData] of buildLevel(props.records, primaryAxis.value)) {
        rows.push({ depth: 0, key: pk, total: pData.total, byStatus: pData.byStatus });

        if (secondaryAxis.value) {
            for (const [sk, sData] of buildLevel(pData.items, secondaryAxis.value)) {
                rows.push({ depth: 1, key: sk, total: sData.total, byStatus: sData.byStatus });

                if (tertiaryAxis.value) {
                    for (const [tk, tData] of buildLevel(sData.items, tertiaryAxis.value)) {
                        rows.push({ depth: 2, key: tk, total: tData.total, byStatus: tData.byStatus });
                    }
                }
            }
        }
    }

    return rows;
});

/* ── Active statuses across data ────── */
const activeStatuses = computed(() => {
    const all = new Set();
    for (const row of hierarchyRows.value) {
        for (const s of Object.keys(row.byStatus)) all.add(s);
    }
    const order = ['pending', 'interviewed', 'approved_history', 'denied_history', 'approved', 'denied', 'active', 'completed', 'withdrawn', 'loa', 'suspended'];
    return order.filter(s => all.has(s));
});

// First 2 status cards for the top metric row
const topMetricCards = computed(() => summaryCards.value.slice(0, 2));

// Top 8 primary groups for right panel
const topBreakdownRows = computed(() => breakdownData.value.slice(0, 8));
const maxBreakdownTotal = computed(() => {
    const top = topBreakdownRows.value;
    return top.length ? Math.max(...top.map(r => r.total)) : 1;
});
</script>

<template>
    <div>
        <!-- ─── Header ─────────────────────────── -->
        <div class="report-header">
            <div class="report-header-logos">
                <img src="/images/pgp-logo.png" alt="PGP Logo" class="report-logo" />
            </div>
            <div class="report-header-text">
                <h1>SCHOLARSHIP SUMMARY REPORT</h1>
                <h2>Statistical Breakdown</h2>
            </div>
            <div class="report-header-logos">
                <img src="/images/yakap-logo.png" alt="YAKAP Logo" class="report-logo" />
            </div>
        </div>

        <!-- ─── Filters ─────────────────────────── -->
        <div v-if="Object.keys(filters).length" class="filter-bar">
            <span v-for="(val, key) in filters" :key="key" class="filter-badge">
                <strong>{{ key }}:</strong> {{ val }}
            </span>
        </div>

        <!-- ─── TOP METRIC CARDS ───────────────────── -->
        <div class="sum-metrics-row">
            <div class="sum-metric-card">
                <div class="sum-metric-label">Total Records</div>
                <div class="sum-metric-value">{{ records.length }}</div>
            </div>
            <div v-for="card in topMetricCards" :key="card.status" class="sum-metric-card">
                <div class="sum-metric-label">{{ card.label }}</div>
                <div class="sum-metric-value">{{ card.count }}</div>
            </div>
        </div>

        <!-- ─── TWO-COLUMN PANEL ───────────────────── -->
        <div class="sum-two-col">

            <!-- LEFT: Status Overview -->
            <div class="sum-panel">
                <div class="sum-panel-title">Status Overview</div>
                <div class="sum-status-list">
                    <div v-for="card in summaryCards" :key="card.status" class="sum-status-row-wrap">
                        <div class="sum-status-row">
                            <div :class="['sum-dot', `sum-dot-${card.status}`]"></div>
                            <span class="sum-status-name">{{ card.label }}</span>
                            <span class="sum-status-count">{{ card.count }}</span>
                            <span class="sum-pct-label">{{ records.length ? ((card.count / records.length) *
                                100).toFixed(1) : 0 }}%</span>
                        </div>
                        <div class="sum-bar-track">
                            <div :class="['sum-bar-fill', `sum-bar-${card.status}`]"
                                :style="{ width: records.length ? ((card.count / records.length) * 100).toFixed(1) + '%' : '0%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Top by Primary Axis -->
            <div class="sum-panel">
                <div class="sum-panel-title">Top by {{ axisLabels[primaryAxis] || primaryAxis }}</div>
                <div class="sum-rank-list">
                    <div v-for="(row, idx) in topBreakdownRows" :key="row.key" class="sum-rank-row">
                        <div class="sum-rank-meta">
                            <span class="sum-rank-num">{{ idx + 1 }}</span>
                            <span class="sum-rank-name">{{ row.key }}</span>
                            <span class="sum-rank-count">{{ row.total }}</span>
                            <span class="sum-rank-pct">{{ records.length ? ((row.total / records.length) *
                                100).toFixed(1) : 0 }}%</span>
                        </div>
                        <div class="sum-rank-track">
                            <div class="sum-rank-fill"
                                :style="{ width: maxBreakdownTotal ? ((row.total / maxBreakdownTotal) * 100).toFixed(1) + '%' : '0%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── FULL-WIDTH BREAKDOWN TABLE ───────────── -->
        <div class="sum-section-title">
            Detailed Breakdown by {{ axisTitle }}
        </div>

        <table class="breakdown-table modern-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th>{{ axisTitle }}</th>
                    <th v-for="s in activeStatuses" :key="s" :class="['count-col', `status-th-${s}`]">
                        {{ formatStatus(s) }}
                    </th>
                    <th class="count-col">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in hierarchyRows" :key="idx" :class="`sum-depth-${row.depth}`">
                    <td :class="`sum-depth-${row.depth}-cell`">{{ row.key }}</td>
                    <td v-for="s in activeStatuses" :key="s" class="count-col">{{ row.byStatus[s] || '—' }}</td>
                    <td class="count-col bold">{{ row.total }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Grand Total</strong></td>
                    <td v-for="s in activeStatuses" :key="s" class="count-col">
                        <strong>{{hierarchyRows.filter(r => r.depth === (tertiaryAxis ? 2 : secondaryAxis ? 1 :
                            0)).reduce((sum, r) => sum + (r.byStatus[s] || 0), 0)}}</strong>
                    </td>
                    <td class="count-col"><strong>{{ records.length }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="report-footer" style="margin-top: 10pt;">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total Records: {{ records.length }}</span>
        </div>
    </div>
</template>
