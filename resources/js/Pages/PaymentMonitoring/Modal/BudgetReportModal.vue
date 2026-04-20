<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import ProgressSpinner from 'primevue/progressspinner';
import AppIcon from '@/Components/ui/AppIcon.vue';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import { usePdfPrint, renderVueTemplate } from '@/composables/usePdfPrint';
import BudgetReportTemplate from '@/Pages/PaymentMonitoring/Pdf/BudgetReportTemplate.vue';
import { exportBudgetReportExcel } from '@/Pages/PaymentMonitoring/Excel/BudgetReportExcel.js';
import axios from 'axios';

const props = defineProps({
    visible: { type: Boolean, required: true },
    fiscalYears: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:visible', 'preview']);

function close() { emit('update:visible', false); }

/* Draggable  */
const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

/*  PDF builder  */
const { buildHtmlDoc } = usePdfPrint();

/*  State  */
const chosenProgram = ref(null);
const chosenFiscalYear = ref(null);
const chosenRc = ref(null);   // { id, name, code }
const chosenParticular = ref(null);   // { id, name, account_code, allotment }
const rcOptions = ref([]);
const particularOptions = ref([]);
const loadingRcs = ref(false);
const loadingParticulars = ref(false);
const loading = ref(false);
const loadingExcel = ref(false);
const error = ref(null);

const canGenerate = computed(() =>
    chosenProgram.value && chosenFiscalYear.value && chosenRc.value && chosenParticular.value
);

/*  Cascade: load RCs when program + fiscal year are both set  */
async function loadRcs() {
    const programId = typeof chosenProgram.value === 'object'
        ? chosenProgram.value?.id : chosenProgram.value;
    if (!programId || !chosenFiscalYear.value) return;
    loadingRcs.value = true;
    rcOptions.value = [];
    chosenRc.value = null;
    try {
        const { data } = await axios.get(route('budget-report.rcenters'), {
            params: { program_id: programId, fiscal_year: chosenFiscalYear.value },
        });
        rcOptions.value = data;
    } catch {
        rcOptions.value = [];
    } finally {
        loadingRcs.value = false;
    }
}

/*  Cascade: load Particulars when RC is set  */
async function loadParticulars() {
    const programId = typeof chosenProgram.value === 'object'
        ? chosenProgram.value?.id : chosenProgram.value;
    if (!chosenRc.value?.id || !programId) return;
    loadingParticulars.value = true;
    particularOptions.value = [];
    chosenParticular.value = null;
    try {
        const { data } = await axios.get(route('budget-report.particulars'), {
            params: { rc_id: chosenRc.value.id, program_id: programId },
        });
        particularOptions.value = data;
    } catch {
        particularOptions.value = [];
    } finally {
        loadingParticulars.value = false;
    }
}

watch([chosenProgram, chosenFiscalYear], () => {
    chosenRc.value = null;
    chosenParticular.value = null;
    rcOptions.value = [];
    particularOptions.value = [];
    loadRcs();
});

watch(chosenRc, () => {
    chosenParticular.value = null;
    particularOptions.value = [];
    if (chosenRc.value) loadParticulars();
});

/*  Generate Excel  */
async function generateExcel() {
    if (!canGenerate.value) return;
    loadingExcel.value = true;
    error.value = null;
    try {
        const programId = typeof chosenProgram.value === 'object'
            ? chosenProgram.value?.id : chosenProgram.value;

        const { data } = await axios.get(route('budget-report.api'), {
            params: {
                program_id: programId,
                fiscal_year: chosenFiscalYear.value,
                rc_id: chosenRc.value.id,
                particular_id: chosenParticular.value.id,
            },
        });

        const today = new Date().toLocaleDateString('en-PH', {
            year: 'numeric', month: 'long', day: 'numeric',
        });

        exportBudgetReportExcel({ reportData: data, today });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Failed to load report.';
    } finally {
        loadingExcel.value = false;
    }
}

/*  Generate  */
async function generateReport() {
    if (!canGenerate.value) return;
    loading.value = true;
    error.value = null;
    try {
        const programId = typeof chosenProgram.value === 'object'
            ? chosenProgram.value?.id : chosenProgram.value;

        const { data } = await axios.get(route('budget-report.api'), {
            params: {
                program_id: programId,
                fiscal_year: chosenFiscalYear.value,
                rc_id: chosenRc.value.id,
                particular_id: chosenParticular.value.id,
            },
        });

        const today = new Date().toLocaleDateString('en-PH', {
            year: 'numeric', month: 'long', day: 'numeric',
        });

        const bodyHtml = renderVueTemplate(BudgetReportTemplate, { reportData: data, today });
        const title = `Budget-Report-${data.program_name}-${data.fiscal_year}`;
        const htmlDoc = buildHtmlDoc(bodyHtml, title, 'landscape');

        emit('update:visible', false);
        emit('preview', { htmlDoc, title, paperSize: 'landscape', reportData: data, today });

    } catch (e) {
        error.value = e.response?.data?.message ?? 'Failed to load report.';
    } finally {
        loading.value = false;
    }
}

function handleOpen() {
    resetDrag();
    chosenProgram.value = null;
    chosenFiscalYear.value = null;
    chosenRc.value = null;
    chosenParticular.value = null;
    rcOptions.value = [];
    particularOptions.value = [];
    error.value = null;
}
</script>

<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal @show="handleOpen"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal budget-report-modal" :style="dragStyle">

                <!-- iOS Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close" v-tooltip.bottom="'Close'">
                        <AppIcon name="times" />
                    </button>
                    <span class="ios-nav-title">Budget / Allotment Report</span>
                    <button class="ios-nav-btn ios-nav-action" :disabled="!canGenerate || loading || loadingExcel"
                        @click="generateReport" v-tooltip.bottom="'Generate & Preview PDF'">
                        <AppIcon :name="loading ? 'spinner' : 'check'" />
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body budget-report-body">

                    <!-- Loading -->
                    <div v-if="loading" class="flex flex-col items-center justify-center py-16 gap-4">
                        <ProgressSpinner style="width:40px;height:40px" strokeWidth="4" />
                        <p class="text-sm text-gray-500">Building report...</p>
                    </div>

                    <!-- Prompt -->
                    <template v-else>
                        <div class="ios-section py-4" style="max-width:520px;margin:0 auto">
                            <div class="ios-section-label">Report Parameters</div>
                            <div class="ios-card mt-2">

                                <!-- Program -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="bookmark-fill" style="color:#007AFF;font-size:13px" />
                                        Program
                                    </div>
                                    <div class="ios-row-control">
                                        <ProgramSelect v-model="chosenProgram" custom-placeholder="Select program"
                                            class="ios-select" />
                                    </div>
                                </div>

                                <!-- Fiscal Year -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="calendar" style="color:#FF9500;font-size:13px" />
                                        Fiscal Year
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="chosenFiscalYear" :options="fiscalYears"
                                            placeholder="Select fiscal year" class="ios-select"
                                            :pt="{ root: { style: 'border-radius:1rem' } }" />
                                    </div>
                                </div>

                                <!-- Responsibility Center -->
                                <div class="ios-row">
                                    <div class="ios-row-label">
                                        <AppIcon name="building" style="color:#34C759;font-size:13px" />
                                        R. Center
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="chosenRc" :options="rcOptions" optionLabel="name" dataKey="id"
                                            :placeholder="loadingRcs ? 'Loading...' : 'Select R. Center'"
                                            :loading="loadingRcs"
                                            :disabled="!chosenProgram || !chosenFiscalYear || loadingRcs"
                                            class="ios-select" :pt="{ root: { style: 'border-radius:1rem' } }">
                                            <template #option="{ option }">
                                                <span>{{ option.name }} <span style="opacity:.5">({{ option.code
                                                }})</span></span>
                                            </template>
                                        </Select>
                                    </div>
                                </div>

                                <!-- Account Code / Particular -->
                                <div class="ios-row ios-row-last">
                                    <div class="ios-row-label">
                                        <AppIcon name="list" style="color:#AF52DE;font-size:13px" />
                                        Account Code
                                    </div>
                                    <div class="ios-row-control">
                                        <Select v-model="chosenParticular" :options="particularOptions"
                                            optionLabel="name" dataKey="id"
                                            :placeholder="loadingParticulars ? 'Loading...' : 'Select particular'"
                                            :loading="loadingParticulars" :disabled="!chosenRc || loadingParticulars"
                                            class="ios-select" :pt="{ root: { style: 'border-radius:1rem' } }">
                                            <template #option="{ option }">
                                                <div>
                                                    <div>{{ option.name }}</div>
                                                    <div style="font-size:11px;opacity:.55">{{ option.account_code }}
                                                    </div>
                                                </div>
                                            </template>
                                        </Select>
                                    </div>
                                </div>

                            </div>
                            <div v-if="error" class="ios-section-footer ios-error">{{ error }}</div>
                            <div v-else class="ios-section-footer">
                                Includes OBRs with status:
                                <strong>PAID, CLAIMED</strong>.
                                Amounts are shown in the Credit column.
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.budget-report-modal {
    width: auto;
    min-width: 460px;
    max-width: 92vw;
}

.budget-report-body {
    overflow: visible;
    padding-bottom: 20px;
}

:deep(.ios-row-control) {
    flex: 0 0 auto;
    width: auto;
    min-width: 200px;
    max-width: 280px;
    overflow: visible;
}
</style>
