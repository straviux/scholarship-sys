import { ref } from 'vue';
import MarkdownIt from 'markdown-it';
import DOMPurify from 'dompurify';

/**
 * Composable for rendering markdown content safely
 *
 * @returns {Object} Object containing renderMarkdown function
 */
export function useMarkdown() {
	// Initialize markdown-it with secure configuration
	const md = new MarkdownIt({
		html: true, // Enable HTML tags in source
		linkify: true, // Auto-convert URL-like text to links
		typographer: true, // Enable smart quotes and other typographic replacements
		breaks: true, // Convert '\n' in paragraphs into <br>
	});

	/**
	 * Render markdown to sanitized HTML
	 *
	 * @param {string} markdownContent - The markdown content to render
	 * @returns {string} Sanitized HTML string
	 */
	const renderMarkdown = (markdownContent) => {
		if (!markdownContent) return '';

		try {
			// Render markdown to HTML
			const htmlContent = md.render(markdownContent);

			// Sanitize the HTML to prevent XSS attacks
			const cleanHtml = DOMPurify.sanitize(htmlContent, {
				ALLOWED_TAGS: [
					'h1',
					'h2',
					'h3',
					'h4',
					'h5',
					'h6',
					'p',
					'br',
					'hr',
					'strong',
					'em',
					'b',
					'i',
					'u',
					's',
					'del',
					'mark',
					'code',
					'pre',
					'ul',
					'ol',
					'li',
					'blockquote',
					'a',
					'img',
					'table',
					'thead',
					'tbody',
					'tr',
					'th',
					'td',
					'div',
					'span',
				],
				ALLOWED_ATTR: [
					'href',
					'src',
					'alt',
					'title',
					'class',
					'id',
					'target',
					'rel',
					'width',
					'height',
				],
				// Ensure links open in new tab and are safe
				ALLOWED_URI_REGEXP:
					/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
			});

			return cleanHtml;
		} catch (error) {
			console.error('Error rendering markdown:', error);
			return markdownContent; // Return original content on error
		}
	};

	return {
		renderMarkdown,
	};
}
