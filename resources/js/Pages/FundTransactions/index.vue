<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import VoucherWizard from "@/Components/Obligations/VoucherWizard.vue";
import DeleteConfirmModal from "@/Pages/FundTransactions/Modal/DeleteConfirmModal.vue";
import ViewTransactionModal from "@/Pages/FundTransactions/Modal/ViewTransactionModal.vue";
import RemarksModal from "@/Pages/FundTransactions/Modal/RemarksModal.vue";
import StatusModal from "@/Pages/FundTransactions/Modal/StatusModal.vue";
import TrackingHistoryModal from "@/Pages/FundTransactions/Modal/TrackingHistoryModal.vue";
import ObrTrackingModal from "@/Pages/FundTransactions/Modal/ObrTrackingModal.vue";
import FilterPage from "@/Components/Filters/FilterPage.vue";
import axios from "axios";
import { usePdfPrint, renderVueTemplate } from "@/composables/usePdfPrint";
import { stripHtml } from "@/utils/sanitize";
import { useSystemOptions } from "@/composables/useSystemOptions";
import {
    getStatusBadgeClass,
    getStatusTextClass,
    getStatusIcon,
} from "@/Pages/FundTransactions/statusMeta";
import ObrTemplate from "@/Pages/FundTransactions/Pdf/ObrTemplate.vue";
import DvTemplate from "@/Pages/FundTransactions/Pdf/DvTemplate.vue";
import PayrollTemplate from "@/Pages/FundTransactions/Pdf/PayrollTemplate.vue";
import LosTemplate from "@/Pages/FundTransactions/Pdf/LosTemplate.vue";
import PdfPreviewModal from "@/Pages/FundTransactions/Modal/PdfPreviewModal.vue";

const toast = useToast();
const { buildHtmlDoc, printHtml } = usePdfPrint();

// PDF Preview modal state
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref("");
const pdfPreviewTitle = ref("");
const pdfPreviewSize = ref("a4");

const ftDrawerPt = {
    root: { class: "ft-floating-drawer" },
    mask: { class: "ft-floating-drawer-mask" },
};

const page = usePage();
const _url = new URLSearchParams(window.location.search);

const normalizeObrTypeValue = (value) => {
    const text = String(value ?? "").trim();
    if (!text) return "";

    return text.toLowerCase().replace(/[\s-]+/g, "_");
};

const humanizeObrTypeValue = (value) => {
    const normalized = normalizeObrTypeValue(value);
    if (!normalized) return "";

    return normalized
        .split("_")
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(" ");
};

const formatObrTypeLabel = (value, fallback = "---") => {
    const normalized = normalizeObrTypeValue(value);
    if (!normalized) return fallback;

    const match = _obrTypeRaw.value.find(
        (option) => normalizeObrTypeValue(option.value) === normalized,
    );
    return match?.label || humanizeObrTypeValue(normalized);
};

const getObrTypeTextClass = (value) => {
    switch (normalizeObrTypeValue(value)) {
        case "regular":
            return "text-gray-800 dark:text-gray-200";
        case "financial_assistance":
            return "text-yellow-800 dark:text-yellow-200";
        case "reimbursement":
            return "text-purple-800 dark:text-purple-200";
        default:
            return "text-gray-700 dark:text-gray-300";
    }
};

const formatVoucherDocumentType = (value) => {
    if (value === "disbursements") {
        return "Disbursement Voucher";
    }

    if (value === "payroll") {
        return "Payroll";
    }

    return value || "---";
};

const REMARKS_PREVIEW_LENGTH = 100;

const getRemarksPreview = (remarks) => {
    const text = stripHtml(remarks).trim();
    if (text.length <= REMARKS_PREVIEW_LENGTH) return text;
    return text.slice(0, REMARKS_PREVIEW_LENGTH).trimEnd() + "…";
};

const formatVoucherDocumentTypeAbbr = (value) => {
    if (value === "disbursements") {
        return "DV";
    }

    if (value === "payroll") {
        return "PR";
    }

    return value || "---";
};

const showWizard = ref(false);
const currentStep = ref(1);
const voucherType = ref("obligations");
const selectedScholars = ref([]);
const vouchers = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const showDeleteConfirmDialog = ref(false);
const voucherToDelete = ref(null);
const searchQuery = ref(_url.get("search") || "");
const showViewDialog = ref(false);
const selectedVoucher = ref(null);
const viewModalTab = ref("details");
const scholarsDetails = ref([]);
const loadingScholars = ref(false);
const expandedScholarRows = ref(new Set());

const toggleScholarsExpand = (voucherId) => {
    const next = new Set(expandedScholarRows.value);
    if (next.has(voucherId)) {
        next.delete(voucherId);
    } else {
        next.add(voucherId);
    }
    expandedScholarRows.value = next;
};
const scholarsCache = ref(new Map()); // Cache for scholar details by ID
const editingId = ref(null);
const editFormData = ref(null);
const responsibilityCenters = ref([]);
const contextMenu = ref();
const selectedContextVoucher = ref(null);
const showRemarksDialog = ref(false);
const selectedVoucherForRemarks = ref(null);
const remarksForm = reactive({
    remarks: "",
});
const savingRemarks = ref(false);
const contextMenuItems = ref([]);
const showStatusDialog = ref(false);
const selectedVoucherForStatus = ref(null);
const statusForm = reactive({
    obr_status: "on process",
    remarks: "",
    status_updated_at: null,
    obr_no: undefined,
});
const savingStatus = ref(false);
const _obrStatusRaw = useSystemOptions("obr_status");
const obrStatuses = computed(() => [
    "No OBR",
    ..._obrStatusRaw.value.map((o) => o.label),
]);
const showOBRTrackingDialog = ref(false);
const selectedVoucherForOBRTracking = ref(null);
const statusFilter = ref(_url.get("status") || "");
const obrNoFilter = ref(_url.get("obr_no_mode") || "");
const obrTypeFilter = ref(normalizeObrTypeValue(_url.get("type")));
const disbursementTypeFilter = ref(_url.get("dv_type") || "");
const userFilter = ref(_url.get("user") || "all");
const currentPage = ref(1);
const perPage = ref(parseInt(_url.get("per_page") || "10"));
const filteredTotal = ref(0);
const loadingMore = ref(false);
const hasMoreVouchers = computed(() => vouchers.value.length < filteredTotal.value);
const obrTrackingForm = reactive({
    fiscal_year: new Date().getFullYear(),
    obr_no: "",
    date_obligated: null,
    dv_no: "",
});
const updatingOBRTracking = ref(false);
const obrTrackingResult = ref(null);
const showTrackingHistoryDialog = ref(false);
const trackingHistoryData = ref(null);
const loadingTrackingHistory = ref(false);
const statusFilterOptions = computed(() => [
    { label: "No OBR", value: "No OBR" },
    ..._obrStatusRaw.value.map((o) => ({ label: o.label, value: o.label })),
]);

const obrNoFilterOptions = [
    { label: "With OBR No.", value: "with" },
    { label: "Without OBR No.", value: "without" },
];

const _obrTypeRaw = useSystemOptions("disbursement_type");
const obrTypeFilterOptions = computed(() =>
    _obrTypeRaw.value.map((o) => ({
        label: o.label,
        value: o.value,
    })),
);

const disbursementTypeFilterOptions = [
    { label: "Disbursement Voucher", value: "disbursements" },
    { label: "Payroll", value: "payroll" },
];

