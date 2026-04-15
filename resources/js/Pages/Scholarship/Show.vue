<template>

    <Head :title="`${profile.first_name} ${profile.last_name} - Scholar Profile`" />

    <AdminLayout>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="goBackToProfiles()"
                        v-tooltip.top="'Back to Profiles'" />
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ profile.first_name }} {{ profile.middle_name }} {{ profile.last_name }}
                            {{ profile.extension_name }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Scholar Profile</p>
                    </div>
                </div>
                <div
                    class="flex items-center bg-white/80 dark:bg-[#1f2633]/90 backdrop-blur-sm border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm p-1 gap-0.5">
                    <Button icon="pi pi-book" label="Generate Ledger" size="small" rounded text
                        class="!font-medium text-gray-700 dark:text-gray-300" @click="showLedgerModal = true"
                        v-tooltip.top="'Generate Scholar Ledger'" />
                    <Button icon="pi pi-file-word" label="Certification" size="small" rounded text
                        class="!font-medium text-gray-700 dark:text-gray-300" @click="showCertPicker = true"
                        v-tooltip.top="'Generate Certification'" />
                    <div class="w-px h-6 bg-gray-200 mx-0.5 self-center flex-shrink-0"></div>
                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-user" label="Edit Personal" size="small"
                        rounded text class="!font-medium text-gray-700 dark:text-gray-300"
                        @click="showPersonalInfoModal = true" v-tooltip.top="'Edit Personal Information'" />
                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-home" label="Edit Family" size="small"
                        rounded text class="!font-medium text-gray-700 dark:text-gray-300"
                        @click="showFamilyInfoModal = true" v-tooltip.top="'Edit Family Information'" />
                </div>
            </div>

            <!-- Tab Navigation -->
            <div
                class="!rounded-4xl overflow-hidden bg-white dark:bg-[#1f2633] shadow-sm border border-gray-200 dark:border-white/10">
                <Tabs v-model:value="activeTab">
                    <TabList>
                        <Tab value="0"><i class="pi pi-user mr-2"></i>Personal Information</Tab>
                        <Tab value="1"><i class="pi pi-users mr-2"></i>Family Information</Tab>
                        <Tab value="2"><i class="pi pi-graduation-cap mr-2"></i>Academic Information</Tab>
                        <Tab value="3"><i class="pi pi-wallet mr-2"></i>Obligations & Transactions</Tab>
                        <Tab value="4"><i class="pi pi-paperclip mr-2"></i>Attachments</Tab>
                        <Tab value="5"><i class="pi pi-check-circle mr-2"></i>Approval History</Tab>
                        <Tab value="6"><i class="pi pi-list mr-2"></i>Activity Logs</Tab>
                    </TabList>
                    <TabPanels>
                        <!-- Personal Information Tab -->
                        <TabPanel value="0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <!-- Basic Information -->
                                <div class="space-y-4">
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">
                                        Basic Information</h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Full
                                            Name</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ fullName }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Date of
                                            Birth</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ formatDate(profile.date_of_birth)
                                            }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Age</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            calculateAge(profile.date_of_birth) }} years old</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Gender</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.gender || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Civil
                                            Status</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.civil_status || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Religion</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.religion || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Place of
                                            Birth</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.place_of_birth || 'N/A'
                                            }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Indigenous
                                            Group</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.indigenous_group || 'N/A'
                                            }}</p>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="space-y-4">
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">
                                        Contact Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Primary
                                            Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.contact_no || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Secondary
                                            Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.contact_no_2 || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Email</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.email || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Permanent
                                            Address</label>
                                        <p class="text-gray-900 dark:text-gray-100">
                                            {{ profile.address }}<br>
                                            {{ profile.barangay }}, {{ profile.municipality }}
                                        </p>
                                    </div>

                                    <div v-if="profile.temporary_address || profile.temporary_municipality">
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Present
                                            Address</label>
                                        <p class="text-gray-900 dark:text-gray-100">
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
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">
                                        Father's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Name</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.father_name || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Occupation</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.father_occupation ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.father_contact_no ||
                                            'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Mother's Information -->
                                <div class="space-y-4">
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">
                                        Mother's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Name</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.mother_name || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Occupation</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.mother_occupation ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.mother_contact_no ||
                                            'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Guardian's Information -->
                                <div class="space-y-4">
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">
                                        Guardian's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Name</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.guardian_name || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Relationship</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.guardian_relationship ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Occupation</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.guardian_occupation ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.guardian_contact_no ||
                                            'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Gross Monthly Income -->
                                <div class="col-span-full">
                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Parents/Guardian
                                            Gross Monthly
                                            Income</label>
                                        <p class="text-gray-900 dark:text-gray-100 text-lg font-semibold">
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
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Scholarship
                                        Records</h3>
                                    <Button v-if="hasPermission('applicants.edit')" icon="pi pi-plus" label="Add Record"
                                        @click="openAddRecordModal" severity="success" size="small" />
                                </div>
                                <div v-if="scholarshipRecords.length > 0">
                                    <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                        :value="scholarshipRecords" stripedRows showGridlines>
                                        <Column header="Program & School" headerClass="min-w-[250px]"
                                            bodyClass="min-w-[250px]">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{
                                                        slotProps.data.program?.name || 'N/A' }}</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{
                                                        slotProps.data.school?.name ||
                                                        'N/A' }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Course" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
                                            <template #body="slotProps">
                                                {{ slotProps.data.course?.name || 'N/A' }}
                                            </template>
                                        </Column>

                                        <Column header="Academic Details" headerClass="min-w-[180px]"
                                            bodyClass="min-w-[180px]">
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

                                        <Column header="Dates" headerClass="min-w-[160px]" bodyClass="min-w-[160px]">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="text-sm"><span class="font-medium">Filed:</span> {{
                                                        formatDateShort(slotProps.data.date_filed) }}</p>
                                                    <p class="text-sm"><span class="font-medium">Approved:</span> {{
                                                        formatDateShort(slotProps.data.date_approved) }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Status" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                                            <template #body="slotProps">
                                                <Chip v-if="slotProps.data.unified_status"
                                                    :label="slotProps.data.unified_status"
                                                    :class="getStatusClass(slotProps.data.unified_status)" />
                                            </template>
                                        </Column>

                                        <Column header="Remarks" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
                                            <template #body="slotProps">
                                                <div v-if="slotProps.data.remarks" v-safe-html="slotProps.data.remarks"
                                                    class="prose prose-sm max-w-none dark:prose-invert"></div>
                                                <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                                            </template>
                                        </Column>

                                        <Column header="Attachments" headerClass="min-w-[150px]"
                                            bodyClass="min-w-[150px]">
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
                                                        class="bg-blue-100 text-blue-800 dark:text-blue-300" />
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Actions" headerClass="min-w-[150px]" bodyClass="min-w-[150px]"
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
                                    <p class="text-gray-500 dark:text-gray-400">No scholarship records available</p>
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
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">All
                                        Attachments</h3>
                                    <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                        :value="allAttachments" stripedRows showGridlines paginator :rows="10">
                                        <Column header="Source" headerClass="min-w-[150px]" bodyClass="min-w-[150px]">
                                            <template #body="slotProps">
                                                <Chip :label="slotProps.data.attachment_source"
                                                    :class="slotProps.data.attachment_source === 'Scholarship Record' ? 'bg-blue-100 text-blue-800 dark:text-blue-300' : 'bg-green-100 text-green-800'" />
                                            </template>
                                        </Column>

                                        <Column header="Attachment Name" headerClass="min-w-[200px]"
                                            bodyClass="min-w-[200px]">
                                            <template #body="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <i :class="getFileIcon(slotProps.data.file_type)"
                                                        class="text-blue-600 dark:text-blue-400"></i>
                                                    <span class="font-medium">{{ slotProps.data.attachment_name
                                                        }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Related Record" headerClass="min-w-[250px]"
                                            bodyClass="min-w-[250px]">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{
                                                        slotProps.data.record_info.program }}</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{
                                                        slotProps.data.record_info.academic_year }} - {{
                                                            slotProps.data.record_info.term }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="File Details" headerClass="min-w-[200px]"
                                            bodyClass="min-w-[200px]">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{
                                                        slotProps.data.file_name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{
                                                        formatFileSize(slotProps.data.file_size) }}</p>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Uploaded" headerClass="min-w-[150px]" bodyClass="min-w-[150px]">
                                            <template #body="slotProps">
                                                {{ formatDateShort(slotProps.data.created_at) }}
                                            </template>
                                        </Column>

                                        <Column header="Actions" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
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
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">No Attachments Available</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Upload attachments from the
                                        Academic
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
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Status Change
                                            Timeline</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Complete history of all
                                            status updates</p>
                                    </div>

                                    <div v-for="(timeline, timelineIndex) in statusTimeline" :key="timeline.id"
                                        class="flex gap-4">
                                        <!-- Timeline Dot and Line -->
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/20 border-2 flex items-center justify-center border-blue-400">
                                                <i class="pi pi-check text-xs text-blue-600 dark:text-blue-400"></i>
                                            </div>
                                            <div v-if="timelineIndex < (statusTimeline.length - 1)"
                                                class="w-0.5 h-12 bg-gray-300 dark:bg-gray-600 mt-2"></div>
                                        </div>

                                        <!-- Timeline Content -->
                                        <div class="flex-1 pb-4">
                                            <div
                                                class="bg-white dark:bg-[#1f2633] p-4 rounded border border-gray-200 dark:border-white/10">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                                            Status: <span class="text-blue-600 dark:text-blue-400">{{
                                                                timeline.new_status
                                                                }}</span>
                                                        </h5>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{
                                                            formatDateTime(timeline.performed_at) }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4 mb-3">
                                                    <div>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400">Previous
                                                            Status</p>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{
                                                                timeline.old_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400">New Status
                                                        </p>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{
                                                                timeline.new_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">Encoded by</p>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                                        timeline.changed_by?.name || 'System'
                                                        }}</p>
                                                </div>

                                                <div v-if="timeline.remarks"
                                                    class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded border-l-4 border-blue-400">
                                                    <p
                                                        class="text-xs text-blue-700 dark:text-blue-300 font-semibold mb-1">
                                                        Remarks:
                                                    </p>
                                                    <p class="text-sm text-blue-900 dark:text-blue-200"
                                                        v-safe-html="timeline.remarks"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty state for status timeline -->
                                <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <i class="pi pi-inbox text-4xl mb-4 opacity-50"></i>
                                    <p class="text-lg">No Status History</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No status changes have been
                                        recorded yet</p>
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
                                            <div class="absolute left-0 top-1 w-9 h-9 rounded-full flex items-center justify-center text-white font-bold shadow-lg ring-4 ring-white dark:ring-[#1f2633]"
                                                :class="getActivityColor(activity.activity_type)">
                                                <i :class="getActivityIcon(activity.activity_type)"></i>
                                            </div>

                                            <!-- Activity Card -->
                                            <div
                                                class="bg-white dark:bg-[#1f2633] rounded-lg border border-gray-200 dark:border-white/10 hover:shadow-md transition-shadow p-4">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ getActivityLabel(activity.activity_type) }}
                                                    </h5>
                                                    <span
                                                        class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap ml-2">
                                                        {{ getRelativeTime(activity.performed_at) }}
                                                    </span>
                                                </div>

                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{
                                                    activity.description }}</p>

                                                <!-- User Info -->
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{
                                                        activity.user?.name ||
                                                        'System' }}</span>
                                                    <span v-if="activity.user?.office_designation">
                                                        ({{ activity.user.office_designation }})
                                                    </span>
                                                </div>

                                                <!-- Datetime Info -->
                                                <div
                                                    class="text-xs text-gray-500 dark:text-gray-400 mb-3 pb-3 border-b border-gray-100 dark:border-white/10">
                                                    {{ formatDateTime(activity.performed_at) }}
                                                </div>

                                                <!-- Details -->
                                                <div v-if="activity.details" class="mb-3">
                                                    <div
                                                        class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-2">
                                                        Details:</div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        <div v-for="(value, key) in activity.details" :key="key"
                                                            class="text-sm bg-gray-50 dark:bg-[#2a3040] p-2 rounded">
                                                            <span class="text-gray-600 dark:text-gray-400">{{
                                                                formatDetailKey(key) }}:
                                                            </span>
                                                            <span
                                                                class="text-gray-900 dark:text-gray-100 font-medium">{{
                                                                    maskIdValue(key,
                                                                        value) }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Remarks -->
                                                <div v-if="activity.remarks"
                                                    class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded border-l-4 border-blue-400">
                                                    <p
                                                        class="text-xs text-blue-700 dark:text-blue-300 font-semibold mb-1">
                                                        Remarks:</p>
                                                    <p class="text-sm text-blue-900 dark:text-blue-200"
                                                        v-safe-html="activity.remarks"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <i class="pi pi-history text-4xl mb-4 opacity-50"></i>
                                    <p class="text-lg">No Activity Records</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No activities have been
                                        logged for this
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
        <ManageAttachmentsModal v-model:visible="showAttachmentsModal" :record="selectedRecord"
            :has-edit-permission="hasPermission('applicants.edit')" @success="handleModalSuccess" />

        <!-- View Attachment Modal -->
        <ViewAttachmentModal v-model:visible="showViewerModal" :attachment="viewerAttachment" />

        <!-- QR Code Modal -->
        <QrCodeModal v-model:visible="showQrModal" :qr-data="qrCodeData" />

        <!-- Add/Edit Scholarship Record Modal -->
        <ScholarshipRecordModal v-model:visible="showRecordModal" :mode="recordModalMode" :record="editingRecord"
            :profile-id="profile.profile_id" @success="handleModalSuccess" />

        <!-- Delete Confirmation Dialog -->
        <DeleteRecordModal v-model:visible="showDeleteConfirm" :record="recordToDelete" @success="handleModalSuccess" />

        <!-- Scholar Ledger PDF Preview -->
        <PdfPreviewModal :show="showPdfPreview" @update:show="showPdfPreview = $event" :htmlDoc="pdfPreviewHtml"
            :title="pdfPreviewTitle" :paperSize="pdfPreviewSize" />

        <!-- Ledger Options Modal (iOS style) -->
        <Dialog v-model:visible="showLedgerModal" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="ledgerModalStyle">
                    <!-- Nav Bar -->
                    <div class="ios-nav-bar" @pointerdown="onLedgerDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showLedgerModal = false">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Generate Scholar Ledger</span>
                        <button class="ios-nav-btn ios-nav-action" @click="generateLedger">
                            Generate
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="ios-body">
                        <div class="ios-section">
                            <div class="ios-section-label">
                                <i class="pi pi-calendar"
                                    style="color: #34C759; font-size: 11px; margin-right: 4px;"></i>
                                Equivalent No. of Years for ROS
                            </div>
                            <div v-if="ledgerTermGroups.length > 0" class="ios-card" style="overflow: hidden;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #f9f9fb; border-bottom: 0.5px solid #e5e5ea;">
                                            <th style="width: 36px; padding: 7px 4px 7px 12px;"></th>
                                            <th
                                                style="text-align: left; padding: 7px 16px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px;">
                                                Semester Label</th>
                                            <th
                                                style="text-align: right; padding: 7px 18px 7px 8px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                Amount</th>
                                            <th
                                                style="text-align: right; padding: 7px 16px 7px 8px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px;">
                                                ROS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(group, gi) in ledgerTermGroups" :key="group.yearLevel">
                                            <tr style="background: #f2f2f7; border-top: 0.5px solid #e5e5ea;">
                                                <td colspan="4"
                                                    style="padding: 5px 16px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px;">
                                                    {{ formatYearLevel(group.yearLevel) }}
                                                </td>
                                            </tr>
                                            <tr v-for="(term, ti) in group.terms" :key="term.key"
                                                :style="(gi < ledgerTermGroups.length - 1 || ti < group.terms.length - 1) ? 'border-bottom: 0.5px solid #e5e5ea;' : ''">
                                                <td style="padding: 10px 4px 10px 12px; white-space: nowrap;">
                                                    <button
                                                        @click="ledgerExcluded[term.key] = !ledgerExcluded[term.key]"
                                                        :title="ledgerExcluded[term.key] ? 'Include in ledger' : 'Exclude from ledger'"
                                                        :style="ledgerExcluded[term.key]
                                                            ? 'background: #ff3b30; color: #fff; border: none; border-radius: 5px; padding: 1px 5px; font-size: 10px; cursor: pointer; line-height: 1.6;'
                                                            : 'background: #34c759; color: #fff; border: none; border-radius: 5px; padding: 1px 5px; font-size: 10px; cursor: pointer; line-height: 1.6;'">
                                                        <i :class="ledgerExcluded[term.key] ? 'pi pi-times' : 'pi pi-check'"
                                                            style="font-size:8px; font-weight: 600;"></i>
                                                    </button>
                                                </td>
                                                <td style="padding: 6px 12px;"
                                                    :style="ledgerExcluded[term.key] ? { opacity: '0.35' } : {}">
                                                    <input :value="ledgerSemesterLabels[term.key] ?? term.semester"
                                                        @input="ledgerSemesterLabels[term.key] = $event.target.value"
                                                        :disabled="ledgerExcluded[term.key]"
                                                        style="width: 100%; border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; font-size: 13px; color: #1c1c1e; outline: none; padding: 6px 10px; transition: border-color 0.15s;"
                                                        @focus="$event.target.style.borderColor = '#007AFF'"
                                                        @blur="$event.target.style.borderColor = '#d1d1d6'" />
                                                </td>
                                                <td
                                                    :style="{ padding: '10px 18px 10px 8px', fontSize: '12px', color: '#3a3a3c', textAlign: 'right', whiteSpace: 'nowrap', fontVariantNumeric: 'tabular-nums', opacity: ledgerExcluded[term.key] ? '0.35' : '1' }">
                                                    ₱{{ term.amount.toLocaleString('en-PH', {
                                                        minimumFractionDigits: 2
                                                    }) }}
                                                </td>
                                                <td
                                                    style="padding: 10px 16px 10px 8px; text-align: right; white-space: nowrap;">
                                                    <div
                                                        style="display: flex; gap: 4px; justify-content: flex-end; align-items: center;">
                                                        <template v-if="!ledgerExcluded[term.key]">
                                                            <button
                                                                v-for="opt in [{ label: '\u2014', value: '' }, { label: '4M', value: '4' }, { label: '6M', value: '6' }, { label: '12M', value: '12' }]"
                                                                :key="`ros-${opt.value}`"
                                                                @click="ledgerRosOverrides[term.key] = opt.value"
                                                                :style="ledgerRosOverrides[term.key] === opt.value
                                                                    ? 'background: #007AFF; color: #fff; border: none; border-radius: 8px; padding: 4px 8px; font-size: 12px; font-weight: 600; cursor: pointer;'
                                                                    : 'background: #e5e5ea; color: #1c1c1e; border: none; border-radius: 8px; padding: 4px 8px; font-size: 12px; font-weight: 500; cursor: pointer;'">
                                                                {{ opt.label }}
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="ios-card">
                                <div class="ios-row" style="color: #8e8e93; font-size: 13px; justify-content: center;">
                                    No disbursement records —
                                    ROS auto-detected</div>
                            </div>
                            <div class="ios-section-footer">Edit the semester label as it will appear in the ledger
                                (e.g. 1ST &amp; 2ND
                                SEMESTER). Changes are for printing only.</div>
                        </div>
                        <div style="height: 20px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Certification Type Picker -->
        <Dialog v-model:visible="showCertPicker" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal" :style="certModalStyle">
                    <!-- Nav Bar -->
                    <div class="ios-nav-bar" @pointerdown="onCertDragStart">
                        <button class="ios-nav-btn ios-nav-cancel" @click="showCertPicker = false">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-nav-title">Generate Certification</span>
                        <div style="width: 60px;"></div>
                    </div>

                    <!-- Body -->
                    <div class="ios-body">
                        <div class="ios-section">
                            <div class="ios-section-label">Course Name <span
                                    style="color: #8e8e93; font-weight: 400;">(optional)</span></div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <InputText v-model="certCourseName" placeholder="e.g. Medicine"
                                        style="border: none; background: transparent; box-shadow: none; padding: 0; font-size: 14px; color: #1c1c1e; width: 100%; outline: none;" />
                                </div>
                            </div>
                            <div class="ios-section-footer">Leave blank to use the course from the scholar's profile.
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">Certification Type</div>
                            <div class="ios-card">
                                <div class="ios-row" style="cursor: pointer; border-bottom: 0.5px solid #e5e5ea;"
                                    @click="generateCertification('review')">
                                    <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                        <i class="pi pi-file-word" style="color: #007AFF; font-size: 16px;"></i>
                                        <span style="font-size: 14px; color: #1c1c1e;">Certification for Review</span>
                                    </div>
                                    <i class="pi pi-chevron-right"
                                        style="color: #c7c7cc; font-size: 12px; flex-shrink: 0;"></i>
                                </div>
                                <div class="ios-row" style="cursor: pointer;"
                                    @click="generateCertification('postgrad')">
                                    <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                        <i class="pi pi-file-word" style="color: #007AFF; font-size: 16px;"></i>
                                        <span style="font-size: 14px; color: #1c1c1e;">Certification for
                                            Post-Grad</span>
                                    </div>
                                    <i class="pi pi-chevron-right"
                                        style="color: #c7c7cc; font-size: 12px; flex-shrink: 0;"></i>
                                </div>
                            </div>
                        </div>

                        <div style="height: 20px;"></div>
                    </div>
                </div>
            </template>
        </Dialog>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, inject, reactive } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';
