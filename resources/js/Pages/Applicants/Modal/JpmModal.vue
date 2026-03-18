<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';

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
    // Reset form to original profile data when closing without saving
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
    // Trigger spin animation
    isResetting.value = true;

    // Add timeout to show animation before clearing
    setTimeout(() => {
        jpmForm.value.is_jpm_member = false;
        jpmForm.value.is_father_jpm = false;
        jpmForm.value.is_mother_jpm = false;
        jpmForm.value.is_guardian_jpm = false;
        jpmForm.value.is_not_jpm = false;

        toast.info('All JPM status cleared');

        // Stop animation after a brief moment
        setTimeout(() => {
            isResetting.value = false;
        }, 300);
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
        preserveState: false,  // Changed to false to reload fresh data
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
    <Dialog :visible="show" @update:visible="emit('update:show', $event)" :style="{ width: '600px' }"
        header="Edit JPM Information" :modal="true">
        <div class="space-y-4">
            <!-- Applicant Info Header -->
            <div v-if="profile" class="bg-blue-50 p-3 rounded border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div class="font-semibold text-blue-700 text-lg">
                        {{ profile.last_name }}, {{ profile.first_name }}
                    </div>
                    <Button icon="pi pi-copy" size="small" text rounded severity="secondary"
                        @click="copyToClipboard(`${profile.last_name}, ${profile.first_name}`, 'Applicant name')"
                        v-tooltip.top="'Copy applicant name'" />
                </div>
                <div class="text-sm text-gray-600 mt-1 space-y-0.5">
                    <div v-if="hasFather" class="flex items-center justify-between group">
                        <span>
                            <span class="font-medium">Father:</span> {{ profile.father_name }}
                        </span>
                        <Button icon="pi pi-copy" size="small" text rounded severity="secondary"
                            @click="copyToClipboard(profile.father_name, 'Father name')"
                            v-tooltip.top="'Copy father name'" />
                    </div>
                    <div v-if="hasMother" class="flex items-center justify-between group">
                        <span>
                            <span class="font-medium">Mother:</span> {{ profile.mother_name }}
                        </span>
                        <Button icon="pi pi-copy" size="small" text rounded severity="secondary"
                            @click="copyToClipboard(profile.mother_name, 'Mother name')"
                            v-tooltip.top="'Copy mother name'" />
                    </div>
                    <div v-if="hasGuardian" class="flex items-center justify-between group">
                        <span>
                            <span class="font-medium">Guardian:</span> {{ profile.guardian_name }}
                        </span>
                        <Button icon="pi pi-copy" size="small" text rounded severity="secondary"
                            @click="copyToClipboard(profile.guardian_name, 'Guardian name')"
                            v-tooltip.top="'Copy guardian name'" />
                    </div>
                </div>
            </div>

            <!-- JPM Tagging Section -->
            <div class="space-y-3">
                <div class="flex justify-between items-center border-b pb-2">
                    <label class="text-sm font-bold text-gray-700">JPM Member Tagging</label>
                    <Button icon="pi pi-refresh" severity="warn" text rounded @click="resetAllJpmStatus"
                        v-tooltip.top="'Reset all JPM status'" :disabled="!hasAnyJpmMember && !jpmForm.is_not_jpm"
                        :class="isResetting ? 'animate-spin' : 'animate-pulse'" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Applicant - Always visible -->
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                        :class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }">
                        <Checkbox v-model="jpmForm.is_jpm_member" binary inputId="jpm_applicant"
                            :disabled="jpmForm.is_not_jpm" />
                        <span class="text-sm">Applicant</span>
                    </label>

                    <!-- Father - Only show if father name exists -->
                    <label v-if="hasFather" class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                        :class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }">
                        <Checkbox v-model="jpmForm.is_father_jpm" binary inputId="jpm_father"
                            :disabled="jpmForm.is_not_jpm" />
                        <span class="text-sm">Father ({{ profile.father_name }})</span>
                    </label>

                    <!-- Mother - Only show if mother name exists -->
                    <label v-if="hasMother" class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                        :class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }">
                        <Checkbox v-model="jpmForm.is_mother_jpm" binary inputId="jpm_mother"
                            :disabled="jpmForm.is_not_jpm" />
                        <span class="text-sm">Mother ({{ profile.mother_name }})</span>
                    </label>

                    <!-- Guardian - Only show if guardian name exists -->
                    <label v-if="hasGuardian"
                        class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                        :class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }">
                        <Checkbox v-model="jpmForm.is_guardian_jpm" binary inputId="jpm_guardian"
                            :disabled="jpmForm.is_not_jpm" />
                        <span class="text-sm">Guardian ({{ profile.guardian_name }})</span>
                    </label>
                </div>

                <div class="pt-2 border-t">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                        :class="{ 'opacity-50 cursor-not-allowed': hasAnyJpmMember }">
                        <Checkbox v-model="jpmForm.is_not_jpm" binary inputId="jpm_not_member"
                            :disabled="hasAnyJpmMember" />
                        <span class="text-sm font-medium text-orange-600">Not a JPM Member</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-7" v-if="hasAnyJpmMember">
                        <i class="pi pi-info-circle mr-1"></i>
                        Uncheck all JPM members above to enable this option
                    </p>
                </div>
            </div>

            <!-- JPM Remarks Section -->
            <div class="space-y-2">
                <label for="jpmRemarks" class="block text-sm font-bold text-gray-700 border-b pb-2">
                    Remarks</label>
                <Textarea id="jpmRemarks" v-model="jpmForm.jpm_remarks" rows="4"
                    placeholder="Enter additional remarks about JPM status, verification details, etc..."
                    class="w-full" />
                <p class="text-xs text-gray-500">
                    <i class="pi pi-info-circle mr-1"></i>
                    Add any additional notes or verification details regarding JPM membership.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined />
                <Button label="Save JPM Data" severity="success" icon="pi pi-check" @click="saveJpmData" />
            </div>
        </template>
    </Dialog>
</template>

<style scoped></style>
