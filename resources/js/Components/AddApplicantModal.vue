<template>
    <Dialog :visible="visible" modal header="Add New Applicant" :style="{ width: '90vw', maxWidth: '50vw' }"
        @update:visible="handleVisibilityChange" :maximizable="true">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-user-plus text-lg text-green-600"></i>
                <span class="font-semibold text-xl">New Applicant</span>
            </div>
        </template>

        <div class="space-y-4">
            <p class="text-sm text-gray-600">
                Add a new applicant to the waiting list. You can add basic information now and complete the academic
                details later.
            </p>

            <PersonalInformationFields v-model="personalInfo" :show-header="true" />

            <div class="bg-blue-50 border border-blue-200 rounded p-3">
                <p class="text-sm text-blue-800">
                    <i class="pi pi-info-circle mr-2"></i>
                    You can complete the academic information and other details after creating the applicant record.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="handleCancel" />
                <Button label="Add Applicant" icon="pi pi-check" severity="success" @click="handleSubmit"
                    :loading="form.processing" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import PersonalInformationFields from '@/Components/PersonalInformationFields.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'success']);

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    contact_no: '',
    contact_no_2: '',
    email: '',
    date_of_birth: '',
    gender: '',
    place_of_birth: null,
    civil_status: '',
    religion: '',
    indigenous_group: '',
    municipality: null,
    barangay: null,
    address: '',
});

// Computed property for two-way binding with PersonalInformationFields
const personalInfo = computed({
    get: () => ({
        first_name: form.first_name,
        middle_name: form.middle_name,
        last_name: form.last_name,
        extension_name: form.extension_name,
        contact_no: form.contact_no,
        contact_no_2: form.contact_no_2,
        email: form.email,
        date_of_birth: form.date_of_birth,
        gender: form.gender,
        place_of_birth: form.place_of_birth,
        civil_status: form.civil_status,
        religion: form.religion,
        indigenous_group: form.indigenous_group,
        municipality: form.municipality,
        barangay: form.barangay,
        address: form.address,
    }),
    set: (value) => {
        form.first_name = value.first_name;
        form.middle_name = value.middle_name;
        form.last_name = value.last_name;
        form.extension_name = value.extension_name;
        form.contact_no = value.contact_no;
        form.contact_no_2 = value.contact_no_2;
        form.email = value.email;
        form.date_of_birth = value.date_of_birth;
        form.gender = value.gender;
        form.place_of_birth = value.place_of_birth;
        form.civil_status = value.civil_status;
        form.religion = value.religion;
        form.indigenous_group = value.indigenous_group;
        form.municipality = value.municipality;
        form.barangay = value.barangay;
        form.address = value.address;
    }
});

const handleVisibilityChange = (value) => {
    emit('update:visible', value);
    if (!value) {
        form.reset();
    }
};

const handleCancel = () => {
    emit('update:visible', false);
    form.reset();
};

const handleSubmit = () => {
    form.post(route('scholarship.profile.store.applicant'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:visible', false);
            emit('success');
            form.reset();
        },
    });
};
</script>