import ScholarLedgerTemplate from '@/Pages/Scholarship/Pdf/ScholarLedgerTemplate.vue';
import ScholarCertificationTemplate from '@/Pages/Scholarship/Pdf/ScholarCertificationTemplate.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';

import PersonalInformationModal from '@/Components/modals/PersonalInformationModal.vue';
import FamilyInformationModal from '@/Components/modals/FamilyInformationModal.vue';
import ManageAttachmentsModal from '@/Components/modals/ManageAttachmentsModal.vue';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';
import QrCodeModal from '@/Components/modals/QrCodeModal.vue';
import ScholarshipRecordModal from '@/Components/modals/ScholarshipRecordModal.vue';
import DeleteRecordModal from '@/Components/modals/DeleteRecordModal.vue';
import ObligationsTransactions from '@/Components/ObligationsTransactions.vue';

// PDF preview state
const showPdfPreview = ref(false);
const pdfPreviewHtml = ref('');
const pdfPreviewTitle = ref('');
const pdfPreviewSize = ref('long');

// Certification type picker
const showCertPicker = ref(false);
const certCourseName = ref('');

// Ledger modal
const showLedgerModal = ref(false);
const ledgerRosOverrides = reactive({});
const ledgerSemesterLabels = reactive({}); // { termKey: editableLabel }
const ledgerExcluded = reactive({});       // { termKey: true/false }

