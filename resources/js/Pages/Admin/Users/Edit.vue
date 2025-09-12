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
import { onMounted } from "vue";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
    user: { type: Object, required: true },
    roles: Array,
});
// const form = useForm({ name: props.role.name });

const form = useForm({
    name: props.user?.name,
    username: props.user?.username,
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
        <template #header>Users</template>

        <div class="max-w-md mx-auto p-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Update User
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
                        <VueMultiselect v-model="form.roles" :options="roles" :close-on-select="true"
                            placeholder="Select role" label="name" track-by="name" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing">
                            Update
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
