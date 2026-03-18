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
    requirement: Object,
    msg: String
});

const isOpen = computed(() => props.action == 'create' || props.action == 'edit');

const form = useForm({
    name: props.requirement?.name || "",
    description: props.requirement?.description || "",
    remarks: props.requirement?.remarks || "",
    is_active: props.requirement?.is_active ?? 1,
});

const submit = () => {
    if (props.action == 'create') {
        form.post(route("program_requirements.store"), {
            onSuccess: () => {
                form.reset('name');
                form.reset('description');
                form.reset('remarks');
                form.reset('is_active');
                toast.success("Data has been added", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            },
            onError: (err) => {
                // Error will be handled by form.errors
            }
        });
    } if (props.action == 'edit') {
        form.put(route("program_requirements.update", props.requirement.id), {
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
    window.location.href = route('program_requirements.index');
};
</script>
<template>
    <Dialog :visible="isOpen" modal
        :header="props.action == 'create' ? 'New Requirement Form' : 'Edit Requirement Form'"
        :style="{ width: '600px' }" @update:visible="closeModal">
        <form @submit.prevent="submit" class="mt-4">
            <div class="mt-4 flex justify-between gap-4">
                <div class="w-full">
                    <InputLabel for="name" value="Requirement" />
                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus
                        autocomplete="name" />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="description" value="Description" />
                <TextArea id="description" type="text" class="mt-1 block w-full" v-model="form.description"
                    autocomplete="description" />
                <InputError class="mt-2" :message="form.errors.description" />
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
