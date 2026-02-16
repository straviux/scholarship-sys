<template>
    <Dialog :visible="visible" modal header="Edit Family Information" :style="{ width: '90vw', maxWidth: '800px' }"
        @update:visible="val => emit('update:visible', val)" :maximizable="true">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-home text-lg text-green-600" style="font-size: 1.2rem;"></i>
                <span class="font-semibold text-xl">Edit Family Information</span>
            </div>
        </template>

        <div class="space-y-4">
            <FamilyInformationFields v-model:father_name="form.father_name"
                v-model:father_occupation="form.father_occupation" v-model:father_contact_no="form.father_contact_no"
                v-model:mother_name="form.mother_name" v-model:mother_occupation="form.mother_occupation"
                v-model:mother_contact_no="form.mother_contact_no" v-model:guardian_name="form.guardian_name"
                v-model:guardian_occupation="form.guardian_occupation"
                v-model:guardian_relationship="form.guardian_relationship"
                v-model:guardian_contact_no="form.guardian_contact_no"
                v-model:parents_guardian_gross_monthly_income="form.parents_guardian_gross_monthly_income"
                :show-header="false" />

            <!-- Validation Messages -->
            <div v-if="validationError" class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                <p class="text-sm text-red-800 font-medium">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    {{ validationError }}
                </p>
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="closeModal" outlined size="small" />
            <Button label="Update" @click="submitForm" :loading="saving" size="small" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
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
