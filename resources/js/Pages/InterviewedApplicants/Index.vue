<template>
    <AdminLayout>

        <Head title="Interviewed Applicants - Approval Management" />

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-comments text-blue-600" style="font-size:2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Interviewed Applicants</h1>
                            <p class="text-sm text-gray-600">Review interview assessments and manage approvals</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-2 items-center">
                        <Button icon="pi pi-file-export" label="Generate Report" severity="info" outlined size="small"
                            @click="showReportModal = true" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6">
                <div class="space-y-3 -mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Recommendation</label>
                            <Select v-model="filters.recommendation" :options="recommendationOptions"
                                optionLabel="label" optionValue="value" placeholder="All Recommendations" size="small"
                                class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Name</label>
                            <InputText v-model="filters.name" placeholder="Search by name" size="small" class="w-full"
                                @keyup.enter="applyFilters" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                            <ProgramSelect v-model="filters.program" size="small" class="w-full" />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ stats.total }}</div>
                    <div class="text-xs text-gray-500">Total Interviewed</div>
                </div>
                <div class="bg-white border rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ stats.recommended }}</div>
                    <div class="text-xs text-gray-500">Recommended</div>
                </div>
                <div class="bg-white border rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ stats.furtherEval }}</div>
                    <div class="text-xs text-gray-500">For Further Evaluation</div>
                </div>
                <div class="bg-white border rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ stats.notRecommended }}</div>
                    <div class="text-xs text-gray-500">Not Recommended</div>
                </div>
            </div>

            <!-- Interviewed Applicants Table -->
            <Card>
                <template #header>
                    <div class="flex items-center gap-2 p-4">
                        <i class="pi pi-list text-blue-600 text-xl"></i>
                        <span class="font-semibold">Interviewed Applicants ({{ filteredList.length }})</span>
                    </div>
                </template>
                <template #content>
                    <!-- Selection Bar -->
                    <div v-if="selectedRows.length > 0"
                        class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-check-circle text-yellow-600 text-lg"></i>
                            <div>
                                <div class="font-semibold text-yellow-900 text-sm">{{ selectedRows.length }}
                                    applicant(s) selected</div>
                                <div class="text-xs text-yellow-700">Export selected applicants as PDF or Excel</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button icon="pi pi-file-pdf" label="Export PDF" severity="danger" outlined size="small"
                                @click="exportSelected('pdf')" />
                            <Button icon="pi pi-file-excel" label="Export Excel" severity="success" outlined
                                size="small" @click="exportSelected('excel')" />
                        </div>
                    </div>

                    <div v-if="filteredList.length === 0" class="text-center py-8 text-gray-500">
                        No interviewed applicants found
                    </div>
                    <DataTable v-else v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredList"
                        responsiveLayout="scroll" class="text-sm" dataKey="id" v-model:selection="selectedRows"
                        @rowContextmenu="(event) => openContextMenu(event.originalEvent, event.data)" contextMenu>
                        <Column selectionMode="multiple" :exportable="false" style="width: 3rem"></Column>
                        <Column field="profile.last_name" header="Name" sortable>
                            <template #body="slotProps">
                                <div class="font-medium">
                                    {{ slotProps.data.profile.last_name }}, {{ slotProps.data.profile.first_name }}
                                </div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.profile.contact_no }}</div>
                            </template>
                        </Column>
                        <Column field="program.shortname" header="Program" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.program?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="course.shortname" header="Course" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.course?.shortname || 'N/A' }}
                            </template>
                        </Column>
                        <Column header="Recommendation" sortable sortField="recommendation">
                            <template #body="slotProps">
                                <Tag :value="formatRecommendation(slotProps.data.recommendation)"
                                    :severity="getRecommendationSeverity(slotProps.data.recommendation)" />
                            </template>
                        </Column>
                        <Column header="Interviewed" sortable sortField="interviewed_at">
                            <template #body="slotProps">
                                <div class="text-sm">{{ formatDate(slotProps.data.interviewed_at) }}</div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.interviewer?.name || 'N/A' }}</div>
                            </template>
                        </Column>
                        <Column header="Actions" :style="{ width: '80px' }">
                            <template #body="slotProps">
                                <Button icon="pi pi-ellipsis-v" @click="openContextMenu($event, slotProps.data)" text
                                    rounded size="small" v-tooltip.top="'Actions'" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body" />

        <!-- Generate Report Modal -->
        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event"
            :interviewed-applicants="filteredList" />

        <!-- Interview Assessment Modal -->
        <Dialog v-model:visible="showAssessmentDialog" modal header="Interview Assessment" :style="{ width: '600px' }">
            <div v-if="selectedRecord" class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded">
                    <div class="font-semibold text-blue-900">
                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ selectedRecord.program?.shortname || 'N/A' }} — {{ selectedRecord.course?.shortname || 'N/A'
                        }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Interviewed: {{ formatDate(selectedRecord.interviewed_at) }} by {{
                            selectedRecord.interviewer?.name ||
                            'N/A' }}
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-800 border-b pb-1 mb-3">INTERVIEWER'S ASSESSMENT</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500">Academic Potential</label>
                            <div>
                                <Tag :value="capitalize(selectedRecord.academic_potential)"
                                    :severity="getRatingSeverity(selectedRecord.academic_potential)" class="text-xs" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Financial Need</label>
                            <div>
                                <Tag :value="capitalize(selectedRecord.financial_need_level)"
                                    :severity="getNeedSeverity(selectedRecord.financial_need_level)" class="text-xs" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Communication</label>
                            <div>
                                <Tag :value="capitalize(selectedRecord.communication_skills)"
                                    :severity="getRatingSeverity(selectedRecord.communication_skills)"
                                    class="text-xs" />
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-800 border-b pb-1 mb-3">RECOMMENDATION</h4>
                    <div>
                        <Tag :value="formatRecommendation(selectedRecord.recommendation)"
                            :severity="getRecommendationSeverity(selectedRecord.recommendation)" />
                    </div>
                    <div v-if="selectedRecord.interview_remarks" class="mt-2">
                        <label class="text-xs font-medium text-gray-500">Remarks</label>
                        <p class="text-sm text-gray-800">{{ selectedRecord.interview_remarks }}</p>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" @click="showAssessmentDialog = false" />
            </template>
        </Dialog>

        <!-- Approval Dialog -->
        <Dialog v-model:visible="showApprovalDialog" modal header="Approve Application" :style="{ width: '600px' }">
            <div v-if="selectedRecord" class="space-y-4">
                <!-- Profile Information -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded">
                    <div class="font-semibold text-blue-900 mb-2">
                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">
                        Contact: {{ selectedRecord.profile.contact_no }}
                    </div>
                </div>

                <!-- Academic Details -->
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 border border-gray-200 rounded">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Program</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.program?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Course</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.course?.shortname || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Year Level</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ selectedRecord.year_level || 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date Filed</label>
                        <div class="text-sm font-medium text-gray-900">
                            {{ formatDate(selectedRecord.date_filed) }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Approval Date</label>
                    <Calendar v-model="approvalForm.date_approved" showIcon class="w-full" :maxDate="new Date()" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks (Optional)</label>
                    <Editor v-model="approvalForm.remarks" editorStyle="height: 120px">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showApprovalDialog = false" />
                <Button label="Approve" severity="success" @click="confirmApprove" :loading="approvalForm.processing" />
            </template>
        </Dialog>

        <!-- Deny Dialog -->
        <Dialog v-model:visible="showDenyDialog" modal header="Confirm Deny Application" :style="{ width: '500px' }">
            <div v-if="selectedRecord" class="space-y-4">
                <div class="p-4 bg-red-50 border border-red-200 rounded">
                    <div class="font-semibold text-red-900">
                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ selectedRecord.program?.shortname }} - {{ selectedRecord.course?.shortname }}
                    </div>
                </div>

                <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    This action will permanently deny the application.
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Denial Reason</label>
                    <Select v-model="denyForm.reason" :options="declineReasons" optionLabel="label" optionValue="value"
                        placeholder="Select a reason" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Details</label>
                    <Textarea v-model="denyForm.details" rows="3" placeholder="Provide specific details..." />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDenyDialog = false" />
                <Button label="Confirm Deny" severity="danger" @click="confirmDeny" :loading="denyForm.processing" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import ContextMenu from 'primevue/contextmenu';
