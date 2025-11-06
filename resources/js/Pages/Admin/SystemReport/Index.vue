<template>

    <Head title="System Stats" />

    <AdminLayout>
        <template #header>
            System Stats
        </template>

        <div class="p-6 space-y-6">
            <!-- Header Panel -->
            <Panel class="mb-6">
                <template #header>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-bar text-xl"></i>
                        <span class="font-semibold text-lg">System Statistics Report</span>
                    </div>
                </template>

                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        View comprehensive system statistics and performance metrics
                    </div>
                    <div class="flex gap-3">
                        <Button @click="refreshReport" :loading="loading" icon="pi pi-refresh" label="Refresh"
                            severity="secondary" raised />
                        <Button @click="exportReport" icon="pi pi-download" label="Export JSON" severity="success"
                            raised />
                    </div>
                </div>
            </Panel>

            <!-- Report Info -->
            <div class="text-sm text-gray-600">
                Generated on {{ report.generated_at }}
            </div>

            <!-- Executive Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border border-gray-200">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Records</p>
                                <p class="text-2xl font-bold text-blue-600">{{
                                    report.executive_summary.total_scholarship_records }}</p>
                            </div>
                            <i class="pi pi-file-text text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border border-gray-200">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending Applications</p>
                                <p class="text-2xl font-bold text-yellow-600">{{
                                    report.executive_summary.pending_applications }}</p>
                            </div>
                            <i class="pi pi-clock text-3xl text-yellow-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border border-gray-200">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Approval Rate</p>
                                <p class="text-2xl font-bold text-green-600">{{ report.executive_summary.approval_rate
                                }}%</p>
                            </div>
                            <i class="pi pi-check-circle text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border border-gray-200">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">System Health</p>
                                <p class="text-2xl font-bold" :class="systemHealthColor">{{ systemHealthStatus }}</p>
                            </div>
                            <i class="pi pi-shield text-3xl" :class="systemHealthColor"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Data Integrity Section -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-orange-500"></i>
                        Data Integrity Report
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                            <p class="text-sm text-red-600">Records without Programs</p>
                            <p class="text-xl font-bold text-red-700">{{ report.data_integrity.records_without_programs
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
                            <p class="text-xl font-bold text-purple-700">{{ report.data_integrity.orphaned_requirements
                            }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Application Status Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
            </div>

            <!-- Performance Metrics -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-line text-blue-500"></i>
                        Performance Metrics
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-600">Average Processing Time</p>
                            <p class="text-2xl font-bold text-blue-700">{{
                                report.performance_metrics.average_processing_time_days }} days</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-sm text-green-600">Applications This Month</p>
                            <p class="text-2xl font-bold text-green-700">{{
                                report.performance_metrics.applications_this_month }}</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm text-purple-600">Applications Last Month</p>
                            <p class="text-2xl font-bold text-purple-700">{{
                                report.performance_metrics.applications_last_month }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Geographic Distribution -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-map-marker text-green-500"></i>
                        Geographic Distribution
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                                <div v-for="item in report.geographic_distribution.by_school" :key="item.school_name"
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

            <!-- Academic Analysis -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-book text-purple-500"></i>
                        Academic Analysis
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">Top Courses</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="item in report.academic_analysis.by_course" :key="item.course_name"
                                    class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <div>
                                        <div class="text-sm font-medium">{{ item.course_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.approval_rate }}% approval rate</div>
                                    </div>
                                    <span class="font-semibold text-purple-600">{{ item.total_applications }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">By Year Level</h4>
                            <Chart type="pie" :data="yearLevelChartData" :options="chartOptions" class="w-full h-64" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- System Health -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-cog text-gray-500"></i>
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

            <!-- User Activity -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-users text-indigo-500"></i>
                        User Activity
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-indigo-50 rounded-lg">
                            <p class="text-sm text-indigo-600">Total Users</p>
                            <p class="text-2xl font-bold text-indigo-700">{{ report.user_activity.total_users }}</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-sm text-green-600">Active Today</p>
                            <p class="text-2xl font-bold text-green-700">{{ report.user_activity.active_users_today }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-600">New This Month</p>
                            <p class="text-2xl font-bold text-blue-700">{{ report.user_activity.new_users_this_month }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <p class="text-sm text-orange-600">Inactive Users</p>
                            <p class="text-2xl font-bold text-orange-700">{{ report.user_activity.inactive_users }}</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Chart from 'primevue/chart'

const props = defineProps({
    report: Object
})

const loading = ref(false)

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

const systemHealthColor = computed(() => {
    const status = systemHealthStatus.value
    switch (status) {
        case 'Healthy': return 'text-green-600'
        case 'Warning': return 'text-yellow-600'
        case 'Critical': return 'text-red-600'
        default: return 'text-gray-600'
    }
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

onMounted(() => {
    // Any initialization logic
})
</script>

<style scoped>
.grid {
    display: grid;
}
</style>