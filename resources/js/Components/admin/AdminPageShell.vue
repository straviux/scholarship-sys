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
    <div class="ios-page-shell">
        <section class="ios-section ios-page-hero-section">
            <div class="ios-card ios-page-hero">
                <div class="ios-page-header">
                    <div class="ios-page-icon">
                        <AppIcon :name="icon" :size="20" />
                    </div>
                    <div class="ios-page-copy">
                        <div v-if="eyebrow" class="ios-page-eyebrow">{{ eyebrow }}</div>
                        <h1 class="ios-page-title">{{ title }}</h1>
                        <p v-if="description" class="ios-page-description">{{ description }}</p>
                        <div v-if="$slots.meta" class="ios-page-meta">
                            <slot name="meta" />
                        </div>
                    </div>
                </div>

                <div v-if="$slots.actions" class="ios-page-actions">
                    <slot name="actions" />
                </div>
            </div>
        </section>

        <slot />
    </div>
</template>