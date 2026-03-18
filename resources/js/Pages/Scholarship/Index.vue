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
                        <div class="join border border-gray-300 rounded-lg">
                            <button v-for="option in profileTypeOptions" :key="option.value"
                                :class="['join-item btn btn-sm', profileType === option.value ? 'btn-active' : 'btn-ghost']"
                                @click="profileType = option.value">
                                <i :class="option.icon" class="mr-1"></i>
                                {{ option.label }}
                            </button>
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-3 items-center">
                        <Button icon="pi pi-plus" @click="addRecordPopover.toggle($event)" severity="success"
                            v-tooltip.bottom="'Add New Record'" v-if="hasPermission('applicants.create')" />
                        <Popover ref="addRecordPopover">
                            <div class="flex flex-col gap-2 w-48">
                                <!-- <Button @click="openAddApplicantModal" label="Add Applicant" icon="pi pi-user-plus"
                                    severity="success" outlined class="justify-start" /> -->
                                <Button @click="openAddExistingModal" label="Add Existing" icon="pi pi-user-edit"
                                    severity="info" outlined class="justify-start" />
                            </div>
                        </Popover>
                        <!-- <Button icon="pi pi-refresh" @click="refreshData" severity="secondary" outlined
                            v-tooltip.bottom="'Refresh'" /> -->
                        <Button icon="pi pi-print" @click="actionsPopover.toggle($event)" severity="secondary"
                            v-tooltip.bottom="'Reports & Export'" v-if="hasPermission('reports.view')" />
                        <Popover ref="actionsPopover">
                            <div class="flex flex-col gap-2 w-48">
                                <Button @click="openReportModal" label="Generate Report" icon="pi pi-file-pdf"
                                    severity="secondary" outlined class="justify-start"
                                    v-if="hasPermission('reports.generate')" />
                                <Button @click="openExportModal" label="Export Data" icon="pi pi-download"
                                    severity="secondary" outlined class="justify-start"
                                    v-if="hasPermission('reports.generate')" />
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
                            <Button label="More Filters" icon="pi pi-filter" severity="secondary" size="small" outlined
                                @click="openDrawer()" />
                        </div>
                        <div class="flex items-center gap-3">
                            <Button severity="secondary" outlined size="small" icon="pi pi-history"
                                @click="clearAllFilters" v-tooltip.bottom="'Clear Filters'" />
                        </div>
                    </div>

                    <!-- Basic Filters Row -->
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:gap-8">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                            <ProgramSelect v-model="filter.program" label="shortname" custom-placeholder="All Programs"
                                size="small" class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                            <CourseSelect v-model="filter.course" label="name" custom-placeholder="All Courses"
                                size="small" class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                            <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                                size="small" class="w-full" />
                        </div>
                        <!-- Only show Unified Status filter when profileType is 'all' -->
                        <div class="flex flex-col" v-if="profileType === 'all'">
                            <label class="text-xs font-medium text-gray-600 mb-1">Status</label>
                            <Select v-model="filter.unified_status" :options="unifiedStatusOptions" optionLabel="label"
                                optionValue="value" placeholder="All Statuses" showClear class="w-full" size="small" />
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Active Filter Tags -->
            <div v-if="activeFilterTags.length" class="flex flex-wrap items-center gap-2 mt-2">
                <span class="text-xs text-gray-500">Active Filters:</span>
                <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="info" rounded class="cursor-pointer"
                    @click="removeFilter(tag.key)">
                    <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    <i class="pi pi-times ml-1" style="font-size: 0.6rem"></i>
                </Tag>
            </div>

            <!-- Filter Drawer -->
            <Drawer v-model:visible="showFilterDrawer" header="All Filters" position="right" class="!w-[600px]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                        <ProgramSelect v-model="drawerFilter.program" label="shortname"
                            custom-placeholder="All Programs" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                        <CourseSelect v-model="drawerFilter.course" label="name" custom-placeholder="All Courses"
                            size="small" class="w-full" :scholarship-program-id="drawerFilter.program?.id" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                        <SchoolSelect v-model="drawerFilter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" class="w-full" :multiple="false" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                        <YearLevelSelect v-model="drawerFilter.year_level" custom-placeholder="All Year Levels"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                        <Select v-model="drawerFilter.academic_year" :options="academicYearOptions" optionLabel="label"
                            optionValue="value" placeholder="All Years" showClear size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Term</label>
                        <TermSelect v-model="drawerFilter.term" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                        <MunicipalitySelect v-model="drawerFilter.municipality" custom-placeholder="All Municipalities"
                            size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Barangay</label>
                        <BarangaySelect v-model="drawerFilter.barangay" :municipality-id="drawerFilter.municipality?.id"
                            custom-placeholder="All Barangays" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Grant Provision</label>
                        <Select v-model="drawerFilter.grant_provision" :options="grantProvisionOptions"
                            placeholder="All Provisions" size="small" class="w-full" showClear />
                    </div>
                    <div class="flex flex-col" v-if="profileType === 'all'">
                        <label class="text-xs font-medium text-gray-600 mb-1">Status</label>
                        <Select v-model="drawerFilter.unified_status" :options="unifiedStatusOptions"
                            optionLabel="label" optionValue="value" placeholder="All Statuses" showClear size="small"
                            class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Contract</label>
                        <Select v-model="drawerFilter.contract_status" :options="attachmentStatusOptions"
                            placeholder="All" size="small" class="w-full" showClear optionLabel="label"
                            optionValue="value" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Voucher</label>
                        <Select v-model="drawerFilter.voucher_status" :options="attachmentStatusOptions"
                            placeholder="All" size="small" class="w-full" showClear optionLabel="label"
                            optionValue="value" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <Button severity="secondary" outlined size="small" icon="pi pi-history" label="Clear"
                        @click="clearDrawerFilters" />
                    <Button label="Apply" icon="pi pi-filter-fill" severity="info" size="small"
                        @click="applyDrawerFilters" />
                </div>
            </Drawer>

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
                        <div class="flex">
                            <span class="text-sm opacity-60" v-if="simpleView">Right click on profile row to show
                                context
                                menu</span>
                        </div>
                        <div class="flex items-center justify-center gap-4">
                            <div class="text-sm text-gray-600 flex items-center justify-center gap-2">
                                <RecordsSelect v-model="records" label="label" class="w-28" size="small" />
                                <span>/ <strong>{{ totalRecords }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="simpleView" inputId="simpleViewToggle" binary />
                                <label for="simpleViewToggle" class="text-xs text-gray-600 cursor-pointer"
                                    @click="toggleSimpleView">Simple
                                    View</label>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable View -->
                    <DataTable :value="profilesData" paginator :rows="dataViewRows" :totalRecords="totalRecords"
                        :first="first" @page="onPageChange" :lazy="true"
                        paginatorTemplate="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                        :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
                        :rowHover="true" stripedRows class="compact-table"
                        @rowContextmenu="(event) => openContextMenu(event.originalEvent, event.data)" contextMenu
                        :globalFilter="globalFilter">

                        <Column field="unique_id" header="ID" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <div class="w-[40px]">
                                        <Avatar :label="getInitials(slotProps.data)" size="normal" shape="circle"
                                            class="bg-gradient-to-br from-blue-500 to-blue-600 text-white" />
                                    </div>
                                    <div>
                                        <div as="button"
                                            class="font-bold text-sm text-sky-700 underline underline-offset-2 cursor-pointer hover:text-blue-800"
                                            @click="viewFullProfile(slotProps.data)"
                                            @contextmenu.prevent="openContextMenu($event, slotProps.data)">{{
                                                getFullName(slotProps.data) }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-xs text-gray-500">{{ slotProps.data.unique_id || 'N/A' }}
                                            </div>
                                            <Badge v-if="slotProps.data.has_contract"
                                                :value="`Contract (${slotProps.data.contract_count})`"
                                                severity="success" size="small"
                                                v-tooltip.bottom="'Contract attachment uploaded'" />
                                            <Badge v-if="slotProps.data.has_voucher"
                                                :value="`Voucher (${slotProps.data.voucher_count})`" severity="info"
                                                size="small"
                                                v-tooltip.bottom="'Disbursement/Voucher attachment uploaded'" />
                                        </div>
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
                                    <div class="text-xs text-gray-600 truncate"
                                        v-if="slotProps.data.latest_scholarship_record.year_level">
                                        {{ slotProps.data.latest_scholarship_record.year_level }} Year
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400">N/A</div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'pending'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Awaiting review'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'approved'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Complete approval in Interviewed Applicants'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'denied'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Application has been denied'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'active'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Enrolled as scholar'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'completed'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Scholarship completed'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record && slotProps.data.latest_scholarship_record.unified_status === 'unknown'"
                                    :style="getStatusStyle(slotProps.data.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Status unknown'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    {{
                                        getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="slotProps.data.latest_scholarship_record"
                                    :style="getStatusStyle('unknown')"
                                    v-tooltip="'Unrecognized status - treating as unknown'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                    Unknown
                                </div>
                                <div v-else
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block bg-gray-100 text-gray-800 border-gray-300">
                                    No Record
                                </div>
                            </template>
                        </Column>

                        <Column field="grant_provision" header="Grant Provision" style="min-width: 160px;"
                            v-if="!simpleView">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record" class="flex items-center gap-2">
                                    <Chip v-if="slotProps.data.latest_scholarship_record.grant_provision"
                                        :label="slotProps.data.latest_scholarship_record.grant_provision" size="small"
                                        class="font-medium cursor-pointer"
                                        @click="hasPermission('applicants.edit') && openGrantProvisionDialog(slotProps.data)" />
                                    <Button v-else-if="hasPermission('applicants.edit')" icon="pi pi-plus" label="Set"
                                        size="small" severity="secondary" text
                                        @click="openGrantProvisionDialog(slotProps.data)" />
                                    <Button
                                        v-if="slotProps.data.latest_scholarship_record.grant_provision && hasPermission('applicants.edit')"
                                        icon="pi pi-pencil" size="small" severity="secondary" text rounded
                                        @click="openGrantProvisionDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                </div>
                                <span v-else class="text-sm text-gray-400">N/A</span>
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 120px;" v-if="!simpleView">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" size="small" severity="info" outlined rounded
                                        v-tooltip.top="'View'" @click="viewFullProfile(slotProps.data)" />
                                    <Button icon="pi pi-trash" size="small" severity="danger" outlined rounded
                                        v-tooltip.top="'Soft Delete (Admin Only)'"
                                        @click="confirmDeleteProfile(slotProps.data)"
                                        :disabled="!hasRole('administrator')"
                                        :class="{ 'opacity-50': !hasRole('administrator') }" />
                                </div>
                            </template>
                        </Column> <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-users text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg">No profiles found</p>
                                <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataTable>
                </Panel>
            </div>
        </div>

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
                            <div class="mt-1" v-if="selectedProfile.latest_scholarship_record.unified_status">
                                <div v-if="selectedProfile.latest_scholarship_record.unified_status === 'pending'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Awaiting review'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'approved'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Complete approval in Interviewed Applicants'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'denied'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Application has been denied'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'active'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Enrolled as scholar'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'completed'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Scholarship completed'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'unknown'"
                                    :style="getStatusStyle(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Status unknown'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
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
                    <Button label="Close" icon="pi pi-times" severity="secondary" @click="showProfileDialog = false" />
                </div>
            </template>
        </Dialog>

        <!-- Generate Report Modal -->
        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event" />

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

        <!-- Grant Provision Update Dialog -->
        <Dialog v-model:visible="showGrantProvisionDialog" modal header="Update Grant Provision"
            :style="{ width: '500px' }">
            <div class="space-y-4" v-if="selectedProfileForGrant">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scholar Name</label>
                    <p class="text-base font-semibold">{{ getFullName(selectedProfileForGrant) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scholarship Record</label>
                    <Select v-model="grantProvisionForm.scholarship_record_id" :options="scholarshipRecordOptions"
                        optionLabel="label" optionValue="value" placeholder="Select scholarship record" class="w-full"
                        @change="onScholarshipRecordChange" />
                </div>
                <div v-if="grantProvisionForm.scholarship_record_id">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grant Provision</label>
                    <Select v-model="grantProvisionForm.grant_provision" :options="grantProvisionOptions"
                        placeholder="Select provision type" class="w-full" showClear />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showGrantProvisionDialog = false" outlined />
                <Button label="Update" @click="updateGrantProvision" :loading="grantProvisionForm.processing"
                    :disabled="!grantProvisionForm.scholarship_record_id" />
            </template>
        </Dialog>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:visible="showDeleteConfirmDialog" modal header="Confirm Soft Delete"
            :style="{ width: '500px' }">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-orange-500"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Soft Delete Profile</p>
                        <p class="text-sm text-gray-600">This action can be undone from the Deleted Records page</p>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded p-3">
                    <p class="text-sm text-blue-800">
                        <strong>Profile:</strong> {{ profileToDelete ? getFullName(profileToDelete) : 'N/A' }}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteConfirmDialog = false" outlined />
                <Button label="Soft Delete" severity="danger" @click="deleteProfile" />
            </template>
        </Dialog>

        <!-- Application Form Modal -->
        <ApplicantFormModal v-model:visible="showAddApplicantModal" :profiles="profiles" @success="refreshData" />

        <!-- Scholar Form Modal (Create) -->
        <ScholarFormModal v-model:visible="showAddExistingModal" mode="create" @success="refreshData" />

        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body" />
    </AdminLayout>
</template>

<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, inject } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import moment from 'moment';
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { useFilterManager } from '@/composables/useFilterManager';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';


// Custom Select Components
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import BarangaySelect from '@/Components/selects/BarangaySelect.vue';
import RecordsSelect from '@/Components/selects/RecordsSelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';

// Modal Components
import ApplicantFormModal from '@/Components/modals/ApplicantFormModal.vue';
import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';
import GenerateReportModal from './Modal/GenerateReportModal.vue';

// Props
const props = defineProps({
    profiles: Object,
    filters: Object,
    programs: Array,
    approvalStatuses: Array,
    declineReasons: Object,
    profiles_total: [String, Number],
});

// Determine initial profile type from URL
const getInitialProfileType = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('profile_type');
    return type || props.filters?.profile_type || 'all';
};

// Page-specific state
const profileType = ref(getInitialProfileType());

// Filter management via composable
const {
    filters: filter,
    globalFilter,
    records,
    rows,
    first,
    totalRecords,
    search: triggerSearch,
    clear: clearFilters,
    onPageChange,
} = useFilterManager({
    routeName: 'scholarship.profiles',
    props,
    filterPropName: 'filters',
    filterDefs: [
        { key: 'name', type: 'text', default: '' },
        { key: 'program', type: 'select', default: '', extract: v => v?.shortname?.toLowerCase() },
        { key: 'school', type: 'select', default: '', extract: v => v?.shortname?.toLowerCase() },
        { key: 'course', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'year_level', type: 'select', default: '', extract: v => v?.value?.toLowerCase() },
        { key: 'academic_year', type: 'text', default: '' },
        { key: 'term', type: 'select', default: '', extract: v => v?.value?.toLowerCase() },
        { key: 'municipality', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'barangay', type: 'select', default: '', extract: v => v?.name?.toLowerCase() },
        { key: 'grant_provision', type: 'text', default: null },
        { key: 'unified_status', type: 'text', default: null },
        { key: 'contract_status', type: 'text', default: null },
        { key: 'voucher_status', type: 'text', default: null },
    ],
    beforeSearch(params, filterValues) {
        // Handle profile type
        if (profileType.value === 'existing') {
            params.profile_type = 'existing';
        } else if (profileType.value === 'pending') {
            params.profile_type = 'pending';
        } else if (profileType.value === 'declined') {
            params.profile_type = 'declined';
        } else {
            params.profile_type = 'all';
        }

        // Handle attachment filter values (three-state: null, 'with', 'without')
        if (params.contract_status && !['with', 'without'].includes(params.contract_status)) {
            delete params.contract_status;
        }
        if (params.voucher_status && !['with', 'without'].includes(params.voucher_status)) {
            delete params.voucher_status;
        }
    },
});

// Keep clearFilters also resetting profileType
const originalClearFilters = clearFilters;
const clearAllFilters = () => {
    profileType.value = 'all';
    originalClearFilters();
};

// Computed: active filter tags for display
const activeFilterTags = computed(() => {
    const tags = [];
    const f = filter.value;
    const labelMap = {
        name: 'Name',
        program: 'Program',
        school: 'School',
        course: 'Course',
        municipality: 'Municipality',
        barangay: 'Barangay',
        year_level: 'Year Level',
        academic_year: 'Academic Year',
        term: 'Term',
        grant_provision: 'Grant Provision',
        unified_status: 'Status',
        contract_status: 'Contract',
        voucher_status: 'Voucher',
    };
    for (const [key, label] of Object.entries(labelMap)) {
        const val = f[key];
        if (!val) continue;
        let display;
        if (typeof val === 'object') {
            display = val.shortname || val.name || val.value || JSON.stringify(val);
        } else {
            display = String(val);
        }
        tags.push({ key, label, display });
    }
    return tags;
});

const removeFilter = (key) => {
    const selectKeys = ['grant_provision', 'unified_status', 'contract_status', 'voucher_status', 'academic_year', 'term'];
    filter.value[key] = selectKeys.includes(key) ? null : '';
    triggerSearch();
};

// Auto-trigger search when basic filters change
watch(
    () => [filter.value.program, filter.value.course, filter.value.year_level],
    () => { triggerSearch(); },
);

// Filter drawer state
const showFilterDrawer = ref(false);
const drawerFilter = ref({});
const drawerFilterKeys = ['program', 'course', 'school', 'municipality', 'barangay', 'year_level', 'academic_year', 'term', 'grant_provision', 'unified_status', 'contract_status', 'voucher_status'];

const openDrawer = () => {
    const snapshot = {};
    for (const key of drawerFilterKeys) {
        const val = filter.value[key];
        snapshot[key] = val instanceof Date ? new Date(val) : val;
    }
    drawerFilter.value = snapshot;
    showFilterDrawer.value = true;
};

const applyDrawerFilters = () => {
    for (const key of drawerFilterKeys) {
        filter.value[key] = drawerFilter.value[key];
    }
    triggerSearch();
    showFilterDrawer.value = false;
};

const clearDrawerFilters = () => {
    const nullKeys = ['grant_provision', 'unified_status', 'contract_status', 'voucher_status', 'academic_year', 'term'];
    for (const key of drawerFilterKeys) {
        drawerFilter.value[key] = nullKeys.includes(key) ? null : '';
    }
};

// UI State
const actionsPopover = ref();
const simpleView = ref(localStorage.getItem('scholarProfileSimpleView') === 'true' || false);
const contextMenu = ref();
const selectedProfileForContext = ref(null);

// Permission composable
const { hasPermission } = usePermission();

// Helper to check user role
const hasRole = (role) => {
    try {
        const page = usePage();
        const user = page.props?.auth?.user;
        if (!user) return false;

        // Check roles array
        if (Array.isArray(user.roles)) {
            return user.roles.some(r => r.name === role || r === role);
        }

        // Fallback: check if role is in user directly
        return user[role] === true || false;
    } catch (error) {
        console.error('Error checking role:', error);
        return false;
    }
};

// Grant Provision Options
const grantProvisionOptions = ref(['Matriculation', 'RLE', 'Tuition', 'RLE and Tuition']);

// Academic Year Options - generate current year and previous years with all ranges first, then single years
const academicYearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    // Add all range year options first
    for (let i = currentYear; i >= currentYear - 10; i--) {
        years.push({ label: `${i}-${i + 1}`, value: `${i}-${i + 1}` });
    }
    // Add all single year options
    for (let i = currentYear; i >= currentYear - 10; i--) {
        years.push({ label: i.toString(), value: i.toString() });
    }
    return years;
});

