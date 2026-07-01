<template>
    <IosModal :visible="show" width="760px" max-width="92vw" body-style="padding: 0;"
        @update:visible="$emit('update:show', $event)">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="close">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">Endorse Profiles</span>
        </template>

        <template #header-right>
            <button class="ios-nav-btn ios-nav-action" @click="submitEndorse"
                :disabled="endorsing || selectedProfileIds.size === 0" v-tooltip.bottom="'Endorse Selected Profiles'">
                <AppIcon v-if="endorsing" name="spinner" :size="16" class="animate-spin" />
                <template v-else>Endorse {{ selectedCount ? `(${selectedCount})` : '' }}</template>
            </button>
        </template>

        <div class="endorse-body">
            <!-- ═══ SECTION 1: FILTERS ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.filters }">
                <div class="rw-section-header" @click="toggleSection('filters')">
                    <div class="rw-section-step">1</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Filters</span>
                        <span class="rw-section-summary">{{ activeFiltersCount > 0 ? activeFiltersCount + ` filter(s)
                            active` :
                            'No filters set' }}</span>
                    </div>
                    <AppIcon :name="collapsed.filters ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.filters" class="rw-section-body">
                    <div class="rw-field-group">
                        <label class="rw-label">Status</label>
                        <div class="rw-chip-group">
                            <button v-for="opt in statusChoices" :key="opt.value"
                                :class="['rw-chip', selectedStatus === opt.value && 'rw-chip-active']"
                                @click="selectedStatus = opt.value">
                                <span v-if="opt.color" class="rw-chip-dot" :style="{ background: opt.color }"></span>
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <div class="rw-filters-grid">
                        <div class="rw-filter-item">
                            <label class="rw-label">Program</label>
                            <ProgramSelect v-model="selectedPrograms" label="shortname"
                                custom-placeholder="All Programs" class="rw-select" :multiple="true" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">School</label>
                            <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All Schools"
                                class="rw-select" :multiple="true" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">Course</label>
                            <CourseSelect v-model="selectedCourses" :scholarship-program-id="selectedPrograms?.[0]?.id"
                                label="shortname" custom-placeholder="All Courses" :multiple="true" class="rw-select" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">Municipality</label>
                            <MunicipalitySelect v-model="selectedMunicipalities" custom-placeholder="All Municipalities"
                                class="rw-select" :multiple="true" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">Year Level</label>
                            <YearLevelSelect v-model="selectedYearLevels" custom-placeholder="All Year Levels"
                                class="rw-select" :multiple="true" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">Grant Provision</label>
                            <MultiSelect v-model="selectedGrantProvisions" :options="grantProvisionOptions"
                                optionLabel="label" optionValue="value" placeholder="All Provisions" showClear filter
                                :filterFields="['label', 'value']" :maxSelectedLabels="3"
                                :selectedItemsLabel="'{0} provisions selected'" showSelectAll class="rw-select" />
                        </div>
                    </div>

                    <div class="rw-field-group" style="margin-top: 12px;">
                        <label class="rw-label">Date Range</label>
                        <div class="rw-date-row">
                            <DatePicker v-model="dateFrom" placeholder="From date" showButtonBar dateFormat="M dd, yy"
                                class="rw-datepicker" showIcon iconDisplay="input" />
                            <span class="rw-date-sep">—</span>
                            <DatePicker v-model="dateTo" placeholder="To date" showButtonBar dateFormat="M dd, yy"
                                class="rw-datepicker" showIcon iconDisplay="input" />
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <AppButton label="Fetch Profiles" icon="search" severity="info" size="small"
                            @click="fetchProfiles" :disabled="fetching" />
                        <AppButton v-if="activeFiltersCount > 0" severity="secondary" outlined size="small"
                            icon="history" label="Clear Filters" @click="clearAllFilters" />
                    </div>
                </div>
            </div>

            <!-- ═══ SECTION 2: PROFILE LIST ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.list }">
                <div class="rw-section-header" @click="toggleSection('list')">
                    <div class="rw-section-step">2</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Select Profiles</span>
                        <span class="rw-section-summary">{{ profiles.length }} profiles loaded · {{ selectedCount }}
                            selected</span>
                    </div>
                    <AppIcon :name="collapsed.list ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.list" class="rw-section-body">
                    <div v-if="profiles.length === 0 && !fetching" class="text-center py-8 text-gray-400">
                        <AppIcon name="users" :size="32" class="mx-auto mb-2" />
                        <p class="text-sm">No profiles loaded. Set filters and click "Fetch Profiles".</p>
                    </div>

                    <div v-if="fetching" class="flex items-center justify-center py-8 text-gray-400">
                        <AppIcon name="spinner" :size="24" class="animate-spin mr-2" />
                        <span class="text-sm">Loading profiles...</span>
                    </div>

                    <div v-if="profiles.length > 0" class="flex items-center gap-3 mb-3 px-1">
                        <button class="text-xs text-blue-600 font-medium hover:underline" @click="toggleSelectAll">
                            {{ selectedCount === profiles.length ? 'Deselect All' : 'Select All' }}
                        </button>
                        <span class="text-xs text-gray-400">{{ selectedCount }} of {{ profiles.length }} selected</span>
                    </div>

                    <div v-if="profiles.length > 0" class="endorse-profile-list">
                        <label v-for="profile in profiles" :key="profile.profile_id" class="endorse-profile-row"
                            :class="{ 'endorse-profile-selected': selectedProfileIds.has(profile.profile_id) }">
                            <input type="checkbox" :checked="selectedProfileIds.has(profile.profile_id)"
                                @change="toggleProfile(profile.profile_id)" class="endorse-checkbox" />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-gray-800 truncate">
                                    {{ profile.full_name || 'N/A' }}
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-500">{{ profile.unique_id || '—' }}</span>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="text-xs text-gray-500">{{ profile.municipality || '—' }}</span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold', getStatusBadge(profile)]">
                                    {{ getProfileStatusLabel(profile) }}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ═══ SECTION 3: ENDORSEMENT DETAILS (OPTIONAL) ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.details }">
                <div class="rw-section-header" @click="toggleSection('details')">
                    <div class="rw-section-step">3</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Endorsement Details</span>
                        <span class="rw-section-summary">Optional · {{ endorsementDetails ? 'Details provided' : 'None'
                            }}</span>
                    </div>
                    <AppIcon :name="collapsed.details ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.details" class="rw-section-body">
                    <div class="rw-field-group">
                        <label class="rw-label">Details / Remarks</label>
                        <Textarea v-model="endorsementDetails" class="rw-input"
                            placeholder="Optional: reason for endorsement, who endorsed to, etc." rows="3" autoResize />
                        <span class="rw-hint">Leave blank if not applicable.</span>
                    </div>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import moment from 'moment';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import { useSystemOptions } from '@/composables/useSystemOptions';

