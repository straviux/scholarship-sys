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
 * Strip all HTML tags and return plain text. Useful for tooltips and truncated previews.
 */
export function stripHtml(html) {
	if (!html) return '';
	const el = document.createElement('div');
	el.innerHTML = DOMPurify.sanitize(html);
	return el.textContent || el.innerText || '';
}
