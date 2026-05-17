/**
 * PDF Print CSS Library
 * ─────────────────────────────────────────────────────
 * Font  : Arial / Helvetica, sans-serif
 * Units : pt  (points — 1pt = 1/72 inch)
 * Target: window.print() via a new browser window
 *
 * GRID TABLE SYSTEM
 * ─────────────────
 * Build a form/table by combining:
 *
 *   .pdf-table   → CSS Grid container (define columns separately)
 *   .pdf-grid-*  → column definition presets
 *
 * Every direct child of .pdf-table is a "cell". By default cells are
 * flex row, vertically centred. Use .blk for block layout (HTML content).
 *
 * COLUMN SPANS  (relative — spans N cols from current position)
 *   .cs-1 … .cs-8
 *
 * COLUMN START  (absolute — pins cell to column N, combine with .cs-N)
 *   .c1 … .c8
 *
 * ROW SPAN
 *   .rs-2, .rs-3
 *
 * OBR SEMANTIC SHORTCUTS  (use instead of raw column numbers for OBR docs)
 *   .obr-label   col 1          100pt  – left labels (Payee:, Address: …)
 *   .obr-main    col 2          1fr    – main content, col 2 only
 *   .obr-wide    col 2–3        1fr+70 – main content, year col merged in
 *   .obr-year    col 3           70pt  – year column
 *   .obr-fpp     col 4           50pt  – F.P.P sub-col
 *   .obr-code    col 5           65pt  – Account Code sub-col
 *   .obr-amt     col 6          110pt  – Amount sub-col
 *   .obr-right   col 4–6        225pt  – right 3 cols merged
 *   .obr-title   col 1–3              – left half of row (label+main)
 *   .obr-all     col 1–6              – full-width row
 *
 * EXAMPLE  (a 3-cell OBR content row):
 *   <div class="obr-label b-r b-b t-9">Payee:</div>
 *   <div class="obr-wide  b-r b-b bold t-11">JUAN DELA CRUZ</div>
 *   <div class="obr-right     b-b">&nbsp;</div>
 */

