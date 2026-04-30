<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoucherWizard from '@/Components/Obligations/VoucherWizard.vue';
import FloatingDrawer from '@/Components/FloatingDrawer.vue';
import DeleteConfirmModal from '@/Pages/FundTransactions/Modal/DeleteConfirmModal.vue';
import ViewTransactionModal from '@/Pages/FundTransactions/Modal/ViewTransactionModal.vue';
import FileUploadModal from '@/Pages/FundTransactions/Modal/FileUploadModal.vue';
import RemarksModal from '@/Pages/FundTransactions/Modal/RemarksModal.vue';
import StatusModal from '@/Pages/FundTransactions/Modal/StatusModal.vue';
import QrCodeModal from '@/Pages/FundTransactions/Modal/QrCodeModal.vue';
import TrackingHistoryModal from '@/Pages/FundTransactions/Modal/TrackingHistoryModal.vue';
import ObrTrackingModal from '@/Pages/FundTransactions/Modal/ObrTrackingModal.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import axios from 'axios';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';
import { useSystemOptions } from '@/composables/useSystemOptions';
import ObrTemplate from '@/Pages/FundTransactions/Pdf/ObrTemplate.vue';
import DvTemplate from '@/Pages/FundTransactions/Pdf/DvTemplate.vue';
import PayrollTemplate from '@/Pages/FundTransactions/Pdf/PayrollTemplate.vue';
import LosTemplate from '@/Pages/FundTransactions/Pdf/LosTemplate.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';

const toast = useToast();
const { buildHtmlDoc, printHtml } = usePdfPrint();

// PDF Preview modal state
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref('');
const pdfPreviewTitle = ref('');
const pdfPreviewSize = ref('a4');

const ftDrawerPt = {
    root: { class: 'ft-floating-drawer' },
    mask: { class: 'ft-floating-drawer-mask' }
};

const page = usePage();
const _url = new URLSearchParams(window.location.search);
const showWizard = ref(false);
const currentStep = ref(1);
const voucherType = ref('obligations');
const selectedScholars = ref([]);
const vouchers = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const showDeleteConfirmDialog = ref(false);
const voucherToDelete = ref(null);
const searchQuery = ref(_url.get('search') || '');
const showViewDialog = ref(false);
const selectedVoucher = ref(null);
const viewModalTab = ref('details');
const scholarsDetails = ref([]);
const loadingScholars = ref(false);
const scholarsCache = ref(new Map()); // Cache for scholar details by ID
const editingId = ref(null);
const editFormData = ref(null);
const responsibilityCenters = ref([]);
const contextMenu = ref();
const selectedContextVoucher = ref(null);
const showRemarksDialog = ref(false);
const selectedVoucherForRemarks = ref(null);
const remarksForm = reactive({
    remarks: ''
});
const savingRemarks = ref(false);
const contextMenuItems = ref([]);
const showStatusDialog = ref(false);
const selectedVoucherForStatus = ref(null);
const statusForm = reactive({
    obr_status: 'on process',
    remarks: ''
});
const savingStatus = ref(false);
const _obrStatusRaw = useSystemOptions('obr_status');
const obrStatuses = computed(() => ['No OBR', ..._obrStatusRaw.value.map(o => o.label)]);
const showOBRTrackingDialog = ref(false);
const selectedVoucherForOBRTracking = ref(null);
const statusFilter = ref(_url.get('status') || '');
const obrNoFilter = ref(_url.get('obr_no_mode') || '');
const obrTypeFilter = ref(_url.get('type') || '');
const disbursementTypeFilter = ref(_url.get('dv_type') || '');
const userFilter = ref(_url.get('user') || 'all');
const currentPage = ref(parseInt(_url.get('page') || '1'));
const perPage = ref(parseInt(_url.get('per_page') || '10'));
const filteredTotal = ref(0);
const obrTrackingForm = reactive({
    fiscal_year: new Date().getFullYear(),
    obr_no: '',
    date_obligated: null,
    dv_no: ''
});
const updatingOBRTracking = ref(false);
const obrTrackingResult = ref(null);
const showTrackingHistoryDialog = ref(false);
const trackingHistoryData = ref(null);
const loadingTrackingHistory = ref(false);
const showFileUploadDialog = ref(false);
const selectedVoucherForUpload = ref(null);
const uploadedFiles = ref({
    obr: null,
    dv_payroll: null,
    los: null,
    cheque: null
});
const fileInputs = ref({
    obr: null,
    dv_payroll: null,
    los: null,
    cheque: null
});
const uploadingFile = ref(null);
const voucherDocuments = ref(new Map()); // Map to store documents by voucher ID
const selectedVoucherDocuments = computed(() => {
    if (!selectedVoucher.value) return {};
    return voucherDocuments.value.get(selectedVoucher.value.id) || {};
});

// Preview state
const showPreviewModal = ref(false);
const previewData = ref(null);
const previewZoom = ref(100); // Zoom level for preview images

// QR Code state
const showQrModal = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const uploadPollingInterval = ref(null);

const quillToolbar = [
    [{ align: [] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link']
];

const statusFilterOptions = computed(() => [
    { label: 'No OBR', value: 'No OBR' },
    ..._obrStatusRaw.value.map(o => ({ label: o.label, value: o.label })),
]);

const obrNoFilterOptions = [
    { label: 'With OBR No.', value: 'with' },
    { label: 'Without OBR No.', value: 'without' },
];

const _obrTypeRaw = useSystemOptions('disbursement_type');
const obrTypeFilterOptions = computed(() => _obrTypeRaw.value.map(o => ({
    label: o.label,
    value: o.value.replace(/_/g, ' ').toUpperCase(),
})));

const disbursementTypeFilterOptions = [
    { label: 'Disbursement Voucher', value: 'disbursements' },
    { label: 'Payroll', value: 'payroll' },
];

const handleCreateVoucher = () => {
    editingId.value = null;
    editFormData.value = null;
    showWizard.value = true;
    currentStep.value = 1;
    selectedScholars.value = [];
    voucherType.value = 'obligations';
};

const handleWizardClose = () => {
    showWizard.value = false;
    currentStep.value = 1;
    selectedScholars.value = [];
    fetchVouchers();
};

// Open file upload dialog
const openFileUploadDialog = (voucher) => {
    selectedVoucherForUpload.value = voucher;
    uploadedFiles.value = {
        obr: null,
        dv_payroll: null,
        los: null,
        cheque: null
    };
    showFileUploadDialog.value = true;

    // Fetch existing documents for this voucher
    loadVoucherDocuments(voucher.id);
};

// Load documents for a specific voucher from the API
const loadVoucherDocuments = async (voucherId) => {
    try {
        const response = await axios.get(`/api/fund-transactions/${voucherId}/documents`);
        if (response.data.success && response.data.data) {
            const documentsMap = {};
            response.data.data.forEach(doc => {
                documentsMap[doc.document_type] = doc;
            });
            voucherDocuments.value.set(voucherId, documentsMap);
        }
    } catch (error) {
        console.error('Error loading documents:', error);
    }
};

// Handle file selection
const handleFileSelect = (docType, event) => {
    const file = event.target.files?.[0];
    if (file) {
        // Validate file size (max 10MB)
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            toast.add({
                severity: 'error',
                summary: 'File Too Large',
                detail: `Maximum file size is 10MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB`,
                life: 5000
            });
            event.target.value = '';
            return;
        }
        uploadedFiles.value[docType] = file;
        event.target.value = ''; // Reset input
    }
};

// Upload file to server
const uploadFile = async (docType) => {
    if (!selectedVoucherForUpload.value || !uploadedFiles.value[docType]) return;

    uploadingFile.value = docType;
    try {
        const formData = new FormData();
        formData.append('document', uploadedFiles.value[docType]);
        formData.append('document_type', docType);

        const response = await axios.post(
            `/api/fund-transactions/${selectedVoucherForUpload.value.id}/upload-document`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        );

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${docType.toUpperCase()} document uploaded successfully`,
            life: 3000
        });

        // Store the uploaded file info in cache
        if (!voucherDocuments.value.has(selectedVoucherForUpload.value.id)) {
            voucherDocuments.value.set(selectedVoucherForUpload.value.id, {
                obr: null,
                dv_payroll: null,
                los: null,
                cheque: null
            });
        }
        const docs = voucherDocuments.value.get(selectedVoucherForUpload.value.id);
        docs[docType] = response.data.data;
        uploadedFiles.value[docType] = null;
    } catch (error) {
        console.error(`Error uploading ${docType}:`, error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Upload Failed',
            detail: `Failed to upload ${docType}: ${errorMsg}`,
            life: 5000
        });
    } finally {
        uploadingFile.value = null;
    }
};

// Remove uploaded document
const removeDocument = async (docType) => {
    if (!selectedVoucherForUpload.value) return;

    try {
        await axios.delete(
            `/api/fund-transactions/${selectedVoucherForUpload.value.id}/document/${docType}`
        );

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${docType.toUpperCase()} document removed successfully`,
            life: 3000
        });

        if (voucherDocuments.value.has(selectedVoucherForUpload.value.id)) {
            const docs = voucherDocuments.value.get(selectedVoucherForUpload.value.id);
            docs[docType] = null;
        }
        uploadedFiles.value[docType] = null;
    } catch (error) {
        console.error(`Error removing ${docType}:`, error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Remove Failed',
            detail: `Failed to remove ${docType}: ${errorMsg}`,
            life: 5000
        });
    }
};

