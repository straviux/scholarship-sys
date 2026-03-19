<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoucherWizard from '@/Components/Obligations/VoucherWizard.vue';
import axios from 'axios';

const toast = useToast();

const page = usePage();
const showWizard = ref(false);
const currentStep = ref(1);
const voucherType = ref('obligations');
const selectedScholars = ref([]);
const vouchers = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const showDeleteConfirmDialog = ref(false);
const voucherToDelete = ref(null);
const searchQuery = ref('');
const showViewDialog = ref(false);
const selectedVoucher = ref(null);
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
const obrStatuses = ['No OBR', 'LOA', 'Irregular', 'Transferred', 'Claimed', 'Paid', 'On Process', 'Denied'];
const showOBRTrackingDialog = ref(false);
const selectedVoucherForOBRTracking = ref(null);
const statusFilter = ref('');  // Status filter
const userFilter = ref('');  // User filter - '' for all, 'my-records' for current user
const obrTrackingForm = reactive({
    fiscal_year: new Date().getFullYear(),
    obr_no: '',
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

    console.log('Preview document:', doc);

    const downloadUrl = doc.download_url || `/api/fund-transactions/${selectedVoucherForUpload.value.id}/document/${docType}/download`;

    previewData.value = {
        docType: docType,
        filename: doc.filename,
        mimeType: doc.mime_type,
        url: downloadUrl,
        path: downloadUrl // For images and PDFs, use the download URL
    };

    previewZoom.value = 100; // Reset zoom when opening new preview
    console.log('Preview data:', previewData.value);
    showPreviewModal.value = true;
};

// Show QR code for mobile upload
const showQrCode = async (voucher, docType = null) => {
    try {
        console.log('Generating QR code for voucher:', voucher, 'docType:', docType);
        const response = await axios.post(`/api/fund-transactions/${voucher.id}/generate-qr`, {
            doc_type: docType
        });
        console.log('QR Code Response:', response.data);
        console.log('QR Code SVG Type:', typeof response.data.qr_code_svg);
        console.log('QR Code SVG Value:', response.data.qr_code_svg);

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
        console.log('QR Code Data Set:', qrCodeData.value);
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
        const response = await axios.get('/api/fund-transactions');
        vouchers.value = response.data.data || [];

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

// Computed stats
const totalVouchers = computed(() => vouchers.value.length);
const filteredVouchers = computed(() => {
    let filtered = vouchers.value;

    // Filter by creator if "My Records" selected
    if (userFilter.value === 'my-records') {
        const currentUserId = page.props.auth?.user?.id;
        if (currentUserId) {
            filtered = filtered.filter(v => v.creator?.id === currentUserId);
        }
    }

    // Filter by status if selected
    if (statusFilter.value) {
        filtered = filtered.filter(v => v.obr_status === statusFilter.value);
    }

    // Filter by search query
    if (!searchQuery.value.trim()) {
        return filtered;
    }

    const search = searchQuery.value.toLowerCase();
    return filtered.filter(v => {
        // Check standard fields
        const standardMatch =
            v.voucher_number?.toLowerCase().includes(search) ||
            v.payee_name?.toLowerCase().includes(search) ||
            v.voucher_type?.toLowerCase().includes(search) ||
            v.creator?.name?.toLowerCase().includes(search);

        // If standard field matches, return true
        if (standardMatch) return true;

        // Check if search matches any scholar names from the cached details
        if (v.scholar_ids && v.scholar_ids.length > 0) {
            // Look through our cached scholar details
            for (const scholarId of v.scholar_ids) {
                const scholarData = scholarsCache.value.get(scholarId);
                if (scholarData) {
                    const fullName = `${scholarData.first_name || ''} ${scholarData.last_name || ''}`.toLowerCase();
                    if (fullName.includes(search)) {
                        return true;
                    }
                }
            }
        }

        return false;
    });
});
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Roles is an array of strings like ['administrator']
    return user.roles?.includes('administrator') ?? false;
});

// Computed property for status counts
const statusCounts = computed(() => {
    return {
        'On Process': vouchers.value.filter(v => v.obr_status === 'On Process').length,
        'No OBR': vouchers.value.filter(v => !v.obr_status || v.obr_status === '').length,
        'LOA': vouchers.value.filter(v => v.obr_status === 'LOA').length,
        'Irregular': vouchers.value.filter(v => v.obr_status === 'Irregular').length,
        'Transferred': vouchers.value.filter(v => v.obr_status === 'Transferred').length,
        'Claimed': vouchers.value.filter(v => v.obr_status === 'Claimed').length,
        'Paid': vouchers.value.filter(v => v.obr_status === 'Paid').length,
        'Denied': vouchers.value.filter(v => v.obr_status === 'Denied').length
    };
});

// Computed property for user record counts
const myRecordsCount = computed(() => {
    const userId = page.props.auth?.user?.id;
    return userId ? vouchers.value.filter(v => v.creator_id === userId).length : 0;
});

const totalRecordsCount = computed(() => vouchers.value.length);

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

        console.log('Tracking history response:', response.data);

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
        showViewDialog.value = true;

        // Fetch scholar details
        await fetchScholarsDetails(voucher.scholar_ids || []);
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

    try {
        let url = '';
        let fileType = '';

        if (docType === 'OBR') {
            url = `/api/fund-transactions/${selectedVoucher.value.id}/obr-pdf`;
            fileType = 'pdf';
        } else if (docType === 'DV') {
            url = `/api/fund-transactions/${selectedVoucher.value.id}/dv-pdf`;
            fileType = 'pdf';
        } else if (docType === 'PR') {
            url = `/api/fund-transactions/${selectedVoucher.value.id}/payroll-pdf`;
            fileType = 'pdf';
        } else if (docType === 'LOS') {
            url = `/api/fund-transactions/${selectedVoucher.value.id}/list-of-scholars-pdf`;
            fileType = 'pdf';
        }

        // Show loading toast
        toast.add({
            severity: 'info',
            summary: 'Generating',
            detail: `${docType} document generation in progress...`,
            life: 2000
        });

        // Open the file in a new tab
        window.open(url, '_blank');

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${docType} document generated successfully`,
            life: 3000
        });
    } catch (error) {
        console.error(`Error generating ${docType}:`, error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to generate ${docType} document`,
            life: 5000
        });
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
            icon: 'pi pi-eye',
            command: () => viewVoucher(voucher.id)
        },
        {
            label: 'Edit',
            icon: 'pi pi-pencil',
            command: () => editVoucher(voucher.id)
        },
        {
            label: 'Upload Documents',
            icon: 'pi pi-upload',
            command: () => openFileUploadDialog(voucher)
        },
        {
            label: 'Add/Edit Remarks',
            icon: 'pi pi-comment',
            command: () => openRemarksModal(voucher)
        },
        {
            label: 'Change Status',
            icon: 'pi pi-sync',
            command: () => openStatusModal(voucher)
        },
        {
            label: 'Update OBR Info',
            icon: 'pi pi-pencil',
            command: () => openOBRTrackingDialog(voucher)
        },
        {
            label: 'View Tracking History',
            icon: 'pi pi-history',
            command: () => fetchTrackingHistory(voucher)
        }
    ];

    if (isAdmin.value) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Delete',
            icon: 'pi pi-trash',
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
            voucher_type: voucherData.voucher_type,
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
            notes: voucherData.notes,
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
        console.log('Saving status - Current form data:', {
            obr_status: statusForm.obr_status,
            remarks: statusForm.remarks
        });

        // Just send the status and remarks - minimal update
        const response = await axios.patch(
            `/api/fund-transactions/${selectedVoucherForStatus.value.id}/update-status`,
            {
                transaction_status: statusForm.obr_status,
                remarks: statusForm.remarks
            }
        );

        console.log('Status update response:', response.data);
        console.log('Response obr_status value:', response.data.data?.obr_status);
        console.log('Response remarks value:', response.data.data?.remarks);

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
        'No OBR': 'bg-gray-100 text-gray-800',
        'LOA': 'bg-blue-100 text-blue-800',
        'Irregular': 'bg-orange-100 text-orange-800',
        'Transferred': 'bg-purple-100 text-purple-800',
        'Claimed': 'bg-indigo-100 text-indigo-800',
        'Paid': 'bg-green-100 text-green-800',
        'On Process': 'bg-yellow-100 text-yellow-800',
        'Denied': 'bg-red-100 text-red-800'
    };
    return statusColors[status] || 'bg-gray-100 text-gray-800';
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
    return selectedVoucher.value.voucher_type === 'payroll' ? 'PR' : 'DV';
};

