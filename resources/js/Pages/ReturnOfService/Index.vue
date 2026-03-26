<template>

    <Head title="Return of Service Batches" />

    <AdminLayout>

        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-graduation-cap text-blue-600" style="font-size:2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Return of Service</h1>
                            <p class="text-sm text-gray-600">Manage ROS batches and track scholar service obligations
                            </p>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <Button v-if="hasPermission('return-of-service.export')" icon="pi pi-download" label="Export"
                            severity="secondary" outlined rounded size="small" @click="exportRecords" />
                        <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus" label="New Batch"
                            severity="success" rounded size="small" @click="openNewBatchDialog" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel class="mb-6 !rounded-4xl overflow-hidden">
                <div class="flex items-end gap-3 -mt-6 flex-wrap">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Batch Name</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-gray-400" />
                            <InputText v-model="batchSearch" placeholder="Search batch name..." size="small" />
                        </IconField>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Created By</label>
                        <InputText v-model="batchCreatedByFilter" placeholder="Filter by creator..." size="small" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Year</label>
                        <InputNumber v-model="batchYearFilter" placeholder="e.g., 2025" :useGrouping="false"
                            size="small" inputStyle="width: 120px" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Description</label>
                        <InputText v-model="batchDescriptionFilter" placeholder="Search description..." size="small" />
                    </div>
                    <Button severity="secondary" outlined rounded size="small" icon="pi pi-history"
                        @click="clearBatchFilters" v-tooltip.bottom="`Clear Filters`" />
                </div>
            </Panel>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-blue-600">{{ batches.length }}</div>
                    <div class="text-xs text-gray-500">Total Batches</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-indigo-600">{{ filteredBatches.length }}</div>
                    <div class="text-xs text-gray-500">Filtered Results</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{batches.reduce((s, b) => s + (b.total_scholars ||
                        0), 0) }}
                    </div>
                    <div class="text-xs text-gray-500">Total Scholars</div>
                </div>
                <div class="bg-white border rounded-4xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-gray-400">{{batches.filter(b => b.result_date).length}}</div>
                    <div class="text-xs text-gray-500">With Results</div>
                </div>
            </div>

            <!-- Batch Cards -->
            <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                <div class="text-sm text-gray-500 mb-4 -mt-2">{{ filteredBatches.length }} batch(es)</div>

                <!-- Batch Cards Grid -->
                <div class="grid grid-cols-1 gap-4">
                    <div v-for="batch in filteredBatches" :key="batch.id"
                        class="bg-white !rounded-4xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="pi pi-folder text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 text-base leading-tight">{{
                                            batch.batch_name
                                            }}</h4>
                                        <div class="text-xs text-gray-500 space-y-0.5 mt-1">
                                            <p v-if="batch.exam_date_from || batch.exam_date_to">
                                                <span class="font-medium">Exam:</span>
                                                {{ formatDateLong(batch.exam_date_from) }} –
                                                {{ formatDateLong(batch.exam_date_to) }}
                                            </p>
                                            <p v-if="batch.result_date">
                                                <span class="font-medium">Result:</span>
                                                {{ formatDateLong(batch.result_date) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="pi pi-users text-xs"></i>
                                        {{ batch.total_scholars }}
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1.5">by {{ batch.created_by }}</p>
                                </div>
                            </div>

                            <div v-if="batch.description" class="bg-gray-50 rounded-2xl px-4 py-2.5 mb-4">
                                <p class="text-sm text-gray-600">{{ batch.description }}</p>
                            </div>

                            <div class="flex gap-1.5 flex-wrap border-t border-gray-100 pt-3">
                                <Button icon="pi pi-eye" label="View Batch" severity="secondary" text size="small"
                                    rounded @click="openViewBatchDialog(batch)" />
                                <Button v-if="hasPermission('return-of-service.edit')" icon="pi pi-pencil" label="Edit"
                                    severity="warning" text size="small" rounded @click="openEditBatchDialog(batch)" />
                                <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus"
                                    label="Add Scholar" severity="success" text size="small" rounded
                                    @click="openAddScholarDialog(batch)" />
                                <Button icon="pi pi-download" label="Export" severity="info" text size="small" rounded
                                    @click="exportBatch(batch)" />
                                <Button v-if="hasPermission('return-of-service.delete')" icon="pi pi-trash"
                                    severity="danger" text size="small" rounded @click="confirmDeleteBatch(batch)"
                                    v-tooltip.top="`Delete`" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="batches.length === 0" class="text-center py-16">
                    <div class="w-16 h-16 rounded-3xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-inbox text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 mb-4">No ROS batches created yet.</p>
                    <Button v-if="hasPermission('return-of-service.create')" icon="pi pi-plus"
                        label="Create First Batch" severity="success" rounded @click="openNewBatchDialog" />
                </div>

                <!-- No Results State -->
                <div v-else-if="filteredBatches.length === 0" class="text-center py-16">
                    <div class="w-16 h-16 rounded-3xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-search text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 mb-4">No batches match your filters.</p>
                    <Button icon="pi pi-times" label="Clear Filters" severity="secondary" outlined rounded
                        @click="clearBatchFilters" />
                </div>

            </Panel>
        </div>


        <!-- ══════════════════════════════════════════
            BATCH FORM MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showBatchDialog"
            @update:visible="v => { if (!v) { showBatchDialog = false; resetBatchForm(); } }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="batchDrag.modalStyle.value">
                    <div class="ios-nav-bar" @pointerdown="batchDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showBatchDialog = false; resetBatchForm();"
                            v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">{{ batchMode === 'add' ? 'New Batch' : 'Edit Batch' }}</span>
                        <button class="ios-nav-btn ios-nav-action" @click="submitBatchForm"
                            :disabled="batchForm.processing" v-tooltip.bottom="`Save`">
                            <i class="pi pi-check"></i>
                        </button>
                    </div>
                    <div class="ios-body">
                        <!-- Basic Info -->
                        <div class="ios-section">
                            <div class="ios-section-label">Basic Info</div>
                            <div class="ios-card">
                                <div class="ios-row ios-row-stacked">
                                    <div class="ios-row-label">
                                        <i class="pi pi-tag" style="color: #007AFF; font-size: 13px;"></i>
                                        Batch Name <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                    </div>
                                    <InputText v-model="batchForm.batch_name" placeholder="e.g., Batch 2025-A"
                                        class="ios-full-input" :class="{ 'p-invalid': batchForm.errors.batch_name }" />
                                </div>
                                <div class="ios-row ios-row-stacked">
                                    <div class="ios-row-label">
                                        <i class="pi pi-align-left" style="color: #8E8E93; font-size: 13px;"></i>
                                        Description
                                    </div>
                                    <Textarea v-model="batchForm.description" rows="2"
                                        placeholder="Notes about this batch" class="ios-full-input" />
                                </div>
                            </div>
                            <div v-if="batchForm.errors.batch_name" class="ios-section-footer ios-error">
                                {{ batchForm.errors.batch_name }}
                            </div>
                        </div>

                        <!-- Exam Period -->
                        <div class="ios-section">
                            <div class="ios-section-label">Exam Period</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                        Date From
                                    </div>
                                    <div class="ios-row-control">
                                        <DatePicker v-model="batchForm.exam_date_from" placeholder="Any" showIcon
                                            iconDisplay="input" class="ios-datepicker" />
                                    </div>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                        Date To
                                    </div>
                                    <div class="ios-row-control">
                                        <DatePicker v-model="batchForm.exam_date_to" placeholder="Any" showIcon
                                            iconDisplay="input" class="ios-datepicker" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">Details</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar-times" style="color: #AF52DE; font-size: 13px;"></i>
                                        Result Date
                                    </div>
                                    <div class="ios-row-control">
                                        <DatePicker v-model="batchForm.result_date" placeholder="Any" showIcon
                                            iconDisplay="input" class="ios-datepicker" />
                                    </div>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-graduation-cap" style="color: #34C759; font-size: 13px;"></i>
                                        Course <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                    </div>
                                    <div class="ios-row-control">
                                        <CourseSelect v-model="batchForm.course_id" customPlaceholder="Select"
                                            class="ios-select" />
                                    </div>
                                </div>
                            </div>
                            <div v-if="batchForm.errors.course_id" class="ios-section-footer ios-error">
                                {{ batchForm.errors.course_id }}
                            </div>
                        </div>

                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            SCHOLAR FORM MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showScholarDialog"
            @update:visible="v => { if (!v) { showScholarDialog = false; resetScholarForm(); } }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="scholarDrag.modalStyle.value">
                    <div class="ios-nav-bar" @pointerdown="scholarDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel"
                            @click="showScholarDialog = false; resetScholarForm();" v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">{{ scholarMode === 'add' ? 'Add Scholar' : 'Edit Scholar' }}</span>
                        <button class="ios-nav-btn ios-nav-action" @click="submitScholarForm"
                            :disabled="scholarForm.processing || isEndDateInvalid" v-tooltip.bottom="`Save`">
                            <i class="pi pi-check"></i>
                        </button>
                    </div>
                    <div class="ios-body">
                        <!-- Scholar Identity -->
                        <div class="ios-section">
                            <div class="ios-section-label">Scholar</div>
                            <div class="ios-card">
                                <div v-if="scholarMode === 'edit'" class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-user" style="color: #007AFF; font-size: 13px;"></i>
                                        Name
                                    </div>
                                    <span class="text-sm text-gray-700 font-medium"
                                        style="max-width: 220px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ scholarForm.record_label }}
                                    </span>
                                </div>
                                <div v-else class="ios-row ios-row-stacked">
                                    <div class="ios-row-label">
                                        <i class="pi pi-users" style="color: #007AFF; font-size: 13px;"></i>
                                        Select Scholars <span style="color: #FF3B30; margin-left: 2px;">*</span>
                                    </div>
                                    <MultiSelect v-model="scholarForm.selectedProfile" :options="scholarshipRecords"
                                        optionLabel="label" placeholder="Search completed scholars..." :filter="true"
                                        class="ios-full-input" @filter="onProfileFilter" />
                                    <small class="text-gray-500 text-xs">Only completed records from Medicine and
                                        Medical Allied Courses program</small>
                                </div>
                            </div>
                            <div v-if="scholarForm.errors.profile_id" class="ios-section-footer ios-error">
                                {{ scholarForm.errors.profile_id }}
                            </div>
                        </div>

                        <!-- Service Dates -->
                        <div class="ios-section">
                            <div class="ios-section-label">Return of Service</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #34C759; font-size: 13px;"></i>
                                        Start Date
                                    </div>
                                    <div class="ios-row-control">
                                        <DatePicker v-model="scholarForm.service_start_date" placeholder="Select"
                                            showIcon iconDisplay="input" class="ios-datepicker" />
                                    </div>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
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
                                        <i class="pi pi-clock" style="color: #5856D6; font-size: 13px;"></i>
                                        Years of Service
                                    </div>
                                    <div class="ios-row-control">
                                        <InputNumber v-model="scholarForm.years_of_service" :min="0" placeholder="Auto"
                                            class="ios-inputnumber" />
                                    </div>
                                </div>
                            </div>
                            <div v-if="isEndDateInvalid" class="ios-section-footer ios-error">
                                End date cannot be earlier than start date
                            </div>
                            <div v-else class="ios-section-footer">
                                Auto-calculated from service dates (editable)
                            </div>
                        </div>

                        <!-- Completion Status -->
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

                        <!-- Remarks -->
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
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            VIEW SCHOLAR MODAL (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showViewScholarDialog" @update:visible="v => { if (!v) showViewScholarDialog = false; }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="viewScholarDrag.modalStyle.value">
                    <div class="ios-nav-bar" @pointerdown="viewScholarDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showViewScholarDialog = false"
                            v-tooltip.bottom="`Close`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Scholar Details</span>
                        <div style="width: 48px;"></div>
                    </div>
                    <div class="ios-body" v-if="viewingScholar">
                        <!-- Scholar Name Banner -->
                        <div class="ios-section">
                            <div class="ios-card" style="background: #EFF6FF; border-color: #BFDBFE;">
                                <div class="ios-row" style="min-height: 48px;">
                                    <div class="ios-row-label"
                                        style="font-size: 15px; font-weight: 600; color: #1D4ED8;">
                                        {{ viewingScholar.scholar_name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Details -->
                        <div class="ios-section">
                            <div class="ios-section-label">Return of Service</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-clock" style="color: #5856D6; font-size: 13px;"></i>
                                        Years of Service
                                    </div>
                                    <span class="font-bold text-blue-600" style="font-size: 16px;">
                                        {{ viewingScholar.years_of_service || 0 }}
                                    </span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-check-circle" style="color: #34C759; font-size: 13px;"></i>
                                        Status
                                    </div>
                                    <Tag :value="viewingScholar.completion_status"
                                        :severity="getCompletionSeverity(viewingScholar.completion_status)" />
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #34C759; font-size: 13px;"></i>
                                        Service Start
                                    </div>
                                    <span class="text-sm text-gray-700">
                                        {{ formatDateLong(viewingScholar.service_start_date) }}
                                    </span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                        Service End
                                    </div>
                                    <span class="text-sm text-gray-700">
                                        {{ formatDateLong(viewingScholar.service_end_date) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div v-if="viewingScholar.remarks" class="ios-section">
                            <div class="ios-section-label">Remarks</div>
                            <div class="ios-card" style="padding: 10px 16px;">
                                <p class="text-sm text-gray-700" v-html="viewingScholar.remarks"></p>
                            </div>
                        </div>

                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            VIEW BATCH MODAL (iOS – wide)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showViewBatchDialog"
            @update:visible="v => { if (!v) { showViewBatchDialog = false; viewingBatch = null; scholarSearch = ''; } }"
            modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal ios-modal-wide" :style="viewBatchDrag.modalStyle.value" v-if="viewingBatch">
                    <div class="ios-nav-bar" @pointerdown="viewBatchDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel"
                            @click="showViewBatchDialog = false; viewingBatch = null; scholarSearch = '';"
                            v-tooltip.bottom="`Close`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title"
                            style="max-width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ viewingBatch.batch_name }}
                        </span>
                        <button v-if="hasPermission('return-of-service.create')" class="ios-nav-btn ios-nav-action"
                            @click="openAddScholarDialog(viewingBatch)" v-tooltip.bottom="`Add Scholar`">
                            <i class="pi pi-plus"></i>
                        </button>
                        <div v-else style="width: 48px;"></div>
                    </div>
                    <div class="ios-body">
                        <!-- Batch Info -->
                        <div class="ios-section">
                            <div class="ios-section-label">Batch Info</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-graduation-cap" style="color: #34C759; font-size: 13px;"></i>
                                        Course
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ viewingBatch.course_name
                                    }}</span>
                                </div>
                                <div v-if="viewingBatch.exam_date_from || viewingBatch.exam_date_to" class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                        Exam Dates
                                    </div>
                                    <span class="text-sm text-gray-700">
                                        {{ formatDateLong(viewingBatch.exam_date_from) }} –
                                        {{ formatDateLong(viewingBatch.exam_date_to) }}
                                    </span>
                                </div>
                                <div v-if="viewingBatch.result_date" class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-calendar-times" style="color: #AF52DE; font-size: 13px;"></i>
                                        Result Date
                                    </div>
                                    <span class="text-sm text-gray-700">{{ formatDateLong(viewingBatch.result_date)
                                    }}</span>
                                </div>
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <i class="pi pi-user" style="color: #8E8E93; font-size: 13px;"></i>
                                        Created By
                                    </div>
                                    <span class="text-sm text-gray-600">{{ viewingBatch.created_by }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="viewingBatch.description" class="ios-section">
                            <div class="ios-section-label">Description</div>
                            <div class="ios-card" style="padding: 10px 16px;">
                                <p class="text-sm text-gray-600">{{ viewingBatch.description }}</p>
                            </div>
                        </div>

                        <!-- Scholars Table -->
                        <div class="ios-section">
                            <div class="ios-section-label">Scholars</div>
                            <div class="mb-2">
                                <InputText v-model="scholarSearch" placeholder="Search scholar name or status..."
                                    class="w-full" size="small" />
                            </div>
                            <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="filteredScholars"
                                :rows="10" paginator :rowHover="true" stripedRows showGridlines
                                responsiveLayout="scroll">
                                <Column field="scholar_name" header="Scholar Name" sortable style="min-width: 200px">
                                    <template #body="slotProps">
                                        <span class="font-semibold">{{ slotProps.data.scholar_name }}</span>
                                    </template>
                                </Column>
                                <Column field="years_of_service" header="Years ROS" sortable style="width: 120px">
                                    <template #body="slotProps">
                                        <span class="font-semibold text-blue-600">{{ slotProps.data.years_of_service ||
                                            0 }}</span>
                                    </template>
                                </Column>
                                <Column field="completion_status" header="Status" sortable style="width: 130px">
                                    <template #body="slotProps">
                                        <Tag :value="slotProps.data.completion_status"
                                            :severity="getCompletionSeverity(slotProps.data.completion_status)" />
                                    </template>
                                </Column>
                                <Column field="service_start_date" header="Service Start" sortable
                                    style="min-width: 140px">
                                    <template #body="slotProps">
                                        <span class="font-mono text-sm">{{
                                            formatDateLong(slotProps.data.service_start_date) }}</span>
                                    </template>
                                </Column>
                                <Column field="service_end_date" header="Service End" sortable style="min-width: 140px">
                                    <template #body="slotProps">
                                        <span class="font-mono text-sm">{{
                                            formatDateLong(slotProps.data.service_end_date) }}</span>
                                    </template>
                                </Column>
                                <Column field="remarks" header="Remarks" style="min-width: 200px">
                                    <template #body="slotProps">
                                        <span v-if="slotProps.data.remarks" class="text-sm text-gray-700"
                                            v-html="slotProps.data.remarks"></span>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </template>
                                </Column>
                                <Column header="Actions" style="width: 120px">
                                    <template #body="slotProps">
                                        <div class="flex gap-1">
                                            <Button icon="pi pi-eye" severity="secondary" text rounded size="small"
                                                @click="viewScholar(slotProps.data)" v-tooltip.top="`View`" />
                                            <Button v-if="hasPermission('return-of-service.edit')" icon="pi pi-pencil"
                                                severity="warning" text rounded size="small"
                                                @click="openEditScholarDialog(viewingBatch, slotProps.data)"
                                                v-tooltip.top="`Edit`" />
                                            <Button v-if="hasPermission('return-of-service.delete')" icon="pi pi-trash"
                                                severity="danger" text rounded size="small"
                                                @click="confirmDeleteScholar(slotProps.data)"
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
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            DELETE BATCH CONFIRM (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showDeleteBatchDialog" @update:visible="v => { if (!v) showDeleteBatchDialog = false; }" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="deleteBatchDrag.modalStyle.value">
                    <div class="ios-nav-bar" @pointerdown="deleteBatchDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showDeleteBatchDialog = false"
                            v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Delete Batch</span>
                        <div style="width: 48px;"></div>
                    </div>
                    <div class="ios-body">
                        <div class="ios-section">
                            <div class="ios-card" style="background: #FFF5F5; border-color: #FECACA;">
                                <div class="ios-row"
                                    style="gap: 12px; padding: 14px 16px; min-height: 60px; align-items: flex-start;">
                                    <div
                                        class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                        <i class="pi pi-exclamation-triangle text-red-500 text-sm"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p class="text-sm font-semibold text-gray-800">{{ batchToDelete?.batch_name }}
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">
                                            This will delete all {{ batchToDelete?.total_scholars }} scholars in this
                                            batch. This action cannot be undone.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ios-section">
                            <button class="ios-destructive-btn" @click="deleteBatch">Delete Batch</button>
                        </div>
                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- ══════════════════════════════════════════
            DELETE SCHOLAR CONFIRM (iOS)
        ══════════════════════════════════════════ -->
        <Dialog :visible="showDeleteScholarDialog" @update:visible="v => { if (!v) showDeleteScholarDialog = false; }"
            modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="deleteScholarDrag.modalStyle.value">
                    <div class="ios-nav-bar" @pointerdown="deleteScholarDrag.onDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showDeleteScholarDialog = false"
                            v-tooltip.bottom="`Cancel`">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Remove Scholar</span>
                        <div style="width: 48px;"></div>
                    </div>
                    <div class="ios-body">
                        <div class="ios-section">
                            <div class="ios-card" style="background: #FFF5F5; border-color: #FECACA;">
                                <div class="ios-row"
                                    style="gap: 12px; padding: 14px 16px; min-height: 60px; align-items: flex-start;">
                                    <div
                                        class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                        <i class="pi pi-exclamation-triangle text-red-500 text-sm"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p class="text-sm font-semibold text-gray-700">{{ scholarToDelete?.scholar_name
                                        }}</p>
                                        <p class="text-xs text-red-600 mt-1">This action cannot be undone.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ios-section">
                            <button class="ios-destructive-btn" @click="deleteScholar">Remove from Batch</button>
                        </div>
                        <div style="height: 24px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Preview Scholar Drawer -->
        <div v-if="showPreviewScholarDialog && Array.isArray(scholarForm.selectedProfile) && scholarForm.selectedProfile.length > 0"
            class="fixed right-0 top-0 h-screen w-80 bg-white border-l border-gray-200 shadow-2xl overflow-y-auto rounded-l-2xl"
            style="z-index: 9999;">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 text-sm">Selected Scholars</h3>
                    <button @click="showPreviewScholarDialog = false" class="text-gray-400 hover:text-gray-600">
                        <i class="pi pi-times text-sm"></i>
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

        <!-- Toast Notifications with higher z-index than drawer -->
        <Toast position="top-right" :life="3500" :baseZIndex="20000" />
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed, reactive, onBeforeUnmount } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Toolbar from 'primevue/toolbar';
import Panel from 'primevue/panel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import DatePicker from 'primevue/datepicker';
import Toast from 'primevue/toast';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import { usePermission } from '@/composable/permissions';

// ─── Draggable Modal Factory ───
function useDraggable(width) {
    const dragOffset = ref({ x: 0, y: 0 });
    const dragStart = ref(null);
    const modalStyle = computed(() => ({
        width,
        transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
    }));
    function onDragStart(e) {
        if (e.target.closest('button, .p-editor')) return;
        dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
        document.addEventListener('pointermove', onDragMove);
        document.addEventListener('pointerup', onDragEnd);
    }
    function onDragMove(e) {
        if (!dragStart.value) return;
        dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
    }
    function onDragEnd() {
        dragStart.value = null;
        document.removeEventListener('pointermove', onDragMove);
        document.removeEventListener('pointerup', onDragEnd);
    }
    function cleanup() {
        document.removeEventListener('pointermove', onDragMove);
        document.removeEventListener('pointerup', onDragEnd);
    }
    return { modalStyle, onDragStart, cleanup };
}

const batchDrag = useDraggable('520px');
const scholarDrag = useDraggable('600px');
const viewScholarDrag = useDraggable('480px');
const viewBatchDrag = useDraggable('90vw');
const deleteBatchDrag = useDraggable('420px');
const deleteScholarDrag = useDraggable('420px');

const props = defineProps({
    batches: Array,
    courses: Array,
    completionOptions: Array,
});

const { hasPermission } = usePermission();
const toast = useToast();

// Dialog states
const showBatchDialog = ref(false);
const showScholarDialog = ref(false);
const showViewScholarDialog = ref(false);
const showViewBatchDialog = ref(false);
const showDeleteBatchDialog = ref(false);
const showDeleteScholarDialog = ref(false);
const showPreviewScholarDialog = ref(false);

// Modes
const batchMode = ref('add');
const scholarMode = ref('add');

// Data
const batches = ref(props.batches || []);
const scholarshipRecords = ref([]);
const viewingScholar = ref(null);
const viewingBatch = ref(null);
const batchToDelete = ref(null);
const scholarToDelete = ref(null);
const currentBatch = ref(null);
const currentScholar = ref(null);
const scholarSearch = ref('');
const batchSearch = ref('');
const batchCreatedByFilter = ref('');
const batchDescriptionFilter = ref('');
const batchYearFilter = ref(null);

// Watch for changes in props.batches and update local ref
watch(
    () => props.batches,
    (newBatches) => {
        batches.value = newBatches || [];
    }
);

// Forms
const batchForm = useForm({
    batch_name: '',
    description: '',
    exam_date_from: null,
    exam_date_to: null,
    result_date: null,
    course_id: null,
});

const scholarForm = reactive({
    batch_id: null,
    profile_id: null,
    selectedProfile: null,
    lastname: '',
    firstname: '',
    middlename: '',
    ext: '',
    years_of_service: null,
    service_start_date: null,
    service_end_date: null,
    completion_status: 'pending',
    remarks: '',
    record_label: '',
    errors: {},
    processing: false,
    reset() {
        this.batch_id = null;
        this.profile_id = null;
        this.selectedProfile = null;
        this.lastname = '';
        this.firstname = '';
        this.middlename = '';
        this.ext = '';
        this.years_of_service = null;
        this.service_start_date = null;
        this.service_end_date = null;
        this.completion_status = 'pending';
        this.remarks = '';
        this.record_label = '';
        this.errors = {};
    },
    clearErrors() {
        this.errors = {};
    },
});

// Computed property to validate end date is not before start date
const isEndDateInvalid = computed(() => {
    if (!scholarForm.service_start_date || !scholarForm.service_end_date) {
        return false;
    }

    let startDate = scholarForm.service_start_date;
    let endDate = scholarForm.service_end_date;

    if (typeof startDate === 'string') {
        startDate = new Date(startDate);
    }
    if (typeof endDate === 'string') {
        endDate = new Date(endDate);
    }

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        return false;
    }

    return endDate < startDate;
});
const computedYearsOfService = computed(() => {
    console.log('Computing years of service...');
    console.log('Start date:', scholarForm.service_start_date, 'type:', typeof scholarForm.service_start_date);
    console.log('End date:', scholarForm.service_end_date, 'type:', typeof scholarForm.service_end_date);

    if (!scholarForm.service_start_date || !scholarForm.service_end_date) {
        console.log('Missing dates, returning null');
        return null;
    }

    let startDate = scholarForm.service_start_date;
    let endDate = scholarForm.service_end_date;

    // Convert strings to Date objects if needed
    if (typeof startDate === 'string') {
        startDate = new Date(startDate);
    }
    if (typeof endDate === 'string') {
        endDate = new Date(endDate);
    }

    console.log('Parsed start:', startDate);
    console.log('Parsed end:', endDate);

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        console.log('Invalid dates');
        return null;
    }

    // Calculate difference in days and convert to years with decimal places
    const diffTime = endDate - startDate;
    const diffDays = diffTime / (1000 * 60 * 60 * 24);

    console.log('Diff days:', diffDays);

    // Convert days to months (average 30.44 days per month), then to years
    const monthsDecimal = diffDays / 30.44;
    const yearsDecimal = monthsDecimal / 12;

    // Round to 1 decimal place (e.g., 3.5 for 3 years 6 months)
    const rounded = Math.round(yearsDecimal * 10) / 10;

    console.log('Computed years:', rounded);

    return rounded >= 0 ? rounded : null;
});

// Watch for date changes to auto-populate years of service
watch(
    () => [scholarForm.service_start_date, scholarForm.service_end_date],
    () => {
        console.log('Dates changed:', {
            start: scholarForm.service_start_date,
            end: scholarForm.service_end_date,
            computed: computedYearsOfService.value
        });
        // Auto-populate years_of_service if both dates are set and years_of_service is empty
        if (computedYearsOfService.value !== null && !scholarForm.years_of_service) {
            scholarForm.years_of_service = computedYearsOfService.value;
        }
    },
    { deep: true }
);

// Watch for profile selection
watch(() => scholarForm.selectedProfile, (selected) => {
    console.log('=== PROFILE SELECTION CHANGED ===');
    console.log('selected value:', selected);
    console.log('is array:', Array.isArray(selected));

    if (Array.isArray(selected) && selected.length > 0) {
        // MultiSelect returns an array, get the first (and only) selected item
        console.log('first item:', selected[0]);
        scholarForm.profile_id = selected[0].id;
        scholarForm.record_label = selected[0].label;
        // Populate name fields from profile
        scholarForm.lastname = selected[0].lastname || '';
        scholarForm.firstname = selected[0].firstname || '';
        scholarForm.middlename = selected[0].middlename || '';
        scholarForm.ext = selected[0].ext || '';
        console.log('Set profile_id to:', scholarForm.profile_id);
        // Open preview drawer
        showPreviewScholarDialog.value = true;
    } else if (selected && selected.id) {
        // Fallback for single object
        scholarForm.profile_id = selected.id;
        scholarForm.record_label = selected.label;
        // Populate name fields from profile
        scholarForm.lastname = selected.lastname || '';
        scholarForm.firstname = selected.firstname || '';
        scholarForm.middlename = selected.middlename || '';
        scholarForm.ext = selected.ext || '';
        console.log('Set profile_id (single object) to:', scholarForm.profile_id);
        // Open preview drawer
        showPreviewScholarDialog.value = true;
    } else {
        // Clear if nothing selected
        scholarForm.profile_id = null;
        scholarForm.record_label = '';
        scholarForm.lastname = '';
        scholarForm.firstname = '';
        scholarForm.middlename = '';
        scholarForm.ext = '';
        console.log('Cleared profile_id');
        // Close preview drawer
        showPreviewScholarDialog.value = false;
    }
}, { deep: true });

// Batch management
const openViewBatchDialog = (batch) => {
    viewingBatch.value = batch;
    showViewBatchDialog.value = true;
};

const openNewBatchDialog = () => {
    batchMode.value = 'add';
    batchForm.reset();
    batchForm.clearErrors();
    showBatchDialog.value = true;
};

const openEditBatchDialog = (batch) => {
    batchMode.value = 'edit';
    batchForm.reset();
    batchForm.clearErrors();
    batchForm.batch_name = batch.batch_name;
    batchForm.description = batch.description;
    batchForm.exam_date_from = batch.exam_date_from ? new Date(batch.exam_date_from) : null;
    batchForm.exam_date_to = batch.exam_date_to ? new Date(batch.exam_date_to) : null;
    batchForm.result_date = batch.result_date ? new Date(batch.result_date) : null;
    batchForm.course_id = batch.course_id ? { id: batch.course_id, name: batch.course_name } : null;
    batchForm._method = 'PUT';
    currentBatch.value = batch;
    showBatchDialog.value = true;
};

const submitBatchForm = async () => {
    const url = batchMode.value === 'add'
        ? route('return-of-service.batch.store')
        : route('return-of-service.batch.update', currentBatch.value.id);

    const formData = new FormData();
    formData.append('batch_name', batchForm.batch_name);
    if (batchForm.description) formData.append('description', batchForm.description);
    if (batchForm.exam_date_from) formData.append('exam_date_from', formatDate(batchForm.exam_date_from));
    if (batchForm.exam_date_to) formData.append('exam_date_to', formatDate(batchForm.exam_date_to));
    if (batchForm.result_date) formData.append('result_date', formatDate(batchForm.result_date));
    if (batchForm.course_id) {
        const courseId = typeof batchForm.course_id === 'object' ? batchForm.course_id.id : batchForm.course_id;
        formData.append('course_id', courseId);
    }

    if (batchMode.value === 'edit') {
        formData.append('_method', 'PUT');
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        const response = await axios.post(url, formData, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = response.data;

        showBatchDialog.value = false;
        batchForm.reset();
        batchForm.clearErrors();
        currentBatch.value = null;

        const actionMsg = batchMode.value === 'add' ? 'created' : 'updated';
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Batch ${actionMsg} successfully`,
            life: 3000
        });

        // Update local batches array directly
        if (batchMode.value === 'add') {
            // Add new batch to list
            const newBatch = {
                ...data.batch,
                scholars: [],
                total_scholars: 0
            };
            batches.value.push(newBatch);
        } else {
            // Update existing batch
            const batchIndex = batches.value.findIndex(b => b.id === data.batch.id);
            if (batchIndex !== -1) {
                batches.value[batchIndex] = {
                    ...batches.value[batchIndex],
                    ...data.batch
                };
            }
        }
    } catch (error) {
        console.error('Error submitting batch form:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save batch',
            life: 5000
        });
    }
};


const fetchBatches = async () => {
    // Function to reload batches if needed (not used currently)
    // Data updates are handled directly in submitBatchForm and deleteBatch
};

const resetBatchForm = () => {
    batchForm.reset();
    batchForm.clearErrors();
    currentBatch.value = null;
};

const clearBatchFilters = () => {
    batchSearch.value = '';
    batchCreatedByFilter.value = '';
    batchDescriptionFilter.value = '';
    batchYearFilter.value = null;
};

const confirmDeleteBatch = (batch) => {
    batchToDelete.value = batch;
    showDeleteBatchDialog.value = true;
};

const deleteBatch = async () => {
    try {
        const deletedBatchId = batchToDelete.value.id; // Store ID before clearing
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await axios.post(
            route('return-of-service.batch.destroy', deletedBatchId),
            { _method: 'DELETE' },
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }
        );

        const data = response.data;

        showDeleteBatchDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Batch deleted successfully',
            life: 3000
        });

        // Remove batch from local array
        const batchIndex = batches.value.findIndex(b => b.id === deletedBatchId);
        if (batchIndex !== -1) {
            batches.value.splice(batchIndex, 1);
        }

        batchToDelete.value = null;
    } catch (error) {
        console.error('Error:', error);
        const message = error.response?.data?.message || 'Failed to delete batch';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 5000
        });
    }
};

// Scholar management
const openAddScholarDialog = (batch) => {
    scholarMode.value = 'add';
    scholarForm.reset();
    scholarForm.clearErrors();
    scholarForm.batch_id = batch.id;
    scholarForm.completion_status = 'pending';
    currentBatch.value = batch;
    currentScholar.value = null;
    scholarshipRecords.value = [];
    showScholarDialog.value = true;
};

const openEditScholarDialog = (batch, scholar) => {
    scholarMode.value = 'edit';
    scholarForm.reset();
    scholarForm.clearErrors();
    scholarForm.batch_id = batch.id;
    scholarForm.profile_id = scholar.profile_id;
    scholarForm.years_of_service = scholar.years_of_service;
    scholarForm.service_start_date = scholar.service_start_date ? new Date(scholar.service_start_date) : null;
    scholarForm.service_end_date = scholar.service_end_date ? new Date(scholar.service_end_date) : null;
    scholarForm.completion_status = scholar.completion_status;
    scholarForm.remarks = scholar.remarks;
    scholarForm.record_label = scholar.scholar_name || 'N/A';
    currentBatch.value = batch;
    currentScholar.value = scholar;
    showScholarDialog.value = true;
};

const formatDate = (date) => {
    if (!date) return '';
    if (typeof date === 'string') {
        // If it's already a string, check if it's valid
        if (date.match(/^\d{4}-\d{2}-\d{2}$/)) return date;
        return '';
    }
    if (date instanceof Date && !isNaN(date)) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    return '';
};

const formatDateLong = (date) => {
    if (!date) return '-';
    let dateObj;
    if (typeof date === 'string') {
        dateObj = new Date(date);
    } else if (date instanceof Date) {
        dateObj = date;
    } else {
        return '-';
    }

    if (isNaN(dateObj)) return '-';

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const month = monthNames[dateObj.getMonth()];
    const day = dateObj.getDate();
    const year = dateObj.getFullYear();
    return `${month} ${day}, ${year}`;
};

const formatDateInput = (event) => {
    const input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove non-digits

    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }

    input.value = value;

    // Try to parse and emit the date if it's complete
    if (value.length === 10) {
        const [month, day, year] = value.split('/');
        const date = new Date(year, month - 1, day);
        if (!isNaN(date.getTime())) {
            // Update the appropriate form field based on which input triggered this
            const fieldName = input.id || (input.parentElement?.querySelector('[id]')?.id);
            // The input will automatically update via v-model
        }
    }
};

const submitScholarForm = async () => {
    scholarForm.processing = true;
    try {
        const updatedBatchId = scholarForm.batch_id;
        const isEditMode = scholarMode.value === 'edit';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        let successCount = 0;
        let errorCount = 0;
        let lastError = '';

        if (isEditMode) {
            // Edit mode - update single scholar
            const url = route('return-of-service.scholar.update', currentScholar.value.id);
            const formData = new FormData();
            if (currentBatch.value.course_id) {
                const courseId = typeof currentBatch.value.course_id === 'object' ? currentBatch.value.course_id.id : currentBatch.value.course_id;
                formData.append('course_id', courseId);
            }
            formData.append('years_of_service', Math.round(scholarForm.years_of_service));
            if (scholarForm.service_start_date) formData.append('service_start_date', formatDate(scholarForm.service_start_date));
            if (scholarForm.service_end_date) formData.append('service_end_date', formatDate(scholarForm.service_end_date));
            formData.append('completion_status', scholarForm.completion_status);
            if (scholarForm.remarks) formData.append('remarks', scholarForm.remarks);
            formData.append('_method', 'PUT');

            try {
                const response = await axios.post(url, formData, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = response.data;
                successCount = 1;
            } catch (error) {
                if (error.response?.data?.errors) {
                    scholarForm.errors = error.response.data.errors;
                }
                lastError = error.response?.data?.message || 'Failed to update scholar';
                errorCount = 1;
            }
        } else {
            // Add mode - handle multiple selected scholars
            const selectedProfiles = Array.isArray(scholarForm.selectedProfile) ? scholarForm.selectedProfile : [scholarForm.selectedProfile];

            for (const profile of selectedProfiles) {
                const url = route('return-of-service.scholar.store');
                const formData = new FormData();

                // Required fields
                formData.append('batch_id', updatedBatchId);
                formData.append('profile_id', profile.id);
                formData.append('completion_status', scholarForm.completion_status || 'pending');

                // Optional fields
                if (currentBatch.value.course_id) {
                    const courseId = typeof currentBatch.value.course_id === 'object' ? currentBatch.value.course_id.id : currentBatch.value.course_id;
                    formData.append('course_id', courseId);
                }
                if (scholarForm.years_of_service !== null && scholarForm.years_of_service !== '') {
                    formData.append('years_of_service', Math.round(scholarForm.years_of_service));
                }
                if (scholarForm.remarks) formData.append('remarks', scholarForm.remarks);

                const serviceStartDate = formatDate(scholarForm.service_start_date);
                if (serviceStartDate) formData.append('service_start_date', serviceStartDate);

                const serviceEndDate = formatDate(scholarForm.service_end_date);
                if (serviceEndDate) formData.append('service_end_date', serviceEndDate);

                try {
                    const response = await axios.post(url, formData, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    const data = response.data;
                    successCount++;
                } catch (error) {
                    errorCount++;
                    lastError = error.response?.data?.message || 'Failed to add scholar';
                }
            }
        }

        if (errorCount > 0 && successCount === 0) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: lastError,
                life: 5000
            });
            scholarForm.processing = false;
            return;
        }

        showScholarDialog.value = false;
        scholarForm.reset();
        scholarForm.clearErrors();
        currentScholar.value = null;

        // Show success toast
        const msg = isEditMode
            ? 'Scholar updated successfully'
            : successCount === 1
                ? 'Scholar added successfully'
                : `${successCount} scholars added successfully`;

        if (errorCount > 0) {
            toast.add({
                severity: 'warn',
                summary: 'Partial Success',
                detail: `${msg}. ${errorCount} failed.`,
                life: 5000
            });
        } else {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: msg,
                life: 3000
            });
        }

        // Refresh the current batch to show updated scholars
        if (viewingBatch.value && viewingBatch.value.id === updatedBatchId) {
            try {
                const res = await axios.get(route('return-of-service.batch.show', updatedBatchId));
                const batchData = res.data;
                if (batchData && batchData.scholars) {
                    viewingBatch.value.scholars = batchData.scholars;
                    // Update the batch in the batches array as well
                    const batchIndex = batches.value.findIndex(b => b.id === updatedBatchId);
                    if (batchIndex !== -1) {
                        batches.value[batchIndex].scholars = batchData.scholars;
                        batches.value[batchIndex].total_scholars = batchData.scholars.length;
                    }
                }
            } catch (error) {
                console.error('Error fetching updated batch:', error);
            }
        } else {
            // If batch is not being viewed, we still need to update the batches array
            const batchIndex = batches.value.findIndex(b => b.id === updatedBatchId);
            if (batchIndex !== -1) {
                try {
                    const res = await axios.get(route('return-of-service.batch.show', updatedBatchId));
                    const batchData = res.data;
                    if (batchData && batchData.scholars) {
                        batches.value[batchIndex].scholars = batchData.scholars;
                        batches.value[batchIndex].total_scholars = batchData.scholars.length;
                    }
                } catch (error) {
                    console.error('Error fetching updated batch:', error);
                }
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while submitting the form',
            life: 5000
        });
    } finally {
        scholarForm.processing = false;
    }
};

const resetScholarForm = () => {
    scholarForm.reset();
    scholarForm.clearErrors();
    currentBatch.value = null;
    currentScholar.value = null;
};

const confirmDeleteScholar = (scholar) => {
    scholarToDelete.value = scholar;
    showDeleteScholarDialog.value = true;
};

const deleteScholar = async () => {
    const scholarId = scholarToDelete.value.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        const response = await axios.post(
            route('return-of-service.scholar.destroy', scholarId),
            { _method: 'DELETE' },
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }
        );

        const data = response.data;

        showDeleteScholarDialog.value = false;
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Scholar deleted successfully',
            life: 3000
        });

        // Update viewing batch if open
        if (viewingBatch.value && viewingBatch.value.scholars) {
            const scholarIndex = viewingBatch.value.scholars.findIndex(s => s.id === scholarId);
            if (scholarIndex !== -1) {
                viewingBatch.value.scholars.splice(scholarIndex, 1);
                viewingBatch.value.total_scholars = viewingBatch.value.scholars.length;
            }
        }

        scholarToDelete.value = null;
    } catch (error) {
        console.error('Error:', error);
        const message = error.response?.data?.message || 'Failed to delete scholar';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 5000
        });
    }
};

const viewScholar = (scholar) => {
    viewingScholar.value = scholar;
    showViewScholarDialog.value = true;
};

const searchProfiles = async (query) => {
    if (!query || query.length < 1) {
        scholarshipRecords.value = [];
        return;
    }
    try {
        const response = await axios.get(route('return-of-service.search-records'), {
            params: {
                q: query,
                status: 'completed',
                program: 'MEDICINE AND MEDICAL ALLIED COURSES'
            }
        });

        // Filter out profiles already in the current batch
        const filteredProfiles = response.data.filter(profile => {
            return !isScholarAlreadyInBatch(profile.id);
        });

        scholarshipRecords.value = filteredProfiles;
    } catch (error) {
        console.error('Error searching records:', error);
    }
};

const onProfileFilter = (event) => {
    searchProfiles(event.value);
};

const isScholarAlreadyInBatch = (profileId) => {
    if (!currentBatch.value || !currentBatch.value.scholars) {
        return false;
    }
    return currentBatch.value.scholars.some(scholar => scholar.profile_id === profileId);
};

const exportBatch = (batch) => {
    window.location.href = route('return-of-service.export') + '?batch_id=' + batch.id;
};

const exportRecords = () => {
    window.location.href = route('return-of-service.export');
};

const getCompletionSeverity = (status) => {
    const severityMap = {
        'pending': 'secondary',
        'ongoing': 'info',
        'suspended': 'warning',
        'completed': 'success',
    };
    return severityMap[status] || 'info';
};

const filteredBatches = computed(() => {
    if (!batches.value || batches.value.length === 0) return [];

    const nameQuery = batchSearch.value.toLowerCase().trim();
    const creatorQuery = batchCreatedByFilter.value.toLowerCase().trim();
    const descQuery = batchDescriptionFilter.value.toLowerCase().trim();
    const selectedYear = batchYearFilter.value;

    return batches.value.filter(batch => {
        const matchesName = !nameQuery || batch.batch_name?.toLowerCase().includes(nameQuery);
        const matchesCreator = !creatorQuery || batch.created_by?.toLowerCase().includes(creatorQuery);
        const matchesDesc = !descQuery || batch.description?.toLowerCase().includes(descQuery);

        let matchesYear = true;
        if (selectedYear) {
            const examYear = batch.exam_date_from ? new Date(batch.exam_date_from).getFullYear() : null;
            matchesYear = examYear === selectedYear;
        }

        return matchesName && matchesCreator && matchesDesc && matchesYear;
    });
});

const filteredScholars = computed(() => {
    if (!viewingBatch.value || !viewingBatch.value.scholars) return [];

    const query = scholarSearch.value.toLowerCase().trim();
    if (!query) return viewingBatch.value.scholars;

    return viewingBatch.value.scholars.filter(scholar =>
        scholar.scholar_name?.toLowerCase().includes(query) ||
        scholar.course_name?.toLowerCase().includes(query) ||
        scholar.completion_status?.toLowerCase().includes(query)
    );
});

onBeforeUnmount(() => {
    batchDrag.cleanup();
    scholarDrag.cleanup();
    viewScholarDrag.cleanup();
    viewBatchDrag.cleanup();
    deleteBatchDrag.cleanup();
    deleteScholarDrag.cleanup();
});
</script>

<style scoped>
/* ── iOS Row Layout ── */
.ios-row-label {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.ios-row-control {
    flex: 0 0 200px;
    width: 200px;
    min-width: 0;
}

.ios-row-control> :deep(*) {
    width: 100%;
    min-width: 0;
}

.ios-row-stacked {
    flex-direction: column;
    align-items: flex-start;
    padding: 10px 16px;
    min-height: auto;
    gap: 6px;
}

.ios-full-input {
    width: 100%;
}

/* Wide batch view modal */
.ios-modal-wide {
    height: 85vh;
}

/* Page-level input rounding */
:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-inputnumber-input) {
    border-radius: 1rem;
}

/* Destructive Button */
.ios-destructive-btn {
    display: block;
    width: 100%;
    background: #FFFFFF;
    border: 0.5px solid #E5E5EA;
    border-radius: 10px;
    padding: 12px;
    text-align: center;
    font-size: 15px;
    color: #FF3B30;
    cursor: pointer;
    letter-spacing: -0.4px;
    transition: background 0.15s;
}

.ios-destructive-btn:hover {
    background: #F2F2F7;
}

/* ── PrimeVue Select override (ios-select class) ── */
:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #8E8E93 !important;
    text-align: right;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

/* ── PrimeVue DatePicker override (ios-datepicker class) ── */
:deep(.ios-datepicker .p-datepicker) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

:deep(.ios-datepicker .p-inputtext) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: right;
    color: #8E8E93;
    font-size: 13px;
    padding: 4px 40px 4px 8px;
}

/* ── InputNumber override (ios-inputnumber class) ── */
:deep(.ios-inputnumber .p-inputnumber-input) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: right;
    color: #8E8E93;
    font-size: 13px;
    padding: 4px 8px;
}

/* ── Full-width text inputs ── */
:deep(.ios-full-input.p-inputtext),
:deep(.ios-full-input .p-inputtext) {
    font-size: 13px;
    border-radius: 6px;
    width: 100%;
}

:deep(.ios-full-input.p-textarea),
:deep(.ios-full-input .p-textarea) {
    font-size: 13px;
    border-radius: 6px;
    resize: none;
    width: 100%;
}

:deep(.ios-full-input .p-multiselect),
:deep(.ios-full-input.p-multiselect) {
    font-size: 13px;
    border-radius: 8px;
    width: 100%;
}
</style>