// Download document
const downloadDocument = (docType) => {
    if (!selectedVoucherForUpload.value) return;
    const url = `/api/fund-transactions/${selectedVoucherForUpload.value.id}/document/${docType}/download`;
    window.open(url, '_blank');
};

// Preview document
const previewDocument = async (docType) => {
    if (!selectedVoucherForUpload.value) return;

    const doc = voucherDocuments.value.get(selectedVoucherForUpload.value.id)?.[docType];
    if (!doc) {
        console.error('Document not found:', docType);
        return;
    }

    const downloadUrl = doc.download_url || `/api/fund-transactions/${selectedVoucherForUpload.value.id}/document/${docType}/download`;

    previewData.value = {
        docType: docType,
        filename: doc.filename,
        mimeType: doc.mime_type,
        url: downloadUrl,
        path: downloadUrl // For images and PDFs, use the download URL
    };

    previewZoom.value = 100; // Reset zoom when opening new preview
    showPreviewModal.value = true;
};

// Show QR code for mobile upload
const showQrCode = async (voucher, docType = null) => {
    try {
        const response = await axios.post(`/api/fund-transactions/${voucher.id}/generate-qr`, {
            doc_type: docType
        });

        if (!response.data.qr_code_svg) {
            throw new Error('No QR code SVG in response');
        }

        const qrSvg = response.data.qr_code_svg;

        qrCodeData.value = {
            qrCode: qrSvg,
            url: response.data.url,
            expiresAt: response.data.expires_at,
            voucher: voucher,
            docType: docType
        };
        showQrModal.value = true;
        startQrCountdown();
    } catch (error) {
        console.error('Failed to generate QR code:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || error.message || 'Failed to generate QR code',
            life: 5000
        });
    }
};

// Start countdown timer for QR code expiration
const startQrCountdown = () => {
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }

    const updateCountdown = () => {
        if (!qrCodeData.value) return;

        const now = new Date();
        const expiresAt = new Date(qrCodeData.value.expiresAt);
        const diff = expiresAt - now;

        if (diff <= 0) {
            qrCountdown.value = 'EXPIRED';
            clearInterval(qrCountdownInterval.value);
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        if (days > 0) {
            qrCountdown.value = `${days}d ${hours}h`;
        } else if (hours > 0) {
            qrCountdown.value = `${hours}h ${minutes}m`;
        } else {
            qrCountdown.value = `${minutes}m ${seconds}s`;
        }
    };

    updateCountdown();
    qrCountdownInterval.value = setInterval(updateCountdown, 1000);
};

// Copy URL to clipboard
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'URL copied to clipboard',
            life: 2000
        });
    }).catch(() => {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to copy to clipboard',
            life: 3000
        });
    });
};

// Start polling for uploads while QR modal is open
const startUploadPolling = () => {
    uploadPollingInterval.value = setInterval(() => {
        if (showQrModal.value && qrCodeData.value?.voucher?.id) {
            fetchVouchers();
        }
    }, 3000);
};

// Stop polling when modal closes
const stopUploadPolling = () => {
    if (uploadPollingInterval.value) {
        clearInterval(uploadPollingInterval.value);
        uploadPollingInterval.value = null;
    }
};

const handleScholarSelection = (scholars, type) => {
    selectedScholars.value = scholars;
    voucherType.value = type;
};

// Fetch vouchers from API
const fetchVouchers = async () => {
    loading.value = true;
    try {
        const params = {};
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (statusFilter.value) params.obr_status = statusFilter.value;
        if (obrNoFilter.value) params.obr_no_mode = obrNoFilter.value;
        if (obrTypeFilter.value) params.obr_type = obrTypeFilter.value;
        if (disbursementTypeFilter.value) params.disbursement_type = disbursementTypeFilter.value;
        if (userFilter.value === 'my-records') {
            const userId = page.props.auth?.user?.id;
            if (userId) params.created_by = userId;
        }
        params.page = currentPage.value;
        params.per_page = perPage.value;

        const response = await axios.get('/api/fund-transactions', { params });
        vouchers.value = response.data.data || [];
        totalRecordsCount.value = response.data.total ?? 0;
        filteredTotal.value = response.data.filtered_total ?? 0;
        myRecordsCount.value = response.data.my_records_count ?? 0;

        // Fetch and cache scholars for school payees
        for (const voucher of vouchers.value) {
            if (isPayeeSchool(voucher) && voucher.scholar_ids?.length > 0) {
                fetchAndCacheScholarDetails(voucher.scholar_ids);
            }
        }
    } catch (error) {
        console.error('Error fetching vouchers:', error);
        vouchers.value = [];
    } finally {
        loading.value = false;
    }
};

// Fetch and cache scholar details
const fetchAndCacheScholarDetails = async (scholarIds) => {
    if (!scholarIds || scholarIds.length === 0) return;

    try {
        for (const scholar of scholarIds) {
            const profileId = typeof scholar === 'object' ? scholar.profile_id : scholar;

            // Skip if already in cache
            if (scholarsCache.value.has(profileId)) continue;

            try {
                const response = await axios.get(`/api/scholarships/profile/${profileId}`);
                if (response.data.data) {
                    scholarsCache.value.set(profileId, response.data.data);
                }
            } catch (error) {
                console.error(`Error fetching scholar ${profileId}:`, error);
            }
        }
    } catch (error) {
        console.error('Error in fetchAndCacheScholarDetails:', error);
    }
};

// Fetch scholar details for display in modal
const fetchScholarsDetails = async (scholarIds) => {
    if (!scholarIds || scholarIds.length === 0) {
        scholarsDetails.value = [];
        return;
    }

    loadingScholars.value = true;
    try {
        const details = [];
        for (const scholar of scholarIds) {
            const profileId = typeof scholar === 'object' ? scholar.profile_id : scholar;

            // Check cache first
            if (scholarsCache.value.has(profileId)) {
                details.push(scholarsCache.value.get(profileId));
            } else {
                try {
                    const response = await axios.get(`/api/scholarships/profile/${profileId}`);
                    if (response.data.data) {
                        scholarsCache.value.set(profileId, response.data.data);
                        details.push(response.data.data);
                    }
                } catch (error) {
                    console.error(`Error fetching scholar ${profileId}:`, error);
                }
            }
        }
        scholarsDetails.value = details;
    } catch (error) {
        console.error('Error fetching scholars details:', error);
        scholarsDetails.value = [];
    } finally {
        loadingScholars.value = false;
    }
};

const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Roles is an array of strings like ['administrator']
    return user.roles?.includes('administrator') ?? false;
});

// Computed property for user record counts
const myRecordsCount = ref(0);

const totalRecordsCount = ref(0);

// Fetch tracking history for a voucher
const fetchTrackingHistory = async (voucher) => {
    if (!voucher.fiscal_year || !voucher.obr_no) {
        toast.add({
            severity: 'warn',
            summary: 'Incomplete OBR Data',
            detail: `Fiscal Year: ${voucher.fiscal_year}, OBR No: ${voucher.obr_no}. Please save OBR tracking first.`,
            life: 5000
        });
        return false;
    }

    loadingTrackingHistory.value = true;
    try {
        const params = {
            fiscal_year: voucher.fiscal_year,
            obr_no: voucher.obr_no,
            dv_no: voucher.dv_no || '',
            type: voucher.type || ''
        };

        const response = await axios.get('/api/obr-tracking-info', { params });

        if (response.data.success) {
            // Store the tracking data from wrapped response
            trackingHistoryData.value = response.data.data;

            // If DV number was auto-fetched, update the voucher in the list
            if (response.data.used_dv_no && !voucher.dv_no) {
                voucher.dv_no = response.data.used_dv_no;
                // Also update in the vouchers array
                const voucherIndex = vouchers.value.findIndex(v => v.id === voucher.id);
                if (voucherIndex > -1) {
                    vouchers.value[voucherIndex].dv_no = response.data.used_dv_no;
                }
            }

            showTrackingHistoryDialog.value = true;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: response.data.message || 'Failed to fetch tracking history',
                life: 3000
            });
        }
    } catch (error) {
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMsg,
            life: 5000
        });
    } finally {
        loadingTrackingHistory.value = false;
    }
};

// Delete voucher
const deleteVoucher = (voucherId) => {
    voucherToDelete.value = voucherId;
    showDeleteConfirmDialog.value = true;
};