// Ledger modal drag
const ledgerDragOffset = ref({ x: 0, y: 0 });
const ledgerDragStart = ref(null);
const ledgerModalStyle = computed(() => ({
    width: '560px',
    transform: `translate(${ledgerDragOffset.value.x}px, ${ledgerDragOffset.value.y}px)`,
}));
function onLedgerDragStart(e) {
    if (e.target.closest('button, input, select, a, .p-select')) return;
    ledgerDragStart.value = { x: e.clientX - ledgerDragOffset.value.x, y: e.clientY - ledgerDragOffset.value.y };
    document.addEventListener('pointermove', onLedgerDragMove);
    document.addEventListener('pointerup', onLedgerDragEnd);
}
function onLedgerDragMove(e) {
    if (!ledgerDragStart.value) return;
    ledgerDragOffset.value = { x: e.clientX - ledgerDragStart.value.x, y: e.clientY - ledgerDragStart.value.y };
}
function onLedgerDragEnd() {
    ledgerDragStart.value = null;
    document.removeEventListener('pointermove', onLedgerDragMove);
    document.removeEventListener('pointerup', onLedgerDragEnd);
}

const certDragOffset = ref({ x: 0, y: 0 });
const certDragStart = ref(null);
const certModalStyle = computed(() => ({
    width: '380px',
    transform: `translate(${certDragOffset.value.x}px, ${certDragOffset.value.y}px)`,
}));
function onCertDragStart(e) {
    if (e.target.closest('button, input, select, a, .p-inputtext')) return;
    certDragStart.value = { x: e.clientX - certDragOffset.value.x, y: e.clientY - certDragOffset.value.y };
    document.addEventListener('pointermove', onCertDragMove);
    document.addEventListener('pointerup', onCertDragEnd);
}
function onCertDragMove(e) {
    if (!certDragStart.value) return;
    certDragOffset.value = { x: e.clientX - certDragStart.value.x, y: e.clientY - certDragStart.value.y };
}
function onCertDragEnd() {
    certDragStart.value = null;
    document.removeEventListener('pointermove', onCertDragMove);
    document.removeEventListener('pointerup', onCertDragEnd);
}

onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onLedgerDragMove);
    document.removeEventListener('pointerup', onLedgerDragEnd);
    document.removeEventListener('pointermove', onCertDragMove);
    document.removeEventListener('pointerup', onCertDragEnd);
});

const LEDGER_YEAR_ORDER = ['1ST YEAR', '2ND YEAR', '3RD YEAR', '4TH YEAR', '5TH YEAR'];
const NUMERIC_YEAR_RE = /^(1ST|2ND|3RD|4TH|5TH|6TH|7TH|8TH)(\s+YEAR)?$/i;
const formatYearLevel = (yl) => {
    const u = String(yl ?? '').toUpperCase().trim();
    if (NUMERIC_YEAR_RE.test(u)) {
        const base = u.replace(/\s+YEAR$/i, '').trim();
        return `${base} YEAR`;
    }
    return u || '—';
};

const is4MonthTerm = (semester) => {
    if (typeof semester !== 'string') return false;
    const s = semester.toLowerCase();
    return s.includes('trimester') || s.includes('3rd semester') || s.includes('3rd sem');
};
const isScholar4Month = computed(() =>
    (props.profile?.disbursements || []).some(d => is4MonthTerm(d.semester))
);

const autoRosValue = (semester) => {
    if (isScholar4Month.value || is4MonthTerm(semester)) return '4';
    return '6';
};
const ledgerTermGroups = computed(() => {
    const disburse = props.profile?.disbursements || [];
    const yearMap = {};
    disburse.forEach(d => {
        const yl = d.year_level ?? '—';
        const key = `${yl}||${d.academic_year ?? ''}||${d.semester ?? ''}`;
        if (!yearMap[yl]) yearMap[yl] = {};
        if (!yearMap[yl][key]) {
            yearMap[yl][key] = {
                key,
                label: [d.academic_year, d.semester].filter(Boolean).join(' · ') || '—',
                semester: d.semester,
                amount: 0,
            };
        }
        yearMap[yl][key].amount += parseFloat(d.amount) || 0;
    });
    return Object.entries(yearMap)
        .sort(([a], [b]) => {
            const au = formatYearLevel(a);
            const bu = formatYearLevel(b);
            const isRevA = au === 'REVIEW' ? 1 : 0;
            const isRevB = bu === 'REVIEW' ? 1 : 0;
            if (isRevA !== isRevB) return isRevA - isRevB;
            const ia = LEDGER_YEAR_ORDER.indexOf(au);
            const ib = LEDGER_YEAR_ORDER.indexOf(bu);
            return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
        })
        .map(([yearLevel, terms]) => ({
            yearLevel,
            terms: Object.values(terms).sort((a, b) => {
                const [, ayA, semA] = a.key.split('||');
                const [, ayB, semB] = b.key.split('||');
                if (ayA !== ayB) return ayA < ayB ? -1 : 1;
                return semA < semB ? -1 : semA > semB ? 1 : 0;
            }),
        }));
});

