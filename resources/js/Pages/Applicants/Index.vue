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

import ApplicantProfileModal from '@/Pages/Applicants/Modal/ApplicantProfileModal.vue';
import GenerateReportModal from './Modal/GenerateReportModal.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
const showReportModal = ref(false);
const openReportModal = () => { showReportModal.value = true; };

// COURSE MULTISELECT COMPONENT
// How to use: 1. import component, 2. define model, 3. define scholarshipProgramId (set to null if fetching all course)
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import Pagination from '@/Components/Pagination.vue';
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
    page: props.filter.page || 1,
})

const searchInput = ref(null);
const selectedProfile = ref({});

// Applicant Modal state
const showApplicantModal = ref(false);
const modalAction = ref('');
const modalProfile = ref(null);

const editApplicant = (profile) => {
    modalProfile.value = profile;
    modalAction.value = 'update';
    showApplicantModal.value = true;
}

const closeApplicantModal = () => {
    showApplicantModal.value = false;
    modalProfile.value = null;
    modalAction.value = '';
}

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
    filter.page = 1;
    globalFilter.value = ''; // Clear global search
    // Clear URL params by reloading the page with no query params
    router.get(route('waitinglist.index'), {}, {
        replace: true,
        preserveScroll: true,
    });
}

const sortBy = (column) => {
    if (column == 'name') {
        form.sort.last_name = form.sort.last_name == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'date_filed') {
        form.sort.date_filed = form.sort.date_filed == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'school') {
        form.sort.school = form.sort.school == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'course') {
        form.sort.course = form.sort.course == 'desc' ? 'asc' : 'desc';
    }
    if (column == 'year_level') {
        form.sort.year_level = form.sort.year_level == 'desc' ? 'asc' : 'desc';
    }
    // Apply the sort by triggering a filter with current filters
    filterList(true);
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
    date_from: filter.date_from,
    date_to: filter.date_to,
    records: filter.records
}), (newFilter, oldFilter) => {
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        // Always reset to page 1 when any filter changes to search through all records
        filterList(true);
        filterListTimeout = null;
    }, 500);
}, { deep: true });

// Inline JPM status update
const updateJpmStatus = ({ id = null, is_jpm_member = null, is_father_jpm = null, is_mother_jpm = null, is_guardian_jpm = null }) => {
    // Only send fields that are not null
    const payload = {};
    if (is_jpm_member !== null) payload.is_jpm_member = is_jpm_member;
    if (is_father_jpm !== null) payload.is_father_jpm = is_father_jpm;
    if (is_mother_jpm !== null) payload.is_mother_jpm = is_mother_jpm;
    if (is_guardian_jpm !== null) payload.is_guardian_jpm = is_guardian_jpm;

    console.log('Updating JPM status:', { id, payload }); // Debug log

    router.put(route('waitinglist.updateJpmStatus', id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            console.log('JPM update success:', page);

            // Manually update the original props data to reflect the changes
            // This will automatically trigger the computed applicants to re-evaluate
            const profileIndex = props.profiles.data.findIndex(profile => profile.profile_id === id);
            if (profileIndex !== -1) {
                // Update each field that was changed in the original props data
                if (is_jpm_member !== null) {
                    props.profiles.data[profileIndex].is_jpm_member = is_jpm_member;
                }
                if (is_father_jpm !== null) {
                    props.profiles.data[profileIndex].is_father_jpm = is_father_jpm;
                }
                if (is_mother_jpm !== null) {
                    props.profiles.data[profileIndex].is_mother_jpm = is_mother_jpm;
                }
                if (is_guardian_jpm !== null) {
                    props.profiles.data[profileIndex].is_guardian_jpm = is_guardian_jpm;
                }
                console.log('Updated local data for profile:', id, props.profiles.data[profileIndex]);
            }

            toast.success('JPM status updated successfully');
        },
        onError: (errors) => {
            console.error('JPM update errors:', errors);
            toast.error('Failed to update JPM status: ' + Object.values(errors).join(', '));
        },
        onFinish: () => {
            console.log('JPM update finished');
        }
    });
};

// JPM Remarks functionality
const showJpmRemarksModal = ref(false);
const selectedProfileForRemarks = ref(null);
const jpmRemarksForm = ref('');