// View voucher
const viewVoucher = async (voucherId) => {
    const voucher = vouchers.value.find(v => v.id === voucherId);
    if (voucher) {
        selectedVoucher.value = voucher;
        viewModalTab.value = 'details';
        showViewDialog.value = true;

        // Load both scholar details and document metadata for tab content.
        await Promise.all([
            fetchScholarsDetails(voucher.scholar_ids || []),
            loadVoucherDocuments(voucher.id)
        ]);
    }
};

// Edit voucher
const editVoucher = async (voucherId) => {
    try {
        // Fetch fresh voucher data from API to ensure we have all fields
        const response = await axios.get(`/api/fund-transactions/${voucherId}`);
        const voucher = response.data.data;

        // Set up edit data
        editFormData.value = {
            ...voucher,
            responsibility_center: voucher.responsibility_center || ''
        };
        editingId.value = voucherId;
        showWizard.value = true;
    } catch (error) {
        console.error('Error fetching voucher:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load voucher data',
            life: 3000
        });
    }
};

// Save edited voucher
const saveVoucher = async () => {
    if (!editFormData.value) return;

    editingId.value = editFormData.value.id;
    try {
        await axios.put(`/api/fund-transactions/${editFormData.value.id}`, editFormData.value);

        // Update the voucher in the list
        const index = vouchers.value.findIndex(v => v.id === editFormData.value.id);
        if (index !== -1) {
            vouchers.value[index] = editFormData.value;
        }

        showWizard.value = false;
        editFormData.value = null;
        editingId.value = null;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Voucher updated successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error updating voucher:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update voucher: ' + errorMsg,
            life: 5000
        });
    } finally {
        editingId.value = null;
    }
};

// Generate document
const generateDocument = async (docType) => {
    if (!selectedVoucher.value) return;

    if (docType === 'OBR') {
        // Client-side PDF — no Node.js / Browsershot needed
        try {
            const html = renderVueTemplate(ObrTemplate, { voucher: selectedVoucher.value, scholarDetails: scholarsDetails.value });
            const title = `OBR-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title);
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = 'a4';
            showPdfPreview.value = true;
        } catch (error) {
            console.error('Error generating OBR:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to generate OBR: ' + error.message, life: 5000 });
        }
        return;
    }

    if (docType === 'DV') {
        try {
            const html = renderVueTemplate(DvTemplate, { voucher: selectedVoucher.value, scholarDetails: scholarsDetails.value });
            const title = `DV-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, 'long');
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = 'long';
            showPdfPreview.value = true;
        } catch (error) {
            console.error('Error generating DV:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to generate DV: ' + error.message, life: 5000 });
        }
        return;
    }

    if (docType === 'PR') {
        try {
            const html = renderVueTemplate(PayrollTemplate, { voucher: selectedVoucher.value, scholarDetails: scholarsDetails.value });
            const title = `Payroll-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, 'landscape');
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = 'landscape';
            showPdfPreview.value = true;
        } catch (error) {
            console.error('Error generating Payroll:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to generate Payroll: ' + error.message, life: 5000 });
        }
        return;
    }

    // LOS — client-side
    if (docType === 'LOS') {
        try {
            const html = renderVueTemplate(LosTemplate, { voucher: selectedVoucher.value, scholarDetails: scholarsDetails.value });
            const title = `ListOfScholars-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, 'a4');
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = 'a4';
            showPdfPreview.value = true;
        } catch (error) {
            console.error('Error generating LOS:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to generate LOS: ' + error.message, life: 5000 });
        }
        return;
    }
};

// Confirm delete
const confirmDelete = async () => {
    if (!voucherToDelete.value) return;

    deletingId.value = voucherToDelete.value;
    try {
        await axios.delete(`/api/fund-transactions/${voucherToDelete.value}`);
        vouchers.value = vouchers.value.filter(v => v.id !== voucherToDelete.value);
        showDeleteConfirmDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Voucher deleted successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error deleting voucher:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete voucher: ' + errorMsg,
            life: 5000
        });
    } finally {
        deletingId.value = null;
        voucherToDelete.value = null;
    }
};

// Context Menu - DataTable row context menu handler
const onRowContextMenu = (event) => {
    openContextMenu(event.originalEvent, event.data);
};

const openContextMenu = (event, voucher) => {
    event.preventDefault();
    const items = [
        {
            label: 'View',
            icon: 'eye',
            command: () => viewVoucher(voucher.id)
        },
        {
            label: 'Edit',
            icon: 'pencil',
            command: () => editVoucher(voucher.id)
        },
        {
            label: 'Upload Documents',
            icon: 'upload',
            command: () => openFileUploadDialog(voucher)
        },
        {
            label: 'Add/Edit Remarks',
            icon: 'comment',
            command: () => openRemarksModal(voucher)
        },
        {
            label: 'Change Status',
            icon: 'sync',
            command: () => openStatusModal(voucher)
        },
        {
            label: 'Update OBR Info',
            icon: 'pencil',
            command: () => openOBRTrackingDialog(voucher)
        },
        {
            label: 'View Tracking History',
            icon: 'history',
            command: () => fetchTrackingHistory(voucher)
        }
    ];

    if (isAdmin.value) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Delete',
            icon: 'trash',
            command: () => deleteVoucher(voucher.id),
            class: 'p-menuitem-danger'
        });
    }

    contextMenuItems.value = items;
    contextMenu.value.show(event);
};

// Open remarks modal
const openRemarksModal = (voucher) => {
    selectedVoucherForRemarks.value = voucher;
    remarksForm.remarks = voucher.remarks || '';
    showRemarksDialog.value = true;
};

// Save remarks
const saveRemarks = async () => {
    if (!selectedVoucherForRemarks.value) return;

    savingRemarks.value = true;
    try {
        // GET the current voucher data
        const currentVoucher = await axios.get(`/api/fund-transactions/${selectedVoucherForRemarks.value.id}`);
        const voucherData = currentVoucher.data.data;

        // PUT with all required fields plus updated remarks
        await axios.put(`/api/fund-transactions/${selectedVoucherForRemarks.value.id}`, {
            disbursement_type: voucherData.disbursement_type,
            explanation: voucherData.explanation,
            payee_type: voucherData.payee_type,
            payee_name: voucherData.payee_name,
            payee_address: voucherData.payee_address,
            responsibility_center: voucherData.responsibility_center,
            account_code: voucherData.account_code,
            particulars_name: voucherData.particulars_name,
            particulars_description: voucherData.particulars_description,
            amount: voucherData.amount,
            obr_type: voucherData.obr_type,
            scholar_ids: voucherData.scholar_ids,
            remarks: remarksForm.remarks,
            transaction_status: voucherData.transaction_status
        });

        // Update the voucher in the list
        const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForRemarks.value.id);
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].remarks = remarksForm.remarks;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForRemarks.value.id) {
            selectedVoucher.value.remarks = remarksForm.remarks;
        }

        showRemarksDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Remarks saved successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error saving remarks:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save remarks: ' + errorMsg,
            life: 5000
        });
    } finally {
        savingRemarks.value = false;
    }
};

// Open transaction status modal
const openStatusModal = (voucher) => {
    selectedVoucherForStatus.value = voucher;
    statusForm.obr_status = voucher.obr_status || 'On Process';
    statusForm.remarks = voucher.remarks || '';
    showStatusDialog.value = true;
};

// Save transaction status
const saveStatus = async () => {
    if (!selectedVoucherForStatus.value) return;

    savingStatus.value = true;
    try {
        // Just send the status and remarks - minimal update
        const response = await axios.patch(
            `/api/fund-transactions/${selectedVoucherForStatus.value.id}/update-status`,
            {
                transaction_status: statusForm.obr_status,
                remarks: statusForm.remarks
            }
        );

        // Update the voucher in the list with the actual returned values
        const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForStatus.value.id);
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].obr_status = response.data.data?.obr_status;
            vouchers.value[voucherIndex].remarks = response.data.data?.remarks;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForStatus.value.id) {
            selectedVoucher.value.obr_status = response.data.data?.obr_status;
            selectedVoucher.value.remarks = response.data.data?.remarks;
        }

        showStatusDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Transaction status updated successfully',
            life: 3000
        });
    } catch (error) {
        console.error('Error saving transaction status:', error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update transaction status: ' + errorMsg,
            life: 5000
        });
    } finally {
        savingStatus.value = false;
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '---';
    try {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return date;
    }
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount || 0);
};

// Calculate total amount (sum of individual line item amounts if available, otherwise header amount)
const calculateTotalAmount = (voucher) => {
    if (!voucher) return 0;

    // If scholar_ids is an array, try to sum individual amounts
    if (Array.isArray(voucher.scholar_ids) && voucher.scholar_ids.length > 0) {
        let hasAmounts = false;
        let total = 0;

        for (const scholar of voucher.scholar_ids) {
            // Check if scholar has an amount property
            if (typeof scholar === 'object' && scholar !== null && typeof scholar.amount !== 'undefined') {
                total += parseFloat(scholar.amount) || 0;
                hasAmounts = true;
            }
        }

        // If we found amounts in the scholar objects, return the sum
        if (hasAmounts) {
            return total;
        }

        // If scholar_ids exist but no individual amounts, fall back to header amount
        if (voucher.amount) {
            return parseFloat(voucher.amount);
        }
    }

    // Fallback: use header amount
    if (voucher.amount) {
        return parseFloat(voucher.amount);
    }

    return 0;
};

