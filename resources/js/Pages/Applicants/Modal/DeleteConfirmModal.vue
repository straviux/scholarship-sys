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
    width: '460px',
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
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
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
                                <i class="pi pi-exclamation-triangle"
                                    style="font-size: 24px; color: #FF3B30; flex-shrink: 0;"></i>
                                <div>
                                    <div style="font-size: 15px; font-weight: 600; color: #000; margin-bottom: 4px;">
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
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-nav-destructive {
    color: #FF3B30 !important;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}
</style>

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
