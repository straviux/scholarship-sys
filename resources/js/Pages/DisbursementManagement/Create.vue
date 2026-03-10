<template>
    <AdminLayout>

        <Head title="Map Disbursement to Fund Transaction" />

        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-6 px-3 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('disbursement-management.index')"
                        class="text-blue-600 hover:text-blue-800 text-sm mb-4 flex items-center gap-1">
                        <i class="pi pi-arrow-left text-xs"></i> Back
                    </Link>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">{{ obrNo }}</h1>
                    <p class="text-sm sm:text-base text-slate-600">
                        {{ existingTransaction ? 'Update' : 'Create' }} fund transaction mapping for {{
                            disbursements.length }} profile(s)
                    </p>
                </div>

                <!-- Disbursements Summary -->
                <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6 border border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">Disbursement Details</h2>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 pb-6 border-b border-slate-200">
                        <div>
                            <div class="text-xs sm:text-sm text-slate-500">OBR Status</div>
                            <Badge :value="disbursements[0].obr_status"
                                :severity="getOBRStatusSeverity(disbursements[0].obr_status)" class="mt-1" />
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm text-slate-500">Date Obligated</div>
                            <div class="font-semibold text-slate-900 mt-1">{{
                                formatDate(disbursements[0].date_obligated) }}</div>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm text-slate-500">Academic Year</div>
                            <div class="font-semibold text-slate-900 mt-1">{{ disbursements[0].academic_year }}</div>
                        </div>
                        <div>
                            <div class="text-xs sm:text-sm text-slate-500">Semester</div>
                            <div class="font-semibold text-slate-900 mt-1">{{ disbursements[0].semester }}</div>
                        </div>
                    </div>

                    <!-- Profiles Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-3 py-2 text-left font-semibold text-slate-900">Scholar</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-900">Year</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-900">Type</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-900">Amount</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-900 w-6">
                                        <Checkbox v-model="selectAllChecked" :binary="true"
                                            @change="toggleAllProfiles" />
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(profile, idx) in disbursements" :key="profile.disbursement_id"
                                    class="border-b border-slate-200 hover:bg-slate-50">
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-slate-900">{{ profile.scholar_name }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">Year {{ profile.year_level }}</td>
                                    <td class="px-3 py-3">
                                        <Badge
                                            :value="profile.disbursement_type === 'disbursements' ? 'Disbursement' : 'Payroll'"
                                            :severity="profile.disbursement_type === 'disbursements' ? 'info' : 'success'"
                                            class="text-xs" />
                                    </td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-900">₱{{
                                        Number(profile.amount).toFixed(2) }}</td>
                                    <td class="px-3 py-3">
                                        <Checkbox :model-value="selectededProfileIds.includes(profile.profile_id)"
                                            :binary="true" @change="toggleProfile(profile.profile_id)" />
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 border-t-2 border-slate-200 font-semibold">
                                    <td colspan="3" class="px-3 py-3">Total ({{ selectededProfileIds.length }} selected)
                                    </td>
                                    <td class="px-3 py-3 text-right text-slate-900">₱{{
                                        calculateSelectedTotal().toFixed(2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Mapping Form -->
                <form @submit.prevent="handleSubmit"
                    class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900 mb-6">Fund Transaction Mapping</h2>

                    <div class="space-y-6">
                        <!-- OBR Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">OBR Type</label>
                            <InputText v-model="form.obr_type" placeholder="e.g., Regular Obligation, Capital Outlay..."
                                class="w-full text-sm" />
                        </div>

                        <!-- Payee Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Payee Type *</label>
                            <Dropdown v-model="form.payee_type" :options="payeeTypeOptions" option-label="label"
                                option-value="value" placeholder="Select Payee Type" class="w-full text-sm"
                                :class="{ 'border-red-500': errors.payee_type }" />
                            <div v-if="errors.payee_type" class="text-red-600 text-xs mt-1">{{ errors.payee_type[0]
                                }}</div>
                        </div>

                        <!-- Payee Name -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Payee Name *</label>
                            <InputText v-model="form.payee_name" placeholder="Name of payee" class="w-full text-sm"
                                :class="{ 'border-red-500': errors.payee_name }" />
                            <div v-if="errors.payee_name" class="text-red-600 text-xs mt-1">{{ errors.payee_name[0]
                                }}</div>
                        </div>

                        <!-- Payee Address -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Payee Address</label>
                            <Textarea v-model="form.payee_address" placeholder="Address of payee..." rows="2"
                                class="w-full text-sm" />
                        </div>

                        <!-- Voucher Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Voucher Type *</label>
                            <Dropdown v-model="form.voucher_type" :options="voucherTypeOptions" option-label="label"
                                option-value="value" placeholder="Select Type" class="w-full text-sm"
                                :class="{ 'border-red-500': errors.voucher_type }" />
                            <div v-if="errors.voucher_type" class="text-red-600 text-xs mt-1">{{ errors.voucher_type[0]
                                }}</div>
                        </div>

                        <!-- Explanation -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Explanation</label>
                            <Textarea v-model="form.explanation" placeholder="Transaction explanation..." rows="3"
                                class="w-full text-sm" />
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label class="block text-sm font-medium text-slate-900 mb-2">Remarks</label>
                            <Textarea v-model="form.remarks" placeholder="Additional remarks..." rows="3"
                                class="w-full text-sm" />
                        </div>

                        <!-- Attachments -->
                        <div v-if="attachments.length > 0">
                            <label class="block text-sm font-medium text-slate-900 mb-3">Disbursement Attachments ({{
                                attachments.length }})</label>
                            <div class="space-y-2">
                                <div v-for="attachment in attachments" :key="attachment.id"
                                    class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <i class="pi pi-file text-slate-600 flex-shrink-0 text-lg"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ attachment.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ formatFileSize(attachment.size) }} • {{ attachment.attachment_type }}
                                                <span v-if="!attachment.file_exists"
                                                    class="ml-2 text-red-500 font-medium">(File Missing)</span>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1 truncate" v-if="attachment.file_path">
                                                Path: {{ attachment.file_path }}</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="openAttachmentModal(attachment)"
                                        class="ml-3 px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded transition flex-shrink-0">
                                        <i class="pi pi-eye text-xs mr-1"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Note About OBR Status -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex gap-3">
                                <i class="pi pi-info-circle text-blue-600 flex-shrink-0"></i>
                                <div class="text-xs sm:text-sm text-blue-700">
                                    <strong>Transaction Status:</strong> {{ disbursements[0].obr_status }} (synced from
                                    disbursement)
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-3 justify-end pt-6 border-t border-slate-200">
                            <Button type="button" label="Cancel" severity="secondary" @click="goBack" class="text-sm" />
                            <Button type="submit"
                                :label="existingTransaction ? 'Update Transaction' : 'Create Transaction'"
                                @click="handleSubmit" :loading="submitting" class="text-sm" />
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attachment Preview Modal -->
        <Dialog v-model:visible="showAttachmentModal" modal :header="selectedAttachment?.name" :style="{ width: '90vw' }"
            :maximizable="true" class="p-fluid">
            <div v-if="selectedAttachment" class="flex flex-col">
                <!-- Zoom Controls -->
                <div class="flex gap-2 mb-4 pb-4 border-b border-slate-200">
                    <Button icon="pi pi-minus" @click="zoomOut" severity="secondary" size="small" rounded text
                        v-tooltip.bottom="'Zoom Out'" />
                    <span class="px-3 py-2 text-sm font-medium text-slate-900 bg-slate-100 rounded min-w-16 text-center">
                        {{ Math.round(attachmentZoom * 100) }}%
                    </span>
                    <Button icon="pi pi-plus" @click="zoomIn" severity="secondary" size="small" rounded text
                        v-tooltip.bottom="'Zoom In'" />
                    <Button icon="pi pi-refresh" @click="resetZoom" severity="secondary" size="small" rounded text
                        v-tooltip.bottom="'Reset Zoom'" />
                </div>

                <!-- Preview Container -->
                <div class="flex justify-center overflow-auto bg-slate-100 rounded p-4" style="max-height: 70vh;">
                    <!-- Image Preview -->
                    <div v-if="isImageFile(selectedAttachment.type) && selectedAttachment.path"
                        class="flex items-center justify-center">
                        <img :src="getAttachmentUrl(selectedAttachment)" :alt="selectedAttachment.name"
                            :style="{ transform: `scale(${attachmentZoom})` }"
                            class="object-contain transition-transform duration-200" />
                    </div>

                    <!-- PDF Preview -->
                    <div v-else-if="isPdfFile(selectedAttachment.type) && selectedAttachment.path"
                        class="w-full">
                        <embed :src="getAttachmentUrl(selectedAttachment)" type="application/pdf"
                            class="w-full" />
                    </div>

                    <!-- File Not Available -->
                    <div v-else
                        class="flex flex-col items-center justify-center gap-4 p-12 text-center">
                        <i class="pi pi-exclamation-circle text-5xl text-amber-500"></i>
                        <div>
                            <p class="font-semibold text-slate-900">File Not Available</p>
                            <p class="text-sm text-slate-600 mt-1">This file is not available in the current environment.</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex gap-3 justify-end pt-4 mt-4 border-t border-slate-200">
                    <Button label="Close" severity="secondary" @click="showAttachmentModal = false" size="small" />
                    <a v-if="selectedAttachment.path" :href="getAttachmentUrl(selectedAttachment)" target="_blank"
                        rel="noopener noreferrer"
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded inline-flex items-center gap-2">
                        <i class="pi pi-download text-sm"></i> Download
                    </a>
                </div>
            </div>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';

const page = usePage();
const props = defineProps({
    obrNo: String,
    disbursements: Array,
    existingTransaction: Object,
    attachments: {
        type: Array,
        default: () => [],
    },
});

const submitting = ref(false);
const showAttachmentModal = ref(false);
const selectedAttachment = ref(null);
const attachmentZoom = ref(1);
const selectededProfileIds = ref(props.existingTransaction?.scholar_ids || props.disbursements.map(d => d.profile_id));
const selectAllChecked = computed({
    get: () => selectededProfileIds.value.length === props.disbursements.length,
    set: (val) => toggleAllProfiles()
});

const errors = ref({});

const calculateSelectedTotal = () => {
    return props.disbursements
        .filter(d => selectededProfileIds.value.includes(d.profile_id))
        .reduce((sum, d) => sum + Number(d.amount), 0);
};

const form = reactive({
    obr_no: props.obrNo,
    obr_type: props.existingTransaction?.obr_type || '',
    payee_type: props.existingTransaction?.payee_type || 'scholar',
    payee_name: props.existingTransaction?.payee_name || '',
    payee_address: props.existingTransaction?.payee_address || '',
    voucher_type: props.existingTransaction?.voucher_type || '',
    scholar_ids: [...selectededProfileIds.value],
    amount: calculateSelectedTotal(),
    explanation: '',
    remarks: '',
});

// Watch for changes in selected profiles and update form
watch(selectededProfileIds, (newIds) => {
    form.scholar_ids = [...newIds];
    form.amount = calculateSelectedTotal();
}, { deep: true });

const voucherTypeOptions = [
    { label: 'Disbursement', value: 'disbursements' },
    { label: 'Payroll', value: 'payroll' },
];

const payeeTypeOptions = [
    { label: 'Scholar', value: 'scholar' },
    { label: 'School', value: 'school' },
    { label: 'Individual', value: 'individual' },
];

function toggleProfile(profileId) {
    const idx = selectededProfileIds.value.indexOf(profileId);
    if (idx > -1) {
        selectededProfileIds.value.splice(idx, 1);
    } else {
        selectededProfileIds.value.push(profileId);
    }
}

function toggleAllProfiles() {
    if (selectededProfileIds.value.length === props.disbursements.length) {
        selectededProfileIds.value = [];
    } else {
        selectededProfileIds.value = props.disbursements.map(d => d.profile_id);
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getOBRStatusSeverity(status) {
    const severities = {
        'LOA': 'info',
        'IRREGULAR': 'warning',
        'TRANSFERRED': 'info',
        'CLAIMED': 'success',
        'PAID': 'success',
        'ON PROCESS': 'warning',
        'DENIED': 'danger',
    };
    return severities[status] || 'secondary';
}

function goBack() {
    window.history.back();
}

function handleSubmit() {
    // Clear errors
    errors.value = {};

    // Validate required fields
    if (selectededProfileIds.value.length === 0) {
        errors.value.scholar_ids = ['Please select at least one profile'];
    }
    if (!form.voucher_type) {
        errors.value.voucher_type = ['Voucher type is required'];
    }
    if (!form.payee_type) {
        errors.value.payee_type = ['Payee type is required'];
    }
    if (!form.payee_name) {
        errors.value.payee_name = ['Payee name is required'];
    }

    // If there are validation errors, don't submit
    if (Object.keys(errors.value).length > 0) {
        return;
    }

    submitting.value = true;

    router.post(route('disbursement-management.store'), {
        obr_no: form.obr_no,
        obr_type: form.obr_type,
        payee_type: form.payee_type,
        payee_name: form.payee_name,
        payee_address: form.payee_address,
        voucher_type: form.voucher_type,
        scholar_ids: selectededProfileIds.value,
        amount: calculateSelectedTotal(),
        explanation: form.explanation,
        remarks: form.remarks,
    }, {
        onError: (err) => {
            errors.value = err;
            submitting.value = false;
        },
        onSuccess: () => {
            submitting.value = false;
        }
    });
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function openAttachmentModal(attachment) {
    selectedAttachment.value = attachment;
    attachmentZoom.value = 1;
    showAttachmentModal.value = true;
}

function zoomIn() {
    if (attachmentZoom.value < 3) {
        attachmentZoom.value = Math.min(3, attachmentZoom.value + 0.2);
    }
}

function zoomOut() {
    if (attachmentZoom.value > 0.5) {
        attachmentZoom.value = Math.max(0.5, attachmentZoom.value - 0.2);
    }
}

function resetZoom() {
    attachmentZoom.value = 1;
}

function isImageFile(mimeType) {
    const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    return imageTypes.includes(mimeType);
}

function isPdfFile(mimeType) {
    return mimeType === 'application/pdf';
}

function getAttachmentUrl(attachment) {
    if (!attachment.path) {
        return '';
    }

    // If the path is already a full URL, reconstruct it with current host
    if (attachment.path.startsWith('http://') || attachment.path.startsWith('https://')) {
        // Extract the path part (everything after the domain)
        const urlObj = new URL(attachment.path);
        const pathPart = urlObj.pathname + urlObj.search + urlObj.hash;
        // Return with current host
        return window.location.origin + pathPart;
    }

    // If it's a relative path, ensure it starts with /
    if (!attachment.path.startsWith('/')) {
        return '/' + attachment.path;
    }

    return attachment.path;
}
</script>
