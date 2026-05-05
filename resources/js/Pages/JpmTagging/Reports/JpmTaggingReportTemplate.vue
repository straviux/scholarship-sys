<script setup>
import { computed } from 'vue';
import { stripHtml } from '@/utils/sanitize';

const props = defineProps({
    profiles: {
        type: Array,
        default: () => [],
    },
    activeFilters: {
        type: Array,
        default: () => [],
    },
    generatedAt: {
        type: String,
        default: '',
    },
    options: {
        type: Object,
        default: () => ({}),
    },
});

const sortedProfiles = computed(() => [...props.profiles].sort((left, right) =>
    fullName(left).localeCompare(fullName(right), undefined, {
        sensitivity: 'base',
        numeric: true,
    })
));

const totals = computed(() => {
    let tagged = 0;
    let notJpm = 0;
    let untagged = 0;

    for (const profile of props.profiles) {
        const status = jpmStatus(profile);

        if (status.label === 'JPM Member') {
            tagged += 1;
        } else if (status.label === 'Not JPM') {
            notJpm += 1;
        } else {
            untagged += 1;
        }
    }

    return {
        total: props.profiles.length,
        tagged,
        notJpm,
        untagged,
    };
});

const preparedBy = computed(() => props.options?.preparedBy || '');
const preparedByTitle = computed(() => props.options?.preparedByTitle || '');
const signatoryName = computed(() => props.options?.signatoryName || '');
const signatoryTitle = computed(() => props.options?.signatoryTitle || '');
const reportTitle = computed(() => props.options?.reportTitle || 'JPM TAGGING REPORT');
const includeProfileRemarks = computed(() => props.options?.includeProfileRemarks !== false);
const includeJpmRemarks = computed(() => props.options?.includeJpmRemarks !== false);
const asOfLabel = computed(() => props.generatedAt?.split(' — ')[0] || props.generatedAt || '');
const summaryItems = computed(() => [
    { label: 'Total', value: totals.value.total },
    { label: 'Tagged', value: totals.value.tagged },
    { label: 'Not JPM', value: totals.value.notJpm },
    { label: 'Untagged', value: totals.value.untagged },
]);

function fullName(profile) {
    const parts = [profile.last_name, ', ', profile.first_name, profile.middle_name, profile.extension_name].filter(Boolean);
    return parts.join(' ').replace(' ,', ',');
}

function plainText(value, fallback = 'Not provided') {
    const text = stripHtml(value || '').trim();
    return text || fallback;
}

function latestProgramLabel(profile) {
    return profile.latest_scholarship_record?.program?.shortname || profile.latest_scholarship_record?.program?.name || 'Program not set';
}

function latestSchoolLabel(profile) {
    return profile.latest_scholarship_record?.school?.shortname
        || profile.latest_scholarship_record?.school?.name
        || profile.latest_scholarship_record?.course?.shortname
        || profile.latest_scholarship_record?.course?.name
        || '';
}

function statusLabel(status) {
    const labels = {
        pending: 'Pending',
        interviewed: 'Interviewed',
        approved: 'Approved',
        active: 'Active',
        denied: 'Denied',
        suspended: 'Suspended',
        completed: 'Completed',
    };

    return labels[status] || status || 'Unknown';
}

function jpmStatus(profile) {
    const members = [];

    if (profile.is_jpm_member) members.push('Applicant');
    if (profile.is_father_jpm) members.push('Father');
    if (profile.is_mother_jpm) members.push('Mother');
    if (profile.is_guardian_jpm) members.push('Guardian');

    if (members.length > 0) {
        return {
            label: 'JPM Member',
            detail: members.join(', '),
        };
    }

    if (profile.is_not_jpm) {
        return {
            label: 'Not JPM',
            detail: '',
        };
    }

    if (stripHtml(profile.jpm_remarks || '').trim()) {
        return {
            label: 'Tagged With Note',
            detail: '',
        };
    }

    return {
        label: 'Untagged',
        detail: '',
    };
}
</script>

