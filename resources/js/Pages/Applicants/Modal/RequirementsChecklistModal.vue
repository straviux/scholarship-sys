<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import ProgressSpinner from 'primevue/progressspinner';
import Dialog from 'primevue/dialog';
import { useConfirm } from 'primevue/useconfirm';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import axios from 'axios';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    applicant: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:visible', 'success']);

const confirm = useConfirm();

// State
const isLoading = ref(false);
const togglingRequirementId = ref(null);
const requirementsData = ref([]);
const uploadingRequirementId = ref(null);
const showQrModal = ref(false);
const qrModalRequirement = ref(null);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const uploadPollingInterval = ref(null);
const showPreviewModal = ref(false);
const previewFile = ref(null);
const pendingUncheck = ref(null);
const previewZoom = ref(100);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const panX = ref(0);
const panY = ref(0);

// Computed
const visibleDialog = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const checkedCount = computed(() => {
    return requirementsData.value.filter(r => r.is_checked).length;
});

const totalCount = computed(() => {
    return requirementsData.value.length;
});

const completionPercentage = computed(() => {
    if (totalCount.value === 0) return 0;
    return Math.round((checkedCount.value / totalCount.value) * 100);
});

// Methods
const loadRequirements = async () => {
    if (!props.applicant) {
        toast.error('No applicant selected');
        return;
    }

    const previousRequirements = [...(requirementsData.value || [])];
    isLoading.value = true;
    try {
        const response = await axios.get(
            route('scholarship.profile.requirements-checklist', props.applicant.profile_id)
        );

        // Load all profile requirements
        const newRequirements = response.data.requirements || [];

        // Check if any new files were uploaded
        let newUploads = 0; K
        for (const newReq of newRequirements) {
            const oldReq = previousRequirements.find(r => r.id === newReq.id);
            if (oldReq && !oldReq.file_path && newReq.file_path) {
                newUploads++;
            }
        }

        if (newUploads > 0) {
            toast.success(`${newUploads} file(s) uploaded successfully!`);
        }

        requirementsData.value = newRequirements;
    } catch (error) {
        console.error('Error loading requirements:', error);
        if (error.response?.status === 404) {
            toast.error('Profile or requirements not found.');
        } else if (error.response?.status === 403) {
            toast.error('You do not have permission to view this applicant\'s requirements.');
        } else {
            toast.error('Failed to load requirements. Please try again.');
        }
    } finally {
        isLoading.value = false;
    }
};
const toggleRequirementClick = async (requirement, event) => {
    // Don't toggle if clicking on Button, input, or label elements
    if (event.target.closest('button') ||
        event.target.closest('label') ||
        event.target.closest('input')) {
        return;
    }

    if (requirement.is_checked) {
        // Currently checked, user wants to uncheck - show dialog
        confirm.require({
            message: `Are you sure you want to uncheck "${requirement.name}"? This will delete the uploaded file.`,
            header: 'Confirm Uncheck',
            icon: 'pi pi-exclamation-triangle',
            accept: () => confirmUncheck(requirement),
            reject: () => {
                // User cancelled - do nothing
            }
        });
    } else {
        // Currently unchecked, user wants to check - do it immediately
        requirement.is_checked = true;
        confirmCheck(requirement);
    }
};

const handleCheckboxChange = async (newValue, requirement) => {
    // This is now handled by toggleRequirementClick
};


const confirmCheck = async (requirement) => {
    togglingRequirementId.value = requirement.requirement_id;
    try {
        await axios.post(
            route('scholarship.profile.check-requirement', props.applicant.profile_id),
            { requirement_id: requirement.requirement_id }
        );
        toast.success(`${requirement.name} checked`);
    } catch (error) {
        console.error('Error checking requirement:', error.response?.data || error);
        toast.error(error.response?.data?.error || 'Failed to update requirement');
        // Revert checkbox state on error
        requirement.is_checked = false;
    } finally {
        togglingRequirementId.value = null;
    }
};

const confirmUncheck = async (requirement) => {
    togglingRequirementId.value = requirement.requirement_id;
    try {
        // Was checked, now unchecking - delete from database
        await axios.post(
            route('scholarship.profile.uncheck-requirement', props.applicant.profile_id),
            { requirement_id: requirement.requirement_id }
        );
        requirement.file_name = null;
        requirement.file_path = null;
        requirement.is_checked = false;
        toast.success(`${requirement.name} unchecked`);
    } catch (error) {
        console.error('Error unchecking requirement:', error.response?.data || error);
        toast.error(error.response?.data?.error || 'Failed to uncheck requirement');
        // Revert checkbox state on error
        requirement.is_checked = true;
    } finally {
        togglingRequirementId.value = null;
        pendingUncheck.value = null;
    }
};

