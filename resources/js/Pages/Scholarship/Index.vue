<template>

    <Head title="Profiles" />

    <AdminLayout>
        <div class="ios-settings-form">
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] !rounded-4xl !px-4 sm:!px-6 lg:!px-8 scholarship-toolbar">
                <template #start>
                    <div class="flex min-w-0 items-center gap-3 scholarship-toolbar__brand">
                        <AppIcon name="users" :size="32" class="text-indigo-500" />
                        <div class="min-w-0">
                            <h1 class="text-xl font-bold text-gray-700 sm:text-2xl">Scholarship Profiles</h1>
                            <p class="text-sm text-gray-600">Browse and manage scholarship applicant profiles</p>
                        </div>
                    </div>
                </template>

                <template #center>
                    <!-- Program tabs — the primary filter, front and center -->
                    <div class="flex flex-wrap items-center justify-center gap-2" role="tablist"
                        aria-label="Scholarship programs">
                        <button type="button" role="tab" :aria-selected="!filter.program"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="!filter.program
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(null)">
                            <AppIcon name="layers" :size="14" />
                            All Programs
                        </button>
                        <button v-for="program in programs" :key="program.id" type="button" role="tab"
                            :aria-selected="isProgramTabActive(program)"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="isProgramTabActive(program)
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(program)">
                            <span class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: program.bg_color || getProgramColor(program.id) }"></span>
                            {{ program.shortname || program.name }}
                        </button>
                    </div>
                </template>
                <template #end>
                    <div class="flex flex-wrap items-center justify-end gap-3 scholarship-toolbar__actions">
                        <AppButton icon="plus" @click="addRecordPopover.toggle($event)" severity="success"
                            v-tooltip.bottom="'Add New Record'" rounded
                            outlined />
                        <Popover ref="addRecordPopover">
                            <div class="flex flex-col gap-2 w-48">
                                <AppButton @click="openAddActiveModal" label="Add Active" icon="user-edit"
                                    severity="info" outlined class="justify-start" />
                            </div>
                        </Popover>
                        <!-- Export the full filtered set. Export Selected lives in the
                             selected-rows toolbar above the table once rows are checked. -->
                        <AppButton v-if="hasPermission('reports.view')" icon="download" label="Export All"
                            @click="openExportAll" severity="info" rounded outlined :loading="reportLoading" />
                    </div>
                </template>
            </Toolbar>

            <!-- Filter Drawer -->
            <IosModal v-model:visible="showFilterDrawer" title="All Filters"
                width="calc(100vw - 1rem)" max-width="min(600px, calc(100vw - 1rem))">
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                        <ProgramSelect v-model="drawerFilter.program" label="shortname"
                            custom-placeholder="All Programs" size="small" class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Course</label>
                        <CourseSelect v-model="drawerFilter.course" label="name" custom-placeholder="All Courses"
                            size="small" class="w-full" :scholarship-program-id="drawerFilter.program?.id"
                            :load-all-when-no-program="true" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">School</label>
                        <SchoolSelect v-model="drawerFilter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" class="w-full" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Year Level</label>
                        <YearLevelSelect v-model="drawerFilter.year_level" custom-placeholder="All Year Levels"
                            size="small" class="w-full" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Review Status</label>
                        <Select v-model="drawerFilter.needs_term_review" :options="legacyTermReviewOptions"
                            optionLabel="label" optionValue="value" placeholder="All" showClear size="small"
                            class="w-full" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                        <MultiSelect v-model="drawerFilter.academic_year" :options="academicYearOptions" optionLabel="label"
                            optionValue="value" placeholder="All Years" showClear showSelectAll size="small"
                            class="w-full" :maxSelectedLabels="1" :selectedItemsLabel="'{0} selected'" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Term</label>
                        <TermSelect v-model="drawerFilter.term" size="small" class="w-full" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Municipality</label>
                        <MunicipalitySelect v-model="drawerFilter.municipality" custom-placeholder="All Municipalities"
                            size="small" class="w-full" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Barangay</label>
                        <BarangaySelect v-model="drawerFilter.barangay" :municipality-id="drawerFilterMunicipalityId"
                            custom-placeholder="All Barangays" size="small" class="w-full" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Grant Provision</label>
                        <Select v-model="drawerFilter.grant_provision" :options="grantProvisionOptions"
                            optionLabel="label" optionValue="value" placeholder="All Provisions" size="small"
                            class="w-full" showClear />
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
                    <div class="flex flex-col col-span-2">
                        <label class="text-xs font-medium text-gray-600 mb-1">Encoded By</label>
                        <InputText v-model="drawerFilter.encoded_by" placeholder="Type encoder name..." size="small" class="w-full" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t mb-4">
                    <AppButton severity="secondary" outlined size="small" icon="history" label="Clear"
                        @click="clearDrawerFilters" />
                    <AppButton label="Apply" icon="filter-fill" severity="info" size="small"
                        @click="applyDrawerFilters" />
                </div>
            </IosModal>

            <!-- Profiles Panel (filters + dataview merged) -->
            <Panel class="!rounded-4xl overflow-hidden mt-4">
                <!-- Status Tabs -->
                <div class="mb-6 -mt-2 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1" role="tablist" aria-label="Profile status views">
                        <button v-for="tab in statusTabs" :key="tab.value" type="button" role="tab"
                            :aria-selected="activeTab === tab.value"
                            class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                            :class="activeTab === tab.value
                                ? 'border-blue-500 font-semibold text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            @click="selectTab(tab.value)">
                            <div class="flex items-center gap-2">
                                <AppIcon :name="tab.icon" :size="14" />
                                <span>{{ tab.label }}</span>
                            </div>
                        </button>

                        <!-- Other Records — reveals the remaining statuses -->
                        <button type="button" role="tab" :aria-selected="isOtherActive"
                            class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                            :class="isOtherActive
                                ? 'border-blue-500 font-semibold text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            @click="otherRecordsPopover.toggle($event)">
                            <div class="flex items-center gap-2">
                                <AppIcon name="layers" :size="14" />
                                <span>{{ otherButtonLabel }}</span>
                                <AppIcon name="chevron-down" :size="14" />
                            </div>
                        </button>
                        <Popover ref="otherRecordsPopover">
                            <div class="flex flex-col gap-1 w-48">
                                <button v-for="option in otherStatusOptions" :key="option.value" type="button"
                                    class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-gray-100 dark:hover:bg-white/10"
                                    :class="activeTab === option.value ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-200' : 'text-gray-700 dark:text-gray-200'"
                                    @click="selectOtherStatus(option.value)">
                                    <span>{{ option.label }}</span>
                                    <AppIcon v-if="activeTab === option.value" name="check" :size="14" />
                                </button>
                            </div>
                        </Popover>
                    </div>
                    <div class="flex items-center gap-3">
                        <AppButton icon="settings-2" severity="secondary" rounded text size="large"
                            v-tooltip.bottom="'Display Settings'" @click="showDisplaySettingsModal = true" />
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <InputGroup class="w-full sm:w-64">
                        <InputGroupAddon>
                            <AppIcon name="search" :size="14" class="text-gray-400" />
                        </InputGroupAddon>
                        <InputText v-model="globalFilter" placeholder="Search..." size="small"
                            @keyup.enter="triggerSearch()" />
                    </InputGroup>
                    <div class="flex flex-col">
                        <SchoolSelect v-model="filter.school" label="shortname" custom-placeholder="All Schools"
                            size="small" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <MunicipalitySelect v-model="filter.municipality" custom-placeholder="All Municipalities"
                            size="small" :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <CourseSelect v-model="filter.course" label="name" custom-placeholder="All Courses" size="small"
                            :scholarship-program-id="filter.program?.id" :load-all-when-no-program="true"
                            :multiple="true" />
                    </div>
                    <div class="flex flex-col">
                        <YearLevelSelect v-model="filter.year_level" custom-placeholder="All Year Levels"
                            size="small" :multiple="true" />
                    </div>
                    <AppIcon name="sliders-horizontal" :size="24"
                        class="text-gray-400 cursor-pointer self-center" @click="openDrawer()"
                        v-tooltip.bottom="'More Filters'" />
                    <AppButton v-if="activeFilterTags.length" icon="times" severity="danger" text rounded size="small"
                        @click="clearFilters" v-tooltip.bottom="'Clear Filters'" />
                </div>

                <!-- Active Filter Tags -->
                <div v-if="activeFilterTags.length" class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs text-gray-500">Active Filters:</span>
                    <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                        <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    </Tag>
                </div>

                <!-- Selected rows toolbar -->
                <div v-if="selectedRows.length > 0"
                    class="mb-4 rounded-3xl border border-yellow-200 bg-yellow-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <AppIcon name="check-circle" :size="18" class="text-yellow-600" />
                            <div class="font-semibold text-yellow-900 text-sm">{{ selectedRows.length }}
                                record(s) selected</div>
                        </div>
                        <AppButton v-if="hasPermission('reports.view')" icon="download" label="Export Selected"
                            @click="openExportSelected" severity="info" rounded size="small" />
                    </div>
                </div>

                <!-- DataTable View -->
                <div class="scholarship-table-wrap">
                    <DataTable :value="tableData"
                        :rowHover="true" stripedRows class="compact-table [&_.p-datatable-thead_th]:text-sm [&_.p-chip]:text-xs" scrollable tableStyle="min-width: 84rem"
                        @rowContextmenu="(event) => openContextMenu(event.originalEvent, event.data)" contextMenu
                        :globalFilter="globalFilter"
                        :rowClass="(row) => expandedRows.length && !expandedRows.some(r => r.profile_id === row.profile_id) ? 'row-blurred' : ''"
                        v-model:expandedRows="expandedRows" v-model:selection="selectedRows" dataKey="profile_id">

                        <Column selectionMode="multiple" :exportable="false" headerClass="w-12" bodyClass="w-12" />
                        <Column expander headerClass="w-12" bodyClass="w-12" />

                        <Column field="unique_id" header="Name" headerClass="min-w-[160px]" bodyClass="min-w-[160px]">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <div class="w-[40px] shrink-0">
                                        <img v-if="slotProps.data.gender == 'M'" src="/images/male-avatar.png"
                                            alt="avatar" class="rounded-full w-10 h-10" />
                                        <img v-else-if="slotProps.data.gender == 'F'" src="/images/female-avatar.png"
                                            alt="avatar" class="rounded-full w-10 h-10" />
                                        <Avatar v-else :label="getInitials(slotProps.data)" size="normal" shape="circle"
                                            class="bg-gradient-to-br from-blue-500 to-blue-600 text-white" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div as="button"
                                                class="font-semibold text-sky-800 text-sm flex-1 min-w-0 cursor-pointer hover:text-cyan-600 underline underline-offset-2"
                                                @click="viewFullProfile(slotProps.data)"
                                                @contextmenu.prevent="openContextMenu($event, slotProps.data)">{{
                                                    getFullName(slotProps.data) }}</div>
                                            <span v-if="slotProps.data.is_graduated"
                                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-semibold uppercase tracking-[0.12em] text-emerald-800 dark:bg-emerald-900/35 dark:text-emerald-200"
                                                v-tooltip.bottom="slotProps.data.graduation_date ? `Graduated ${formatDate(slotProps.data.graduation_date)}` : 'Graduated'">
                                                Graduated
                                            </span>
                                            <span v-if="slotProps.data.has_ongoing_ros"
                                                class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-2xs font-semibold uppercase tracking-[0.12em] text-sky-800 dark:bg-sky-900/35 dark:text-sky-200"
                                                v-tooltip.bottom="slotProps.data.ros_start_date ? `ROS ongoing since ${formatDate(slotProps.data.ros_start_date)}` : 'ROS ongoing'">
                                                ROS Ongoing
                                            </span>
                                            <Badge v-if="slotProps.data.needs_legacy_term_review" value="Needs Review"
                                                severity="warn" size="small"
                                                v-tooltip.bottom="getLegacyTermReviewTooltip(slotProps.data)" />
                                        </div>
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
                                    <button type="button"
                                        class="ml-auto shrink-0 self-start cursor-pointer text-gray-400 hover:text-gray-600 transition-colors"
                                        v-tooltip.top="'Copy name'"
                                        @click.stop="copyProfileName(slotProps.data)">
                                        <AppIcon name="copy" :size="12" />
                                    </button>
                                </div>
                            </template>
                        </Column>


                        <Column header="Address" headerClass="min-w-[150px]" bodyClass="min-w-[150px]">
                            <template #body="slotProps">
                                <div class="text-xs flex items-center gap-2 uppercase" v-if="slotProps.data.municipality">
                                    <AppIcon name="map" :size="12" class="text-gray-500" />
                                    <span>{{ slotProps.data.municipality }}{{ slotProps.data.barangay ? `,
                                        ${slotProps.data.barangay}` : '' }}</span>
                                </div>
                                <span v-else class="text-xs text-gray-400">-</span>
                                <div class="text-xs mt-0.5 flex items-center gap-2">
                                    <AppIcon name="phone" :size="12" class="text-gray-500" />
                                    <span>{{ slotProps.data.contact_no || 'No contact no.' }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Academic" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record" class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-2xs font-bold shrink-0"
                                        :style="{ backgroundColor: slotProps.data.latest_scholarship_record.program?.bg_color || getProgramColor(slotProps.data.latest_scholarship_record.program?.id ?? 0) }"
                                        v-tooltip.top="slotProps.data.latest_scholarship_record.program?.name || 'Program'">
                                        {{ getProgramAbbrev(slotProps.data.latest_scholarship_record.program) }}
                                    </div>
                                    <div class="text-xs flex flex-col gap-0.5 min-w-0 leading-snug">
                                        <div class="font-medium truncate"
                                            v-if="slotProps.data.latest_scholarship_record.school">
                                            {{ slotProps.data.latest_scholarship_record.school.shortname }}
                                        </div>
                                        <div class="truncate" v-if="slotProps.data.latest_scholarship_record.course">
                                            {{ slotProps.data.latest_scholarship_record.course.name ||
                                                slotProps.data.latest_scholarship_record.course.shortname }}
                                        </div>
                                        <div class="text-gray-600 truncate"
                                            v-if="slotProps.data.latest_scholarship_record.year_level">
                                            {{ slotProps.data.latest_scholarship_record.year_level }} Year
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400">N/A</div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                            <template #body="slotProps">
                                <template v-if="slotProps.data.latest_scholarship_record">
                                    <div :class="getStatusBadgeClass(slotProps.data.latest_scholarship_record.unified_status)"
                                        :v-tooltip="getStatusTooltip(slotProps.data.latest_scholarship_record.unified_status)"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block cursor-help">
                                        {{
                                            getScholarshipStatusLabel(slotProps.data.latest_scholarship_record.unified_status)
                                        }}
                                    </div>
                                </template>
                                <div v-else
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600">
                                    No Record
                                </div>
                            </template>
                        </Column>

                        <Column header="Previous Records" headerClass="min-w-[150px]" bodyClass="min-w-[150px]">
                            <template #body="slotProps">
                                <div v-if="Object.keys(slotProps.data.previous_record_statuses ?? {}).length"
                                    class="flex flex-wrap gap-1">
                                    <div v-for="(count, status) in slotProps.data.previous_record_statuses"
                                        :key="status">
                                        <div class="flex items-center gap-1">
                                            <div :class="getStatusBadgeClass(status)"
                                                class="px-2 py-0.5 rounded-full text-xs font-semibold border text-center inline-block">
                                                {{ getScholarshipStatusLabel(status) }}
                                            </div>
                                            <Badge v-if="count > 1" :value="count" severity="secondary" size="small"
                                                v-tooltip.top="`${count} records with this status`" />
                                        </div>
                                        <div v-if="status === 'completed' && slotProps.data.previous_completed_periods?.length"
                                            class="mt-0.5 text-xs leading-tight text-gray-500 dark:text-gray-400">
                                            <div v-for="(period, i) in slotProps.data.previous_completed_periods"
                                                :key="i">
                                                {{ period }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </template>
                        </Column>

                        <Column field="grant_provision" header="Grant Provision" headerClass="min-w-[160px]"
                            bodyClass="min-w-[160px]" v-if="!simpleView">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.latest_scholarship_record" class="flex items-center gap-2">
                                    <Chip v-if="slotProps.data.latest_scholarship_record.grant_provision"
                                        :label="getSystemOptionLabel('grant_provision', slotProps.data.latest_scholarship_record.grant_provision)"
                                        size="small" class="font-medium cursor-pointer"
                                        @click="openGrantProvisionDialog(slotProps.data)" />
                                    <AppButton v-else icon="plus" label="Set"
                                        size="small" severity="secondary" text
                                        @click="openGrantProvisionDialog(slotProps.data)" />
                                    <AppButton
                                        v-if="slotProps.data.latest_scholarship_record.grant_provision"
                                        icon="pencil" size="small" severity="secondary" text rounded
                                        @click="openGrantProvisionDialog(slotProps.data)" v-tooltip.top="'Edit'" />
                                </div>
                                <span v-else class="text-sm text-gray-400">N/A</span>
                            </template>
                        </Column>

                        <Column header="Actions" headerClass="min-w-[120px]" bodyClass="min-w-[120px]"
                            v-if="!simpleView">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <AppButton icon="eye" size="small" severity="info" outlined rounded
                                        v-tooltip.top="'View'" @click="viewFullProfile(slotProps.data)" />
                                    <AppButton icon="trash" size="small" severity="danger" outlined rounded
                                        v-tooltip.top="'Soft Delete (Admin Only)'"
                                        @click="confirmDeleteProfile(slotProps.data)"
                                        :disabled="!hasRole('administrator')"
                                        :class="{ 'opacity-50': !hasRole('administrator') }" />
                                </div>
                            </template>
                        </Column>

                        <template #expansion="slotProps">
                            <div class="px-4 py-3">
                                <div class="flex items-center gap-2 mb-3">
                                    <AppIcon name="history" :size="16" class="text-indigo-500" />
                                    <span class="text-sm font-semibold text-gray-700">Scholarship Records</span>
                                    <Badge :value="slotProps.data.scholarship_grant?.length ?? 0" severity="secondary"
                                        size="small" />
                                </div>
                                <DataTable :value="slotProps.data.scholarship_grant" size="small" scrollable
                                    tableStyle="min-width: 66rem" v-if="slotProps.data.scholarship_grant?.length"
                                    :rowClass="(row) => row.id === slotProps.data.latest_scholarship_record?.id ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                    <Column header="#" headerClass="w-10" bodyClass="w-10">
                                        <template #body="r">
                                            <span class="text-xs text-gray-400">{{
                                                slotProps.data.scholarship_grant.indexOf(r.data) + 1 }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Status" headerClass="min-w-[110px]" bodyClass="min-w-[110px]">
                                        <template #body="r">
                                            <div class="flex items-center gap-1">
                                                <div :class="getStatusBadgeClass(r.data.unified_status)"
                                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border inline-block">
                                                    {{ getScholarshipStatusLabel(r.data.unified_status) }}
                                                </div>
                                                <AppIcon
                                                    v-if="r.data.id === slotProps.data.latest_scholarship_record?.id"
                                                    name="star-fill" :size="12" class="text-blue-400"
                                                    v-tooltip.top="'Latest record'" />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Program" headerClass="min-w-[100px]" bodyClass="min-w-[100px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.program?.shortname || '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Course" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.course?.shortname || '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="School" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.school?.shortname || '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Year" headerClass="min-w-[80px]" bodyClass="min-w-[80px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.year_level ? r.data.year_level + ' yr' : '—'
                                            }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Academic Year" headerClass="min-w-[110px]"
                                        bodyClass="min-w-[110px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.academic_year || '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Term" headerClass="min-w-[80px]" bodyClass="min-w-[80px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.term || '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Grant Provision" headerClass="min-w-[110px]"
                                        bodyClass="min-w-[110px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ getSystemOptionLabel('grant_provision',
                                                r.data.grant_provision, '—') }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Date Filed" headerClass="min-w-[100px]" bodyClass="min-w-[100px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.date_filed ? formatDate(r.data.date_filed) :
                                                '—' }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Date Approved" headerClass="min-w-[110px]"
                                        bodyClass="min-w-[110px]">
                                        <template #body="r">
                                            <span class="text-xs">{{ r.data.date_approved ?
                                                formatDate(r.data.date_approved) : '—' }}</span>
                                        </template>
                                    </Column>
                                </DataTable>
                                <p v-else class="text-xs text-gray-400 italic">No scholarship records found.</p>
                            </div>
                        </template>

                        <template #empty>
                            <div class="text-center py-12">
                                <AppIcon name="users" :size="64" class="text-gray-300 mb-4" />
                                <p class="text-gray-500 text-lg">No profiles found</p>
                                <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <div v-if="tableData.length > 0" class="flex flex-col items-center gap-1 mt-4">
                    <AppButton v-if="hasMore" label="Show More" icon="chevron-down" severity="secondary" size="small"
                        outlined rounded @click="loadMore()" />
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        Showing {{ tableData.length }} of {{ totalRecords }} entries
                    </span>
                </div>
            </Panel>
        </div>

        <!-- Full Profile View Dialog -->
        <IosModal :visible="showProfileDialog" width="900px" max-width="90vw" body-style="padding: 16px;"
            @update:visible="showProfileDialog = $event">
            <template #title>
                <span class="ios-nav-title flex items-center gap-2 text-nav-title">
                    <AppIcon name="user" :size="18" class="text-blue-600" />
                    <span class="font-semibold">Profile Details</span>
                </span>
            </template>

            <div v-if="selectedProfile" class="space-y-6">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <AppIcon name="user" :size="16" class="text-blue-600" />
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
                        <AppIcon name="bookmark" :size="16" class="text-blue-600" />
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
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Awaiting review'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'interviewed'"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Interviewed, awaiting decision'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'approved'"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Enrolled as scholar'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'denied'"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Application has been denied'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'active'"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Enrolled as scholar'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="['completed', 'completed-transferred'].includes(selectedProfile.latest_scholarship_record.unified_status)"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="selectedProfile.latest_scholarship_record.unified_status === 'completed-transferred'
                                        ? 'Scholarship completed and transferred'
                                        : 'Scholarship completed'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                                <div v-else-if="selectedProfile.latest_scholarship_record.unified_status === 'unknown'"
                                    :class="getStatusBadgeClass(selectedProfile.latest_scholarship_record.unified_status)"
                                    v-tooltip="'Status unknown'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold border cursor-help inline-block">
                                    {{
                                        getScholarshipStatusLabel(selectedProfile.latest_scholarship_record.unified_status)
                                    }}
                                </div>
                            </div>
                            <p v-else class="text-sm font-medium text-gray-500 dark:text-gray-400">N/A</p>
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
                        <AppIcon name="chart-bar" :size="16" class="text-blue-600" />
                        Summary
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ selectedProfile.total_scholarships || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total Applications</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-white/10">
                    <AppButton label="Close" icon="times" severity="secondary" @click="showProfileDialog = false" />
                </div>
            </div>
        </IosModal>

        <!-- Export Modal (ticked rows or full filtered set) -->
        <ExportSelectedModal :show="showExportModal" :selected-rows="exportRows" :mode="exportMode"
            :default-title="exportDefaultTitle" default-sort="name" :enable-signatories="true" :enable-projected="true"
            :enable-jpm="true" :enable-grant-provision="true" @update:show="showExportModal = $event" />

        <!-- Centered loading message while the full report dataset is fetched -->
        <LoadingIndicator :show="reportLoading" message="Generating report data…"
            subtext="Fetching all records matching the current filters. Large result sets may take a moment." />

        <!-- Grant Provision Update Dialog -->
        <IosModal :visible="showGrantProvisionDialog" title="Update Grant Provision" width="calc(100vw - 2rem)"
            max-width="500px" body-style="padding: 16px;" @update:visible="showGrantProvisionDialog = $event">
            <div class="space-y-4" v-if="selectedProfileForGrant">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scholar Name</label>
                    <p class="text-base font-semibold">{{ getFullName(selectedProfileForGrant) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scholarship
                        Record</label>
                    <Select v-model="grantProvisionForm.scholarship_record_id" :options="scholarshipRecordOptions"
                        optionLabel="label" optionValue="value" placeholder="Select scholarship record" class="w-full"
                        @change="onScholarshipRecordChange" />
                </div>
                <div v-if="grantProvisionForm.scholarship_record_id">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Grant
                        Provision</label>
                    <Select v-model="grantProvisionForm.grant_provision" :options="grantProvisionOptions"
                        optionLabel="label" optionValue="value" placeholder="Select provision type" class="w-full"
                        showClear />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-gray-200 dark:border-white/10">
                <AppButton label="Cancel" severity="secondary" outlined @click="showGrantProvisionDialog = false" />
                <AppButton label="Update" icon="check" severity="info" @click="updateGrantProvision"
                    :loading="grantProvisionForm.processing" :disabled="!grantProvisionForm.scholarship_record_id" />
            </div>
        </IosModal>

        <!-- Delete Confirmation Modal -->
        <IosConfirmDialog
            :visible="showDeleteConfirmDialog"
            title="Confirm Soft Delete"
            width="460px"
            message="Soft delete this profile? This action can be undone from the Deleted Records page."
            icon-color="#f97316"
            data-label="Profile"
            :data="[{ label: 'Name', value: profileToDelete ? getFullName(profileToDelete) : 'N/A' }]"
            @accept="deleteProfile"
            @update:visible="showDeleteConfirmDialog = $event"
        />

        <!-- Scholar Form Modal (Create) -->
        <ScholarFormModal v-model:visible="showAddActiveModal" mode="create" @success="refreshData" />


        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" :model="contextMenuItems" appendTo="body">
            <template #item="{ item, props }">
                <a v-ripple v-bind="props.action" class="flex items-center gap-2 w-full">
                    <AppIcon v-if="item.icon" :name="item.icon" :size="14" />
                    <span>{{ item.label }}</span>
                    <AppIcon v-if="item.items" name="chevron-right" :size="14" class="ml-auto" />
                </a>
            </template>
        </ContextMenu>

        <!-- Confirmation Dialog -->
        <IosConfirmDialog
            v-model:visible="confirmDialogVisible"
            :title="confirmDialogHeader"
            width="450px"
            :message="confirmDialogMessage"
            :icon="confirmDialogIcon || 'exclamation-triangle'"
            action-class=""
            @accept="handleConfirmDialogAccept"
        />

        <!-- Display Settings Modal -->
        <IosModal v-model:visible="showDisplaySettingsModal" title="Display Settings" width="calc(100vw - 2rem)"
            max-width="400px" body-style="padding: 16px;">
            <div class="ios-section ios-section-tight">
                <div class="ios-section-label text-compact">Rows Per Page</div>
                <div class="ios-card">
                    <div class="ios-row ios-row-last">
                        <span class="ios-row-label text-sm">Show</span>
                        <div class="flex items-center gap-2">
                            <RecordsSelect v-model="records" label="label" class="w-20" size="small" />
                            <span class="text-sm text-gray-600">/ <strong>{{ totalRecords }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ios-section">
                <div class="ios-section-label text-compact">View Mode</div>
                <div class="ios-card">
                    <div class="ios-row ios-row-last">
                        <span class="ios-row-label text-sm">{{ simpleView ? 'Simple View' : 'Detailed View' }}</span>
                        <ToggleSwitch v-model="simpleView" />
                    </div>
                </div>
            </div>
        </IosModal>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, inject } from 'vue';
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { useFilterManager } from '@/composables/useFilterManager';

import IosModal from '@/Components/ui/IosModal.vue';
import IosConfirmDialog from '@/Components/ui/IosConfirmDialog.vue';
import { toast } from '@/utils/toast';
import axios from 'axios';
import { getSystemOptionLabel, useSystemOptions } from '@/composables/useSystemOptions';
import moment from 'moment';
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
import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';
import ExportSelectedModal from '@/Pages/Applicants/Modal/ExportSelectedModal.vue';
import LoadingIndicator from '@/Components/ui/LoadingIndicator.vue';

// Props
const props = defineProps({
    profiles: Object,
    filters: Object,
    programs: Array,
});
// Page-specific state

// Multiselect filters submit as a comma-joined string; single-select ones
// keep passing through a plain value, matching each field's `getter`.
const extractMultiValue = (value, getter) => {
    if (Array.isArray(value)) {
        return value.map(getter).filter(Boolean).join(',');
    }
    return getter(value);
};

// Filter management via composable
const {
    filters: filter,
    globalFilter,
    records,
    totalRecords,
    search: triggerSearch,
    hasMore,
    loadMore,
} = useFilterManager({
    routeName: 'scholarship.profiles',
    props,
    filterPropName: 'filters',
    filterDefs: [
        { key: 'name', type: 'text', default: '' },
        { key: 'program', type: 'select', default: '', extract: v => v?.shortname?.toLowerCase() },
        { key: 'school', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.shortname?.toLowerCase()) },
        { key: 'course', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.name?.toLowerCase()) },
        { key: 'year_level', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.value?.toLowerCase()) },
        { key: 'academic_year', type: 'select', default: '', multiple: true, extract: v => Array.isArray(v) ? v.filter(Boolean).join(',') : v },
        { key: 'term', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.value?.toLowerCase()) },
        { key: 'municipality', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.name?.toLowerCase()) },
        { key: 'barangay', type: 'select', default: '', multiple: true, extract: v => extractMultiValue(v, item => item?.name?.toLowerCase()) },
        { key: 'grant_provision', type: 'text', default: null },
        { key: 'unified_status', type: 'text', default: null },
        { key: 'needs_term_review', type: 'text', default: null },
        { key: 'contract_status', type: 'text', default: null },
        { key: 'voucher_status', type: 'text', default: null },
        { key: 'encoded_by', type: 'text', default: '' },
    ],
    beforeSearch(params, filterValues) {
        // Handle attachment filter values (three-state: null, 'with', 'without')
        if (params.contract_status && !['with', 'without'].includes(params.contract_status)) {
            delete params.contract_status;
        }
        if (params.voucher_status && !['with', 'without'].includes(params.voucher_status)) {
            delete params.voucher_status;
        }
    },
});