// Pre-populate overrides + semester labels when modal opens
watch(showLedgerModal, (val) => {
    if (val) {
        ledgerTermGroups.value.forEach(group => {
            group.terms.forEach(term => {
                if (ledgerRosOverrides[term.key] === undefined) {
                    ledgerRosOverrides[term.key] = autoRosValue(term.semester);
                }
                if (ledgerSemesterLabels[term.key] === undefined) {
                    ledgerSemesterLabels[term.key] = term.semester ?? '';
                }
                if (ledgerExcluded[term.key] === undefined) {
                    ledgerExcluded[term.key] = false;
                }
            });
        });
    }
});

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
const editingRecord = ref(null);
const recordToDelete = ref(null);
const qrCodeData = ref(null);
const selectedRecord = ref(null);
const viewerAttachment = ref(null);
const activityLogs = ref([]);
const statusTimeline = ref([]);

// Status composable
const { statusOptions, getStatusLabel, getStatusSeverity } = useScholarshipStatus();

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
        'approved': 'border-green-400 bg-green-50 dark:bg-green-900/20',
        'denied': 'border-red-400 bg-red-50 dark:bg-red-900/20',
        'pending': 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
        'active': 'border-blue-400 bg-blue-50 dark:bg-blue-900/20',
        'completed': 'border-gray-400 bg-gray-50 dark:bg-[#2a3040]',
        'withdrawn': 'border-purple-400 bg-purple-50 dark:bg-purple-900/20',
        'loa': 'border-orange-400 bg-orange-50 dark:bg-orange-900/20',
        'suspended': 'border-red-900 bg-red-50 dark:bg-red-900/20',
        'unknown': 'border-gray-300 bg-gray-50 dark:bg-[#2a3040]',
        'declined': 'border-red-400 bg-red-50 dark:bg-red-900/20',
        'conditional': 'border-blue-400 bg-blue-50 dark:bg-blue-900/20',
        'resubmitted': 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
        'discontinued': 'border-orange-400 bg-orange-50 dark:bg-orange-900/20',
        'renewal_application': 'border-purple-400 bg-purple-50 dark:bg-purple-900/20'
    };
    return classes[action] || 'border-gray-400 bg-gray-50 dark:bg-[#2a3040]';
};

