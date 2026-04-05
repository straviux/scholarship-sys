<template>
    <AdminLayout>

        <Head title="Interviewed Applicants - Approval Management" />

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-comments text-blue-600 text-[2rem] short:text-[1.5rem]"></i>
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Interviewed Applicants</h1>
                            <p class="text-sm text-gray-600">Review interview assessments and manage approvals</p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-2 items-center">
                        <Button icon="pi pi-print" severity="secondary" outlined rounded size="large"
                            @click="showReportModal = true" />
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
                    <Button severity="secondary" outlined rounded size="small" icon="pi pi-history"
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
                            <InputIcon class="pi pi-search text-gray-400" />
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
                        <i class="pi pi-check-circle text-yellow-600 text-lg"></i>
                        <div>
                            <div class="font-semibold text-yellow-900 text-sm">{{ selectedRows.length }}
                                applicant(s) selected</div>
                            <div class="text-xs text-yellow-700">Export selected applicants as PDF or Excel</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-file-pdf" label="Export PDF" severity="danger" outlined rounded size="small"
                            @click="exportSelected('pdf')" />
                        <Button icon="pi pi-file-excel" label="Export Excel" severity="success" outlined rounded
                            size="small" @click="exportSelected('excel')" />
                    </div>
                </div>

                <div v-if="filteredList.length === 0" class="text-center py-8 text-gray-500">
                    No interviewed applicants found
                </div>
                <DataTable v-else v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredList"
                    responsiveLayout="scroll" class="text-sm" dataKey="id" v-model:selection="selectedRows"
                    showGridlines stripedRows scrollable
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
                    <Column header="Interview Date" sortable sortField="interviewed_at">
                        <template #body="slotProps">
                            <div class="text-sm">{{ formatDate(slotProps.data.interviewed_at) }}</div>
                        </template>
                    </Column>
                    <Column header="Interviewed By">
                        <template #body="slotProps">
                            <div class="text-sm">{{ slotProps.data.interviewer?.name || 'N/A' }}</div>
                        </template>
                    </Column>
                    <Column header="Actions" :style="{ width: '80px' }">
                        <template #body="slotProps">
                            <Button icon="pi pi-ellipsis-v" @click="openContextMenu($event, slotProps.data)" text
                                rounded size="small" v-tooltip.top="'Actions'" />
                        </template>
                    </Column>
                </DataTable>
            </Panel>
        </div>

        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body" />

        <!-- Generate Report Modal -->
        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event"
            :interviewed-applicants="filteredList" />

        <!-- ══════════════════════════════════════════
            INTERVIEW ASSESSMENT MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showAssessmentDialog" @update:visible="v => { if (!v) showAssessmentDialog = false; }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal assessment-modal"
                    :style="[assessmentDrag.dragStyle.value, { width: 'min(480px, 96vw)' }]">
                    <div class="ios-nav-bar" @pointerdown="assessmentDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showAssessmentDialog = false"
                            v-tooltip.bottom="`Close`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Interview Assessment</span>
                        <div style="width: 8px;"></div>
                    </div>
                    <div class="ios-body" v-if="selectedRecord">
                        <!-- Two-column layout -->
                        <div class="assessment-grid">
                            <!-- LEFT: Applicant Info -->
                            <div>
                                <div class="ios-section">
                                    <div class="ios-section-label">Applicant</div>
                                    <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                                        <div class="ios-row">
                                            <div class="ios-row-label"
                                                style="font-size: 15px; font-weight: 600; color: #1D4ED8;">
                                                {{ selectedRecord.profile.last_name }}, {{
                                                    selectedRecord.profile.first_name }}
                                            </div>
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-book" style="color: #007AFF; font-size: 13px;"></i>
                                                Program
                                            </div>
                                            <span class="text-sm text-gray-700">{{ selectedRecord.program?.shortname ||
                                                'N/A' }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-graduation-cap"
                                                    style="color: #34C759; font-size: 13px;"></i>
                                                Course
                                            </div>
                                            <span class="text-sm text-gray-700">{{ selectedRecord.course?.shortname ||
                                                'N/A' }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-calendar" style="color: #8E8E93; font-size: 13px;"></i>
                                                Interview Date
                                            </div>
                                            <span class="text-sm text-gray-600">{{
                                                formatDate(selectedRecord.interviewed_at) }}</span>
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-user" style="color: #8E8E93; font-size: 13px;"></i>
                                                Interviewed By
                                            </div>
                                            <span class="text-sm text-gray-600">{{ selectedRecord.interviewer?.name ||
                                                'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recommendation -->
                                <div class="ios-section">
                                    <div class="ios-section-label">Recommendation</div>
                                    <div class="ios-card">
                                        <div class="ios-row" style="min-height: 52px;">
                                            <div class="ios-row-label">
                                                <i class="pi pi-check-circle"
                                                    style="color: #34C759; font-size: 13px;"></i>
                                                Decision
                                            </div>
                                            <Tag :value="formatRecommendation(selectedRecord.recommendation)"
                                                :severity="getRecommendationSeverity(selectedRecord.recommendation)" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT: Assessment Ratings + Remarks -->
                            <div>
                                <div class="ios-section">
                                    <div class="ios-section-label">Interviewer's Assessment</div>
                                    <div class="ios-card">
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-star" style="color: #FF9500; font-size: 13px;"></i>
                                                Academic Potential
                                            </div>
                                            <Tag :value="capitalize(selectedRecord.academic_potential)"
                                                :severity="getRatingSeverity(selectedRecord.academic_potential)" />
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-wallet" style="color: #FF3B30; font-size: 13px;"></i>
                                                Financial Need
                                            </div>
                                            <Tag :value="capitalize(selectedRecord.financial_need_level)"
                                                :severity="getNeedSeverity(selectedRecord.financial_need_level)" />
                                        </div>
                                        <div class="ios-row">
                                            <div class="ios-row-label">
                                                <i class="pi pi-comments" style="color: #5856D6; font-size: 13px;"></i>
                                                Communication
                                            </div>
                                            <Tag :value="capitalize(selectedRecord.communication_skills)"
                                                :severity="getRatingSeverity(selectedRecord.communication_skills)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="ios-section">
                                    <div class="ios-section-label">Remarks</div>
                                    <div class="ios-card" style="padding: 10px 16px; min-height: 64px;">
                                        <p v-if="selectedRecord.interview_remarks" class="text-sm text-gray-700">{{
                                            selectedRecord.interview_remarks }}</p>
                                        <p v-else class="text-sm text-gray-400 italic">No remarks provided.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Edit Assessment Modal (reuses InterviewAssessmentModal) -->
        <InterviewAssessmentModal v-model="showEditDialog" :applicant="editApplicant" :recordId="selectedRecord?.id"
            :isEdit="true" successMessage="Assessment updated successfully." :initialValues="selectedRecord ? {
                academic_potential: selectedRecord.academic_potential,
                financial_need_level: selectedRecord.financial_need_level,
                communication_skills: selectedRecord.communication_skills,
                recommendation: selectedRecord.recommendation,
                interview_remarks: selectedRecord.interview_remarks,
            } : null" @submitted="onEditSubmitted" />

        <!-- ══════════════════════════════════════════
            APPROVE APPLICATION MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showApprovalDialog" @update:visible="v => { if (!v) showApprovalDialog = false; }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="approvalDrag.dragStyle.value">
                    <div class="ios-nav-bar" @pointerdown="approvalDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showApprovalDialog = false"
                            v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Approve Application</span>
                        <button class="ios-nav-btn ios-nav-action" @click="confirmApprove"
                            :disabled="approvalForm.processing" v-tooltip.bottom="`Approve`">
                            <i class="pi pi-check"></i>
                        </button>
                    </div>
                    <div class="ios-body" v-if="selectedRecord">
                        <!-- Applicant Info -->
                        <div class="ios-section">
                            <div class="ios-section-label">Applicant</div>
                            <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                                <div class="ios-row">
                                    <div class="ios-row-label"
                                        style="font-size: 15px; font-weight: 600; color: #1D4ED8;">
                                        {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name }}
                                    </div>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-phone" style="color: #34C759; font-size: 13px;"></i>
                                        Contact
                                    </div>
                                    <span class="text-sm text-gray-700">{{ selectedRecord.profile.contact_no }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">Academic Details</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-book" style="color: #007AFF; font-size: 13px;"></i>
                                        Program
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ selectedRecord.program?.shortname
                                        || 'N/A' }}</span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-graduation-cap" style="color: #34C759; font-size: 13px;"></i>
                                        Course
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ selectedRecord.course?.shortname
                                        || 'N/A' }}</span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-list-check" style="color: #5856D6; font-size: 13px;"></i>
                                        Year Level
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ selectedRecord.year_level ||
                                        'N/A' }}</span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #8E8E93; font-size: 13px;"></i>
                                        Date Filed
                                    </div>
                                    <span class="text-sm text-gray-700">{{ formatDate(selectedRecord.date_filed)
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">Approval Details</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar-plus" style="color: #34C759; font-size: 13px;"></i>
                                        Approval Date <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                    </div>
                                    <div class="ios-row-control">
                                        <Calendar v-model="approvalForm.date_approved" showIcon :maxDate="new Date()"
                                            class="ios-datepicker" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="ios-section">
                            <div class="ios-section-label">Remarks (Optional)</div>
                            <div class="ios-card" style="overflow: visible;">
                                <div class="ios-row ios-row-stacked" style="gap: 0; padding: 0;">
                                    <Editor v-model="approvalForm.remarks" editorStyle="height: 120px"
                                        class="ios-full-input">
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
                        </div>

                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            DENY APPLICATION MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showDenyDialog" @update:visible="v => { if (!v) showDenyDialog = false; }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="denyDrag.dragStyle.value">
                    <div class="ios-nav-bar" @pointerdown="denyDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showDenyDialog = false"
                            v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Deny Application</span>
                        <div style="width: 48px;"></div>
                    </div>
                    <div class="ios-body" v-if="selectedRecord">
                        <!-- Warning Card -->
                        <div class="ios-section">
                            <div class="ios-card" style="background: #FFF5F5; border-color: #FECACA;">
                                <div class="ios-row"
                                    style="gap: 12px; padding: 14px 16px; min-height: 60px; align-items: flex-start;">
                                    <div
                                        class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                        <i class="pi pi-exclamation-triangle text-red-500 text-sm"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ selectedRecord.profile.last_name }}, {{ selectedRecord.profile.first_name
                                            }}
                                        </p>
                                        <p class="text-xs text-gray-600 mt-0.5">
                                            {{ selectedRecord.program?.shortname }} — {{
                                                selectedRecord.course?.shortname }}
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">
                                            This action will permanently deny the application.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Denial Reason -->
                        <div class="ios-section">
                            <div class="ios-section-label">Denial Reason <span
                                    style="color: #FF3B30; margin-left: 2px;">*</span></div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-stacked" style="padding: 10px 16px;">
                                    <Select v-model="denyForm.reason" :options="declineReasons" optionLabel="label"
                                        optionValue="value" placeholder="Select a reason" class="ios-full-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">Details <span
                                    style="color: #FF3B30; margin-left: 2px;">*</span></div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-stacked" style="padding: 10px 16px;">
                                    <Textarea v-model="denyForm.details" rows="3"
                                        placeholder="Provide specific details..." class="ios-full-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Destructive Action -->
                        <div class="ios-section">
                            <button class="ios-destructive-btn" @click="confirmDeny" :disabled="denyForm.processing">
                                Confirm Deny Application
                            </button>
                        </div>

                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';
