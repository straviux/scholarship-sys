# iOS/macOS Design System Consolidation

## Overview
All iOS/macOS themed CSS classes have been consolidated into a single centralized CSS file to ensure design uniformity and simplify future updates.

## Changes Made

### 1. **Created Central CSS File**
   - **Location**: `resources/css/ios-design-system.css`
   - **Content**: All iOS-themed classes (.ios-*, animations, dialog overrides)
   - **Status**: Globally imported in `resources/js/app.js`

### 2. **Removed Duplicate Styles From**
   - `resources/js/Pages/Applicants/Modal/InterviewAssessmentModal.vue`
   - `resources/js/Pages/Applicants/Modal/YakapCategoryModal.vue`
   - `resources/js/Pages/Course/Index.vue`
   - `resources/js/Pages/FundTransactions/index.vue`

### 3. **Global Import**
   Added to `resources/js/app.js`:
   ```javascript
   import '../css/ios-design-system.css';
   ```

## CSS Classes Included

### Modal Structure
- `.ios-modal` - Main modal container with glassmorphic background
- `.ios-dialog-root.p-dialog` - PrimeVue Dialog override (global, teleported)
- `.ios-dialog-mask` - Modal backdrop with blur effect

### Navigation
- `.ios-nav-bar` - Draggable header bar
- `.ios-nav-title` - Centered title text
- `.ios-nav-btn` - Navigation buttons (absolute positioned)
- `.ios-nav-cancel` - Close button (left side, #8E8E93)
- `.ios-nav-action` - Submit/confirm button (right side)
- `.ios-nav-destructive` - Red destructive action (#FF3B30)

### Content Sections
- `.ios-body` - Scrollable content container
- `.ios-section` - Content section groups
- `.ios-section-label` - Uppercase labels with icons
- `.ios-section-footer` - Helper text below sections
- `.ios-error` - Error message styling (#FF3B30)

### Cards & Lists
- `.ios-card` - Grouped card container
- `.ios-row` - Row item in card
- `.ios-row-last` - Last row (no bottom border)
- `.ios-row-label` - Row text content

### Segmented Controls
- `.ios-segmented-control` - Tab-like control container
- `.ios-segment` - Individual button (flex 1)
- `.ios-segment-active` - Active state (green #34C759)
- `.ios-segment-active .pi` - Icon color on active

### Animations
- `.fade-scale-enter-active` / `.fade-scale-leave-active` - Transition definition
- `.fade-scale-enter-from` / `.fade-scale-leave-to` - Scale and opacity animation

## Color Palette
- Primary Background: #F2F2F7 (light gray)
- Surface: #FFFFFF (white)
- Border: #E5E5EA (light border)
- Text Primary: #000000
- Text Secondary: #555 (medium gray)
- Text Tertiary: #8E8E93 (muted)
- Active: #34C759 (green)
- Destructive: #FF3B30 (red)
- Disabled: #C7C7CC

## Design Updates Going Forward

**All iOS design changes should be made in:**
```
resources/css/ios-design-system.css
```

This ensures:
- ✅ Consistent styling across all components
- ✅ Single source of truth for design tokens
- ✅ No scattered style definitions
- ✅ Easier maintenance and debugging
- ✅ Global theme updates in one file

## Component Usage

Simply apply the class names to your HTML elements. The global CSS will automatically apply:

```vue
<div class="ios-modal">
    <div class="ios-nav-bar">
        <button class="ios-nav-btn ios-nav-cancel"><i class="pi pi-times"></i></button>
        <span class="ios-nav-title">Title</span>
        <button class="ios-nav-btn ios-nav-action"><i class="pi pi-check"></i></button>
    </div>
    <div class="ios-body">
        <div class="ios-section">
            <div class="ios-section-label">Label</div>
            <div class="ios-segmented-control">
                <button class="ios-segment ios-segment-active">Option<i class="pi pi-check"></i></button>
                <button class="ios-segment">Option</button>
            </div>
        </div>
    </div>
</div>
```

## Future Refactoring

Other components using iOS styles:
- `GenerateReportModalIOS.vue` - Consider migrating to centralized system
- Any other components with `.ios-*` classes in their scoped styles

These can be gradually refactored to use the centralized CSS file in future updates.
