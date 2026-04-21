<template>
    <div class="p-4 short:p-3 relative">

        <!-- Header -->
        <div class="flex justify-between items-start mb-4 short:mb-3 gap-4">
            <!-- Left: Title -->
            <div class="flex-shrink-0">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-300">Obligations and Transactions</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ disbursements.length }} record{{
                    disbursements.length !== 1 ? 's' : '' }}</p>
            </div>

            <!-- Center: Totals -->
            <div class="flex items-center gap-3 flex-wrap flex-1 justify-center">
                <!-- Total -->
                <div
                    class="flex flex-col items-center bg-[#f0f4ff] border-[0.5px] border-[#c7d2fe] rounded-xl py-[7px] px-4 min-w-[110px]">
                    <span class="text-[10px] font-semibold text-[#6366f1] uppercase tracking-[0.4px]">Total</span>
                    <span class="text-[15px] font-bold text-[#3730a3] mt-px tabular-nums whitespace-nowrap">{{
                        formatCurrency(totalAmount) }}</span>
                </div>
                <!-- On Process -->
                <div v-if="pendingCount > 0"
                    class="flex flex-col items-center bg-[#fffbeb] border-[0.5px] border-[#fcd34d] rounded-xl py-[7px] px-4 min-w-[110px]"
                    v-tooltip.top="`${pendingCount} record(s) not yet settled`">
                    <span class="text-[10px] font-semibold text-[#d97706] uppercase tracking-[0.4px]">On Process</span>
                    <span class="text-[15px] font-bold text-[#92400e] mt-px tabular-nums whitespace-nowrap">{{
                        formatCurrency(pendingAmount) }}</span>
                </div>
                <!-- Settled -->
                <div v-if="settledAmount > 0"
                    class="flex flex-col items-center bg-[#f0fdf4] border-[0.5px] border-[#86efac] rounded-xl py-[7px] px-4 min-w-[110px]">
                    <span class="text-[10px] font-semibold text-[#16a34a] uppercase tracking-[0.4px]">Settled</span>
                    <span class="text-[15px] font-bold text-[#14532d] mt-px tabular-nums whitespace-nowrap">{{
                        formatCurrency(settledAmount) }}</span>
                </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex-shrink-0 gap-2">
                <AppButton v-if="hasPermission('applicants.edit')" icon="plus" label="Add Disbursement"
                    @click="showAddModal = true" severity="success" size="small" raised disabled />
                <p class="text-xs text-gray-400 dark:text-gray-300 p-2">Adding disbursement is temporarily disabled. Use
                    Fund
                    Transaction instead.</p>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center py-12">
            <AppIcon name="spinner" :size="30" class="text-gray-300" />
        </div>

        <!-- Empty state -->
        <div v-else-if="disbursements.length === 0" class="text-center py-12">
            <AppIcon name="inbox" :size="36" class="text-gray-300 mb-4 block" />
            <p class="text-gray-500 dark:text-gray-400">No disbursements found</p>
        </div>

        <!-- Table -->
        <div v-else class="rounded-2xl overflow-hidden border-[0.5px] border-[#e5e5ea] bg-white overflow-x-auto">
            <table class="w-full border-collapse text-sm table-fixed min-w-[960px]">
                <thead>
                    <tr class="bg-[#f9f9fb] border-b-[0.5px] border-[#e5e5ea]">
                        <th class="w-9 py-[7px] px-3"></th>
                        <th class="tbl-col-header py-[7px] px-3 text-left w-[130px]">Date / OBR</th>
                        <th class="tbl-col-header py-[7px] px-3 text-left w-[160px]">Year / Term</th>
                        <th class="tbl-col-header py-[7px] px-3 text-left w-[220px]">Payee</th>
                        <th class="tbl-col-header py-[7px] px-3 text-left w-[185px]">Encoder</th>
                        <th class="tbl-col-header py-[7px] pl-6 pr-3 text-left w-[110px]">Status</th>
                        <th class="tbl-col-header py-[7px] px-3 text-right w-[120px]">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(group, gi) in groupedDisbursements" :key="group.year">
                        <!-- Academic Year Group Header -->
                        <tr class="bg-[#f2f2f7] border-t-[0.5px] border-b-[0.5px] border-[#e5e5ea]">
                            <td colspan="7" class="py-[5px] px-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-[#8e8e93] uppercase tracking-[0.4px]">AY {{
                                        group.year }}</span>
                                    <span class="text-xs text-gray-600">{{ group.items.length }} record{{
                                        group.items.length !== 1 ? 's' : '' }} · <span
                                            class="text-[12px] text-gray-700 font-bold">{{
                                                formatCurrency(group.total)
                                            }}</span></span>
                                </div>
                            </td>
                        </tr>

                        <template v-for="(item, rowIdx) in group.items" :key="item.disbursement_id">
                            <!-- Main Row -->
                            <tr @click="toggleRow(item.disbursement_id)"
                                class="cursor-pointer border-b-[0.5px] border-[#e5e5ea] transition-[background] duration-150"
                                :style="{ background: expandedRows[item.disbursement_id] ? '#eef4ff' : '#ffffff' }">

                                <!-- Toggle Chevron -->
                                <td class="py-[11px] px-3 text-center w-9">
                                    <AppIcon name="chevron-right" :size="18"
                                        class="text-gray-600 transition-transform duration-200"
                                        :style="{ transform: expandedRows[item.disbursement_id] ? 'rotate(90deg)' : 'rotate(0deg)' }" />
                                </td>

                                <!-- Date / OBR No. -->
                                <td class="py-[11px] px-3 w-[130px]">
                                    <div class="text-[13px] text-[#1c1c1e] font-medium">
                                        {{ item.date_obligated ? formatDate(item.date_obligated) : '—' }}
                                    </div>
                                    <div v-if="item.obr_no" class="mt-[3px]">
                                        <span
                                            class="text-[10px] font-semibold text-[#1d4ed8] bg-[#dbeafe] rounded-[6px] px-[6px] py-[2px] tracking-[0.2px]">{{
                                                item.obr_no }}</span>
                                    </div>
                                    <span v-if="item.is_legacy"
                                        class="text-[10px] bg-[#e5e5ea] text-[#636366] rounded px-[5px] py-px inline-block mt-0.5">Legacy</span>
                                </td>

                                <!-- Year Level / Semester -->
                                <td class="py-[11px] px-3 w-[160px]">
                                    <div class="text-[13px] text-[#1c1c1e]">{{ item.year_level || '—' }}</div>
                                    <div class="text-[11px] text-[#8e8e93] mt-px">{{ item.semester || '—' }}</div>
                                </td>

                                <!-- Payee / Type -->
                                <td class="py-[11px] px-3 w-[220px]">
                                    <div class="text-[13px] text-[#1c1c1e] truncate">{{ item.payee || '—' }}</div>
                                    <div v-if="item.disbursement_type" class="text-[11px] text-[#8e8e93] mt-px">{{
                                        formatDisbursementType(item.disbursement_type) }}</div>
                                </td>

                                <!-- Encoder -->
                                <td class="py-[11px] px-3 w-[185px]">
                                    <div class="text-[10px] text-[#3a3a3c] truncate">{{ item.encoder || '—' }}</div>
                                    <div v-if="item.encoder_designation"
                                        class="text-[10px] text-[#8e8e93] truncate mt-px">
                                        {{ item.encoder_designation }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-[11px] pl-6 pr-3 w-[110px]">
                                    <span v-if="item.obr_status"
                                        class="text-[11px] font-semibold py-[3px] px-[9px] rounded-[20px] inline-block whitespace-nowrap"
                                        :style="getObrStatusIosStyle(item.obr_status)">
                                        {{ item.obr_status }}
                                    </span>
                                    <span v-else class="text-[#8e8e93] text-xs">—</span>
                                </td>

                                <!-- Amount -->
                                <td class="py-[11px] px-3 text-right w-[120px]">
                                    <span class="text-sm font-semibold text-[#1c1c1e] tabular-nums whitespace-nowrap">{{
                                        item.amount ? formatCurrency(item.amount) : '—' }}</span>
                                </td>
                            </tr>

                            <!-- Expanded Detail Panel -->
                            <tr v-if="expandedRows[item.disbursement_id]">
                                <td colspan="7" class="p-0">
                                    <div
                                        class="bg-[#f5f7ff] border-b-[0.5px] border-[#d0d5e8] pt-4 pr-6 pb-4 pl-[52px]">
                                        <div class="grid grid-cols-2 gap-5 mb-[14px]">

                                            <!-- Left: Academic + Cheque -->
                                            <div>
                                                <!-- Academic subsection -->
                                                <div class="mb-[14px]">
                                                    <div class="tbl-section-header mb-[7px]">
                                                        <AppIcon name="book" :size="12" class="mr-1 text-[#007AFF]" />
                                                        Academic
                                                    </div>
                                                    <div class="flex flex-wrap gap-4">
                                                        <div v-if="item.academic_year">
                                                            <div class="tbl-detail-label">Academic Year</div>
                                                            <div class="tbl-detail-value">{{ item.academic_year }}</div>
                                                        </div>
                                                        <div v-if="item.year_level">
                                                            <div class="tbl-detail-label">Year Level</div>
                                                            <div class="tbl-detail-value">{{ item.year_level }}</div>
                                                        </div>
                                                        <div v-if="item.semester">
                                                            <div class="tbl-detail-label">Semester</div>
                                                            <div class="tbl-detail-value">{{ item.semester }}</div>
                                                        </div>
                                                        <div
                                                            v-if="item.profile?.scholarship_grant?.[0]?.course?.shortname">
                                                            <div class="tbl-detail-label">Course</div>
                                                            <div class="tbl-detail-value">{{
                                                                item.profile.scholarship_grant[0].course.shortname }}
                                                            </div>
                                                        </div>
                                                        <div
                                                            v-if="item.profile?.scholarship_grant?.[0]?.school?.shortname">
                                                            <div class="tbl-detail-label">School</div>
                                                            <div class="tbl-detail-value">{{
                                                                item.profile.scholarship_grant[0].school.shortname }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Cheque subsection -->
                                                <div>
                                                    <div class="tbl-section-header mb-[7px]">
                                                        <AppIcon name="check-circle" :size="12"
                                                            class="mr-1 text-[#34C759]" />Cheque
                                                    </div>
                                                    <div class="flex gap-5">
                                                        <div>
                                                            <div class="tbl-detail-label">Cheque No.</div>
                                                            <div class="tbl-detail-value">{{
                                                                item.cheques?.[0]?.cheque_no || '—' }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="tbl-detail-label">Date Released</div>
                                                            <div class="tbl-detail-value">{{
                                                                item.cheques?.[0]?.date_released ?
                                                                    formatDate(item.cheques[0].date_released) : '—' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right: Remarks + Attachments -->
                                            <div>
                                                <!-- Remarks subsection -->
                                                <div v-if="item.remarks" class="mb-[14px]">
                                                    <div class="tbl-section-header mb-[7px]">
                                                        <AppIcon name="comment" :size="12"
                                                            class="mr-1 text-[#FF9500]" />Remarks
                                                    </div>
                                                    <div class="text-xs text-[#3a3a3c] border-l-2 border-[#d0d5e8] pl-2 leading-[1.5]"
                                                        v-safe-html="item.remarks"></div>
                                                </div>

                                                <!-- Attachments subsection -->
                                                <div>
                                                    <div class="tbl-section-header mb-[7px]">
                                                        <AppIcon name="upload" :size="12" class="mr-1 text-[#007AFF]" />
                                                        Attachments
                                                        ({{ item.attachments?.length || 0 }})
                                                    </div>
                                                    <div v-if="item.attachments?.length > 0"
                                                        class="flex flex-wrap gap-1.5">
                                                        <div v-for="att in item.attachments" :key="att.attachment_id"
                                                            class="flex items-center gap-[5px] bg-white border-[0.5px] border-[#d0d5e8] rounded-lg py-1 px-[9px] text-[11px]">
                                                            <AppIcon :name="getFileIcon(att.file_type)" :size="12"
                                                                class="text-[#007AFF]" />
                                                            <span class="text-[#1c1c1e] max-w-[100px] truncate">{{
                                                                att.attachment_type }}</span>
                                                            <button @click.stop="viewAttachment(att)"
                                                                class="bg-transparent border-0 cursor-pointer p-0 leading-none">
                                                                <AppIcon name="eye" :size="11" class="text-[#007AFF]" />
                                                            </button>
                                                            <button @click.stop="downloadAttachment(att)"
                                                                class="bg-transparent border-0 cursor-pointer p-0 leading-none">
                                                                <AppIcon name="download" :size="11"
                                                                    class="text-[#007AFF]" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-xs text-[#8e8e93]">No attachments</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div
                                            class="flex flex-wrap gap-2 pt-3 border-t-[0.5px] border-[#d0d5e8] justify-end">
                                            <button v-if="hasPermission('applicants.edit')"
                                                @click.stop="showQrCode(item)"
                                                class="tbl-action-btn text-[#007AFF] bg-[#f0f0f5]">
                                                <AppIcon name="qrcode" :size="12" /> QR Upload
                                            </button>
                                            <button v-if="hasPermission('applicants.edit')"
                                                @click.stop="manageAttachments(item)"
                                                class="tbl-action-btn text-[#007AFF] bg-[#f0f0f5]">
                                                <AppIcon name="upload" :size="12" /> Attachments
                                            </button>
                                            <button v-if="hasPermission('applicants.edit')"
                                                @click.stop="manageCheque(item)"
                                                class="tbl-action-btn text-[#007AFF] bg-[#f0f0f5]">
                                                <AppIcon name="file" :size="12" /> Cheque
                                            </button>
                                            <button v-if="hasPermission('applicants.edit')"
                                                @click.stop="editDisbursement(item)"
                                                class="tbl-action-btn text-[#FF9500] bg-[#fff8ec]">
                                                <AppIcon name="pencil" :size="12" /> Edit
                                            </button>
                                            <button v-if="hasPermission('applicants.delete')"
                                                @click.stop="confirmDelete(item)"
                                                class="tbl-action-btn text-[#FF3B30] bg-[#fff0f0]">
                                                <AppIcon name="trash" :size="12" /> Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>


        <!-- Add/Edit Disbursement Modal -->
        <Dialog v-model:visible="showAddModal" modal :header="editMode ? 'Edit Disbursement' : 'Add Disbursement'"
            :style="{ width: '50vw' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Type {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <Select v-model="form.disbursement_type" :options="disbursementTypes" optionLabel="label"
                            optionValue="value" placeholder="Select type" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Payee {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputText v-model="form.payee" placeholder="Enter payee name" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">OBR No.</label>
                        <InputText v-model="form.obr_no" placeholder="Enter OBR number" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">OBR
                            Status</label>
                        <Select v-model="form.obr_status" :options="obrStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="Select OBR status" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                            Obligated</label>
                        <DatePicker v-model="form.date_obligated" dateFormat="mm/dd/yy"
                            placeholder="MM/DD/YYYY, YYYY-MM-DD, or Oct. 22, 2025" class="w-full" :manualInput="true"
                            @input="handleDateInput($event, 'date_obligated')" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Year
                            Level</label>
                        <YearLevelSelect v-model="form.year_level" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Semester</label>
                        <TermSelect v-model="form.semester" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Academic
                            Year</label>
                        <AcademicYearSelect v-model="form.academic_year" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Amount {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputNumber v-model="form.amount" mode="currency" currency="PHP" locale="en-PH"
                            placeholder="Enter amount" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Remarks</label>
                    <Editor v-model="form.remarks" editorStyle="height: 120px">
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
                <AppButton label="Cancel" severity="secondary" @click="closeModal" outlined size="small" />
                <AppButton :label="editMode ? 'Update' : 'Create'" @click="saveDisbursement" :loading="saving"
                    size="small" />
            </template>
        </Dialog>

        <!-- Manage Cheque Modal -->
        <Dialog v-model:visible="showChequeModal" header="Manage Cheque" :style="{ width: '40vw', zIndex: 1100 }"
            appendTo="body">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cheque No. *</label>
                    <InputText v-model="chequeForm.cheque_no" placeholder="Enter cheque number" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Released</label>
                    <DatePicker v-model="chequeForm.date_released" dateFormat="mm/dd/yy"
                        placeholder="MM/DD/YYYY, YYYY-MM-DD, or Oct. 22, 2025" class="w-full" :manualInput="true"
                        @input="handleDateInput($event, 'date_released', true)" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Remarks</label>
                    <Editor v-model="chequeForm.remarks" editorStyle="height: 120px">
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
                <AppButton label="Cancel" severity="secondary" @click="showChequeModal = false" outlined size="small" />
                <AppButton :label="chequeEditMode ? 'Update' : 'Add Cheque'" @click="saveCheque" :loading="saving"
                    size="small" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" modal header="Confirm Delete" :style="{ width: '30vw' }">
            <p>Are you sure you want to delete this disbursement?</p>
            <template #footer>
                <AppButton label="Cancel" severity="secondary" @click="showDeleteDialog = false" outlined
                    size="small" />
                <AppButton label="Delete" severity="danger" @click="deleteDisbursement" :loading="deleting"
                    size="small" />
            </template>
        </Dialog>

        <!-- Manage Attachments Modal -->
        <Dialog v-model:visible="showAttachmentsModal" modal header="Manage Attachments" :style="{ width: '50vw' }">
            <div class="space-y-4">
                <!-- Existing Attachments -->
                <div
                    v-if="selectedDisbursement && selectedDisbursement.attachments && selectedDisbursement.attachments.length > 0">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Existing Attachments</h4>
                    <div class="space-y-2">
                        <div v-for="attachment in selectedDisbursement.attachments" :key="attachment.attachment_id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <AppIcon :name="getFileIcon(attachment.file_type)" :size="24" class="text-blue-600" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                        attachment.file_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ attachment.attachment_type }}
                                        • {{
                                            formatFileSize(attachment.file_size) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <AppButton icon="eye" size="small" outlined label="View"
                                    @click="viewAttachment(attachment)" />
                                <AppButton icon="download" size="small" outlined
                                    @click="downloadAttachment(attachment)" />
                                <AppButton v-if="hasPermission('applicants.edit')" icon="trash" size="small"
                                    severity="danger" outlined @click="deleteAttachment(attachment)" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload New Attachment -->
                <div v-if="hasPermission('applicants.edit')">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Upload New Attachment</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Attachment
                                Type
                                *</label>
                            <Select v-model="attachmentForm.attachment_type" :options="attachmentTypes"
                                optionLabel="label" optionValue="value" placeholder="Select type" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File (PDF or
                                Image)
                                *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Accepted formats: PDF, JPG, PNG
                                (Max 25MB)
                            </p>
                        </div>
                        <div v-if="attachmentForm.file">
                            <p class="text-sm text-gray-700 dark:text-gray-300">Selected: <span class="font-medium">{{
                                attachmentForm.file.name
                                    }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <AppButton label="Cancel" severity="secondary" @click="closeAttachmentsModal" outlined size="small" />
                <AppButton v-if="hasPermission('applicants.edit')" label="Upload" @click="uploadAttachment"
                    :loading="uploading" :disabled="!attachmentForm.file || !attachmentForm.attachment_type"
                    size="small" />
            </template>
        </Dialog>

        <!-- View Attachment Modal -->
        <ViewAttachmentModal v-model:visible="showViewerModal" :attachment="viewerAttachment" />

        <!-- QR Code Modal -->
        <Dialog v-model:visible="showQrModal" modal header="Mobile Upload QR Code"
            :style="{ width: '30vw', minWidth: '400px' }">
            <div v-if="qrCodeData" class="text-center space-y-4">
                <!-- QR Code -->
                <div
                    class="bg-white dark:bg-gray-800 p-4 short:p-3 rounded-lg border-2 border-gray-200 dark:border-gray-600 inline-block">
                    <div v-safe-html="qrCodeData.qrCode"></div>
                </div>

                <!-- Instructions -->
                <div class="text-left space-y-3">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            <AppIcon name="info-circle" :size="14" class="mr-2 inline" />How to use:
                        </p>
                        <ol class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-decimal list-inside">
                            <li>Scan this QR code with your mobile device</li>
                            <li>Take a photo or select from gallery</li>
                            <li>Upload will be automatically optimized</li>
                        </ol>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded">
                        <p class="text-xs text-yellow-800 dark:text-yellow-300">
                            <AppIcon name="exclamation-triangle" :size="12" class="mr-2 inline" />
                            <strong>Expires in:</strong>
                            <span :class="{
                                'text-yellow-600': qrCountdown.includes('min') && !qrCountdown.includes('0 min'),
                                'text-orange-600': qrCountdown.includes('0 min') && parseInt(qrCountdown) >= 5,
                                'text-red-600 font-bold': qrCountdown.includes('0 min') && parseInt(qrCountdown) < 5 || qrCountdown === 'EXPIRED'
                            }">
                                {{ qrCountdown || 'Loading...' }}
                            </span>
                        </p>
                    </div>

                    <!-- Mobile URL (for copying) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Or copy this
                            link:</label>
                        <div class="flex gap-2">
                            <InputText type="text" :value="qrCodeData.url" readonly class="flex-1 text-xs" />
                            <AppButton icon="copy" size="small" @click="copyToClipboard(qrCodeData.url)"
                                v-tooltip.top="'Copy link'" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <AppButton label="Close" severity="secondary" @click="showQrModal = false" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';
import { useSystemOptions } from '@/composables/useSystemOptions';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

const props = defineProps({
    profileId: [Number, String],
});

// Permission composable
const { hasPermission } = usePermission();

// State
const loading = ref(false);
const saving = ref(false);

// Expandable rows state
const expandedRows = ref({});
const toggleRow = (id) => {
    if (expandedRows.value[id]) {
        delete expandedRows.value[id];
    } else {
        expandedRows.value[id] = true;
    }
};
const deleting = ref(false);
const uploading = ref(false);
const disbursements = ref([]);
const showAddModal = ref(false);
const showChequeModal = ref(false);
const showDeleteDialog = ref(false);
const showAttachmentsModal = ref(false);
const showViewerModal = ref(false);
const showQrModal = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const editMode = ref(false);
const chequeEditMode = ref(false);
const selectedDisbursement = ref(null);
const viewerAttachment = ref(null);
const fileInput = ref(null);

// Form data
const form = ref({
    disbursement_type: null,
    payee: '',
    obr_no: '',
    obr_status: null,
    date_obligated: null,
    year_level: '',
    semester: null,
    academic_year: '',
    amount: '',
    remarks: '',
});

const chequeForm = ref({
    cheque_no: '',
    date_released: null,
    remarks: '',
});

const attachmentForm = ref({
    attachment_type: null,
    file: null,
});

// Options
const disbursementTypes = useSystemOptions('disbursement_type');
const attachmentTypes = useSystemOptions('attachment_type');
const obrStatusOptions = useSystemOptions('obr_status');

// Methods
const loadDisbursements = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('disbursements.index', props.profileId));
        disbursements.value = response.data;
        // Debug: Log first disbursement to check data structure
        if (response.data.length > 0) {
            console.log('First disbursement:', response.data[0]);
            console.log('Profile:', response.data[0].profile);
            console.log('Scholarship Grant:', response.data[0].profile?.scholarship_grant);
        }
    } catch (error) {
        console.error('Error loading disbursements:', error);
        toast.error('Failed to load disbursements');
    } finally {
        loading.value = false;
    }
};

const saveDisbursement = async () => {
    // Check if OBR Status exempts Type, Payee, and Amount requirement
    const exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
    const isExempt = exemptStatuses.includes(form.value.obr_status);

    // Validate required fields based on OBR Status
    if (!isExempt) {
        if (!form.value.disbursement_type || !form.value.payee || !form.value.amount) {
            toast.error('Please fill in Type, Payee, and Amount fields');
            return;
        }
    }

    saving.value = true;
    try {
        const data = {
            ...form.value,
            profile_id: props.profileId,
            // Format date to YYYY-MM-DD to avoid timezone issues
            date_obligated: formatDateForBackend(form.value.date_obligated),
            // Extract string values from select components if they return objects
            year_level: typeof form.value.year_level === 'object' ? form.value.year_level?.value : form.value.year_level,
            semester: typeof form.value.semester === 'object' ? form.value.semester?.value : form.value.semester,
            academic_year: typeof form.value.academic_year === 'object' ? form.value.academic_year?.value : form.value.academic_year,
        };

        if (editMode.value) {
            await axios.put(route('disbursements.update', selectedDisbursement.value.disbursement_id), data);
            toast.success('Disbursement updated successfully');
        } else {
            await axios.post(route('disbursements.store'), data);
            toast.success('Disbursement created successfully');
        }

        closeModal();
        loadDisbursements();
    } catch (error) {
        console.error('Error saving disbursement:', error);
        toast.error('Failed to save disbursement');
    } finally {
        saving.value = false;
    }
};

const editDisbursement = (disbursement) => {
    editMode.value = true;
    selectedDisbursement.value = disbursement;
    form.value = {
        disbursement_type: disbursement.disbursement_type,
        payee: disbursement.payee,
        obr_no: disbursement.obr_no || '',
        obr_status: disbursement.obr_status || null,
        date_obligated: disbursement.date_obligated ? new Date(disbursement.date_obligated) : null,
        year_level: disbursement.year_level || '',
        semester: disbursement.semester || null,
        academic_year: disbursement.academic_year || '',
        amount: parseFloat(disbursement.amount) || 0,
        remarks: disbursement.remarks || '',
        // pass profile_id so backend can sync scholar_ids amount
        profile_id: disbursement.fund_transaction_id ? null : undefined,
    };
    showAddModal.value = true;
};

const manageCheque = (disbursement) => {
    // Don't close View Attachment modal - allow both to be open
    // Only close other modals that might conflict
    showAttachmentsModal.value = false;
    showQrModal.value = false;

    selectedDisbursement.value = disbursement;
    if (disbursement.cheques && disbursement.cheques.length > 0) {
        const cheque = disbursement.cheques[0];
        chequeEditMode.value = true;
        chequeForm.value = {
            cheque_no: cheque.cheque_no,
            date_released: cheque.date_released ? new Date(cheque.date_released) : null,
            remarks: cheque.remarks || '',
        };
    } else {
        chequeEditMode.value = false;
        chequeForm.value = {
            cheque_no: '',
            date_released: null,
            remarks: '',
        };
    }
    showChequeModal.value = true;
};

const saveCheque = async () => {
    if (!chequeForm.value.cheque_no) {
        toast.error('Please fill in cheque number');
        return;
    }

    saving.value = true;
    try {
        // Format date to avoid timezone issues
        const data = {
            ...chequeForm.value,
            date_released: formatDateForBackend(chequeForm.value.date_released),
        };

        if (chequeEditMode.value) {
            const chequeId = selectedDisbursement.value.cheques[0].cheque_id;
            await axios.put(route('cheques.update', chequeId), data);
            toast.success('Cheque updated successfully');
        } else {
            await axios.post(route('disbursements.cheques.store', selectedDisbursement.value.disbursement_id), data);
            toast.success('Cheque added successfully');
        }

        showChequeModal.value = false;
        loadDisbursements();
    } catch (error) {
        console.error('Error saving cheque:', error);
        toast.error('Failed to save cheque');
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (disbursement) => {
    selectedDisbursement.value = disbursement;
    showDeleteDialog.value = true;
};

const deleteDisbursement = async () => {
    deleting.value = true;
    try {
        await axios.delete(route('disbursements.destroy', selectedDisbursement.value.disbursement_id));
        toast.success('Disbursement deleted successfully');
        showDeleteDialog.value = false;
        loadDisbursements();
    } catch (error) {
        console.error('Error deleting disbursement:', error);
        toast.error('Failed to delete disbursement');
    } finally {
        deleting.value = false;
    }
};

const closeModal = () => {
    showAddModal.value = false;
    editMode.value = false;
    selectedDisbursement.value = null;
    form.value = {
        disbursement_type: null,
        payee: '',
        obr_no: '',
        date_obligated: null,
        year_level: '',
        semester: null,
        academic_year: '',
        amount: '',
        remarks: '',
    };
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Helper function to format date for backend (YYYY-MM-DD)
const formatDateForBackend = (date) => {
    if (!date) return null;
    if (!(date instanceof Date)) return date;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Helper function to handle manual date input (supports YYYY-MM-DD, MM/DD/YYYY, and "Oct. 22, 2025" formats)
const handleDateInput = (event, field, isCheque = false) => {
    const input = event.target?.value?.trim();
    if (!input) return;

    let parsedDate = null;

    // Try parsing YYYY-MM-DD format (e.g., 2025-10-28)
    const isoMatch = input.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (isoMatch) {
        const [, year, month, day] = isoMatch;
        parsedDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    }

    // Try parsing MM/DD/YYYY format (e.g., 10/28/2025)
    if (!parsedDate) {
        const usMatch = input.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (usMatch) {
            const [, month, day, year] = usMatch;
            parsedDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
        }
    }

    // Try parsing "Oct. 22, 2025" or "October 22, 2025" format
    if (!parsedDate) {
        const monthNames = {
            'jan': 0, 'january': 0,
            'feb': 1, 'february': 1,
            'mar': 2, 'march': 2,
            'apr': 3, 'april': 3,
            'may': 4,
            'jun': 5, 'june': 5,
            'jul': 6, 'july': 6,
            'aug': 7, 'august': 7,
            'sep': 8, 'september': 8,
            'oct': 9, 'october': 9,
            'nov': 10, 'november': 10,
            'dec': 11, 'december': 11
        };

        // Match patterns like "Oct. 22, 2025", "October 22, 2025", "Oct 22, 2025"
        const textMatch = input.match(/^([a-z]+)\.?\s+(\d{1,2}),?\s+(\d{4})$/i);
        if (textMatch) {
            const [, monthStr, day, year] = textMatch;
            const monthIndex = monthNames[monthStr.toLowerCase()];
            if (monthIndex !== undefined) {
                parsedDate = new Date(parseInt(year), monthIndex, parseInt(day));
            }
        }
    }

    // If we successfully parsed a valid date, update the form
    if (parsedDate && !isNaN(parsedDate.getTime())) {
        setTimeout(() => {
            if (isCheque) {
                chequeForm.value[field] = parsedDate;
            } else {
                form.value[field] = parsedDate;
            }
        }, 0);
    }
};

const formatCurrency = (amount) => {
    if (!amount) return '';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const formatDisbursementType = (type) => {
    const types = {
        'regular': 'Regular',
        'reimbursement': 'Reimbursement',
        'financial_assistance': 'Financial Assistance',
        // fund_transaction obr_type values (uppercase)
        'REGULAR': 'Regular',
        'REIMBURSEMENT': 'Reimbursement',
        'FINANCIAL ASSISTANCE': 'Financial Assistance',
        // fund_transaction disbursement_type values
        'disbursements': 'Disbursement',
        'payroll': 'Payroll',
    };
    return types[type] || type;
};

const getObrStatusIosStyle = (status) => {
    const styles = {
        'CLAIMED': 'background: #d1f5e0; color: #187a3c;',
        'PAID': 'background: #d1f5e0; color: #187a3c;',
        'ON PROCESS': 'background: #fff3cd; color: #8a5700;',
        'LOA': 'background: #ffe9d0; color: #a04000;',
        'IRREGULAR': 'background: #fef9c3; color: #7a5c00;',
        'TRANSFERRED': 'background: #dbeafe; color: #1d4ed8;',
        'DENIED': 'background: #fee2e2; color: #991b1b;',
    };
    return styles[status] || 'background: #e5e5ea; color: #3a3a3c;';
};

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'file-pdf';
    if (fileType?.includes('image')) return 'image';
    return 'file';
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const manageAttachments = (disbursement) => {
    selectedDisbursement.value = disbursement;
    attachmentForm.value = {
        attachment_type: null,
        file: null,
    };
    showAttachmentsModal.value = true;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (25MB max)
        if (file.size > 25 * 1024 * 1024) {
            toast.error('File size must be less than 25MB');
            event.target.value = '';
            return;
        }
        attachmentForm.value.file = file;
    }
};

const uploadAttachment = async () => {
    if (!attachmentForm.value.file || !attachmentForm.value.attachment_type) {
        toast.error('Please select both attachment type and file');
        return;
    }

    uploading.value = true;
    try {
        const formData = new FormData();
        formData.append('attachment_type', attachmentForm.value.attachment_type);
        formData.append('file', attachmentForm.value.file);

        const response = await axios.post(
            route('disbursements.attachments.upload', selectedDisbursement.value.disbursement_id),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        );

        toast.success('Attachment uploaded successfully');

        // Update the selected disbursement's attachments immediately
        if (response.data.attachments) {
            selectedDisbursement.value.attachments = response.data.attachments;
        }

        // Reset form
        attachmentForm.value = {
            attachment_type: null,
            file: null,
        };
        if (fileInput.value) {
            fileInput.value.value = '';
        }

        loadDisbursements();
    } catch (error) {
        console.error('Error uploading attachment:', error);
        toast.error('Failed to upload attachment');
    } finally {
        uploading.value = false;
    }
};

const viewAttachment = (attachment) => {
    viewerAttachment.value = {
        ...attachment,
        file_name: attachment.file_name || attachment.attachment_type,
        view_route: 'disbursements.attachments.view',
        download_route: 'disbursements.attachments.download',
    };
    showViewerModal.value = true;
};

const downloadAttachment = async (attachment) => {
    try {
        window.open(route('disbursements.attachments.download', attachment.attachment_id), '_blank');
    } catch (error) {
        console.error('Error downloading attachment:', error);
        toast.error('Failed to download attachment');
    }
};

const deleteAttachment = async (attachment) => {
    if (!confirm('Are you sure you want to delete this attachment?')) {
        return;
    }

    try {
        const response = await axios.delete(route('disbursements.attachments.delete', attachment.attachment_id));
        toast.success('Attachment deleted successfully');

        // Update the selected disbursement's attachments immediately
        if (selectedDisbursement.value && response.data.attachments) {
            selectedDisbursement.value.attachments = response.data.attachments;
        }

        loadDisbursements();
    } catch (error) {
        console.error('Error deleting attachment:', error);
        toast.error('Failed to delete attachment');
    }
};

const closeAttachmentsModal = () => {
    showAttachmentsModal.value = false;
    selectedDisbursement.value = null;
    attachmentForm.value = {
        attachment_type: null,
        file: null,
    };
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// QR Code for mobile upload
const showQrCode = async (disbursement) => {
    try {
        const response = await axios.post(route('disbursements.generate-qr', disbursement.disbursement_id));
        qrCodeData.value = {
            qrCode: response.data.qr_code,
            url: response.data.url,
            expiresAt: response.data.expires_at,
            disbursement: disbursement
        };
        showQrModal.value = true;
        startCountdown();
    } catch (error) {
        toast.error('Failed to generate QR code');
        console.error(error);
    }
};

const startCountdown = () => {
    // Clear any existing interval
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }

    const updateCountdown = () => {
        if (!qrCodeData.value) return;

        const now = new Date();
        const expiresAt = new Date(qrCodeData.value.expiresAt);
        const diff = expiresAt - now;

        if (diff <= 0) {
            qrCountdown.value = 'EXPIRED';
            clearInterval(qrCountdownInterval.value);
            return;
        }

        const totalMinutes = Math.floor(diff / 1000 / 60);
        const seconds = Math.floor((diff / 1000) % 60);
        qrCountdown.value = `${totalMinutes} min ${seconds} sec`;
    };

    updateCountdown();
    qrCountdownInterval.value = setInterval(updateCountdown, 1000);
};

// Watch for modal close to clear interval
watch(showQrModal, (newValue) => {
    if (!newValue && qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
        qrCountdownInterval.value = null;
    }
});

// Cleanup on component unmount
onUnmounted(() => {
    if (qrCountdownInterval.value) {
        clearInterval(qrCountdownInterval.value);
    }
});

const copyToClipboard = async (text) => {
    try {
        // Try modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            toast.success('Link copied to clipboard!');
        } else {
            // Fallback for non-HTTPS contexts (like IP addresses)
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const successful = document.execCommand('copy');
            textArea.remove();
            if (successful) {
                toast.success('Link copied to clipboard!');
            } else {
                throw new Error('Copy failed');
            }
        }
    } catch (error) {
        // If all else fails, show the URL in a prompt
        toast.error('Failed to copy automatically. Please copy manually:');
        prompt('Copy this URL:', text);
    }
};

// Image zoom functions removed — handled internally by ViewAttachmentModal

// Computed properties for summary
const totalAmount = computed(() => {
    return disbursements.value.reduce((sum, item) => {
        return sum + (parseFloat(item.amount) || 0);
    }, 0);
});

const settledStatuses = ['CLAIMED', 'PAID'];
const pendingAmount = computed(() => {
    return disbursements.value
        .filter(item => !settledStatuses.includes(item.obr_status))
        .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
});
const pendingCount = computed(() => {
    return disbursements.value.filter(item => !settledStatuses.includes(item.obr_status)).length;
});
const settledAmount = computed(() => {
    return disbursements.value
        .filter(item => settledStatuses.includes(item.obr_status))
        .reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
});

const groupedDisbursements = computed(() => {
    const groups = {};
    disbursements.value.forEach(item => {
        const year = item.academic_year || 'N/A';
        if (!groups[year]) {
            groups[year] = { year, items: [], total: 0 };
        }
        groups[year].items.push(item);
        groups[year].total += parseFloat(item.amount) || 0;
    });
    return Object.values(groups).sort((a, b) => b.year.localeCompare(a.year));
});

// Load data on mount
onMounted(() => {
    loadDisbursements();
});
</script>

<style scoped>
.tbl-col-header {
    font-size: 11px;
    font-weight: 600;
    color: #8e8e93;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.tbl-section-header {
    font-size: 10px;
    font-weight: 600;
    color: #8e8e93;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
}

.tbl-detail-label {
    font-size: 10px;
    color: #8e8e93;
}

.tbl-detail-value {
    font-size: 12px;
    color: #1c1c1e;
    font-weight: 500;
    margin-top: 1px;
}

.tbl-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    border-radius: 10px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}
</style>
