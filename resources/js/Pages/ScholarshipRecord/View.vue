<template>

    <Head title="Scholarship" />

    <AdminLayout>
        <template #header> Scholarship Record</template>

        <div class="px-4">
            <div class="flex justify-between">
                <div class="breadcrumbs text-sm mb-4">
                    <ul>
                        <li><a :href="route('dashboard')">Home</a></li>
                        <li><a :href="route('scholarship_records.index')">Scholarship Records</a></li>
                        <li>View Record</li>
                    </ul>
                </div>
                <a :href="route('scholarship_records.index')" class="text-blue-800 underline">Go Back</a>
            </div>
            <!-- {{ profile }} -->
            <div class="flex flex-col md:flex-row  gap-8">
                <!-- main container -->
                <div class="w-full lg:w-[50%] xl:w-[70%] bg-white border shadow-lg rounded p-4 self-start">
                    <div class="flex justify-between">
                        <div class="flex items-center">
                            <figure>
                                <img v-if="record.profile.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                                    class="rounded-xl w-24" />

                                <img v-if="record.profile.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                                    class="rounded-xl w-24" />
                            </figure>
                            <div>
                                <h3>{{ record.profile.last_name }}, {{ record.profile.first_name }} {{
                                    record.profile.middle_name
                                    || '' }} {{
                                        record.profile.extension_name
                                        || '' }}</h3>

                                <div class="flex gap-2">
                                    <DynamicHeroicon name="calendar" :outline="true" :size="4" class="text-gray-500" />
                                    <span class="text-xs text-gray-600">Date Filed
                                        {{ record.date_filed ? moment(record.date_filed).format('MMM DD, YYYY') : ''
                                        }}</span>
                                </div>

                            </div>
                        </div>
                        <button class="btn btn-xs text-white bg-slate-700" @click="toggleRemarkModal">Remarks</button>
                    </div>

                    <div class="px-2">
                        <div class="flex flex-col md:flex-row md:items-center gap-2 mt-6">
                            <div class="w-full lg:w-[12%] text-gray-800">Academic Info:</div>
                            <div class="flex gap-2">
                                <div class=" badge badge-neutral badge-outline badge-sm">{{
                                    record.academic_year }}
                                </div>
                                <div class="badge badge-neutral badge-outline badge-sm">{{ record.term }}
                                </div>
                                <div class="badge badge-neutral badge-outline badge-sm">{{ record.year_level
                                }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center  gap-2 mt-4">
                            <div class="w-full lg:w-[12%] text-gray-800">School Name:</div>
                            <span class="uppercase">{{ record.school_name }}</span>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center  gap-2 mt-4">
                            <div class="w-full lg:w-[12%] text-gray-800">Program:</div>
                            <span class="uppercase">{{ record.program?.name || '-' }} ({{ record.program?.shortname ||
                                '-'
                            }})</span>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center  gap-2 mt-4">
                            <div class="w-full lg:w-[12%] text-gray-800">Course:</div>
                            <span class="uppercase">{{ record.course?.name || '-' }} ({{ record.course?.shortname || '-'
                            }})</span>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center  gap-2 mt-4">
                            <div class="w-full lg:w-[12%] text-gray-800">Status:</div>
                            <span class="text-orange-500" v-if="record.scholarship_status == 0">Pending</span>
                            <span class="text-emerald-500" v-if="record.scholarship_status == 1">Active</span>
                            <span class="text-blue-500" v-if="record.scholarship_status == 2">Completed</span>
                            <span class="text-red-500" v-if="record.scholarship_status == 3">Suspended</span>
                            <span class="text-red-500" v-if="record.scholarship_status == 4">Cancelled</span>
                            <div class="flex items-stretch" v-if="hasRole('administrator')">
                                <div class="dropdown dropdown-end ml-4">
                                    <div tabindex="0" role="button"
                                        class="btn btn-xs bg-slate-700 text-xs px-4 text-white uppercase">
                                        Update</div>
                                    <ul tabindex="0"
                                        class="menu dropdown-content bg-slate-700 border rounded-sm z-1 mt-1 p-2 shadow-lg">
                                        <li> <button @click="updatescholarshipstatus(1)"
                                                class="text-emerald-400 text-shadow-xs font-medium underline underline-offset-2">Approve
                                            </button></li>
                                        <li> <button @click="updatescholarshipstatus(2)"
                                                class="text-blue-400 text-shadow-xs font-medium underline underline-offset-2">Completed
                                            </button></li>
                                        <li> <button @click="updatescholarshipstatus(3)"
                                                class="text-orange-400 text-shadow-xs font-medium underline underline-offset-2">Suspend
                                            </button></li>
                                        <li> <button @click="updatescholarshipstatus(4)"
                                                class="text-red-400 text-shadow-xs font-medium underline underline-offset-2">End
                                            </button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center gap-2 mt-4">
                            <div class="w-full lg:w-[12%] text-gray-800">Remarks:</div>
                            <span v-html="record.remarks" class="prose"></span>

                        </div>
                    </div>
                </div>

                <!-- side container -->
                <div class="w-full lg:w-[50%] xl:w-[30%] bg-white border shadow-lg rounded px-4 py-2 self-start">
                    <h3 class="p-2 text-lg text-gray-700">Requirements</h3>
                    <div class="p-2 space-y-6">
                        <div v-for="(req, i) in record.program.requirements" :key="req.id"
                            class="p-2 border rounded-lg">
                            <div class="flex">
                                <p class="font-semibold w-full text-sm text-gray-800">{{ req.name }}</p>
                            </div>

                            <div class="flex my-4">
                                <!-- {{ scholarship_record.requirements }} -->

                                <div v-if="record.requirements[i]?.requirement_id == req.id"
                                    class="text-green-600 w-full">
                                    <!-- <p class="text-xs">✅ Uploaded: {{
                                        record.requirements[i].file_name }}</p> -->

                                    <Image alt="Image" preview>
                                        <template #previewicon>
                                            <i class="pi pi-search"></i>
                                        </template>
                                        <template #image>
                                            <img :src="`${record.requirements[i].file_path}`" alt="image" />
                                        </template>
                                        <template #preview="slotProps">
                                            <img :src="`${record.requirements[i].file_path}`" alt="preview"
                                                :style="slotProps.style" @click="slotProps.onClick" />
                                        </template>
                                    </Image>
                                </div>
                            </div>
                            <!-- Hidden input with proper ref setter -->
                            <input type="file" class="hidden" :ref="el => setFileInputRef(el, req.id)"
                                @change="e => handleFileChange(e, req.id)" />
                            <!-- Show selected file -->
                            <p v-if="forms[req.id].file" class="my-2 text-sm text-gray-600">
                                📂 Selected: {{ forms[req.id].file.name }}
                            </p>
                            <div class="flex justify-between">
                                <!-- Trigger button -->
                                <button type="button"
                                    class="px-4 py-1 text-sm bg-gray-200 rounded hover:bg-gray-300 w-1/2 cursor-pointer"
                                    @click="triggerFilePicker(req.id)">
                                    Choose File
                                </button>

                                <!-- Upload button -->
                                <button type="button"
                                    class="ml-2 px-4 py-1 text-sm bg-blue-600 text-white rounded  cursor-pointer"
                                    :disabled="!forms[req.id].file || forms[req.id].processing"
                                    @click="submitForm(req.id)">
                                    Upload
                                </button>
                            </div>


                        </div>
                    </div>
                </div>
            </div>

        </div>
        <Modal marginTop=" md" maxWidth="lg" :show="showRemarksModal" @close="closeModal">
            <div class="p-6">
                <div class="flex text-left">
                    <div class="w-full">
                        <InputLabel for="remarks" value="Remarks" />

                        <!-- <TextArea autofocus id="remarks" type="text" class="mt-1 block w-full" v-model="remarks" /> -->
                        <Editor v-model="remarks" editorStyle="height: 320px" />
                    </div>

                </div>
                <div class="mt-6 flex space-x-4 justify-end">
                    <button class="btn bg-red-700 text-white btn-sm" @click="closeModal">Cancel</button>
                    <button class="btn bg-slate-700 text-white btn-sm" @click="updateRemarks">Save</button>
                </div>
            </div>
        </Modal>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { usePermission } from '@/composable/permissions';
import { PencilSquareIcon, TrashIcon } from "@heroicons/vue/20/solid";
import InputLabel from "@/Components/InputLabel.vue";
import TextArea from "@/Components/TextArea.vue";
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { DynamicHeroicon } from 'vue-dynamic-heroicons';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import moment from 'moment'


const showRemarksModal = ref(false);
const toggleRemarkModal = () => {
    // console.log('test');
    showRemarksModal.value = true;
}

const closeModal = () => {
    showRemarksModal.value = false;
};
const { hasRole } = usePermission();
// CloudUploadIcon
const props = defineProps({
    record: Object,
    action: String,
    message: Object
});

const remarks = ref(props.record.remarks || '');
const forms = ref({})
const fileInputs = ref({})
// Initialize form objects per requirement
props.record.program.requirements.forEach(req => {
    forms.value[req.id] = useForm({
        requirement_id: req.id,
        file: null,
    });
    fileInputs.value[req.id] = ref(null)
})

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
        route('scholarship.requirements.upload', props.record.id),
        {
            onSuccess: () => {
                forms.value[reqId].reset('file');
                toast.success("File has been uploaded", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                router.reload({ only: ['record'] })
            },
            onError: (err) => {
                console.log(err)
                // console.log(props.record)
                // console.log(forms)
            }
        }
    )
}


const updatescholarshipstatus = (status_id) => {

    axios.put(route('scholarship_records-api.updatestatus', props.record), {
        status_id: status_id // Assuming 1 is the ID for 'Active' status
    })
        .then((res) => {
            // console.log(res);
            toast.success("Scholarship status has been updated!");
            router.reload({ only: ['record'] })
        })
        .catch(error => {
            console.error('Error approving scholarship:', error);
        });
};

const updateRemarks = () => {

    axios.put(route('scholarship_records-api.updateremarks', props.record), {
        remarks: remarks.value// Assuming 1 is the ID for 'Active' status
    })
        .then(() => {
            toast.success("Scholarship remarks has been updated!");
            router.reload({ only: ['record'] })
            closeModal();
        })
        .catch(error => {
            console.error('Error approving scholarship:', error);
        });
};



</script>

<style lang="scss" scoped></style>