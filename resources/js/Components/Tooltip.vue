<template>
    <div class="relative flex items-center" @mouseenter="show = true" @mouseleave="show = false">
        <slot />
        <transition name="fade">
            <div v-if="show"
                class="absolute left-full top-1/2 ml-2 -translate-y-1/2 px-2 py-1 rounded bg-gray-800 text-xs text-white whitespace-nowrap z-50 shadow-lg">
                {{ text }}
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref } from 'vue';
const props = defineProps({
    text: { type: String, required: true }
});
const show = ref(false);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