<template>
    <div class="report-page">
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p style="font-weight:700;font-size:11pt;">Republic of the Philippines</p>
            <p style="font-weight:700;font-size:11pt;">Provincial Government of Palawan</p>
            <p style="font-size:10pt;">Akbay sa Mag-aaral Yaman ng kinabukasan</p>
            <p style="font-size:10pt;">(Programang Pang-Edukasyon para sa Palaweño)</p>
            <p style="font-size:10pt;">Puerto Princesa City, Palawan</p>
        </div>

        <div style="text-align:center;padding:10pt 0 6pt;">
            <p style="font-weight:700;font-size:13pt;">{{ reportTitle }}</p>
            <p style="font-size:9pt;margin-top:3pt;">As of {{ asOfLabel }}</p>
        </div>

        <div
            style="display:flex;align-items:center;gap:8pt;flex-wrap:wrap;border:0.5pt solid #cbd5e1;border-radius:8pt;padding:6pt 8pt;margin-bottom:10pt;background:#f8fafc;">
            <span style="font-size:7.5pt;font-weight:700;text-transform:uppercase;color:#475569;">Summary</span>
            <span v-for="item in summaryItems" :key="item.label" style="font-size:8pt;color:#0f172a;">
                <strong>{{ item.label }}:</strong> {{ item.value }}
            </span>
        </div>

        <div v-if="activeFilters.length" style="margin-bottom:10pt;">
            <div style="font-size:8pt;font-weight:700;text-transform:uppercase;color:#475569;margin-bottom:4pt;">Applied
                Filters</div>
            <div style="display:flex;flex-wrap:wrap;gap:4pt;">
                <span v-for="tag in activeFilters" :key="tag.key"
                    style="display:inline-flex;align-items:center;gap:3pt;border:0.5pt solid #cbd5e1;border-radius:999pt;padding:3pt 7pt;font-size:7.5pt;background:#f8fafc;color:#334155;">
                    <strong>{{ tag.label }}:</strong> {{ tag.display }}
                </span>
            </div>
        </div>

        <div v-if="sortedProfiles.length === 0"
            style="text-align:center;padding:24pt;color:#888;font-size:10pt;font-style:italic;">
            No profiles match the selected JPM filters.
        </div>

        <table v-else style="width:100%;border-collapse:collapse;table-layout:fixed;">
            <thead>
                <tr>
                    <th
                        style="width:4%;border:0.5pt solid #cbd5e1;padding:6pt 4pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;">
                        #</th>
                    <th
                        style="width:24%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        Scholar</th>
                    <th
                        style="width:14%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        Mother</th>
                    <th
                        style="width:14%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        Father</th>
                    <th
                        style="width:14%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        Guardian</th>
                    <th
                        style="width:20%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        Remarks</th>
                    <th
                        style="width:10%;border:0.5pt solid #cbd5e1;padding:6pt;background:#e2e8f0;font-size:7.5pt;text-transform:uppercase;text-align:left;">
                        JPM Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(profile, index) in sortedProfiles" :key="profile.profile_id">
                    <td
                        style="border:0.5pt solid #e2e8f0;padding:6pt 4pt;font-size:8pt;text-align:center;vertical-align:top;">
                        {{ index + 1 }}</td>
                    <td style="border:0.5pt solid #e2e8f0;padding:6pt;vertical-align:top;">
                        <div style="font-size:8.5pt;font-weight:700;color:#0f172a;">{{ fullName(profile) }}</div>
                        <div style="font-size:7.5pt;color:#475569;margin-top:2pt;">{{ latestProgramLabel(profile) }}
                        </div>
                        <div v-if="latestSchoolLabel(profile)" style="font-size:7pt;color:#64748b;">{{
                            latestSchoolLabel(profile) }}</div>
                        <div v-if="profile.latest_scholarship_record?.unified_status"
                            style="font-size:7pt;color:#334155;margin-top:3pt;">
                            Status: {{ statusLabel(profile.latest_scholarship_record.unified_status) }}
                        </div>
                    </td>
                    <td
                        style="border:0.5pt solid #e2e8f0;padding:6pt;font-size:8pt;vertical-align:top;word-break:break-word;">
                        {{ plainText(profile.mother_name) }}</td>
                    <td
                        style="border:0.5pt solid #e2e8f0;padding:6pt;font-size:8pt;vertical-align:top;word-break:break-word;">
                        {{ plainText(profile.father_name) }}</td>
                    <td
                        style="border:0.5pt solid #e2e8f0;padding:6pt;font-size:8pt;vertical-align:top;word-break:break-word;">
                        {{ plainText(profile.guardian_name) }}</td>
                    <td style="border:0.5pt solid #e2e8f0;padding:6pt;vertical-align:top;">
                        <div v-if="includeProfileRemarks" style="margin-bottom:4pt;">
                            <div style="font-size:7pt;font-weight:700;text-transform:uppercase;color:#64748b;">Profile
                            </div>
                            <div style="font-size:8pt;color:#0f172a;white-space:pre-wrap;word-break:break-word;">{{
                                plainText(profile.remarks, 'No remarks available.') }}</div>
                        </div>
                        <div v-if="includeJpmRemarks">
                            <div style="font-size:7pt;font-weight:700;text-transform:uppercase;color:#64748b;">JPM</div>
                            <div style="font-size:8pt;color:#0f172a;white-space:pre-wrap;word-break:break-word;">{{
                                plainText(profile.jpm_remarks, 'No JPM note available.') }}</div>
                        </div>
                    </td>
                    <td style="border:0.5pt solid #e2e8f0;padding:6pt;vertical-align:top;">
                        <div style="font-size:8pt;font-weight:700;color:#0f172a;">{{ jpmStatus(profile).label }}</div>
                        <div v-if="jpmStatus(profile).detail"
                            style="font-size:7pt;color:#475569;margin-top:2pt;word-break:break-word;">
                            {{ jpmStatus(profile).detail }}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="display:flex;justify-content:space-between;gap:24pt;margin-top:18pt;align-items:flex-end;">
            <div style="flex:1;">
                <div style="font-size:7pt;text-transform:uppercase;color:#64748b;">Generated</div>
                <div style="font-size:8pt;color:#0f172a;">{{ generatedAt }}</div>
            </div>
            <div style="display:flex;gap:36pt;min-width:50%;justify-content:flex-end;">
                <div v-if="preparedBy" style="min-width:180pt;text-align:center;">
                    <div style="border-top:0.8pt solid #1f2937;padding-top:5pt;font-size:8pt;font-weight:700;">{{
                        preparedBy }}</div>
                    <div v-if="preparedByTitle" style="font-size:7pt;color:#475569;">{{ preparedByTitle }}</div>
                    <div v-else style="font-size:7pt;color:#475569;">Prepared by</div>
                </div>
                <div v-if="signatoryName" style="min-width:180pt;text-align:center;">
                    <div style="border-top:0.8pt solid #1f2937;padding-top:5pt;font-size:8pt;font-weight:700;">{{
                        signatoryName }}</div>
                    <div v-if="signatoryTitle" style="font-size:7pt;color:#475569;">{{ signatoryTitle }}</div>
                </div>
            </div>
        </div>
    </div>
</template>