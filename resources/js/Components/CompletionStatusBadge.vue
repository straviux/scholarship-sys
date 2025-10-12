<template>
    <div class="inline-flex items-center gap-2">
        <Badge :value="statusConfig.label" :severity="statusConfig.color" class="flex items-center gap-1">
            <i :class="statusConfig.icon" class="text-xs"></i>
            <span>{{ statusConfig.label }}</span>
        </Badge>

        <!-- Completion date -->
        <div v-if="record.completion_date" class="text-xs text-gray-500">
            {{ formatDate(record.completion_date) }}
        </div>

        <!-- Grade display -->
        <div v-if="record.completion && record.completion.final_grade" class="text-xs font-medium"
            :class="getGradeColorClass(record.completion.final_grade)">
            {{ record.completion.final_grade }}
        </div>

        <!-- Honors display -->
        <div v-if="record.completion && record.completion.honors" class="text-xs text-yellow-600 font-medium">
            {{ record.completion.honors }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from 'primevue/badge';

const props = defineProps({
    record: {
        type: Object,
        required: true
    }
});

const statusConfig = computed(() => {
    const statuses = {
        active: { label: 'Active', color: 'success', icon: 'pi-play-circle' },
        completed: { label: 'Completed', color: 'info', icon: 'pi-check-circle' },
        discontinued: { label: 'Discontinued', color: 'warning', icon: 'pi-pause-circle' },
        transferred: { label: 'Transferred', color: 'secondary', icon: 'pi-arrow-right-arrow-left' },
        suspended: { label: 'Suspended', color: 'danger', icon: 'pi-ban' }
    };

    return statuses[props.record.completion_status] ||
        { label: props.record.completion_status, color: 'secondary', icon: 'pi-circle' };
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
};

const getGradeColorClass = (grade) => {
    if (grade <= 1.25) return 'text-yellow-600'; // Summa Cum Laude
    if (grade <= 1.45) return 'text-yellow-500'; // Magna Cum Laude
    if (grade <= 1.75) return 'text-yellow-400'; // Cum Laude
    if (grade <= 2.0) return 'text-green-600';   // Very Good
    if (grade <= 3.0) return 'text-blue-600';    // Good
    return 'text-gray-600';                      // Satisfactory
};
</script>