const onUpload = async (event, requirement) => {
    if (!requirement.is_checked) {
        toast.error('Please check the requirement first');
        return;
    }

    const file = event.files[0];
    if (!file) return;

    uploadingRequirementId.value = requirement.requirement_id;

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('requirement_id', requirement.requirement_id);

        const response = await axios.post(
            route('scholarship.profile.upload-requirement', props.applicant.profile_id),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        );

        // Update the requirement in the list
        const reqIndex = requirementsData.value.findIndex(r => r.id === requirement.id);
        if (reqIndex !== -1) {
            requirementsData.value[reqIndex] = response.data.requirement;
        }

        toast.success(`${requirement.name} uploaded successfully`);
    } catch (error) {
        console.error('Error uploading file:', error);
        if (error.response?.data?.message) {
            toast.error(error.response.data.message);
        } else {
            toast.error(`Failed to upload ${requirement.name}`);
        }
    } finally {
        uploadingRequirementId.value = null;
        // Clear the file input
        const fileInput = document.getElementById(`file-${requirement.id}`);
        if (fileInput) fileInput.value = '';
    }
};

const downloadFile = (requirement) => {
    if (!requirement.file_path) return;

    // The file_path is already a public URL (e.g., /storage/attachments/...)
    // We can download it directly
    const link = document.createElement('a');
    link.href = requirement.file_path;
    link.download = requirement.file_name || requirement.name;
    link.style.display = 'none';
    document.body.appendChild(link);

    // Use setTimeout to ensure the click happens after DOM manipulation
    setTimeout(() => {
        link.click();
        document.body.removeChild(link);
    }, 0);
};

