<script setup>
import { computed, onMounted, onUnmounted, watch, ref } from "vue";
import { useGSAPAnimation } from "@/composables/useGSAPAnimation";
import { modalAnimation } from "@/utils/animations";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: "2xl",
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    marginTop: {
        type: String,
        default: "mt-2",
    },
});

const emit = defineEmits(["close"]);
const dialog = ref(null);
const { animate, animateFrom } = useGSAPAnimation();

watch(
    () => props.show,
    (isVisible) => {
        if (isVisible) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = null;
        }
    }
);

const close = () => {
    if (props.closeable) {
        emit("close");
    }
};

const closeOnEscape = (e) => {
    if (e.key === "Escape" && props.show) {
        close();
    }
};

const onDialogShow = () => {
    const dialogContent = dialog.value?.$el?.querySelector('.p-dialog-content');
    if (dialogContent) {
        animateFrom(dialogContent, modalAnimation.entrance());
        animate(dialogContent, { opacity: 1, scale: 1, y: 0, duration: 0.4 });
    }
};

onMounted(() => document.addEventListener("keydown", closeOnEscape));

onUnmounted(() => {
    document.removeEventListener("keydown", closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        sm: "sm:max-w-sm",
        md: "sm:max-w-md",
        lg: "sm:max-w-lg",
        xl: "sm:max-w-xl",
        "2xl": "sm:max-w-2xl",
        "4xl": "sm:max-w-4xl",
    }[props.maxWidth];
});
</script>

<template>
    <Dialog ref="dialog" :visible="show" modal :closable="closeable" :style="{ width: maxWidthClass }"
        @show="onDialogShow" @update:visible="(val) => { if (!val) close(); }">
        <slot v-if="show" />
    </Dialog>
</template>
