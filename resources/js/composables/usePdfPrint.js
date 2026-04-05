import { getPdfCss } from '@/Pages/FundTransactions/Pdf/pdf-styles.js';
import { createApp } from 'vue';
import safeHtmlDirective from '@/directives/safeHtml';

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
	 * @returns {string}
	 */
	const buildHtmlDoc = (bodyHtml, title = 'Document', paperSize = 'a4') => `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>${getPdfCss(paperSize)}</style>
</head>
<body>${bodyHtml}</body>
</html>`;

	/**
	 * Opens the document in a new tab and triggers the print dialog directly.
	 * @param {string} bodyHtml
	 * @param {string} [title]
	 * @param {'a4'|'long'} [paperSize]
	 */
	const printHtml = (bodyHtml, title = 'Document', paperSize = 'a4') => {
		const win = window.open('', '_blank');
		if (!win) {
			alert('Pop-up blocked. Please allow pop-ups for this site and try again.');
			return;
		}
		win.document.write(buildHtmlDoc(bodyHtml, title, paperSize));
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