// Get status color for badge
const getStatusColor = (status) => {
    const statusColors = {
        'No OBR': 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
        'LOA': 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
        'Irregular': 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-200',
        'Transferred': 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200',
        'Claimed': 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200',
        'Paid': 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
        'On Process': 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
        'Denied': 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200'
    };
    return statusColors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
};

// Check if payee is school
const isPayeeSchool = (voucher) => {
    return voucher?.payee_type?.toLowerCase() === 'school' ||
        voucher?.payee_name?.toLowerCase().includes('school');
};

// Get first scholar name from cache (for table display)
const getFirstScholarNameFromCache = (voucher) => {
    if (!voucher?.scholar_ids || voucher.scholar_ids.length === 0) {
        return '';
    }

    // Get first scholar ID  
    const firstScholarId = typeof voucher.scholar_ids[0] === 'object'
        ? voucher.scholar_ids[0].profile_id
        : voucher.scholar_ids[0];

    // Look up in cache
    const scholar = scholarsCache.value.get(firstScholarId);
    if (scholar) {
        const name = `${scholar.first_name} ${scholar.last_name}`;
        return name.length > 25 ? name.substring(0, 25) + '...' : name;
    }
    return '';
};

// Get first scholar name truncated (for view modal)
const getFirstScholarName = (voucher) => {
    if (!voucher?.scholar_ids || voucher.scholar_ids.length === 0) {
        return '';
    }
    // If scholarsDetails has the first scholar, use its name
    const firstScholar = scholarsDetails.value?.[0];
    if (firstScholar) {
        const name = `${firstScholar.first_name} ${firstScholar.last_name}`;
        return name.length > 25 ? name.substring(0, 25) + '...' : name;
    }
    return '';
};

// Get document button label based on voucher type
const getDocumentButtonLabel = () => {
    if (!selectedVoucher.value) return 'Document';
    return selectedVoucher.value.disbursement_type === 'payroll' ? 'PR' : 'DV';
};

// Get document type to generate based on voucher type
const getDocumentType = () => {
    if (!selectedVoucher.value) return 'DV';
    return selectedVoucher.value.disbursement_type === 'payroll' ? 'PR' : 'DV';
};

// Fetch responsibility centers and particulars
const fetchResponsibilityCentersAndParticulars = async () => {
    try {
        const response = await axios.get('/api/responsibility-centers');
        if (response.data && response.data.data) {
            responsibilityCenters.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching responsibility centers:', error);
    }
};

// Update OBR tracking
const updateOBRTracking = async (fiscalYear, obrNo, dvNo) => {
    try {
        // Validate required fields - Only fiscal year and OBR are required
        if (!fiscalYear || !obrNo) {
            toast.add({
                severity: 'warn',
                summary: 'Missing Required Fields',
                detail: 'Fiscal Year and OBR Number are required. DV Number is optional and will be auto-fetched.',
                life: 5000
            });
            return false;
        }

        // Just return success with the data (no external API call)
        return {
            success: true,
            data: {
                fiscal_year: fiscalYear,
                obr_no: obrNo,
                dv_no: dvNo || null
            }
        };
    } catch (error) {
        console.error('Error updating OBR tracking:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message,
            life: 5000
        });
        return false;
    }
};

// Open OBR Tracking dialog
const openOBRTrackingDialog = (voucher) => {
    selectedVoucherForOBRTracking.value = voucher;
    obrTrackingForm.fiscal_year = voucher.fiscal_year || new Date().getFullYear();
    obrTrackingForm.obr_no = voucher.obr_no || '';
    obrTrackingForm.date_obligated = voucher.date_obligated ? voucher.date_obligated.substring(0, 10) : null;
    obrTrackingForm.dv_no = voucher.dv_no || '';
    obrTrackingResult.value = null;

    showOBRTrackingDialog.value = true;
};

// Save OBR Tracking
const saveOBRTracking = async () => {
    updatingOBRTracking.value = true;
    try {
        const result = await updateOBRTracking(
            obrTrackingForm.fiscal_year,
            obrTrackingForm.obr_no,
            obrTrackingForm.dv_no
        );

        if (result) {
            obrTrackingResult.value = result;

            // Now save the OBR tracking data to the voucher
            if (selectedVoucherForOBRTracking.value) {
                try {
                    // GET current voucher data
                    const currentVoucher = await axios.get(`/api/fund-transactions/${selectedVoucherForOBRTracking.value.id}`);
                    const voucherData = currentVoucher.data.data;

                    // Validate obr_status - preserve existing status
                    const validStatuses = ['No OBR', 'LOA', 'Irregular', 'Transferred', 'Claimed', 'Paid', 'On Process', 'Denied'];
                    const statusToSend = voucherData.obr_status && validStatuses.includes(voucherData.obr_status)
                        ? voucherData.obr_status
                        : (voucherData.obr_status || 'On Process'); // Keep existing status or default to 'On Process' if none

                    // PUT with OBR tracking fields
                    await axios.put(`/api/fund-transactions/${selectedVoucherForOBRTracking.value.id}`, {
                        disbursement_type: voucherData.disbursement_type,
                        explanation: voucherData.explanation,
                        payee_type: voucherData.payee_type,
                        payee_name: voucherData.payee_name,
                        payee_address: voucherData.payee_address,
                        responsibility_center: voucherData.responsibility_center,
                        account_code: voucherData.account_code,
                        particulars_name: voucherData.particulars_name,
                        particulars_description: voucherData.particulars_description,
                        amount: voucherData.amount,
                        obr_type: voucherData.obr_type,
                        scholar_ids: voucherData.scholar_ids,
                        remarks: voucherData.remarks,
                        transaction_status: statusToSend,
                        fiscal_year: parseInt(obrTrackingForm.fiscal_year) || null,
                        obr_no: obrTrackingForm.obr_no || null,
                        date_obligated: obrTrackingForm.date_obligated || null,
                        dv_no: obrTrackingForm.dv_no || null
                    });

                    // Update the voucher in the local list
                    const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForOBRTracking.value.id);
                    if (voucherIndex > -1) {
                        vouchers.value[voucherIndex].fiscal_year = obrTrackingForm.fiscal_year;
                        vouchers.value[voucherIndex].obr_no = obrTrackingForm.obr_no;
                        vouchers.value[voucherIndex].date_obligated = obrTrackingForm.date_obligated;
                        vouchers.value[voucherIndex].dv_no = obrTrackingForm.dv_no;
                        vouchers.value[voucherIndex].obr_status = statusToSend;
                    }

                    // Also update selectedVoucher if it's the same voucher
                    if (selectedVoucher.value?.id === selectedVoucherForOBRTracking.value.id) {
                        selectedVoucher.value.fiscal_year = obrTrackingForm.fiscal_year;
                        selectedVoucher.value.obr_no = obrTrackingForm.obr_no;
                        selectedVoucher.value.date_obligated = obrTrackingForm.date_obligated;
                        selectedVoucher.value.dv_no = obrTrackingForm.dv_no;
                        selectedVoucher.value.obr_status = statusToSend;
                    }

                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'OBR tracking information saved to voucher',
                        life: 3000
                    });
                } catch (saveError) {
                    console.error('Error saving OBR tracking to voucher:', saveError);
                    const errorMessage = saveError.response?.data?.errors
                        ? Object.entries(saveError.response.data.errors)
                            .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                            .join(' | ')
                        : saveError.response?.data?.message || 'Failed to save OBR tracking';
                    toast.add({
                        severity: 'error',
                        summary: 'Validation Error',
                        detail: errorMessage,
                        life: 5000
                    });
                }
            }
        } else {
            showOBRTrackingDialog.value = false;
        }
    } catch (error) {
        console.error('Error saving OBR tracking:', error);
    } finally {
        updatingOBRTracking.value = false;
    }
};

// Sync current filter state to the browser URL (replaceState — no navigation)
const syncFiltersToUrl = () => {
    const params = new URLSearchParams();
    if (searchQuery.value?.trim()) params.set('search', searchQuery.value.trim());
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (obrNoFilter.value) params.set('obr_no_mode', obrNoFilter.value);
    if (obrTypeFilter.value) params.set('type', obrTypeFilter.value);
    if (disbursementTypeFilter.value) params.set('dv_type', disbursementTypeFilter.value);
    if (userFilter.value && userFilter.value !== 'all') params.set('user', userFilter.value);
    if (currentPage.value > 1) params.set('page', currentPage.value);
    if (perPage.value !== 10) params.set('per_page', perPage.value);
    const qs = params.toString();
    window.history.replaceState(null, '', qs ? `${window.location.pathname}?${qs}` : window.location.pathname);
};

