<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';

const props = defineProps({
    show: Boolean,
    profile: Object,
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'success']);

const remarksForm = useForm({
    remarks: ''
});

// When showing, populate form
const onShow = () => {
    if (props.profile) {
        remarksForm.remarks = props.profile.remarks || '';
    }
};

const close = () => {
    emit('update:show', false);
    remarksForm.reset();
};

const submit = () => {
    remarksForm.post(route('applicants.update-remarks', props.profile.profile_id), {
        onSuccess: () => {
            toast.success('Remarks updated successfully!');
            close();
            emit('success');
            if (props.refreshActivityLogs) props.refreshActivityLogs();
        },
        onError: () => {
            toast.error('Failed to update remarks');
        }
    });
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '560px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-editor')) return;
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
</script>

<template>
    <Dialog :visible="show" modal @update:visible="val => !val && close()" @show="onShow"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Remarks</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="remarksForm.processing">
                        {{ remarksForm.processing ? 'Saving...' : 'Save' }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="profile">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">{{ profile.last_name }}, {{ profile.first_name }}</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ profile.contact_no }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Input -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card">
                            <Editor v-model="remarksForm.remarks" editorStyle="height: 150px">
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
                        <div v-if="remarksForm.errors.remarks" class="ios-section-footer ios-error">
                            {{ remarksForm.errors.remarks }}
                        </div>
                    </div>

                    <!-- Bottom spacing -->
                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
