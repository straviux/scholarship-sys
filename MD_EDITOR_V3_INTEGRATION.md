# MD-Editor-v3 Integration Documentation

## Overview

This document details the integration of **md-editor-v3**, a powerful Vue 3 markdown editor with live preview, into the System Updates admin interface.

## What is md-editor-v3?

md-editor-v3 is a feature-rich markdown editor for Vue 3 that provides:

- ✅ **Live Preview**: Real-time markdown rendering side-by-side
- ✅ **WYSIWYG Toolbar**: Easy-to-use formatting buttons
- ✅ **Syntax Highlighting**: Support for 100+ programming languages
- ✅ **Theme Support**: Multiple preview themes (GitHub, VS Code, etc.)
- ✅ **Mobile Responsive**: Works on all screen sizes
- ✅ **Extensible**: Custom toolbars and plugins

## Installation

### Package Installation

```bash
npm install md-editor-v3 --legacy-peer-deps
```

**Package Details:**

- Package: `md-editor-v3` (v4.x.x)
- Dependencies: Includes CodeMirror for code highlighting
- Size Impact: ~793 KB (minified), ~258 KB (gzipped)
- License: MIT

## Implementation

### 1. Component Integration

**File**: `resources/js/Pages/Admin/SystemUpdates.vue`

#### Imports Added

```vue
<script setup>
	import { MdEditor } from 'md-editor-v3';
	import 'md-editor-v3/lib/style.css';
</script>
```

#### Form Data Structure Updated

```javascript
const form = ref({
	title: '',
	content: '', // Plain text content
	markdown_content: '', // Markdown formatted content
	is_markdown: false, // Toggle flag
	type: 'info',
	priority: 'normal',
	is_global: true,
});
```

### 2. UI Components

#### Markdown Toggle Checkbox

```vue
<div>
    <label class="flex items-center">
        <Checkbox v-model="form.is_markdown" binary @change="onMarkdownToggle" />
        <span class="ml-2 text-sm text-gray-700">Use Markdown Format</span>
    </label>
    <p class="text-xs text-gray-500 mt-1">
        Enable rich text formatting with headings, lists, code blocks, and more
    </p>
</div>
```

#### Markdown Editor Component

```vue
<div v-if="form.is_markdown">
    <label class="block text-sm font-medium text-gray-700 mb-2">Markdown Content</label>
    <MdEditor 
        v-model="form.markdown_content" 
        :language="'en-US'"
        :preview-theme="'github'"
        :code-theme="'github'"
        :toolbars-exclude="['github', 'save', 'htmlPreview', 'catalog']"
        :placeholder="'Enter your markdown content here...'"
        style="height: 400px;"
    />
    <p class="text-xs text-gray-500 mt-1">
        Preview on the right shows how the content will appear to users
    </p>
</div>
```

#### Plain Text Textarea (Fallback)

```vue
<div v-else>
    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
    <Textarea v-model="form.content" required rows="6" class="w-full"
        placeholder="Enter update content" />
</div>
```

### 3. Logic Implementation

#### Toggle Handler

```javascript
const onMarkdownToggle = () => {
	// When toggling markdown mode, preserve content between formats
	if (form.value.is_markdown) {
		// Switching to markdown: copy content to markdown_content if empty
		if (!form.value.markdown_content && form.value.content) {
			form.value.markdown_content = form.value.content;
		}
	} else {
		// Switching to plain text: copy markdown_content to content if empty
		if (!form.value.content && form.value.markdown_content) {
			form.value.content = form.value.markdown_content;
		}
	}
};
```

#### Create Update Method