// Pagination handler (matches Applicants pattern)
const onPageChange = (e) => {
    currentPage.value = e.page + 1;
    perPage.value = e.rows;
    syncFiltersToUrl();
    fetchVouchers();
};

// Re-fetch when dropdown filters or user filter changes (reset to page 1)
watch([statusFilter, obrNoFilter, obrTypeFilter, disbursementTypeFilter, userFilter], () => {
    currentPage.value = 1;
    syncFiltersToUrl();
    fetchVouchers();
});

// Re-fetch when records-per-page changes via RecordsSelect (reset to page 1)
watch(perPage, () => {
    currentPage.value = 1;
    syncFiltersToUrl();
    fetchVouchers();
});

// Debounced re-fetch for search input (reset to page 1)
let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        syncFiltersToUrl();
        fetchVouchers();
    }, 400);
});

// Watch for editFormData changes to ensure responsibility center is properly set
watch(
    () => editFormData.value?.responsibility_center,
    (newValue) => {
        if (editFormData.value && newValue !== undefined) {
            // Ensure the value is a string and matches one of the options
            const stringValue = String(newValue).trim();
            const validOption = responsibilityCenters.value.find(rc => rc.code === stringValue);
            if (validOption) {
                editFormData.value.responsibility_center = validOption.code;
            }
        }
    }
);

// Handle QR modal show/hide - manage countdown and polling
watch(
    () => showQrModal.value,
    (newValue) => {
        if (newValue) {
            // Modal opened - start polling for uploads
            startUploadPolling();
        } else {
            // Modal closed - cleanup countdown and polling
            if (qrCountdownInterval.value) {
                clearInterval(qrCountdownInterval.value);
                qrCountdownInterval.value = null;
            }
            stopUploadPolling();
        }
    }
);

// Fetch on mount
onMounted(() => {
    fetchVouchers();
    fetchResponsibilityCentersAndParticulars();
});
</script>

