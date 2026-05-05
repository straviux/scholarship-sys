<template>

    <Head title="JPM Tagging" />

    <AdminLayout>
        <div>
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-4 sm:!px-6 lg:!px-8 scholarship-toolbar">
                <template #start>
                    <div class="flex min-w-0 items-center gap-3 scholarship-toolbar__brand">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-500 via-teal-500 to-sky-500 text-white shadow-sm">
                            <AppIcon name="tags" :size="28" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl font-bold text-gray-700 sm:text-2xl">JPM Tagging</h1>
                            <p class="text-sm text-gray-600">Review scholars and isolate JPM tagging into its own
                                workflow</p>
                        </div>
                    </div>
                </template>

                <template #end>
                    <div class="flex items-center gap-3">
                        <AppButton icon="printer" label="Generate Report" severity="secondary" rounded
                            @click="showReportModal = true" />
                    </div>
                </template>
            </Toolbar>

            <FloatingDrawer v-model:visible="showFilterDrawer" header="All Filters" position="right"
                class="!w-[calc(100vw-1rem)] sm:!w-[min(600px,calc(100vw-1rem))] !max-w-[calc(100vw-1rem)]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Program</label>
                        <ProgramSelect v-model="drawerFilter.program" label="shortname"
                            custom-placeholder="All Programs" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Course</label>
                        <CourseSelect v-model="drawerFilter.course" label="name" custom-placeholder="All Courses"
                            size="small" class="w-full" :scholarship-program-id="drawerFilter.program?.id" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">School</label>
                        <SchoolSelect v-model="drawerFilter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" class="w-full" :multiple="false" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Year Level</label>
                        <YearLevelSelect v-model="drawerFilter.year_level" custom-placeholder="All Year Levels"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Academic Year</label>
                        <Select v-model="drawerFilter.academic_year" :options="academicYearOptions" optionLabel="label"
                            optionValue="value" placeholder="All Years" showClear size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Term</label>
                        <TermSelect v-model="drawerFilter.term" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Municipality</label>
                        <MunicipalitySelect v-model="drawerFilter.municipality" custom-placeholder="All Municipalities"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Barangay</label>
                        <BarangaySelect v-model="drawerFilter.barangay" :municipality-id="drawerFilter.municipality?.id"
                            custom-placeholder="All Barangays" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Grant Provision</label>
                        <Select v-model="drawerFilter.grant_provision" :options="grantProvisionOptions"
                            optionLabel="label" optionValue="value" placeholder="All Provisions" size="small"
                            class="w-full" showClear />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Status</label>
                        <Select v-model="drawerFilter.unified_status" :options="unifiedStatusOptions"
                            optionLabel="label" optionValue="value" placeholder="All Statuses" showClear size="small"
                            class="w-full" filter>
                            <template #filter="{ value, updateModel }">
                                <InputGroup>
                                    <InputText :value="value" @input="updateModel($event.target.value)"
                                        placeholder="Search status..." class="w-full" />
                                    <AppButton v-if="value" icon="times" severity="secondary" text size="small"
                                        @click="updateModel('')" />
                                </InputGroup>
                            </template>
                        </Select>
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Review Status</label>
                        <Select v-model="drawerFilter.review_status" :options="reviewStatusOptions" optionLabel="label"
                            optionValue="value" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">JPM Status</label>
                        <Select v-model="drawerFilter.jpm_status" :options="jpmStatusOptions" optionLabel="label"
                            optionValue="value" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Contract</label>
                        <Select v-model="drawerFilter.contract_status" :options="attachmentStatusOptions"
                            placeholder="All" size="small" class="w-full" showClear optionLabel="label"
                            optionValue="value" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-xs font-medium text-gray-600">Voucher</label>
                        <Select v-model="drawerFilter.voucher_status" :options="attachmentStatusOptions"
                            placeholder="All" size="small" class="w-full" showClear optionLabel="label"
                            optionValue="value" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                    <AppButton severity="secondary" outlined size="small" icon="history" label="Clear"
                        @click="clearDrawerFilters" />
                    <AppButton label="Apply" icon="filter-fill" severity="info" size="small"
                        @click="applyDrawerFilters" />
                </div>
            </FloatingDrawer>

            <Panel class="!rounded-4xl overflow-hidden mt-8">
                <div
                    class="mb-4 flex flex-col gap-3 rounded-4xl bg-gray-50 p-3 dark:bg-[#1e242b] -mt-2 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center xl:max-w-md">
                        <InputGroup>
                            <InputGroupAddon>
                                <AppIcon name="search" :size="14" class="text-gray-400" />
                            </InputGroupAddon>
                            <InputText v-model="globalFilter" placeholder="Search by scholar name or details"
                                size="small" @keyup.enter="triggerSearch()" />
                        </InputGroup>
                        <AppButton icon="filter" severity="warn" text rounded @click="openDrawer()"
                            v-tooltip.bottom="'More Filters'" />
                    </div>
                    <div class="flex w-full flex-wrap items-center justify-between gap-3 xl:w-auto xl:justify-end">
                        <div class="flex items-center gap-2">
                            <RecordsSelect v-model="records" label="label" class="w-28" size="small" />
                            <span class="text-sm text-gray-600">/ <strong>{{ totalRecords }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4 flex flex-wrap items-end gap-3">
                    <div class="flex flex-col">
                        <ProgramSelect v-model="filter.program" label="shortname" custom-placeholder="All Programs"
                            size="small" />
                    </div>
                    <div class="flex flex-col">
                        <CourseSelect v-model="filter.course" label="name" custom-placeholder="All Courses"
                            size="small" />
                    </div>
                    <div class="flex flex-col">
                        <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                            size="small" />
                    </div>
                    <div class="flex flex-col">
                        <Select v-model="filter.unified_status" :options="unifiedStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="All Statuses" showClear size="small" filter>
                            <template #filter="{ value, updateModel }">
                                <InputGroup>
                                    <InputText :value="value" @input="updateModel($event.target.value)"
                                        placeholder="Search status..." class="w-full" />
                                    <AppButton v-if="value" icon="times" severity="secondary" text size="small"
                                        @click="updateModel('')" />
                                </InputGroup>
                            </template>
                        </Select>
                    </div>
                    <div class="flex flex-col">
                        <Select v-model="filter.review_status" :options="reviewStatusOptions" optionLabel="label"
                            optionValue="value" size="small" class="min-w-[160px]" />
                    </div>
                    <div class="flex flex-col">
                        <Select v-model="filter.jpm_status" :options="jpmStatusOptions" optionLabel="label"
                            optionValue="value" size="small" class="min-w-[160px]" />
                    </div>
                    <AppButton v-if="activeFilterTags.length" icon="times" severity="danger" text rounded size="small"
                        @click="clearFilters" v-tooltip.bottom="'Clear Filters'" />
                </div>

                <div v-if="activeFilterTags.length" class="mb-6 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500">Active Filters:</span>
                    <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                        <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    </Tag>
                </div>

                <DataView :value="profileRows" dataKey="profile_id" layout="list">
                    <template #list="{ items }">
                        <div v-if="items.length" class="space-y-4 pb-4">
                            <article v-for="profile in items" :key="profile.profile_id"
                                class="mx-auto max-w-5xl overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-white via-white to-slate-50 shadow-sm">
                                <div class="border-b border-slate-100 bg-white/80 px-5 py-4 backdrop-blur">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 space-y-3">
                                            <div>
                                                <h2 class="truncate text-lg font-semibold text-slate-900">
                                                    {{ fullName(profile) }}
                                                </h2>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    {{ latestProgramLabel(profile) }}
                                                    <template v-if="latestSchoolLabel(profile)">
                                                        • {{ latestSchoolLabel(profile) }}
                                                    </template>
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <Tag :severity="jpmStatus(profile).severity" rounded>
                                                    <span class="text-xs font-semibold">{{ jpmStatus(profile).label
                                                    }}</span>
                                                </Tag>
                                                <Tag v-if="jpmStatus(profile).detail" severity="contrast" rounded>
                                                    <span class="text-xs">{{ jpmStatus(profile).detail }}</span>
                                                </Tag>
                                                <Tag v-if="profile.latest_scholarship_record?.unified_status"
                                                    :severity="statusSeverity(profile.latest_scholarship_record.unified_status)"
                                                    rounded>
                                                    <span class="text-xs font-semibold">{{
                                                        statusLabel(profile.latest_scholarship_record.unified_status)
                                                    }}</span>
                                                </Tag>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row lg:flex-col">
                                            <AppButton icon="tags"
                                                :label="hasExistingTagging(profile) ? 'Edit Tagging' : 'Tag JPM'"
                                                severity="info" rounded :outlined="!hasExistingTagging(profile)"
                                                :disabled="!canManageJpm" @click="openJpmModal(profile)" />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-4 px-5 py-5 md:grid-cols-2">
                                    <section class="rounded-3xl border border-slate-100 bg-white px-4 py-4">
                                        <div
                                            class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                            Family</div>
                                        <div class="space-y-3 text-sm text-slate-700">
                                            <div>
                                                <div
                                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                    Mother</div>
                                                <div class="mt-1">{{ presentText(profile.mother_name) }}</div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                    Father</div>
                                                <div class="mt-1">{{ presentText(profile.father_name) }}</div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                    Guardian</div>
                                                <div class="mt-1">{{ presentText(profile.guardian_name) }}</div>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="rounded-3xl border border-slate-100 bg-white px-4 py-4">
                                        <div
                                            class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                            Remarks</div>
                                        <div class="space-y-4 text-sm text-slate-700">
                                            <div>
                                                <div
                                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                    Profile
                                                    Remarks</div>
                                                <p class="mt-1 leading-6 text-slate-700">{{ previewText(profile.remarks)
                                                }}</p>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                    JPM Note</div>
                                                <p class="mt-1 leading-6 text-slate-700">{{
                                                    previewText(profile.jpm_remarks) }}</p>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </article>
                        </div>

                        <div v-else
                            class="mx-auto max-w-3xl rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-8 py-16 text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                <AppIcon name="tags" :size="26" />
                            </div>
                            <h2 class="mt-5 text-lg font-semibold text-slate-700">No profiles matched this JPM queue
                            </h2>
                            <p class="mt-2 text-sm text-slate-500">Adjust the filters or clear them to load more
                                profiles for tagging.
                            </p>
                        </div>
                    </template>
                </DataView>

                <Paginator :rows="rows" :first="first" :totalRecords="totalRecords" @page="onPageChange"
                    template="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} profiles"
                    class="border-none bg-transparent px-0 pb-0 pt-2" />
            </Panel>
        </div>

        <JpmModal :show="showJpmModal" :profile="selectedProfileForJpm" @update:show="showJpmModal = $event" />
        <JpmReportModal :show="showReportModal" :applied-filters="props.filters" :active-filter-tags="activeFilterTags"
            :total-records="totalRecords" @update:show="showReportModal = $event" />
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { usePermission } from '@/composable/permissions';
import { useFilterManager } from '@/composables/useFilterManager';
import { stripHtml } from '@/utils/sanitize';
import FloatingDrawer from '@/Components/FloatingDrawer.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import JpmModal from '@/Pages/Applicants/Modal/JpmModal.vue';
import JpmReportModal from '@/Pages/JpmTagging/Modal/ReportModal.vue';

const FILTER_LABELS = {
    name: 'Name',
    program: 'Program',
    school: 'School',
    course: 'Course',
    municipality: 'Municipality',
    barangay: 'Barangay',
    year_level: 'Year Level',
    academic_year: 'Academic Year',
    term: 'Term',
    grant_provision: 'Grant Provision',
    unified_status: 'Status',
    review_status: 'Review Status',
    jpm_status: 'JPM Status',
    contract_status: 'Contract',
    voucher_status: 'Voucher',
};

const FILTER_VALUE_LABELS = {
    review_status: {
        all: 'All',
        needs_review: 'Needs Review',
        tagged: 'Tagged',
    },
    jpm_status: {
        all: 'All',
        jpm: 'JPM',
        not_member: 'Not Member',
    },
};

const VALID_REVIEW_STATUS_VALUES = ['needs_review', 'tagged'];
const VALID_JPM_STATUS_VALUES = ['jpm', 'not_member'];

const DRAWER_FILTER_KEYS = ['program', 'course', 'school', 'municipality', 'barangay', 'year_level', 'academic_year', 'term', 'grant_provision', 'unified_status', 'review_status', 'jpm_status', 'contract_status', 'voucher_status'];
const NULLABLE_DRAWER_FILTER_KEYS = new Set(['grant_provision', 'unified_status', 'contract_status', 'voucher_status', 'academic_year', 'term']);
const ALL_VALUE_FILTER_KEYS = new Set(['review_status', 'jpm_status']);

const SCHOLARSHIP_STATUS_META = {
    pending: { label: 'Pending', severity: 'warn' },
    interviewed: { label: 'Interviewed', severity: 'info' },
    approved: { label: 'Approved', severity: 'success' },
    active: { label: 'Active', severity: 'success' },
    denied: { label: 'Denied', severity: 'danger' },
    suspended: { label: 'Suspended', severity: 'contrast' },
    completed: { label: 'Completed', severity: 'secondary' },
};

const normalizedText = (value) => stripHtml(value || '').trim();

const resolveFilterDisplay = (key, value) => {
    if (typeof value === 'object') {
        return value.shortname || value.name || value.value || JSON.stringify(value);
    }

    return FILTER_VALUE_LABELS[key]?.[value] ?? value;
};

const props = defineProps({
    profiles: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    profiles_total: {
        type: [String, Number],
        default: 0,
    },
    records: {
        type: [String, Number],
        default: 10,
    },
});

const { hasPermission } = usePermission();

const {
    filters: filter,
    globalFilter,
    records,
    rows,
    first,
    totalRecords,
    search: triggerSearch,
    clear: clearAllFilters,
    onPageChange,
} = useFilterManager({
    routeName: 'jpm-tagging.index',
    props,
    filterPropName: 'filters',
    filterDefs: [
        { key: 'name', type: 'text', default: '' },
        { key: 'program', type: 'select', default: '', extract: value => value?.shortname?.toLowerCase() },
        { key: 'school', type: 'select', default: '', extract: value => value?.shortname?.toLowerCase() },
        { key: 'course', type: 'select', default: '', extract: value => value?.name?.toLowerCase() },
        { key: 'year_level', type: 'select', default: '', extract: value => value?.value?.toLowerCase() },
        { key: 'academic_year', type: 'text', default: '' },
        { key: 'term', type: 'select', default: '', extract: value => value?.value?.toLowerCase() },
        { key: 'municipality', type: 'select', default: '', extract: value => value?.name?.toLowerCase() },
        { key: 'barangay', type: 'select', default: '', extract: value => value?.name?.toLowerCase() },
        { key: 'grant_provision', type: 'text', default: null },
        { key: 'unified_status', type: 'text', default: null },
        { key: 'review_status', type: 'text', default: 'all' },
        { key: 'jpm_status', type: 'text', default: 'all' },
        { key: 'contract_status', type: 'text', default: null },
        { key: 'voucher_status', type: 'text', default: null },
    ],
    beforeSearch(params) {
        if (params.contract_status && !['with', 'without'].includes(params.contract_status)) {
            delete params.contract_status;
        }

        if (params.voucher_status && !['with', 'without'].includes(params.voucher_status)) {
            delete params.voucher_status;
        }

        if (!VALID_REVIEW_STATUS_VALUES.includes(params.review_status)) {
            delete params.review_status;
        }

        if (!VALID_JPM_STATUS_VALUES.includes(params.jpm_status)) {
            delete params.jpm_status;
        }
    },
});

const activeFilterTags = computed(() => {
    const tags = [];

    if (globalFilter.value) {
        tags.push({ key: 'global_search', label: 'Search', display: String(globalFilter.value) });
    }

    for (const [key, label] of Object.entries(FILTER_LABELS)) {
        const value = filter.value[key];

        if (!value || value === 'all') {
            continue;
        }

        const display = resolveFilterDisplay(key, value);

        tags.push({ key, label, display: String(display) });
    }

    return tags;
});

watch(
    () => [filter.value.program, filter.value.course, filter.value.year_level, filter.value.unified_status, filter.value.review_status, filter.value.jpm_status],
    () => {
        triggerSearch();
    },
);

watch(records, () => {
    triggerSearch();
});

const showFilterDrawer = ref(false);
const drawerFilter = ref({});

const openDrawer = () => {
    const snapshot = {};

    for (const key of DRAWER_FILTER_KEYS) {
        snapshot[key] = filter.value[key];
    }

    drawerFilter.value = snapshot;
    showFilterDrawer.value = true;
};

const applyDrawerFilters = () => {
    for (const key of DRAWER_FILTER_KEYS) {
        filter.value[key] = drawerFilter.value[key];
    }

    triggerSearch();
    showFilterDrawer.value = false;
};

const clearDrawerFilters = () => {
    for (const key of DRAWER_FILTER_KEYS) {
        if (ALL_VALUE_FILTER_KEYS.has(key)) {
            drawerFilter.value[key] = 'all';
            continue;
        }

        drawerFilter.value[key] = NULLABLE_DRAWER_FILTER_KEYS.has(key) ? null : '';
    }
};

const clearFilters = clearAllFilters;

const academicYearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];

    for (let year = currentYear; year >= currentYear - 10; year -= 1) {
        years.push({ label: `${year}-${year + 1}`, value: `${year}-${year + 1}` });
    }

    for (let year = currentYear; year >= currentYear - 10; year -= 1) {
        years.push({ label: year.toString(), value: year.toString() });
    }

    return years;
});

const unifiedStatusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Interviewed', value: 'interviewed' },
    { label: 'Approved', value: 'approved' },
    { label: 'Active', value: 'active' },
    { label: 'Denied', value: 'denied' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Completed', value: 'completed' },
];

const reviewStatusOptions = Object.entries(FILTER_VALUE_LABELS.review_status).map(([value, label]) => ({
    label,
    value,
}));

const jpmStatusOptions = Object.entries(FILTER_VALUE_LABELS.jpm_status).map(([value, label]) => ({
    label,
    value,
}));

const attachmentStatusOptions = [
    { label: 'With Attachment', value: 'with' },
    { label: 'Without Attachment', value: 'without' },
];

const grantProvisionOptions = [
    { label: 'Full', value: 'full' },
    { label: 'Half', value: 'half' },
    { label: 'Quarter', value: 'quarter' },
    { label: 'Monthly Allowance', value: 'monthly_allowance' },
    { label: 'Tuition Only', value: 'tuition_only' },
    { label: 'Stipend Only', value: 'stipend_only' },
];

const profileRows = computed(() => props.profiles?.data ?? []);
const canManageJpm = computed(() => hasPermission('jpm.manage'));
const showJpmModal = ref(false);
const showReportModal = ref(false);
const selectedProfileForJpm = ref(null);