const PDF_CSS = `
/* ── Reset ──────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10pt;
  color: #000;
  background: #fff;
  padding: 6mm 5mm;
}

@page { size: {{PAGE_SIZE}}; margin: 6mm 5mm; }

/* Ensure Quill/HTML content renders correctly */
p  { display: block !important; margin: 0 !important; padding: 0 !important; line-height: 1.5; }
ol, ul { padding-left: 14pt; margin: 0; }
strong { font-weight: bold; }
em     { font-style: italic; }

/* ── Typography ─────────────────────────────── */
.t-5   { font-size:  5pt; }
.t-6   { font-size:  6pt; }
.t-7   { font-size:  7pt; }
.t-8   { font-size:  8pt; }
.t-9   { font-size:  9pt; }
.t-10  { font-size: 10pt; }
.t-11  { font-size: 11pt; }
.t-12  { font-size: 12pt; }
.t-14  { font-size: 14pt; }

.bold      { font-weight: bold; }
.normal    { font-weight: normal; }
.italic    { font-style: italic; }
.underline { text-decoration: underline; }
.nowrap    { white-space: nowrap; }

.left   { text-align: left;   justify-content: flex-start; }
.center { text-align: center; justify-content: center; }
.right  { text-align: right;  justify-content: flex-end; }

/* ── Borders ─────────────────────────────────── */
.b-all    { border: 1pt solid #000; }
.b-t      { border-top:    1pt solid #000; }
.b-r      { border-right:  1pt solid #000; }
.b-b      { border-bottom: 1pt solid #000; }
.b-l      { border-left:   1pt solid #000; }

.b-none   { border:        none !important; }
.b-t-none { border-top:    none !important; }
.b-r-none { border-right:  none !important; }
.b-b-none { border-bottom: none !important; }
.b-l-none { border-left:   none !important; }

/* ── Spacing ─────────────────────────────────── */
.px-1 { padding-left: 2pt;  padding-right: 2pt;  }
.px-2 { padding-left: 4pt;  padding-right: 4pt;  }
.px-3 { padding-left: 6pt;  padding-right: 6pt;  }
.px-4 { padding-left: 8pt;  padding-right: 8pt;  }
.py-1 { padding-top:  2pt;  padding-bottom: 2pt; }
.py-2 { padding-top:  4pt;  padding-bottom: 4pt; }
.py-3 { padding-top:  6pt;  padding-bottom: 6pt; }
.pt-0  { padding-top: 0;    }
.pt-4  { padding-top: 4pt;  }
.pt-8  { padding-top: 8pt;  }
.pt-40 { padding-top: 40pt; }

/* ── Grid Table System ───────────────────────── */
/*
  .pdf-table is a CSS Grid container.
  Each direct child is treated as a cell.
  Add column-definition class (.pdf-grid-obr, .pdf-g-3, …) to configure columns.
*/
.pdf-table {
  display: grid;
  width: 100%;
}

/* Default cell — flex row, vertically centred */
.pdf-table > div {
  display: flex;
  align-items: center;
  padding: 2pt 4pt;
  min-height: 18pt;
  word-break: break-word;
  overflow: hidden;
  line-height: 1.4;
}

/* Block cell — for cells containing HTML (Quill output, paragraphs) */
.pdf-table > div.blk {
  display: block;
  padding: 3pt 4pt;
}

/* Column-direction flex cell — stacks children vertically */
.pdf-table > div.col-dir {
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

/* ── Column Span Utilities (relative) ────────── */
/*  Spans N columns from the cell's current grid position  */
.cs-1 { grid-column: span 1; }
.cs-2 { grid-column: span 2; }
.cs-3 { grid-column: span 3; }
.cs-4 { grid-column: span 4; }
.cs-5 { grid-column: span 5; }
.cs-6 { grid-column: span 6; }
.cs-7 { grid-column: span 7; }
.cs-8 { grid-column: span 8; }

/* ── Absolute Column Start ───────────────────── */
/*  Pins the cell to start at column N.
    Combine with .cs-N for precise placement:
      <div class="c3 cs-2">  → starts at col 3, spans 2 columns  */
.c1 { grid-column-start: 1; }
.c2 { grid-column-start: 2; }
.c3 { grid-column-start: 3; }
.c4 { grid-column-start: 4; }
.c5 { grid-column-start: 5; }
.c6 { grid-column-start: 6; }
.c7 { grid-column-start: 7; }
.c8 { grid-column-start: 8; }

/* ── Row Span ─────────────────────────────────── */
.rs-2 { grid-row: span 2; }
.rs-3 { grid-row: span 3; }
.rs-4 { grid-row: span 4; }

/* ── Grid Column Definitions ─────────────────── */

/*
  OBR — 6 columns  (total = 576pt, matches DV content width)
  ┌─────────┬──────────────┬────────┬───────┬──────────┬──────────┐
  │ label   │     main     │  year  │  fpp  │  code    │  amount  │
  │  100pt  │    181pt     │  70pt  │  50pt │  65pt    │  110pt   │
  └─────────┴──────────────┴────────┴───────┴──────────┴──────────┘
*/
.pdf-grid-obr {
  grid-template-columns: 100pt 181pt 70pt 50pt 65pt 110pt;
}

/* Generic equal-column grids */
.pdf-g-2 { grid-template-columns: 1fr 1fr; }
.pdf-g-3 { grid-template-columns: 1fr 1fr 1fr; }
.pdf-g-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

/* ── OBR Semantic Column Shortcuts ───────────── */
/*
  Use these in OBR templates instead of raw column numbers.
  Much easier to read and customize.
*/
.obr-label  { grid-column: 1;     }   /* left label                         */
.obr-main   { grid-column: 2;     }   /* main content (col 2 only)          */
.obr-wide   { grid-column: 2 / 4; }   /* main + year merged (no year shown) */
.obr-year   { grid-column: 3;     }   /* year / right-side label            */
.obr-fpp    { grid-column: 4;     }   /* F.P.P                              */
.obr-code   { grid-column: 5;     }   /* Account Code                       */
.obr-amt    { grid-column: 6;     }   /* Amount                             */
.obr-right  { grid-column: 4 / 7; }   /* fpp + code + amount merged         */
.obr-title  { grid-column: 1 / 4; }   /* label + main + year merged         */
.obr-all    { grid-column: 1 / 7; }   /* full-width row                     */

/* ── Page Break ──────────────────────────────── */
.break-after  { page-break-after:  always; }
.break-before { page-break-before: always; }
.no-break     { page-break-inside: avoid;  }

/* ── DV (Disbursement Voucher) row system ────── */
/*
  Use .dv-row as the flex-row container for each DV row.
  Column widths are set per-row with inline styles since
  the DV layout has varying proportions per row.
*/
.dv-row {
  display: flex;
  align-items: stretch;
  border-bottom: 1pt solid #000;
}
/* Standalone .blk — for v-html cells outside .pdf-table */
.blk {
  display: block;
  padding: 3pt 4pt;
}

/* ── Print Overrides ────────────────────────── */
@media print {
  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body { margin: 0; padding: 0; }
}
`;

/**
 * Paper size pixel dimensions at 96 dpi.
 * Used by the preview modal to set the correct iframe/wrapper size.
 */
export const PAPER_SIZES = {
	a4: { w: 794, h: 1123, cssSize: 'A4', label: 'A4 — 210 × 297 mm' },
	'a4-landscape': {
		w: 1123,
		h: 794,
		cssSize: 'A4 landscape',
		label: 'A4 Landscape — 297 × 210 mm',
	},
	letter: { w: 816, h: 1056, cssSize: '8.5in 11in', label: 'Letter — 8.5 × 11 in' },
	'letter-landscape': {
		w: 1056,
		h: 816,
		cssSize: '11in 8.5in',
		label: 'Letter Landscape — 11 × 8.5 in',
	},
	long: { w: 816, h: 1248, cssSize: '8.5in 13in', label: 'Long — 8.5 × 13 in' },
	landscape: { w: 1248, h: 816, cssSize: '13in 8.5in', label: 'Long Landscape — 13 × 8.5 in' },
};

/**
 * Returns the full CSS string for the given paper size.
 * @param {'a4'|'long'} paperSize
 */
export const getPdfCss = (paperSize = 'a4') => {
	const size = PAPER_SIZES[paperSize]?.cssSize ?? PAPER_SIZES.a4.cssSize;
	return PDF_CSS.replace('{{PAGE_SIZE}}', size);
};

/** Default export: A4 CSS (backward-compat for any direct consumer). */
export default getPdfCss('a4');
