<template>
    <AdminLayout>

        <Head title="Interviewed Applicants - Approval Management" />

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon name="message-square-more" class="text-blue-600 w-8 h-8 short:w-6 short:h-6" />
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Interviewed Applicants</h1>
                            <p class="text-sm text-gray-600">Review interview assessments and manage approvals</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-2 items-center">
                        <AppButton icon="printer" severity="info" text rounded size="large"
                            @click="showReportModal = true" v-tooltip.bottom="'Print Report'" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-4 short:mb-2 !rounded-4xl overflow-hidden">
                <div class="flex items-end gap-3 -mt-6 flex-wrap">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Recommendation</label>
                        <Select v-model="filters.recommendation" :options="recommendationOptions" optionLabel="label"
                            optionValue="value" placeholder="All Recommendations" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                        <ProgramSelect v-model="filters.program" size="small" class="w-full" />
                    </div>
                    <AppButton severity="secondary" text rounded size="small" icon="history"
                        @click="filters.recommendation = null; filters.name = ''; filters.program = ''"
                        v-tooltip.bottom="'Clear Filters'" />
                </div>
            </Panel>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 short:mb-2">
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-blue-600">{{ stats.total }}</div>
                    <div class="text-xs text-gray-500">Total Interviewed</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-green-600">{{ stats.recommended }}</div>
                    <div class="text-xs text-gray-500">Recommended</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-yellow-600">{{ stats.furtherEval }}</div>
                    <div class="text-xs text-gray-500">For Further Evaluation</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl short:text-xl font-bold text-red-600">{{ stats.notRecommended }}</div>
                    <div class="text-xs text-gray-500">Not Recommended</div>
                </div>
            </div>

            <!-- Interviewed Applicants Table -->
            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <!-- Info Bar -->
                <div
                    class="md:flex hidden items-center justify-between gap-4 mb-4 p-3 bg-gray-50 dark:bg-[#1e242b] rounded-4xl -mt-2">
                    <div class="flex-1 max-w-md">
                        <IconField iconPosition="left">
                            <InputIcon>
                                <AppIcon name="search" :size="16" class="text-gray-400" />
                            </InputIcon>
                            <InputText v-model="filters.name" placeholder="Search by name..." class="w-full"
                                size="small" />
                        </IconField>
                    </div>
                    <span class="text-sm text-gray-500">{{ filteredList.length }} result(s)</span>
                </div>

                <!-- Selection Bar -->
                <div v-if="selectedRows.length > 0"
                    class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <AppIcon name="check-circle" :size="18" class="text-yellow-600" />
                        <div>
                            <div class="font-semibold text-yellow-900 text-sm">{{ selectedRows.length }}
                                applicant(s) selected</div>
                            <div class="text-xs text-yellow-700">Export selected applicants as PDF or Excel</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <AppButton icon="file-type" label="Export PDF" severity="danger" outlined rounded size="small"
                            @click="exportSelected('pdf')" />
                        <AppButton icon="file-spreadsheet" label="Export Excel" severity="success" outlined rounded
                            size="small" @click="exportSelected('excel')" />
                    </div>
                </div>

                <div v-if="filteredList.length === 0" class="text-center py-8 text-gray-500">
                    No interviewed applicants found
                </div>
                <DataTable v-else v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredList"
                    responsiveLayout="scroll" class="text-sm" dataKey="id" v-model:selection="selectedRows"
                    v-model:expandedRows="expandedRows" showGridlines stripedRows scrollable
                    :rowClass="(row) => Object.keys(expandedRows).length > 0 && !expandedRows[row.id] ? 'row-blurred' : ''"
                    @rowContextmenu="(event) => openContextMenu(event.originalEvent, event.data)" contextMenu>
                    <Column selectionMode="multiple" :exportable="false" style="width: 3rem"></Column>
                    <Column expander :exportable="false" headerClass="w-12" bodyClass="w-12" />
                    <Column field="profile.last_name" header="Name" sortable>
                        <template #body="slotProps">
                            <div class="font-medium">
                                {{ slotProps.data.profile.last_name }}, {{ slotProps.data.profile.first_name }}
                            </div>
                            <div class="text-[11px] mono text-gray-500">{{ slotProps.data.profile.contact_no }}</div>
                        </template>
                    </Column>
                    <Column field="program.shortname" header="Program" sortable>
                        <template #body="slotProps">
                            <span class="text-xs"> {{ slotProps.data.program?.shortname || 'N/A' }}</span>
                        </template>
                    </Column>
                    <Column field="school.shortname" header="School" sortable>
                        <template #body="slotProps">
                            <span class="text-xs"> {{ slotProps.data.school?.shortname ||
                                slotProps.data.school?.name || 'N/A' }}</span>
                        </template>
                    </Column>
                    <Column field="course.shortname" header="Course" sortable>
                        <template #body="slotProps">
                            <span class="text-[10px] font-semibold"> {{ slotProps.data.course?.name || 'N/A' }}</span>
                        </template>
                    </Column>
                    <Column header="Year Level" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                        <template #body="slotProps">
                            <span class="text-xs"> {{ getSystemOptionLabel('year_level', slotProps.data.year_level,
                                'N/A') }}</span>
                        </template>
                    </Column>
                    <Column header="Term" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                        <template #body="slotProps">
                            <span class="text-xs"> {{ getSystemOptionLabel('term', slotProps.data.term, 'N/A') }}</span>
                        </template>
                    </Column>
                    <Column header="Academic Year" headerClass="min-w-[140px]" bodyClass="min-w-[140px]">
                        <template #body="slotProps">
                            <span class="text-xs"> {{ slotProps.data.academic_year || 'N/A' }}</span>
                        </template>
                    </Column>
                    <Column header="Grant Provision" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
                        <template #body="slotProps">
                            <div class="text-xs leading-snug">
                                {{ slotProps.data.grant_provision_label || getSystemOptionLabel('grant_provision',
                                    slotProps.data.grant_provision, 'N/A') }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Recommendation" sortable sortField="recommendation">
                        <template #body="slotProps">
                            <span
                                :class="['text-[11px] font-semibold', getRecommendationTextClass(slotProps.data.recommendation)]">
                                {{ formatRecommendation(slotProps.data.recommendation) }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Endorsed By" headerClass="min-w-[180px]" bodyClass="min-w-[180px]">
                        <template #body="slotProps">
                            <div class="text-sm leading-snug uppercase">
                                {{ slotProps.data.endorsed_by || '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Actions" :style="{ width: '80px' }">
                        <template #body="slotProps">
                            <AppButton icon="ellipsis-vertical" @click="openContextMenu($event, slotProps.data)" text
                                rounded size="small" v-tooltip.top="'Actions'" />
                        </template>
                    </Column>

                    <template #expansion="slotProps">
                        <div class="px-4 pb-4">
                            <div class="overflow-hidden rounded border border-slate-200 bg-slate-50">
                                <table class="w-full table-fixed border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-100">
                                            <th colspan="3"
                                                class="border-b border-slate-200 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                                Projected Detail
                                            </th>
                                            <th colspan="2"
                                                class="border-b border-l border-slate-200 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                                Interview Detail
                                            </th>
                                        </tr>
                                        <tr class="bg-white">
                                            <th
                                                class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Terms</th>
                                            <th
                                                class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Expense</th>
                                            <th
                                                class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Completion</th>
                                            <th
                                                class="border-b border-l border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Interview Date</th>
                                            <th
                                                class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                Interviewed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white align-top">
                                            <td class="px-3 py-3 text-sm font-semibold text-slate-700">
                                                <span v-if="slotProps.data.projected_term_count !== null">
                                                    {{ formatProjectedTerms(slotProps.data.projected_term_count) }}
                                                </span>
                                                <span v-else class="text-amber-700">Not configured</span>
                                            </td>
                                            <td class="px-3 py-3 text-sm font-semibold text-emerald-700">
                                                <span v-if="slotProps.data.projected_total_expense !== null">
                                                    {{ formatCurrency(slotProps.data.projected_total_expense) }}
                                                </span>
                                                <span v-else class="text-amber-700">Not configured</span>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-700">
                                                <div v-if="slotProps.data.projected_completion_year !== null"
                                                    class="font-semibold">
                                                    {{ slotProps.data.projected_completion_year }}
                                                </div>
                                                <div v-if="slotProps.data.projected_completion_academic_year"
                                                    class="text-xs text-gray-500">
                                                    AY {{ slotProps.data.projected_completion_academic_year }}
                                                </div>
                                                <div v-else-if="slotProps.data.projected_completion_year === null"
                                                    class="text-amber-700">
                                                    Not configured
                                                </div>
                                            </td>
                                            <td
                                                class="border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700">
                                                {{ formatDate(slotProps.data.interviewed_at) }}
                                            </td>
                                            <td class="px-3 py-3 text-sm font-semibold text-slate-700 uppercase">
                                                {{ slotProps.data.interviewer?.name || 'N/A' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </Panel>
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

        <!-- Generate Report Modal -->
        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event"
            :interviewed-applicants="filteredList" />

        <AssessmentViewModal v-model:show="showAssessmentDialog" :record="selectedRecord"
            :initial-mode="assessmentInitialMode" :can-manage="canManageActions" :can-revert="canManageActions"
            :approval-form="approvalForm" :deny-form="denyForm" :decline-reasons="declineReasons"
            :interviewers="interviewers" @updated="onAssessmentUpdated" @confirm-approve="confirmApprove"
            @confirm-deny="confirmDeny" @revert="confirmRevert" />
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import AppButton from '@/Components/ui/AppButton.vue';
import moment from 'moment';
import { toast } from '@/utils/toast';
import { usePermission } from '@/composable/permissions';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import ContextMenu from 'primevue/contextmenu';
import AssessmentViewModal from './Modal/AssessmentViewModal.vue';
import GenerateReportModal from './Modal/GenerateReportModalIOS.vue';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';

const { hasRole } = usePermission();

const props = defineProps({
    interviewed_applicants: Array,
    decline_reasons: Object,
    interviewers: {
        type: Array,
        default: () => [],
    },
});

// State
const filters = ref({
    recommendation: null,
    name: '',
    program: ''
});

const contextMenu = ref();
const showAssessmentDialog = ref(false);
const assessmentInitialMode = ref('view');
const showReportModal = ref(false);
const selectedRows = ref([]);
const expandedRows = ref({});

const approvalForm = useForm({
    date_approved: new Date(),
});

const denyForm = useForm({
    reason: '',
    details: ''
});

const selectedRecord = ref(null);
const canManageActions = computed(() => hasRole('administrator') || hasRole('program_manager') || hasRole('screening_officer'));

// Options
const recommendationOptions = [
    { label: 'All Recommendations', value: null },
    { label: 'Recommended for Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' }
];

const declineReasons = computed(() => {
    if (!props.decline_reasons) return [];
    return Object.entries(props.decline_reasons).map(([value, label]) => ({
        value,
        label
    }));
});

// Computed
const filteredList = computed(() => {
    let list = props.interviewed_applicants || [];

    if (filters.value.recommendation) {
        list = list.filter(r => r.recommendation === filters.value.recommendation);
    }

    if (filters.value.name) {
        const name = filters.value.name.toLowerCase();
        list = list.filter(r => {
            const fullName = `${r.profile.first_name} ${r.profile.last_name}`.toLowerCase();
            return fullName.includes(name);
        });
    }

    if (filters.value.program) {
        list = list.filter(r => r.program && r.program.id == filters.value.program);
    }

    return list;
});

const formatDateForPayload = (value) => {
    if (!value) {
        return null;
    }

    return moment(value).format('YYYY-MM-DD');
};

const approvalValidationFields = [
    {
        key: 'date_approved',
        label: 'Approval Date',
        hasValue: () => Boolean(approvalForm.date_approved),
    },
];

const validateApprovalForm = () => {
    const clientErrors = {};
    const missingFields = [];

    approvalForm.clearErrors(...approvalValidationFields.map(field => field.key));

    approvalValidationFields.forEach((field) => {
        if (!field.hasValue()) {
            clientErrors[field.key] = `${field.label} is required.`;
            missingFields.push(field.label);
        }
    });

    if (!missingFields.length) {
        return true;
    }

    approvalForm.errors = {
        ...approvalForm.errors,
        ...clientErrors,
    };

    toast.warn('Complete the highlighted approval fields before submitting.');

    return false;
};

const populateApprovalForm = (record) => {
    approvalForm.date_approved = record.date_approved ? new Date(record.date_approved) : new Date();
};

const resetApprovalForm = (record = null) => {
    approvalForm.reset();
    approvalForm.clearErrors();

    if (record) {
        populateApprovalForm(record);
    }
};

const resetDenyForm = () => {
    denyForm.reset();
    denyForm.clearErrors();
};

const openAssessmentDialog = (record, mode = 'view') => {
    selectedRecord.value = record;
    assessmentInitialMode.value = mode;

    if (mode === 'approve') {
        resetApprovalForm(record);
    }

    if (mode === 'deny') {
        resetDenyForm();
    }

    showAssessmentDialog.value = true;
};

const stats = computed(() => {
    const all = props.interviewed_applicants || [];
    return {
        total: all.length,
        recommended: all.filter(r => r.recommendation === 'recommended').length,
        furtherEval: all.filter(r => r.recommendation === 'further_evaluation').length,
        notRecommended: all.filter(r => r.recommendation === 'not_recommended').length,
    };
});

// Context Menu
const contextMenuItems = computed(() => {
    const items = [
        {
            label: 'View Assessment',
            icon: 'file',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'view');
                }
            }
        },
        {
            label: 'Edit Assessment',
            icon: 'pencil',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'edit');
                }
            }
        },
        {
            label: 'View Profile',
            icon: 'eye',
            command: () => {
                if (selectedRecord.value) {
                    viewProfile(selectedRecord.value);
                }
            }
        }
    ];

    if (canManageActions.value) {
        items.push({ separator: true });
        items.push({
            label: 'Approve',
            icon: 'check',
            class: 'p-menuitem-success',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'approve');
                }
            }
        });
        items.push({
            label: 'Deny',
            icon: 'x',
            class: 'p-menuitem-danger',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'deny');
                }
            }
        });

        items.push({ separator: true });
        items.push({
            label: 'Revert to Pending',
            icon: 'arrow-left',
            command: () => {
                if (selectedRecord.value) {
                    revertStatus(selectedRecord.value);
                }
            }
        });
    }

    return items;
});

