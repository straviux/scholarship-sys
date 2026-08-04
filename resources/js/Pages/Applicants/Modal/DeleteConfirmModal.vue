<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';
import IosConfirmDialog from '@/Components/ui/IosConfirmDialog.vue';

const props = defineProps({
    show: Boolean,
    applicant: Object,
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'deleted']);

const deleting = ref(false);

const close = () => {
    emit('update:show', false);
};

const message = 'Permanently delete this applicant? This action cannot be undone.';

const data = computed(() => {
    if (!props.applicant) return [];
    return [
        { label: 'Name', value: `${props.applicant.last_name}, ${props.applicant.first_name}`, color: '#FF3B30' },
        { label: 'Contact', value: props.applicant.contact_no },
    ];
});

const deleteApplicant = () => {
    if (!props.applicant || deleting.value) return;
    deleting.value = true;

    router.delete(route('applicants.destroy', props.applicant.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = false;
            close();
            toast.success('Applicant deleted successfully');
            emit('deleted');
            if (props.refreshActivityLogs) props.refreshActivityLogs();
        },
        onError: () => {
            deleting.value = false;
            close();
            toast.error('Failed to delete applicant');
        }
    });
};
</script>

<template>
    <IosConfirmDialog
        :visible="show"
        title="Confirm Deletion"
        :message="message"
        data-label="Applicant"
        :data="data"
        accept-label="Delete"
        :loading="deleting"
        @accept="deleteApplicant"
        @update:visible="val => !val && close()"
    />
</template>
