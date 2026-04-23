<script setup>

import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment'
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, onBeforeUnmount, watch, computed, inject } from 'vue';
import { usePermission } from '@/composable/permissions';
import { useFilterManager } from '@/composables/useFilterManager';
import { stripHtml } from '@/utils/sanitize';
import axios from 'axios';

import FloatingDrawer from '@/Components/FloatingDrawer.vue';
import ApplicantFormModal from './Modal/ApplicantFormModal.vue';
import YakapCategoryModal from './Modal/YakapCategoryModal.vue';
import ExportSelectedModal from './Modal/ExportSelectedModal.vue';
import PriorityModal from './Modal/PriorityModal.vue';
import JpmModal from './Modal/JpmModal.vue';
import RequirementsChecklistModal from './Modal/RequirementsChecklistModal.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
import InterviewAssessmentModal from './Modal/InterviewAssessmentModal.vue';
import ProfileReviewModal from './Modal/ProfileReviewModal.vue';
import RemarksModal from './Modal/RemarksModal.vue';
import DeleteConfirmModal from './Modal/DeleteConfirmModal.vue';
import UpdateYakapModal from './Modal/UpdateYakapModal.vue';
import BatchUpdateYakapModal from './Modal/BatchUpdateYakapModal.vue';

import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const { hasPermission, hasRole } = usePermission();

// Inject the refresh function from AdminLayout
const refreshActivityLogs = inject('refreshActivityLogs', null);

const props = defineProps({
    profile: Object,
    profiles: Object,
    profiles_total: [String, Number],
    action: String,
    records: [String, Number],
    filter: Object,
    message: Object,
    sort: {
        date_filed: { type: String },
        last_name: { type: String },
    },
    // Approval workflow props
    approvalStatuses: {
        type: Array,
        default: () => []
    },
    declineReasons: {
        type: Object,
        default: () => ({})
    },
    autoApprovalConfig: {
        type: Object,
        default: () => ({})
    }
});

// Determine initial JPM filter value from props
const getInitialJpmFilter = () => {
    const f = props.filter || {};
    if (f.show_jpm_only === true || f.show_jpm_only === 'true' || f.show_jpm_only === '1') return 'jpm_only';
    if (f.hide_jpm === true || f.hide_jpm === 'true' || f.hide_jpm === '1') return 'hide_jpm';
    if (f.hide_all_tagged === true || f.hide_all_tagged === 'true' || f.hide_all_tagged === '1') return 'hide_all_tagged';
    return 'all';
};

// Filter management via composable
const {
    filters: filter,
    globalFilter,
    records,
    rows,
    first,
    totalRecords,
    showAllFilters,
    search: triggerSearch,
    clear: clearFilter,
    onPageChange,
} = useFilterManager({
    routeName: 'applicants.index',
    props,
    filterPropName: 'filter',
    routerOptions: { replace: true },
    filterDefs: [
        { key: 'name', type: 'text', default: '' },
        { key: 'parent_name', type: 'text', default: '' },
        { key: 'program', type: 'select', default: '', extract: v => v?.shortname?.toLowerCase() },
        { key: 'school', type: 'select', default: '', extract: v => v?.shortname?.toLowerCase() },
        { key: 'course', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'municipality', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'barangay', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'year_level', type: 'select', default: '', extract: v => v?.value?.toLowerCase() },
        { key: 'academic_year', type: 'text', default: '' },
        { key: 'term', type: 'select', default: '', extract: v => v?.value?.toLowerCase() },
        { key: 'yakap_category', type: 'text', default: '' },
        { key: 'priority_level', type: 'text', default: '' },
        { key: 'date_from', type: 'date', default: null },
        { key: 'date_to', type: 'date', default: null },
        { key: 'encoded_from', type: 'date', default: null },
        { key: 'encoded_to', type: 'date', default: null },
        { key: 'remarks', type: 'text', default: '' },
        { key: 'jpm_filter', type: 'text', default: getInitialJpmFilter() },
    ],
    beforeSearch(params, filterValues) {
        // JPM filter -> convert to special backend params
        const jpm = filterValues.jpm_filter;
        delete params.jpm_filter;
        if (jpm === 'jpm_only') params.show_jpm_only = 1;
        else if (jpm === 'hide_jpm') params.hide_jpm = 1;
        else if (jpm === 'hide_all_tagged') params.hide_all_tagged = 1;

        // Include sort params if set
        if (form.sort && Object.values(form.sort).some(v => v)) {
            params.sort = form.sort;
        }
    },
});

// Computed: active filter tags for display
const activeFilterTags = computed(() => {
    const tags = [];
    const f = filter.value;
    const labelMap = {
        name: 'Name',
        parent_name: 'Parent/Guardian',
        program: 'Program',
        school: 'School',
        course: 'Course',
        municipality: 'Municipality',
        barangay: 'Barangay',
        year_level: 'Year Level',
        academic_year: 'Academic Year',
        term: 'Term',
        yakap_category: 'YAKAP',
        priority_level: 'Priority',
        date_from: 'Date Filed From',
        date_to: 'Date Filed To',
        encoded_from: 'Date Encoded From',
        encoded_to: 'Date Encoded To',
        remarks: 'Remarks',
    };
    for (const [key, label] of Object.entries(labelMap)) {
        const val = f[key];
        if (!val) continue;
        let display;
        if (val instanceof Date) {
            display = moment(val).format('MMM DD, YYYY');
        } else if (typeof val === 'object') {
            display = val.shortname || val.name || val.value || JSON.stringify(val);
        } else {
            display = String(val);
        }
        tags.push({ key, label, display });
    }
    return tags;
});

// Auto-trigger search when basic filters change
watch(
    () => [filter.value.program, filter.value.course, filter.value.year_level, filter.value.date_from, filter.value.date_to],
    () => { triggerSearch(); },
);

// Trigger search when records per page changes
watch(records, () => {
    triggerSearch();
});

const form = useForm({
    sort: {
        date_filed: props.sort?.date_filed || "",
        last_name: props.sort?.last_name || "",
        school: props.sort?.school || "",
        course: props.sort?.course || "",
        year_level: props.sort?.year_level || "",
    },
});

const searchInput = ref(null);

// JPM Filter Options
const jpmFilterOptions = [
    { label: 'Show All', value: 'all' },
    { label: 'Show JPM Only', value: 'jpm_only' },
    { label: 'Hide JPM', value: 'hide_jpm' },
    { label: 'Hide All Tagged', value: 'hide_all_tagged' }
];

// YAKAP Category Filter Options
const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' }
];

// Priority Filter Options
const priorityFilterOptions = [
    { label: 'Urgent', value: 'urgent' },
    { label: 'High', value: 'high' },
    { label: 'Normal', value: 'normal' },
];

// Applicant Modal state
// const showApplicantModal = ref(false);
const showApplicationFormModal = ref(false);
const applicationFormMode = ref('create');
// const modalAction = ref('');
const modalProfile = ref(null);

// YAKAP Category Modal state - restore from localStorage (no default to force user selection)
const showYakapCategoryModal = ref(false);
const selectedYakapCategory = ref(localStorage.getItem('selectedYakapCategory') || '');
const selectedYakapLocation = ref(localStorage.getItem('selectedYakapLocation') || '');

// Watch for changes to selectedYakapCategory and persist to localStorage
watch(selectedYakapCategory, (newValue) => {
    if (newValue) localStorage.setItem('selectedYakapCategory', newValue);
});

// Watch for changes to selectedYakapLocation and persist to localStorage
watch(selectedYakapLocation, (newValue) => {
    localStorage.setItem('selectedYakapLocation', newValue);
});

// Update YAKAP Category Modal state
const showUpdateYakapModal = ref(false);
const selectedProfileForYakap = ref(null);
const originalYakapCategory = ref('');
const originalYakapLocation = ref('');
const updateYakapForm = useForm({
    yakap_category: '',
    yakap_location: ''
});

