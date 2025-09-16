<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});
const classes = computed(() =>
    props.active
        ? "relative px-4 py-3 flex rounded-xl text-white bg-linear-to-r from-[#2b5876] to-[#4e4376]"
        : "px-4 py-3 flex rounded-md text-gray-300 hover:text-gray-50 group"
);
</script>

<template>
    <Link :href="href" :class="classes">
    <slot />
    </Link>
</template>
