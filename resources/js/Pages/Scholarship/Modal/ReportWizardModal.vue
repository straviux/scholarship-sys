<template>
    <IosModal :visible="show" width="760px" max-width="92vw" body-style="padding: 0;"
        @update:visible="handleWizardVisibleUpdate">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleWizardBack">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">{{ modeTitle || 'Report Wizard' }}</span>
        </template>

        <template #header-right>
            <button class="ios-nav-btn ios-nav-cancel" @click="handleWizardBack">
                <AppIcon name="x" :size="16" />
            </button>
        </template>

        <div class="p-3 max-h-[78vh] overflow-y-auto">
            <!-- ═══ STEPPER ═══ -->
            <div class="flex items-center justify-center gap-0 pb-4">
                <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                    :class="{ 'opacity-40 cursor-default': currentStep < 1 && currentStep !== 1 }"
                    @click="goToStep(1)">
                    <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                        :class="currentStep > 1 ? 'bg-green-500' : 'bg-blue-500'">
                        <AppIcon v-if="currentStep > 1" name="check" :size="10" />
                        <span v-else>1</span>
                    </div>
                    <span class="text-xs font-medium whitespace-nowrap"
                        :class="currentStep === 1 ? 'text-blue-500 font-semibold' : currentStep > 1 ? 'text-green-500' : 'text-gray-400'">Report Type</span>
                </div>
                <div class="flex-1 h-0.5 mx-2 transition-colors"
                    :class="currentStep > 1 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                    :class="{ 'opacity-40 cursor-default': currentStep < 2 && currentStep !== 2 }"
                    @click="goToStep(2)">
                    <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                        :class="currentStep > 2 ? 'bg-green-500' : currentStep === 2 ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <AppIcon v-if="currentStep > 2" name="check" :size="10" />
                        <span v-else>2</span>
                    </div>
                    <span class="text-xs font-medium whitespace-nowrap"
                        :class="currentStep === 2 ? 'text-blue-500 font-semibold' : currentStep > 2 ? 'text-green-500' : 'text-gray-400'">Filters</span>
                </div>
                <div class="flex-1 h-0.5 mx-2 transition-colors"
                    :class="currentStep > 2 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                    :class="{ 'opacity-40 cursor-default': currentStep < 3 && currentStep !== 3 }"
                    @click="goToStep(3)">
                    <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                        :class="currentStep > 3 ? 'bg-green-500' : currentStep === 3 ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <AppIcon v-if="currentStep > 3" name="check" :size="10" />
                        <span v-else>3</span>
                    </div>
                    <span class="text-xs font-medium whitespace-nowrap"
                        :class="currentStep === 3 ? 'text-blue-500 font-semibold' : currentStep > 3 ? 'text-green-500' : 'text-gray-400'">Layout</span>
                </div>
                <div class="flex-1 h-0.5 mx-2 transition-colors"
                    :class="currentStep > 3 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                <div class="flex items-center gap-1.5 cursor-pointer select-none transition-opacity"
                    :class="{ 'opacity-40 cursor-default': currentStep !== 4 && currentStep < 4 }"
                    @click="goToStep(4)">
                    <div class="w-[26px] h-[26px] rounded-full flex items-center justify-center flex-shrink-0 transition-colors text-xs font-bold text-white"
                        :class="currentStep === 4 ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <span>4</span>
                    </div>
                    <span class="text-xs font-medium whitespace-nowrap"
                        :class="currentStep === 4 ? 'text-blue-500 font-semibold' : 'text-gray-400'">Options</span>
                </div>
            </div>

            <!-- ═══ STEP 1: REPORT TYPE ═══ -->
            <div v-show="currentStep === 1" class="py-2">
                <div class="mb-3" v-if="!mode">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Type</label>
                    <div class="flex border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                        <button :class="['flex-1 flex items-center justify-center gap-1 px-2.5 py-1.5 text-xs font-medium border-none cursor-pointer transition-colors',
                            reportType === 'list' ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                            reportType !== 'summary' ? 'border-r border-gray-300 dark:border-gray-600' : '']"
                            @click="reportType = 'list'">
                            <AppIcon name="list" :size="14" />
                            Detailed List
                        </button>
                        <button :class="['flex-1 flex items-center justify-center gap-1 px-2.5 py-1.5 text-xs font-medium border-none cursor-pointer transition-colors',
                            reportType === 'summary' ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                            @click="reportType = 'summary'">
                            <AppIcon name="bar-chart-3" :size="14" />
                            Summary
                        </button>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 py-3 text-center">Report type is set to <strong>{{ modeTitle }}</strong> based on the selected mode.</p>
            </div>

            <!-- ═══ STEP 2: FILTERS ═══ -->
            <div v-show="currentStep === 2" class="py-2">
                <div class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Status</label>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="opt in statusChoices" :key="opt.value"
                            :class="['inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-full border cursor-pointer transition-colors',
                                selectedStatus === opt.value ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-500 text-blue-600 dark:text-blue-400 font-semibold' : 'bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600']"
                            @click="selectedStatus = opt.value">
                            <span v-if="opt.color" class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: opt.color }"></span>
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Program</label>
                        <ProgramSelect v-model="selectedPrograms" label="shortname"
                            custom-placeholder="All Programs" class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" :multiple="true" />
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">School</label>
                        <SchoolSelect v-model="selectedSchools" label="shortname" custom-placeholder="All Schools"
                            class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" :multiple="true" />
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Course</label>
                        <CourseSelect v-model="selectedCourses" :scholarship-program-id="selectedPrograms?.[0]?.id"
                            label="shortname" custom-placeholder="All Courses" :multiple="true"
                            class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" />
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Municipality</label>
                        <MunicipalitySelect v-model="selectedMunicipalities" custom-placeholder="All Municipalities"
                            class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" :multiple="true" />
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Year Level</label>
                        <YearLevelSelect v-model="selectedYearLevels" custom-placeholder="All Year Levels"
                            class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" :multiple="true" />
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Grant Provision</label>
                        <MultiSelect v-model="selectedGrantProvisions" :options="grantProvisionOptions"
                            optionLabel="label" optionValue="value" placeholder="All Provisions" showClear filter
                            :filterFields="['label', 'value']" :maxSelectedLabels="3"
                            :selectedItemsLabel="'{0} provisions selected'" showSelectAll
                            class="[&_.p-dropdown]:w-full [&_.p-multiselect]:w-full [&_.p-inputtext]:w-full [&_.p-dropdown]:text-xs [&_.p-multiselect]:text-xs [&_.p-inputtext]:text-xs [&_.p-dropdown]:py-1.5 [&_.p-multiselect]:py-1.5 [&_.p-inputtext]:py-1.5" />
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Date Range</label>
                    <div class="flex items-center gap-2">
                        <DatePicker v-model="dateFrom" placeholder="From date"
                            showButtonBar dateFormat="M dd, yy"
                            class="flex-1 [&_.p-datepicker]:w-full [&_.p-datepicker]:text-xs" showIcon iconDisplay="input" />
                        <span class="text-gray-400 text-sm flex-shrink-0">—</span>
                        <DatePicker v-model="dateTo" placeholder="To date"
                            showButtonBar dateFormat="M dd, yy"
                            class="flex-1 [&_.p-datepicker]:w-full [&_.p-datepicker]:text-xs" showIcon iconDisplay="input" />
                    </div>
                    <div v-if="isDateToInvalid" class="text-[11px] text-red-500 mt-1">
                        End date must be after start date
                    </div>
                </div>

                <div v-if="activeFiltersCount > 0" class="mt-2.5 pt-2.5 border-t border-gray-100 dark:border-gray-700">
                    <button class="inline-flex items-center gap-1 text-[11px] font-medium text-red-500 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md px-2.5 py-1 cursor-pointer transition-colors hover:bg-red-100 dark:hover:bg-red-900/40"
                        @click="clearAllFilters">
                        <AppIcon name="x" :size="12" /> Clear {{ activeFiltersCount }} filter(s)
                    </button>
                </div>
            </div>

            <!-- ═══ STEP 3: LAYOUT & FORMATTING ═══ -->
            <div v-show="currentStep === 3" class="py-2">
                <div class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Title</label>
                    <Editor v-model="customReportTitle" editorStyle="height: 100px">
                        <template v-slot:toolbar>
                            <span class="ql-formats">
                                <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                            </span>
                        </template>
                    </Editor>
                    <span class="block text-[10px] text-gray-400 mt-1">Leave blank to use "{{ defaultReportTitle }}"</span>
                </div>

                <div class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Paper &amp; Orientation</label>
                    <div class="flex gap-2 w-1/2">
                        <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                            class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Group &amp; Sort</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <Select v-model="groupBy" :options="groupByOptions" optionLabel="label" optionValue="value"
                                class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                            <Select v-if="reportType === 'list' && groupBy && groupBy !== 'none'"
                                v-model="groupBySecondary" :options="secondaryGroupByOptions"
                                optionLabel="label" optionValue="value" placeholder="Sub-group (optional)" showClear
                                class="mt-1.5 w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                            <Select v-if="reportType === 'list' && groupBySecondary && groupBySecondary !== 'none'"
                                v-model="groupByTertiary" :options="tertiaryGroupByOptions"
                                optionLabel="label" optionValue="value" placeholder="3rd group (optional)" showClear
                                class="mt-1.5 w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                            <InputText v-if="reportType === 'list' && groupBy && groupBy !== 'none'"
                                v-model="customGroupMainLabel" placeholder="Custom label (optional)"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                            <InputText v-if="reportType === 'list' && groupBySecondary && groupBySecondary !== 'none'"
                                v-model="customGroupSubLabel" placeholder="Sub-group label (optional)"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                            <InputText v-if="reportType === 'list' && groupByTertiary && groupByTertiary !== 'none'"
                                v-model="customGroupTertiaryLabel" placeholder="3rd group label (optional)"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                        </div>
                        <div class="flex-1">
                            <Select v-model="sortBy" :options="sortByOptions" optionLabel="label" optionValue="value"
                                class="w-full [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        </div>
                    </div>
                </div>

                <div v-if="isEfaApprovalList" class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Upcoming Term &amp; Academic Year</label>
                    <div class="flex gap-2">
                        <Select v-model="efaUpcomingTerm" :options="termOptions" optionLabel="label" optionValue="value"
                            placeholder="Term" class="flex-1 [&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                        <InputText v-model="efaUpcomingAcademicYear" placeholder="Academic Year (e.g. 2026-2027)"
                            class="flex-1 [&_.p-inputtext]:w-full [&_.p-inputtext]:text-xs [&_.p-inputtext]:py-1.5" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Signatory Style</label>
                    <label class="flex items-center gap-4 py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                        <span>For Approval Layout</span>
                        <ToggleSwitch v-model="useInterviewedSignatories" />
                    </label>
                </div>

                <div class="mb-3">
                    <div class="flex gap-2" style="align-items: flex-start;">
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Prepared By</label>
                            <InputText v-model="preparedBy" placeholder="Name (optional)"
                                class="w-full [&_.p-inputtext]:w-full" />
                            <InputText v-model="preparedByTitle"
                                :placeholder="useInterviewedSignatories ? 'Position (optional)' : 'Designation (optional)'"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full" />
                            <InputText v-if="useInterviewedSignatories" v-model="preparedByOffice" placeholder="Office (optional)"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ useInterviewedSignatories ? 'Approved By' : 'Noted By' }}</label>
                            <InputText v-model="signatoryName" placeholder="Name (optional)"
                                class="w-full [&_.p-inputtext]:w-full" />
                            <InputText v-model="signatoryTitle"
                                :placeholder="useInterviewedSignatories ? 'Position (optional)' : 'Designation (optional)'"
                                class="mt-1.5 w-full [&_.p-inputtext]:w-full" />
                        </div>
                    </div>
                    <span class="block text-[10px] text-gray-400 mt-1.5">Leave all signatory fields blank to hide the signature block at the bottom of the report.</span>
                </div>
            </div>

            <!-- ═══ STEP 4: REPORT OPTIONS ═══ -->
            <div v-show="currentStep === 4" class="py-2">
                <div class="grid grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <div class="mb-3">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Report Options</label>
                            <div class="flex flex-col gap-0.5">
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Include Projected Expense</span>
                                    <ToggleSwitch v-model="includeProjectedExpense" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Include Grant</span>
                                    <ToggleSwitch v-model="includeGrantProvision" />
                                </label>
                                <div v-if="includeGrantProvision" class="mt-2">
                                    <Select v-model="grantValue" :options="grantProvisionOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select grant…" showClear
                                        class="[&_.p-dropdown]:w-full [&_.p-dropdown]:text-xs [&_.p-dropdown]:py-1.5" />
                                </div>
                                <label v-if="reportType === 'list' && canEnableJpmHighlighting"
                                    class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Highlight JPM Members</span>
                                    <ToggleSwitch v-model="enableJpmHighlighting" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="mb-3">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Show / Hide Fields</label>
                            <div class="flex flex-col gap-0.5">
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Address</span>
                                    <ToggleSwitch v-model="showAddress" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Contact Number</span>
                                    <ToggleSwitch v-model="showContactNumber" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Date Filed</span>
                                    <ToggleSwitch v-model="showDateFiled" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Program</span>
                                    <ToggleSwitch v-model="showProgram" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>School</span>
                                    <ToggleSwitch v-model="showSchool" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Course</span>
                                    <ToggleSwitch v-model="showCourse" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Remarks</span>
                                    <ToggleSwitch v-model="showRemarks" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Requirements</span>
                                    <ToggleSwitch v-model="showRequirements" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer border-b border-gray-50 dark:border-gray-800 hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Scholarship Date</span>
                                    <ToggleSwitch v-model="showScholarshipDate" />
                                </label>
                                <label class="flex items-center justify-between py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer hover:text-gray-900 dark:hover:text-gray-100">
                                    <span>Year Level</span>
                                    <ToggleSwitch v-model="showYearLevel" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ NAVIGATION ═══ -->
            <div class="flex items-center justify-between pt-3.5 mt-2 border-t border-gray-100 dark:border-gray-700">
                <button v-if="currentStep > 1"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-blue-500 bg-transparent px-4 py-2 rounded-lg cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30 border-none"
                    @click="prevStep">
                    <AppIcon name="chevron-left" :size="13" /> Back
                </button>
                <div v-else></div>
                <button v-if="currentStep < 4"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-white bg-blue-500 px-4 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-blue-600"
                    @click="nextStep">
                    Next <AppIcon name="chevron-right" :size="13" />
                </button>
                <button v-else
                    class="inline-flex items-center gap-1 text-sm font-bold text-white bg-green-500 px-5 py-2 rounded-lg cursor-pointer border-none transition-colors hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="generateReport" :disabled="generating">
                    <AppIcon v-if="generating" name="spinner" :size="14" class="animate-spin" />
                    <template v-else>Generate Report</template>
                </button>
            </div>
        </div>
    </IosModal>

    <!-- Report Preview Modal (unchanged) -->
    <IosModal :visible="showPreview" width="95vw" max-width="95vw" :modal-content-style="{ height: '90vh' }"
        body-style="padding: 0; flex: 1; display: flex; direction: column; overflow: hidden;"
        @update:visible="showPreview = $event">
        <template #header-left>
            <button class="ios-nav-btn ios-nav-cancel" @click="showPreview = false">
                <AppIcon name="chevron-left" :size="13" /> Back
            </button>
        </template>

        <template #title>
            <span class="ios-nav-title">Report Preview</span>
        </template>

        <template #header-right>
            <div class="ios-nav-actions flex items-center gap-2">
                <div class="flex items-center gap-1.5 mr-2">
                    <button @click="zoomLevel = Math.max(40, zoomLevel - 10)"
                        class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                        :disabled="zoomLevel <= 40">
                        <AppIcon name="minus" :size="10" />
                    </button>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 w-10 text-center">{{ zoomLevel }}%</span>
                    <button @click="zoomLevel = Math.min(200, zoomLevel + 10)"
                        class="w-7 h-7 rounded-full flex items-center justify-center bg-white dark:bg-[#2a3040] border border-[#e5e5ea] dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#343d4e] transition-colors disabled:opacity-40"
                        :disabled="zoomLevel >= 200">
                        <AppIcon name="plus" :size="10" />
                    </button>
                </div>
                <button class="ios-icon-btn" @click="doExportExcel" title="Export to Excel">
                    <AppIcon name="file-spreadsheet" :size="16" style="color: #34C759;" />
                </button>
                <button class="ios-icon-btn" @click="doPrint" title="Print / Save as PDF">
                    <AppIcon name="printer" :size="16" style="color: #007AFF;" />
                </button>
            </div>
        </template>

        <!-- Preview Body -->
        <div class="overflow-auto bg-[#d1d1d6] dark:bg-[#1c1c1e]" style="flex: 1; min-height: 0; padding: 16px 0;">
            <div style="display: flex; justify-content: center;">
                <iframe v-if="previewHtml" :srcdoc="previewHtml"
                    :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', border: 'none', background: '#fff', boxShadow: '0 2px 20px rgba(0,0,0,0.15)', width: iframeWidth + 'px', height: iframeHeight + 'px' }"
                    frameborder="0"></iframe>
                <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <AppIcon name="spinner" class="w-8 h-8 mb-3" />
                    <p>Generating report...</p>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import axios from 'axios';