const openUpdateYakapModal = (profile, isNewApplicant = false) => {
    selectedProfileForYakap.value = profile;
    const grants = Array.isArray(profile.scholarshipGrant) ? profile.scholarshipGrant : [];
    const grant = grants.length > 0 ? grants[0] : null;

    if (!grant) {
        // No scholarship record exists, fetch or create one
        axios.get(route('scholarship-record.get-or-create', profile.profile_id))
            .then(response => {
                const createdGrant = response.data;
                // Update the profile with the new grant
                if (!selectedProfileForYakap.value.scholarshipGrant) {
                    selectedProfileForYakap.value.scholarshipGrant = [];
                }
                selectedProfileForYakap.value.scholarshipGrant = [createdGrant];

                // Store original values to detect changes
                originalYakapCategory.value = createdGrant.yakap_category || 'yakap-capitol';
                originalYakapLocation.value = createdGrant.yakap_location || '';
                updateYakapForm.yakap_category = createdGrant.yakap_category || 'yakap-capitol';
                updateYakapForm.yakap_location = createdGrant.yakap_location || '';
                showUpdateYakapModal.value = true;
                if (isNewApplicant) {
                    toast.info('Please set YAKAP category for this applicant.');
                }
            })
            .catch(error => {
                toast.error('Failed to create scholarship record');
                console.error(error);
            });
    } else {
        // Store original values to detect changes
        originalYakapCategory.value = grant.yakap_category || 'yakap-capitol';
        originalYakapLocation.value = grant.yakap_location || '';
        updateYakapForm.yakap_category = grant.yakap_category || 'yakap-capitol';
        updateYakapForm.yakap_location = grant.yakap_location || '';
        showUpdateYakapModal.value = true;
    }
};

const closeUpdateYakapModal = () => {
    showUpdateYakapModal.value = false;
    selectedProfileForYakap.value = null;
    updateYakapForm.reset();
};

const submitUpdateYakap = () => {
    if (!selectedProfileForYakap.value) return;

    const profile = selectedProfileForYakap.value;
    const grants = Array.isArray(profile.scholarshipGrant) ? profile.scholarshipGrant : [];
    const grant = grants.length > 0 ? grants[0] : null;

    if (!grant || !grant.id) {
        // If no grant exists, we need to create one first
        // For now, show error with instruction to create record first
        toast.error('Unable to update: No scholarship record exists. Please create one first.');
        return;
    }

    // Check if values have actually changed
    const categoryChanged = updateYakapForm.yakap_category !== originalYakapCategory.value;
    const locationChanged = updateYakapForm.yakap_location !== originalYakapLocation.value;

    if (!categoryChanged && !locationChanged) {
        closeUpdateYakapModal();
        return;
    }

    // Convert yakap_location object to string (municipality name or school name)
    let yakapLocation = updateYakapForm.yakap_location;
    if (yakapLocation && typeof yakapLocation === 'object') {
        yakapLocation = yakapLocation.name || '';
    }

    // Create a fresh form submission with proper data types
    axios.put(route('scholarship-record.update-yakap', grant.id), {
        yakap_category: updateYakapForm.yakap_category,
        yakap_location: yakapLocation || null
    }).then(response => {
        closeUpdateYakapModal();
        toast.success('YAKAP category updated successfully!');
        refreshApplicationList();
        if (refreshActivityLogs) refreshActivityLogs();
    }).catch(error => {
        toast.error('Failed to update YAKAP category');
        console.error(error.response?.data || error);
    });
}; const handleYakapCategoryChange = () => {
    // Clear location when yakap category is changed
    updateYakapForm.yakap_location = null;
};

const handleBatchYakapCategoryChange = () => {
    // Clear location when yakap category is changed in batch form
    batchYakapForm.yakap_location = null;
};

const openBatchYakapModal = () => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant');
        return;
    }
    batchYakapForm.yakap_category = '';
    batchYakapForm.yakap_location = '';
    showBatchYakapModal.value = true;
};

const closeBatchYakapModal = () => {
    showBatchYakapModal.value = false;
    selectedRows.value = [];
    batchYakapForm.reset();
};

const submitBatchYakapUpdate = () => {
    if (selectedRows.value.length === 0) {
        toast.error('No applicants selected');
        return;
    }

    if (!batchYakapForm.yakap_category) {
        toast.error('Please select a YAKAP category');
        return;
    }

    // Convert yakap_location object to string if needed
    let yakapLocation = batchYakapForm.yakap_location;
    if (yakapLocation && typeof yakapLocation === 'object') {
        yakapLocation = yakapLocation.name || '';
    }

    // Prepare profile IDs for batch update
    const profileIds = selectedRows.value.map(row => row.profile_id);

    // Send batch update request
    axios.post(route('scholarship-record.batch-update-yakap'), {
        profile_ids: profileIds,
        yakap_category: batchYakapForm.yakap_category,
        yakap_location: yakapLocation || null
    }).then(response => {
        closeBatchYakapModal();
        toast.success(`YAKAP category updated for ${profileIds.length} applicant(s)!`);
        refreshApplicationList();
    }).catch(error => {
        toast.error('Failed to update YAKAP categories');
        console.error(error.response?.data || error);
    });
};

const editApplicant = (profile) => {
    modalProfile.value = profile;
    applicationFormMode.value = 'edit';
    showApplicationFormModal.value = true;
}

const viewFullProfile = (profile) => {
    if (!profile?.profile_id) {
        return;
    }

    router.visit(route('scholarship.profile.show', profile.profile_id));
}

const closeModal = () => {
    showApplicationFormModal.value = false;
    modalProfile.value = null;
    // Don't reset yakap values - they persist for next new applicant
    // For UPDATE mode, refresh to show updated data
    // For CREATE mode, refresh is handled by handleApplicantCreated
    // if (applicationFormMode.value === 'edit') {
    //     refreshApplicationList();
    // }
}

const handleApplicantCreated = (newProfile) => {
    // Don't auto-open YAKAP modal - let user manually update if needed via action buttons
    // This prevents accidentally saving default values
    if (newProfile) {
        refreshApplicationList();
    }
}

const openApplicationFormModal = () => {
    modalProfile.value = null;
    applicationFormMode.value = 'create';
    showApplicationFormModal.value = true;
}

const openYakapCategoryModal = () => {
    showYakapCategoryModal.value = true;
}

const handleYakapCategorySelected = (data) => {
    selectedYakapCategory.value = data.category;
    selectedYakapLocation.value = data.location;
    showYakapCategoryModal.value = false;
    // Now open the applicant form modal
    openApplicationFormModal();
}

// const closeApplicantModal = () => {
//     showApplicantModal.value = false;
//     modalProfile.value = null;
//     modalAction.value = '';
// }

// filterList and clearFilter are provided by useFilterManager as triggerSearch and clearFilter



const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
}


onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// triggerSearch and clearFilter are provided by useFilterManager composable

// Combined JPM Tagging & Remarks functionality
const showJpmModal = ref(false);
const selectedProfileForJpm = ref(null);

const openJpmModal = (profile) => {
    if (!hasPermission('jpm.manage')) {
        toast.error('You do not have permission to manage JPM tagging');
        return;
    }
    selectedProfileForJpm.value = profile;
    showJpmModal.value = true;
};

// Remarks Modal functionality
const showRemarksModal = ref(false);
const selectedProfileForRemarks = ref(null);
const remarksForm = useForm({
    remarks: ''
});

const openRemarksModal = (profile) => {
    selectedProfileForRemarks.value = profile;
    remarksForm.remarks = profile.remarks || '';
    showRemarksModal.value = true;
};

const closeRemarksModal = () => {
    showRemarksModal.value = false;
    remarksForm.reset();
};

const submitRemarks = () => {
    remarksForm.post(route('applicants.update-remarks', selectedProfileForRemarks.value.profile_id), {
        onSuccess: () => {
            toast.success('Remarks updated successfully!');
            closeRemarksModal();
            refreshApplicationList();
            if (refreshActivityLogs) refreshActivityLogs();
        },
        onError: () => {
            toast.error('Failed to update remarks');
        }
    });
};