```javascript
const createUpdate = async () => {
	// Validate required fields
	if (!form.value.title) return;

	// Validate content based on markdown mode
	if (form.value.is_markdown && !form.value.markdown_content) return;
	if (!form.value.is_markdown && !form.value.content) return;

	isCreating.value = true;
	try {
		// Prepare payload based on markdown mode
		const payload = {
			title: form.value.title,
			type: form.value.type,
			priority: form.value.priority,
			is_global: form.value.is_global,
			is_markdown: form.value.is_markdown,
		};

		if (form.value.is_markdown) {
			payload.markdown_content = form.value.markdown_content;
			// Optionally provide a plain text summary
			payload.content = form.value.content || form.value.markdown_content.substring(0, 200);
		} else {
			payload.content = form.value.content;
		}

		await axios.post('/api/system-updates', payload);
		showCreateModal.value = false;

		// Reset form
		form.value = {
			title: '',
			content: '',
			markdown_content: '',
			is_markdown: false,
			type: 'info',
			priority: 'normal',
			is_global: true,
		};

		await fetchUpdates();
	} catch (error) {
		console.error('Error creating update:', error);
	} finally {
		isCreating.value = false;
	}
};
```

### 4. UI Enhancements

#### Modal Width Increased

Changed dialog width from `32rem` to `56rem` to accommodate the editor:

```vue
<Dialog v-model:visible="showCreateModal" modal header="Create System Update"
    :style="{ width: '56rem' }"
    :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
    class="create-update-dialog">
```

#### Markdown Badge Added

Shows which notifications are markdown-formatted:

```vue
<Tag
	v-if="update.is_markdown"
	value="Markdown"
	severity="info"
	icon="pi pi-file-edit"
	class="text-xs"
/>
```

## Editor Configuration

### Language Settings

```javascript
:language="'en-US'"  // Interface language
```

### Preview Theme

```javascript
:preview-theme="'github'"  // Options: github, vuepress, mk-cute, etc.
```

### Code Highlighting Theme

```javascript
:code-theme="'github'"  // Syntax highlighting for code blocks
```

### Toolbar Customization

```javascript
:toolbars-exclude="['github', 'save', 'htmlPreview', 'catalog']"
```

**Excluded Toolbars:**

- `github`: GitHub-specific features
- `save`: Save button (handled by form submit)
- `htmlPreview`: Raw HTML preview (not needed)
- `catalog`: Table of contents (for long documents)

**Included Toolbars (Default):**

- Bold, Italic, Strikethrough
- Headings (H1-H6)
- Lists (ordered/unordered)
- Code (inline and blocks)
- Links, Images
- Blockquote, Table
- Horizontal Rule
- Undo/Redo
- Fullscreen mode

### Editor Height

```javascript
style = 'height: 400px;'; // Provides comfortable editing space
```

## Features

### 1. Live Preview

- **Split View**: Editor on left, preview on right
- **Real-time Rendering**: See changes as you type
- **Synchronized Scrolling**: Preview scrolls with editor

### 2. Toolbar Actions

- **Formatting**: Click buttons to insert markdown syntax
- **Shortcuts**: Keyboard shortcuts for common actions
- **Insert Elements**: Quick insertion of tables, code blocks, etc.

### 3. Content Preservation

- **Toggle Safety**: Content preserved when switching between modes
- **Auto-copy**: Plain text copied to markdown (and vice versa) when switching
- **No Data Loss**: Original content maintained

### 4. Responsive Design

- **Desktop**: Full split-view editor
- **Tablet**: Adjustable split or tabbed view
- **Mobile**: Stackable editor and preview

## Usage Guide

### Creating a Markdown Notification

1. **Open Create Dialog**

   - Click "Create Update" button in System Updates page

2. **Enable Markdown Mode**

   - Check "Use Markdown Format" checkbox
   - Editor appears with toolbar and live preview

3. **Write Content**

   - Type markdown in left pane
   - See live preview on right pane
   - Use toolbar buttons for formatting

4. **Use Toolbar Features**

   - **Bold**: Select text → Click B button or Ctrl+B
   - **Heading**: Click H dropdown → Select level
   - **List**: Click list button → Start typing
   - **Code Block**: Click code button → Select language
   - **Link**: Click link button → Enter URL and text
   - **Image**: Click image button → Enter URL and alt text
   - **Table**: Click table button → Configure rows/columns

