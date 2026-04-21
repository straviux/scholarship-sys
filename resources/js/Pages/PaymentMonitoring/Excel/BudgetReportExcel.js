import * as XLSXStyle from 'xlsx';

const NC = 9; // #, Date Obligated, Account Code, Prepared By, Payee, Particulars, Credit, Debit, Remarks
const thin = { style: 'thin' };
const med = { style: 'medium' };

const ST = {
	colHeader: {
		font: { bold: true, sz: 10 },
		border: { top: med, bottom: med, left: thin, right: thin },
		alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
	},
	data: {
		font: { sz: 9 },
		border: { top: thin, bottom: thin, left: thin, right: thin },
		alignment: { vertical: 'center', wrapText: true },
	},
	dataCenter: {
		font: { sz: 9 },
		border: { top: thin, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'center', vertical: 'center' },
	},
	dataAmt: {
		font: { sz: 9 },
		border: { top: thin, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
	},
	endingBalance: {
		font: { bold: true, sz: 10 },
		border: { top: med, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'center', vertical: 'center' },
	},
	endingAmt: {
		font: { bold: true, sz: 10 },
		border: { top: med, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
	},
	subTotal: {
		font: { bold: true, sz: 9 },
		border: { top: thin, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'center', vertical: 'center' },
	},
	subTotalAmt: {
		font: { bold: true, sz: 9 },
		border: { top: thin, bottom: thin, left: thin, right: thin },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
	},
	grandLabel: {
		font: { bold: true, sz: 10 },
		border: { top: med, bottom: med, left: thin, right: thin },
		alignment: { horizontal: 'center', vertical: 'center' },
	},
	grandAmt: {
		font: { bold: true, sz: 10 },
		border: { top: med, bottom: med, left: thin, right: thin },
		alignment: { horizontal: 'right', vertical: 'center' },
		numFmt: '#,##0.00',
	},
};

const cell = (v, s, t) => ({ v, s, t: t ?? (typeof v === 'number' ? 'n' : 's') });
const empty = (s = {}) => ({ v: '', t: 's', s });
const blank = () => Array(NC).fill(empty());
const mg = (r, c1, c2) => ({ s: { r, c: c1 }, e: { r, c: c2 } });
const fmtAmt = (v) => parseFloat(v || 0);
const fmtDate = (d) => {
	if (!d) return '-';
	return new Date(d).toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	});
};

/**
 * @param {object} opts
 * @param {object} opts.reportData  - API response from budget-report.api
 * @param {string} opts.today
 */
export function exportBudgetReportExcel({ reportData, today }) {
	const M = NC - 1;
	const sheetData = [];
	const merges = [];
	let R = 0;

	// Column headers
	sheetData.push(
		[
			'#',
			'Date Obligated',
			'Account Code',
			'Prepared By',
			'Payee',
			'Particulars',
			'Credit',
			'Debit',
			'Remarks',
		].map((h) => cell(h, ST.colHeader)),
	);
	R++;

	// ENDING BALANCE row
	const ebRow = blank();
	ebRow[0] = empty(ST.endingBalance);
	ebRow[1] = cell('ENDING BALANCE', ST.endingBalance);
	for (let c = 2; c <= 5; c++) ebRow[c] = empty(ST.endingBalance);
	ebRow[6] = cell(fmtAmt(reportData.allotment), ST.endingAmt);
	ebRow[7] = empty(ST.endingBalance);
	ebRow[8] = empty(ST.endingBalance);
	merges.push(mg(R, 1, 5));
	sheetData.push(ebRow);
	R++;

	// Data rows
	(reportData.rows ?? []).forEach((row, i) => {
		sheetData.push([
			cell(i + 1, ST.dataCenter),
			cell(fmtDate(row.date_obligated), ST.dataCenter),
			cell(row.account_code ?? '-', ST.data),
			cell(row.prepared_by ?? '-', ST.data),
			cell(row.payee ?? '-', ST.data),
			cell(row.particulars ?? '-', ST.data),
			cell(fmtAmt(row.credit), ST.dataAmt),
			empty(ST.dataCenter),
			cell(row.status ?? '-', ST.dataCenter),
		]);
		R++;
	});

	// Sub-Total
	const subCreditAmt = fmtAmt(reportData.sub_credit);
	const stRow = blank();
	stRow[0] = cell('Sub-Total', ST.subTotal);
	for (let c = 1; c <= 5; c++) stRow[c] = empty(ST.subTotal);
	stRow[6] = cell(subCreditAmt, ST.subTotalAmt);
	stRow[7] = empty(ST.subTotal);
	stRow[8] = empty(ST.subTotal);
	merges.push(mg(R, 0, 5));
	sheetData.push(stRow);
	R++;

	// Grand Total (remaining = allotment - sub_credit)
	const remaining = fmtAmt(reportData.allotment) - subCreditAmt;
	const gtRow = blank();
	gtRow[0] = cell('Grand Total (Remaining)', ST.grandLabel);
	for (let c = 1; c <= 5; c++) gtRow[c] = empty(ST.grandLabel);
	gtRow[6] = cell(remaining, ST.grandAmt);
	gtRow[7] = empty(ST.grandLabel);
	gtRow[8] = empty(ST.grandLabel);
	merges.push(mg(R, 0, 5));
	sheetData.push(gtRow);
	R++;

	// Build worksheet
	const ws = XLSXStyle.utils.aoa_to_sheet(sheetData);
	ws['!merges'] = merges;
	ws['!cols'] = [
		{ wch: 5 },
		{ wch: 16 },
		{ wch: 15 },
		{ wch: 18 },
		{ wch: 24 },
		{ wch: 36 },
		{ wch: 15 },
		{ wch: 12 },
		{ wch: 16 },
	];

	const wb = XLSXStyle.utils.book_new();
	XLSXStyle.utils.book_append_sheet(wb, ws, 'Allotment Report');

	const safe = (s) => (s ?? '').replace(/[^a-zA-Z0-9]/g, '_');
	const filename =
		[
			'Budget_Report',
			safe(reportData.program_name),
			safe(reportData.fiscal_year),
			new Date().toISOString().slice(0, 10),
		]
			.filter(Boolean)
			.join('_') + '.xlsx';
	XLSXStyle.writeFile(wb, filename);
}