// Custom Select Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['update:show', 'endorsed']);

// ─── State ───
const endorsing = ref(false);
const fetching = ref(false);
const profiles = ref([]);
const selectedProfileIds = ref(new Set());
const endorsementDetails = ref('');
const collapsed = ref({ filters: false, list: false, details: false });

// Filters
const selectedStatus = ref(null);
const selectedPrograms = ref([]);
const selectedSchools = ref([]);
const selectedCourses = ref([]);
const selectedMunicipalities = ref([]);
const selectedYearLevels = ref([]);
const selectedGrantProvisions = ref([]);
const dateFrom = ref(null);
const dateTo = ref(null);

// ─── Options ───
const grantProvisionOptions = useSystemOptions('grant_provision');

const statusChoices = [
    { value: null, label: 'All Statuses', color: null },
    { value: 'pending', label: 'Pending', color: '#F59E0B' },
    { value: 'interviewed', label: 'Interviewed', color: '#6366F1' },
    { value: 'approved', label: 'Approved', color: '#3B82F6' },
    { value: 'active', label: 'Active', color: '#10B981' },
    { value: 'completed', label: 'Completed', color: '#6B7280' },
    { value: 'withdrawn', label: 'Withdrawn', color: '#8B5CF6' },
    { value: 'loa', label: 'Leave of Absence', color: '#D97706' },
    { value: 'suspended', label: 'Suspended', color: '#DC2626' },
    { value: 'endorsed', label: 'Endorsed', color: '#8B5CF6' },
];

