<template>
    <div class="inline-flex items-center gap-2">
        <Badge :value="statusConfig.label" :severity="statusConfig.color" class="flex items-center gap-1">
            <i :class="statusConfig.icon" class="text-xs"></i>
            <span>{{ statusConfig.label }}</span>
        </Badge>

        <!-- Application cycle indicator -->
        <div v-if="record.application_cycle > 1" class="inline-flex items-center gap-1">
            <i class="pi pi-refresh text-blue-500 text-xs"
                v-tooltip="'Application Cycle ' + record.application_cycle"></i>
            <span class="text-xs text-gray-500">
                Cycle {{ record.application_cycle }}
            </span>
        </div>

        <!-- Resubmission indicator -->
        <div v-if="record.approval_status === 'resubmitted'" class="flex items-center gap-1">
            <i class="pi pi-history text-blue-500 text-xs" v-tooltip="'Resubmission #' + record.resubmission_count"></i>
        </div>

        <!-- Auto-approved indicator -->
        <div v-if="record.auto_approved" class="inline-flex items-center">
            <i class="pi pi-cog text-green-500 text-xs" v-tooltip="'Auto-approved by system'"></i>
        </div>

        <!-- Next application eligibility -->
        <div v-if="record.completion_status === 'completed' && canApplyNext" class="inline-flex items-center">
            <Button icon="pi pi-arrow-up" size="small" text rounded severity="success"
                v-tooltip="'Eligible for higher degree application'" @click="$emit('apply-next')" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from 'primevue/badge';
import Button from 'primevue/button';

const props = defineProps({
    record: {
        type: Object,
        required: true
    },
    canApplyNext: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['apply-next']);

const statusConfig = computed(() => {
    const statuses = {
        pending: { label: 'Pending', color: 'warning', icon: 'pi-clock' },
        approved: { label: 'Approved', color: 'success', icon: 'pi-check-circle' },
        declined: { label: 'Declined', color: 'danger', icon: 'pi-times-circle' },
        conditional: { label: 'Conditional', color: 'info', icon: 'pi-info-circle' },
        resubmitted: { label: 'Resubmitted', color: 'secondary', icon: 'pi-refresh' },
        withdrawn: { label: 'Withdrawn', color: 'secondary', icon: 'pi-minus-circle' }
    };

    return statuses[props.record.approval_status] ||
        { label: props.record.approval_status, color: 'secondary', icon: 'pi-circle' };
});
</script>