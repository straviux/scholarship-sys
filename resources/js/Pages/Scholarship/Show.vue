<template>

    <Head :title="`${profile.first_name} ${profile.last_name} - Scholar Profile`" />

    <AdminLayout>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" text rounded @click="goBackToProfiles()"
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
                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-user" label="Edit Personal Info"
                        severity="warning" size="small" @click="showPersonalInfoModal = true"
                        v-tooltip.top="'Edit Personal Information'" />
                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-home" label="Edit Family Info"
                        severity="info" size="small" @click="showFamilyInfoModal = true"
                        v-tooltip.top="'Edit Family Information'" />
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
                        <Tab value="5">Approval History</Tab>
                        <Tab value="6">Activity Logs</Tab>
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
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Scholarship Records</h3>
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-plus" label="Add Record"
                                        @click="openAddRecordModal" severity="success" size="small" />
                                </div>
                                <div v-if="scholarshipRecords.length > 0">
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
                                                <Chip v-if="slotProps.data.unified_status"
                                                    :label="slotProps.data.unified_status"
                                                    :class="getStatusClass(slotProps.data.unified_status)" />
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

                                        <Column header="Actions" style="min-width: 150px"
                                            v-if="hasPermission('applicants.edit')">
                                            <template #body="slotProps">
                                                <div class="flex gap-1">
                                                    <Button icon="pi pi-pencil" size="small" outlined severity="warning"
                                                        v-tooltip.top="'Edit Record'"
                                                        @click="openEditRecordModal(slotProps.data)" />
                                                    <Button icon="pi pi-trash" size="small" outlined severity="danger"
                                                        v-tooltip.top="'Delete Record'"
                                                        @click="confirmDeleteRecord(slotProps.data)" />
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

                        <!-- Approval History Tab -->
                        <TabPanel value="5">
                            <div class="p-6">
                                <!-- Status Timeline -->
                                <div v-if="statusTimeline && statusTimeline.length > 0" class="space-y-4">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900">Status Change Timeline</h3>
                                        <p class="text-sm text-gray-600">Complete history of all status updates</p>
                                    </div>

                                    <div v-for="(timeline, timelineIndex) in statusTimeline" :key="timeline.id"
                                        class="flex gap-4">
                                        <!-- Timeline Dot and Line -->
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="w-10 h-10 rounded-full bg-blue-50 border-2 flex items-center justify-center border-blue-400">
                                                <i class="pi pi-check text-xs text-blue-600"></i>
                                            </div>
                                            <div v-if="timelineIndex < (statusTimeline.length - 1)"
                                                class="w-0.5 h-12 bg-gray-300 mt-2"></div>
                                        </div>

                                        <!-- Timeline Content -->
                                        <div class="flex-1 pb-4">
                                            <div class="bg-white p-4 rounded border border-gray-200">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h5 class="font-semibold text-gray-900">
                                                            Status: <span class="text-blue-600">{{ timeline.new_status
                                                                }}</span>
                                                        </h5>
                                                        <p class="text-sm text-gray-600">{{
                                                            formatDateTime(timeline.performed_at) }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4 mb-3">
                                                    <div>
                                                        <p class="text-xs text-gray-600">Previous Status</p>
                                                        <p class="text-sm font-medium text-gray-900">{{
                                                            timeline.old_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-600">New Status</p>
                                                        <p class="text-sm font-medium text-gray-900">{{
                                                            timeline.new_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <p class="text-xs text-gray-600">Encoded by</p>
                                                    <p class="text-sm font-medium text-gray-900">{{
                                                        timeline.changed_by?.name || 'System'
                                                        }}</p>
                                                </div>

                                                <div v-if="timeline.remarks"
                                                    class="bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                                                    <p class="text-xs text-blue-700 font-semibold mb-1">Remarks:
                                                    </p>
                                                    <p class="text-sm text-blue-900">{{ timeline.remarks }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty state for status timeline -->
                                <div v-else class="text-center py-12 text-gray-500">
                                    <i class="pi pi-inbox text-4xl mb-4" style="opacity: 0.5"></i>
                                    <p class="text-lg">No Status History</p>
                                    <p class="text-sm text-gray-400 mt-2">No status changes have been recorded yet</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Activity Logs Tab -->
                        <TabPanel value="6">
                            <div class="p-6">
                                <div v-if="activityLogs.length > 0" class="relative">
                                    <!-- Timeline background line -->
                                    <div
                                        class="absolute left-4 top-8 bottom-0 w-1 bg-gradient-to-b from-blue-300 via-purple-300 to-pink-300">
                                    </div>

                                    <!-- Timeline container -->
                                    <div class="space-y-8">
                                        <div v-for="(activity, index) in activityLogs" :key="activity.id"
                                            class="relative pl-16">

                                            <!-- Timeline dot -->
                                            <div class="absolute left-0 top-1 w-9 h-9 rounded-full flex items-center justify-center text-white font-bold shadow-lg ring-4 ring-white"
                                                :class="getActivityColor(activity.activity_type)">
                                                <i :class="getActivityIcon(activity.activity_type)"></i>
                                            </div>

                                            <!-- Activity Card -->
                                            <div
                                                class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow p-4">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h5 class="font-semibold text-gray-900">
                                                        {{ getActivityLabel(activity.activity_type) }}
                                                    </h5>
                                                    <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                                        {{ getRelativeTime(activity.performed_at) }}
                                                    </span>
                                                </div>

                                                <p class="text-sm text-gray-600 mb-3">{{ activity.description }}</p>

                                                <!-- User Info -->
                                                <div class="text-sm text-gray-600 mb-2">
                                                    <span class="font-medium text-gray-900">{{ activity.user?.name ||
                                                        'System' }}</span>
                                                    <span v-if="activity.user?.office_designation">
                                                        ({{ activity.user.office_designation }})
                                                    </span>
                                                </div>

                                                <!-- Datetime Info -->
                                                <div class="text-xs text-gray-500 mb-3 pb-3 border-b border-gray-100">
                                                    {{ formatDateTime(activity.performed_at) }}
                                                </div>

                                                <!-- Details -->
                                                <div v-if="activity.details" class="mb-3">
                                                    <div class="text-xs text-gray-600 font-semibold mb-2">Details:</div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        <div v-for="(value, key) in activity.details" :key="key"
                                                            class="text-sm bg-gray-50 p-2 rounded">
                                                            <span class="text-gray-600">{{ formatDetailKey(key) }}:
                                                            </span>
                                                            <span class="text-gray-900 font-medium">{{ maskIdValue(key,
                                                                value) }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Remarks -->
                                                <div v-if="activity.remarks"
                                                    class="p-2 bg-blue-50 rounded border-l-4 border-blue-400">
                                                    <p class="text-xs text-blue-700 font-semibold mb-1">Remarks:</p>
                                                    <p class="text-sm text-blue-900">{{ activity.remarks }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-gray-500">
                                    <i class="pi pi-history text-4xl mb-4" style="opacity: 0.5"></i>
                                    <p class="text-lg">No Activity Records</p>
                                    <p class="text-sm text-gray-400 mt-2">No activities have been logged for this
                                        profile yet</p>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>

        <!-- Personal Information Modal -->
        <PersonalInformationModal v-model:visible="showPersonalInfoModal" :profile="profile" @success="handleSuccess" />

        <!-- Family Information Modal -->
        <FamilyInformationModal v-model:visible="showFamilyInfoModal" :profile="profile" @success="handleSuccess" />

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

        <!-- Add/Edit Scholarship Record Modal -->
        <Dialog v-model:visible="showRecordModal" modal
            :header="recordModalMode === 'add' ? 'Add Scholarship Record' : 'Edit Scholarship Record'"
            :style="{ width: '700px' }">
            <div class="space-y-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Program <span v-if="isG12Record"
                                class="text-xs text-gray-500">(Optional for G12)</span><span v-else>*</span></label>
                        <ProgramSelect v-model="recordForm.program_id" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">School <span v-if="isG12Record"
                                class="text-xs text-gray-500">(Optional for G12)</span><span v-else>*</span></label>
                        <SchoolSelect v-model="recordForm.school_id" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course <span v-if="isG12Record"
                                class="text-xs text-gray-500">(N/A for G12)</span><span v-else>*</span></label>
                        <CourseSelect v-model="recordForm.course_id"
                            :scholarship-program-id="typeof recordForm.program_id === 'object' ? recordForm.program_id?.id : recordForm.program_id"
                            :disabled="isG12Record" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Level *</label>
                        <YearLevelSelect v-model="recordForm.year_level" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year <span
                                v-if="isG12Record" class="text-xs text-gray-500">(Optional for G12)</span><span
                                v-else>*</span></label>
                        <AcademicYearSelect v-model="recordForm.academic_year" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Term <span v-if="isG12Record"
                                class="text-xs text-gray-500">(Optional for G12)</span><span v-else>*</span></label>
                        <TermSelect v-model="recordForm.term" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Filed</label>
                        <DatePicker v-model="recordForm.date_filed" dateFormat="yy-mm-dd" showIcon fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Approved</label>
                        <DatePicker v-model="recordForm.date_approved" dateFormat="yy-mm-dd" showIcon fluid />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <Select v-model="recordForm.unified_status" :options="unifiedStatusOptions" optionLabel="label"
                        optionValue="value" placeholder="Select Status" fluid />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grant Provision</label>
                    <Select v-model="recordForm.grant_provision" :options="grantProvisionOptions" optionLabel="label"
                        optionValue="value" placeholder="Select Grant Provision" fluid showClear />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <InputText v-model="recordForm.remarks" placeholder="Enter remarks" fluid />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeRecordModal" outlined size="small" />
                <Button :label="recordModalMode === 'add' ? 'Add Record' : 'Update Record'" @click.stop="submitRecord"
                    :loading="recordForm.processing" size="small" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteConfirm" modal header="Confirm Deletion" :style="{ width: '450px' }">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                <div>
                    <p class="text-gray-900 font-semibold mb-2">Are you sure you want to delete this scholarship record?
                    </p>
                    <div v-if="recordToDelete" class="bg-gray-100 p-3 rounded border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-900">{{ recordToDelete.program?.name || 'N/A' }}</p>
                        <p class="text-xs text-gray-600">{{ recordToDelete.academic_year }} - {{ recordToDelete.term }}
                        </p>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">This action cannot be undone.</p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteConfirm = false" outlined size="small" />
                <Button label="Delete" severity="danger" @click="deleteRecord" :loading="deleting" size="small" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted, nextTick, onMounted, inject } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
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
import DatePicker from 'primevue/datepicker';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import PersonalInformationModal from '@/Components/modals/PersonalInformationModal.vue';
import FamilyInformationModal from '@/Components/modals/FamilyInformationModal.vue';
import ObligationsTransactions from '@/Components/ObligationsTransactions.vue';

const props = defineProps({
    profile: Object,
});

// Permission composable
const { hasPermission } = usePermission();

// Inject the refresh function from AdminLayout
const refreshActivityLogs = inject('refreshActivityLogs', null);

// State
const activeTab = ref(localStorage.getItem('scholarProfileActiveTab') || '0');
const showPersonalInfoModal = ref(false);
const showFamilyInfoModal = ref(false);
const showAttachmentsModal = ref(false);
const showViewerModal = ref(false);
const showQrModal = ref(false);
const showRecordModal = ref(false);
const showDeleteConfirm = ref(false);
const recordModalMode = ref('add'); // 'add' or 'edit'
const recordToDelete = ref(null);
const deleting = ref(false);
const qrCodeData = ref(null);
const qrCountdown = ref('');
const qrCountdownInterval = ref(null);
const selectedRecord = ref(null);
const viewerAttachment = ref(null);
const uploading = ref(false);
const activityLogs = ref([]);
const statusTimeline = ref([]);
const recordForm = ref({
    grant_id: null,
    program_id: null,
    school_id: null,
    course_id: null,
    year_level: null,
    academic_year: null,
    term: null,
    date_filed: null,
    date_approved: null,
    unified_status: 'pending',
    grant_provision: null,
    remarks: null,
    processing: false
});
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

// Status composable
const { statusOptions, getStatusLabel, getStatusSeverity } = useScholarshipStatus();

// Unified status options for form (exclude 'unknown' from dropdown)
const unifiedStatusOptions = computed(() => statusOptions.value.filter(status => status.value !== 'unknown'));

// Grant provision options
const grantProvisionOptions = [
    { label: 'Matriculation', value: 'Matriculation' },
    { label: 'RLE', value: 'RLE' },
    { label: 'Tuition', value: 'Tuition' },
    { label: 'RLE and Tuition', value: 'RLE and Tuition' }
];

// Image zoom state
const imageZoom = ref(1);
const imagePosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Watch for tab changes and persist to localStorage
watch(activeTab, (newValue) => {
    console.log('Tab changed to:', newValue);
    localStorage.setItem('scholarProfileActiveTab', newValue);
    // Load status timeline when tab 5 (Approval History) is selected
    if (newValue === '5') {
        console.log('Approval History tab selected, loading timeline...');
        loadStatusTimeline();
    }
});

// Load status timeline if Approval History tab is already open on mount
onMounted(() => {
    console.log('Component mounted, activeTab:', activeTab.value);
    if (activeTab.value === '5') {
        console.log('Tab 5 is already active, loading timeline on mount...');
        loadStatusTimeline();
    }
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

const isG12Record = computed(() => {
    const yearLevelValue = typeof recordForm.value.year_level === 'object' ? recordForm.value.year_level?.value : recordForm.value.year_level;
    return yearLevelValue === 'G12';
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

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(date);
};

const sortedApprovalHistory = (history) => {
    if (!history) return [];
    return [...history].sort((a, b) => new Date(b.performed_at) - new Date(a.performed_at));
};

const getHistoryActionLabel = (action) => {
    const labels = {
        'approved': 'Approved',
        'denied': 'Denied',
        'pending': 'Pending',
        'active': 'Active',
        'completed': 'Completed',
        'withdrawn': 'Withdrawn',
        'loa': 'Leave of Absence',
        'suspended': 'Suspended',
        'unknown': 'Unknown',
        'declined': 'Declined',
        'conditional': 'Conditional Approval',
        'resubmitted': 'Resubmitted',
        'discontinued': 'Discontinued',
        'renewal_application': 'Renewal Application'
    };
    return labels[action] || action;
};

const getHistoryIcon = (action) => {
    const icons = {
        'approved': 'pi pi-check',
        'denied': 'pi pi-times',
        'pending': 'pi pi-clock',
        'active': 'pi pi-circle-fill',
        'completed': 'pi pi-check-circle',
        'withdrawn': 'pi pi-times-circle',
        'loa': 'pi pi-pause',
        'suspended': 'pi pi-ban',
        'unknown': 'pi pi-question',
        'declined': 'pi pi-times',
        'conditional': 'pi pi-info-circle',
        'resubmitted': 'pi pi-refresh',
        'discontinued': 'pi pi-pause',
        'renewal_application': 'pi pi-plus'
    };
    return icons[action] || 'pi pi-circle';
};

const getHistoryStatusClass = (action) => {
    const classes = {
        'approved': 'border-green-400 bg-green-50',
        'denied': 'border-red-400 bg-red-50',
        'pending': 'border-yellow-400 bg-yellow-50',
        'active': 'border-blue-400 bg-blue-50',
        'completed': 'border-gray-400 bg-gray-50',
        'withdrawn': 'border-purple-400 bg-purple-50',
        'loa': 'border-orange-400 bg-orange-50',
        'suspended': 'border-red-900 bg-red-50',
        'unknown': 'border-gray-300 bg-gray-50',
        'declined': 'border-red-400 bg-red-50',
        'conditional': 'border-blue-400 bg-blue-50',
        'resubmitted': 'border-yellow-400 bg-yellow-50',
        'discontinued': 'border-orange-400 bg-orange-50',
        'renewal_application': 'border-purple-400 bg-purple-50'
    };
    return classes[action] || 'border-gray-400 bg-gray-50';
};

const handleSuccess = () => {
    showPersonalInfoModal.value = false;
    showFamilyInfoModal.value = false;
    setTimeout(() => {
        router.reload({ only: ['profile'] });
        if (refreshActivityLogs) refreshActivityLogs();
    }, 1500);
};

const goBackToProfiles = () => {
    const savedFilters = localStorage.getItem('scholarshipProfileFilters');
    if (savedFilters) {
        const filters = JSON.parse(savedFilters);
        router.visit(route('scholarship.profiles', filters));
    } else {
        router.visit(route('scholarship.profiles'));
    }
};

// Scholarship Record CRUD Methods
const openAddRecordModal = () => {
    recordModalMode.value = 'add';
    recordForm.value = {
        grant_id: null,
        program_id: null,
        school_id: null,
        course_id: null,
        year_level: null,
        academic_year: null,
        term: null,
        date_filed: new Date(),
        date_approved: null,
        unified_status: 'pending',
        grant_provision: null,
        remarks: null,
        processing: false
    };
    showRecordModal.value = true;
};

const openEditRecordModal = async (record) => {
    // console.log(record)
    recordModalMode.value = 'edit';
    recordForm.value = {
        // Use record.id as grant_id
        grant_id: record.id,
        // Use full objects for select components if available, otherwise use IDs
        program_id: record.program || record.program_id,
        school_id: record.school || record.school_id,
        course_id: record.course || record.course_id,
        year_level: record.year_level,
        academic_year: record.academic_year,
        term: record.term,
        date_filed: record.date_filed ? new Date(record.date_filed) : null,
        date_approved: record.date_approved ? new Date(record.date_approved) : null,
        unified_status: record.unified_status || 'pending',
        grant_provision: record.grant_provision || null,
        remarks: record.remarks,
        processing: false
    };
    showRecordModal.value = true;

    // Wait for modal to render and select components to initialize
    await nextTick();
};

const closeRecordModal = () => {
    showRecordModal.value = false;
    recordForm.value = {
        grant_id: null,
        program_id: null,
        school_id: null,
        course_id: null,
        year_level: null,
        academic_year: null,
        term: null,
        date_filed: null,
        date_approved: null,
        unified_status: 'pending',
        grant_provision: null,
        remarks: null,
        processing: false
    };
};

const submitRecord = async () => {
    recordForm.value.processing = true;

    try {
        // Validate required fields
        const yearLevelValue = typeof recordForm.value.year_level === 'object' ? recordForm.value.year_level?.value : recordForm.value.year_level;
        const isG12 = yearLevelValue === 'G12';

        // For non-G12 records, program, school, academic_year, and term are required
        if (!isG12) {
            if (!recordForm.value.program_id) {
                toast.error('Program is required');
                recordForm.value.processing = false;
                return;
            }
            if (!recordForm.value.school_id) {
                toast.error('School is required');
                recordForm.value.processing = false;
                return;
            }
            if (!recordForm.value.academic_year) {
                toast.error('Academic Year is required');
                recordForm.value.processing = false;
                return;
            }
            if (!recordForm.value.term) {
                toast.error('Term is required');
                recordForm.value.processing = false;
                return;
            }
        }

        // Year level is always required
        if (!yearLevelValue) {
            toast.error('Year Level is required');
            recordForm.value.processing = false;
            return;
        }

        const formData = {
            profile_id: props.profile.profile_id,
            program_id: typeof recordForm.value.program_id === 'object' ? recordForm.value.program_id?.id : recordForm.value.program_id,
            school_id: typeof recordForm.value.school_id === 'object' ? recordForm.value.school_id?.id : recordForm.value.school_id,
            course_id: typeof recordForm.value.course_id === 'object' ? recordForm.value.course_id?.id : recordForm.value.course_id,
            year_level: yearLevelValue,
            academic_year: typeof recordForm.value.academic_year === 'object' ? recordForm.value.academic_year?.value : recordForm.value.academic_year,
            term: typeof recordForm.value.term === 'object' ? recordForm.value.term?.value : recordForm.value.term,
            date_filed: formatDateForAPI(recordForm.value.date_filed),
            date_approved: formatDateForAPI(recordForm.value.date_approved),
            unified_status: recordForm.value.unified_status,
            grant_provision: recordForm.value.grant_provision,
            remarks: recordForm.value.remarks
        };
        console.log('Form data being sent:', formData);
        let response;
        if (recordModalMode.value === 'add') {
            response = await axios.post(route('scholarship_records.store'), formData);
            toast.success('Scholarship record added successfully');
        } else {
            console.log('Updating record with ID:', recordForm.value.grant_id);
            response = await axios.put(route('scholarship_records.update', recordForm.value.grant_id), formData);
            toast.success('Scholarship record updated successfully');
        }

        closeRecordModal();
        router.reload({ only: ['profile'] });
        if (refreshActivityLogs) refreshActivityLogs();
    } catch (error) {
        console.error('Error submitting scholarship record:', error);
        console.error('Error response:', error.response?.data);
        const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Failed to save scholarship record';
        toast.error(typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg));
    } finally {
        recordForm.value.processing = false;
    }
};

const confirmDeleteRecord = (record) => {
    recordToDelete.value = record;
    showDeleteConfirm.value = true;
};

const deleteRecord = async () => {
    deleting.value = true;

    try {
        if (!recordToDelete.value) {
            throw new Error('No record selected for deletion');
        }

        const recordId = recordToDelete.value.id || recordToDelete.value.grant_id;
        console.log('Deleting record with id:', recordId);
        console.log('Full record object:', recordToDelete.value);

        if (!recordId) {
            throw new Error('Record does not have a valid ID');
        }

        await axios.delete(route('scholarship_records.destroy', recordId));
        toast.success('Scholarship record deleted successfully');
        showDeleteConfirm.value = false;
        recordToDelete.value = null;
        router.reload({ only: ['profile'] });
        if (refreshActivityLogs) refreshActivityLogs();
    } catch (error) {
        console.error('Error deleting scholarship record:', error);
        toast.error(error.response?.data?.message || 'Failed to delete scholarship record');
    } finally {
        deleting.value = false;
    }
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'active': 'bg-green-100 text-green-800',
        'completed': 'bg-gray-100 text-gray-800',
        'denied': 'bg-red-100 text-red-800',
        'withdrawn': 'bg-purple-100 text-purple-800',
        'unknown': 'bg-gray-200 text-gray-700',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-200 text-gray-700';
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
        if (refreshActivityLogs) refreshActivityLogs();
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
        if (refreshActivityLogs) refreshActivityLogs();
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

const formatDateForAPI = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getAttachmentUrl = (attachment) => {
    const routeName = attachment.view_route || 'scholarship.records.attachments.view';
    return route(routeName, attachment.attachment_id);
};

// Activity Logs Methods
const getActivityIcon = (activityType) => {
    const icons = {
        'profile_edited': 'pi pi-user',
        'attachment_uploaded': 'pi pi-upload',
        'record_created': 'pi pi-plus-circle',
        'record_updated': 'pi pi-pencil',
        'record_deleted': 'pi pi-trash',
        'status_changed': 'pi pi-arrow-right-arrow-left',
        'profile_created': 'pi pi-user-plus'
    };
    return icons[activityType] || 'pi pi-history';
};

const getActivityColor = (activityType) => {
    const colors = {
        'profile_edited': 'bg-blue-500',
        'attachment_uploaded': 'bg-green-500',
        'record_created': 'bg-purple-500',
        'record_updated': 'bg-orange-500',
        'record_deleted': 'bg-red-500',
        'status_changed': 'bg-indigo-500',
        'profile_created': 'bg-teal-500'
    };
    return colors[activityType] || 'bg-gray-500';
};

const getActivityLabel = (activityType) => {
    const labels = {
        'profile_edited': 'Profile Updated',
        'profile_updated': 'Profile Updated',
        'attachment_uploaded': 'Attachment Uploaded',
        'record_created': 'Scholarship Record Created',
        'record_updated': 'Scholarship Record Updated',
        'record_deleted': 'Scholarship Record Deleted',
        'status_changed': 'Status Changed',
        'profile_created': 'Profile Created'
    };
    return labels[activityType] || activityType;
};

const getRelativeTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    if (days < 7) return `${days} day${days !== 1 ? 's' : ''} ago`;

    return formatDateShort(dateString);
};

const formatDetailKey = (key) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const maskIdValue = (key, value) => {
    // Check if user is admin (has permission to view IDs)
    const isAdmin = hasPermission('admin.access') || hasPermission('applicants.edit');

    // List of ID-related field names that should be masked for non-admins
    const idFields = ['record_id', 'attachment_id', 'profile_id', 'id'];
    const keyLower = key.toLowerCase();

    // If it's an ID field and user is not admin, mask it
    if (!isAdmin && idFields.some(field => keyLower.includes(field))) {
        return '****';
    }

    return value;
};

// Fetch activity logs for the profile
const fetchActivityLogs = async () => {
    try {
        console.log('Fetching activities for profile_id:', props.profile.profile_id);
        const response = await axios.get(`/activity-logs/${props.profile.profile_id}`);
        console.log('Activity logs response:', response.data);
        let activities = response.data.data || response.data || [];

        // Sort by activity type hierarchy and then by date (latest first within each type)
        const hierarchy = {
            'profile_created': 1,
            'record_created': 2,
            'profile_updated': 3,
            'record_updated': 4,
            'attachment_uploaded': 5,
            'status_changed': 6,
            'record_deleted': 7
        };

        activities.sort((a, b) => {
            // Sort by date first (latest first - newest at top), then by hierarchy
            const dateCompare = new Date(b.performed_at) - new Date(a.performed_at);
            if (dateCompare !== 0) {
                return dateCompare;
            }

            // If same date, sort by hierarchy (reverse so higher hierarchy numbers come first)
            const hierarchyA = hierarchy[a.activity_type] || 999;
            const hierarchyB = hierarchy[b.activity_type] || 999;
            return hierarchyB - hierarchyA;
        });

        activityLogs.value = activities;
        console.log('Activities loaded:', activityLogs.value.length);
    } catch (error) {
        console.error('Error fetching activity logs:', error);
        activityLogs.value = [];
    }
};

// Fetch activity logs when component mounts
fetchActivityLogs();

// Load status timeline data from API
const loadStatusTimeline = async () => {
    try {
        console.log('Loading status timeline for profile_id:', props.profile.profile_id);
        const response = await axios.get(`/activity-logs/${props.profile.profile_id}/status-timeline`);
        console.log('Status timeline response:', response.data);
        statusTimeline.value = response.data.data || response.data || [];
        console.log('Status timeline loaded:', statusTimeline.value.length, 'items');
    } catch (error) {
        console.error('Error loading status timeline:', error);
        statusTimeline.value = [];
    }
};

</script>
