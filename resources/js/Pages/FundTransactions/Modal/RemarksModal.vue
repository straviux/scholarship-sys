<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div ref="elModal" class="ios-modal" style="width: 90vw; max-width: 500px;" :style="modalStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="$emit('update:show', false)">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Add/Edit Remarks</span>
                    <button class="ios-nav-btn ios-nav-action" @click="saveRemarks" :disabled="isSaving">
                        <AppIcon v-if="isSaving" name="spinner" :size="12" style="margin-right: 3px;" />Save
                    </button>
                </div>
                <div class="ios-body">
                    <div v-if="modelValue" style="padding-top: 16px;">
                        <div class="ios-section">
                            <div class="ios-card" style="padding: 12px 16px;">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Transaction ID: {{
                                    modelValue.transaction_id }}</p>
                                <p class="text-sm dark:text-gray-400 mt-4">Add or edit remarks for
                                    this voucher</p>
                            </div>
                        </div>
                        <div class="ios-section" style="margin-bottom: 16px;">
                            <p class="ios-section-label">Remarks</p>

                            <Editor :modelValue="remarks" @update:modelValue="remarks = $event"
                                class="ios-card h-48 text-lg">
                                <template #toolbar>
                                    <span class="ql-formats">
                                        <button class="ql-bold"></button>
                                        <button class="ql-italic"></button>
                                        <button class="ql-underline"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-list" value="ordered"></button>
                                        <button class="ql-list" value="bullet"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-clean"></button>
                                    </span>
                                </template>
                            </Editor>
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
import Editor from 'primevue/editor';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Object,
        default: null
    },
    isSaving: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:show', 'save']);

const elModal = ref(null);
const remarks = ref('');
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
    if (newVal?.remarks) {
        remarks.value = newVal.remarks;
    }
}, { deep: true });

const saveRemarks = () => {
    emit('save', remarks.value);
};
</script>