// FilterPage component configuration
const filterConfig = computed(() => [
    {
        key: "obr_status",
        options: statusFilterOptions.value,
        placeholder: "OBR Status",
        class: "w-40",
    },
    {
        key: "obr_no_mode",
        options: obrNoFilterOptions,
        placeholder: "OBR No.",
        class: "w-44",
    },
    {
        key: "obr_type",
        options: obrTypeFilterOptions.value,
        placeholder: "OBR Type",
        class: "w-44",
    },
    {
        key: "disbursement_type",
        options: disbursementTypeFilterOptions,
        placeholder: "DV Type",
        class: "w-44",
    },
]);

const onFilterChange = (filters) => {
    statusFilter.value = filters.obr_status || "";
    obrNoFilter.value = filters.obr_no_mode || "";
    obrTypeFilter.value = normalizeObrTypeValue(filters.obr_type || "");
    disbursementTypeFilter.value = filters.disbursement_type || "";
};

const clearAllFilters = () => {
    statusFilter.value = "";
    obrNoFilter.value = "";
    obrTypeFilter.value = "";
    disbursementTypeFilter.value = "";
    searchQuery.value = "";
    userFilter.value = "all";
};

const handleCreateVoucher = () => {
    editingId.value = null;
    editFormData.value = null;
    showWizard.value = true;
    currentStep.value = 1;
    selectedScholars.value = [];
    voucherType.value = "obligations";
};

const handleWizardClose = () => {
    showWizard.value = false;
    currentStep.value = 1;
    selectedScholars.value = [];
    fetchVouchers();
};

const handleScholarSelection = (scholars, type) => {
    selectedScholars.value = scholars;
    voucherType.value = type;
};

// Fetch vouchers from API. Pass { append: true } to load the next batch
// onto the end of the list (Show More) instead of replacing it.
const fetchVouchers = async ({ append = false } = {}) => {
    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        currentPage.value = 1;
    }

    try {
        const params = {};
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (statusFilter.value) params.obr_status = statusFilter.value;
        if (obrNoFilter.value) params.obr_no_mode = obrNoFilter.value;
        if (obrTypeFilter.value) params.obr_type = obrTypeFilter.value;
        if (disbursementTypeFilter.value)
            params.disbursement_type = disbursementTypeFilter.value;
        if (userFilter.value === "my-records") {
            const userId = page.props.auth?.user?.id;
            if (userId) params.created_by = userId;
        }
        params.page = currentPage.value;
        params.per_page = perPage.value;

        const response = await axios.get("/api/fund-transactions", { params });
        const newVouchers = response.data.data || [];
        vouchers.value = append ? [...vouchers.value, ...newVouchers] : newVouchers;
        totalRecordsCount.value = response.data.total ?? 0;
        filteredTotal.value = response.data.filtered_total ?? 0;
        myRecordsCount.value = response.data.my_records_count ?? 0;

        // Fetch and cache scholars for school payees
        for (const voucher of newVouchers) {
            if (isPayeeSchool(voucher) && voucher.scholar_ids?.length > 0) {
                fetchAndCacheScholarDetails(voucher.scholar_ids);
            }
        }
    } catch (error) {
        console.error("Error fetching vouchers:", error);
        if (!append) vouchers.value = [];
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

// Load the next batch of vouchers onto the end of the current list
const loadMoreVouchers = () => {
    if (loadingMore.value || !hasMoreVouchers.value) return;
    currentPage.value += 1;
    fetchVouchers({ append: true });
};

// Fetch and cache scholar details
const fetchAndCacheScholarDetails = async (scholarIds) => {
    if (!scholarIds || scholarIds.length === 0) return;

    try {
        for (const scholar of scholarIds) {
            const profileId =
                typeof scholar === "object" ? scholar.profile_id : scholar;

            // Skip if already in cache
            if (scholarsCache.value.has(profileId)) continue;

            try {
                const response = await axios.get(
                    `/api/scholarships/profile/${profileId}`,
                );
                if (response.data.data) {
                    scholarsCache.value.set(profileId, response.data.data);
                }
            } catch (error) {
                console.error(`Error fetching scholar ${profileId}:`, error);
            }
        }
    } catch (error) {
        console.error("Error in fetchAndCacheScholarDetails:", error);
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
            const profileId =
                typeof scholar === "object" ? scholar.profile_id : scholar;

            // Check cache first
            if (scholarsCache.value.has(profileId)) {
                details.push(scholarsCache.value.get(profileId));
            } else {
                try {
                    const response = await axios.get(
                        `/api/scholarships/profile/${profileId}`,
                    );
                    if (response.data.data) {
                        scholarsCache.value.set(profileId, response.data.data);
                        details.push(response.data.data);
                    }
                } catch (error) {
                    console.error(
                        `Error fetching scholar ${profileId}:`,
                        error,
                    );
                }
            }
        }
        scholarsDetails.value = details;
    } catch (error) {
        console.error("Error fetching scholars details:", error);
        scholarsDetails.value = [];
    } finally {
        loadingScholars.value = false;
    }
};

const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Roles is an array of strings like ['administrator']
    return user.roles?.includes("administrator") ?? false;
});

// Records marked Paid or Claimed are locked from further edits for non-admins.
const LOCKED_STATUSES = ["Paid", "Claimed"];
const isVoucherLocked = (voucher) =>
    LOCKED_STATUSES.includes(voucher?.obr_status) && !isAdmin.value;

// Computed property for user record counts
const myRecordsCount = ref(0);

const totalRecordsCount = ref(0);

// Fetch tracking history for a voucher
const fetchTrackingHistory = async (voucher) => {
    if (!voucher.fiscal_year || !voucher.obr_no) {
        toast.add({
            severity: "warn",
            summary: "Incomplete OBR Data",
            detail: `Fiscal Year: ${voucher.fiscal_year}, OBR No: ${voucher.obr_no}. Please save OBR tracking first.`,
            life: 5000,
        });
        return false;
    }

    loadingTrackingHistory.value = true;
    try {
        const params = {
            fiscal_year: voucher.fiscal_year,
            obr_no: voucher.obr_no,
            dv_no: voucher.dv_no || "",
            type: voucher.type || "",
        };

        const response = await axios.get("/api/obr-tracking-info", { params });

        if (response.data.success) {
            // Store the tracking data from wrapped response
            trackingHistoryData.value = response.data.data;

            // If DV number was auto-fetched, update the voucher in the list
            if (response.data.used_dv_no && !voucher.dv_no) {
                voucher.dv_no = response.data.used_dv_no;
                // Also update in the vouchers array
                const voucherIndex = vouchers.value.findIndex(
                    (v) => v.id === voucher.id,
                );
                if (voucherIndex > -1) {
                    vouchers.value[voucherIndex].dv_no =
                        response.data.used_dv_no;
                }
            }

            showTrackingHistoryDialog.value = true;
        } else {
            toast.add({
                severity: "error",
                summary: "Error",
                detail:
                    response.data.message || "Failed to fetch tracking history",
                life: 3000,
            });
        }
    } catch (error) {
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: "error",
            summary: "Error",
            detail: errorMsg,
            life: 5000,
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
    const fallbackVoucher =
        vouchers.value.find((v) => v.id === voucherId) || null;

    try {
        const response = await axios.get(`/api/fund-transactions/${voucherId}`);
        const voucher = response.data?.data || fallbackVoucher;

        if (!voucher) {
            return;
        }

        selectedVoucher.value = voucher;
        viewModalTab.value = "details";
        showViewDialog.value = true;

        await fetchScholarsDetails(voucher.scholar_ids || []);
    } catch (error) {
        console.error("Error fetching voucher details:", error);

        if (!fallbackVoucher) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to load voucher data",
                life: 3000,
            });
            return;
        }

        selectedVoucher.value = fallbackVoucher;
        viewModalTab.value = "details";
        showViewDialog.value = true;

        await fetchScholarsDetails(fallbackVoucher.scholar_ids || []);
    }
};

