<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Chart from 'primevue/chart';
import Card from 'primevue/card';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// Register the plugin globally
import { Chart as ChartJS } from 'chart.js';
ChartJS.register(ChartDataLabels);

const props = defineProps({
    dailyStats: Object,
    monthlyStats: Object,
    programDistribution: Object,
    statusDistribution: Object,
    recentStats: Object,
    yearlyTrends: Object,
    geographicDistribution: Object,
    academicAnalysis: Object,
    dashboard_links: Array
});

// Chart options
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
});

const pieChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                generateLabels: function (chart) {
                    const data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, i) => {
                            const value = data.datasets[0].data[i];
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);

                            return {
                                text: `${label}: ${value} (${percentage}%)`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].backgroundColor[i],
                                lineWidth: 0,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                    return [];
                }
            }
        },
        datalabels: {
            display: true,
            color: 'white',
            font: {
                weight: 'bold',
                size: 14
            },
            formatter: (value, context) => {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${value}\n(${percentage}%)`;
            },
            textAlign: 'center'
        }
    }
});

const doughnutChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                generateLabels: function (chart) {
                    const data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, i) => {
                            const value = data.datasets[0].data[i];
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);

                            return {
                                text: `${label}: ${value} (${percentage}%)`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].backgroundColor[i],
                                lineWidth: 0,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                    return [];
                }
            }
        },
        datalabels: {
            display: true,
            color: 'white',
            font: {
                weight: 'bold',
                size: 12
            },
            formatter: (value, context) => {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${value}\n(${percentage}%)`;
            },
            textAlign: 'center'
        }
    }
});

const barChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
});

const monthlyChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'x',
    plugins: {
        legend: {
            position: 'top',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            stacked: false
        },
        x: {
            stacked: false
        }
    }
});

// Computed properties for formatted stats
const formattedStats = computed(() => {
    return {
        todayVsYesterday: {
            value: props.recentStats.today - props.recentStats.yesterday,
            percentage: props.recentStats.yesterday > 0
                ? ((props.recentStats.today - props.recentStats.yesterday) / props.recentStats.yesterday * 100).toFixed(1)
                : props.recentStats.today > 0 ? 100 : 0
        }
    };
});

onMounted(() => {
    // Any initialization logic
});
</script>

