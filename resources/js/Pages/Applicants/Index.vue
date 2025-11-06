<script setup>

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import moment from 'moment'
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { usePermission } from '@/composable/permissions';

// PrimeVue Components
import Button from 'primevue/button';
import Toolbar from 'primevue/toolbar';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import FloatLabel from 'primevue/floatlabel';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import IftaLabel from 'primevue/iftalabel';
import Checkbox from 'primevue/checkbox';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Panel from 'primevue/panel';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Popover from 'primevue/popover';

import ApplicantFormModal from '@/Components/modals/ApplicantFormModal.vue';
// import ApplicantProfileModal from '@/Pages/Applicants/Modal/ApplicantProfileModal.vue';
import GenerateReportModal from './Modal/GenerateReportModal.vue';
import PriorityModal from './Modal/PriorityModal.vue';
import JpmModal from './Modal/JpmModal.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
import GridView from '@/Components/GridView.vue';
import ApplicantGridCard from '@/Components/ApplicantGridCard.vue';
const showReportModal = ref(false);
const openReportModal = () => { showReportModal.value = true; };

// Actions popover
const actionsPopover = ref();

import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const { hasPermission, hasRole } = usePermission();

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
        course: { type: String },
        applied_year_level: { type: String },
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

const form = useForm({
    course: props.filter.course || "",
    municipality: props.filter.municipality || "",
    name: props.filter.name || "",
    records: props.records || 10,
    sort: {
        date_filed: props.sort.date_filed || "",
        last_name: props.sort.last_name || "",
        school: props.sort.school || "",
        course: props.sort.course || "",
        year_level: props.sort.year_level || "",
    },
});

const toDate = (val) => val ? new Date(val) : null;

// Get records from URL if not provided by backend
const getRecordsFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const urlRecords = urlParams.get('records');
    return urlRecords ? parseInt(urlRecords) : 10;
};

// Determine initial JPM filter value from props
const getInitialJpmFilter = () => {
    if (props.filter.show_jpm_only === true || props.filter.show_jpm_only === 'true' || props.filter.show_jpm_only === '1') {
        return 'jpm_only';
    }
    if (props.filter.hide_jpm === true || props.filter.hide_jpm === 'true' || props.filter.hide_jpm === '1') {
        return 'hide_jpm';
    }
    if (props.filter.hide_all_tagged === true || props.filter.hide_all_tagged === 'true' || props.filter.hide_all_tagged === '1') {
        return 'hide_all_tagged';
    }
    return 'all';
};

const filter = useForm({
    records: parseInt(props.records) || getRecordsFromUrl(),
    name: props.filter.name || "",
    parent_name: props.filter.parent_name || "",
    program: props.filter.program || "",
    school: props.filter.school || "",
    course: props.filter.course || "",
    municipality: props.filter.municipality || "",
    year_level: props.filter.year_level || "",
    date_from: props.filter.date_from ? toDate(props.filter.date_from) : null,
    date_to: props.filter.date_to ? toDate(props.filter.date_to) : null,
    remarks: props.filter.remarks || "",
    global_search: props.filter.global_search || "",
    jpm_filter: getInitialJpmFilter(),
    page: props.filter.page || 1,
})

const searchInput = ref(null);
const selectedProfile = ref({});

// View mode: 'table' or 'grid' - persisted in localStorage
const viewMode = ref(localStorage.getItem('applicants_view_mode') || 'table');

// Watch for viewMode changes and persist to localStorage
watch(viewMode, (newValue) => {
    localStorage.setItem('applicants_view_mode', newValue);
});

// JPM Filter Options
const jpmFilterOptions = [
    { label: 'Show All', value: 'all' },
    { label: 'Show JPM Only', value: 'jpm_only' },
    { label: 'Hide JPM', value: 'hide_jpm' },
    { label: 'Hide All Tagged', value: 'hide_all_tagged' }
];

// Applicant Modal state
// const showApplicantModal = ref(false);
const showApplicationFormModal = ref(false);
const applicationFormMode = ref('create');
// const modalAction = ref('');
const modalProfile = ref(null);

const editApplicant = (profile) => {
    console.log(profile)
    modalProfile.value = profile;
    applicationFormMode.value = 'edit';
    showApplicationFormModal.value = true;
}

const closeModal = () => {
    showApplicationFormModal.value = false;
    modalProfile.value = null;
    // Refresh the applicants list after modal closes
    refreshApplicationList();
}

const openApplicationFormModal = () => {
    modalProfile.value = null;
    applicationFormMode.value = 'create';
    showApplicationFormModal.value = true;
}

// const closeApplicantModal = () => {
//     showApplicantModal.value = false;
//     modalProfile.value = null;
//     modalAction.value = '';
// }

