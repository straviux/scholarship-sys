<script setup>

import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment'
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch, computed, inject } from 'vue';
import { usePermission } from '@/composable/permissions';
import { useApi } from '@/composable/api';
import { useFilterManager } from '@/composables/useFilterManager';
import { stripHtml } from '@/utils/sanitize';
import axios from 'axios';

import IosModal from '@/Components/ui/IosModal.vue';
import LoadingIndicator from '@/Components/ui/LoadingIndicator.vue';
import ApplicantFormModal from './Modal/ApplicantFormModal.vue';
import YakapCategoryModal from './Modal/YakapCategoryModal.vue';
import ExportSelectedModal from './Modal/ExportSelectedModal.vue';
import PriorityModal from './Modal/PriorityModal.vue';
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
import { toast } from '@/utils/toast';

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
    },
    interviewers: {
        type: Array,
        default: () => []
    },
    // Tracking lists (waiting / interview / endorsed / personal)
    activeList: {
        type: String,
        default: 'all'
    },
    listCounts: {
        type: Object,
        default: () => ({})
    },
    listMembership: {
        type: Object,
        default: () => ({})
    }
});

// ============================================
// TRACKING LISTS
// ============================================
const listTabs = [
    { key: 'all', label: 'Applicants', icon: 'users', badgeClass: '' },
    { key: 'waiting', label: 'Waiting', icon: 'clock', badgeClass: 'bg-amber-50 text-amber-700' },
    { key: 'interview', label: 'Interview', icon: 'comments', badgeClass: 'bg-blue-50 text-blue-700' },
    { key: 'endorsed', label: 'Endorsed', icon: 'share-2', badgeClass: 'bg-purple-50 text-purple-700' },
    { key: 'personal', label: 'My List', icon: 'bookmark', badgeClass: 'bg-emerald-50 text-emerald-700' },
];

const activeListTab = computed(() => props.activeList || 'all');

const switchListTab = (key) => {
    if (key === activeListTab.value) return;

    router.get(route('applicants.index'), key === 'all' ? {} : { list: key }, {
        preserveScroll: true,
        preserveState: false,
    });
};

// Which lists the given profile currently belongs to
const listsFor = (profile) => props.listMembership?.[profile?.profile_id] || [];

const addToList = (profile, listType) => {
    router.post(route('applicant-lists.store'), {
        profile_ids: [profile.profile_id],
        list_type: listType,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const label = listTabs.find(t => t.key === listType)?.label || listType;
            toast.success(`Added to ${label} list`);
        },
        onError: () => toast.error('Failed to add to list'),
    });
};

const removeFromList = (profile, listType) => {
    router.delete(route('applicant-lists.destroy'), {
        data: {
            profile_ids: [profile.profile_id],
            list_type: listType,
        },
        preserveScroll: true,
        onSuccess: () => {
            const label = listTabs.find(t => t.key === listType)?.label || listType;
            toast.success(`Removed from ${label} list`);
        },
        onError: () => toast.error('Failed to remove from list'),
    });
};