const editJpmRemarks = (profile) => {
    selectedProfileForRemarks.value = profile;
    jpmRemarksForm.value = profile.jpm_remarks || '';
    showJpmRemarksModal.value = true;
};

const closeJpmRemarksModal = () => {
    showJpmRemarksModal.value = false;
    selectedProfileForRemarks.value = null;
    jpmRemarksForm.value = '';
};

const updateJpmRemarks = () => {
    if (!selectedProfileForRemarks.value) return;

    const payload = {
        jpm_remarks: jpmRemarksForm.value
    };

    router.put(route('waitinglist.updateJpmRemarks', selectedProfileForRemarks.value.profile_id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Update the local data
            const profileIndex = props.profiles.data.findIndex(profile => profile.profile_id === selectedProfileForRemarks.value.profile_id);
            if (profileIndex !== -1) {
                props.profiles.data[profileIndex].jpm_remarks = jpmRemarksForm.value;
            }

            closeJpmRemarksModal();
            toast.success('JPM remarks updated successfully');
        },
        onError: (errors) => {
            console.error('JPM remarks update errors:', errors);
            toast.error('Failed to update JPM remarks: ' + Object.values(errors).join(', '));
        }
    });
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
const filters = ref({});
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
    }));
});

// Delete confirmation properties
const showConfirmDeleteModal = ref(false);
const selectedApplicant = ref(null);

