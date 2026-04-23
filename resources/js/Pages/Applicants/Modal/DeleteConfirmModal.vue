<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermission } from '@/composable/permissions';
import { toast } from 'vue3-toastify';

const props = defineProps({
    show: Boolean,
    applicant: Object,
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'deleted']);

const { hasRole } = usePermission();
const deleting = ref(false);

const close = () => {
    emit('update:show', false);
};

const deleteApplicant = () => {
    if (!props.applicant || deleting.value) return;
    deleting.value = true;

    router.delete(route('applicants.destroy', props.applicant.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = false;
            close();
            toast.success('Applicant deleted successfully');
            emit('deleted');
            if (props.refreshActivityLogs) props.refreshActivityLogs();
        },
        onError: () => {
            deleting.value = false;
            close();
            toast.error('Failed to delete applicant');
        }
    });
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: 'calc(100vw - 24px)',
    maxWidth: '460px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, a')) return;
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
    <Dialog :visible="show" modal @update:visible="val => !val && close()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Confirm Deletion</span>
                    <button class="ios-nav-btn ios-nav-action ios-nav-destructive" @click="deleteApplicant"
                        :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="applicant">
                    <!-- Warning Section -->
                    <div class="ios-section">
                        <div class="ios-card">
                            <div class="ios-row" style="padding: 12px 16px; gap: 12px;">
                                <AppIcon name="exclamation-triangle" :size="24"
                                    style="color: #FF3B30; flex-shrink: 0;" />
                                <div>
                                    <div class="del-warn-title"
                                        style="font-size: 15px; font-weight: 600; margin-bottom: 4px;">
                                        {{ hasRole('administrator') ? `Permanently delete this applicant?` : `Delete
                                        this applicant ? ` }}
                                    </div>
                                    <div style="font-size: 13px; color: #8E8E93; line-height: 1.4;">
                                        {{ hasRole('administrator')
                                            ? `Administrators can permanently delete records. This action cannot be undone.`
                                            : `Non-administrators can soft delete records. Administrators can restore them
                                        later.` }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Name</span>
                                <span style="font-size: 14px; color: #FF3B30; font-weight: 600;">
                                    {{ applicant.last_name }}, {{ applicant.first_name }}
                                </span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Contact</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ applicant.contact_no }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom spacing -->
                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Component-unique: warning dialog title text */
.del-warn-title {
    color: #1f2937;
}
</style>

<style>
/* Dark override for DeleteConfirmModal-unique .del-warn-title */
.dark .del-warn-title {
    color: #d1d5db !important;
}

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
