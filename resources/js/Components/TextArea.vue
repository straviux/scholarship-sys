<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
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
    <textarea
        class="border border-gray-300 p-4 focus-within:outline-none focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 rounded-sm shadow-xs"
        v-model="model" ref="input"></textarea>
</template>