// Get document type to generate based on voucher type
const getDocumentType = () => {
    if (!selectedVoucher.value) return 'DV';
    return selectedVoucher.value.voucher_type === 'payroll' ? 'PR' : 'DV';
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
    obrTrackingForm.dv_no = voucher.dv_no || '';
    obrTrackingResult.value = null;

    // Debug: Log what was loaded from the database
    console.log('openOBRTrackingDialog - Loaded from voucher:', {
        voucher_id: voucher.id,
        fiscal_year: voucher.fiscal_year,
        obr_no: voucher.obr_no,
        dv_no: voucher.dv_no,
        fiscal_year_type: typeof voucher.fiscal_year,
        obr_no_type: typeof voucher.obr_no,
        dv_no_type: typeof voucher.dv_no
    });

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
                        voucher_type: voucherData.voucher_type,
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
                        notes: voucherData.notes,
                        remarks: voucherData.remarks,
                        transaction_status: statusToSend,
                        fiscal_year: parseInt(obrTrackingForm.fiscal_year) || null,
                        obr_no: obrTrackingForm.obr_no || null,
                        dv_no: obrTrackingForm.dv_no || null
                    });

                    // Update the voucher in the local list
                    const voucherIndex = vouchers.value.findIndex(v => v.id === selectedVoucherForOBRTracking.value.id);
                    if (voucherIndex > -1) {
                        vouchers.value[voucherIndex].fiscal_year = obrTrackingForm.fiscal_year;
                        vouchers.value[voucherIndex].obr_no = obrTrackingForm.obr_no;
                        vouchers.value[voucherIndex].dv_no = obrTrackingForm.dv_no;
                        vouchers.value[voucherIndex].obr_status = statusToSend;
                    }

                    // Also update selectedVoucher if it's the same voucher
                    if (selectedVoucher.value?.id === selectedVoucherForOBRTracking.value.id) {
                        selectedVoucher.value.fiscal_year = obrTrackingForm.fiscal_year;
                        selectedVoucher.value.obr_no = obrTrackingForm.obr_no;
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
                        <i class="pi pi-credit-card text-indigo-900" style="font-size: 2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Fund Transactions Management</h1>
                            <p class="text-sm text-gray-600">Create and manage financial transactions</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <Button icon="pi pi-plus" @click="handleCreateVoucher" severity="success" rounded outlined
                        v-tooltip.bottom="'Create Fund Transaction'" />
                </template>
            </Toolbar>


            <!-- List/Summary Section -->
            <Panel class="!rounded-4xl overflow-hidden mt-8">
                <!-- Info Bar -->
                <div class="flex items-center justify-between gap-4 mb-4 p-3 bg-gray-50 rounded-4xl -mt-2">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-gray-400" />
                            <InputText v-model="searchQuery" placeholder="Search voucher, payee, or scholar..."
                                class="w-full" size="small" />
                        </IconField>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm opacity-60">Right click on row for actions</span>
                        <Button icon="pi pi-refresh" severity="secondary" size="small" rounded outlined
                            @click="fetchVouchers" :disabled="loading" :loading="loading"
                            v-tooltip.bottom="'Refresh'" />
                    </div>
                </div>

                <!-- Filter Chips -->
                <div class="flex flex-wrap gap-3 items-center mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="userFilter" name="userFilter" value="" inputId="uf-all" />
                        <span class="text-sm text-gray-700">All Records</span>
                        <Badge :value="totalRecordsCount" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="userFilter" name="userFilter" value="my-records" inputId="uf-my" />
                        <span class="text-sm text-gray-700">My Records</span>
                        <Badge :value="myRecordsCount" severity="secondary" />
                    </label>
                    <span class="text-gray-300 mx-1">|</span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="" inputId="sf-all" />
                        <span class="text-sm text-gray-700">All Status</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="On Process"
                            inputId="sf-process" />
                        <span class="text-sm text-gray-700">On Process</span>
                        <Badge :value="statusCounts['On Process']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="No OBR" inputId="sf-noobr" />
                        <span class="text-sm text-gray-700">No OBR</span>
                        <Badge :value="statusCounts['No OBR']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="LOA" inputId="sf-loa" />
                        <span class="text-sm text-gray-700">LOA</span>
                        <Badge :value="statusCounts['LOA']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="Irregular"
                            inputId="sf-irregular" />
                        <span class="text-sm text-gray-700">Irregular</span>
                        <Badge :value="statusCounts['Irregular']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="Transferred"
                            inputId="sf-transferred" />
                        <span class="text-sm text-gray-700">Transferred</span>
                        <Badge :value="statusCounts['Transferred']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="Claimed" inputId="sf-claimed" />
                        <span class="text-sm text-gray-700">Claimed</span>
                        <Badge :value="statusCounts['Claimed']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="Paid" inputId="sf-paid" />
                        <span class="text-sm text-gray-700">Paid</span>
                        <Badge :value="statusCounts['Paid']" severity="secondary" />
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <RadioButton v-model="statusFilter" name="statusFilter" value="Denied" inputId="sf-denied" />
                        <span class="text-sm text-gray-700">Denied</span>
                        <Badge :value="statusCounts['Denied']" severity="secondary" />
                    </label>
                </div>

                <!-- Context Menu -->
                <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body" />

                <!-- DataTable -->
                <DataTable :value="filteredVouchers" stripedRows showGridlines responsiveLayout="scroll"
                    :loading="loading"
                    :emptyMessage="vouchers.length === 0 ? 'No vouchers created yet. Click Create Fund Transaction to get started.' : 'No vouchers match your search.'"
                    :scrollable="true" scrollHeight="600px" @row-contextmenu="onRowContextMenu" contextMenu
                    v-model:contextMenuSelection="selectedContextVoucher">

                    <Column header="OBR No" style="min-width: 140px">
                        <template #body="slotProps">
                            <span class="text-sm font-medium text-blue-600">{{ slotProps.data.obr_no || '---' }}</span>
                        </template>
                    </Column>

                    <Column header="Payee" style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="text-sm font-semibold text-gray-800">{{ slotProps.data.payee_name }}</div>
                            <div v-if="isPayeeSchool(slotProps.data)"
                                class="text-xs font-medium italic text-gray-600 mt-1">
                                {{ getFirstScholarNameFromCache(slotProps.data) || '---' }}
                            </div>
                        </template>
                    </Column>

                    <Column header="OBR Type" style="min-width: 130px">
                        <template #body="slotProps">
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-medium': true,
                                'text-gray-800': slotProps.data.obr_type === 'REGULAR',
                                'text-yellow-800': slotProps.data.obr_type === 'FINANCIAL ASSISTANCE',
                                'text-purple-800': slotProps.data.obr_type === 'REIMBURSEMENT'
                            }">
                                {{ slotProps.data.obr_type || '---' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Disbursement Type" style="min-width: 150px">
                        <template #body="slotProps">
                            <span class="text-xs font-medium uppercase">
                                {{ slotProps.data.voucher_type === 'disbursements' ? 'DV' :
                                    (slotProps.data.voucher_type === 'payroll' ? 'Payroll' : slotProps.data.voucher_type) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Status" style="min-width: 140px">
                        <template #body="slotProps">
                            <span
                                :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(slotProps.data.obr_status)]">
                                {{ slotProps.data.obr_status || 'On Process' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Total Amount" style="min-width: 130px">
                        <template #body="slotProps">
                            <span class="text-sm font-medium text-gray-900">{{
                                formatAmount(calculateTotalAmount(slotProps.data)) }}</span>
                        </template>
                    </Column>

                    <Column header="Processed By" style="min-width: 130px">
                        <template #body="slotProps">
                            <span class="text-xs font-semibold text-gray-600">{{ slotProps.data.creator?.name || '---'
                            }}</span>
                        </template>
                    </Column>

                    <Column header="Date" style="min-width: 110px">
                        <template #body="slotProps">
                            <span class="text-xs text-gray-600">{{ formatDate(slotProps.data.created_at) }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 70px">
                        <template #body="slotProps">
                            <Button icon="pi pi-ellipsis-v" @click="(e) => openContextMenu(e, slotProps.data)" text
                                rounded size="small" v-tooltip="'Actions'" />
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
        <Dialog v-model:visible="showDeleteConfirmDialog" modal header="Confirm Delete" class="w-11/12 sm:w-96"
            :style="{ maxWidth: '500px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Delete Voucher</p>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-3">
                    <p class="text-sm text-red-800">
                        <strong>Voucher #:</strong> {{vouchers.find(v => v.id === voucherToDelete)?.voucher_number ||
                            'N/A'}}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteConfirmDialog = false" outlined />
                <Button label="Delete" severity="danger" @click="confirmDelete"
                    :loading="deletingId === voucherToDelete" />
            </template>
        </Dialog>

        <!-- View Fund Transaction Dialog -->
        <Dialog v-model:visible="showViewDialog" modal header="Fund Transaction Details" class="w-11/12 sm:max-w-2xl"
            :style="{ maxWidth: '700px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="selectedVoucher" class="space-y-4">
                <!-- Fund Transaction Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">OBR Number</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.obr_no || '---' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Disbursement Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.voucher_type === 'disbursements'
                            ?
                            'Disbursement Voucher' : (selectedVoucher.voucher_type === 'payroll' ? 'Payroll' :
                                selectedVoucher.voucher_type) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Payee</p>
                        <div>
                            <p class="text-sm text-gray-900">{{ selectedVoucher.payee_name }}</p>
                            <p v-if="isPayeeSchool(selectedVoucher)" class="text-xs text-gray-600 mt-1">
                                Scholar: {{ getFirstScholarName(selectedVoucher) || '---' }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Amount</p>
                        <p class="text-sm text-gray-900 font-medium">{{ formatAmount(selectedVoucher.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Created By</p>
                        <p class="text-sm text-gray-900">{{ selectedVoucher.creator?.name || '---' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">Date</p>
                        <p class="text-sm text-gray-900">{{ formatDate(selectedVoucher.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">OBR Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ selectedVoucher.obr_type || '---' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase">OBR Status</p>
                        <span
                            :class="['px-3 py-1 rounded-full text-xs font-medium inline-block', getStatusColor(selectedVoucher.obr_status)]">
                            {{ selectedVoucher.obr_status || 'On Process' }}
                        </span>
                    </div>
                </div>

                <!-- Remarks -->
                <div v-if="selectedVoucher.remarks" class=" border rounded p-3">
                    <p class="text-xs font-semibolduppercase mb-2">Remarks</p>
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ selectedVoucher.remarks }}</p>
                </div>

                <!-- Scholars List -->
                <div class="bg-white border border-gray-200 rounded p-4">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Scholars ({{ selectedVoucher.scholar_ids?.length
                        || 0
                    }})</p>
                    <div v-if="loadingScholars" class="text-center py-2">
                        <i class="pi pi-spin pi-spinner mr-2 text-xs"></i> <span class="text-xs">Loading...</span>
                    </div>
                    <div v-else-if="scholarsDetails && scholarsDetails.length > 0"
                        class="space-y-1 max-h-48 overflow-y-auto">
                        <div v-for="(scholar, index) in scholarsDetails" :key="index"
                            class="text-xs text-gray-700 py-1 px-2 bg-gray-50 rounded flex items-center justify-between gap-2">
                            <span class="font-medium">{{ index + 1 }}. {{ scholar.first_name }} {{ scholar.last_name
                            }}</span>
                            <span class="text-gray-600 whitespace-nowrap">
                                <span v-if="scholar.course_name">{{ scholar.course_name }}</span>
                                <span v-if="scholar.year_level" class="ml-1">| {{
                                    /^(1st|2nd|3rd|4th)$/i.test(scholar.year_level) ? scholar.year_level + ' YEAR' :
                                        scholar.year_level
                                }}</span>
                                <span v-if="scholar.academic_year" class="ml-1">| {{ scholar.academic_year
                                }}</span>
                                <span v-if="scholar.term" class="ml-1">| {{ scholar.term }}</span>
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-500">No scholars</div>
                </div>

                <!-- Total Amount -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-900">Total Amount</p>
                    <p class="text-lg font-bold text-blue-600">{{ formatAmount(calculateTotalAmount(selectedVoucher)) }}
                    </p>
                </div>

                <!-- Generate Section -->
                <div class="border-t pt-4">
                    <p class="text-sm font-semibold text-gray-900 mb-3">Generate</p>
                    <div class="flex gap-2">
                        <Button label="OBR" @click="generateDocument('OBR')" class="flex-1" severity="info">
                            <template #icon>
                                <i class="pi pi-file-pdf"></i>
                            </template>
                        </Button>
                        <Button :label="getDocumentButtonLabel()" @click="generateDocument(getDocumentType())"
                            class="flex-1" severity="success">
                            <template #icon>
                                <i class="pi pi-money-bill"></i>
                            </template>
                        </Button>
                        <Button label="LOS" @click="generateDocument('LOS')" class="flex-1" severity="help">
                            <template #icon>
                                <i class="pi pi-users"></i>
                            </template>
                        </Button>
                    </div>
                </div>

                <!-- Tracking History Section -->
                <div v-if="selectedVoucher?.fiscal_year && selectedVoucher?.obr_no" class="border-t pt-4">
                    <p class="text-sm font-semibold text-gray-900 mb-3">Tracking</p>
                    <Button label="View Tracking History" @click="fetchTrackingHistory(selectedVoucher)" class="w-full"
                        severity="info" :loading="loadingTrackingHistory">
                        <template #icon>
                            <i class="pi pi-history"></i>
                        </template>
                    </Button>
                </div>
                <div v-else-if="selectedVoucher" class="border-t pt-4 text-xs text-gray-500">
                    <p>No OBR info available</p>
                </div>

                <!-- File Upload Section -->
                <div class="border-t pt-4">
                    <p class="text-sm font-semibold text-gray-900 mb-3">Documents</p>
                    <Button label="Upload Documents" @click="openFileUploadDialog(selectedVoucher)" class="w-full"
                        severity="warning">
                        <template #icon>
                            <i class="pi pi-upload"></i>
                        </template>
                    </Button>
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showViewDialog = false" outlined />
            </template>
        </Dialog>

        <!-- File Upload Dialog -->
        <Dialog v-model:visible="showFileUploadDialog" modal header="Upload Documents" class="w-11/12 sm:max-w-2xl"
            :style="{ maxWidth: '700px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="selectedVoucherForUpload" class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
                    <p class="text-xs font-medium text-blue-900 mb-1">VOUCHER</p>
                    <p class="text-sm font-semibold text-blue-900">{{ selectedVoucherForUpload.voucher_number }}</p>
                    <p class="text-xs text-blue-800 mt-1">{{ selectedVoucherForUpload.payee_name }}</p>
                </div>

                <p class="text-sm text-gray-600">Upload up to four documents: OBR, DV/Payroll, LOS, and Cheque.
                    Maximum 10MB per file.</p>

                <!-- OBR Document -->
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-file-pdf text-red-600 text-lg"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">OBR</p>
                                <p class="text-xs text-gray-600">Obligation Request</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input ref="fileInputs.obr" type="file" accept=".pdf,.doc,.docx"
                                @change="(e) => handleFileSelect('obr', e)" style="display: none;" />
                            <Button icon="pi pi-folder-open" @click="$refs['fileInputs.obr'][0]?.click()"
                                severity="info" text size="small" v-tooltip="'Select File'" />
                            <Button icon="pi pi-qrcode" @click="showQrCode(selectedVoucherForUpload, 'obr')"
                                severity="info" text size="small" v-tooltip="'QR Code for OBR'" />
                            <Badge v-if="uploadedFiles.obr || voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                                value="✓" severity="success" />
                        </div>
                    </div>
                    <div v-if="uploadedFiles.obr"
                        class="mb-3 flex items-center justify-between bg-white p-2 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 flex-1 truncate">{{ uploadedFiles.obr.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="uploadedFiles.obr || voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                            @click="uploadFile('obr')" icon="pi pi-cloud-upload" severity="info" text
                            :loading="uploadingFile === 'obr'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                            @click="previewDocument('obr')" icon="pi pi-eye" severity="warning" text
                            v-tooltip="'Preview'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                            @click="downloadDocument('obr')" icon="pi pi-download" severity="success" text />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.obr"
                            @click="removeDocument('obr')" icon="pi pi-trash" severity="danger" text />
                    </div>
                </div>

                <!-- DV/Payroll Document -->
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-file-pdf text-red-600 text-lg"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">DV/Payroll</p>
                                <p class="text-xs text-gray-600">Disbursement Voucher or Payroll</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input ref="fileInputs.dv_payroll" type="file" accept=".pdf,.doc,.docx"
                                @change="(e) => handleFileSelect('dv_payroll', e)" style="display: none;" />
                            <Button icon="pi pi-folder-open" @click="$refs['fileInputs.dv_payroll'][0]?.click()"
                                severity="info" text size="small" v-tooltip="'Select File'" />
                            <Button icon="pi pi-qrcode" @click="showQrCode(selectedVoucherForUpload, 'dv_payroll')"
                                severity="info" text size="small" v-tooltip="'QR Code for DV/Payroll'" />
                            <Badge
                                v-if="uploadedFiles.dv_payroll || voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                                value="✓" severity="success" />
                        </div>
                    </div>
                    <div v-if="uploadedFiles.dv_payroll"
                        class="mb-3 flex items-center justify-between bg-white p-2 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 flex-1 truncate">{{ uploadedFiles.dv_payroll.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="uploadedFiles.dv_payroll || voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                            @click="uploadFile('dv_payroll')" icon="pi pi-cloud-upload" severity="info" text
                            :loading="uploadingFile === 'dv_payroll'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                            @click="previewDocument('dv_payroll')" icon="pi pi-eye" severity="warning" text
                            v-tooltip="'Preview'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                            @click="downloadDocument('dv_payroll')" icon="pi pi-download" severity="success" text />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.dv_payroll"
                            @click="removeDocument('dv_payroll')" icon="pi pi-trash" severity="danger" text />
                    </div>
                </div>

                <!-- LOS Document -->
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-file-pdf text-red-600 text-lg"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">List of Scholars</p>
                                <p class="text-xs text-gray-600">List of Scholars</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input ref="fileInputs.los" type="file" accept=".pdf,.doc,.docx"
                                @change="(e) => handleFileSelect('los', e)" style="display: none;" />
                            <Button icon="pi pi-folder-open" @click="$refs['fileInputs.los'][0]?.click()"
                                severity="info" text size="small" v-tooltip="'Select File'" />
                            <Button icon="pi pi-qrcode" @click="showQrCode(selectedVoucherForUpload, 'los')"
                                severity="info" text size="small" v-tooltip="'QR Code for LOS'" />
                            <Badge v-if="uploadedFiles.los || voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                                value="✓" severity="success" />
                        </div>
                    </div>
                    <div v-if="uploadedFiles.los"
                        class="mb-3 flex items-center justify-between bg-white p-2 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 flex-1 truncate">{{ uploadedFiles.los.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="uploadedFiles.los || voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                            @click="uploadFile('los')" icon="pi pi-cloud-upload" severity="info" text
                            :loading="uploadingFile === 'los'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                            @click="previewDocument('los')" icon="pi pi-eye" severity="warning" text
                            v-tooltip="'Preview'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                            @click="downloadDocument('los')" icon="pi pi-download" severity="success" text />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.los"
                            @click="removeDocument('los')" icon="pi pi-trash" severity="danger" text />
                    </div>
                </div>

                <!-- Cheque Document -->
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-file-pdf text-red-600 text-lg"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Cheques</p>
                                <p class="text-xs text-gray-600">Cheque Copy or Payment Proof</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input ref="fileInputs.cheque" type="file" accept=".pdf,.doc,.docx"
                                @change="(e) => handleFileSelect('cheque', e)" style="display: none;" />
                            <Button icon="pi pi-folder-open" @click="$refs['fileInputs.cheque'][0]?.click()"
                                severity="info" text size="small" v-tooltip="'Select File'" />
                            <Button icon="pi pi-qrcode" @click="showQrCode(selectedVoucherForUpload, 'cheque')"
                                severity="info" text size="small" v-tooltip="'QR Code for Cheque'" />
                            <Badge
                                v-if="uploadedFiles.cheque || voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                                value="✓" severity="success" />
                        </div>
                    </div>
                    <div v-if="uploadedFiles.cheque"
                        class="mb-3 flex items-center justify-between bg-white p-2 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 flex-1 truncate">{{ uploadedFiles.cheque.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="uploadedFiles.cheque || voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                            @click="uploadFile('cheque')" icon="pi pi-cloud-upload" severity="info" text
                            :loading="uploadingFile === 'cheque'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                            @click="previewDocument('cheque')" icon="pi pi-eye" severity="warning" text
                            v-tooltip="'Preview'" />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                            @click="downloadDocument('cheque')" icon="pi pi-download" severity="success" text />
                        <Button v-if="voucherDocuments.get(selectedVoucherForUpload.id)?.cheque"
                            @click="removeDocument('cheque')" icon="pi pi-trash" severity="danger" text />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showFileUploadDialog = false" outlined />
            </template>
        </Dialog>

        <!-- QR Code Modal -->
        <Dialog v-model:visible="showQrModal" modal header="Mobile Upload QR Code" class="w-11/12 sm:max-w-2xl"
            :style="{ maxWidth: '600px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="qrCodeData" class="text-center space-y-4">
                <!-- QR Code -->
                <div class="bg-white p-6 rounded-lg border-2 border-gray-200 inline-block">
                    <div v-if="qrCodeData.qrCode" v-html="qrCodeData.qrCode"></div>
                    <div v-else class="text-gray-500 py-8">
                        <i class="pi pi-exclamation-triangle text-2xl"></i>
                        <p class="text-sm mt-2">QR code could not be generated</p>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="text-left space-y-3">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            <i class="pi pi-info-circle mr-2"></i>How to use:
                        </p>
                        <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                            <li>Scan this QR code with your mobile device</li>
                            <li>Select document type (OBR, DV/Payroll, LOS, or Cheque)</li>
                            <li>Take a photo or select from gallery</li>
                            <li>Upload the document</li>
                            <li>Document appears in the system automatically</li>
                        </ol>
                    </div>

                    <!-- Expiration Notice -->
                    <div class="bg-amber-50 border border-amber-200 rounded p-3">
                        <p class="text-xs text-amber-800">
                            <i class="pi pi-info-circle mr-1"></i>
                            <span class="font-semibold">Expires in: {{ qrCountdown }}</span>
                        </p>
                    </div>

                    <!-- Copy URL -->
                    <div class="flex gap-2">
                        <InputText type="text" :value="qrCodeData.url" readonly class="flex-1" />
                        <Button icon="pi pi-copy" size="small" @click="copyToClipboard(qrCodeData.url)"
                            v-tooltip="'Copy upload link'" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showQrModal = false" outlined />
            </template>
        </Dialog>

        <!-- Remarks Dialog -->
        <Dialog v-model:visible="showRemarksDialog" modal header="Add/Edit Remarks" class="w-11/12 sm:max-w-xl"
            :style="{ maxWidth: '600px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="selectedVoucherForRemarks" class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Voucher: {{
                        selectedVoucherForRemarks.voucher_number }}
                    </p>
                    <p class="text-xs text-gray-500">Add or edit remarks for this voucher</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Remarks</label>
                    <Editor v-model="remarksForm.remarks" editorStyle="height: 150px">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showRemarksDialog = false" outlined />
                <Button label="Save" severity="success" @click="saveRemarks" :loading="savingRemarks" />
            </template>
        </Dialog>

        <!-- Transaction Status Dialog -->
        <Dialog v-model:visible="showStatusDialog" modal header="Update Transaction Status" class="w-11/12 sm:w-96"
            :style="{ maxWidth: '500px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="selectedVoucherForStatus" class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Voucher: {{
                        selectedVoucherForStatus.voucher_number }}
                    </p>
                    <p class="text-xs text-gray-500">Change the transaction status for this voucher</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">OBR Status</label>
                    <Dropdown v-model="statusForm.obr_status" :options="obrStatuses" placeholder="Select a status"
                        class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Remarks (Optional)</label>
                    <Editor v-model="statusForm.remarks" editorStyle="height: 120px">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showStatusDialog = false" outlined />
                <Button label="Update" severity="success" @click="saveStatus" :loading="savingStatus" />
            </template>
        </Dialog>

        <!-- OBR Tracking Dialog -->
        <Dialog v-model:visible="showOBRTrackingDialog" modal header="Update OBR Info" class="w-11/12 sm:max-w-xl"
            :style="{ maxWidth: '600px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div class="space-y-4">
                <div v-if="selectedVoucherForOBRTracking" class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
                    <p class="text-xs font-medium text-blue-900 mb-1">VOUCHER</p>
                    <p class="text-sm font-semibold text-blue-900">{{ selectedVoucherForOBRTracking.voucher_number }}
                    </p>
                    <p class="text-xs text-blue-800 mt-1">{{ selectedVoucherForOBRTracking.payee_name }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Enter OBR and DV details to update this voucher's tracking
                        information
                    </p>
                </div>

                <div v-if="!obrTrackingResult">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Fiscal Year *</label>
                        <InputText v-model.number="obrTrackingForm.fiscal_year" type="number" class="w-full"
                            placeholder="e.g., 2025" />
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">OBR Number *</label>
                        <InputText v-model="obrTrackingForm.obr_no" type="text" class="w-full"
                            placeholder="e.g., 200-25-12-24188" />
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-900 mb-2">DV Number (Optional)</label>
                        <InputText v-model="obrTrackingForm.dv_no" type="text" class="w-full"
                            placeholder="e.g., 25-12-23743" />
                        <p class="text-xs text-gray-500 mt-1">If not provided, it will be auto-fetched from the OBR</p>
                    </div>
                </div>

                <div v-if="obrTrackingResult" class="bg-green-50 border border-green-200 rounded p-4">
                    <p class="text-sm font-semibold text-green-900 mb-2">✓ Saved Successfully</p>
                    <div class="text-xs text-green-800 space-y-1">
                        <p><strong>Fiscal Year:</strong> {{ obrTrackingForm.fiscal_year }}</p>
                        <p><strong>OBR Number:</strong> {{ obrTrackingForm.obr_no }}</p>
                        <p v-if="obrTrackingForm.dv_no"><strong>DV Number:</strong> {{ obrTrackingForm.dv_no }}</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button v-if="!obrTrackingResult" label="Cancel" severity="secondary"
                    @click="showOBRTrackingDialog = false" outlined />
                <Button v-if="obrTrackingResult" label="Close" severity="secondary"
                    @click="showOBRTrackingDialog = false" outlined />
                <Button v-if="!obrTrackingResult" label="Save" severity="success" @click="saveOBRTracking"
                    :loading="updatingOBRTracking" />
            </template>
        </Dialog>

        <!-- Tracking History Dialog -->
        <Dialog v-model:visible="showTrackingHistoryDialog" modal header="Tracking Timeline"
            class="w-11/12 sm:max-w-2xl" :style="{ maxWidth: '720px' }"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="trackingHistoryData" class="space-y-0">
                <!-- Tracking Timeline -->
                <div v-if="trackingHistoryData.tracking_information && trackingHistoryData.tracking_information.length > 0"
                    class="space-y-0 max-h-[80vh] overflow-y-auto">
                    <div v-for="(entry, index) in trackingHistoryData.tracking_information" :key="index"
                        class="relative pl-10 py-3 border-l-2 border-blue-300">

                        <!-- Status icon - checkmark for all entries -->
                        <div
                            class="absolute top-3 left-4 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white">
                            <i class="pi pi-check text-xs font-bold"></i>
                        </div>

                        <div class="flex items-start justify-between gap-2 pl-2">
                            <p class="text-sm text-gray-800">{{ entry.trn_remarks || entry.transaction_description ||
                                entry.description }}</p>
                            <span class="text-xs text-gray-500 whitespace-nowrap flex-shrink-0">
                                {{ formatDate(entry.trn_date || entry.transaction_date || entry.date) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- No Timeline Message -->
                <div v-else class="text-center py-8 text-gray-500">
                    <p class="text-sm">No tracking information</p>
                </div>
            </div>

            <div v-else class="text-center py-8">
                <i class="pi pi-spin pi-spinner text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-600">Loading...</p>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" @click="showTrackingHistoryDialog = false" />
            </template>
        </Dialog>

        <!-- Document Preview Modal -->
        <Dialog v-model:visible="showPreviewModal" modal header="Document Preview" class="w-11/12 sm:max-w-4xl"
            :style="{ maxWidth: '1000px', maxHeight: '90vh' }" :maximizable="true"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <div v-if="previewData" class="space-y-4 h-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ previewData.filename }}</p>
                        <p class="text-xs text-gray-600 mt-1">Document Type: {{ previewData.docType }} | {{
                            previewData.mimeType
                        }}</p>
                    </div>
                    <div class="flex gap-2">
                        <!-- Zoom Controls for Images -->
                        <div v-if="previewData.mimeType && previewData.mimeType.startsWith('image/')"
                            class="flex items-center gap-2 bg-gray-100 rounded px-2 py-1">
                            <Button icon="pi pi-minus" @click="previewZoom -= 10" :disabled="previewZoom <= 50" text
                                size="small" />
                            <span class="text-xs font-medium w-12 text-center">{{ previewZoom }}%</span>
                            <Button icon="pi pi-plus" @click="previewZoom += 10" :disabled="previewZoom >= 200" text
                                size="small" />
                            <Button icon="pi pi-home" @click="previewZoom = 100" text size="small"
                                v-tooltip="'Reset Zoom'" />
                        </div>
                        <a :href="previewData.url" download :download="previewData.filename"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                            <i class="pi pi-download text-sm"></i>
                            <span>Download</span>
                        </a>
                    </div>
                </div>

                <!-- Image Preview with Scroll -->
                <div v-if="previewData.mimeType && previewData.mimeType.startsWith('image/')"
                    class="overflow-auto border border-gray-200 rounded-lg bg-gray-50 p-4"
                    style="max-height: calc(90vh - 200px)">
                    <div class="flex justify-center">
                        <img :src="previewData.path" :alt="previewData.filename"
                            class="rounded-lg border border-gray-200"
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

                <!-- PDF Preview -->
                <div v-else-if="previewData.mimeType && previewData.mimeType.includes('pdf')" class="space-y-2">
                    <p class="text-xs text-gray-600">PDFs open in a new window for best viewing experience</p>
                    <Button label="Open PDF in Viewer" @click="() => window.open(previewData.url, '_blank')"
                        icon="pi pi-external-link" class="w-full" severity="info" />
                    <p class="text-xs text-gray-500">Or download to view on your computer</p>
                </div>

                <!-- Other File Types -->
                <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                    <i class="pi pi-file text-5xl text-gray-400 mb-4"></i>
                    <p class="text-sm text-gray-600 mb-4">Preview not available for this file type</p>
                    <p class="text-xs text-gray-500 mb-6">{{ previewData.mimeType || 'File type unknown' }}</p>
                    <Button label="Download File" @click="() => window.open(previewData.url, '_blank')"
                        icon="pi pi-download" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Close" severity="secondary" @click="showPreviewModal = false" />
            </template>
        </Dialog>

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

<!-- Unscoped: PrimeVue Dialog uses Portal (teleports outside component DOM) -->
<style>
/* iOS Dialog styling */
.ios-dialog-root.p-dialog {
    border-radius: 14px !important;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12) !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4) !important;
    backdrop-filter: blur(4px);
}
</style>
