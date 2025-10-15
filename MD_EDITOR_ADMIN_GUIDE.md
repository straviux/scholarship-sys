# MD-Editor-v3 Admin Interface Guide

## Quick Visual Reference

### 1. System Updates Page

```
┌─────────────────────────────────────────────────────────────────┐
│ Admin Layout                                                     │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ System Updates Management                                   │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ System Updates                       [Create Update] ←──────┤ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ System Updates                                              │ │
│ │ ┌───────────────────────────────────────────────────────┐  │ │
│ │ │ 📄 Markdown Test - System Documentation               │  │ │
│ │ │ [NORMAL] [INFO] [Markdown 📝]                         │  │ │
│ │ │ Markdown Test - This is a test notification...        │  │ │
│ │ │ Created Oct 15, 2025 12:00 AM by Admin                │  │ │
│ │ │                              [Active] [Deactivate]    │  │ │
│ │ └───────────────────────────────────────────────────────┘  │ │
│ │ ┌───────────────────────────────────────────────────────┐  │ │
│ │ │ 📄 Welcome to New System                              │  │ │
│ │ │ [NORMAL] [INFO]                                       │  │ │
│ │ │ Welcome to the new scholarship system! We have...     │  │ │
│ │ │ Created Oct 14, 2025 10:30 AM by Admin                │  │ │
│ │ │                              [Active] [Deactivate]    │  │ │
│ │ └───────────────────────────────────────────────────────┘  │ │
│ └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

### 2. Create Update Modal (Plain Text Mode)

```
┌──────────────────────────────────────────────────────────────┐
│ ⓧ Create System Update                                       │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  Title                                                        │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ System Maintenance Notice                              │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                               │
│  ☐ Use Markdown Format                                       │
│  Enable rich text formatting with headings, lists...         │
│                                                               │
│  Content                                                      │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ We will be performing maintenance on the system...     │  │
│  │                                                         │  │
│  │ Please save your work before 10:00 PM.                 │  │
│  │                                                         │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                               │
│  Type              Priority                                   │
│  ┌──────────────┐  ┌──────────────┐                          │
│  │ Info       ▼ │  │ Normal     ▼ │                          │
│  └──────────────┘  └──────────────┘                          │
│                                                               │
│  ☑ Visible to all users                                      │
│                                                               │
├──────────────────────────────────────────────────────────────┤
│                                    [Cancel] [Create Update]  │
└──────────────────────────────────────────────────────────────┘
```

### 3. Create Update Modal (Markdown Mode)

```
┌─────────────────────────────────────────────────────────────────────────┐
│ ⓧ Create System Update                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Title                                                                   │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ System Maintenance Notice                                         │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ☑ Use Markdown Format                                                  │
│  Enable rich text formatting with headings, lists, code blocks...       │
│                                                                          │
│  Markdown Content                                                        │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ ─── MARKDOWN EDITOR ──────────────────────────────────────────────│  │
│  │ [B][I][S] [H▼] [≡] [¶] [<>] [🔗][🖼️][⊞] [↶][↷] [⛶]            │  │
│  │ ┌──────────────────────────┬──────────────────────────────────┐  │  │
│  │ │ EDITOR                   │ PREVIEW                          │  │  │
│  │ ├──────────────────────────┼──────────────────────────────────┤  │  │
│  │ │ # System Maintenance     │ System Maintenance               │  │  │
│  │ │                          │                                  │  │  │
│  │ │ We will perform          │ We will perform scheduled        │  │  │
│  │ │ **scheduled maintenance**│ maintenance on the system.       │  │  │
│  │ │ on the system.           │                                  │  │  │
│  │ │                          │ Details:                         │  │  │
│  │ │ ## Details               │ • Date: October 20, 2025         │  │  │
│  │ │                          │ • Time: 10:00 PM - 12:00 AM      │  │  │
│  │ │ - **Date**: Oct 20, 2025 │ • Duration: 2 hours              │  │  │
│  │ │ - **Time**: 10 PM - 12 AM│                                  │  │  │
│  │ │ - **Duration**: 2 hours  │ Please save your work before the │  │  │
│  │ │                          │ maintenance window.              │  │  │
│  │ │ Please save your work... │                                  │  │  │
│  │ │                          │                                  │  │  │
│  │ └──────────────────────────┴──────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│  Preview on the right shows how the content will appear to users        │
│                                                                          │
│  Type              Priority                                              │
│  ┌──────────────┐  ┌──────────────┐                                     │
│  │ Warning    ▼ │  │ High       ▼ │                                     │
│  └──────────────┘  └──────────────┘                                     │
│                                                                          │
│  ☑ Visible to all users                                                 │
│                                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                           [Cancel] [Create Update]      │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4. Markdown Toolbar Explained

