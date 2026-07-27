<script setup>
// Small centered "working" indicator shown while a potentially slow
// operation runs (e.g. fetching a large report dataset). The dim mask
// blocks interaction until the operation finishes.
defineProps({
    show: Boolean,
    message: {
        type: String,
        default: 'Loading…'
    },
    subtext: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30">
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-xl">
                <div class="h-5 w-5 shrink-0 rounded-full border-2 border-blue-200 border-t-blue-500 animate-spin"></div>
                <div>
                    <div class="text-sm font-semibold text-gray-700">{{ message }}</div>
                    <div v-if="subtext" class="text-2xs text-gray-400">{{ subtext }}</div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
