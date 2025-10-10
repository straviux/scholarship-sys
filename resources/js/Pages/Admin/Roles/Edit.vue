<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import VueMultiselect from "vue-multiselect";
import Table from "@/Components/Table.vue";
import TableRow from "@/Components/TableRow.vue";
import TableHeaderCell from "@/Components/TableHeaderCell.vue";
import TableDataCell from "@/Components/TableDataCell.vue";
import { onMounted, watch, computed } from "vue";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
    role: { type: Object, required: true },
    permissions: Array,
});

// Check if this is the administrator role to prevent modification
const isAdministratorRole = computed(() => props.role.name === 'administrator');

const form = useForm({ name: props.role.name, permissions: [] });

onMounted(() => {
    form.permissions = props.role?.permissions;
    console.log(form.permissions);
});

watch(
    () => props.role,
    () => (form.permissions = props.role?.permissions)
);
</script>

<template>

    <Head title="Update role" />

    <AdminLayout>
        <template #header>Roles</template>

        <div class="max-w-md mx-auto py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Update Role
                </h1>
                <Link :href="route('roles.index')"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded-sm flex items-center justify-center gap-1">
                <ArrowUturnLeftIcon class="h-4 w-4" />
                <span>Back</span></Link>
            </div>
            <div class="mt-6 max-w-md mx-auto bg-gray-100 shadow-lg rounded-lg p-6">
                <form @submit.prevent="form.put(route('roles.update', role.id))">
                    <div class="mt-4">
                        <InputLabel for="name" value="Name" />

                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name"
                            :readonly="isAdministratorRole"
                            :class="{ 'bg-gray-200 cursor-not-allowed': isAdministratorRole }" autofocus
                            autocomplete="username" />

                        <p v-if="isAdministratorRole" class="mt-1 text-sm text-gray-600">
                            Administrator role name cannot be modified for security reasons.
                        </p>

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="mt-4">
                        <InputLabel for="permissions" value="Permissions" />
                        <VueMultiselect v-model="form.permissions" :options="permissions" :multiple="true"
                            :close-on-select="true" placeholder="" label="name" track-by="id" />
                    </div>
                    <div class="flex items-center mt-4 justify-end">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Update
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-6 max-w-md mx-auto bg-gray-100 shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-semibold text-indigo-700">Permissions</h1>
            <div class="bg-white mt-2">
                <Table>
                    <template #header>
                        <TableRow class="border-b">
                            <TableHeaderCell>#</TableHeaderCell>
                            <TableHeaderCell>Name</TableHeaderCell>
                            <TableHeaderCell>Action</TableHeaderCell>
                        </TableRow>
                    </template>
                    <template #default>
                        <TableRow v-for="(rolePermission, index) in role.permissions"
                            :key="'rolePermission_' + rolePermission.id">
                            <TableDataCell>{{ index + 1 }}</TableDataCell>
                            <TableDataCell>{{
                                rolePermission.name
                                }}</TableDataCell>
                            <TableDataCell class="space-x-6">
                                <Link :href="route('roles.permission.destroy', [
                                    role.id,
                                    rolePermission.id,
                                ])
                                    " class="text-red-500 hover:text-red-600" method="DELETE" as="button">Revoke</Link>
                            </TableDataCell>
                        </TableRow>
                    </template>
                </Table>
            </div>
        </div>
    </AdminLayout>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