const filterList = (resetToPage1 = false) => {
    // Prepare filter values
    const program = filter.program?.shortname?.toLowerCase() || "";
    const parent_name = filter.parent_name.toLowerCase() || "";
    const course = filter.course?.shortname?.toLowerCase() || "";
    const municipality = filter.municipality?.name?.toLowerCase() || "";
    const name = filter.name.toLowerCase() || "";
    const school = filter.school?.shortname?.toLowerCase() || "";
    const year_level = filter.year_level?.value?.toLowerCase() || "";
    const remarks = filter.remarks.toLowerCase() || "";
    const global_search = globalFilter.value.toLowerCase() || "";
    const records = filter.records;
    const sort = form.sort;

    // Use date_from and date_to directly
    let date_from = filter.date_from ? moment(filter.date_from).format('YYYY-MM-DD') : "";
    let date_to = filter.date_to ? moment(filter.date_to).format('YYYY-MM-DD') : "";

    // Reset to page 1 only when filtering/searching, otherwise use current page
    let currentPage = resetToPage1 ? 1 : filter.page;

    const params = {};
    if (program) params.program = program;
    if (course) params.course = course;
    if (school) params.school = school;
    if (municipality) params.municipality = municipality;
    if (name) params.name = name;
    if (parent_name) params.parent_name = parent_name;
    if (year_level) params.year_level = year_level;
    if (date_from) params.date_from = date_from;
    if (date_to) params.date_to = date_to;
    if (remarks) params.remarks = remarks;
    if (global_search) params.global_search = global_search;

    // Handle JPM filter
    if (filter.jpm_filter === 'jpm_only') {
        params.show_jpm_only = 1;
    } else if (filter.jpm_filter === 'hide_jpm') {
        params.hide_jpm = 1;
    } else if (filter.jpm_filter === 'hide_all_tagged') {
        params.hide_all_tagged = 1;
    }
    // If 'all', don't add any JPM filter parameters

    params.records = records; // Always include records to persist pagination
    if (sort && Object.values(sort).some(v => v)) params.sort = sort;
    params.page = currentPage;

    router.get(route('waitinglist.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const clearFilter = () => {
    filter.name = null;
    filter.parent_name = null;
    filter.program = null;
    filter.school = null;
    filter.course = null;
    filter.municipality = null;
    filter.year_level = null;
    filter.remarks = null;
    filter.date_from = null;
    filter.date_to = null;
    filter.records = 10;
    filter.global_search = '';
    filter.jpm_filter = 'all';
    filter.page = 1;
    globalFilter.value = ''; // Clear global search
    // Clear URL params by reloading the page with no query params
    router.get(route('waitinglist.index'), {}, {
        replace: true,
        preserveScroll: true,
    });
}



const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
}

const userEncodedCount = ref({ total: 0, today: 0 });

onMounted(async () => {
    window.addEventListener('keydown', handleKeydown);
    // Fetch user encoded records count
    try {
        const res = await fetch(route('waitinglist.getUserEncodedRecords'));
        if (res.ok) {
            userEncodedCount.value = await res.json();
        }
    } catch (e) {
        userEncodedCount.value = { total: 0, today: 0 };
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Only trigger filterList from filter changes, not both form and filter
let filterListTimeout = null;

// Watch for filter changes but exclude page and global_search changes to avoid pagination conflicts
watch(() => ({
    name: filter.name,
    parent_name: filter.parent_name,
    program: filter.program,
    school: filter.school,
    course: filter.course,
    municipality: filter.municipality,
    year_level: filter.year_level,
    remarks: filter.remarks,
    date_from: filter.date_from ? filter.date_from.toString() : null,
    date_to: filter.date_to ? filter.date_to.toString() : null,
    jpm_filter: filter.jpm_filter,
    records: filter.records
}), (newFilter, oldFilter) => {
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        // Always reset to page 1 when any filter changes to search through all records
        filterList(true);
        filterListTimeout = null;
    }, 500);
}, { deep: true });

// Combined JPM Tagging & Remarks functionality
const showJpmModal = ref(false);
const selectedProfileForJpm = ref(null);

const openJpmModal = (profile) => {
    selectedProfileForJpm.value = profile;
    showJpmModal.value = true;
};


// Persist showJpmColumns state in localStorage
const showJpmColumns = ref(localStorage.getItem('showJpmColumns') === 'true');

// Watch for changes in showJpmColumns and persist to localStorage
watch(showJpmColumns, (newValue) => {
    localStorage.setItem('showJpmColumns', newValue.toString());
});

// Filter state
const showAllFilters = ref(false);

// DataTable properties - disable client-side filtering and pagination
const globalFilter = ref(props.filter.global_search || '');
const first = ref(0);
const rows = ref(parseInt(props.records) || 10);

// Watch for changes in globalFilter and trigger backend search only
watch(globalFilter, (newValue) => {
    // Update the filter object for backend search
    filter.global_search = newValue;
    // Trigger backend search with debouncing
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        filterList(true); // Reset to page 1 when searching
        filterListTimeout = null;
    }, 500);
});

// Watch for changes in filter.records and sync with DataTable rows
watch(() => filter.records, (newValue) => {
    rows.value = parseInt(newValue) || 10;
}, { immediate: true });

// Handle pagination change
const onPageChange = (event) => {
    const page = event.page + 1; // PrimeVue uses 0-based indexing, backend uses 1-based
    filter.page = page;
    // Call filterList immediately without debouncing for pagination
    filterList(false); // Don't reset to page 1, use current page
};

// Calculate total records for pagination
const totalRecords = computed(() => props.profiles_total || 0);

// Sync first with current page
watch(() => filter.page, (newPage) => {
    first.value = (newPage - 1) * rows.value;
}, { immediate: true });