const viewAttachment = (requirement) => {
    if (!requirement.file_path || !requirement.file_name) return;

    // Determine file type
    const fileExtension = requirement.file_name.split('.').pop().toLowerCase();
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    const isImage = imageExtensions.includes(fileExtension);

    previewFile.value = {
        file_name: requirement.file_name,
        file_path: requirement.file_path,
        isImage,
        type: fileExtension,
        requirementName: requirement.name
    };

    showPreviewModal.value = true;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const showQrUpload = async (requirement) => {
    try {
        // Generate QR code with requirement context
        const response = await axios.post(route('applicants.requirement.generate-qr'), {
            requirement_id: requirement.requirement_id,
            profile_id: props.applicant.profile_id
        });
        const { qr_code_svg, url, expires_at } = response.data;
        qrModalRequirement.value = requirement;
        qrCodeData.value = {
            qrCode: qr_code_svg,
            url,
            expiresAt: expires_at,
        };
        showQrModal.value = true;
        startQrCountdown();

        // Start polling for uploads while QR modal is open
        startUploadPolling();
    } catch (error) {
        console.error('Error generating QR code:', error);
        toast.error('Failed to generate QR code');
    }
};


const startUploadPolling = () => {
    // Check for uploads every 3 seconds while QR modal is open
    uploadPollingInterval.value = setInterval(() => {
        if (showQrModal.value) {
            loadRequirements();
        }
    }, 3000);
};

const stopUploadPolling = () => {
    if (uploadPollingInterval.value) {
        clearInterval(uploadPollingInterval.value);
        uploadPollingInterval.value = null;
    }
};

const zoomIn = () => {
    if (previewZoom.value < 300) {
        previewZoom.value += 20;
    }
};

const zoomOut = () => {
    if (previewZoom.value > 50) {
        previewZoom.value -= 20;
    }
};

const resetZoom = () => {
    previewZoom.value = 100;
};

const handlePreviewWheel = (event) => {
    event.preventDefault();
    if (event.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
};

const onImageMouseDown = (event) => {
    if (previewZoom.value <= 100) return; // Only allow dragging when zoomed in
    isDragging.value = true;
    dragStart.value = { x: event.clientX, y: event.clientY };
};

const onImageMouseMove = (event) => {
    if (!isDragging.value) return;

    const deltaX = event.clientX - dragStart.value.x;
    const deltaY = event.clientY - dragStart.value.y;

    panX.value += deltaX;
    panY.value += deltaY;

    dragStart.value = { x: event.clientX, y: event.clientY };
};

const onImageMouseUp = () => {
    isDragging.value = false;
};

const onImageMouseLeave = () => {
    isDragging.value = false;
};

const startQrCountdown = () => {
    const updateCountdown = () => {
        if (!qrCodeData.value) return;

        const expiresAt = new Date(qrCodeData.value.expiresAt);
        const now = new Date();
        const diffMs = expiresAt - now;

        if (diffMs <= 0) {
            qrCountdown.value = 'EXPIRED';
            clearInterval(qrCountdownInterval.value);
            return;
        }

        const totalSeconds = Math.floor(diffMs / 1000);
        const totalMinutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        qrCountdown.value = `${totalMinutes} min ${seconds} sec`;
    };

    updateCountdown();
    qrCountdownInterval.value = setInterval(updateCountdown, 1000);
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.success('URL copied to clipboard');
};

// Watchers and hooks
watch(showQrModal, (newValue) => {
    if (!newValue) {
        if (qrCountdownInterval.value) {
            clearInterval(qrCountdownInterval.value);
        }
        stopUploadPolling();
        // Reload requirements after QR modal closes (user has completed upload)
        setTimeout(() => {
            loadRequirements();
        }, 1000);
    }
});

watch(visibleDialog, (newValue) => {
    if (!newValue) {
        if (qrCountdownInterval.value) {
            clearInterval(qrCountdownInterval.value);
        }
        stopUploadPolling();
    }
});

watch(showPreviewModal, (newValue) => {
    if (newValue) {
        previewZoom.value = 100; // Reset zoom when opening preview
        panX.value = 0;
        panY.value = 0;
    }
});

onMounted(() => {
    if (props.visible && requirementsData.value.length === 0) {
        loadRequirements();
    }
});
</script>

<template>
    <Dialog v-model:visible="visibleDialog" modal
        :header="`Requirements - ${applicant?.last_name}, ${applicant?.first_name}`" :style="{ width: '50vw' }"
        @show="loadRequirements">

        <!-- Content -->
        <div v-if="isLoading" class="flex justify-center items-center py-8">
            <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
        </div>

        <div v-else>
            <!-- Requirements List -->
            <div v-if="requirementsData.length > 0" class="space-y-2">
                <div v-for="requirement in requirementsData" :key="requirement.id"
                    class="flex items-center gap-3 border-b py-4 border-gray-100 last:border-b-0 cursor-pointer hover:bg-gray-50"
                    @click="toggleRequirementClick(requirement, $event)">

                    <!-- Checkbox -->
                    <Checkbox :modelValue="requirement.is_checked" :input-id="`req-${requirement.id}`" binary
                        :disabled="togglingRequirementId === requirement.requirement_id" @click.stop
                        class="pointer-events-none" />

                    <!-- Requirement Info -->
                    <label class="flex-1 cursor-pointer">
                        <div class="text-sm font-medium text-gray-800">{{ requirement.name }}</div>
                    </label>

                    <!-- File Status and Actions -->
                    <div v-if="requirement.is_submitted && requirement.file_name"
                        class="text-xs text-green-600 flex items-center gap-2" @click.stop>
                        <span>✓ {{ requirement.file_name }}</span>
                        <div class="flex gap-1">
                            <Button icon="pi pi-eye" size="small" text rounded @click.stop="viewAttachment(requirement)"
                                v-tooltip="'View attachment'" />
                            <Button icon="pi pi-download" size="small" text rounded
                                @click.stop="downloadFile(requirement)" v-tooltip="'Download attachment'" />
                        </div>
                    </div>

                    <!-- File Upload - Only if checked -->
                    <template v-if="requirement.is_checked">
                        <input type="file" :id="`file-${requirement.id}`" class="hidden"
                            @change="(e) => onUpload({ files: [e.target.files[0]] }, requirement)" />
                        <div class="flex gap-1" @click.stop>
                            <label :for="`file-${requirement.id}`" class="cursor-pointer" @click.stop>
                                <Button icon="pi pi-upload" size="small"
                                    :loading="uploadingRequirementId === requirement.requirement_id"
                                    :disabled="uploadingRequirementId === requirement.requirement_id" text as="div"
                                    v-tooltip="'Upload file directly'" />
                            </label>
                            <Button icon="pi pi-qrcode" size="small" text @click.stop="showQrUpload(requirement)"
                                v-tooltip="'Upload via QR code'" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8 text-gray-500">
                <p class="text-sm">No requirements</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center text-xs text-gray-600 w-full pt-4">
                <div>{{ checkedCount }}/{{ totalCount }} checked</div>
                <Button label="Close" severity="secondary" text @click="visibleDialog = false" />
            </div>
        </template>
    </Dialog>

    <!-- QR Code Modal for Requirement -->
    <Dialog v-model:visible="showQrModal" modal :header="`Upload ${qrModalRequirement?.name} via QR Code`"
        :style="{ width: '50vw' }">
        <div v-if="qrCodeData" class="space-y-4">
            <!-- QR Code Display -->
            <div class="flex justify-center">
                <div class="p-4 bg-white rounded-lg border-2 border-gray-200">
                    <div v-html="qrCodeData.qrCode" class="w-64 h-64"></div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-900">
                    <strong>Mobile Upload:</strong> Open this page on your mobile device and scan this QR code to upload
                    <strong>{{ qrModalRequirement?.name }}</strong>.
                </p>
            </div>

            <!-- URL Option -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-600 mb-2">Or use this link on mobile:</p>
                <div class="flex gap-2">
                    <input type="text" :value="qrCodeData.url" readonly
                        class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded text-xs text-gray-700"
                        @click="$event.target.select()" />
                    <Button icon="pi pi-copy" size="small" @click="copyToClipboard(qrCodeData.url)"
                        v-tooltip="'Copy URL'" />
                </div>
            </div>

            <!-- Expiration Timer -->
            <div class="flex items-center justify-center">
                <div class="text-lg font-bold rounded-lg px-4 py-2 bg-yellow-100 text-yellow-800">
                    Expires in: {{ qrCountdown }}
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Refresh" icon="pi pi-refresh" @click="loadRequirements()" />
            <Button label="Close" severity="secondary" @click="showQrModal = false" />
        </template>
    </Dialog>

    <!-- File Preview Modal -->
    <Dialog v-model:visible="showPreviewModal" modal :header="`Preview: ${previewFile?.file_name || ''}`"
        :style="{ width: '90vw', height: '100vh' }">
        <div v-if="previewFile" class="space-y-4">
            <!-- Image Preview with Zoom Controls -->
            <div v-if="previewFile.isImage" class="flex flex-col items-center gap-3">
                <!-- Zoom Controls -->
                <div class="flex items-center gap-2 bg-gray-100 p-2 rounded-lg">
                    <Button icon="pi pi-minus" size="small" severity="secondary" @click="zoomOut"
                        :disabled="previewZoom <= 50" />
                    <span class="text-sm font-medium w-12 text-center">{{ previewZoom }}%</span>
                    <Button icon="pi pi-plus" size="small" severity="secondary" @click="zoomIn"
                        :disabled="previewZoom >= 300" />
                    <div class="border-l border-gray-300 h-6 mx-1"></div>
                    <Button icon="pi pi-refresh" size="small" severity="secondary" @click="resetZoom" label="Reset" />
                </div>
                <!-- Scrollable Image Container -->
                <div class="w-full max-h-[60vh] overflow-auto border border-gray-200 rounded-lg flex justify-center items-start bg-gray-50"
                    @wheel="handlePreviewWheel">
                    <img :src="previewFile.file_path" :alt="previewFile.file_name" :style="{
                        transform: `translate(${panX}px, ${panY}px) scale(${previewZoom / 100})`,
                        cursor: previewZoom > 100 ? 'grab' : 'default',
                        userSelect: 'none'
                    }" class="rounded" @mousedown="onImageMouseDown" @mousemove="onImageMouseMove"
                        @mouseup="onImageMouseUp" @mouseleave="onImageMouseLeave" draggable="false" />
                </div>
            </div>

            <!-- Non-Image Preview Message -->
            <div v-else class="flex flex-col items-center justify-center py-8 bg-gray-50 rounded-lg">
                <i class="pi pi-file text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 text-sm">Cannot preview {{ previewFile.type.toUpperCase() }} files directly</p>
                <p class="text-gray-500 text-xs mt-1">Use the download button to open the file</p>
            </div>


        </div>

        <template #footer>
            <div class="flex justify-between w-full">

                <!-- File Information -->
                <div class="bg-gray-50 rounded-lg p-3 text-xs">
                    <div class="space-y-1">
                        <div><strong>File:</strong> {{ previewFile.file_name }}</div>
                        <div><strong>Requirement:</strong> {{ previewFile.requirementName }}</div>
                        <div><strong>Type:</strong> {{ previewFile.type.toUpperCase() }}</div>
                    </div>
                </div>
                <div class="space-x-2 mt-12">
                    <Button label="Download" icon="pi pi-download" @click="downloadFile(previewFile)"
                        severity="primary" />
                    <Button label="Close" severity="secondary" @click="showPreviewModal = false" />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.p-dialog-header) {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

:deep(.p-dialog-content) {
    padding: 1rem;
}

:deep(.p-dialog-footer) {
    padding: 0.75rem 1rem;
    border-top: 1px solid #f3f4f6;
}

:deep(.p-checkbox) {
    margin-right: 0;
}

:deep(.p-button.p-button-sm) {
    padding: 0.25rem 0.5rem;
}

:deep(.p-button-icon-only) {
    width: 1.75rem;
    height: 1.75rem;
}
</style>
