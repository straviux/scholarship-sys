<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 500px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Update Transaction Status</span>
                    <button class="ios-nav-btn ios-nav-action" @click="saveStatus" :disabled="isSaving">
                        <AppIcon v-if="isSaving" name="spinner" :size="12" style="margin-right: 3px;" />Update
                    </button>
                </div>
                <div class="ios-body">
                    <div v-if="modelValue" class="pt-6 pb-12">
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px;">
                                <p style="font-size: 14px; font-weight: 500; color: #3c3c43;">Transaction ID: {{
                                    modelValue.transaction_id }}</p>
                                <p style="font-size: 12px; color: #8E8E93; margin-top: 2px;">Change the transaction
                                    status for this voucher</p>
                            </div>
                        </div>
                        <div class="ios-section">
                            <p class="ios-section-label">OBR Status</p>
                            <Select v-model="status" :options="statusOptions" placeholder="Select a status"
                                class="w-full" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Object,
        default: null
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    isSaving: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'save']);

const elModal = ref(null);
const status = ref('on process');
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);

const modalStyle = computed(() => ({
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`
}));

function onDragStart(e) {
    if (e.target.closest('button')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}

function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}

function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});


watch(() => props.modelValue, (newVal) => {
    if (newVal?.obr_status) {
        status.value = newVal.obr_status;
    }
}, { deep: true });

const saveStatus = () => {
    emit('save', {
        status: status.value
    });
};
</script>
