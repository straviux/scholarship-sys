<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import ScrollToTop from "@/Components/ui/ScrollToTop.vue";
import { useSmoothScroll } from "@/composables/useSmoothScroll";
import { router } from "@inertiajs/vue3";
import { onMounted } from "vue";

const { scrollToTop } = useSmoothScroll();

onMounted(() => {
    // Smooth scroll to top on page navigation
    router.on('navigate', () => {
        scrollToTop(0.5);
    });
});
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100">

        <slot />

        <!-- Scroll to Top Button -->
        <ScrollToTop :showThreshold="400" :scrollDuration="0.8" />

    </div>
</template>
