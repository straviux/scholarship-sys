<template>
    <IosModal
        :visible="visible"
        width="90vw"
        max-width="800px"
        title="Edit Family Information"
        :show-action="true"
        :action-disabled="saving"
        body-style="padding: 16px;"
        @update:visible="val => emit('update:visible', val)"
        @close="closeModal"
        @action="submitForm"
    >
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

        <div v-if="validationError"
            style="margin-top: 12px; background: #FFF2F2; border: 1px solid #FFD2D2; border-radius: 10px; padding: 12px;">
            <p style="font-size: 13px; color: #FF3B30; font-weight: 500;">
                <AppIcon name="exclamation-triangle" style="margin-right: 8px;" />
                {{ validationError }}
            </p>
        </div>
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';
import { ref, watch } from 'vue';
import FamilyInformationFields from '@/Components/forms/fields/FamilyInformationFields.vue';
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    profile: Object
});

const emit = defineEmits(['update:visible', 'success']);

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