// Profile Type Options
const profileTypeOptions = ref([
    { label: 'All', value: 'all', icon: 'pi pi-users' },
    { label: 'Existing', value: 'existing', icon: 'pi pi-check-circle' },
    { label: 'Pending', value: 'pending', icon: 'pi pi-clock' },
    { label: 'Declined', value: 'declined', icon: 'pi pi-times-circle' }
]);

// Profile view state
const showProfileDialog = ref(false);
const selectedProfile = ref(null);

// Modal states
const showReportModal = ref(false);
const showExportModal = ref(false);
const showAddApplicantModal = ref(false);
const showAddExistingModal = ref(false);
const addRecordPopover = ref();

// Grant Provision Dialog
const showGrantProvisionDialog = ref(false);
const selectedProfileForGrant = ref(null);
const scholarshipRecordOptions = ref([]);
const grantProvisionForm = useForm({
    scholarship_record_id: null,
    grant_provision: null,
});

// Delete Confirmation Dialog
const showDeleteConfirmDialog = ref(false);
const profileToDelete = ref(null);

// Inject the refresh function from AdminLayout
const refreshActivityLogs = inject('refreshActivityLogs', null);
const { statusOptions, getStatusLabel, getStatusSeverity, getStatusStyle } = useScholarshipStatus();

