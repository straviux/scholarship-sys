<script setup>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    visible: { type: Boolean, default: false },
    course: { type: Object, default: null },
    scholarshipProgramsOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:visible', 'saved']);

const isEdit = computed(() => !!props.course);
const modalTitle = computed(() => isEdit.value ? 'Edit Course' : 'New Course');

const processing = ref(false);
const errors = reactive({});
const showDescription = ref(false);

const form = reactive({
    name: '',
    shortname: '',
    field_of_study: '',
    description: '',
    remarks: '',
    start_date: '',
    end_date: '',
    scholarship_program_id: '',
    is_active: 1,
});

const resetForm = () => {
    Object.assign(form, {
        name: '', shortname: '', field_of_study: '', description: '',
        remarks: '', start_date: '', end_date: '',
        scholarship_program_id: '', is_active: 1,
    });
    Object.keys(errors).forEach(k => delete errors[k]);
};

// Populate form when editing
watch(() => props.visible, (val) => {
    if (val) {
        dragOffset.value = { x: 0, y: 0 };
        Object.keys(errors).forEach(k => delete errors[k]);
        if (props.course) {
            form.name = props.course.name || '';
            form.shortname = props.course.shortname || '';
            form.field_of_study = props.course.field_of_study || '';
            form.description = props.course.description || '';
            form.remarks = props.course.remarks || '';
            form.start_date = props.course.start_date || '';
            form.end_date = props.course.end_date || '';
            form.scholarship_program_id = props.course.scholarship_program_id || '';
            form.is_active = props.course.is_active ?? 1;
            showDescription.value = !!props.course.description;
        } else {
            resetForm();
            showDescription.value = false;
        }
    }
});

const submit = async () => {
    if (processing.value) return;
    processing.value = true;
    Object.keys(errors).forEach(k => delete errors[k]);

    try {
        let response;
        if (isEdit.value) {
            response = await axios.put(route('courses.update', props.course.id), { ...form });
        } else {
            response = await axios.post(route('courses.store'), { ...form });
        }
        toast.success(response.data.message, { position: toast.POSITION.TOP_RIGHT });
        emit('saved', response.data.course);
        closeModal();
        if (!isEdit.value) resetForm();
    } catch (err) {
        if (err.response?.status === 422) {
            Object.assign(errors, err.response.data.errors || {});
        } else {
            toast.error('Something went wrong', { position: toast.POSITION.TOP_RIGHT });
        }
    } finally {
        processing.value = false;
    }
};

const closeModal = () => {
    emit('update:visible', false);
};

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
                <!-- iOS Navigation Bar (drag handle) -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="closeModal" v-tooltip.bottom="'Close'">
                        <i class="pi pi-times"></i>
                    </button>
                    <span class="ios-nav-title">{{ modalTitle }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="processing"
                        v-tooltip.bottom="isEdit ? 'Save' : 'Add'">
                        <i v-if="processing" class="pi pi-spin pi-spinner"></i>
                        <template v-else>{{ isEdit ? 'Save' : 'Add' }}</template>
                    </button>
                </div>

                <div class="ios-body">
                    <!-- PROGRAM SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Scholarship Program</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <i class="pi pi-bookmark-fill" style="color: #007AFF; font-size: 13px;"></i>
                                    Program
                                </div>
                                <div class="ios-row-control">
                                    <Select v-model="form.scholarship_program_id" :options="scholarshipProgramsOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select Program" showClear
                                        class="ios-select" />
                                </div>
                            </div>
                        </div>
                        <div v-if="errors.scholarship_program_id" class="ios-section-footer ios-error">
                            {{ errors.scholarship_program_id?.[0] || errors.scholarship_program_id }}
                        </div>
                    </div>

                    <!-- COURSE DETAILS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Course Details</div>
                        <div class="ios-card">
                            <!-- Name -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-graduation-cap" style="color: #AF52DE; font-size: 13px;"></i>
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.name" placeholder="Course name" class="ios-row-input" />
                                </div>
                            </div>
                            <!-- Shortname -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <i class="pi pi-tag" style="color: #FF9500; font-size: 13px;"></i>
                                    Shortname
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.shortname" placeholder="e.g. BSIT" class="ios-row-input" />
                                </div>
                            </div>
                            <!-- Field of Study -->
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <i class="pi pi-book" style="color: #5856D6; font-size: 13px;"></i>
                                    Field of Study
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.field_of_study" placeholder="e.g. Information Technology"
                                        class="ios-row-input" />
                                </div>
                            </div>
                        </div>
                        <div v-if="errors.name" class="ios-section-footer ios-error">
                            {{ errors.name?.[0] || errors.name }}
                        </div>
                        <div v-if="errors.shortname" class="ios-section-footer ios-error">
                            {{ errors.shortname?.[0] || errors.shortname }}
                        </div>
                        <div v-if="errors.field_of_study" class="ios-section-footer ios-error">
                            {{ errors.field_of_study?.[0] || errors.field_of_study }}
                        </div>
                    </div>

                    <!-- DESCRIPTION SECTION -->
                    <div class="ios-section">
                        <label class="ios-section-label"
                            style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                            Description
                            <ToggleSwitch v-model="showDescription" />
                        </label>
                        <div v-if="showDescription" class="ios-card" style="padding: 10px 16px;">
                            <Textarea v-model="form.description" rows="3" class="w-full"
                                style="border: none; resize: vertical;" placeholder="Course description (optional)" />
                        </div>
                        <div v-if="errors.description" class="ios-section-footer ios-error">
                            {{ errors.description?.[0] || errors.description }}
                        </div>
                    </div>

                    <!-- SCHEDULE SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Schedule</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-dates">
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    Start Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.start_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                                <span class="ios-date-separator">—</span>
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    End Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.end_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
                            <!-- <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <i class="pi pi-calendar" style="color: #FF3B30; font-size: 13px;"></i>
                                    End Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.end_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div> -->
                        </div>
                        <div v-if="errors.start_date" class="ios-section-footer ios-error">
                            {{ errors.start_date?.[0] || errors.start_date }}
                        </div>
                        <div v-if="errors.end_date" class="ios-section-footer ios-error">
                            {{ errors.end_date?.[0] || errors.end_date }}
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
                        <div v-if="errors.remarks" class="ios-section-footer ios-error">
                            {{ errors.remarks?.[0] || errors.remarks }}
                        </div>
                    </div>

                    <!-- STATUS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Status</div>
                        <div class="ios-card">
                            <label class="ios-row ios-row-last" style="cursor: pointer;">
                                <div class="ios-row-label">
                                    <i class="pi pi-check-circle" style="color: #34C759; font-size: 13px;"></i>
                                    Active
                                </div>
                                <ToggleSwitch v-model="form.is_active" :trueValue="1" :falseValue="0" />
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

/* iOS Modal Shell */
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

/* Dual date row: label + picker + label + picker */
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

/* ═══════════════════════════════════════════════
   STYLE OVERRIDES FOR PRIMEVUE INSIDE iOS CARD
   ═══════════════════════════════════════════════ */

/* Select inside ios-row */
:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    text-align: left;
    padding: 0;
    width: 100%;
    max-width: 100%;
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

/* DatePicker inside ios-row */
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

/* Editor overrides inside ios-card */
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
