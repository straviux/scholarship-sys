<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import PrimaryButton from "@/Components/ui/buttons/PrimaryButton.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import VueMultiselect from "vue-multiselect";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
import { computed } from "vue";

const props = defineProps({
    roles: Array,
});

// Filter out administrator role - there should only be one administrator
const availableRoles = computed(() => {
    return props.roles.filter(role => role.name !== 'administrator');
});

const form = useForm({
    name: "",
    username: "",
    password: "",
    password_confirmation: "",
    roles: [],
});


console.log(props.roles);
const submit = () => {
    form.post(route("users.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <AdminLayout>
        <template #header>Create User</template>

        <div class="max-w-md mx-auto p-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Create New User
                </h1>
                <Link :href="route('users.index')"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded-sm flex items-center justify-center gap-1">
                <ArrowUturnLeftIcon class="h-4 w-4" />
                <span>Back</span></Link>
            </div>
            <div class="mt-6 max-w-md mx-auto bg-gray-100 shadow-lg rounded-lg p-6">
                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Name" />

                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required
                            autofocus autocomplete="name" />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="username" value="Username" />

                        <TextInput id="username" type="text" class="mt-1 block w-full" v-model="form.username" required
                            autocomplete="username" />

                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>
                    <div class="mt-4">
                        <InputLabel for="roles" value="Role" />
                        <VueMultiselect v-model="form.roles" :options="availableRoles" :close-on-select="true"
                            placeholder="Select role" label="name" track-by="name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Password" />

                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password"
                            required autocomplete="new-password" />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password_confirmation" value="Confirm Password" />

                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
                            v-model="form.password_confirmation" required autocomplete="new-password" />

                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing">
                            Create User
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
