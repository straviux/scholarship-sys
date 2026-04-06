<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Toolbar from 'primevue/toolbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Panel from 'primevue/panel';
import Badge from 'primevue/badge';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import RadioButton from 'primevue/radiobutton';
import Tag from 'primevue/tag';
import BudgetMonitoringModal from '@/Pages/PaymentMonitoring/Modal/BudgetMonitoringModal.vue';
import BudgetReportModal from '@/Pages/PaymentMonitoring/Modal/BudgetReportModal.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';
import { exportBudgetReportExcel } from '@/Pages/PaymentMonitoring/Excel/BudgetReportExcel.js';
import TermSelect from '@/Components/selects/TermSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

const props = defineProps({
    paymentData: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', transaction_status: '', academic_year: '', semester: '' }),
    },
    budgetParticulars: {
        type: Array,
        default: () => [],
    },
    disbursedByProgramYear: {
        type: Object,
        default: () => ({}),
    },
    fiscalYears: {
        type: Array,
        default: () => [],
    },
});

// Reactive state
const searchInput = ref(props.filters.search);
const selectedStatus = ref(props.filters.transaction_status || 'all');
const selectedAcademicYear = ref(props.filters.academic_year);
const selectedSemester = ref(props.filters.semester);
const selectedProgram = ref(props.filters.program || '');
const selectedCourse = ref(props.filters.course ? (Array.isArray(props.filters.course) ? props.filters.course : []) : []);
const selectedSchool = ref(props.filters.school || '');

// Watch for program changes and clear course selection
watch(selectedProgram, () => {
    selectedCourse.value = [];
});

// Debounce timer to prevent infinite filter loops
let filterTimeout;

// Budget Monitoring modal
const showBudgetModal = ref(false);

// OBR Report modal
const showBudgetReportModal = ref(false);

// PDF Preview
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref('');
const pdfPreviewTitle = ref('');
const pdfPreviewSize = ref('landscape');
const pdfExcelFn = ref(null);

function onBudgetReportPreview({ htmlDoc, title, paperSize, reportData, today }) {
    pdfPreviewHtml.value = htmlDoc;
    pdfPreviewTitle.value = title;
    pdfPreviewSize.value = paperSize;
    pdfExcelFn.value = reportData
        ? () => exportBudgetReportExcel({ reportData, today })
        : null;
    showPdfPreview.value = true;
}

// Format currency helper
const formatCurrency = (val) =>
    parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Methods
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        // Extract course names from objects if needed
        const courseValues = selectedCourse.value ? selectedCourse.value.map(course =>
            typeof course === 'object' ? course.name : course
        ) : [];

        router.get(route('payment-monitoring.index'), {
            search: searchInput.value,
            transaction_status: selectedStatus.value === 'all' ? '' : selectedStatus.value,
            academic_year: selectedAcademicYear.value,
            semester: selectedSemester.value,
            program: typeof selectedProgram.value === 'object' ? selectedProgram.value?.name : selectedProgram.value,
            course: courseValues.length > 0 ? courseValues : [],
            school: typeof selectedSchool.value === 'object' ? selectedSchool.value?.name : selectedSchool.value,
        }, {
            preserveState: false,
            replace: true,
        });
    }, 800);
};

const clearFilters = () => {
    clearTimeout(filterTimeout);
    searchInput.value = '';
    selectedStatus.value = 'all';
    selectedAcademicYear.value = '';
    selectedSemester.value = '';
    selectedProgram.value = '';
    selectedCourse.value = [];
    selectedSchool.value = '';
    router.get(route('payment-monitoring.index'), {}, {
        preserveState: false,
        replace: true,
    });
};

// Computed property for status options including "No OBR assigned"
const statusOptionsWithNoOBR = computed(() => {
    return [
        { label: 'Show All', value: '' },
        { label: 'No OBR Assigned', value: 'no-obr' },
        { label: 'On Process', value: 'on process' },
        { label: 'Suspend', value: 'suspend' },
        { label: 'Completed', value: 'completed' }
    ];
});