const unifiedStatusOptions = computed(() => [
    { label: 'All Statuses', value: null },
    ...statusOptions.value
]);

const attachmentStatusOptions = computed(() => [
    { label: 'All', value: null },
    { label: 'With Attachment', value: 'with' },
    { label: 'Without Attachment', value: 'without' }
]);

const profilesData = computed(() => {
    return props.profiles?.data || [];
});

// Computed rows for DataView - provides fallback when records is null
const dataViewRows = computed(() => {
    return rows.value || 10;
});

const contextMenuItems = computed(() => [
    {
        label: 'View Profile',
        icon: 'pi pi-eye',
        command: () => {
            if (selectedProfileForContext.value) {
                viewFullProfile(selectedProfileForContext.value);
            }
        }
    },
    {
        separator: true,
        visible: () => hasPermission('applicants.edit')
    },
    {
        label: 'Grant Provision',
        icon: 'pi pi-bookmark',
        command: () => {
            if (selectedProfileForContext.value && hasPermission('applicants.edit')) {
                openGrantProvisionDialog(selectedProfileForContext.value);
            }
        },
        visible: () => hasPermission('applicants.edit') && selectedProfileForContext.value?.latest_scholarship_record
    },
    {
        separator: true,
        visible: () => hasRole('administrator')
    },
    {
        label: 'Soft Delete',
        icon: 'pi pi-trash',
        command: () => {
            if (selectedProfileForContext.value && hasRole('administrator')) {
                confirmDeleteProfile(selectedProfileForContext.value);
            }
        },
        visible: () => hasRole('administrator')
    }
]);

