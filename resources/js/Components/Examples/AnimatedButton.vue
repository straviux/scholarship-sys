<!-- Example: Animated Button Component -->
<template>
    <Button ref="button" :label="label" :loading="loading" :disabled="disabled || loading" @click="handleClick"
        v-bind="$attrs" />
</template>

<script setup>
import { ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { buttonAnimation } from '@/utils/animations';

const props = defineProps({
    label: String,
    onClick: {
        type: Function,
        required: true,
    },
    disabled: Boolean,
    showSuccessFeedback: {
        type: Boolean,
        default: true,
    },
});

const button = ref(null);
const loading = ref(false);
const { animate } = useGSAPAnimation();

const handleClick = async () => {
    // Show click animation
    await animate(button.value?.$el, buttonAnimation.click());

    loading.value = true;

    try {
        // Execute the callback
        await props.onClick();

        // Show success animation if enabled
        if (props.showSuccessFeedback) {
            animate(button.value?.$el.querySelector('.p-button'), buttonAnimation.success());
        }
    } catch (error) {
        console.error('Button action failed:', error);
    } finally {
        loading.value = false;
    }
};
</script>