// Computed property for status counts
const statusCounts = computed(() => {
    const counts = {
        all: props.paymentData.length,
        'no-obr': props.paymentData.filter(item => !item.transaction_status).length,
        'LOA': props.paymentData.filter(item => item.transaction_status === 'LOA').length,
        'Irregular': props.paymentData.filter(item => item.transaction_status === 'Irregular').length,
        'Transferred': props.paymentData.filter(item => item.transaction_status === 'Transferred').length,
        'Claimed': props.paymentData.filter(item => item.transaction_status === 'Claimed').length,
        'Paid': props.paymentData.filter(item => item.transaction_status === 'Paid').length,
        'On Process': props.paymentData.filter(item => item.transaction_status === 'On Process').length,
        'Denied': props.paymentData.filter(item => item.transaction_status === 'Denied').length,
    };
    return counts;
});



// Computed property for filtered data
const filteredData = computed(() => {
    let data = props.paymentData;

    if (searchInput.value) {
        data = data.filter((item) =>
            item.scholar_name.toLowerCase().includes(searchInput.value.toLowerCase())
        );
    }

    if (selectedStatus.value && selectedStatus.value !== 'all') {
        if (selectedStatus.value === 'no-obr') {
            data = data.filter((item) => !item.transaction_status);
        } else {
            data = data.filter((item) => item.transaction_status === selectedStatus.value);
        }
    }

    if (selectedAcademicYear.value && selectedAcademicYear.value !== '') {
        data = data.filter((item) => item.academic_year === selectedAcademicYear.value);
    }

    if (selectedSemester.value) {
        data = data.filter((item) => item.term === selectedSemester.value);
    }

    if (selectedProgram.value && selectedProgram.value !== '') {
        const programValue = typeof selectedProgram.value === 'object' ? selectedProgram.value.name : selectedProgram.value;
        data = data.filter((item) => item.program === programValue);
    }

    if (selectedCourse.value && Array.isArray(selectedCourse.value) && selectedCourse.value.length > 0) {
        const courseNames = selectedCourse.value.map(course =>
            typeof course === 'object' ? course.name : course
        );
        data = data.filter((item) => courseNames.includes(item.course));
    }

    if (selectedSchool.value && selectedSchool.value !== '') {
        const schoolValue = typeof selectedSchool.value === 'object' ? selectedSchool.value.name : selectedSchool.value;
        data = data.filter((item) => item.school === schoolValue);
    }

    // Sort alphabetically by scholar name
    data = data.sort((a, b) => a.scholar_name.localeCompare(b.scholar_name));

    return data;
});

// PrimeVue Tag severity mapping
const getStatusSeverity = (status) => {
    const map = {
        'LOA': 'info',
        'Irregular': 'warn',
        'Transferred': 'secondary',
        'Claimed': 'contrast',
        'Paid': 'success',
        'On Process': 'warn',
        'Denied': 'danger',
    };
    return map[status] || 'secondary';
};

// Status badge styling (legacy — kept for reference)
const getStatusBadgeClass = (status) => {
    const baseClass = 'px-2 sm:px-3 py-1 rounded-full text-xs font-semibold';
    const statusMap = {
        'LOA': 'bg-blue-100 text-blue-800',
        'Irregular': 'bg-yellow-100 text-yellow-800',
        'Transferred': 'bg-purple-100 text-purple-800',
        'Claimed': 'bg-indigo-100 text-indigo-800',
        'Paid': 'bg-green-100 text-green-800',
        'On Process': 'bg-orange-100 text-orange-800',
        'Denied': 'bg-red-100 text-red-800',
        '': 'bg-gray-100 text-gray-800',
    };
    return `${baseClass} ${statusMap[status] || statusMap['']}`;
};

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Expanded rows for accordion (blur effect on other rows)
// PrimeVue 4 uses object format { [dataKey]: true } when dataKey is set
const expandedRows = ref({});
const collapseExpandedRows = () => {
    expandedRows.value = {};
};

// Group filteredData by scholar — latest transaction shown as preview row
const groupedData = computed(() => {
    const groups = {};
    filteredData.value.forEach(item => {
        if (!groups[item.profile_id]) {
            groups[item.profile_id] = {
                profile_id: item.profile_id,
                scholar_name: item.scholar_name,
                transactions: [],
            };
        }
        groups[item.profile_id].transactions.push(item);
    });

    return Object.values(groups).map(g => {
        const sorted = [...g.transactions].sort((a, b) => {
            const dateA = a.date_obligated ? new Date(a.date_obligated) : new Date(0);
            const dateB = b.date_obligated ? new Date(b.date_obligated) : new Date(0);
            return dateB - dateA;
        });
        return {
            ...g,
            transactions: sorted,
            latest: sorted[0],
            totalAmount: g.transactions.reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0),
        };
    }).sort((a, b) => a.scholar_name.localeCompare(b.scholar_name));
});
</script>

