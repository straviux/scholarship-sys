<template>
    <div>
        <h4 v-if="showHeader" class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i class="pi pi-user text-blue-600"></i>
            <slot name="header">Personal Information</slot>
        </h4>
        <div class="space-y-4">
            <!-- Name Fields Row -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="flex flex-col gap-2 md:col-span-3">
                    <label class="text-sm font-medium text-gray-700">Last Name *</label>
                    <InputText :modelValue="modelValue.last_name" @update:modelValue="updateField('last_name', $event)"
                        placeholder="Enter last name" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-3">
                    <label class="text-sm font-medium text-gray-700">First Name *</label>
                    <InputText :modelValue="modelValue.first_name"
                        @update:modelValue="updateField('first_name', $event)" placeholder="Enter first name" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-4">
                    <label class="text-sm font-medium text-gray-700">Middle Name</label>
                    <InputText :modelValue="modelValue.middle_name"
                        @update:modelValue="updateField('middle_name', $event)" placeholder="Enter middle name" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Ext.</label>
                    <InputText :modelValue="modelValue.extension_name"
                        @update:modelValue="updateField('extension_name', $event)" placeholder="Jr., Sr." />
                </div>
            </div>

            <!-- Contact Fields Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Contact Number *</label>
                    <InputText :modelValue="modelValue.contact_no"
                        @update:modelValue="updateField('contact_no', $event)" placeholder="Enter contact number" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Secondary Contact Number</label>
                    <InputText :modelValue="modelValue.contact_no_2"
                        @update:modelValue="updateField('contact_no_2', $event)"
                        placeholder="Enter secondary contact" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <InputText :modelValue="modelValue.email" @update:modelValue="updateField('email', $event)"
                        type="email" placeholder="Enter email address" />
                </div>
            </div>

            <!-- Personal Details Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                    <InputText :modelValue="modelValue.date_of_birth"
                        @update:modelValue="updateField('date_of_birth', $event)" type="date" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Gender</label>
                    <Select :modelValue="modelValue.gender" @update:modelValue="updateField('gender', $event)"
                        :options="genderOptions" placeholder="Select gender" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Place of Birth</label>
                    <MunicipalitySelect :modelValue="modelValue.place_of_birth"
                        @update:modelValue="updateField('place_of_birth', $event)"
                        custom-placeholder="Select municipality" />
                </div>
            </div>

            <!-- Personal Details Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Civil Status</label>
                    <Select :modelValue="modelValue.civil_status"
                        @update:modelValue="updateField('civil_status', $event)" :options="civilStatusOptions"
                        placeholder="Select civil status" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Religion</label>
                    <InputText :modelValue="modelValue.religion" @update:modelValue="updateField('religion', $event)"
                        placeholder="Enter religion" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-700">Indigenous Group</label>
                    <InputText :modelValue="modelValue.indigenous_group"
                        @update:modelValue="updateField('indigenous_group', $event)"
                        placeholder="Enter indigenous group" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';

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
    { label: 'Divorced', value: 'Divorced' },
];

const updateField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value
    });
};
</script>
