<template>

    <Head title="Profiles" />

    <AdminLayout>
        <div>
            <!-- Toolbar -->
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-users text-indigo-900" style="font-size:2rem"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Scholarship Profiles</h1>
                            <p class="text-sm text-gray-600">Browse and manage scholarship applicant profiles</p>
                        </div>
                    </div>
                </template>

                <template #center>
                    <div class="flex items-center justify-center">
                        <SelectButton v-model="profileType" :options="profileTypeOptions" optionLabel="label"
                            optionValue="value" aria-labelledby="profile-type">
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="slotProps.option.icon"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </SelectButton>
                    </div>
                </template>

                <template #end>
                    <div class="flex gap-3 items-center">
                        <Button icon="pi pi-plus" @click="addRecordPopover.toggle($event)" severity="success"
                            v-tooltip.bottom="'Add New Record'" />
                        <Popover ref="addRecordPopover">
                            <div class="flex flex-col gap-2 w-48">
                                <!-- <Button @click="openAddApplicantModal" label="Add Applicant" icon="pi pi-user-plus"
                                    severity="success" outlined class="justify-start" /> -->
                                <Button @click="openAddExistingModal" label="Add Existing" icon="pi pi-user-edit"
                                    severity="info" outlined class="justify-start" />
                            </div>
                        </Popover>
                        <Button icon="pi pi-refresh" @click="refreshData" severity="secondary" outlined
                            v-tooltip.bottom="'Refresh'" />
                        <Button icon="pi pi-print" @click="actionsPopover.toggle($event)" severity="info"
                            v-tooltip.bottom="'Reports & Export'" />
                        <Popover ref="actionsPopover">
                            <div class="flex flex-col gap-2 w-48">
                                <Button @click="openReportModal" label="Generate Report" icon="pi pi-file-pdf"
                                    severity="secondary" outlined class="justify-start" />
                                <Button @click="openExportModal" label="Export Data" icon="pi pi-download"
                                    severity="secondary" outlined class="justify-start" />
                            </div>
                        </Popover>
                    </div>
                </template>
            </Toolbar>

            <!-- Filters Panel -->
            <Panel>
                <div class="space-y-3 -mt-6">
                    <!-- Filter Controls Header -->
                    <div class="flex justify-between items-center py-1">
                        <div class="flex items-center gap-3">
                            <Button :label="showAllFilters ? 'Show Basic Filters' : 'Show All Filters'"
                                icon="pi pi-filter" severity="secondary" size="small" outlined
                                @click="showAllFilters = !showAllFilters" />
                        </div>
                        <div class="flex items-center gap-3">
                            <Tag :value="`${totalRecords} profiles`" severity="info" />
                            <Button severity="secondary" outlined size="small" icon="pi pi-times" @click="clearFilters"
                                v-tooltip.bottom="'Clear Filters'" />
                        </div>
                    </div>

                    <!-- All Filters -->
                    <div class="space-y-3">
                        <!-- Default Filters Row -->
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:gap-8">
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Applicant Name</label>
                                <InputText v-model="filter.name" placeholder="Search applicant name..." class="w-full"
                                    size="small" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                                <ProgramSelect v-model="filter.program" label="shortname"
                                    custom-placeholder="All Programs" size="small" class="w-full" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                                <CourseSelect v-model="filter.course" label="shortname" custom-placeholder="All Courses"
                                    size="small" class="w-full" />
                            </div>
                            <!-- Only show Approval Status filter when profileType is 'all' -->
                            <div class="flex flex-col" v-if="profileType === 'all'">
                                <label class="text-xs font-medium text-gray-600 mb-1">Approval Status</label>
                                <Select v-model="filter.approval_status" :options="approvalStatusOptions"
                                    optionLabel="label" optionValue="value" placeholder="All Statuses" showClear
                                    class="w-full" size="small" />
                            </div>
                        </div>

                        <!-- Additional Filters (shown when showAllFilters is true) -->
                        <template v-if="showAllFilters">
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:gap-8">
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                                    <SchoolSelect v-model="filter.school" label="shortname"
                                        custom-placeholder="All Schools" size="small" class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                                    <MunicipalitySelect v-model="filter.municipality"
                                        custom-placeholder="All Municipalities" size="small" class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                                    <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                                        size="small" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </Panel>

            <!-- Profiles DataView -->
            <div class="mt-8">
                <Panel>
                    <!-- Info Bar -->
                    <div class="md:flex hidden items-center justify-between gap-4 mb-4 p-3 bg-gray-50 rounded-lg -mt-2">
                        <div class="flex-1 max-w-md">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-gray-400" />
                                <InputText v-model="globalFilter" placeholder="Search..." class="w-full" size="small" />
                            </IconField>
                        </div>
                        <div class="flex items-center justify-center gap-4">
                            <div class="text-sm text-gray-600 flex items-center justify-center gap-2">
                                <RecordsSelect v-model="filter.records" label="label" class="w-28" size="small" />
                                <span>/ <strong>{{ totalRecords }}</strong></span>
                            </div>
                            <SelectButton v-model="layout" :options="layoutOptions" optionLabel="icon" dataKey="value"
                                aria-labelledby="custom" size="small">
                                <template #option="slotProps">
                                    <i :class="slotProps.option.icon" v-tooltip.bottom="slotProps.option.tooltip"></i>
                                </template>
                            </SelectButton>
                        </div>
                    </div>

                    <!-- DataTable View -->
                    <DataTable v-if="layout === 'table'" :value="profilesData" paginator :rows="dataViewRows"
                        :totalRecords="totalRecords" :first="first" @page="onPageChange" :lazy="true"
                        paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                        :rowHover="true" stripedRows class="compact-table">

                        <Column field="unique_id" header="ID" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <Avatar :label="getInitials(slotProps.data)" size="normal" shape="circle"
                                        class="bg-gradient-to-br from-blue-500 to-blue-600 text-white" />
                                    <div>
                                        <div class="font-bold text-sm">{{ getFullName(slotProps.data) }}</div>
                                        <div class="text-xs text-gray-500">{{ slotProps.data.unique_id || 'N/A' }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="contact_no" header="Contact" style="min-width: 130px;">
                            <template #body="slotProps">
                                <div class="text-sm">
                                    <div>{{ slotProps.data.contact_no || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ slotProps.data.municipality || 'N/A' }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="program" header="Program" style="min-width: 150px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record"
                                    class="text-sm font-semibold truncate">
                                    {{ slotProps.data.latest_scholarship_record.program?.shortname || 'N/A' }}
                                </div>
                                <div v-else class="text-sm text-gray-400">N/A</div>
                            </template>
                        </Column>

                        <Column field="course" header="Course" style="min-width: 150px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record">
                                    <div class="text-sm font-medium truncate">
                                        {{ slotProps.data.latest_scholarship_record.course?.shortname || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ slotProps.data.latest_scholarship_record.school?.shortname || 'N/A' }}
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400">N/A</div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" style="min-width: 140px;">
                            <template #body="slotProps">
                                <Chip
                                    v-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.scholarship_status !== null"
                                    :label="getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.scholarship_status)"
                                    :severity="getScholarshipStatusSeverity(slotProps.data.latest_scholarship_record.scholarship_status)"
                                    size="small" class="font-medium" />
                                <Chip v-else label="No Record" severity="secondary" size="small" />
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" size="small" severity="info" outlined rounded
                                        v-tooltip.top="'View'" @click="viewFullProfile(slotProps.data)" />
                                    <Button icon="pi pi-history" size="small" severity="secondary" outlined rounded
                                        v-tooltip.top="'History'" @click="viewFullHistory(slotProps.data)" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-users text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg">No profiles found</p>
                                <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataTable>

                    <!-- DataView (List/Grid) -->
                    <DataView v-else :value="profilesData" :layout="layout" paginator :rows="dataViewRows"
                        :totalRecords="totalRecords" :first="first" @page="onPageChange" :lazy="true"
                        paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'">

                        <!-- List Layout -->
                        <template #list="slotProps">
                            <div class="grid grid-cols-1 gap-3">
                                <div v-for="(item, index) in slotProps.items" :key="index"
                                    class="flex flex-col md:flex-row gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md hover:border-blue-300 transition-all duration-200 bg-white">
                                    <!-- Avatar and Basic Info -->
                                    <div class="flex items-center gap-3 md:min-w-[260px]">
                                        <Avatar :label="getInitials(item)" size="large" shape="circle"
                                            class="bg-gradient-to-br from-blue-500 to-blue-600 text-white" />
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-base text-gray-900 truncate"
                                                :title="getFullName(item)">
                                                {{ getFullName(item) }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ item.unique_id || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1 flex items-center gap-1.5">
                                                <i class="pi pi-phone text-[10px]"></i>
                                                {{ item.contact_no || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Academic Info -->
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3 min-w-0">
                                        <div v-if="item.latest_scholarship_record">
                                            <div class="text-[10px] text-gray-500 uppercase tracking-wide mb-0.5">
                                                Program</div>
                                            <div class="font-semibold text-sm text-gray-900 truncate"
                                                :title="item.latest_scholarship_record.program?.name">
                                                {{ item.latest_scholarship_record.program?.shortname || 'N/A' }}
                                            </div>
                                        </div>
                                        <div v-if="item.latest_scholarship_record">
                                            <div class="text-[10px] text-gray-500 uppercase tracking-wide mb-0.5">Course
                                            </div>
                                            <div class="font-medium text-sm text-gray-900 truncate"
                                                :title="item.latest_scholarship_record.course?.name">
                                                {{ item.latest_scholarship_record.course?.shortname || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-600 truncate"
                                                :title="item.latest_scholarship_record.school?.name">
                                                {{ item.latest_scholarship_record.school?.shortname || 'N/A' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status and Actions -->
                                    <div
                                        class="flex flex-row md:flex-col justify-between md:justify-start items-center md:items-end gap-2 md:min-w-[140px]">
                                        <!-- Scholarship Status -->
                                        <div v-if="item.latest_scholarship_record && item.latest_scholarship_record.scholarship_status !== null"
                                            class="text-right">
                                            <Chip
                                                :label="getScholarshipStatusLabel(item.latest_scholarship_record.scholarship_status)"
                                                :severity="getScholarshipStatusSeverity(item.latest_scholarship_record.scholarship_status)"
                                                size="small" class="font-medium" />
                                        </div>
                                        <div v-else>
                                            <Chip label="No Record" severity="secondary" size="small" />
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-eye" size="small" severity="info" outlined rounded
                                                v-tooltip.top="'View'" @click="viewFullProfile(item)" />
                                            <Button icon="pi pi-history" size="small" severity="secondary" outlined
                                                rounded v-tooltip.top="'History'" @click="viewFullHistory(item)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Grid Layout -->
                        <template #grid="slotProps">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <div v-for="(item, index) in slotProps.items" :key="index"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md hover:border-blue-300 transition-all duration-200 bg-white">
                                    <div class="flex flex-col items-center text-center space-y-3">
                                        <!-- Avatar -->
                                        <Avatar :label="getInitials(item)" size="xlarge" shape="circle"
                                            class="bg-gradient-to-br from-blue-500 to-blue-600 text-white" />

                                        <!-- Name & ID -->
                                        <div class="w-full">
                                            <div class="font-bold text-sm text-gray-900 truncate"
                                                :title="getFullName(item)">
                                                {{ getFullName(item) }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ item.unique_id || 'N/A' }}
                                            </div>
                                        </div>

                                        <Divider class="my-1" />

                                        <!-- Academic Info -->
                                        <div class="w-full space-y-2 text-sm">
                                            <div v-if="item.latest_scholarship_record">
                                                <div class="text-[10px] text-gray-500 uppercase tracking-wide">Program
                                                </div>
                                                <div class="font-semibold text-gray-900 truncate"
                                                    :title="item.latest_scholarship_record.program?.name">
                                                    {{ item.latest_scholarship_record.program?.shortname || 'N/A' }}
                                                </div>
                                            </div>
                                            <div v-if="item.latest_scholarship_record">
                                                <div class="text-[10px] text-gray-500 uppercase tracking-wide">Course
                                                </div>
                                                <div class="font-medium text-gray-900 truncate"
                                                    :title="item.latest_scholarship_record.course?.name">
                                                    {{ item.latest_scholarship_record.course?.shortname || 'N/A' }}
                                                </div>
                                            </div>

                                            <!-- Status -->
                                            <div class="pt-1">
                                                <div
                                                    v-if="item.latest_scholarship_record && item.latest_scholarship_record.scholarship_status !== null">
                                                    <Chip
                                                        :label="getScholarshipStatusLabel(item.latest_scholarship_record.scholarship_status)"
                                                        :severity="getScholarshipStatusSeverity(item.latest_scholarship_record.scholarship_status)"
                                                        size="small" class="font-medium" />
                                                </div>
                                                <Chip v-else label="No Record" severity="secondary" size="small" />
                                            </div>
                                        </div>

                                        <Divider class="my-1" />

                                        <!-- Actions -->
                                        <div class="flex gap-2 w-full">
                                            <Button icon="pi pi-eye" size="small" severity="info" outlined
                                                class="flex-1" v-tooltip.top="'View'" @click="viewFullProfile(item)" />
                                            <Button icon="pi pi-history" size="small" severity="secondary" outlined
                                                class="flex-1" v-tooltip.top="'History'"
                                                @click="viewFullHistory(item)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-users text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg">No profiles found</p>
                                <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataView>
                </Panel>
            </div>
        </div>

        <!-- Approval Workflow Dialog -->
        <Dialog v-model:visible="showApprovalDialog" modal header="Application Review & Approval"
            :style="{ width: '90vw', maxWidth: '1200px' }" class="p-fluid" :closable="true" :dismissableMask="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-clipboard-check text-lg text-blue-600"></i>
                        <span class="font-semibold">Application Review & Approval</span>
                    </div>
                    <div v-if="selectedApplication" class="text-sm text-gray-600">
                        {{ getFullName(selectedApplication.profile || selectedApplication) }} - {{
                            selectedApplication.program?.shortname }}
                    </div>
                </div>
            </template>

            <ApprovalWorkflow v-if="selectedApplication" :application="selectedApplication"
                :approval-statuses="approvalStatuses || []" :decline-reasons="declineReasons || {}"
                :show-applicant-name="true" @approved="handleApprovalAction" @declined="handleApprovalAction"
                @conditionalApproval="handleApprovalAction" @refresh="refreshData" />
        </Dialog>

        <!-- Full Profile View Dialog -->
        <Dialog v-model:visible="showProfileDialog" modal header="Profile Details"
            :style="{ width: '90vw', maxWidth: '900px' }" :closable="true">
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-user text-lg text-blue-600"></i>
                    <span class="font-semibold">Profile Details</span>
                </div>
            </template>

            <div v-if="selectedProfile" class="space-y-6">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <i class="pi pi-user text-blue-600"></i>
                        Personal Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600">Full Name</label>
                            <p class="text-sm font-medium">{{ getFullName(selectedProfile) }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Unique ID</label>
                            <p class="text-sm font-medium">{{ selectedProfile.unique_id || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Contact Number</label>
                            <p class="text-sm font-medium">{{ selectedProfile.contact_no || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Email</label>
                            <p class="text-sm font-medium">{{ selectedProfile.email || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Municipality</label>
                            <p class="text-sm font-medium">{{ selectedProfile.municipality || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Barangay</label>
                            <p class="text-sm font-medium">{{ selectedProfile.barangay || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <Divider />

                <!-- Latest Scholarship Information -->
                <div v-if="selectedProfile.latest_scholarship_record">
                    <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <i class="pi pi-bookmark text-blue-600"></i>
                        Latest Scholarship Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600">Program</label>
                            <p class="text-sm font-medium">
                                {{ selectedProfile.latest_scholarship_record.program?.name || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Status</label>
                            <div class="mt-1"
                                v-if="selectedProfile.latest_scholarship_record.scholarship_status !== null">
                                <Chip
                                    :label="getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.scholarship_status)"
                                    :severity="getScholarshipStatusSeverity(selectedProfile.latest_scholarship_record.scholarship_status)" />
                            </div>
                            <p v-else class="text-sm font-medium text-gray-500">N/A</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">School</label>
                            <p class="text-sm font-medium">
                                {{ selectedProfile.latest_scholarship_record.school?.name || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Course</label>
                            <p class="text-sm font-medium">
                                {{ selectedProfile.latest_scholarship_record.course?.name || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Year Level</label>
                            <p class="text-sm font-medium">
                                {{ selectedProfile.latest_scholarship_record.year_level || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Date Applied</label>
                            <p class="text-sm font-medium">
                                {{ formatDate(selectedProfile.latest_scholarship_record.created_at) }}
                            </p>
                        </div>
                        <div v-if="selectedProfile.latest_scholarship_record.scholarship_status_remarks"
                            class="md:col-span-2">
                            <label class="text-xs font-medium text-gray-600">Status Remarks</label>
                            <p class="text-sm font-medium">
                                {{ selectedProfile.latest_scholarship_record.scholarship_status_remarks }}
                            </p>
                        </div>
                    </div>
                </div>

                <Divider />

                <!-- Summary -->
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <i class="pi pi-chart-bar text-blue-600"></i>
                        Summary
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ selectedProfile.total_scholarships || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 mt-1">Total Applications</div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="View Full History" icon="pi pi-history" severity="info" outlined
                        @click="viewFullHistory(selectedProfile)" />
                    <Button label="Close" icon="pi pi-times" severity="secondary" @click="showProfileDialog = false" />
                </div>
            </template>
        </Dialog>

        <!-- Report Modal Placeholder -->
        <Dialog v-model:visible="showReportModal" modal header="Generate Report" :style="{ width: '600px' }">
            <div class="text-center py-8">
                <i class="pi pi-file-pdf text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Report generation functionality coming soon...</p>
            </div>
            <template #footer>
                <Button label="Close" @click="showReportModal = false" />
            </template>
        </Dialog>

        <!-- Export Modal Placeholder -->
        <Dialog v-model:visible="showExportModal" modal header="Export Data" :style="{ width: '600px' }">
            <div class="text-center py-8">
                <i class="pi pi-download text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Data export functionality coming soon...</p>
            </div>
            <template #footer>
                <Button label="Close" @click="showExportModal = false" />
            </template>
        </Dialog>

        <!-- Application Form Modal -->
        <ApplicantFormModal v-model:visible="showAddApplicantModal" :profiles="profiles" @success="refreshData" />

        <!-- Scholar Form Modal -->
        <ScholarFormModal v-model:visible="showAddExistingModal" mode="create" @success="refreshData" />
    </AdminLayout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ApprovalWorkflow from '@/Pages/Scholarship/Components/ApprovalWorkflow.vue';
import moment from 'moment';

// PrimeVue Components
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import DataView from 'primevue/dataview';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Popover from 'primevue/popover';

// Custom Select Components
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

// Modal Components
import ApplicantFormModal from '@/Components/modals/ApplicantFormModal.vue';
import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';

// Props
const props = defineProps({
    profiles: Object,
    filters: Object,
    programs: Array,
    approvalStatuses: Array,
    declineReasons: Object,
    profiles_total: [String, Number],
});

// Get records from URL if not provided by backend
const getRecordsFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const urlRecords = urlParams.get('records');
    return urlRecords ? parseInt(urlRecords) : 10;
};

// Determine initial profile type from URL
const getInitialProfileType = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('profile_type');
    return type || props.filters?.profile_type || 'all';
};

// Filter state
const filter = useForm({
    records: props.filters?.records ? parseInt(props.filters.records) : getRecordsFromUrl(),
    name: props.filters?.name || "",
    program: props.filters?.program || "",
    school: props.filters?.school || "",
    course: props.filters?.course || "",
    municipality: props.filters?.municipality || "",
    year_level: props.filters?.year_level || "",
    approval_status: props.filters?.approval_status || null,
    global_search: props.filters?.global_search || "",
    page: props.filters?.page || 1,
});

// UI State
const showAllFilters = ref(false);
const globalFilter = ref(props.filters?.global_search || '');
const first = ref(0);
const rows = ref(props.filters?.records ? parseInt(props.filters.records) : 10);
const layout = ref('table'); // Default to table view
const actionsPopover = ref();
const profileType = ref(getInitialProfileType());

// Profile Type Options
const profileTypeOptions = ref([
    { label: 'All', value: 'all', icon: 'pi pi-users' },
    { label: 'Existing', value: 'existing', icon: 'pi pi-check-circle' },
    { label: 'Declined', value: 'declined', icon: 'pi pi-times-circle' }
]);

// Layout options
const layoutOptions = ref([
    { icon: 'pi pi-table', value: 'table', tooltip: 'Table View' },
    { icon: 'pi pi-list', value: 'list', tooltip: 'List View' },
    { icon: 'pi pi-th-large', value: 'grid', tooltip: 'Grid View' }
]);

// Approval workflow state
const showApprovalDialog = ref(false);
const selectedApplication = ref(null);

// Profile view state
const showProfileDialog = ref(false);
const selectedProfile = ref(null);

// Modal states
const showReportModal = ref(false);
const showExportModal = ref(false);
const showAddApplicantModal = ref(false);
const showAddExistingModal = ref(false);
const addRecordPopover = ref();

// Computed properties
const approvalStatusOptions = computed(() => [
    { label: 'All Statuses', value: null },
    ...(Array.isArray(props.approvalStatuses) ? props.approvalStatuses : [])
]);

const totalRecords = computed(() => props.profiles_total || 0);

const profilesData = computed(() => {
    return props.profiles?.data || [];
});

// Computed rows for DataView - provides fallback when filter.records is null
const dataViewRows = computed(() => {
    return rows.value || 10;
});

// Helper functions
const getFullName = (profile) => {
    if (!profile) return 'N/A';
    const parts = [
        profile.first_name,
        profile.middle_name,
        profile.last_name,
        profile.extension_name
    ].filter(Boolean);
    return parts.join(' ');
};

const getInitials = (profile) => {
    if (!profile) return '?';
    const firstInitial = profile.first_name?.charAt(0) || '';
    const lastInitial = profile.last_name?.charAt(0) || '';
    return (firstInitial + lastInitial).toUpperCase() || '?';
};

const getApprovalStatusLabel = (status) => {
    if (!Array.isArray(props.approvalStatuses)) return status || 'Unknown';
    const statusObj = props.approvalStatuses.find(s => s.value === status);
    return statusObj?.label || status || 'Unknown';
};

const getApprovalStatusSeverity = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'declined':
            return 'danger';
        case 'auto_approved':
            return 'info';
        case 'conditionally_approved':
            return 'contrast';
        default:
            return 'secondary';
    }
};

const getScholarshipStatusLabel = (status) => {
    const statusMap = {
        0: 'Pending',
        1: 'Active Scholar',
        2: 'Completed',
        3: 'Suspended',
        4: 'Cancelled'
    };
    return statusMap[status] || 'Unknown';
};

const getScholarshipStatusSeverity = (status) => {
    switch (parseInt(status)) {
        case 0:
            return 'secondary'; // Pending
        case 1:
            return 'success'; // Active Scholar
        case 2:
            return 'info'; // Completed
        case 3:
            return 'warn'; // Suspended
        case 4:
            return 'danger'; // Cancelled
        default:
            return 'secondary';
    }
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return moment(date).format('MMM DD, YYYY');
};

// Filter methods
let filterListTimeout = null;

const filterList = (resetToPage1 = false) => {
    // Prepare filter values
    const program = filter.program?.shortname?.toLowerCase() || "";
    const course = filter.course?.shortname?.toLowerCase() || "";
    const municipality = filter.municipality?.name?.toLowerCase() || "";
    const name = filter.name.toLowerCase() || "";
    const school = filter.school?.shortname?.toLowerCase() || "";
    const year_level = filter.year_level?.value?.toLowerCase() || "";
    const global_search = globalFilter.value.toLowerCase() || "";
    const records = filter.records;
    const approval_status = filter.approval_status || "";

    // Reset to page 1 only when filtering/searching, otherwise use current page
    let currentPage = resetToPage1 ? 1 : filter.page;

    const params = {};
    if (program) params.program = program;
    if (course) params.course = course;
    if (school) params.school = school;
    if (municipality) params.municipality = municipality;
    if (name) params.name = name;
    if (year_level) params.year_level = year_level;
    if (global_search) params.global_search = global_search;

    // Handle profile type - only add approval_status if profileType is 'all'
    if (profileType.value === 'existing') {
        params.profile_type = 'existing';
        // Filter for approved statuses (approved, auto_approved, conditionally_approved)
    } else if (profileType.value === 'declined') {
        params.profile_type = 'declined';
        // Filter for declined status
    } else {
        // profileType is 'all'
        params.profile_type = 'all';
        if (approval_status) params.approval_status = approval_status;
    }

    params.records = records; // Always include records to persist pagination
    params.page = currentPage;

    router.get(route('scholarship.profiles'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filter.name = "";
    filter.program = "";
    filter.school = "";
    filter.course = "";
    filter.municipality = "";
    filter.year_level = "";
    filter.approval_status = null;
    filter.records = 10;
    filter.global_search = '';
    filter.page = 1;
    globalFilter.value = '';
    profileType.value = 'all'; // Reset to 'all'

    router.get(route('scholarship.profiles'), {}, {
        replace: true,
        preserveScroll: true,
    });
};

// Action methods
const viewFullProfile = (profile) => {
    selectedProfile.value = profile;
    showProfileDialog.value = true;
};

const viewFullHistory = (profile) => {
    router.visit(route('scholarship.profile.history', profile.profile_id));
};

const reviewApplication = (scholarshipRecord) => {
    selectedApplication.value = scholarshipRecord;
    showApprovalDialog.value = true;
};

const editProfile = (profile) => {
    // Navigate to profile edit page
    router.visit(route('profile.edit', profile.profile_id));
};

const handleApprovalAction = (result) => {
    if (result.success) {
        showApprovalDialog.value = false;
        selectedApplication.value = null;
        refreshData();
    }
};

const openReportModal = () => {
    actionsPopover.value.hide();
    showReportModal.value = true;
};

const openExportModal = () => {
    actionsPopover.value.hide();
    showExportModal.value = true;
};

// Add Record methods
const openAddApplicantModal = () => {
    addRecordPopover.value.hide();
    showAddApplicantModal.value = true;
};

const openAddExistingModal = () => {
    addRecordPopover.value.hide();
    showAddExistingModal.value = true;
};

const refreshData = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
    });
};

// Pagination
const onPageChange = (event) => {
    const page = event.page + 1; // PrimeVue uses 0-based indexing, backend uses 1-based
    filter.page = page;
    filterList(false); // Don't reset to page 1, use current page
};

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        // Focus on global search
    }
};

// Watchers
watch(() => ({
    name: filter.name,
    program: filter.program,
    school: filter.school,
    course: filter.course,
    municipality: filter.municipality,
    year_level: filter.year_level,
    approval_status: filter.approval_status,
    records: filter.records
}), (newFilter, oldFilter) => {
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        filterList(true); // Reset to page 1 when any filter changes
        filterListTimeout = null;
    }, 500);
}, { deep: true });

// Watch for profile type changes
watch(profileType, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Clear approval_status when switching to 'existing' or 'declined'
        if (newValue === 'existing' || newValue === 'declined') {
            filter.approval_status = null;
        }
        filterList(true); // Reset to page 1 when profile type changes
    }
});

watch(globalFilter, (newValue) => {
    filter.global_search = newValue;
    if (filterListTimeout) clearTimeout(filterListTimeout);
    filterListTimeout = setTimeout(() => {
        filterList(true); // Reset to page 1 when searching
        filterListTimeout = null;
    }, 500);
});

watch(() => filter.records, (newValue) => {
    // Keep rows synced with filter.records, allowing null
    rows.value = newValue ? parseInt(newValue) : null;
}, { immediate: true });

watch(() => filter.page, (newPage) => {
    first.value = (newPage - 1) * dataViewRows.value;
}, { immediate: true });

watch(() => dataViewRows.value, () => {
    first.value = (filter.page - 1) * dataViewRows.value;
});

// Lifecycle
onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    globalFilter.value = props.filters?.global_search || '';
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
/* Toolbar Grid Layout */
:deep(.p-toolbar) {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1rem;
    align-items: center;
}

:deep(.p-toolbar-start) {
    justify-self: start;
}

:deep(.p-toolbar-center) {
    justify-self: center;
}

:deep(.p-toolbar-end) {
    justify-self: end;
}

:deep(.compact-table .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.75rem;
}

:deep(.compact-table .p-datatable-thead > tr > th) {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

:deep(.compact-table .p-chip) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

:deep(.compact-table .p-button) {
    padding: 0.25rem 0.5rem;
}
</style>