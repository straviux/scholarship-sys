<template>
    <AdminLayout>

        <Head title="Interviewed Applicants - Approval Management" />

        <div class="ios-settings-form">
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-[var(--toolbar-pull)] short:mb-2 !rounded-4xl !px-8">
                <template #start>
                    <div class="flex items-center gap-3">
                        <AppIcon name="message-square-more" class="text-blue-600 text-[2rem] short:text-[1.5rem]" />
                        <div>
                            <h1 class="text-2xl short:text-xl font-bold text-gray-700">Interviewed Applicants</h1>
                            <p class="text-sm text-gray-600 short:text-xs">Review interview assessments and manage
                                approvals</p>
                        </div>
                    </div>
                </template>
                <template #center>
                    <!-- Program tabs — the primary filter, front and center -->
                    <div class="flex flex-wrap items-center justify-center gap-2" role="tablist"
                        aria-label="Scholarship programs">
                        <button type="button" role="tab" :aria-selected="!filters.program"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="!filters.program
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(null)">
                            <AppIcon name="layers" :size="14" />
                            All Programs
                        </button>
                        <button v-for="(program, i) in programs" :key="program.id" type="button" role="tab"
                            :aria-selected="isProgramTabActive(program)"
                            class="flex cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                            :class="isProgramTabActive(program)
                                ? 'bg-indigo-500 !text-white shadow-md'
                                : 'bg-white text-slate-600 hover:text-indigo-600'"
                            @click="selectProgramTab(program)">
                            <span class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: program.bg_color || programDotColors[i % programDotColors.length] }"></span>
                            {{ program.shortname || program.name }}
                        </button>
                    </div>
                </template>
                <template #end>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <AppButton icon="users" label="Cumulative List" severity="secondary" rounded size="small"
                            @click="openCumulativeScholarListModal" />
                        <AppButton v-if="activeTab === 'interviewed'" icon="printer" severity="info" text rounded
                            size="large" @click="openReportModal" v-tooltip.bottom="'Print Report'" />
                    </div>
                </template>
            </Toolbar>

            <Tabs v-model:value="activeTab" class="!bg-transparent">
                <TabPanels class="!bg-transparent !p-0">
                    <TabPanel value="interviewed" class="!bg-transparent !p-0">

                        
                        

                        <Panel class="!rounded-4xl overflow-hidden shadow-sm mt-4">
                            <!-- View Tabs -->
                            <div class="mb-4 -mt-2 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-1" role="tablist" aria-label="Interviewed applicant views">
                                    <button type="button" role="tab" :aria-selected="activeTab === 'interviewed'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'interviewed'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'interviewed'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="clipboard-list" :size="14" />
                                            <span>Interviewed</span>
                                            <span
                                                class="rounded-full bg-blue-50 px-2 py-0.5 text-2xs font-semibold text-blue-700">
                                                {{ filteredList.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'recommendation-lists'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'recommendation-lists'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'recommendation-lists'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list-checks" :size="14" />
                                            <span>Approval Requests</span>
                                            <span
                                                class="rounded-full bg-emerald-50 px-2 py-0.5 text-2xs font-semibold text-emerald-700">
                                                {{ recommendationLists.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'all'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'all'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'all'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list" :size="14" />
                                            <span>All</span>
                                            <span
                                                class="rounded-full bg-amber-50 px-2 py-0.5 text-2xs font-semibold text-amber-700">
                                                {{ recommendationListAuditRecords.length }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-sm opacity-75">
                                    <span class="font-semibold text-blue-600">{{ stats.total }} interviewed</span>
                                    <span class="text-gray-300">|</span>
                                    <span class="font-semibold text-green-600">{{ stats.recommended }} recommended</span>
                                    <span class="text-gray-300">|</span>
                                    <span class="font-semibold text-yellow-600">{{ stats.furtherEval }} for evaluation</span>
                                    <span class="text-gray-300">|</span>
                                    <span class="font-semibold text-red-600">{{ stats.notRecommended }} not recommended</span>
                                </div>
                            </div>

                            <div v-if="selectedRows.length > 0"
                                class="mb-4 rounded-3xl border border-yellow-200 bg-yellow-50 p-3">
                                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="flex items-center gap-3">
                                        <AppIcon name="check-circle" :size="18" class="text-yellow-600" />
                                        <div>
                                            <div class="font-semibold text-yellow-900 text-sm">{{ selectedRows.length }}
                                                applicant(s) selected</div>
                                            <div class="text-xs text-yellow-700">
                                                Export the current selection.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <AppButton icon="file-spreadsheet" label="Export Excel" severity="success"
                                            outlined rounded size="small" @click="exportSelected" />
                                    </div>
                                </div>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex flex-wrap items-end gap-3 mb-4">
                                <InputGroup class="w-full sm:w-64">
                                    <InputGroupAddon>
                                        <AppIcon name="search" :size="14" class="text-gray-400" />
                                    </InputGroupAddon>
                                    <InputText v-model="filters.name" placeholder="Search by name..." size="small" />
                                </InputGroup>
                                <div class="flex flex-col">
                                    <Select v-model="filters.recommendation" :options="recommendationOptions"
                                        optionLabel="label" optionValue="value" placeholder="All Recommendations" size="small"
                                        class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <CourseSelect v-model="filters.course" size="small" class="w-full" :load-all-when-no-program="true" />
                                </div>
                                <div class="flex flex-col">
                                    <Select v-model="filters.listStatus" :options="recommendationListStatusOptions"
                                        optionLabel="label" optionValue="value" placeholder="All Approval Request Status"
                                        size="small" class="min-w-[220px] w-full" />
                                </div>
                                <div class="ml-auto flex flex-wrap justify-end gap-2 self-center">
                                    <AppButton icon="filter" label="Show Eligible Applicants" severity="secondary" rounded
                                        size="xsmall" @click="presetRecommendationCreationFilters" />
                                    <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                        size="xsmall" @click="clearInterviewedFilters" />
                                </div>
                            </div>

                            <!-- Active filter tags -->
                            <div v-if="activeFilterTags.length" class="flex flex-wrap items-center gap-2 px-2 py-2 border-b border-slate-100">
                                <span class="text-xs text-gray-500">Active Filters:</span>
                                <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                                    <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                                </Tag>
                            </div>

                            <div v-if="filteredList.length === 0" class="text-center py-8 text-gray-500">
                                No interviewed applicants found
                            </div>
                            <DataTable v-else
                                :value="filteredList" responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean" dataKey="id"
                                v-model:expandedRows="expandedRows" showGridlines stripedRows scrollable
                                :rowClass="(row) => Object.keys(expandedRows).length > 0 && !expandedRows[row.id] ? 'ios-row-blurred' : ''"
                                @rowContextmenu="(event) => openContextMenu(event.originalEvent, event.data)"
                                contextMenu>
                                <Column :exportable="false" headerClass="w-12" bodyClass="w-12">
                                    <template #header>
                                        <div class="flex justify-center">
                                            <Checkbox :modelValue="allFilteredRowsSelected" binary
                                                :indeterminate="someFilteredRowsSelected"
                                                :disabled="filteredList.length === 0"
                                                @update:modelValue="toggleSelectAllFilteredRows" />
                                        </div>
                                    </template>
                                    <template #body="slotProps">
                                        <div class="flex justify-center"
                                            v-tooltip.top="slotProps.data.is_in_recommendation_list ? 'Already included in an approval request (still selectable for export)' : 'Select applicant'">
                                            <Checkbox :modelValue="isRowSelected(slotProps.data)" binary
                                                @update:modelValue="(checked) => toggleRowSelection(slotProps.data, checked)" />
                                        </div>
                                    </template>
                                </Column>
                                <Column expander :exportable="false" headerClass="w-12" bodyClass="w-12" />
                                <Column field="profile.last_name" header="Name" sortable>
                                    <template #body="slotProps">
                                        <div class="font-medium">
                                            {{ slotProps.data.profile.last_name }}, {{
                                                slotProps.data.profile.first_name
                                            }}
                                        </div>
                                        <div class="text-2xs mono text-gray-500">{{
                                            slotProps.data.profile.contact_no
                                            }}</div>

                                    </template>
                                </Column>
                                <Column field="program.shortname" header="Program" sortable>
                                    <template #body="slotProps">
                                        <span class="text-xs"> {{ slotProps.data.program?.shortname || 'N/A'
                                            }}</span>
                                    </template>
                                </Column>
                                <Column field="school.shortname" header="School" sortable>
                                    <template #body="slotProps">
                                        <span class="text-xs"> {{ slotProps.data.school?.shortname ||
                                            slotProps.data.school?.name || 'N/A' }}</span>
                                    </template>
                                </Column>
                                <Column field="course.shortname" header="Course" sortable>
                                    <template #body="slotProps">
                                        <span class="text-3xs font-semibold"> {{ slotProps.data.course?.name ||
                                            'N/A'
                                            }}</span>
                                    </template>
                                </Column>
                                <Column header="Year Level" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                                    <template #body="slotProps">
                                        <span class="text-xs"> {{ getSystemOptionLabel('year_level',
                                            slotProps.data.year_level,
                                            'N/A') }}</span>
                                    </template>
                                </Column>
                                <Column header="Term" headerClass="min-w-[120px]" bodyClass="min-w-[120px]">
                                    <template #body="slotProps">
                                        <span class="text-xs"> {{ getSystemOptionLabel('term', slotProps.data.term,
                                            'N/A') }}</span>
                                    </template>
                                </Column>
                                <Column header="Academic Year" headerClass="min-w-[140px]" bodyClass="min-w-[140px]">
                                    <template #body="slotProps">
                                        <span class="text-xs"> {{ slotProps.data.academic_year || 'N/A' }}</span>
                                    </template>
                                </Column>
                                <Column header="Grant Provision" headerClass="min-w-[200px]" bodyClass="min-w-[200px]">
                                    <template #body="slotProps">
                                        <div class="text-xs leading-snug">
                                            {{ slotProps.data.grant_provision_label ||
                                                getSystemOptionLabel('grant_provision',
                                                    slotProps.data.grant_provision, 'N/A') }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Recommendation" sortable sortField="recommendation"
                                    headerClass="min-w-[220px]" bodyClass="min-w-[220px]">
                                    <template #body="slotProps">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                :class="['text-2xs font-semibold', getRecommendationTextClass(slotProps.data.recommendation)]">
                                                {{ formatRecommendation(slotProps.data.recommendation) }}
                                            </span>
                                            <span v-if="slotProps.data.is_in_recommendation_list"
                                                class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2 py-0.5 text-3xs font-semibold text-amber-700">
                                                Already in Approval Request
                                            </span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Endorsed By" headerClass="min-w-[180px]" bodyClass="min-w-[180px]">
                                    <template #body="slotProps">
                                        <div class="text-sm leading-snug uppercase">
                                            {{ slotProps.data.endorsed_by || '-' }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Actions" :style="{ width: '80px' }">
                                    <template #body="slotProps">
                                        <AppButton icon="ellipsis-vertical"
                                            @click="openContextMenu($event, slotProps.data)" text rounded size="small"
                                            v-tooltip.top="'Actions'" />
                                    </template>
                                </Column>

                                <template #expansion="slotProps">
                                    <div class="px-4 pb-4">
                                        <div class="overflow-hidden rounded border border-slate-200 bg-slate-50">
                                            <table class="w-full table-fixed border-collapse text-sm">
                                                <thead>
                                                    <tr class="bg-slate-100">
                                                        <th colspan="3"
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                                            Projected Detail
                                                        </th>
                                                        <th colspan="2"
                                                            class="border-b border-l border-slate-200 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                                            Interview Detail
                                                        </th>
                                                    </tr>
                                                    <tr class="bg-white">
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-2xs font-medium uppercase tracking-wide text-slate-500">
                                                            Terms</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-2xs font-medium uppercase tracking-wide text-slate-500">
                                                            Expense</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-2xs font-medium uppercase tracking-wide text-slate-500">
                                                            Completion</th>
                                                        <th
                                                            class="border-b border-l border-slate-200 px-3 py-2 text-left text-2xs font-medium uppercase tracking-wide text-slate-500">
                                                            Interview Date</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-2xs font-medium uppercase tracking-wide text-slate-500">
                                                            Interviewed By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="bg-white align-top">
                                                        <td class="px-3 py-3 text-sm font-semibold text-slate-700">
                                                            <span v-if="slotProps.data.projected_term_count !== null">
                                                                {{
                                                                    formatProjectedTerms(slotProps.data.projected_term_count)
                                                                }}
                                                            </span>
                                                            <span v-else class="text-amber-700">Not
                                                                configured</span>
                                                        </td>
                                                        <td class="px-3 py-3 text-sm font-semibold text-emerald-700">
                                                            <span
                                                                v-if="slotProps.data.projected_total_expense !== null">
                                                                {{
                                                                    formatCurrency(slotProps.data.projected_total_expense)
                                                                }}
                                                            </span>
                                                            <span v-else class="text-amber-700">Not
                                                                configured</span>
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-slate-700">
                                                            <div v-if="slotProps.data.projected_completion_year !== null"
                                                                class="font-semibold">
                                                                {{ slotProps.data.projected_completion_year }}
                                                            </div>
                                                            <div v-if="slotProps.data.projected_completion_academic_year"
                                                                class="text-xs text-gray-500">
                                                                AY {{
                                                                    slotProps.data.projected_completion_academic_year
                                                                }}
                                                            </div>
                                                            <div v-else-if="slotProps.data.projected_completion_year === null"
                                                                class="text-amber-700">
                                                                Not configured
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="border-l border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700">
                                                            {{ formatDate(slotProps.data.interviewed_at) }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-3 text-sm font-semibold text-slate-700 uppercase">
                                                            {{ slotProps.data.interviewer?.name || 'N/A' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>

                            <!-- Show More (bottom) -->
                            <div v-if="filteredList.length > 0" class="flex flex-col items-center gap-1 px-2 py-3 border-t border-slate-100">
                                <AppButton v-if="hasMoreInterviewed" label="Show More" icon="chevron-down"
                                    severity="secondary" size="small" outlined rounded @click="loadMoreInterviewed" />
                                <span class="text-xs text-gray-400">
                                    Showing {{ props.interviewed_applicants?.length || 0 }} of {{
                                        props.interviewed_applicants_pagination.total || 0 }} entries
                                </span>
                            </div>
                        </Panel>
                    </TabPanel>

                    <TabPanel value="recommendation-lists">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm mt-4">
                            <!-- View Tabs -->
                            <div class="mb-4 -mt-2 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-1" role="tablist" aria-label="Interviewed applicant views">
                                    <button type="button" role="tab" :aria-selected="activeTab === 'interviewed'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'interviewed'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'interviewed'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="clipboard-list" :size="14" />
                                            <span>Interviewed</span>
                                            <span
                                                class="rounded-full bg-blue-50 px-2 py-0.5 text-2xs font-semibold text-blue-700">
                                                {{ filteredList.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'recommendation-lists'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'recommendation-lists'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'recommendation-lists'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list-checks" :size="14" />
                                            <span>Approval Requests</span>
                                            <span
                                                class="rounded-full bg-emerald-50 px-2 py-0.5 text-2xs font-semibold text-emerald-700">
                                                {{ recommendationLists.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'all'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'all'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'all'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list" :size="14" />
                                            <span>All</span>
                                            <span
                                                class="rounded-full bg-amber-50 px-2 py-0.5 text-2xs font-semibold text-amber-700">
                                                {{ recommendationListAuditRecords.length }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-500">{{ filteredRecommendationLists.length }} saved
                                        transaction(s)</span>
                                    <AppButton v-if="deletedRecommendationLists.length > 0" icon="archive"
                                        label="Deleted Lists" severity="warning" outlined rounded size="xsmall"
                                        @click="showDeletedListsModal = true" />
                                    <AppButton icon="plus" label="Create Approval Request" severity="info" rounded
                                        size="small" @click="openCreateRecommendationListModal" />
                                </div>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex flex-wrap items-end gap-3 mb-4">
                                <InputGroup class="w-full sm:w-64">
                                    <InputGroupAddon>
                                        <AppIcon name="search" :size="14" class="text-gray-400" />
                                    </InputGroupAddon>
                                    <InputText v-model="filters.name" placeholder="Search by name..." size="small" />
                                </InputGroup>
                                <div class="flex flex-col">
                                    <Select v-model="filters.recommendation" :options="recommendationOptions"
                                        optionLabel="label" optionValue="value" placeholder="All Recommendations" size="small"
                                        class="w-full" />
                                </div>
                                <div class="flex flex-col">
                                    <CourseSelect v-model="filters.course" size="small" class="w-full" :load-all-when-no-program="true" />
                                </div>
                                <div class="ml-auto flex flex-wrap justify-end gap-2 self-center">
                                    <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                        size="xsmall" @click="clearInterviewedFilters" />
                                </div>
                            </div>

                            <!-- Active filter tags -->
                            <div v-if="activeFilterTags.length" class="flex flex-wrap items-center gap-2 px-2 py-2 border-b border-slate-100">
                                <span class="text-xs text-gray-500">Active Filters:</span>
                                <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                                    <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                                </Tag>
                            </div>

                            <div v-if="filteredRecommendationLists.length === 0"
                                class="py-10 text-center text-gray-500">
                                No saved approval requests yet
                            </div>

                            <DataTable v-else :value="filteredRecommendationLists" dataKey="id"
                                v-model:expandedRows="recommendationListExpandedRows" showGridlines stripedRows
                                scrollable responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean"
                                @rowContextmenu="(event) => openRecommendationListContextMenu(event.originalEvent, event.data)"
                                contextMenu>
                                <Column expander :exportable="false" headerClass="w-12" bodyClass="w-12" />
                                <Column field="list_number" header="List No." sortable headerClass="min-w-[160px]"
                                    bodyClass="min-w-[160px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.list_number }}</div>
                                        <div class="text-2xs text-slate-500">{{ slotProps.data.report_title }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span
                                                :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-3xs font-semibold', getRecommendationListApprovalBadgeClass(slotProps.data)]">
                                                {{ getRecommendationListApprovalLabel(slotProps.data) }}
                                            </span>
                                            <span v-if="slotProps.data.approved_at" class="text-3xs text-slate-500">
                                                {{ formatDateTime(slotProps.data.approved_at) }}
                                            </span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Applicants" sortable field="record_count" headerClass="min-w-[110px]"
                                    bodyClass="min-w-[110px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.record_count }}
                                        </div>
                                        <div class="text-2xs text-green-700 font-semibold">Recommended for Approval
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Projected Grant" sortable field="total_projected_expense"
                                    headerClass="min-w-[170px]" bodyClass="min-w-[170px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-emerald-700">
                                            {{ formatCurrency(slotProps.data.total_projected_expense) }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Budget Allocation" headerClass="min-w-[260px]"
                                    bodyClass="min-w-[260px]">
                                    <template #body="slotProps">
                                        <div v-if="slotProps.data.budget_allocation" class="leading-relaxed">
                                            <div class="font-semibold text-slate-800">{{
                                                formatBudgetAllocationTitle(slotProps.data.budget_allocation) }}</div>
                                            <div v-if="formatBudgetAllocationDescription(slotProps.data.budget_allocation)"
                                                class="text-2xs text-slate-500">
                                                {{ formatBudgetAllocationDescription(slotProps.data.budget_allocation)
                                                }}
                                            </div>
                                        </div>
                                        <div v-else class="text-xs leading-relaxed text-slate-500">
                                            No saved budget allocation
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Prepared By" headerClass="min-w-[180px]" bodyClass="min-w-[180px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.prepared_by || 'N/A'
                                        }}</div>
                                        <div class="text-2xs text-slate-500">{{ slotProps.data.prepared_by_position
                                            || 'Position not set' }}</div>
                                    </template>
                                </Column>
                                <Column header="Created" sortable field="created_at" headerClass="min-w-[170px]"
                                    bodyClass="min-w-[170px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{
                                            formatDateTime(slotProps.data.created_at) }}</div>
                                        <div class="text-2xs text-slate-500">{{ slotProps.data.creator?.name ||
                                            'Unknown user' }}</div>
                                    </template>
                                </Column>
                                <Column header="Actions" :style="{ width: '80px' }">
                                    <template #body="slotProps">
                                        <button class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 border-none cursor-pointer bg-transparent"
                                            @click="openRecommendationListContextMenu($event, slotProps.data)">
                                            <AppIcon name="ellipsis-vertical" :size="16" class="text-gray-500" />
                                        </button>
                                    </template>
                                </Column>

                                <template #expansion="slotProps">
                                    <div class="grid gap-4 px-4 pb-4 xl:grid-cols-[280px,1fr]">
                                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Transaction Summary</div>
                                            <dl class="mt-3 space-y-3 text-sm">
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">
                                                        Recommendation</dt>
                                                    <dd class="mt-1 font-semibold text-green-700">Recommended for
                                                        Approval</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">List
                                                        Approval</dt>
                                                    <dd class="mt-1">
                                                        <span
                                                            :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', getRecommendationListApprovalBadgeClass(slotProps.data)]">
                                                            {{ getRecommendationListApprovalLabel(slotProps.data) }}
                                                        </span>
                                                    </dd>
                                                    <dd class="text-xs text-slate-500">{{
                                                        formatRecommendationListApprovalMeta(slotProps.data) }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Prepared
                                                        By</dt>
                                                    <dd class="mt-1 font-semibold text-slate-800">{{
                                                        slotProps.data.prepared_by || 'N/A' }}</dd>
                                                    <dd class="text-xs text-slate-500">{{
                                                        slotProps.data.prepared_by_position || 'Position not set' }}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Approved
                                                        By</dt>
                                                    <dd class="mt-1 font-semibold text-slate-800">{{
                                                        slotProps.data.approved_by || 'N/A' }}</dd>
                                                    <dd class="text-xs text-slate-500">{{
                                                        slotProps.data.approved_by_position || 'Position not set' }}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Budget
                                                        Allocation</dt>
                                                    <dd v-if="slotProps.data.budget_allocation"
                                                        class="mt-1 leading-relaxed">
                                                        <div class="font-semibold text-slate-800">{{
                                                            formatBudgetAllocationTitle(slotProps.data.budget_allocation)
                                                        }}</div>
                                                        <div v-if="formatBudgetAllocationDescription(slotProps.data.budget_allocation)"
                                                            class="text-xs text-slate-500">
                                                            {{
                                                                formatBudgetAllocationDescription(slotProps.data.budget_allocation)
                                                            }}
                                                        </div>
                                                    </dd>
                                                    <dd v-else class="mt-1 text-xs leading-relaxed text-slate-500">No
                                                        saved budget allocation</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-xs uppercase tracking-wide text-slate-500">JPM
                                                        Highlight</dt>
                                                    <dd class="mt-1 text-xs leading-relaxed"
                                                        :class="slotProps.data.highlight_jpm_members ? 'font-semibold text-emerald-700' : 'text-slate-500'">
                                                        {{ slotProps.data.highlight_jpm_members ? `Enabled for printed
                                                        applicant names` : 'Disabled' }}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                                <div class="text-sm font-semibold text-slate-800">Saved Applicants
                                                    Snapshot</div>
                                                <div class="text-xs text-slate-500">The printed report uses this stored
                                                    selection.</div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                    <thead
                                                        class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left">Name</th>
                                                            <th class="px-4 py-3 text-left">Program</th>
                                                            <th class="px-4 py-3 text-left">School</th>
                                                            <th class="px-4 py-3 text-left">Projected Terms</th>
                                                            <th class="px-4 py-3 text-left">Projected Grant</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="record in slotProps.data.records"
                                                            :key="`recommendation-record-${slotProps.data.id}-${record.id}`">
                                                            <td class="px-4 py-3 font-semibold text-slate-800">
                                                                <span class="inline-block"
                                                                    :class="slotProps.data.highlight_jpm_members && recommendationRecordHasJpm(record) ? 'rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-900' : ''">
                                                                    {{ formatApplicantName(record) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-600">{{
                                                                record.program?.shortname || 'N/A' }}</td>
                                                            <td class="px-4 py-3 text-slate-600">{{
                                                                record.school?.shortname || record.school?.name || 'N/A'
                                                            }}</td>
                                                            <td class="px-4 py-3 text-slate-600">{{
                                                                formatProjectedTerms(record.projected_term_count) }}
                                                            </td>
                                                            <td class="px-4 py-3 font-semibold text-emerald-700">{{
                                                                formatCurrency(record.projected_total_expense) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
                        </Panel>

                        <IosModal v-model:visible="showDeletedListsModal" title="Soft-Deleted Lists"
                            width="calc(100vw - 2rem)" max-width="1100px" body-style="padding: 16px;">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <div>
                                    <div class="text-xs text-slate-500">Restore a list or permanently remove a record
                                        that is already soft-deleted.</div>
                                </div>
                                <span class="text-sm font-semibold text-amber-700">
                                    {{ filteredDeletedRecommendationLists.length }} deleted transaction(s)
                                </span>
                            </div>

                            <div v-if="filteredDeletedRecommendationLists.length === 0"
                                class="py-8 text-center text-gray-500">
                                No deleted approval requests match the current filters
                            </div>

                            <DataTable v-else :value="filteredDeletedRecommendationLists" dataKey="id" showGridlines
                                stripedRows scrollable responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean">
                                <Column field="list_number" header="List No." sortable headerClass="min-w-[170px]"
                                    bodyClass="min-w-[170px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.list_number }}</div>
                                        <div class="text-2xs text-slate-500">{{ slotProps.data.report_title }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span
                                                :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-3xs font-semibold', getRecommendationListApprovalBadgeClass(slotProps.data)]">
                                                {{ getRecommendationListApprovalLabel(slotProps.data) }}
                                            </span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Applicants" sortable field="record_count" headerClass="min-w-[120px]"
                                    bodyClass="min-w-[120px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.record_count }}
                                        </div>
                                        <div class="text-2xs text-slate-500">Stored snapshot</div>
                                    </template>
                                </Column>
                                <Column header="Budget Allocation" headerClass="min-w-[260px]"
                                    bodyClass="min-w-[260px]">
                                    <template #body="slotProps">
                                        <div v-if="slotProps.data.budget_allocation" class="leading-relaxed">
                                            <div class="font-semibold text-slate-800">{{
                                                formatBudgetAllocationTitle(slotProps.data.budget_allocation) }}</div>
                                            <div v-if="formatBudgetAllocationDescription(slotProps.data.budget_allocation)"
                                                class="text-2xs text-slate-500">
                                                {{ formatBudgetAllocationDescription(slotProps.data.budget_allocation)
                                                }}
                                            </div>
                                        </div>
                                        <div v-else class="text-xs leading-relaxed text-slate-500">
                                            No saved budget allocation
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Deleted" sortable field="deleted_at" headerClass="min-w-[180px]"
                                    bodyClass="min-w-[180px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">
                                            {{ formatDateTime(slotProps.data.deleted_at) }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Actions" :style="{ width: '320px' }">
                                    <template #body="slotProps">
                                        <div class="flex flex-wrap gap-2">
                                            <AppButton icon="rotate-ccw" label="Restore" severity="warning" outlined
                                                rounded size="small"
                                                @click="restoreRecommendationList(slotProps.data)" />
                                            <AppButton icon="trash" label="Delete Permanently" severity="danger" rounded
                                                size="small" @click="forceDeleteRecommendationList(slotProps.data)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </IosModal>
                    </TabPanel>

                    <TabPanel value="all">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm mt-4">
                            <!-- View Tabs -->
                            <div class="mb-4 -mt-2 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-1" role="tablist" aria-label="Interviewed applicant views">
                                    <button type="button" role="tab" :aria-selected="activeTab === 'interviewed'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'interviewed'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'interviewed'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="clipboard-list" :size="14" />
                                            <span>Interviewed</span>
                                            <span
                                                class="rounded-full bg-blue-50 px-2 py-0.5 text-2xs font-semibold text-blue-700">
                                                {{ filteredList.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'recommendation-lists'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'recommendation-lists'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'recommendation-lists'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list-checks" :size="14" />
                                            <span>Approval Requests</span>
                                            <span
                                                class="rounded-full bg-emerald-50 px-2 py-0.5 text-2xs font-semibold text-emerald-700">
                                                {{ recommendationLists.length }}
                                            </span>
                                        </div>
                                    </button>
                                    <button type="button" role="tab" :aria-selected="activeTab === 'all'"
                                        class="cursor-pointer border-b-2 px-3 py-2 text-sm transition-colors"
                                        :class="activeTab === 'all'
                                            ? 'border-blue-500 font-semibold text-blue-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        @click="activeTab = 'all'">
                                        <div class="flex items-center gap-2">
                                            <AppIcon name="list" :size="14" />
                                            <span>All</span>
                                            <span
                                                class="rounded-full bg-amber-50 px-2 py-0.5 text-2xs font-semibold text-amber-700">
                                                {{ recommendationListAuditRecords.length }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span v-if="bypassedAuditCount > 0" class="text-sm font-semibold text-red-600">
                                        {{ bypassedAuditCount }} processed outside an approval request
                                    </span>
                                    <AppButton icon="file-spreadsheet" label="Export Excel" severity="success" outlined
                                        rounded size="xsmall" @click="exportAuditRecords" />
                                </div>
                            </div>

                            <div v-if="selectedAuditRows.length > 0"
                                class="mb-4 rounded-3xl border border-yellow-200 bg-yellow-50 p-3">
                                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="flex items-center gap-3">
                                        <AppIcon name="check-circle" :size="18" class="text-yellow-600" />
                                        <div>
                                            <div class="font-semibold text-yellow-900 text-sm">{{ selectedAuditRows.length }}
                                                record(s) selected</div>
                                            <div class="text-xs text-yellow-700">
                                                Export the current selection.
                                            </div>
                                        </div>
                                    </div>
                                    <AppButton icon="file-spreadsheet" label="Export Excel" severity="success" outlined
                                        rounded size="small" @click="exportSelectedAuditRecords" />
                                </div>
                            </div>

                            <!-- Filters above table -->
                            <div class="flex flex-wrap items-end gap-3 mb-4">
                                <InputGroup class="w-full sm:w-64">
                                    <InputGroupAddon>
                                        <AppIcon name="search" :size="14" class="text-gray-400" />
                                    </InputGroupAddon>
                                    <InputText v-model="filters.name" placeholder="Search by name..." size="small" />
                                </InputGroup>
                                <div class="flex items-center gap-2">
                                    <Checkbox v-model="auditOnlyBypassed" binary inputId="auditOnlyBypassed" />
                                    <label for="auditOnlyBypassed" class="cursor-pointer text-sm text-gray-700">
                                        Show only processed-outside-request records
                                    </label>
                                </div>
                            </div>

                            <div v-if="filteredAuditRecords.length === 0" class="py-10 text-center text-gray-500">
                                No approval request records found
                            </div>

                            <DataTable v-else :value="filteredAuditRecords" dataKey="id" showGridlines stripedRows
                                scrollable responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean">
                                <Column :exportable="false" headerClass="w-12" bodyClass="w-12">
                                    <template #header>
                                        <div class="flex justify-center">
                                            <Checkbox :modelValue="allFilteredAuditRowsSelected" binary
                                                :indeterminate="someFilteredAuditRowsSelected"
                                                :disabled="filteredAuditRecords.length === 0"
                                                @update:modelValue="toggleSelectAllFilteredAuditRows" />
                                        </div>
                                    </template>
                                    <template #body="slotProps">
                                        <div class="flex justify-center">
                                            <Checkbox :modelValue="isAuditRowSelected(slotProps.data)" binary
                                                @update:modelValue="(checked) => toggleAuditRowSelection(slotProps.data, checked)" />
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Name" headerClass="min-w-[220px]" bodyClass="min-w-[220px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ formatApplicantName(slotProps.data) }}</div>
                                    </template>
                                </Column>
                                <Column header="Program / Course / School" headerClass="min-w-[240px]" bodyClass="min-w-[240px]">
                                    <template #body="slotProps">
                                        <div class="text-slate-700">{{ slotProps.data.program || 'N/A' }} — {{ slotProps.data.course || 'N/A' }}</div>
                                        <div class="text-2xs text-slate-500">{{ slotProps.data.school || 'N/A' }}</div>
                                    </template>
                                </Column>
                                <Column header="Current Status" headerClass="min-w-[160px]" bodyClass="min-w-[160px]">
                                    <template #body="slotProps">
                                        <span
                                            :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-3xs font-semibold', auditStatusBadgeClass(slotProps.data.unified_status)]">
                                            {{ auditStatusLabel(slotProps.data.unified_status) }}
                                        </span>
                                        <div v-if="slotProps.data.date_approved" class="mt-1 text-2xs text-slate-500">
                                            Approved {{ formatDate(slotProps.data.date_approved) }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="In Approval Request(s)" headerClass="min-w-[240px]" bodyClass="min-w-[240px]">
                                    <template #body="slotProps">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="listEntry in slotProps.data.lists" :key="listEntry.list_number"
                                                :class="['inline-flex items-center rounded-full px-2 py-0.5 text-3xs font-semibold',
                                                    listEntry.approved_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">
                                                {{ listEntry.list_number }}
                                            </span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Flag" headerClass="min-w-[220px]" bodyClass="min-w-[220px]">
                                    <template #body="slotProps">
                                        <span v-if="slotProps.data.processed_outside_list"
                                            class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-3xs font-semibold text-red-700">
                                            <AppIcon name="alert-triangle" :size="12" />
                                            Processed outside this request
                                        </span>
                                        <span v-else class="text-2xs text-slate-400">—</span>
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

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

        <ContextMenu ref="recommendationListContextMenu" :model="recommendationListContextMenuItems" appendTo="body">
            <template #item="{ item, props }">
                <a v-ripple v-bind="props.action" class="flex items-center gap-2 w-full">
                    <AppIcon v-if="item.icon" :name="item.icon" :size="14" />
                    <span>{{ item.label }}</span>
                </a>
            </template>
        </ContextMenu>

        <!-- Generate Report Modal -->
        <CumulativeScholarListModal :show="showCumulativeScholarListModal"
            @update:show="showCumulativeScholarListModal = $event" :budget-allocations="props.budget_allocations" />

        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event"
            :interviewed-applicants="filteredList" :budget-allocations="props.budget_allocations" />

        <CreateRecommendationListModal :show="showCreateRecommendationListModal"
            @update:show="handleRecommendationListModalVisibility"
            :applicants="recommendationApplicantPool" :budget-allocations="props.budget_allocations"
            :default-prepared-by="currentUser?.name || ''" :loading="isCreatingRecommendationList"
            :mode="recommendationListModalMode" :initial-data="editingRecommendationList"
            :submit-intent="recommendationListSubmitIntent" @submit="submitRecommendationList" />

        <AssessmentViewModal v-model:show="showAssessmentDialog" :record="selectedRecord"
            :initial-mode="assessmentInitialMode" :can-manage="canManageActions" :can-revert="canManageActions"
            :deny-form="denyForm" :decline-reasons="declineReasons"
            :interviewers="interviewers" @updated="onAssessmentUpdated"
            @confirm-deny="confirmDeny" @revert="confirmRevert" />

        <PdfPreviewModal v-model:show="showRecommendationListPreview" :htmlDoc="recommendationListPreviewHtml"
            :title="recommendationListPreviewTitle" :paperSize="recommendationListPreviewPaperSize"
            :onExcel="exportPreviewedRecommendationListExcel" />

        <!-- Confirmation Dialog -->
        <IosModal v-model:visible="confirmDialogVisible" :title="confirmDialogHeader" width="calc(100vw - 2rem)"
            max-width="450px" body-style="padding: 16px;">
            <template #header-right>
                <button
                    class="ios-nav-btn ios-nav-action text-nav"
                    type="button"
                    @click="handleConfirmDialogAccept"
                >
                    
                    <AppIcon name="check" :size="18" />
                </button>
            </template>
            <div class="flex items-start gap-3">
                <i v-if="confirmDialogIcon" :class="confirmDialogIcon" class="text-xl mt-0.5" />
                <p class="m-0 text-sm leading-relaxed text-gray-700">{{ confirmDialogMessage }}</p>
            </div>
        </IosModal>
    </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, useForm, Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppIcon from '@/Components/ui/AppIcon.vue';
import axios from 'axios';
import AppButton from '@/Components/ui/AppButton.vue';
import moment from 'moment';
import IosModal from '@/Components/ui/IosModal.vue';
import { toast } from '@/utils/toast';
import { usePermission } from '@/composable/permissions';
import { useApi } from '@/composable/api';

import CourseSelect from '@/Components/selects/CourseSelect.vue';
import ContextMenu from 'primevue/contextmenu';
import AssessmentViewModal from './Modal/AssessmentViewModal.vue';
import CumulativeScholarListModal from './Modal/CumulativeScholarListModal.vue';
import CreateRecommendationListModal from './Modal/CreateRecommendationListModal.vue';
import GenerateReportModal from './Modal/GenerateReportModalIOS.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';
import {
    exportInterviewedApplicantsExcel,
    exportRecommendationListExcel,
    exportRecommendationListAuditExcel,
    printRecommendationList,
    buildRecommendationListHtml,
} from './interviewedApplicantsExport';

const { hasRole } = usePermission();
const page = usePage();

const props = defineProps({
    interviewed_applicants: Array,
    interviewed_applicants_pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            per_page: 100,
            total: 0,
            last_page: 1,
            from: 0,
            to: 0,
        }),
    },
    interviewed_applicants_filters: {
        type: Object,
        default: () => ({
            recommendation: null,
            name: '',
            program: null,
            course: null,
        }),
    },
    interviewed_applicants_stats: {
        type: Object,
        default: () => ({
            total: 0,
            recommended: 0,
            further_eval: 0,
            not_recommended: 0,
        }),
    },
    budget_allocations: {
        type: Array,
        default: () => [],
    },
    decline_reasons: Object,
    interviewers: {
        type: Array,
        default: () => [],
    },
    recommendation_lists: {
        type: Array,
        default: () => [],
    },
    deleted_recommendation_lists: {
        type: Array,
        default: () => [],
    },
    recommendation_list_audit_records: {
        type: Array,
        default: () => [],
    },
});

const recommendationListReloadProps = [
    'interviewed_applicants',
    'interviewed_applicants_pagination',
    'budget_allocations',
    'recommendation_lists',
    'deleted_recommendation_lists',
    'recommendation_list_audit_records',
];

// State
const activeTab = ref(sessionStorage.getItem('interviewed_applicants_tab') || 'interviewed');
const filters = ref({
    recommendation: null,
    name: '',
    program: null,
    course: null,
    listStatus: null,
});

// Program tabs (toolbar center) — same active-program list ProgramSelect uses
const { data: programsData, fetchData: fetchPrograms } = useApi(route('scholarshipprograms.getactivelist'));
const programs = computed(() => programsData.value || []);
onMounted(fetchPrograms);

// Fallback dot colors for programs without a bg_color
const programDotColors = ['#6366F1', '#8B5CF6', '#EC4899', '#F43F5E', '#F97316', '#EAB308', '#22C55E', '#14B8A6', '#06B6D4', '#3B82F6'];

const isProgramTabActive = (program) => {
    const current = filters.value?.program;
    if (!current || !program) return false;
    if (current.id != null && program.id != null && String(current.id) === String(program.id)) return true;
    const currentName = (current.shortname || current.name || '').toLowerCase();
    const programName = (program.shortname || program.name || '').toLowerCase();
    return currentName !== '' && currentName === programName;
};

const selectProgramTab = (program) => {
    if (!program) {
        if (!filters.value.program) return;
        filters.value.program = null;
    } else {
        if (isProgramTabActive(program)) return;
        filters.value.program = program;
    }
    // The filters watcher fires onFilterChange()
};

const contextMenu = ref();
const recommendationListContextMenu = ref();
const showAssessmentDialog = ref(false);
const assessmentInitialMode = ref('view');
const showCumulativeScholarListModal = ref(false);
const showReportModal = ref(false);
const showCreateRecommendationListModal = ref(false);
const showDeletedListsModal = ref(false);
const isCreatingRecommendationList = ref(false);
const recommendationListModalMode = ref('create');
const recommendationListSubmitIntent = ref('save');
const editingRecommendationList = ref(null);
const selectedRows = ref([]);
const expandedRows = ref({});
const recommendationLists = ref([...(props.recommendation_lists || [])]);
const recommendationListAuditRecords = ref([...(props.recommendation_list_audit_records || [])]);
const auditOnlyBypassed = ref(false);
const deletedRecommendationLists = ref([...(props.deleted_recommendation_lists || [])]);
const recommendationListExpandedRows = ref({});
const showRecommendationListPreview = ref(false);
const recommendationListPreviewHtml = ref('');
const recommendationListPreviewTitle = ref('');
const recommendationListPreviewPaperSize = ref('a4');
const previewedRecommendationList = ref(null);

// Confirmation Dialog
const confirmDialogVisible = ref(false);
const confirmDialogHeader = ref('');
const confirmDialogMessage = ref('');
const confirmDialogAcceptLabel = ref('');
const confirmDialogIcon = ref('');
const confirmDialogSeverity = ref('primary');
const confirmDialogOnAccept = ref(null);

function openConfirmDialog({ header, message, acceptLabel, icon, severity, onAccept }) {
    confirmDialogHeader.value = header;
    confirmDialogMessage.value = message;
    confirmDialogAcceptLabel.value = acceptLabel;
    confirmDialogIcon.value = icon || '';
    confirmDialogSeverity.value = severity || 'primary';
    confirmDialogOnAccept.value = onAccept;
    confirmDialogVisible.value = true;
}

function handleConfirmDialogAccept() {
    confirmDialogVisible.value = false;
    if (typeof confirmDialogOnAccept.value === 'function') {
        confirmDialogOnAccept.value();
    }
}

const denyForm = useForm({
    reason: '',
    details: ''
});

const selectedRecord = ref(null);
const selectedRecommendationList = ref(null);
const canManageActions = computed(() => hasRole('administrator') || hasRole('program_manager') || hasRole('screening_officer'));
const currentUser = computed(() => page.props.auth?.user ?? null);

// Options
const recommendationOptions = [
    { label: 'All Recommendations', value: null },
    { label: 'Recommended for Approval', value: 'recommended' },
    { label: 'For Further Evaluation', value: 'further_evaluation' },
    { label: 'Not Recommended', value: 'not_recommended' }
];

const recommendationListStatusOptions = [
    { label: 'All Applicants', value: null },
    { label: 'Not Yet in Approval Request', value: 'available' },
    { label: 'Already in Approval Request', value: 'included' },
];

const getRecommendationListStatusLabel = (value) => {
    return recommendationListStatusOptions.find((option) => option.value === value)?.label || 'All Applicants';
};

const declineReasons = computed(() => {
    if (!props.decline_reasons) return [];
    return Object.entries(props.decline_reasons).map(([value, label]) => ({
        value,
        label
    }));
});

const normalizedNameFilter = computed(() => filters.value.name?.trim().toLowerCase() || '');

const selectedProgramId = computed(() => {
    return typeof filters.value.program === 'object'
        ? filters.value.program?.id
        : filters.value.program;
});

const recordMatchesName = (record, query) => {
    if (!query) {
        return true;
    }

    const firstName = record?.profile?.first_name ?? '';
    const lastName = record?.profile?.last_name ?? '';
    const middleName = record?.profile?.middle_name ?? '';
    const haystack = [
        firstName,
        lastName,
        `${firstName} ${lastName}`,
        `${lastName}, ${firstName}`,
        `${lastName}, ${firstName} ${middleName}`,
    ]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
};

const recordMatchesProgram = (record, programId) => {
    if (!programId) {
        return true;
    }

    return String(record?.program?.id ?? '') === String(programId);
};

const selectedCourseId = computed(() => {
    return typeof filters.value.course === 'object'
        ? filters.value.course?.id
        : filters.value.course;
});

const recordMatchesCourse = (record, courseId) => {
    if (!courseId) {
        return true;
    }

    return String(record?.course?.id ?? '') === String(courseId);
};

// Computed
const recommendationRecordIndex = computed(() => {
    const recordIndex = new Map();

    recommendationLists.value.forEach((recommendationList) => {
        const listNumber = recommendationList?.list_number;
        const sourceRecordIds = Array.isArray(recommendationList?.selected_record_ids) && recommendationList.selected_record_ids.length > 0
            ? recommendationList.selected_record_ids
            : (recommendationList?.records || []).map((record) => record.id);

        sourceRecordIds.forEach((recordId) => {
            const normalizedRecordId = Number(recordId);

            if (!Number.isFinite(normalizedRecordId)) {
                return;
            }

            if (!recordIndex.has(normalizedRecordId)) {
                recordIndex.set(normalizedRecordId, new Set());
            }

            if (listNumber) {
                recordIndex.get(normalizedRecordId).add(listNumber);
            }
        });
    });

    return recordIndex;
});

const interviewedApplicantsWithRecommendationFlags = computed(() => {
    return (props.interviewed_applicants || []).map((record) => {
        const recommendationListNumbers = Array.from(recommendationRecordIndex.value.get(Number(record.id)) || []);

        return {
            ...record,
            is_in_recommendation_list: recommendationListNumbers.length > 0,
            recommendation_list_numbers: recommendationListNumbers,
        };
    });
});

const activeFilterTags = computed(() => {
    const tags = [];

    if (filters.value.name?.trim()) {
        tags.push({
            key: 'name',
            label: 'Search',
            display: filters.value.name.trim(),
        });
    }

    if (filters.value.recommendation) {
        tags.push({
            key: 'recommendation',
            label: 'Recommendation',
            display: formatRecommendation(filters.value.recommendation),
        });
    }

    if (filters.value.program) {
        tags.push({
            key: 'program',
            label: 'Program',
            display: filters.value.program?.shortname || filters.value.program?.name || 'N/A',
        });
    }

    if (filters.value.course) {
        tags.push({
            key: 'course',
            label: 'Course',
            display: filters.value.course?.shortname || filters.value.course?.name || 'N/A',
        });
    }

    if (activeTab.value === 'interviewed' && filters.value.listStatus) {
        tags.push({
            key: 'listStatus',
            label: 'Approval Request',
            display: getRecommendationListStatusLabel(filters.value.listStatus),
        });
    }

    return tags;
});

const filteredList = computed(() => {
    let list = interviewedApplicantsWithRecommendationFlags.value;
    const nameQuery = normalizedNameFilter.value;
    const programId = selectedProgramId.value;
    const courseId = selectedCourseId.value;

    if (filters.value.recommendation) {
        list = list.filter(r => r.recommendation === filters.value.recommendation);
    }

    if (nameQuery) {
        list = list.filter((record) => recordMatchesName(record, nameQuery));
    }

    if (programId) {
        list = list.filter((record) => recordMatchesProgram(record, programId));
    }

    if (courseId) {
        list = list.filter((record) => recordMatchesCourse(record, courseId));
    }

    if (filters.value.listStatus === 'available') {
        list = list.filter((record) => !record.is_in_recommendation_list);
    }

    if (filters.value.listStatus === 'included') {
        list = list.filter((record) => record.is_in_recommendation_list);
    }

    return list;
});

const selectedRowIds = computed(() => new Set(selectedRows.value.map((record) => Number(record.id))));

const allFilteredRowsSelected = computed(() => {
    return filteredList.value.length > 0
        && filteredList.value.every((record) => selectedRowIds.value.has(Number(record.id)));
});

const someFilteredRowsSelected = computed(() => {
    return !allFilteredRowsSelected.value
        && filteredList.value.some((record) => selectedRowIds.value.has(Number(record.id)));
});

const filterRecommendationLists = (listSource) => {
    let list = [...(listSource || [])];
    const nameQuery = normalizedNameFilter.value;
    const programId = selectedProgramId.value;

    if (filters.value.recommendation) {
        list = list.filter((recommendationList) => {
            if (recommendationList.recommendation_status) {
                return recommendationList.recommendation_status === filters.value.recommendation;
            }

            return (recommendationList.records || []).some((record) => record.recommendation === filters.value.recommendation);
        });
    }

    if (nameQuery) {
        list = list.filter((recommendationList) => {
            return (recommendationList.records || []).some((record) => recordMatchesName(record, nameQuery));
        });
    }

    if (programId) {
        list = list.filter((recommendationList) => {
            return (recommendationList.records || []).some((record) => recordMatchesProgram(record, programId));
        });
    }

    return list;
};

const filteredRecommendationLists = computed(() => filterRecommendationLists(recommendationLists.value));

const filteredDeletedRecommendationLists = computed(() => filterRecommendationLists(deletedRecommendationLists.value));

// "All" audit view — every record ever added to an approval request, with its
// live current status, so staff can spot records that moved past "interviewed"
// (active/completed/denied) while every request containing them is still unapproved.
const bypassedAuditCount = computed(() => recommendationListAuditRecords.value.filter((r) => r.processed_outside_list).length);

const filteredAuditRecords = computed(() => {
    const nameQuery = normalizedNameFilter.value;
    let list = recommendationListAuditRecords.value;

    if (auditOnlyBypassed.value) {
        list = list.filter((r) => r.processed_outside_list);
    }

    if (nameQuery) {
        list = list.filter((record) => recordMatchesName(record, nameQuery));
    }

    return list;
});

const auditStatusLabels = {
    interviewed: 'Interviewed',
    active: 'Active',
    completed: 'Completed',
    denied: 'Denied',
    approved: 'Approved',
};

const auditStatusLabel = (status) => auditStatusLabels[status] || status || 'Unknown';

const auditStatusBadgeClass = (status) => {
    if (status === 'interviewed') return 'bg-blue-50 text-blue-700';
    if (status === 'denied') return 'bg-red-50 text-red-700';
    if (status === 'active' || status === 'completed' || status === 'approved') return 'bg-emerald-50 text-emerald-700';
    return 'bg-gray-100 text-gray-600';
};

// Selection + export-selected for the "All" audit view, mirroring the
// Interviewed tab's selection pattern.
const selectedAuditRows = ref([]);
const selectedAuditRowIds = computed(() => new Set(selectedAuditRows.value.map((record) => Number(record.id))));

const isAuditRowSelected = (record) => selectedAuditRowIds.value.has(Number(record?.id));

const toggleAuditRowSelection = (record, checked) => {
    const recordId = Number(record.id);

    if (checked) {
        if (!selectedAuditRowIds.value.has(recordId)) {
            selectedAuditRows.value = [...selectedAuditRows.value, record];
        }
        return;
    }

    selectedAuditRows.value = selectedAuditRows.value.filter((selectedRecord) => Number(selectedRecord.id) !== recordId);
};

const allFilteredAuditRowsSelected = computed(() => {
    return filteredAuditRecords.value.length > 0
        && filteredAuditRecords.value.every((record) => selectedAuditRowIds.value.has(Number(record.id)));
});

const someFilteredAuditRowsSelected = computed(() => {
    return !allFilteredAuditRowsSelected.value
        && filteredAuditRecords.value.some((record) => selectedAuditRowIds.value.has(Number(record.id)));
});

const toggleSelectAllFilteredAuditRows = (checked) => {
    if (checked) {
        const selectedById = new Map(selectedAuditRows.value.map((record) => [Number(record.id), record]));
        filteredAuditRecords.value.forEach((record) => selectedById.set(Number(record.id), record));
        selectedAuditRows.value = Array.from(selectedById.values());
        return;
    }

    selectedAuditRows.value = [];
};

const resetDenyForm = () => {
    denyForm.reset();
    denyForm.clearErrors();
};

const openAssessmentDialog = (record, mode = 'view') => {
    selectedRecord.value = record;
    assessmentInitialMode.value = mode;

    if (mode === 'deny') {
        resetDenyForm();
    }

    showAssessmentDialog.value = true;
};

const stats = computed(() => ({
    total: props.interviewed_applicants_stats?.total ?? 0,
    recommended: props.interviewed_applicants_stats?.recommended ?? 0,
    furtherEval: props.interviewed_applicants_stats?.further_eval ?? 0,
    notRecommended: props.interviewed_applicants_stats?.not_recommended ?? 0,
}));

// Approval Request Context Menu
const openRecommendationListContextMenu = (event, recommendationList) => {
    selectedRecommendationList.value = recommendationList;
    recommendationListContextMenu.value.show(event);
};

const recommendationListContextMenuItems = computed(() => {
    if (!selectedRecommendationList.value) {
        return [];
    }

    const recommendationList = selectedRecommendationList.value;
    const items = [];

    if (recommendationList.is_approved) {
        items.push({
            label: 'Revert Approval',
            icon: 'rotate-ccw',
            command: () => revertRecommendationListApproval(recommendationList),
        });
    } else {
        items.push({
            label: 'Approve',
            icon: 'check-circle',
            command: () => approveRecommendationList(recommendationList),
        });
    }

    items.push(
        {
            label: 'Print',
            icon: 'printer',
            command: () => printSavedRecommendationList(recommendationList),
        },
        {
            label: 'Update List',
            icon: 'refresh-cw',
            command: () => openUpdateListModal(recommendationList),
        },
        {
            label: 'Settings',
            icon: 'settings',
            command: () => openEditRecommendationListModal(recommendationList),
        },
        {
            label: 'Delete',
            icon: 'trash',
            command: () => deleteRecommendationList(recommendationList),
        },
    );

    return items;
});

// Context Menu
const contextMenuItems = computed(() => {
    const items = [
        {
            label: 'View Assessment',
            icon: 'file',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'view');
                }
            }
        },
        {
            label: 'Edit Assessment',
            icon: 'pencil',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'edit');
                }
            }
        }
    ];

    if (canManageActions.value) {
        items.push({ separator: true });
        items.push({
            label: 'Deny',
            icon: 'x',
            class: 'p-menuitem-danger',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'deny');
                }
            }
        });

        items.push({ separator: true });
        items.push({
            label: 'Revert to Pending',
            icon: 'arrow-left',
            disabled: Boolean(selectedRecord.value?.is_in_recommendation_list),
            command: () => {
                if (selectedRecord.value) {
                    promptRevertStatus(selectedRecord.value);
                }
            }
        });
    }

    return items;
});

const openContextMenu = (event, record) => {
    selectedRecord.value = record;
    contextMenu.value.show(event);
};

// Recommendation lists must be scoped to a single program — once a selection
// has started, rows from a different program are disabled from being added.
const selectionAnchorProgramId = computed(() => {
    return selectedRows.value.length > 0 ? (selectedRows.value[0]?.program?.id ?? null) : null;
});

const isRowSelectable = (record) => {
    if (selectionAnchorProgramId.value === null) return true;
    return String(record?.program?.id ?? '') === String(selectionAnchorProgramId.value);
};

const isRowSelected = (record) => selectedRowIds.value.has(Number(record?.id));

const toggleRowSelection = (record, checked) => {
    if (!isRowSelectable(record)) {
        return;
    }

    const recordId = Number(record.id);

    if (checked) {
        if (!selectedRowIds.value.has(recordId)) {
            selectedRows.value = [...selectedRows.value, record];
        }

        return;
    }

    selectedRows.value = selectedRows.value.filter((selectedRecord) => Number(selectedRecord.id) !== recordId);
};

const toggleSelectAllFilteredRows = (checked) => {
    if (checked) {
        const selectedById = new Map(selectedRows.value.map((record) => [Number(record.id), record]));

        filteredList.value.forEach((record) => {
            if (isRowSelectable(record)) {
                selectedById.set(Number(record.id), record);
            }
        });

        selectedRows.value = Array.from(selectedById.values());
        return;
    }

    selectedRows.value = [];
};

const syncSelectedRows = () => {
    const currentRecordsById = new Map(
        interviewedApplicantsWithRecommendationFlags.value.map((record) => [Number(record.id), record]),
    );

    selectedRows.value = selectedRows.value
        .map((record) => currentRecordsById.get(Number(record.id)))
        .filter((record) => Boolean(record));
};

// --- Pagination ---
const currentPage = ref(props.interviewed_applicants_pagination?.current_page ?? 1);
const perPage = ref(props.interviewed_applicants_pagination?.per_page ?? 100);

const fetchPage = (page, perPageValue) => {
    const params = {};

    if (page && page !== currentPage.value) {
        params.page = page;
    }
    if (perPageValue && perPageValue !== perPage.value) {
        params.per_page = perPageValue;
    }

    // Pass current filters
    if (filters.value.recommendation) {
        params.recommendation = filters.value.recommendation;
    }
    if (filters.value.name?.trim()) {
        params.name = filters.value.name.trim();
    }
    if (filters.value.program) {
        const programId = typeof filters.value.program === 'object'
            ? filters.value.program?.id
            : filters.value.program;
        if (programId) {
            params.program = programId;
        }
    }
    if (filters.value.course) {
        const courseId = typeof filters.value.course === 'object'
            ? filters.value.course?.id
            : filters.value.course;
        if (courseId) {
            params.course = courseId;
        }
    }

    router.get(route('scholarship.interviewed-applicants'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['interviewed_applicants', 'interviewed_applicants_pagination', 'interviewed_applicants_filters'],
        onSuccess: () => {
            if (page) currentPage.value = page;
            if (perPageValue) perPage.value = perPageValue;
        },
    });
};

// "Show More" — grow the requested page size and refetch from the start,
// so the accumulated list keeps growing instead of paging through slices.
const hasMoreInterviewed = computed(() =>
    (props.interviewed_applicants?.length || 0) < (props.interviewed_applicants_pagination?.total || 0)
);

const loadMoreInterviewed = () => {
    const batchSize = props.interviewed_applicants_pagination?.per_page || 100;
    fetchPage(1, perPage.value + batchSize);
};

let filterTimeout = null;

const onFilterChange = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchPage(1, perPage.value);
    }, 400);
};

watch(() => filters.value.recommendation, onFilterChange);
watch(() => filters.value.name, onFilterChange);
watch(() => filters.value.program, onFilterChange);
watch(() => filters.value.course, onFilterChange);

// Methods
const onAssessmentUpdated = (changes) => {
    if (selectedRecord.value) {
        selectedRecord.value = {
            ...selectedRecord.value,
            ...changes,
        };
    }

    router.reload({
        only: recommendationListReloadProps,
        preserveState: true,
        preserveScroll: true,
    });
};

const openReportModal = () => {
    if ((props.budget_allocations || []).length > 0) {
        showReportModal.value = true;
        return;
    }

    router.reload({
        only: recommendationListReloadProps,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            showReportModal.value = true;
        },
    });
};

const openCumulativeScholarListModal = () => {
    if ((props.budget_allocations || []).length > 0) {
        showCumulativeScholarListModal.value = true;
        return;
    }

    router.reload({
        only: recommendationListReloadProps,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            showCumulativeScholarListModal.value = true;
        },
    });
};

const clearInterviewedFilters = () => {
    filters.value.recommendation = null;
    filters.value.name = '';
    filters.value.program = null;
    filters.value.course = null;
    filters.value.listStatus = null;
    currentPage.value = 1;
    fetchPage(1, perPage.value);
};

const presetRecommendationCreationFilters = () => {
    filters.value.recommendation = 'recommended';
    filters.value.listStatus = 'available';
    currentPage.value = 1;
    fetchPage(1, perPage.value);
};

const handleRecommendationListModalVisibility = (value) => {
    showCreateRecommendationListModal.value = value;

    if (!value) {
        recommendationListModalMode.value = 'create';
        recommendationListSubmitIntent.value = 'save';
        editingRecommendationList.value = null;
    }
};

const openCreateRecommendationListModal = () => {
    recommendationListModalMode.value = 'create';
    recommendationListSubmitIntent.value = 'save';
    editingRecommendationList.value = null;
    showCreateRecommendationListModal.value = true;
};

const openEditRecommendationListModal = (recommendationList) => {
    editingRecommendationList.value = recommendationList;
    recommendationListModalMode.value = 'edit';
    recommendationListSubmitIntent.value = 'save';
    showCreateRecommendationListModal.value = true;
};

const openPrintRecommendationListModal = (recommendationList) => {
    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for printing.');
        return;
    }

    editingRecommendationList.value = recommendationList;
    recommendationListModalMode.value = 'edit';
    recommendationListSubmitIntent.value = 'print';
    showCreateRecommendationListModal.value = true;
};

const upsertRecommendationList = (recommendationList) => {
    recommendationLists.value = [
        recommendationList,
        ...recommendationLists.value.filter((existingRecommendationList) => existingRecommendationList.id !== recommendationList.id),
    ];

    deletedRecommendationLists.value = deletedRecommendationLists.value.filter(
        (existingRecommendationList) => existingRecommendationList.id !== recommendationList.id,
    );

    if (editingRecommendationList.value?.id === recommendationList.id) {
        editingRecommendationList.value = recommendationList;
    }
};

const upsertDeletedRecommendationList = (recommendationList) => {
    deletedRecommendationLists.value = [
        recommendationList,
        ...deletedRecommendationLists.value.filter((existingRecommendationList) => existingRecommendationList.id !== recommendationList.id),
    ];

    recommendationLists.value = recommendationLists.value.filter(
        (existingRecommendationList) => existingRecommendationList.id !== recommendationList.id,
    );

    if (editingRecommendationList.value?.id === recommendationList.id) {
        editingRecommendationList.value = recommendationList;
    }
};

const collapseRecommendationListRow = (recommendationListId) => {
    const nextExpandedRows = { ...recommendationListExpandedRows.value };
    delete nextExpandedRows[recommendationListId];
    recommendationListExpandedRows.value = nextExpandedRows;
};

const confirmDeny = () => {
    if (!selectedRecord.value || !denyForm.reason || !denyForm.details) {
        toast.error('Please fill in all required fields');
        return;
    }

    denyForm.post(route('scholarship.record.decline', selectedRecord.value.id), {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Application denied successfully');
        },
        onError: (errors) => {
            toast.error('Failed to deny application');
            console.error(errors);
        }
    });
};

const revertStatus = (record) => {
    router.patch(route('scholarship.record.update-status', record.id), {
        unified_status: 'pending'
    }, {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Status reverted to pending');
        },
        onError: () => {
            toast.error('Failed to revert status');
        }
    });
};

// Revert to Pending needs a confirmation, and is blocked for records already
// included in an approval request (they must be removed from the request first).
const promptRevertStatus = (record) => {
    if (!record) return;

    if (record.is_in_recommendation_list) {
        toast.error('This applicant is already in an approval request. Remove them from it before reverting to pending.');
        return;
    }

    const applicantName = formatApplicantName(record);

    openConfirmDialog({
        header: 'Revert to Pending',
        message: `Revert ${applicantName} to Pending? This clears their interview assessment and recommendation.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Revert',
        severity: 'warning',
        onAccept: () => {
            revertStatus(record);
        },
    });
};

const confirmRevert = () => {
    if (!selectedRecord.value) return;

    promptRevertStatus(selectedRecord.value);
};

const formatDate = (date) => {
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
};

const formatProjectedTerms = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    const terms = Number(value);
    if (!Number.isFinite(terms)) {
        return 'Not configured';
    }

    return `${terms} ${terms === 1 ? 'term' : 'terms'}`;
};

const formatRecommendation = (value) => {
    const labels = {
        recommended: 'Recommended for Approval',
        further_evaluation: 'For Further Evaluation',
        not_recommended: 'Not Recommended',
    };
    return labels[value] || 'N/A';
};

const getRecommendationTextClass = (value) => {
    const map = {
        recommended: 'text-green-600',
        further_evaluation: 'text-yellow-600',
        not_recommended: 'text-red-600',
    };
    return map[value] || 'text-slate-500';
};

const applyFilters = () => {
    // Filters are reactive
};

const exportSelected = async () => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant');
        return;
    }

    try {
        await exportInterviewedApplicantsExcel({ records: selectedRows.value });
        toast.success(`Exported ${selectedRows.value.length} applicant(s) as EXCEL.`);
    } catch (error) {
        console.error('Failed to export interviewed applicants:', error);
        toast.error('Failed to export applicant(s) as EXCEL.');
    }
};

const exportAuditRecords = async () => {
    if (filteredAuditRecords.value.length === 0) {
        toast.warn('No records to export');
        return;
    }

    try {
        await exportRecommendationListAuditExcel({ records: filteredAuditRecords.value });
        toast.success(`Exported ${filteredAuditRecords.value.length} record(s) to Excel.`);
    } catch (error) {
        console.error('Failed to export approval request audit records:', error);
        toast.error('Failed to export records to Excel.');
    }
};

const exportSelectedAuditRecords = async () => {
    if (selectedAuditRows.value.length === 0) {
        toast.warn('Please select at least one record');
        return;
    }

    try {
        await exportRecommendationListAuditExcel({ records: selectedAuditRows.value });
        toast.success(`Exported ${selectedAuditRows.value.length} record(s) to Excel.`);
    } catch (error) {
        console.error('Failed to export selected approval request audit records:', error);
        toast.error('Failed to export selected record(s) to Excel.');
    }
};

const createRecommendationList = async (payload) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    const { record_ids: recordIds, ...reportFields } = payload;

    if (!recordIds || recordIds.length === 0) {
        toast.warn('Please select at least one applicant.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.post(route('scholarship.recommendation-lists.store'), {
            record_ids: recordIds,
            ...reportFields,
        });

        const savedRecommendationList = response.data?.data;

        if (!savedRecommendationList) {
            throw new Error('Approval request payload was not returned.');
        }

        upsertRecommendationList(savedRecommendationList);
        recommendationListExpandedRows.value = { [savedRecommendationList.id]: true };
        handleRecommendationListModalVisibility(false);
        activeTab.value = 'recommendation-lists';
        toast.success(response.data?.message || 'Approval request created successfully.');
    } catch (error) {
        console.error('Failed to create approval request:', error);

        const message = error?.response?.data?.errors?.record_ids?.[0]
            || error?.response?.data?.message
            || 'Failed to create approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const generateRecommendationListPrint = (recommendationList, successMessage = null, printOptions = {}) => {
    try {
        const html = buildRecommendationListHtml({
            recommendationList,
            ...printOptions,
        });
        previewedRecommendationList.value = recommendationList;
        recommendationListPreviewHtml.value = html;
        recommendationListPreviewTitle.value = recommendationList?.list_number
            ? `Approval Request ${recommendationList.list_number}`
            : 'Approval Request';
        const paperMap = { landscape: { A4: 'a4-landscape', Letter: 'letter-landscape', Legal: 'landscape' }, portrait: { A4: 'a4', Letter: 'letter', Legal: 'long' } };
        const orientation = recommendationList?.orientation || 'landscape';
        const paperSize = recommendationList?.paper_size || 'A4';
        recommendationListPreviewPaperSize.value = paperMap[orientation]?.[paperSize] || 'a4-landscape';
        showRecommendationListPreview.value = true;

        if (successMessage) {
            toast.success(successMessage);
        }

        return true;
    } catch (error) {
        console.error('Failed to print approval request:', error);
        toast.error('Failed to print approval request.');
        return false;
    }
};

const updateRecommendationList = async (payload, { shouldPrintAfterSave = false } = {}) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!editingRecommendationList.value?.id) {
        toast.error('Recommendation list is unavailable for editing.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.update', editingRecommendationList.value.id),
            payload,
        );

        const savedRecommendationList = response.data?.data;

        if (!savedRecommendationList) {
            throw new Error('Recommendation list payload was not returned.');
        }

        upsertRecommendationList(savedRecommendationList);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [savedRecommendationList.id]: true,
        };
        handleRecommendationListModalVisibility(false);
        activeTab.value = 'recommendation-lists';

        const successMessage = response.data?.message || 'Approval request updated successfully.';

        if (shouldPrintAfterSave) {
            const printOptions = {
                includeInterviewColumns: payload?.include_interview_columns,
                includeProjectedColumns: payload?.include_projected_columns,
            };
            const printed = generateRecommendationListPrint(
                savedRecommendationList,
                `Updated ${savedRecommendationList.list_number}. Printing approval request.`,
                printOptions,
            );

            if (!printed) {
                toast.success(successMessage);
            }

            return;
        }

        toast.success(successMessage);
    } catch (error) {
        console.error('Failed to update approval request:', error);

        const message = error?.response?.data?.message
            || 'Failed to update approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const submitRecommendationList = async (payload) => {
    if (payload?.is_update_list && editingRecommendationList.value?.id) {
        await saveUpdateListChanges(payload.record_ids || []);
        return;
    }

    if (recommendationListModalMode.value === 'edit') {
        await updateRecommendationList(payload, {
            shouldPrintAfterSave: recommendationListSubmitIntent.value === 'print',
        });
        return;
    }

    await createRecommendationList(payload);
};

const exportPreviewedRecommendationListExcel = async () => {
    if (!previewedRecommendationList.value) {
        return;
    }

    try {
        await exportRecommendationListExcel({ recommendationList: previewedRecommendationList.value });
        toast.success('Exported approval request as EXCEL.');
    } catch (error) {
        console.error('Failed to export approval request:', error);
        toast.error('Failed to export approval request as EXCEL.');
    }
};

const printSavedRecommendationList = (recommendationList) => {
    openPrintRecommendationListModal(recommendationList);
};

const performApproveRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for approval.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.approve', recommendationList.id),
        );

        const approvedRecommendationList = response.data?.data;

        if (!approvedRecommendationList?.id) {
            throw new Error('Approved approval request payload was not returned.');
        }

        upsertRecommendationList(approvedRecommendationList);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [approvedRecommendationList.id]: true,
        };
        refreshPage();
        toast.success(response.data?.message || 'Approval request approved successfully.');
    } catch (error) {
        console.error('Failed to approve approval request:', error);

        const message = error?.response?.data?.message
            || 'Failed to approve approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const performRevertRecommendationListApproval = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for approval revert.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.revert-approval', recommendationList.id),
        );

        const revertedRecommendationList = response.data?.data;

        if (!revertedRecommendationList?.id) {
            throw new Error('Reverted approval request payload was not returned.');
        }

        upsertRecommendationList(revertedRecommendationList);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [revertedRecommendationList.id]: true,
        };
        refreshPage();
        toast.success(response.data?.message || 'Approval request approval reverted successfully.');
    } catch (error) {
        console.error('Failed to revert approval request approval:', error);

        const message = error?.response?.data?.message
            || 'Failed to revert approval request approval.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const approveRecommendationList = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for approval.');
        return;
    }

    if (recommendationList.is_approved) {
        toast.warn('Approval request is already approved.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this approval request';
    const approverName = currentUser.value?.name || 'the current user';

    openConfirmDialog({
        header: 'Approve Request',
        message: `Approve ${targetLabel}? This will record ${approverName} as the approving user and timestamp the list.`,
        icon: 'pi pi-check-circle',
        acceptLabel: 'Approve',
        severity: 'success',
        onAccept: () => {
            void performApproveRecommendationList(recommendationList);
        },
    });
};

const revertRecommendationListApproval = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for approval revert.');
        return;
    }

    if (!recommendationList.is_approved) {
        toast.warn('Approval request is not approved.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this approval request';

    openConfirmDialog({
        header: 'Revert Request Approval',
        message: `Revert approval for ${targetLabel}? This will remove the list approval stamp and restore affected applicants to their previous review status.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Revert Approval',
        severity: 'warning',
        onAccept: () => {
            void performRevertRecommendationListApproval(recommendationList);
        },
    });
};

const performDeleteRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for deletion.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.delete(
            route('scholarship.recommendation-lists.destroy', recommendationList.id),
        );

        if (response.data?.data?.id) {
            upsertDeletedRecommendationList(response.data.data);
        } else {
            recommendationLists.value = recommendationLists.value.filter(
                (existingRecommendationList) => existingRecommendationList.id !== recommendationList.id,
            );
        }

        collapseRecommendationListRow(recommendationList.id);

        if (editingRecommendationList.value?.id === recommendationList.id) {
            handleRecommendationListModalVisibility(false);
        }

        toast.success(response.data?.message || 'Approval request deleted successfully.');
    } catch (error) {
        console.error('Failed to delete approval request:', error);

        const message = error?.response?.data?.message
            || 'Failed to delete approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const deleteRecommendationList = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for deletion.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this approval request';

    openConfirmDialog({
        header: 'Delete Approval Request',
        message: `Delete ${targetLabel}? This will move the saved approval request to the deleted section until it is restored.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        severity: 'danger',
        onAccept: () => {
            void performDeleteRecommendationList(recommendationList);
        },
    });
};

const performRestoreRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for restoration.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.restore', recommendationList.id),
        );

        if (response.data?.data?.id) {
            upsertRecommendationList(response.data.data);
        } else {
            deletedRecommendationLists.value = deletedRecommendationLists.value.filter(
                (existingRecommendationList) => existingRecommendationList.id !== recommendationList.id,
            );
        }

        toast.success(response.data?.message || 'Approval request restored successfully.');
    } catch (error) {
        console.error('Failed to restore approval request:', error);

        const message = error?.response?.data?.message
            || 'Failed to restore approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const restoreRecommendationList = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for restoration.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this approval request';

    openConfirmDialog({
        header: 'Restore Approval Request',
        message: `Restore ${targetLabel}? This will return it to the active approval request table.`,
        icon: 'pi pi-refresh',
        acceptLabel: 'Restore',
        severity: 'warning',
        onAccept: () => {
            void performRestoreRecommendationList(recommendationList);
        },
    });
};

const performForceDeleteRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for permanent deletion.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.delete(
            route('scholarship.recommendation-lists.force-delete', recommendationList.id),
        );

        deletedRecommendationLists.value = deletedRecommendationLists.value.filter(
            (existingRecommendationList) => existingRecommendationList.id !== recommendationList.id,
        );

        toast.success(response.data?.message || 'Approval request permanently deleted.');
    } catch (error) {
        console.error('Failed to permanently delete approval request:', error);

        const message = error?.response?.data?.message
            || 'Failed to permanently delete approval request.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const forceDeleteRecommendationList = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Approval request is unavailable for permanent deletion.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this approval request';

    openConfirmDialog({
        header: 'Permanently Delete Soft-Deleted List',
        message: `Permanently delete the soft-deleted record ${targetLabel}? This cannot be undone and will remove the saved approval request permanently.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete Permanently',
        severity: 'danger',
        onAccept: () => {
            void performForceDeleteRecommendationList(recommendationList);
        },
    });
};

// Opens the unified Create/Update Approval Request modal in "update-list"
// intent — its own Select Applicants step (pre-checked with current members)
// replaces the old separate current-list/add-applicants modal pair.
const openUpdateListModal = (recommendationList) => {
    if (isCreatingRecommendationList.value || !recommendationList?.id) {
        return;
    }

    editingRecommendationList.value = recommendationList;
    recommendationListModalMode.value = 'edit';
    recommendationListSubmitIntent.value = 'update-list';
    showCreateRecommendationListModal.value = true;
};

// Pool of applicants offered by the Select Applicants step: recommended
// applicants not already in another approval request. When updating an
// existing request, its current members are included too (pre-checked),
// even though they're already "in a request" — this one.
const recommendationApplicantPool = computed(() => {
    if (recommendationListSubmitIntent.value === 'update-list' && editingRecommendationList.value) {
        const currentIds = new Set(
            (editingRecommendationList.value.records || []).map((r) => Number(r.id))
        );

        return (props.interviewed_applicants || [])
            .filter((r) => r.recommendation === 'recommended'
                && (!r.is_in_recommendation_list || currentIds.has(Number(r.id))));
    }

    return (props.interviewed_applicants || [])
        .filter((r) => r.recommendation === 'recommended' && !r.is_in_recommendation_list);
});

async function saveUpdateListChanges(recordIds) {
    if (!editingRecommendationList.value?.id) return;

    if (recordIds.length === 0) {
        toast.warn('Please keep at least one applicant in the request.');
        return;
    }

    isCreatingRecommendationList.value = true;
    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.refresh', editingRecommendationList.value.id),
            { record_ids: recordIds },
        );

        upsertRecommendationList(response.data?.data);
        handleRecommendationListModalVisibility(false);
        editingRecommendationList.value = null;
        toast.success(response.data?.message || 'Approval request updated successfully.');
    } catch (error) {
        const msg = error?.response?.data?.message || error?.message || 'Failed to update approval request.';
        console.error('Failed to update approval request:', error);
        toast.error(msg);
    } finally {
        isCreatingRecommendationList.value = false;
    }
}

const refreshRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value || !recommendationList?.id) {
        return;
    }

    recommendationList._refreshing = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.refresh', recommendationList.id),
        );

        upsertRecommendationList(response.data?.data);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [recommendationList.id]: true,
        };
        refreshPage();
        toast.success(response.data?.message || 'Recommendation list updated successfully.');
    } catch (error) {
        console.error('Failed to refresh approval request:', error);
        toast.error(error?.response?.data?.message || 'Failed to update approval request.');
    } finally {
        recommendationList._refreshing = false;
    }
};

const formatApplicantName = (record) => {
    const lastName = record?.profile?.last_name || 'N/A';
    const firstName = record?.profile?.first_name || '';
    const middleName = record?.profile?.middle_name?.trim();
    const middleInitial = middleName ? `${middleName.charAt(0).toUpperCase()}.` : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
};

const formatDateTime = (value) => {
    return value ? moment(value).format('MMM DD, YYYY h:mm A') : 'N/A';
};

const getRecommendationListApprovalLabel = (recommendationList) => {
    return recommendationList?.is_approved ? 'Approved' : 'Pending Approval';
};

const getRecommendationListApprovalBadgeClass = (recommendationList) => {
    return recommendationList?.is_approved
        ? 'bg-emerald-50 text-emerald-700'
        : 'bg-amber-50 text-amber-700';
};

