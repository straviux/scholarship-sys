<script setup>
import IosModal from '@/Components/ui/IosModal.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';

defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm',
    },
    message: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'exclamation-triangle',
    },
    iconColor: {
        type: String,
        default: '#FF3B30',
    },
    dataLabel: {
        type: String,
        default: '',
    },
    data: {
        type: Array,
        default: () => [],
    },
    acceptLabel: {
        type: String,
        default: 'Confirm',
    },
    actionClass: {
        type: [String, Array, Object],
        default: 'ios-nav-destructive',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    width: {
        type: String,
        default: '400px',
    },
});

const emit = defineEmits(['update:visible', 'close', 'accept']);

const close = () => {
    emit('update:visible', false);
    emit('close');
};
</script>

<template>
    <IosModal
        :visible="visible"
        :title="title"
        :width="width"
        max-width="calc(100vw - 24px)"
        body-style="padding: 0 16px;"
        show-action
        :action-class="actionClass"
        :loading="loading"
        @action="emit('accept')"
        @close="close"
        @update:visible="val => !val && close()"
    >
        <div class="ios-section">
            <div class="ios-card !bg-white/55 dark:!bg-[#222831]/55 backdrop-blur-[14px] backdrop-saturate-[1.8] !border-white/50 dark:!border-white/8">
                <div class="ios-row ios-row-last items-start gap-3 px-4 py-3.5">
                    <AppIcon :name="icon" :size="24" :style="{ color: iconColor, flexShrink: 0, marginTop: '2px' }" />
                    <p class="text-sm leading-snug text-[#3c3c43] dark:text-gray-300 m-0">{{ message }}</p>
                    <slot name="message" />
                </div>
            </div>
        </div>

        <div v-if="$slots.data || data.length" class="ios-section pb-4" >
            <div v-if="dataLabel" class="ios-section-label text-compact">{{ dataLabel }}</div>
            <div class="ios-card !bg-white/55 dark:!bg-[#222831]/55 backdrop-blur-[14px] backdrop-saturate-[1.8] !border-white/50 dark:!border-white/8">
                <slot name="data">
                    <div
                        v-for="(row, index) in data"
                        :key="row.label ?? index"
                        class="ios-row"
                        :class="{ 'ios-row-last': index === data.length - 1 }"
                    >
                        <span class="ios-row-label text-sm">{{ row.label }}</span>
                        <span class="text-[13px] font-semibold text-[#8e8e93]" :style="row.color ? { color: row.color } : null">{{ row.value }}</span>
                    </div>
                </slot>
            </div>
        </div>

        <div style="height: 4px;"></div>
    </IosModal>
</template>
