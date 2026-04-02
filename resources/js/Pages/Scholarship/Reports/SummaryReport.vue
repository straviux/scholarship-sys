<script setup>
import { computed } from 'vue';
import { formatStatus, getGroupValue } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

/* ── Summary cards ────── */
const statusCounts = computed(() => {
    const counts = {};
    for (const rec of props.records) {
        const s = rec.approval_status || rec.unified_status || 'unknown';
        counts[s] = (counts[s] || 0) + 1;
    }
    return counts;
});

const summaryCards = computed(() => {
    const order = ['pending', 'interviewed', 'approved', 'denied', 'active', 'completed', 'withdrawn', 'loa', 'suspended'];
    const cards = order
        .filter(s => statusCounts.value[s])
        .map(s => ({ label: formatStatus(s), count: statusCounts.value[s], status: s }));
    return cards;
});

/* ── Breakdown by the chosen axis ────── */
const breakdownAxis = computed(() => props.options.summaryBreakdownAxis || 'program');

const breakdownData = computed(() => {
    const grouped = {};
    for (const rec of props.records) {
        const key = getGroupValue(rec, breakdownAxis.value);
        if (!grouped[key]) grouped[key] = { total: 0, byStatus: {} };
        grouped[key].total += 1;
        const s = rec.approval_status || rec.unified_status || 'unknown';
        grouped[key].byStatus[s] = (grouped[key].byStatus[s] || 0) + 1;
    }
    return Object.entries(grouped)
        .sort(([, a], [, b]) => b.total - a.total)
        .map(([key, data]) => ({ key, ...data }));
});

const activeStatuses = computed(() => {
    const all = new Set();
    for (const row of breakdownData.value) {
        for (const s of Object.keys(row.byStatus)) all.add(s);
    }
    const order = ['pending', 'interviewed', 'approved', 'denied', 'active', 'completed', 'withdrawn', 'loa', 'suspended'];
    return order.filter(s => all.has(s));
});

const axisLabels = {
    program: 'Program',
    school: 'School',
    course: 'Course',
    municipality: 'Municipality',
    year_level: 'Year Level',
    grant_provision: 'Grant Provision',
    unified_status: 'Status',
};
</script>

<template>
    <div>
        <div class="report-header">
            <h1>SCHOLARSHIP SUMMARY REPORT</h1>
            <h2>Statistical Breakdown</h2>
            <div class="subtitle">{{ generatedAt }}</div>
        </div>

        <div v-if="Object.keys(filters).length" class="filter-bar">
            <span v-for="(val, key) in filters" :key="key" class="filter-badge">
                <strong>{{ key }}:</strong> {{ val }}
            </span>
        </div>

        <!-- Overall Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="count">{{ records.length }}</div>
                <div class="label">Total Records</div>
            </div>
            <div v-for="card in summaryCards" :key="card.status" class="summary-card">
                <div class="count">{{ card.count }}</div>
                <div class="label">{{ card.label }}</div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <h3 style="font-size: 10pt; margin-bottom: 4pt;">Status Overview</h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="count-col">Count</th>
                    <th class="count-col">%</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(count, status) in statusCounts" :key="status">
                    <td>
                        <span :class="['status-badge', `status-${status}`]">{{ formatStatus(status) }}</span>
                    </td>
                    <td class="count-col">{{ count }}</td>
                    <td class="count-col">{{ records.length ? ((count / records.length) * 100).toFixed(1) : 0 }}%</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td class="count-col"><strong>{{ records.length }}</strong></td>
                    <td class="count-col"><strong>100%</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Breakdown by Axis -->
        <h3 style="font-size: 10pt; margin: 8pt 0 4pt;">
            Breakdown by {{ axisLabels[breakdownAxis] || breakdownAxis }}
        </h3>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>{{ axisLabels[breakdownAxis] || breakdownAxis }}</th>
                    <th v-for="s in activeStatuses" :key="s" class="count-col">{{ formatStatus(s) }}</th>
                    <th class="count-col">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in breakdownData" :key="row.key">
                    <td>{{ row.key }}</td>
                    <td v-for="s in activeStatuses" :key="s" class="count-col">{{ row.byStatus[s] || 0 }}</td>
                    <td class="count-col bold">{{ row.total }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Grand Total</strong></td>
                    <td v-for="s in activeStatuses" :key="s" class="count-col">
                        <strong>{{breakdownData.reduce((sum, r) => sum + (r.byStatus[s] || 0), 0)}}</strong>
                    </td>
                    <td class="count-col"><strong>{{ records.length }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="report-footer">
            <span>Total Records: {{ records.length }}</span>
            <span>Generated: {{ generatedAt }}</span>
        </div>
    </div>
</template>