const { statusOptions, getStatusLabel } = useScholarshipStatus();

// Status tabs — the Profiles page primarily lists Active, Completed and
// Graduate records. Pending records live on the Applicants page. Every other
// status is reachable through the "Other Records" button.
const statusTabs = [
    { label: 'All', value: 'all', icon: 'users' },
    { label: 'Active', value: 'active', icon: 'user-check' },
    { label: 'Term Completed', value: 'completed', icon: 'circle-check' },
    { label: 'Graduated', value: 'graduated', icon: 'graduation-cap' },
];
const TAB_STATUSES = statusTabs.map(t => t.value);

// "Other Records" statuses — everything except the tabs, the folded-in
// 'approved' state, and 'pending' (which belongs on the Applicants page).
const EXCLUDED_OTHER_STATUSES = ['pending', 'active', 'approved', 'completed'];
const otherStatusOptions = computed(() =>
    statusOptions.value.filter(option => !EXCLUDED_OTHER_STATUSES.includes(option.value))
);
const isAllowedStatus = (status) =>
    TAB_STATUSES.includes(status) || otherStatusOptions.value.some(option => option.value === status);

const activeTab = ref(
    isAllowedStatus(filter.value.unified_status) ? filter.value.unified_status : 'all'
);

// The "Other Records" button is highlighted whenever a non-tab status is active
const isOtherActive = computed(() => !TAB_STATUSES.includes(activeTab.value));
const otherButtonLabel = computed(() =>
    isOtherActive.value ? getStatusLabel(activeTab.value) : 'Other Records'
);

