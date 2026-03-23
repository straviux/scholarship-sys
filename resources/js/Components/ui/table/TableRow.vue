<script setup>
import { onMounted, ref, getCurrentInstance } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { listAnimation } from '@/utils/animations';

const row = ref(null);
const { animate } = useGSAPAnimation();

onMounted(() => {
    if (row.value) {
        // Animate row entrance with initial opacity/y
        animate(row.value, {
            opacity: [0, 1],
            y: [10, 0],
            duration: 0.3,
            ease: 'power2.out',
        });
    }
});
</script>

<template>
    <tr ref="row">
        <slot />
    </tr>
</template>
