<template>
    <IosModal :visible="show" title="Edit User" width="500px" max-width="calc(100vw - 2rem)"
        body-style="padding: 16px;" @update:visible="val => emit('update:show', val)">
        <form @submit.prevent="submit" class="space-y-6 ios-admin-user-form" v-if="user">
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

            <!-- User Info Display -->
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border dark:border-gray-700">
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">User Information</h4>
                <div class="flex items-center gap-3">
                    <Avatar v-if="user.has_profile_photo" :image="user.profile_photo_url"
                        class="border-2 border-gray-200" shape="circle" size="large" />
                    <Avatar v-else :label="user.name.charAt(0).toUpperCase()" class="bg-blue-500 text-white"
                        shape="circle" size="large" />
                    <div>
                        <div class="font-semibold text-gray-800 dark:text-gray-200">{{ user.name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">@{{ user.username }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">User ID: {{ user.id }}</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-gray-200 dark:border-white/10">
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined />
                <AppButton label="Update User" severity="info" @click="submit" :loading="form.processing"
                    icon="pen-to-square" />
            </div>
        </form>
    </IosModal>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    user: Object,
    roles: Array,
});

const emit = defineEmits(['update:show', 'success']);

// Filter out administrator role unless user is already admin
const availableRoles = computed(() => {
    if (!props.roles) return [];

    const isCurrentlyAdmin = props.user?.roles?.some(role => role.name === 'administrator');
    if (isCurrentlyAdmin) {
        return props.roles; // Allow administrator to keep their role
    }
    return props.roles.filter(role => role.name !== 'administrator');
});

const form = useForm({
    name: "",
    username: "",
    office_designation: "",
    roles: null,
});

// Initialize form when user prop changes
watch(() => props.user, (newUser) => {
    if (newUser) {
        form.name = newUser.name || "";
        form.username = newUser.username || "";
        form.office_designation = newUser.office_designation || "";
        form.roles = newUser.roles?.[0] || null;
        form.clearErrors();
    }
}, { immediate: true });

// Reset form when modal is opened
watch(() => props.show, (newValue) => {
    if (newValue && props.user) {
        form.name = props.user.name || "";
        form.username = props.user.username || "";
        form.office_designation = props.user.office_designation || "";
        form.roles = props.user.roles?.[0] || null;
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.user?.id) return;

    form.put(route("users.update", props.user.id), {
        onSuccess: () => {
            toast.success('User updated successfully!');
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

// Helper function to format role names for display
const formatRoleName = (roleName) => {
    if (!roleName) return 'Unknown Role';
    return roleName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};
</script>

