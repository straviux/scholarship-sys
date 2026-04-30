import { PAPER_SIZES } from '@/Pages/FundTransactions/Pdf/pdf-styles';

const REPORT_MARGINS_MM = {
	top: 10,
	right: 10,
	bottom: 14,
	left: 10,
};

const REPORT_PAPER_KEYS = {
	A4: {
		portrait: 'a4',
		landscape: 'a4-landscape',
	},
	Letter: {
		portrait: 'letter',
		landscape: 'letter-landscape',
	},
	Legal: {
		portrait: 'long',
		landscape: 'landscape',
	},
};

const PX_TO_MM = 25.4 / 96;

export function getReportPaperConfig(paperSize = 'A4', orientation = 'landscape') {
	const paperKey = REPORT_PAPER_KEYS[paperSize]?.[orientation] || 'a4-landscape';
	const paper = PAPER_SIZES[paperKey] || PAPER_SIZES['a4-landscape'];
	const widthMm = Number((paper.w * PX_TO_MM).toFixed(2));
	const heightMm = Number((paper.h * PX_TO_MM).toFixed(2));
	const printableHeightMm = Number(
		(heightMm - REPORT_MARGINS_MM.top - REPORT_MARGINS_MM.bottom).toFixed(2),
	);

	return {
		key: paperKey,
		cssPageSize: paper.cssSize,
		widthPx: paper.w,
		heightPx: paper.h,
		widthMm,
		heightMm,
		printableHeightMm,
		marginsMm: REPORT_MARGINS_MM,
	};
}

/**
 * Report Print CSS
 * ─────────────────────────────────────────────────
 * Minimal, clean CSS for scholarship report templates.
 * Designed for client-side rendering via renderVueTemplate → iframe/print.
 */
export function getReportCss(pageConfig = getReportPaperConfig()) {
	const config =
		typeof pageConfig === 'string' ?
			{
				cssPageSize: pageConfig,
				heightMm: 210,
				printableHeightMm: 186,
				marginsMm: REPORT_MARGINS_MM,
			}
		:	pageConfig;

	return CSS_TEMPLATE.replace(/\{\{PAGE_SIZE\}\}/g, config.cssPageSize)
		.replace(/\{\{PAGE_HEIGHT_MM\}\}/g, String(config.heightMm))
		.replace(/\{\{PRINTABLE_HEIGHT_MM\}\}/g, String(config.printableHeightMm))
		.replace(/\{\{MARGIN_TOP_MM\}\}/g, String(config.marginsMm.top))
		.replace(/\{\{MARGIN_RIGHT_MM\}\}/g, String(config.marginsMm.right))
		.replace(/\{\{MARGIN_BOTTOM_MM\}\}/g, String(config.marginsMm.bottom))
		.replace(/\{\{MARGIN_LEFT_MM\}\}/g, String(config.marginsMm.left));
}

