<template>
    <IosModal :visible="show" title="Generate JPM Report" width="min(720px, calc(100vw - 2rem))"
        :show-action="true" action-label="Preview" :loading="generating" @action="generateReport"
        @update:visible="value => { if (!value) close(); }">
        <div class="space-y-5">
            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                This report uses the currently applied JPM filters and includes all matching profiles, not just the
                current page.
            </div>

            <div v-if="totalRecords > 500"
                class="rounded-3xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Large report detected: {{ totalRecords }} profiles. Preview generation may take longer than usual.
            </div>

            <div v-if="activeFilterTags.length" class="space-y-2">
                <div class="text-sm font-semibold text-slate-700">Applied Filters</div>
                <div class="flex flex-wrap gap-2">
                    <Tag v-for="tag in activeFilterTags" :key="tag.key" severity="secondary" rounded>
                        <span class="text-xs">{{ tag.label }}: <strong>{{ tag.display }}</strong></span>
                    </Tag>
                </div>
            </div>

            <div class="space-y-3 rounded-3xl border border-slate-200 px-4 py-4">
                <div>
                    <div class="text-sm font-semibold text-slate-700">Header</div>
                    <div class="text-xs text-slate-500">Customize the report title and request date shown at the top
                        of the printed report.</div>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Report Title</label>
                    <InputText v-model="reportTitle" class="w-full" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Request Date</label>
                    <DatePicker v-model="requestDate" showButtonBar dateFormat="M dd, yy" showIcon
                        iconDisplay="input" class="w-full" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Paper Size</label>
                    <Select v-model="paperSize" :options="paperSizeOptions" optionLabel="label" optionValue="value"
                        class="w-full" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Orientation</label>
                    <Select v-model="orientation" :options="orientationOptions" optionLabel="label" optionValue="value"
                        class="w-full" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Prepared By</label>
                    <InputText v-model="preparedBy" class="w-full" placeholder="Optional" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Prepared By Title</label>
                    <InputText v-model="preparedByTitle" class="w-full" placeholder="Optional" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Signatory Name</label>
                    <InputText v-model="signatoryName" class="w-full" placeholder="Optional" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Signatory Title</label>
                    <InputText v-model="signatoryTitle" class="w-full" placeholder="Optional" />
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="flex items-center justify-between rounded-3xl border border-slate-200 px-4 py-3">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Include Profile Remarks</div>
                        <div class="text-xs text-slate-500">Show scholar remarks in the report table.</div>
                    </div>
                    <ToggleSwitch v-model="includeProfileRemarks" />
                </div>
                <div class="flex items-center justify-between rounded-3xl border border-slate-200 px-4 py-3">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Include JPM Notes</div>
                        <div class="text-xs text-slate-500">Show JPM-specific notes in the report table.</div>
                    </div>
                    <ToggleSwitch v-model="includeJpmRemarks" />
                </div>
            </div>
        </div>

    </IosModal>

    <IosModal :visible="showPreview" width="min(98vw, 1600px)" body-style="padding: 0;"
        :modal-content-style="{ height: '95vh', display: 'flex', flexDirection: 'column' }"
        @update:visible="value => { showPreview = value; if (!value) resetPreviewState(); }">
        <template #title>
            <div>
                <div class="text-base font-semibold text-slate-800">JPM Report Preview</div>
                <div class="text-xs text-slate-500">{{ orientation }} · {{ paperSize }}</div>
            </div>
        </template>
        <template #header-right>
            <div class="flex items-center gap-2 pr-2">
                <button @click="zoomLevel = Math.max(40, zoomLevel - 10)"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-600"
                    :disabled="zoomLevel <= 40">
                    <AppIcon name="minus" :size="10" />
                </button>
                <span class="w-12 text-center text-xs font-medium text-slate-500">{{ zoomLevel }}%</span>
                <button @click="zoomLevel = Math.min(200, zoomLevel + 10)"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-600"
                    :disabled="zoomLevel >= 200">
                    <AppIcon name="plus" :size="10" />
                </button>
                <AppButton icon="printer" label="Print" severity="secondary" @click="doPrint" />
            </div>
        </template>

        <div class="h-[calc(95vh-8rem)] overflow-auto bg-slate-200 py-4">
            <div class="flex justify-center">
                <iframe v-if="previewHtml" :srcdoc="previewHtml"
                    :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', border: 'none', background: '#fff', boxShadow: '0 2px 20px rgba(0,0,0,0.15)', width: `${iframeWidth}px`, height: `${iframeHeight}px` }"
                    frameborder="0"></iframe>
                <div v-else class="flex items-center justify-center py-20 text-slate-500">Generating report...</div>
            </div>
        </div>
    </IosModal>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import moment from 'moment';
import axios from 'axios';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { renderVueTemplate } from '@/composables/usePdfPrint';
import { pagedjsPolyfillScript } from '@/utils/pagedjsPolyfill';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';
import JpmTaggingReportTemplate from '@/Pages/JpmTagging/Reports/JpmTaggingReportTemplate.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appliedFilters: {
        type: Object,
        default: () => ({}),
    },
    activeFilterTags: {
        type: Array,
        default: () => [],
    },
    totalRecords: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:show']);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);

const generating = ref(false);
const showPreview = ref(false);
const previewHtml = ref('');
const zoomLevel = ref(110);
const iframePagedHeight = ref(null);