5. **Submit**
   - Fill in title, type, and priority
   - Click "Create Update" button
   - Notification saved with markdown formatting

### Example Workflow

````markdown
# Welcome to the New System

We're excited to announce **major improvements**!

## Key Features

### 1. Enhanced Performance

Our system is now **50% faster** with optimized queries.

### 2. New UI Components

Beautiful new interface elements:

- Improved navigation
- Better forms
- Responsive design

### 3. Code Examples

Here's how to use the new API:

```javascript
const response = await fetch('/api/v2/users');
const data = await response.json();
console.log(data);
```
````

## What's Next?

Visit our [documentation](https://example.com/docs) for more details.

---

Thank you for your continued support!

````

## Editor Shortcuts

### Text Formatting
- **Bold**: Ctrl/Cmd + B
- **Italic**: Ctrl/Cmd + I
- **Strikethrough**: Ctrl/Cmd + D

### Elements
- **Link**: Ctrl/Cmd + K
- **Code Block**: Ctrl/Cmd + Shift + C
- **Quote**: Ctrl/Cmd + Q

### Actions
- **Undo**: Ctrl/Cmd + Z
- **Redo**: Ctrl/Cmd + Y
- **Save**: Ctrl/Cmd + S (triggers form submit)
- **Fullscreen**: F11 or fullscreen button

## Advanced Configuration

### Custom Toolbars
To add/remove specific toolbar items:
```vue
<MdEditor
    :toolbars="[
        'bold', 'italic', 'strikethrough',
        'title', 'sub', 'sup',
        'quote', 'unorderedList', 'orderedList',
        'codeRow', 'code',
        'link', 'image', 'table'
    ]"
/>
````

### Theme Customization

Available preview themes:

- `default`
- `github` (current)
- `vuepress`
- `mk-cute`
- `smart-blue`
- `cyanosis`

### Code Highlighting Themes

Available code themes:

- `github` (current)
- `atom`
- `gradient`
- `kimbie`
- `a11y`

### Enable Image Upload

```vue
<MdEditor @on-upload-img="handleImageUpload" />
```

```javascript
const handleImageUpload = async (files, callback) => {
	const formData = new FormData();
	formData.append('image', files[0]);

	const response = await axios.post('/api/upload-image', formData);
	callback([response.data.url]);
};
```

## Troubleshooting

### Editor not showing

**Issue**: Editor doesn't render in the dialog

**Solution**:

- Check console for import errors
- Ensure CSS is imported: `import 'md-editor-v3/lib/style.css'`
- Verify v-model binding: `v-model="form.markdown_content"`

### Preview not updating

**Issue**: Live preview doesn't reflect changes

**Solution**:

- Check that `markdown_content` is reactive
- Ensure editor has sufficient height (min 300px)
- Verify no CSS conflicts with PrimeVue

### Build size too large

**Issue**: Bundle size increased significantly

**Solution**:

- md-editor-v3 is large (~793 KB minified)
- Consider lazy loading: Only load when markdown mode is enabled
- Gzipped size is acceptable (~258 KB)

### Styling conflicts

**Issue**: Editor styles clash with existing UI

**Solution**:

- md-editor-v3 CSS is scoped and well-isolated
- Adjust dialog width if needed
- Use `:deep()` for specific style overrides

## Performance Considerations

### Bundle Size Impact

- **Before**: 608 KB (app.js)
- **After**: 793 KB (SystemUpdates.js)
- **Increase**: +185 KB (minified), +79 KB (gzipped)
- **Impact**: Acceptable for admin-only feature

### Loading Strategy

Current: Bundled with SystemUpdates page (admin only)
Future optimization: Lazy load when markdown mode enabled

### Optimization Tips

1. **Lazy Loading**:

   ```javascript
   const MdEditor = defineAsyncComponent(() => import('md-editor-v3').then((m) => m.MdEditor));
   ```

2. **Code Splitting**:
   Configure vite to split editor into separate chunk

3. **Conditional Import**:
   Only import CSS when editor is needed

## Best Practices

### Content Management

✅ **DO:**

- Enable markdown for complex, formatted announcements
- Use plain text for simple one-line updates
- Preview content before publishing
- Test on different screen sizes

❌ **DON'T:**

- Use markdown for emergency notifications (keep simple)
- Forget to fill in plain text summary
- Over-complicate formatting
- Use very long documents (performance impact)

### Accessibility

- Provide meaningful alt text for images
- Use proper heading hierarchy (H1 → H2 → H3)
- Ensure sufficient color contrast in content
- Test with screen readers

### Security

- DOMPurify sanitizes all output (from useMarkdown composable)
- md-editor-v3 doesn't execute arbitrary code
- Image URLs are validated before insertion
- XSS protection is maintained

## Comparison: md-editor-v3 vs Alternatives

| Feature           | md-editor-v3    | TinyMCE         | Quill           | Plain Textarea |
| ----------------- | --------------- | --------------- | --------------- | -------------- |
| Vue 3 Support     | ✅ Native       | ⚠️ Wrapper      | ⚠️ Wrapper      | ✅ Native      |
| Live Preview      | ✅ Yes          | ❌ No           | ❌ No           | ❌ No          |
| Markdown Native   | ✅ Yes          | ❌ HTML         | ❌ HTML         | ⚠️ Manual      |
| Bundle Size       | 793 KB          | ~900 KB         | ~450 KB         | Minimal        |
| Code Highlighting | ✅ 100+ langs   | ⚠️ Plugin       | ⚠️ Plugin       | ❌ No          |
| Toolbar           | ✅ Customizable | ✅ Customizable | ✅ Customizable | ❌ None        |
| License           | MIT (Free)      | Commercial      | BSD (Free)      | N/A            |
| Learning Curve    | Low             | High            | Medium          | N/A            |

**Why md-editor-v3?**

- Best Vue 3 integration
- Native markdown support (matches our backend)
- Live preview essential for content creators
- Open source and actively maintained
- No licensing costs

## Future Enhancements

### Planned Features

1. **Image Upload**: Direct image upload to server
2. **Template Library**: Pre-made notification templates
3. **Version History**: Track changes to notifications
4. **Draft System**: Save drafts before publishing
5. **Rich Embeds**: Support for videos, tweets, etc.

### Advanced Features

1. **Collaborative Editing**: Multiple admins editing simultaneously
2. **Spell Check**: Built-in grammar and spell checking
3. **Emoji Picker**: Visual emoji selector
4. **Diagram Support**: Mermaid/PlantUML diagrams
5. **Math Equations**: LaTeX/MathJax support

## Resources

### Official Documentation

- [md-editor-v3 GitHub](https://github.com/imzbf/md-editor-v3)
- [Online Demo](https://imzbf.github.io/md-editor-v3/)
- [API Reference](https://imzbf.github.io/md-editor-v3/en-US/index)

### Markdown Guides

- [Markdown Guide](https://www.markdownguide.org/)
- [GitHub Flavored Markdown](https://github.github.com/gfm/)
- [CommonMark Spec](https://commonmark.org/)

### Vue 3 Resources

- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [PrimeVue Components](https://primevue.org/)

---

**Implementation Date**: October 15, 2025  
**Build Status**: ✅ Success (25.99s)  
**Package Version**: md-editor-v3 v4.x.x  
**Bundle Impact**: +185 KB (minified), +79 KB (gzipped)

## Summary

md-editor-v3 provides a professional, user-friendly markdown editing experience for creating rich system notifications. The integration maintains backward compatibility while offering powerful formatting capabilities for administrators who need to create complex, well-formatted announcements.

The live preview feature ensures content creators can see exactly how their notifications will appear to end users, reducing errors and improving content quality.