import * as XLSX from 'xlsx';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { useSystemOptions } from '@/composables/useSystemOptions';
import { renderVueTemplate } from '@/composables/usePdfPrint';
import { getProfileReportTitle, formatName, formatDate, formatStatus, getReportStatus } from '@/Pages/Scholarship/Reports/report-helpers';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';

// Custom Select Components
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';

import ProfileReportTemplate from '@/Pages/Scholarship/Reports/ProfileReportTemplate.vue';

const props = defineProps({
    show: Boolean,
    mode: { type: String, default: '' },
    selectedProgram: { type: Object, default: null },
});

const emit = defineEmits(['update:show']);

const modeTitle = computed(() => {
    switch (props.mode) {
        case 'master-list': return 'Master List';
        case 'approval-list': return 'Approval List';
        case 'summary': return 'Summary Report';
        default: return 'Report';
    }
});

// ─── State ───
const generating = ref(false);
const showPreview = ref(false);
const previewHtml = ref('');
const reportRecords = ref([]);
const zoomLevel = ref(100);

// ─── Stepper ───
const currentStep = ref(1);
const maxReachedStep = ref(1);

function goToStep(step) {
    if (step <= maxReachedStep.value) {
        currentStep.value = step;
    }
}

function nextStep() {
    if (currentStep.value < 4) {
        currentStep.value++;
        if (currentStep.value > maxReachedStep.value) {
            maxReachedStep.value = currentStep.value;
        }
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

// Step 1
const reportType = ref('list');
const selectedStatus = ref(null); // null = All

// Step 2
const dateFrom = ref(null);
const dateTo = ref(null);
const selectedPrograms = ref([]);
const selectedSchools = ref([]);
const selectedCourses = ref([]);
const selectedMunicipalities = ref([]);
const selectedYearLevels = ref([]);
const selectedGrantProvisions = ref([]);

// Step 3
const paperSize = ref('A4');
const orientation = ref('landscape');
const customReportTitle = ref('');
const preparedByTitle = ref('');
const preparedByOffice = ref('');
const signatoryName = ref('');
const signatoryTitle = ref('');
const groupBy = ref('none');
const groupBySecondary = ref('none');
const groupByTertiary = ref('none');
const customGroupMainLabel = ref('');
const customGroupSubLabel = ref('');
const customGroupTertiaryLabel = ref('');
const showSequenceNumbers = ref(true);
const includeRemarks = ref(false);
const includeProjectedExpense = ref(true);
const includeGrantProvision = ref(false);
const grantValue = ref(null);
const enableJpmHighlighting = ref(false);
const useInterviewedSignatories = ref(false);
const jpmFilter = ref('all');
const sortBy = ref('default');
const showAddress = ref(true);
const showContactNumber = ref(true);
const showDateFiled = ref(true);
const showProgram = ref(true);
const showSchool = ref(true);
const showCourse = ref(true);
const showRemarks = ref(true);
const showRequirements = ref(true);
const showScholarshipDate = ref(true);
const showYearLevel = ref(true);

// ─── Composables ───
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const preparedBy = ref('');

const canEnableJpmHighlighting = computed(() => {
    if (!currentUser.value) return false;
    const userRoles = currentUser.value.roles || [];
    const allowedRoles = ['administrator', 'jpm_admin', 'program_manager'];
    return userRoles.some(role => allowedRoles.includes(role.name || role));
});

// ─── Computed summaries ───
const statusLabel = computed(() => {
    const found = statusChoices.value.find(s => s.value === selectedStatus.value);
    return found ? found.label : 'All Statuses';
});

const groupByLabel = computed(() => {
    const found = groupByOptions.find(o => o.value === groupBy.value);
    return found ? found.label : 'None';
});

// ─── Status choices ───
const statusChoices = computed(() => {
    const configs = [
        { value: null, label: 'All Statuses', color: null },
        { value: 'pending', label: 'Pending', color: '#F59E0B' },
        { value: 'interviewed', label: 'Interviewed', color: '#6366F1' },
        { value: 'approved_history', label: 'Approved', color: '#3B82F6' },
        { value: 'denied_history', label: 'Denied', color: '#EF4444' },
        { value: 'active', label: 'Active', color: '#10B981' },
        { value: 'completed', label: 'Completed', color: '#6B7280' },
        { value: 'withdrawn', label: 'Withdrawn', color: '#8B5CF6' },
        { value: 'loa', label: 'Leave of Absence', color: '#D97706' },
        { value: 'suspended', label: 'Suspended', color: '#DC2626' },
        { value: 'endorsed', label: 'Endorsed', color: '#7C3AED' },
    ];
    return configs;
});

// ─── Options ───
const grantProvisionOptions = useSystemOptions('grant_provision');

const grantValueLabel = computed(() => {
    if (!grantValue.value) return null;
    const option = grantProvisionOptions.value?.find(o => o.value === grantValue.value);
    return option?.label || grantValue.value;
});

const groupByOptions = [
    { label: 'No Grouping', value: 'none' },
    { label: 'By Status', value: 'unified_status' },
    { label: 'By School', value: 'school' },
    { label: 'By Program', value: 'program' },
    { label: 'By Course', value: 'course' },
    { label: 'By Year Level', value: 'year_level' },
    { label: 'By Municipality', value: 'municipality' },
    { label: 'By Grant Provision', value: 'grant_provision' },
];

const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const sortByOptions = [
    { label: 'Default Order', value: 'default' },
    { label: 'Alphabetical (A-Z)', value: 'name_asc' },
    { label: 'Alphabetical (Z-A)', value: 'name_desc' },
    { label: 'Date Filed (Oldest First)', value: 'date_filed_asc' },
    { label: 'Date Filed (Newest First)', value: 'date_filed_desc' },
    { label: 'Date Approved (Oldest First)', value: 'date_approved_asc' },
    { label: 'Date Approved (Newest First)', value: 'date_approved_desc' },
];

const secondaryGroupByOptions = computed(() =>
    groupByOptions.filter(o => o.value !== 'none' && o.value !== groupBy.value)
);

const tertiaryGroupByOptions = computed(() =>
    groupByOptions.filter(o => o.value !== 'none' && o.value !== groupBy.value && o.value !== groupBySecondary.value)
);

// EFA Approval List detection
const isEfaApprovalList = computed(() => {
    if (props.mode !== 'approval-list') return false;
    const program = props.selectedProgram || selectedPrograms.value?.[0];
    if (!program) return false;
    const name = (program.shortname || program.name || '').toUpperCase();
    return name === 'EFA';
});

const efaUpcomingTerm = ref('');
const efaUpcomingAcademicYear = ref('');

const termOptions = [
    { label: '1st Semester', value: '1st Semester' },
    { label: '2nd Semester', value: '2nd Semester' },
    { label: 'Summer', value: 'Summer' },
    { label: '1st Trimester', value: '1st Trimester' },
    { label: '2nd Trimester', value: '2nd Trimester' },
    { label: '3rd Trimester', value: '3rd Trimester' },
];

// ─── Computed ───
const isDateToInvalid = computed(() => {
    if (dateFrom.value && dateTo.value) {
        return moment(dateTo.value).isBefore(dateFrom.value);
    }
    return false;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (dateFrom.value || dateTo.value) count++;
    if (selectedPrograms.value?.length > 0) count++;
    if (selectedSchools.value?.length > 0) count++;
    if (selectedCourses.value?.length > 0) count++;
    if (selectedMunicipalities.value?.length > 0) count++;
    if (selectedYearLevels.value?.length > 0) count++;
    if (selectedGrantProvisions.value?.length > 0) count++;
    return count;
});

const defaultReportTitle = computed(() => getProfileReportTitle(selectedStatus.value, reportType.value));
const resolvedReportTitle = computed(() => customReportTitle.value?.trim() || defaultReportTitle.value);

const paperConfig = computed(() => getReportPaperConfig(paperSize.value, orientation.value));
const iframeWidth = computed(() => paperConfig.value.widthPx);
const iframeHeight = computed(() => paperConfig.value.heightPx);

function resetPreviewState() {
    showPreview.value = false;
    previewHtml.value = '';
    reportRecords.value = [];
}


// ─── Methods ───
function handleWizardVisibleUpdate(value) {
    if (value) emit('update:show', value);
    else close();
}

function handleWizardBack() {
    close();
}

function close() {
    resetPreviewState();
    emit('update:show', false);
}

function clearMultiSelection(selection) {
    if (Array.isArray(selection.value)) {
        if (selection.value.length === 0) return;
        selection.value.splice(0, selection.value.length);
        return;
    }

    selection.value = [];
}

function clearAllFilters() {
    dateFrom.value = null;
    dateTo.value = null;
    clearMultiSelection(selectedCourses);
    clearMultiSelection(selectedPrograms);
    clearMultiSelection(selectedSchools);
    clearMultiSelection(selectedMunicipalities);
    clearMultiSelection(selectedYearLevels);
    clearMultiSelection(selectedGrantProvisions);
}

async function generateReport() {
    if (generating.value) return;
    generating.value = true;

    try {
        // Build query params
        const params = {};
        if (selectedStatus.value) params.unified_status = selectedStatus.value;
        if (selectedPrograms.value?.length > 0) {
            params.program = selectedPrograms.value.map(p => p.id).join(',');
        }
        if (selectedSchools.value?.length > 0) {
            params.school = selectedSchools.value.map(s => s.shortname).join(',');
        }
        if (selectedCourses.value?.length > 0) {
            params.courses = selectedCourses.value.map(c => c.name).join(',');
        }
        if (selectedMunicipalities.value?.length > 0) {
            params.municipality = selectedMunicipalities.value.map(m => m.name).join(',');
        }
        if (selectedYearLevels.value?.length > 0) {
            params.year_level = selectedYearLevels.value.map(y => y.value).join(',');
        }
        if (selectedGrantProvisions.value?.length > 0) {
            params.grant_provision = selectedGrantProvisions.value.join(',');
        }
        if (dateFrom.value) params.date_from = moment(dateFrom.value).format('YYYY-MM-DD');
        if (dateTo.value) params.date_to = moment(dateTo.value).format('YYYY-MM-DD');
        if (enableJpmHighlighting.value) params.enable_jpm_highlighting = 1;
        if (jpmFilter.value === 'jpm_only') params.show_jpm_only = 1;
        if (jpmFilter.value === 'hide_jpm') params.hide_jpm = 1;
        if (isEfaApprovalList.value) params.efa_approval_mode = 1;

        // Fetch data
        const response = await axios.get(route('profile.generateReport'), { params });
        let records = [];
        if (Array.isArray(response.data)) {
            records = response.data;
        } else if (Array.isArray(response.data?.data)) {
            records = response.data.data;
        }
        reportRecords.value = records;

        // Build filter summary for template header
        const filterSummary = buildFilterSummary();

        // Build template props
        const templateProps = {
            records,
            filters: filterSummary,
            reportType: reportType.value,
            options: {
                showSequenceNumbers: showSequenceNumbers.value,
                includeRemarks: includeRemarks.value,
                includeGrantProvision: includeGrantProvision.value,
                grantValue: grantValue.value,
                grantValueLabel: grantValueLabel.value,
                includeProjectedExpense: includeProjectedExpense.value,
                enableJpmHighlighting: enableJpmHighlighting.value,
                jpmFilter: jpmFilter.value,
                selectedStatus: selectedStatus.value,
                reportTitle: customReportTitle.value?.trim() || '',
                preparedBy: preparedBy.value?.trim() || '',
                groupBy: groupBy.value,
                groupBySecondary: groupBySecondary.value !== 'none' ? groupBySecondary.value : null,
                groupByTertiary: groupByTertiary.value !== 'none' ? groupByTertiary.value : null,
                customGroupMainLabel: customGroupMainLabel.value?.trim() || '',
                customGroupSubLabel: customGroupSubLabel.value?.trim() || '',
                customGroupTertiaryLabel: customGroupTertiaryLabel.value?.trim() || '',
                preparedByTitle: preparedByTitle.value?.trim() || '',
                preparedByOffice: preparedByOffice.value?.trim() || '',
                signatoryName: signatoryName.value?.trim() || '',
                signatoryTitle: signatoryTitle.value?.trim() || '',
                useInterviewedSignatories: useInterviewedSignatories.value,
                sortBy: sortBy.value,
                showAddress: showAddress.value,
                showContactNumber: showContactNumber.value,
                showDateFiled: showDateFiled.value,
                showProgram: showProgram.value,
                showSchool: showSchool.value,
                showCourse: showCourse.value,
                showRemarks: showRemarks.value,
                showRequirements: showRequirements.value,
                showScholarshipDate: showScholarshipDate.value,
                showYearLevel: showYearLevel.value,
                isEfaApprovalList: isEfaApprovalList.value,
                efaUpcomingTerm: efaUpcomingTerm.value,
                efaUpcomingAcademicYear: efaUpcomingAcademicYear.value,
            },
            generatedAt: moment().format('MMMM DD, YYYY — h:mm A'),
        };

        // Render to HTML
        const bodyHtml = renderVueTemplate(ProfileReportTemplate, templateProps);

        // Build full document
        const fullHtml = buildReportDoc(bodyHtml, resolvedReportTitle.value, paperConfig.value);

        previewHtml.value = fullHtml;
        showPreview.value = true;
    } catch (error) {
        console.error('Failed to generate report:', error);
    } finally {
        generating.value = false;
    }
}

function buildFilterSummary() {
    const summary = {};
    const statusChoice = statusChoices.value.find(s => s.value === selectedStatus.value);
    if (statusChoice) summary.Status = statusChoice.label;
    if (selectedPrograms.value?.length > 0) {
        summary.Program = selectedPrograms.value.map(p => p.shortname || p.name).join(', ');
    }
    if (selectedSchools.value?.length > 0) {
        summary.School = selectedSchools.value.map(s => s.shortname || s.name).join(', ');
    }
    if (selectedCourses.value?.length > 0) {
        summary.Course = selectedCourses.value.map(c => c.shortname || c.name).join(', ');
    }
    if (selectedMunicipalities.value?.length > 0) {
        summary.Municipality = selectedMunicipalities.value.map(m => m.name).join(', ');
    }
    if (selectedYearLevels.value?.length > 0) {
        summary['Year Level'] = selectedYearLevels.value.map(y => y.value).join(', ');
    }
    if (selectedGrantProvisions.value?.length > 0) {
        summary['Grant Provision'] = selectedGrantProvisions.value.join(', ');
    }
    if (dateFrom.value) summary['Date From'] = moment(dateFrom.value).format('MMM DD, YYYY');
    if (dateTo.value) summary['Date To'] = moment(dateTo.value).format('MMM DD, YYYY');
    return summary;
}

function getReportLifecycleScript() {
    const lines = [
        'function finalizeRender() {',
        '  try {',
        "    var splitTables = document.querySelectorAll('table[data-split-from]');",
        '',
        '    splitTables.forEach(function (table) {',
        "      var ref = table.getAttribute('data-ref');",
        '',
        '      if (!ref) {',
        '        return;',
        '      }',
        '',
        "      var originals = document.querySelectorAll('table[data-ref=\"' + ref + '\"]:not([data-split-from])');",
        '      var original = originals[0];',
        '',
        '      if (!original) {',
        '        return;',
        '      }',
        '',
        "      if (!table.querySelector(':scope > colgroup')) {",
        "        var originalColgroups = original.querySelectorAll(':scope > colgroup');",
        '',
        '        originalColgroups.forEach(function (colgroup) {',
        '          table.insertBefore(colgroup.cloneNode(true), table.firstChild);',
        '        });',
        '      }',
        '',
        "      if (table.querySelector(':scope > thead')) {",
        '        return;',
        '      }',
        '',
        "      var originalHead = original.querySelector(':scope > thead');",
        '',
        '      if (!originalHead) {',
        '        return;',
        '      }',
        '',
        '      table.insertBefore(originalHead.cloneNode(true), table.firstChild);',
        '    });',
        '  } catch (error) {',
        "    console.warn('Scholarship report split table normalization failed', error);",
        '  }',
        '',
        "  var pages = document.querySelector('.pagedjs_pages');",
        '  var h = pages ? pages.scrollHeight + 48 : document.documentElement.scrollHeight;',
        "  window.parent.postMessage({ type: 'pagedjs:rendered', height: h }, '*');",
        "  document.body.style.visibility = 'visible';",
        "  if (document.body.getAttribute('data-auto-print') === '1') {",
        '    window.print();',
        '  }',
        '}',
    ];

    lines.push('var scheduleFinalize = function () {');
    lines.push('  window.requestAnimationFrame(finalizeRender);');
    lines.push('};');
    lines.push("if (document.readyState === 'complete') {");
    lines.push('  scheduleFinalize();');
    lines.push('} else {');
    lines.push("  window.addEventListener('load', scheduleFinalize, { once: true });");
    lines.push('}');

    return lines.join('\n');
}

function buildReportDoc(bodyHtml, title, paperSettings) {
    const lifecycleScript = getReportLifecycleScript();

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>${title}</title>
  <style>
    body { visibility: hidden; margin: 0; padding: 0; }
    ${getReportCss(paperSettings)}
  </style>
  <script>
${lifecycleScript}
  <\/script>
</head>
<body>${bodyHtml}</body>
</html>`;
}

function doPrint() {
    if (!previewHtml.value) return;
    const win = window.open('', '_blank');
    if (!win) {
        alert('Pop-up blocked. Please allow pop-ups for this site.');
        return;
    }
    // Inject data-auto-print so Paged.js triggers window.print() after rendering
    const htmlForPrint = previewHtml.value.replace('<body>', '<body data-auto-print="1">');
    win.document.write(htmlForPrint);
    win.document.close();
}

function doExportExcel() {
    const records = reportRecords.value;
    if (!records || records.length === 0) return;

    const upper = (v) => String(v ?? '').toUpperCase();

    const incrementYearLevelForExcel = (yearLevel) => {
        if (!yearLevel) return '';
        const match = String(yearLevel).match(/^(\d+)(?:ST|ND|RD|TH)?$/i);
        if (match) {
            const num = parseInt(match[1], 10) + 1;
            const suffix = num % 10 === 1 && num !== 11 ? 'ST' : num % 10 === 2 && num !== 12 ? 'ND' : num % 10 === 3 && num !== 13 ? 'RD' : 'TH';
            return `${num}${suffix} YEAR`;
        }
        return String(yearLevel).toUpperCase();
    };

    // Build columns matching the table structure
    const headers = [];
    const keys = [];

    if (showSequenceNumbers.value) { headers.push('#'); keys.push('_seq'); }
    headers.push('NAME'); keys.push('name');
    if (showContactNumber.value) { headers.push('CONTACT NUMBER'); keys.push('contact_number'); }
    if (showAddress.value) { headers.push('ADDRESS'); keys.push('address'); }
    if (showProgram.value) { headers.push('PROGRAM'); keys.push('program'); }
    if (showSchool.value) { headers.push('SCHOOL'); keys.push('school'); }
    if (showCourse.value) { headers.push('COURSE'); keys.push('course'); }
    if (isEfaApprovalList.value) { headers.push('CURRENT YEAR LEVEL'); keys.push('current_year_level'); }
    headers.push('YEAR LEVEL'); keys.push('year_level');
    headers.push('TERM'); keys.push('term');
    headers.push('ACADEMIC YEAR'); keys.push('academic_year');
    headers.push('GRANT PROVISION'); keys.push('grant_provision');
    if (showDateFiled.value) { headers.push('DATE FILED'); keys.push('date_filed'); }
    headers.push('STATUS'); keys.push('status');
    if (showRemarks.value) { headers.push('REMARKS'); keys.push('remarks'); }

    if (includeProjectedExpense.value) {
        headers.push('PROJ. TERMS', 'PROJ. EXPENSE', 'PROJ. COMPLETION');
        keys.push('projected_term_count', 'projected_total_expense', 'projected_completion_year');
    }

    // Build rows — all values uppercase
    const rows = records.map((rec, idx) => {
        const row = [];
        for (let i = 0; i < keys.length; i++) {
            const key = keys[i];
            switch (key) {
                case '_seq': row.push(idx + 1); break;
                case 'name': row.push(upper(formatName(rec))); break;
                case 'contact_number': row.push(upper(rec.contact_no || '')); break;
                case 'address': row.push(upper([rec.barangay, rec.municipality].filter(Boolean).join(', '))); break;
                case 'program': row.push(upper(rec.program_name || rec.program || '')); break;
                case 'school': row.push(upper(rec.school_name || rec.school || '')); break;
                case 'course': row.push(upper(rec.course_name || rec.course || '')); break;
                case 'current_year_level': row.push(upper(incrementYearLevelForExcel(rec.year_level))); break;
                case 'year_level': row.push(upper(rec.year_level || '')); break;
                case 'term': row.push(upper(rec.term || '')); break;
                case 'academic_year': row.push(upper(rec.academic_year || '')); break;
                case 'grant_provision': row.push(upper(grantValue.value || rec.grant_provision_label || rec.grant_provision || '')); break;
                case 'date_filed': row.push(upper(formatDate(rec.date_filed))); break;
                case 'status': row.push(upper(formatStatus(getReportStatus(rec)))); break;
                case 'remarks': row.push(upper(rec.remarks || rec.decline_reason || '')); break;
                case 'projected_term_count': row.push(upper(rec.projected_term_count ?? '')); break;
                case 'projected_total_expense': row.push(upper(rec.projected_total_expense ?? '')); break;
                case 'projected_completion_year': row.push(upper(rec.projected_completion_year ?? '')); break;
                default: row.push(upper(rec[key] ?? '')); break;
            }
        }
        return row;
    });

    // Create workbook and worksheet
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');

    // Set uniform font across all cells
    const DEFAULT_CELL_STYLE = { font: { name: 'Calibri', sz: 10 } };
    const DEFAULT_HEADER_STYLE = { font: { name: 'Calibri', sz: 10, bold: true }, alignment: { horizontal: 'center' } };

    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let r = range.s.r; r <= range.e.r; r++) {
        for (let c = range.s.c; c <= range.e.c; c++) {
            const addr = XLSX.utils.encode_cell({ r, c });
            if (!ws[addr]) continue;
            if (!ws[addr].s) ws[addr].s = {};
            Object.assign(ws[addr].s, r === 0 ? DEFAULT_HEADER_STYLE : DEFAULT_CELL_STYLE);
        }
    }

    // Auto-fit column widths
    const colWidths = headers.map((h, i) => {
        const maxLen = Math.max(h.length, ...rows.map(r => String(r[i] || '').length));
        return { wch: Math.min(Math.max(maxLen + 2, 8), 40) };
    });
    ws['!cols'] = colWidths;

    // Generate filename
    const title = (customReportTitle.value?.trim() || defaultReportTitle.value).replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_');
    const filename = `${title}_${moment().format('YYYY-MM-DD')}.xlsx`;

    XLSX.writeFile(wb, filename);
}

// ─── Watchers ───
watch(enableJpmHighlighting, (v) => {
    if (!v && jpmFilter.value !== 'all') {
        jpmFilter.value = 'all';
    }
});

// Use a shared suppression flag to prevent cascading watcher loops
let suppressingGroupByUpdates = false;
watch(groupBy, (v) => {
    if (suppressingGroupByUpdates) return;
    suppressingGroupByUpdates = true;

    if (v === 'none' || v === groupBySecondary.value) {
        if (groupBySecondary.value !== 'none') {
            groupBySecondary.value = 'none';
        }
        if (groupByTertiary.value !== 'none') {
            groupByTertiary.value = 'none';
        }
    }

    suppressingGroupByUpdates = false;
});

watch(groupBySecondary, (v) => {
    if (suppressingGroupByUpdates) return;

    if (v === 'none' || v === groupByTertiary.value) {
        if (groupByTertiary.value !== 'none') {
            groupByTertiary.value = 'none';
        }
    }
});

// Reset state when modal opens - use a one-shot approach to avoid transition conflicts
let hasResetOnOpen = false;
watch(() => props.show, (v) => {
    if (v) {
        if (hasResetOnOpen) return;
        hasResetOnOpen = true;
        // Reset stepper
        currentStep.value = 1;
        maxReachedStep.value = 1;
        // Set report type based on mode
        if (props.mode === 'summary') {
            reportType.value = 'summary';
        } else {
            reportType.value = 'list';
        }
        // Pre-select the program from the parent modal
        if (props.selectedProgram) {
            selectedPrograms.value = [props.selectedProgram];
        }
        // Use queueMicrotask instead of nextTick to resolve before the next render frame
        queueMicrotask(() => {
            customReportTitle.value = '';
            preparedBy.value = '';
            preparedByTitle.value = '';
            preparedByOffice.value = '';
            signatoryName.value = '';
            signatoryTitle.value = '';
            customGroupMainLabel.value = '';
            customGroupSubLabel.value = '';
            customGroupTertiaryLabel.value = '';
            efaUpcomingTerm.value = '';
            efaUpcomingAcademicYear.value = '';
        });
    } else {
        hasResetOnOpen = false;
        resetPreviewState();
    }
});

onBeforeUnmount(() => {
    resetPreviewState();
});
</script>

<style scoped>
/* ─── Compact PrimeVue inputs for filter selects only ─── */
.rw-select :deep(.p-dropdown),
.rw-select :deep(.p-multiselect),
.rw-select :deep(.p-inputtext) {
    width: 100%;
    font-size: 12px;
    padding: 6px 8px;
}

:deep(.p-datepicker) {
    width: 100%;
    font-size: 12px;
}
</style>