// ─── Computed ───
const selectedCount = computed(() => selectedProfileIds.value.size);

const activeFiltersCount = computed(() => {
    let count = 0;
    if (dateFrom.value || dateTo.value) count++;
    if (selectedPrograms.value?.length > 0) count++;
    if (selectedSchools.value?.length > 0) count++;
    if (selectedCourses.value?.length > 0) count++;
    if (selectedMunicipalities.value?.length > 0) count++;
    if (selectedYearLevels.value?.length > 0) count++;
    if (selectedGrantProvisions.value?.length > 0) count++;
    return count;
});

// ─── Methods ───
function toggleSection(name) {
    collapsed.value[name] = !collapsed.value[name];
}

function close() {
    emit('update:show', false);
}

function buildFilterParams() {
    const params = {};
    if (selectedStatus.value) params.unified_status = selectedStatus.value;
    if (selectedPrograms.value?.length > 0) {
        params.program = selectedPrograms.value.map(p => p.id).join(',');
    }
    if (selectedSchools.value?.length > 0) {
        params.school = selectedSchools.value.map(s => s.shortname).join(',');
    }
    if (selectedCourses.value?.length > 0) {
        params.courses = selectedCourses.value.map(c => c.name).join(',');
    }
    if (selectedMunicipalities.value?.length > 0) {
        params.municipality = selectedMunicipalities.value.map(m => m.name).join(',');
    }
    if (selectedYearLevels.value?.length > 0) {
        params.year_level = selectedYearLevels.value.map(y => y.value).join(',');
    }
    if (selectedGrantProvisions.value?.length > 0) {
        params.grant_provision = selectedGrantProvisions.value.join(',');
    }
    if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
    if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');
    return params;
}

async function fetchProfiles() {
    if (fetching.value) return;
    fetching.value = true;
    try {
        const params = buildFilterParams();
        const response = await axios.get(route('endorse.preview'), { params });
        const data = response.data?.data || response.data || [];
        profiles.value = data;
        selectedProfileIds.value = new Set();
    } catch (error) {
        console.error('Failed to fetch profiles:', error);
    } finally {
        fetching.value = false;
    }
}

function toggleProfile(profileId) {
    const newSet = new Set(selectedProfileIds.value);
    if (newSet.has(profileId)) {
        newSet.delete(profileId);
    } else {
        newSet.add(profileId);
    }
    selectedProfileIds.value = newSet;
}

function toggleSelectAll() {
    if (selectedProfileIds.value.size === profiles.value.length) {
        selectedProfileIds.value = new Set();
    } else {
        selectedProfileIds.value = new Set(profiles.value.map(p => p.profile_id));
    }
}

function clearMultiSelection(selection) {
    if (Array.isArray(selection.value)) {
        selection.value.splice(0, selection.value.length);
        return;
    }
    selection.value = [];
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    clearMultiSelection(selectedCourses);
    clearMultiSelection(selectedPrograms);
    clearMultiSelection(selectedSchools);
    clearMultiSelection(selectedMunicipalities);
    clearMultiSelection(selectedYearLevels);
    clearMultiSelection(selectedGrantProvisions);
}

function getProfileStatusLabel(profile) {
    const record = profile.latest_scholarship_record || profile.scholarship_grant?.[0];
    const status = record?.unified_status;
    const map = {
        pending: 'Pending', interviewed: 'Interviewed', approved: 'Approved',
        active: 'Active', denied: 'Denied', completed: 'Completed',
        withdrawn: 'Withdrawn', loa: 'LOA', suspended: 'Suspended',
        endorsed: 'Endorsed',
    };
    return map[status] || status || '—';
}

