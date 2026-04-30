<template>

    <Head :title="`${profile.first_name} ${profile.last_name} - Scholar Profile`" />

    <AdminLayout>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header with Back Button -->
            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex min-w-0 items-start gap-4 sm:items-center">
                    <AppButton icon="arrow-left" text rounded severity="secondary" @click="goBackToProfiles()"
                        v-tooltip.top="'Back to Profiles'" />
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 sm:text-3xl">
                            {{ profile.first_name }} {{ profile.middle_name }} {{ profile.last_name }}
                            {{ profile.extension_name }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Scholar Profile</p>
                    </div>
                </div>
                <div
                    class="flex flex-wrap items-center justify-start gap-0.5 rounded-2xl border border-gray-200/80 bg-white/80 p-1 shadow-sm backdrop-blur-sm dark:border-white/10 dark:bg-[#1f2633]/90 xl:justify-end">
                    <AppButton icon="book" label="Generate Ledger" size="small" rounded text
                        class="!font-medium text-gray-700 dark:text-gray-300" @click="openLedgerModal"
                        v-tooltip.top="'Generate Scholar Ledger'" />
                    <AppButton icon="file-word" label="Certification" size="small" rounded text
                        class="!font-medium text-gray-700 dark:text-gray-300" @click="showCertPicker = true"
                        v-tooltip.top="'Generate Certification'" />
                    <div class="w-px h-6 bg-gray-200 mx-0.5 self-center flex-shrink-0"></div>
                    <AppButton v-if="hasPermission('applicants.edit')" icon="user" label="Edit Personal" size="small"
                        rounded text class="!font-medium text-gray-700 dark:text-gray-300"
                        @click="showPersonalInfoModal = true" v-tooltip.top="'Edit Personal Information'" />
                    <AppButton v-if="hasPermission('applicants.edit')" icon="home" label="Edit Family" size="small"
                        rounded text class="!font-medium text-gray-700 dark:text-gray-300"
                        @click="showFamilyInfoModal = true" v-tooltip.top="'Edit Family Information'" />
                </div>
            </div>

            <!-- Tab Navigation -->
            <div
                class="!rounded-4xl overflow-hidden bg-white dark:bg-[#1f2633] shadow-sm border border-gray-200 dark:border-white/10">
                <Tabs v-model:value="activeTab" scrollable>
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
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.mother_name
                                            || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Occupation</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            profile.mother_occupation ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            profile.mother_contact_no ||
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
                                        <p class="text-gray-900 dark:text-gray-100">{{ profile.guardian_name
                                            || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Relationship</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            profile.guardian_relationship ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Occupation</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            profile.guardian_occupation ||
                                            'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">Contact</label>
                                        <p class="text-gray-900 dark:text-gray-100">{{
                                            profile.guardian_contact_no ||
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
                                            {{ formatCurrency(profile.parents_guardian_gross_monthly_income)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Academic Information Tab -->
                        <TabPanel value="2">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Academic
                                        Enrollments</h3>
                                    <AppButton v-if="canCreateAcademicEnrollment" icon="plus" label="Add Enrollment"
                                        @click="openAddEnrollmentModal" severity="success" size="small" />
                                </div>
                                <div v-if="academicTermsNeedReview"
                                    class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-400/30 dark:bg-amber-900/20 dark:text-amber-100">
                                    <div class="flex items-start gap-3">
                                        <AppIcon name="exclamation-triangle" :size="18"
                                            class="mt-0.5 text-amber-600 dark:text-amber-300" />
                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold">
                                                Legacy academic records need review.
                                            </p>
                                            <p class="text-sm leading-6">
                                                {{ academicTermsNeedingReviewCount }} enrollment{{
                                                    academicTermsNeedingReviewCount === 1 ? '' : 's' }} still {{
                                                    academicTermsNeedingReviewCount === 1 ? 'has' : 'have' }} multiple
                                                pending or active terms. New records now allow only one open term per
                                                enrollment. Existing records were left unchanged and should be cleaned
                                                up manually.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="academicEnrollmentGroups.length > 0" class="space-y-5">
                                    <div v-for="enrollment in academicEnrollmentGroups" :key="enrollment.id"
                                        class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#1f2633]">
                                        <div
                                            class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 dark:border-white/10 md:flex-row md:items-center md:justify-between">
                                            <div class="space-y-1">
                                                <p class="text-normal font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ enrollment.program?.name || 'N/A' }}
                                                </p>
                                                <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                                    {{ enrollment.course?.name || 'N/A' }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ enrollment.school?.name || 'N/A' }}
                                                </p>
                                                <p v-if="enrollment.graduation_remarks"
                                                    class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ enrollment.graduation_remarks }}
                                                </p>
                                            </div>
                                            <div
                                                class="flex flex-col items-center justify-center gap-3 md:ml-auto md:max-w-[520px] md:items-center">
                                                <div v-if="enrollment.is_graduated"
                                                    class="flex items-center justify-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 shadow-sm dark:border-emerald-400/30 dark:bg-emerald-900/20 dark:text-emerald-200">
                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white dark:bg-emerald-500">
                                                        <AppIcon name="graduation-cap" :size="18" />
                                                    </div>
                                                    <div class="min-w-0 text-center">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                                                            Graduated
                                                        </p>
                                                        <p class="text-sm font-semibold">
                                                            {{ enrollment.graduation_date ?
                                                                formatDateShort(enrollment.graduation_date) :
                                                                'Recorded' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div v-if="enrollment.needsTermReview"
                                                    class="flex items-center justify-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-amber-900 shadow-sm dark:border-amber-400/30 dark:bg-amber-900/20 dark:text-amber-100">
                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white dark:bg-amber-400 dark:text-amber-950">
                                                        <AppIcon name="exclamation-triangle" :size="18" />
                                                    </div>
                                                    <div class="min-w-0 text-center">
                                                        <p
                                                            class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                                                            Needs Review
                                                        </p>
                                                        <p class="text-sm font-semibold">
                                                            {{ enrollment.openTermCount }} open term{{
                                                                enrollment.openTermCount === 1 ? '' : 's' }} found
                                                        </p>
                                                    </div>
                                                </div>
                                                <div v-if="canManageAcademicEnrollmentActions(enrollment)"
                                                    class="flex flex-wrap items-center justify-center gap-2 md:justify-end">
                                                    <AppButton v-if="canCreateAcademicTerm" icon="plus" outlined
                                                        severity="success" @click="openAddTermModal(enrollment)"
                                                        v-tooltip.top="'Add academic term'" />
                                                    <AppButton v-if="canEditAcademicEnrollmentDetails" icon="pencil"
                                                        outlined severity="secondary"
                                                        @click="openEditEnrollmentModal(enrollment)"
                                                        v-tooltip.top="'Edit enrollment'" />
                                                    <AppButton v-if="canEditAcademicEnrollmentDetails"
                                                        icon="graduation-cap" outlined severity="info"
                                                        @click="openGraduationModal(enrollment)"
                                                        v-tooltip.top="enrollment.is_graduated ? 'Update graduation' : 'Record graduation'" />
                                                    <AppButton v-if="canDeleteAcademicEnrollmentDetails" icon="trash"
                                                        outlined severity="danger"
                                                        @click="confirmDeleteEnrollment(enrollment)"
                                                        v-tooltip.top="'Delete enrollment'" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <DataTable class="academic-terms-table show-profile-table"
                                                v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }"
                                                :value="enrollment.displayTerms || enrollment.terms" showGridlines
                                                contextMenu scrollable tableStyle="min-width: 74rem"
                                                rowGroupMode="rowspan" :groupRowsBy="['academic_year', 'year_level']"
                                                @rowContextmenu="(event) => openAcademicTermContextMenu(event.originalEvent, event.data)">
                                                <Column field="academic_year" header="Academic Year"
                                                    headerClass="min-w-[150px] text-center"
                                                    bodyClass="min-w-[150px] align-middle text-center">
                                                    <template #body="slotProps">
                                                        <span
                                                            class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                            {{ slotProps.data.academic_year || 'N/A' }}
                                                        </span>
                                                    </template>
                                                </Column>

                                                <Column field="year_level" header="Year Level"
                                                    headerClass="min-w-[130px] text-center"
                                                    bodyClass="min-w-[130px] align-middle text-center">
                                                    <template #body="slotProps">
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                                            {{ slotProps.data.year_level || 'N/A' }}
                                                        </span>
                                                    </template>
                                                </Column>

                                                <Column header="Semester" headerClass="min-w-[160px]"
                                                    bodyClass="min-w-[160px]">
                                                    <template #body="slotProps">
                                                        <div class="space-y-1">
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                                {{ slotProps.data.term || 'N/A' }}
                                                            </span>
                                                            <div v-if="slotProps.data.linkedRecordCount > 1"
                                                                class="text-xs font-medium text-amber-700 dark:text-amber-300">
                                                                Linked scholarship record {{
                                                                    slotProps.data.linkedRecordIndex + 1 }} of {{
                                                                    slotProps.data.linkedRecordCount }}
                                                            </div>
                                                        </div>
                                                    </template>
                                                </Column>

                                                <Column header="Dates" headerClass="min-w-[160px]"
                                                    bodyClass="min-w-[160px]">
                                                    <template #body="slotProps">
                                                        <div class="space-y-1">
                                                            <p class="text-sm"><span class="font-medium">Filed:</span>
                                                                {{
                                                                    formatDateShort(slotProps.data.date_filed)
                                                                }}</p>
                                                            <p class="text-sm"><span
                                                                    class="font-medium">Approved:</span>
                                                                {{
                                                                    formatDateShort(slotProps.data.date_approved)
                                                                }}</p>
                                                        </div>
                                                    </template>
                                                </Column>

                                                <Column header="Status" headerClass="min-w-[120px]"
                                                    bodyClass="min-w-[120px]">
                                                    <template #body="slotProps">
                                                        <Chip v-if="slotProps.data.unified_status"
                                                            :label="getStatusLabel(slotProps.data.unified_status)"
                                                            :class="getStatusClass(slotProps.data.unified_status)" />
                                                        <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                                                    </template>
                                                </Column>

                                                <Column header="Remarks" headerClass="min-w-[220px]"
                                                    bodyClass="min-w-[220px]">
                                                    <template #body="slotProps">
                                                        <div v-if="slotProps.data.remarks"
                                                            v-safe-html="slotProps.data.remarks"
                                                            class="prose prose-sm max-w-none dark:prose-invert">
                                                        </div>
                                                        <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                                                    </template>
                                                </Column>

                                                <Column header="Attachments" headerClass="min-w-[80px]"
                                                    bodyClass="min-w-[80px]">
                                                    <template #body="slotProps">
                                                        <div class="flex gap-2">
                                                            <AppButton
                                                                v-if="canViewScholarshipAttachments && resolveAcademicRecord(slotProps.data)"
                                                                icon="qrcode" text severity="info"
                                                                v-tooltip.top="'Show QR Code'"
                                                                @click="showQrCode(resolveAcademicRecord(slotProps.data))" />
                                                            <AppButton
                                                                v-if="canViewScholarshipAttachments && resolveAcademicRecord(slotProps.data)"
                                                                icon="paperclip" text
                                                                v-tooltip.top="'Manage Attachments'"
                                                                @click="manageAttachments(resolveAcademicRecord(slotProps.data))" />
                                                            <Chip
                                                                v-if="slotProps.data.attachments && slotProps.data.attachments.length > 0"
                                                                :label="slotProps.data.attachments.length.toString()"
                                                                class="bg-blue-100 text-blue-800 dark:text-blue-300" />
                                                        </div>
                                                    </template>
                                                </Column>

                                                <Column header="Actions" headerClass="min-w-[50px]"
                                                    bodyClass="min-w-[50px]" v-if="hasAcademicTermActionsPermission">
                                                    <template #body="slotProps">
                                                        <div class="flex gap-1">
                                                            <AppButton v-if="canOpenAcademicTermActions(slotProps.data)"
                                                                icon="ellipsis-vertical" text severity="secondary"
                                                                v-tooltip.top="'Open Actions'"
                                                                @click="openAcademicTermContextMenu($event, slotProps.data)" />
                                                        </div>
                                                    </template>
                                                </Column>
                                            </DataTable>
                                        </div>
                                    </div>
                                    <ContextMenu ref="academicTermContextMenu" :model="academicTermContextMenuItems"
                                        appendTo="body">
                                        <template #item="{ item, props }">
                                            <a v-ripple v-bind="props.action" class="flex items-center gap-2 w-full">
                                                <AppIcon v-if="item.icon" :name="item.icon" :size="14" />
                                                <span>{{ item.label }}</span>
                                            </a>
                                        </template>
                                    </ContextMenu>
                                </div>
                                <div v-else class="text-center py-12">
                                    <AppIcon name="info-circle" :size="48" class="text-gray-300 mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400">No academic enrollments
                                        available</p>
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
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                        All
                                        Attachments</h3>
                                    <DataTable class="show-profile-table"
                                        v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" :value="allAttachments"
                                        stripedRows showGridlines paginator :rows="10" scrollable
                                        tableStyle="min-width: 68rem">
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
                                                    <span class="font-medium">{{
                                                        slotProps.data.attachment_name
                                                    }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Related Record" headerClass="min-w-[250px]"
                                            bodyClass="min-w-[250px]">
                                            <template #body="slotProps">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                                        {{
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
                                                    <AppButton icon="eye" size="small" outlined label="View"
                                                        @click="viewAttachment(slotProps.data)"
                                                        v-tooltip.top="'Preview'" />
                                                    <AppButton icon="download" size="small" outlined
                                                        @click="downloadAttachment(slotProps.data)"
                                                        v-tooltip.top="'Download'" />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                                <div v-else class="text-center py-12">
                                    <AppIcon name="paperclip" :size="48" class="text-gray-300 mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">No Attachments
                                        Available</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Upload
                                        attachments from the
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
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            Status Change
                                            Timeline</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Complete history
                                            of all
                                            status updates</p>
                                    </div>

                                    <div v-for="(timeline, timelineIndex) in statusTimeline" :key="timeline.id"
                                        class="flex gap-4">
                                        <!-- Timeline Dot and Line -->
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/20 border-2 flex items-center justify-center border-blue-400">
                                                <AppIcon name="check" :size="12"
                                                    class="text-blue-600 dark:text-blue-400" />
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
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            {{
                                                                formatDateTime(timeline.performed_at) }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4 mb-3">
                                                    <div>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                                            Previous
                                                            Status</p>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{
                                                                timeline.old_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                                            New Status
                                                        </p>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{
                                                                timeline.new_status || 'N/A'
                                                            }}</p>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                                        Encoded by</p>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{
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
                                    <AppIcon name="inbox" :size="48" class="mb-4 opacity-50" />
                                    <p class="text-lg">No Status History</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No status
                                        changes have been
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
                                                <AppIcon :name="getActivityIcon(activity.activity_type)" />
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
                                    <AppIcon name="history" :size="48" class="mb-4 opacity-50" />
                                    <p class="text-lg">No Activity Records</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No activities
                                        have been
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
            :has-edit-permission="canEditScholarshipAttachments" @success="handleModalSuccess" />

        <!-- View Attachment Modal -->
        <ViewAttachmentModal v-model:visible="showViewerModal" :attachment="viewerAttachment" />

        <!-- QR Code Modal -->
        <QrCodeModal v-model:visible="showQrModal" :qr-data="qrCodeData" />

        <AcademicEnrollmentModal v-model:visible="showEnrollmentModal" :mode="enrollmentModalMode"
            :enrollment="editingEnrollment" :profile-id="profile.profile_id" @success="handleModalSuccess" />

        <AcademicEnrollmentTermModal v-model:visible="showTermModal" :mode="termModalMode" :term="editingTerm"
            :enrollment-id="activeEnrollmentForTerm?.id ?? null" @success="handleModalSuccess" />

        <AcademicEnrollmentGraduationModal v-model:visible="showGraduationModal" :enrollment="enrollmentForGraduation"
            @success="handleModalSuccess" />

        <AcademicEnrollmentTermCompletionModal v-model:visible="showTermCompletionModal" :term="termForCompletion"
            @success="handleModalSuccess" />

        <AcademicRecordDeleteModal v-model:visible="showAcademicDeleteModal" :target="deleteTarget"
            :target-type="deleteTargetType" @success="handleModalSuccess" />

        <!-- Scholar Ledger PDF Preview -->
        <PdfPreviewModal :show="showPdfPreview" @update:show="showPdfPreview = $event" :htmlDoc="pdfPreviewHtml"
            :title="pdfPreviewTitle" :paperSize="pdfPreviewSize" />

        <!-- Ledger Options Modal (iOS style) -->
        <Dialog v-model:visible="showLedgerModal" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-modal ledger-ios-modal" :style="ledgerModalStyle">
                    <!-- Nav Bar -->
                    <div class="ios-nav-bar ledger-ios-nav" @pointerdown="onLedgerDragStart">
                        <AppButton icon="times" text rounded class="ios-nav-btn ios-nav-cancel ledger-nav-button"
                            @click="showLedgerModal = false" v-tooltip.top="'Close'" />
                        <span class="ios-nav-title">Generate Scholar Ledger</span>
                        <div class="ios-nav-actions ledger-nav-actions">
                            <AppButton v-if="hasPermission('scholarships.edit')" label="Save" text
                                class="ios-nav-btn ios-nav-action ledger-nav-button" :loading="ledgerSaving"
                                @click="saveLedger" />
                            <AppButton label="Generate" text class="ios-nav-btn ios-nav-action ledger-nav-button"
                                :disabled="ledgerSaving" @click="generateLedger" />
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="ios-body">
                        <div class="ios-section">
                            <div class="ios-section-footer">Year levels are fixed, including PGI and Review. Encode
                                the academic year, term, date obligated, OBR no., type of payment, amount, and ROS.
                                Use Add Term at the bottom of each year level for trimester or extra rows.</div>
                            <div class="ios-section-footer italic">Rows without encoded details are skipped when
                                generating
                                the PDF.</div>
                        </div>
                        <div class="ios-section">
                            <div class="ios-section-label">
                                <AppIcon name="file-text" :size="11" style="color: #007AFF; margin-right: 4px;" />
                                Ledger Details
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
                                <div class="ios-card">
                                    <div class="ios-row" style="align-items: flex-start;">
                                        <div style="display: flex; flex-direction: column; gap: 6px; width: 100%;">
                                            <span class="ios-row-label">Other Assistance</span>
                                            <Editor v-model="ledgerOtherAssistance" editorStyle="height: 120px"
                                                class="ledger-other-assistance-editor">
                                                <template #toolbar>
                                                    <span class="ql-formats">
                                                        <button class="ql-bold"></button>
                                                        <button class="ql-italic"></button>
                                                        <button class="ql-underline"></button>
                                                    </span>
                                                </template>
                                            </Editor>
                                        </div>
                                    </div>
                                </div>

                                <div class="ios-card">
                                    <div class="ios-row">
                                        <div style="display: flex; flex-direction: column; gap: 6px; width: 100%;">
                                            <span class="ios-row-label">Licensure Examination Result</span>
                                            <DatePicker v-model="ledgerLicensureExaminationResult"
                                                dateFormat="MM dd, yy" placeholder="April 08, 2026"
                                                class="w-full ledger-licensure-datepicker" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">
                                <AppIcon name="book" :size="11" style="color: #34C759; margin-right: 4px;" />
                                Manual Ledger Entries
                            </div>

                        </div>

                        <div v-for="section in ledgerSections" :key="section.yearLevel" class="ios-section">
                            <div class="ios-section-label">{{ formatYearLevel(section.yearLevel) }}</div>
                            <div class="ios-card" style="overflow: auto;">
                                <table style="width: 100%; min-width: 960px; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #f9f9fb; border-bottom: 0.5px solid #e5e5ea;">
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px;">
                                                Term</th>
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                Academic Year</th>
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                Date Obligated</th>
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                OBR No.</th>
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                Type of Payment</th>
                                            <th
                                                style="text-align: right; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                Amount</th>
                                            <th
                                                style="text-align: left; padding: 7px 12px; font-size: 11px; font-weight: 600; color: #8e8e93; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;">
                                                ROS (Months)</th>
                                            <th style="width: 52px; padding: 7px 12px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in section.rows" :key="row.id"
                                            style="border-bottom: 0.5px solid #e5e5ea;">
                                            <td style="padding: 8px 12px; min-width: 190px;">
                                                <input :value="row.term"
                                                    @input="handleLedgerTermInput(row, $event.target.value)"
                                                    placeholder="e.g. 3RD SEMESTER / TRIMESTER"
                                                    style="width: 100%; border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; font-size: 13px; color: #1c1c1e; outline: none; padding: 6px 10px; transition: border-color 0.15s;"
                                                    @focus="$event.target.style.borderColor = '#007AFF'"
                                                    @blur="$event.target.style.borderColor = '#d1d1d6'" />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 140px;">
                                                <AcademicYearSelect v-model="row.academicYear" ios-compact />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 150px;">
                                                <input v-model="row.dateObligated" type="date"
                                                    style="width: 100%; border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; font-size: 13px; color: #1c1c1e; outline: none; padding: 6px 10px; transition: border-color 0.15s;"
                                                    @focus="$event.target.style.borderColor = '#007AFF'"
                                                    @blur="$event.target.style.borderColor = '#d1d1d6'" />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 140px;">
                                                <input v-model="row.obrNo" placeholder="OBR-2026-001"
                                                    style="width: 100%; border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; font-size: 13px; color: #1c1c1e; outline: none; padding: 6px 10px; transition: border-color 0.15s;"
                                                    @focus="$event.target.style.borderColor = '#007AFF'"
                                                    @blur="$event.target.style.borderColor = '#d1d1d6'" />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 180px;">
                                                <Select v-model="row.paymentType" :options="ledgerPaymentTypes"
                                                    optionLabel="label" optionValue="value" placeholder="Select type"
                                                    style="width: 100%;" :pt="{
                                                        root: { style: 'border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; min-height: 38px;' },
                                                        label: { style: 'font-size: 13px; color: #1c1c1e; padding: 6px 10px;' },
                                                        dropdown: { style: 'width: 2.25rem; color: #6b7280;' },
                                                        overlay: { style: 'border-radius: 12px; overflow: hidden;' },
                                                        list: { style: 'padding: 4px;' },
                                                        option: { style: 'font-size: 13px; border-radius: 8px;' }
                                                    }" />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 130px;">
                                                <input v-model="row.amount" type="number" min="0" step="0.01"
                                                    placeholder="0.00"
                                                    style="width: 100%; border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; font-size: 13px; color: #1c1c1e; outline: none; padding: 6px 10px; text-align: right; transition: border-color 0.15s;"
                                                    @focus="$event.target.style.borderColor = '#007AFF'"
                                                    @blur="$event.target.style.borderColor = '#d1d1d6'" />
                                            </td>
                                            <td style="padding: 8px 12px; min-width: 70px; text-align: right;">
                                                <Select v-model="row.rosMonths" :options="ledgerRosOptions"
                                                    optionLabel="label" optionValue="value" placeholder="Select ROS"
                                                    style="width: 100%;" :pt="{
                                                        root: { style: 'border: 1px solid #d1d1d6; border-radius: 8px; background: #fff; min-height: 38px;' },
                                                        label: { style: 'font-size: 13px; color: #1c1c1e; padding: 6px 10px; text-align: right;' },
                                                        dropdown: { style: 'width: 2.25rem; color: #6b7280;' },
                                                        overlay: { style: 'border-radius: 12px; overflow: hidden;' },
                                                        list: { style: 'padding: 4px;' },
                                                        option: { style: 'font-size: 13px; border-radius: 8px;' }
                                                    }" />
                                            </td>
                                            <td style="padding: 8px 12px; white-space: nowrap; text-align: center;">
                                                <AppButton icon="trash" text severity="danger"
                                                    @click="removeLedgerRow(section.yearLevel, row.id)"
                                                    v-tooltip.top="'Remove row'" />
                                            </td>
                                        </tr>
                                        <tr v-if="section.rows.length === 0">
                                            <td colspan="8"
                                                style="padding: 14px 16px; text-align: center; color: #8e8e93; font-size: 13px;">
                                                No terms added for this year level yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div
                                    style="padding: 12px 16px; border-top: 0.5px solid #e5e5ea; display: flex; justify-content: flex-start;">
                                    <AppButton icon="plus" label="Add Term" text size="xsmall" severity="info"
                                        @click="addLedgerRow(section.yearLevel)" />
                                </div>
                            </div>
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
                        <AppButton icon="times" text rounded
                            class="ios-nav-btn ios-nav-cancel !bg-transparent !border-0 !shadow-none"
                            @click="showCertPicker = false" v-tooltip.top="'Close'" />
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
                                        <AppIcon name="file-word" :size="16" style="color: #007AFF;" />
                                        <span style="font-size: 14px; color: #1c1c1e;">Certification for Review</span>
                                    </div>
                                    <AppIcon name="chevron-right" :size="12" style="color: #c7c7cc; flex-shrink: 0;" />
                                </div>
                                <div class="ios-row" style="cursor: pointer;"
                                    @click="generateCertification('postgrad')">
                                    <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                        <AppIcon name="file-word" :size="16" style="color: #007AFF;" />
                                        <span style="font-size: 14px; color: #1c1c1e;">Certification for
                                            Post-Grad</span>
                                    </div>
                                    <AppIcon name="chevron-right" :size="12" style="color: #c7c7cc; flex-shrink: 0;" />
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
import { ref, computed, watch, onMounted, onBeforeUnmount, inject } from 'vue';
import axios from 'axios';
import { toast } from '@/utils/toast';
import { usePermission } from '@/composable/permissions';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { useSystemOptions } from '@/composables/useSystemOptions';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';
import ScholarLedgerTemplate from '@/Pages/Scholarship/Pdf/ScholarLedgerTemplate.vue';
import ScholarCertificationTemplate from '@/Pages/Scholarship/Pdf/ScholarCertificationTemplate.vue';
import PdfPreviewModal from '@/Pages/FundTransactions/Modal/PdfPreviewModal.vue';
import ContextMenu from 'primevue/contextmenu';

import PersonalInformationModal from '@/Components/modals/PersonalInformationModal.vue';
import FamilyInformationModal from '@/Components/modals/FamilyInformationModal.vue';
import ManageAttachmentsModal from '@/Components/modals/ManageAttachmentsModal.vue';
import ViewAttachmentModal from '@/Components/modals/ViewAttachmentModal.vue';
import QrCodeModal from '@/Components/modals/QrCodeModal.vue';
import AcademicEnrollmentModal from '@/Components/modals/AcademicEnrollmentModal.vue';
import AcademicEnrollmentTermModal from '@/Components/modals/AcademicEnrollmentTermModal.vue';
import AcademicEnrollmentGraduationModal from '@/Components/modals/AcademicEnrollmentGraduationModal.vue';
import AcademicEnrollmentTermCompletionModal from '@/Components/modals/AcademicEnrollmentTermCompletionModal.vue';
import AcademicRecordDeleteModal from '@/Components/modals/AcademicRecordDeleteModal.vue';
import ObligationsTransactions from '@/Components/ObligationsTransactions.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';

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
const ledgerPaymentTypes = useSystemOptions('disbursement_type');
const ledgerRosOptions = [
    { label: '—', value: '' },
    { label: '4M', value: '4' },
    { label: '6M', value: '6' },
    { label: '12M', value: '12' },
];

// Ledger modal drag
const ledgerDragOffset = ref({ x: 0, y: 0 });
const ledgerDragStart = ref(null);
const ledgerModalStyle = computed(() => ({
    width: '1360px',
    maxWidth: 'calc(100vw - 24px)',
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
    maxWidth: 'calc(100vw - 24px)',
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

const LEDGER_YEAR_LEVELS = ['1ST YEAR', '2ND YEAR', '3RD YEAR', '4TH YEAR', '5TH YEAR', 'PGI', 'REVIEW'];
const LEDGER_STANDARD_TERMS = ['1ST SEMESTER', '2ND SEMESTER'];
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

const autoRosValue = (semester) => {
    if (is4MonthTerm(semester)) return '4';
    return '6';
};

const normalizeLedgerYearLevel = (value) => {
    const formatted = formatYearLevel(value);
    return LEDGER_YEAR_LEVELS.includes(formatted) ? formatted : null;
};

let nextLedgerRowId = 0;

const createLedgerRowId = () => `ledger-row-${++nextLedgerRowId}`;

const normalizeLedgerText = (value) => String(value ?? '').trim().toUpperCase();

const createLedgerRow = (yearLevel, overrides = {}) => {
    const term = overrides.term ?? '';

    return {
        id: createLedgerRowId(),
        yearLevel,
        term,
        academicYear: overrides.academicYear ?? '',
        dateObligated: overrides.dateObligated ?? '',
        obrNo: overrides.obrNo ?? '',
        paymentType: overrides.paymentType ?? '',
        amount: overrides.amount ?? '',
        rosMonths: Object.prototype.hasOwnProperty.call(overrides, 'rosMonths')
            ? overrides.rosMonths
            : autoRosValue(term),
    };
};

const defaultTermsForYearLevel = (yearLevel) => {
    if (yearLevel === 'REVIEW') {
        return ['REVIEW'];
    }

    return [...LEDGER_STANDARD_TERMS];
};

const buildDefaultLedgerSections = () => {
    return LEDGER_YEAR_LEVELS.map((yearLevel) => ({
        yearLevel,
        rows: defaultTermsForYearLevel(yearLevel).map((term) => createLedgerRow(yearLevel, { term })),
    }));
};

const buildLedgerSectionsFromEntries = (entries = []) => {
    const groupedEntries = new Map();

    for (const entry of entries) {
        const normalizedYearLevel = normalizeLedgerYearLevel(entry?.year_level);
        if (!normalizedYearLevel) {
            continue;
        }

        const yearEntries = groupedEntries.get(normalizedYearLevel) ?? [];
        yearEntries.push(entry);
        groupedEntries.set(normalizedYearLevel, yearEntries);
    }

    return LEDGER_YEAR_LEVELS.map((yearLevel) => {
        const storedRows = groupedEntries.get(yearLevel) ?? [];

        return {
            yearLevel,
            rows: storedRows.length > 0
                ? storedRows.map((entry) => createLedgerRow(yearLevel, {
                    term: entry.semester ?? '',
                    academicYear: entry.academic_year ?? '',
                    dateObligated: entry.date_obligated ?? '',
                    obrNo: entry.obr_no ?? '',
                    paymentType: entry.disbursement_type ?? '',
                    amount: entry.amount ?? '',
                    rosMonths: Object.prototype.hasOwnProperty.call(entry, 'ros_months')
                        ? String(entry.ros_months ?? '')
                        : autoRosValue(entry.semester ?? ''),
                }))
                : defaultTermsForYearLevel(yearLevel).map((term) => createLedgerRow(yearLevel, { term })),
        };
    });
};

const ledgerSections = ref(buildDefaultLedgerSections());
const ledgerOtherAssistance = ref('');
const ledgerLicensureExaminationResult = ref(null);
const ledgerSaving = ref(false);

const getLedgerSection = (yearLevel) => {
    return ledgerSections.value.find((section) => section.yearLevel === yearLevel);
};

const addLedgerRow = (yearLevel) => {
    const section = getLedgerSection(yearLevel);
    if (!section) return;

    const hasTrimesterRow = section.rows.some((row) => {
        const normalizedTerm = normalizeLedgerText(row.term);
        return normalizedTerm.includes('3RD SEMESTER') || normalizedTerm.includes('TRIMESTER');
    });

    section.rows.push(createLedgerRow(yearLevel, {
        term: yearLevel === 'REVIEW' ? '' : (hasTrimesterRow ? '' : '3RD SEMESTER'),
    }));
};

const removeLedgerRow = (yearLevel, rowId) => {
    const section = getLedgerSection(yearLevel);
    if (!section) return;

    section.rows = section.rows.filter((row) => row.id !== rowId);
};

const handleLedgerTermInput = (row, value) => {
    const previousAutoRos = autoRosValue(row.term);
    row.term = value;

    if (row.rosMonths === previousAutoRos || row.rosMonths === null || row.rosMonths === undefined) {
        row.rosMonths = autoRosValue(value);
    }
};

const normalizeLedgerAmount = (value) => {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
};

const isLedgerRowFilled = (row) => {
    return [row.academicYear, row.dateObligated, row.obrNo, row.paymentType]
        .some((value) => String(value ?? '').trim() !== '')
        || normalizeLedgerAmount(row.amount) !== null;
};

const ledgerPrintableRows = computed(() => {
    return ledgerSections.value.flatMap((section) => {
        return section.rows
            .filter((row) => isLedgerRowFilled(row))
            .map((row) => ({
                id: row.id,
                year_level: section.yearLevel,
                academic_year: String(row.academicYear ?? '').trim(),
                semester: String(row.term ?? '').trim(),
                date_obligated: row.dateObligated || '',
                obr_no: String(row.obrNo ?? '').trim(),
                disbursement_type: String(row.paymentType ?? '').trim(),
                amount: normalizeLedgerAmount(row.amount),
                ros_months: row.rosMonths === '' || row.rosMonths === null || row.rosMonths === undefined
                    ? null
                    : Number(row.rosMonths),
            }));
    });
});

const props = defineProps({
    profile: Object,
});

const savedLedgerState = ref(props.profile?.scholar_ledger ?? null);

const parseLedgerDisplayDate = (value) => {
    const rawValue = String(value ?? '').trim();
    if (!rawValue) {
        return null;
    }

    const parsedDate = new Date(rawValue);
    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
};

const formatLedgerDisplayDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return null;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
    }).format(value);
};

const hydrateLedgerState = (ledger = savedLedgerState.value) => {
    ledgerOtherAssistance.value = ledger?.other_assistance ?? '';
    ledgerLicensureExaminationResult.value = parseLedgerDisplayDate(ledger?.licensure_examination_result);
    ledgerSections.value = buildLedgerSectionsFromEntries(ledger?.entries ?? []);
};

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
const showEnrollmentModal = ref(false);
const showTermModal = ref(false);
const showGraduationModal = ref(false);
const showTermCompletionModal = ref(false);
const showAcademicDeleteModal = ref(false);
const enrollmentModalMode = ref('add');
const termModalMode = ref('add');
const editingEnrollment = ref(null);
const activeEnrollmentForTerm = ref(null);
const editingTerm = ref(null);
const enrollmentForGraduation = ref(null);
const termForCompletion = ref(null);
const deleteTarget = ref(null);
const deleteTargetType = ref('term');
const qrCodeData = ref(null);
const selectedRecord = ref(null);
const selectedAcademicTerm = ref(null);
const academicTermContextMenu = ref(null);
const viewerAttachment = ref(null);
const activityLogs = ref([]);
const statusTimeline = ref([]);

// Status composable
const { statusOptions, getStatusLabel, getStatusSeverity } = useScholarshipStatus();

// Watch for tab changes and persist to localStorage
watch(activeTab, (newValue) => {
    localStorage.setItem('scholarProfileActiveTab', newValue);
    // Load status timeline when tab 5 (Approval History) is selected
    if (newValue === '5') {
        loadStatusTimeline();
    }
});

// Load status timeline if Approval History tab is already open on mount
onMounted(() => {
    if (activeTab.value === '5') {
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

const OPEN_ACADEMIC_TERM_STATUSES = ['pending', 'active'];

const normalizeAcademicTermStatus = (status) => String(status ?? '').trim().toLowerCase();

const countOpenAcademicTerms = (terms = []) => {
    return terms.filter((term) => OPEN_ACADEMIC_TERM_STATUSES.includes(normalizeAcademicTermStatus(term?.unified_status))).length;
};

const createAcademicDisplayRows = (terms = [], enrollmentId = null) => {
    return terms.flatMap((term) => {
        const mappedRecords = Array.isArray(term?.record_maps)
            ? term.record_maps
                .map((recordMap) => recordMap?.scholarship_record || null)
                .filter(Boolean)
            : [];

        const fallbackRecord = term?.primary_record_map?.scholarship_record || null;
        const linkedRecords = mappedRecords.length > 0
            ? mappedRecords
            : (fallbackRecord ? [fallbackRecord] : []);

        if (linkedRecords.length === 0) {
            return [{
                ...term,
                displayId: `term:${term.id}:unmapped`,
                isGroupedTerm: true,
                enrollmentId,
                legacyRecord: null,
                attachments: [],
                linkedRecordCount: 0,
                linkedRecordIndex: 0,
            }];
        }

        return linkedRecords.map((record, linkedRecordIndex) => ({
            ...term,
            displayId: `term:${term.id}:record:${record.id ?? linkedRecordIndex}`,
            isGroupedTerm: true,
            enrollmentId,
            legacyRecord: record,
            attachments: record.attachments || [],
            linkedRecordCount: linkedRecords.length,
            linkedRecordIndex,
        }));
    });
};

const buildLegacyAcademicEnrollmentGroups = (records = []) => {
    const groups = new Map();

    records.forEach((record) => {
        const key = [
            record.profile_id || props.profile.profile_id,
            record.school_id ?? 'none',
            record.course_id ?? 'none',
        ].join(':');

        if (!groups.has(key)) {
            groups.set(key, {
                id: `legacy:${key}`,
                isGroupedEnrollment: false,
                program: record.program || null,
                school: record.school || null,
                course: record.course || null,
                graduation_date: null,
                graduation_remarks: null,
                is_graduated: false,
                terms: [],
            });
        }

        groups.get(key).terms.push({
            ...record,
            displayId: `legacy-record:${record.id ?? record.record_id ?? groups.get(key).terms.length}`,
            isGroupedTerm: false,
            enrollmentId: null,
            legacyRecord: record,
            attachments: record.attachments || [],
            linkedRecordCount: 1,
            linkedRecordIndex: 0,
        });
    });

    return Array.from(groups.values()).map((group) => {
        const openTermCount = countOpenAcademicTerms(group.terms);

        return {
            ...group,
            displayTerms: group.terms,
            openTermCount,
            needsTermReview: openTermCount > 1,
        };
    });
};

const supportsAcademicEnrollmentCrud = computed(() => Array.isArray(props.profile.academic_enrollments));

const academicEnrollmentGroups = computed(() => {
    const enrollments = Array.isArray(props.profile.academic_enrollments)
        ? props.profile.academic_enrollments
        : [];

    if (enrollments.length > 0) {
        return enrollments.map((enrollment) => ({
            terms: Array.isArray(enrollment.terms)
                ? enrollment.terms.map((term) => ({
                    ...term,
                    isGroupedTerm: true,
                    enrollmentId: enrollment.id,
                    legacyRecord: term.primary_record_map?.scholarship_record || null,
                    attachments: term.primary_record_map?.scholarship_record?.attachments || [],
                }))
                : [],
            displayTerms: createAcademicDisplayRows(enrollment.terms, enrollment.id),
            id: enrollment.id,
            isGroupedEnrollment: true,
            program: enrollment.program || null,
            school: enrollment.school || null,
            course: enrollment.course || null,
            graduation_date: enrollment.graduation_date || null,
            graduation_remarks: enrollment.graduation_remarks || null,
            is_graduated: Boolean(enrollment.is_graduated || enrollment.graduation_date),
            openTermCount: countOpenAcademicTerms(enrollment.terms),
            needsTermReview: countOpenAcademicTerms(enrollment.terms) > 1,
        }));
    }

    return buildLegacyAcademicEnrollmentGroups(scholarshipRecords.value);
});

const academicTermsNeedingReviewCount = computed(() => {
    return academicEnrollmentGroups.value.filter((enrollment) => enrollment.needsTermReview).length;
});

const academicTermsNeedReview = computed(() => academicTermsNeedingReviewCount.value > 0);

const canManageAcademicEnrollment = (enrollment) => {
    return supportsAcademicEnrollmentCrud.value && Boolean(enrollment?.isGroupedEnrollment && typeof enrollment?.id === 'number');
};

const canManageAcademicTerm = (term) => {
    return supportsAcademicEnrollmentCrud.value && Boolean(term?.isGroupedTerm && typeof term?.id === 'number');
};

const canCreateAcademicEnrollment = computed(() => supportsAcademicEnrollmentCrud.value && hasPermission('scholarships.create'));
const canCreateAcademicTerm = computed(() => hasPermission('scholarships.create'));
const canEditAcademicEnrollmentDetails = computed(() => hasPermission('scholarships.edit'));
const canDeleteAcademicEnrollmentDetails = computed(() => hasPermission('scholarships.delete'));
const canEditAcademicTermDetails = computed(() => hasPermission('scholarships.edit'));
const canDeleteAcademicTermDetails = computed(() => hasPermission('scholarships.delete'));
const hasAcademicTermActionsPermission = computed(() => canEditAcademicTermDetails.value || canDeleteAcademicTermDetails.value);
const canViewScholarshipAttachments = computed(() => hasPermission('scholarships.view'));
const canEditScholarshipAttachments = computed(() => hasPermission('scholarships.edit'));

const canManageAcademicEnrollmentActions = (enrollment) => {
    return canManageAcademicEnrollment(enrollment) && (
        canCreateAcademicTerm.value
        || canEditAcademicEnrollmentDetails.value
        || canDeleteAcademicEnrollmentDetails.value
    );
};

const canOpenAcademicTermActions = (term) => {
    return canManageAcademicTerm(term) && hasAcademicTermActionsPermission.value;
};

const isCompletedAcademicTerm = (term) => {
    return String(term?.unified_status ?? '').toLowerCase() === 'completed';
};

const academicTermContextMenuItems = computed(() => {
    if (!selectedAcademicTerm.value || !canOpenAcademicTermActions(selectedAcademicTerm.value)) {
        return [];
    }

    const items = [];

    if (canEditAcademicTermDetails.value) {
        items.push(
            {
                label: 'Edit Term',
                icon: 'pencil',
                command: () => openEditTermModal(selectedAcademicTerm.value),
            },
            {
                label: isCompletedAcademicTerm(selectedAcademicTerm.value) ? 'Update Completion' : 'Complete Semester',
                icon: 'check-circle',
                command: () => openCompleteTermModal(selectedAcademicTerm.value),
            },
        );
    }

    if (canDeleteAcademicTermDetails.value) {
        items.push({
            label: 'Delete Term',
            icon: 'trash',
            command: () => confirmDeleteTerm(selectedAcademicTerm.value),
        });
    }

    return items;
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

const resolveAcademicRecord = (termOrRecord) => {
    if (!termOrRecord) {
        return null;
    }

    return termOrRecord.legacyRecord || termOrRecord;
};

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
        'approved': 'check',
        'denied': 'times',
        'pending': 'clock',
        'active': 'circle-fill',
        'completed': 'check-circle',
        'withdrawn': 'times-circle',
        'loa': 'pause',
        'suspended': 'ban',
        'unknown': 'question',
        'declined': 'times',
        'conditional': 'info-circle',
        'resubmitted': 'refresh',
        'discontinued': 'pause',
        'renewal_application': 'plus'
    };
    return icons[action] || 'circle';
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

watch(
    () => props.profile?.scholar_ledger,
    (ledger) => {
        savedLedgerState.value = ledger ?? null;

        if (showLedgerModal.value) {
            return;
        }

        hydrateLedgerState(ledger);
    },
    { immediate: true, deep: true }
);

const openLedgerModal = () => {
    hydrateLedgerState();
    showLedgerModal.value = true;
};

const getEditorTextContent = (value) => {
    const html = String(value ?? '').trim();
    if (!html) {
        return '';
    }

    if (typeof DOMParser !== 'undefined') {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return (doc.body.textContent || '')
            .replace(/\u00a0/g, ' ')
            .trim();
    }

    return html
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .trim();
};

const normalizeEditorHtml = (value) => {
    const html = String(value ?? '').trim();
    return getEditorTextContent(html) ? html : null;
};

const buildLedgerPayload = () => ({
    other_assistance: normalizeEditorHtml(ledgerOtherAssistance.value),
    licensure_examination_result: formatLedgerDisplayDate(ledgerLicensureExaminationResult.value),
    entries: ledgerPrintableRows.value,
});

const persistLedger = async ({ showSuccessToast = true } = {}) => {
    if (!hasPermission('scholarships.edit')) {
        return true;
    }

    ledgerSaving.value = true;

    try {
        const payload = buildLedgerPayload();
        const response = await axios.put(route('scholarship.profile.ledger.update', props.profile.profile_id), payload);
        savedLedgerState.value = response.data?.data ?? {
            other_assistance: payload.other_assistance,
            licensure_examination_result: payload.licensure_examination_result,
            entries: payload.entries,
        };

        if (showSuccessToast) {
            toast.success('Scholar ledger saved successfully.');
        }

        return true;
    } catch (error) {
        console.error('Failed to save scholar ledger:', error);
        toast.error(error.response?.data?.message || 'Failed to save scholar ledger.');
        return false;
    } finally {
        ledgerSaving.value = false;
    }
};

const saveLedger = async () => {
    await persistLedger();
};

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

const generateLedger = async () => {
    if (!ledgerPrintableRows.value.length) {
        toast.error('Add at least one ledger entry before generating the scholar ledger.');
        return;
    }

    const savedSuccessfully = await persistLedger({ showSuccessToast: false });
    if (!savedSuccessfully) {
        return;
    }

    const today = new Date().toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
    const authUser = usePage().props.auth?.user;
    const bodyHtml = renderVueTemplate(ScholarLedgerTemplate, {
        profile: props.profile,
        preparedBy: authUser?.name ?? '',
        preparedByDesignation: authUser?.office_designation ?? '',
        today,
        otherAssistance: ledgerOtherAssistance.value.trim(),
        licensureExaminationResult: formatLedgerDisplayDate(ledgerLicensureExaminationResult.value) ?? '',
        ledgerEntries: ledgerPrintableRows.value,
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

// Academic enrollment and term actions
const openAddEnrollmentModal = () => {
    if (!canCreateAcademicEnrollment.value) {
        return;
    }

    enrollmentModalMode.value = 'add';
    editingEnrollment.value = null;
    showEnrollmentModal.value = true;
};

const openEditEnrollmentModal = (enrollment) => {
    if (!canManageAcademicEnrollment(enrollment) || !canEditAcademicEnrollmentDetails.value) {
        return;
    }

    enrollmentModalMode.value = 'edit';
    editingEnrollment.value = enrollment;
    showEnrollmentModal.value = true;
};

const openAddTermModal = (enrollment) => {
    if (!canManageAcademicEnrollment(enrollment) || !canCreateAcademicTerm.value) {
        return;
    }

    termModalMode.value = 'add';
    activeEnrollmentForTerm.value = enrollment;
    editingTerm.value = null;
    showTermModal.value = true;
};

const openAcademicTermContextMenu = (event, term) => {
    if (!canOpenAcademicTermActions(term)) {
        return;
    }

    selectedAcademicTerm.value = term;
    academicTermContextMenu.value?.show(event);
};

const openEditTermModal = (term) => {
    if (!canManageAcademicTerm(term) || !canEditAcademicTermDetails.value) {
        return;
    }

    termModalMode.value = 'edit';
    editingTerm.value = term;
    activeEnrollmentForTerm.value = academicEnrollmentGroups.value.find((enrollment) => enrollment.id === term.enrollmentId) || null;
    showTermModal.value = true;
};

const openGraduationModal = (enrollment) => {
    if (!canManageAcademicEnrollment(enrollment) || !canEditAcademicEnrollmentDetails.value) {
        return;
    }

    enrollmentForGraduation.value = enrollment;
    showGraduationModal.value = true;
};

const openCompleteTermModal = (term) => {
    if (!canManageAcademicTerm(term) || !canEditAcademicTermDetails.value) {
        return;
    }

    termForCompletion.value = term;
    showTermCompletionModal.value = true;
};

const confirmDeleteEnrollment = (enrollment) => {
    if (!canManageAcademicEnrollment(enrollment) || !canDeleteAcademicEnrollmentDetails.value) {
        return;
    }

    deleteTargetType.value = 'enrollment';
    deleteTarget.value = enrollment;
    showAcademicDeleteModal.value = true;
};

const confirmDeleteTerm = (term) => {
    if (!canManageAcademicTerm(term) || !canDeleteAcademicTermDetails.value) {
        return;
    }

    deleteTargetType.value = 'term';
    deleteTarget.value = term;
    showAcademicDeleteModal.value = true;
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
    editingEnrollment.value = null;
    activeEnrollmentForTerm.value = null;
    editingTerm.value = null;
    enrollmentForGraduation.value = null;
    termForCompletion.value = null;
    deleteTarget.value = null;
    selectedAcademicTerm.value = null;
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
        'profile_edited': 'user',
        'attachment_uploaded': 'upload',
        'record_created': 'plus-circle',
        'record_updated': 'pencil',
        'record_deleted': 'trash',
        'status_changed': 'arrow-right-arrow-left',
        'profile_created': 'user-plus'
    };
    return icons[activityType] || 'history';
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
        const response = await axios.get(`/activity-logs/${props.profile.profile_id}`);
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
        const response = await axios.get(`/activity-logs/${props.profile.profile_id}/status-timeline`);
        statusTimeline.value = response.data.data || response.data || [];
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

.ledger-ios-modal {
    max-height: calc(100vh - 24px);
}

.ledger-ios-nav {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
}

.ledger-ios-nav .ios-nav-title {
    text-align: center;
    min-width: 0;
}

.ledger-nav-actions {
    position: static;
    transform: none;
    right: auto;
    top: auto;
    display: flex;
    align-items: center;
    justify-self: end;
}

.ledger-ios-nav :deep(.ledger-nav-button) {
    position: static;
    top: auto;
    transform: none;
    min-height: 0;
    padding: 4px 8px;
    border: none;
    box-shadow: none;
    background: transparent;
}

.ledger-ios-nav :deep(.ledger-nav-button.p-button:hover) {
    background: rgba(0, 0, 0, 0.04);
}

.ledger-ios-nav :deep(.ledger-nav-button .p-button-label) {
    font-size: 15px;
    font-weight: 600;
}

.ledger-ios-nav :deep(.ledger-nav-button .p-button-icon) {
    font-size: 14px;
}

.ledger-ios-nav :deep(.ledger-nav-button.ios-nav-cancel) {
    justify-self: start;
}

.ledger-ios-nav :deep(.ledger-nav-button.ios-nav-action) {
    color: #374151;
}

.ledger-other-assistance-editor {
    width: 100%;
}

.ledger-other-assistance-editor :deep(.p-editor-container) {
    border: 1px solid #d1d1d6;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}

.ledger-other-assistance-editor :deep(.ql-toolbar.ql-snow) {
    border: none;
    border-bottom: 0.5px solid #e5e5ea;
    padding: 6px 8px;
}

.ledger-other-assistance-editor :deep(.ql-container.ql-snow) {
    border: none;
    font-size: 14px;
    color: #1c1c1e;
}

.ledger-other-assistance-editor :deep(.ql-editor) {
    padding: 10px 12px;
}

.ledger-licensure-datepicker {
    width: 100%;
}

.ledger-licensure-datepicker :deep(.p-inputtext),
.ledger-licensure-datepicker :deep(.p-datepicker-input) {
    border: none;
    background: transparent;
    box-shadow: none;
    padding: 0;
    font-size: 14px;
    color: #1c1c1e;
    width: 100%;
    outline: none;
}

.show-profile-table :deep(.p-datatable-table-container) {
    overflow-x: auto;
}

.academic-terms-table :deep(.p-datatable-tbody > tr > td:first-child) {
    border-left: 1px solid var(--p-datatable-body-cell-border-color, var(--p-content-border-color, #e5e7eb));
}
</style>