// Helper functions
const getFullName = (profile) => {
    if (!profile) return 'N/A';

    // Format: Last, First Middle Extension
    const lastName = profile.last_name || '';
    const firstName = profile.first_name || '';
    const middleName = profile.middle_name || '';
    const extensionName = profile.extension_name || '';

    // Build the first part (first middle extension)
    const firstPart = [firstName, middleName, extensionName].filter(Boolean).join(' ');

    // Combine with last name
    if (lastName && firstPart) {
        return `${lastName}, ${firstPart}`;
    } else if (lastName) {
        return lastName;
    } else if (firstPart) {
        return firstPart;
    }

    return 'N/A';
};

const getInitials = (profile) => {
    if (!profile) return '?';
    const firstInitial = profile.first_name?.charAt(0) || '';
    const lastInitial = profile.last_name?.charAt(0) || '';
    return (firstInitial + lastInitial).toUpperCase() || '?';
};

const getProfileTypeLabel = (type) => {
    const option = profileTypeOptions.value.find(opt => opt.value === type);
    return option?.label || 'All';
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
    return getStatusLabel(status);
};

const getScholarshipStatusSeverity = (status) => {
    return getStatusSeverity(status);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return moment(date).format('MMM DD, YYYY');
};

// filterList and clearFilters are provided by useFilterManager composable

