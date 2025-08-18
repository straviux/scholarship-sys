<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
const form = useForm({ name: "" });
</script>

<template>
    <Head title="Create new permission" />

    <AdminLayout>
        <template #header>Permissions</template>

        <div class="max-w-md mx-auto py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Create New Permission
                </h1>
                <Link
                    :href="route('permissions.index')"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                    ><ArrowUturnLeftIcon class="h-5 w-5" /> Back</Link
                >
            </div>
            <div
                class="mt-6 max-w-md mx-auto bg-gray-100 shadow-lg rounded-lg p-6"
            >
                <form @submit.prevent="form.post(route('permissions.store'))">
                    <div>
                        <InputLabel for="name" value="Name" />

                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="flex justify-end items-center mt-4">
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Create
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
