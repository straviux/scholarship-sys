<template>
    <div>
        <h4 v-if="showHeader" class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">
            <slot name="header">Family Information</slot>
        </h4>
        <div class="space-y-4">
            <!-- Father's Information Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <InputText :modelValue="father_name" @update:modelValue="$emit('update:father_name', $event)"
                        inputId="father_name" variant="filled" fluid />
                    <label for="father_name" class="text-sm">Father's Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="father_occupation"
                        @update:modelValue="$emit('update:father_occupation', $event)" inputId="father_occupation"
                        fluid />
                    <label for="father_occupation" class="text-sm">Father's Occupation</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="father_contact_no"
                        @update:modelValue="$emit('update:father_contact_no', $event)" inputId="father_contact_no"
                        fluid />
                    <label for="father_contact_no" class="text-sm">Father's Contact #</label>
                </FloatLabel>
            </div>

            <!-- Mother's Information Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <InputText :modelValue="mother_name" @update:modelValue="$emit('update:mother_name', $event)"
                        inputId="mother_name" variant="filled" fluid />
                    <label for="mother_name" class="text-sm">Mother's Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="mother_occupation"
                        @update:modelValue="$emit('update:mother_occupation', $event)" inputId="mother_occupation"
                        fluid />
                    <label for="mother_occupation" class="text-sm">Mother's Occupation</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="mother_contact_no"
                        @update:modelValue="$emit('update:mother_contact_no', $event)" inputId="mother_contact_no"
                        fluid />
                    <label for="mother_contact_no" class="text-sm">Mother's Contact #</label>
                </FloatLabel>
            </div>

            <!-- Guardian Information Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-10">
                <FloatLabel>
                    <InputText :modelValue="guardian_name" @update:modelValue="$emit('update:guardian_name', $event)"
                        inputId="guardian_name" fluid />
                    <label for="guardian_name" class="text-sm">Guardian Name</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="guardian_occupation"
                        @update:modelValue="$emit('update:guardian_occupation', $event)" inputId="guardian_occupation"
                        fluid />
                    <label for="guardian_occupation" class="text-sm">Guardian Occupation</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="guardian_relationship"
                        @update:modelValue="$emit('update:guardian_relationship', $event)"
                        inputId="guardian_relationship" fluid />
                    <label for="guardian_relationship" class="text-sm">Relationship</label>
                </FloatLabel>

                <FloatLabel>
                    <InputText :modelValue="guardian_contact_no"
                        @update:modelValue="$emit('update:guardian_contact_no', $event)" inputId="guardian_contact_no"
                        fluid />
                    <label for="guardian_contact_no" class="text-sm">Guardian Contact #</label>
                </FloatLabel>
            </div>

            <!-- Gross Monthly Income Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-10">
                <FloatLabel>
                    <InputText :modelValue="parents_guardian_gross_monthly_income"
                        @update:modelValue="$emit('update:parents_guardian_gross_monthly_income', $event)"
                        inputId="parents_guardian_gross_monthly_income" fluid type="number" />
                    <label for="parents_guardian_gross_monthly_income" class="text-sm">Parents/Guardian Gross Monthly
                        Income</label>
                </FloatLabel>
            </div>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    father_name: String,
    father_occupation: String,
    father_contact_no: String,
    mother_name: String,
    mother_occupation: String,
    mother_contact_no: String,
    guardian_name: String,
    guardian_occupation: String,
    guardian_relationship: String,
    guardian_contact_no: String,
    parents_guardian_gross_monthly_income: [String, Number],
    showHeader: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits([
    'update:father_name',
    'update:father_occupation',
    'update:father_contact_no',
    'update:mother_name',
    'update:mother_occupation',
    'update:mother_contact_no',
    'update:guardian_name',
    'update:guardian_occupation',
    'update:guardian_relationship',
    'update:guardian_contact_no',
    'update:parents_guardian_gross_monthly_income',
]);
</script>
