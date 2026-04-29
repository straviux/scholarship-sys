<template>
    <!--
        Individual Scholar Certification  –  A4
        certType: 'review' | 'postgrad'
        All layout uses inline styles so renderVueTemplate captures them correctly.
    -->
    <div
        style="max-width:950px;margin:0 auto;background:#fff;font-family:Verdana,Geneva,sans-serif;font-size:11pt;line-height:1.4;color:#333;">

        <!-- ── Header ──────────────────────────────────────── -->
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:56pt;text-align:center;margin-top:18pt">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:0;top:50%;transform:translateY(-50%);width:72pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="Yakap Logo"
                style="position:absolute;right:0;top:50%;transform:translateY(-50%);width:72pt;height:auto;" />
            <p style="font-size:11.5pt;font-weight:400;">Republic of the Philippines</p>
            <p style="font-size:11.5pt;font-weight:400;">Provincial Government of Palawan</p>
            <p style="font-size:11.5pt;font-weight:400;">OFFICE OF THE GOVERNOR</p>
            <p style="font-size:11.5pt;font-weight:700;">AKBAY SA MAG-AARAL YAMAN NG KINABUKASAN</p>
            <p style="font-size:11.5pt;font-weight:400;">(Programang Pang-Edukasyon Para sa Palawenyo)</p>
        </div>

        <!-- ── Document Title ──────────────────────────────── -->
        <div style="text-align:center;margin-top:36pt;">
            <p style="font-size:24pt;font-weight:700;text-decoration: underline;">CERTIFICATION</p>
        </div>


        <!-- ── Body ────────────────────────────────────────── -->
        <div style="margin-top:32pt;font-size:11pt;line-height:1.8;text-align: justify;">

            <!-- Paragraph 1 - REVIEW -->
            <p v-if="certType === 'review'" style="text-align:justify;text-indent:60pt;margin-bottom:14pt !important;">
                This is to certify that, <strong>{{ honorific }} {{ fullNameCaps }}</strong>, {{ displayCourseName }}
                student of {{
                    schoolName }},
                is a recipient of Doctor of Medicine/Medical Related Scholarship of Provincial Government of Palawan
                through Scholarship Program
                &#8220;Programang Pang-Edukasyon para sa Palawe&#241;o&#8221;.
            </p>

            <!-- Paragraph 1 - POST-GRAD -->
            <p v-else style="text-align:justify;text-indent:60pt;margin-bottom:14pt !important;">
                This is to certify that, <strong>{{ honorific }} {{ fullNameCaps }}</strong>, a {{ displayCourseName }}
                student of {{
                    schoolName }},
                , is a recipient of the Medical Scholarship of Akbay sa Mag-Aaral Yaman Ng Kinabukasan Scholarship
                Program of the Provincial Government of Palawan.
            </p>

            <!-- Paragraph 2 – REVIEW -->
            <p v-if="certType === 'review'" style="text-align:justify;text-indent:60pt;margin-top:14pt !important;">
                {{ lastNameRef }}, presently taking Review
                for Board
                Exam (PLE), is requesting us to grant Financial
                Assistance to cover the cost of Review. Per our policy and SP Resolution, this is allowed but with
                corresponding equivalent of additional six (6) months Return-of-Service.
            </p>

            <!-- Paragraph 2 – POST-GRAD -->
            <p v-else style="text-align:justify;text-indent:60pt;margin-top:14pt !important;">
                {{ lastNameRef }}, is presently taking her Post-Graduate Internship (PGI) and has requested Financial
                Assistance to cover the cost of her internship. In accordance with our policy and the Sangguniang
                Panlalawigan Resolution, the granting of said assistance is allowed, subject to an additional six (6)
                moths Return-of-Service (ROS) obligation for every year of support. The amount granted for PGI is
                Seventy Thousand Pesos (₱70,000.00) per semester, or One Hundred Forty Thousand Pesos (₱140,000.00) per
                year.
            </p>

            <!-- Paragraph 3 – REVIEW -->
            <p v-if="certType === 'review'" style="text-align:justify;text-indent:60pt;margin-top: 14pt !important;">
                The amount granted on Review is Seventy Thousand Pesos only (Php 70,000.00).
            </p>

            <!-- Paragraph 3 – POST-GRAD -->
            <p v-else style="text-align:justify;text-indent:60pt;margin-top: 14pt !important;">
                Issued upon the request of {{ lastNameRef }} for whatever legal purposes this may serve her.
            </p>

        </div>

        <!-- ── Signatory ────────────────────────────────────── -->
        <div style="margin-top:72pt;text-align:center;">
            <p style="font-weight:700;font-size:11pt;text-decoration: underline;">NUR-AINA S. IBRAHIM</p>
            <p style="font-size:11pt;">Program Manager</p>
            <p style="font-size:11pt;margin-top: -6pt;">Akbay sa Mag-Aaral Yaman ng Kinabukasan</p>
        </div>

    </div>

    <!-- ── Page Footer ─────────────────────────────────────── -->
    <div
        style="position:fixed;bottom:0;left:0;right:0;text-align:center;font-family:Verdana,Geneva,sans-serif;font-size:8pt;color:#333;">
        <div style="border-top:2.2pt solid #00B050;margin-bottom:4pt;"></div>
        <p style="margin:0;">Provincial Capitol, Fernandez St., Bgy. Tanglaw, City of Puerto Princesa, 5300</p>
        <p style="margin:0;">pgpscholarshipprogram@gmail.com</p>
    </div>

</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    certType: { type: String, default: 'review' }, // 'review' | 'postgrad'
    today: { type: String, default: '' },
    courseName: { type: String, default: null },
});

const honorific = computed(() => {
    const g = (props.profile.gender || '').toLowerCase();
    if (g.includes('female') || g === 'f') return 'Ms.';
    if (g.includes('male') || g === 'm') return 'Mr.';
    return 'Ms./Mr.';
});

const fullNameCaps = computed(() => {
    const p = props.profile;
    return [p.first_name, p.middle_name, p.last_name, p.extension_name]
        .filter(Boolean).join(' ').toUpperCase();
});

const lastNameRef = computed(() => {
    const last = (props.profile.last_name || '');
    const capitalized = last.charAt(0).toUpperCase() + last.slice(1).toLowerCase();
    return `${honorific.value} ${capitalized}`;
});

const pronoun = computed(() => {
    const g = (props.profile.gender || '').toLowerCase();
    return (g.includes('female') || g === 'f') ? 'she' : 'he';
});

const possessive = computed(() => {
    const g = (props.profile.gender || '').toLowerCase();
    return (g.includes('female') || g === 'f') ? 'her' : 'his';
});

const latestRecord = computed(() => {
    const grants = (props.profile.scholarship_grant || []).filter(g => !g.deleted_at);
    return grants.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] ?? null;
});

const profileCourseName = computed(() => latestRecord.value?.course?.name ?? '—');
const displayCourseName = computed(() => props.courseName || profileCourseName.value);

const schoolName = computed(() =>
    latestRecord.value?.school_name ?? latestRecord.value?.school?.name ?? '—'
);

const programName = computed(() => latestRecord.value?.program?.name ?? '—');

</script>
