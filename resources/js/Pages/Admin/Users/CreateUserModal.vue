<template>
    <Dialog :visible="show" @update:visible="val => emit('update:show', val)" modal header="Create New User"
        :style="{ width: 'calc(100vw - 2rem)', maxWidth: '500px' }" :closable="true">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Name Field -->
            <div class="field">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full
                    Name</label>
                <InputText id="name" v-model="form.name" placeholder="Enter full name" class="w-full"
                    :class="{ 'p-invalid': form.errors.name }" />
                <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <!-- Username Field -->
            <div class="field">
                <label for="username"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                <InputText id="username" v-model="form.username" placeholder="Enter username" class="w-full"
                    :class="{ 'p-invalid': form.errors.username }" />
                <small v-if="form.errors.username" class="p-error">{{ form.errors.username }}</small>
            </div>

            <!-- Office Designation Field -->
            <div class="field">
                <label for="office_designation"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Office
                    Designation</label>
                <InputText id="office_designation" v-model="form.office_designation" class="w-full"
                    :class="{ 'p-invalid': form.errors.office_designation }" />
                <small v-if="form.errors.office_designation" class="p-error">{{ form.errors.office_designation
                }}</small>
            </div>

            <!-- Role Selection -->
            <div class="field">
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
                <Select v-model="form.roles" :options="availableRoles" optionLabel="name" placeholder="Select a role"
                    class="w-full" :class="{ 'p-invalid': form.errors.roles }">
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <AppIcon name="shield" :size="14" class="text-gray-500" />
                            <span>{{ formatRoleName(slotProps.option.name) }}</span>
                        </div>
                    </template>
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-2">
                            <AppIcon name="shield" :size="14" class="text-gray-500" />
                            <span>{{ formatRoleName(slotProps.value.name) }}</span>
                        </div>
                        <span v-else class="text-gray-400">Select a role</span>
                    </template>
                </Select>
                <small v-if="form.errors.roles" class="p-error">{{ form.errors.roles }}</small>
            </div>

            <!-- Password Field -->
            <div class="field">
                <label for="password"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                <Password v-model="form.password" placeholder="Enter password" toggleMask class="w-full"
                    :class="{ 'p-invalid': form.errors.password }" :feedback="false" :promptLabel="'Choose a password'"
                    :weakLabel="'Too simple'" :mediumLabel="'Average complexity'" :strongLabel="'Complex password'" />
                <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
                <small v-else class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                    Password should be at least 8 characters long and contain a mix of letters and numbers.
                </small>
            </div>

            <!-- Confirm Password Field -->
            <div class="field">
                <label for="password_confirmation"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm
                    Password</label>
                <Password v-model="form.password_confirmation" placeholder="Confirm password" toggleMask class="w-full"
                    :class="{ 'p-invalid': form.errors.password_confirmation }" :feedback="false" />
                <small v-if="form.errors.password_confirmation" class="p-error">{{ form.errors.password_confirmation
                    }}</small>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined :disabled="form.processing" />
                <AppButton label="Create User" severity="success" @click="submit" :loading="form.processing"
                    icon="user-plus" :disabled="!form.name || !form.username || !form.password || !form.roles" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import { toast } from 'vue3-toastify';

const props = defineProps({
    show: Boolean,
    roles: Array,
});

const emit = defineEmits(['update:show', 'success']);

// Filter out administrator role - there should only be one administrator
const availableRoles = computed(() => {
    return props.roles?.filter(role => role.name !== 'administrator') || [];
});

const form = useForm({
    name: "",
    username: "",
    office_designation: "",
    password: "",
    password_confirmation: "",
    roles: null,
});

// Reset form when modal is opened
watch(() => props.show, (newValue) => {
    if (newValue) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    form.post(route("users.store"), {
        onSuccess: () => {
            toast.success('User created successfully!');
            emit('success');
            closeModal();
        },
        onError: (errors) => {
            if (Object.keys(errors).length > 0) {
                toast.error('Please check the form for errors.');
            }
        },
        onFinish: () => {
            form.reset("password", "password_confirmation");
        },
    });
};

const closeModal = () => {
    emit('update:show', false);
};

// Helper function to format role names for display
const formatRoleName = (roleName) => {
    if (!roleName) return 'Unknown Role';
    return roleName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
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

.custom-dialog :deep(.p-dialog-header) {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.custom-dialog :deep(.p-dialog-content) {
    padding: 2rem;
}

.custom-dialog :deep(.p-dialog-footer) {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem;
}

/* Enhanced form styling */
:deep(.p-inputtext) {
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 0.875rem;
    border: 2px solid #e5e7eb;
    transition: all 0.2s ease;
}

:deep(.p-inputtext:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-password) {
    border-radius: 8px;
}

:deep(.p-password input) {
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 0.875rem;
    border: 2px solid #e5e7eb;
    transition: all 0.2s ease;
}

:deep(.p-select) {
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    transition: all 0.2s ease;
}

:deep(.p-select:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.p-button) {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

:deep(.p-button:hover) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
