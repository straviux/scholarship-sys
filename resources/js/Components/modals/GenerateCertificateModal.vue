<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ProgressBar from 'primevue/progressbar';

const toast = useToast();

const props = defineProps({
    show: Boolean,
    applicant: Object,
});

const emit = defineEmits(['update:show']);

const certificateFormat = ref('pdf');
const template = ref('approval_certificate');
const loading = ref(false);
const templates = ref([
    { label: 'Approval Certificate', value: 'approval_certificate' },
    { label: 'Completion Certificate', value: 'completion_certificate' },
]);

const formats = ref([
    { label: 'PDF', value: 'pdf' },
    { label: 'DOCX', value: 'docx' },
    { label: 'XLSX', value: 'xlsx' },
]);

const generateCertificate = async () => {
    if (!props.applicant) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No applicant selected' });
        return;
    }

    loading.value = true;

    try {
        const response = await axios.post('/api/jasper/certificate', {
            profile_id: props.applicant.profile_id,
            template: template.value,
            format: certificateFormat.value,
        }, {
            responseType: 'blob',
            timeout: 60000,
        });

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `certificate_${props.applicant.profile_id}.${certificateFormat.value}`);
        document.body.appendChild(link);
        link.click();
        link.parentElement.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Certificate generated successfully',
            life: 3000,
        });

        emit('update:show', false);

    } catch (error) {
        console.error('Certificate generation failed:', error);
        
        let errorMessage = 'Failed to generate certificate';
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 5000,
        });

    } finally {
        loading.value = false;
    }
};

const onHide = () => {
    emit('update:show', false);
};
</script>

<template>
    <Dialog
        :visible="show"
        @update:visible="onHide"
        header="Generate Certificate"
        :modal="true"
        style="width: 500px"
    >
        <div v-if="applicant" class="space-y-4">
            <!-- Applicant Info -->
            <div class="bg-blue-50 p-4 rounded border-l-4 border-blue-500">
                <div class="font-semibold text-blue-900">
                    {{ applicant.last_name }}, {{ applicant.first_name }}
                </div>
                <div class="text-sm text-blue-700">
                    {{ applicant.scholarship_grant?.[0]?.course?.name || 'N/A' }}
                </div>
            </div>

            <!-- Template Selection -->
            <div>
                <label for="template" class="block text-sm font-medium text-gray-700 mb-2">
                    Template
                </label>
                <Select
                    id="template"
                    v-model="template"
                    :options="templates"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select template"
                    class="w-full"
                />
            </div>

            <!-- Format Selection -->
            <div>
                <label for="format" class="block text-sm font-medium text-gray-700 mb-2">
                    Output Format
                </label>
                <Select
                    id="format"
                    v-model="certificateFormat"
                    :options="formats"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select format"
                    class="w-full"
                />
            </div>

            <!-- Loading Progress -->
            <ProgressBar v-if="loading" mode="indeterminate" />
        </div>

        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                @click="onHide"
                :disabled="loading"
            />
            <Button
                label="Generate"
                severity="success"
                @click="generateCertificate"
                :loading="loading"
            />
        </template>
    </Dialog>
</template>

<style scoped>
/* Add custom styles here if needed */
</style>
