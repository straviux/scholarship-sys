# Smooth Scroll Implementation Guide

> Note: The old `v-smooth-scroll` directive has been removed. Use `useSmoothScroll()` in click handlers instead.

## Quick Start

### 1. **Scroll to Top Button**

Add to your main layout:

```vue
<template>
  <ScrollToTop />
</template>

<script setup>
import ScrollToTop from '@/Components/ui/ScrollToTop.vue';
</script>
```

### 2. **Anchor-Style Navigation**

Use the composable directly in click handlers when you want link-like section navigation:

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToElement } = useSmoothScroll();

const goToSection = (selector) => {
  scrollToElement(selector, 80, 0.8);
};
</script>

<template>
  <a href="#section-1" @click.prevent="goToSection('#section-1')">Go to Section 1</a>
  <a href="#section-2" @click.prevent="goToSection('#section-2')">Go to Section 2</a>

  <div id="section-1">Content here</div>
  <div id="section-2">More content here</div>
</template>
```

### 3. **Programmatic Scrolling**

Use the `useSmoothScroll()` composable in components:

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToElement, scrollToTop, scrollToPosition } = useSmoothScroll();

// Scroll to element
const handleScrollToElement = () => {
  scrollToElement('#my-element', 60, 0.8);
};

// Scroll to position
const handleScrollToPosition = () => {
  scrollToPosition(500, 0.8); // Scroll to 500px from top
};

// Scroll to top
const handleScrollToTop = () => {
  scrollToTop(0.6);
};
</script>

<template>
  <button @click="handleScrollToElement">Scroll to Element</button>
  <button @click="handleScrollToPosition">Scroll to 500px</button>
  <button @click="handleScrollToTop">Back to Top</button>
</template>
```

## API Reference

### `useSmoothScroll()`

#### Methods

- **`scrollToElement(target, offset = 0, duration = 0.8)`**
  - `target`: DOM element or selector string (e.g., '#my-id' or element ref)
  - `offset`: Pixels from top to offset the scroll (useful for fixed headers)
  - `duration`: Animation duration in seconds
  - Returns: void

- **`scrollToPosition(y, duration = 0.8)`**
  - `y`: Y position in pixels to scroll to
  - `duration`: Animation duration in seconds
  - Returns: void

- **`scrollToTop(duration = 0.6)`**
  - `duration`: Animation duration in seconds
  - Shortcut for `scrollToPosition(0, duration)`
  - Returns: void

#### Reactive Refs

- **`isScrolling`** (ref<boolean>)
  - True while a smooth scroll animation is in progress
  - Use to disable buttons/links during scrolling

- **`scrollProgress`** (ref<number>)
  - Current scroll progress as percentage (0-100)
  - Useful for progress bars or animations

#### Utility Functions

- **`getScrollPosition()`**
  - Returns current Y scroll position in pixels

- **`isElementInViewport(element)`**
  - Returns true if element is visible in viewport

- **`hasScrolledPast(position)`**
  - Returns true if user has scrolled past a specific Y position

### Anchor-Style Links Without a Directive

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToElement } = useSmoothScroll();
</script>

<template>
  <a href="#target-id" @click.prevent="scrollToElement('#target-id', 80, 0.8)">
    Link
  </a>
</template>
```

## Component: ScrollToTop

### Props

- `showThreshold` (number, default: 300)
  - Show button after scrolling this many pixels

- `scrollDuration` (number, default: 0.6)
  - Duration for scroll animation to top

### Example

```vue
<ScrollToTop :showThreshold="500" :scrollDuration="0.8" />
```

## Features

✅ **Smooth scrolling** via `window.scrollTo({ behavior: 'smooth' })`
✅ **Respects prefers-reduced-motion** for accessibility
✅ **Offset support** for fixed headers
✅ **Progress tracking** for UI integrations
✅ **Reusable composable** for programmatic control
✅ **Scroll-to-top button** component included

## Accessibility Considerations

- All smooth scrolling respects `prefers-reduced-motion` media query
- Instant scroll happens for users who prefer no motion
- Scroll to top button has proper ARIA labels
- Keyboard navigable (tab + enter)

## Performance Tips

1. Use `isScrolling` ref to prevent multiple simultaneous scroll animations
2. Cache element references when scrolling frequently
3. Consider offset values for fixed headers (typically 60-100px)
4. Duration of 0.6-1.0 seconds is optimal for user experience

## Browser Support

- Modern browsers with smooth scroll support
- Works on mobile (iOS, Android)

## Examples

### Scroll to Section on Button Click

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToElement } = useSmoothScroll();

const goToSection = () => {
  scrollToElement('#about-section', 80, 0.8);
};
</script>

<template>
  <button @click="goToSection">Learn More</button>
  <section id="about-section">About us...</section>
</template>
```

### Table of Contents with Smooth Navigation

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToElement } = useSmoothScroll();

const sections = [
  { id: 'intro', title: 'Introduction' },
  { id: 'features', title: 'Features' },
  { id: 'pricing', title: 'Pricing' },
];
</script>

<template>
  <nav>
    <a
      v-for="section in sections"
      :key="section.id"
      :href="`#${section.id}`"
      @click.prevent="scrollToElement(`#${section.id}`, 80, 0.8)"
    >
      {{ section.title }}
    </a>
  </nav>
</template>
```

### Pagination with Scroll to Top

```vue
<script setup>
import { useSmoothScroll } from '@/composables/useSmoothScroll';

const { scrollToTop } = useSmoothScroll();

const changePage = (newPage) => {
  // Fetch data...
  // Then scroll to top
  scrollToTop(0.6);
};
</script>

<template>
  <Pagination @change="changePage" />
</template>
```