// Update first value when rows change
watch(() => rows.value, () => {
    first.value = (filter.page - 1) * rows.value;
});

// Computed for DataTable data
const applicants = computed(() => {
    const data = props.profiles.data || [];

    // Data is already filtered in the backend to exclude approved and declined applications
    const filteredData = data;

    // Ensure JPM boolean fields are properly converted to boolean values
    return filteredData.map(profile => ({
        ...profile,
        is_jpm_member: Boolean(profile.is_jpm_member),
        is_father_jpm: Boolean(profile.is_father_jpm),
        is_mother_jpm: Boolean(profile.is_mother_jpm),
        is_guardian_jpm: Boolean(profile.is_guardian_jpm),
        is_not_jpm: Boolean(profile.is_not_jpm),
    }));
});

// Delete confirmation propertiesr
const showConfirmDeleteModal = ref(false);
const selectedApplicant = ref(null);

// Combined profile and review modal state
const showProfileReviewModal = ref(false);
const selectedApplication = ref(null);
const selectedApplicantForReview = ref(null);

// Priority modal state
const showPriorityModal = ref(false);
const selectedApplicantForPriority = ref(null);

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

    router.delete(route('waitinglist.destroy', selectedApplicant.value.profile_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            toast.success('Applicant deleted successfully');
        },
        onError: () => {
            closeDeleteModal();
            toast.error('Failed to delete applicant');
        }
    });
};

// Combined profile and review modal methods
const openProfileReviewModal = (applicant) => {
    // Store the full profile data
    selectedApplicantForReview.value = applicant;

    // Convert applicant data to application format expected by ApprovalWorkflow
    selectedApplication.value = {
        id: applicant.scholarship_grant?.[0]?.id || applicant.profile_id,
        profile_id: applicant.profile_id,
        profile: applicant,
        program: applicant.scholarship_grant?.[0]?.program || null,
        course: applicant.scholarship_grant?.[0]?.course || null,
        school: applicant.scholarship_grant?.[0]?.school || null,
        year_level: applicant.scholarship_grant?.[0]?.year_level || null,
        approval_status: applicant.scholarship_grant?.[0]?.approval_status || 'pending',
        scholarship_status: applicant.scholarship_grant?.[0]?.scholarship_status || 0,
        created_at: applicant.scholarship_grant?.[0]?.created_at || applicant.created_at,
        conditional_requirements: applicant.scholarship_grant?.[0]?.conditional_requirements || null,
        conditional_deadline: applicant.scholarship_grant?.[0]?.conditional_deadline || null,
        conditional_deadline_notified_at: applicant.scholarship_grant?.[0]?.conditional_deadline_notified_at || null,
        conditional_deadline_expired: applicant.scholarship_grant?.[0]?.conditional_deadline_expired || false,
        approval_remarks: applicant.scholarship_grant?.[0]?.approval_remarks || null,
    };
    showProfileReviewModal.value = true;
};

const closeProfileReviewModal = () => {
    showProfileReviewModal.value = false;
    selectedApplication.value = null;
    selectedApplicantForReview.value = null;
};

const handleApprovalAction = () => {
    closeProfileReviewModal();
    toast.success('Application reviewed successfully');
    refreshApplicationList();
};