// Action methods
const viewFullProfile = (profile) => {
    // Save current filters to localStorage before navigating
    const filters = {
        unified_status: route().params?.unified_status || null,
        profile_type: route().params?.profile_type || null,
        name: route().params?.name || null,
        program: route().params?.program || null,
        school: route().params?.school || null,
        course: route().params?.course || null,
        municipality: route().params?.municipality || null,
        year_level: route().params?.year_level || null,
        global_search: route().params?.global_search || null,
        grant_provision: route().params?.grant_provision || null,
        contract_status: route().params?.contract_status || null,
        voucher_status: route().params?.voucher_status || null,
        records: route().params?.records || 10,
        page: route().params?.page || 1
    };
    localStorage.setItem('scholarshipProfileFilters', JSON.stringify(filters));
    router.visit(route('scholarship.profile.show', profile.profile_id));
};

const confirmDeleteProfile = (profile) => {
    profileToDelete.value = profile;
    showDeleteConfirmDialog.value = true;
};

const deleteProfile = async () => {
    if (!profileToDelete.value) return;

    const profile = profileToDelete.value;
    showDeleteConfirmDialog.value = false;

    try {
        const response = await axios.delete(route('applicants.destroy', profile.profile_id));
        toast.success('Profile soft deleted successfully. You can restore it from the Deleted Records page.');
        refreshData();
    } catch (error) {
        console.error('Delete profile error:', error);
        console.error('Response:', error.response?.data);
        const errorMsg = error.response?.data?.message || error.message || 'Failed to delete profile';
        toast.error(errorMsg);
    } finally {
        profileToDelete.value = null;
    }
};

