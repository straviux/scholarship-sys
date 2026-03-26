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
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';

const props = defineProps({
    paymentData: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', transaction_status: '', academic_year: '', semester: '' }),
    },
});

// Reactive state
const searchInput = ref(props.filters.search);
const selectedStatus = ref(props.filters.transaction_status);
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
            transaction_status: selectedStatus.value,
            academic_year: selectedAcademicYear.value,
            semester: selectedSemester.value,
            program: typeof selectedProgram.value === 'object' ? selectedProgram.value?.name : selectedProgram.value,
            course: courseValues.length > 0 ? courseValues : [],
            school: typeof selectedSchool.value === 'object' ? selectedSchool.value?.name : selectedSchool.value,
        }, {
            preserveState: false,
            replace: true,
        });
    }, 300);
};

const clearFilters = () => {
    clearTimeout(filterTimeout);
    searchInput.value = '';
    selectedStatus.value = '';
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
        'IRREGULAR': props.paymentData.filter(item => item.transaction_status === 'IRREGULAR').length,
        'TRANSFERRED': props.paymentData.filter(item => item.transaction_status === 'TRANSFERRED').length,
        'CLAIMED': props.paymentData.filter(item => item.transaction_status === 'CLAIMED').length,
        'PAID': props.paymentData.filter(item => item.transaction_status === 'PAID').length,
        'ON PROCESS': props.paymentData.filter(item => item.transaction_status === 'ON PROCESS').length,
        'DENIED': props.paymentData.filter(item => item.transaction_status === 'DENIED').length,
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

    if (selectedStatus.value && selectedStatus.value !== '') {
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
        'IRREGULAR': 'warn',
        'TRANSFERRED': 'secondary',
        'CLAIMED': 'contrast',
        'PAID': 'success',
        'ON PROCESS': 'warn',
        'DENIED': 'danger',
    };
    return map[status] || 'secondary';
};

// Status badge styling (legacy — kept for reference)
const getStatusBadgeClass = (status) => {
    const baseClass = 'px-2 sm:px-3 py-1 rounded-full text-xs font-semibold';
    const statusMap = {
        'LOA': 'bg-blue-100 text-blue-800',
        'IRREGULAR': 'bg-yellow-100 text-yellow-800',
        'TRANSFERRED': 'bg-purple-100 text-purple-800',
        'CLAIMED': 'bg-indigo-100 text-indigo-800',
        'PAID': 'bg-green-100 text-green-800',
        'ON PROCESS': 'bg-orange-100 text-orange-800',
        'DENIED': 'bg-red-100 text-red-800',
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
</script>

<template>

    <Head title="Payment Monitoring" />

    <AdminLayout>

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-dollar text-blue-600" style="font-size:2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Payment Monitoring</h1>
                            <p class="text-sm text-gray-600">Track OBR status for active scholarship records</p>
                        </div>
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6 !rounded-4xl overflow-hidden">
                <div class="flex items-end gap-3 -mt-6 flex-wrap">
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
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                    <label v-for="opt in [
                        { label: 'All', value: '', count: statusCounts.all },
                        { label: 'No OBR', value: 'no-obr', count: statusCounts['no-obr'] },
                        { label: 'LOA', value: 'LOA', count: statusCounts['LOA'] },
                        { label: 'Irregular', value: 'IRREGULAR', count: statusCounts['IRREGULAR'] },
                        { label: 'Transferred', value: 'TRANSFERRED', count: statusCounts['TRANSFERRED'] },
                        { label: 'Claimed', value: 'CLAIMED', count: statusCounts['CLAIMED'] },
                        { label: 'Paid', value: 'PAID', count: statusCounts['PAID'] },
                        { label: 'On Process', value: 'ON PROCESS', count: statusCounts['ON PROCESS'] },
                        { label: 'Denied', value: 'DENIED', count: statusCounts['DENIED'] },
                    ]" :key="opt.value" class="flex items-center gap-1.5 cursor-pointer">
                        <RadioButton v-model="selectedStatus" name="pmStatus" :value="opt.value" />
                        <span class="text-sm text-gray-700 whitespace-nowrap">{{ opt.label }}</span>
                        <Badge :value="opt.count" severity="secondary" />
                    </label>
                </div>
            </Panel>

            <!-- Stats Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-blue-600">{{ statusCounts.all }}</div>
                    <div class="text-xs text-gray-500">Total Records</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-orange-500">{{ statusCounts['ON PROCESS'] }}</div>
                    <div class="text-xs text-gray-500">On Process</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{ statusCounts['PAID'] }}</div>
                    <div class="text-xs text-gray-500">Paid</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-gray-400">{{ statusCounts['no-obr'] }}</div>
                    <div class="text-xs text-gray-500">No OBR</div>
                </div>
            </div>

            <!-- Data Table -->
            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <div class="flex items-center justify-between mb-4 -mt-2">
                    <span class="text-sm text-gray-500">{{ filteredData.length }} record(s)</span>
                </div>

                <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredData"
                    :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" class="text-sm" showGridlines
                    stripedRows scrollable>

                    <Column field="scholar_name" header="Scholar Name" sortable style="min-width: 180px">
                        <template #body="{ data }">
                            <a :href="route('scholarship.profile.show', data.profile_id)" target="_blank"
                                class="text-blue-600 hover:text-blue-800 hover:underline font-medium flex items-center gap-1">
                                {{ data.scholar_name }}
                                <i class="pi pi-external-link" style="font-size: 9pt;"></i>
                            </a>
                        </template>
                    </Column>

                    <Column field="disbursement_type" header="Type" sortable style="min-width: 110px">
                        <template #body="{ data }">
                            <Tag v-if="data.disbursement_type"
                                :value="data.disbursement_type === 'disbursements' ? 'Disbursement' : data.disbursement_type === 'payroll' ? 'Payroll' : data.disbursement_type"
                                :severity="data.disbursement_type === 'disbursements' ? 'info' : 'success'" rounded />
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </template>
                    </Column>

                    <Column field="academic_year" header="Acad. Year" sortable style="min-width: 110px" />
                    <Column field="year_level" header="Year" sortable style="min-width: 70px" />
                    <Column field="term" header="Term" sortable style="min-width: 80px" />

                    <Column header="Status" style="min-width: 140px">
                        <template #body="{ data }">
                            <Tag v-if="data.transaction_status" :value="data.transaction_status" rounded
                                :severity="getStatusSeverity(data.transaction_status)" />
                            <span v-else class="text-gray-400 text-xs italic">No OBR</span>
                        </template>
                    </Column>

                    <Column header="Amount" sortable style="min-width: 110px">
                        <template #body="{ data }">
                            <span v-if="data.amount" class="font-semibold text-green-700">
                                ₱{{ parseFloat(data.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <Column header="OBR Date" style="min-width: 110px">
                        <template #body="{ data }">
                            <span v-if="data.date_obligated" class="text-gray-700">{{ formatDate(data.date_obligated)
                                }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <Column field="obr_no" header="OBR No." style="min-width: 130px">
                        <template #body="{ data }">
                            <span v-if="data.obr_no" class="font-mono text-blue-700">{{ data.obr_no }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <Column field="remarks" header="Remarks" style="min-width: 180px">
                        <template #body="{ data }">
                            <span v-if="data.remarks" class="text-gray-600" v-html="data.remarks"></span>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="py-12 text-center text-gray-500">
                            <i class="pi pi-inbox text-3xl mb-2 block"></i>
                            <p>No payment records found matching the current filters.</p>
                        </div>
                    </template>
                </DataTable>
            </Panel>
        </div>

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
</style>
