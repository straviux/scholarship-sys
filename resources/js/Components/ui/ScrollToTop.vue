<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useSmoothScroll, hasScrolledPast } from '@/composables/useSmoothScroll';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const props = defineProps({
    showThreshold: {
        type: Number,
        default: 300, // Show button after scrolling 300px
    },
    scrollDuration: {
        type: Number,
        default: 0.6,
    },
});

const isVisible = ref(false);
const button = ref(null);
const { scrollToTop } = useSmoothScroll();
const { animate } = useGSAPAnimation();

const handleScroll = () => {
    const shouldShow = hasScrolledPast(props.showThreshold);
    isVisible.value = shouldShow;
};

const handleClick = async () => {
    if (button.value) {
        await animate(button.value, buttonAnimation.click());
    }
    scrollToTop(props.scrollDuration);
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-95 translate-y-4"
        enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-200"
        leave-to-class="opacity-0 scale-95 translate-y-4">
        <button v-if="isVisible" ref="button" @click="handleClick" aria-label="Scroll to top"
            class="fixed bottom-8 right-8 z-40 p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
            </svg>
        </button>
    </Transition>
</template>