const otherRecordsPopover = ref(null);

const applyStatus = (status) => {
    if (activeTab.value !== status) {
        activeTab.value = status;
        collapseExpandedRows();
        // Setting the status filter triggers the filter watcher, which runs the search
        filter.value.unified_status = status;
    }
    otherRecordsPopover.value?.hide();
};

const selectTab = (tab) => applyStatus(tab);
const selectOtherStatus = (status) => applyStatus(status);

// Program tabs (toolbar center) — match on shortname so the active state also
// holds when the filter was rehydrated from the query string.
const isProgramTabActive = (program) => {
    const current = filter.value?.program;
    if (!current || !program) return false;
    const currentName = (current.shortname || current.name || '').toLowerCase();
    const programName = (program.shortname || program.name || '').toLowerCase();
    return currentName === programName;
};

const selectProgramTab = (program) => {
    if (!program) {
        if (!filter.value.program) return;
        filter.value.program = '';
    } else {
        if (isProgramTabActive(program)) return;
        filter.value.program = program;
    }
    collapseExpandedRows();
    // The filter watcher below fires triggerSearch()
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
        needs_term_review: 'Review Status',
        contract_status: 'Contract',
        voucher_status: 'Voucher',
        encoded_by: 'Encoded By',
    };
    for (const [key, label] of Object.entries(labelMap)) {
        const val = f[key];
        if (!val || (Array.isArray(val) && val.length === 0)) continue;
        let display;
        if (key === 'needs_term_review' && val === 'needs_review') {
            display = 'Needs Review';
        } else if (Array.isArray(val)) {
            display = val.map(item => item?.shortname || item?.name || item?.value || item).join(', ');
        } else if (typeof val === 'object') {
            display = val.shortname || val.name || val.value || JSON.stringify(val);
        } else {
            display = String(val);
        }
        tags.push({ key, label, display });
    }
    return tags;
});