<template>

    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-end gap-2">
                <h1 class="text-2xl text-white">Dashboard</h1>
                <div class="text-sm text-white">
                    {{ new Date().toLocaleDateString('en-PH', {
                        timeZone: 'Asia/Manila',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }) }}
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Today's Applications -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Today</p>
                                <p class="text-2xl font-bold text-blue-600">{{ recentStats.today }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span
                                        :class="formattedStats.todayVsYesterday.value >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formattedStats.todayVsYesterday.value >= 0 ? '+' : '' }}{{
                                            formattedStats.todayVsYesterday.value }}
                                    </span>
                                    from yesterday
                                </p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="pi pi-calendar text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- This Week -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">This Week</p>
                                <p class="text-2xl font-bold text-green-600">{{ recentStats.thisWeek }}</p>
                                <p class="text-xs text-gray-500 mt-1">Applications filed</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="pi pi-chart-line text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- This Month -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">This Month</p>
                                <p class="text-2xl font-bold text-purple-600">{{ recentStats.thisMonth }}</p>
                                <p class="text-xs text-gray-500 mt-1">Applications filed</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="pi pi-calendar-plus text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Total Applications -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Applications</p>
                                <p class="text-2xl font-bold text-orange-600">{{ recentStats.total }}</p>
                                <p class="text-xs text-gray-500 mt-1">All time</p>
                            </div>
                            <div class="bg-orange-100 p-3 rounded-full">
                                <i class="pi pi-users text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Status Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pending Applications -->
                <Card class="shadow-sm border-l-4 border-yellow-400">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Pending</p>
                                <p class="text-xl font-bold text-yellow-600">{{ recentStats.pendingApplications }}</p>
                            </div>
                            <i class="pi pi-clock text-yellow-600 text-2xl"></i>
                        </div>
                    </template>
                </Card>

                <!-- Active Applications -->
                <Card class="shadow-sm border-l-4 border-green-400">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Active</p>
                                <p class="text-xl font-bold text-green-600">{{ recentStats.approvedApplications }}</p>
                            </div>
                            <i class="pi pi-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </template>
                </Card>

                <!-- Completed Applications -->
                <Card class="shadow-sm border-l-4 border-blue-400">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Completed</p>
                                <p class="text-xl font-bold text-blue-600">{{ recentStats.completedApplications }}</p>
                            </div>
                            <i class="pi pi-flag text-blue-600 text-2xl"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts Row 1 - Time-based Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Daily Statistics -->
                <Card class="shadow-sm">
                    <template #header>
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Daily Applications (Current Month)</h3>
                            <i class="pi pi-chart-line text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="line" :data="dailyStats" :options="chartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>

                <!-- Monthly Statistics -->
                <Card class="shadow-sm">
                    <template #header>
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Monthly Trends (Current Year)</h3>
                            <i class="pi pi-chart-bar text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="bar" :data="monthlyStats" :options="monthlyChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>

                <!-- Yearly Trends -->
                <Card class="shadow-sm">
                    <template #header>
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Yearly Trends</h3>
                            <i class="pi pi-chart-line text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="line" :data="yearlyTrends" :options="chartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Program Distribution -->
                <Card class="shadow-sm">
                    <template #header>
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Program Distribution</h3>
                            <i class="pi pi-chart-pie text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="pie" :data="programDistribution" :options="pieChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>

                <!-- Status Distribution -->
                <Card class="shadow-sm">
                    <template #header>
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Application Status</h3>
                            <i class="pi pi-chart-pie text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="doughnut" :data="statusDistribution" :options="doughnutChartOptions"
                                class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

        </div>

        <!-- Geographic Distribution Section -->
        <div class="grid grid-cols-1 gap-6 mt-6">
            <Card class="shadow-sm">
                <template #header>
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Geographic Distribution</h3>
                        <i class="pi pi-map-marker text-gray-400"></i>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">Top Municipalities</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="item in geographicDistribution.by_municipality" :key="item.municipality"
                                    class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm">{{ item.municipality }}</span>
                                    <div class="text-right">
                                        <span class="font-semibold text-blue-600">{{ item.count }}</span>
                                        <div class="text-xs text-gray-500">
                                            {{((item.count / geographicDistribution.by_municipality.reduce((sum, m) =>
                                                sum + m.count, 0)) * 100).toFixed(1)}}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">Top Schools</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="item in geographicDistribution.by_school" :key="item.school_name"
                                    class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <div>
                                        <div class="text-sm font-medium">{{ item.school_name }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-semibold text-green-600">{{ item.count }}</span>
                                        <div class="text-xs text-gray-500">
                                            {{((item.count / geographicDistribution.by_school.reduce((sum, s) => sum +
                                                s.count, 0)) * 100).toFixed(1)}}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Academic Analysis Section -->
        <div class="grid grid-cols-1 gap-6 mt-6">
            <Card class="shadow-sm">
                <template #header>
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Academic Analysis</h3>
                        <i class="pi pi-book text-gray-400"></i>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">Top Courses</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="item in academicAnalysis.by_course" :key="item.course_name"
                                    class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <div>
                                        <div class="text-sm font-medium">{{ item.course_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.approval_rate }}% approval rate</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-semibold text-purple-600">{{ item.total_applications }}</span>
                                        <div class="text-xs text-gray-500">
                                            {{((item.total_applications / academicAnalysis.by_course.reduce((sum, c) =>
                                                sum + c.total_applications, 0)) * 100).toFixed(1)}}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">By Year Level</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <div v-for="item in academicAnalysis.by_year_level" :key="item.year_level"
                                    class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <div>
                                        <div class="text-sm font-medium">{{ item.year_level }}</div>
                                        <div class="text-xs text-gray-500">{{ item.approval_rate }}% approval rate</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-semibold text-blue-600">{{ item.count }}</span>
                                        <div class="text-xs text-gray-500">
                                            {{((item.count / academicAnalysis.by_year_level.reduce((sum, y) => sum +
                                                y.count, 0)) * 100).toFixed(1)}}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom styles for the dashboard */
.card {
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>