// Persist showJpmColumns state in localStorage
// Only restore from localStorage if user has jpm.view permission
const showJpmColumns = ref(hasPermission('jpm.view') && localStorage.getItem('showJpmColumns') === 'true');

// Watch for changes in showJpmColumns and persist to localStorage
// Only persist if user has jpm.view permission
watch(showJpmColumns, (newValue) => {
    if (hasPermission('jpm.view')) {
        localStorage.setItem('showJpmColumns', newValue.toString());
    } else {
        // Clear from localStorage if permission is revoked
        localStorage.removeItem('showJpmColumns');
        showJpmColumns.value = false;
    }
});

// Add a watcher to reset showJpmColumns if user loses permission during session
const page = usePage();
watch(() => page.props.auth?.user?.permissions, (newPermissions) => {
    if (newPermissions && !newPermissions.includes('jpm.view')) {
        showJpmColumns.value = false;
        localStorage.removeItem('showJpmColumns');
    }
}, { deep: true });

// Computed property to ensure JPM controls are only shown if user ACTUALLY has permission
// This acts as a final safeguard against any stale state
const canShowJpmControls = computed(() => {
    return hasPermission('jpm.view') && showJpmColumns.value === true;
});

// Filter drawer state
const showFilterDrawer = ref(false);

// Academic Year Options
const academicYearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = currentYear; i >= currentYear - 10; i--) {
        years.push({ label: `${i}-${i + 1}`, value: `${i}-${i + 1}` });
    }
    for (let i = currentYear; i >= currentYear - 10; i--) {
        years.push({ label: i.toString(), value: i.toString() });
    }
    return years;
});

// Separate drawer filter model (only applied on submit)
const drawerFilter = ref({});

const drawerFilterKeys = ['parent_name', 'program', 'course', 'school', 'municipality', 'barangay', 'year_level', 'academic_year', 'term', 'yakap_category', 'priority_level', 'date_from', 'date_to', 'encoded_from', 'encoded_to'];

const openDrawer = () => {
    // Snapshot current applied filters into drawer model
    const snapshot = {};
    for (const key of drawerFilterKeys) {
        const val = filter.value[key];
        snapshot[key] = val instanceof Date ? new Date(val) : val;
    }
    drawerFilter.value = snapshot;
    showFilterDrawer.value = true;
};

const applyDrawerFilters = () => {
    for (const key of drawerFilterKeys) {
        filter.value[key] = drawerFilter.value[key];
    }
    triggerSearch();
    showFilterDrawer.value = false;
};

const clearDrawerFilters = () => {
    const dateKeys = ['date_from', 'date_to', 'encoded_from', 'encoded_to'];
    const nullKeys = ['academic_year', 'term'];
    for (const key of drawerFilterKeys) {
        if (dateKeys.includes(key)) drawerFilter.value[key] = null;
        else if (nullKeys.includes(key)) drawerFilter.value[key] = null;
        else drawerFilter.value[key] = '';
    }
};

// Simple view toggle - hide action buttons for easier viewing
const simpleView = ref(localStorage.getItem('simpleView') !== null ? localStorage.getItem('simpleView') === 'true' : true);

// Watch for changes in simpleView and persist to localStorage
watch(simpleView, (newValue) => {
    localStorage.setItem('simpleView', newValue.toString());
});

// Context menu
const contextMenu = ref();
const selectedContextRow = ref(null);
const contextMenuItems = ref([]);

// Build context menu items based on permissions
const buildContextMenu = (rowData) => {
    selectedContextRow.value = rowData;
    const items = [];

    if (hasPermission('applicants.view')) {
        items.push({
            label: 'Review Application',
            icon: 'id-card',
            command: () => openProfileReviewModal(rowData)
        });
        if (hasPermission('scholarships.view')) {
            items.push({
                label: 'View Full Profile',
                icon: 'eye',
                command: () => viewFullProfile(rowData)
            });
        }
        if (hasRole('administrator') || hasRole('program_manager') || hasRole('screening-officer')) {
            items.push({
                label: 'Interview',
                icon: 'comments',
                command: () => handleProfileReviewInterview(rowData)
            });
        }
        items.push({
            label: 'Edit Requirements',
            icon: 'book-check',
            command: () => openRequirementsModal(rowData)
        });
    }

    if (hasPermission('applicants.edit')) {
        items.push(
            {
                label: 'Edit Applicant',
                icon: 'user-edit',
                command: () => editApplicant(rowData)
            },
            {
                label: 'Update YAKAP Category',
                icon: 'heart',
                command: () => openUpdateYakapModal(rowData)
            }
        );
    }

    if (hasPermission('priority.manage')) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Assign Priority',
            icon: 'star',
            command: () => openPriorityModal(rowData)
        });
        if (rowData.priority_level && rowData.priority_level !== 'normal') {
            items.push({
                label: 'Remove Priority',
                icon: 'star-fill',
                command: () => removePriority(rowData)
            });
        }
    }

    if (hasPermission('jpm.view') && showJpmColumns.value) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Edit JPM Tagging',
            icon: 'tags',
            command: () => openJpmModal(rowData),
            disabled: !hasPermission('jpm.manage')
        });
    }

    if (hasPermission('applicants.edit')) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Add/Edit Remarks',
            icon: 'comment',
            command: () => openRemarksModal(rowData)
        });
    }

    if (hasPermission('applicants.delete')) {
        items.push(
            {
                separator: true
            },
            {
                label: 'Delete Applicant',
                icon: 'trash',
                command: () => confirmDeleteApplicant(rowData)
            }
        );
    }

    return items;
};

const onRowContextMenu = (event) => {
    contextMenuItems.value = buildContextMenu(event.data);
    contextMenu.value.show(event.originalEvent);
};

const showRowContextMenu = (event, rowData) => {
    contextMenuItems.value = buildContextMenu(rowData);
    contextMenu.value.show(event);
};

// showAllFilters, globalFilter, first, rows, totalRecords, onPageChange
// are all provided by useFilterManager composable above

// Row selection state
const selectedRows = ref([]);
const showBatchYakapModal = ref(false);
const batchYakapForm = useForm({
    yakap_category: '',
    yakap_location: ''
});

// Memoization cache for expensive computations
const jpmStatusCache = new Map();
const formatMemoCache = new Map();
const applicantMemoCache = new Map();
const jpmStatusMemoCache = new Map();

// Pass raw data directly - transformations happen only on render
const applicants = computed(() => {
    return props.profiles.data || [];
});

// Delete confirmation propertiesr
const showConfirmDeleteModal = ref(false);
const selectedApplicant = ref(null);

// Combined profile and review modal state
const showProfileReviewModal = ref(false);
const selectedApplicantForReview = ref(null);

// Interview assessment modal state
const showInterviewModal = ref(false);
const interviewRecordId = ref(null);
const showAcademicInfoIncompleteDialog = ref(false);
const missingAcademicInfoFields = ref([]);
const academicInfoDialogOffset = ref({ x: 0, y: 0 });
const academicInfoDialogDragStart = ref(null);

const academicInfoDialogStyle = computed(() => ({
    width: 'calc(100vw - 24px)',
    maxWidth: '460px',
    transform: `translate(${academicInfoDialogOffset.value.x}px, ${academicInfoDialogOffset.value.y}px)`,
}));

// Profile menu items for dropdown
const profileMenuItems = ref([
    {
        label: 'Mark as Approved for Review',
        icon: 'check',
        command: () => markAsApproved()
    },
    {
        label: 'Mark as Denied',
        icon: 'x',
        command: () => markAsDenied()
    }
]);

// Priority modal state
const showPriorityModal = ref(false);
const selectedApplicantForPriority = ref(null);

// Requirements Checklist Modal state
const showRequirementsChecklistModal = ref(false);
const selectedApplicantForRequirements = ref(null);

