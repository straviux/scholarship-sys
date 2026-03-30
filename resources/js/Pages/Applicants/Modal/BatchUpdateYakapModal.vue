<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';

const props = defineProps({
    show: Boolean,
    selectedRows: { type: Array, default: () => [] },
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'success']);

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' },
];

const batchYakapForm = useForm({
    yakap_category: '',
    yakap_location: '',
});

const close = () => {
    emit('update:show', false);
    batchYakapForm.reset();
};

const handleCategoryChange = () => {
    batchYakapForm.yakap_location = null;
};

const submit = () => {
    if (props.selectedRows.length === 0) {
        toast.error('No applicants selected');
        return;
    }
    if (!batchYakapForm.yakap_category) {
        toast.error('Please select a YAKAP category');
        return;
    }

    let yakapLocation = batchYakapForm.yakap_location;
    if (yakapLocation && typeof yakapLocation === 'object') yakapLocation = yakapLocation.name || '';

    const profileIds = props.selectedRows.map(row => row.profile_id);

    axios.post(route('scholarship-record.batch-update-yakap'), {
        profile_ids: profileIds,
        yakap_category: batchYakapForm.yakap_category,
        yakap_location: yakapLocation || null,
    }).then(() => {
        close();
        toast.success(`YAKAP category updated for ${profileIds.length} applicant(s)!`);
        emit('success');
    }).catch(error => {
        toast.error('Failed to update YAKAP categories');
        console.error(error.response?.data || error);
    });
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '680px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, select, a, .p-select')) return;
    dragStart.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y };
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
}
function onDragMove(e) {
    if (!dragStart.value) return;
    dragOffset.value = { x: e.clientX - dragStart.value.x, y: e.clientY - dragStart.value.y };
}
function onDragEnd() {
    dragStart.value = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
}
onBeforeUnmount(() => {
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);
});
</script>

<template>
    <Dialog :visible="show" modal @update:visible="val => !val && close()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">Batch Update YAKAP</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit"
                        :disabled="!batchYakapForm.yakap_category">
                        Update All
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body">
                    <!-- Selection Summary -->
                    <div class="ios-section">
                        <div class="ios-section-label">Selection Summary</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Selected Applicants</span>
                                <span class="ios-badge">{{ selectedRows.length }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label" style="color: #8E8E93; font-size: 13px;">
                                    Batch update will apply YAKAP category to all selected applicants
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Applicants Preview -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicants</div>
                        <div class="ios-card ios-applicant-list">
                            <div v-for="(row, idx) in selectedRows.slice(0, 8)" :key="idx" class="ios-row">
                                <span class="ios-row-label">{{ row.last_name }}, {{ row.first_name }}</span>
                            </div>
                            <div v-if="selectedRows.length > 8" class="ios-row ios-row-last"
                                style="color: #8E8E93; font-size: 13px; justify-content: center;">
                                ... and {{ selectedRows.length - 8 }} more
                            </div>
                        </div>
                    </div>

                    <!-- YAKAP Category Selection -->
                    <div class="ios-section">
                        <div class="ios-section-label">Update Options</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">YAKAP Category</span>
                                <div class="ios-row-control ios-select">
                                    <Select v-model="batchYakapForm.yakap_category" :options="yakapCategoryOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select Category"
                                        @change="handleCategoryChange" />
                                </div>
                            </div>

                            <!-- Municipality (YAKAP Field) -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-field'" class="ios-row">
                                <span class="ios-row-label">Municipality</span>
                                <div class="ios-row-control ios-select">
                                    <MunicipalitySelect v-model="batchYakapForm.yakap_location"
                                        placeholder="Select Municipality" :clearable="false" />
                                </div>
                            </div>

                            <!-- School (YAKAP School) -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-school'" class="ios-row">
                                <span class="ios-row-label">School</span>
                                <div class="ios-row-control ios-select">
                                    <SchoolSelect v-model="batchYakapForm.yakap_location" placeholder="Select School"
                                        :clearable="false" />
                                </div>
                            </div>

                            <!-- Capitol description -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-capitol'" class="ios-row ios-row-last">
                                <span style="font-size: 13px; color: #8E8E93;">No specific location required</span>
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            Select the YAKAP category to apply to all selected applicants
                        </div>
                    </div>

                    <!-- Bottom spacing -->
                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    margin: 0 auto;
}

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
}

.ios-nav-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

.ios-section {
    margin-top: 22px;
}

.ios-section:first-child {
    margin-top: 16px;
}

.ios-section-label {
    font-size: 13px;
    font-weight: 400;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: -0.08px;
    padding: 0 16px 6px;
}

.ios-section-footer {
    font-size: 13px;
    color: #6D6D72;
    padding: 6px 16px 0;
    line-height: 1.3;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

.ios-applicant-list {
    max-height: 200px;
    overflow-y: auto;
}

.ios-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    min-height: 36px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row-last {
    border-bottom: none;
}

.ios-row:last-child {
    border-bottom: none;
}

.ios-row-label {
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.ios-row-control {
    flex: 0 0 200px;
    width: 200px;
    display: flex;
    justify-content: flex-end;
    overflow: hidden;
}

.ios-row-control>* {
    width: 100%;
    min-width: 0;
}

.ios-badge {
    background: #007AFF;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    padding: 1px 10px;
    border-radius: 20px;
    min-width: 28px;
    text-align: center;
}

:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    padding: 0;
    width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #8E8E93 !important;
    text-align: left;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

/* Dark mode overrides */
:global(.dark) .ios-modal {
    background: #222831;
}
:global(.dark) .ios-nav-bar {
    background: #2a3040;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
:global(.dark) .ios-nav-title {
    color: #d1d5db;
}
:global(.dark) .ios-nav-cancel {
    color: #9ca3af;
}
:global(.dark) .ios-nav-action {
    color: #d1d5db;
}
:global(.dark) .ios-section-label {
    color: #9ca3af;
}
:global(.dark) .ios-section-footer {
    color: #6b7280;
}
:global(.dark) .ios-card {
    background: #222831;
    border-color: rgba(255, 255, 255, 0.08);
}
:global(.dark) .ios-row {
    border-bottom-color: rgba(255, 255, 255, 0.06);
}
:global(.dark) .ios-row-label {
    color: #d1d5db;
}
:global(.dark) .ios-body {
    scrollbar-color: transparent transparent;
}
:global(.dark) .ios-body:hover {
    scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    width: auto !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
