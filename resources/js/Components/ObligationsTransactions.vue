<template>
    <div class="p-6 relative">
        <!-- Backdrop overlay when viewer modal is open -->
        <div v-if="showViewerModal" class="fixed inset-0 bg-black/30 z-[999]" style="margin: -1.5rem;"></div>

        <!-- Header with Add Button and Summary Toggle -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Disbursements & Cheques</h3>
                <p class="text-sm text-gray-500 mt-1">Manage disbursements and cheque processing</p>
            </div>
            <div class="flex gap-2">
                <Button :icon="showSummary ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
                    :label="showSummary ? 'Hide Summary' : 'Show Summary'" outlined severity="secondary" size="small"
                    @click="showSummary = !showSummary" />
                <Button v-if="hasPermission('applicants.edit')" icon="pi pi-plus" label="Add Disbursement"
                    @click="showAddModal = true" severity="success" size="small" raised />
            </div>
        </div>

        <!-- Summary Cards -->
        <div v-if="showSummary" class="mb-6">
            <!-- Total Amount Card -->
            <div
                class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 shadow-sm mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-700 font-medium">Total Disbursements</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">{{ formatCurrency(totalAmount) }}</p>
                        <p class="text-xs text-blue-600 mt-1">{{ disbursements.length }} transactions</p>
                    </div>
                    <i class="pi pi-money-bill text-4xl text-blue-300"></i>
                </div>
            </div>

            <!-- Summary by Year -->
            <div class="mb-4 flex gap-2">
                <Button label="Expand All Years" icon="pi pi-expand" size="small" text rounded
                    @click="Object.keys(semesterSummary).forEach(year => expandedYears[year] = true)" />
                <Button label="Collapse All Years" icon="pi pi-compress" size="small" text rounded
                    @click="Object.keys(semesterSummary).forEach(year => expandedYears[year] = false)" />
            </div>

            <div class="space-y-4">
                <template v-for="(yearData, year) in semesterSummary" :key="year">
                    <!-- Year Card (Collapsible) -->
                    <div
                        class="bg-white border-2 border-gray-300 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        <!-- Year Header -->
                        <button @click="expandedYears[year] = !expandedYears[year]"
                            class="w-full px-4 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300 hover:from-gray-100 hover:to-gray-150 transition-colors text-left">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 flex-1">
                                    <i :class="expandedYears[year] ? 'pi pi-chevron-down' : 'pi pi-chevron-right'"
                                        class="text-gray-600"></i>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Academic Year: {{ year }}</p>
                                        <p class="text-xs text-gray-600 mt-0.5">{{ yearData.count }} transactions • {{
                                            formatCurrency(yearData.total) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(yearData.total) }}</p>
                                </div>
                            </div>
                        </button>

                        <!-- Semesters (Collapsible) -->
                        <template v-if="expandedYears[year]">
                            <div class="p-4 space-y-3 bg-gray-50">
                                <template v-for="(semData, semester) in yearData.semesters"
                                    :key="`${year}-${semester}`">
                                    <!-- Semester Card -->
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <!-- Semester Header -->
                                        <div class="mb-3 pb-3 border-b border-gray-200">
                                            <p class="text-sm font-bold text-gray-800">{{ semester }}</p>
                                            <p class="text-lg font-bold text-gray-900 mt-1">{{
                                                formatCurrency(semData.total) }}</p>
                                            <p class="text-xs text-gray-600 mt-0.5">{{ semData.count }} transactions</p>
                                        </div>

                                        <!-- Status Breakdown -->
                                        <div class="space-y-2">
                                            <template v-for="(statusData, status) in semData.byStatus"
                                                :key="`${year}-${semester}-${status}`">
                                                <div class="flex items-center justify-between p-2 rounded"
                                                    :class="getStatusBgClass(status)">
                                                    <div class="flex flex-col">
                                                        <p class="text-xs font-semibold"
                                                            :class="getStatusTextClass(status)">{{ status }}</p>
                                                        <p class="text-xs" :class="getStatusTextClass(status)">{{
                                                            statusData.count }} item{{ statusData.count !== 1 ? 's' : ''
                                                            }}</p>
                                                    </div>
                                                    <p class="text-sm font-bold" :class="getStatusTextClass(status)">{{
                                                        formatCurrency(statusData.amount) }}</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Disbursements List -->
        <DataView :value="disbursements" :loading="loading">
            <template #empty>
                <div class="text-center py-12">
                    <i class="pi pi-money-bill text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No disbursements found</p>
                    <Button v-if="hasPermission('applicants.edit')" label="Add First Disbursement" class="mt-4"
                        @click="showAddModal = true" severity="success" size="small" raised />
                </div>
            </template>

            <template #list="slotProps">
                <div class="space-y-3">
                    <div v-for="(item, index) in slotProps.items" :key="index"
                        class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">

                        <!-- Compact Header with OBR, Type, Status, and Amount -->
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <!-- Left: OBR Number (Prominent) -->

                                <div class="flex flex-wrap items-center gap-8">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">OBR Date</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow bg-gray-50">{{
                                            item.date_obligated ? formatDate(item.date_obligated) : '-'
                                        }}</p>
                                    </div>
                                    <div v-if="item.obr_no" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">OBR No.</p>
                                        <p class="text-normal font-bold px-2 py-1 rounded-lg shadow bg-gray-50">{{
                                            item.obr_no }}
                                        </p>
                                    </div>

                                    <div v-if="item.disbursement_type" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">Type</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow"
                                            :class="getDisbursementTypeClass(item.disbursement_type)">{{
                                                formatDisbursementType(item.disbursement_type)
                                            }}</p>
                                    </div>
                                    <div v-if="item.obr_status" class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">Status</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow"
                                            :class="getObrStatusClass(item.obr_status)">{{
                                                formatDisbursementType(item.obr_status)
                                            }}</p>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <p class="text-xs font-medium">Payee</p>
                                        <p class="text-sm font-bold px-2 py-1 rounded-lg shadow bg-gray-50">{{
                                            item.payee || '-' }}
                                        </p>
                                    </div>

                                </div>

                                <!-- Right: Amount -->
                                <div v-if="item.amount" class="text-right flex flex-col gap-2">
                                    <p class="text-xs font-medium">Amount</p>
                                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(item.amount) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Content Section -->
                        <div class="p-4">
                            <div class="grid grid-cols-1 lg:grid-cols-[2fr_0.5fr_auto] gap-4">
                                <!-- Column 1: Academic, Cheque, and Remarks (Stacked) -->
                                <div class="space-y-3">
                                    <!-- Academic Information -->
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                                            <i class="pi pi-book text-gray-500 mr-2"></i>
                                            Academic Information
                                        </h4>
                                        <div
                                            class="flex flex-wrap items-center gap-3 text-xs bg-gray-50 rounded px-3 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <span class="text-gray-500 mr-1">Year:</span>
                                                <span class="font-medium text-gray-900">{{ item.year_level || '-'
                                                    }}</span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center">
                                                <span class="text-gray-500 mr-1">Term:</span>
                                                <span class="font-medium text-gray-900">{{ item.semester || '-'
                                                    }}</span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center">
                                                <span class="text-gray-500 mr-1">AY:</span>
                                                <span class="font-medium text-gray-900">{{ item.academic_year || '-'
                                                    }}</span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center">
                                                <span class="text-gray-500 mr-1">Course:</span>
                                                <span class="font-medium text-gray-900">{{
                                                    item.profile?.scholarship_grant?.[0]?.course?.shortname || '-'
                                                }}</span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center">
                                                <span class="text-gray-500 mr-1">School:</span>
                                                <span class="font-medium text-gray-900">{{
                                                    item.profile?.scholarship_grant?.[0]?.school?.shortname || '-'
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cheque Information -->
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                                            <i class="pi pi-check-circle text-green-600 mr-2"></i>
                                            Cheque Information
                                        </h4>
                                        <div
                                            class="flex flex-wrap items-center gap-3 text-xs bg-green-50 rounded px-3 py-2 border border-green-200">
                                            <div class="flex items-center">
                                                <span class="text-gray-600 mr-1">Cheque:</span>
                                                <span class="font-semibold text-gray-900">{{ item.cheques &&
                                                    item.cheques.length > 0 ? item.cheques[0].cheque_no : '-' }}</span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center">
                                                <span class="text-gray-600 mr-1">Released:</span>
                                                <span class="font-medium text-gray-900">{{ item.cheques &&
                                                    item.cheques.length > 0 && item.cheques[0].date_released ?
                                                    formatDate(item.cheques[0].date_released) : '-' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                                            <i class="pi pi-comment text-yellow-600 mr-2"></i>
                                            Remarks
                                        </h4>
                                        <div class="text-xs bg-yellow-50 border-l-2 border-yellow-400 px-3 py-2">
                                            <span class="text-gray-700">{{ item.remarks || '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Column 2: Attachments (Compact) -->
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                                        <i class="pi pi-paperclip text-blue-600 mr-2"></i>
                                        Attachments ({{ item.attachments ? item.attachments.length : 0 }})
                                    </h4>
                                    <div v-if="item.attachments && item.attachments.length > 0"
                                        class="flex flex-col gap-1 text-xs bg-blue-50 rounded px-2 py-2 border border-blue-200">
                                        <div v-for="attachment in item.attachments" :key="attachment.attachment_id"
                                            class="flex items-center gap-1 bg-white px-2 py-1 rounded border border-blue-200">
                                            <i :class="getFileIcon(attachment.file_type)"
                                                class="text-blue-600 text-sm"></i>
                                            <span class="font-medium text-gray-900 flex-1 truncate text-xs">{{
                                                attachment.attachment_type }}</span>
                                            <div class="flex gap-1">
                                                <Button icon="pi pi-eye" size="small" text rounded
                                                    v-tooltip.top="'View'" @click="viewAttachment(attachment)"
                                                    class="!w-6 !h-6" />
                                                <Button icon="pi pi-download" size="small" text rounded
                                                    v-tooltip.top="'Download'" @click="downloadAttachment(attachment)"
                                                    class="!w-6 !h-6" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs bg-blue-50 rounded px-3 py-2 border border-blue-200">
                                        <span class="text-gray-600">-</span>
                                    </div>
                                </div>

                                <!-- Column 3: Actions Section -->
                                <div class="flex lg:flex-col gap-2 lg:border-l lg:border-gray-200 lg:pl-4">
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-qrcode" size="small"
                                        severity="info" outlined rounded v-tooltip.top="'Show QR Code'"
                                        @click="showQrCode(item)" :disabled="showViewerModal" />
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-paperclip" size="small"
                                        severity="secondary" outlined rounded v-tooltip.top="'Manage Attachments'"
                                        @click="manageAttachments(item)" :disabled="showViewerModal" />
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-file" size="small"
                                        severity="info" outlined rounded v-tooltip.top="'Manage Cheque'"
                                        @click="manageCheque(item)"
                                        style="pointer-events: auto; position: relative; z-index: 1200;" />
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-pencil" size="small"
                                        severity="warning" outlined rounded v-tooltip.top="'Edit'"
                                        @click="editDisbursement(item)" :disabled="showViewerModal" />
                                    <Button v-if="hasPermission('applicants.delete')" icon="pi pi-trash" size="small"
                                        severity="danger" outlined rounded v-tooltip.top="'Delete'"
                                        @click="confirmDelete(item)" :disabled="showViewerModal" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </DataView>

        <!-- Add/Edit Disbursement Modal -->
        <Dialog v-model:visible="showAddModal" modal :header="editMode ? 'Edit Disbursement' : 'Add Disbursement'"
            :style="{ width: '50vw' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <Select v-model="form.disbursement_type" :options="disbursementTypes" optionLabel="label"
                            optionValue="value" placeholder="Select type" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payee {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputText v-model="form.payee" placeholder="Enter payee name" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OBR No.</label>
                        <InputText v-model="form.obr_no" placeholder="Enter OBR number" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OBR Status</label>
                        <Select v-model="form.obr_status" :options="obrStatusOptions" optionLabel="label"
                            optionValue="value" placeholder="Select OBR status" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Obligated</label>
                        <DatePicker v-model="form.date_obligated" dateFormat="mm/dd/yy"
                            placeholder="MM/DD/YYYY, YYYY-MM-DD, or Oct. 22, 2025" class="w-full" :manualInput="true"
                            @input="handleDateInput($event, 'date_obligated')" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                        <YearLevelSelect v-model="form.year_level" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <TermSelect v-model="form.semester" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                        <AcademicYearSelect v-model="form.academic_year" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Amount {{ !['LOA', 'IRREGULAR', 'TRANSFERRED'].includes(form.obr_status) ? '*' : '' }}
                        </label>
                        <InputNumber v-model="form.amount" mode="currency" currency="PHP" locale="en-PH"
                            placeholder="Enter amount" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
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
                <Button label="Cancel" severity="secondary" @click="closeModal" outlined size="small" />
                <Button :label="editMode ? 'Update' : 'Create'" @click="saveDisbursement" :loading="saving"
                    size="small" />
            </template>
        </Dialog>

        <!-- Manage Cheque Modal -->
        <Dialog v-model:visible="showChequeModal" header="Manage Cheque" :style="{ width: '40vw', zIndex: 1100 }"
            appendTo="body">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cheque No. *</label>
                    <InputText v-model="chequeForm.cheque_no" placeholder="Enter cheque number" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Released</label>
                    <DatePicker v-model="chequeForm.date_released" dateFormat="mm/dd/yy"
                        placeholder="MM/DD/YYYY, YYYY-MM-DD, or Oct. 22, 2025" class="w-full" :manualInput="true"
                        @input="handleDateInput($event, 'date_released', true)" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
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
                <Button label="Cancel" severity="secondary" @click="showChequeModal = false" outlined size="small" />
                <Button :label="chequeEditMode ? 'Update' : 'Add Cheque'" @click="saveCheque" :loading="saving"
                    size="small" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" modal header="Confirm Delete" :style="{ width: '30vw' }">
            <p>Are you sure you want to delete this disbursement?</p>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteDialog = false" outlined size="small" />
                <Button label="Delete" severity="danger" @click="deleteDisbursement" :loading="deleting" size="small" />
            </template>
        </Dialog>

        <!-- Manage Attachments Modal -->
        <Dialog v-model:visible="showAttachmentsModal" modal header="Manage Attachments" :style="{ width: '50vw' }">
            <div class="space-y-4">
                <!-- Existing Attachments -->
                <div
                    v-if="selectedDisbursement && selectedDisbursement.attachments && selectedDisbursement.attachments.length > 0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Existing Attachments</h4>
                    <div class="space-y-2">
                        <div v-for="attachment in selectedDisbursement.attachments" :key="attachment.attachment_id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                            <div class="flex items-center gap-3">
                                <i :class="getFileIcon(attachment.file_type)" class="text-2xl text-blue-600"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ attachment.file_name }}</p>
                                    <p class="text-xs text-gray-500">{{ attachment.attachment_type }} • {{
                                        formatFileSize(attachment.file_size) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button icon="pi pi-eye" size="small" outlined label="View"
                                    @click="viewAttachment(attachment)" />
                                <Button icon="pi pi-download" size="small" outlined
                                    @click="downloadAttachment(attachment)" />
                                <Button v-if="hasPermission('applicants.edit')" icon="pi pi-trash" size="small"
                                    severity="danger" outlined @click="deleteAttachment(attachment)" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload New Attachment -->
                <div v-if="hasPermission('applicants.edit')">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Upload New Attachment</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attachment Type *</label>
                            <Select v-model="attachmentForm.attachment_type" :options="attachmentTypes"
                                optionLabel="label" optionValue="value" placeholder="Select type" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File (PDF or Image) *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (Max 25MB)</p>
                        </div>
                        <div v-if="attachmentForm.file">
                            <p class="text-sm text-gray-700">Selected: <span class="font-medium">{{
                                attachmentForm.file.name
                                    }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeAttachmentsModal" outlined size="small" />
                <Button v-if="hasPermission('applicants.edit')" label="Upload" @click="uploadAttachment"
                    :loading="uploading" :disabled="!attachmentForm.file || !attachmentForm.attachment_type"
                    size="small" />
            </template>
        </Dialog>

        <!-- View Attachment Modal -->
        <Dialog v-model:visible="showViewerModal" :header="viewerAttachment?.file_name"
            :breakpoints="{ '1199px': '90vw', '575px': '98vw' }" :style="{ width: '85vw', maxWidth: '1200px' }"
            :maximizable="true" :pt="{
                mask: { style: 'pointer-events: none' },
                root: { style: 'pointer-events: auto' }
            }">
            <div class="flex items-center justify-center bg-gray-100 rounded relative overflow-hidden min-h-[70vh]">
                <!-- PDF Viewer -->
                <iframe v-if="viewerAttachment && viewerAttachment.file_type?.includes('pdf')"
                    :src="getAttachmentUrl(viewerAttachment)" class="w-full h-full rounded" frameborder="0">
                </iframe>

                <!-- Image Viewer with Zoom -->
                <div v-else-if="viewerAttachment && viewerAttachment.file_type?.includes('image')"
                    class="w-full h-full flex items-center justify-center relative overflow-auto" @wheel="handleWheel"
                    @mousedown="handleMouseDown" @mousemove="handleMouseMove" @mouseup="handleMouseUp"
                    @mouseleave="handleMouseUp"
                    :style="{ cursor: imageZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }">
                    <img :src="getAttachmentUrl(viewerAttachment)" :alt="viewerAttachment.file_name"
                        class="max-w-full max-h-full object-contain rounded select-none" draggable="false" :style="{
                            transform: `scale(${imageZoom}) translate(${imagePosition.x / imageZoom}px, ${imagePosition.y / imageZoom}px)`,
                            transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                        }" />
                </div>

                <!-- Fallback -->
                <div v-else class="text-center p-4 sm:p-8">
                    <i class="pi pi-file text-4xl sm:text-6xl text-gray-400 mb-4"></i>
                    <p class="text-sm sm:text-base text-gray-600">Unable to preview this file type</p>
                    <Button label="Download Instead" icon="pi pi-download" class="mt-4" size="small"
                        @click="downloadAttachment(viewerAttachment)" />
                </div>
            </div>

            <template #footer>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 w-full pt-4">
                    <!-- Zoom Controls (only show for images) -->
                    <div v-if="viewerAttachment && viewerAttachment.file_type?.includes('image')"
                        class="flex items-center gap-2">
                        <Button icon="pi pi-minus" @click="zoomOut" size="small" severity="secondary" rounded
                            :disabled="imageZoom <= 0.5" />
                        <span class="px-3 py-2 text-sm font-semibold min-w-[60px] text-center">{{ Math.round(imageZoom *
                            100) }}%</span>
                        <Button icon="pi pi-plus" @click="zoomIn" size="small" severity="secondary" rounded
                            :disabled="imageZoom >= 5" />
                        <Button icon="pi pi-refresh" @click="resetZoom" size="small" severity="secondary" rounded
                            v-tooltip.top="'Reset Zoom'" />
                    </div>
                    <div v-else></div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <Button label="Download" icon="pi pi-download" @click="downloadAttachment(viewerAttachment)"
                            size="small" />
                        <Button label="Close" severity="secondary" @click="showViewerModal = false" size="small" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- QR Code Modal -->
        <Dialog v-model:visible="showQrModal" modal header="Mobile Upload QR Code"
            :style="{ width: '30vw', minWidth: '400px' }">
            <div v-if="qrCodeData" class="text-center space-y-4">
                <!-- QR Code -->
                <div class="bg-white p-6 rounded-lg border-2 border-gray-200 inline-block">
                    <div v-html="qrCodeData.qrCode"></div>
                </div>

                <!-- Instructions -->
                <div class="text-left space-y-3">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            <i class="pi pi-info-circle mr-2"></i>How to use:
                        </p>
                        <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                            <li>Scan this QR code with your mobile device</li>
                            <li>Take a photo or select from gallery</li>
                            <li>Upload will be automatically optimized</li>
                        </ol>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <p class="text-xs text-yellow-800">
                            <i class="pi pi-exclamation-triangle mr-2"></i>
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
                        <label class="block text-xs font-medium text-gray-700 mb-1">Or copy this link:</label>
                        <div class="flex gap-2">
                            <InputText type="text" :value="qrCodeData.url" readonly class="flex-1 text-xs" />
                            <Button icon="pi pi-copy" size="small" @click="copyToClipboard(qrCodeData.url)"
                                v-tooltip.top="'Copy link'" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" @click="showQrModal = false" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';
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
const deleting = ref(false);
const uploading = ref(false);
const disbursements = ref([]);
const showAddModal = ref(false);
const showChequeModal = ref(false);
const showDeleteDialog = ref(false);
const showAttachmentsModal = ref(false);
const showViewerModal = ref(false);
const showQrModal = ref(false);
const showSummary = ref(true);
const expandedYears = ref({});
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const editMode = ref(false);
const chequeEditMode = ref(false);
const selectedDisbursement = ref(null);
const viewerAttachment = ref(null);
const fileInput = ref(null);

// Image zoom state
const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

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
const disbursementTypes = [
    { label: 'Regular', value: 'regular' },
    { label: 'Reimbursement', value: 'reimbursement' },
    { label: 'Financial Assistance', value: 'financial_assistance' },
];

const attachmentTypes = [
    { label: 'Voucher', value: 'voucher' },
    { label: 'Cheque', value: 'cheque' },
    { label: 'Receipt', value: 'receipt' },
];

const obrStatusOptions = [
    { label: 'LOA', value: 'LOA' },
    { label: 'IRREGULAR', value: 'IRREGULAR' },
    { label: 'TRANSFERRED', value: 'TRANSFERRED' },
    { label: 'CLAIMED', value: 'CLAIMED' },
    { label: 'PAID', value: 'PAID' },
    { label: 'ON PROCESS', value: 'ON PROCESS' },
    { label: 'DENIED', value: 'DENIED' },
];

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
        date_obligated: disbursement.date_obligated ? new Date(disbursement.date_obligated) : null,
        year_level: disbursement.year_level || '',
        semester: disbursement.semester || null,
        academic_year: disbursement.academic_year || '',
        amount: parseFloat(disbursement.amount) || 0,
        remarks: disbursement.remarks || '',
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
    };
    return types[type] || type;
};

const getDisbursementTypeClass = (type) => {
    const classes = {
        'regular': 'bg-blue-100 text-blue-800',
        'reimbursement': 'bg-orange-100 text-orange-800',
        'financial_assistance': 'bg-purple-100 text-purple-800',
    };
    return classes[type] || '';
};

const getChequeStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'released': 'bg-blue-100 text-blue-800',
        'cleared': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800',
        'bounced': 'bg-red-100 text-red-800',
    };
    return classes[status] || '';
};

const getObrStatusClass = (status) => {
    const classes = {
        'LOA': 'bg-orange-100 text-orange-800',
        'IRREGULAR': 'bg-yellow-100 text-yellow-800',
        'TRANSFERRED': 'bg-blue-100 text-blue-800',
        'CLAIMED': 'bg-purple-100 text-purple-800',
        'PAID': 'bg-green-100 text-green-800',
        'ON PROCESS': 'bg-cyan-100 text-cyan-800',
        'DENIED': 'bg-red-100 text-red-800',
    };
    return classes[status] || '';
};

const getStatusCardClass = (status) => {
    const classes = {
        'LOA': 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200',
        'IRREGULAR': 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200',
        'TRANSFERRED': 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200',
        'CLAIMED': 'bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200',
        'PAID': 'bg-gradient-to-br from-green-50 to-green-100 border-green-200',
        'ON PROCESS': 'bg-gradient-to-br from-cyan-50 to-cyan-100 border-cyan-200',
        'DENIED': 'bg-gradient-to-br from-red-50 to-red-100 border-red-200',
    };
    return classes[status] || 'bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200';
};

const getStatusBgClass = (status) => {
    const classes = {
        'LOA': 'bg-orange-50',
        'IRREGULAR': 'bg-yellow-50',
        'TRANSFERRED': 'bg-blue-50',
        'CLAIMED': 'bg-purple-50',
        'PAID': 'bg-green-50',
        'ON PROCESS': 'bg-cyan-50',
        'DENIED': 'bg-red-50',
    };
    return classes[status] || 'bg-gray-50';
};

const getStatusTextClass = (status) => {
    const classes = {
        'LOA': 'text-orange-700',
        'IRREGULAR': 'text-yellow-700',
        'TRANSFERRED': 'text-blue-700',
        'CLAIMED': 'text-purple-700',
        'PAID': 'text-green-700',
        'ON PROCESS': 'text-cyan-700',
        'DENIED': 'text-red-700',
    };
    return classes[status] || 'text-gray-700';
};

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'pi pi-file-pdf';
    if (fileType?.includes('image')) return 'pi pi-image';
    return 'pi pi-file';
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
    viewerAttachment.value = attachment;
    showViewerModal.value = true;
};

const getAttachmentUrl = (attachment) => {
    if (!attachment) return '';
    // Use the view route for proper access control
    return route('disbursements.attachments.view', attachment.attachment_id);
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

// Image zoom functions
const handleWheel = (event) => {
    event.preventDefault();
    const delta = event.deltaY > 0 ? -0.1 : 0.1;
    imageZoom.value = Math.max(0.5, Math.min(5, imageZoom.value + delta));
};

const handleMouseDown = (event) => {
    if (imageZoom.value > 1) {
        isDragging.value = true;
        dragStart.value = {
            x: event.clientX - imagePosition.value.x,
            y: event.clientY - imagePosition.value.y
        };
    }
};

const handleMouseMove = (event) => {
    if (isDragging.value) {
        imagePosition.value = {
            x: event.clientX - dragStart.value.x,
            y: event.clientY - dragStart.value.y
        };
    }
};

const handleMouseUp = () => {
    isDragging.value = false;
};

const resetZoom = () => {
    imageZoom.value = 1;
    imagePosition.value = { x: 0, y: 0 };
};

const zoomIn = () => {
    imageZoom.value = Math.min(5, imageZoom.value + 0.25);
};

const zoomOut = () => {
    imageZoom.value = Math.max(0.5, imageZoom.value - 0.25);
};

// Reset zoom when viewer modal opens
watch(showViewerModal, (newValue) => {
    if (newValue) {
        imageZoom.value = 1;
        imagePosition.value = { x: 0, y: 0 };
    }
});

// Computed properties for summary
const totalAmount = computed(() => {
    return disbursements.value.reduce((sum, item) => {
        return sum + (parseFloat(item.amount) || 0);
    }, 0);
});

const semesterSummary = computed(() => {
    const yearSummary = {};

    disbursements.value.forEach(item => {
        const year = item.academic_year || 'N/A';
        const semester = item.semester || 'N/A';

        // Initialize year if not exists
        if (!yearSummary[year]) {
            yearSummary[year] = {
                academicYear: year,
                total: 0,
                count: 0,
                semesters: {}
            };
        }

        // Initialize semester within year if not exists
        if (!yearSummary[year].semesters[semester]) {
            yearSummary[year].semesters[semester] = {
                semester: semester,
                total: 0,
                count: 0,
                byStatus: {}
            };
        }

        const status = item.obr_status || 'ON PROCESS';
        const amount = parseFloat(item.amount) || 0;

        // Update year totals
        yearSummary[year].total += amount;
        yearSummary[year].count++;

        // Update semester totals
        yearSummary[year].semesters[semester].total += amount;
        yearSummary[year].semesters[semester].count++;

        // Update status breakdown for semester
        if (!yearSummary[year].semesters[semester].byStatus[status]) {
            yearSummary[year].semesters[semester].byStatus[status] = {
                count: 0,
                amount: 0
            };
        }
        yearSummary[year].semesters[semester].byStatus[status].count++;
        yearSummary[year].semesters[semester].byStatus[status].amount += amount;
    });

    // Sort by year descending
    return Object.fromEntries(
        Object.entries(yearSummary).sort(([yearA], [yearB]) => {
            return yearB.localeCompare(yearA);
        })
    );
});

// Load data on mount
onMounted(() => {
    loadDisbursements();
});
</script>