const refreshApplicationList = () => {
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
    refreshApplicationList();
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

// Handle grid card actions
const handleCardAction = ({ action, applicant }) => {
    switch (action) {
        case 'review':
            openProfileReviewModal(applicant);
            break;
        case 'jpm-tag':
            openJpmModal(applicant);
            break;
        case 'assign-priority':
            openPriorityModal(applicant);
            break;
        case 'edit':
            editApplicant(applicant);
            break;
        case 'delete':
            confirmDeleteApplicant(applicant);
            break;
        default:
            console.warn('Unknown action:', action);
    }
};

// Utility functions for applicant data formatting
const getApplicantInitials = (applicant) => {
    if (!applicant) return '';
    const firstInitial = applicant.first_name?.charAt(0) || '';
    const lastInitial = applicant.last_name?.charAt(0) || '';
    return `${firstInitial}${lastInitial}`.toUpperCase();
};

const getApplicantFullName = (applicant) => {
    if (!applicant) return '';
    const parts = [
        applicant.last_name,
        ',',
        applicant.first_name,
        applicant.middle_name,
        applicant.extension_name
    ].filter(Boolean);

    return parts.join(' ').replace(' ,', ',');
};

// Get JPM status for tag display with member details
const getJpmStatus = (profile) => {
    if (!profile) return null;

    const members = [];
    if (profile.is_jpm_member) members.push('Applicant');
    if (profile.is_father_jpm) members.push('Father');
    if (profile.is_mother_jpm) members.push('Mother');
    if (profile.is_guardian_jpm) members.push('Guardian');

    if (members.length > 0) {
        return {
            status: 'member',
            members: members
        };
    }

    // Check if explicitly marked as "Not JPM"
    if (profile.is_not_jpm) {
        return {
            status: 'not_member',
            members: []
        };
    }

    // If jpm_remarks exists, it means they've been checked but not a member
    if (profile.jpm_remarks && profile.jpm_remarks.trim() !== '') {
        return {
            status: 'not_member',
            members: []
        };
    }

    // Return null if no JPM status has been tagged
    return null;
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
        case 'low': return 'secondary';
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

</script>

<template>

    <Head title="Applicants" />
    <AdminLayout>
        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-3">

                        <i class="pi pi-users text-indigo-900" style="font-size:2rem"></i>

                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Applicants Management</h1>
                            <p class="text-sm text-gray-600">Manage scholarship applicants and their profiles</p>
                        </div>
                    </div>
                </template>

                <template #end>
                    <div class="flex gap-3 items-center">
                        <!-- JPM Controls -->
                        <div class="flex items-center gap-4" v-if="hasPermission('jpm.view')">
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="showJpmColumns" inputId="showJpmToggle" binary />
                                <label for="showJpmToggle" class="text-sm text-gray-600 cursor-pointer">Enable JPM
                                    Tagging</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <label for="jpmFilter" class="text-sm text-gray-600">JPM Filter:</label>
                                <Select v-model="filter.jpm_filter" :options="jpmFilterOptions" optionLabel="label"
                                    size="small" optionValue="value" placeholder="Select filter" class="w-40"
                                    inputId="jpmFilter" />
                            </div>
                        </div>

                        <Divider layout="vertical" class="h-6" v-if="hasPermission('jpm.view')" />

                        <Button icon="pi pi-user-plus" @click="openApplicationFormModal"
                            v-if="hasPermission('applicants.create')" severity="success"
                            v-tooltip.bottom="'Add New Applicant'" />
                        <Button icon="pi pi-print" @click="openReportModal" severity="info"
                            v-tooltip.bottom="'Generate Report'" v-if="hasPermission('reports.generate')" />
                        <!-- <Button as="a" label="Existing" icon="pi pi-user"
                            v-if="hasPermission('create-scholar-profile') && !hasRole('user')"
                            :href="route('waitinglist.index', { action: 'add-existing' })" severity="secondary"
                            size="small" /> -->
                    </div>
                </template>
            </Toolbar>

            <!-- Header Section -->
            <Panel>
                <!-- Filters Section -->
                <div class="space-y-3 -mt-6">
                    <!-- Filter Controls Header -->
                    <div class="flex justify-between items-center py-1">
                        <div class="flex items-center gap-3">

                            <Button :label="showAllFilters ? 'Show Basic Filters' : 'Show All Filters'"
                                icon="pi pi-filter" severity="secondary" size="small" outlined
                                @click="showAllFilters = !showAllFilters" />
                        </div>
                        <div class="flex items-center gap-3">
                            <Button severity="secondary" outlined size="small" icon="pi pi-history" @click="clearFilter"
                                v-tooltip.bottom="'Clear Filters'" />
                        </div>
                    </div>

                    <!-- All Filters in grouped rows -->
                    <div class="space-y-3">
                        <!-- Default Filters Row: Applicant Name, Program, Date From, Date To -->
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:gap-8">
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Applicant Name</label>
                                <InputText v-model="filter.name" placeholder="Search applicant name..." class="w-full"
                                    size="small" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                                <ProgramSelect v-model="filter.program" label="shortname"
                                    custom-placeholder="All Programs" size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                                <CourseSelect v-model="filter.course" label="shortname" custom-placeholder="All Courses"
                                    size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Date Filed</label>
                                <div class="flex gap-2">
                                    <!-- <FloatLabel label="Date From" class="w-full">
                                        <DatePicker v-model="filter.date_from" placeholder="Date From" size="small"
                                            class="w-full" :show-icon="true" date-format="M dd, yy" />
                                    </FloatLabel> -->
                                    <InputGroup>
                                        <InputGroupAddon>
                                            <span class="text-xs">From</span>
                                        </InputGroupAddon>
                                        <DatePicker v-model="filter.date_from" size="small" class="w-full"
                                            :show-icon="true" iconDisplay="input" date-format="M dd, yy" />
                                    </InputGroup>
                                    <InputGroup>
                                        <InputGroupAddon>
                                            <span class="text-xs">To</span>
                                        </InputGroupAddon>
                                        <DatePicker v-model="filter.date_to" size="small" class="w-full"
                                            :show-icon="true" iconDisplay="input" date-format="M dd, yy" />
                                    </InputGroup>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Filters (shown when showAllFilters is true) -->
                        <template v-if="showAllFilters">
                            <!-- Second Row: School, Municipality, Course, Year Level -->
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:gap-8">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                                    <SchoolSelect v-model="filter.school" label="shortname"
                                        custom-placeholder="All Schools" size="small" class="w-full"
                                        :multiple="false" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                                    <MunicipalitySelect v-model="filter.municipality"
                                        custom-placeholder="All Municipalities" size="small" class="w-full" />
                                </div>

                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                                    <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                                        size="small" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </Panel>

            <!-- Applicants DataTable -->
            <div class="mt-8">
                <Panel>
                    <!-- Info Bar -->
                    <div class="md:grid hidden grid-cols-3 items-center mb-4 bg-gray-50 rounded -mt-2 p-2">

                        <div class="flex gap-4 items-center">
                            <IconField iconPosition="left" class="w-full">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="globalFilter" placeholder="Search across all fields..."
                                    class="w-full" />
                            </IconField>
                        </div>
                        <div class="text-sm text-gray-600 text-center">
                            Showing
                            <RecordsSelect v-model="filter.records" label="label" class="w-24" size="small" /> of {{
                                props.profiles_total || 0 }} records
                        </div>
                        <div class="flex gap-2 justify-end mr-2">
                            <Button icon="pi pi-list" :severity="viewMode === 'table' ? 'primary' : 'secondary'"
                                :outlined="viewMode !== 'table'" @click="viewMode = 'table'" size="small"
                                v-tooltip.bottom="'Table View'" />
                            <Button icon="pi pi-th-large" :severity="viewMode === 'grid' ? 'primary' : 'secondary'"
                                :outlined="viewMode !== 'grid'" @click="viewMode = 'grid'" size="small"
                                v-tooltip.bottom="'Grid View'" />
                        </div>

                    </div>

                    <!-- Table View -->
                    <DataTable v-if="viewMode === 'table'" :value="applicants" stripedRows showGridlines
                        responsiveLayout="scroll" :emptyMessage="'No applicants to display'" :lazy="true" paginator
                        :rows="rows" :totalRecords="totalRecords" :first="first" @page="onPageChange"
                        paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                        <!-- Date Filed Column -->
                        <Column header="Date Filed" style="min-width: 110px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium">
                                    {{ slotProps.data.date_filed ? moment(slotProps.data.date_filed).format(`MMM DD,
                                    YYYY`) : '-' }}
                                </div>
                            </template>
                        </Column>

                        <!-- Sequence Number & Name Column -->
                        <Column header="Applicant" style="min-width: 300px">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-2">
                                    <!-- <span class="text-gray-400 text-sm">#{{
                                        slotProps.data.sequence_number_by_school_course || '-' }}</span> -->
                                    <div class="flex gap-2">

                                        <div class="flex flex-col gap-1">
                                            <div class="flex gap-1">
                                                <div v-if="slotProps.data.gender">
                                                    <img v-if="slotProps.data.gender == 'M'"
                                                        src="/images/male-avatar.png" alt="avatar"
                                                        class="rounded-full w-6 h-6" />
                                                    <img v-if="slotProps.data.gender == 'F'"
                                                        src="/images/female-avatar.png" alt="avatar"
                                                        class="rounded-full w-6 h-6" />
                                                </div>
                                                <div v-else>
                                                    <div
                                                        class="ml-1 w-5 h-5 rounded-full bg-gray-400 flex items-center justify-center text-xs text-white font-semibold">
                                                        {{ getApplicantInitials(slotProps.data) }}
                                                    </div>
                                                </div>
                                                <div class="font-semibold text-gray-800 text-sm">
                                                    {{ slotProps.data.last_name }}, {{ slotProps.data.first_name }} {{
                                                        slotProps.data.middle_name || '' }} {{ slotProps.data.extension_name
                                                        ||
                                                        '' }}
                                                </div>
                                            </div>
                                            <div class="ml-1 text-xs text-gray-500 mt-0.5 flex items-center gap-3 ">
                                                <i class="pi pi-phone" style="font-size: 0.75rem;"></i>
                                                <span>{{ slotProps.data.contact_no || 'No contact no.' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <div class="px-1">
                                            <div class="text-xs font-semibold text-gray-500">
                                                Prog. <span class="font-bold text-gray-600">#{{
                                                    slotProps.data.sequence_number || '-'
                                                    }}</span>
                                            </div>
                                        </div>
                                        <div class="px-1">
                                            <div class="text-xs font-semibold text-gray-500">
                                                Cour. <span class="font-bold text-gray-600">#{{
                                                    slotProps.data.sequence_number_by_course || '-'
                                                    }}</span>
                                            </div>
                                        </div>
                                        <div class="px-1">
                                            <div class="text-xs font-semibold text-gray-500">
                                                Sch. <span class="font-bold text-gray-600">#{{
                                                    slotProps.data.sequence_number_by_school_course || '-'
                                                    }}</span>

                                            </div>
                                        </div>
                                        <div class="px-1">
                                            <div class="text-xs font-semibold text-gray-500">
                                                Date <span class="font-bold text-gray-600">#{{
                                                    slotProps.data.daily_sequence_number ||
                                                    '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Education Details Column -->
                        <Column header="Education Details" style="min-width: 200px">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.scholarship_grant[0]" class="flex flex-col gap-0.5">
                                    <div class="text-sm font-medium" v-if="slotProps.data.scholarship_grant[0]?.school">
                                        {{ slotProps.data.scholarship_grant[0].school.shortname }}
                                    </div>
                                    <div class="text-sm" v-if="slotProps.data.scholarship_grant[0]?.course">
                                        {{ slotProps.data.scholarship_grant[0].course.shortname }}
                                    </div>
                                    <div class="text-xs text-gray-600"
                                        v-if="slotProps.data.scholarship_grant[0]?.year_level">
                                        Year {{ slotProps.data.scholarship_grant[0].year_level }}
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Address Column -->
                        <Column header="Address" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium" v-if="slotProps.data.municipality">
                                    {{ slotProps.data.municipality }}{{ slotProps.data.barangay ? `,
                                    ${slotProps.data.barangay}` : '' }}
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Parent/Guardian Column (only visible when showJpmColumns is enabled) -->
                        <Column header="Parent/Guardian" v-if="hasPermission('jpm.view') && showJpmColumns"
                            style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="text-sm space-y-1">
                                    <div v-if="slotProps.data.father_name">
                                        <span class="font-medium">{{ slotProps.data.father_name }}</span>
                                        <span class="text-gray-500 italic text-xs"> (father)</span>
                                    </div>
                                    <div v-if="slotProps.data.mother_name">
                                        <span class="font-medium">{{ slotProps.data.mother_name }}</span>
                                        <span class="text-gray-500 italic text-xs"> (mother)</span>
                                    </div>
                                    <div v-if="slotProps.data.guardian_name">
                                        <span class="font-medium">{{ slotProps.data.guardian_name }}</span>
                                        <span class="text-gray-500 italic text-xs"> (guardian)</span>
                                    </div>
                                    <span
                                        v-if="!slotProps.data.father_name && !slotProps.data.mother_name && !slotProps.data.guardian_name"
                                        class="text-gray-400">-</span>
                                </div>
                            </template>
                        </Column>

                        <!-- JPM Status Column (only visible when showJpmColumns is enabled) -->
                        <Column header="JPM Status" v-if="hasPermission('jpm.view') && showJpmColumns"
                            style="min-width: 180px">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-1">
                                    <div class="flex justify-center" v-if="getJpmStatus(slotProps.data)">
                                        <Tag :severity="getJpmTagSeverity(getJpmStatus(slotProps.data))"
                                            :value="getJpmTagLabel(getJpmStatus(slotProps.data))" />
                                    </div>
                                    <span v-else class="text-gray-400 text-sm text-center">-</span>

                                    <!-- Show member details -->
                                    <div v-if="getJpmStatus(slotProps.data)?.status === 'member'"
                                        class="text-xs text-gray-600 text-center italic">
                                        {{ getJpmMemberDetails(getJpmStatus(slotProps.data)) }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Remarks Column (hidden when JPM columns visible) -->
                        <Column header="Remarks" v-if="!showJpmColumns" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="text-xs">{{ slotProps.data.remarks || '-' }}</div>
                            </template>
                        </Column>

                        <!-- Encoded By Column (only visible for admins) -->
                        <Column header="Encoded By" v-if="hasRole('administrator')" style="min-width: 180px">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-1 text-xs">
                                    <div v-if="slotProps.data.created_by" class="font-medium text-gray-700">
                                        {{ slotProps.data.created_by }}
                                    </div>
                                    <div v-if="slotProps.data.created_at" class="text-gray-500">
                                        {{ moment(slotProps.data.created_at).format('MMM DD, YYYY') }}
                                    </div>
                                    <span v-if="!slotProps.data.created_by && !slotProps.data.created_at"
                                        class="text-gray-400">-</span>
                                </div>
                            </template>
                        </Column>

                        <!-- JPM Actions Column (visible when JPM columns enabled) -->
                        <Column header="Tagging" v-if="hasPermission('jpm.view') && showJpmColumns"
                            style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-2 items-center">
                                    <Button @click="openJpmModal(slotProps.data)" rounded icon="pi pi-tags"
                                        severity="info" size="small" outlined :disabled="!hasPermission('jpm.manage')"
                                        v-tooltip.top="'Edit JPM tagging and remarks'" />

                                    <!-- Quick preview of remarks if exists -->
                                    <div v-if="slotProps.data.jpm_remarks"
                                        class="text-xs text-gray-600 italic truncate">
                                        "{{ slotProps.data.jpm_remarks }}"
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Priority Column -->
                        <Column header="Priority" style="width: 180px" v-if="hasPermission('priority.manage')">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2 justify-between">
                                    <div
                                        v-if="slotProps.data.priority_level && slotProps.data.priority_level !== 'normal'">
                                        <Tag :severity="getPrioritySeverity(slotProps.data.priority_level)"
                                            :value="formatPriorityName(slotProps.data.priority_level)"
                                            v-tooltip.top="slotProps.data.priority_reason" />
                                    </div>
                                    <span v-else class="text-gray-400 text-sm">Normal</span>

                                    <!-- Priority Actions -->
                                    <div class="flex">
                                        <Button icon="pi pi-star" severity="warn" size="small" rounded outlined
                                            v-tooltip.top="'Assign Priority'"
                                            @click="openPriorityModal(slotProps.data)" />

                                        <Button icon="pi pi-star-fill" severity="secondary" size="small" rounded
                                            outlined v-tooltip.top="'Remove Priority'"
                                            @click="removePriority(slotProps.data)"
                                            v-if="slotProps.data.priority_level && slotProps.data.priority_level !== 'normal'" />
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Actions Column -->
                        <Column header="Actions" style="width: 200px">
                            <template #body="slotProps">

                                <div class="flex gap-1 justify-center flex-wrap">
                                    <Button icon="pi pi-id-card" severity="success" size="small" rounded outlined
                                        v-tooltip.top="'Review Application & View Profile'"
                                        @click="openProfileReviewModal(slotProps.data)"
                                        v-if="hasPermission('applicants.view')" />

                                    <Button icon="pi pi-user-edit" severity="help" size="small" rounded outlined
                                        v-tooltip.top="'Edit Applicant'" @click="editApplicant(slotProps.data)"
                                        v-if="hasPermission('applicants.edit')" />

                                    <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                        v-tooltip.top="'Delete Applicant'"
                                        @click="confirmDeleteApplicant(slotProps.data)"
                                        v-if="hasPermission('applicants.delete')" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Grid View -->
                    <GridView v-else-if="viewMode === 'grid'" :items="applicants" :total-records="totalRecords"
                        :rows="rows" :first="first" :columns="{ xs: 1, md: 2, lg: 3, xl: 4 }"
                        empty-message="No applicants to display" empty-icon="pi pi-users" @page-change="onPageChange">
                        <template #default="{ item: applicant }">
                            <ApplicantGridCard :applicant="applicant"
                                :show-jpm-badge="showJpmColumns && getJpmStatus(applicant)"
                                :jpm-details="getJpmMemberDetails(applicant)" :actions="[
                                    {
                                        name: 'review',
                                        icon: 'pi pi-check-circle',
                                        label: 'Review',
                                        severity: 'success',
                                        condition: () => hasPermission('applicants.view')
                                    },
                                    {
                                        name: 'jpm-tag',
                                        icon: 'pi pi-tags',
                                        severity: 'info',
                                        tooltip: 'JPM Tagging',
                                        outlined: true,
                                        condition: () => hasPermission('jpm.view') && showJpmColumns && hasPermission('jpm.manage')
                                    },
                                    {
                                        name: 'assign-priority',
                                        icon: 'pi pi-star',
                                        severity: 'warn',
                                        tooltip: 'Assign Priority',
                                        outlined: true,
                                        condition: () => hasPermission('priority.manage')
                                    },
                                    {
                                        name: 'edit',
                                        icon: 'pi pi-user-edit',
                                        severity: 'help',
                                        tooltip: 'Edit',
                                        condition: () => hasPermission('applicants.edit')
                                    },
                                    {
                                        name: 'delete',
                                        icon: 'pi pi-trash',
                                        severity: 'danger',
                                        tooltip: 'Delete',
                                        condition: () => hasPermission('applicants.delete')
                                    }
                                ]" @action="handleCardAction" />
                        </template>
                    </GridView>
                </Panel>
            </div>
        </div>

        <!-- Combined JPM Tagging & Remarks Modal -->
        <!-- JPM Modal -->
        <JpmModal :show="showJpmModal" :profile="selectedProfileForJpm" @update:show="showJpmModal = $event" />

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDeleteModal" :style="{ width: '450px' }" header="Confirm Deletion"
            :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                <div>
                    <p class="text-lg font-semibold text-gray-800 mb-2">
                        Are you sure you want to delete this applicant?
                    </p>
                    <div class="bg-gray-100 p-3 rounded border-l-4 border-red-500" v-if="selectedApplicant">
                        <div class="font-semibold text-red-700">
                            {{ selectedApplicant.last_name }}, {{ selectedApplicant.first_name }}
                        </div>
                        <div class="text-sm text-gray-600">{{ selectedApplicant.contact_no }}</div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeDeleteModal" outlined />
                <Button label="Delete Applicant" severity="danger" @click="deleteApplicant" />
            </template>
        </Dialog>

        <!-- Modals -->
        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event" />

        <!-- Integrated Profile & Review Modal -->
        <Dialog v-model:visible="showProfileReviewModal" modal header="Application Review & Applicant Profile"
            :style="{ width: '50vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" :maximizable="true"
            class="p-fluid">

            <div v-if="selectedApplicantForReview">
                <!-- Header Summary -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-4">
                        <Avatar :label="getApplicantInitials(selectedApplicantForReview)" size="large" shape="circle"
                            class="bg-blue-600 text-white" />
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900">{{
                                getApplicantFullName(selectedApplicantForReview) }}</h3>
                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                                <span><i class="pi pi-phone mr-1"></i>{{ selectedApplicantForReview.contact_no || 'N/A'
                                    }}</span>
                                <span><i class="pi pi-envelope mr-1"></i>{{ selectedApplicantForReview.email || 'N/A'
                                    }}</span>
                                <span><i class="pi pi-calendar mr-1"></i>{{
                                    formatDate(selectedApplicantForReview.date_filed)
                                    }}</span>
                            </div>
                        </div>
                        <!-- Queue Numbers -->
                        <div class="flex items-center gap-2">
                            <div class="text-center px-2 py-1 bg-indigo-100 rounded border border-indigo-200">
                                <div class="text-sm font-bold text-indigo-600">#{{
                                    selectedApplicantForReview.sequence_number ||
                                    '-' }}</div>
                                <div class="text-xs text-indigo-700">{{
                                    selectedApplicantForReview.scholarship_grant?.[0]?.program?.shortname }}</div>
                            </div>
                            <div class="text-center px-2 py-1 bg-purple-100 rounded border border-purple-200">
                                <div class="text-sm font-bold text-purple-600">#{{
                                    selectedApplicantForReview.sequence_number_by_course || '-' }}</div>
                                <div class="text-xs text-purple-700">{{
                                    selectedApplicantForReview.scholarship_grant?.[0]?.course?.shortname }}</div>
                            </div>
                            <div class="text-center px-2 py-1 bg-green-100 rounded border border-green-200">
                                <div class="text-sm font-bold text-green-600">#{{
                                    selectedApplicantForReview.sequence_number_by_school_course || '-' }}</div>
                                <div class="text-xs text-green-700">{{
                                    selectedApplicantForReview.scholarship_grant?.[0]?.school?.shortname }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabbed Content -->
                <Tabs value="applicationReview">
                    <TabList>
                        <Tab value="applicationReview">Review</Tab>
                        <Tab value="profileInformation">Profile</Tab>
                        <Tab value="attachments">Attachments</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="applicationReview" header="Application Review">
                            <ApprovalWorkflow v-if="selectedApplication" :application="selectedApplication"
                                :approval-statuses="props.approvalStatuses || []"
                                :decline-reasons="props.declineReasons || {}"
                                :auto-approval-config="props.autoApprovalConfig || {}" :show-applicant-name="false"
                                @approved="handleApprovalAction" @declined="handleApprovalAction"
                                @conditionalApproval="handleApprovalAction" @refresh="refreshApplicationList" />
                        </TabPanel>

                        <TabPanel value="profileInformation" header="Profile Information">
                            <!-- Personal & Academic Information -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                <!-- Personal Information -->
                                <div class="border rounded p-3">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                        <i class="pi pi-user text-blue-600 text-sm"></i>
                                        Personal
                                    </h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-gray-600">Full Name</label>
                                                <div class="font-medium">{{
                                                    getApplicantFullName(selectedApplicantForReview) }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Gender</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.gender === 'M' ?
                                                    'Male' :
                                                    selectedApplicantForReview.gender === 'F' ? 'Female' : 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-gray-600">Contact</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.contact_no ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Email</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.email || 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Income</label>
                                            <div class="font-medium">{{ selectedApplicantForReview.gross_monthly_income
                                                || 'N/A'
                                                }}</div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Address</label>
                                            <div class="font-medium">{{
                                                getApplicantFullAddress(selectedApplicantForReview) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information -->
                                <div class="border rounded p-3">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                        <i class="pi pi-graduation-cap text-green-600 text-sm"></i>
                                        Academic
                                    </h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-gray-600">Program</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.program?.shortname
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">School</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.school?.shortname
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-gray-600">Course</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.course?.shortname
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Year Level</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.year_level ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-gray-600">Academic Year</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.academic_year ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Term</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.term || 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-gray-600">Remarks</label>
                                            <div class="font-medium">{{ selectedApplicantForReview.remarks || `No
                                                remarks` }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Family Information -->
                            <div class="border rounded p-3 mt-3">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Family</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                    <!-- Father -->
                                    <div>
                                        <h5 class="text-xs font-medium text-blue-600 mb-1 flex items-center gap-1">
                                            <i class="pi pi-user text-xs"></i> Father
                                        </h5>
                                        <div class="space-y-1">
                                            <div>
                                                <label class="text-gray-600">Name</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.father_name ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Occupation</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.father_occupation
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Contact</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.father_contact_no
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mother -->
                                    <div>
                                        <h5 class="text-xs font-medium text-pink-600 mb-1 flex items-center gap-1">
                                            <i class="pi pi-user text-xs"></i> Mother
                                        </h5>
                                        <div class="space-y-1">
                                            <div>
                                                <label class="text-gray-600">Name</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.mother_name ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Occupation</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.mother_occupation
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Contact</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.mother_contact_no
                                                    ||
                                                    'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Guardian -->
                                    <div>
                                        <h5 class="text-xs font-medium text-purple-600 mb-1 flex items-center gap-1">
                                            <i class="pi pi-users text-xs"></i> Guardian
                                        </h5>
                                        <div class="space-y-1">
                                            <div>
                                                <label class="text-gray-600">Name</label>
                                                <div class="font-medium">{{ selectedApplicantForReview.guardian_name ||
                                                    'N/A' }}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Occupation</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.guardian_occupation ||
                                                    'N/A' }}</div>
                                            </div>
                                            <div>
                                                <label class="text-gray-600">Contact</label>
                                                <div class="font-medium">{{
                                                    selectedApplicantForReview.guardian_contact_no ||
                                                    'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </Dialog>

        <!-- Application Form Modal - for creating/editing applicants -->
        <ApplicantFormModal v-model:visible="showApplicationFormModal" :mode="applicationFormMode"
            :profile="modalProfile" @success="closeModal" />

        <!-- Applicant Profile Modal - for editing existing applicants (keeping for backward compatibility) -->
        <!-- <ApplicantProfileModal v-if="(props.action == 'update') || showApplicantModal"
            :action="showApplicantModal ? modalAction : props.action"
            :profile="showApplicantModal ? modalProfile : props.profile" :is-inline-modal="showApplicantModal"
            @close="closeApplicantModal" /> -->

        <!-- Priority Modal -->
        <PriorityModal :show="showPriorityModal" :applicant="selectedApplicantForPriority"
            @update:show="showPriorityModal = $event" @success="handlePrioritySuccess" />
    </AdminLayout>
</template>