<template>
    <IosModal :visible="visible" title="Approval Request Details" width="calc(100vw - 2rem)" max-width="900px"
        body-style="padding: 16px;" @update:visible="val => emit('update:visible', val)">
        <div v-if="recommendationList" class="pb-4">
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                <div>
                    <div class="text-lg font-bold text-slate-800">{{ recommendationList.list_number }}</div>
                    <div class="text-xs text-slate-500 mt-1">{{ stripHtml(recommendationList.report_title) }}</div>
                </div>
                <div class="text-right">
                    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', badgeClass]">
                        {{ approvalLabel }}
                    </span>
                    <div class="text-2xs text-slate-500 mt-1">{{ approvalMeta }}</div>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Request Date</div>
                    <div class="mt-1 font-semibold text-slate-800">{{ formatDate(recommendationList.request_date) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Applicants</div>
                    <div class="mt-1 font-semibold text-slate-800">{{ recommendationList.record_count }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Total Amount</div>
                    <div class="mt-1 font-semibold text-emerald-700">{{ formatCurrency(totalAmount) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Prepared By</div>
                    <div class="mt-1 font-semibold text-slate-800">{{ recommendationList.prepared_by || 'N/A' }}</div>
                    <div class="text-xs text-slate-500">{{ recommendationList.prepared_by_position || 'Position not set' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Approved By</div>
                    <div class="mt-1 font-semibold text-slate-800">{{ recommendationList.approved_by || 'N/A' }}</div>
                    <div class="text-xs text-slate-500">{{ recommendationList.approved_by_position || 'Position not set' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Budget Allocation</div>
                    <div v-if="recommendationList.budget_allocation" class="mt-1">
                        <div class="font-semibold text-slate-800">{{ budgetTitle }}</div>
                        <div v-if="budgetDescription" class="text-xs text-slate-500">{{ budgetDescription }}</div>
                    </div>
                    <div v-else class="mt-1 text-xs text-slate-500">No saved budget allocation</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">JPM Highlight</div>
                    <div class="mt-1 text-xs" :class="recommendationList.highlight_jpm_members ? 'font-semibold text-emerald-700' : 'text-slate-500'">
                        {{ recommendationList.highlight_jpm_members ? 'Enabled for printed applicant names' : 'Disabled' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Created</div>
                    <div class="mt-1 font-semibold text-slate-800">{{ formatDateTime(recommendationList.created_at) }}</div>
                    <div class="text-xs text-slate-500">{{ recommendationList.creator?.name || 'Unknown user' }}</div>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-sm font-semibold text-slate-800">Saved Applicants Snapshot</div>
                    <div class="text-xs text-slate-500">The printed report uses this stored selection.</div>
                </div>
                <div class="max-h-[22rem] overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Program</th>
                                <th class="px-4 py-3 text-left">School</th>
                                <th class="px-4 py-3 text-left">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in recommendationList.records" :key="`rl-detail-${recommendationList.id}-${record.id}`">
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <span class="inline-block"
                                        :class="recommendationList.highlight_jpm_members && recommendationRecordHasJpm(record) ? 'rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-900' : ''">
                                        {{ formatApplicantName(record) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ record.program?.shortname || 'N/A' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ record.school?.shortname || record.school?.name || 'N/A' }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-700">{{ formatCurrency(record.grant_amount) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200 bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800" colspan="3">Total</td>
                                <td class="px-4 py-3 font-bold text-emerald-700">{{ formatCurrency(totalAmount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { computed } from 'vue';
import moment from 'moment';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    recommendationList: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:visible']);

const totalAmount = computed(() => {
    return (props.recommendationList?.records || [])
        .reduce((sum, record) => sum + (Number(record?.grant_amount) || 0), 0);
});

const approvalLabel = computed(() => (props.recommendationList?.is_approved ? 'Approved' : 'Pending Approval'));

const badgeClass = computed(() => (props.recommendationList?.is_approved
    ? 'bg-emerald-50 text-emerald-700'
    : 'bg-amber-50 text-amber-700'));

const approvalMeta = computed(() => {
    if (!props.recommendationList?.is_approved) {
        return 'Waiting for a final approval action on this saved list.';
    }

    const approverName = props.recommendationList?.approver?.name || 'Unknown user';
    return `Approved by ${approverName} on ${formatDateTime(props.recommendationList?.approved_at)}.`;
});

const budgetTitle = computed(() => {
    const budgetAllocation = props.recommendationList?.budget_allocation;
    return budgetAllocation?.particular_name?.trim() || budgetAllocation?.description?.trim() || 'Unnamed Allocation';
});

const budgetDescription = computed(() => {
    const budgetAllocation = props.recommendationList?.budget_allocation;
    if (!budgetAllocation) {
        return '';
    }

    const description = budgetAllocation.description?.trim();
    return description && description !== budgetTitle.value ? description : '';
});

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
};

const formatDate = (value) => {
    return value ? moment(value).format('MMM DD, YYYY') : 'N/A';
};

const formatDateTime = (value) => {
    return value ? moment(value).format('MMM DD, YYYY h:mm A') : 'N/A';
};

const stripHtml = (value) => {
    if (!value) {
        return '';
    }
    const container = document.createElement('div');
    container.innerHTML = value;
    return (container.textContent || '').trim();
};

const formatApplicantName = (record) => {
    const lastName = record?.profile?.last_name || 'N/A';
    const firstName = record?.profile?.first_name || '';
    const middleName = record?.profile?.middle_name?.trim();
    const middleInitial = middleName ? `${middleName.charAt(0).toUpperCase()}.` : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
};

const recommendationRecordHasJpm = (record) => {
    return Boolean(
        record?.profile?.is_jpm_member
        || record?.profile?.is_father_jpm
        || record?.profile?.is_mother_jpm
        || record?.profile?.is_guardian_jpm,
    );
};
</script>
