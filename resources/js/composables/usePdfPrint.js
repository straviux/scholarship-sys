import { PAPER_SIZES, getPdfCss } from '@/Pages/FundTransactions/Pdf/pdf-styles.js';
import { createApp } from 'vue';
import safeHtmlDirective from '@/directives/safeHtml';

const DEFAULT_PRINT_FOOTER_MARGIN = '6mm 5mm 15mm 5mm';

function escapeHtml(value = '') {
	return String(value)
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#39;');
}

function escapeCssString(value = '') {
	return String(value)
		.replaceAll('\\', '\\\\')
		.replaceAll('"', '\\"')
		.replaceAll('\n', '\\A ')
		.replaceAll('\r', '');
}

function resolvePaperCssSize(paperSize = 'a4') {
	return PAPER_SIZES[paperSize]?.cssSize ?? PAPER_SIZES.a4.cssSize;
}

function buildPrintFooterConfig(paperSize = 'a4', options = {}) {
	const generatedAt = options?.generatedAt ? escapeHtml(options.generatedAt) : '';
	const generatedAtCss = options?.generatedAt ? escapeCssString(options.generatedAt) : '';
	const showPageNumbers = options?.showPageNumbers !== false;

	if (!generatedAt && !showPageNumbers) {
		return { bodyClass: '', css: '', html: '' };
	}

	const generatedAtHtml = generatedAt
		? `<span class="pdf-print-meta-footer__generated">Generated: ${generatedAt}</span>`
		: '';

	return {
		bodyClass: 'pdf-has-print-meta-footer',
		css: `
@page { size: ${resolvePaperCssSize(paperSize)}; margin: ${DEFAULT_PRINT_FOOTER_MARGIN}; }
${generatedAtCss ? `@page { @bottom-left { content: "Generated: ${generatedAtCss}"; color: #555; font-size: 8pt; } }` : ''}
${showPageNumbers ? '@page { @bottom-right { content: "Page " counter(page); color: #555; font-size: 8pt; } }' : ''}
body.pdf-has-print-meta-footer {
	display: flex;
	flex-direction: column;
	min-height: calc(100vh - 12mm);
}
.pdf-print-content {
	flex: 1 0 auto;
}
.pdf-print-meta-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12pt;
	margin-top: 8mm;
  padding-top: 2mm;
  border-top: 0.5pt solid #d9d9d9;
  background: #fff;
  color: #555;
  font-size: 8pt;
  line-height: 1.2;
	flex-shrink: 0;
}
.pdf-print-meta-footer__generated {
  white-space: nowrap;
}
@media print {
  body.pdf-has-print-meta-footer {
		display: block;
		min-height: auto;
  }
	.pdf-print-meta-footer {
		display: none;
	}
}
`,
		html: generatedAtHtml ? `<div class="pdf-print-meta-footer">${generatedAtHtml}</div>` : '',
	};
}

/**
 * Renders a Vue component to an HTML string.
 * Mounts it onto a temporary detached div, grabs innerHTML, then unmounts.
 *
 * @param {Component} Component  – Vue SFC / component object
 * @param {Object}    [props]    – props to pass
 * @returns {string}             – rendered inner HTML
 *
 * Usage:
 *   import ObrTemplate from '@/Pages/FundTransactions/Pdf/ObrTemplate.vue';
 *   const html = renderVueTemplate(ObrTemplate, { voucher, scholarDetails });
 */
export function renderVueTemplate(Component, props = {}) {
	const el = document.createElement('div');
	const app = createApp(Component, props);
	app.directive('safe-html', safeHtmlDirective);
	app.mount(el);
	const html = el.innerHTML;
	app.unmount();
	return html;
}

/**
 * usePdfPrint — client-side PDF generation via window.print()
 *
 * Usage:
 *   const { buildHtmlDoc, printHtml } = usePdfPrint();
 *
 *   // Build the full HTML string (for iframe srcdoc or preview):
 *   const doc = buildHtmlDoc(generateOBRContent(voucher, scholars), 'OBR-2024-001');
 *
 *   // Open a new tab and immediately trigger print dialog:
 *   printHtml(generateOBRContent(voucher, scholars), 'OBR-2024-001');
 */
export function usePdfPrint() {
	/**
	 * Assembles a complete HTML document string ready for srcdoc or window.open.
	 * @param {string} bodyHtml  – inner body HTML from a template generator
	 * @param {string} [title]   – <title> / suggested filename
	 * @param {'a4'|'long'} [paperSize] – paper size (default: 'a4')
	 * @param {string} [extraCss] – additional CSS appended to the print document
	 * @param {{ generatedAt?: string, showPageNumbers?: boolean }} [options]
	 * @returns {string}
	 */
	const buildHtmlDoc = (
		bodyHtml,
		title = 'Document',
		paperSize = 'a4',
		extraCss = '',
		options = {},
	) => {
		const footerConfig = buildPrintFooterConfig(paperSize, options);
		const bodyContent = footerConfig.bodyClass
			? `<div class="pdf-print-content">${bodyHtml}</div>${footerConfig.html}`
			: bodyHtml;

		return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>${getPdfCss(paperSize)}
${footerConfig.css}
${extraCss}</style>
</head>
<body class="${footerConfig.bodyClass}">${bodyContent}</body>
</html>`;
	};

	/**
	 * Opens the document in a new tab and triggers the print dialog directly.
	 * @param {string} bodyHtml
	 * @param {string} [title]
	 * @param {'a4'|'long'} [paperSize]
	 * @param {string} [extraCss]
	 * @param {{ generatedAt?: string, showPageNumbers?: boolean }} [options]
	 */
	const printHtml = (bodyHtml, title = 'Document', paperSize = 'a4', extraCss = '', options = {}) => {
		const win = window.open('', '_blank');
		if (!win) {
			alert('Pop-up blocked. Please allow pop-ups for this site and try again.');
			return;
		}
		win.document.write(buildHtmlDoc(bodyHtml, title, paperSize, extraCss, options));
		win.document.close();
		win.onload = () => {
			win.focus();
			win.print();
		};
		setTimeout(() => {
			if (win && !win.closed) {
				win.focus();
				win.print();
			}
		}, 800);
	};

	return { buildHtmlDoc, printHtml };
}