// Filter management via composable
const {
    filters: filter,
    globalFilter,
    records,
    totalRecords,
    showAllFilters,
    search: triggerSearch,
    clear: clearFilter,
    hasMore,
    loadMore,
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
        { key: 'encoded_by', type: 'text', default: '' },
    ],
    beforeSearch(params) {
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
        encoded_by: 'Encoded By',
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

// Program tabs (toolbar center) — same active-program list ProgramSelect uses
const { data: programsData, fetchData: fetchPrograms } = useApi(route('scholarshipprograms.getactivelist'));
const programs = computed(() => programsData.value || []);
onMounted(fetchPrograms);

// Fallback dot colors for programs without a bg_color
const programDotColors = ['#6366F1', '#8B5CF6', '#EC4899', '#F43F5E', '#F97316', '#EAB308', '#22C55E', '#14B8A6', '#06B6D4', '#3B82F6'];

// Program avatar in the Academic column — fixed abbreviations for the four
// scholarship programs, with a generic initials fallback.
const getProgramAbbrev = (program) => {
    const name = (program?.shortname || program?.name || '').toUpperCase();
    if (name.includes('MED')) return 'MED';
    if (name.includes('EFA')) return 'EFA';
    if (name.includes('TEC') || name.includes('TECH')) return 'TEC';
    if (name.includes('BAR')) return 'BAR';
    return name.split(/\s+/).map(w => w[0]).join('').slice(0, 3) || '?';
};

const getProgramAvatarColor = (program) => {
    if (program?.bg_color) return program.bg_color;
    if (program?.id != null) return programDotColors[program.id % programDotColors.length];
    return '#6366F1';
};

// Match on shortname so the active state also holds when the filter was
// rehydrated from the query string.
const isProgramTabActive = (program) => {
    const current = filter.value?.program;
    if (!current || !program) return false;
    const currentName = (current.shortname || current.name || '').toLowerCase();
    const programName = (program.shortname || program.name || '').toLowerCase();
    return currentName === programName;
};

const selectProgramTab = (program) => {
    if (!program) {
        if (!filter.value.program) return;
        filter.value.program = '';
    } else {
        if (isProgramTabActive(program)) return;
        filter.value.program = program;
    }
    // The filter watcher below fires triggerSearch()
};

// Auto-trigger search when basic filters change
watch(
    () => [filter.value.program, filter.value.course, filter.value.school, filter.value.municipality, filter.value.year_level, filter.value.date_from, filter.value.date_to],
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
const showApplicationFormModal = ref(false);
const applicationFormMode = ref('create');
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


const closeModal = () => {
    showApplicationFormModal.value = false;
    modalProfile.value = null;
    // Don't reset yakap values - they persist for next new applicant
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

const drawerFilterKeys = ['parent_name', 'program', 'course', 'school', 'municipality', 'barangay', 'year_level', 'academic_year', 'term', 'yakap_category', 'priority_level', 'date_from', 'date_to', 'encoded_from', 'encoded_to', 'encoded_by'];

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
    const nullKeys = ['academic_year', 'term', 'encoded_by'];
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
        if (hasRole('administrator') || hasRole('program_manager') || hasRole('screening_officer')) {
            items.push({
                label: 'Interview',
                icon: 'comments',
                command: () => handleProfileReviewInterview(rowData)
            });
        }
    }

    // Grouped edit actions
    const editItems = [
        {
            label: 'Application',
            icon: 'user-edit',
            command: () => editApplicant(rowData)
        },
    ];

    if (hasPermission('applicants.view')) {
        editItems.push({
            label: 'Requirements',
            icon: 'book-check',
            command: () => openRequirementsModal(rowData)
        });
    }

    editItems.push(
        {
            label: 'YAKAP Category',
            icon: 'heart',
            command: () => openUpdateYakapModal(rowData)
        },
        {
            label: 'Remarks',
            icon: 'comment',
            command: () => openRemarksModal(rowData)
        }
    );

    items.push({
        separator: true
    });
    items.push({
        label: 'Edit',
        icon: 'pencil',
        items: editItems
    });

    // Add to / remove from tracking lists
    const memberOf = listsFor(rowData);
    const addTargets = listTabs
        .filter(tab => tab.key !== 'all' && !memberOf.includes(tab.key))
        .map(tab => ({
            label: tab.label,
            icon: tab.icon,
            command: () => addToList(rowData, tab.key)
        }));

    if (addTargets.length > 0) {
        items.push({
            label: 'Add to',
            icon: 'plus',
            items: addTargets
        });
    }

    if (memberOf.length > 0) {
        items.push({
            label: 'Remove from',
            icon: 'minus',
            items: memberOf.map(key => ({
                label: listTabs.find(t => t.key === key)?.label || key,
                icon: 'times',
                command: () => removeFromList(rowData, key)
            }))
        });
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

    if (hasRole('administrator')) {
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

// showAllFilters, globalFilter, totalRecords, hasMore, loadMore
// are all provided by useFilterManager composable above

// Row selection state
const selectedRows = ref([]);
const showBatchYakapModal = ref(false);
const batchYakapForm = useForm({
    yakap_category: '',
    yakap_location: ''
});

// Memoization cache for expensive computations
const formatMemoCache = new Map();
const applicantMemoCache = new Map();

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

// Export / report modal state
const showExportModal = ref(false);
const exportPopover = ref(null);
const exportMode = ref('selected'); // 'selected' = checked rows, 'all' = full filtered set
const reportRows = ref([]);
const reportLoading = ref(false);

// Rows fed to the export modal: the checked rows, or the fetched full set.
const exportRows = computed(() => exportMode.value === 'all' ? reportRows.value : selectedRows.value);

const openExportSelected = () => {
    exportPopover.value?.hide();
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant to export.');
        return;
    }
    exportMode.value = 'selected';
    showExportModal.value = true;
};

const openExportAll = () => {
    exportPopover.value?.hide();
    openReportModal();
};

// Generate a report of every applicant matching the current filters/tab,
// not just the checked rows. Fetches the full set from the server first.
const openReportModal = async () => {
    if (reportLoading.value) return;
    reportLoading.value = true;
    try {
        // Reuse the exact query string that produced the current view.
        const qs = window.location.search || '';
        const { data } = await axios.get(route('applicants.report-data') + qs);
        const rows = data?.data ?? [];

        if (!rows.length) {
            toast.warn('No applicants match the current filters.');
            return;
        }

        reportRows.value = rows;
        exportMode.value = 'all';
        showExportModal.value = true;

        if (data?.capped) {
            toast.warn('Report was capped at 20,000 records. Narrow the filters for a complete set.');
        }
    } catch (error) {
        console.error('Failed to load report data:', error);
        toast.error('Failed to load report data.');
    } finally {
        reportLoading.value = false;
    }
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

// Copy "lastname, firstname" to the clipboard
const copyApplicantName = (applicant) => {
    const text = [applicant?.last_name, applicant?.first_name].filter(Boolean).join(', ');
    if (!text) return;
    navigator.clipboard.writeText(text)
        .then(() => toast.success(`Copied "${text}"`))
        .catch(() => toast.error('Failed to copy name'));
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
        <div class="ios-settings-form">
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Applicants Management</h1>
                            <p class="text-sm short:text-xs text-gray-600">Manage scholarship applicants and their
                                profiles</p>
                        </div>
                    </div>
                </template>

                <template #center>
                    <!-- Program tabs — the primary filter, front and center -->
                    <div class="flex flex-wrap items-center justify-center gap-2" role="tablist"
                        aria-label="Scholarship programs">
                        <button type="button" role="tab" :aria-selected="!filter.program"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="!filter.program
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(null)">
                            <AppIcon name="layers" :size="14" />
                            All Programs
                        </button>
                        <button v-for="(program, i) in programs" :key="program.id" type="button" role="tab"
                            :aria-selected="isProgramTabActive(program)"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="isProgramTabActive(program)
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(program)">
                            <span class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: program.bg_color || programDotColors[i % programDotColors.length] }"></span>
                            {{ program.shortname || program.name }}
                        </button>
                    </div>
                </template>

                <template #end>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <AppButton icon="plus" @click="openYakapCategoryModal" severity="success"
                            v-tooltip.bottom="'Add New Applicant'" rounded outlined />
                        <!-- Export — ticked rows or the full filtered set -->
                        <AppButton v-if="hasPermission('applicants.export')" icon="download" label="Export"
                            @click="exportPopover.toggle($event)" severity="info" rounded outlined
                            :loading="reportLoading" v-tooltip.bottom="'Export applicants'" />
                        <Popover ref="exportPopover">
                            <div class="flex flex-col gap-2 w-60">
                                <AppButton @click="openExportSelected" label="Export Selected" icon="square-check"
                                    severity="info" outlined class="justify-start" />
                                <AppButton @click="openExportAll" label="Export All (current filters)" icon="layers"
                                    severity="secondary" outlined class="justify-start" />
                            </div>
                        </Popover>
                    </div>
                </template>
            </Toolbar>




            <!-- Filter Drawer -->
            <IosModal v-model:visible="showFilterDrawer" title="All Filters"
                modal-class="!w-[calc(100vw-1rem)] sm:!w-[min(600px,calc(100vw-1rem))] !max-w-[calc(100vw-1rem)]">

                <div class="grid grid-cols-2 gap-4 mt-4">
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
                        <label class="text-xs font-medium text-gray-600 mb-1">Encoded By</label>
                        <InputText v-model="drawerFilter.encoded_by" placeholder="Type encoder name..." size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col col-span-2">
                        <label class="text-xs font-medium text-gray-600 mb-1">Date Filed</label>
                        <div class="flex gap-2">
                            <DatePicker v-model="drawerFilter.date_from" size="small" class="w-full"
                                date-format="M dd, yy" showIcon iconDisplay="input" placeholder="From" />
                            <DatePicker v-model="drawerFilter.date_to" size="small" class="w-full"
                                date-format="M dd, yy" showIcon iconDisplay="input" placeholder="To" />
                        </div>
                    </div>
                    <div class="flex flex-col col-span-2">
                        <label class="text-xs font-medium text-gray-600 mb-1">Date Encoded</label>
                        <div class="flex gap-2">
                            <DatePicker v-model="drawerFilter.encoded_from" size="small" class="w-full"
                                date-format="M dd, yy" showIcon iconDisplay="input" placeholder="From" />
                            <DatePicker v-model="drawerFilter.encoded_to" size="small" class="w-full"
                                date-format="M dd, yy" showIcon iconDisplay="input" placeholder="To" />
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t mb-4">
                    <AppButton severity="secondary" outlined size="small" icon="history" label="Clear"
                        @click="clearDrawerFilters" />
                    <AppButton label="Apply" icon="filter" severity="info" size="small" @click="applyDrawerFilters" />
                </div>
            </IosModal>

            <!-- Applicants DataTable -->
            <Panel class="!rounded-4xl overflow-hidden mt-4">

                <!-- List Tabs -->
                <div class="mb-4 -mt-2 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1" role="tablist" aria-label="Applicant lists">
                        <button v-for="tab in listTabs" :key="tab.key" type="button" role="tab"
                            :aria-selected="activeListTab === tab.key"
                            class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                            :class="activeListTab === tab.key
                                ? 'border-blue-500 font-semibold text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            @click="switchListTab(tab.key)">
                            <div class="flex items-center gap-2">
                                <AppIcon :name="tab.icon" :size="14" />
                                <span>{{ tab.label }}</span>
                                <span v-if="tab.key !== 'all'"
                                    class="rounded-full px-2 py-0.5 text-2xs font-semibold"
                                    :class="tab.badgeClass">
                                    {{ listCounts?.[tab.key] ?? 0 }}
                                </span>
                            </div>
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <RecordsSelect v-model="records" label="label" class="w-16" size="small" />
                            <span class="text-sm text-gray-600">/ <strong>{{ totalRecords }}</strong></span>
                        </div>
                        <AppButton :icon="simpleView ? 'table' : 'list'" severity="secondary" rounded outlined
                            size="small"
                            v-tooltip.bottom="simpleView ? 'Switch to Detailed View' : 'Switch to Simple View'"
                            @click="simpleView = !simpleView" />
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <InputGroup class="w-full sm:w-64">
                        <InputGroupAddon>
                            <AppIcon name="search" :size="14" class="text-gray-400" />
                        </InputGroupAddon>
                        <InputText v-model="globalFilter" placeholder="Type name, remarks etc.." size="small"
                            @keyup.enter="triggerSearch()" />
                    </InputGroup>
                    <div class="flex flex-col">
                        <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" :multiple="false" />
                    </div>
                    <div class="flex flex-col">
                        <MunicipalitySelect v-model="filter.municipality" custom-placeholder="All Municipalities"
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
                        <DatePicker v-model="filter.date_from" size="small" class="w-36" date-format="M dd, yy" showIcon
                            iconDisplay="input" placeholder="Filed From" />
                        <DatePicker v-model="filter.date_to" size="small" class="w-36" date-format="M dd, yy" showIcon
                            iconDisplay="input" placeholder="Filed To" />
                    </div>
                    <AppIcon name="sliders-horizontal" :size="24"
                        class="text-gray-400 cursor-pointer self-center" @click="openDrawer()"
                        v-tooltip.bottom="'More Filters'" />
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
                <DataTable :value="applicants" stripedRows
                    responsiveLayout="scroll" :emptyMessage="'No applicants to display'"
                    v-model:selection="selectedRows" dataKey="profile_id" :scrollable="true"
                    class="applicants-table ios-datatable-rounded" @row-contextmenu="onRowContextMenu" contextMenu>

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
                                            <div class="font-semibold text-sky-800 text-sm flex-1 min-w-0 cursor-pointer hover:text-cyan-600 underline underline-offset-2"
                                                @click="openProfileReviewModal(slotProps.data)">
                                                {{ slotProps.data.last_name }}, {{ slotProps.data.first_name }} {{
                                                    slotProps.data.middle_name || '' }} {{
                                                    slotProps.data.extension_name || '' }}
                                            </div>
                                            <button type="button"
                                                class="shrink-0 cursor-pointer text-gray-400 hover:text-gray-600 transition-colors"
                                                v-tooltip.top="'Copy name'"
                                                @click.stop="copyApplicantName(slotProps.data)">
                                                <AppIcon name="copy" :size="12" />
                                            </button>
                                            <!-- Warning for profiles without an academic record -->
                                            <AppIcon v-if="slotProps.data.has_academic_record === false"
                                                name="exclamation-triangle" :size="12"
                                                class="shrink-0 text-amber-500"
                                                v-tooltip.top="'No academic record'" />
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

                    <!-- Address Column -->
                    <Column header="Address" style="min-width: 130px; max-width: 150px">
                        <template #body="slotProps">
                            <div class="ml-1 text-xs mt-0.5 flex items-center gap-3 uppercase"
                                v-if="slotProps.data.municipality">
                                <AppIcon name="map" :size="12" class="text-gray-500" />
                                <span>{{ slotProps.data.municipality }}{{ slotProps.data.barangay ? `,
                                    ${slotProps.data.barangay}` : '' }}</span>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                            <div class="ml-1 text-xs mt-0.5 flex items-center gap-3">
                                <AppIcon name="phone" :size="12" class="text-gray-500" />
                                <span>{{ slotProps.data.contact_no || 'No contact no.' }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Academic Column -->
                    <Column header="Academic" style="min-width: 240px; max-width: 280px">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-1.5">
                                <div v-if="slotProps.data.scholarship_grant[0]" class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-2xs font-bold shrink-0"
                                        :style="{ backgroundColor: getProgramAvatarColor(slotProps.data.scholarship_grant[0].program) }"
                                        v-tooltip.top="slotProps.data.scholarship_grant[0].program?.name || 'Program'">
                                        {{ getProgramAbbrev(slotProps.data.scholarship_grant[0].program) }}
                                    </div>
                                    <div
                                        class="text-xs flex flex-col gap-0.5 min-w-0 whitespace-normal break-words leading-snug">
                                        <div class="font-medium" v-if="slotProps.data.scholarship_grant[0]?.school">
                                            {{ slotProps.data.scholarship_grant[0].school.shortname }}
                                        </div>
                                        <div v-if="slotProps.data.scholarship_grant[0]?.course">
                                            {{ slotProps.data.scholarship_grant[0].course.name ||
                                                slotProps.data.scholarship_grant[0].course.shortname }}
                                        </div>
                                        <div class="text-gray-600" v-if="slotProps.data.scholarship_grant[0]?.year_level">
                                            {{ slotProps.data.scholarship_grant[0].year_level }} Year
                                        </div>
                                    </div>
                                </div>
                                <div v-if="slotProps.data.has_academic_record === false" class="flex items-center">
                                    <Tag severity="warn" rounded
                                        v-tooltip.top="'This profile has no academic record'">
                                        <span class="flex items-center gap-1 text-2xs">
                                            <AppIcon name="exclamation-triangle" :size="10" />
                                            No Academic Record
                                        </span>
                                    </Tag>
                                </div>
                                <span
                                    v-if="!slotProps.data.scholarship_grant[0] && slotProps.data.has_academic_record !== false"
                                    class="text-gray-400">-</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Remarks" style="min-width: 260px; max-width: 320px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.remarks" v-safe-html="slotProps.data.remarks"
                                class="text-xs prose prose-xs max-w-none line-clamp-3"></div>
                            <span v-else class="text-xs text-gray-400">-</span>
                        </template>
                    </Column>

                    <Column header="Created / Updated" style="min-width: 150px" v-if="!simpleView">
                        <template #body="slotProps">
                            <div class="flex flex-col gap-1.5 text-xs leading-tight">
                                <div v-if="slotProps.data.created_by || slotProps.data.created_at"
                                    class="flex flex-col">
                                    <span class="text-slate-700">{{ slotProps.data.created_by?.name }}</span>
                                    <span v-if="slotProps.data.created_at" class="text-slate-400">
                                        Created {{ formatDateFiled(slotProps.data.created_at) }}
                                    </span>
                                </div>
                                <div v-if="slotProps.data.updated_by || slotProps.data.updated_at"
                                    class="flex flex-col">
                                    <span class="text-slate-700">{{ slotProps.data.updated_by?.name }}</span>
                                    <span v-if="slotProps.data.updated_at" class="text-slate-400">
                                        Updated {{ formatDateFiled(slotProps.data.updated_at) }}
                                    </span>
                                </div>
                                <span
                                    v-if="!slotProps.data.created_by && !slotProps.data.created_at && !slotProps.data.updated_by && !slotProps.data.updated_at"
                                    class="text-gray-400">-</span>
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
                    <Column header="⋮" style="width: 48px">
                        <template #body="slotProps">
                            <div class="flex items-center justify-center">
                                <AppButton icon="ellipsis-vertical" text severity="secondary"
                                    @click="(event) => showRowContextMenu(event, slotProps.data)"
                                    v-tooltip.top="'Actions'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div v-if="applicants.length > 0" class="flex flex-col items-center gap-1 mt-4">
                    <AppButton v-if="hasMore" label="Show More" icon="chevron-down" severity="secondary" size="small"
                        outlined rounded @click="loadMore()" />
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        Showing {{ applicants.length }} of {{ totalRecords }} entries
                    </span>
                </div>
            </Panel>
        </div>

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
            :applicants="applicants" :list-membership="listMembership"
            @interview="handleProfileReviewInterview" @edit-profile="handleProfileReviewEdit"
            @edit-requirements="openRequirementsModal" @edit-yakap="openUpdateYakapModal" @edit-remarks="openRemarksModal"
            @assign-priority="openPriorityModal" @remove-priority="removePriority"
            @add-to-list="addToList" @remove-from-list="removeFromList"
            @delete="confirmDeleteApplicant" @closed="closeProfileReviewModal" />

        <!-- YAKAP Category Modal - for selecting category when creating new applicant -->
        <YakapCategoryModal v-model:visible="showYakapCategoryModal" @selected="handleYakapCategorySelected" />

        <!-- Update YAKAP Category Modal -->
        <UpdateYakapModal :show="showUpdateYakapModal" :profile="selectedProfileForYakap"
            :refreshActivityLogs="refreshActivityLogs" @update:show="showUpdateYakapModal = $event"
            @success="refreshApplicationList" />

        <!-- Interview Assessment Modal -->
        <InterviewAssessmentModal v-model="showInterviewModal" :applicant="selectedApplicantForReview"
            :recordId="interviewRecordId" :interviewers="interviewers" @submitted="onInterviewSubmitted" />

        <!-- Batch Update YAKAP Category Modal -->
        <BatchUpdateYakapModal :show="showBatchYakapModal" :selectedRows="selectedRows"
            :refreshActivityLogs="refreshActivityLogs"
            @update:show="val => { showBatchYakapModal = val; if (!val) selectedRows = []; }"
            @success="() => { refreshApplicationList(); selectedRows = []; }" />

        <!-- Application Form Modal - for creating/editing applicants -->
        <ApplicantFormModal v-model:visible="showApplicationFormModal" :mode="applicationFormMode"
            :profile="modalProfile" :yakap-category="selectedYakapCategory" :yakap-location="selectedYakapLocation"
            @success="closeModal" @applicant-created="handleApplicantCreated" />

        <!-- Priority Modal -->
        <PriorityModal :show="showPriorityModal" :applicant="selectedApplicantForPriority"
            @update:show="showPriorityModal = $event" @success="handlePrioritySuccess" />

        <!-- Requirements Checklist Modal -->
        <RequirementsChecklistModal :visible="showRequirementsChecklistModal"
            :applicant="selectedApplicantForRequirements" @update:visible="showRequirementsChecklistModal = $event" />

        <!-- Export / Report Modal (checked rows, or full filtered set) -->
        <ExportSelectedModal :show="showExportModal" :selected-rows="exportRows" :mode="exportMode"
            :enable-signatories="true" :enable-jpm="true" default-sort="date_filed"
            @update:show="showExportModal = $event" />

        <!-- Centered loading message while the full report dataset is fetched -->
        <LoadingIndicator :show="reportLoading" message="Generating report data…"
            subtext="Fetching all applicants matching the current filters. Large result sets may take a moment." />
    </AdminLayout>
</template>
