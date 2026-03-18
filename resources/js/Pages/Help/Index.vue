<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const activeAccordion = ref(0);

const faqs = ref([
    {
        label: 'Getting Started with the System',
        icon: 'pi pi-fw pi-book',
        content: 'Overview of the scholarship system and its main components.'
    },
    {
        label: 'Adding a New Applicant',
        icon: 'pi pi-fw pi-user-plus',
        content: 'Step-by-step guide for adding new scholarship applicants.'
    },
    {
        label: 'The Approval Workflow',
        icon: 'pi pi-fw pi-check-circle',
        content: 'Understanding the complete approval process from submission to final decision.'
    },
    {
        label: 'Understanding Unified Status',
        icon: 'pi pi-fw pi-sync',
        content: 'How the unified status system works and what each status means.'
    },
    {
        label: 'Common Tasks',
        icon: 'pi pi-fw pi-list',
        content: 'Quick reference for frequently performed tasks in the system.'
    }
]);
</script>

<template>
    <AdminLayout>

        <Head title="Help & Instructions" />

        <div class="space-y-6">
            <!-- Header with Toolbar Layout -->
            <Toolbar class="border-0 bg-transparent p-0">
                <template #start>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-16 h-1">
                            <i class="pi pi-book text-indigo-900" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-700">Help & Instructions</h1>
                            <p class="text-gray-600 mt-1">Learn how to use the Scholarship Program Management System</p>
                        </div>
                    </div>
                </template>
            </Toolbar>

            <!-- System Overview -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <i class="pi pi-info-circle text-blue-600 text-2xl flex-shrink-0 mt-1"></i>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">What is this System?</h2>
                        <p class="text-gray-700 leading-relaxed">
                            The Scholarship Program Management System helps you manage scholarship applications from
                            start to
                            finish.
                            Track applicants, approve or deny applications, manage disbursements, and generate
                            reports📎 all in one
                            place.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Content - Accordion -->
            <Accordion :activeIndex="activeAccordion" @update:activeIndex="(val) => activeAccordion = val">
                <!-- Getting Started -->
                <AccordionTab>
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <i class="pi pi-fw pi-book text-blue-600"></i>
                            <span class="font-semibold text-gray-800">Getting Started with the System</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-bold text-lg text-gray-900 mb-3">Welcome to the Scholarship Program System
                            </h4>
                            <p class="text-gray-700 mb-4">
                                This system provides a comprehensive platform for managing scholarship applications and
                                recipient
                                information.
                                Below is an overview of the main components:
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-blue-900 mb-2">
                                    <i class="pi pi-fw pi-clipboard mr-2"></i>Applicants
                                </h5>
                                <p class="text-sm text-gray-700">
                                    The entry point for new scholarship applicants. All new applicants begin their
                                    journey here
                                    before being
                                    evaluated and moved to the next stage of the process.
                                </p>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-green-900 mb-2">
                                    <i class="pi pi-fw pi-check-circle mr-2"></i>Interviewed Applicants
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Applicants who have been reviewed by program managers and are ready for final
                                    approval or denial
                                    decisions.
                                </p>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-purple-900 mb-2">
                                    <i class="pi pi-fw pi-users mr-2"></i>Profiles
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Complete information and history of approved scholars. Track their progress,
                                    requirements, and
                                    disbursements.
                                </p>
                            </div>

                            <div class="bg-orange-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-orange-900 mb-2">
                                    <i class="pi pi-fw pi-book mr-2"></i>Documents and Forms
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Manage scholarship program documents, forms, and official correspondence.
                                </p>
                            </div>
                        </div>
                    </div>
                </AccordionTab>

                <!-- Adding New Applicant -->
                <AccordionTab>
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <i class="pi pi-fw pi-user-plus text-green-600"></i>
                            <span class="font-semibold text-gray-800">Adding a New Applicant</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <h4 class="font-bold text-lg text-gray-900">Step-by-Step Process</h4>

                        <div class="space-y-3">
                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 text-gray-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">1</span>
                                    Navigate to Applicants
                                </h5>
                                <p class="text-sm text-gray-700 mt-1">
                                    Go to the Scholarship menu and select "Applicants" from the sidebar.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 text-gray-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">2</span>
                                    Click "Add New Applicant"
                                </h5>
                                <p class="text-sm text-gray-700 mt-1">
                                    Look for the "Add New" button and click to open the applicant form.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 text-gray-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">3</span>
                                    Fill in the Application Form
                                </h5>
                                <p class="text-sm text-gray-700 mt-1">
                                    Enter the applicant's personal information including name, contact details, academic
                                    information, and
                                    select the appropriate scholarship program.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 text-gray-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">4</span>
                                    Upload Required Documents
                                </h5>
                                <p class="text-sm text-gray-700 mt-1">
                                    Attach all required documents (birth certificate, school records, financial
                                    documents, etc.)
                                    based on
                                    the selected scholarship program's requirements.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 text-gray-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">5</span>
                                    Submit the Application
                                </h5>
                                <p class="text-sm text-gray-700 mt-1">
                                    Click "Save" or "Submit" to add the applicant to the applicant list.
                                </p>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <i class="pi pi-fw pi-info-circle mr-2"></i>
                                <strong>Note:</strong> All required fields must be completed before submission. The
                                system will
                                validate
                                your entries and notify you of any errors.
                            </p>
                        </div>
                    </div>
                </AccordionTab>

                <!-- Approval Workflow -->
                <AccordionTab>
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <i class="pi pi-fw pi-check-circle text-purple-600"></i>
                            <span class="font-semibold text-gray-800">The Approval Workflow</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <h4 class="font-bold text-lg text-gray-900">Complete Approval Process</h4>

                        <div class="space-y-3">
                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">Stage 1: Applicants (Initial Submission)
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Applicants are added to the applicant list. Their documents are verified to ensure
                                    all
                                    required
                                    files are present.
                                    The system checks for completeness and compliance with program requirements.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">Stage 2: Evaluation & Decision by Program
                                    Manager</h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    Program managers thoroughly evaluate each applicant based on:
                                </p>
                                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-2">
                                    <li>Academic performance and records</li>
                                    <li>Financial need assessment</li>
                                    <li>Compliance with program eligibility criteria</li>
                                    <li>Document verification and authenticity</li>
                                    <li>Overall fit for the scholarship program</li>
                                </ul>
                                <p class="text-sm text-gray-700 mt-2">
                                    <strong>The program manager then makes the approval or denial decision</strong> and
                                    moves the
                                    applicant to the "Interviewed Applicants" list with their decision recorded.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">Stage 3: Notification & Review by Approver
                                </h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    The approver (administrator or designated authority) is notified when new
                                    applications
                                    have been approved or denied by the program manager. This review serves as:
                                </p>
                                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-2">
                                    <li>A final verification step to prevent mistakes or misclicking</li>
                                    <li>Quality assurance for the decision made by the program manager</li>
                                    <li>Audit trail confirmation for compliance purposes</li>
                                </ul>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">Stage 4: Finalization by Approver</h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    The approver reviews the program manager's decision and finalizes the action:
                                </p>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2">
                                        <i class="pi pi-check text-green-600 mt-1"></i>
                                        <div>
                                            <strong class="text-gray-900">Finalize Approval:</strong>
                                            <p class="text-sm text-gray-700">Applicant is confirmed as accepted and
                                                moves to the
                                                Profiles section as an active scholar.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <i class="pi pi-times text-red-600 mt-1"></i>
                                        <div>
                                            <strong class="text-gray-900">Finalize Denial:</strong>
                                            <p class="text-sm text-gray-700">Application rejection is confirmed and
                                                applicant is
                                                notified. The applicant can reapply in future cycles.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">Stage 5: Active Scholar Profile</h5>
                                <p class="text-sm text-gray-700">
                                    Approved and finalized applicants become active scholars. Their information is
                                    maintained in the
                                    Profiles section where requirements tracking, disbursement management, and progress
                                    monitoring
                                    occur.
                                </p>
                            </div>
                        </div>

                        <Divider />

                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <i class="pi pi-fw pi-info-circle mr-2"></i>
                                <strong>Tip:</strong> You can view the complete history of an applicant including all
                                approval
                                actions and status changes in their profile under the "Approval History" tab.
                            </p>
                        </div>
                    </div>
                </AccordionTab>

                <!-- Unified Status -->
                <AccordionTab>
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <i class="pi pi-fw pi-sync text-orange-600"></i>
                            <span class="font-semibold text-gray-800">Understanding Unified Status</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <h4 class="font-bold text-lg text-gray-900">What is Unified Status?</h4>

                        <p class="text-gray-700">
                            The Unified Status is a comprehensive system that consolidates an applicant's current state
                            across all
                            stages of the
                            scholarship process. It provides a single source of truth about where each applicant stands
                            in the
                            workflow.
                        </p>

                        <Divider />

                        <h5 class="font-bold text-gray-900 mt-4">Status Types and Meanings</h5>

                        <div class="space-y-3">
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-yellow-500 text-white rounded-full text-sm font-semibold">PENDING</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Applicant has been added to the applicant list. Documents are being verified and
                                    initial
                                    screening is in progress. Waiting for review by program manager.
                                </p>
                            </div>

                            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-green-500 text-white rounded-full text-sm font-semibold">APPROVED</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Application has been officially approved by both program manager and approver.
                                    The applicant is ready to transition to active scholar status.
                                </p>
                            </div>

                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-red-500 text-white rounded-full text-sm font-semibold">DENIED</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Application has been rejected. The applicant has been notified and may reapply
                                    in future scholarship cycles if eligible.
                                </p>
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm font-semibold">ACTIVE</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Scholar is currently active and enrolled. They have access to all scholar services
                                    and benefits. Requirements tracking and disbursements are managed in this status.
                                </p>
                            </div>

                            <div class="bg-gray-50 border-l-4 border-gray-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-gray-500 text-white rounded-full text-sm font-semibold">COMPLETED</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Scholar has successfully completed their scholarship program. All requirements
                                    have been fulfilled and the scholarship duration has ended.
                                </p>
                            </div>

                            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-purple-500 text-white rounded-full text-sm font-semibold">WITHDRAWN</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Scholar has voluntarily withdrawn from the scholarship program. This may be due to
                                    personal reasons, relocation, or other circumstances.
                                </p>
                            </div>

                            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-orange-500 text-white rounded-full text-sm font-semibold">LOA</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Scholar is on Leave of Absence. They are temporarily pausing their scholarship
                                    but may resume it later. Disbursements are typically held during LOA period.
                                </p>
                            </div>

                            <div class="bg-red-50 bg-opacity-10 border-l-4 border-red-900 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-red-900 text-white rounded-full text-sm font-semibold">SUSPENDED</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Scholar's scholarship has been suspended due to non-compliance, rule violations, or
                                    performance issues. Administrative action may be required to resolve the suspension.
                                </p>
                            </div>

                            <div class="bg-slate-50 border-l-4 border-slate-400 p-4 rounded">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-slate-400 text-white rounded-full text-sm font-semibold">UNKNOWN</span>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Status is undefined or could not be determined. This is typically a system state and
                                    should be resolved by an administrator.
                                </p>
                            </div>
                        </div>

                        <Divider />

                        <h5 class="font-bold text-gray-900">How Unified Status Works</h5>

                        <div class="space-y-3 mt-3">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <i class="pi pi-check text-green-600 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <strong>Single Source of Truth:</strong> One status for each applicant
                                        eliminates confusion
                                        and ensures consistency
                                        across the entire system.
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <i class="pi pi-check text-green-600 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <strong>Automatic Updates:</strong> Status changes are automatically recorded
                                        with
                                        timestamps, creating an
                                        audit trail of all transitions.
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <i class="pi pi-check text-green-600 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <strong>Status History:</strong> Each applicant's complete status history is
                                        available in
                                        their profile for
                                        transparency and accountability.
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <i class="pi pi-check text-green-600 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <strong>Quick Status Changes:</strong> All status transitions are managed
                                        through the system
                                        interface, ensuring
                                        proper permissions and validation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4">
                            <p class="text-sm text-green-800">
                                <i class="pi pi-fw pi-info-circle mr-2"></i>
                                <strong>Pro Tip:</strong> Filter applicants by status in the various lists to focus on
                                specific
                                stages of the workflow.
                            </p>
                        </div>
                    </div>
                </AccordionTab>

                <!-- Common Tasks -->
                <AccordionTab>
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <i class="pi pi-fw pi-list text-red-600"></i>
                            <span class="font-semibold text-gray-800">Common Tasks</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <h4 class="font-bold text-lg text-gray-900">Quick Reference Guide</h4>

                        <div class="space-y-3">
                            <div class="bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-lg">
                                <h5 class="font-semibold text-red-900 mb-2">
                                    <i class="pi pi-fw pi-plus mr-2"></i>Add a New Applicant
                                </h5>
                                <ol class="text-sm text-red-800 space-y-1 list-decimal list-inside">
                                    <li>Go to Scholarship ✏️ Applicants</li>
                                    <li>Click "Add New" button</li>
                                    <li>Fill in all required information</li>
                                    <li>Upload required documents</li>
                                    <li>Click Save</li>
                                </ol>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-search mr-2"></i>Search for an Applicant
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Use the search bar at the top of any list to quickly find an applicant by name, ID,
                                    or other
                                    criteria.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-pencil mr-2"></i>Update Applicant Information
                                </h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    Click on any applicant to open their profile and then:
                                </p>
                                <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                                    <li>Click the "Edit" button</li>
                                    <li>Make necessary changes</li>
                                    <li>Save the changes</li>
                                </ol>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-check-circle mr-2"></i>Mark as Reviewed
                                </h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    To move an applicant from Applicants to Interviewed Applicants:
                                </p>
                                <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                                    <li>Open the applicant's profile</li>
                                    <li>Click "Mark as Reviewed" button</li>
                                    <li>Add your review notes and recommendation</li>
                                    <li>Save</li>
                                </ol>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-star mr-2"></i>Approve or Deny an Applicant
                                </h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    In the Interviewed Applicants list:
                                </p>
                                <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                                    <li>Click on the applicant</li>
                                    <li>Review the application and program manager notes</li>
                                    <li>Click "Approve" or "Deny" button</li>
                                    <li>Add final remarks if needed</li>
                                    <li>Confirm the action</li>
                                </ol>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-download mr-2"></i>Generate Reports
                                </h5>
                                <p class="text-sm text-gray-700">
                                    Navigate to Profiles to generate various reports on
                                    scholars list, summary and statistics.
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-900 mb-2">
                                    <i class="pi pi-fw pi-book mr-2"></i>Upload Documents
                                </h5>
                                <p class="text-sm text-gray-700 mb-2">
                                    In an applicant's profile:
                                </p>
                                <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                                    <li>Go to the "Documents" or "Attachments" section</li>
                                    <li>Click "Upload" or drag and drop files</li>
                                    <li>Select the document type</li>
                                    <li>Confirm upload</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </AccordionTab>
            </Accordion>

            <!-- Need More Help -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <i class="pi pi-question-circle text-blue-600 text-2xl flex-shrink-0 mt-1"></i>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Still Need Help?</h2>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold mt-0.5">📎</span>
                                <span>Review the sections above for detailed guidance on common tasks</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold mt-0.5">📎</span>
                                <span>Check the Approval History in scholar profiles to track status changes</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold mt-0.5">📎</span>
                                <span>Navigate to Profiles to generate reports on scholars list, summary, and
                                    statistics</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
:deep(.p-accordion) {
    width: 100%;
}

:deep(.p-accordion .p-accordion-header-link) {
    background-color: #f9fafb;
    border-color: #e5e7eb;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

:deep(.p-accordion .p-accordion-header-link:hover) {
    background-color: #f3f4f6;
}

:deep(.p-accordion .p-accordion-header.p-highlight > .p-accordion-header-link) {
    background-color: #eff6ff;
    border-color: #3b82f6;
    color: #1e40af;
}

:deep(.p-accordion .p-accordion-content) {
    background-color: #ffffff;
    padding: 1.5rem;
    border-color: #e5e7eb;
    border-top: none;
}
</style>