const handleSuccess = () => {
    showPersonalInfoModal.value = false;
    showFamilyInfoModal.value = false;
    setTimeout(() => {
        router.reload({ only: ['profile'] });
        if (refreshActivityLogs) refreshActivityLogs();
    }, 1500);
};

const { buildHtmlDoc } = usePdfPrint();

const generateCertification = (certType) => {
    showCertPicker.value = false;
    const today = new Date().toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
    const certBodyHtml = renderVueTemplate(ScholarCertificationTemplate, {
        profile: props.profile,
        certType,
        today,
        courseName: certCourseName.value.trim() || null,
    });
    const bodyHtml = '<style>@page { margin: 6mm 2.23cm; } @media screen { body { padding: 6mm 2.23cm; font-family: Verdana, Geneva, sans-serif; } } @media print { body { padding: 0; font-family: Verdana, Geneva, sans-serif; } }</style>' + certBodyHtml;
    const safeName = `${props.profile.last_name}_${props.profile.first_name}`.replace(/\s+/g, '_');
    const typeLabel = certType === 'review' ? 'Review' : 'PostGrad';
    pdfPreviewTitle.value = `Certification-${typeLabel}-${safeName}`;
    pdfPreviewSize.value = 'a4';
    pdfPreviewHtml.value = buildHtmlDoc(bodyHtml, pdfPreviewTitle.value, 'a4');
    showPdfPreview.value = true;
};