const confirmDeleteApplicant = (applicant) => {
    selectedApplicant.value = applicant;
    showConfirmDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showConfirmDeleteModal.value = false;
    selectedApplicant.value = null;
};

const deleteApplicant = () => {
    if (!selectedApplicant.value) return;

    router.delete(route('applicants.destroy', selectedApplicant.value.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            toast.success('Applicant deleted successfully');
            if (refreshActivityLogs) refreshActivityLogs();
        },
        onError: () => {
            closeDeleteModal();
            toast.error('Failed to delete applicant');
        }
    });
};

// Combined profile and review modal methods
const openProfileReviewModal = (applicant) => {
    selectedApplicantForReview.value = applicant;
    showProfileReviewModal.value = true;
};

const closeProfileReviewModal = () => {
    showProfileReviewModal.value = false;
    selectedApplicantForReview.value = null;
};

const hasAcademicFieldValue = (value) => {
    if (value === null || value === undefined) {
        return false;
    }

    if (typeof value === 'string') {
        return value.trim().length > 0;
    }

    return true;
};

const getMissingAcademicInfoFields = (applicant) => {
    const scholarshipGrant = applicant?.scholarship_grant?.[0] ?? null;

    return [
        {
            label: 'Program',
            value: scholarshipGrant?.program_id ?? scholarshipGrant?.program?.id ?? scholarshipGrant?.program?.shortname ?? scholarshipGrant?.program?.name,
        },
        {
            label: 'School',
            value: scholarshipGrant?.school_id ?? scholarshipGrant?.school?.id ?? scholarshipGrant?.school?.shortname ?? scholarshipGrant?.school?.name,
        },
        {
            label: 'Course',
            value: scholarshipGrant?.course_id ?? scholarshipGrant?.course?.id ?? scholarshipGrant?.course?.shortname ?? scholarshipGrant?.course?.name,
        },
        {
            label: 'Year Level',
            value: scholarshipGrant?.year_level,
        },
        {
            label: 'Term',
            value: scholarshipGrant?.term,
        },
        {
            label: 'Academic Year',
            value: scholarshipGrant?.academic_year,
        },
    ]
        .filter(({ value }) => !hasAcademicFieldValue(value))
        .map(({ label }) => label);
};

const closeAcademicInfoIncompleteDialog = () => {
    showAcademicInfoIncompleteDialog.value = false;
    missingAcademicInfoFields.value = [];
};

const onAcademicInfoDialogDragStart = (event) => {
    if (event.target.closest('button, a')) {
        return;
    }

    academicInfoDialogDragStart.value = {
        x: event.clientX - academicInfoDialogOffset.value.x,
        y: event.clientY - academicInfoDialogOffset.value.y,
    };

    document.addEventListener('pointermove', onAcademicInfoDialogDragMove);
    document.addEventListener('pointerup', onAcademicInfoDialogDragEnd);
};

const onAcademicInfoDialogDragMove = (event) => {
    if (!academicInfoDialogDragStart.value) {
        return;
    }

    academicInfoDialogOffset.value = {
        x: event.clientX - academicInfoDialogDragStart.value.x,
        y: event.clientY - academicInfoDialogDragStart.value.y,
    };
};

const onAcademicInfoDialogDragEnd = () => {
    academicInfoDialogDragStart.value = null;
    document.removeEventListener('pointermove', onAcademicInfoDialogDragMove);
    document.removeEventListener('pointerup', onAcademicInfoDialogDragEnd);
};

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onAcademicInfoDialogDragMove);
    document.removeEventListener('pointerup', onAcademicInfoDialogDragEnd);
});

const resolveInterviewRecordId = (applicant) => {
    if (!applicant) {
        return null;
    }

    if (Array.isArray(applicant.scholarship_grant) && applicant.scholarship_grant.length > 0) {
        return applicant.scholarship_grant[0]?.id || null;
    }

    return applicant.scholarship_grant_id || applicant.record_id || null;
};

const handleProfileReviewInterview = (applicant) => {
    const missingFields = getMissingAcademicInfoFields(applicant);

    if (missingFields.length > 0) {
        missingAcademicInfoFields.value = missingFields;
        showAcademicInfoIncompleteDialog.value = true;
        return;
    }

    selectedApplicantForReview.value = applicant;
    interviewRecordId.value = resolveInterviewRecordId(applicant);

    if (!interviewRecordId.value) {
        toast.error('No scholarship record selected for interview.');
        return;
    }

    showInterviewModal.value = true;
};

const handleProfileReviewEdit = (applicant) => {
    editApplicant(applicant);
};

const onInterviewSubmitted = () => {
    closeProfileReviewModal();
    refreshApplicationList();
};

const handleApprovalAction = () => {
    closeProfileReviewModal();
    toast.success('Application reviewed successfully');
    refreshApplicationList();
};

const refreshApplicationList = () => {
    // Clear memoization caches before refresh
    applicantMemoCache.clear();
    jpmStatusMemoCache.clear();
    formatMemoCache.clear();

    router.reload({
        only: ['profiles'],
        preserveState: true,
        preserveScroll: true,
    });
};

// Priority modal functions
const openPriorityModal = (applicant) => {
    selectedApplicantForPriority.value = applicant;
    showPriorityModal.value = true;
};

const closePriorityModal = () => {
    showPriorityModal.value = false;
    selectedApplicantForPriority.value = null;
};

const handlePrioritySuccess = () => {
    closePriorityModal();
    // Toast is shown in the modal after successful API response
    // Don't show another toast here to avoid duplicates
};

const removePriority = (applicant) => {
    if (!applicant?.profile_id) return;

    router.delete(route("applicants.remove-priority", applicant.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Priority level removed successfully!');
            refreshApplicationList();
        },
        onError: () => {
            toast.error('Failed to remove priority level.');
        }
    });
};

// Requirements Checklist Modal functions
const openRequirementsModal = (applicant) => {
    selectedApplicantForRequirements.value = applicant;
    showRequirementsChecklistModal.value = true;
};

const closeRequirementsModal = () => {
    showRequirementsChecklistModal.value = false;
    selectedApplicantForRequirements.value = null;
};

// Export Selected Rows Modal state
const showExportModal = ref(false);
const exportPaperSize = ref('A4');
const exportOrientation = ref('landscape');
const exportReportType = ref('list');

const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal/Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const reportTypeOptions = [
    { label: 'Detailed List', value: 'list' },
    { label: 'Summary', value: 'summary' }
];

const openExportModal = () => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant');
        return;
    }
    showExportModal.value = true;
};

const closeExportModal = () => {
    showExportModal.value = false;
};

const exportSelectedRows = (format) => {
    if (selectedRows.value.length === 0) {
        toast.error('No applicants selected');
        return;
    }

    // Build query parameters with selected profile IDs
    const profileIds = selectedRows.value.map(row => row.profile_id).join(',');
    const params = new URLSearchParams({
        profile_ids: profileIds,
        report_type: exportReportType.value,
        paper_size: exportPaperSize.value,
        orientation: exportOrientation.value
    });

    if (format === 'pdf') {
        window.open(`/api/export-selected/pdf?${params.toString()}`, '_blank');
    } else if (format === 'excel') {
        window.open(`/api/export-selected/excel?${params.toString()}`, '_blank');
    }

    closeExportModal();
    toast.success(`Exporting ${selectedRows.value.length} applicant(s) as ${format.toUpperCase()}...`);
};

// Utility functions for applicant data formatting (memoized)
const getApplicantInitials = (applicant) => {
    if (!applicant) return '';

    const cacheKey = `initials_${applicant.profile_id}`;
    if (formatMemoCache.has(cacheKey)) {
        return formatMemoCache.get(cacheKey);
    }

    const firstInitial = applicant.first_name?.charAt(0) || '';
    const lastInitial = applicant.last_name?.charAt(0) || '';
    const result = `${firstInitial}${lastInitial}`.toUpperCase();

    formatMemoCache.set(cacheKey, result);
    return result;
};