const CSS_TEMPLATE = `
/* ── Reset ─────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt;
  color: #000;
  background: #fff;
  line-height: 1.4;
  counter-reset: page;
}

@page {
  size: {{PAGE_SIZE}};
  margin: {{MARGIN_TOP_MM}}mm {{MARGIN_RIGHT_MM}}mm {{MARGIN_BOTTOM_MM}}mm {{MARGIN_LEFT_MM}}mm;
  @bottom-right {
    content: "Page " counter(page) " of " counter(pages);
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    color: #888;
  }
}

@media print {
  body { padding: 0; }
  .no-print { display: none !important; }
  .page-break { page-break-before: always; }
}

/* ── Paged.js page breaks ────────────────────── */
.summary-section {
  break-before: page;
}

.report-page {
  display: flex;
  flex-direction: column;
}

/* ── Report Header ─────────────────────────── */
.report-header {
  display: flex;
  align-items: center;
  gap: 8pt;
  margin-bottom: 6pt;
  padding-bottom: 6pt;
  border-bottom: 1.5pt solid #000;
}

.report-header-logos {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.report-logo {
  width: 48pt;
  height: 48pt;
  object-fit: contain;
}

.report-header-text {
  flex: 1;
  text-align: center;
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

/* ── Record Total (top of page 1) ─────────── */
.report-total-line {
  font-size: 8.5pt;
  font-weight: 600;
  color: #333;
  margin-bottom: 6pt;
  padding: 3pt 0;
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
  margin-top: auto;
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
.status-approved_history { background: #DBEAFE; color: #1E40AF; border-color: #3B82F6; }
.status-denied_history   { background: #FEE2E2; color: #991B1B; border-color: #EF4444; }
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

/* ── Summary Report — Modern Layout ────────── */

/* Top metric cards row */
.sum-metrics-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6pt;
  margin-bottom: 10pt;
}

.sum-metric-card {
  border: 1pt solid #cbd5e1;
  border-radius: 5pt;
  padding: 8pt 10pt;
}

.sum-metric-label {
  font-size: 6.5pt;
  color: #64748b;
  margin-bottom: 3pt;
  text-transform: uppercase;
  letter-spacing: 0.4pt;
}

.sum-metric-value {
  font-size: 22pt;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
}

/* Two-column section */
.sum-two-col {
  display: grid;
  grid-template-columns: 52% 46%;
  gap: 8pt;
  margin-bottom: 10pt;
}

.sum-panel {
  border: 0.5pt solid #e2e8f0;
  border-radius: 5pt;
  padding: 8pt 10pt;
}

.sum-panel-title {
  font-size: 8.5pt;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 7pt;
  padding-bottom: 4pt;
  border-bottom: 0.5pt solid #e2e8f0;
}

.sum-status-list {
  display: flex;
  flex-direction: column;
  gap: 5pt;
}

.sum-status-row-wrap {
  display: flex;
  flex-direction: column;
  gap: 2pt;
}

.sum-status-row {
  display: flex;
  align-items: center;
  gap: 5pt;
}

.sum-dot {
  width: 7pt;
  height: 7pt;
  border-radius: 50%;
  flex-shrink: 0;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.sum-dot-pending     { background: #F59E0B; }
.sum-dot-interviewed { background: #6366F1; }
.sum-dot-approved_history { background: #3B82F6; }
.sum-dot-denied_history   { background: #EF4444; }
.sum-dot-approved    { background: #3B82F6; }
.sum-dot-denied      { background: #EF4444; }
.sum-dot-active      { background: #10B981; }
.sum-dot-completed   { background: #6B7280; }
.sum-dot-withdrawn   { background: #8B5CF6; }
.sum-dot-loa         { background: #D97706; }
.sum-dot-suspended   { background: #DC2626; }

.sum-status-name {
  flex: 1;
  font-size: 8pt;
  color: #334155;
}

.sum-status-count {
  font-size: 9pt;
  font-weight: 700;
  color: #0f172a;
  min-width: 20pt;
  text-align: right;
}

.sum-pct-label {
  font-size: 7pt;
  color: #64748b;
  min-width: 26pt;
  text-align: right;
}

.sum-bar-track {
  width: 100%;
  height: 4pt;
  background: #e2e8f0;
  border-radius: 2pt;
  overflow: hidden;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.sum-bar-fill {
  height: 100%;
  border-radius: 2pt;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.sum-bar-pending     { background: #F59E0B; }
.sum-bar-interviewed { background: #6366F1; }
.sum-bar-approved_history { background: #3B82F6; }
.sum-bar-denied_history   { background: #EF4444; }
.sum-bar-approved    { background: #3B82F6; }
.sum-bar-denied      { background: #EF4444; }
.sum-bar-active      { background: #10B981; }
.sum-bar-completed   { background: #6B7280; }
.sum-bar-withdrawn   { background: #8B5CF6; }
.sum-bar-loa         { background: #D97706; }
.sum-bar-suspended   { background: #DC2626; }

/* Rank list (right panel) */
.sum-rank-list {
  display: flex;
  flex-direction: column;
  gap: 7pt;
}

.sum-rank-row {
  display: flex;
  flex-direction: column;
  gap: 2pt;
}

.sum-rank-meta {
  display: flex;
  align-items: baseline;
  gap: 3pt;
}

.sum-rank-num {
  font-size: 7pt;
  font-weight: 700;
  color: #94a3b8;
  min-width: 10pt;
}

.sum-rank-name {
  font-size: 7.5pt;
  color: #334155;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 120pt;
}

.sum-rank-count {
  font-size: 8.5pt;
  font-weight: 700;
  color: #0f172a;
}

.sum-rank-pct {
  font-size: 7pt;
  color: #94a3b8;
  min-width: 24pt;
  text-align: right;
}

.sum-rank-track {
  height: 5pt;
  background: #e2e8f0;
  border-radius: 2.5pt;
  overflow: hidden;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.sum-rank-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 2.5pt;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* Full-width section title */
.sum-section-title {
  font-size: 8pt;
  font-weight: 700;
  color: #0f172a;
  text-transform: uppercase;
  letter-spacing: 0.5pt;
  margin: 10pt 0 5pt;
  padding: 4pt 8pt;
  background: #f1f5f9;
  border-left: 3pt solid #3b82f6;
  border-radius: 2pt;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* Hierarchy depth rows */
.sum-depth-0 {
  background: #f1f5f9;
  font-weight: 700;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}
.sum-depth-0-cell {
  color: #0f172a;
  font-weight: 700;
}
.sum-depth-1 {
  background: #ffffff;
}
.sum-depth-1-cell {
  padding-left: 14pt !important;
  color: #334155;
  font-style: italic;
}
.sum-depth-2 {
  background: #fafafa;
}
.sum-depth-2-cell {
  padding-left: 26pt !important;
  color: #64748b;
  font-size: 7.5pt;
}

/* Modern table dark header */
.modern-table thead th {
  background: #1e293b !important;
  color: #fff !important;
  font-size: 7.5pt;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* Status-tinted column headers */
.status-th-pending     { background: #FEF3C7 !important; color: #92400E !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-interviewed { background: #EEF2FF !important; color: #3730A3 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-approved    { background: #DBEAFE !important; color: #1E40AF !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-denied      { background: #FEE2E2 !important; color: #991B1B !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-active      { background: #D1FAE5 !important; color: #065F46 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-completed   { background: #F3F4F6 !important; color: #374151 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-withdrawn   { background: #EDE9FE !important; color: #5B21B6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-loa         { background: #FEF3C7 !important; color: #92400E !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.status-th-suspended   { background: #FEE2E2 !important; color: #991B1B !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

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