// Auto-trigger search when basic filters change
watch(
    () => [filter.value.program, filter.value.school, filter.value.municipality, filter.value.course, filter.value.year_level, filter.value.unified_status, filter.value.needs_term_review],
    () => {
        collapseExpandedRows();
        triggerSearch();
    },
);

watch(globalFilter, () => {
    collapseExpandedRows();
});

// Trigger search when records per page changes
watch(records, () => {
    triggerSearch();
});

// Filter drawer state
const showFilterDrawer = ref(false);
const drawerFilter = ref({});
const drawerFilterKeys = ['program', 'course', 'school', 'municipality', 'barangay', 'year_level', 'academic_year', 'term', 'grant_provision', 'needs_term_review', 'contract_status', 'voucher_status', 'encoded_by'];

// BarangaySelect only scopes to a single municipality — with multiselect,
// use the first pick as a best-effort scope.
const drawerFilterMunicipalityId = computed(() => {
    const municipality = drawerFilter.value.municipality;
    return Array.isArray(municipality) ? municipality[0]?.id : municipality?.id;
});

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
    collapseExpandedRows();
    triggerSearch();
    showFilterDrawer.value = false;
};

const clearDrawerFilters = () => {
    const nullKeys = ['grant_provision', 'needs_term_review', 'contract_status', 'voucher_status', 'academic_year', 'term', 'encoded_by'];
    for (const key of drawerFilterKeys) {
        drawerFilter.value[key] = nullKeys.includes(key) ? null : '';
    }
};

