<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});
const props = defineProps({
    label: {
        type: String,
    },
    required: {
        type: Boolean,
        default: false,
    }
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <!-- <input class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs" v-model="model"
        ref="input" /> -->

    <label
        class="input focus-within:outline-none focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 flex items-center gap-2">
        <span class="text-xs text-gray-700 capitalize font-medium">{{ props.label }}</span>
        <input class="grow " v-model="model" ref="input" type="date" />
        <span class="text-lg" v-if="props.required">*</span>
    </label>
</template>