const openContextMenu = (event, record) => {
    selectedRecord.value = record;
    contextMenu.value.show(event);
};

// Methods
const onAssessmentUpdated = (changes) => {
    if (selectedRecord.value) {
        selectedRecord.value = {
            ...selectedRecord.value,
            ...changes,
        };
    }

    router.reload({
        only: ['interviewed_applicants'],
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmApprove = () => {
    if (!selectedRecord.value) return;

    if (!validateApprovalForm()) {
        return;
    }

    approvalForm.transform((data) => ({
        date_approved: formatDateForPayload(data.date_approved),
    })).post(route('scholarship.record.approve', selectedRecord.value.id), {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Application approved successfully');
        },
        onError: (errors) => {
            const hasSummaryOnlyErrors = Object.keys(errors || {}).some((field) => field !== 'date_approved');
            toast.error(hasSummaryOnlyErrors
                ? 'Saved assessment details are incomplete. Use Edit Assessment, then try approving again.'
                : 'Review the approval date and try again.');
            console.error(errors);
        }
    });
};

const confirmDeny = () => {
    if (!selectedRecord.value || !denyForm.reason || !denyForm.details) {
        toast.error('Please fill in all required fields');
        return;
    }

    denyForm.post(route('scholarship.record.decline', selectedRecord.value.id), {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Application denied successfully');
        },
        onError: (errors) => {
            toast.error('Failed to deny application');
            console.error(errors);
        }
    });
};

const revertStatus = (record) => {
    router.patch(route('scholarship.record.update-status', record.id), {
        unified_status: 'pending'
    }, {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Status reverted to pending');
        },
        onError: () => {
            toast.error('Failed to revert status');
        }
    });
};

