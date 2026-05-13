<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

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
    is_unrenewed_jpm: false,
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
            is_unrenewed_jpm: Boolean(newProfile.is_unrenewed_jpm),
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
        jpmForm.value.is_unrenewed_jpm = false;
    }
});

// Watch for "Unrenewed JPM" - mutually exclusive with "Not JPM"
watch(() => jpmForm.value.is_unrenewed_jpm, (isUnrenewed) => {
    if (isUnrenewed) {
        jpmForm.value.is_not_jpm = false;
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

const fullAddress = computed(() => {
    if (!props.profile) return '';
    const parts = [
        props.profile.address,
        props.profile.barangay,
        props.profile.municipality,
    ]
        .map(part => (typeof part === 'string' ? part.trim() : ''))
        .filter(Boolean);
    return parts.join(', ');
});

const hasAddress = computed(() => fullAddress.value !== '');

const subjectLabel = computed(() => {
    const status = props.profile?.latest_scholarship_record?.unified_status;
    return ['active', 'approved', 'completed'].includes(status) ? 'Scholar' : 'Applicant';
});

const closeModal = () => {
    if (props.profile) {
        jpmForm.value = {
            is_jpm_member: Boolean(props.profile.is_jpm_member),
            is_father_jpm: Boolean(props.profile.is_father_jpm),
            is_mother_jpm: Boolean(props.profile.is_mother_jpm),
            is_guardian_jpm: Boolean(props.profile.is_guardian_jpm),
            is_not_jpm: Boolean(props.profile.is_not_jpm),
            is_unrenewed_jpm: Boolean(props.profile.is_unrenewed_jpm),
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
        jpmForm.value.is_unrenewed_jpm = false;
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
        is_unrenewed_jpm: jpmForm.value.is_unrenewed_jpm ? 1 : 0,
        jpm_remarks: jpmForm.value.jpm_remarks
    };

    router.put(route('jpm-tagging.update', props.profile.profile_id), payload, {
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
</script>

<template>
    <IosModal :visible="show" title="JPM Tagging" width="560px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" action-label="Save" @action="saveJpmData"
        @update:visible="val => !val && closeModal()">
        <div v-if="profile">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>{{ subjectLabel }} Information</span>
                            <button class="ios-copy-btn" @click="resetAllJpmStatus"
                                :disabled="!hasAnyJpmMember && !jpmForm.is_not_jpm && !jpmForm.is_unrenewed_jpm"
                                :class="isResetting ? 'animate-spin' : ''"
                                v-tooltip.left="'Clear all JPM tags'">
                                <AppIcon name="refresh" :size="14" />
                            </button>
                        </div>
                        <div class="ios-card">
                            <div class="ios-row">
                               
                                <span class="ios-row-label" style="flex:1">{{ profile.last_name }}, {{ profile.first_name }}</span>
                                
                                <Checkbox v-model="jpmForm.is_jpm_member" class="mr-8" binary :disabled="jpmForm.is_not_jpm" v-tooltip.left="'Tag as JPM member'" />
                                 <button class="ios-copy-btn" @click="copyToClipboard(`${profile.last_name}, ${profile.first_name}`, `${subjectLabel} name`)">
                                    <AppIcon name="clipboard-copy" v-tooltip.left="'Copy'" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasFather" class="ios-row" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                
                                <span class="ios-row-label" style="flex:1"><span style="color: #8E8E93;">Father:</span> {{ profile.father_name }}</span>
                                
                                <Checkbox v-model="jpmForm.is_father_jpm" class="mr-8" binary :disabled="jpmForm.is_not_jpm" v-tooltip.left="'Tag as  JPM member'" />
                                <button class="ios-copy-btn" @click="copyToClipboard(profile.father_name, 'Father name')">
                                    <AppIcon name="clipboard-copy" v-tooltip.left="'Copy'" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasMother" class="ios-row" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                
                                <span class="ios-row-label" style="flex:1"><span style="color: #8E8E93;">Mother:</span> {{ profile.mother_name }}</span>
                               
                                <Checkbox v-model="jpmForm.is_mother_jpm" class="mr-8" binary :disabled="jpmForm.is_not_jpm" v-tooltip.left="'Tag as  JPM member'" />
                                 <button class="ios-copy-btn" @click="copyToClipboard(profile.mother_name, 'Mother name')">
                                    <AppIcon name="clipboard-copy" v-tooltip.left="'Copy'" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasGuardian" class="ios-row" :class="{ 'ios-row-last': !hasAddress }" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                               
                                <span class="ios-row-label" style="flex:1"><span style="color: #8E8E93;">Guardian:</span> {{ profile.guardian_name }}</span>
                                 
                                <Checkbox v-model="jpmForm.is_guardian_jpm"  class="mr-8" binary :disabled="jpmForm.is_not_jpm" v-tooltip.left="'Tag as JPM member'" />
                                <button class="ios-copy-btn" @click="copyToClipboard(profile.guardian_name, 'Guardian name')">
                                    <AppIcon name="clipboard-copy" v-tooltip.left="'Copy'" :size="14" />
                                </button>
                            </div>
                            <div v-if="hasAddress" class="ios-row ios-row-last">
                                <span class="ios-row-label" style="flex:1"><span style="color: #8E8E93;">Address:</span> {{ fullAddress }}</span>
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

                    <!-- Unrenewed JPM Option -->
                    <div class="ios-section">
                        <div class="ios-card">
                            <div class="ios-row ios-row-last" :style="{ opacity: jpmForm.is_not_jpm ? 0.5 : 1 }">
                                <span class="ios-row-label" style="color: #FF3B30;">Unrenewed JPM Member</span>
                                <Checkbox v-model="jpmForm.is_unrenewed_jpm" binary :disabled="jpmForm.is_not_jpm" />
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            Mark if a previously tagged JPM member did not renew.
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
    </IosModal>
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
</style>