<script setup>
import { computed, ref } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { toggleAnimation } from '@/utils/animations';

const emit = defineEmits(['update:checked']);
const checkbox = ref(null);
const { animate } = useGSAPAnimation();

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        animate(checkbox.value, toggleAnimation.pulse());
        emit('update:checked', val);
    },
});
</script>

<template>
    <input ref="checkbox" type="checkbox" :value="value" v-model="proxyChecked"
        class="rounded-sm border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500" />
</template>