// Combined profile and review modal state
const showProfileReviewModal = ref(false);
const selectedApplication = ref(null);
const selectedApplicantForReview = ref(null);

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

    router.delete(route('profile.destroy', selectedApplicant.value.profile_id), {
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
        application_status: applicant.scholarship_grant?.[0]?.application_status || 0,
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

const handleApprovalAction = (application) => {
    closeProfileReviewModal();
    toast.success('Application reviewed successfully');
    refreshApplicationList();
};

const refreshApplicationList = () => {
    router.reload({ only: ['profiles'] });
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

const getApplicantFullAddress = (applicant) => {
    if (!applicant) return '';
    const parts = [
        applicant.barangay,
        applicant.municipality,
        applicant.province
    ].filter(Boolean);

    return parts.join(', ') || 'N/A';
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
                        <i class="pi pi-users text-2xl text-blue-600"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Applicants Management</h1>
                            <p class="text-sm text-gray-600">Manage scholarship applicants and their profiles</p>
                        </div>
                    </div>
                </template>

                <template #end>
                    <div class="flex gap-3 items-center">
                        <!-- JPM Tagging Toggle -->
                        <div class="flex items-center gap-2" v-if="hasPermission('can-view-jpm')">
                            <Checkbox v-model="showJpmColumns" inputId="showJpmToggle" binary />
                            <label for="showJpmToggle" class="text-sm text-gray-600 cursor-pointer">Enable JPM
                                Tagging</label>
                        </div>

                        <Divider layout="vertical" class="h-6" v-if="hasPermission('can-view-jpm')" />

                        <Button @click="openReportModal" label="Generate Report" icon="pi pi-file-pdf" severity="info"
                            size="small" />
                        <Button as="a" label="New" icon="pi pi-user-plus"
                            v-if="hasPermission('create-scholar-profile') && !hasRole('user')" severity="success" :href="route('waitinglist.index', {
                                action: 'create'
                            })" raised size="small" />
                        <Button as="a" label="Existing" icon="pi pi-user"
                            v-if="hasPermission('create-scholar-profile') && !hasRole('user')"
                            :href="route('waitinglist.index', { action: 'add-existing' })" severity="secondary"
                            size="small" />
                    </div>
                </template>
            </Toolbar>

            <!-- Header Section -->
            <Panel>
                <!-- Filters Section -->
                <div class="space-y-3 -mt-6">
                    <!-- Filter Controls Header -->
                    <div class="flex justify-between items-center py-1">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-filter text-blue-600"></i>
                            <h3 class="text-base font-medium text-gray-700">Filters</h3>
                        </div>
                        <div class="flex items-center gap-3">
                            <Button :label="showAllFilters ? 'Show Basic Filters' : 'Show All Filters'"
                                :icon="showAllFilters ? 'pi pi-minus' : 'pi pi-plus'" severity="info" size="small"
                                outlined @click="showAllFilters = !showAllFilters" />
                            <Button label="Clear" severity="secondary" size="small" icon="pi pi-times"
                                @click="clearFilter" outlined />
                        </div>
                    </div>

                    <!-- All Filters in grouped rows -->
                    <div class="space-y-3">
                        <!-- Default Filters Row: Program, School, Municipality, Applicant Name -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                                <ProgramSelect v-model="filter.program" label="shortname"
                                    custom-placeholder="All Programs" size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                                <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="All Schools"
                                    size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                                <MunicipalitySelect v-model="filter.municipality"
                                    custom-placeholder="All Municipalities" size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Applicant Name</label>
                                <InputText v-model="filter.name" placeholder="Search applicant name..." class="w-full"
                                    size="small" />
                            </div>
                        </div>

                        <!-- Additional Filters (shown when showAllFilters is true) -->
                        <template v-if="showAllFilters">
                            <!-- Second Row: Course, Year Level, Parents/Guardian, Remarks -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                                    <CourseSelect v-model="filter.course" label="shortname"
                                        custom-placeholder="All Courses" size="small" class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                                    <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                                        size="small" class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Parents/Guardian</label>
                                    <InputText v-model="filter.parent_name" placeholder="Search parent/guardian..."
                                        size="small" class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Remarks</label>
                                    <InputText v-model="filter.remarks" placeholder="Search remarks..." class="w-full"
                                        size="small" />
                                </div>
                            </div>

                            <!-- Third Row: Date Range -->
                            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-2">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Date From</label>
                                    <DatePicker v-model="filter.date_from" :manualInput="true" showButtonBar
                                        @clear-click="filter.date_from = null" placeholder="Start date" size="small"
                                        class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Date To</label>
                                    <DatePicker v-model="filter.date_to" :manualInput="true" showButtonBar
                                        @clear-click="filter.date_to = null" placeholder="End date" size="small"
                                        class="w-full" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </Panel>

            <!-- Applicants DataTable -->
            <div class="mt-4">
                <Panel>
                    <!-- Info Bar -->
                    <div class="md:flex hidden  justify-between items-center mb-4 px-3 bg-gray-50 rounded -mt-2">
                        <div class="text-sm text-gray-600">
                            <i class="pi pi-info-circle mr-2"></i>
                            By default, sequence # is by school per course
                        </div>
                        <div class="flex gap-4 items-center">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="globalFilter" placeholder="Search across all fields..."
                                    class="w-80" />
                            </IconField>
                        </div>
                        <div class="text-sm text-gray-600">
                            Showing
                            <RecordsSelect v-model="filter.records" label="label" class="w-24" size="small" /> of {{
                                props.profiles_total || 0 }} records
                        </div>
                    </div>

                    <DataTable :value="applicants" stripedRows showGridlines responsiveLayout="scroll"
                        :emptyMessage="'No applicants to display'" :lazy="true" paginator :rows="rows"
                        :totalRecords="totalRecords" :first="first" @page="onPageChange"
                        paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                        <!-- Sequence Number & Name Column -->
                        <Column header="Applicant" style="min-width: 300px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-400 text-sm">#{{
                                        slotProps.data.sequence_number_by_school_course || '-' }}</span>
                                    <div class="flex items-center gap-2">
                                        <img v-if="slotProps.data.gender == 'M'" src="/images/male-avatar.png"
                                            alt="avatar" class="rounded-full w-8 h-8" />
                                        <img v-if="slotProps.data.gender == 'F'" src="/images/female-avatar.png"
                                            alt="avatar" class="rounded-full w-8 h-8" />
                                        <div>
                                            <div class="font-semibold text-gray-800 text-sm">
                                                {{ slotProps.data.last_name }}, {{ slotProps.data.first_name }} {{
                                                    slotProps.data.middle_name || '' }} {{ slotProps.data.extension_name ||
                                                    '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Parent/Guardian Column -->
                        <Column header="Parent/Guardian" style="min-width: 200px">
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

                        <!-- School Column -->
                        <Column header="School" style="min-width: 120px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium" v-if="slotProps.data.scholarship_grant[0]?.school">
                                    {{ slotProps.data.scholarship_grant[0].school.shortname }}
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Course Column -->
                        <Column header="Course" style="min-width: 120px">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.scholarship_grant[0]?.course">
                                    <div class="text-sm font-bold">{{
                                        slotProps.data.scholarship_grant[0].course.shortname }}</div>
                                    <div v-if="slotProps.data.scholarship_grant[0]?.program"
                                        class="text-xs text-gray-600">
                                        [{{ slotProps.data.scholarship_grant[0].program.shortname }}]
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Year Level Column -->
                        <Column header="Year Level" style="width: 100px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium text-center">
                                    {{ slotProps.data.scholarship_grant[0]?.year_level || '-' }}
                                </div>
                            </template>
                        </Column>

                        <!-- Contact Number Column (hidden when JPM columns visible) -->
                        <Column header="Contact #" v-if="!showJpmColumns" style="min-width: 120px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium">{{ slotProps.data.contact_no || '-' }}</div>
                            </template>
                        </Column>

                        <!-- Date Filed Column (hidden when JPM columns visible) -->
                        <Column header="Date Filed" v-if="!showJpmColumns" style="min-width: 110px">
                            <template #body="slotProps">
                                <div class="text-sm font-medium">
                                    {{ slotProps.data.date_filed ? moment(slotProps.data.date_filed).format(`MMM DD,
                                    YYYY`) : '-' }}
                                </div>
                            </template>
                        </Column>

                        <!-- Remarks Column (hidden when JPM columns visible) -->
                        <Column header="Remarks" v-if="!showJpmColumns" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="text-xs">{{ slotProps.data.remarks || '-' }}</div>
                            </template>
                        </Column>

                        <!-- JPM Tagged Column (visible when JPM columns enabled) -->
                        <Column header="Tagged" v-if="hasPermission('can-view-jpm') && showJpmColumns"
                            style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-2">
                                    <div class="flex gap-2">
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <Checkbox v-model="slotProps.data.is_jpm_member" binary
                                                :disabled="hasRole('user')"
                                                @update:modelValue="(value) => updateJpmStatus({ id: slotProps.data.profile_id, is_jpm_member: value })" />
                                            <span>applicant</span>
                                        </label>
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <Checkbox v-model="slotProps.data.is_father_jpm" binary
                                                :disabled="hasRole('user')"
                                                @update:modelValue="(value) => updateJpmStatus({ id: slotProps.data.profile_id, is_father_jpm: value })" />
                                            <span>father</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <Checkbox v-model="slotProps.data.is_guardian_jpm" binary
                                                :disabled="hasRole('user')"
                                                @update:modelValue="(value) => updateJpmStatus({ id: slotProps.data.profile_id, is_guardian_jpm: value })" />
                                            <span>guardian</span>
                                        </label>
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <Checkbox v-model="slotProps.data.is_mother_jpm" binary
                                                :disabled="hasRole('user')"
                                                @update:modelValue="(value) => updateJpmStatus({ id: slotProps.data.profile_id, is_mother_jpm: value })" />
                                            <span>mother</span>
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- JPM Remarks Column (visible when JPM columns enabled) -->
                        <Column v-if="hasPermission('can-view-jpm') && showJpmColumns" style="min-width: 220px">
                            <template #header>
                                <div class="flex justify-between items-center w-full">
                                    <span>JPM Remarks</span>
                                    <i class="pi pi-comment text-gray-400 text-sm"
                                        v-tooltip.top="'Click on any row to edit remarks'"></i>
                                </div>
                            </template>
                            <template #body="slotProps">
                                <div class="text-xs cursor-pointer hover:bg-gray-100 p-1 rounded relative group"
                                    @click="editJpmRemarks(slotProps.data)" v-tooltip.top="'Click to edit JPM remarks'">
                                    <div class="flex justify-between items-center">
                                        <span>{{ slotProps.data.jpm_remarks || 'Click to add remarks...' }}</span>
                                        <Button icon="pi pi-pencil" severity="secondary" size="small" class="ml-1" text
                                            rounded />
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Actions Column -->
                        <Column header="Actions" style="width: 250px">
                            <template #body="slotProps">
                                <div class="flex gap-1 justify-center">
                                    <Button icon="pi pi-check-circle" label="Review" severity="success" size="small"
                                        outlined v-tooltip.top="'Review Application & View Profile'"
                                        @click="openProfileReviewModal(slotProps.data)"
                                        v-if="hasPermission('create-scholar-profile')" />
                                    <Button icon="pi pi-user-edit" severity="warning" size="small" rounded outlined
                                        v-tooltip.top="'Edit Applicant'" @click="editApplicant(slotProps.data)" />
                                    <Button icon="pi pi-trash" severity="danger" size="small" rounded outlined
                                        v-tooltip.top="'Delete Applicant'"
                                        @click="confirmDeleteApplicant(slotProps.data)"
                                        v-if="hasPermission('delete-scholar-profile') && !hasRole('user')" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </Panel>
            </div>
        </div>

        <!-- JPM Remarks Modal -->
        <Dialog v-model:visible="showJpmRemarksModal" :style="{ width: '500px' }" header="Edit JPM Remarks"
            :modal="true">
            <div class="space-y-4">
                <div v-if="selectedProfileForRemarks" class="bg-gray-100 p-3 rounded border-l-4 border-blue-500">
                    <div class="font-semibold text-blue-700">
                        {{ selectedProfileForRemarks.last_name }}, {{ selectedProfileForRemarks.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">{{ selectedProfileForRemarks.contact_no }}</div>
                </div>

                <div class="space-y-2">
                    <label for="jpmRemarks" class="block text-sm font-medium text-gray-700">JPM Remarks</label>
                    <Textarea id="jpmRemarks" v-model="jpmRemarksForm" rows="4" placeholder="Enter JPM remarks..."
                        class="w-full" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeJpmRemarksModal" />
                    <Button label="Save" severity="info" @click="updateJpmRemarks" />
                </div>
            </template>
        </Dialog>

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
            :style="{ width: '95vw', maxWidth: '1400px' }" :maximizable="true" class="p-fluid">

            <div v-if="selectedApplicantForReview">
                <!-- Header Summary Card -->
                <Card class="bg-blue-50 border-blue-200 mb-4">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <Avatar :label="getApplicantInitials(selectedApplicantForReview)" size="xlarge"
                                shape="circle" class="bg-blue-600 text-white" />
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900">{{
                                            getApplicantFullName(selectedApplicantForReview) }}</h3>
                                        <div class="flex items-center gap-4 mt-1">
                                            <span class="text-sm text-gray-600">
                                                <i class="pi pi-phone mr-1"></i>{{ selectedApplicantForReview.contact_no
                                                    || 'N/A' }}
                                            </span>
                                            <span class="text-sm text-gray-600">
                                                <i class="pi pi-envelope mr-1"></i>{{ selectedApplicantForReview.email
                                                    || 'N/A' }}
                                            </span>
                                            <span class="text-sm text-gray-600">
                                                <i class="pi pi-calendar mr-1"></i>Filed: {{
                                                    formatDate(selectedApplicantForReview.date_filed) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Complete Queue Sequencing Information -->
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="text-center px-2 py-1 bg-indigo-100 rounded-md border border-indigo-200">
                                            <div class="text-sm font-bold text-indigo-600 leading-tight">
                                                #{{ selectedApplicantForReview.sequence_number || '-' }}
                                            </div>
                                            <div class="text-xs text-indigo-700 leading-tight">{{
                                                selectedApplicantForReview.scholarship_grant?.[0]?.program?.shortname }}
                                            </div>
                                        </div>
                                        <div
                                            class="text-center px-2 py-1 bg-purple-100 rounded-md border border-purple-200">
                                            <div class="text-sm font-bold text-purple-600 leading-tight">
                                                #{{ selectedApplicantForReview.sequence_number_by_course || '-' }}
                                            </div>
                                            <div class="text-xs text-purple-700 leading-tight">{{
                                                selectedApplicantForReview.scholarship_grant?.[0]?.course?.shortname }}
                                            </div>
                                        </div>
                                        <div
                                            class="text-center px-2 py-1 bg-orange-100 rounded-md border border-orange-200">
                                            <div class="text-sm font-bold text-orange-600 leading-tight">
                                                #{{ selectedApplicantForReview.daily_sequence_number || '-' }}
                                            </div>
                                            <div class="text-xs text-orange-700 leading-tight">{{
                                                formatDate(selectedApplicantForReview.date_filed) }}</div>
                                        </div>
                                        <div
                                            class="text-center px-2 py-1 bg-green-100 rounded-md border border-green-200">
                                            <div class="text-sm font-bold text-green-600 leading-tight">
                                                #{{ selectedApplicantForReview.sequence_number_by_school_course || '-'
                                                }}
                                            </div>
                                            <div class="text-xs text-green-700 leading-tight">{{
                                                selectedApplicantForReview.scholarship_grant?.[0]?.school?.shortname
                                                }}/{{
                                                    selectedApplicantForReview.scholarship_grant?.[0]?.course?.shortname }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Tabbed Content -->
                <Tabs value="applicationReview">
                    <TabList>
                        <Tab value="applicationReview">Application Review</Tab>
                        <Tab value="profileInformation">Profile Information</Tab>
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
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Personal Information -->
                                <Card>
                                    <template #title>
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-user text-blue-600"></i>
                                            Personal Information
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="space-y-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full
                                                        Name</label>
                                                    <InputText :value="getApplicantFullName(selectedApplicantForReview)"
                                                        readonly class="w-full" />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                                    <InputText
                                                        :value="selectedApplicantForReview.gender === 'M' ? 'Male' : selectedApplicantForReview.gender === 'F' ? 'Female' : 'N/A'"
                                                        readonly class="w-full" />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary
                                                        Contact</label>
                                                    <InputText :value="selectedApplicantForReview.contact_no || 'N/A'"
                                                        readonly class="w-full" />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-1">Secondary
                                                        Contact</label>
                                                    <InputText :value="selectedApplicantForReview.contact_no_2 || 'N/A'"
                                                        readonly class="w-full" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Email
                                                    Address</label>
                                                <InputText :value="selectedApplicantForReview.email || 'N/A'" readonly
                                                    class="w-full" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent's
                                                    Gross
                                                    Monthly
                                                    Income</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.gross_monthly_income || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                                <Textarea :value="getApplicantFullAddress(selectedApplicantForReview)"
                                                    readonly class="w-full" rows="2" />
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <!-- Academic Information -->
                                <Card>
                                    <template #title>
                                        <div class="flex items-center gap-2 p-2">
                                            <i class="pi pi-graduation-cap text-lg text-green-600"></i>
                                            <span class="font-medium">Academic Information</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.program?.shortname || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">School</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.school?.shortname || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.course?.shortname || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Year
                                                    Level</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.year_level || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Academic
                                                    Year</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.academic_year || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.scholarship_grant?.[0]?.term || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                            <Textarea
                                                :value="selectedApplicantForReview.remarks || 'No remarks provided'"
                                                readonly class="w-full" rows="2" />
                                        </div>
                                    </template>
                                </Card>
                            </div>

                            <!-- Family Information -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                                <!-- Father Information -->
                                <Card>
                                    <template #title>
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-user text-blue-600"></i>
                                            Father Information
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                                <InputText :value="selectedApplicantForReview.father_name || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.father_occupation || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.father_contact_no || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <!-- Mother Information -->
                                <Card>
                                    <template #title>
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-user text-pink-600"></i>
                                            Mother Information
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                                <InputText :value="selectedApplicantForReview.mother_name || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.mother_occupation || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.mother_contact_no || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <!-- Guardian Information -->
                                <Card>
                                    <template #title>
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-users text-purple-600"></i>
                                            Guardian Information
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                                <InputText :value="selectedApplicantForReview.guardian_name || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.guardian_occupation || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                                <InputText
                                                    :value="selectedApplicantForReview.guardian_contact_no || 'N/A'"
                                                    readonly class="w-full" />
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </Dialog>

        <!-- Applicant Profile Modal - handles both route-level and local modal state -->
        <ApplicantProfileModal v-if="(props.action == 'create' || props.action == 'update') || showApplicantModal"
            :action="showApplicantModal ? modalAction : props.action"
            :profile="showApplicantModal ? modalProfile : props.profile" :is-inline-modal="showApplicantModal"
            @close="closeApplicantModal" />
    </AdminLayout>
</template>