<template>

    <Head title="Payment Monitoring" />

    <AdminLayout>

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 short:mb-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-dollar text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700 short:text-xl">Payment Monitoring</h1>
                            <p class="text-sm text-gray-600 short:text-xs">Track OBR status for active scholarship
                                records</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-3">
                        <Button v-if="props.budgetParticulars.length" icon="pi pi-wallet" label="Budget Monitoring"
                            severity="secondary" rounded outlined size="small" @click="showBudgetModal = true"
                            v-tooltip.bottom="'Budget Monitoring'" />
                        <Button icon="pi pi-file-edit" label="Budget Allotment Report" severity="secondary" rounded
                            outlined size="small" @click="showBudgetReportModal = true"
                            v-tooltip.bottom="'Budget Report'" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6 short:mb-3 !rounded-4xl overflow-hidden">
                <div class="flex items-end gap-3 short:gap-2 -mt-6 short:-mt-3 flex-wrap">
                    <!-- Search -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Scholar Name</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-gray-400" />
                            <InputText v-model="searchInput" placeholder="Search by name..." size="small" />
                        </IconField>
                    </div>

                    <!-- Academic Year -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                        <AcademicYearSelect v-model="selectedAcademicYear" size="small" />
                    </div>

                    <!-- Term -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Term</label>
                        <TermSelect v-model="selectedSemester" size="small" />
                    </div>

                    <!-- Program -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                        <ProgramSelect v-model="selectedProgram" size="small" />
                    </div>

                    <!-- Course -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                        <CourseSelect v-model="selectedCourse" :scholarship-program-id="selectedProgram?.id"
                            :multiple="true" size="small" />
                    </div>

                    <!-- School -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                        <SchoolSelect v-model="selectedSchool" size="small" />
                    </div>

                    <!-- Clear -->
                    <Button severity="secondary" outlined rounded size="small" icon="pi pi-history"
                        @click="clearFilters" v-tooltip.bottom="`Clear Filters`" />
                </div>

                <!-- Status filter row -->
                <div class="flex flex-wrap gap-2 short:gap-1 mt-4 short:mt-2 pt-4 short:pt-2 border-t border-gray-100">
                    <label v-for="opt in [
                        { label: 'All', value: 'all', count: statusCounts.all },
                        { label: 'No OBR', value: 'no-obr', count: statusCounts['no-obr'] },
                        { label: 'LOA', value: 'LOA', count: statusCounts['LOA'] },
                        { label: 'Irregular', value: 'Irregular', count: statusCounts['Irregular'] },
                        { label: 'Transferred', value: 'Transferred', count: statusCounts['Transferred'] },
                        { label: 'Claimed', value: 'Claimed', count: statusCounts['Claimed'] },
                        { label: 'Paid', value: 'Paid', count: statusCounts['Paid'] },
                        { label: 'On Process', value: 'On Process', count: statusCounts['On Process'] },
                        { label: 'Denied', value: 'Denied', count: statusCounts['Denied'] },
                    ]" :key="opt.value" class="flex items-center gap-1.5 cursor-pointer">
                        <RadioButton v-model="selectedStatus" name="pmStatus" :value="opt.value" />
                        <span class="text-sm text-gray-700 whitespace-nowrap">{{ opt.label }}</span>
                        <Badge :value="opt.count" severity="secondary" />
                    </label>
                </div>
            </Panel>

            <!-- Stats Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 short:gap-2 mb-6 short:mb-3">
                <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-blue-600">{{ statusCounts.all }}</div>
                    <div class="text-xs text-gray-500">Total Records</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-orange-500">{{ statusCounts['On Process'] }}</div>
                    <div class="text-xs text-gray-500">On Process</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-green-600">{{ statusCounts['Paid'] }}</div>
                    <div class="text-xs text-gray-500">Paid</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-gray-400">{{ statusCounts['no-obr'] }}</div>
                    <div class="text-xs text-gray-500">No OBR</div>
                </div>
            </div>

            <!-- Data Table -->
            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <div class="flex items-center justify-between mb-4 short:mb-2 -mt-2">
                    <span class="text-sm text-gray-500">
                        {{ groupedData.length }} scholar(s) &middot; {{ filteredData.length }} transaction(s)
                    </span>
                </div>

                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="groupedData"
                    :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" class="text-sm" showGridlines
                    stripedRows scrollable dataKey="profile_id" v-model:expandedRows="expandedRows"
                    :rowClass="(row) => Object.keys(expandedRows).length > 0 && !expandedRows[row.profile_id] ? 'row-blurred' : ''">

                    <Column expander headerClass="w-12" bodyClass="w-12" />

                    <Column field="scholar_name" header="Scholar" sortable style="min-width: 200px">
                        <template #body="{ data }">
                            <a :href="route('scholarship.profile.show', data.profile_id)" target="_blank"
                                class="text-blue-600 hover:text-blue-800 hover:underline font-medium flex items-center gap-1">
                                {{ data.scholar_name }}
                                <i class="pi pi-external-link" style="font-size: 9pt;"></i>
                            </a>
                            <Badge v-if="data.transactions.length > 1" :value="`${data.transactions.length} records`"
                                severity="secondary" size="small" class="mt-1" />
                        </template>
                    </Column>

                    <Column header="Latest Term" style="min-width: 130px">
                        <template #body="{ data }">
                            <div class="text-xs font-medium">{{ data.latest.academic_year || '—' }}</div>
                            <div class="text-xs text-gray-500">
                                {{ data.latest.term || '' }}
                                <span v-if="data.latest.year_level"> · Yr {{ data.latest.year_level }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="OBR Type / Kind" style="min-width: 160px">
                        <template #body="{ data }">
                            <span v-if="data.latest.obr_type" class="text-xs font-medium" :class="{
                                'text-gray-700': data.latest.obr_type === 'REGULAR',
                                'text-yellow-600': data.latest.obr_type === 'FINANCIAL ASSISTANCE',
                                'text-purple-600': data.latest.obr_type === 'REIMBURSEMENT',
                            }">{{ data.latest.obr_type }}</span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                            <div class="text-xs mt-0.5">
                                <span v-if="data.latest.disbursement_type" class="font-medium" :class="{
                                    'text-blue-600': data.latest.disbursement_type === 'disbursements',
                                    'text-green-600': data.latest.disbursement_type === 'payroll',
                                }">
                                    {{ data.latest.disbursement_type === 'disbursements' ? 'Disbursement'
                                        : data.latest.disbursement_type === 'payroll' ? 'Payroll'
                                            : data.latest.disbursement_type }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="min-width: 130px">
                        <template #body="{ data }">
                            <Tag v-if="data.latest.transaction_status" :value="data.latest.transaction_status" rounded
                                :severity="getStatusSeverity(data.latest.transaction_status)" />
                            <span v-else class="text-gray-400 text-xs italic">No OBR</span>
                        </template>
                    </Column>

                    <Column header="Latest Amount" style="min-width: 120px">
                        <template #body="{ data }">
                            <span v-if="data.latest.amount" class="font-semibold text-green-700 text-xs">
                                ₱{{ parseFloat(data.latest.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })
                                }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </template>
                    </Column>

                    <Column header="OBR No." style="min-width: 140px">
                        <template #body="{ data }">
                            <span v-if="data.latest.obr_no" class="font-mono text-blue-700 text-xs">{{
                                data.latest.obr_no }}</span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                            <div v-if="data.latest.date_obligated" class="text-xs text-gray-500 mt-0.5">
                                {{ formatDate(data.latest.date_obligated) }}
                            </div>
                        </template>
                    </Column>

                    <Column header="Pre-Total" style="min-width: 120px">
                        <template #body="{ data }">
                            <span v-if="data.totalAmount > 0" class="font-bold text-green-800 text-xs">
                                ₱{{ data.totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                            <div v-if="data.transactions.length > 1" class="text-xs text-gray-400 mt-0.5">
                                {{ data.transactions.length }} records
                            </div>
                        </template>
                    </Column>

                    <!-- Expansion: all transactions for this scholar -->
                    <template #expansion="slotProps">
                        <div class="p-4 bg-gray-50 dark:bg-[#1e242b] rounded-2xl mx-4 mb-3">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">
                                    All Fund Transactions
                                    <Badge :value="slotProps.data.transactions.length" severity="info" size="small"
                                        class="ml-1" />
                                </h4>
                            </div>

                            <DataTable :value="slotProps.data.transactions" showGridlines stripedRows
                                class="text-xs nested-pm-table">
                                <Column header="Acad. Year / Term" style="min-width: 130px">
                                    <template #body="{ data }">
                                        <div class="font-medium">{{ data.academic_year || '—' }}</div>
                                        <div class="text-gray-500">
                                            {{ data.term || '' }}
                                            <span v-if="data.year_level"> · Yr {{ data.year_level }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="OBR Type" style="min-width: 140px">
                                    <template #body="{ data }">
                                        <span v-if="data.obr_type" class="font-medium" :class="{
                                            'text-gray-700': data.obr_type === 'REGULAR',
                                            'text-yellow-600': data.obr_type === 'FINANCIAL ASSISTANCE',
                                            'text-purple-600': data.obr_type === 'REIMBURSEMENT',
                                        }">{{ data.obr_type }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                                <Column header="Type" style="min-width: 110px">
                                    <template #body="{ data }">
                                        <span v-if="data.disbursement_type" class="font-medium" :class="{
                                            'text-blue-600': data.disbursement_type === 'disbursements',
                                            'text-green-600': data.disbursement_type === 'payroll',
                                        }">
                                            {{ data.disbursement_type === 'disbursements' ? 'Disbursement'
                                                : data.disbursement_type === 'payroll' ? 'Payroll'
                                                    : data.disbursement_type }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                                <Column header="Status" style="min-width: 120px">
                                    <template #body="{ data }">
                                        <Tag v-if="data.transaction_status" :value="data.transaction_status" rounded
                                            :severity="getStatusSeverity(data.transaction_status)" />
                                        <span v-else class="text-gray-400 italic">No OBR</span>
                                    </template>
                                </Column>
                                <Column header="Amount" style="min-width: 110px">
                                    <template #body="{ data }">
                                        <span v-if="data.amount" class="font-semibold text-green-700">
                                            ₱{{ parseFloat(data.amount).toLocaleString('en-US', {
                                                minimumFractionDigits:
                                                    2
                                            }) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                                <Column header="OBR Date" style="min-width: 110px">
                                    <template #body="{ data }">
                                        <span v-if="data.date_obligated">{{ formatDate(data.date_obligated) }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                                <Column header="OBR No." style="min-width: 130px">
                                    <template #body="{ data }">
                                        <span v-if="data.obr_no" class="font-mono text-blue-700">{{ data.obr_no
                                            }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                                <Column header="Remarks" style="min-width: 160px">
                                    <template #body="{ data }">
                                        <span v-if="data.remarks" v-safe-html="data.remarks"
                                            class="text-gray-600"></span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </Column>
                            </DataTable>

                            <!-- Pre-Total row -->
                            <div
                                class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ slotProps.data.transactions.length }}
                                    transaction(s)</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">Pre-Total:</span>
                                    <span class="text-lg font-bold text-green-700">
                                        ₱{{ slotProps.data.totalAmount.toLocaleString('en-US', {
                                            minimumFractionDigits:
                                                2
                                        }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div class="py-12 text-center text-gray-500">
                            <i class="pi pi-inbox text-3xl mb-2 block"></i>
                            <p>No payment records found matching the current filters.</p>
                        </div>
                    </template>
                </DataTable>
            </Panel>
        </div>

        <!-- Budget Monitoring Modal -->
        <BudgetMonitoringModal v-model:visible="showBudgetModal" :budgetParticulars="props.budgetParticulars"
            :disbursedByProgramYear="props.disbursedByProgramYear" :fiscalYears="props.fiscalYears"
            @preview="onBudgetReportPreview" />

        <!-- OBR Allotment Report Modal -->
        <BudgetReportModal v-model:visible="showBudgetReportModal" :fiscalYears="props.fiscalYears"
            @preview="onBudgetReportPreview" />

        <!-- PDF Preview -->
        <PdfPreviewModal :show="showPdfPreview" @update:show="showPdfPreview = $event" :htmlDoc="pdfPreviewHtml"
            :title="pdfPreviewTitle" :paperSize="pdfPreviewSize" :onExcel="pdfExcelFn" />

    </AdminLayout>
</template>

<style scoped>
:deep(.p-datatable) {
    border-radius: 0;
    overflow: hidden;
    border: none;
}

:deep(.p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color);
}

:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-multiselect) {
    border-radius: 1rem;
}

:deep(.p-datatable-tbody > tr.row-blurred > td) {
    opacity: 0.4;
    filter: blur(1.5px);
    transition: opacity 0.2s, filter 0.2s;
    pointer-events: none;
}

:deep(.nested-pm-table .p-datatable) {
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color);
}

:deep(.nested-pm-table .p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}
</style>
