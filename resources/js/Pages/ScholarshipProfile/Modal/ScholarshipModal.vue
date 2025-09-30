<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-10">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/75" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex justify-center p-4 text-center items-center min-h-screen">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-5xl transform overflow-hidden rounded-sm  text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3"
                                class="text-normal font-medium leading-6 bg-[#222831] text-white flex items-center justify-between px-4 py-2">
                                <span>View Scholarship</span>

                                <Link class="-mr-2 " :href="route('profile.index', { id: profile_id, action: 'view' })">
                                <XMarkIcon class="h-8 w-8 text-red-500" />
                                </Link>
                            </DialogTitle>
                            <div class="p-4 bg-white">

                                <div class="my-2 ">
                                    <div class="py-2 px-4 bg-gray-100 rounded">
                                        <span class="text-normal font-medium text-gray-600">{{ profile_name }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-4 rounded-lg">

                                    <div class="w-1/2">
                                        <ScholarshipTable :profile="scholarship_record" />
                                    </div>
                                    <div class="w-1/2 px-4 flex">
                                        <fieldset
                                            class="fieldset bg-base-100 border-base-300 rounded-box w-full border p-4 text-xs">
                                            <legend class="fieldset-legend text-gray-700">Requirements</legend>
                                            <div class="p-6 space-y-6">

                                                <div v-for="(req, i) in scholarship_record.program.requirements"
                                                    :key="req.id" class="p-2 border rounded-lg">
                                                    <div class="flex">
                                                        <p class="font-semibold w-full">{{ req.name }}</p>
                                                    </div>

                                                    <div class="flex my-4">
                                                        <!-- {{ scholarship_record.requirements }} -->

                                                        <div v-if="scholarship_record.requirements[i]?.requirement_id == req.id"
                                                            class="text-green-600 text-sm w-full">
                                                            ✅ Uploaded: {{
                                                                scholarship_record.requirements[i].file_name }}
                                                        </div>
                                                    </div>
                                                    <!-- Hidden input with proper ref setter -->
                                                    <input type="file" class="hidden"
                                                        :ref="el => setFileInputRef(el, req.id)"
                                                        @change="e => handleFileChange(e, req.id)" />
                                                    <!-- Show selected file -->
                                                    <p v-if="forms[req.id].file" class="my-2 text-sm text-gray-600">
                                                        📂 Selected: {{ forms[req.id].file.name }}
                                                    </p>
                                                    <div class="flex justify-between">
                                                        <!-- Trigger button -->
                                                        <button type="button"
                                                            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 w-1/2 cursor-pointer"
                                                            @click="triggerFilePicker(req.id)">
                                                            Choose File
                                                        </button>

                                                        <!-- Upload button -->
                                                        <button type="button"
                                                            class="ml-2 px-4 py-2 bg-blue-600 text-white rounded  cursor-pointer"
                                                            :disabled="!forms[req.id].file || forms[req.id].processing"
                                                            @click="submitForm(req.id)">
                                                            Upload
                                                        </button>
                                                    </div>


                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import { XMarkIcon } from "@heroicons/vue/20/solid";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import ScholarshipTable from "../Components/ScholarshipTable.vue";

const props = defineProps({
    profile_id: [String, Number],
    profile_name: String,
    scholarship_record: Object,
    action: String,
    msg: String,
    errors: Object
});

// console.log(props.scholarship_record)
const forms = ref({})
const fileInputs = ref({})
// Initialize form objects per requirement
props.scholarship_record.program.requirements.forEach(req => {
    forms.value[req.id] = useForm({
        requirement_id: req.id,
        file: null,
    });
    fileInputs.value[req.id] = ref(null)
})

const emit = defineEmits(['success'])
const isOpen = computed(() => props.action == 'view-scholarship');

// Helper: assign refs into our object
function setFileInputRef(el, reqId) {
    if (el) {
        fileInputs.value[reqId] = el
    }
}

function triggerFilePicker(reqId) {
    fileInputs.value[reqId].click()
}

function handleFileChange(e, reqId) {
    forms.value[reqId].file = e.target.files[0]
}

function submitForm(reqId) {
    forms.value[reqId].post(
        route('scholarship.requirements.upload', props.scholarship_record.id),
        {
            onSuccess: () => {
                forms.value[reqId].reset('file');
                toast.success("File has been uploaded", {
                    position: toast.POSITION.TOP_RIGHT,
                });
            }
        }
    )
}
onMounted(() => {

})
</script>
<style></style>
