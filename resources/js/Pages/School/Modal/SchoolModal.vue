<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import AppIcon from '@/Components/ui/AppIcon.vue';

const props = defineProps({
    visible: Boolean,
    school: Object,
});

const emit = defineEmits(['update:visible', 'saved']);

const isEdit = computed(() => !!props.school);
const modalTitle = computed(() => isEdit.value ? 'Edit School' : 'New School');

const form = useForm({
    name: '',
    shortname: '',
    campus: '',
    address: '',
    start_date: null,
    end_date: null,
    remarks: '',
    is_active: 1,
});

watch(() => props.visible, (val) => {
    if (val) {
        dragOffset.value = { x: 0, y: 0 };
        form.clearErrors();
        form.name = props.school?.name ?? '';
        form.shortname = props.school?.shortname ?? '';
        form.campus = props.school?.campus ?? '';
        form.address = props.school?.address ?? '';
        form.start_date = props.school?.start_date ? new Date(props.school.start_date) : null;
        form.end_date = props.school?.end_date ? new Date(props.school.end_date) : null;
        form.remarks = props.school?.remarks ?? '';
        form.is_active = props.school?.is_active ?? 1;
    }
});

const submit = () => {
    const opts = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(isEdit.value ? 'School updated successfully.' : 'School added successfully.');
            emit('saved');
        },
    };
    if (isEdit.value) {
        form.put(route('school.update', props.school.id), opts);
    } else {
        form.post(route('school.store'), opts);
    }
};

const closeModal = () => emit('update:visible', false);

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '580px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-checkbox, .p-radiobutton, .p-editor, .p-datepicker')) return;
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
    <Dialog :visible="visible" modal @update:visible="val => !val && closeModal()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">

                <!-- iOS Navigation Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal" v-tooltip.bottom="'Close'">
                        <AppIcon name="x" :size="16" />
                    </button>
                    <span class="ios-nav-title">{{ modalTitle }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="form.processing"
                        v-tooltip.bottom="isEdit ? 'Save' : 'Add'">
                        <AppIcon v-if="form.processing" name="spinner" :size="16" />
                        <template v-else>{{ isEdit ? 'Save' : 'Add' }}</template>
                    </button>
                </div>

                <div class="ios-body">

                    <!-- SCHOOL DETAILS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">School Details</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="building-2" :size="13" style="color: #007AFF;" />
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.name" placeholder="School name" class="ios-row-input" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="tag" :size="13" style="color: #FF9500;" />
                                    Shortname
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.shortname" placeholder="e.g. UST" class="ios-row-input" />
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.name" class="ios-section-footer ios-error">{{ form.errors.name }}</div>
                        <div v-if="form.errors.shortname" class="ios-section-footer ios-error">{{ form.errors.shortname
                        }}</div>
                    </div>

                    <!-- LOCATION SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Location</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="map-pin" :size="13" style="color: #34C759;" />
                                    Campus
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.campus" placeholder="e.g. Main Campus"
                                        class="ios-row-input" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last ios-row-textarea">
                                <div class="ios-row-label" style="align-self: flex-start; padding-top: 8px;">
                                    <AppIcon name="home" :size="13" style="color: #5856D6;" />
                                    Address
                                </div>
                                <Textarea v-model="form.address" placeholder="Full address" rows="2" autoResize
                                    class="ios-textarea" />
                            </div>
                        </div>
                        <div v-if="form.errors.address" class="ios-section-footer ios-error">{{ form.errors.address }}
                        </div>
                    </div>

                    <!-- SCHEDULE SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Schedule</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-dates">
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    Start Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.start_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                                <span class="ios-date-separator">—</span>
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" :size="13" style="color: #FF3B30;" />
                                    End Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.end_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REMARKS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card" style="padding: 0;">
                            <Editor v-model="form.remarks" editorStyle="height: 120px">
                                <template #toolbar>
                                    <span class="ql-formats">
                                        <button class="ql-bold"></button>
                                        <button class="ql-italic"></button>
                                        <button class="ql-underline"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-list" value="ordered"></button>
                                        <button class="ql-list" value="bullet"></button>
                                    </span>
                                    <span class="ql-formats">
                                        <button class="ql-clean"></button>
                                    </span>
                                </template>
                            </Editor>
                        </div>
                        <div v-if="form.errors.remarks" class="ios-section-footer ios-error">{{ form.errors.remarks }}
                        </div>
                    </div>

                    <!-- STATUS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Status</div>
                        <div class="ios-card">
                            <label class="ios-row ios-row-last" style="cursor: pointer;">
                                <div class="ios-row-label">
                                    <AppIcon name="check-circle" :size="13" style="color: #34C759;" />
                                    Active
                                </div>
                                <ToggleSwitch v-model="form.is_active" :trueValue="1" :falseValue="0" size="small" />
                            </label>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* ═══════════════════════════════════════════════
   iOS DESIGN SYSTEM — PrimeVue Dialog Override
   ═══════════════════════════════════════════════ */

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

/* Navigation Bar */
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
    color: #6B7280;
    font-weight: 400;
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

/* Scrollable Body */
.ios-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}

/* Sections */
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

.ios-error {
    color: #FF3B30;
}

/* iOS Grouped Card */
.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
}

/* iOS Row */
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
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #8E8E93;
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

/* Textarea row */
.ios-row-textarea {
    align-items: flex-start;
    padding: 8px 16px;
}

/* Dual date row */
.ios-row-dates {
    gap: 4px;
}

.ios-row-dates .ios-row-control {
    flex: 0 0 150px;
    width: 150px;
}

.ios-date-separator {
    color: #8E8E93;
    font-size: 14px;
    flex-shrink: 0;
}

/* ─── PrimeVue overrides ─── */

/* Select */
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
    padding: 4px 2px 4px 8px;
    font-size: 13px;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
}

/* DatePicker */
:deep(.ios-datepicker .p-datepicker) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

:deep(.ios-datepicker .p-inputtext) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: right;
    color: #8E8E93;
    font-size: 13px;
    padding: 4px 24px 4px 8px;
}

/* InputText inside ios-row-control */
:deep(.ios-row-input.p-inputtext),
:deep(.ios-row-input) {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    text-align: right;
    color: #1c1c1e !important;
    font-size: 13px;
    padding: 4px 2px 4px 8px;
    width: 100%;
}

:deep(.ios-row-input.p-inputtext:focus),
:deep(.ios-row-input:focus) {
    outline: none !important;
    box-shadow: none !important;
}

/* Textarea inside ios-row-textarea */
.ios-textarea {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    color: #3a3a3c;
    font-size: 13px;
    flex: 1;
    min-width: 0;
    resize: none;
}

.ios-textarea:focus {
    outline: none !important;
    box-shadow: none !important;
}

/* Editor inside ios-card */
.ios-card :deep(.p-editor) {
    border: none;
}

.ios-card :deep(.p-editor .ql-toolbar) {
    border: none;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-card :deep(.p-editor .ql-container) {
    border: none;
}

/* ToggleSwitch iOS green */
:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
}
</style>

<!-- Unscoped: targets teleported Dialog elements at body level -->
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
