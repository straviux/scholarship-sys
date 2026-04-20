<template>
    <Button v-bind="buttonAttrs" :size="buttonSize">
        <!-- Left icon slot (default) -->
        <template v-if="icon && iconPos !== 'right'" #icon>
            <AppIcon :name="icon" :size="iconSize" :style="iconStyles" class="p-button-icon p-button-icon-left" />
        </template>

        <!-- Default content slot pass-through -->
        <template v-if="$slots.default" #default>
            <slot />
        </template>

        <!-- Right icon slot -->
        <template v-if="icon && iconPos === 'right'" #icon>
            <AppIcon :name="icon" :size="iconSize" :style="iconStyles" class="p-button-icon p-button-icon-right" />
        </template>
    </Button>
</template>

<script setup>
import { computed, useAttrs } from 'vue';
import Button from 'primevue/button';
import AppIcon from '@/Components/ui/AppIcon.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    icon: { type: String, default: null },
    iconPos: { type: String, default: 'left' },
    // size also controls icon pixel size — maps PrimeVue size variants to px
    size: { type: String, default: null },
    iconColor: { type: String, default: null },
});

const attrs = useAttrs();
const extraSmallAliases = ['xsmall', 'extra-small', 'xs'];
const normalizedSize = computed(() => {
    return extraSmallAliases.includes(props.size) ? 'xsmall' : props.size;
});

// Strip icon/iconPos from the attrs forwarded to PrimeVue Button (we handle them ourselves)
const buttonAttrs = computed(() => {
    const { icon: _icon, iconPos: _iconPos, class: className, ...rest } = attrs;
    return {
        ...rest,
        class: [className, normalizedSize.value === 'xsmall' ? 'app-button--xsmall' : null],
    };
});

// Map PrimeVue size prop → pixel size for the icon
const sizeMap = { xsmall: 12, small: 13, large: 17 };
const buttonSize = computed(() => (normalizedSize.value === 'xsmall' ? undefined : normalizedSize.value));
const iconSize = computed(() => sizeMap[normalizedSize.value] ?? 14);
const iconStyles = computed(() => (props.iconColor ? { color: props.iconColor } : undefined));
</script>

<style>
.app-button--xsmall.p-button {
    min-height: 1.875rem;
    padding: 0.3125rem 0.6875rem;
    gap: 0.325rem;
    font-size: 0.75rem;
}

.app-button--xsmall.p-button .p-button-label {
    font-size: 0.75rem;
    line-height: 1.15;
}

.app-button--xsmall.p-button.p-button-icon-only {
    width: 1.875rem;
    padding: 0.3125rem;
}
</style>