// UI State

const simpleView = ref(localStorage.getItem('scholarProfileSimpleView') === 'true' || false);
const showDisplaySettingsModal = ref(false);
const expandedRows = ref([]);
const contextMenu = ref();
const selectedProfileForContext = ref(null);

// Export — either the rows ticked in the table ('selected') or the full
// filtered set fetched from the server ('all'). Both feed the shared modal.
const selectedRows = ref([]);
const showExportModal = ref(false);
const exportMode = ref('selected');
const reportRows = ref([]);
const reportLoading = ref(false);

const exportRows = computed(() => (exportMode.value === 'all' ? reportRows.value : selectedRows.value));

// Confirmation Dialog (IosModal-based, matches InterviewedApplicants/Index.vue and Applicants/Index.vue)
const confirmDialogVisible = ref(false);
const confirmDialogHeader = ref('');
const confirmDialogMessage = ref('');
const confirmDialogAcceptLabel = ref('');
const confirmDialogIcon = ref('');
const confirmDialogOnAccept = ref(null);

function openConfirmDialog({ header, message, acceptLabel, icon, onAccept }) {
    confirmDialogHeader.value = header;
    confirmDialogMessage.value = message;
    confirmDialogAcceptLabel.value = acceptLabel;
    confirmDialogIcon.value = icon || '';
    confirmDialogOnAccept.value = onAccept;
    confirmDialogVisible.value = true;
}