const openJpmModal = (profile) => {
    if (!canManageJpm.value) {
        return;
    }

    selectedProfileForJpm.value = profile;
    showJpmModal.value = true;
};

const previewText = (value) => {
    const text = normalizedText(value);
    return text || 'No remarks available.';
};

const presentText = (value) => {
    const text = normalizedText(value);
    return text || 'Not provided';
};

const fullName = (profile) => {
    const parts = [profile.last_name, ', ', profile.first_name, profile.middle_name, profile.extension_name].filter(Boolean);
    return parts.join(' ').replace(' ,', ',');
};

const latestProgramLabel = (profile) => profile.latest_scholarship_record?.program?.shortname || 'Program not set';
const latestSchoolLabel = (profile) => profile.latest_scholarship_record?.school?.shortname || profile.latest_scholarship_record?.course?.shortname || '';

const hasExistingTagging = (profile) => Boolean(
    profile.is_jpm_member
    || profile.is_father_jpm
    || profile.is_mother_jpm
    || profile.is_guardian_jpm
    || profile.is_not_jpm
    || normalizedText(profile.jpm_remarks),
);

const jpmStatus = (profile) => {
    const members = [];

    if (profile.is_jpm_member) members.push('Applicant');
    if (profile.is_father_jpm) members.push('Father');
    if (profile.is_mother_jpm) members.push('Mother');
    if (profile.is_guardian_jpm) members.push('Guardian');

    if (members.length > 0) {
        return {
            label: 'JPM Member',
            detail: members.join(', '),
            severity: 'success',
        };
    }

    if (profile.is_not_jpm) {
        return {
            label: 'Not JPM',
            detail: '',
            severity: 'warn',
        };
    }

    if (normalizedText(profile.jpm_remarks)) {
        return {
            label: 'Tagged With Note',
            detail: '',
            severity: 'info',
        };
    }

    return {
        label: 'Untagged',
        detail: '',
        severity: 'secondary',
    };
};

const statusLabel = (status) => {
    return SCHOLARSHIP_STATUS_META[status]?.label || status || 'Unknown';
};

const statusSeverity = (status) => {
    return SCHOLARSHIP_STATUS_META[status]?.severity || 'secondary';
};
</script>