<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
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
const showPreviewModal = ref(false);
const previewFile = ref(null);
const pendingUncheck = ref(null);
const previewZoom = ref(100);
// Image preview drag (NOT modal drag)
const isImageDragging = ref(false);
const imageDragStart = ref({ x: 0, y: 0 });
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

        const newRequirements = response.data.requirements || [];

        let newUploads = 0;
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
    if (event.target.closest('button') ||
        event.target.closest('label') ||
        event.target.closest('input')) {
        return;
    }

    if (requirement.is_checked) {
        confirm.require({
            message: `Are you sure you want to uncheck "${requirement.name}"? This will delete the uploaded file.`,
            header: 'Confirm Uncheck',
            icon: 'pi pi-exclamation-triangle',
            accept: () => confirmUncheck(requirement),
            reject: () => { }
        });
    } else {
        requirement.is_checked = true;
        confirmCheck(requirement);
    }
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
        requirement.is_checked = false;
    } finally {
        togglingRequirementId.value = null;
    }
};

const confirmUncheck = async (requirement) => {
    togglingRequirementId.value = requirement.requirement_id;
    try {
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
        const fileInput = document.getElementById(`file-${requirement.id}`);
        if (fileInput) fileInput.value = '';
    }
};

const downloadFile = (requirement) => {
    if (!requirement.file_path) return;
    const link = document.createElement('a');
    link.href = requirement.file_path;
    link.download = requirement.file_name || requirement.name;
    link.style.display = 'none';
    document.body.appendChild(link);
    setTimeout(() => {
        link.click();
        document.body.removeChild(link);
    }, 0);
};

const viewAttachment = (requirement) => {
    if (!requirement.file_path || !requirement.file_name) return;
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
    } catch (error) {
        console.error('Error generating QR code:', error);
        toast.error('Failed to generate QR code');
    }
};

const zoomIn = () => { if (previewZoom.value < 300) previewZoom.value += 20; };
const zoomOut = () => { if (previewZoom.value > 50) previewZoom.value -= 20; };
const resetZoom = () => { previewZoom.value = 100; };

const handlePreviewWheel = (event) => {
    event.preventDefault();
    if (event.deltaY < 0) zoomIn();
    else zoomOut();
};

const onImageMouseDown = (event) => {
    if (previewZoom.value <= 100) return;
    isImageDragging.value = true;
    imageDragStart.value = { x: event.clientX, y: event.clientY };
};

const onImageMouseMove = (event) => {
    if (!isImageDragging.value) return;
    panX.value += event.clientX - imageDragStart.value.x;
    panY.value += event.clientY - imageDragStart.value.y;
    imageDragStart.value = { x: event.clientX, y: event.clientY };
};

const onImageMouseUp = () => { isImageDragging.value = false; };
const onImageMouseLeave = () => { isImageDragging.value = false; };

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
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            toast.success('URL copied to clipboard');
        }).catch(() => {
            toast.error('Failed to copy to clipboard');
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            toast.success('URL copied to clipboard');
        } catch {
            toast.error('Failed to copy to clipboard');
        }
        document.body.removeChild(textArea);
    }
};

function closeMain() {
    emit('update:visible', false);
}

// Watchers
watch(showQrModal, (newValue) => {
    if (!newValue) {
        if (qrCountdownInterval.value) clearInterval(qrCountdownInterval.value);
        setTimeout(() => { loadRequirements(); }, 500);
    }
});

watch(visibleDialog, (newValue) => {
    if (!newValue) {
        if (qrCountdownInterval.value) clearInterval(qrCountdownInterval.value);
    }
});

watch(showPreviewModal, (newValue) => {
    if (newValue) {
        previewZoom.value = 100;
        panX.value = 0;
        panY.value = 0;
    }
});

onMounted(() => {
    if (props.visible && requirementsData.value.length === 0) {
        loadRequirements();
    }
});

/* ── iOS Modal Drag ── */
const iosDragOffset = ref({ x: 0, y: 0 });
const iosDragStart = ref(null);
const modalStyle = computed(() => ({
    width: '600px',
    transform: `translate(${iosDragOffset.value.x}px, ${iosDragOffset.value.y}px)`,
}));

function onModalDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-checkbox, .p-progressspinner')) return;
    iosDragStart.value = { x: e.clientX - iosDragOffset.value.x, y: e.clientY - iosDragOffset.value.y };
    document.addEventListener('pointermove', onModalDragMove);
    document.addEventListener('pointerup', onModalDragEnd);
}
function onModalDragMove(e) {
    if (!iosDragStart.value) return;
    iosDragOffset.value = { x: e.clientX - iosDragStart.value.x, y: e.clientY - iosDragStart.value.y };
}
function onModalDragEnd() {
    iosDragStart.value = null;
    document.removeEventListener('pointermove', onModalDragMove);
    document.removeEventListener('pointerup', onModalDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onModalDragMove);
    document.removeEventListener('pointerup', onModalDragEnd);
    if (qrCountdownInterval.value) clearInterval(qrCountdownInterval.value);
});

/* ── QR Modal Drag ── */
const qrDragOffset = ref({ x: 0, y: 0 });
const qrDragStart = ref(null);
const qrModalStyle = computed(() => ({
    width: '500px',
    transform: `translate(${qrDragOffset.value.x}px, ${qrDragOffset.value.y}px)`,
}));

function onQrDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a')) return;
    qrDragStart.value = { x: e.clientX - qrDragOffset.value.x, y: e.clientY - qrDragOffset.value.y };
    document.addEventListener('pointermove', onQrDragMove);
    document.addEventListener('pointerup', onQrDragEnd);
}
function onQrDragMove(e) {
    if (!qrDragStart.value) return;
    qrDragOffset.value = { x: e.clientX - qrDragStart.value.x, y: e.clientY - qrDragStart.value.y };
}
function onQrDragEnd() {
    qrDragStart.value = null;
    document.removeEventListener('pointermove', onQrDragMove);
    document.removeEventListener('pointerup', onQrDragEnd);
}

/* ── Preview Modal Drag ── */
const prevDragOffset = ref({ x: 0, y: 0 });
const prevDragStart = ref(null);
const previewModalStyle = computed(() => ({
    width: '85vw',
    transform: `translate(${prevDragOffset.value.x}px, ${prevDragOffset.value.y}px)`,
}));

function onPrevDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, img')) return;
    prevDragStart.value = { x: e.clientX - prevDragOffset.value.x, y: e.clientY - prevDragOffset.value.y };
    document.addEventListener('pointermove', onPrevDragMove);
    document.addEventListener('pointerup', onPrevDragEnd);
}
function onPrevDragMove(e) {
    if (!prevDragStart.value) return;
    prevDragOffset.value = { x: e.clientX - prevDragStart.value.x, y: e.clientY - prevDragStart.value.y };
}
function onPrevDragEnd() {
    prevDragStart.value = null;
    document.removeEventListener('pointermove', onPrevDragMove);
    document.removeEventListener('pointerup', onPrevDragEnd);
}
</script>

