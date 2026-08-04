<script setup>
// Decorative, empty background layer: a dim dot grid everywhere, plus a
// second copy of the same grid masked by a radial gradient that follows the
// pointer, so the pattern brightens where attention is and fades elsewhere.
// Mounted once in AdminLayout.vue; fixed + negative z-index so it never
// affects layout or intercepts clicks.
import { ref, onMounted, onUnmounted } from 'vue';

const stage = ref(null);

function setSpot(xPct, yPct) {
    const el = stage.value;
    if (!el) return;
    el.style.setProperty('--dgs-mx', xPct + '%');
    el.style.setProperty('--dgs-my', yPct + '%');
}

function onPointerMove(e) {
    setSpot((e.clientX / window.innerWidth) * 100, (e.clientY / window.innerHeight) * 100);
}

let reduced = false;

onMounted(() => {
    reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced) {
        setSpot(50, 28);
    } else {
        window.addEventListener('pointermove', onPointerMove, { passive: true });
    }
});

onUnmounted(() => {
    window.removeEventListener('pointermove', onPointerMove);
});
</script>

<template>
    <div ref="stage" class="dgs-stage" aria-hidden="true">
        <div class="dgs-base"></div>
        <div class="dgs-spot"></div>
    </div>
</template>

<style>
@property --dgs-mx {
    syntax: '<percentage>';
    inherits: true;
    initial-value: 50%;
}

@property --dgs-my {
    syntax: '<percentage>';
    inherits: true;
    initial-value: 28%;
}

.dgs-stage {
    position: fixed;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    --dgs-dot-rgb: 17, 20, 28;
    --dgs-spot-rgb: 79, 70, 229;
    /* tailwind indigo-600, matches the cog icon accent already used in the app */
}

html.dark .dgs-stage {
    --dgs-dot-rgb: 255, 255, 255;
    --dgs-spot-rgb: 129, 140, 248;
    /* indigo-400, matches the dark-mode cog icon accent */
}

.dgs-base,
.dgs-spot {
    position: absolute;
    inset: -2px;
    background-image: radial-gradient(rgba(var(--dgs-dot-rgb), 1) 1px, transparent 1.6px);
    background-size: 26px 26px;
}

.dgs-base {
    opacity: 0.05;
}

html.dark .dgs-base {
    opacity: 0.07;
}

.dgs-spot {
    opacity: 0.85;
    -webkit-mask-image: radial-gradient(circle at var(--dgs-mx) var(--dgs-my),
            rgba(0, 0, 0, 1) 0%,
            rgba(0, 0, 0, 0.4) 34%,
            rgba(0, 0, 0, 0) 65%);
    mask-image: radial-gradient(circle at var(--dgs-mx) var(--dgs-my),
            rgba(0, 0, 0, 1) 0%,
            rgba(0, 0, 0, 0.4) 34%,
            rgba(0, 0, 0, 0) 65%);
    transition: --dgs-mx 0.4s cubic-bezier(.2, .7, .3, 1), --dgs-my 0.4s cubic-bezier(.2, .7, .3, 1);
}

.dgs-stage::before {
    /* soft ambient color glow under the spotlight, same position, no mask */
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at var(--dgs-mx) var(--dgs-my),
            rgba(var(--dgs-spot-rgb), 0.10) 0%,
            rgba(var(--dgs-spot-rgb), 0) 70%);
    transition: --dgs-mx 0.4s cubic-bezier(.2, .7, .3, 1), --dgs-my 0.4s cubic-bezier(.2, .7, .3, 1);
}

@media (prefers-reduced-motion: reduce) {

    .dgs-spot,
    .dgs-stage::before {
        transition: none;
    }
}
</style>