const confirmRevert = () => {
    if (!selectedRecord.value) return;

    revertStatus(selectedRecord.value);
};

const viewProfile = (record) => {
    router.visit(route('scholarship.profile.show', record.profile.profile_id));
};

const formatDate = (date) => {
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
};

const formatProjectedTerms = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    const terms = Number(value);
    if (!Number.isFinite(terms)) {
        return 'Not configured';
    }

    return `${terms} ${terms === 1 ? 'term' : 'terms'}`;
};

const formatRecommendation = (value) => {
    const labels = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };
    return labels[value] || 'N/A';
};

const getRecommendationTextClass = (value) => {
    const map = {
        recommended: 'text-green-600',
        further_evaluation: 'text-yellow-600',
        not_recommended: 'text-red-600',
    };
    return map[value] || 'text-slate-500';
};

const applyFilters = () => {
    // Filters are reactive
};

const exportSelected = (format) => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant');
        return;
    }
    const ids = selectedRows.value.map(r => r.id).join(',');
    const params = new URLSearchParams({ ids });
    window.open(`/api/interviewed-applicants/export/${format}?${params.toString()}`, '_blank');
    toast.success(`Exporting ${selectedRows.value.length} applicant(s) as ${format.toUpperCase()}...`);
};

const refreshPage = () => {
    router.reload({
        only: ['interviewed_applicants'],
        preserveState: true,
        preserveScroll: true
    });
};
</script>

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

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
}

:deep(.p-datatable-tbody > tr.row-blurred > td) {
    opacity: 0.4;
    filter: blur(1.5px);
    transition: opacity 0.2s, filter 0.2s;
    pointer-events: none;
}

/* Rounded DataTable */
:deep(.p-datatable) {
    border-radius: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--p-datatable-border-color, #e2e8f0);
}

:deep(.p-datatable .p-datatable-table-container) {
    border-radius: 0;
    overflow: hidden;
}

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

:deep(.p-paginator) {
    border: none;
    border-top: 1px solid var(--p-datatable-border-color, #e2e8f0);
}

:deep(.ios-datepicker) {
    width: 100%;
}
</style>