const formatRecommendationListApprovalMeta = (recommendationList) => {
    if (!recommendationList?.is_approved) {
        return 'Waiting for a final approval action on this saved list.';
    }

    const approverName = recommendationList?.approver?.name || 'Unknown user';
    const approvedAt = formatDateTime(recommendationList?.approved_at);

    return `Approved by ${approverName} on ${approvedAt}.`;
};

const budgetAllocationCurrencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const formatBudgetAllocationAmount = (budgetAllocation) => {
    const amount = Number(budgetAllocation?.total_allotment);

    return Number.isFinite(amount) ? budgetAllocationCurrencyFormatter.format(amount) : null;
};

const formatBudgetAllocationTitle = (budgetAllocation) => {
    if (!budgetAllocation) {
        return 'No saved budget allocation';
    }

    return budgetAllocation.particular_name?.trim()
        || budgetAllocation.description?.trim()
        || 'Unnamed Allocation';
};

const formatBudgetAllocationDescription = (budgetAllocation) => {
    if (!budgetAllocation) {
        return '';
    }

    const description = budgetAllocation.description?.trim();
    const label = formatBudgetAllocationTitle(budgetAllocation);

    return [
        description && description !== label ? description : null,
        formatBudgetAllocationAmount(budgetAllocation),
    ].filter(Boolean).join(' · ');
};