```
┌─────────────────────────────────────────────────────────────────┐
│ Toolbar Buttons:                                                 │
│                                                                  │
│ [B]     - Bold text (Ctrl+B)                                    │
│ [I]     - Italic text (Ctrl+I)                                  │
│ [S]     - Strikethrough (Ctrl+D)                                │
│ [H▼]    - Headings dropdown (H1-H6)                             │
│ [≡]     - Unordered list                                        │
│ [¶]     - Ordered list                                          │
│ [<>]    - Code block with syntax highlighting                  │
│ [🔗]    - Insert link (Ctrl+K)                                  │
│ [🖼️]    - Insert image                                          │
│ [⊞]     - Insert table                                          │
│ [↶]     - Undo (Ctrl+Z)                                         │
│ [↷]     - Redo (Ctrl+Y)                                         │
│ [⛶]     - Fullscreen mode (F11)                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 5. User View (Notification Dropdown)

```
┌─────────────────────────────────────────────┐
│ ⓧ System Maintenance Notice                │
│ ⚠️  System Maintenance Notice              │
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ System Maintenance                      │ │
│ │                                         │ │
│ │ We will perform scheduled maintenance   │ │
│ │ on the system.                          │ │
│ │                                         │ │
│ │ Details:                                │ │
│ │ • Date: October 20, 2025                │ │
│ │ • Time: 10:00 PM - 12:00 AM             │ │
│ │ • Duration: 2 hours                     │ │
│ │                                         │ │
│ │ Please save your work before the        │ │
│ │ maintenance window.                     │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ Priority: High                              │
│ @Admin • Oct 15, 2025 12:00 AM              │
│                                             │
├─────────────────────────────────────────────┤
│                          [Mark as Read]     │
└─────────────────────────────────────────────┘
```

## Common Workflows

### Workflow 1: Creating a Simple Text Notification

1. Click **[Create Update]** button
2. Enter title: "System Update"
3. Leave **Use Markdown Format** unchecked
4. Type content in textarea
5. Select Type and Priority
6. Click **[Create Update]**

**Time**: ~30 seconds  
**Best for**: Quick announcements, simple messages

### Workflow 2: Creating a Rich Markdown Notification

1. Click **[Create Update]** button
2. Enter title: "Feature Release Notes"
3. Check **☑ Use Markdown Format**
4. Markdown editor appears with toolbar
5. Write content using editor or toolbar buttons
6. Preview updates in real-time on the right
7. Select Type and Priority
8. Click **[Create Update]**

**Time**: ~2-5 minutes  
**Best for**: Detailed announcements, documentation, feature releases

### Workflow 3: Converting Plain Text to Markdown

1. Click **[Create Update]** button
2. Enter title and plain text content first
3. Check **☑ Use Markdown Format**
4. Content automatically copied to markdown editor
5. Enhance with markdown formatting
6. Click **[Create Update]**

**Time**: ~1-3 minutes  
**Best for**: Starting with draft, then enhancing

## Keyboard Shortcuts Quick Reference

### Text Formatting

```
Ctrl/Cmd + B     Bold selected text
Ctrl/Cmd + I     Italic selected text
Ctrl/Cmd + D     Strikethrough
```

### Elements

```
Ctrl/Cmd + K     Insert link
Ctrl/Cmd + Q     Blockquote
Ctrl/Cmd + Shift + C    Code block
```

### Editor Actions

```
Ctrl/Cmd + Z     Undo
Ctrl/Cmd + Y     Redo
Ctrl/Cmd + S     Submit form
F11              Fullscreen toggle
Esc              Exit fullscreen
```

### Navigation

```
Tab              Insert 2 spaces
Shift + Tab      Remove indentation
Ctrl/Cmd + F     Find in editor
```

## Tips & Tricks

### 💡 Tip 1: Quick Headings

Type `#` followed by space for H1, `##` for H2, etc.

### 💡 Tip 2: Quick Lists

Type `-` or `*` followed by space for bullet list  
Type `1.` followed by space for numbered list

### 💡 Tip 3: Quick Code

Type ` ``` ` on a new line, press Enter, then type language name for syntax highlighted code block

### 💡 Tip 4: Preview Toggle

Press fullscreen button to focus on writing, then preview to see rendered output

### 💡 Tip 5: Content Preservation

Toggle between markdown and plain text modes freely - content is preserved

### 💡 Tip 6: Copy-Paste from Docs

Markdown content copied from GitHub, Stack Overflow, etc. can be pasted directly

## Mobile Responsiveness

### Desktop (>960px)

- Full split-view editor and preview side-by-side
- All toolbar buttons visible
- Optimal editing experience

### Tablet (640px - 960px)

- Modal takes 90% viewport width
- Split view maintained but narrower
- Some toolbar buttons may wrap

### Mobile (<640px)

- Modal takes 95% viewport width
- Editor and preview may stack vertically
- Toolbar may collapse to dropdown menu
- Still fully functional

## Accessibility Features

### Keyboard Navigation

- All toolbar buttons accessible via keyboard
- Tab through form fields in logical order
- Enter to submit, Esc to close modal

### Screen Reader Support

- Proper ARIA labels on all controls
- Semantic HTML in rendered markdown
- Alt text required for images

### High Contrast

- Editor supports system theme preferences
- Preview renders with good contrast ratios
- Focus indicators visible

## Error States

### Empty Content

```
┌─────────────────────────────────────┐
│ ⚠️  Please enter content            │
└─────────────────────────────────────┘
```

### Network Error

```
┌─────────────────────────────────────┐
│ ❌ Failed to create notification    │
│    Please try again                 │
└─────────────────────────────────────┘
```

### Validation Error

```
┌─────────────────────────────────────┐
│ ⚠️  Title is required                │
└─────────────────────────────────────┘
```

## Summary

The md-editor-v3 integration provides a professional, intuitive interface for creating rich markdown notifications. The live preview ensures administrators can see exactly how content will appear to users, while the familiar toolbar makes markdown accessible even to non-technical users.

---

**Last Updated**: October 15, 2025  
**Interface Version**: 1.0  
**Supported Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
