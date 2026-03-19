# macOS-Inspired Auth Page Styling Guide

Reference implementations:
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/Register.vue`

---

## Overview

Lock-screen style auth pages inspired by macOS Sonoma. Uses **Tailwind CSS for all layout**, with scoped CSS reserved only for **theme colors**, **PrimeVue `:deep()` overrides**, and **dark mode color swaps**.

---

## Tech Stack

- **Vue 3 + Inertia.js** — SPA page components
- **PrimeVue** — Auto-imported via `unplugin-vue-components` + `PrimeVueResolver` (no manual imports needed)
- **Tailwind CSS** — Via `@tailwindcss/vite` plugin; use utility classes for ALL layout/spacing/sizing
- **Dark mode** — Class-based `.dark` on root element, persisted to `localStorage` key `'theme'`

---

## Styling Rules

### DO use Tailwind for:
- Layout: `flex`, `items-center`, `justify-between`, `flex-col`, `relative`, `absolute`, `inset-0`
- Sizing: `w-full`, `w-16`, `h-16`, `h-dvh`, `max-w-[340px]`
- Spacing: `gap-4`, `mb-3.5`, `mt-0.5`, `p-5`, `m-0`, `mb-1`, `mb-7`
- Typography: `text-[22px]`, `font-semibold`, `text-xs`, `text-[13px]`, `text-[11px]`, `tracking-[-0.3px]`
- Borders: `rounded-[14px]`
- Misc: `overflow-hidden`, `antialiased`, `cursor-pointer`, `no-underline`, `object-contain`, `z-[1]`

### DO use scoped CSS for:
- Font-family declarations (SF Pro stack)
- Theme-specific colors (light + dark)
- `filter: drop-shadow()` values
- `backdrop-filter: blur()` values
- PrimeVue `:deep()` component overrides
- Transition/hover color changes
- `.dark` class color overrides

### NEVER use scoped CSS for:
- Flexbox layout (`display: flex`, `align-items`, `justify-content`, `gap`)
- Width/height/sizing (`width: 100%`, `max-width`, `height`)
- Margins/padding (`margin`, `padding`)
- Font size/weight (`font-size`, `font-weight`)
- Positioning (`position`, `z-index`, `inset`)
- Text alignment (`text-align`)
- Overflow (`overflow`)

---

## Template Structure

```
div.mac-login-screen (h-dvh overflow-hidden relative flex items-center justify-center antialiased)
├── div.mac-wallpaper (absolute inset-0)
└── div (relative z-[1] flex flex-col items-center w-full max-w-[340px] p-5)
    ├── Message.mac-status (Login only — status message)
    ├── div (flex flex-col items-center w-full)
    │   ├── div (flex items-center gap-4 mb-3.5) — logo row
    │   │   ├── img.mac-logo (w-16 h-16 object-contain rounded-[14px])
    │   │   └── img.mac-logo (w-16 h-16 object-contain rounded-[14px])
    │   ├── h1.mac-title (text-[22px] font-semibold tracking-[-0.3px] m-0 mb-1 text-center)
    │   ├── p.mac-subtitle (text-[13px] mt-0 mb-7 font-normal)
    │   └── form (w-full flex flex-col items-center gap-2.5)
    │       ├── div (w-full) — field wrapper (repeated per field)
    │       │   ├── IconField.mac-iconfield > InputIcon + InputText.mac-input
    │       │   └── InputError (mt-1 pl-1 text-xs text-[#FF3B30])
    │       ├── div (flex items-center justify-between w-full mt-0.5) — options row
    │       │   ├── label.mac-checkbox-label (flex items-center gap-2 cursor-pointer text-xs)
    │       │   │   └── ToggleSwitch + span (Login only)
    │       │   └── Link.mac-link (text-xs font-medium no-underline)
    │       └── div (flex w-full items-center justify-between mt-2) — submit row
    │           ├── Button.mac-theme-toggle (text rounded, sun/moon icon)
    │           └── Button.mac-submit (rounded, arrow-right icon)
    └── div.mac-footer (mt-10 flex items-center gap-1.5 text-[11px])
        ├── span — "Scholarship Management System"
        ├── span (text-sm leading-none) — "·"
        └── span — "© {year}"
```

---

## Marker Classes (CSS hooks only — no layout)

These classes exist on elements purely as selectors for theme colors and dark mode overrides. **All layout for these elements comes from Tailwind utilities.**

| Class | Purpose |
|---|---|
| `.mac-login-screen` | Font-family declaration |
| `.mac-wallpaper` | Gradient background (light/dark) |
| `.mac-logo` | `filter: drop-shadow()` |
| `.mac-title` | Text color (light/dark) |
| `.mac-subtitle` | Text color (light/dark) |
| `.mac-checkbox-label` | Text color + hover (light/dark) |
| `.mac-link` | Link color + hover (light/dark) |
| `.mac-footer` | Text color (light/dark) |
| `.mac-status` | Status message styling (Login only) |

---

## Color Palette

### Light Theme

| Element | Value |
|---|---|
| Wallpaper gradient | `linear-gradient(160deg, #f5f5f7, #e8e8ed, #f0f0f3, #e5e5ea)` |
| Title | `#1d1d1f` |
| Subtitle / muted text | `rgba(0, 0, 0, 0.45)` |
| Link | `#007AFF` → hover `#0055D4` |
| Footer | `rgba(0, 0, 0, 0.3)` |
| Input background | `rgba(255, 255, 255, 0.7)` |
| Input border | `rgba(0, 0, 0, 0.1)` |
| Input focus ring | `0 0 0 3px rgba(0, 122, 255, 0.15)` |
| Placeholder | `rgba(0, 0, 0, 0.3)` |
| Error text | `#FF3B30` |
| Submit button | `#007AFF` |
| Toggle checked | `#007AFF` |
| Theme toggle icon | `#f5a623` |

### Dark Theme (`.dark` class)

| Element | Value |
|---|---|
| Wallpaper gradient | `linear-gradient(160deg, #1c2028, #222831, #1e242d, #222831)` |
| Title | `#f5f5f7` |
| Subtitle / muted text | `rgba(255, 255, 255, 0.45)` |
| Link | `#0A84FF` → hover `#409CFF` |
| Footer | `rgba(255, 255, 255, 0.25)` |
| Input background | `rgba(255, 255, 255, 0.08)` |
| Input border | `rgba(255, 255, 255, 0.1)` |
| Input focus ring | `0 0 0 3px rgba(10, 132, 255, 0.2)` |
| Placeholder | `rgba(255, 255, 255, 0.25)` |
| Submit button | `#0A84FF` |
| Theme toggle icon | `rgba(255, 255, 255, 0.5)` |

---

## PrimeVue Components Used

| Component | Class Override | Notes |
|---|---|---|
| `InputText` | `.mac-input` | Inside `IconField`; 40px height, 10px radius |
| `IconField` | `.mac-iconfield` | Full width wrapper |
| `InputIcon` | — | Icon color set via `.mac-iconfield .p-inputicon` |
| `Password` | `.mac-password` | `toggleMask`, `:feedback="false"`, `inputClass="mac-input"` |
| `ToggleSwitch` | — | Scaled 0.8, checked color `#007AFF` |
| `Button` (submit) | `.mac-submit` | 36×36 rounded circle, system blue |
| `Button` (theme) | `.mac-theme-toggle` | 36×36 text button, sun/moon icon |
| `Message` | `.mac-status` | Full width, 10px radius, blur backdrop (Login only) |

---

## Dark Mode Implementation

```js
// In <script setup>
const isDark = ref(localStorage.getItem('theme') === 'dark');

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};
```

```html
<!-- Root element toggles .dark class -->
<div class="mac-login-screen ..." :class="{ dark: isDark }">
```

```css
/* All dark overrides use .dark ancestor selector */
.dark .mac-wallpaper { background: ...; }
.dark .mac-title { color: #f5f5f7; }
.dark :deep(.mac-input) { background: ...; }
```

---

## Font Stack

```css
font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
```

Applied via `.mac-login-screen` scoped CSS (only property that can't be a Tailwind utility without config).

---

## Key Design Tokens

- **Input height:** 40px
- **Input border-radius:** 10px
- **Logo border-radius:** 14px (Tailwind: `rounded-[14px]`)
- **Logo size:** 64×64 (Tailwind: `w-16 h-16`)
- **Submit/toggle button:** 36×36 circle
- **Max panel width:** 340px (Tailwind: `max-w-[340px]`)
- **Field gap:** 10px (Tailwind: `gap-2.5`)
- **System blue:** `#007AFF` (light) / `#0A84FF` (dark)
- **Navbar dark color:** `#222831` (used in dark wallpaper gradient)
