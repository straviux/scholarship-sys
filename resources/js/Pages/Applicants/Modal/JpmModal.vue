<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    profile: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:show']);

const jpmForm = ref({
    is_jpm_member: false,
    is_father_jpm: false,
    is_mother_jpm: false,
    is_guardian_jpm: false,
    is_not_jpm: false,
    jpm_remarks: ''
});

// Watch for profile changes to initialize form
watch(() => props.profile, (newProfile) => {
    if (newProfile) {
        jpmForm.value = {
            is_jpm_member: Boolean(newProfile.is_jpm_member),
            is_father_jpm: Boolean(newProfile.is_father_jpm),
            is_mother_jpm: Boolean(newProfile.is_mother_jpm),
            is_guardian_jpm: Boolean(newProfile.is_guardian_jpm),
            is_not_jpm: Boolean(newProfile.is_not_jpm),
            jpm_remarks: newProfile.jpm_remarks || ''
        };
    }
}, { immediate: true });

// Computed to check if any JPM member checkbox is checked
const hasAnyJpmMember = computed(() => {
    return jpmForm.value.is_jpm_member ||
        jpmForm.value.is_father_jpm ||
        jpmForm.value.is_mother_jpm ||
        jpmForm.value.is_guardian_jpm;
});

// Watch for "Not JPM" checkbox changes - uncheck all JPM members if checked
watch(() => jpmForm.value.is_not_jpm, (isNotJpm) => {
    if (isNotJpm) {
        jpmForm.value.is_jpm_member = false;
        jpmForm.value.is_father_jpm = false;
        jpmForm.value.is_mother_jpm = false;
        jpmForm.value.is_guardian_jpm = false;
    }
});

// Watch for any JPM member checkbox - uncheck "Not JPM" if any member is checked
watch(() => [
    jpmForm.value.is_jpm_member,
    jpmForm.value.is_father_jpm,
    jpmForm.value.is_mother_jpm,
    jpmForm.value.is_guardian_jpm
], () => {
    if (hasAnyJpmMember.value) {
        jpmForm.value.is_not_jpm = false;
    }
});

// Check if family member exists in profile
const hasFather = computed(() => {
    return props.profile?.father_name && props.profile.father_name.trim() !== '';
});

const hasMother = computed(() => {
    return props.profile?.mother_name && props.profile.mother_name.trim() !== '';
});

const hasGuardian = computed(() => {
    return props.profile?.guardian_name && props.profile.guardian_name.trim() !== '';
});

const closeModal = () => {
    if (props.profile) {
        jpmForm.value = {
            is_jpm_member: Boolean(props.profile.is_jpm_member),
            is_father_jpm: Boolean(props.profile.is_father_jpm),
            is_mother_jpm: Boolean(props.profile.is_mother_jpm),
            is_guardian_jpm: Boolean(props.profile.is_guardian_jpm),
            is_not_jpm: Boolean(props.profile.is_not_jpm),
            jpm_remarks: props.profile.jpm_remarks || ''
        };
    }
    emit('update:show', false);
};

const isResetting = ref(false);

const resetAllJpmStatus = () => {
    isResetting.value = true;
    setTimeout(() => {
        jpmForm.value.is_jpm_member = false;
        jpmForm.value.is_father_jpm = false;
        jpmForm.value.is_mother_jpm = false;
        jpmForm.value.is_guardian_jpm = false;
        jpmForm.value.is_not_jpm = false;
        toast.info('All JPM status cleared');
        setTimeout(() => { isResetting.value = false; }, 300);
    }, 400);
};

const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.success(`${label} copied to clipboard`);
    }).catch(() => {
        toast.error('Failed to copy to clipboard');
    });
};

