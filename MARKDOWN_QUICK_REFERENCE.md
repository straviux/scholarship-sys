# Markdown System - Quick Reference Guide

## Overview

The notification system now supports **markdown formatting** for rich text content. This allows you to create beautifully formatted system updates with headings, lists, code blocks, links, and more!

## How It Works

### For Administrators

When creating a system update, you can now:

1. Set `is_markdown` to `true`
2. Add markdown-formatted content to `markdown_content` field
3. Optionally add plain text summary to `content` field (for preview)

### For Users

- Markdown notifications appear in the notification dropdown like normal notifications
- When you click to view the full notification, the markdown is rendered with proper formatting
- Plain text notifications continue to work exactly as before

## Markdown Syntax Reference

### Headings

```markdown
# Heading 1

## Heading 2

### Heading 3

#### Heading 4

##### Heading 5

###### Heading 6
```

### Text Formatting

```markdown
**Bold text**
_Italic text_
**_Bold and italic_**
~~Strikethrough~~
`inline code`
```

### Lists

#### Unordered Lists

```markdown
- Item 1
- Item 2
  - Sub-item 2.1
  - Sub-item 2.2
- Item 3
```

#### Ordered Lists

```markdown
1. First item
2. Second item
3. Third item
```

### Links

```markdown
[Link Text](https://example.com)
[Link with Title](https://example.com 'This is a tooltip')
```

### Images

```markdown
![Alt Text](https://example.com/image.jpg)
![Image with Title](https://example.com/image.jpg 'Image caption')
```

### Code Blocks

#### Inline Code

```markdown
Use `const variable = "value"` for inline code.
```

#### Multi-line Code Blocks

````markdown
```javascript
const greeting = 'Hello, World!';
console.log(greeting);
```
````

### Blockquotes

```markdown
> This is a blockquote.
> It can span multiple lines.
>
> And have multiple paragraphs.
```

### Horizontal Rules

```markdown
---

---

---
```

### Tables

```markdown
| Column 1 | Column 2 | Column 3 |
| -------- | -------- | -------- |
| Data 1   | Data 2   | Data 3   |
| Data 4   | Data 5   | Data 6   |
```

## Example Notifications

### Example 1: Simple Announcement

```markdown
# System Maintenance

We will perform **scheduled maintenance** on the system.

## Details

- **Date**: October 20, 2025
- **Time**: 10:00 PM - 12:00 AM
- **Duration**: 2 hours

Please save your work before the maintenance window.
```

### Example 2: Feature Update

```markdown
# New Feature: Batch Export

We're excited to announce a new **batch export** feature!

## Key Features

1. Export multiple reports at once
2. Schedule exports for later
3. Email delivery of completed exports

Visit our [help center](https://example.com/help) for more information.
```

### Example 3: Technical Notice

````markdown
# API Update Notice

The scholarship API has been updated to version 2.0.

## Breaking Changes

### Authentication

The authentication endpoint has changed:

```javascript
// Old (deprecated)
POST / api / auth / login;

// New
POST / api / v2 / auth / login;
```
````

### Response Format

All API responses now include a `version` field:

```json
{
  "version": "2.0",
  "data": { ... }
}
```

> **Important**: Please update your integrations by **October 31, 2025**.

For assistance, contact the IT team.

````

### Example 4: Documentation with Images
```markdown
# User Guide: Profile Update

Follow these steps to update your profile:

## Step 1: Navigate to Profile
Click the **Profile** icon in the top right corner.

![Profile Icon](https://example.com/images/profile-icon.png)

## Step 2: Edit Information
1. Click the **Edit** button
2. Update your information
3. Click **Save**

For more help, see our [video tutorial](https://example.com/tutorials/profile-update).
````

## Creating Markdown Notifications

### Via API

```javascript
// Create a markdown notification
axios.post('/api/system-updates', {
	title: 'System Update',
	content: 'Short plain text summary (optional)',
	markdown_content: '# Full Markdown Content\n\nThis is **markdown**!',
	is_markdown: true,
	type: 'info',
	priority: 'normal',
	is_global: true,
});
```

### Via Database Seeder

```php
SystemUpdate::create([
    'title' => 'Update Title',
    'content' => 'Plain text summary',
    'markdown_content' => <<<'MARKDOWN'
# Heading

Your markdown content here...
MARKDOWN,
    'is_markdown' => true,
    'type' => 'info',
    'priority' => 'normal',
    'is_global' => true,
    'created_by' => auth()->id()
]);
```

### Via Tinker

```bash
php artisan tinker
```

```php
\App\Models\SystemUpdate::create([
    'title' => 'Test Notification',
    'markdown_content' => "# Hello\n\nThis is **markdown**!",
    'is_markdown' => true,
    'type' => 'info',
    'priority' => 'normal',
    'is_global' => true,
    'created_by' => 1
]);
```

## Best Practices

### ✅ DO

- Use headings to organize content hierarchically
- Keep code examples properly formatted in code blocks
- Provide plain text `content` as a summary for the notification list
- Use links to reference external documentation
- Test markdown rendering before sending to all users

### ❌ DON'T

- Don't use HTML directly (it will be sanitized for security)
- Don't create overly complex nested structures
- Don't use markdown for simple one-line messages
- Don't forget to set `is_markdown: true`
- Don't include sensitive information in code examples

## Security Features

All markdown is:

1. **Sanitized** using DOMPurify to prevent XSS attacks
2. **Validated** to only allow safe HTML tags
3. **Filtered** to remove potentially harmful attributes
4. **Tested** for URI safety in links

You can safely use markdown without worrying about security vulnerabilities!

## Styling Preview

When rendered, your markdown will have:

- Professional typography with proper spacing
- Syntax-highlighted code blocks (dark theme)
- Styled blockquotes with blue left border
- Responsive tables with alternating row colors
- Hover effects on links
- Proper heading hierarchy with bottom borders
- Responsive images

## Testing Your Markdown

### Quick Test

Use the provided seeder to see a full example:

```bash
php artisan db:seed --class=MarkdownTestSeeder
```

### Custom Test

Create your own test notification:

```bash
php artisan tinker
```

Then paste your markdown and create a notification.

## Troubleshooting

### Markdown not rendering?

- Check that `is_markdown` is set to `true`
- Verify `markdown_content` field is not null or empty
- Check browser console for JavaScript errors

### Missing styles?

- Run `npm run build` to rebuild assets
- Clear browser cache
- Check that CSS is loaded properly

### Links not working?

- Ensure URLs are properly formatted
- Use full URLs (https://example.com) not relative paths
- Check that link text is between `[` and `]`

## Future Features (Planned)

- 🎨 **Markdown Editor**: Visual editor in admin panel
- 👁️ **Live Preview**: See rendered output while typing
- 📝 **Templates**: Pre-made markdown templates
- 📷 **Image Upload**: Upload images directly
- 🎨 **Syntax Highlighting**: Color-coded code blocks
- 😊 **Emoji Support**: Use `:smile:` shortcodes
- 📊 **Diagrams**: Support for mermaid diagrams

## Resources

### Markdown Guides

- [Markdown Guide](https://www.markdownguide.org/)
- [GitHub Markdown](https://guides.github.com/features/mastering-markdown/)
- [Markdown Cheatsheet](https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet)

### Tools

- [StackEdit](https://stackedit.io/) - Online markdown editor
- [Dillinger](https://dillinger.io/) - Another online editor
- [Markdown Tables Generator](https://www.tablesgenerator.com/markdown_tables)

---

**Happy Markdown Writing! 📝**

For questions or issues, contact the development team.
