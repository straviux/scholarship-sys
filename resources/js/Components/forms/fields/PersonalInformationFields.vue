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
                        <InputText :modelValue="last_name" @update:modelValue="$emit('update:last_name', $event)"
                            inputId="last_name" variant="filled" fluid />
                        <label class="text-sm" for="last_name">Last Name *</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-3">
                    <FloatLabel>
                        <InputText :modelValue="first_name" @update:modelValue="$emit('update:first_name', $event)"
                            inputId="first_name" variant="filled" fluid />
                        <label class="text-sm" for="first_name">First Name *</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-3">
                    <FloatLabel>
                        <InputText :modelValue="middle_name" @update:modelValue="$emit('update:middle_name', $event)"
                            inputId="middle_name" variant="filled" fluid />
                        <label class="text-sm" for="middle_name">Middle Name</label>
                    </FloatLabel>
                </div>
                <div class="md:col-span-1">
                    <FloatLabel>
                        <InputText :modelValue="extension_name"
                            @update:modelValue="$emit('update:extension_name', $event)" inputId="extension_name"
                            variant="filled" fluid />
                        <label class="text-sm" for="extension_name">Ext.</label>
                    </FloatLabel>
                </div>
            </div>

            <!-- Personal Details Row 1-->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                <div>
                    <FloatLabel>
                        <GenderSelect :modelValue="gender" @update:modelValue="$emit('update:gender', $event)"
                            inputId="gender" placeholder="&nbsp;" />
                        <label class="text-sm" for="gender">Gender</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <DatePicker :modelValue="date_of_birth" placeholder="mm/dd/yyyy"
                            @update:modelValue="$emit('update:date_of_birth', $event)" type="date"
                            inputId="date_of_birth" variant="filled" showIcon fluid iconDisplay="input"
                            :manualInput="true" @input="formatDateInput" />
                        <label class="text-sm" for="date_of_birth">Date of Birth</label>
                    </FloatLabel>

                </div>
                <div class="flex flex-col gap-2">
                    <FloatLabel v-if="!customPlaceOfBirth">
                        <MunicipalitySelect :modelValue="place_of_birth"
                            @update:modelValue="$emit('update:place_of_birth', $event)" custom-placeholder="&nbsp;" />
                        <label class="text-sm" for="place_of_birth">Place of Birth</label>
                    </FloatLabel>
                    <FloatLabel v-else>
                        <InputText :modelValue="typeof place_of_birth === 'string' ? place_of_birth : ''"
                            @update:modelValue="$emit('update:place_of_birth', $event)" inputId="custom_place_of_birth"
                            variant="filled" fluid />
                        <label class="text-sm" for="custom_place_of_birth">Place of Birth (Custom)</label>
                    </FloatLabel>
                    <div class="flex items-center gap-2 mt-1">
                        <Checkbox v-model="customPlaceOfBirth" inputId="customPlaceOfBirth" binary />
                        <label for="customPlaceOfBirth" class="text-xs text-gray-600 cursor-pointer">Not in list</label>
                    </div>
                </div>

            </div>
            <!-- Personal Details Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                <div>
                    <FloatLabel>
                        <CivilStatusSelect :modelValue="civil_status"
                            @update:modelValue="$emit('update:civil_status', $event)" placeholder="&nbsp;"
                            inputId="civil_status" />
                        <label class="text-sm" for="civil_status">Civil Status</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <ReligionSelect :modelValue="religion" @update:modelValue="$emit('update:religion', $event)"
                            placeholder="&nbsp;" inputId="religion" />
                        <label class="text-sm" for="religion">Religion</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="indigenous_group"
                            @update:modelValue="$emit('update:indigenous_group', $event)" inputId="indigenous_group"
                            variant="filled" fluid />
                        <label class="text-sm" for="indigenous_group">Indigenous Group</label>
                    </FloatLabel>
                </div>
            </div>
            <!-- Personal Details Row 3 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">

                <div>
                    <FloatLabel>
                        <InputText :modelValue="contact_no" @update:modelValue="$emit('update:contact_no', $event)"
                            inputId="contact_no" variant="filled" fluid />
                        <label class="text-sm" for="contact_no">Contact Number *</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="contact_no_2" @update:modelValue="$emit('update:contact_no_2', $event)"
                            inputId="contact_no_2" variant="filled" fluid />
                        <label class="text-sm" for="contact_no_2">Secondary Contact Number</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="email" @update:modelValue="$emit('update:email', $event)" type="email"
                            inputId="email" variant="filled" fluid />
                        <label class="text-sm" for="email">Email</label>
                    </FloatLabel>
                </div>

            </div>



            <!-- Permanent Address Row -->
            <h5 class="text-sm font-semibold text-gray-700 mt-6 mb-4">Permanent Address</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <FloatLabel>
                        <MunicipalitySelect :modelValue="municipality"
                            @update:modelValue="$emit('update:municipality', $event)" custom-placeholder="&nbsp;" />
                        <label class="text-sm" for="municipality">Municipality</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <BarangaySelect :modelValue="barangay" @update:modelValue="$emit('update:barangay', $event)"
                            :municipalityId="municipalityId" custom-placeholder="&nbsp;" />
                        <label class="text-sm" for="barangay">Barangay</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="address" @update:modelValue="$emit('update:address', $event)"
                            inputId="address" variant="filled" fluid />
                        <label class="text-sm" for="address">Street Address</label>
                    </FloatLabel>
                </div>
            </div>

            <!-- Present Address Row -->
            <div class="flex items-center gap-4 mt-6 mb-4">
                <h5 class="text-sm font-semibold text-gray-700">Present Address</h5>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="sameAsPermanent" inputId="sameAsPermanent" binary
                        @change="handleSameAsPermanentChange" />
                    <label for="sameAsPermanent" class="text-sm text-gray-600 cursor-pointer">Same as Permanent
                        Address</label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <FloatLabel>
                        <MunicipalitySelect :modelValue="temporary_municipality"
                            @update:modelValue="$emit('update:temporary_municipality', $event)"
                            custom-placeholder="&nbsp;" :disabled="sameAsPermanent" />
                        <label class="text-sm" for="temporary_municipality">Municipality</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <BarangaySelect :modelValue="temporary_barangay"
                            @update:modelValue="$emit('update:temporary_barangay', $event)"
                            :municipalityId="temporaryMunicipalityId" custom-placeholder="&nbsp;"
                            :disabled="sameAsPermanent" />
                        <label class="text-sm" for="temporary_barangay">Barangay</label>
                    </FloatLabel>
                </div>
                <div>
                    <FloatLabel>
                        <InputText :modelValue="temporary_address"
                            @update:modelValue="$emit('update:temporary_address', $event)" inputId="temporary_address"
                            variant="filled" fluid :disabled="sameAsPermanent" />
                        <label class="text-sm" for="temporary_address">Street Address</label>
                    </FloatLabel>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, watch, computed, ref } from 'vue';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import Checkbox from 'primevue/checkbox';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';