const openGrantProvisionDialog = (profile) => {
    selectedProfileForGrant.value = profile;

    // Fetch all scholarship records for this profile
    axios.get(route('scholarship.profile.records', profile.profile_id))
        .then(response => {
            const records = response.data;
            scholarshipRecordOptions.value = records.map(record => ({
                value: record.id,
                label: `${record.program?.shortname || 'N/A'} - ${record.course?.shortname || 'N/A'} - ${record.year_level ? record.year_level + ' Year' : 'N/A'} (${getScholarshipStatusLabel(record.unified_status)})${record.grant_provision ? ' - ' + record.grant_provision : ''}`,
                grant_provision: record.grant_provision
            }));

            // Pre-select the latest record
            if (records.length > 0) {
                const latestRecord = records[0];
                grantProvisionForm.scholarship_record_id = latestRecord.id;
                grantProvisionForm.grant_provision = latestRecord.grant_provision;
            }
        })
        .catch(error => {
            console.error('Failed to fetch scholarship records:', error);
        });

    showGrantProvisionDialog.value = true;
};

const onScholarshipRecordChange = () => {
    // Update grant provision when record changes
    const selected = scholarshipRecordOptions.value.find(
        opt => opt.value === grantProvisionForm.scholarship_record_id
    );
    if (selected) {
        grantProvisionForm.grant_provision = selected.grant_provision;
    }
};