const getApplicantFullName = (applicant) => {
    if (!applicant) return '';

    const cacheKey = `fullname_${applicant.profile_id}`;
    if (formatMemoCache.has(cacheKey)) {
        return formatMemoCache.get(cacheKey);
    }

    const parts = [
        applicant.last_name,
        ',',
        applicant.first_name,
        applicant.middle_name,
        applicant.extension_name
    ].filter(Boolean);

    const result = parts.join(' ').replace(' ,', ',');
    formatMemoCache.set(cacheKey, result);
    return result;
};

// Get JPM status for tag display with member details (memoized)
const getJpmStatus = (profile) => {
    if (!profile) return null;

    // Use profile_id as cache key
    const cacheKey = profile.profile_id;
    if (jpmStatusMemoCache.has(cacheKey)) {
        return jpmStatusMemoCache.get(cacheKey);
    }

    const members = [];
    if (profile.is_jpm_member) members.push('Applicant');
    if (profile.is_father_jpm) members.push('Father');
    if (profile.is_mother_jpm) members.push('Mother');
    if (profile.is_guardian_jpm) members.push('Guardian');

    let result = null;
    if (members.length > 0) {
        result = {
            status: 'member',
            members: members
        };
    } else if (profile.is_not_jpm) {
        result = {
            status: 'not_member',
            members: []
        };
    } else if (profile.jpm_remarks && profile.jpm_remarks.trim() !== '') {
        result = {
            status: 'not_member',
            members: []
        };
    }

    jpmStatusMemoCache.set(cacheKey, result);
    return result;
};

// Get tag severity for JPM status
const getJpmTagSeverity = (statusObj) => {
    if (!statusObj) return 'secondary';
    switch (statusObj.status) {
        case 'member': return 'success';
        case 'not_member': return 'warn';
        default: return 'secondary';
    }
};

// Get tag label for JPM status
const getJpmTagLabel = (statusObj) => {
    if (!statusObj) return '';
    switch (statusObj.status) {
        case 'member': return 'Member';
        case 'not_member': return 'Not Member';
        default: return '';
    }
};

// Get member details text
const getJpmMemberDetails = (statusObj) => {
    if (!statusObj || statusObj.status !== 'member' || !statusObj.members.length) return '';
    return statusObj.members.join(', ');
};

// Memoized date formatter to avoid moment() calls on every render
const dateFormatterCache = new Map();
const formatDateFiled = (date) => {
    if (!date) return '-';
    const cacheKey = date.toString();
    if (!dateFormatterCache.has(cacheKey)) {
        dateFormatterCache.set(cacheKey, moment(date).format('MMM DD, YYYY'));
    }
    return dateFormatterCache.get(cacheKey);
};

const getApplicantFullAddress = (applicant) => {
    if (!applicant) return '';
    const parts = [
        applicant.barangay,
        applicant.municipality,
        applicant.province
    ].filter(Boolean);

    return parts.join(', ') || 'N/A';
};

// Priority helper functions
const getPrioritySeverity = (priority) => {
    switch (priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warn';
        case 'normal': return 'info';
        default: return 'secondary';
    }
};

const formatPriorityName = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return moment(date).format('MMM DD, YYYY');
};

// Truncate text for display with tooltip support
const truncateText = (text, maxLength = 80) => {
    if (!text) return '';
    const plainText = stripHtml(text);
    if (plainText.length <= maxLength) return plainText;
    return plainText.substring(0, maxLength) + '...';
};

</script>