const paperSize = ref('Legal');
const orientation = ref('landscape');
const preparedBy = ref('');
const preparedByTitle = ref('');
const signatoryName = ref('');
const signatoryTitle = ref('');
const reportTitle = ref('JPM TAGGING REPORT');
const requestDate = ref(new Date());
const includeProfileRemarks = ref(true);
const includeJpmRemarks = ref(true);

const paperSizeOptions = [
    { label: 'A4', value: 'A4' },
    { label: 'Letter', value: 'Letter' },
    { label: 'Legal / Long', value: 'Legal' },
];

const orientationOptions = [
    { label: 'Portrait', value: 'portrait' },
    { label: 'Landscape', value: 'landscape' },
];

const paperConfig = computed(() => getReportPaperConfig(paperSize.value, orientation.value));
const iframeWidth = computed(() => paperConfig.value.widthPx);
const iframeHeight = computed(() => iframePagedHeight.value ?? paperConfig.value.heightPx);

function close() {
    resetPreviewState();
    emit('update:show', false);
}

function normalizeFilters() {
    return Object.entries(props.appliedFilters || {}).reduce((params, [key, value]) => {
        if (value === null || value === '' || key === 'page' || key === 'records') {
            return params;
        }

        params[key] = value;
        return params;
    }, {});
}

async function generateReport() {
    if (generating.value) {
        return;
    }

    generating.value = true;

    try {
        const response = await axios.get(route('jpm-tagging.report'), {
            params: normalizeFilters(),
        });

        const records = response.data?.data ?? [];
        const bodyHtml = renderVueTemplate(JpmTaggingReportTemplate, {
            profiles: records,
            activeFilters: props.activeFilterTags,
            generatedAt: moment().format('MMMM DD, YYYY — h:mm A'),
            options: {
                reportTitle: reportTitle.value?.trim() || 'JPM TAGGING REPORT',
                requestDate: requestDate.value ? moment(requestDate.value).format('MMMM DD, YYYY') : '',
                includeProfileRemarks: includeProfileRemarks.value,
                includeJpmRemarks: includeJpmRemarks.value,
                preparedBy: preparedBy.value?.trim() || '',
                preparedByTitle: preparedByTitle.value?.trim() || '',
                signatoryName: signatoryName.value?.trim() || '',
                signatoryTitle: signatoryTitle.value?.trim() || '',
            },
        });

        previewHtml.value = buildReportDoc(bodyHtml, reportTitle.value?.trim() || 'JPM Tagging Report', paperConfig.value);
        showPreview.value = true;
    } catch (error) {
        console.error('Failed to generate JPM report:', error);
    } finally {
        generating.value = false;
    }
}

function buildReportDoc(bodyHtml, title, pageSettings) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>${title}</title>
  <style>
    body { visibility: hidden; margin: 0; padding: 0; }
    ${getReportCss(pageSettings)}
  </style>
    <script>${pagedjsPolyfillScript}<\/script>
  <script>
    window.PagedPolyfill.on('rendered', function () {
      // Repeat <thead> on split tables (paged.js v0.4.3 doesn't do this automatically)
      try {
        var splitTables = document.querySelectorAll('table[data-split-from]');
        splitTables.forEach(function (tbl) {
          if (tbl.querySelector(':scope > thead')) return;
          var ref = tbl.getAttribute('data-ref');
          if (!ref) return;
          // Find the original (non-split) table with the same data-ref
          var originals = document.querySelectorAll('table[data-ref="' + ref + '"]:not([data-split-from])');
          var original = originals[0];
          if (!original) return;
          var origThead = original.querySelector(':scope > thead');
          if (!origThead) return;
          var headClone = origThead.cloneNode(true);
          tbl.insertBefore(headClone, tbl.firstChild);
        });
      } catch (e) { console.warn('thead repeat failed', e); }

      var pages = document.querySelector('.pagedjs_pages');
      var h = pages ? pages.scrollHeight + 48 : document.documentElement.scrollHeight;
      window.parent.postMessage({ type: 'pagedjs:rendered', height: h }, '*');
      document.body.style.visibility = 'visible';
      if (document.body.getAttribute('data-auto-print') === '1') {
        window.print();
      }
    });
  <\/script>
</head>
<body>${bodyHtml}</body>
</html>`;
}

function doPrint() {
    if (!previewHtml.value) {
        return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('Pop-up blocked. Please allow pop-ups for this site.');
        return;
    }

    const htmlForPrint = previewHtml.value.replace('<body>', '<body data-auto-print="1">');
    printWindow.document.write(htmlForPrint);
    printWindow.document.close();
}

function onPagedMessage(event) {
    if (event.data?.type === 'pagedjs:rendered') {
        iframePagedHeight.value = event.data.height;
    }
}

function resetPreviewState() {
    window.removeEventListener('message', onPagedMessage);
    showPreview.value = false;
    previewHtml.value = '';
    iframePagedHeight.value = null;
}

watch(showPreview, value => {
    if (value) {
        iframePagedHeight.value = null;
        window.addEventListener('message', onPagedMessage);
        return;
    }

    window.removeEventListener('message', onPagedMessage);
});

watch(() => props.show, value => {
    if (!value) {
        resetPreviewState();
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('message', onPagedMessage);
});
</script>