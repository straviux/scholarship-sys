import DOMPurify from 'dompurify';

/**
 * Sanitize user-generated HTML (Quill output, remarks, markdown renders).
 * Strips <script>, event handlers, and javascript: URLs while keeping safe formatting tags.
 */
export function sanitizeHtml(dirty) {
	if (!dirty) return '';
	return DOMPurify.sanitize(dirty, {
		USE_PROFILES: { html: true, svg: true, svgFilters: true },
	});
}

/**
 * Normalize rich-text HTML for printable government documents.
 * Keeps structure/formatting but removes link and text-color styling so
 * rendered output falls back to the document's default text color.
 */
export function normalizeDocumentHtml(html) {
	if (!html) return '';

	const sanitizedHtml = sanitizeHtml(html);

	if (typeof document === 'undefined') {
		return sanitizedHtml;
	}

	const container = document.createElement('div');
	container.innerHTML = sanitizedHtml;

	container.querySelectorAll('*').forEach((element) => {
		if (element.tagName === 'A') {
			element.removeAttribute('href');
			element.removeAttribute('target');
			element.removeAttribute('rel');
		}

		element.removeAttribute('color');

		if (element instanceof HTMLElement) {
			element.style.removeProperty('color');
			element.style.removeProperty('text-decoration-color');
			element.style.removeProperty('caret-color');

			if (!element.getAttribute('style')?.trim()) {
				element.removeAttribute('style');
			}
		}

		Array.from(element.classList).forEach((className) => {
			if (/^ql-color-/i.test(className)) {
				element.classList.remove(className);
			}
		});

		if (!element.getAttribute('class')?.trim()) {
			element.removeAttribute('class');
		}
	});

	return container.innerHTML;
}

/**
 * Strip all HTML tags and return plain text. Useful for tooltips and truncated previews.
 */
export function stripHtml(html) {
	if (!html) return '';
	const el = document.createElement('div');
	el.innerHTML = DOMPurify.sanitize(html);
	return el.textContent || el.innerText || '';
}