const recommendationRecordHasJpm = (record) => {
    return Boolean(
        record?.profile?.is_jpm_member
        || record?.profile?.is_father_jpm
        || record?.profile?.is_mother_jpm
        || record?.profile?.is_guardian_jpm,
    );
};

const refreshPage = () => {
    router.reload({
        only: recommendationListReloadProps,
        preserveState: true,
        preserveScroll: true
    });
};

watch(activeTab, (value) => {
    sessionStorage.setItem('interviewed_applicants_tab', value);
});

watch(() => props.recommendation_lists, (value) => {
    recommendationLists.value = [...(value || [])];
}, { deep: true });

watch(() => props.deleted_recommendation_lists, (value) => {
    deletedRecommendationLists.value = [...(value || [])];
}, { deep: true });

watch(() => props.recommendation_list_audit_records, (value) => {
    recommendationListAuditRecords.value = [...(value || [])];
}, { deep: true });

watch(interviewedApplicantsWithRecommendationFlags, () => {
    syncSelectedRows();
});

watch(recommendationListAuditRecords, () => {
    const currentRecordsById = new Map(
        recommendationListAuditRecords.value.map((record) => [Number(record.id), record]),
    );

    selectedAuditRows.value = selectedAuditRows.value
        .map((record) => currentRecordsById.get(Number(record.id)))
        .filter((record) => Boolean(record));
});

onMounted(() => {
    document.body.classList.add('ios-admin-page');
});

onBeforeUnmount(() => {
    document.body.classList.remove('ios-admin-page');
});
</script>