import GenerateReportModal from './Modal/GenerateReportModalIOS.vue';

const { hasRole } = usePermission();

const props = defineProps({
    interviewed_applicants: Array,
    decline_reasons: Object,
});

// State
const filters = ref({
    recommendation: null,
    name: '',
    program: ''
});

const contextMenu = ref();
const showAssessmentDialog = ref(false);
const showReportModal = ref(false);
const selectedRows = ref([]);

const approvalForm = useForm({
    date_approved: new Date(),
    remarks: ''
});

const denyForm = useForm({
    reason: '',
    details: ''
});

const selectedRecord = ref(null);
const showApprovalDialog = ref(false);
const showDenyDialog = ref(false);

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
            icon: 'pi pi-file',
            command: () => {
                if (selectedRecord.value) {
                    showAssessmentDialog.value = true;
                }
            }
        },
        {
            label: 'View Profile',
            icon: 'pi pi-eye',
            command: () => {
                if (selectedRecord.value) {
                    viewProfile(selectedRecord.value);
                }
            }
        }
    ];

    if (hasRole('administrator') || hasRole('program_manager')) {
        items.push({ separator: true });
        items.push({
            label: 'Approve',
            icon: 'pi pi-check',
            class: 'p-menuitem-success',
            command: () => {
                if (selectedRecord.value) {
                    approveApplication(selectedRecord.value);
                }
            }
        });
        items.push({
            label: 'Deny',
            icon: 'pi pi-times',
            class: 'p-menuitem-danger',
            command: () => {
                if (selectedRecord.value) {
                    denyApplication(selectedRecord.value);
                }
            }
        });
    }

    items.push({ separator: true });
    items.push({
        label: 'Revert to Pending',
        icon: 'pi pi-arrow-left',
        command: () => {
            if (selectedRecord.value) {
                revertStatus(selectedRecord.value);
            }
        }
    });

    return items;
});

