<template>
    <Dialog :visible="visible" modal :style="{ width: '90vw', maxWidth: '800px' }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="dragStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Edit Family Information</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submitForm" :disabled="saving">
                        <i class="pi pi-check"></i>
                    </button>
                </div>
                <div class="ios-body">
                    <FamilyInformationFields v-model:father_name="form.father_name"
                        v-model:father_occupation="form.father_occupation"
                        v-model:father_contact_no="form.father_contact_no" v-model:mother_name="form.mother_name"
                        v-model:mother_occupation="form.mother_occupation"
                        v-model:mother_contact_no="form.mother_contact_no" v-model:guardian_name="form.guardian_name"
                        v-model:guardian_occupation="form.guardian_occupation"
                        v-model:guardian_relationship="form.guardian_relationship"
                        v-model:guardian_contact_no="form.guardian_contact_no"
                        v-model:parents_guardian_gross_monthly_income="form.parents_guardian_gross_monthly_income"
                        :show-header="false" />

                    <!-- Validation Messages -->
                    <div v-if="validationError"
                        style="margin-top: 12px; background: #FFF2F2; border: 1px solid #FFD2D2; border-radius: 10px; padding: 12px;">
                        <p style="font-size: 13px; color: #FF3B30; font-weight: 500;">
                            <i class="pi pi-exclamation-triangle" style="margin-right: 8px;"></i>
                            {{ validationError }}
                        </p>
                    </div>
                </div>

            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import FamilyInformationFields from '@/Components/forms/fields/FamilyInformationFields.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    profile: Object
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const saving = ref(false);
const validationError = ref('');
const form = ref({
    father_name: '',
    father_occupation: '',
    father_contact_no: '',
    mother_name: '',
    mother_occupation: '',
    mother_contact_no: '',
    guardian_name: '',
    guardian_occupation: '',
    guardian_relationship: '',
    guardian_contact_no: '',
    parents_guardian_gross_monthly_income: ''
});

// Watch profile prop to populate form
watch(() => props.profile, (newProfile) => {
    if (newProfile) {
        form.value = {
            father_name: newProfile.father_name || '',
            father_occupation: newProfile.father_occupation || '',
            father_contact_no: newProfile.father_contact_no || '',
            mother_name: newProfile.mother_name || '',
            mother_occupation: newProfile.mother_occupation || '',
            mother_contact_no: newProfile.mother_contact_no || '',
            guardian_name: newProfile.guardian_name || '',
            guardian_occupation: newProfile.guardian_occupation || '',
            guardian_relationship: newProfile.guardian_relationship || '',
            guardian_contact_no: newProfile.guardian_contact_no || '',
            parents_guardian_gross_monthly_income: newProfile.parents_guardian_gross_monthly_income || ''
        };
        validationError.value = '';
    }
}, { immediate: true, deep: true });

const closeModal = () => {
    emit('update:visible', false);
    validationError.value = '';
    form.value = {
        father_name: '',
        father_occupation: '',
        father_contact_no: '',
        mother_name: '',
        mother_occupation: '',
        mother_contact_no: '',
        guardian_name: '',
        guardian_occupation: '',
        guardian_relationship: '',
        guardian_contact_no: '',
        parents_guardian_gross_monthly_income: ''
    };
};

const submitForm = async () => {
    validationError.value = '';

    saving.value = true;

    try {
        const formData = {
            father_name: form.value.father_name,
            father_occupation: form.value.father_occupation,
            father_contact_no: form.value.father_contact_no,
            mother_name: form.value.mother_name,
            mother_occupation: form.value.mother_occupation,
            mother_contact_no: form.value.mother_contact_no,
            guardian_name: form.value.guardian_name,
            guardian_occupation: form.value.guardian_occupation,
            guardian_relationship: form.value.guardian_relationship,
            guardian_contact_no: form.value.guardian_contact_no,
            parents_guardian_gross_monthly_income: form.value.parents_guardian_gross_monthly_income
        };

        await axios.put(route('scholarship-profiles.update', props.profile.profile_id), formData, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        toast.success('Family information updated successfully');
        emit('success');
        closeModal();
    } catch (error) {
        console.error('Error updating family information:', error);
        console.error('Error response:', error.response?.data);

        if (error.response?.data?.errors) {
            // Handle validation errors
            const errorMessages = Object.values(error.response.data.errors).flat();
            validationError.value = errorMessages.join(' ');
        } else {
            validationError.value = error.response?.data?.message || 'Failed to update family information';
        }
        toast.error(validationError.value);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}

.ios-nav-bar {
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 48px;
    cursor: grab;
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
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.ios-nav-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}


.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 16px;
}

/* Dark mode */
:global(.dark) .ios-modal {
    background: #222831;
}

:global(.dark) .ios-nav-bar {
    background: #2a3040;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-nav-title {
    color: #d1d5db;
}

:global(.dark) .ios-nav-cancel {
    color: #9ca3af;
}

:global(.dark) .ios-nav-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-body {
    color: #d1d5db;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.ios-dialog-root .p-dialog-content {
    padding: 0 !important;
    background: transparent !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