<template>
    <!-- Main Requirements Dialog -->
    <Dialog :visible="visibleDialog" modal @update:visible="val => { if (!val) closeMain(); }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }" @show="loadRequirements">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onModalDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeMain"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Requirements</span>
                    <span class="ios-nav-btn" style="visibility: hidden; right: 16px;">_</span>
                </div>

                <!-- Applicant name subtitle -->
                <div class="ios-subtitle text-gray-800 font-semibold" v-if="applicant">
                    {{ applicant?.last_name }}, {{ applicant?.first_name }}
                </div>

                <!-- Body -->
                <div class="ios-body">
                    <!-- Loading -->
                    <div v-if="isLoading" style="display: flex; justify-content: center; padding: 40px 0;">
                        <ProgressSpinner style="width: 36px; height: 36px;" strokeWidth="4" />
                    </div>

                    <template v-else>
                        <!-- Requirements List -->
                        <div v-if="requirementsData.length > 0" class="ios-section" style="margin-top: 12px;">
                            <div class="ios-card">
                                <div v-for="(requirement, idx) in requirementsData" :key="requirement.id"
                                    class="ios-req-row" :class="{ 'ios-row-last': idx === requirementsData.length - 1 }"
                                    @click="toggleRequirementClick(requirement, $event)" style="cursor: pointer;">

                                    <!-- Checkbox -->
                                    <Checkbox :modelValue="requirement.is_checked" :input-id="`req-${requirement.id}`"
                                        binary :disabled="togglingRequirementId === requirement.requirement_id"
                                        @click.stop class="pointer-events-none" style="flex-shrink: 0;" />

                                    <!-- Requirement Info -->
                                    <div style="flex: 1; min-width: 0; margin-left: 10px;">
                                        <div style="font-size: 14px; font-weight: 500; color: #000;">{{ requirement.name
                                            }}</div>
                                        <!-- File status -->
                                        <div v-if="requirement.is_submitted && requirement.file_name"
                                            style="font-size: 11px; color: #34C759; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                            <span>✓ {{ requirement.file_name }}</span>
                                        </div>
                                    </div>

                                    <!-- Action buttons -->
                                    <div style="display: flex; gap: 2px; flex-shrink: 0; margin-left: 6px;" @click.stop>
                                        <!-- View/Download for submitted files -->
                                        <button v-if="requirement.is_submitted && requirement.file_name"
                                            class="ios-icon-btn" @click.stop="viewAttachment(requirement)" title="View">
                                            <i class="pi pi-eye" style="font-size: 13px; color: #007AFF;"></i>
                                        </button>
                                        <button v-if="requirement.is_submitted && requirement.file_name"
                                            class="ios-icon-btn" @click.stop="downloadFile(requirement)"
                                            title="Download">
                                            <i class="pi pi-download" style="font-size: 13px; color: #34C759;"></i>
                                        </button>

                                        <!-- Upload buttons for checked items -->
                                        <template v-if="requirement.is_checked">
                                            <input type="file" :id="`file-${requirement.id}`" style="display: none;"
                                                @change="(e) => onUpload({ files: [e.target.files[0]] }, requirement)" />
                                            <label :for="`file-${requirement.id}`"
                                                style="cursor: pointer; display: flex;" @click.stop>
                                                <span class="ios-icon-btn">
                                                    <i class="pi pi-upload" style="font-size: 13px; color: #007AFF;"
                                                        :style="{ opacity: uploadingRequirementId === requirement.requirement_id ? 0.4 : 1 }"></i>
                                                </span>
                                            </label>
                                            <button class="ios-icon-btn" @click.stop="showQrUpload(requirement)"
                                                title="QR Upload">
                                                <i class="pi pi-qrcode" style="font-size: 13px; color: #8E8E93;"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else style="text-align: center; padding: 40px 0; color: #8E8E93;">
                            <i class="pi pi-inbox" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                            No requirements
                        </div>
                    </template>

                    <div style="height: 10px;"></div>
                </div>

                <!-- Footer -->
                <div class="ios-footer">
                    <span style="font-size: 13px; color: #8E8E93;">{{ checkedCount }}/{{ totalCount }} checked</span>
                    <button class="ios-footer-close" @click="closeMain">Close</button>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- QR Code Modal -->
    <Dialog :visible="showQrModal" modal @update:visible="val => { if (!val) showQrModal = false; }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="qrModalStyle">
                <div class="ios-nav-bar" @pointerdown="onQrDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="showQrModal = false"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">QR Upload</span>
                    <button class="ios-nav-btn ios-nav-action" @click="loadRequirements()">
                        <i class="pi pi-refresh" style="font-size: 13px;"></i>
                    </button>
                </div>

                <div class="ios-body" v-if="qrCodeData">
                    <!-- QR Code -->
                    <div class="ios-section" style="margin-top: 16px; ">
                        <div class="ios-card"
                            style="padding: 20px;height: 290px; display: flex; justify-content: center;">
                            <div v-html="qrCodeData.qrCode" style="width: 200px; height: 200px;margin-left:-28px"></div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="ios-section">
                        <div class="ios-card" style="padding: 12px 16px;">
                            <div style="font-size: 13px; color: #007AFF; font-weight: 500;">
                                <i class="pi pi-info-circle" style="margin-right: 4px;"></i> Mobile Upload
                            </div>
                            <div style="font-size: 12px; color: #6D6D72; margin-top: 4px;">
                                Scan this QR code on your mobile device to upload <strong>{{ qrModalRequirement?.name
                                    }}</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- URL -->
                    <div class="ios-section">
                        <div class="ios-section-label">Or use this link</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last" style="gap: 8px;">
                                <InputText type="text" :value="qrCodeData.url" readonly
                                    style="flex: 1; font-size: 11px; border: none; background: transparent; box-shadow: none; padding: 0;"
                                    @click="$event.target.select()" />
                                <button class="ios-icon-btn" @click="copyToClipboard(qrCodeData.url)" title="Copy">
                                    <i class="pi pi-copy" style="font-size: 14px; color: #007AFF;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Countdown -->
                    <div class="ios-section">
                        <div class="ios-card" style="padding: 14px; text-align: center;">
                            <div
                                style="font-size: 11px; color: #8E8E93; text-transform: uppercase; margin-bottom: 4px;">
                                Expires in</div>
                            <div style="font-size: 18px; font-weight: 700; color: #FF9500;">{{ qrCountdown }}</div>
                        </div>
                    </div>

                    <div style="height: 16px;"></div>
                </div>
            </div>
        </template>
    </Dialog>

    <!-- File Preview Modal -->
    <Dialog :visible="showPreviewModal" modal @update:visible="val => { if (!val) showPreviewModal = false; }"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal ios-modal-preview" :style="previewModalStyle">
                <div class="ios-nav-bar" @pointerdown="onPrevDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="showPreviewModal = false"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title" style="font-size: 15px;">{{ previewFile?.file_name || 'Preview'
                        }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click="downloadFile(previewFile)">
                        <i class="pi pi-download" style="font-size: 14px;"></i>
                    </button>
                </div>

                <div class="ios-body" v-if="previewFile">
                    <!-- Image Preview -->
                    <div v-if="previewFile.isImage">
                        <!-- Zoom Controls -->
                        <div class="ios-section" style="margin-top: 12px;">
                            <div class="ios-card"
                                style="padding: 8px 16px; display: flex; align-items: center; justify-content: center; gap: 12px;">
                                <button class="ios-icon-btn" @click="zoomOut" :disabled="previewZoom <= 50">
                                    <i class="pi pi-minus" style="font-size: 13px; color: #007AFF;"></i>
                                </button>
                                <span
                                    style="font-size: 13px; font-weight: 600; color: #000; min-width: 40px; text-align: center;">{{
                                        previewZoom }}%</span>
                                <button class="ios-icon-btn" @click="zoomIn" :disabled="previewZoom >= 300">
                                    <i class="pi pi-plus" style="font-size: 13px; color: #007AFF;"></i>
                                </button>
                                <div style="width: 1px; height: 20px; background: #E5E5EA;"></div>
                                <button class="ios-icon-btn" @click="resetZoom"
                                    style="font-size: 12px; color: #007AFF;">Reset</button>
                            </div>
                        </div>

                        <!-- Image Container -->
                        <div class="ios-section">
                            <div class="ios-preview-container" @wheel="handlePreviewWheel">
                                <img :src="previewFile.file_path" :alt="previewFile.file_name" :style="{
                                    transform: `translate(${panX}px, ${panY}px) scale(${previewZoom / 100})`,
                                    cursor: previewZoom > 100 ? 'grab' : 'default',
                                    userSelect: 'none',
                                    maxWidth: '100%',
                                    borderRadius: '8px',
                                }" @mousedown="onImageMouseDown" @mousemove="onImageMouseMove"
                                    @mouseup="onImageMouseUp" @mouseleave="onImageMouseLeave" draggable="false" />
                            </div>
                        </div>
                    </div>

                    <!-- Non-image -->
                    <div v-else style="text-align: center; padding: 40px 0; color: #8E8E93;">
                        <i class="pi pi-file" style="font-size: 36px; display: block; margin-bottom: 10px;"></i>
                        <div style="font-size: 14px;">Cannot preview {{ previewFile.type.toUpperCase() }} files</div>
                        <div style="font-size: 12px; margin-top: 4px;">Use the download button to open the file</div>
                    </div>

                    <!-- File Info -->
                    <div class="ios-section" style="margin-bottom: 16px;">
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">File</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ previewFile.file_name }}</span>
                            </div>
                            <div class="ios-row">
                                <span class="ios-row-label">Requirement</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ previewFile.requirementName }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Type</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ previewFile.type.toUpperCase()
                                    }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-modal-preview {
    max-height: 92vh;
}

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
    max-width: 60%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 15px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #007AFF;
    font-weight: 600;
}

.ios-subtitle {
    padding: 6px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    font-size: 13px;
    text-align: center;
    flex-shrink: 0;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-req-row {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    min-height: 44px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
    transition: background 0.15s;
}

.ios-req-row:hover {
    background: rgba(0, 0, 0, 0.02);
}

.ios-row-last {
    border-bottom: none;
}

.ios-req-row:last-child {
    border-bottom: none;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    font-weight: 500;
    flex-shrink: 0;
}

.ios-icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ios-icon-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.ios-icon-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.ios-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #FFFFFF;
    border-top: 0.5px solid #E5E5EA;
    flex-shrink: 0;
}

.ios-footer-close {
    background: none;
    border: none;
    font-size: 15px;
    color: #6B7280;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: opacity 0.15s;
}

.ios-footer-close:hover {
    opacity: 0.6;
}

.ios-preview-container {
    background: #F2F2F7;
    border: 0.5px solid #E5E5EA;
    border-radius: 10px;
    overflow: auto;
    max-height: 55vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 8px;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
