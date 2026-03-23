<!-- Example: Animated Toggle Component -->
<template>
    <div class="flex items-center gap-3">
        <label for="toggle">{{ label }}</label>
        <ToggleSwitch v-model="internalValue" @change="handleToggle" ref="toggle" :input-id="`toggle-${id}`" />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { toggleAnimation } from '@/utils/animations';

const props = defineProps({
    modelValue: Boolean,
    label: String,
    id: String,
});

const emit = defineEmits(['update:modelValue', 'change']);

const toggle = ref(null);
const internalValue = ref(props.modelValue);
const { animate } = useGSAPAnimation();

const handleToggle = async () => {
    // Animate the toggle
    const toggleBox = toggle.value?.$el.querySelector('.p-toggleswitch');
    if (toggleBox) {
        await animate(toggleBox, toggleAnimation.pulse());
    }

    // Emit the change
    emit('update:modelValue', internalValue.value);
    emit('change', internalValue.value);
};

watch(() => props.modelValue, (newVal) => {
    internalValue.value = newVal;
});
</script>
