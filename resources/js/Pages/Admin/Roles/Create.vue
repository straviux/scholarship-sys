<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import VueMultiselect from "vue-multiselect";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
defineProps({
    permissions: Array,
});
const form = useForm({ name: "", permissions: [] });
</script>

<template>
    <Head title="Create new role" />

    <AdminLayout>
        <template #header>Roles</template>

        <div class="max-w-md mx-auto py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Create New Role
                </h1>
                <Link
                    :href="route('roles.index')"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                    ><ArrowUturnLeftIcon class="h-4 w-4" />
                    <span>Back</span></Link
                >
            </div>
            <div
                class="mt-6 max-w-md mx-auto bg-gray-100 shadow-lg rounded-lg p-6"
            >
                <form @submit.prevent="form.post(route('roles.store'))">
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
                    <div class="mt-4">
                        <InputLabel for="permissions" value="Permissions" />
                        <VueMultiselect
                            v-model="form.permissions"
                            :options="permissions"
                            :multiple="true"
                            :close-on-select="true"
                            placeholder=""
                            label="name"
                            track-by="id"
                        />
                    </div>
                    <div class="flex items-center mt-4">
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
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