const generateLedger = () => {
    const today = new Date().toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
    const authUser = usePage().props.auth?.user;
    const bodyHtml = renderVueTemplate(ScholarLedgerTemplate, {
        profile: props.profile,
        preparedBy: authUser?.name ?? '',
        preparedByDesignation: authUser?.office_designation ?? '',
        today,
        rosOverrides: { ...ledgerRosOverrides },
        termSemesterLabels: { ...ledgerSemesterLabels },
        excludedTerms: { ...ledgerExcluded },
    });
    const safeName = `${props.profile.last_name}_${props.profile.first_name}`.replace(/\s+/g, '_');
    pdfPreviewTitle.value = `Ledger-${safeName}`;
    pdfPreviewSize.value = 'long';
    pdfPreviewHtml.value = buildHtmlDoc(bodyHtml, pdfPreviewTitle.value, 'long');
    showLedgerModal.value = false;
    showPdfPreview.value = true;
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
    editingRecord.value = null;
    showRecordModal.value = true;
};

const openEditRecordModal = (record) => {
    recordModalMode.value = 'edit';
    editingRecord.value = record;
    showRecordModal.value = true;
};

const confirmDeleteRecord = (record) => {
    recordToDelete.value = record;
    showDeleteConfirm.value = true;
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'approved': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'completed': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'denied': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'withdrawn': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        'unknown': 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

// Attachment methods
const manageAttachments = (record) => {
    selectedRecord.value = record;
    showAttachmentsModal.value = true;
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
    } catch (error) {
        toast.error('Failed to generate QR code');
        console.error(error);
    }
};

