<template>
    <Dialog
        :visible="visible"
        modal
        :dismissableMask="dismissableMask"
        :closeOnEscape="closeOnEscape"
        :style="dialogStyle"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        v-bind="$attrs"
        @update:visible="handleVisibleUpdate"
    >
        <template #container>
            <div class="ios-modal" :class="modalClass" :style="resolvedModalStyle">
                <div class="ios-nav-bar" :class="headerClass" @pointerdown="handleDragStart">
                    <slot name="header-left">
                        <button v-if="showClose" class="ios-nav-btn ios-nav-cancel" type="button" @click="handleClose">
                            <AppIcon :name="closeIcon" :size="iconSize" />
                        </button>
                    </slot>

                    <slot name="title">
                        <span class="ios-nav-title" :class="titleClass">{{ title }}</span>
                    </slot>

                    <slot name="header-right">
                        <button
                            v-if="showAction"
                            class="ios-nav-btn ios-nav-action"
                            :class="actionClass"
                            type="button"
                            @click.stop="emit('action')"
                            :disabled="actionDisabled || loading"
                        >
                            <AppIcon v-if="loading" name="spinner" :size="iconSize" class="animate-spin" />
                            <AppIcon v-else-if="actionIcon" :name="actionIcon" :size="iconSize" />
                            <span v-if="actionLabel">{{ actionLabel }}</span>
                        </button>
                    </slot>
                </div>

                <div class="ios-body" :class="bodyClass" :style="bodyStyle">
                    <slot />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    width: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: '',
    },
    minWidth: {
        type: String,
        default: '',
    },
    showClose: {
        type: Boolean,
        default: true,
    },
    showAction: {
        type: Boolean,
        default: false,
    },
    actionDisabled: {
        type: Boolean,
        default: false,
    },
    actionIcon: {
        type: String,
        default: 'check',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    closeIcon: {
        type: String,
        default: 'times',
    },
    iconSize: {
        type: Number,
        default: 14,
    },
    dismissableMask: {
        type: Boolean,
        default: false,
    },
    closeOnEscape: {
        type: Boolean,
        default: true,
    },
    draggable: {
        type: Boolean,
        default: true,
    },
    modalClass: {
        type: [String, Array, Object],
        default: '',
    },
    modalContentStyle: {
        type: [String, Array, Object],
        default: '',
    },
    headerClass: {
        type: [String, Array, Object],
        default: '',
    },
    titleClass: {
        type: [String, Array, Object],
        default: '',
    },
    actionClass: {
        type: [String, Array, Object],
        default: '',
    },
    bodyClass: {
        type: [String, Array, Object],
        default: '',
    },
    bodyStyle: {
        type: [String, Array, Object],
        default: '',
    },
    dialogStyle: {
        type: [String, Array, Object],
        default: '',
    },
});

const emit = defineEmits(['update:visible', 'close', 'action']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const resolvedModalStyle = computed(() => {
    const style = [];

    if (props.width || props.maxWidth || props.minWidth) {
        style.push({
            ...(props.width ? { width: props.width } : {}),
            ...(props.maxWidth ? { maxWidth: props.maxWidth } : {}),
            ...(props.minWidth ? { minWidth: props.minWidth } : {}),
        });
    }

    if (props.draggable) {
        style.push(dragStyle.value);
    }

    if (props.modalContentStyle) {
        style.push(props.modalContentStyle);
    }

    return style;
});

const handleDragStart = (event) => {
    if (!props.draggable) {
        return;
    }

    onDragStart(event);
};

const handleVisibleUpdate = (value) => {
    if (!value) {
        resetDrag();
        emit('close');
    }

    emit('update:visible', value);
};

const handleClose = () => {
    resetDrag();
    emit('close');
    emit('update:visible', false);
};
</script>
