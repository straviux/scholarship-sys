<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Table from "@/Components/ui/table/Table.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHeaderCell from "@/Components/ui/table/TableHeaderCell.vue";
import TableDataCell from "@/Components/ui/table/TableDataCell.vue";
import { onMounted, computed } from "vue";

const props = defineProps({
    user: { type: Object, required: true },
    roles: Array,
});

// Filter out administrator role - there should only be one administrator
// However, if current user is already administrator, allow keeping that role
const availableRoles = computed(() => {
    const isCurrentlyAdmin = props.user?.roles?.some(role => role.name === 'administrator');
    if (isCurrentlyAdmin) {
        return props.roles; // Allow administrator to keep their role
    }
    return props.roles.filter(role => role.name !== 'administrator');
});

const form = useForm({
    name: props.user?.name,
    username: props.user?.username,
    office_designation: props.user?.office_designation,
    roles: [],
});

onMounted(() => {
    form.roles = props.user?.roles[0];
    console.log(form.roles);
});

const submit = () => {
    form.put(route("users.update", props.user?.id));
};
</script>

<template>
    <AdminLayout>
        <template #header>Edit User</template>

        <div class="max-w-md mx-auto p-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Update User
                </h1>
                <Link :href="route('users.index')"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded-sm flex items-center justify-center gap-1">
                    <i class="pi pi-arrow-left" />
                    <span>Back</span>
                </Link>
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
                        <InputLabel for="office_designation" value="Office Designation" />

                        <TextInput id="office_designation" type="text" class="mt-1 block w-full"
                            v-model="form.office_designation" autocomplete="off" />

                        <InputError class="mt-2" :message="form.errors.office_designation" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="roles" value="Role" />
                        <Select v-model="form.roles" :options="availableRoles" optionLabel="name"
                            placeholder="Select role" class="w-full" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <Button type="submit" label="Update" icon="pi pi-check" :loading="form.processing" />
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