function handleConfirmDialogAccept() {
    confirmDialogVisible.value = false;
    if (typeof confirmDialogOnAccept.value === 'function') {
        confirmDialogOnAccept.value();
    }
}

// Report title defaults to "<Status> Scholarship Records Report" for the active tab
const STATUS_TITLE_WORD = { all: 'All', active: 'Active', completed: 'Term Completed', graduated: 'Graduated' };
const exportDefaultTitle = computed(() => {
    const word = STATUS_TITLE_WORD[activeTab.value] || getStatusLabel(activeTab.value);
    return `${word} Scholarship Records Report`;
});

const openExportSelected = () => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one record to export.');
        return;
    }

    openConfirmDialog({
        header: 'Export Selected',
        message: `Export ${selectedRows.value.length} selected record(s)? You'll choose a format (PDF or Excel) in the next step.`,
        acceptLabel: 'Export',
        icon: 'pi pi-download',
        onAccept: () => {
            exportMode.value = 'selected';
            showExportModal.value = true;
        },
    });
};

// Export every record matching the current filters/tab, not just ticked rows.
const openExportAll = () => {
    openConfirmDialog({
        header: 'Export All',
        message: 'Export all records matching the current filters? You\'ll choose a format (PDF or Excel) in the next step.',
        acceptLabel: '',
        icon: 'pi pi-download',
        onAccept: () => {
            fetchAndShowExportAll();
        },
    });
};

