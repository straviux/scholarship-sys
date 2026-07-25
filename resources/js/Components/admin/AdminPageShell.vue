<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import AppIcon from '@/Components/ui/AppIcon.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'settings',
    },
    // Kept for backward compatibility with existing pages; no longer rendered.
    eyebrow: {
        type: String,
        default: 'Admin',
    },
});

onMounted(() => {
    document.body.classList.add('ios-admin-page');
});

onBeforeUnmount(() => {
    document.body.classList.remove('ios-admin-page');
});
</script>

<template>
    <div class="ios-page-shell ios-settings-form">
        <!-- Toolbar header (Interviewed Applicants style) -->
        <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] short:mb-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <AppIcon :name="icon" class="text-blue-600 text-[2rem] short:text-[1.5rem]" />
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-gray-600 short:text-xs">{{ description }}</p>
                    </div>
                </div>
            </template>
            <template #end>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <div v-if="$slots.meta"
                        class="flex flex-wrap items-center gap-2 [&>span]:inline-flex [&>span]:items-center [&>span]:whitespace-nowrap [&>span]:rounded-full [&>span]:bg-blue-50 [&>span]:px-2.5 [&>span]:py-0.5 [&>span]:text-xs [&>span]:font-semibold [&>span]:text-blue-700">
                        <slot name="meta" />
                    </div>
                    <slot name="actions" />
                </div>
            </template>
        </Toolbar>

        <slot />
    </div>
</template>

