# Animation Integration Guide

This guide shows how to integrate the shared animation utilities into your components.

## Setup

Animations are provided through direct composable imports. There is no global animation plugin, injected animation config, or `$animate` helper in the current app bootstrap.

### Options API Usage

```vue
<template>
  <button @click="handleClick" ref="button">Click Me</button>
</template>

<script>
import { quickAnimateFrom } from '@/composables/useGSAPAnimation';

export default {
  methods: {
    handleClick() {
      quickAnimateFrom(this.$refs.button, {
        x: 12,
        opacity: 0,
        duration: 0.2,
        clearProps: 'transform,opacity',
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

### Notes

- Use `useGSAPAnimation()` when you need `animate`, `animateFrom`, or `stagger` from the shared transition wrapper.
- Use `quickAnimateFrom()` for small one-off transitions when a component-level composable instance is unnecessary.
- There is no injected `animations` object or `$animationConfig`; import timing presets from `@/composables/useAnimationDefaults` when needed.

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

1. **Prefer `useGSAPAnimation()` for shared patterns** - It keeps component animation code consistent.
2. **Use `quickAnimateFrom()` for one-off transitions** - Avoid extra composable setup when you only need a single entrance effect.
3. **Limit simultaneous animations** - Stagger heavy animations.
4. **Avoid animating large DOM trees** - Animate containers instead.
5. **Prefer `transform` and `opacity`** - They are cheaper than layout-affecting properties.

## Timing Guidelines

- Instant feedback: 100-200ms (button clicks, toggles)
- Transitions: 300-400ms (modal open, dropdown open)
- Exit animations: 250ms (modal close)
- List stagger: 50-100ms per item

## Memory Cleanup

The current implementation uses native transitions through the shared composable helpers, so most components do not need explicit animation teardown.

On unmount, the composable clears its local context reference, which helps components avoid holding stale animation state.

- Clears the composable's local context ref
- Avoids stale references after unmount
- Keeps cleanup requirements minimal for standard component transitions

In practice, the main rule is simple: keep animation work scoped to mounted elements and avoid long-lived timers or observers in custom animation code.