const fetchAndShowExportAll = async () => {
    if (reportLoading.value) return;
    reportLoading.value = true;
    try {
        // Reuse the exact query string that produced the current view.
        const qs = window.location.search || '';
        const { data } = await axios.get(route('profile.reportData') + qs);
        const rows = data?.data ?? [];

        if (!rows.length) {
            toast.warn('No records match the current filters.');
            return;
        }

        reportRows.value = rows;
        exportMode.value = 'all';
        showExportModal.value = true;

        if (data?.capped) {
            toast.warn('Report was capped at 20,000 records. Narrow the filters for a complete set.');
        }
    } catch (error) {
        console.error('Failed to load report data:', error);
        toast.error('Failed to load report data.');
    } finally {
        reportLoading.value = false;
    }
};

const collapseExpandedRows = () => {
    expandedRows.value = [];
};

const clearFilters = () => {
    collapseExpandedRows();
    // Reset every filter except the status tab, which always constrains the listing
    filter.value.name = '';
    filter.value.program = '';
    filter.value.school = '';
    filter.value.course = '';
    filter.value.year_level = '';
    filter.value.academic_year = '';
    filter.value.term = '';
    filter.value.municipality = '';
    filter.value.barangay = '';
    filter.value.grant_provision = null;
    filter.value.needs_term_review = null;
    filter.value.contract_status = null;
    filter.value.voucher_status = null;
    filter.value.encoded_by = '';
    globalFilter.value = '';
    filter.value.unified_status = activeTab.value;
    triggerSearch();
};

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
const _grantProvisionRaw = useSystemOptions('grant_provision');
const grantProvisionOptions = computed(() => _grantProvisionRaw.value);

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

// Profile view state
const showProfileDialog = ref(false);
const selectedProfile = ref(null);

const PROGRAM_COLORS = ['#6366F1', '#8B5CF6', '#EC4899', '#F43F5E', '#F97316', '#EAB308', '#22C55E', '#14B8A6', '#06B6D4', '#3B82F6'];

function getProgramColor(id) {
    return PROGRAM_COLORS[id % PROGRAM_COLORS.length];
}

// Program avatar in the Academic column — fixed abbreviations for the four
// scholarship programs, with a generic initials fallback.
function getProgramAbbrev(program) {
    const name = (program?.shortname || program?.name || '').toUpperCase();
    if (name.includes('MED')) return 'MED';
    if (name.includes('EFA')) return 'EFA';
    if (name.includes('TEC') || name.includes('TECH')) return 'TEC';
    if (name.includes('BAR')) return 'BAR';
    return name.split(/\s+/).map(w => w[0]).join('').slice(0, 3) || '?';
}

