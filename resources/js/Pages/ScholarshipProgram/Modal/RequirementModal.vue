<script setup>
import { ref, computed } from "vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    action: String,
    program: Object,
    requirements: Array,
    msg: String
});

const isOpen = computed(() => props.action == 'update-requirement');
const selectedRequirements = ref([...props.program.requirements].map(r => r.id));
const form = useForm({
    scholarship_program_id: props.program.id,
    name: props.program?.name || "",
    requirements: selectedRequirements || [],
});

const submit = () => {
    form.put(route("scholarshipprograms.update-requirement", props.program.id), {
        onSuccess: (response) => {
            toast.success("data has been updated", {
                position: toast.POSITION.TOP_RIGHT,
            });
        },
        onError: (err) => {
            form.errors = err;
        }
    });
};

const closeModal = () => {
    window.location.href = route('scholarshipprograms.index');
};
</script>


<template>
    <Dialog :visible="isOpen" modal header="Update Requirements Form" :style="{ width: '700px' }"
        @update:visible="closeModal">
        <form @submit.prevent="submit">
            <div class="mt-4 flex justify-between gap-4">
                <div class="w-3/4">
                    <InputLabel for="name" value="Name" />
                    <div
                        class="p-2 bg-gray-100 dark:bg-gray-800 mt-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                        {{
                            props.program.name
                        }}</div>
                </div>
                <div class="w-1/4">
                    <InputLabel for="shortname" value="Shortname" />
                    <div
                        class="p-2 bg-gray-100 dark:bg-gray-800 mt-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                        {{
                            props.program.shortname }}</div>
                </div>
            </div>

            <fieldset class="fieldset bg-base-100 border-base-300 rounded-box w-full border p-4 mt-4 text-lg">
                <legend class="fieldset-legend text-gray-700 dark:text-gray-300">Requirements</legend>
                <label class="flex items-center gap-2 my-2 text-gray-600 dark:text-gray-400 cursor-pointer"
                    v-for="req in requirements" :key="req.id">
                    <Checkbox v-model="form.requirements" :inputId="'req_' + req.id" :value="req.id" />
                    {{ req.name }}
                </label>
            </fieldset>
            <div class="flex items-center justify-end mt-6">
                <Button label="Cancel" severity="secondary" text @click="closeModal" class="mr-2" />
                <Button type="submit" label="Submit" icon="pi pi-check" :loading="form.processing"
                    :disabled="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