const saveJpmData = () => {
    if (!props.profile) return;

    const payload = {
        is_jpm_member: jpmForm.value.is_jpm_member ? 1 : 0,
        is_father_jpm: jpmForm.value.is_father_jpm ? 1 : 0,
        is_mother_jpm: jpmForm.value.is_mother_jpm ? 1 : 0,
        is_guardian_jpm: jpmForm.value.is_guardian_jpm ? 1 : 0,
        is_not_jpm: jpmForm.value.is_not_jpm ? 1 : 0,
        jpm_remarks: jpmForm.value.jpm_remarks
    };

    router.put(route('applicants.updateJpmStatus', props.profile.profile_id), payload, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            closeModal();
            toast.success('JPM data updated successfully');
        },
        onError: (errors) => {
            console.error('JPM update errors:', errors);
            toast.error('Failed to update JPM data: ' + Object.values(errors).join(', '));
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
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-checkbox, .p-editor')) return;
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
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">JPM Tagging</span>
                    <button class="ios-nav-btn ios-nav-action" @click="saveJpmData">Save</button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="profile">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant Information</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">{{ profile.last_name }}, {{ profile.first_name }}</span>
                                <button class="ios-copy-btn"
                                    @click="copyToClipboard(`${profile.last_name}, ${profile.first_name}`, 'Applicant name')">
                                    <AppIcon name="copy" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasFather" class="ios-row">
                                <span class="ios-row-label"><span style="color: #8E8E93;">Father:</span> {{
                                    profile.father_name }}</span>
                                <button class="ios-copy-btn"
                                    @click="copyToClipboard(profile.father_name, 'Father name')">
                                    <AppIcon name="copy" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasMother" class="ios-row">
                                <span class="ios-row-label"><span style="color: #8E8E93;">Mother:</span> {{
                                    profile.mother_name }}</span>
                                <button class="ios-copy-btn"
                                    @click="copyToClipboard(profile.mother_name, 'Mother name')">
                                    <AppIcon name="copy" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasGuardian" class="ios-row ios-row-last">
                                <span class="ios-row-label"><span style="color: #8E8E93;">Guardian:</span> {{
                                    profile.guardian_name }}</span>
                                <button class="ios-copy-btn"
                                    @click="copyToClipboard(profile.guardian_name, 'Guardian name')">
                                    <AppIcon name="copy" :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- JPM Member Tagging -->
                    <div class="ios-section">
                        <div class="ios-section-label"
                            style="display: flex; align-items: center; justify-content: space-between;">
                            <span>JPM Member Tagging</span>
                            <button class="ios-copy-btn" style="font-size: 12px;" @click="resetAllJpmStatus"
                                :disabled="!hasAnyJpmMember && !jpmForm.is_not_jpm"
                                :class="isResetting ? 'animate-spin' : ''">
                                <AppIcon name="refresh" :size="14" />
                            </button>
                        </div>
                        <div class="ios-card">
                            <!-- Applicant -->
                            <div class="ios-row" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                <span class="ios-row-label">Applicant</span>
                                <Checkbox v-model="jpmForm.is_jpm_member" binary :disabled="jpmForm.is_not_jpm" />
                            </div>
                            <!-- Father -->
                            <div v-if="hasFather" class="ios-row" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                <span class="ios-row-label">Father</span>
                                <Checkbox v-model="jpmForm.is_father_jpm" binary :disabled="jpmForm.is_not_jpm" />
                            </div>
                            <!-- Mother -->
                            <div v-if="hasMother" class="ios-row" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                <span class="ios-row-label">Mother</span>
                                <Checkbox v-model="jpmForm.is_mother_jpm" binary :disabled="jpmForm.is_not_jpm" />
                            </div>
                            <!-- Guardian -->
                            <div v-if="hasGuardian" class="ios-row ios-row-last"
                                :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                <span class="ios-row-label">Guardian</span>
                                <Checkbox v-model="jpmForm.is_guardian_jpm" binary :disabled="jpmForm.is_not_jpm" />
                            </div>
                        </div>
                    </div>

                    <!-- Not JPM Option -->
                    <div class="ios-section">
                        <div class="ios-card">
                            <div class="ios-row ios-row-last" :style="{ opacity: hasAnyJpmMember ? 0.5 : 1 }">
                                <span class="ios-row-label" style="color: #FF9500;">Not a JPM Member</span>
                                <Checkbox v-model="jpmForm.is_not_jpm" binary :disabled="hasAnyJpmMember" />
                            </div>
                        </div>
                        <div v-if="hasAnyJpmMember" class="ios-section-footer">
                            Uncheck all JPM members above to enable this option
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card">
                            <Editor v-model="jpmForm.jpm_remarks" editorStyle="height: 120px">
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
                        <div class="ios-section-footer">
                            Add any notes or verification details regarding JPM membership.
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Component-unique: copy-to-clipboard button */
.ios-copy-btn {
    background: none;
    border: none;
    color: #8E8E93;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    font-size: 13px;
    transition: opacity 0.15s;
}

.ios-copy-btn:hover {
    opacity: 0.6;
}
</style>

<style>
/* Dark override for JpmModal-unique .ios-copy-btn */
.dark .ios-copy-btn {
    color: #9ca3af !important;
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