const showAddActiveModal = ref(false);
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

const attachmentStatusOptions = computed(() => [
    { label: 'All', value: null },
    { label: 'With Attachment', value: 'with' },
    { label: 'Without Attachment', value: 'without' }
]);

const legacyTermReviewOptions = [
    { label: 'Needs Review', value: 'needs_review' },
];

const profilesData = computed(() => {
    return props.profiles?.data || [];
});

// All rows always shown; non-expanded rows are blurred via rowClass
const tableData = computed(() => profilesData.value);

const contextMenuItems = computed(() => [
    {
        label: 'View Profile',
        icon: 'eye',
        command: () => {
            if (selectedProfileForContext.value) {
                viewFullProfile(selectedProfileForContext.value);
            }
        }
    },
    {
        separator: true
    },
    {
        label: 'Grant Provision',
        icon: 'bookmark',
        command: () => {
            if (selectedProfileForContext.value) {
                openGrantProvisionDialog(selectedProfileForContext.value);
            }
        },
        visible: () => selectedProfileForContext.value?.latest_scholarship_record
    },
    {
        separator: true,
        visible: () => hasRole('administrator')
    },
    {
        label: 'Soft Delete',
        icon: 'trash',
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

// Copy "lastname, firstname" to the clipboard
const copyProfileName = (profile) => {
    const text = [profile?.last_name, profile?.first_name].filter(Boolean).join(', ');
    if (!text) return;
    navigator.clipboard.writeText(text)
        .then(() => toast.success(`Copied "${text}"`))
        .catch(() => toast.error('Failed to copy name'));
};

const getLegacyTermReviewTooltip = (profile) => {
    if (!profile?.needs_legacy_term_review) {
        return 'Legacy records are aligned with the single open-term rule.';
    }

    const conflictingGroups = profile.legacy_term_review_group_count || 0;
    const totalOpenTerms = profile.legacy_term_review_open_term_total || 0;
    const groupLabel = conflictingGroups === 1 ? 'legacy enrollment group' : 'legacy enrollment groups';
    const termLabel = totalOpenTerms === 1 ? 'open term' : 'open terms';

    return `${conflictingGroups} ${groupLabel} still contain ${totalOpenTerms} pending/active ${termLabel}. Review this profile's legacy records.`;
};



const getScholarshipStatusLabel = (status) => {
    // Profiles page shows per-term records, so "completed" is spelled out as "Term Completed"
    if (status === 'completed') return 'Term Completed';
    return getStatusLabel(status);
};



const getStatusTooltip = (status) => {
    const tooltips = {
        pending: 'Awaiting review',
        interviewed: 'Interviewed, awaiting decision',
        approved: 'Enrolled as scholar',
        denied: 'Application has been denied',
        active: 'Enrolled as scholar',
        completed: 'Scholarship term completed',
        'completed-transferred': 'Scholarship completed and transferred',
        unknown: 'Status unknown',
    };
    return tooltips[status] || 'Unrecognized status';
};

const getStatusBadgeClass = (status) => {
    const key = String(status || 'unknown').toLowerCase();
    const classes = {
        pending: 'bg-amber-100 text-amber-800 border-amber-500 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-600',
        interviewed: 'bg-indigo-100 text-indigo-800 border-indigo-500 dark:bg-indigo-900/40 dark:text-indigo-200 dark:border-indigo-600',
        approved: 'bg-emerald-100 text-emerald-800 border-emerald-500 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-600',
        denied: 'bg-red-100 text-red-800 border-red-500 dark:bg-red-900/40 dark:text-red-200 dark:border-red-600',
        active: 'bg-emerald-100 text-emerald-800 border-emerald-500 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-600',
        completed: 'bg-gray-100 text-gray-800 border-gray-400 dark:bg-gray-700/50 dark:text-gray-200 dark:border-gray-500',
        'completed-transferred': 'bg-slate-100 text-slate-800 border-slate-400 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-500',
        withdrawn: 'bg-violet-100 text-violet-800 border-violet-500 dark:bg-violet-900/40 dark:text-violet-200 dark:border-violet-600',
        loa: 'bg-yellow-100 text-yellow-800 border-yellow-500 dark:bg-yellow-900/40 dark:text-yellow-200 dark:border-yellow-600',
        suspended: 'bg-rose-100 text-rose-800 border-rose-500 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-600',
        unknown: 'bg-slate-100 text-slate-700 border-slate-400 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-500',
    };

    return classes[key] || classes.unknown;
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
        name: route().params?.name || null,
        program: route().params?.program || null,
        school: route().params?.school || null,
        course: route().params?.course || null,
        municipality: route().params?.municipality || null,
        year_level: route().params?.year_level || null,
        needs_term_review: route().params?.needs_term_review || null,
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
                label: `${record.program?.shortname || 'N/A'} - ${record.course?.shortname || 'N/A'} - ${record.year_level ? record.year_level + ' Year' : 'N/A'} (${getScholarshipStatusLabel(record.unified_status)})${record.grant_provision ? ' - ' + getSystemOptionLabel('grant_provision', record.grant_provision) : ''}`,
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



// Add Record methods
const openAddActiveModal = () => {
    addRecordPopover.value.hide();
    showAddActiveModal.value = true;
};

const refreshData = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
    });
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

watch(simpleView, (newValue) => {
    localStorage.setItem('scholarProfileSimpleView', newValue.toString());
});

// Lifecycle
onMounted(() => {
    window.addEventListener('keydown', handleKeydown);

    // Keep the listing constrained to an allowed status (the tabs plus the
    // "Other Records" statuses). Pending/unknown URLs snap back to the All tab.
    // Setting the status filter triggers the watcher, which reloads the data.
    if (!isAllowedStatus(filter.value.unified_status)) {
        activeTab.value = 'all';
        filter.value.unified_status = 'all';
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

</script>
