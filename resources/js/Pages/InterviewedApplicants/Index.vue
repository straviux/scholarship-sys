<template>
    <AdminLayout>

        <Head title="Interviewed Applicants - Approval Management" />

        <div class="ios-settings-form">
            <!-- Toolbar -->
            <Toolbar class="mb-4 -mt-2 short:mb-2 !rounded-4xl !px-8">
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
                <template #end>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div class="flex flex-wrap gap-3" role="tablist" aria-label="Interviewed applicant views">
                            <button type="button" role="tab" :aria-selected="activeTab === 'interviewed'"
                                class="cursor-pointer rounded-full  px-4 py-[0.65rem] text-slate-700 transition-colors"
                                :class="activeTab === 'interviewed'
                                    ? ' bg-blue-400 !text-slate-50'
                                    : ' bg-white hover:border-blue-200'" @click="activeTab = 'interviewed'">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="clipboard-list" :size="14" />
                                    <span>Interviewed Applicants</span>
                                    <span
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                        {{ filteredList.length }}
                                    </span>
                                </div>
                            </button>
                            <button type="button" role="tab" :aria-selected="activeTab === 'recommendation-lists'"
                                class="cursor-pointer rounded-full  px-4 py-[0.65rem] text-slate-700 transition-colors"
                                :class="activeTab === 'recommendation-lists'
                                    ? 'bg-blue-400 !text-slate-50'
                                    : ' bg-white hover:border-blue-200'" @click="activeTab = 'recommendation-lists'">
                                <div class="flex items-center gap-2">
                                    <AppIcon name="list-checks" :size="14" />
                                    <span>Recommendation Lists</span>
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                        {{ recommendationLists.length }}
                                    </span>
                                </div>
                            </button>
                        </div>
                        <AppButton icon="users" label="Cumulative List" severity="secondary" rounded size="small"
                            @click="openCumulativeScholarListModal" />
                        <AppButton v-if="activeTab === 'interviewed'" icon="printer" severity="info" text rounded
                            size="large" @click="openReportModal" v-tooltip.bottom="'Print Report'" />
                    </div>
                </template>
            </Toolbar>

            <Tabs v-model:value="activeTab">
                <Panel class="mb-6 short:mb-3 !rounded-4xl overflow-hidden">
                    <div v-if="activeTab === 'interviewed'"
                        class="flex items-end gap-3 short:gap-2 -mt-6 short:-mt-3 flex-wrap">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Applicant Name</label>
                            <IconField iconPosition="left">
                                <InputIcon>
                                    <AppIcon name="search" :size="14" class="text-gray-400" />
                                </InputIcon>
                                <InputText v-model="filters.name" placeholder="Search by name..." size="small" />
                            </IconField>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Recommendation</label>
                            <Select v-model="filters.recommendation" :options="recommendationOptions"
                                optionLabel="label" optionValue="value" placeholder="All Recommendations" size="small"
                                class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                            <ProgramSelect v-model="filters.program" size="small" class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Recommendation List</label>
                            <Select v-model="filters.listStatus" :options="recommendationListStatusOptions"
                                optionLabel="label" optionValue="value" placeholder="All Recommendation List Status"
                                size="small" class="min-w-[220px] w-full" />
                        </div>
                        <div class="ml-auto flex flex-wrap justify-end gap-2">
                            <AppButton icon="filter" label="Show Eligible Applicants" severity="secondary" rounded
                                size="xsmall" @click="presetRecommendationCreationFilters" />
                            <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                size="xsmall" @click="clearInterviewedFilters" />
                        </div>
                    </div>

                    <div v-else class="flex items-end gap-3 short:gap-2 -mt-6 short:-mt-3 flex-wrap">
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Applicant Name</label>
                            <IconField iconPosition="left">
                                <InputIcon>
                                    <AppIcon name="search" :size="14" class="text-gray-400" />
                                </InputIcon>
                                <InputText v-model="filters.name" placeholder="Search by name..." size="small" />
                            </IconField>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Recommendation</label>
                            <Select v-model="filters.recommendation" :options="recommendationOptions"
                                optionLabel="label" optionValue="value" placeholder="All Recommendations" size="small"
                                class="w-full" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-600 mb-1">Program</label>
                            <ProgramSelect v-model="filters.program" size="small" class="w-full" />
                        </div>
                        <div class="ml-auto flex flex-wrap justify-end gap-2">
                            <AppButton icon="history" label="Reset Filters" severity="secondary" outlined rounded
                                size="xsmall" @click="clearInterviewedFilters" />
                        </div>
                    </div>

                </Panel>

                <div v-if="activeFilterTags.length" class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500">Active Filters:</span>
                    <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                        <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    </Tag>
                </div>

                <TabPanels>
                    <TabPanel value="interviewed">

                        <Panel
                            class="mb-4 short:mb-2 !rounded-4xl overflow-hidden border border-sky-100 bg-sky-50/70 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-3">
                                    <AppIcon name="info-circle" :size="18" class="mt-0.5 text-sky-600" />
                                    <div>
                                        <div class="text-sm font-semibold text-sky-900">How to create a recommendation
                                            list</div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-sky-800">
                                            <span class="rounded-full bg-white px-3 py-1 shadow-sm">1. Filter to
                                                Recommended for
                                                Approval</span>
                                            <span class="rounded-full bg-white px-3 py-1 shadow-sm">2. Show applicants
                                                not yet in a
                                                recommendation list</span>
                                            <span class="rounded-full bg-white px-3 py-1 shadow-sm">3. Select
                                                applicants</span>
                                            <span class="rounded-full bg-white px-3 py-1 shadow-sm">4. Click Create
                                                Recommendation
                                                List</span>
                                        </div>
                                        <div class="mt-2 text-xs text-sky-700">
                                            Applicants already saved in a recommendation list are flagged in the table
                                            and excluded
                                            by the eligible filter.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Panel>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 short:gap-2 mb-6 short:mb-3">
                            <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                                <div class="text-2xl short:text-xl font-bold text-blue-600">{{ stats.total }}</div>
                                <div class="text-xs text-gray-500">Total Interviewed</div>
                            </div>
                            <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                                <div class="text-2xl short:text-xl font-bold text-green-600">{{ stats.recommended }}
                                </div>
                                <div class="text-xs text-gray-500">Recommended</div>
                            </div>
                            <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                                <div class="text-2xl short:text-xl font-bold text-yellow-600">{{ stats.furtherEval }}
                                </div>
                                <div class="text-xs text-gray-500">For Further Evaluation</div>
                            </div>
                            <div class="bg-white border rounded-4xl p-4 short:p-2 text-center shadow-sm">
                                <div class="text-2xl short:text-xl font-bold text-red-600">{{ stats.notRecommended }}
                                </div>
                                <div class="text-xs text-gray-500">Not Recommended</div>
                            </div>
                        </div>

                        <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                            <div class="flex items-center justify-between mb-4 short:mb-2 -mt-2"
                                v-if="selectedRows.length">
                                <span class="text-sm text-gray-500">
                                    <span>&middot; {{ selectedRows.length }} selected</span>
                                </span>
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
                                                Create a saved recommendation list or export the current selection.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <AppButton icon="list-checks" label="Create Recommendation List" severity="info"
                                            rounded size="small" :disabled="!canCreateRecommendationListFromSelection"
                                            @click="openCreateRecommendationListModal" />
                                        <AppButton icon="file-type" label="Export PDF" severity="danger" outlined
                                            rounded size="small" @click="exportSelected('pdf')" />
                                        <AppButton icon="file-spreadsheet" label="Export Excel" severity="success"
                                            outlined rounded size="small" @click="exportSelected('excel')" />
                                    </div>
                                </div>
                                <div v-if="selectionHasNonRecommended" class="mt-2 text-xs font-medium text-amber-700">
                                    Only applicants already marked Recommended for Approval can be saved in a
                                    recommendation list.
                                </div>
                                <div v-if="selectionHasExistingRecommendationList"
                                    class="mt-2 text-xs font-medium text-amber-700">
                                    Remove applicants already in a recommendation list before creating a new
                                    recommendation list.
                                </div>
                            </div>
                            <!-- Paginator (top) -->
                            <div class="flex items-center justify-between px-2 py-2 border-b border-slate-100">
                                <span class="text-xs text-gray-400">
                                    {{ props.interviewed_applicants_pagination.from || 0 }}&ndash;{{
                                        props.interviewed_applicants_pagination.to || 0 }} of {{
                                        props.interviewed_applicants_pagination.total || 0 }}
                                </span>
                                <Paginator
                                    :first="(props.interviewed_applicants_pagination.current_page - 1) * props.interviewed_applicants_pagination.per_page"
                                    :rows="props.interviewed_applicants_pagination.per_page"
                                    :totalRecords="props.interviewed_applicants_pagination.total"
                                    :rowsPerPageOptions="[10, 20, 50, 100, 200]" @page="onPageChange"
                                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                                    class="!p-0 text-xs [&_.p-paginator-page]:!min-w-[1.75rem] [&_.p-paginator-page]:!h-[1.75rem] [&_.p-paginator-page]:!text-xs [&_.p-paginator-element]:!text-xs [&_.p-paginator-element]:!min-w-[1.75rem] [&_.p-paginator-element]:!h-[1.75rem] [&_.p-paginator-rpp-dropdown]:!text-xs [&_.p-paginator-current]:!text-xs" />
                            </div>

                            <div v-if="filteredList.length === 0" class="text-center py-8 text-gray-500">
                                No interviewed applicants found
                            </div>
                            <DataTable v-else v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
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
                                            v-tooltip.top="slotProps.data.is_in_recommendation_list ? 'Already included in a recommendation list (still selectable for export)' : 'Select applicant'">
                                            <Checkbox :modelValue="isRowSelected(slotProps.data)" binary
                                                :disabled="!isRowSelectable(slotProps.data)"
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
                                        <div class="text-[11px] mono text-gray-500">{{
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
                                        <span class="text-[10px] font-semibold"> {{ slotProps.data.course?.name ||
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
                                                :class="['text-[11px] font-semibold', getRecommendationTextClass(slotProps.data.recommendation)]">
                                                {{ formatRecommendation(slotProps.data.recommendation) }}
                                            </span>
                                            <span v-if="slotProps.data.is_in_recommendation_list"
                                                class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                                Already in Recommendation List
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
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                            Terms</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                            Expense</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                            Completion</th>
                                                        <th
                                                            class="border-b border-l border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                                            Interview Date</th>
                                                        <th
                                                            class="border-b border-slate-200 px-3 py-2 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
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

                            <!-- Paginator (bottom) -->
                            <div class="flex items-center justify-between px-2 py-2 border-t border-slate-100">
                                <span class="text-xs text-gray-400">
                                    {{ props.interviewed_applicants_pagination.from || 0 }}&ndash;{{
                                        props.interviewed_applicants_pagination.to || 0 }} of {{
                                        props.interviewed_applicants_pagination.total || 0 }}
                                </span>
                                <Paginator
                                    :first="(props.interviewed_applicants_pagination.current_page - 1) * props.interviewed_applicants_pagination.per_page"
                                    :rows="props.interviewed_applicants_pagination.per_page"
                                    :totalRecords="props.interviewed_applicants_pagination.total"
                                    :rowsPerPageOptions="[10, 20, 50, 100, 200]" @page="onPageChange"
                                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                                    class="!p-0 text-xs [&_.p-paginator-page]:!min-w-[1.75rem] [&_.p-paginator-page]:!h-[1.75rem] [&_.p-paginator-page]:!text-xs [&_.p-paginator-element]:!text-xs [&_.p-paginator-element]:!min-w-[1.75rem] [&_.p-paginator-element]:!h-[1.75rem] [&_.p-paginator-rpp-dropdown]:!text-xs [&_.p-paginator-current]:!text-xs" />
                            </div>
                        </Panel>
                    </TabPanel>

                    <TabPanel value="recommendation-lists">
                        <Panel class="!rounded-4xl overflow-hidden shadow-sm">
                            <div class="flex items-center justify-between mb-4 short:mb-2 -mt-2">
                                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                    <span>{{ filteredRecommendationLists.length }} saved transaction(s)</span>
                                    <span v-if="deletedRecommendationLists.length > 0"
                                        class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                        {{ filteredDeletedRecommendationLists.length }} deleted
                                    </span>
                                </div>
                            </div>

                            <div v-if="filteredRecommendationLists.length === 0"
                                class="py-10 text-center text-gray-500">
                                No saved recommendation lists yet
                            </div>

                            <DataTable v-else :value="filteredRecommendationLists" dataKey="id"
                                v-model:expandedRows="recommendationListExpandedRows" showGridlines stripedRows
                                scrollable responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean">
                                <Column expander :exportable="false" headerClass="w-12" bodyClass="w-12" />
                                <Column field="list_number" header="List No." sortable headerClass="min-w-[160px]"
                                    bodyClass="min-w-[160px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.list_number }}</div>
                                        <div class="text-[11px] text-slate-500">{{ slotProps.data.report_title }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span
                                                :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold', getRecommendationListApprovalBadgeClass(slotProps.data)]">
                                                {{ getRecommendationListApprovalLabel(slotProps.data) }}
                                            </span>
                                            <span v-if="slotProps.data.approved_at" class="text-[10px] text-slate-500">
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
                                        <div class="text-[11px] text-green-700 font-semibold">Recommended for Approval
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
                                                class="text-[11px] text-slate-500">
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
                                        <div class="text-[11px] text-slate-500">{{ slotProps.data.prepared_by_position
                                            || 'Position not set' }}</div>
                                    </template>
                                </Column>
                                <Column header="Created" sortable field="created_at" headerClass="min-w-[170px]"
                                    bodyClass="min-w-[170px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{
                                            formatDateTime(slotProps.data.created_at) }}</div>
                                        <div class="text-[11px] text-slate-500">{{ slotProps.data.creator?.name ||
                                            'Unknown user' }}</div>
                                    </template>
                                </Column>
                                <Column header="Actions" :style="{ width: '390px' }">
                                    <template #body="slotProps">
                                        <div class="flex flex-wrap gap-2">
                                            <AppButton v-if="slotProps.data.is_approved" icon="rotate-ccw"
                                                label="Revert Approval" severity="warning" outlined rounded size="small"
                                                @click="revertRecommendationListApproval(slotProps.data)" />
                                            <AppButton v-else icon="check-circle" label="Approve" severity="success"
                                                rounded size="small"
                                                @click="approveRecommendationList(slotProps.data)" />
                                            <AppButton icon="pencil" label="Edit" severity="secondary" outlined rounded
                                                size="small" @click="openEditRecommendationListModal(slotProps.data)" />
                                            <AppButton icon="printer" label="Print" severity="info" rounded size="small"
                                                @click="printSavedRecommendationList(slotProps.data)" />
                                            <AppButton icon="trash" label="Delete" severity="danger" outlined rounded
                                                size="small" @click="deleteRecommendationList(slotProps.data)" />
                                        </div>
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

                        <Panel v-if="deletedRecommendationLists.length > 0"
                            class="mt-4 !rounded-4xl overflow-hidden shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 short:mb-2 -mt-2">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Soft-Deleted Lists</div>
                                    <div class="text-xs text-slate-500">Restore a list or permanently remove a record
                                        that is
                                        already soft-deleted.</div>
                                </div>
                                <span class="text-sm font-semibold text-amber-700">
                                    {{ filteredDeletedRecommendationLists.length }} deleted transaction(s)
                                </span>
                            </div>

                            <div v-if="filteredDeletedRecommendationLists.length === 0"
                                class="py-8 text-center text-gray-500">
                                No deleted recommendation lists match the current filters
                            </div>

                            <DataTable v-else :value="filteredDeletedRecommendationLists" dataKey="id" showGridlines
                                stripedRows scrollable responsiveLayout="scroll"
                                class="text-sm ios-interviewed-table ios-datatable-clean">
                                <Column field="list_number" header="List No." sortable headerClass="min-w-[170px]"
                                    bodyClass="min-w-[170px]">
                                    <template #body="slotProps">
                                        <div class="font-semibold text-slate-800">{{ slotProps.data.list_number }}</div>
                                        <div class="text-[11px] text-slate-500">{{ slotProps.data.report_title }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span
                                                :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold', getRecommendationListApprovalBadgeClass(slotProps.data)]">
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
                                        <div class="text-[11px] text-slate-500">Stored snapshot</div>
                                    </template>
                                </Column>
                                <Column header="Budget Allocation" headerClass="min-w-[260px]"
                                    bodyClass="min-w-[260px]">
                                    <template #body="slotProps">
                                        <div v-if="slotProps.data.budget_allocation" class="leading-relaxed">
                                            <div class="font-semibold text-slate-800">{{
                                                formatBudgetAllocationTitle(slotProps.data.budget_allocation) }}</div>
                                            <div v-if="formatBudgetAllocationDescription(slotProps.data.budget_allocation)"
                                                class="text-[11px] text-slate-500">
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

        <!-- Generate Report Modal -->
        <CumulativeScholarListModal :show="showCumulativeScholarListModal"
            @update:show="showCumulativeScholarListModal = $event" :budget-allocations="props.budget_allocations" />

        <GenerateReportModal :show="showReportModal" @update:show="showReportModal = $event"
            :interviewed-applicants="filteredList" :budget-allocations="props.budget_allocations" />

        <CreateRecommendationListModal :show="showCreateRecommendationListModal"
            @update:show="handleRecommendationListModalVisibility"
            :selected-count="recommendationListModalSelectedCount" :budget-allocations="props.budget_allocations"
            :default-prepared-by="currentUser?.name || ''" :loading="isCreatingRecommendationList"
            :mode="recommendationListModalMode" :initial-data="editingRecommendationList"
            :submit-intent="recommendationListSubmitIntent" @submit="submitRecommendationList" />

        <AssessmentViewModal v-model:show="showAssessmentDialog" :record="selectedRecord"
            :initial-mode="assessmentInitialMode" :can-manage="canManageActions" :can-revert="canManageActions"
            :approval-form="approvalForm" :deny-form="denyForm" :decline-reasons="declineReasons"
            :interviewers="interviewers" @updated="onAssessmentUpdated" @confirm-approve="confirmApprove"
            @confirm-deny="confirmDeny" @revert="confirmRevert" />
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
import { useConfirm } from 'primevue/useconfirm';
import { toast } from '@/utils/toast';
import { usePermission } from '@/composable/permissions';

import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import ContextMenu from 'primevue/contextmenu';
import AssessmentViewModal from './Modal/AssessmentViewModal.vue';
import CumulativeScholarListModal from './Modal/CumulativeScholarListModal.vue';
import CreateRecommendationListModal from './Modal/CreateRecommendationListModal.vue';
import GenerateReportModal from './Modal/GenerateReportModalIOS.vue';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';
import {
    exportInterviewedApplicantsExcel,
    printInterviewedApplicantsSelection,
    printRecommendationList,
} from './interviewedApplicantsExport';
import Paginator from 'primevue/paginator';

const { hasRole } = usePermission();
const page = usePage();

const props = defineProps({
    interviewed_applicants: Array,
    interviewed_applicants_pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            per_page: 50,
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
});

const recommendationListReloadProps = [
    'interviewed_applicants',
    'interviewed_applicants_pagination',
    'budget_allocations',
    'recommendation_lists',
    'deleted_recommendation_lists',
];

// State
const activeTab = ref('interviewed');
const filters = ref({
    recommendation: null,
    name: '',
    program: null,
    listStatus: null,
});

const contextMenu = ref();
const showAssessmentDialog = ref(false);
const assessmentInitialMode = ref('view');
const showCumulativeScholarListModal = ref(false);
const showReportModal = ref(false);
const showCreateRecommendationListModal = ref(false);
const isCreatingRecommendationList = ref(false);
const recommendationListModalMode = ref('create');
const recommendationListSubmitIntent = ref('save');
const editingRecommendationList = ref(null);
const selectedRows = ref([]);
const expandedRows = ref({});
const confirm = useConfirm();
const recommendationLists = ref([...(props.recommendation_lists || [])]);
const deletedRecommendationLists = ref([...(props.deleted_recommendation_lists || [])]);
const recommendationListExpandedRows = ref({});

const approvalForm = useForm({
    date_approved: new Date(),
});

const denyForm = useForm({
    reason: '',
    details: ''
});

const selectedRecord = ref(null);
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
    { label: 'Not Yet in Recommendation List', value: 'available' },
    { label: 'Already in Recommendation List', value: 'included' },
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

    if (activeTab.value === 'interviewed' && filters.value.listStatus) {
        tags.push({
            key: 'listStatus',
            label: 'Recommendation List',
            display: getRecommendationListStatusLabel(filters.value.listStatus),
        });
    }

    return tags;
});

const filteredList = computed(() => {
    let list = interviewedApplicantsWithRecommendationFlags.value;
    const nameQuery = normalizedNameFilter.value;
    const programId = selectedProgramId.value;

    if (filters.value.recommendation) {
        list = list.filter(r => r.recommendation === filters.value.recommendation);
    }

    if (nameQuery) {
        list = list.filter((record) => recordMatchesName(record, nameQuery));
    }

    if (programId) {
        list = list.filter((record) => recordMatchesProgram(record, programId));
    }

    if (filters.value.listStatus === 'available') {
        list = list.filter((record) => !record.is_in_recommendation_list);
    }

    if (filters.value.listStatus === 'included') {
        list = list.filter((record) => record.is_in_recommendation_list);
    }

    return list;
});

const selectionHasNonRecommended = computed(() => {
    return selectedRows.value.some((record) => record.recommendation !== 'recommended');
});

const selectionHasExistingRecommendationList = computed(() => {
    return selectedRows.value.some((record) => record.is_in_recommendation_list);
});

const canCreateRecommendationListFromSelection = computed(() => {
    return selectedRows.value.length > 0
        && !selectionHasNonRecommended.value
        && !selectionHasExistingRecommendationList.value;
});

const recommendationListModalSelectedCount = computed(() => {
    if (recommendationListModalMode.value === 'edit') {
        return Number(editingRecommendationList.value?.record_count || editingRecommendationList.value?.records?.length || 0);
    }

    return selectedRows.value.length;
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

const formatDateForPayload = (value) => {
    if (!value) {
        return null;
    }

    return moment(value).format('YYYY-MM-DD');
};

const approvalValidationFields = [
    {
        key: 'date_approved',
        label: 'Approval Date',
        hasValue: () => Boolean(approvalForm.date_approved),
    },
];

const validateApprovalForm = () => {
    const clientErrors = {};
    const missingFields = [];

    approvalForm.clearErrors(...approvalValidationFields.map(field => field.key));

    approvalValidationFields.forEach((field) => {
        if (!field.hasValue()) {
            clientErrors[field.key] = `${field.label} is required.`;
            missingFields.push(field.label);
        }
    });

    if (!missingFields.length) {
        return true;
    }

    approvalForm.errors = {
        ...approvalForm.errors,
        ...clientErrors,
    };

    toast.warn('Complete the highlighted approval fields before submitting.');

    return false;
};

const populateApprovalForm = (record) => {
    approvalForm.date_approved = record.date_approved ? new Date(record.date_approved) : new Date();
};

const resetApprovalForm = (record = null) => {
    approvalForm.reset();
    approvalForm.clearErrors();

    if (record) {
        populateApprovalForm(record);
    }
};

const resetDenyForm = () => {
    denyForm.reset();
    denyForm.clearErrors();
};

const openAssessmentDialog = (record, mode = 'view') => {
    selectedRecord.value = record;
    assessmentInitialMode.value = mode;

    if (mode === 'approve') {
        resetApprovalForm(record);
    }

    if (mode === 'deny') {
        resetDenyForm();
    }

    showAssessmentDialog.value = true;
};

const stats = computed(() => {
    const pagination = props.interviewed_applicants_pagination;
    return {
        total: pagination?.total ?? 0,
        recommended: 0, // server-side aggregation needed, not from current page
        furtherEval: 0,
        notRecommended: 0,
    };
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
        },
        {
            label: 'View Profile',
            icon: 'eye',
            command: () => {
                if (selectedRecord.value) {
                    viewProfile(selectedRecord.value);
                }
            }
        }
    ];

    if (canManageActions.value) {
        items.push({ separator: true });
        items.push({
            label: 'Approve',
            icon: 'check',
            class: 'p-menuitem-success',
            command: () => {
                if (selectedRecord.value) {
                    openAssessmentDialog(selectedRecord.value, 'approve');
                }
            }
        });
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
            command: () => {
                if (selectedRecord.value) {
                    revertStatus(selectedRecord.value);
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

const isRowSelectable = (record) => true;

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
            selectedById.set(Number(record.id), record);
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
const perPage = ref(props.interviewed_applicants_pagination?.per_page ?? 50);

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

const onPageChange = (event) => {
    fetchPage(event.page + 1, event.rows);
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
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant.');
        return;
    }

    if (selectionHasNonRecommended.value) {
        toast.warn('Only applicants marked Recommended for Approval can be saved in a recommendation list.');
        return;
    }

    if (selectionHasExistingRecommendationList.value) {
        toast.warn('Remove applicants already in a recommendation list before creating a new recommendation list.');
        return;
    }

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

const confirmApprove = () => {
    if (!selectedRecord.value) return;

    if (!validateApprovalForm()) {
        return;
    }

    approvalForm.transform((data) => ({
        date_approved: formatDateForPayload(data.date_approved),
    })).post(route('scholarship.record.approve', selectedRecord.value.id), {
        onSuccess: () => {
            showAssessmentDialog.value = false;
            assessmentInitialMode.value = 'view';
            toast.success('Application approved successfully');
        },
        onError: (errors) => {
            const hasSummaryOnlyErrors = Object.keys(errors || {}).some((field) => field !== 'date_approved');
            toast.error(hasSummaryOnlyErrors
                ? 'Saved assessment details are incomplete. Use Edit Assessment, then try approving again.'
                : 'Review the approval date and try again.');
            console.error(errors);
        }
    });
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

const confirmRevert = () => {
    if (!selectedRecord.value) return;

    revertStatus(selectedRecord.value);
};

const viewProfile = (record) => {
    router.visit(route('scholarship.profile.show', record.profile.profile_id));
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

const exportSelected = (format) => {
    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant');
        return;
    }

    try {
        if (format === 'pdf') {
            const opened = printInterviewedApplicantsSelection({
                records: selectedRows.value,
            });

            if (!opened) {
                toast.error('Pop-up blocked. Please allow pop-ups and try again.');
                return;
            }
        } else if (format === 'excel') {
            exportInterviewedApplicantsExcel({ records: selectedRows.value });
        }

        toast.success(`Exported ${selectedRows.value.length} applicant(s) as ${format.toUpperCase()}.`);
    } catch (error) {
        console.error('Failed to export interviewed applicants:', error);
        toast.error(`Failed to export applicant(s) as ${format.toUpperCase()}.`);
    }
};

const createRecommendationList = async (payload) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (selectedRows.value.length === 0) {
        toast.warn('Please select at least one applicant.');
        return;
    }

    if (selectionHasNonRecommended.value) {
        toast.warn('Only applicants marked Recommended for Approval can be saved in a recommendation list.');
        return;
    }

    if (selectionHasExistingRecommendationList.value) {
        toast.warn('Remove applicants already in a recommendation list before creating a new recommendation list.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.post(route('scholarship.recommendation-lists.store'), {
            record_ids: selectedRows.value.map((record) => record.id),
            ...payload,
        });

        const savedRecommendationList = response.data?.data;

        if (!savedRecommendationList) {
            throw new Error('Recommendation list payload was not returned.');
        }

        upsertRecommendationList(savedRecommendationList);
        recommendationListExpandedRows.value = { [savedRecommendationList.id]: true };
        selectedRows.value = [];
        handleRecommendationListModalVisibility(false);
        activeTab.value = 'recommendation-lists';
        toast.success(response.data?.message || 'Recommendation list created successfully.');
    } catch (error) {
        console.error('Failed to create recommendation list:', error);

        const message = error?.response?.data?.errors?.record_ids?.[0]
            || error?.response?.data?.message
            || 'Failed to create recommendation list.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const generateRecommendationListPrint = (recommendationList, successMessage = null) => {
    try {
        const opened = printRecommendationList({ recommendationList });

        if (!opened) {
            toast.error('Pop-up blocked. Please allow pop-ups and try again.');
            return false;
        }

        if (successMessage) {
            toast.success(successMessage);
        }

        return true;
    } catch (error) {
        console.error('Failed to print recommendation list:', error);
        toast.error('Failed to print recommendation list.');
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

        const successMessage = response.data?.message || 'Recommendation list updated successfully.';

        if (shouldPrintAfterSave) {
            const printed = generateRecommendationListPrint(
                savedRecommendationList,
                `Updated ${savedRecommendationList.list_number}. Printing recommendation list.`,
            );

            if (!printed) {
                toast.success(successMessage);
            }

            return;
        }

        toast.success(successMessage);
    } catch (error) {
        console.error('Failed to update recommendation list:', error);

        const message = error?.response?.data?.message
            || 'Failed to update recommendation list.';

        toast.error(message);
    } finally {
        isCreatingRecommendationList.value = false;
    }
};

const submitRecommendationList = async (payload) => {
    if (recommendationListModalMode.value === 'edit') {
        await updateRecommendationList(payload, {
            shouldPrintAfterSave: recommendationListSubmitIntent.value === 'print',
        });
        return;
    }

    await createRecommendationList(payload);
};

const printSavedRecommendationList = (recommendationList) => {
    openPrintRecommendationListModal(recommendationList);
};

const performApproveRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for approval.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.approve', recommendationList.id),
        );

        const approvedRecommendationList = response.data?.data;

        if (!approvedRecommendationList?.id) {
            throw new Error('Approved recommendation list payload was not returned.');
        }

        upsertRecommendationList(approvedRecommendationList);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [approvedRecommendationList.id]: true,
        };
        refreshPage();
        toast.success(response.data?.message || 'Recommendation list approved successfully.');
    } catch (error) {
        console.error('Failed to approve recommendation list:', error);

        const message = error?.response?.data?.message
            || 'Failed to approve recommendation list.';

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
        toast.error('Recommendation list is unavailable for approval revert.');
        return;
    }

    isCreatingRecommendationList.value = true;

    try {
        const response = await axios.patch(
            route('scholarship.recommendation-lists.revert-approval', recommendationList.id),
        );

        const revertedRecommendationList = response.data?.data;

        if (!revertedRecommendationList?.id) {
            throw new Error('Reverted recommendation list payload was not returned.');
        }

        upsertRecommendationList(revertedRecommendationList);
        recommendationListExpandedRows.value = {
            ...recommendationListExpandedRows.value,
            [revertedRecommendationList.id]: true,
        };
        refreshPage();
        toast.success(response.data?.message || 'Recommendation list approval reverted successfully.');
    } catch (error) {
        console.error('Failed to revert recommendation list approval:', error);

        const message = error?.response?.data?.message
            || 'Failed to revert recommendation list approval.';

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
        toast.error('Recommendation list is unavailable for approval.');
        return;
    }

    if (recommendationList.is_approved) {
        toast.warn('Recommendation list is already approved.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this recommendation list';
    const approverName = currentUser.value?.name || 'the current user';

    confirm.require({
        message: `Approve ${targetLabel}? This will record ${approverName} as the approving user and timestamp the list.`,
        header: 'Approve Recommendation List',
        icon: 'pi pi-check-circle',
        acceptLabel: 'Approve',
        rejectLabel: 'Cancel',
        accept: () => {
            void performApproveRecommendationList(recommendationList);
        },
    });
};

const revertRecommendationListApproval = (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for approval revert.');
        return;
    }

    if (!recommendationList.is_approved) {
        toast.warn('Recommendation list is not approved.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this recommendation list';

    confirm.require({
        message: `Revert approval for ${targetLabel}? This will remove the list approval stamp and restore affected applicants to their previous review status.`,
        header: 'Revert Recommendation List Approval',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Revert Approval',
        rejectLabel: 'Cancel',
        accept: () => {
            void performRevertRecommendationListApproval(recommendationList);
        },
    });
};

const performDeleteRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for deletion.');
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

        toast.success(response.data?.message || 'Recommendation list deleted successfully.');
    } catch (error) {
        console.error('Failed to delete recommendation list:', error);

        const message = error?.response?.data?.message
            || 'Failed to delete recommendation list.';

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
        toast.error('Recommendation list is unavailable for deletion.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this recommendation list';

    confirm.require({
        message: `Delete ${targetLabel}? This will move the saved recommendation list to the deleted section until it is restored.`,
        header: 'Delete Recommendation List',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        accept: () => {
            void performDeleteRecommendationList(recommendationList);
        },
    });
};

const performRestoreRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for restoration.');
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

        toast.success(response.data?.message || 'Recommendation list restored successfully.');
    } catch (error) {
        console.error('Failed to restore recommendation list:', error);

        const message = error?.response?.data?.message
            || 'Failed to restore recommendation list.';

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
        toast.error('Recommendation list is unavailable for restoration.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this recommendation list';

    confirm.require({
        message: `Restore ${targetLabel}? This will return it to the active recommendation list table.`,
        header: 'Restore Recommendation List',
        icon: 'pi pi-refresh',
        acceptLabel: 'Restore',
        rejectLabel: 'Cancel',
        accept: () => {
            void performRestoreRecommendationList(recommendationList);
        },
    });
};

const performForceDeleteRecommendationList = async (recommendationList) => {
    if (isCreatingRecommendationList.value) {
        return;
    }

    if (!recommendationList?.id) {
        toast.error('Recommendation list is unavailable for permanent deletion.');
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

        toast.success(response.data?.message || 'Recommendation list permanently deleted.');
    } catch (error) {
        console.error('Failed to permanently delete recommendation list:', error);

        const message = error?.response?.data?.message
            || 'Failed to permanently delete recommendation list.';

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
        toast.error('Recommendation list is unavailable for permanent deletion.');
        return;
    }

    const targetLabel = recommendationList.list_number || recommendationList.report_title || 'this recommendation list';

    confirm.require({
        message: `Permanently delete the soft-deleted record ${targetLabel}? This cannot be undone and will remove the saved recommendation list permanently.`,
        header: 'Permanently Delete Soft-Deleted List',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete Permanently',
        rejectLabel: 'Cancel',
        accept: () => {
            void performForceDeleteRecommendationList(recommendationList);
        },
    });
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

watch(() => props.recommendation_lists, (value) => {
    recommendationLists.value = [...(value || [])];
}, { deep: true });

watch(() => props.deleted_recommendation_lists, (value) => {
    deletedRecommendationLists.value = [...(value || [])];
}, { deep: true });

watch(interviewedApplicantsWithRecommendationFlags, () => {
    syncSelectedRows();
});

onMounted(() => {
    document.body.classList.add('ios-admin-page');
});

onBeforeUnmount(() => {
    document.body.classList.remove('ios-admin-page');
});
</script>
