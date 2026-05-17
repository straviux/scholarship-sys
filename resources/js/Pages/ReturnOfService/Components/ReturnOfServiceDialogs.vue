<template>
    <IosModal :visible="showBatchDialog" :title="batchMode === 'add' ? 'New Batch' : 'Edit Batch'" width="520px"
        close-icon="x" :icon-size="16" :show-action="true" :loading="batchForm.processing"
        body-style="padding: 0 16px 24px;" @action="emit('submit-batch')"
        @update:visible="(visible) => { if (!visible) closeBatchDialog(); }">
                    <div class="ios-section">
                        <div class="ios-section-label">Basic Info</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-stacked">
                                <div class="ios-row-label">
                                    <AppIcon name="tag" :size="13" style="color: #007AFF;" />
                                    Batch Name <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                </div>
                                <InputText v-model="batchForm.batch_name" placeholder="e.g., Batch 2025-A"
                                    class="ios-full-input" :class="{ 'p-invalid': batchForm.errors.batch_name }" />
                            </div>
                            <div class="ios-row ios-row-stacked">
                                <div class="ios-row-label">
                                    <AppIcon name="align-left" :size="13" style="color: #8E8E93;" />
                                    Description
                                </div>
                                <Textarea v-model="batchForm.description" rows="2" placeholder="Notes about this batch"
                                    class="ios-full-input" />
                            </div>
                        </div>
                        <div v-if="batchForm.errors.batch_name" class="ios-section-footer ios-error">
                            {{ batchForm.errors.batch_name }}
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Exam Period</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    Date From
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="batchForm.exam_date_from" placeholder="Any" showIcon
                                        iconDisplay="input" class="ios-datepicker" />
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    Date To
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="batchForm.exam_date_to" placeholder="Any" showIcon
                                        iconDisplay="input" class="ios-datepicker" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Details</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar-x" :size="13" style="color: #AF52DE;" />
                                    Result Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="batchForm.result_date" placeholder="Any" showIcon
                                        iconDisplay="input" class="ios-datepicker" />
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="graduation-cap" :size="13" style="color: #34C759;" />
                                    Course <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                </div>
                                <div class="ios-row-control">
                                    <CourseSelect v-model="batchForm.course_id" customPlaceholder="Select"
                                        class="ios-select" :load-all-when-no-program="true" />
                                </div>
                            </div>
                        </div>
                        <div v-if="batchForm.errors.course_id" class="ios-section-footer ios-error">
                            {{ batchForm.errors.course_id }}
                        </div>
                    </div>

                    <div style="height: 24px;"></div>
    </IosModal>

    <IosModal :visible="showScholarDialog" :title="scholarMode === 'add' ? 'Add Scholar' : 'Edit Scholar'"
        width="600px" close-icon="x" :icon-size="16" :show-action="true"
        :action-disabled="scholarForm.processing || isEndDateInvalid" :loading="scholarForm.processing"
        body-style="padding: 0 16px 24px;" @action="emit('submit-scholar')"
        @update:visible="(visible) => { if (!visible) closeScholarDialog(); }">
                    <div class="ios-section">
                        <div class="ios-section-label">Scholar</div>
                        <div class="ios-card">
                            <div v-if="scholarMode === 'edit'" class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="user" :size="13" style="color: #007AFF;" />
                                    Name
                                </div>
                                <span class="text-sm text-gray-700 font-medium scholar-name-text">
                                    {{ scholarForm.record_label }}
                                </span>
                            </div>
                            <div v-else class="ios-row ios-row-stacked">
                                <div class="ios-row-label">
                                    <AppIcon name="users" :size="13" style="color: #007AFF;" />
                                    Select Scholars <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                </div>
                                <MultiSelect v-model="scholarForm.selectedProfile" :options="scholarshipRecords"
                                    optionLabel="label" placeholder="Search completed scholars..." :filter="true"
                                    class="ios-full-input" @filter="(event) => emit('profile-filter', event)" />
                                <small class="text-gray-500 text-xs">Only completed records from Medicine and Medical
                                    Allied Courses program</small>
                            </div>
                        </div>
                        <div v-if="scholarForm.errors.profile_id" class="ios-section-footer ios-error">
                            {{ scholarForm.errors.profile_id }}
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Return of Service</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #34C759;" />
                                    Start Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="scholarForm.service_start_date" placeholder="Select" showIcon
                                        iconDisplay="input" class="ios-datepicker" />
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    End Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="scholarForm.service_end_date" placeholder="Select" showIcon
                                        iconDisplay="input" class="ios-datepicker"
                                        :class="{ 'p-invalid': isEndDateInvalid }" />
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="clock" :size="13" style="color: #5856D6;" />
                                    Years of Service
                                </div>
                                <div class="ios-row-control ios-years-row-control">
                                    <div class="ios-years-control">
                                        <button type="button" class="ios-inline-action-btn"
                                            @click="emit('auto-calculate-years-of-service')">
                                            Auto Calculate
                                        </button>
                                        <InputNumber v-model="scholarForm.years_of_service" :min="0"
                                            :minFractionDigits="0" :maxFractionDigits="2" :step="0.01"
                                            :useGrouping="false" mode="decimal" placeholder="Manual"
                                            class="ios-full-input ios-years-input" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="isEndDateInvalid" class="ios-section-footer ios-error">
                            End date cannot be earlier than start date
                        </div>
                        <div v-else class="ios-section-footer">
                            Manual by default. Use Auto Calculate to apply completed scholarship terms or service dates.
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">
                            Completion Status <span style="color: #FF3B30; margin-left: 2px;">*</span>
                        </div>
                        <div class="ios-card">
                            <div v-for="option in completionOptions" :key="option.value" class="ios-row">
                                <div class="ios-row-label">{{ option.label }}</div>
                                <RadioButton v-model="scholarForm.completion_status" :value="option.value"
                                    :inputId="`status_${option.value}`" />
                            </div>
                        </div>
                        <div v-if="scholarForm.errors.completion_status" class="ios-section-footer ios-error">
                            {{ scholarForm.errors.completion_status }}
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card" style="overflow: visible;">
                            <div class="ios-row ios-row-stacked" style="gap: 0; padding: 0;">
                                <Editor v-model="scholarForm.remarks" editorStyle="height: 120px"
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
    </IosModal>

    <IosModal :visible="showViewScholarDialog" title="Scholar Details" width="480px" close-icon="x"
        :icon-size="16" body-style="padding: 0 16px 24px;"
        @update:visible="(visible) => { if (!visible) emit('close-view-scholar'); }">
        <template #header-right>
            <div style="width: 48px;"></div>
        </template>
        <div v-if="viewingScholar">
                    <div class="ios-section">
                        <div class="ios-card scholar-banner-card">
                            <div class="ios-row scholar-banner-row">
                                <div class="ios-row-label scholar-banner-label">
                                    {{ viewingScholar.scholar_name }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Return of Service</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="clock" :size="13" style="color: #5856D6;" />
                                    Years of Service
                                </div>
                                <span class="font-bold text-blue-600 scholar-stat-value">
                                    {{ viewingScholar.years_of_service || 0 }}
                                </span>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="check-circle" :size="13" style="color: #34C759;" />
                                    Status
                                </div>
                                <Tag :value="viewingScholar.completion_status"
                                    :severity="getCompletionSeverity(viewingScholar.completion_status)" />
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #34C759;" />
                                    Service Start
                                </div>
                                <span class="text-sm text-gray-700">
                                    {{ formatDateLong(viewingScholar.service_start_date) }}
                                </span>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    Service End
                                </div>
                                <span class="text-sm text-gray-700">
                                    {{ formatDateLong(viewingScholar.service_end_date) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="viewingScholar.remarks" class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card remarks-card">
                            <p class="text-sm text-gray-700" v-safe-html="viewingScholar.remarks"></p>
                        </div>
                    </div>

                    <div style="height: 24px;"></div>
        </div>
    </IosModal>

    <IosModal :visible="showViewBatchDialog" width="90vw" close-icon="x" :icon-size="16"
        :modal-content-style="{ height: '85vh' }" body-style="padding: 0 16px 24px;"
        @update:visible="(visible) => { if (!visible) emit('close-view-batch'); }">
        <template #title>
            <span v-if="viewingBatch" class="ios-nav-title ios-nav-title--truncate"
                style="max-width: min(52vw, 560px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ viewingBatch.batch_name }}
            </span>
        </template>
        <template #header-right>
            <div class="ios-nav-actions">
                <button v-if="canExport" class="ios-nav-btn ios-nav-action ios-nav-btn--inline"
                    @click="emit('open-report', viewingBatch)" v-tooltip.bottom="`Generate Report`">
                    <AppIcon name="file-text" :size="16" />
                </button>
                <button v-if="canCreate" class="ios-nav-btn ios-nav-action ios-nav-btn--inline"
                    @click="emit('open-add-scholar')" v-tooltip.bottom="`Add Scholar`">
                    <AppIcon name="plus" :size="16" />
                </button>
                <div v-if="!canExport && !canCreate" style="width: 48px;"></div>
            </div>
        </template>
        <div v-if="viewingBatch">
                    <div class="ios-section">
                        <div class="ios-section-label">Batch Info</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="graduation-cap" :size="13" style="color: #34C759;" />
                                    Course
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ viewingBatch.course_name }}</span>
                            </div>
                            <div v-if="viewingBatch.exam_date_from || viewingBatch.exam_date_to" class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    Exam Dates
                                </div>
                                <span class="text-sm text-gray-700">
                                    {{ formatDateLong(viewingBatch.exam_date_from) }} -
                                    {{ formatDateLong(viewingBatch.exam_date_to) }}
                                </span>
                            </div>
                            <div v-if="viewingBatch.result_date" class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar-x" :size="13" style="color: #AF52DE;" />
                                    Result Date
                                </div>
                                <span class="text-sm text-gray-700">{{ formatDateLong(viewingBatch.result_date)
                                }}</span>
                            </div>
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="user" :size="13" style="color: #8E8E93;" />
                                    Created By
                                </div>
                                <span class="text-sm text-gray-600">{{ viewingBatch.created_by }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="viewingBatch.description" class="ios-section">
                        <div class="ios-section-label">Description</div>
                        <div class="ios-card remarks-card">
                            <p class="text-sm text-gray-600">{{ viewingBatch.description }}</p>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Scholars</div>
                        <div class="mb-2">
                            <InputText v-model="scholarSearchModel" placeholder="Search scholar name or status..."
                                class="w-full" size="small" />
                        </div>
                        <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredScholars"
                            :rows="10" paginator :rowHover="true" stripedRows showGridlines responsiveLayout="scroll">
                            <Column field="scholar_name" header="Scholar Name" sortable style="min-width: 200px">
                                <template #body="slotProps">
                                    <span class="font-semibold">{{ slotProps.data.scholar_name }}</span>
                                </template>
                            </Column>
                            <Column field="years_of_service" header="Years ROS" sortable style="width: 120px">
                                <template #body="slotProps">
                                    <span class="font-semibold text-blue-600">{{ slotProps.data.years_of_service || 0
                                    }}</span>
                                </template>
                            </Column>
                            <Column field="completion_status" header="Status" sortable style="width: 130px">
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.completion_status"
                                        :severity="getCompletionSeverity(slotProps.data.completion_status)" />
                                </template>
                            </Column>
                            <Column field="service_start_date" header="Service Start" sortable style="min-width: 140px">
                                <template #body="slotProps">
                                    <span class="font-mono text-sm">{{ formatDateLong(slotProps.data.service_start_date)
                                    }}</span>
                                </template>
                            </Column>
                            <Column field="service_end_date" header="Service End" sortable style="min-width: 140px">
                                <template #body="slotProps">
                                    <span class="font-mono text-sm">{{ formatDateLong(slotProps.data.service_end_date)
                                    }}</span>
                                </template>
                            </Column>
                            <Column field="remarks" header="Remarks" style="min-width: 200px">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.remarks" class="text-sm text-gray-700"
                                        v-safe-html="slotProps.data.remarks"></span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 120px">
                                <template #body="slotProps">
                                    <div class="flex gap-1">
                                        <AppButton icon="eye" severity="secondary" text rounded size="small"
                                            @click="emit('view-scholar', slotProps.data)" v-tooltip.top="`View`" />
                                        <AppButton v-if="canEdit" icon="pencil" severity="warning" text rounded
                                            size="small" @click="emit('open-edit-scholar', slotProps.data)"
                                            v-tooltip.top="`Edit`" />
                                        <AppButton v-if="canDelete" icon="trash" severity="danger" text rounded
                                            size="small" @click="emit('confirm-delete-scholar', slotProps.data)"
                                            v-tooltip.top="`Delete`" />
                                    </div>
                                </template>
                            </Column>
                            <template #empty>
                                <div class="text-center py-8">
                                    <p class="text-gray-500">No scholars in this batch yet.</p>
                                </div>
                            </template>
                        </DataTable>
                    </div>

                    <div style="height: 24px;"></div>
        </div>
    </IosModal>

    <IosModal :visible="showDeleteBatchDialog" title="Delete Batch" width="420px" close-icon="x"
        :icon-size="16" body-style="padding: 0 16px 24px;"
        @update:visible="(visible) => { if (!visible) emit('close-delete-batch'); }">
        <template #header-right>
            <div style="width: 48px;"></div>
        </template>
                    <div class="ios-section">
                        <div class="ios-card destructive-card">
                            <div class="ios-row destructive-row">
                                <div class="destructive-icon-wrap">
                                    <AppIcon name="exclamation-triangle" class="text-red-500" :size="16" />
                                </div>
                                <div class="destructive-copy">
                                    <p class="text-sm font-semibold text-gray-800">{{ batchToDelete?.batch_name }}</p>
                                    <p class="text-xs text-red-600 mt-1">
                                        This will delete all {{ batchToDelete?.total_scholars }} scholars in this batch.
                                        This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ios-section">
                        <button class="ios-destructive-btn" @click="emit('delete-batch')">Delete Batch</button>
                    </div>
                    <div style="height: 24px;"></div>
    </IosModal>

    <IosModal :visible="showDeleteScholarDialog" title="Remove Scholar" width="420px" close-icon="x"
        :icon-size="16" body-style="padding: 0 16px 24px;"
        @update:visible="(visible) => { if (!visible) emit('close-delete-scholar'); }">
        <template #header-right>
            <div style="width: 48px;"></div>
        </template>
                    <div class="ios-section">
                        <div class="ios-card destructive-card">
                            <div class="ios-row destructive-row">
                                <div class="destructive-icon-wrap">
                                    <AppIcon name="exclamation-triangle" class="text-red-500" :size="16" />
                                </div>
                                <div class="destructive-copy">
                                    <p class="text-sm font-semibold text-gray-700">{{ scholarToDelete?.scholar_name }}
                                    </p>
                                    <p class="text-xs text-red-600 mt-1">This action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ios-section">
                        <button class="ios-destructive-btn" @click="emit('delete-scholar')">Remove from Batch</button>
                    </div>
                    <div style="height: 24px;"></div>
    </IosModal>

    <IosModal :visible="showReportDialog" title="Generate Report" width="480px" close-icon="x"
        :icon-size="16" :show-action="true" action-icon="file-text" :action-disabled="!canSubmitReport"
        body-style="padding: 0 16px 24px;" @action="submitReport"
        @update:visible="(visible) => { if (!visible) closeReportDialog(); }">
                    <div class="ios-section">
                        <div class="ios-section-label">Output Format</div>
                        <div class="ios-card">
                            <div v-for="option in formatOptions" :key="option.value" class="ios-row">
                                <div>
                                    <div class="ios-row-label text-gray-800">{{ option.label }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ option.description }}</div>
                                </div>
                                <RadioButton v-model="reportFormat" :inputId="`report-format-${option.value}`"
                                    :value="option.value" />
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-section-label">Report Scope</div>
                        <div class="ios-card" v-if="reportContextBatch">
                            <div class="ios-row">
                                <div>
                                    <div class="ios-row-label text-gray-800">Current Batch</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ reportContextBatch.batch_name }} • {{ reportContextBatch.total_scholars || 0
                                        }} scholar(s)
                                    </div>
                                </div>
                                <Tag value="Batch" severity="info" />
                            </div>
                        </div>
                        <div class="ios-card" v-else>
                            <div v-for="option in availableReportScopeOptions" :key="option.value" class="ios-row">
                                <div class="ios-row-label text-gray-800">{{ option.label }}</div>
                                <RadioButton v-model="reportScope" :inputId="`report-scope-${option.value}`"
                                    :value="option.value" />
                            </div>
                        </div>
                    </div>

                    <div v-if="!reportContextBatch && reportScope === 'batch'" class="ios-section">
                        <div class="ios-section-label">Batch Selection</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-stacked">
                                <div class="ios-row-label">
                                    <AppIcon name="folder" :size="13" style="color: #007AFF;" />
                                    Batch
                                </div>
                                <Select v-model="reportBatchId" :options="reportBatchOptions" optionLabel="label"
                                    optionValue="value" placeholder="Select batch" class="ios-full-input" />
                            </div>
                        </div>
                    </div>

                    <div class="ios-section">
                        <div class="ios-card report-note-card">
                            <div class="ios-row report-note-row">
                                <div class="report-note-icon-wrap">
                                    <AppIcon name="info" class="text-blue-600" :size="16" />
                                </div>
                                <div class="text-sm text-gray-700 leading-6">
                                    PDF opens a printable report in a new tab. Excel downloads a workbook using the same
                                    selected scope.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 24px;"></div>
    </IosModal>

    <div v-if="showPreviewScholarDialog && Array.isArray(scholarForm.selectedProfile) && scholarForm.selectedProfile.length > 0"
        class="preview-drawer">
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 text-sm">Selected Scholars</h3>
                <button @click="emit('close-preview-scholar')" class="text-gray-400 hover:text-gray-600">
                    <AppIcon name="x" :size="14" />
                </button>
            </div>
            <div class="space-y-2">
                <div v-for="(scholar, index) in scholarForm.selectedProfile" :key="scholar.id"
                    class="bg-blue-50 p-3 rounded-2xl border border-blue-100">
                    <p class="font-semibold text-gray-800 text-sm">{{ index + 1 }}. {{ scholar.label }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import AppButton from '@/Components/ui/AppButton.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    showBatchDialog: { type: Boolean, default: false },
    showScholarDialog: { type: Boolean, default: false },
    showViewScholarDialog: { type: Boolean, default: false },
    showViewBatchDialog: { type: Boolean, default: false },
    showDeleteBatchDialog: { type: Boolean, default: false },
    showDeleteScholarDialog: { type: Boolean, default: false },
    showPreviewScholarDialog: { type: Boolean, default: false },
    showReportDialog: { type: Boolean, default: false },
    batchMode: { type: String, default: 'add' },
    scholarMode: { type: String, default: 'add' },
    batchForm: { type: Object, required: true },
    scholarForm: { type: Object, required: true },
    completionOptions: { type: Array, default: () => [] },
    scholarshipRecords: { type: Array, default: () => [] },
    isEndDateInvalid: { type: Boolean, default: false },
    viewingScholar: { type: Object, default: null },
    viewingBatch: { type: Object, default: null },
    filteredScholars: { type: Array, default: () => [] },
    scholarSearch: { type: String, default: '' },
    batchToDelete: { type: Object, default: null },
    scholarToDelete: { type: Object, default: null },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    reportBatchOptions: { type: Array, default: () => [] },
    reportContextBatch: { type: Object, default: null },
    filteredBatchCount: { type: Number, default: 0 },
    totalBatchCount: { type: Number, default: 0 },
    formatDateLong: { type: Function, required: true },
    getCompletionSeverity: { type: Function, required: true },
});

