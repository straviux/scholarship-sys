# GSAP Animation Integration Checklist

## Files Created

### Composables
- ✅ `resources/js/composables/useAnimationDefaults.js` - Animation timing and easing presets, prefers-reduced-motion handling
- ✅ `resources/js/composables/useGSAPAnimation.js` - Main GSAP animation composable with automatic cleanup

### Utils
- ✅ `resources/js/utils/animations.js` - Predefined animation configurations for common interactions

### Plugins
- ✅ `resources/js/plugins/animationPlugin.js` - Vue 3 plugin for global animation utilities

### Documentation
- ✅ `resources/js/ANIMATION_GUIDE.md` - Comprehensive usage guide
- ✅ `resources/js/Components/Examples/AnimatedToggle.vue` - Example toggle component
- ✅ `resources/js/Components/Examples/AnimatedButton.vue` - Example button component

### Configuration
- ✅ `package.json` - Added `gsap@^3.12.2` dependency
- ✅ `resources/js/app.js` - Imported and registered animation plugin

## Installation

Run npm install to get GSAP:
```bash
npm install
```

## Next Steps - Apply to Your Components

### DataTable Animations
- Row entrance animations (stagger)
- Row hover effects

### Form Components
- Input focus animations
- Validation feedback (shake/highlight)
- Form submission feedback

### Dialog/Modal
- Entrance/exit animations
- Content reveal stagger

### Navigation
- Page transition animations
- Active state animations
- Sidebar animations

### Cards/Panels
- Entrance animations
- Hover effects

### Toolbars/Menus
- Dropdown animations
- Menu item stagger

### Notifications/Toasts
- Entrance animations
- Auto-dismiss fade out

## Key Features

✅ Automatic context cleanup (prevents memory leaks with Inertia)
✅ Respects `prefers-reduced-motion` automatically
✅ Common presets (timings, easings, animations)
✅ Global plugin access via `$animate`, `$animationConfig`
✅ Composable API for Composition API components
✅ Example components included

## Usage Pattern

```javascript
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const { animate } = useGSAPAnimation();
animate(element, buttonAnimation.click());
```

All animations automatically:
- Skip if `prefers-reduced-motion` is enabled
- Cleanup on component unmount
- Use optimal timing and easing
