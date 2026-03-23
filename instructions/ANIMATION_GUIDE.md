# GSAP Animation Integration Guide

This guide shows how to integrate GSAP animations into your components.

## Setup

GSAP is now integrated as a Vue 3 plugin with automatic context cleanup to prevent memory leaks during Inertia page transitions.

### Global Usage (Options API)

```vue
<template>
  <button @click="handleClick" ref="button">Click Me</button>
</template>

<script>
export default {
  data() {
    return {
      button: null,
    };
  },
  methods: {
    handleClick() {
      // Use $animate for simple tweens
      this.$animate(this.$refs.button, {
        scale: 0.95,
        duration: 0.2,
        yoyo: true,
        repeat: 1,
      });
    },
  },
};
</script>
```

### Composition API Usage (Recommended)

```vue
<template>
  <button @click="handleClick" ref="button">Click Me</button>
</template>

<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const button = ref(null);
const { animate } = useGSAPAnimation();

const handleClick = () => {
  animate(button.value, buttonAnimation.click());
};
</script>
```

## Common Animations

### Toggle/Checkbox

```vue
<template>
  <Checkbox v-model="checked" @change="animateToggle" ref="checkbox" />
</template>

<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { toggleAnimation } from '@/utils/animations';

const checked = ref(false);
const checkbox = ref(null);
const { animate } = useGSAPAnimation();

const animateToggle = () => {
  animate(checkbox.value?.$el, toggleAnimation.pulse());
};
</script>
```

### Select Dropdown

```vue
<template>
  <Select 
    v-model="selected" 
    @show="animateOpen"
    @hide="animateClose"
    ref="select"
    :options="options"
  />
</template>

<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { selectAnimation } from '@/utils/animations';

const selected = ref(null);
const select = ref(null);
const options = ['Option 1', 'Option 2'];
const { animate } = useGSAPAnimation();

const animateOpen = () => {
  const dropdown = select.value?.overlayViewChild;
  if (dropdown) {
    animate(dropdown, selectAnimation.open());
  }
};

const animateClose = () => {
  const dropdown = select.value?.overlayViewChild;
  if (dropdown) {
    animate(dropdown, selectAnimation.close());
  }
};
</script>
```

### Button Click Feedback

```vue
<template>
  <Button @click="handleSave" ref="button" label="Save" />
</template>

<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const button = ref(null);
const { animate } = useGSAPAnimation();

const handleSave = async () => {
  // Show click animation
  await animate(button.value?.$el, buttonAnimation.click());
  
  // Do your save logic
  // ... api call ...
  
  // Show success animation
  animate(button.value?.$el, buttonAnimation.success());
};
</script>
```

### Modal Entrance

```vue
<template>
  <Dialog v-model:visible="visible" ref="dialog">
    <!-- content -->
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { modalAnimation } from '@/utils/animations';

const visible = ref(false);
const dialog = ref(null);
const { animate, animateFrom } = useGSAPAnimation();

watch(visible, (isVisible) => {
  if (isVisible) {
    // Set initial state
    const panel = dialog.value?.$el.querySelector('.p-dialog');
    if (panel) {
      animateFrom(panel, modalAnimation.entrance());
      animate(panel, { opacity: 1, scale: 1, y: 0, duration: 0.4 });
    }
  }
});
</script>
```

### List Item Stagger

```vue
<template>
  <div class="list">
    <div v-for="item in items" :key="item.id" class="list-item">
      {{ item.name }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { listAnimation } from '@/utils/animations';

const items = ref([
  { id: 1, name: 'Item 1' },
  { id: 2, name: 'Item 2' },
  { id: 3, name: 'Item 3' },
]);

const { stagger } = useGSAPAnimation();

onMounted(() => {
  const listItems = document.querySelectorAll('.list-item');
  stagger(listItems, {
    opacity: 1,
    y: 0,
    duration: 0.4,
    stagger: 0.1,
  });
});
</script>
```

## Available Animation Presets

### timings (in seconds)
- `microInteraction`: 0.2 - quick feedback
- `transition`: 0.3 - general transitions
- `entrance`: 0.4 - entering elements
- `exit`: 0.25 - leaving elements

### easings
- `default`: 'power2.out'
- `bounce`: 'elastic.out(1, 0.5)'
- `spring`: 'back.out(1.7)'
- `smooth`: 'sine.inOut'

### Available Animations
- `toggleAnimation` - pulse, flip
- `selectAnimation` - open, close, optionStagger
- `buttonAnimation` - click, hover, success
- `modalAnimation` - entrance, exit
- `fadeAnimation` - in, out
- `slideAnimation` - inRight, inLeft, outRight, outLeft
- `listAnimation` - itemStagger, itemEntry
- `shakeAnimation` - error shake
- `pulseAnimation` - loading pulse
- `highlightAnimation` - form validation

## Accessibility

Animations automatically respect `prefers-reduced-motion` media query. No configuration needed.

If animation is disabled by the user or in `useAnimationDefaults.js`:
- All animations return immediately without visual effect
- `shouldAnimate()` returns `false`

## Performance Tips

1. **Always use `useGSAPAnimation()` composable** - Provides automatic context cleanup
2. **Use `gsap.context()`** - Prevents memory leaks with Inertia transitions
3. **Limit simultaneous animations** - Stagger heavy animations
4. **Avoid animating large DOM trees** - Animate containers instead
5. **Use `transform` properties** - Faster than layout properties (top, left)

## Timing Guidelines

- Instant feedback: 100-200ms (button clicks, toggles)
- Transitions: 300-400ms (modal open, dropdown open)
- Exit animations: 250ms (modal close)
- List stagger: 50-100ms per item

## Memory Cleanup

The `useGSAPAnimation()` composable automatically runs `ctx.revert()` on component unmount, which:
- Kills all animations
- Removes all selectors
- Clears the context
- Prevents memory leaks

This is critical when using Inertia page transitions where components are frequently mounted/unmounted.