const emit = defineEmits([
    'close-batch',
    'submit-batch',
    'close-scholar',
    'submit-scholar',
    'close-view-scholar',
    'close-view-batch',
    'close-delete-batch',
    'delete-batch',
    'close-delete-scholar',
    'delete-scholar',
    'close-preview-scholar',
    'update:scholarSearch',
    'profile-filter',
    'auto-calculate-years-of-service',
    'open-add-scholar',
    'open-edit-scholar',
    'view-scholar',
    'confirm-delete-scholar',
    'open-report',
    'close-report',
    'generate-report',
]);

const scholarSearchModel = computed({
    get: () => props.scholarSearch,
    set: (value) => emit('update:scholarSearch', value),
});

const formatOptions = [
    {
        label: 'PDF Report',
        value: 'pdf',
        description: 'Open a printable report in a new tab.',
    },
    {
        label: 'Excel Workbook',
        value: 'excel',
        description: 'Download the selected report data as .xlsx.',
    },
];

const reportFormat = ref('pdf');
const reportScope = ref('filtered');
const reportBatchId = ref(null);

const availableReportScopeOptions = computed(() => {
    if (props.reportContextBatch) {
        return [];
    }

    const options = [];

    if (props.filteredBatchCount > 0) {
        options.push({
            label: `Filtered Results (${props.filteredBatchCount})`,
            value: 'filtered',
        });
    }

    if (props.totalBatchCount > 0) {
        options.push({
            label: `All Batches (${props.totalBatchCount})`,
            value: 'all',
        });
    }

    if (props.reportBatchOptions.length > 0) {
        options.push({
            label: 'Single Batch',
            value: 'batch',
        });
    }

    return options;
});

