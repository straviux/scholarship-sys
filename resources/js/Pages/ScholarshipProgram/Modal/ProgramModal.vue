<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import TextArea from "@/Components/ui/inputs/TextArea.vue";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    action: String,
    program: Object,
    msg: String
});

const isOpen = computed(() => props.action == 'create' || props.action == 'edit');

const form = useForm({
    name: props.program?.name || "",
    shortname: props.program?.shortname || "",
    description: props.program?.description || "",
    remarks: props.program?.remarks || "",
    start_date: props.program?.start_date || "",
    end_date: props.program?.end_date || "",
    is_active: props.program?.is_active ?? 1,
});

const resetForm = () => {
    form.reset();
}

const submit = () => {
    if (props.action == 'create') {
        form.post(route("scholarshipprograms.store"), {
            onSuccess: () => {
                form.reset('name');
                form.reset('shortname');
                form.reset('description');
                form.reset('remarks');
                form.reset('start_date');
                form.reset('end_date');
                form.reset('requirements');
                toast.success("Program has been added", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
            onError: (err) => {
                // Error will be handled by form.errors
            }
        });
    } if (props.action == 'edit') {
        form.put(route("scholarshipprograms.update", props.program.id), {
            onSuccess: (response) => {
                toast.success("data has been updated", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
            onError: (err) => {
                form.errors = err;
            }
        });
    }
};

const closeModal = () => {
    window.location.href = route('scholarshipprograms.index');
};
</script>


<template>
    <Dialog :visible="isOpen" modal header="Scholarship Program Form" :style="{ width: '700px' }"
        @update:visible="closeModal">
        <form @submit.prevent="submit">
            <div class="mt-4 flex justify-between gap-4">
                <div class="w-3/4">
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                        autocomplete="name" />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <div class="w-1/4">
                    <InputLabel for="shortname" value="Shortname" />
                    <TextInput id="shortname" type="text" class="mt-1 block w-full" v-model="form.shortname" required
                        autocomplete="shortname" />
                    <InputError class="mt-2" :message="form.errors.shortname" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="description" value="Description" />
                <TextArea id="description" type="text" class="mt-1 block w-full" v-model="form.description"
                    autocomplete="description" />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <div class="mt-4 flex justify-between gap-4">
                <div class="w-1/2">
                    <InputLabel for="start_date" value="Start Date" />
                    <TextInput id="start_date" type="date" class="mt-1 block w-full" v-model="form.start_date"
                        autocomplete="start_date" />
                    <InputError class="mt-2" :message="form.errors.start_date" />
                </div>

                <div class="w-1/2">
                    <InputLabel for="end_date" value="End Date" />
                    <TextInput id="end_date" type="date" class="mt-1 block w-full" v-model="form.end_date"
                        autocomplete="end_date" />
                    <InputError class="mt-2" :message="form.errors.end_date" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="remarks" value="Remarks" />
                <TextArea id="remarks" type="text" class="mt-1 block w-full" v-model="form.remarks"
                    autocomplete="remarks" />
                <InputError class="mt-2" :message="form.errors.remarks" />
            </div>

            <div class="mt-6">
                <InputLabel class="mb-1" for="is_active" value="Is Active?" />
                <div class="flex gap-4 mt-2">
                    <div class="flex items-center gap-2">
                        <RadioButton v-model="form.is_active" inputId="is_active_yes" name="is_active" :value="1" />
                        <label for="is_active_yes" class="text-sm font-medium text-gray-900 cursor-pointer">Yes</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <RadioButton v-model="form.is_active" inputId="is_active_no" name="is_active" :value="0" />
                        <label for="is_active_no" class="text-sm font-medium text-gray-900 cursor-pointer">No</label>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.is_active" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <Button label="Cancel" severity="secondary" text @click="closeModal" class="mr-2" />
                <Button type="submit" label="Submit" icon="pi pi-check" :loading="form.processing"
                    :disabled="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
