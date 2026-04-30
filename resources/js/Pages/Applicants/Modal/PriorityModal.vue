<script setup>
import { watch, ref, reactive, inject, computed, onBeforeUnmount } from "vue";
import { toast } from '@/utils/toast';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    applicant: Object,
});

const emit = defineEmits(['update:show', 'success']);

const refreshActivityLogs = inject('refreshActivityLogs', null);

const isSubmitting = ref(false);
const errors = reactive({
    priority_level: null,
    priority_reason: null,
});

const priorityOptions = [
    { value: 'normal', label: 'Normal Priority', description: 'Default priority level' },
    { value: 'high', label: 'High Priority', description: 'Faster processing' },
    { value: 'urgent', label: 'Urgent Priority', description: 'Immediate attention required' }
];

const formData = reactive({
    priority_level: 'normal',
    priority_reason: '',
});

watch(() => props.show, (newValue) => {
    if (newValue && props.applicant) {
        formData.priority_level = props.applicant.priority_level || 'normal';
        formData.priority_reason = '';
        errors.priority_level = null;
        errors.priority_reason = null;
    }
});

const validateForm = () => {
    errors.priority_level = null;
    errors.priority_reason = null;

    if (!formData.priority_level) {
        errors.priority_level = 'Priority level is required';
    }
    if (!formData.priority_reason || formData.priority_reason.trim().length === 0) {
        errors.priority_reason = 'Reason is required';
    }
    if (formData.priority_reason && formData.priority_reason.length > 500) {
        errors.priority_reason = 'Reason must not exceed 500 characters';
    }

    return !errors.priority_level && !errors.priority_reason;
};

const submit = async () => {
    if (!props.applicant?.profile_id) return;

    if (!validateForm()) {
        toast.error('Please fix the form errors.');
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await axios.post(
            route("applicants.assign-priority", props.applicant.profile_id),
            {
                priority_level: formData.priority_level,
                priority_reason: formData.priority_reason
            }
        );

        toast.success(response.data?.message || 'Priority level assigned successfully!');

        if (props.applicant) {
            props.applicant.priority_level = formData.priority_level;
            props.applicant.priority_reason = formData.priority_reason;
            props.applicant.priority_assigned_at = new Date().toISOString();
        }

        if (refreshActivityLogs) {
            refreshActivityLogs();
        }

        emit('success');
        closeModal();
    } catch (error) {
        console.error('Priority assignment error:', error);

        if (error.response?.status === 422 && error.response?.data?.errors) {
            const serverErrors = error.response.data.errors;
            if (serverErrors.priority_level) {
                errors.priority_level = Array.isArray(serverErrors.priority_level)
                    ? serverErrors.priority_level[0]
                    : serverErrors.priority_level;
            }
            if (serverErrors.priority_reason) {
                errors.priority_reason = Array.isArray(serverErrors.priority_reason)
                    ? serverErrors.priority_reason[0]
                    : serverErrors.priority_reason;
            }
            toast.error('Please check the form for errors.');
        } else if (error.response?.status === 403) {
            toast.error('You do not have permission to assign priority.');
        } else if (error.response?.data?.message) {
            toast.error(error.response.data.message);
        } else {
            toast.error('Failed to assign priority level. Please try again.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    emit('update:show', false);
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 'urgent': return 'bg-red-500';
        case 'high': return 'bg-orange-500';
        case 'normal': return 'bg-blue-500';
        case 'low': return 'bg-gray-500';
        default: return 'bg-gray-400';
    }
};

const getPriorityDot = (priority) => {
    switch (priority) {
        case 'urgent': return '#FF3B30';
        case 'high': return '#FF9500';
        case 'normal': return '#007AFF';
        case 'low': return '#8E8E93';
        default: return '#8E8E93';
    }
};

const getPriorityLabel = (priority) => {
    const option = priorityOptions.find(opt => opt.value === priority);
    return option ? option.label : 'Unknown';
};

const formatPriorityName = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '500px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-checkbox')) return;
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
    <Dialog :visible="show" modal @update:visible="val => !val && closeModal()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal" :disabled="isSubmitting">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">Priority Level</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="isSubmitting">
                        {{ isSubmitting ? 'Saving...' : 'Assign' }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="applicant">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">{{ applicant.last_name }}, {{ applicant.first_name }}</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ applicant.contact_no }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Priority Level Selection -->
                    <div class="ios-section">
                        <div class="ios-section-label">Priority Level</div>
                        <div class="ios-card">
                            <div v-for="(option, index) in priorityOptions" :key="option.value" class="ios-row"
                                :class="{ 'ios-row-last': index === priorityOptions.length - 1 }"
                                style="cursor: pointer;" @click="formData.priority_level = option.value">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="ios-priority-dot"
                                        :style="{ background: getPriorityDot(option.value) }"></span>
                                    <div>
                                        <span class="ios-row-label">{{ option.label }}</span>
                                        <div style="font-size: 12px; color: #8E8E93;">{{ option.description }}</div>
                                    </div>
                                </div>
                                <AppIcon v-if="formData.priority_level === option.value" name="check" :size="14"
                                    style="color: #007AFF;" />
                            </div>
                        </div>
                        <div v-if="errors.priority_level" class="ios-section-footer ios-error">
                            {{ errors.priority_level }}
                        </div>
                    </div>

                    <!-- Priority Reason -->
                    <div class="ios-section">
                        <div class="ios-section-label">Reason</div>
                        <div class="ios-card" style="padding: 12px;">
                            <Textarea v-model="formData.priority_reason" class="w-full border-none shadow-none" rows="4"
                                placeholder="Explain why this applicant should receive priority status..."
                                style="background: transparent; resize: vertical; font-size: 14px;" />
                        </div>
                        <div v-if="errors.priority_reason" class="ios-section-footer ios-error">
                            {{ errors.priority_reason }}
                        </div>
                    </div>

                    <!-- Current Priority (if exists) -->
                    <div v-if="applicant.priority_level && applicant.priority_level !== 'normal'" class="ios-section">
                        <div class="ios-section-label">Current Status</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Level</span>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="ios-priority-dot"
                                        :style="{ background: getPriorityDot(applicant.priority_level) }"></span>
                                    <span style="font-size: 13px; color: #8E8E93;">{{
                                        formatPriorityName(applicant.priority_level) }}</span>
                                </div>
                            </div>
                            <div v-if="applicant.priority_reason" class="ios-row">
                                <span class="ios-row-label">Reason</span>
                                <span style="font-size: 13px; color: #8E8E93; max-width: 60%; text-align: right;">{{
                                    applicant.priority_reason }}</span>
                            </div>
                            <div v-if="applicant.priority_assigned_at" class="ios-row ios-row-last">
                                <span class="ios-row-label">Assigned</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{
                                    formatDate(applicant.priority_assigned_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Component-unique: priority selection dot indicator */
.ios-priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
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