// Edit voucher
const editVoucher = async (voucherId) => {
    try {
        // Fetch fresh voucher data from API to ensure we have all fields
        const response = await axios.get(`/api/fund-transactions/${voucherId}`);
        const voucher = response.data.data;

        if (isVoucherLocked(voucher)) {
            toast.add({
                severity: "warn",
                summary: "Record Locked",
                detail: "This record is Paid or Claimed and can only be edited by an administrator.",
                life: 4000,
            });
            return;
        }

        // Set up edit data
        editFormData.value = {
            ...voucher,
            responsibility_center: voucher.responsibility_center || "",
        };
        editingId.value = voucherId;
        showWizard.value = true;
    } catch (error) {
        console.error("Error fetching voucher:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to load voucher data",
            life: 3000,
        });
    }
};

// Save edited voucher
const saveVoucher = async () => {
    if (!editFormData.value) return;

    editingId.value = editFormData.value.id;
    try {
        await axios.put(
            `/api/fund-transactions/${editFormData.value.id}`,
            editFormData.value,
        );

        // Update the voucher in the list
        const index = vouchers.value.findIndex(
            (v) => v.id === editFormData.value.id,
        );
        if (index !== -1) {
            vouchers.value[index] = editFormData.value;
        }

        showWizard.value = false;
        editFormData.value = null;
        editingId.value = null;
        toast.add({
            severity: "success",
            summary: "Success",
            detail: "Voucher updated successfully",
            life: 3000,
        });
    } catch (error) {
        console.error("Error updating voucher:", error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to update voucher: " + errorMsg,
            life: 5000,
        });
    } finally {
        editingId.value = null;
    }
};

