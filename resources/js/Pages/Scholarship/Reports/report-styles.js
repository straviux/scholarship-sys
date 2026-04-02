/**
 * Report Print CSS
 * ─────────────────────────────────────────────────
 * Minimal, clean CSS for scholarship report templates.
 * Designed for client-side rendering via renderVueTemplate → iframe/print.
 *
 * @param {string} pageSize — CSS @page size value, e.g. '210mm 297mm' or '297mm 210mm'
 * @returns {string} — complete CSS string
 */
export function getReportCss(pageSize = '297mm 210mm') {
	return CSS_TEMPLATE.replace(/\{\{PAGE_SIZE\}\}/g, pageSize);
}

const CSS_TEMPLATE = `
/* ── Reset ─────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt;
  color: #000;
  background: #fff;
  padding: 8mm 10mm;
  line-height: 1.4;
}

@page {
  size: {{PAGE_SIZE}};
  margin: 0;
}

@media print {
  body { padding: 8mm 10mm; }
  .no-print { display: none !important; }
  .page-break { page-break-before: always; }
}

/* ── Report Header ─────────────────────────── */
.report-header {
  text-align: center;
  margin-bottom: 6pt;
  padding-bottom: 4pt;
  border-bottom: 1.5pt solid #000;
}

.report-header h1 {
  font-size: 14pt;
  font-weight: bold;
  margin-bottom: 2pt;
  letter-spacing: 0.5pt;
}

.report-header h2 {
  font-size: 11pt;
  font-weight: 600;
  margin-bottom: 2pt;
}

.report-header .subtitle {
  font-size: 9pt;
  color: #444;
}

/* ── Filter Badges ─────────────────────────── */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 4pt;
  margin-bottom: 6pt;
  font-size: 7pt;
}

.filter-badge {
  background: #f0f0f0;
  border: 0.5pt solid #ccc;
  border-radius: 3pt;
  padding: 1pt 4pt;
  white-space: nowrap;
}

.filter-badge strong {
  font-weight: 600;
}

/* ── Summary Cards ─────────────────────────── */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120pt, 1fr));
  gap: 6pt;
  margin-bottom: 10pt;
}

.summary-card {
  border: 0.5pt solid #ccc;
  border-radius: 4pt;
  padding: 6pt 8pt;
  text-align: center;
}

.summary-card .count {
  font-size: 18pt;
  font-weight: 300;
  color: #222;
}

.summary-card .label {
  font-size: 7pt;
  text-transform: uppercase;
  letter-spacing: 0.3pt;
  color: #666;
  margin-top: 2pt;
}

/* ── Tables ────────────────────────────────── */
.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 8pt;
  margin-bottom: 8pt;
}

.report-table th,
.report-table td {
  border: 0.5pt solid #999;
  padding: 3pt 5pt;
  text-align: left;
  vertical-align: top;
}

.report-table th {
  background: #e8e8e8;
  font-weight: 600;
  font-size: 7.5pt;
  text-transform: uppercase;
  letter-spacing: 0.2pt;
  white-space: nowrap;
}

.report-table tbody tr:nth-child(even) {
  background: #fafafa;
}

.report-table .text-center { text-align: center; }
.report-table .text-right  { text-align: right; }
.report-table .nowrap      { white-space: nowrap; }

/* ── Group Headers ─────────────────────────── */
.group-header {
  background: #ddd;
  font-weight: bold;
  font-size: 9pt;
  padding: 4pt 6pt;
  border: 0.5pt solid #999;
  margin-top: 6pt;
}

.sub-group-header {
  background: #eaeaea;
  font-weight: 600;
  font-size: 8.5pt;
  padding: 3pt 6pt 3pt 16pt;
  border: 0.5pt solid #999;
}

.tertiary-group-header {
  background: #f2f2f2;
  font-weight: 500;
  font-size: 8pt;
  padding: 2pt 6pt 2pt 28pt;
  border: 0.5pt solid #999;
}

/* ── Group Totals ──────────────────────────── */
.group-total {
  font-weight: bold;
  font-size: 8pt;
  text-align: right;
  padding: 3pt 6pt;
  border: 0.5pt solid #999;
  background: #f5f5f5;
}

/* ── Footer ────────────────────────────────── */
.report-footer {
  display: flex;
  justify-content: space-between;
  font-size: 7pt;
  color: #888;
  border-top: 0.5pt solid #ccc;
  padding-top: 4pt;
  margin-top: 8pt;
}

/* ── JPM Highlight ─────────────────────────── */
.jpm-row {
  background: #ecfdf5 !important;
}

/* ── Status Badges (for All Status report) ── */
.status-badge {
  display: inline-block;
  padding: 1pt 4pt;
  border-radius: 3pt;
  font-size: 7pt;
  font-weight: 600;
  border: 0.5pt solid;
}

.status-pending     { background: #FEF3C7; color: #92400E; border-color: #F59E0B; }
.status-interviewed { background: #EEF2FF; color: #3730A3; border-color: #6366F1; }
.status-approved    { background: #DBEAFE; color: #1E40AF; border-color: #3B82F6; }
.status-denied      { background: #FEE2E2; color: #991B1B; border-color: #EF4444; }
.status-active      { background: #D1FAE5; color: #065F46; border-color: #10B981; }
.status-completed   { background: #F3F4F6; color: #374151; border-color: #6B7280; }
.status-withdrawn   { background: #EDE9FE; color: #5B21B6; border-color: #8B5CF6; }
.status-loa         { background: #FEF3C7; color: #92400E; border-color: #D97706; }
.status-suspended   { background: #FEE2E2; color: #991B1B; border-color: #DC2626; }

/* ── Summary Breakdown Table ───────────────── */
.breakdown-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 9pt;
  margin-bottom: 10pt;
}

.breakdown-table th,
.breakdown-table td {
  border: 0.5pt solid #999;
  padding: 4pt 8pt;
}

.breakdown-table th {
  background: #e8e8e8;
  font-weight: 600;
  text-align: left;
}

.breakdown-table .total-row {
  font-weight: bold;
  background: #f0f0f0;
}

.breakdown-table .count-col {
  text-align: center;
  width: 60pt;
}

/* ── Utilities ─────────────────────────────── */
.bold { font-weight: bold; }
.italic { font-style: italic; }
.text-sm { font-size: 7pt; }
.text-muted { color: #888; }
.mt-1 { margin-top: 4pt; }
.mt-2 { margin-top: 8pt; }
.mb-1 { margin-bottom: 4pt; }
.mb-2 { margin-bottom: 8pt; }
`;
