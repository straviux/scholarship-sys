<template>

    <Head title="System Stats" />

    <AdminLayout>
        <AdminPageShell title="System Statistics Report"
            description="Prioritize cleanup, monitor scholarship flow, and jump directly to the section that needs action from the iOS-styled management dashboard."
            icon="chart-bar" eyebrow="Management Dashboard">
            <template #meta>
                <span>Generated {{ formattedGeneratedAt }}</span>
                <span>System Health: {{ systemHealthStatus }}</span>
                <span>{{ attentionSummaryText }}</span>
            </template>
            <template #actions>
                <AppButton @click="refreshReport" :loading="loading" icon="refresh" label="Refresh" severity="secondary"
                    raised />
                <AppButton @click="exportReport" icon="download" label="Export JSON" severity="success" raised />
            </template>

            <section class="ios-section">
                <div class="ios-section-label">Summary</div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="card in summaryCards" :key="card.label" class="ios-card px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{
                                    card.label }}</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ card.value }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ card.note }}</p>
                            </div>
                            <AppIcon :name="card.icon" class="text-2xl text-slate-500" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="sticky top-4 z-10 rounded-2xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur">
                <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Quick Navigation
                        </div>
                        <p class="mt-1 text-sm text-slate-600">Jump to the area you need to manage without scanning the
                            full
                            report.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="section in reportSections" :key="section.id" type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            @click="scrollToSection(section.id)">
                            <AppIcon :name="section.icon" :size="14" />
                            {{ section.label }}
                        </button>
                    </div>
                </div>
            </section>

        </AdminPageShell>

        <div class="mx-auto max-w-[1600px] p-4 short:p-3 space-y-5 short:space-y-3">

            <section id="attention-center" class="report-section">
                <Card class="border border-slate-200 shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="exclamation-triangle" class="text-amber-500" />
                            Attention Center
                        </div>
                    </template>
                    <template #content>
                        <div v-if="attentionItems.length" class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3">
                            <button v-for="item in attentionItems" :key="item.label" type="button"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-slate-300 hover:bg-slate-100"
                                @click="scrollToSection(item.sectionId)">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ item.label }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ item.description }}</p>
                                    </div>
                                    <span :class="item.valueClass"
                                        class="inline-flex min-w-14 items-center justify-center rounded-2xl border px-3 py-2 text-lg font-semibold">
                                        {{ item.count }}
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span :class="item.badgeClass"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em]">
                                        {{ item.badge }}
                                    </span>
                                    <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Open
                                        section</span>
                                </div>
                            </button>
                        </div>
                        <div v-else
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            No active management queues need attention right now.
                        </div>
                    </template>
                </Card>
            </section>

            <!-- Data Integrity Section -->
            <section id="data-integrity" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="exclamation-triangle" class="text-orange-500" />
                            Data Integrity Report
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                                <p class="text-sm text-red-600">Records without Programs</p>
                                <p class="text-xl font-bold text-red-700">{{
                                    report.data_integrity.records_without_programs
                                }}</p>
                            </div>
                            <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
                                <p class="text-sm text-orange-600">Records without Courses</p>
                                <p class="text-xl font-bold text-orange-700">{{
                                    report.data_integrity.records_without_courses }}</p>
                            </div>
                            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <p class="text-sm text-yellow-600">Records without Schools</p>
                                <p class="text-xl font-bold text-yellow-700">{{
                                    report.data_integrity.records_without_schools }}</p>
                            </div>
                            <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                                <p class="text-sm text-purple-600">Orphaned Requirements</p>
                                <p class="text-xl font-bold text-purple-700">{{
                                    report.data_integrity.orphaned_requirements
                                }}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex items-center gap-2 text-amber-800">
                                        <AppIcon name="exclamation-triangle" />
                                        <span class="font-semibold">Legacy Term Cleanup</span>
                                    </div>
                                    <p class="mt-1 text-sm text-amber-700">
                                        Profiles listed here still have legacy scholarship records that map to multiple
                                        pending or active terms inside the same enrollment slice.
                                    </p>
                                </div>
                                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-white/80 px-4 py-3 border border-amber-200">
                                        <p class="text-amber-600">Profiles</p>
                                        <p class="text-xl font-bold text-amber-900">{{
                                            report.data_integrity.legacy_term_cleanup.profiles_needing_review }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white/80 px-4 py-3 border border-amber-200">
                                        <p class="text-amber-600">Conflicting Enrollments</p>
                                        <p class="text-xl font-bold text-amber-900">{{
                                            report.data_integrity.legacy_term_cleanup.conflicting_group_count }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white/80 px-4 py-3 border border-amber-200">
                                        <p class="text-amber-600">Highest Open-Term Count</p>
                                        <p class="text-xl font-bold text-amber-900">{{
                                            report.data_integrity.legacy_term_cleanup.highest_open_term_count }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="report.data_integrity.legacy_term_cleanup.profiles.length"
                                class="mt-4 overflow-hidden rounded-2xl border border-amber-200 bg-white">
                                <div class="max-h-80 overflow-y-auto">
                                    <div v-for="profile in report.data_integrity.legacy_term_cleanup.profiles"
                                        :key="profile.profile_id"
                                        class="flex flex-col gap-2 border-b border-amber-100 px-4 py-3 last:border-b-0 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ profile.display_name }}</div>
                                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">
                                                {{ profile.unique_id || 'No ID' }}
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div
                                                class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.12em]">
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">
                                                    {{ profile.conflicting_group_count }} conflicting enrollment{{
                                                        profile.conflicting_group_count === 1 ? '' : 's' }}
                                                </span>
                                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-rose-800">
                                                    {{ profile.conflicting_open_term_total }} open terms
                                                </span>
                                            </div>
                                            <AppButton icon="eye" label="Open Profile" severity="info" text size="small"
                                                @click="openScholarshipProfile(profile.profile_id)" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else
                                class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                No legacy profiles currently need open-term cleanup.
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <!-- Application Status Charts -->
            <section id="status-trends" class="report-section grid grid-cols-1 lg:grid-cols-2 gap-4 short:gap-2">
                <Card>
                    <template #title>Application Status Distribution</template>
                    <template #content>
                        <Chart type="doughnut" :data="statusChartData" :options="chartOptions" class="w-full h-64" />
                    </template>
                </Card>

                <Card>
                    <template #title>Applications by Program</template>
                    <template #content>
                        <Chart type="bar" :data="programChartData" :options="barChartOptions" class="w-full h-64" />
                    </template>
                </Card>
            </section>

            <!-- Performance Metrics -->
            <section id="performance" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="chart-line" class="text-blue-500" />
                            Performance Metrics
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 short:gap-2">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-600">Average Processing Time</p>
                                <p class="text-2xl short:text-xl font-bold text-blue-700">{{
                                    report.performance_metrics.average_processing_time_days }} days</p>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <p class="text-sm text-green-600">Applications This Month</p>
                                <p class="text-2xl short:text-xl font-bold text-green-700">{{
                                    report.performance_metrics.applications_this_month }}</p>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <p class="text-sm text-purple-600">Applications Last Month</p>
                                <p class="text-2xl short:text-xl font-bold text-purple-700">{{
                                    report.performance_metrics.applications_last_month }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <!-- Geographic Distribution -->
            <section id="geography" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="map-marker" class="text-green-500" />
                            Geographic Distribution
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 short:gap-2">
                            <div>
                                <h4 class="font-semibold mb-3">Top Municipalities</h4>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <div v-for="item in report.geographic_distribution.by_municipality"
                                        :key="item.municipality"
                                        class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="text-sm">{{ item.municipality }}</span>
                                        <span class="font-semibold text-blue-600">{{ item.count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">Top Schools</h4>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <div v-for="item in report.geographic_distribution.by_school"
                                        :key="item.school_name"
                                        class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <div>
                                            <div class="text-sm font-medium">{{ item.school_name }}</div>
                                        </div>
                                        <span class="font-semibold text-green-600">{{ item.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <!-- Academic Analysis -->
            <section id="academics" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="book" class="text-purple-500" />
                            Academic Analysis
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 short:gap-2">
                            <div>
                                <h4 class="font-semibold mb-3">Top Courses</h4>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <div v-for="item in report.academic_analysis.by_course" :key="item.course_name"
                                        class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <div>
                                            <div class="text-sm font-medium">{{ item.course_name }}</div>
                                            <div class="text-xs text-gray-500">{{ item.approval_rate }}% approval rate
                                            </div>
                                        </div>
                                        <span class="font-semibold text-purple-600">{{ item.total_applications }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">By Year Level</h4>
                                <Chart type="pie" :data="yearLevelChartData" :options="chartOptions"
                                    class="w-full h-64" />
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <!-- System Health -->
            <section id="system-health" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="cog" class="text-gray-500" />
                            System Health
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Database Status</p>
                                <p class="text-lg font-bold"
                                    :class="report.system_health.database_status === 'Connected' ? 'text-green-600' : 'text-red-600'">
                                    {{ report.system_health.database_status }}
                                </p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Cache Status</p>
                                <p class="text-lg font-bold"
                                    :class="report.system_health.cache_status === 'Working' ? 'text-green-600' : 'text-red-600'">
                                    {{ report.system_health.cache_status }}
                                </p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Public Storage</p>
                                <p class="text-lg font-bold text-blue-600">{{
                                    report.system_health.storage_metrics.public_storage_used }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Private Storage</p>
                                <p class="text-lg font-bold text-blue-600">{{
                                    report.system_health.storage_metrics.private_storage_used }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <!-- User Activity -->
            <section id="user-activity" class="report-section">
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <AppIcon name="users" class="text-indigo-500" />
                            User Activity
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-indigo-50 rounded-lg">
                                <p class="text-sm text-indigo-600">Total Users</p>
                                <p class="text-2xl short:text-xl font-bold text-indigo-700">{{
                                    report.user_activity.total_users }}</p>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <p class="text-sm text-green-600">Active Today</p>
                                <p class="text-2xl short:text-xl font-bold text-green-700">{{
                                    report.user_activity.active_users_today }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-600">New This Month</p>
                                <p class="text-2xl short:text-xl font-bold text-blue-700">{{
                                    report.user_activity.new_users_this_month }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-orange-50 rounded-lg">
                                <p class="text-sm text-orange-600">Inactive Users</p>
                                <p class="text-2xl short:text-xl font-bold text-orange-700">{{
                                    report.user_activity.inactive_users }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AdminPageShell from '@/Components/admin/AdminPageShell.vue'




const props = defineProps({
    report: Object
})

const loading = ref(false)

const reportSections = [
    { id: 'attention-center', label: 'Attention Center', icon: 'exclamation-triangle' },
    { id: 'data-integrity', label: 'Data Integrity', icon: 'exclamation-triangle' },
    { id: 'status-trends', label: 'Status Trends', icon: 'chart-bar' },
    { id: 'performance', label: 'Performance', icon: 'chart-line' },
    { id: 'geography', label: 'Geography', icon: 'map-marker' },
    { id: 'academics', label: 'Academics', icon: 'book' },
    { id: 'system-health', label: 'System Health', icon: 'cog' },
    { id: 'user-activity', label: 'User Activity', icon: 'users' },
]

const formattedGeneratedAt = computed(() => {
    const generatedAt = props.report?.generated_at

    if (!generatedAt) {
        return 'Unavailable'
    }

    const parsedDate = new Date(String(generatedAt).replace(' ', 'T'))

    if (Number.isNaN(parsedDate.getTime())) {
        return generatedAt
    }

    return parsedDate.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    })
})

const systemHealthStatus = computed(() => {
    const dbStatus = props.report.system_health.database_status
    const cacheStatus = props.report.system_health.cache_status

    if (dbStatus === 'Connected' && cacheStatus === 'Working') {
        return 'Healthy'
    } else if (dbStatus === 'Connected' || cacheStatus === 'Working') {
        return 'Warning'
    } else {
        return 'Critical'
    }
})

const summaryCards = computed(() => [
    {
        label: 'Profiles Requiring Cleanup',
        value: props.report.data_integrity.legacy_term_cleanup.profiles_needing_review,
        note: 'Legacy open-term conflicts',
        icon: 'exclamation-triangle',
    },
    {
        label: 'Pending Applications',
        value: props.report.executive_summary.pending_applications,
        note: 'Awaiting workflow action',
        icon: 'clock',
    },
    {
        label: 'Approval Rate',
        value: `${props.report.executive_summary.approval_rate}%`,
        note: 'Approved or active applications',
        icon: 'check-circle',
    },
    {
        label: 'Total Profiles',
        value: props.report.executive_summary.total_scholarship_profiles,
        note: 'Profiles currently tracked',
        icon: 'users',
    },
])

const attentionItems = computed(() => {
    const integrity = props.report?.data_integrity ?? {}
    const cleanup = integrity.legacy_term_cleanup ?? {}

    return [
        {
            label: 'Legacy term cleanup',
            count: cleanup.profiles_needing_review ?? 0,
            description: 'Profiles with legacy pending or active terms that collide within the same enrollment slice.',
            sectionId: 'data-integrity',
            badge: 'Cleanup required',
            badgeClass: 'bg-amber-100 text-amber-800',
            valueClass: 'border-amber-200 bg-amber-100 text-amber-800',
        },
        {
            label: 'Records missing program',
            count: integrity.records_without_programs ?? 0,
            description: 'Records without a scholarship program can break downstream analysis and reporting.',
            sectionId: 'data-integrity',
            badge: 'Data integrity',
            badgeClass: 'bg-rose-100 text-rose-800',
            valueClass: 'border-rose-200 bg-rose-100 text-rose-800',
        },
        {
            label: 'Records missing course',
            count: integrity.records_without_courses ?? 0,
            description: 'Course gaps make academic reporting and school/course breakdowns less reliable.',
            sectionId: 'data-integrity',
            badge: 'Data integrity',
            badgeClass: 'bg-orange-100 text-orange-800',
            valueClass: 'border-orange-200 bg-orange-100 text-orange-800',
        },
        {
            label: 'Records missing school',
            count: integrity.records_without_schools ?? 0,
            description: 'School assignments are needed for geographic and institutional reporting.',
            sectionId: 'data-integrity',
            badge: 'Data integrity',
            badgeClass: 'bg-yellow-100 text-yellow-800',
            valueClass: 'border-yellow-200 bg-yellow-100 text-yellow-800',
        },
        {
            label: 'Orphaned requirements',
            count: integrity.orphaned_requirements ?? 0,
            description: 'Requirements with broken ownership links should be checked before cleanup or export.',
            sectionId: 'data-integrity',
            badge: 'Follow-up needed',
            badgeClass: 'bg-violet-100 text-violet-800',
            valueClass: 'border-violet-200 bg-violet-100 text-violet-800',
        },
        {
            label: 'Pending applications',
            count: props.report?.executive_summary?.pending_applications ?? 0,
            description: 'Applications that still need review or progression in the approval workflow.',
            sectionId: 'status-trends',
            badge: 'Workflow queue',
            badgeClass: 'bg-sky-100 text-sky-800',
            valueClass: 'border-sky-200 bg-sky-100 text-sky-800',
        },
    ]
        .filter(item => item.count > 0)
        .sort((left, right) => right.count - left.count)
})

const attentionSummaryText = computed(() => {
    if (!attentionItems.value.length) {
        return 'No active cleanup queues'
    }

    const primaryItem = attentionItems.value[0]

    return `${attentionItems.value.length} active queues • ${primaryItem.label}: ${primaryItem.count}`
})

const statusChartData = computed(() => {
    const statusData = props.report.application_status.by_status
    return {
        labels: statusData.map(item => item.status_name),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: ['#fbbf24', '#10b981', '#ef4444', '#6b7280'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    }
})

const programChartData = computed(() => {
    const programData = props.report.application_status.by_program.slice(0, 10)
    return {
        labels: programData.map(item => item.program_name),
        datasets: [{
            label: 'Applications',
            data: programData.map(item => item.count),
            backgroundColor: '#3b82f6',
            borderColor: '#1d4ed8',
            borderWidth: 1
        }]
    }
})

const yearLevelChartData = computed(() => {
    const yearData = props.report.academic_analysis.by_year_level
    return {
        labels: yearData.map(item => item.year_level),
        datasets: [{
            data: yearData.map(item => item.count),
            backgroundColor: ['#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    }
})

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
}

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    }
}


const openScholarshipProfile = (profileId) => {
    if (!profileId) {
        return;
    }

    router.visit(route('scholarship.profile.show', profileId))
}

const scrollToSection = (sectionId) => {
    if (typeof document === 'undefined') {
        return
    }

    const section = document.getElementById(sectionId)

    if (!section) {
        return
    }

    section.scrollIntoView({ behavior: 'smooth', block: 'start' })
}


const refreshReport = () => {
    loading.value = true
    router.reload({
        onFinish: () => {
            loading.value = false
        }
    })
}

const exportReport = () => {
    window.open('/admin/system-report/export-json', '_blank')
}
</script>

<style scoped>
.grid {
    display: grid;
}

.report-section {
    scroll-margin-top: 7rem;
}
</style>