// Handle success from external modals
const handleModalSuccess = () => {
    router.reload({ only: ['profile'] });
    if (refreshActivityLogs) refreshActivityLogs();
};

// Attachment utility methods (used in Attachments tab)
const viewAttachment = (attachment) => {
    viewerAttachment.value = attachment;
    showViewerModal.value = true;
};

const downloadAttachment = (attachment) => {
    const routeName = attachment.download_route || 'scholarship.records.attachments.download';
    window.open(route(routeName, attachment.attachment_id), '_blank');
};

const getFileIcon = (fileType) => {
    if (fileType?.includes('pdf')) return 'pi-file-pdf';
    if (fileType?.includes('image')) return 'pi-image';
    return 'pi-file';
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
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

<style scoped>
/* Form input overrides for iOS consistency */
:deep(.p-inputtext),
:deep(.p-select) {
    border-radius: 10px;
}

:deep(.p-datepicker) {
    border-radius: 10px;
}

:deep(.p-inputgroup) {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--p-inputtext-border-color, #d1d5db);
}

:deep(.p-inputgroup:focus-within) {
    border-color: var(--p-inputtext-focus-border-color, #6366f1);
}

:deep(.p-inputgroup .p-inputtext),
:deep(.p-inputgroup .p-select),
:deep(.p-inputgroup-addon) {
    border: none;
    border-radius: 0;
}

/* Drag exclusion for editors */
:deep(.p-editor),
:deep(.ql-container),
:deep(.ql-toolbar) {
    -webkit-user-select: text;
    user-select: text;
    pointer-events: auto;
}
</style>
