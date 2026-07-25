import DOMPurify from 'dompurify';

/**
 * Sanitize user-generated HTML (Quill output, remarks, markdown renders).
 * Strips <script>, event handlers, and javascript: URLs while keeping safe formatting tags.
 */
export function sanitizeHtml(dirty) {
	if (!dirty) return '';
	// Normalize broken non-breaking-space entities from legacy data so they
	// render as spaces instead of literal text: "&amp;nbsp;" (double-encoded)
	// and "&NBSP;" (entities are case-sensitive; uppercase is invalid).
	const normalized = String(dirty).replace(/&(amp;)?nbsp;/gi, '&nbsp;');
	return DOMPurify.sanitize(normalized, {
		USE_PROFILES: { html: true, svg: true, svgFilters: true },
	});
}

export function normalizeDocumentHtml(dirty) {
	return sanitizeHtml(dirty);
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