const updateGrantProvision = () => {
    if (!grantProvisionForm.scholarship_record_id) return;

    grantProvisionForm.put(
        route('scholarship-record.update-grant-provision', grantProvisionForm.scholarship_record_id),
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                showGrantProvisionDialog.value = false;
                selectedProfileForGrant.value = null;
                scholarshipRecordOptions.value = [];
                grantProvisionForm.reset();
                refreshData();
                if (refreshActivityLogs) refreshActivityLogs();
            },
            onError: (errors) => {
                console.error('Failed to update grant provision:', errors);
            }
        }
    );
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

const toggleSimpleView = () => {
    simpleView.value = !simpleView.value;
};

const openContextMenu = (event, profile) => {
    selectedProfileForContext.value = profile;
    contextMenu.value.show(event);
};

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (e.ctrlKey && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        // Focus on global search
    }
};

// triggerSearch and clearFilters are provided by useFilterManager composable

// Watch for profile type changes
watch(profileType, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Clear unified_status when switching to 'existing', 'pending' or 'declined'
        if (newValue === 'existing' || newValue === 'pending' || newValue === 'declined') {
            filter.unified_status = null;
        }
        triggerSearch(); // Reset to page 1 when profile type changes
    }
});

watch(simpleView, (newValue) => {
    localStorage.setItem('scholarProfileSimpleView', newValue.toString());
});

// Lifecycle
onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    // Initialize profileType from URL or props
    const initialType = getInitialProfileType();
    profileType.value = initialType;
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