const openContextMenu = (event, record) => {
    selectedRecord.value = record;
    contextMenu.value.show(event);
};

// Methods
const approveApplication = (record) => {
    selectedRecord.value = record;
    approvalForm.reset();
    showApprovalDialog.value = true;
};

const denyApplication = (record) => {
    selectedRecord.value = record;
    denyForm.reset();
    showDenyDialog.value = true;
};

const confirmApprove = () => {
    if (!selectedRecord.value) return;

    approvalForm.post(route('scholarship.record.approve', selectedRecord.value.id), {
        onSuccess: () => {
            showApprovalDialog.value = false;
            toast.success('Application approved successfully');
            refreshPage();
        },
        onError: (errors) => {
            toast.error('Failed to approve application');
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
            showDenyDialog.value = false;
            toast.success('Application denied successfully');
            refreshPage();
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
            toast.success('Status reverted to pending');
            refreshPage();
        },
        onError: () => {
            toast.error('Failed to revert status');
        }
    });
};

const viewProfile = (record) => {
    router.visit(route('scholarship.profile.show', record.profile.profile_id));
};

const formatDate = (date) => {
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
};

const capitalize = (str) => {
    if (!str) return 'N/A';
    return str.charAt(0).toUpperCase() + str.slice(1);
};

const formatRecommendation = (value) => {
    const labels = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };
    return labels[value] || 'N/A';
};

const getRecommendationSeverity = (value) => {
    const map = {
        recommended: 'success',
        further_evaluation: 'warn',
        not_recommended: 'danger',
    };
    return map[value] || 'secondary';
};

const getRatingSeverity = (value) => {
    const map = { excellent: 'success', good: 'info', fair: 'warn' };
    return map[value] || 'secondary';
};

const getNeedSeverity = (value) => {
    const map = { high: 'danger', moderate: 'warn', low: 'info' };
    return map[value] || 'secondary';
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
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
}
</style>
