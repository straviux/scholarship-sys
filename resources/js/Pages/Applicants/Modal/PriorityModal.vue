<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal header="🎯 Assign Priority Level"
        :style="{ width: '500px' }" :closable="true">
        <div class="space-y-6" v-if="applicant">
            <!-- Applicant Info -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-500 p-2 rounded-lg">
                        <i class="pi pi-user text-white"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-800">{{ applicant.last_name }}, {{ applicant.first_name }}
                        </h4>
                        <p class="text-sm text-blue-600">{{ applicant.contact_no }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Priority Level Selection -->
                <div class="field">
                    <label for="priority_level" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="pi pi-flag text-gray-400 mr-1"></i>
                        Priority Level *
                    </label>
                    <Select v-model="form.priority_level" :options="priorityOptions" optionLabel="label"
                        optionValue="value" placeholder="Select priority level" class="w-full"
                        :class="{ 'p-invalid': form.errors.priority_level }">
                        <template #option="slotProps">
                            <div class="flex items-center gap-3 p-2">
                                <div class="w-4 h-4 rounded-full" :class="getPriorityColor(slotProps.option.value)">
                                </div>
                                <div>
                                    <div class="font-medium">{{ slotProps.option.label }}</div>
                                    <div class="text-xs text-gray-500">{{ slotProps.option.description }}</div>
                                </div>
                            </div>
                        </template>
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" :class="getPriorityColor(slotProps.value)"></div>
                                <span>{{ getPriorityLabel(slotProps.value) }}</span>
                            </div>
                            <span v-else class="text-gray-400">Select priority level</span>
                        </template>
                    </Select>
                    <small v-if="form.errors.priority_level" class="p-error">{{ form.errors.priority_level }}</small>
                </div>

                <!-- Priority Reason -->
                <div class="field">
                    <label for="priority_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="pi pi-comment text-gray-400 mr-1"></i>
                        Reason for Priority Assignment *
                    </label>
                    <Textarea v-model="form.priority_reason" rows="4"
                        placeholder="Please explain why this applicant should receive priority status..." class="w-full"
                        :class="{ 'p-invalid': form.errors.priority_reason }" />
                    <small v-if="form.errors.priority_reason" class="p-error">{{ form.errors.priority_reason }}</small>
                </div>

                <!-- Current Priority Display (if exists) -->
                <div v-if="applicant.priority_level && applicant.priority_level !== 'normal'"
                    class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <h5 class="font-medium text-amber-800 mb-2">Current Priority Status:</h5>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full" :class="getPriorityColor(applicant.priority_level)"></div>
                        <span class="font-medium">{{ formatPriorityName(applicant.priority_level) }}</span>
                    </div>
                    <p class="text-sm text-amber-700" v-if="applicant.priority_reason">
                        {{ applicant.priority_reason }}
                    </p>
                    <p class="text-xs text-amber-600 mt-2" v-if="applicant.priority_assigned_at">
                        Assigned {{ formatDate(applicant.priority_assigned_at) }}
                    </p>
                </div>
            </form>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined icon="pi pi-times"
                    :disabled="form.processing" />
                <Button label="Assign Priority" severity="warn" @click="submit" :loading="form.processing"
                    icon="pi pi-flag" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";
import { toast } from 'vue3-toastify';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const props = defineProps({
    show: Boolean,
    applicant: Object,
});

const emit = defineEmits(['update:show', 'success']);

const priorityOptions = [
    { value: 'low', label: 'Low Priority', description: 'Standard processing order' },
    { value: 'normal', label: 'Normal Priority', description: 'Default priority level' },
    { value: 'high', label: 'High Priority', description: 'Faster processing' },
    { value: 'urgent', label: 'Urgent Priority', description: 'Immediate attention required' }
];

const form = useForm({
    priority_level: 'normal',
    priority_reason: '',
});

// Reset form when modal is opened
watch(() => props.show, (newValue) => {
    if (newValue && props.applicant) {
        form.priority_level = props.applicant.priority_level || 'normal';
        form.priority_reason = '';
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.applicant?.profile_id) return;

    form.post(route("applicants.assign-priority", props.applicant.profile_id), {
        onSuccess: () => {
            toast.success('Priority level assigned successfully!');
            emit('success');
            closeModal();
        },
        onError: (errors) => {
            if (Object.keys(errors).length > 0) {
                toast.error('Please check the form for errors.');
            }
        },
    });
};

const closeModal = () => {
    emit('update:show', false);
};

// Helper functions
const getPriorityColor = (priority) => {
    switch (priority) {
        case 'urgent': return 'bg-red-500';
        case 'high': return 'bg-orange-500';
        case 'normal': return 'bg-blue-500';
        case 'low': return 'bg-gray-500';
        default: return 'bg-gray-400';
    }
};

const getPriorityLabel = (priority) => {
    const option = priorityOptions.find(opt => opt.value === priority);
    return option ? option.label : 'Unknown';
};

const formatPriorityName = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<style scoped>
.field {
    margin-bottom: 1rem;
}

.p-error {
    color: var(--red-500);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.p-invalid {
    border-color: var(--red-500);
}
</style>