function getStatusBadge(profile) {
    const record = profile.latest_scholarship_record || profile.scholarship_grant?.[0];
    const status = record?.unified_status;
    const colorMap = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        interviewed: 'bg-indigo-100 text-indigo-800 border-indigo-300',
        approved: 'bg-blue-100 text-blue-800 border-blue-300',
        active: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        denied: 'bg-red-100 text-red-800 border-red-300',
        completed: 'bg-gray-100 text-gray-600 border-gray-300',
        withdrawn: 'bg-purple-100 text-purple-800 border-purple-300',
        loa: 'bg-amber-100 text-amber-800 border-amber-300',
        suspended: 'bg-red-100 text-red-700 border-red-400',
        endorsed: 'bg-violet-100 text-violet-800 border-violet-300',
    };
    return (colorMap[status] || 'bg-gray-100 text-gray-600 border-gray-300') + ' border';
}

async function submitEndorse() {
    if (endorsing.value || selectedProfileIds.value.size === 0) return;
    endorsing.value = true;
    try {
        const response = await axios.post(route('endorse.store'), {
            profile_ids: Array.from(selectedProfileIds.value),
            endorsement_details: endorsementDetails.value?.trim() || null,
        });
        emit('endorsed', response.data);
        emit('update:show', false);
    } catch (error) {
        console.error('Failed to endorse profiles:', error);
    } finally {
        endorsing.value = false;
    }
}
</script>

<style scoped>
/* Reuse report wizard styles */
.endorse-body {
    padding: 4px 12px 12px;
    max-height: 78vh;
    overflow-y: auto;
}

.rw-section {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 8px;
    background: #fff;
    overflow: hidden;
}

.rw-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    cursor: pointer;
    user-select: none;
    -webkit-user-select: none;
}

.rw-section-step {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #007AFF;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rw-section-collapsed .rw-section-step {
    background: #d1d5db;
}

.rw-section-title {
    flex: 1;
    min-width: 0;
}

.rw-section-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
}

.rw-section-summary {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    line-height: 1.3;
    margin-top: 1px;
}

.rw-section-chevron {
    color: #9ca3af;
    flex-shrink: 0;
}

.rw-section-body {
    padding: 4px 12px 12px;
    border-top: 1px solid #f3f4f6;
}

.rw-field-group {
    margin-bottom: 12px;
}

.rw-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin-bottom: 6px;
}

.rw-hint {
    display: block;
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}

.rw-chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.rw-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 500;
    color: #374151;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.12s;
}

.rw-chip:hover {
    background: #e5e7eb;
}

.rw-chip-active {
    background: #eff6ff;
    border-color: #007AFF;
    color: #007AFF;
    font-weight: 600;
}

.rw-chip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.rw-filters-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.rw-filter-item {
    min-width: 0;
}

.rw-select {
    width: 100%;
}

.rw-date-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rw-datepicker {
    flex: 1;
}

.rw-date-sep {
    color: #9ca3af;
    font-size: 13px;
    flex-shrink: 0;
}

.rw-input {
    width: 100%;
}

.rw-input :deep(.p-inputtext) {
    width: 100%;
    font-size: 12px;
}

.rw-input :deep(.p-textarea) {
    width: 100%;
    font-size: 12px;
}

/* Profile list */
.endorse-profile-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
    max-height: 360px;
    overflow-y: auto;
}

.endorse-profile-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.1s;
    border: 1px solid transparent;
}

.endorse-profile-row:hover {
    background: #f9fafb;
}

.endorse-profile-selected {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.endorse-checkbox {
    width: 16px;
    height: 16px;
    accent-color: #007AFF;
    flex-shrink: 0;
    cursor: pointer;
}

/* Dark mode */
.dark .rw-section {
    background: #1f2937;
    border-color: #374151;
}

.dark .rw-section-label {
    color: #e5e7eb;
}

.dark .rw-section-body {
    border-color: #374151;
}

.dark .rw-label {
    color: #9ca3af;
}

.dark .rw-chip {
    color: #d1d5db;
    background: #374151;
    border-color: #4b5563;
}

.dark .rw-chip:hover {
    background: #4b5563;
}

.dark .rw-chip-active {
    background: #1e3a5f;
    border-color: #3b82f6;
    color: #60a5fa;
}

.dark .endorse-profile-row:hover {
    background: #111827;
}

.dark .endorse-profile-selected {
    background: #1e3a5f;
    border-color: #2563eb;
}
</style>
