<template>
    <AdminLayout>

        <Head title="Disbursement Management" />

        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-4 short:py-2 px-3 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-6 short:mb-3">
                    <h1 class="text-2xl sm:text-3xl short:text-xl font-bold text-slate-900 mb-2">Disbursement Management
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600">Map disbursement records to fund transactions and
                        sync OBR status</p>
                </div>

                <!-- Filters Panel -->
                <div class="bg-white rounded-lg shadow-sm p-4 sm:p-4 short:p-3 mb-4 short:mb-2 border border-slate-200">
                    <form @submit.prevent="handleFilter" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                            <!-- Search OBR -->
                            <div>
                                <InputText v-model="filterForm.search" placeholder="Search OBR No..."
                                    class="w-full text-xs sm:text-sm" />
                            </div>

                            <!-- OBR Status Filter -->
                            <div>
                                <Dropdown v-model="filterForm.obr_status" :options="obrStatusOptions"
                                    option-label="label" option-value="value" placeholder="All OBR Status"
                                    class="w-full text-xs sm:text-sm" />
                            </div>

                            <!-- Academic Year Filter -->
                            <div>
                                <Dropdown v-model="filterForm.academic_year" :options="academicYearOptions"
                                    option-label="label" option-value="value" placeholder="All Years"
                                    class="w-full text-xs sm:text-sm" />
                            </div>

                            <!-- Semester Filter -->
                            <div>
                                <Dropdown v-model="filterForm.semester" :options="semesterOptions" option-label="label"
                                    option-value="value" placeholder="All Semesters"
                                    class="w-full text-xs sm:text-sm" />
                            </div>

                            <!-- Unmapped Only -->
                            <div class="flex items-center">
                                <Checkbox v-model="filterForm.unmapped_only" :binary="true" input-id="unmappedOnly"
                                    class="text-xs sm:text-sm" />
                                <label for="unmappedOnly" class="ml-2 text-xs sm:text-sm text-slate-700">Unmapped
                                    Only</label>
                            </div>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <Button type="button" label="Reset" severity="secondary" size="small" @click="handleReset"
                                class="text-xs sm:text-sm" />
                            <Button type="submit" label="Apply Filters" size="small" class="text-xs sm:text-sm" />
                        </div>
                    </form>
                </div>

                <!-- Success Message -->
                <div v-if="$page.props.flash?.success"
                    class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Disbursements Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-slate-200">
                    <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="disbursements"
                        :paginator="true" :rows="10" :row-hover="true" class="text-xs sm:text-sm"
                        responsive-layout="scroll">
                        <!-- OBR No -->
                        <Column field="obr_no" header="OBR No" style="min-width: 120px">
                            <template #body="{ data }">
                                <div class="font-semibold text-slate-900">{{ data.obr_no }}</div>
                            </template>
                        </Column>

                        <!-- Profile Count -->
                        <Column field="profile_count" header="Profiles" style="min-width: 80px">
                            <template #body="{ data }">
                                <Badge :value="`${data.profile_count} Profile${data.profile_count > 1 ? 's' : ''}`"
                                    severity="info" />
                            </template>
                        </Column>

                        <!-- Total Amount -->
                        <Column field="total_amount" header="Total Amount" style="min-width: 100px">
                            <template #body="{ data }">
                                <div class="font-medium text-slate-900">₱{{ Number(data.total_amount).toFixed(2) }}
                                </div>
                            </template>
                        </Column>

                        <!-- Date Obligated -->
                        <Column field="date_obligated" header="Date Obligated" style="min-width: 110px">
                            <template #body="{ data }">
                                <div class="text-slate-600">{{ formatDate(data.date_obligated) }}</div>
                            </template>
                        </Column>

                        <!-- OBR Status -->
                        <Column field="obr_status" header="OBR Status" style="min-width: 110px">
                            <template #body="{ data }">
                                <Badge :value="data.obr_status" :severity="getOBRStatusSeverity(data.obr_status)"
                                    class="text-xs sm:text-sm" />
                            </template>
                        </Column>

                        <!-- Academic Year & Semester -->
                        <Column field="academic_year" header="Year/Sem" style="min-width: 95px">
                            <template #body="{ data }">
                                <div class="text-slate-600">{{ data.academic_year }}/{{ data.semester?.substring(0, 1)
                                }}</div>
                            </template>
                        </Column>

                        <!-- Mapping Status -->
                        <Column field="is_mapped" header="Status" style="min-width: 90px">
                            <template #body="{ data }">
                                <Badge :value="data.is_mapped ? 'Mapped' : 'Unmapped'"
                                    :severity="data.is_mapped ? 'success' : 'warning'" class="text-xs sm:text-sm" />
                            </template>
                        </Column>

                        <!-- Actions -->
                        <Column header="Actions" style="min-width: 140px">
                            <template #body="{ data }">
                                <div class="flex gap-2 flex-wrap">
                                    <AppButton icon="plus" :label="data.is_mapped ? 'Edit' : 'Map'" :as="Link"
                                        :href="route('disbursement-management.show', data.obr_no)" severity="info"
                                        size="small" text class="text-xs" />
                                    <AppButton icon="list" label="View" @click="showDetailsModal(data)"
                                        severity="secondary" size="small" text class="text-xs" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <Dialog v-model:visible="showDetailsFlag" :header="`OBR: ${selectedDisbursement?.obr_no}`" :modal="true"
            :style="{ width: '100%', maxWidth: '600px' }" class="text-xs sm:text-sm">
            <div v-if="selectedDisbursement" class="space-y-4">
                <!-- Summary -->
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-200">
                    <div>
                        <div class="text-slate-500 text-xs">Total Amount</div>
                        <div class="font-semibold text-slate-900">₱{{
                            Number(selectedDisbursement.total_amount).toFixed(2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">Date Obligated</div>
                        <div class="font-semibold text-slate-900">{{ formatDate(selectedDisbursement.date_obligated) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">OBR Status</div>
                        <Badge :value="selectedDisbursement.obr_status"
                            :severity="getOBRStatusSeverity(selectedDisbursement.obr_status)" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">Disbursement Type</div>
                        <div class="font-semibold text-slate-900">{{ selectedDisbursement.disbursement_type }}</div>
                    </div>
                </div>

                <!-- Profiles List -->
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3">Profiles</h4>
                    <div class="space-y-2">
                        <div v-for="profile in selectedDisbursement.profiles" :key="profile.disbursement_id"
                            class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-900 truncate">{{ profile.scholar_name }}</div>
                                    <div class="text-slate-500 text-xs">Year {{ profile.year_level }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-slate-900">₱{{ Number(profile.amount).toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';

const page = usePage();
const props = defineProps({
    disbursements: Array,
    filters: Object,
    obrStatuses: Array,
    academicYears: Array,
    semesters: Array,
});

const showDetailsFlag = ref(false);
const selectedDisbursement = ref(null);

const filterForm = reactive({
    search: props.filters?.search || '',
    obr_status: props.filters?.obr_status || 'all',
    academic_year: props.filters?.academic_year || 'all',
    semester: props.filters?.semester || 'all',
    unmapped_only: props.filters?.unmapped_only ? true : false,
});

const obrStatusOptions = computed(() => [
    { label: 'All Status', value: 'all' },
    ...props.obrStatuses.map(s => ({ label: s, value: s }))
]);

const academicYearOptions = computed(() => [
    { label: 'All Years', value: 'all' },
    ...props.academicYears.map(y => ({ label: y, value: y }))
]);

const semesterOptions = computed(() => [
    { label: 'All Semesters', value: 'all' },
    ...props.semesters.map(s => ({ label: s, value: s }))
]);

function handleFilter() {
    const params = new URLSearchParams();
    if (filterForm.search) params.append('search', filterForm.search);
    if (filterForm.obr_status !== 'all') params.append('obr_status', filterForm.obr_status);
    if (filterForm.academic_year !== 'all') params.append('academic_year', filterForm.academic_year);
    if (filterForm.semester !== 'all') params.append('semester', filterForm.semester);
    if (filterForm.unmapped_only) params.append('unmapped_only', '1');

    window.location.href = `${route('disbursement-management.index')}?${params.toString()}`;
}

function handleReset() {
    filterForm.search = '';
    filterForm.obr_status = 'all';
    filterForm.academic_year = 'all';
    filterForm.semester = 'all';
    filterForm.unmapped_only = false;
    window.location.href = route('disbursement-management.index');
}

function showDetailsModal(data) {
    selectedDisbursement.value = data;
    showDetailsFlag.value = true;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getOBRStatusSeverity(status) {
    const severities = {
        'LOA': 'info',
        'IRREGULAR': 'warning',
        'TRANSFERRED': 'info',
        'CLAIMED': 'success',
        'PAID': 'success',
        'ON PROCESS': 'warning',
        'DENIED': 'danger',
    };
    return severities[status] || 'secondary';
}
</script>
