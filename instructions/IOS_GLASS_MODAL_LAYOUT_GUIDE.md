# iOS Glass Modal Layout Guide

Reference implementation: `resources/js/Pages/InterviewedApplicants/Modal/GenerateReportModalIOS.vue`

## Overview

A frosted-glass modal inspired by iOS Settings, built on **PrimeVue Dialog** in headless `#container` mode with custom pointer-based drag. Uses monochrome minimal button styling and translucent surfaces.

---

## Template Structure

```
Dialog (headless #container mode)
└── .ios-modal (glass shell, draggable via transform)
    ├── .ios-nav-bar (drag handle)
    │   ├── button.ios-nav-btn.ios-nav-cancel (× icon, left)
    │   ├── span.ios-nav-title (centered title)
    │   └── button.ios-nav-btn.ios-nav-action (✓ icon, right)
    └── .ios-body (scrollable content)
        ├── .ios-section
        │   ├── .ios-section-label (uppercase heading)
        │   └── .ios-segmented-control (tab toggle)
        │       └── button.ios-segment (.ios-segment-active)
        ├── .ios-section (filters)
        │   ├── .ios-section-label
        │   └── .ios-card (grouped card)
        │       └── .ios-row (repeating)
        │           ├── .ios-row-label (icon + text)
        │           └── .ios-row-control (fixed-width input)
        ├── .ios-section (date range, same card pattern)
        │   └── .ios-section-footer.ios-error (conditional)
        ├── .ios-section (options, same card pattern)
        │   └── .ios-section-footer (helper text)
        ├── .ios-section (preview card)
        │   └── .ios-card.ios-preview-card
        │       └── .ios-preview-row (label + value pairs)
        └── .ios-section (destructive action)
            └── button.ios-destructive-btn
```

---

## PrimeVue Dialog Setup

```vue
<Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
    :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
    <template #container>
        <div class="ios-modal" :style="modalStyle">
            <!-- content here -->
        </div>
    </template>
</Dialog>
```

**Key points:**
- Use `#container` (headless) slot — PrimeVue renders no chrome
- PT classes `ios-dialog-root` and `ios-dialog-mask` must be styled in an **unscoped** `<style>` block because Dialog teleports to `<body>`
- `modalStyle` is a computed that includes width and `transform: translate(x, y)` for drag

### Unscoped Style Block (required)

```css
<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
</style>
```

---

## Navigation Bar

```vue
<div class="ios-nav-bar" @pointerdown="onDragStart">
    <button class="ios-nav-btn ios-nav-cancel" @click="close" v-tooltip.bottom="'Close'">
        <i class="pi pi-times"></i>
    </button>
    <span class="ios-nav-title">Modal Title</span>
    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="isInvalid"
        v-tooltip.bottom="'Submit'">
        <i class="pi pi-check"></i>
    </button>
</div>
```

- Icon buttons with PrimeVue `v-tooltip` (not native `title`)
- Nav bar doubles as drag handle via `@pointerdown`
- Monochrome colors: cancel `#6B7280`, action `#374151`, disabled `#C7C7CC`

---

## Section Pattern

```vue
<div class="ios-section">
    <div class="ios-section-label">Section Title</div>
    <div class="ios-card">
        <div class="ios-row">
            <div class="ios-row-label">
                <i class="pi pi-icon-name" style="color: #HEX; font-size: 13px;"></i>
                Label
            </div>
            <div class="ios-row-control">
                <!-- Select, DatePicker, ToggleSwitch, etc. -->
            </div>
        </div>
        <!-- more .ios-row -->
    </div>
    <div class="ios-section-footer">Optional helper text</div>
</div>
```

### Row Icon Color Palette
| Purpose | Color |
|---|---|
| Star / highlight | `#FF9500` |
| Primary / link | `#007AFF` |
| Success | `#34C759` |
| Purple accent | `#AF52DE` |
| Danger / date | `#FF3B30` |
| Muted / neutral | `#8E8E93` |
| Indigo | `#5856D6` |

---

## Segmented Control

```vue
<div class="ios-segmented-control">
    <button :class="['ios-segment', active && 'ios-segment-active']" @click="...">
        <i class="pi pi-icon" style="font-size: 13px;"></i>
        Label
    </button>
</div>
```

---

## Key CSS Classes

### Glass Surfaces

| Class | Background | Notes |
|---|---|---|
| `.ios-modal` | `rgba(242,242,247,0.72)` | 40px blur, main shell |
| `.ios-nav-bar` | `rgba(255,255,255,0.45)` | Translucent header |
| `.ios-card` | `rgba(255,255,255,0.55)` | Grouped card, 0.5px white border |
| `.ios-segment-active` | `rgba(255,255,255,0.7)` | Active tab |
| `.ios-destructive-btn` | `rgba(255,255,255,0.45)` | Danger action |

### Layout

| Class | Behavior |
|---|---|
| `.ios-body` | `flex: 1; overflow-y: auto; padding: 0 16px` |
| `.ios-section` | `margin-top: 22px` (first: 16px) |
| `.ios-row` | `display: flex; min-height: 36px; padding: 4px 16px` |
| `.ios-row-label` | `flex-shrink: 0; font-size: 14px; gap: 8px` |
| `.ios-row-control` | `flex: 0 0 200px; width: 200px` (fixed width, all inputs same size) |
| `.ios-row-control > *` | `width: 100%; min-width: 0` (fills wrapper components) |

### Input Overrides (scoped `:deep()`)

- `.ios-select .p-select` — transparent, no border, 13px font, left-aligned
- `.ios-select .p-select-label` — ellipsis truncation, left-aligned
- `.ios-datepicker .p-inputtext` — transparent, right-aligned, 13px
- Add `class="ios-select"` to any Select/custom select component
- Add `class="ios-datepicker"` to any DatePicker

---

## Custom Drag Implementation

Required because PrimeVue Dialog's built-in `:draggable` doesn't work in headless `#container` mode.

```js
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const modalStyle = computed(() => ({
    width: '600px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button')) return; // don't drag on button clicks
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}

function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}

function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}

// Clean up on unmount
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
```

---

## Checklist for New iOS Glass Modals

1. Use PrimeVue `<Dialog>` with `#container` slot and PT classes
2. Add **unscoped** `<style>` block for `.ios-dialog-root` and `.ios-dialog-mask`
3. Implement pointer-based drag on `.ios-nav-bar`
4. Use `v-tooltip` (PrimeVue) instead of native `title` on icon buttons
5. Apply `class="ios-select"` / `class="ios-datepicker"` to inputs
6. Keep `.ios-row-control` at fixed 200px for uniform input widths
7. Use `.ios-section > .ios-section-label > .ios-card > .ios-row` hierarchy
8. Always clean up drag listeners in `onBeforeUnmount`
