<template>

    <Dialog :visible="show" modal :closable="false" header="Change Password" :style="{ width: '400px' }">
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
            <div class="flex justify-end gap-2 mt-6">
                <Button type="button" label="Cancel" @click="onClose" />
                <Button type="submit" label="Submit" severity="success" />
            </div>
        </form>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

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
