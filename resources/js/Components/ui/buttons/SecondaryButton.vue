<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const emit = defineEmits(['click']);
const button = ref(null);
const { animate } = useGSAPAnimation();

defineProps({
    type: {
        type: String,
        default: 'button',
    },
});

const handleClick = async (e) => {
    if (button.value) {
        await animate(button.value, buttonAnimation.click());
    }
    emit('click', e);
};
</script>

<template>
    <button ref="button" @click="handleClick" :type="type"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-xs hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150  cursor-pointer">
        <slot />
    </button>
</template>
