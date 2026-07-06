<template>
    <IosModal :visible="show" width="560px" max-width="92vw" body-style="padding: 0;"
        @update:visible="handleClose">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleClose">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">TechVoc — Approval List</span>
        </template>

        <template #header-right>
            <button class="ios-nav-btn ios-nav-action" @click="generateReport" :disabled="generating">
                <AppIcon v-if="generating" name="spinner" :size="16" class="animate-spin" />
                <template v-else>Generate</template>
            </button>
        </template>

        <div class="rw-body">
            <!-- ═══ SECTION 1: STATUS ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.section1 }">
                <div class="rw-section-header" @click="toggleSection('section1')">
                    <div class="rw-section-step">1</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Status</span>
                        <span class="rw-section-summary">{{ statusLabel }}</span>
                    </div>
                    <AppIcon :name="collapsed.section1 ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.section1" class="rw-section-body">
                    <div class="rw-chip-group">
                        <button v-for="opt in statusChoices" :key="opt.value"
                            :class="['rw-chip', selectedStatus === opt.value && 'rw-chip-active']"
                            @click="selectedStatus = opt.value">
                            <span v-if="opt.color" class="rw-chip-dot" :style="{ background: opt.color }"></span>
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ SECTION 2: FILTERS ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.section2 }">
                <div class="rw-section-header" @click="toggleSection('section2')">
                    <div class="rw-section-step">2</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Filters</span>
                        <span class="rw-section-summary">{{ activeFiltersCount > 0 ? activeFiltersCount + ` filter(s)
                            active` :
                            `No filters set` }}</span>
                    </div>
                    <AppIcon :name="collapsed.section2 ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.section2" class="rw-section-body">
                    <div class="rw-filters-grid">
                        <div class="rw-filter-item">
                            <label class="rw-label">School</label>
                            <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All Schools"
                                class="rw-select" :multiple="false" />
                        </div>
                        <div class="rw-filter-item">
                            <label class="rw-label">Course</label>
                            <CourseSelect v-model="selectedCourses" label="name" custom-placeholder="All Courses"
                                class="rw-select" :load-all-when-no-program="true" />
                        </div>
                    </div>

                    <div class="rw-field-group" style="margin-top: 12px;">
                        <label class="rw-label">Date Range</label>
                        <div class="rw-date-row">
                            <DatePicker v-model="dateFrom" placeholder="From date"
                                showButtonBar dateFormat="M dd, yy"
                                class="rw-datepicker" showIcon iconDisplay="input" />
                            <span class="rw-date-sep">—</span>
                            <DatePicker v-model="dateTo" placeholder="To date"
                                showButtonBar dateFormat="M dd, yy"
                                class="rw-datepicker" showIcon iconDisplay="input" />
                        </div>
                    </div>

                    <div v-if="activeFiltersCount > 0" class="rw-clear-row">
                        <button class="rw-clear-btn" @click="clearAllFilters">
                            <AppIcon name="x" :size="12" /> Clear {{ activeFiltersCount }} filter(s)
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ SECTION 3: OPTIONS ═══ -->
            <div class="rw-section" :class="{ 'rw-section-collapsed': collapsed.section3 }">
                <div class="rw-section-header" @click="toggleSection('section3')">
                    <div class="rw-section-step">3</div>
                    <div class="rw-section-title">
                        <span class="rw-section-label">Options</span>
                        <span class="rw-section-summary">{{ customTitle?.trim() || 'Default Title' }}</span>
                    </div>
                    <AppIcon :name="collapsed.section3 ? 'chevron-down' : 'chevron-up'" :size="14"
                        class="rw-section-chevron" />
                </div>
                <div v-show="!collapsed.section3" class="rw-section-body">
                    <div class="rw-field-group">
                        <label class="rw-label">Report Title</label>
                        <InputText v-model="customTitle" class="rw-input" placeholder="Custom title (optional)" />
                    </div>
                    <div class="rw-field-group">
                        <label class="rw-label">Default Amount (pre-fill ₱)</label>
                        <InputNumber v-model="defaultAmount" placeholder="0.00" :minFractionDigits="2"
                            :maxFractionDigits="2" mode="currency" currency="PHP" class="rw-input" />
                    </div>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import moment from 'moment';
import axios from 'axios';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    program: { type: Object, default: null },
});

const emit = defineEmits(['update:show', 'report-generated']);

