<template>
    <div class="ros-report">
        <header class="ros-report__header">
            <div>
                <div class="ros-report__eyebrow">Scholarship System</div>
                <h1 class="ros-report__title">{{ title }}</h1>
                <p class="ros-report__subtitle">{{ scopeLabel }}</p>
            </div>
            <div class="ros-report__meta">
                <div><strong>Generated:</strong> {{ generatedAt }}</div>
            </div>
        </header>

        <section class="ros-report__summary">
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Batches</div>
                <div class="ros-report__summary-value">{{ summary.totalBatches }}</div>
            </div>
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Scholars</div>
                <div class="ros-report__summary-value">{{ summary.totalScholars }}</div>
            </div>
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Completed</div>
                <div class="ros-report__summary-value">{{ summary.completed }}</div>
            </div>
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Ongoing</div>
                <div class="ros-report__summary-value">{{ summary.ongoing }}</div>
            </div>
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Pending</div>
                <div class="ros-report__summary-value">{{ summary.pending }}</div>
            </div>
            <div class="ros-report__summary-card">
                <div class="ros-report__summary-label">Suspended</div>
                <div class="ros-report__summary-value">{{ summary.suspended }}</div>
            </div>
        </section>

        <section v-for="batch in batches" :key="batch.id" class="ros-report__batch">
            <div class="ros-report__batch-head">
                <div>
                    <h2 class="ros-report__batch-title">{{ batch.batch_name }}</h2>
                    <div class="ros-report__batch-meta">Course: {{ batch.course_name }}</div>
                    <div class="ros-report__batch-meta">
                        Exam: {{ batch.exam_date_range }}
                        <span v-if="batch.result_date_label !== '-'"> | Result: {{ batch.result_date_label }}</span>
                    </div>
                    <div class="ros-report__batch-meta">Created By: {{ batch.created_by }}</div>
                </div>
                <div class="ros-report__badge">{{ batch.total_scholars }} scholar(s)</div>
            </div>

            <div v-if="batch.description" class="ros-report__description">{{ batch.description }}</div>

            <table class="ros-report__table">
                <thead>
                    <tr>
                        <th style="width: 44px;">#</th>
                        <th style="width: 24%;">Scholar Name</th>
                        <th style="width: 10%;">Years ROS</th>
                        <th style="width: 14%;">Service Start</th>
                        <th style="width: 14%;">Service End</th>
                        <th style="width: 14%;">Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody v-if="batch.scholars.length > 0">
                    <tr v-for="scholar in batch.scholars" :key="`${batch.id}-${scholar.index}`">
                        <td>{{ scholar.index }}</td>
                        <td>{{ scholar.scholar_name }}</td>
                        <td>{{ scholar.years_of_service }}</td>
                        <td>{{ scholar.service_start_date_label }}</td>
                        <td>{{ scholar.service_end_date_label }}</td>
                        <td class="ros-report__status">{{ scholar.completion_status_label }}</td>
                        <td>{{ scholar.remarks || '-' }}</td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="7" class="ros-report__empty">No scholars in this batch.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Return of Service Report',
    },
    scopeLabel: {
        type: String,
        default: '',
    },
    generatedAt: {
        type: String,
        default: '',
    },
    summary: {
        type: Object,
        default: () => ({
            totalBatches: 0,
            totalScholars: 0,
            pending: 0,
            ongoing: 0,
            suspended: 0,
            completed: 0,
        }),
    },
    batches: {
        type: Array,
        default: () => [],
    },
});
</script>