import GenderSelect from '@/Components/selects/GenderSelect.vue';
import CivilStatusSelect from '@/Components/selects/CivilStatusSelect.vue';
import ReligionSelect from '@/Components/selects/ReligionSelect.vue';
import { DatePicker } from 'primevue';

const props = defineProps({
    first_name: String,
    middle_name: String,
    last_name: String,
    extension_name: String,
    contact_no: String,
    contact_no_2: String,
    email: String,
    date_of_birth: [Date, String],
    gender: String,
    place_of_birth: [String, Object],
    civil_status: String,
    religion: String,
    indigenous_group: String,
    municipality: [String, Object],
    barangay: [String, Object],
    address: String,
    temporary_municipality: [String, Object],
    temporary_barangay: [String, Object],
    temporary_address: String,
    showHeader: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits([
    'update:first_name',
    'update:middle_name',
    'update:last_name',
    'update:extension_name',
    'update:contact_no',
    'update:contact_no_2',
    'update:email',
    'update:date_of_birth',
    'update:gender',
    'update:place_of_birth',
    'update:civil_status',
    'update:religion',
    'update:indigenous_group',
    'update:municipality',
    'update:barangay',
    'update:address',
    'update:temporary_municipality',
    'update:temporary_barangay',
    'update:temporary_address',
]);

// Extract municipality ID for BarangaySelect
const municipalityId = computed(() => {
    if (!props.municipality) return null;

    // If it's an object, extract the ID
    if (typeof props.municipality === 'object') {
        return props.municipality.id || null;
    }

    // If it's already a number or numeric string, use it directly
    return props.municipality;
});

// Extract temporary municipality ID for BarangaySelect
const temporaryMunicipalityId = computed(() => {
    if (!props.temporary_municipality) return null;

    // If it's an object, extract the ID
    if (typeof props.temporary_municipality === 'object') {
        return props.temporary_municipality.id || null;
    }

    // If it's already a number or numeric string, use it directly
    return props.temporary_municipality;
});

// Checkbox state for custom place of birth
const customPlaceOfBirth = ref(false);

// Watch for changes in place_of_birth to detect if it's a custom string
watch(() => props.place_of_birth, (newValue) => {
    if (typeof newValue === 'string' && newValue !== '') {
        customPlaceOfBirth.value = true;
    }
}, { immediate: true });

// Watch for checkbox change to clear place of birth when switching modes
watch(customPlaceOfBirth, (newValue) => {
    if (newValue) {
        // Switching to custom text field - clear if it's an object
        if (typeof props.place_of_birth === 'object') {
            emit('update:place_of_birth', '');
        }
    } else {
        // Switching back to dropdown - clear text value
        emit('update:place_of_birth', null);
    }
});

// Checkbox state for "Same as Permanent Address"
const sameAsPermanent = ref(false);

// Handle checkbox change - copy permanent address to present address
const handleSameAsPermanentChange = () => {
    if (sameAsPermanent.value) {
        // Copy permanent address to present address
        emit('update:temporary_municipality', props.municipality);
        emit('update:temporary_barangay', props.barangay);
        emit('update:temporary_address', props.address);
    } else {
        // Clear present address when unchecked
        emit('update:temporary_municipality', null);
        emit('update:temporary_barangay', null);
        emit('update:temporary_address', '');
    }
};

// Format date input as user types (auto-insert slashes)
const formatDateInput = (event) => {
    const input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove non-digits

    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }

    input.value = value;

    // Try to parse and emit the date if it's complete
    if (value.length === 10) {
        const [month, day, year] = value.split('/');
        const date = new Date(year, month - 1, day);
        if (!isNaN(date.getTime())) {
            emit('update:date_of_birth', date);
        }
    }
};

onMounted(() => {
    console.log('PersonalInformationFields mounted with props:', props);
});

</script>

<style>
.p-inputtext.p-variant-filled {
    background-color: #ffffff !important;
}

.p-select.p-variant-filled .p-select-label {
    background-color: #ffffff !important;
}

.p-datepicker.p-variant-filled .p-datepicker-input {
    background-color: #ffffff !important;
}
</style>
