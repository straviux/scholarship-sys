<template>
    <Dialog :visible="visible" modal :style="{ width: '90vw', maxWidth: '800px' }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="dragStyle">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal">
                        <AppIcon name="times" />
                    </button>
                    <span class="ios-nav-title">Edit Personal Information</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submitForm" :disabled="saving">
                        <AppIcon name="check" />
                    </button>
                </div>
                <div class="ios-body" style="padding: 16px;">
                    <PersonalInformationFields v-model:first_name="form.first_name"
                        v-model:middle_name="form.middle_name" v-model:last_name="form.last_name"
                        v-model:extension_name="form.extension_name" v-model:contact_no="form.contact_no"
                        v-model:contact_no_2="form.contact_no_2" v-model:email="form.email"
                        v-model:date_of_birth="form.date_of_birth" v-model:gender="form.gender"
                        v-model:place_of_birth="form.place_of_birth" v-model:civil_status="form.civil_status"
                        v-model:religion="form.religion" v-model:indigenous_group="form.indigenous_group"
                        v-model:municipality="form.municipality" v-model:barangay="form.barangay"
                        v-model:address="form.address" v-model:temporary_municipality="form.temporary_municipality"
                        v-model:temporary_barangay="form.temporary_barangay"
                        v-model:temporary_address="form.temporary_address" :show-header="false" />

                    <!-- Validation Messages -->
                    <div v-if="validationError"
                        style="margin-top: 12px; background: #FFF2F2; border: 1px solid #FFD2D2; border-radius: 10px; padding: 12px;">
                        <p style="font-size: 13px; color: #FF3B30; font-weight: 500;">
                            <AppIcon name="exclamation-triangle" style="margin-right: 8px;" />
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
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';
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

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const saving = ref(false);
const validationError = ref('');
const form = ref({
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    contact_no: '',
    contact_no_2: '',
    email: '',
    date_of_birth: null,
    gender: '',
    place_of_birth: '',
    civil_status: '',
    religion: '',
    indigenous_group: '',
    municipality: '',
    barangay: '',
    address: '',
    temporary_municipality: '',
    temporary_barangay: '',
    temporary_address: ''
});

// Watch profile prop to populate form
watch(() => props.profile, (newProfile) => {
    if (newProfile) {
        form.value = {
            first_name: newProfile.first_name || '',
            middle_name: newProfile.middle_name || '',
            last_name: newProfile.last_name || '',
            extension_name: newProfile.extension_name || '',
            contact_no: newProfile.contact_no || '',
            contact_no_2: newProfile.contact_no_2 || '',
            email: newProfile.email || '',
            date_of_birth: newProfile.date_of_birth ? new Date(newProfile.date_of_birth) : null,
            gender: newProfile.gender || '',
            place_of_birth: newProfile.place_of_birth || '',
            civil_status: newProfile.civil_status || '',
            religion: newProfile.religion || '',
            indigenous_group: newProfile.indigenous_group || '',
            municipality: newProfile.municipality || '',
            barangay: newProfile.barangay || '',
            address: newProfile.address || '',
            temporary_municipality: newProfile.temporary_municipality || '',
            temporary_barangay: newProfile.temporary_barangay || '',
            temporary_address: newProfile.temporary_address || ''
        };
    }
}, { immediate: true, deep: true });

const closeModal = () => {
    resetDrag();
    emit('update:visible', false);
    validationError.value = '';
    form.value = {
        first_name: '',
        middle_name: '',
        last_name: '',
        extension_name: '',
        contact_no: '',
        contact_no_2: '',
        email: '',
        date_of_birth: null,
        gender: '',
        place_of_birth: '',
        civil_status: '',
        religion: '',
        indigenous_group: '',
        municipality: '',
        barangay: '',
        address: '',
        temporary_municipality: '',
        temporary_barangay: '',
        temporary_address: ''
    };
};

const submitForm = async () => {
    validationError.value = '';

    // Basic validation
    if (!form.value.first_name?.trim() || !form.value.last_name?.trim()) {
        validationError.value = 'First name and last name are required.';
        return;
    }

    saving.value = true;

    try {
        const formData = {
            first_name: form.value.first_name,
            middle_name: form.value.middle_name,
            last_name: form.value.last_name,
            extension_name: form.value.extension_name,
            contact_no: form.value.contact_no,
            contact_no_2: form.value.contact_no_2,
            email: form.value.email,
            date_of_birth: form.value.date_of_birth ? new Date(form.value.date_of_birth).toISOString().split('T')[0] : null,
            gender: form.value.gender,
            place_of_birth: typeof form.value.place_of_birth === 'object' ? form.value.place_of_birth?.name : form.value.place_of_birth,
            civil_status: form.value.civil_status,
            religion: form.value.religion,
            indigenous_group: form.value.indigenous_group,
            municipality: typeof form.value.municipality === 'object' ? form.value.municipality?.name : form.value.municipality,
            barangay: typeof form.value.barangay === 'object' ? form.value.barangay?.name : form.value.barangay,
            address: form.value.address,
            temporary_municipality: typeof form.value.temporary_municipality === 'object' ? form.value.temporary_municipality?.name : form.value.temporary_municipality,
            temporary_barangay: typeof form.value.temporary_barangay === 'object' ? form.value.temporary_barangay?.name : form.value.temporary_barangay,
            temporary_address: form.value.temporary_address
        };

        await axios.put(route('scholarship-profiles.update', props.profile.profile_id), formData, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        toast.success('Personal information updated successfully');
        emit('success');
        closeModal();
    } catch (error) {
        console.error('Error updating personal information:', error);
        console.error('Error response:', error.response?.data);

        if (error.response?.data?.errors) {
            // Handle validation errors
            const errorMessages = Object.values(error.response.data.errors).flat();
            validationError.value = errorMessages.join(' ');
        } else {
            validationError.value = error.response?.data?.message || 'Failed to update personal information';
        }
        toast.error(validationError.value);
    } finally {
        saving.value = false;
    }
};
</script>
