import XLSXStyle from 'xlsx-js-style';

// Columns: # | Program | Fiscal Year | Allotment | Disbursed | Remaining | Usage %
const NC = 7;

const thin = { style: 'thin' };
const med = { style: 'medium' };

const ST = {
	colHeader: {
		font: { bold: true, sz: 10 },
		alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
		border: { top: med, bottom: med, left: thin, right: thin },
	},
	data: {
		font: { sz: 10 },
		alignment: { horizontal: 'left', vertical: 'center', wrapText: true },
		border: { top: thin, bottom: thin, left: thin, right: thin },
	},
	dataCenter: {
		font: { sz: 10 },
		alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
		border: { top: thin, bottom: thin, left: thin, right: thin },
	},
	dataAmt: {
		font: { sz: 10 },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
		border: { top: thin, bottom: thin, left: thin, right: thin },
	},
	dataAmtOver: {
		font: { bold: true, sz: 10 },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
		border: { top: thin, bottom: thin, left: thin, right: thin },
	},
	grandLabel: {
		font: { bold: true, sz: 10 },
		alignment: { horizontal: 'right', vertical: 'center' },
		border: { top: med, bottom: med, left: thin, right: thin },
	},
	grandAmt: {
		font: { bold: true, sz: 10 },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
		border: { top: med, bottom: med, left: thin, right: thin },
	},
};

function cell(v, s) {
	return { v, t: typeof v === 'number' ? 'n' : 's', s };
}
function mg(ws, start, end, r1, r2) {
	if (!ws['!merges']) ws['!merges'] = [];
	ws['!merges'].push({ s: { r: r1, c: start }, e: { r: r2, c: end } });
}

export function exportBudgetMonitoringExcel({
	rows,
	totals,
	filterProgram,
	filterFiscalYear,
	today,
}) {
	const ws = {};
	let r = 0;

	// ── Column headers ─────────────────────────────────────────────
	const headers = ['#', 'Program', 'Fiscal Year', 'Allotment', 'Disbursed', 'Remaining', 'Usage %'];
	headers.forEach((h, c) => {
		ws[`${String.fromCharCode(65 + c)}${r + 1}`] = cell(h, ST.colHeader);
	});
	r++;

	// ── Data rows ──────────────────────────────────────────────────
	rows.forEach((row, i) => {
		const pct =
			row.total_allotment > 0
				? ((row.disbursed / row.total_allotment) * 100).toFixed(2) + '%'
				: '0.00%';
		ws[`A${r + 1}`] = cell(i + 1, ST.dataCenter);
		ws[`B${r + 1}`] = cell(row.program || '', ST.data);
		ws[`C${r + 1}`] = cell(row.fiscal_year || '—', ST.dataCenter);
		ws[`D${r + 1}`] = cell(parseFloat(row.total_allotment) || 0, ST.dataAmt);
		ws[`E${r + 1}`] = cell(parseFloat(row.disbursed) || 0, ST.dataAmt);
		ws[`F${r + 1}`] = cell(
			parseFloat(row.remaining) || 0,
			row.overBudget ? ST.dataAmtOver : ST.dataAmt,
		);
		ws[`G${r + 1}`] = cell(pct, ST.dataCenter);
		r++;
	});

	// ── Grand total row ────────────────────────────────────────────
	const totalPct =
		totals.allotment > 0 ? ((totals.disbursed / totals.allotment) * 100).toFixed(2) + '%' : '0.00%';
	ws[`A${r + 1}`] = cell('GRAND TOTAL', ST.grandLabel);
	ws[`B${r + 1}`] = cell('', ST.grandLabel);
	ws[`C${r + 1}`] = cell('', ST.grandLabel);
	mg(ws, 0, 2, r, r);
	ws[`D${r + 1}`] = cell(parseFloat(totals.allotment) || 0, ST.grandAmt);
	ws[`E${r + 1}`] = cell(parseFloat(totals.disbursed) || 0, ST.grandAmt);
	ws[`F${r + 1}`] = cell(parseFloat(totals.remaining) || 0, ST.grandAmt);
	ws[`G${r + 1}`] = cell(totalPct, ST.grandLabel);
	r++;

	// ── Sheet range and column widths ─────────────────────────────
	ws['!ref'] = `A1:${String.fromCharCode(65 + NC - 1)}${r}`;
	ws['!cols'] = [
		{ wch: 5 }, // #
		{ wch: 30 }, // Program
		{ wch: 14 }, // Fiscal Year
		{ wch: 16 }, // Allotment
		{ wch: 16 }, // Disbursed
		{ wch: 16 }, // Remaining
		{ wch: 12 }, // Usage %
	];

	const wb = XLSXStyle.utils.book_new();
	XLSXStyle.utils.book_append_sheet(wb, ws, 'Budget Monitoring');

	const nameParts = ['Budget_Monitoring_Report'];
	if (filterProgram) nameParts.push(filterProgram.replace(/[^a-zA-Z0-9]/g, '_'));
	if (filterFiscalYear) nameParts.push(String(filterFiscalYear));
	const filename = nameParts.join('_') + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
	XLSXStyle.writeFile(wb, filename);
}