const canSubmitReport = computed(() => {
    if (props.reportContextBatch) {
        return true;
    }

    if (reportScope.value !== 'batch') {
        return true;
    }

    return !!reportBatchId.value;
});

watch(
    () => props.showReportDialog,
    (visible) => {
        if (!visible) {
            return;
        }

        reportFormat.value = 'pdf';
        reportScope.value = props.reportContextBatch
            ? 'batch'
            : (props.filteredBatchCount > 0 ? 'filtered' : (props.totalBatchCount > 0 ? 'all' : 'batch'));
        reportBatchId.value = props.reportContextBatch?.id ?? props.reportBatchOptions[0]?.value ?? null;
    }
);

const formatDateLong = (...args) => props.formatDateLong(...args);
const getCompletionSeverity = (...args) => props.getCompletionSeverity(...args);

const closeBatchDialog = () => emit('close-batch');
const closeScholarDialog = () => emit('close-scholar');
const closeReportDialog = () => emit('close-report');

const submitReport = () => {
    if (!canSubmitReport.value) {
        return;
    }

    emit('generate-report', {
        format: reportFormat.value,
        scope: props.reportContextBatch ? 'batch' : reportScope.value,
        batchId: props.reportContextBatch?.id ?? reportBatchId.value,
    });
};
</script>

