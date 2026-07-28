<template>

    <IosModal :visible="show" title="Change Password" width="400px" max-width="calc(100vw - 2rem)"
        body-style="padding: 16px;" :dismissable-mask="false" :show-action="true" 
        :loading="form.processing" @close="onClose" @action="submit">
        <div class="mb-4">
            <label class="block mb-1 font-semibold">User</label>
            <InputText :value="user.username" type="text" class="w-full border rounded p-2" disabled />
        </div>
        <form @submit.prevent="submit">

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Password</label>
                <InputText v-model="form.password" type="password" class="w-full border rounded p-2" required
                    minlength="6" />

            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Confirm Password</label>
                <InputText v-model="form.password_confirmation" type="password" class="w-full border rounded p-2"
                    required minlength="6" />

            </div>
            <div v-if="form.errors.password" class="text-red-600 text-xs mt-1">{{ form.errors.password }}</div>
        </form>
    </IosModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    user: Object,
});
const emit = defineEmits(['close', 'success']);

const form = useForm({
    password: '',
    password_confirmation: '',
});

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        form.clearErrors();
    }
});

function onClose() {
    emit('close');
}

function submit() {
    form.post(route('users.changePassword', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            form.reset();
        },
    });
}
</script>