// ─── Collapsible sections ───
const collapsed = ref({ section1: false, section2: false, section3: false });
function toggleSection(name) {
    collapsed.value[name] = !collapsed.value[name];
}

// ─── Status ───
const selectedStatus = ref('active');

const statusChoices = [
    { value: 'active', label: 'Active', color: '#10B981' },
    { value: null, label: 'All Statuses', color: null },
    { value: 'pending', label: 'Pending', color: '#F59E0B' },
    { value: 'interviewed', label: 'Interviewed', color: '#6366F1' },
    { value: 'approved_history', label: 'Approved', color: '#3B82F6' },
    { value: 'completed', label: 'Completed', color: '#6B7280' },
];

const statusLabel = computed(() => {
    const found = statusChoices.find(s => s.value === selectedStatus.value);
    return found ? found.label : 'All Statuses';
});

// ─── Filters ───
const generating = ref(false);
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedSchools = ref(null);
const selectedCourses = ref(null);

const activeFiltersCount = computed(() => {
    let count = 0;
    if (dateFrom.value || dateTo.value) count++;
    if (selectedSchools.value) count++;
    if (selectedCourses.value) count++;
    return count;
});

// ─── Options ───
const customTitle = ref('');
const defaultAmount = ref(null);

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    selectedSchools.value = null;
    selectedCourses.value = null;
}

function handleClose() {
    emit('update:show', false);
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        const params = {};
        if (props.program) params.program = props.program.id;
        if (selectedSchools.value) {
            params.school = selectedSchools.value.shortname;
        }
        if (selectedCourses.value) {
            params.courses = selectedCourses.value.name;
        }
        if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
        if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');
        if (selectedStatus.value) params.unified_status = selectedStatus.value;

        const response = await axios.get(route('profile.generateReport'), { params });
        let records = [];
        if (Array.isArray(response.data)) records = response.data;
        else if (Array.isArray(response.data?.data)) records = response.data.data;

        emit('report-generated', {
            records,
            program: props.program,
            title: customTitle.value?.trim(),
            status: selectedStatus.value,
            defaultAmount: defaultAmount.value,
        });
        handleClose();
    } catch (error) {
        console.error('TechVoc report failed:', error);
    } finally {
        generating.value = false;
    }
}
</script>

<style scoped>
/* ─── Body ─── */
.rw-body {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* ─── Section ─── */
.rw-section {
    background: #fff;
    border: 1px solid #e5e5ea;
    border-radius: 11px;
    overflow: hidden;
    transition: box-shadow 0.15s;
}
.rw-section-collapsed {
    box-shadow: none;
}

.rw-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    cursor: pointer;
    user-select: none;
    transition: background 0.1s;
}
.rw-section-header:hover {
    background: #f9f9fb;
}

.rw-section-step {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #007AFF;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rw-section-title {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.rw-section-label {
    font-size: 13px;
    font-weight: 600;
    color: #1c1c1e;
}

.rw-section-summary {
    font-size: 11px;
    color: #8e8e93;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rw-section-chevron {
    color: #c7c7cc;
    flex-shrink: 0;
}

.rw-section-body {
    padding: 6px 14px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* ─── Chip Group ─── */
.rw-chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.rw-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid #dcdce0;
    background: #fff;
    font-size: 12px;
    font-weight: 500;
    color: #3a3a3c;
    cursor: pointer;
    transition: all 0.15s;
}

.rw-chip:hover {
    border-color: #007AFF;
    color: #007AFF;
}

.rw-chip-active {
    background: #007AFF;
    border-color: #007AFF;
    color: #fff;
}

.rw-chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ─── Filters Grid ─── */
.rw-filters-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.rw-filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* ─── Fields ─── */
.rw-field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rw-label {
    font-size: 12px;
    font-weight: 600;
    color: #555;
}

/* ─── Select / Input ─── */
.rw-select {
    width: 100%;
}

.rw-input {
    width: 100%;
}

/* ─── Date Row ─── */
.rw-date-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rw-datepicker {
    flex: 1;
    min-width: 0;
}

.rw-date-sep {
    color: #8e8e93;
    font-size: 13px;
    flex-shrink: 0;
}

/* ─── Clear ─── */
.rw-clear-row {
    display: flex;
    justify-content: flex-end;
}

.rw-clear-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #ff3b30;
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.1s;
}

.rw-clear-btn:hover {
    background: rgba(255, 59, 48, 0.08);
}
</style>