<template>

    <Head title="Applicants" />
    <AdminLayout>
        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Applicants Management</h1>
                            <p class="text-sm short:text-xs text-gray-600">Manage scholarship applicants and their
                                profiles</p>
                        </div>
                    </div>
                </template>

                <template #end>
                    <div class="flex gap-3 items-center">
                        <!-- JPM Controls -->
                        <template v-if="hasPermission('jpm.view')">
                            <div class="flex items-center justify-between gap-4 flex-1">
                                <label class="text-sm text-gray-600">JPM Tagging</label>
                                <ToggleSwitch v-model="showJpmColumns"
                                    style="transform: scale(0.75); transform-origin: right center;" />
                            </div>

                            <div class="flex items-center gap-2">
                                <Select v-model="filter.jpm_filter" :options="jpmFilterOptions" optionLabel="label"
                                    size="small" optionValue="value" placeholder="Select filter" class="w-40"
                                    inputId="jpmFilter" />
                            </div>

                            <Divider layout="vertical" class="h-6" />
                        </template>

                        <Button @click="openYakapCategoryModal" v-if="hasPermission('applicants.create')"
                            severity="success" text rounded v-tooltip.bottom="'Add New Applicant'">
                            <template #icon>
                                <AppIcon name="user-plus" :size="24" />
                            </template>
                        </Button>


                        <!-- <Button as="a" label="Existing" icon="user"
                            v-if="hasPermission('applicants.create') && !hasRole('user')"
                            :href="route('applicants.index', { action: 'add-existing' })" severity="secondary"
                            size="small" /> -->
                    </div>
                </template>
            </Toolbar>




            <!-- Filter Drawer -->
            <FloatingDrawer v-model:visible="showFilterDrawer" header="All Filters" position="right"
                class="!w-[calc(100vw-1rem)] sm:!w-[min(600px,calc(100vw-1rem))] !max-w-[calc(100vw-1rem)]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                        <ProgramSelect v-model="drawerFilter.program" label="shortname"
                            custom-placeholder="All Programs" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                        <CourseSelect v-model="drawerFilter.course" label="name" custom-placeholder="All Courses"
                            size="small" class="w-full" :scholarship-program-id="drawerFilter.program?.id" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                        <SchoolSelect v-model="drawerFilter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" class="w-full" :multiple="false" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                        <YearLevelSelect v-model="drawerFilter.year_level" custom-placeholder="All Year Levels"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                        <Select v-model="drawerFilter.academic_year" :options="academicYearOptions" optionLabel="label"
                            optionValue="value" placeholder="All Academic Years" size="small" class="w-full"
                            showClear />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Term</label>
                        <TermSelect v-model="drawerFilter.term" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                        <MunicipalitySelect v-model="drawerFilter.municipality" custom-placeholder="All Municipalities"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Barangay</label>
                        <BarangaySelect v-model="drawerFilter.barangay" :municipality-id="drawerFilter.municipality?.id"
                            custom-placeholder="All Barangays" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">YAKAP Category</label>
                        <Select v-model="drawerFilter.yakap_category" :options="yakapCategoryOptions"
                            optionLabel="label" optionValue="value" placeholder="All Categories" size="small"
                            class="w-full" showClear />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Priority</label>
                        <Select v-model="drawerFilter.priority_level" :options="priorityFilterOptions"
                            optionLabel="label" optionValue="value" placeholder="All Priorities" size="small"
                            class="w-full" showClear />
                    </div>
                    <div class="flex flex-col col-span-2">
                        <label class="text-xs font-medium text-gray-600 mb-1">Date Filed</label>
                        <div class="flex gap-2">
                            <InputGroup>
                                <InputGroupAddon><span class="text-xs">From</span></InputGroupAddon>
                                <DatePicker v-model="drawerFilter.date_from" size="small" class="w-full"
                                    date-format="M dd, yy" showIcon iconDisplay="input" />
                            </InputGroup>
                            <InputGroup>
                                <InputGroupAddon><span class="text-xs">To</span></InputGroupAddon>
                                <DatePicker v-model="drawerFilter.date_to" size="small" class="w-full"
                                    date-format="M dd, yy" showIcon iconDisplay="input" />
                            </InputGroup>
                        </div>
                    </div>
                    <div class="flex flex-col col-span-2">
                        <label class="text-xs font-medium text-gray-600 mb-1">Date Encoded</label>
                        <div class="flex gap-2">
                            <InputGroup>
                                <InputGroupAddon><span class="text-xs">From</span></InputGroupAddon>
                                <DatePicker v-model="drawerFilter.encoded_from" size="small" class="w-full"
                                    date-format="M dd, yy" showIcon iconDisplay="input" />
                            </InputGroup>
                            <InputGroup>
                                <InputGroupAddon><span class="text-xs">To</span></InputGroupAddon>
                                <DatePicker v-model="drawerFilter.encoded_to" size="small" class="w-full"
                                    date-format="M dd, yy" showIcon iconDisplay="input" />
                            </InputGroup>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <AppButton severity="secondary" outlined size="small" icon="history" label="Clear"
                        @click="clearDrawerFilters" />
                    <AppButton label="Apply" icon="filter" severity="info" size="small" @click="applyDrawerFilters" />
                </div>
            </FloatingDrawer>

            <!-- Applicants DataTable -->
            <Panel class="!rounded-4xl overflow-hidden mt-8">

                <!-- Info Bar -->
                <div
                    class="flex items-center justify-between gap-4 mb-4 p-3 bg-gray-50 dark:bg-[#1e242b] rounded-4xl -mt-2">
                    <div class="flex gap-4 max-w-md">
                        <InputGroup>
                            <InputGroupAddon>
                                <AppIcon name="search" :size="16" class="text-gray-400" />
                            </InputGroupAddon>
                            <InputText v-model="globalFilter" placeholder="Type name, remarks etc.." size="small"
                                @keyup.enter="triggerSearch()" />
                        </InputGroup>
                        <AppButton icon="filter" severity="warn" text rounded @click="openDrawer()"
                            v-tooltip.bottom="'More Filters'" />
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="ml-auto flex items-center gap-2">
                            <RecordsSelect v-model="records" label="label" class="w-28" size="small" />
                            <span class="text-sm text-gray-600">/ <strong>{{ totalRecords }}</strong></span>
                        </div>
                        <AppButton :icon="simpleView ? 'table' : 'list'" severity="secondary" rounded outlined
                            size="small"
                            v-tooltip.bottom="simpleView ? 'Switch to Detailed View' : 'Switch to Simple View'"
                            @click="simpleView = !simpleView" />
                    </div>
                </div>




                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <div class="flex flex-col">
                        <ProgramSelect v-model="filter.program" label="shortname" custom-placeholder="All Programs"
                            size="small" />
                    </div>
                    <div class="flex flex-col">
                        <CourseSelect v-model="filter.course" label="name" custom-placeholder="All Courses" size="small"
                            :scholarship-program-id="filter.program?.id" />
                    </div>
                    <div class="flex flex-col">
                        <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                            size="small" />
                    </div>
                    <div class="flex gap-3">
                        <InputGroup class="!w-52">
                            <InputGroupAddon>
                                <span class="text-xs">Filed From</span>
                            </InputGroupAddon>
                            <DatePicker v-model="filter.date_from" size="small" class="w-full" date-format="M dd, yy"
                                showIcon iconDisplay="input" />
                        </InputGroup>
                        <InputGroup class="!w-52">
                            <InputGroupAddon>
                                <span class="text-xs">Filed To</span>
                            </InputGroupAddon>
                            <DatePicker v-model="filter.date_to" size="small" class="w-full" date-format="M dd, yy"
                                showIcon iconDisplay="input" />
                        </InputGroup>
                    </div>
                    <AppButton v-if="activeFilterTags.length" icon="times" severity="danger" text rounded size="small"
                        @click="clearFilter" v-tooltip.bottom="'Clear Filters'" />
                </div>

                <!-- Active Filter Tags -->
                <div v-if="activeFilterTags.length" class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs text-gray-500">Active Filters:</span>
                    <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                        <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    </Tag>
                </div>

                <!-- Batch Update Section -->
                <div v-if="selectedRows.length > 0"
                    class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-4xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <AppIcon name="exclamation-circle" :size="20" class="text-yellow-600" />
                        <div>
                            <div class="font-semibold text-yellow-900">{{ selectedRows.length }} applicant(s)
                                selected
                            </div>
                            <div class="text-sm text-yellow-700">Use batch update to change YAKAP category for all
                                selected
                                applicants</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <AppButton v-if="hasPermission('applicants.export')" icon="download" @click="openExportModal"
                            severity="info" label="Export Selected" outlined rounded />
                        <AppButton v-if="hasPermission('applicants.edit')" icon="pencil" @click="openBatchYakapModal"
                            severity="warning" label="Batch Update YAKAP" rounded />
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

                <!-- Table View -->
                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="applicants" stripedRows
                    showGridlines responsiveLayout="scroll" :emptyMessage="'No applicants to display'" :lazy="true"
                    paginator :rows="rows" :totalRecords="totalRecords" :first="first" @page="onPageChange"
                    paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                    v-model:selection="selectedRows" dataKey="profile_id"
                    :rowsPerPageOptions="[10, 25, 50, 100, 250, 500]" :scrollable="true"
                    @row-contextmenu="onRowContextMenu" contextMenu>

                    <!-- Selection Column -->
                    <Column selectionMode="multiple" :exportable="false" style="width: 3rem"></Column>

                    <!-- Date Filed Column -->
                    <Column header="Date Filed" style="width: 110px">
                        <template #body="slotProps">
                            <div class="text-xs font-medium">
                                {{ formatDateFiled(slotProps.data.date_filed) }}
                            </div>
                        </template>
                    </Column>

                    <!-- Sequence Number & Name Column -->
                    <Column header="Applicant" style="min-width: 300px">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-2">
                                <!-- <span class="text-gray-400 text-sm">#{{
                                        slotProps.data.sequence_number_by_school_course || '-' }}</span> -->
                                <div class="flex gap-2 items-start w-full">

                                    <div class="flex flex-col gap-1 flex-1 min-w-0">
                                        <div class="flex gap-1 items-center w-full">
                                            <div v-if="slotProps.data.gender" class="flex-shrink-0">
                                                <img v-if="slotProps.data.gender == 'M'" src="/images/male-avatar.png"
                                                    alt="avatar" class="rounded-full w-6 h-6" />
                                                <img v-if="slotProps.data.gender == 'F'" src="/images/female-avatar.png"
                                                    alt="avatar" class="rounded-full w-6 h-6" />
                                            </div>
                                            <div v-else class="flex-shrink-0">
                                                <div
                                                    class="ml-1 w-5 h-5 rounded-full bg-gray-400 flex items-center justify-center text-xs text-white font-semibold">
                                                    {{ getApplicantInitials(slotProps.data) }}
                                                </div>
                                            </div>
                                            <div class="font-semibold text-sky-700 text-sm flex-1 min-w-0 cursor-pointer hover:text-sky-800 underline underline-offset-4 transition-all"
                                                @click="openProfileReviewModal(slotProps.data)">
                                                {{ slotProps.data.last_name }}, {{ slotProps.data.first_name }} {{
                                                    slotProps.data.middle_name || '' }} {{
                                                    slotProps.data.extension_name || '' }}
                                            </div>
                                            <!-- Priority Badge (visible in simple view) - Fixed position on the right -->
                                            <div v-if="simpleView && slotProps.data.priority_level"
                                                class="flex-shrink-0 ml-2 flex items-center justify-center"
                                                v-tooltip.top="formatPriorityName(slotProps.data.priority_level) + (slotProps.data.priority_reason ? ': ' + slotProps.data.priority_reason : '')">
                                                <!-- Star for High and Urgent -->
                                                <AppIcon
                                                    v-if="slotProps.data.priority_level === 'urgent' || slotProps.data.priority_level === 'high'"
                                                    name="star-fill" :class="{
                                                        'text-red-500': slotProps.data.priority_level === 'urgent',
                                                        'text-orange-500': slotProps.data.priority_level === 'high'
                                                    }" :size="14" />
                                                <!-- Circle for Normal -->
                                                <div v-else-if="slotProps.data.priority_level === 'normal'"
                                                    class="w-3 h-3 rounded-full bg-blue-500"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex items-center gap-1"
                                        v-tooltip.bottom="'Program #' + (slotProps.data.sequence_number || '-')">
                                        <AppIcon name="bookmark" :size="11" class="text-indigo-500" />
                                        <span class="text-xs font-bold text-gray-600">#{{
                                            slotProps.data.sequence_number || '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1"
                                        v-tooltip.bottom="'Course #' + (slotProps.data.sequence_number_by_course || '-')">
                                        <AppIcon name="book-open" :size="11" class="text-teal-500" />
                                        <span class="text-xs font-bold text-gray-600">#{{
                                            slotProps.data.sequence_number_by_course || '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1"
                                        v-tooltip.bottom="'School #' + (slotProps.data.sequence_number_by_school_course || '-')">
                                        <AppIcon name="building-2" :size="11" class="text-amber-500" />
                                        <span class="text-xs font-bold text-gray-600">#{{
                                            slotProps.data.sequence_number_by_school_course || '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1"
                                        v-tooltip.bottom="'Daily #' + (slotProps.data.daily_sequence_number || '-')">
                                        <AppIcon name="calendar" :size="11" class="text-gray-400" />
                                        <span class="text-xs font-bold text-gray-600">#{{
                                            slotProps.data.daily_sequence_number || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Academic Column -->
                    <Column header="Academic" style="min-width: 200px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.scholarship_grant[0]" class="text-xs flex flex-col gap-0.5">
                                <div class="font-medium" v-if="slotProps.data.scholarship_grant[0]?.school">
                                    {{ slotProps.data.scholarship_grant[0].school.shortname }}
                                </div>
                                <div v-if="slotProps.data.scholarship_grant[0]?.course">
                                    {{ slotProps.data.scholarship_grant[0].course.shortname }}
                                </div>
                                <div class="text-gray-600" v-if="slotProps.data.scholarship_grant[0]?.year_level">
                                    Year: {{ slotProps.data.scholarship_grant[0].year_level }}
                                </div>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </Column>

                    <!-- Address Column -->
                    <Column header="Address" style="min-width: 150px">
                        <template #body="slotProps">

                            <div class="ml-1 text-xs  mt-0.5 flex items-center gap-3 "
                                v-if="slotProps.data.municipality">
                                <AppIcon name="map" :size="12" class="text-gray-500" />
                                <span>{{ slotProps.data.municipality }}{{ slotProps.data.barangay ? `,
                                    ${slotProps.data.barangay}` : '' }}</span>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                            <div class="ml-1 text-xs  mt-0.5 flex items-center gap-3 ">
                                <AppIcon name="phone" :size="12" class="text-gray-500" />
                                <span>{{ slotProps.data.contact_no || 'No contact no.' }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Parent/Guardian Column (only visible when showJpmColumns is enabled) -->
                    <Column header="Parent/Guardian" v-if="hasPermission('jpm.view') && showJpmColumns"
                        style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="text-xs space-y-1">
                                <div v-if="slotProps.data.father_name">
                                    <span class="font-medium">{{ slotProps.data.father_name }}</span>
                                    <span class="text-gray-500 italic"> (father)</span>
                                </div>
                                <div v-if="slotProps.data.mother_name">
                                    <span class="font-medium">{{ slotProps.data.mother_name }}</span>
                                    <span class="text-gray-500 italic"> (mother)</span>
                                </div>
                                <div v-if="slotProps.data.guardian_name">
                                    <span class="font-medium">{{ slotProps.data.guardian_name }}</span>
                                    <span class="text-gray-500 italic"> (guardian)</span>
                                </div>
                                <span
                                    v-if="!slotProps.data.father_name && !slotProps.data.mother_name && !slotProps.data.guardian_name"
                                    class="text-gray-400">-</span>
                            </div>
                        </template>
                    </Column>

                    <!-- JPM Status Column (only visible when showJpmColumns is enabled) -->
                    <Column header="JPM" v-if="hasPermission('jpm.view') && showJpmColumns" style="width: 80px">
                        <template #body="slotProps">
                            <div class="flex items-center justify-center">
                                <div v-if="getJpmStatus(slotProps.data)?.status === 'member'"
                                    v-tooltip.top="'JPM Member: ' + getJpmMemberDetails(getJpmStatus(slotProps.data))">
                                    <AppIcon name="check-circle" :size="18" class="text-green-500" />
                                </div>
                                <div v-else-if="getJpmStatus(slotProps.data)?.status === 'not_member'"
                                    v-tooltip.top="'Not a JPM Member'">
                                    <AppIcon name="times-circle" :size="18" class="text-orange-400" />
                                </div>
                                <span v-else class="text-gray-300">-</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Remarks Column (hidden when JPM columns visible) -->
                    <Column header="Remarks" v-if="!showJpmColumns" style="max-width: 200px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.remarks" v-safe-html="slotProps.data.remarks"
                                class="text-xs prose prose-xs max-w-none line-clamp-3"></div>
                            <span v-else class="text-xs text-gray-400">-</span>
                        </template>
                    </Column>

                    <Column header="Created" style="min-width: 160px">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-1 text-[11px]">
                                <div v-if="slotProps.data.created_by" class="text-slate-700">
                                    {{ slotProps.data.created_by.name }}
                                </div>
                                <div v-if="slotProps.data.created_at" class="text-slate-400">
                                    {{ formatDateFiled(slotProps.data.created_at) }}
                                </div>
                                <span v-if="!slotProps.data.created_by && !slotProps.data.created_at"
                                    class="text-gray-400">-</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Updated" style="min-width: 160px">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-1 text-[11px]">
                                <div v-if="slotProps.data.updated_by" class="text-slate-700">
                                    {{ slotProps.data.updated_by.name }}
                                </div>
                                <div v-if="slotProps.data.updated_at" class="text-slate-400">
                                    {{ formatDateFiled(slotProps.data.updated_at) }}
                                </div>
                                <span v-if="!slotProps.data.updated_by && !slotProps.data.updated_at"
                                    class="text-gray-400">-</span>
                            </div>
                        </template>
                    </Column>

                    <!-- JPM Actions Column (visible when JPM columns enabled) -->
                    <Column header="Tagging" v-if="hasPermission('jpm.view') && showJpmColumns"
                        style="min-width: 150px">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-2 items-center">
                                <AppButton @click="openJpmModal(slotProps.data)" rounded icon="tags" severity="info"
                                    size="small" outlined :disabled="!hasPermission('jpm.manage')"
                                    v-tooltip.top="'Edit JPM tagging and remarks'" />

                                <!-- Quick preview of remarks if exists -->
                                <div v-if="slotProps.data.jpm_remarks" class="text-xs text-gray-600 italic truncate">
                                    "{{ slotProps.data.jpm_remarks }}"
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Priority Column -->
                    <Column header="Priority" style="width: 80px"
                        v-if="hasPermission('priority.manage') && !simpleView">
                        <template #body="slotProps">
                            <div class="flex items-center justify-center">
                                <div v-if="slotProps.data.priority_level === 'urgent'"
                                    v-tooltip.top="'Urgent' + (slotProps.data.priority_reason ? ': ' + slotProps.data.priority_reason : '')">
                                    <AppIcon name="exclamation-triangle" :size="18" class="text-red-500" />
                                </div>
                                <div v-else-if="slotProps.data.priority_level === 'high'"
                                    v-tooltip.top="'High' + (slotProps.data.priority_reason ? ': ' + slotProps.data.priority_reason : '')">
                                    <AppIcon name="star-fill" :size="18" class="text-orange-500" />
                                </div>
                                <div v-else v-tooltip.top="'Normal'">
                                    <AppIcon name="minus" :size="14" class="text-gray-300" />
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Actions Column -->
                    <Column header="Actions" style="width: 96px" v-if="!simpleView">
                        <template #body="slotProps">
                            <div class="flex items-center justify-center gap-1">
                                <AppButton v-if="hasPermission('scholarships.view')" icon="eye" text severity="info"
                                    @click="viewFullProfile(slotProps.data)"
                                    v-tooltip.top="'View full profile'" />
                                <AppButton icon="ellipsis-vertical" text severity="secondary"
                                    @click="(event) => showRowContextMenu(event, slotProps.data)"
                                    v-tooltip.top="'More actions'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </Panel>
        </div>

        <!-- Combined JPM Tagging & Remarks Modal -->
        <!-- JPM Modal -->
        <JpmModal :show="showJpmModal" :profile="selectedProfileForJpm" @update:show="showJpmModal = $event" />

        <!-- Remarks Modal -->
        <RemarksModal :show="showRemarksModal" :profile="selectedProfileForRemarks"
            :refreshActivityLogs="refreshActivityLogs" @update:show="showRemarksModal = $event"
            @success="refreshApplicationList" />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmModal :show="showConfirmDeleteModal" :applicant="selectedApplicant"
            :refreshActivityLogs="refreshActivityLogs" @update:show="showConfirmDeleteModal = $event"
            @deleted="refreshApplicationList" />

        <!-- Modals -->
        <!-- Integrated Profile & Review Modal -->
        <ProfileReviewModal v-model:visible="showProfileReviewModal" :applicant="selectedApplicantForReview"
            :applicants="applicants" @interview="handleProfileReviewInterview" @edit-profile="handleProfileReviewEdit"
            @edit-requirements="openRequirementsModal"
            @closed="closeProfileReviewModal" />

        <Dialog :visible="showAcademicInfoIncompleteDialog" modal
            @update:visible="val => !val && closeAcademicInfoIncompleteDialog()"
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal academic-info-ios-modal" :style="academicInfoDialogStyle">
                    <div class="ios-nav-bar" @pointerdown="onAcademicInfoDialogDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="closeAcademicInfoIncompleteDialog">
                            <AppIcon name="times" :size="14" />
                        </button>
                        <span class="ios-nav-title">Academic Information</span>

                    </div>

                    <div class="ios-body">
                        <div class="ios-section" style="margin-top: 16px;">
                            <div class="ios-card">
                                <div class="ios-row academic-info-warning-row"
                                    style="align-items: flex-start; gap: 12px;">
                                    <div class="academic-info-warning-icon">
                                        <AppIcon name="exclamation-triangle" :size="20" />
                                    </div>
                                    <div class="academic-info-warning-copy">
                                        <div class="academic-info-warning-title">Interview cannot start yet</div>
                                        <div class="academic-info-warning-text">
                                            Complete the applicant's academic information first, then try the interview
                                            action again.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">Missing fields</div>
                            <div class="ios-card">
                                <div v-for="(field, index) in missingAcademicInfoFields" :key="field" class="ios-row">
                                    <span class="text-red-500 mono">{{ field }}</span>
                                </div>
                            </div>
                            <div class="ios-section-footer">
                                Update the academic record with all required details before opening the interview
                                assessment.
                            </div>
                        </div>

                        <div style="height: 20px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- YAKAP Category Modal - for selecting category when creating new applicant -->
        <YakapCategoryModal v-model:visible="showYakapCategoryModal" @selected="handleYakapCategorySelected" />

        <!-- Update YAKAP Category Modal -->
        <UpdateYakapModal :show="showUpdateYakapModal" :profile="selectedProfileForYakap"
            :refreshActivityLogs="refreshActivityLogs" @update:show="showUpdateYakapModal = $event"
            @success="refreshApplicationList" />

        <!-- Interview Assessment Modal -->
        <InterviewAssessmentModal v-model="showInterviewModal" :applicant="selectedApplicantForReview"
            :recordId="interviewRecordId" @submitted="onInterviewSubmitted" />

        <!-- Batch Update YAKAP Category Modal -->
        <BatchUpdateYakapModal :show="showBatchYakapModal" :selectedRows="selectedRows"
            :refreshActivityLogs="refreshActivityLogs"
            @update:show="val => { showBatchYakapModal = val; if (!val) selectedRows = []; }"
            @success="() => { refreshApplicationList(); selectedRows = []; }" />

        <!-- Application Form Modal - for creating/editing applicants -->
        <ApplicantFormModal v-model:visible="showApplicationFormModal" :mode="applicationFormMode"
            :profile="modalProfile" :yakap-category="selectedYakapCategory" :yakap-location="selectedYakapLocation"
            @success="closeModal" @applicant-created="handleApplicantCreated" />

        <!-- Applicant Profile Modal - for editing existing applicants (keeping for backward compatibility) -->
        <!-- <ApplicantProfileModal v-if="(props.action == 'update') || showApplicantModal"
            :action="showApplicantModal ? modalAction : props.action"
            :profile="showApplicantModal ? modalProfile : props.profile" :is-inline-modal="showApplicantModal"
            @close="closeApplicantModal" /> -->

        <!-- Priority Modal -->
        <PriorityModal :show="showPriorityModal" :applicant="selectedApplicantForPriority"
            @update:show="showPriorityModal = $event" @success="handlePrioritySuccess" />

        <!-- Requirements Checklist Modal -->
        <RequirementsChecklistModal :visible="showRequirementsChecklistModal"
            :applicant="selectedApplicantForRequirements" @update:visible="showRequirementsChecklistModal = $event" />

        <!-- Export Selected Rows Modal -->
        <ExportSelectedModal :show="showExportModal" :selected-rows="selectedRows"
            @update:show="showExportModal = $event" />
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

:deep(.p-datepicker .p-inputtext) {
    border-radius: 1rem;
}

:deep(.p-inputgroup) {
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid var(--p-inputtext-border-color, #d1d5db);
}

:deep(.p-inputgroup:focus-within) {
    border-color: var(--p-inputtext-focus-border-color, #6366f1);
}

:deep(.p-inputgroup .p-inputgroupaddon) {
    border-radius: 0;
    border: none;
}

:deep(.p-inputgroup .p-inputtext) {
    border-radius: 0;
    border: none;
}

:deep(.p-inputgroup .p-datepicker) {
    border-radius: 0;
    flex: 1;
}

:deep(.p-inputgroup .p-datepicker .p-inputtext) {
    border-radius: 0;
    border: none;
}

/* Ensure the DatePicker icon button inside InputGroup has no rounding */
:deep(.p-inputgroup .p-datepicker .p-datepicker-input-icon-container) {
    border-radius: 0;
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
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
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
    color: #374151;
    font-weight: 600;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
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

.ios-section-footer {
    font-size: 13px;
    color: #6D6D72;
    padding: 6px 16px 0;
    line-height: 1.3;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
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
    color: #8E8E93;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.academic-info-ios-modal {
    max-width: calc(100vw - 24px);
}

.academic-info-warning-row {
    padding: 12px 16px;
}

.academic-info-warning-icon {
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #FEE2E2;
    color: #DC2626;
}

.academic-info-warning-copy {
    min-width: 0;
}

.academic-info-warning-title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 4px;
}

.academic-info-warning-text {
    font-size: 13px;
    color: #6B7280;
    line-height: 1.45;
}

.academic-info-missing-field {
    font-size: 14px;
    color: #111827;
    font-weight: 600;
    text-align: right;
}

.dark .ios-modal {
    background: #111827 !important;
}

.dark .ios-nav-bar {
    background: #111827 !important;
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

.dark .ios-nav-title {
    color: #d1d5db !important;
}

.dark .ios-nav-cancel {
    color: #9ca3af !important;
}

.dark .ios-nav-action {
    color: #d1d5db !important;
}

.dark .ios-section-label,
.dark .ios-section-footer,
.dark .ios-row-label,
.dark .academic-info-warning-text {
    color: #9ca3af !important;
}

.dark .ios-card {
    background: #1f2937 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}

.dark .ios-row {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

.dark .academic-info-warning-title,
.dark .academic-info-missing-field {
    color: #f3f4f6 !important;
}

.dark .academic-info-warning-icon {
    background: rgba(220, 38, 38, 0.18) !important;
    color: #fca5a5 !important;
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