<template>

    <Head title="Fund Transactions" />

    <AdminLayout>

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <!-- Header -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon name="credit-card" :size="32" class="text-indigo-900" />
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-200">Fund Transactions Management
                            </h1>
                            <p class="text-sm text-gray-600">Create and manage financial transactions</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <AppButton icon="plus" @click="handleCreateVoucher" severity="success" rounded outlined
                        v-tooltip.bottom="'Create Fund Transaction'" />
                </template>
            </Toolbar>

            <!-- List/Summary Section -->
            <Panel class="!rounded-4xl overflow-hidden mt-8">
                <!-- Info Bar -->
                <div
                    class="flex items-center justify-between gap-4 mb-4 p-3 bg-gray-50 dark:bg-[#1e242b] rounded-4xl -mt-2">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <AppIcon name="search" :size="14" class="text-gray-400" />
                            </InputIcon>
                            <InputText v-model="searchQuery" placeholder="Search obr no, payee, or scholar..."
                                class="w-full" size="small" />
                        </IconField>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm opacity-60 hidden sm:block">Right click row for actions</span>
                        <AppButton icon="refresh" severity="secondary" size="small" rounded outlined
                            @click="fetchVouchers" :disabled="loading" :loading="loading"
                            v-tooltip.bottom="'Refresh'" />
                    </div>
                </div>

                <!-- Record Filter + Dropdowns Row -->
                <div class="flex flex-wrap gap-3 items-center mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="userFilter" name="userFilter" value="all" inputId="uf-all" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">All Records</span>
                        <Badge :value="totalRecordsCount" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="userFilter" name="userFilter" value="my-records" inputId="uf-my" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">My Records</span>
                        <Badge :value="myRecordsCount" severity="secondary" />
                    </label>

                    <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>

                    <Select v-model="statusFilter" :options="statusFilterOptions" optionLabel="label"
                        optionValue="value" placeholder="OBR Status" size="small" class="w-40" showClear />
                    <Select v-model="obrNoFilter" :options="obrNoFilterOptions" optionLabel="label" optionValue="value"
                        placeholder="OBR No." size="small" class="w-44" showClear />
                    <Select v-model="obrTypeFilter" :options="obrTypeFilterOptions" optionLabel="label"
                        optionValue="value" placeholder="OBR Type" size="small" class="w-44" showClear />
                    <Select v-model="disbursementTypeFilter" :options="disbursementTypeFilterOptions"
                        optionLabel="label" optionValue="value" placeholder="DV Type" size="small" class="w-44"
                        showClear />

                    <AppButton
                        v-if="statusFilter || obrNoFilter || obrTypeFilter || disbursementTypeFilter || searchQuery || userFilter !== 'all'"
                        icon="funnel-x" severity="danger" text rounded
                        @click="statusFilter = null; obrNoFilter = null; obrTypeFilter = null; disbursementTypeFilter = null; searchQuery = ''; userFilter = 'all'"
                        v-tooltip.bottom="'Clear all filters'" />

                    <div class="ml-auto flex items-center gap-2">
                        <RecordsSelect v-model="perPage" size="small" class="w-auto" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">/ <strong>{{ filteredTotal
                        }}</strong></span>
                    </div>
                </div>

                <!-- Context Menu -->
                <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body">
                    <template #item="{ item, props }">
                        <a v-ripple v-bind="props.action" class="flex items-center gap-2 w-full">
                            <AppIcon v-if="item.icon" :name="item.icon" :size="14" />
                            <span>{{ item.label }}</span>
                            <AppIcon v-if="item.items" name="chevron-right" :size="14" class="ml-auto" />
                        </a>
                    </template>
                </ContextMenu>

                <!-- DataTable -->
                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="vouchers" stripedRows
                    showGridlines responsiveLayout="scroll" :loading="loading"
                    :emptyMessage="filteredTotal === 0 ? 'No vouchers created yet. Click Create Fund Transaction to get started.' : 'No vouchers match your search.'"
                    :scrollable="true" scrollHeight="600px" @row-contextmenu="onRowContextMenu" contextMenu
                    v-model:contextMenuSelection="selectedContextVoucher" lazy paginator :rows="perPage"
                    :totalRecords="filteredTotal" :first="(currentPage - 1) * perPage"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                    @page="onPageChange">

                    <Column header="OBR No" :headerStyle="{ minWidth: '140px' }" :bodyStyle="{ minWidth: '140px' }">
                        <template #body="slotProps">
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ slotProps.data.obr_no
                                || '---' }}</span>
                        </template>
                    </Column>

                    <Column header="Payee" :headerStyle="{ minWidth: '200px' }" :bodyStyle="{ minWidth: '200px' }">
                        <template #body="slotProps">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{
                                slotProps.data.payee_name }}</div>
                            <div v-if="isPayeeSchool(slotProps.data)"
                                class="text-xs font-medium italic text-gray-600 dark:text-gray-400 mt-1">
                                {{ getFirstScholarNameFromCache(slotProps.data) || '---' }}
                            </div>
                        </template>
                    </Column>

                    <Column header="OBR Type" :headerStyle="{ minWidth: '130px' }" :bodyStyle="{ minWidth: '130px' }">
                        <template #body="slotProps">
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-medium': true,
                                'text-gray-800 dark:text-gray-200': slotProps.data.obr_type === 'REGULAR',
                                'text-yellow-800 dark:text-yellow-200': slotProps.data.obr_type === 'FINANCIAL ASSISTANCE',
                                'text-purple-800 dark:text-purple-200': slotProps.data.obr_type === 'REIMBURSEMENT'
                            }">
                                {{ slotProps.data.obr_type || '---' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Disbursement Type" :headerStyle="{ minWidth: '150px' }"
                        :bodyStyle="{ minWidth: '150px' }">
                        <template #body="slotProps">
                            <span class="text-xs font-medium uppercase">
                                {{ slotProps.data.disbursement_type === 'disbursements' ? 'DV' :
                                    (slotProps.data.disbursement_type === 'payroll' ? 'Payroll' :
                                        slotProps.data.disbursement_type) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Status" :headerStyle="{ minWidth: '140px' }" :bodyStyle="{ minWidth: '140px' }">
                        <template #body="slotProps">
                            <span
                                :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(slotProps.data.obr_status)]">
                                {{ slotProps.data.obr_status || 'On Process' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Total Amount" :headerStyle="{ minWidth: '130px' }"
                        :bodyStyle="{ minWidth: '130px' }">
                        <template #body="slotProps">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                formatAmount(calculateTotalAmount(slotProps.data)) }}</span>
                        </template>
                    </Column>

                    <Column header="Processed By" :headerStyle="{ minWidth: '130px' }"
                        :bodyStyle="{ minWidth: '130px' }">
                        <template #body="slotProps">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{
                                slotProps.data.creator?.name || '---'
                            }}</span>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">{{
                                formatDate(slotProps.data.created_at) }}</div>
                        </template>
                    </Column>

                    <Column header="OBR Date" :headerStyle="{ minWidth: '110px' }" :bodyStyle="{ minWidth: '110px' }">
                        <template #body="slotProps">
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{
                                slotProps.data.date_obligated ? new
                                    Date(slotProps.data.date_obligated).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short', day: 'numeric'
                                    }) : '---' }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" :headerStyle="{ width: '70px' }" :bodyStyle="{ width: '70px' }">
                        <template #body="slotProps">
                            <AppButton icon="ellipsis-v" @click="(e) => openContextMenu(e, slotProps.data)" text rounded
                                size="small" v-tooltip="'Actions'" />
                        </template>
                    </Column>
                </DataTable>
            </Panel>
        </div>

        <!-- Fund Transaction Wizard (Create & Edit) -->
        <VoucherWizard v-if="showWizard" :visible="showWizard" :mode="editingId ? 'edit' : 'create'"
            :voucherId="editingId" :initialData="editFormData" @close="handleWizardClose"
            @scholar-selected="handleScholarSelection" />

        <!-- Delete Confirmation Dialog -->
        <DeleteConfirmModal :show="showDeleteConfirmDialog" @update:show="showDeleteConfirmDialog = $event"
            :voucher-number="vouchers.find(v => v.id === voucherToDelete)?.transaction_id || 'N/A'"
            :payee-name="vouchers.find(v => v.id === voucherToDelete)?.payee_name"
            :date="vouchers.find(v => v.id === voucherToDelete)?.created_at ? formatDate(vouchers.find(v => v.id === voucherToDelete).created_at) : null"
            :is-deleting="deletingId === voucherToDelete" @confirm-delete="confirmDelete" />

        <!-- View Fund Transaction Dialog -->
        <ViewTransactionModal :show="showViewDialog" @update:show="showViewDialog = $event">
            <div v-if="selectedVoucher" class="pt-2 h-[78vh] max-h-[780px] flex flex-col">
                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
                    <Tabs v-model:value="viewModalTab" class="relative ">
                        <TabList
                            class="sticky top-0 z-30 -mx-4 w-[calc(100%+2rem)] bg-white/95 dark:bg-[#2a3040]/95 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden px-2 pt-2">
                            <Tab value="details">Transaction Details</Tab>
                            <Tab value="tracking">Tracking Info</Tab>
                            <Tab value="documents">Uploaded Documents</Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel value="details">
                                <div class="ios-section">
                                    <div class="ios-card">
                                        <div class="ios-row">
                                            <span class="ios-row-label">OBR Number</span>
                                            <span class="font-medium">{{ selectedVoucher.obr_no || '---' }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Date Obligated</span>
                                            <span>{{ selectedVoucher.date_obligated ?
                                                formatDate(selectedVoucher.date_obligated)
                                                : '---' }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Disbursement Type</span>
                                            <span>{{ selectedVoucher.disbursement_type === 'disbursements' ?
                                                `Disbursement
                                                Voucher` : (selectedVoucher.disbursement_type === 'payroll' ? 'Payroll'
                                                    :
                                                    selectedVoucher.disbursement_type) }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Payee</span>
                                            <div class="text-right">
                                                <p>{{ selectedVoucher.payee_name }}</p>
                                                <p v-if="isPayeeSchool(selectedVoucher)"
                                                    class="text-xs text-[#8E8E93] mt-0.5">
                                                    Scholar: {{ getFirstScholarName(selectedVoucher) || '---' }}</p>
                                            </div>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Amount</span>
                                            <span class="font-semibold">{{ formatAmount(selectedVoucher.amount)
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Created By</span>
                                            <span>{{ selectedVoucher.creator?.name || '---' }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">Date</span>
                                            <span>{{ formatDate(selectedVoucher.created_at) }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label">OBR Type</span>
                                            <span>{{ selectedVoucher.obr_type || '---' }}</span>
                                        </div>
                                        <div class="ios-row [border-bottom:none]">
                                            <span class="ios-row-label">OBR Status</span>
                                            <span
                                                :class="['px-3 py-1 rounded-full text-xs font-medium inline-block', getStatusColor(selectedVoucher.obr_status)]">{{
                                                    selectedVoucher.obr_status || 'On Process' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="selectedVoucher.remarks" class="ios-section">
                                    <p class="ios-section-label">Remarks</p>
                                    <div class="ios-card px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-gray-100"
                                            v-safe-html="selectedVoucher.remarks">
                                        </div>
                                    </div>
                                </div>

                                <div class="ios-section">
                                    <p class="ios-section-label">Scholars ({{ selectedVoucher.scholar_ids?.length || 0
                                    }})</p>
                                    <div class="ios-card px-4 py-3">
                                        <div v-if="loadingScholars" class="text-center py-2">
                                            <AppIcon name="spinner" :size="12" class="mr-2" /> <span
                                                class="text-xs">Loading...</span>
                                        </div>
                                        <div v-else-if="scholarsDetails && scholarsDetails.length > 0"
                                            class="space-y-1 max-h-48 overflow-y-auto">
                                            <div v-for="(scholar, index) in scholarsDetails" :key="index"
                                                class="text-xs text-gray-700 dark:text-gray-300 py-1 px-2 bg-gray-50 dark:bg-[#272f38] rounded flex items-center justify-between gap-2">
                                                <span class="font-medium">{{ index + 1 }}. {{ scholar.first_name }} {{
                                                    scholar.last_name }}</span>
                                                <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                                    <span v-if="scholar.course_name">{{ scholar.course_name }}</span>
                                                    <span v-if="scholar.year_level" class="ml-1">| {{
                                                        /^(1st|2nd|3rd|4th)$/i.test(scholar.year_level) ?
                                                            scholar.year_level + ` YEAR` : scholar.year_level }}</span>
                                                    <span v-if="scholar.academic_year" class="ml-1">| {{
                                                        scholar.academic_year
                                                    }}</span>
                                                    <span v-if="scholar.term" class="ml-1">| {{ scholar.term }}</span>
                                                </span>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-gray-500 dark:text-gray-400">No scholars</div>
                                    </div>
                                </div>

                                <div class="ios-section">
                                    <div class="ios-card px-4 py-3.5 bg-blue-50 dark:bg-blue-950/30">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-[#1e3a5f] dark:text-blue-200">Total
                                                Amount</p>
                                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{
                                                formatAmount(calculateTotalAmount(selectedVoucher)) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="tracking">
                                <div class="ios-section">
                                    <p class="ios-section-label">Tracking</p>
                                    <div class="ios-card px-4 py-3">
                                        <Button v-if="selectedVoucher?.fiscal_year && selectedVoucher?.obr_no"
                                            label="View Tracking History" @click="fetchTrackingHistory(selectedVoucher)"
                                            class="w-full" severity="info" :loading="loadingTrackingHistory">
                                            <template #icon>
                                                <AppIcon name="history" :size="14" />
                                            </template>
                                        </Button>
                                        <p v-else class="text-xs text-gray-500 dark:text-gray-400">No OBR info available
                                        </p>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="documents">
                                <div class="ios-section">
                                    <p class="ios-section-label">Uploaded Files</p>
                                    <div class="ios-card px-4 py-3 space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">OBR</span>
                                            <Tag :value="selectedVoucherDocuments.obr ? 'Uploaded' : 'Missing'"
                                                :severity="selectedVoucherDocuments.obr ? 'success' : 'contrast'" />
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">DV/Payroll</span>
                                            <Tag :value="selectedVoucherDocuments.dv_payroll ? 'Uploaded' : 'Missing'"
                                                :severity="selectedVoucherDocuments.dv_payroll ? 'success' : 'contrast'" />
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">LOS</span>
                                            <Tag :value="selectedVoucherDocuments.los ? 'Uploaded' : 'Missing'"
                                                :severity="selectedVoucherDocuments.los ? 'success' : 'contrast'" />
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">Cheque</span>
                                            <Tag :value="selectedVoucherDocuments.cheque ? 'Uploaded' : 'Missing'"
                                                :severity="selectedVoucherDocuments.cheque ? 'success' : 'contrast'" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
                <div
                    class="sticky bottom-0 z-20 -mx-4 w-[calc(100%+2rem)] bg-white/95 dark:bg-[#2a3040]/95 backdrop-blur-md border-t border-gray-200 dark:border-white/10 py-3">
                    <div class="flex w-full items-center gap-1.5 flex-nowrap overflow-x-auto px-3">
                        <Button label="OBR" @click="generateDocument('OBR')" severity="info" size="small"
                            class="flex-1 whitespace-nowrap !rounded-xl text-xs" v-tooltip.bottom="'Generate OBR'">
                            <template #icon>
                                <AppIcon name="file-pdf" :size="14" />
                            </template>
                        </Button>
                        <Button :label="getDocumentButtonLabel()" @click="generateDocument(getDocumentType())"
                            severity="success" size="small" class="flex-1 whitespace-nowrap !rounded-xl text-xs"
                            v-tooltip.bottom="'Generate DV/PR'">
                            <template #icon>
                                <AppIcon name="money-bill" :size="14" />
                            </template>
                        </Button>
                        <Button label="LOS" @click="generateDocument('LOS')" severity="danger" size="small"
                            class="flex-1 whitespace-nowrap !rounded-xl text-xs" v-tooltip.bottom="'Generate LOS'">
                            <template #icon>
                                <AppIcon name="users" :size="14" />
                            </template>
                        </Button>
                        <Button label="Upload" @click="openFileUploadDialog(selectedVoucher)" severity="warning" outline
                            size="small" class="flex-1 whitespace-nowrap !rounded-xl text-xs"
                            v-tooltip.bottom="'Upload Documents'">
                            <template #icon>
                                <AppIcon name="upload" :size="14" />
                            </template>
                        </Button>
                    </div>
                </div>
            </div>
        </ViewTransactionModal>

        <!-- File Upload Dialog -->
        <FileUploadModal :show="showFileUploadDialog" @update:show="showFileUploadDialog = $event">
            <div v-if="selectedVoucherForUpload" class="pt-4">
                <!-- Voucher Header -->
                <div class="ios-section">
                    <div class="ios-card px-4 py-3 bg-blue-50 dark:bg-blue-950/30">
                        <p class="text-[11px] font-semibold text-blue-700 dark:text-blue-400 uppercase mb-1">
                            Voucher</p>
                        <p class="text-sm font-semibold text-[#1e3a5f] dark:text-blue-200">{{
                            selectedVoucherForUpload.transaction_id }}</p>
                        <p class="text-xs text-blue-500 mt-0.5">{{
                            selectedVoucherForUpload.payee_name }}</p>
                    </div>
                </div>

                <div class="ios-section">
                    <p class="ios-section-label">Instructions</p>
                    <div class="ios-card px-4 py-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Upload up to four documents: OBR,
                            DV/Payroll, LOS,
                            and Cheque. Maximum 10MB per file.</p>
                    </div>
                </div>

                <!-- OBR Document -->
                <div class="ios-section">
                    <p class="ios-section-label">OBR — Obligation Request</p>
                    <div class="ios-card px-4 py-3.5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <AppIcon name="file-pdf" :size="18" class="text-red-600" />
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">OBR</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input ref="fileInputs.obr" type="file" accept=".pdf,.doc,.docx"
                                    @change="(e) => handleFileSelect('obr', e)" class="hidden" />
                                <AppButton icon="folder-open" @click="$refs['fileInputs.obr'][0]?.click()"
                                    severity="info" text size="small" v-tooltip="'Select File'" />
                                <AppButton icon="qrcode" @click="showQrCode(selectedVoucherForUpload, 'obr')"
                                    severity="info" text size="small" v-tooltip="'QR Code for OBR'" />
                                <Badge
                                    v-if="uploadedFiles.obr || voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                    value="✓" severity="success" />
                            </div>
                        </div>
                        <div v-if="uploadedFiles.obr"
                            class="mb-3 flex items-center justify-between bg-white dark:bg-[#272f38] p-2 rounded border border-gray-200 dark:border-white/10">
                            <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{
                                uploadedFiles.obr.name }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <AppButton
                                v-if="uploadedFiles.obr || voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                @click="uploadFile('obr')" icon="cloud-upload" severity="info" text
                                :loading="uploadingFile === 'obr'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                @click="previewDocument('obr')" icon="eye" severity="warning" text
                                v-tooltip="'Preview'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                @click="downloadDocument('obr')" icon="download" severity="success" text />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                @click="removeDocument('obr')" icon="trash" severity="danger" text />
                        </div>
                    </div>
                </div>

                <!-- DV/Payroll Document -->
                <div class="ios-section">
                    <p class="ios-section-label">DV/Payroll — Disbursement Voucher or Payroll</p>
                    <div class="ios-card px-4 py-3.5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <AppIcon name="file-pdf" :size="18" class="text-red-600" />
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">DV/Payroll</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input ref="fileInputs.dv_payroll" type="file" accept=".pdf,.doc,.docx"
                                    @change="(e) => handleFileSelect('dv_payroll', e)" class="hidden" />
                                <AppButton icon="folder-open" @click="$refs['fileInputs.dv_payroll'][0]?.click()"
                                    severity="info" text size="small" v-tooltip="'Select File'" />
                                <AppButton icon="qrcode" @click="showQrCode(selectedVoucherForUpload, 'dv_payroll')"
                                    severity="info" text size="small" v-tooltip="'QR Code for DV/Payroll'" />
                                <Badge
                                    v-if="uploadedFiles.dv_payroll || voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                    value="✓" severity="success" />
                            </div>
                        </div>
                        <div v-if="uploadedFiles.dv_payroll"
                            class="mb-3 flex items-center justify-between bg-white dark:bg-[#272f38] p-2 rounded border border-gray-200 dark:border-white/10">
                            <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{
                                uploadedFiles.dv_payroll.name }}</p>
                        </div>
                        <div class="flex gap-2">
                            <AppButton
                                v-if="uploadedFiles.dv_payroll || voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                @click="uploadFile('dv_payroll')" icon="cloud-upload" severity="info" text
                                :loading="uploadingFile === 'dv_payroll'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                @click="previewDocument('dv_payroll')" icon="eye" severity="warning" text
                                v-tooltip="'Preview'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                @click="downloadDocument('dv_payroll')" icon="download" severity="success" text />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                @click="removeDocument('dv_payroll')" icon="trash" severity="danger" text />
                        </div>
                    </div>
                </div>

                <!-- LOS Document -->
                <div class="ios-section">
                    <p class="ios-section-label">LOS — List of Scholars</p>
                    <div class="ios-card px-4 py-3.5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <AppIcon name="file-pdf" :size="18" class="text-red-600" />
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">List of
                                    Scholars</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input ref="fileInputs.los" type="file" accept=".pdf,.doc,.docx"
                                    @change="(e) => handleFileSelect('los', e)" class="hidden" />
                                <AppButton icon="folder-open" @click="$refs['fileInputs.los'][0]?.click()"
                                    severity="info" text size="small" v-tooltip="'Select File'" />
                                <AppButton icon="qrcode" @click="showQrCode(selectedVoucherForUpload, 'los')"
                                    severity="info" text size="small" v-tooltip="'QR Code for LOS'" />
                                <Badge
                                    v-if="uploadedFiles.los || voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                    value="✓" severity="success" />
                            </div>
                        </div>
                        <div v-if="uploadedFiles.los"
                            class="mb-3 flex items-center justify-between bg-white dark:bg-[#272f38] p-2 rounded border border-gray-200 dark:border-white/10">
                            <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{
                                uploadedFiles.los.name }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <AppButton
                                v-if="uploadedFiles.los || voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                @click="uploadFile('los')" icon="cloud-upload" severity="info" text
                                :loading="uploadingFile === 'los'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                @click="previewDocument('los')" icon="eye" severity="warning" text
                                v-tooltip="'Preview'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                @click="downloadDocument('los')" icon="download" severity="success" text />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                @click="removeDocument('los')" icon="trash" severity="danger" text />
                        </div>
                    </div>
                </div>

                <!-- Cheque Document -->
                <div class="ios-section mb-4">
                    <p class="ios-section-label">Cheques — Cheque Copy or Payment Proof</p>
                    <div class="ios-card px-4 py-3.5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <AppIcon name="file-pdf" :size="18" class="text-red-600" />
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cheques</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input ref="fileInputs.cheque" type="file" accept=".pdf,.doc,.docx"
                                    @change="(e) => handleFileSelect('cheque', e)" class="hidden" />
                                <AppButton icon="folder-open" @click="$refs['fileInputs.cheque'][0]?.click()"
                                    severity="info" text size="small" v-tooltip="'Select File'" />
                                <AppButton icon="qrcode" @click="showQrCode(selectedVoucherForUpload, 'cheque')"
                                    severity="info" text size="small" v-tooltip="'QR Code for Cheque'" />
                                <Badge
                                    v-if="uploadedFiles.cheque || voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                    value="✓" severity="success" />
                            </div>
                        </div>
                        <div v-if="uploadedFiles.cheque"
                            class="mb-3 flex items-center justify-between bg-white dark:bg-[#272f38] p-2 rounded border border-gray-200 dark:border-white/10">
                            <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{
                                uploadedFiles.cheque.name }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <AppButton
                                v-if="uploadedFiles.cheque || voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                @click="uploadFile('cheque')" icon="cloud-upload" severity="info" text
                                :loading="uploadingFile === 'cheque'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                @click="previewDocument('cheque')" icon="eye" severity="warning" text
                                v-tooltip="'Preview'" />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                @click="downloadDocument('cheque')" icon="download" severity="success" text />
                            <AppButton v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                @click="removeDocument('cheque')" icon="trash" severity="danger" text />
                        </div>
                    </div>
                </div>
            </div>
        </FileUploadModal>

        <!-- QR Code Modal -->
        <QrCodeModal :show="showQrModal" @update:show="showQrModal = $event" :model-value="qrCodeData"
            :countdown="qrCountdown" />

        <!-- Remarks Dialog -->
        <RemarksModal :show="showRemarksDialog" @update:show="showRemarksDialog = $event"
            :model-value="selectedVoucherForRemarks" :is-saving="savingRemarks"
            @save="(val) => { remarksForm.remarks = val; saveRemarks(); }" />

        <!-- Transaction Status Dialog -->
        <StatusModal :show="showStatusDialog" @update:show="showStatusDialog = $event"
            :model-value="selectedVoucherForStatus" :status-options="obrStatuses" :is-saving="savingStatus"
            @save="(data) => { statusForm.obr_status = data.status; statusForm.remarks = data.remarks; saveStatus(); }" />

        <!-- PDF Preview Modal -->
        <PdfPreviewModal :show="showPdfPreview" @update:show="showPdfPreview = $event" :html-doc="pdfPreviewHtml"
            :title="pdfPreviewTitle" :paper-size="pdfPreviewSize" />

        <!-- OBR Tracking Dialog -->
        <ObrTrackingModal :show="showOBRTrackingDialog" @update:show="showOBRTrackingDialog = $event"
            :model-value="selectedVoucherForOBRTracking" :is-saving="updatingOBRTracking"
            :is-complete="!!obrTrackingResult"
            @save="(data) => { obrTrackingForm.fiscal_year = data.fiscal_year; obrTrackingForm.obr_no = data.obr_no; obrTrackingForm.date_obligated = data.date_obligated; saveOBRTracking(); }" />

        <!-- Tracking History Dialog -->
        <TrackingHistoryModal :show="showTrackingHistoryDialog" @update:show="showTrackingHistoryDialog = $event"
            :tracking-data="trackingHistoryData" />

        <!-- Document Preview Modal -->
        <Drawer v-model:visible="showPreviewModal" position="right" class="!w-[800px]" :modal="true" :pt="ftDrawerPt">
            <template #header>
                <div class="flex items-center justify-between w-full pr-2">
                    <span class="font-semibold text-gray-900 dark:text-gray-100 text-base">Document Preview</span>
                    <a v-if="previewData" :href="previewData.url" :download="previewData.filename"
                        class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium no-underline">
                        <AppIcon name="download" :size="13" />Download
                    </a>
                </div>
            </template>
            <div v-if="previewData" class="pt-2">
                <!-- File Info -->
                <div class="ios-section">
                    <div class="ios-card">
                        <div class="ios-row">
                            <span class="ios-row-label">Filename</span>
                            <span class="text-[13px] break-all text-right">{{
                                previewData.filename }}</span>
                        </div>
                        <div class="ios-row [border-bottom:none]">
                            <span class="ios-row-label">Type</span>
                            <span class="text-[13px]">{{ previewData.docType }} | {{
                                previewData.mimeType }}</span>
                        </div>
                    </div>
                </div>

                <!-- Zoom Controls for Images -->
                <div v-if="previewData.mimeType && previewData.mimeType.startsWith('image/')" class="ios-section">
                    <p class="ios-section-label">Zoom</p>
                    <div class="ios-card px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <AppButton icon="minus" @click="previewZoom -= 10" :disabled="previewZoom <= 50" text
                                size="small" />
                            <span class="text-[13px] font-medium w-12 text-center">{{
                                previewZoom }}%</span>
                            <AppButton icon="plus" @click="previewZoom += 10" :disabled="previewZoom >= 200" text
                                size="small" />
                            <AppButton icon="home" @click="previewZoom = 100" text size="small"
                                v-tooltip="'Reset Zoom'" />
                        </div>
                    </div>
                </div>

                <!-- Image Preview -->
                <div v-if="previewData.mimeType && previewData.mimeType.startsWith('image/')" class="ios-section mb-4">
                    <div class="ios-card p-4 overflow-auto">
                        <div class="flex justify-center">
                            <img :src="previewData.path" :alt="previewData.filename"
                                class="rounded-lg border border-[#e5e5ea] dark:border-white/10"
                                :style="{ width: previewZoom + '%', maxWidth: 'none' }" @error="() => {
                                    console.error('Failed to load image:', previewData.path);
                                    toast.add({
                                        severity: 'warn',
                                        summary: 'Image Load Error',
                                        detail: 'Could not display image. Try downloading instead.',
                                        life: 3000
                                    });
                                }">
                        </div>
                    </div>
                </div>

                <!-- PDF Preview -->
                <div v-else-if="previewData.mimeType && previewData.mimeType.includes('pdf')" class="ios-section mb-4">
                    <div class="ios-card p-4">
                        <p class="text-[13px] text-[#8E8E93] mb-3">PDFs open in a new
                            window for best viewing experience</p>
                        <AppButton label="Open PDF in Viewer" @click="() => window.open(previewData.url, '_blank')"
                            icon="external-link" class="w-full" severity="info" />
                        <p class="text-xs text-[#8E8E93] mt-2">Or use the Download
                            button above to save to your device</p>
                    </div>
                </div>

                <!-- Other File Types -->
                <div v-else class="ios-section mb-4">
                    <div class="ios-card px-4 py-8 text-center">
                        <AppIcon name="file" :size="48" class="text-[#8E8E93] block mb-3" />
                        <p class="text-sm text-[#3c3c43] dark:text-gray-300 mb-1.5">Preview not
                            available for this file type</p>
                        <p class="text-xs text-[#8E8E93] mb-4">{{
                            previewData.mimeType || 'File type unknown' }}</p>
                        <AppButton label="Download File" @click="() => window.open(previewData.url, '_blank')"
                            icon="download" class="w-full" />
                    </div>
                </div>
            </div>
        </Drawer>

    </AdminLayout>
</template>

<style scoped>
/* Rounded inputs, selects, and datepickers to match macOS layout */
:deep(.p-inputtext) {
    border-radius: 1rem;
}

:deep(.p-select) {
    border-radius: 1rem;
}

/* Rounded DataTable — covers scrollable wrapper and all inner containers */
:deep(.p-datatable) {
    border-radius: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color, #e2e8f0);
}

:deep(.p-datatable .p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

/* Remove outer-edge cell borders so they don't double up with the container border */
:deep(.p-datatable .p-datatable-thead > tr > th:first-child),
:deep(.p-datatable .p-datatable-tbody > tr > td:first-child) {
    border-left: none;
}

:deep(.p-datatable .p-datatable-thead > tr > th:last-child),
:deep(.p-datatable .p-datatable-tbody > tr > td:last-child) {
    border-right: none;
}

:deep(.p-datatable .p-datatable-thead > tr:first-child > th) {
    border-top: none;
}

:deep(.p-datatable .p-datatable-tbody > tr:last-child > td) {
    border-bottom: none;
}

:deep(.p-datatable .p-paginator) {
    border-radius: 0;
    border: none;
    border-top: 1px solid var(--p-datatable-border-color, #e2e8f0);
}

:deep(.p-datatable .p-datatable-header) {
    border-radius: 0;
}

:deep(.p-datatable .p-datatable-footer) {
    border-radius: 0;
}

/* Rounded IconField search wrapper */
:deep(.p-iconfield .p-inputtext) {
    border-radius: 1rem;
}

/* Extra cell padding for better readability */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.85rem 1rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.85rem 1rem;
}

/* Remove highlight border on right-click context menu selection */
:deep(.p-datatable .p-datatable-tbody > tr.p-datatable-contextmenu-row-selected > td) {
    outline: none;
    box-shadow: none;
    border-color: inherit;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-datatable-contextmenu-row-selected) {
    outline: none;
    box-shadow: none;
}
</style>

<style>
/* Disable PrimeVue built-in drawer enter/leave for this custom floating drawer */
.ft-floating-drawer.p-drawer-enter-active,
.ft-floating-drawer.p-drawer-leave-active {
    transition: none !important;
}

.ft-floating-drawer.p-drawer-enter-from,
.ft-floating-drawer.p-drawer-leave-to {
    transform: none !important;
    opacity: 1 !important;
}

.ft-floating-drawer.p-drawer {
    border-radius: 2rem !important;
    height: calc(100vh - 2rem) !important;
    margin: 1rem !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    animation: none !important;
    transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

.ft-floating-drawer .p-drawer-header {
    border-radius: 2rem 2rem 0 0;
}

.ft-floating-drawer .p-drawer-content {
    border-radius: 0 0 2rem 2rem;
}

.ft-floating-drawer-mask {
    background: transparent !important;
    animation: none !important;
    transition: none !important;
}

/* Ensure mask animation cannot conflict */
.ft-floating-drawer-mask.p-overlay-enter-active,
.ft-floating-drawer-mask.p-overlay-leave-active {
    transition: none !important;
    animation: none !important;
}

/* Keep open smooth, make close instant */
.ft-floating-drawer.p-drawer-leave-active {
    transition: none !important;
}
</style>