import { useDraggableModal } from '@/composables/useDraggableModal';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import ContextMenu from 'primevue/contextmenu';
import GenerateReportModal from './Modal/GenerateReportModalIOS.vue';
import InterviewAssessmentModal from '@/Pages/Applicants/Modal/InterviewAssessmentModal.vue';

const { hasRole } = usePermission();

const assessmentDrag = useDraggableModal();
const approvalDrag = useDraggableModal();
const denyDrag = useDraggableModal();

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
const showEditDialog = ref(false);

// Options
const recommendationOptions = [
    { label: 'All Recommendations', value: null },
    { label: 'Recommended for Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' }
];

// Adapt selectedRecord shape to what InterviewAssessmentModal expects
const editApplicant = computed(() => {
    if (!selectedRecord.value) return null;
    const r = selectedRecord.value;
    return {
        last_name: r.profile.last_name,
        first_name: r.profile.first_name,
        scholarship_grant: [{ program: r.program, course: r.course }],
    };
});

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
            label: 'Edit Assessment',
            icon: 'pi pi-pencil',
            command: () => {
                if (selectedRecord.value) {
                    editAssessment(selectedRecord.value);
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
const editAssessment = (record) => {
    selectedRecord.value = record;
    showEditDialog.value = true;
};

const onEditSubmitted = () => {
    router.reload({
        only: ['interviewed_applicants'],
        preserveState: true,
        preserveScroll: true,
    });
};

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

:deep(.assessment-modal .assessment-grid) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 8px;
    padding: 0 4px;
}

:deep(.assessment-modal .assessment-grid .ios-section) {
    padding-left: 0;
    padding-right: 0;
}
</style>
