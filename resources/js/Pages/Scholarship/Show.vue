<template>

    <Head :title="`${profile.first_name} ${profile.last_name} - Scholar Profile`" />

    <AdminLayout>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" text rounded @click="router.visit(route('scholarship.profiles'))"
                        v-tooltip.top="'Back to Profiles'" />
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ profile.first_name }} {{ profile.middle_name }} {{ profile.last_name }}
                            {{ profile.extension_name }}
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Scholar Profile</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-pencil" label="Edit" severity="warning"
                        outlined @click="editProfile" v-tooltip.top="'Edit Profile'" />
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-lg shadow">
                <Tabs v-model:value="activeTab">
                    <TabList>
                        <Tab value="0">Personal Information</Tab>
                        <Tab value="1">Family Information</Tab>
                        <Tab value="2">Academic Information</Tab>
                        <Tab value="3">Obligations & Transactions</Tab>
                        <Tab value="4">Attachments</Tab>
                    </TabList>
                    <TabPanels>
                        <!-- Personal Information Tab -->
                        <TabPanel value="0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <!-- Basic Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Full Name</label>
                                        <p class="text-gray-900">{{ fullName }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Date of Birth</label>
                                        <p class="text-gray-900">{{ formatDate(profile.date_of_birth) }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Age</label>
                                        <p class="text-gray-900">{{ calculateAge(profile.date_of_birth) }} years old</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Gender</label>
                                        <p class="text-gray-900">{{ profile.gender || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Civil Status</label>
                                        <p class="text-gray-900">{{ profile.civil_status || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Religion</label>
                                        <p class="text-gray-900">{{ profile.religion || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Place of Birth</label>
                                        <p class="text-gray-900">{{ profile.place_of_birth || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Indigenous Group</label>
                                        <p class="text-gray-900">{{ profile.indigenous_group || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Contact Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Primary Contact</label>
                                        <p class="text-gray-900">{{ profile.contact_no || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Secondary Contact</label>
                                        <p class="text-gray-900">{{ profile.contact_no_2 || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Email</label>
                                        <p class="text-gray-900">{{ profile.email || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Permanent Address</label>
                                        <p class="text-gray-900">
                                            {{ profile.address }}<br>
                                            {{ profile.barangay }}, {{ profile.municipality }}
                                        </p>
                                    </div>

                                    <div v-if="profile.temporary_address || profile.temporary_municipality">
                                        <label class="text-sm font-medium text-gray-600">Present Address</label>
                                        <p class="text-gray-900">
                                            {{ profile.temporary_address || 'N/A' }}<br>
                                            {{ profile.temporary_barangay || 'N/A' }}, {{ profile.temporary_municipality
                                                ||
                                                'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Family Information Tab -->
                        <TabPanel value="1">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                                <!-- Father's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Father's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.father_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.father_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.father_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Mother's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Mother's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.mother_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.mother_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.mother_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Guardian's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Guardian's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.guardian_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Relationship</label>
                                        <p class="text-gray-900">{{ profile.guardian_relationship || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.guardian_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.guardian_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Gross Monthly Income -->
                                <div class="col-span-full">
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Parents/Guardian Gross Monthly
                                            Income</label>
                                        <p class="text-gray-900 text-lg font-semibold">
                                            {{ formatCurrency(profile.parents_guardian_gross_monthly_income) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Academic Information Tab -->
                        <TabPanel value="2">
                            <div class="p-6">
                                <div v-if="scholarshipRecords.length > 0">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Scholarship Records</h3>
                                    <DataTable :value="scholarshipRecords" stripedRows showGridlines>
                                        <Column header="Program & School" style="min-width: 250px">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900">{{
                                                        slotProps.data.program?.name || 'N/A' }}</p>
                                                    <p class="text-sm text-gray-600">{{ slotProps.data.school?.name ||
                                                        'N/A' }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Course" style="min-width: 200px">
                                            <template #body="slotProps">
                                                {{ slotProps.data.course?.name || 'N/A' }}
                                            </template>
                                        </Column>

                                        <Column header="Academic Details" style="min-width: 180px">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="text-sm"><span class="font-medium">Year:</span> {{
                                                        slotProps.data.year_level || 'N/A' }}</p>
                                                    <p class="text-sm"><span class="font-medium">Term:</span> {{
                                                        slotProps.data.term || 'N/A' }}</p>
                                                    <p class="text-sm"><span class="font-medium">AY:</span> {{
                                                        slotProps.data.academic_year || 'N/A' }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Dates" style="min-width: 160px">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="text-sm"><span class="font-medium">Filed:</span> {{
                                                        formatDateShort(slotProps.data.date_filed) }}</p>
                                                    <p class="text-sm"><span class="font-medium">Approved:</span> {{
                                                        formatDateShort(slotProps.data.date_approved) }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Status" style="min-width: 120px">
                                            <template #body="slotProps">
                                                <Chip v-if="slotProps.data.approval_status"
                                                    :label="slotProps.data.approval_status"
                                                    :class="getStatusClass(slotProps.data.approval_status)" />
                                            </template>
                                        </Column>

                                        <Column field="remarks" header="Remarks" style="min-width: 200px" />

                                        <Column header="Attachments" style="min-width: 150px">
                                            <template #body="slotProps">
                                                <div class="flex gap-2">
                                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-qrcode"
                                                        size="small" outlined severity="info"
                                                        v-tooltip.top="'Show QR Code'"
                                                        @click="showQrCode(slotProps.data)" />
                                                    <Button v-if="hasPermission('applicants.edit')"
                                                        icon="pi pi-paperclip" size="small" outlined
                                                        v-tooltip.top="'Manage Attachments'"
                                                        @click="manageAttachments(slotProps.data)" />
                                                    <Chip
                                                        v-if="slotProps.data.attachments && slotProps.data.attachments.length > 0"
                                                        :label="slotProps.data.attachments.length.toString()"
                                                        class="bg-blue-100 text-blue-800" />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                                <div v-else class="text-center py-12">
                                    <i class="pi pi-info-circle text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No scholarship records available</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Attachments Tab -->
                        <!-- Obligations/Transactions Tab -->
                        <TabPanel value="3">
                            <ObligationsTransactions :profileId="profile.profile_id" />
                        </TabPanel>

                        <!-- Attachments Tab -->
                        <TabPanel value="4">
                            <div class="p-6">
                                <div v-if="allAttachments.length > 0">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">All Attachments</h3>
                                    <DataTable :value="allAttachments" stripedRows showGridlines paginator :rows="10">
                                        <Column header="Source" style="min-width: 150px">
                                            <template #body="slotProps">
                                                <Chip :label="slotProps.data.attachment_source"
                                                    :class="slotProps.data.attachment_source === 'Scholarship Record' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" />
                                            </template>
                                        </Column>

                                        <Column header="Attachment Name" style="min-width: 200px">
                                            <template #body="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <i :class="getFileIcon(slotProps.data.file_type)"
                                                        class="text-blue-600"></i>
                                                    <span class="font-medium">{{ slotProps.data.attachment_name
                                                    }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Related Record" style="min-width: 250px">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900">{{
                                                        slotProps.data.record_info.program }}</p>
                                                    <p class="text-sm text-gray-600">{{
                                                        slotProps.data.record_info.academic_year }} - {{
                                                            slotProps.data.record_info.term }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="File Details" style="min-width: 200px">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="text-sm text-gray-700">{{ slotProps.data.file_name }}</p>
                                                    <p class="text-xs text-gray-500">{{
                                                        formatFileSize(slotProps.data.file_size) }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Uploaded" style="min-width: 150px">
                                            <template #body="slotProps">
                                                {{ formatDateShort(slotProps.data.created_at) }}
                                            </template>
                                        </Column>

                                        <Column header="Actions" style="min-width: 200px">
                                            <template #body="slotProps">
                                                <div class="flex gap-2">
                                                    <Button icon="pi pi-eye" size="small" outlined label="View"
                                                        @click="viewAttachment(slotProps.data)"
                                                        v-tooltip.top="'Preview'" />
                                                    <Button icon="pi pi-download" size="small" outlined
                                                        @click="downloadAttachment(slotProps.data)"
                                                        v-tooltip.top="'Download'" />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                                <div v-else class="text-center py-12">
                                    <i class="pi pi-paperclip text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No Attachments Available</p>
                                    <p class="text-gray-400 text-sm mt-2">Upload attachments from the Academic
                                        Information tab</p>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>

        <!-- Edit Modal -->
        <ScholarFormModal v-model:visible="showEditModal" mode="edit" :profile="profile" @success="handleSuccess" />

        <!-- Manage Attachments Modal -->
        <Dialog v-model:visible="showAttachmentsModal" modal header="Manage Scholarship Record Attachments"
            :style="{ width: '60vw' }">
            <div class="space-y-4">
                <!-- Record Info -->
                <div v-if="selectedRecord" class="bg-gray-50 p-3 rounded border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ selectedRecord.program?.name || 'N/A' }}</p>
                    <p class="text-xs text-gray-600">{{ selectedRecord.academic_year }} - {{ selectedRecord.term }}</p>
                </div>

                <!-- Existing Attachments -->
                <div v-if="selectedRecord && selectedRecord.attachments && selectedRecord.attachments.length > 0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Existing Attachments</h4>
                    <div class="space-y-2">
                        <div v-for="attachment in selectedRecord.attachments" :key="attachment.attachment_id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                            <div class="flex items-center gap-3">
                                <i :class="getFileIcon(attachment.file_type)" class="text-2xl text-blue-600"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ attachment.attachment_name }}</p>
                                    <p class="text-xs text-gray-500">{{ attachment.file_name }} • {{
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
                            <Select v-model="attachmentForm.attachment_name" :options="attachmentTypeOptions"
                                optionLabel="label" optionValue="value" placeholder="Select attachment type"
                                class="w-full" />
                            <p class="text-xs text-gray-500 mt-1">Select the type of attachment you're uploading</p>
                        </div>
                        <div v-if="attachmentForm.attachment_name === 'others'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specify Attachment Type
                                *</label>
                            <InputText v-model="attachmentForm.custom_attachment_name"
                                placeholder="e.g., Medical Certificate, ID Photo" class="w-full" />
                            <p class="text-xs text-gray-500 mt-1">Enter the specific type of attachment</p>
                        </div>
                        <div v-if="attachmentForm.attachment_name === 'contract'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page Number (Optional)</label>
                            <InputNumber v-model="attachmentForm.page_number" :min="1" placeholder="e.g., 1, 2, 3"
                                class="w-full" />
                            <p class="text-xs text-gray-500 mt-1">Specify the page number if uploading contract pages
                                separately
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File (PDF or Image) *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (Max 25MB)</p>
                        </div>
                        <div v-if="attachmentForm.file">
                            <p class="text-sm text-gray-700">Selected: <span class="font-medium">{{
                                attachmentForm.file.name }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeAttachmentsModal" />
                <Button v-if="hasPermission('applicants.edit')" label="Upload" @click="uploadAttachment"
                    :loading="uploading"
                    :disabled="!attachmentForm.file || !attachmentForm.attachment_name || (attachmentForm.attachment_name === 'others' && !attachmentForm.custom_attachment_name)" />
            </template>
        </Dialog>

        <!-- View Attachment Modal -->
        <Dialog v-model:visible="showViewerModal" modal :header="viewerAttachment?.file_name"
            :style="{ width: '80vw', maxWidth: '1200px' }" :maximizable="true">
            <div class="flex items-center justify-center bg-gray-100 rounded relative overflow-hidden"
                style="min-height: 500px;">
                <!-- PDF Viewer -->
                <iframe v-if="viewerAttachment && viewerAttachment.file_type?.includes('pdf')"
                    :src="getAttachmentUrl(viewerAttachment)" class="w-full h-full rounded" style="min-height: 600px;"
                    frameborder="0">
                </iframe>

                <!-- Image Viewer with Zoom -->
                <div v-else-if="viewerAttachment && viewerAttachment.file_type?.includes('image')"
                    class="w-full h-full flex items-center justify-center relative" style="min-height: 600px;"
                    @wheel="handleWheel" @mousedown="handleMouseDown" @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp" @mouseleave="handleMouseUp"
                    :style="{ cursor: imageZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }">
                    <img :src="getAttachmentUrl(viewerAttachment)" :alt="viewerAttachment.file_name"
                        class="max-w-full max-h-[600px] object-contain rounded select-none" draggable="false" :style="{
                            transform: `scale(${imageZoom}) translate(${imagePosition.x / imageZoom}px, ${imagePosition.y / imageZoom}px)`,
                            transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                        }" />

                    <!-- Zoom Controls -->
                    <div class="absolute bottom-4 right-4 flex gap-2 bg-white rounded-lg shadow-lg p-2">
                        <Button icon="pi pi-minus" @click="zoomOut" size="small" severity="secondary" rounded
                            :disabled="imageZoom <= 0.5" />
                        <span class="px-3 py-2 text-sm font-semibold">{{ Math.round(imageZoom * 100) }}%</span>
                        <Button icon="pi pi-plus" @click="zoomIn" size="small" severity="secondary" rounded
                            :disabled="imageZoom >= 5" />
                        <Button icon="pi pi-refresh" @click="resetZoom" size="small" severity="secondary" rounded
                            v-tooltip.top="'Reset Zoom'" />
                    </div>
                </div>

                <!-- Fallback -->
                <div v-else class="text-center p-8">
                    <i class="pi pi-file text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">Unable to preview this file type</p>
                    <Button label="Download Instead" icon="pi pi-download" class="mt-4"
                        @click="downloadAttachment(viewerAttachment)" />
                </div>
            </div>

            <template #footer>
                <Button label="Download" icon="pi pi-download" @click="downloadAttachment(viewerAttachment)" />
                <Button label="Close" severity="secondary" @click="showViewerModal = false" />
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
                            <input type="text" :value="qrCodeData.url" readonly
                                class="flex-1 px-3 py-2 text-xs border border-gray-300 rounded-md bg-gray-50" />
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
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { usePermission } from '@/composable/permissions';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';
import ObligationsTransactions from '@/Components/ObligationsTransactions.vue';

const props = defineProps({
    profile: Object,
});

// Permission composable
const { hasPermission } = usePermission();

// State
const activeTab = ref(localStorage.getItem('scholarProfileActiveTab') || '0');
const showEditModal = ref(false);
const showAttachmentsModal = ref(false);
const showViewerModal = ref(false);
const showQrModal = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const selectedRecord = ref(null);
const viewerAttachment = ref(null);
const uploading = ref(false);
const attachmentForm = ref({
    attachment_name: '',
    custom_attachment_name: '',
    page_number: null,
    file: null
});
const fileInput = ref(null);

// Attachment type options
const attachmentTypeOptions = [
    { label: 'Contract', value: 'contract' },
    { label: 'Copy of Grades', value: 'copy_of_grades' },
    { label: 'Certificate of Enrollment', value: 'certificate_of_enrollment' },
    { label: 'Certificate of Registration', value: 'certificate_of_registration' },
    { label: 'Others', value: 'others' }
];

// Image zoom state
const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Watch for tab changes and persist to localStorage
watch(activeTab, (newValue) => {
    localStorage.setItem('scholarProfileActiveTab', newValue);
});

// Reset zoom when modal opens/closes
watch(showViewerModal, (newValue) => {
    if (newValue) {
        imageZoom.value = 1;
        imagePosition.value = { x: 0, y: 0 };
    }
});

// Computed
const fullName = computed(() => {
    return `${props.profile.first_name} ${props.profile.middle_name || ''} ${props.profile.last_name} ${props.profile.extension_name || ''}`.trim();
});

const currentGrant = computed(() => {
    return props.profile.scholarship_grant?.[0] || null;
});

const scholarshipRecords = computed(() => {
    if (!props.profile.scholarship_grant) return [];
    // Return all records, already sorted by latest first from backend
    return props.profile.scholarship_grant;
});

const allAttachments = computed(() => {
    const attachments = [];

    // Add scholarship record attachments
    if (props.profile.scholarship_grant) {
        props.profile.scholarship_grant.forEach(record => {
            if (record.attachments && record.attachments.length > 0) {
                record.attachments.forEach(attachment => {
                    attachments.push({
                        ...attachment,
                        attachment_source: 'Scholarship Record',
                        record_info: {
                            program: record.program?.name || 'N/A',
                            academic_year: record.academic_year || 'N/A',
                            term: record.term || 'N/A',
                            year_level: record.year_level || 'N/A'
                        },
                        download_route: 'scholarship.records.attachments.download',
                        view_route: 'scholarship.records.attachments.view'
                    });
                });
            }
        });
    }

    // Add disbursement attachments
    if (props.profile.disbursements) {
        props.profile.disbursements.forEach(disbursement => {
            if (disbursement.attachments && disbursement.attachments.length > 0) {
                disbursement.attachments.forEach(attachment => {
                    attachments.push({
                        ...attachment,
                        attachment_source: 'Disbursement',
                        attachment_name: attachment.attachment_type, // Use attachment_type as name for disbursements
                        record_info: {
                            program: `OBR #${disbursement.obr_no || 'N/A'}`,
                            academic_year: disbursement.academic_year || 'N/A',
                            term: disbursement.semester || 'N/A',
                            year_level: disbursement.year_level || 'N/A'
                        },
                        download_route: 'disbursements.attachments.download',
                        view_route: 'disbursements.attachments.view'
                    });
                });
            }
        });
    }

    return attachments;
});

// Methods
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const calculateAge = (dateString) => {
    if (!dateString) return 'N/A';
    const birthDate = new Date(dateString);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const formatDateShort = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
};

const editProfile = () => {
    showEditModal.value = true;
};

const handleSuccess = () => {
    showEditModal.value = false;
    router.reload({ only: ['profile'] });
};

const getStatusClass = (status) => {
    const classes = {
        'approved': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'declined': 'bg-red-100 text-red-800',
        'conditional': 'bg-orange-100 text-orange-800',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

// Attachment methods
const manageAttachments = (record) => {
    selectedRecord.value = record;
    showAttachmentsModal.value = true;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 25 * 1024 * 1024) { // 25MB
            toast.error('File size must not exceed 25MB');
            event.target.value = '';
            return;
        }
        attachmentForm.value.file = file;
    }
};

const uploadAttachment = async () => {
    if (!attachmentForm.value.attachment_name || !attachmentForm.value.file) {
        toast.error('Please provide attachment name and select a file');
        return;
    }

    // Validate "others" - requires custom name
    if (attachmentForm.value.attachment_name === 'others' && !attachmentForm.value.custom_attachment_name) {
        toast.error('Please specify the attachment type');
        return;
    }

    uploading.value = true;
    const formData = new FormData();

    // Use custom name if "others" is selected, otherwise use the selected value
    const finalAttachmentName = attachmentForm.value.attachment_name === 'others'
        ? attachmentForm.value.custom_attachment_name
        : attachmentForm.value.attachment_name;

    formData.append('attachment_name', finalAttachmentName);
    formData.append('file', attachmentForm.value.file);

    // Add page number for contracts
    if (attachmentForm.value.attachment_name === 'contract' && attachmentForm.value.page_number) {
        formData.append('page_number', attachmentForm.value.page_number);
    }

    try {
        const response = await axios.post(route('scholarship.records.attachments.upload', selectedRecord.value.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        toast.success('Attachment uploaded successfully');

        // Update the selected record's attachments
        if (response.data.attachments) {
            selectedRecord.value.attachments = response.data.attachments;
        }

        attachmentForm.value = {
            attachment_name: '',
            custom_attachment_name: '',
            page_number: null,
            file: null
        };
        if (fileInput.value) fileInput.value.value = '';

        // Reload the profile data to update all views
        router.reload({ only: ['profile'] });
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to upload attachment');
    } finally {
        uploading.value = false;
    }
};

const viewAttachment = (attachment) => {
    viewerAttachment.value = attachment;
    showViewerModal.value = true;
};

const downloadAttachment = (attachment) => {
    const routeName = attachment.download_route || 'scholarship.records.attachments.download';
    window.open(route(routeName, attachment.attachment_id), '_blank');
};

const deleteAttachment = async (attachment) => {
    if (!confirm('Are you sure you want to delete this attachment?')) return;

    try {
        const response = await axios.delete(route('scholarship.records.attachments.delete', attachment.attachment_id));
        toast.success('Attachment deleted successfully');

        // Update the selected record's attachments immediately
        if (selectedRecord.value && response.data.attachments) {
            selectedRecord.value.attachments = response.data.attachments;
        }

        // Reload the profile data to update all views
        router.reload({ only: ['profile'] });
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete attachment');
    }
};

const closeAttachmentsModal = () => {
    showAttachmentsModal.value = false;
    selectedRecord.value = null;
    attachmentForm.value = { attachment_name: '', file: null };
    if (fileInput.value) fileInput.value.value = '';
};

// QR Code for mobile upload
const showQrCode = async (record) => {
    try {
        const response = await axios.post(route('scholarship.records.generate-qr', record.id));
        qrCodeData.value = {
            qrCode: response.data.qr_code,
            url: response.data.url,
            expiresAt: response.data.expires_at,
            record: record
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

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'pi-file-pdf';
    if (fileType?.includes('image')) return 'pi-image';
    return 'pi-file';
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

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};

const getAttachmentUrl = (attachment) => {
    const routeName = attachment.view_route || 'scholarship.records.attachments.view';
    return route(routeName, attachment.attachment_id);
};

</script>
