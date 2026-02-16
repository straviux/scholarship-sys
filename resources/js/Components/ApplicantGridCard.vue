<script setup>
import { computed } from 'vue';

const props = defineProps({
    applicant: {
        type: Object,
        required: true
    },
    showJpmBadge: {
        type: Boolean,
        default: false
    },
    jpmDetails: {
        type: String,
        default: ''
    },
    actions: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['action']);

// Helper functions
const getApplicantInitials = (applicant) => {
    if (!applicant) return '';
    const firstInitial = applicant.first_name?.charAt(0) || '';
    const lastInitial = applicant.last_name?.charAt(0) || '';
    return `${firstInitial}${lastInitial}`.toUpperCase();
};

const getPrioritySeverity = (level) => {
    const severityMap = {
        'urgent': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'success',
        'normal': 'secondary'
    };
    return severityMap[level] || 'secondary';
};

const formatPriorityName = (level) => {
    if (!level) return '';
    return level.charAt(0).toUpperCase() + level.slice(1);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const handleAction = (action) => {
    emit('action', { action: action.name, applicant: props.applicant });
};

// Check if action should be displayed based on condition
const shouldShowAction = (action) => {
    if (!action) return false;
    if (!action.condition) return true;
    if (typeof action.condition !== 'function') return true;
    try {
        return action.condition();
    } catch (error) {
        console.warn('Error evaluating action condition:', error);
        return false;
    }
};
</script>

<template>
    <Card class="hover:shadow-lg transition-shadow duration-200 h-full">
        <!-- Header with Avatar -->
        <template #header>
            <slot name="header" :applicant="applicant">
                <div class="relative">
                    <!-- Avatar/Image -->
                    <div class="flex justify-center pt-6 pb-4 bg-gradient-to-br from-indigo-50 to-blue-50">
                        <img v-if="applicant.gender == 'M'" src="/images/male-avatar.png" alt="avatar"
                            class="rounded-full w-24 h-24 border-4 border-white shadow-md" />
                        <img v-else-if="applicant.gender == 'F'" src="/images/female-avatar.png" alt="avatar"
                            class="rounded-full w-24 h-24 border-4 border-white shadow-md" />
                        <!-- <Avatar v-else :label="getApplicantInitials(applicant)" class="w-32 h-32" shape="circle" /> -->
                        <div v-else>
                            <div
                                class="w-24 h-24 rounded-full bg-gray-400 flex items-center justify-center text-xl text-white font-semibold">
                                {{ getApplicantInitials(applicant) }}
                            </div>
                        </div>
                    </div>

                    <!-- JPM Badge -->
                    <Tag v-if="showJpmBadge" severity="success" class="absolute top-2 right-2"
                        v-tooltip.left="jpmDetails">
                        <i class="pi pi-star-fill mr-1"></i> JPM
                    </Tag>

                    <!-- Priority Badge -->
                    <Tag v-if="applicant.priority_level && applicant.priority_level !== 'normal'"
                        :severity="getPrioritySeverity(applicant.priority_level)" class="absolute top-2 left-2">
                        <i class="pi pi-flag-fill mr-1"></i>
                        {{ formatPriorityName(applicant.priority_level) }}
                    </Tag>
                </div>
            </slot>
        </template>

        <!-- Title with Name -->
        <template #title>
            <slot name="title" :applicant="applicant">
                <div class="text-center">
                    <div class="font-bold text-lg text-gray-800">
                        {{ applicant.last_name }}, {{ applicant.first_name }}
                        {{ applicant.middle_name }} {{ applicant.extension_name }}
                    </div>
                </div>
            </slot>
        </template>

        <!-- Content with Details -->
        <template #content>
            <slot name="content">
                <div class="space-y-3 text-sm">
                    <!-- Queue Numbers -->
                    <div class="flex justify-center gap-1">
                        <div class="px-1">
                            <div class="text-xs font-semibold text-gray-500">
                                Prog. <span class="font-bold text-gray-600">#{{
                                    applicant.sequence_number || '-'
                                    }}</span>
                            </div>
                        </div>
                        <div class="px-1">
                            <div class="text-xs font-semibold text-gray-500">
                                Cour. <span class="font-bold text-gray-600">#{{
                                    applicant.sequence_number_by_course || '-'
                                    }}</span>
                            </div>
                        </div>
                        <div class="px-1">
                            <div class="text-xs font-semibold text-gray-500">
                                Sch. <span class="font-bold text-gray-600">#{{
                                    applicant.sequence_number_by_school_course || '-'
                                    }}</span>

                            </div>
                        </div>
                        <div class="px-1">
                            <div class="text-xs font-semibold text-gray-500">
                                Date <span class="font-bold text-gray-600">#{{
                                    applicant.daily_sequence_number ||
                                    '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Information -->
                    <slot name="info">
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <i class="pi pi-phone text-gray-400 mt-1"></i>
                                <span class="text-gray-600">{{ applicant.contact_no || 'N/A' }}</span>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="pi pi-map-marker text-gray-400 mt-1"></i>
                                <span class="text-gray-600">{{ applicant.municipality || 'N/A' }}</span>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="pi pi-building text-gray-400 mt-1"></i>
                                <span class="text-gray-600">
                                    {{ applicant.scholarship_grant?.[0]?.school?.shortname || 'N/A' }}
                                </span>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="pi pi-book text-gray-400 mt-1"></i>
                                <span class="text-gray-600">
                                    {{ applicant.scholarship_grant?.[0]?.course?.name || 'N/A' }}
                                </span>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="pi pi-calendar text-gray-400 mt-1"></i>
                                <span class="text-gray-600">
                                    {{ formatDate(applicant.scholarship_grant?.[0]?.date_filed || applicant.date_filed)
                                    }}
                                </span>
                            </div>

                            <div class="flex items-start gap-2">
                                <i class="pi pi-comment text-gray-400 mt-1"></i>
                                <span class="text-gray-600 text-xs italic">{{ applicant.remarks || '-' }}</span>
                            </div>
                        </div>
                    </slot>
                </div>
            </slot>
        </template>

        <!-- Footer with Actions -->
        <template #footer>
            <slot name="footer" :applicant="applicant">
                <div class="flex gap-2 justify-center flex-wrap" v-if="actions && actions.length > 0">
                    <template v-for="action in actions" :key="action?.name || Math.random()">
                        <Button v-if="shouldShowAction(action)" :icon="action?.icon" :label="action?.label"
                            :severity="action?.severity || 'secondary'" :size="action?.size || 'small'"
                            :outlined="action?.outlined" v-tooltip.top="action?.tooltip"
                            @click="handleAction(action)" />
                    </template>
                </div>
            </slot>
        </template>
    </Card>
</template>

<style scoped>
/* No longer needed as Card handles layout */
</style>
