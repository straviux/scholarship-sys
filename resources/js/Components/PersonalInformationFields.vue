<template>
    <div>
        <h4 v-if="showHeader" class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i class="pi pi-user text-gray-600"></i>
            <slot name="header">Personal Information</slot>
        </h4>
        <div class="space-y-4">
            <!-- Name Fields Row -->
            <div class="grid grid-cols-1 md:grid-cols-10 gap-4 mt-8">
                <div class="md:col-span-3">
                    <FloatLabel>
                        <InputText :modelValue="modelValue.last_name"
                            @update:modelValue="updateField('last_name', $event)" inputId="last_name" variant="filled"
                            fluid />
                        <label for="last_name">Last Name *</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-3">
                    <FloatLabel>
                        <InputText :modelValue="modelValue.first_name"
                            @update:modelValue="updateField('first_name', $event)" inputId="first_name" variant="filled"
                            fluid />
                        <label for="first_name">First Name *</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-3">
                    <FloatLabel>
                        <InputText :modelValue="modelValue.middle_name"
                            @update:modelValue="updateField('middle_name', $event)" inputId="middle_name"
                            variant="filled" fluid />
                        <label for="middle_name">Middle Name</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-1">
                    <FloatLabel>
                        <InputText :modelValue="modelValue.extension_name"
                            @update:modelValue="updateField('extension_name', $event)" inputId="extension_name"
                            variant="filled" fluid />
                        <label for="extension_name">Ext.</label>
                    </FloatLabel>
                </div>
            </div>

            <!-- Personal Details Row 1-->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                <div>
                    <FloatLabel>
                        <Select :modelValue="modelValue.gender" @update:modelValue="updateField('gender', $event)"
                            :options="genderOptions" optionLabel="label" optionValue="value" inputId="gender"
                            variant="filled" fluid />
                        <label for="gender">Gender</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.date_of_birth" placeholder=""
                            @update:modelValue="updateField('date_of_birth', $event)" type="date"
                            inputId="date_of_birth" variant="filled" fluid />
                        <label for="date_of_birth">Date of Birth</label>
                    </FloatLabel>
                </div>
                <div class="flex flex-col gap-2">
                    <FloatLabel>
                        <MunicipalitySelect :modelValue="modelValue.place_of_birth"
                            @update:modelValue="updateField('place_of_birth', $event)" custom-placeholder="&nbsp;" />
                        <label for="place_of_birth">Place of Birth</label>
                    </FloatLabel>
                </div>

            </div>
            <!-- Personal Details Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                <div>
                    <FloatLabel>
                        <Select :modelValue="modelValue.civil_status"
                            @update:modelValue="updateField('civil_status', $event)" :options="civilStatusOptions"
                            optionLabel="label" optionValue="value" inputId="civil_status" variant="filled" fluid />
                        <label for="civil_status">Civil Status</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.religion"
                            @update:modelValue="updateField('religion', $event)" inputId="religion" variant="filled"
                            fluid />
                        <label for="religion">Religion</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.indigenous_group"
                            @update:modelValue="updateField('indigenous_group', $event)" inputId="indigenous_group"
                            variant="filled" fluid />
                        <label for="indigenous_group">Indigenous Group</label>
                    </FloatLabel>
                </div>
            </div>
            <!-- Personal Details Row 3 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">

                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.contact_no"
                            @update:modelValue="updateField('contact_no', $event)" inputId="contact_no" variant="filled"
                            fluid />
                        <label for="contact_no">Contact Number *</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.contact_no_2"
                            @update:modelValue="updateField('contact_no_2', $event)" inputId="contact_no_2"
                            variant="filled" fluid />
                        <label for="contact_no_2">Secondary Contact Number</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.email" @update:modelValue="updateField('email', $event)"
                            type="email" inputId="email" variant="filled" fluid />
                        <label for="email">Email</label>
                    </FloatLabel>
                </div>

            </div>



            <!-- Address Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                <div>
                    <FloatLabel>
                        <MunicipalitySelect :modelValue="modelValue.municipality"
                            @update:modelValue="updateField('municipality', $event)" custom-placeholder="&nbsp;" />
                        <label for="municipality">Municipality</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <BarangaySelect :modelValue="modelValue.barangay"
                            @update:modelValue="updateField('barangay', $event)" :municipality="modelValue.municipality"
                            custom-placeholder="&nbsp;" />
                        <label for="barangay">Barangay</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="modelValue.address" @update:modelValue="updateField('address', $event)"
                            inputId="address" variant="filled" fluid />
                        <label for="address">Street Address</label>
                    </FloatLabel>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import Select from 'primevue/select';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        default: () => ({
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
        })
    },
    showHeader: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue']);

// Dropdown options
const genderOptions = [
    { label: 'Male', value: 'Male' },
    { label: 'Female', value: 'Female' },
];

const civilStatusOptions = [
    { label: 'Single', value: 'Single' },
    { label: 'Married', value: 'Married' },
    { label: 'Widowed', value: 'Widowed' },
    { label: 'Separated', value: 'Separated' },
    { label: 'N/A', value: 'N/A' },
];

const updateField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value
    });
};
</script>