// Generate document
const generateDocument = async (docType) => {
    if (!selectedVoucher.value) return;

    if (docType === "OBR") {
        // Client-side PDF preview and print flow
        try {
            const html = renderVueTemplate(ObrTemplate, {
                voucher: selectedVoucher.value,
                scholarDetails: scholarsDetails.value,
            });
            const title = `OBR-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title);
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = "a4";
            showPdfPreview.value = true;
        } catch (error) {
            console.error("Error generating OBR:", error);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to generate OBR: " + error.message,
                life: 5000,
            });
        }
        return;
    }

    if (docType === "DV") {
        try {
            const html = renderVueTemplate(DvTemplate, {
                voucher: selectedVoucher.value,
                scholarDetails: scholarsDetails.value,
            });
            const title = `DV-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, "long");
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = "long";
            showPdfPreview.value = true;
        } catch (error) {
            console.error("Error generating DV:", error);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to generate DV: " + error.message,
                life: 5000,
            });
        }
        return;
    }

    if (docType === "PR") {
        try {
            const html = renderVueTemplate(PayrollTemplate, {
                voucher: selectedVoucher.value,
                scholarDetails: scholarsDetails.value,
            });
            const title = `Payroll-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, "landscape");
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = "landscape";
            showPdfPreview.value = true;
        } catch (error) {
            console.error("Error generating Payroll:", error);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to generate Payroll: " + error.message,
                life: 5000,
            });
        }
        return;
    }

    // LOS — client-side
    if (docType === "LOS") {
        try {
            const html = renderVueTemplate(LosTemplate, {
                voucher: selectedVoucher.value,
                scholarDetails: scholarsDetails.value,
            });
            const title = `ListOfScholars-${selectedVoucher.value.transaction_id || selectedVoucher.value.id}`;
            pdfPreviewHtml.value = buildHtmlDoc(html, title, "a4");
            pdfPreviewTitle.value = title;
            pdfPreviewSize.value = "a4";
            showPdfPreview.value = true;
        } catch (error) {
            console.error("Error generating LOS:", error);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to generate LOS: " + error.message,
                life: 5000,
            });
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
        vouchers.value = vouchers.value.filter(
            (v) => v.id !== voucherToDelete.value,
        );
        showDeleteConfirmDialog.value = false;
        toast.add({
            severity: "success",
            summary: "Success",
            detail: "Voucher deleted successfully",
            life: 3000,
        });
    } catch (error) {
        console.error("Error deleting voucher:", error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to delete voucher: " + errorMsg,
            life: 5000,
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

    const locked = isVoucherLocked(voucher);

    // Record actions
    const items = [
        {
            label: "View",
            icon: "eye",
            command: () => viewVoucher(voucher.id),
        },
        // Edit functionalities, grouped under one submenu
        {
            label: locked ? "Edit (Locked)" : "Edit",
            icon: locked ? "lock" : "pencil",
            iconClass: locked
                ? "text-gray-400 dark:text-gray-500"
                : "text-gray-600 dark:text-gray-400",
            disabled: locked,
            items: [
                {
                    label: "Voucher",
                    icon: "receipt-text",
                    iconClass: "text-blue-600 dark:text-blue-400",
                    disabled: locked,
                    command: () => editVoucher(voucher.id),
                },
                {
                    label: "Remarks",
                    icon: "comment",
                    iconClass: "text-amber-600 dark:text-amber-400",
                    disabled: locked,
                    command: () => openRemarksModal(voucher),
                },
                {
                    label: "OBR Info",
                    icon: "file-code",
                    iconClass: "text-purple-600 dark:text-purple-400",
                    disabled: locked,
                    command: () => openOBRTrackingDialog(voucher),
                },
            ],
        },
        { separator: true },
        // Status & tracking actions
        {
            label: "Change Status",
            icon: "sync",
            items: obrStatuses.value.map((status) => ({
                label: status,
                icon: getStatusIcon(status),
                iconClass: getStatusTextClass(status),
                command: () => openStatusModal(voucher, status),
            })),
        },
        {
            label: "Tracking History",
            icon: "history",
            command: () => fetchTrackingHistory(voucher),
        },
    ];

    if (isAdmin.value) {
        items.push({ separator: true });
        items.push({
            label: "Delete",
            icon: "trash",
            command: () => deleteVoucher(voucher.id),
            class: "p-menuitem-danger",
        });
    }

    contextMenuItems.value = items;
    contextMenu.value.show(event);
};

// Open remarks modal
const openRemarksModal = (voucher) => {
    if (isVoucherLocked(voucher)) {
        toast.add({
            severity: "warn",
            summary: "Record Locked",
            detail: "This record is Paid or Claimed and can only be edited by an administrator.",
            life: 4000,
        });
        return;
    }

    selectedVoucherForRemarks.value = voucher;
    remarksForm.remarks = voucher.remarks || "";
    showRemarksDialog.value = true;
};

// Save remarks
const saveRemarks = async () => {
    if (!selectedVoucherForRemarks.value) return;

    savingRemarks.value = true;
    try {
        // GET the current voucher data
        const currentVoucher = await axios.get(
            `/api/fund-transactions/${selectedVoucherForRemarks.value.id}`,
        );
        const voucherData = currentVoucher.data.data;

        // PUT with all required fields plus updated remarks
        await axios.put(
            `/api/fund-transactions/${selectedVoucherForRemarks.value.id}`,
            {
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
                transaction_status: voucherData.transaction_status,
            },
        );

        // Update the voucher in the list
        const voucherIndex = vouchers.value.findIndex(
            (v) => v.id === selectedVoucherForRemarks.value.id,
        );
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].remarks = remarksForm.remarks;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForRemarks.value.id) {
            selectedVoucher.value.remarks = remarksForm.remarks;
        }

        showRemarksDialog.value = false;
        toast.add({
            severity: "success",
            summary: "Success",
            detail: "Remarks saved successfully",
            life: 3000,
        });
    } catch (error) {
        console.error("Error saving remarks:", error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to save remarks: " + errorMsg,
            life: 5000,
        });
    } finally {
        savingRemarks.value = false;
    }
};

// Open transaction status modal
const openStatusModal = (voucher, presetStatus = null) => {
    selectedVoucherForStatus.value = presetStatus
        ? { ...voucher, obr_status: presetStatus, status_updated_at: null }
        : voucher;
    statusForm.obr_status = presetStatus || voucher.obr_status || "On Process";
    statusForm.remarks = voucher.remarks || "";
    statusForm.obr_no = undefined;
    showStatusDialog.value = true;
};

// Save transaction status
const saveStatus = async () => {
    if (!selectedVoucherForStatus.value) return;

    savingStatus.value = true;
    try {
        // Just send the status, remarks, and manually picked status date - minimal update
        const response = await axios.patch(
            `/api/fund-transactions/${selectedVoucherForStatus.value.id}/update-status`,
            {
                transaction_status: statusForm.obr_status,
                remarks: statusForm.remarks,
                status_updated_at: statusForm.status_updated_at,
                ...(statusForm.obr_no !== undefined
                    ? { obr_no: statusForm.obr_no }
                    : {}),
            },
        );

        // Update the voucher in the list with the actual returned values
        const voucherIndex = vouchers.value.findIndex(
            (v) => v.id === selectedVoucherForStatus.value.id,
        );
        if (voucherIndex > -1) {
            vouchers.value[voucherIndex].obr_status =
                response.data.data?.obr_status;
            vouchers.value[voucherIndex].remarks = response.data.data?.remarks;
            vouchers.value[voucherIndex].status_updated_at =
                response.data.data?.status_updated_at;
            vouchers.value[voucherIndex].obr_no = response.data.data?.obr_no;
        }

        // Also update the currently viewed voucher if it's the same one
        if (selectedVoucher.value?.id === selectedVoucherForStatus.value.id) {
            selectedVoucher.value.obr_status = response.data.data?.obr_status;
            selectedVoucher.value.remarks = response.data.data?.remarks;
            selectedVoucher.value.status_updated_at =
                response.data.data?.status_updated_at;
            selectedVoucher.value.obr_no = response.data.data?.obr_no;
        }

        showStatusDialog.value = false;
        toast.add({
            severity: "success",
            summary: "Success",
            detail: "Transaction status updated successfully",
            life: 3000,
        });
    } catch (error) {
        console.error("Error saving transaction status:", error);
        const errorMsg = error.response?.data?.message || error.message;
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to update transaction status: " + errorMsg,
            life: 5000,
        });
    } finally {
        savingStatus.value = false;
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return "---";
    try {
        const d = new Date(date);
        return d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (e) {
        return date;
    }
};

// Date-only variant, used for status_updated_at (which has no time component)
const formatDateOnly = (date) => {
    if (!date) return "---";
    try {
        const d = new Date(date);
        return d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    } catch (e) {
        return date;
    }
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "PHP",
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
            if (
                typeof scholar === "object" &&
                scholar !== null &&
                typeof scholar.amount !== "undefined"
            ) {
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

// Status color for badges, kept as an alias for the shared status metadata
const getStatusColor = getStatusBadgeClass;

// Check if payee is school
const isPayeeSchool = (voucher) => {
    return (
        voucher?.payee_type?.toLowerCase() === "school" ||
        voucher?.payee_name?.toLowerCase().includes("school")
    );
};

// Get scholar name from cache by profile ID
const getScholarNameFromCache = (profileId) => {
    if (!profileId) return "";
    const scholar = scholarsCache.value.get(profileId);
    if (scholar) {
        return `${scholar.first_name} ${scholar.last_name}`;
    }
    // Fetch it asynchronously if not cached
    fetchAndCacheScholarDetails([profileId]);
    return "";
};

// Get first scholar name truncated (for view modal)
const getFirstScholarName = (voucher) => {
    if (!voucher?.scholar_ids || voucher.scholar_ids.length === 0) {
        return "";
    }
    // If scholarsDetails has the first scholar, use its name
    const firstScholar = scholarsDetails.value?.[0];
    if (firstScholar) {
        const name = `${firstScholar.first_name} ${firstScholar.last_name}`;
        return name.length > 25 ? name.substring(0, 25) + "..." : name;
    }
    return "";
};

// Get document button label based on voucher type
const getDocumentButtonLabel = () => {
    if (!selectedVoucher.value) return "Document";
    return selectedVoucher.value.disbursement_type === "payroll" ? "PR" : "DV";
};

// Get document type to generate based on voucher type
const getDocumentType = () => {
    if (!selectedVoucher.value) return "DV";
    return selectedVoucher.value.disbursement_type === "payroll" ? "PR" : "DV";
};

// Fetch responsibility centers and particulars
const fetchResponsibilityCentersAndParticulars = async () => {
    try {
        const response = await axios.get("/api/responsibility-centers");
        if (response.data && response.data.data) {
            responsibilityCenters.value = response.data.data;
        }
    } catch (error) {
        console.error("Error fetching responsibility centers:", error);
    }
};

// Update OBR tracking
const updateOBRTracking = async (fiscalYear, obrNo, dvNo) => {
    try {
        // Validate required fields - Only fiscal year and OBR are required
        if (!fiscalYear || !obrNo) {
            toast.add({
                severity: "warn",
                summary: "Missing Required Fields",
                detail: "Fiscal Year and OBR Number are required. DV Number is optional and will be auto-fetched.",
                life: 5000,
            });
            return false;
        }

        // Just return success with the data (no external API call)
        return {
            success: true,
            data: {
                fiscal_year: fiscalYear,
                obr_no: obrNo,
                dv_no: dvNo || null,
            },
        };
    } catch (error) {
        console.error("Error updating OBR tracking:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.message,
            life: 5000,
        });
        return false;
    }
};

// Open OBR Tracking dialog
const openOBRTrackingDialog = (voucher) => {
    if (isVoucherLocked(voucher)) {
        toast.add({
            severity: "warn",
            summary: "Record Locked",
            detail: "This record is Paid or Claimed and can only be edited by an administrator.",
            life: 4000,
        });
        return;
    }

    selectedVoucherForOBRTracking.value = voucher;
    obrTrackingForm.fiscal_year =
        voucher.fiscal_year || new Date().getFullYear();
    obrTrackingForm.obr_no = voucher.obr_no || "";
    obrTrackingForm.date_obligated = voucher.date_obligated
        ? voucher.date_obligated.substring(0, 10)
        : null;
    obrTrackingForm.dv_no = voucher.dv_no || "";
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
            obrTrackingForm.dv_no,
        );

        if (result) {
            obrTrackingResult.value = result;

            // Now save the OBR tracking data to the voucher
            if (selectedVoucherForOBRTracking.value) {
                try {
                    // GET current voucher data
                    const currentVoucher = await axios.get(
                        `/api/fund-transactions/${selectedVoucherForOBRTracking.value.id}`,
                    );
                    const voucherData = currentVoucher.data.data;

                    // Validate obr_status - preserve existing status
                    const validStatuses = [
                        "No OBR",
                        "LOA",
                        "Irregular",
                        "Transferred",
                        "Claimed",
                        "Paid",
                        "On Process",
                        "Denied",
                        "Replacement",
                        "Cancelled",
                    ];
                    const statusToSend =
                        voucherData.obr_status &&
                        validStatuses.includes(voucherData.obr_status)
                            ? voucherData.obr_status
                            : voucherData.obr_status || "On Process"; // Keep existing status or default to 'On Process' if none

                    // PUT with OBR tracking fields
                    await axios.put(
                        `/api/fund-transactions/${selectedVoucherForOBRTracking.value.id}`,
                        {
                            disbursement_type: voucherData.disbursement_type,
                            explanation: voucherData.explanation,
                            payee_type: voucherData.payee_type,
                            payee_name: voucherData.payee_name,
                            payee_address: voucherData.payee_address,
                            responsibility_center:
                                voucherData.responsibility_center,
                            account_code: voucherData.account_code,
                            particulars_name: voucherData.particulars_name,
                            particulars_description:
                                voucherData.particulars_description,
                            amount: voucherData.amount,
                            obr_type: voucherData.obr_type,
                            scholar_ids: voucherData.scholar_ids,
                            remarks: voucherData.remarks,
                            transaction_status: statusToSend,
                            fiscal_year:
                                parseInt(obrTrackingForm.fiscal_year) || null,
                            obr_no: obrTrackingForm.obr_no || null,
                            date_obligated:
                                obrTrackingForm.date_obligated || null,
                            dv_no: obrTrackingForm.dv_no || null,
                        },
                    );

                    // Update the voucher in the local list
                    const voucherIndex = vouchers.value.findIndex(
                        (v) => v.id === selectedVoucherForOBRTracking.value.id,
                    );
                    if (voucherIndex > -1) {
                        vouchers.value[voucherIndex].fiscal_year =
                            obrTrackingForm.fiscal_year;
                        vouchers.value[voucherIndex].obr_no =
                            obrTrackingForm.obr_no;
                        vouchers.value[voucherIndex].date_obligated =
                            obrTrackingForm.date_obligated;
                        vouchers.value[voucherIndex].dv_no =
                            obrTrackingForm.dv_no;
                        vouchers.value[voucherIndex].obr_status = statusToSend;
                    }

                    // Also update selectedVoucher if it's the same voucher
                    if (
                        selectedVoucher.value?.id ===
                        selectedVoucherForOBRTracking.value.id
                    ) {
                        selectedVoucher.value.fiscal_year =
                            obrTrackingForm.fiscal_year;
                        selectedVoucher.value.obr_no = obrTrackingForm.obr_no;
                        selectedVoucher.value.date_obligated =
                            obrTrackingForm.date_obligated;
                        selectedVoucher.value.dv_no = obrTrackingForm.dv_no;
                        selectedVoucher.value.obr_status = statusToSend;
                    }

                    toast.add({
                        severity: "success",
                        summary: "Success",
                        detail: "OBR tracking information saved to voucher",
                        life: 3000,
                    });
                } catch (saveError) {
                    console.error(
                        "Error saving OBR tracking to voucher:",
                        saveError,
                    );
                    const errorMessage = saveError.response?.data?.errors
                        ? Object.entries(saveError.response.data.errors)
                              .map(
                                  ([field, messages]) =>
                                      `${field}: ${messages.join(", ")}`,
                              )
                              .join(" | ")
                        : saveError.response?.data?.message ||
                          "Failed to save OBR tracking";
                    toast.add({
                        severity: "error",
                        summary: "Validation Error",
                        detail: errorMessage,
                        life: 5000,
                    });
                }
            }
        } else {
            showOBRTrackingDialog.value = false;
        }
    } catch (error) {
        console.error("Error saving OBR tracking:", error);
    } finally {
        updatingOBRTracking.value = false;
    }
};

// Sync current filter state to the browser URL (replaceState — no navigation)
const syncFiltersToUrl = () => {
    const params = new URLSearchParams();
    if (searchQuery.value?.trim())
        params.set("search", searchQuery.value.trim());
    if (statusFilter.value) params.set("status", statusFilter.value);
    if (obrNoFilter.value) params.set("obr_no_mode", obrNoFilter.value);
    if (obrTypeFilter.value) params.set("type", obrTypeFilter.value);
    if (disbursementTypeFilter.value)
        params.set("dv_type", disbursementTypeFilter.value);
    if (userFilter.value && userFilter.value !== "all")
        params.set("user", userFilter.value);
    if (perPage.value !== 10) params.set("per_page", perPage.value);
    const qs = params.toString();
    window.history.replaceState(
        null,
        "",
        qs ? `${window.location.pathname}?${qs}` : window.location.pathname,
    );
};

// Re-fetch when dropdown filters or user filter changes (reset to page 1)
watch(
    [
        statusFilter,
        obrNoFilter,
        obrTypeFilter,
        disbursementTypeFilter,
        userFilter,
    ],
    () => {
        currentPage.value = 1;
        syncFiltersToUrl();
        fetchVouchers();
    },
);

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
            const validOption = responsibilityCenters.value.find(
                (rc) => rc.code === stringValue,
            );
            if (validOption) {
                editFormData.value.responsibility_center = validOption.code;
            }
        }
    },
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
        <div
            class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 ios-settings-form"
        >
            <!-- Header -->
            <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon
                            name="credit-card"
                            :size="32"
                            class="text-indigo-900"
                        />
                        <div>
                            <h1
                                class="text-2xl font-bold text-gray-700 dark:text-gray-200"
                            >
                                Fund Transactions Management
                            </h1>
                            <p class="text-sm text-gray-600">
                                Create and manage financial transactions
                            </p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <AppButton
                        icon="plus"
                        @click="handleCreateVoucher"
                        severity="success"
                        rounded
                        outlined
                        v-tooltip.bottom="'Create Fund Transaction'"
                    />
                </template>
            </Toolbar>

            <!-- List/Summary Section -->
            <Panel class="!rounded-4xl mt-8">
                <!-- Info Bar -->
                <div
                    class="flex items-center justify-between gap-4 mb-4 p-3 bg-gray-50 dark:bg-[#1e242b] rounded-4xl -mt-2"
                >
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <AppIcon
                                    name="search"
                                    :size="14"
                                    class="text-gray-400"
                                />
                            </InputIcon>
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search obr no, payee, or scholar..."
                                class="w-full"
                                size="small"
                            />
                        </IconField>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm opacity-60 hidden sm:block"
                            >Right click row for actions</span
                        >
                        <AppButton
                            icon="refresh"
                            severity="secondary"
                            size="small"
                            rounded
                            outlined
                            @click="fetchVouchers"
                            :disabled="loading"
                            :loading="loading"
                            v-tooltip.bottom="'Refresh'"
                        />
                    </div>
                </div>

                <!-- Filter Page Component -->
                <FilterPage
                    :filters="filterConfig"
                    :show-record-filter="true"
                    :show-per-page="true"
                    :total-records-count="totalRecordsCount"
                    :my-records-count="myRecordsCount"
                    :filtered-total="filteredTotal"
                    :model-value="{
                        obr_status: statusFilter,
                        obr_no_mode: obrNoFilter,
                        obr_type: obrTypeFilter,
                        disbursement_type: disbursementTypeFilter,
                    }"
                    :user-filter="userFilter"
                    :per-page="perPage"
                    @update:model-value="onFilterChange"
                    @update:user-filter="
                        (val) => {
                            userFilter = val;
                        }
                    "
                    @update:per-page="
                        (val) => {
                            perPage = val;
                        }
                    "
                    @clear="clearAllFilters"
                    class="flex flex-wrap gap-3 items-center mb-4"
                />

                <!-- Context Menu -->
                <ContextMenu
                    ref="contextMenu"
                    :model="contextMenuItems"
                    appendTo="body"
                >
                    <template #item="{ item, props }">
                        <a
                            v-ripple
                            v-bind="props.action"
                            class="flex items-center gap-2 w-full"
                        >
                            <AppIcon
                                v-if="item.icon"
                                :name="item.icon"
                                :size="14"
                                :class="item.iconClass"
                            />
                            <span>{{ item.label }}</span>
                            <AppIcon
                                v-if="item.items"
                                name="chevron-right"
                                :size="14"
                                class="ml-auto"
                            />
                        </a>
                    </template>
                </ContextMenu>

                <!-- DataTable -->
                <!-- virtualScrollerOptions windows the already-fetched rows so the
                     DOM stays small as "Show More" grows the underlying list -->
                <DataTable
                    :value="vouchers"
                    stripedRows
                    showGridlines
                    responsiveLayout="scroll"
                    :loading="loading"
                    :emptyMessage="
                        filteredTotal === 0
                            ? 'No vouchers created yet. Click Create Fund Transaction to get started.'
                            : 'No vouchers match your search.'
                    "
                    :scrollable="true"
                    scrollHeight="600px"
                    :virtualScrollerOptions="{ itemSize: 72 }"
                    tableStyle="min-width: 100%"
                    @row-contextmenu="onRowContextMenu"
                    contextMenu
                    v-model:contextMenuSelection="selectedContextVoucher"
                    class="ft-table ios-datatable-rounded"
                >
                    <Column
                        header="#"
                        :headerStyle="{ width: '30px' }"
                        :bodyStyle="{ width: '30px' }"
                    >
                        <template #body="slotProps">
                            <span class="text-[11px] font-medium text-gray-400 dark:text-gray-300"
                                >{{ slotProps.index+1 }}</span
                            >
                        </template>
                    </Column>

                    <Column
                        header="Payee"
                        :headerStyle="{ minWidth: '300px' }"
                        :bodyStyle="{ minWidth: '300px' }"
                    >
                        <template #body="slotProps">
                            <div
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >
                                {{ slotProps.data.payee_name }}
                            </div>
                            <template
                                v-if="
                                    slotProps.data.scholar_ids &&
                                    slotProps.data.scholar_ids.length > 0
                                "
                            >
                                <div class="flex flex-col gap-0.5 mt-1">
                                    <div class="flex flex-col gap-0.5 max-h-[32px] overflow-y-auto">
                                        <div
                                            v-for="(
                                                scholar, sIdx
                                            ) in expandedScholarRows.has(
                                                slotProps.data.id,
                                            )
                                                ? slotProps.data.scholar_ids
                                                : slotProps.data.scholar_ids.slice(
                                                      0,
                                                      1,
                                                  )"
                                            :key="sIdx"
                                            class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                        >
                                            <span
                                                v-if="
                                                    typeof scholar === 'object' &&
                                                    scholar?.profile_id
                                                "
                                            >
                                                {{
                                                    getScholarNameFromCache(
                                                        scholar.profile_id,
                                                    ) ||
                                                    `Scholar
                                                #${scholar.profile_id}`
                                                }}
                                            </span>
                                            <span v-else>
                                                {{
                                                    getScholarNameFromCache(
                                                        scholar,
                                                    ) || `Scholar #${scholar}`
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        v-if="
                                            slotProps.data.scholar_ids.length >
                                            1
                                        "
                                        type="button"
                                        class="self-start text-2xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
                                        @click="
                                            toggleScholarsExpand(
                                                slotProps.data.id,
                                            )
                                        "
                                    >
                                        {{
                                            expandedScholarRows.has(
                                                slotProps.data.id,
                                            )
                                                ? "Show less"
                                                : `+${slotProps.data.scholar_ids.length - 1} more`
                                        }}
                                    </button>
                                </div>
                            </template>
                        </template>
                    </Column>
                    <Column
                        header="OBR Info"
                        :headerStyle="{ minWidth: '140px' }"
                        :bodyStyle="{ minWidth: '140px' }"
                    >
                        <template #body="slotProps">
                            <div class="flex items-center gap-4">
                                <div class="flex flex-col items-center">
                                    <span
                                        class="text-xs font-bold text-gray-400 dark:text-gray-300 uppercase"
                                        v-tooltip.bottom="
                                            formatVoucherDocumentType(
                                                slotProps.data.disbursement_type,
                                            )
                                        "
                                    >
                                        {{
                                            formatVoucherDocumentTypeAbbr(
                                                slotProps.data.disbursement_type,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="flex flex-col px-3">
                                    <span v-if="slotProps.data.obr_no"
                                        class="text-sm font-medium text-sky-600 dark:text-blue-400"
                                        >{{ slotProps.data.obr_no }}</span
                                    >
                                    <span v-else class="text-[11px] font-medium italic text-gray-400 dark:text-gray-500"
                                        >-No OBR #-</span>
                                
                                    <span
                                        :class="[
                                            'py-1 rounded-full text-xs font-medium uppercase',
                                            getObrTypeTextClass(
                                                slotProps.data.obr_type,
                                            ),
                                        ]"
                                    >
                                        {{
                                            formatObrTypeLabel(slotProps.data.obr_type)
                                        }}
                                    </span>

                                    <div
                                        class="text-xs text-gray-600 dark:text-gray-400 text-center" v-if="slotProps.data.date_obligated"
                                    >
                                        {{
                                            slotProps.data.date_obligated
                                                ? new Date(
                                                    slotProps.data.date_obligated,
                                                ).toLocaleDateString("en-US", {
                                                    year: "numeric",
                                                    month: "short",
                                                    day: "numeric",
                                                })
                                                : "---"
                                        }}
                                    </div>
                                </div>

                            </div>
                        </template>
                    </Column>
                    <Column
                        header="Status"
                        :headerStyle="{ minWidth: '140px' }"
                        :bodyStyle="{ minWidth: '140px' }"
                    >
                        <template #body="slotProps">
                            <div class="flex flex-col items-center gap-1.5">
                                <span
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1',
                                        getStatusColor(
                                            slotProps.data.obr_status,
                                        ),
                                    ]"
                                >
                                    <AppIcon
                                        :name="
                                            getStatusIcon(
                                                slotProps.data.obr_status ||
                                                    'On Process',
                                            )
                                        "
                                        :size="12"
                                    />
                                    {{
                                        slotProps.data.obr_status ||
                                        "On Process"
                                    }}
                                </span>
                                <div
                                    v-if="slotProps.data.status_updated_at"
                                    class="text-xs text-gray-500 dark:text-gray-500 text-center"
                                >
                                    {{
                                        formatDateOnly(
                                            slotProps.data.status_updated_at,
                                        )
                                    }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column
                        header="Total Amount"
                        :headerStyle="{ minWidth: '130px' }"
                        :bodyStyle="{ minWidth: '130px' }"
                    >
                        <template #body="slotProps">
                            <div class="flex flex-col items-end">
                                <span
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-100"
                                    >{{
                                        formatAmount(
                                            calculateTotalAmount(
                                                slotProps.data,
                                            ),
                                        )
                                    }}</span
                                >
                            </div>
                        </template>
                    </Column>

                    <Column
                        header="Remarks"
                        :headerStyle="{ minWidth: '220px' }"
                        :bodyStyle="{ minWidth: '220px' }"
                    >
                        <template #body="slotProps">
                            <div
                                v-if="slotProps.data.remarks"
                                class="text-[11px] text-gray-700 dark:text-gray-300 max-w-[220px]"
                                v-tooltip.bottom="{
                                    value: stripHtml(slotProps.data.remarks),
                                    class: 'ft-remarks-tooltip',
                                }"
                            >
                                {{ getRemarksPreview(slotProps.data.remarks) }}
                            </div>
                            <span
                                v-else
                                class="text-[11px] text-gray-400 dark:text-gray-500"
                                >—</span
                            >
                        </template>
                    </Column>

                    <Column
                        header="Processed By"
                        :headerStyle="{ minWidth: '130px' }"
                        :bodyStyle="{ minWidth: '130px' }"
                    >
                        <template #body="slotProps">
                            <span
                                class="text-[11px] font-semibold text-gray-600 dark:text-gray-400"
                                >{{
                                    slotProps.data.creator?.name || "---"
                                }}</span
                            >
                            <div
                                class="text-[11px] text-gray-500 dark:text-gray-500 mt-0.5"
                            >
                                {{ formatDate(slotProps.data.created_at) }}
                            </div>
                        </template>
                    </Column>

                    <Column
                        header=""
                        :headerStyle="{ width: '40px' }"
                        :bodyStyle="{ width: '40px' }"
                    >
                        <template #body="slotProps">
                            <AppButton
                                icon="ellipsis-v"
                                @click="
                                    (e) => openContextMenu(e, slotProps.data)
                                "
                                text
                                rounded
                                size="small"
                                v-tooltip="'Actions'"
                            />
                        </template>
                    </Column>
                </DataTable>

                <div v-if="vouchers.length > 0" class="flex flex-col items-center gap-1 mt-4">
                    <AppButton
                        v-if="hasMoreVouchers"
                        label="Show More"
                        icon="chevron-down"
                        severity="secondary"
                        size="small"
                        outlined
                        rounded
                        :loading="loadingMore"
                        :disabled="loadingMore"
                        @click="loadMoreVouchers"
                    />
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        Showing {{ vouchers.length }} of {{ filteredTotal }} entries
                    </span>
                </div>
            </Panel>
        </div>

        <!-- Fund Transaction Wizard (Create & Edit) -->
        <VoucherWizard
            v-if="showWizard"
            :visible="showWizard"
            :mode="editingId ? 'edit' : 'create'"
            :voucherId="editingId"
            :initialData="editFormData"
            @close="handleWizardClose"
            @scholar-selected="handleScholarSelection"
        />

        <!-- Delete Confirmation Dialog -->
        <DeleteConfirmModal
            :show="showDeleteConfirmDialog"
            @update:show="showDeleteConfirmDialog = $event"
            :voucher-number="
                vouchers.find((v) => v.id === voucherToDelete)
                    ?.transaction_id || 'N/A'
            "
            :payee-name="
                vouchers.find((v) => v.id === voucherToDelete)?.payee_name
            "
            :date="
                vouchers.find((v) => v.id === voucherToDelete)?.created_at
                    ? formatDate(
                          vouchers.find((v) => v.id === voucherToDelete)
                              .created_at,
                      )
                    : null
            "
            :is-deleting="deletingId === voucherToDelete"
            @confirm-delete="confirmDelete"
        />

        <!-- View Fund Transaction Dialog -->
        <ViewTransactionModal
            :show="showViewDialog"
            @update:show="showViewDialog = $event"
        >
            <div
                v-if="selectedVoucher"
                class="pt-2 h-[78vh] max-h-[780px] flex flex-col"
            >
                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
                    <Tabs v-model:value="viewModalTab" class="relative">
                        <TabList
                            class="sticky top-0 z-30 -mx-4 w-[calc(100%+2rem)] bg-white/95 dark:bg-[#2a3040]/95 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-white/10 overflow-hidden px-2 pt-2"
                        >
                            <Tab value="details">Transaction Details</Tab>
                            <Tab value="tracking">Tracking Info</Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel value="details">
                                <div class="ios-section">
                                    <div class="ios-card">
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >OBR Number</span
                                            >
                                            <span class="font-medium">{{
                                                selectedVoucher.obr_no || "---"
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Date Obligated</span
                                            >
                                            <span>{{
                                                selectedVoucher.date_obligated
                                                    ? formatDate(
                                                          selectedVoucher.date_obligated,
                                                      )
                                                    : "---"
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Disbursement Type</span
                                            >
                                            <span>{{
                                                formatVoucherDocumentType(
                                                    selectedVoucher.disbursement_type,
                                                )
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Payee</span
                                            >
                                            <div class="text-right">
                                                <p>
                                                    {{
                                                        selectedVoucher.payee_name
                                                    }}
                                                </p>
                                                <p
                                                    v-if="
                                                        isPayeeSchool(
                                                            selectedVoucher,
                                                        )
                                                    "
                                                    class="text-xs text-[#8E8E93] mt-0.5"
                                                >
                                                    Scholar:
                                                    {{
                                                        getFirstScholarName(
                                                            selectedVoucher,
                                                        ) || "---"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Amount</span
                                            >
                                            <span class="font-semibold">{{
                                                formatAmount(
                                                    selectedVoucher.amount,
                                                )
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Created By</span
                                            >
                                            <span>{{
                                                selectedVoucher.creator?.name ||
                                                "---"
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >Date</span
                                            >
                                            <span>{{
                                                formatDate(
                                                    selectedVoucher.created_at,
                                                )
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >OBR Type</span
                                            >
                                            <span>{{
                                                formatObrTypeLabel(
                                                    selectedVoucher.obr_type,
                                                )
                                            }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <span class="ios-row-label text-sm"
                                                >OBR Status</span
                                            >
                                            <span
                                                :class="[
                                                    'px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1',
                                                    getStatusColor(
                                                        selectedVoucher.obr_status,
                                                    ),
                                                ]"
                                            >
                                                <AppIcon
                                                    :name="
                                                        getStatusIcon(
                                                            selectedVoucher.obr_status ||
                                                                'On Process',
                                                        )
                                                    "
                                                    :size="12"
                                                />
                                                {{
                                                    selectedVoucher.obr_status ||
                                                    "On Process"
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="ios-row [border-bottom:none]"
                                        >
                                            <span class="ios-row-label text-sm"
                                                >Status Updated</span
                                            >
                                            <span>{{
                                                formatDateOnly(
                                                    selectedVoucher.status_updated_at,
                                                )
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="selectedVoucher.remarks"
                                    class="ios-section"
                                >
                                    <p class="ios-section-label text-compact">
                                        Remarks
                                    </p>
                                    <div class="ios-card px-4 py-3">
                                        <div
                                            class="text-sm text-gray-900 dark:text-gray-100"
                                            v-safe-html="
                                                selectedVoucher.remarks
                                            "
                                        ></div>
                                    </div>
                                </div>

                                <div class="ios-section">
                                    <p class="ios-section-label text-compact">
                                        Scholars ({{
                                            selectedVoucher.scholar_ids
                                                ?.length || 0
                                        }})
                                    </p>
                                    <div class="ios-card px-4 py-3">
                                        <div
                                            v-if="loadingScholars"
                                            class="text-center py-2"
                                        >
                                            <AppIcon
                                                name="spinner"
                                                :size="12"
                                                class="mr-2"
                                            />
                                            <span class="text-xs"
                                                >Loading...</span
                                            >
                                        </div>
                                        <div
                                            v-else-if="
                                                scholarsDetails &&
                                                scholarsDetails.length > 0
                                            "
                                            class="space-y-1 max-h-48 overflow-y-auto"
                                        >
                                            <div
                                                v-for="(
                                                    scholar, index
                                                ) in scholarsDetails"
                                                :key="index"
                                                class="text-xs text-gray-700 dark:text-gray-300 py-1 px-2 bg-gray-50 dark:bg-[#272f38] rounded flex items-center justify-between gap-2"
                                            >
                                                <span class="font-medium"
                                                    >{{ index + 1 }}.
                                                    {{ scholar.first_name }}
                                                    {{
                                                        scholar.last_name
                                                    }}</span
                                                >
                                                <span
                                                    class="text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                                >
                                                    <span
                                                        v-if="
                                                            scholar.course_name
                                                        "
                                                        >{{
                                                            scholar.course_name
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="
                                                            scholar.year_level
                                                        "
                                                        class="ml-1"
                                                        >|
                                                        {{
                                                            /^(1st|2nd|3rd|4th)$/i.test(
                                                                scholar.year_level,
                                                            )
                                                                ? scholar.year_level +
                                                                  ` YEAR`
                                                                : scholar.year_level
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="
                                                            scholar.academic_year
                                                        "
                                                        class="ml-1"
                                                        >|
                                                        {{
                                                            scholar.academic_year
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="scholar.term"
                                                        class="ml-1"
                                                        >|
                                                        {{ scholar.term }}</span
                                                    >
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            No scholars
                                        </div>
                                    </div>
                                </div>

                                <div class="ios-section">
                                    <div
                                        class="ios-card px-4 py-3.5 bg-blue-50 dark:bg-blue-950/30"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <p
                                                class="text-sm font-semibold text-[#1e3a5f] dark:text-blue-200"
                                            >
                                                Total Amount
                                            </p>
                                            <p
                                                class="text-lg font-bold text-blue-600 dark:text-blue-400"
                                            >
                                                {{
                                                    formatAmount(
                                                        calculateTotalAmount(
                                                            selectedVoucher,
                                                        ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="tracking">
                                <div class="ios-section">
                                    <p class="ios-section-label text-compact">
                                        Tracking
                                    </p>
                                    <div class="ios-card px-4 py-3">
                                        <Button
                                            v-if="
                                                selectedVoucher?.fiscal_year &&
                                                selectedVoucher?.obr_no
                                            "
                                            label="View Tracking History"
                                            @click="
                                                fetchTrackingHistory(
                                                    selectedVoucher,
                                                )
                                            "
                                            class="w-full"
                                            severity="info"
                                            :loading="loadingTrackingHistory"
                                        >
                                            <template #icon>
                                                <AppIcon
                                                    name="history"
                                                    :size="14"
                                                />
                                            </template>
                                        </Button>
                                        <p
                                            v-else
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            No OBR info available
                                        </p>
                                    </div>
                                </div>
                            </TabPanel>

                        </TabPanels>
                    </Tabs>
                </div>
                <div
                    class="sticky bottom-0 z-20 -mx-4 w-[calc(100%+2rem)] bg-white/95 dark:bg-[#2a3040]/95 backdrop-blur-md border-t border-gray-200 dark:border-white/10 py-3"
                >
                    <div
                        class="flex w-full items-center gap-1.5 flex-nowrap overflow-x-auto px-3"
                    >
                        <Button
                            label="OBR"
                            @click="generateDocument('OBR')"
                            severity="info"
                            size="small"
                            class="flex-1 whitespace-nowrap !rounded-xl text-xs"
                            v-tooltip.bottom="'Generate OBR'"
                        >
                            <template #icon>
                                <AppIcon name="file-pdf" :size="14" />
                            </template>
                        </Button>
                        <Button
                            :label="getDocumentButtonLabel()"
                            @click="generateDocument(getDocumentType())"
                            severity="success"
                            size="small"
                            class="flex-1 whitespace-nowrap !rounded-xl text-xs"
                            v-tooltip.bottom="'Generate DV/PR'"
                        >
                            <template #icon>
                                <AppIcon name="money-bill" :size="14" />
                            </template>
                        </Button>
                        <Button
                            label="LOS"
                            @click="generateDocument('LOS')"
                            severity="help"
                            size="small"
                            class="flex-1 whitespace-nowrap !rounded-xl text-xs"
                            v-tooltip.bottom="'Generate LOS'"
                        >
                            <template #icon>
                                <AppIcon name="users" :size="14" />
                            </template>
                        </Button>
                    </div>
                </div>
            </div>
        </ViewTransactionModal>

        <!-- Remarks Dialog -->
        <RemarksModal
            :show="showRemarksDialog"
            @update:show="showRemarksDialog = $event"
            :model-value="selectedVoucherForRemarks"
            :is-saving="savingRemarks"
            @save="
                (val) => {
                    remarksForm.remarks = val;
                    saveRemarks();
                }
            "
        />

        <!-- Transaction Status Dialog -->
        <StatusModal
            :show="showStatusDialog"
            @update:show="showStatusDialog = $event"
            :model-value="selectedVoucherForStatus"
            :status-options="obrStatuses"
            :is-saving="savingStatus"
            @save="
                (data) => {
                    statusForm.obr_status = data.status;
                    statusForm.status_updated_at = data.status_updated_at;
                    if (data.remarks !== undefined)
                        statusForm.remarks = data.remarks;
                    statusForm.obr_no = data.obr_no;
                    saveStatus();
                }
            "
        />

        <!-- PDF Preview Modal -->
        <PdfPreviewModal
            :show="showPdfPreview"
            @update:show="showPdfPreview = $event"
            :html-doc="pdfPreviewHtml"
            :title="pdfPreviewTitle"
            :paper-size="pdfPreviewSize"
        />

        <!-- OBR Tracking Dialog -->
        <ObrTrackingModal
            :show="showOBRTrackingDialog"
            @update:show="showOBRTrackingDialog = $event"
            :model-value="selectedVoucherForOBRTracking"
            :is-saving="updatingOBRTracking"
            :is-complete="!!obrTrackingResult"
            @save="
                (data) => {
                    obrTrackingForm.fiscal_year = data.fiscal_year;
                    obrTrackingForm.obr_no = data.obr_no;
                    obrTrackingForm.date_obligated = data.date_obligated;
                    saveOBRTracking();
                }
            "
        />

        <!-- Tracking History Dialog -->
        <TrackingHistoryModal
            :show="showTrackingHistoryDialog"
            @update:show="showTrackingHistoryDialog = $event"
            :tracking-data="trackingHistoryData"
        />
    </AdminLayout>
</template>
