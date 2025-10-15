<template>
    <Dialog :visible="visible" modal header="Add Existing Scholar" :style="{ width: '90vw', maxWidth: '900px' }"
        @update:visible="handleVisibilityChange">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-user-edit text-lg text-blue-600"></i>
                <span class="font-semibold">Add Existing Scholar</span>
            </div>
        </template>

        <div class="space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded p-3">
                <p class="text-sm text-amber-800 font-medium">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    All academic information must be filled out to add an existing scholar.
                </p>
            </div>

            <!-- Personal Information Section -->
            <PersonalInformationFields v-model="personalInfo" />

            <Divider />

            <!-- Academic Information Section -->
            <div>
                <h4 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="pi pi-book text-blue-600"></i>
                    Academic Information (Required)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-700">Program *</label>
                        <ProgramSelect v-model="form.program" label="name" custom-placeholder="Select program" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-700">School *</label>
                        <SchoolSelect v-model="form.school" label="name" custom-placeholder="Select school" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-700">Course *</label>
                        <CourseSelect v-model="form.course" label="name" custom-placeholder="Select course" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-700">Year Level *</label>
                        <YearLevelSelect v-model="form.year_level" custom-placeholder="Select year level" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-gray-700">Municipality *</label>
                        <MunicipalitySelect v-model="form.municipality" custom-placeholder="Select municipality" />
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="handleCancel" />
                <Button label="Add Existing Scholar" icon="pi pi-check" severity="info" @click="handleSubmit"
                    :loading="form.processing" :disabled="!isFormValid" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import PersonalInformationFields from '@/Components/PersonalInformationFields.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';

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
    place_of_birth: '',
    civil_status: '',
    religion: '',
    indigenous_group: '',
    program: null,
    school: null,
    course: null,
    year_level: null,
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

const isFormValid = computed(() => {
    return form.first_name &&
        form.last_name &&
        form.program &&
        form.school &&
        form.course &&
        form.year_level &&
        form.municipality;
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
    form.post(route('scholarship.profile.store.existing'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:visible', false);
            emit('success');
            form.reset();
        },
    });
};
</script>
