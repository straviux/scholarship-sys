<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import axios from 'axios';
import { toast } from '@/utils/toast';

const props = defineProps({
    visible: Boolean,
    program: Object,
});
const emit = defineEmits(['update:visible', 'saved']);

const isEdit = computed(() => !!props.program?.id);
const processing = ref(false);

const form = ref({
    name: '',
    shortname: '',
    description: '',
    remarks: '',
    is_active: 1,
});
const dateStart = ref(null);
const dateEnd = ref(null);
const errors = ref({});

const populate = () => {
    form.value.name = props.program?.name || '';
    form.value.shortname = props.program?.shortname || '';
    form.value.description = props.program?.description || '';
    form.value.remarks = props.program?.remarks || '';
    form.value.is_active = props.program?.is_active ?? 1;
    dateStart.value = props.program?.start_date ? new Date(props.program.start_date) : null;
    dateEnd.value = props.program?.end_date ? new Date(props.program.end_date) : null;
    errors.value = {};
    dragOffset.value = { x: 0, y: 0 };
};

watch(() => props.visible, (val) => {
    if (val) populate();
});

const close = () => emit('update:visible', false);

const submit = async () => {
    if (processing.value) return;
    processing.value = true;
    errors.value = {};

    const payload = {
        ...form.value,
        start_date: dateStart.value ? new Date(dateStart.value).toISOString().split('T')[0] : null,
        end_date: dateEnd.value ? new Date(dateEnd.value).toISOString().split('T')[0] : null,
    };

    try {
        let res;
        if (isEdit.value) {
            res = await axios.put(route('scholarshipprograms.update', props.program.id), payload);
        } else {
            res = await axios.post(route('scholarshipprograms.store'), payload);
        }
        toast.success(res.data.message || 'Saved', { position: toast.POSITION.TOP_RIGHT });
        emit('saved', res.data.program);
        close();
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            toast.error('Failed to save program', { position: toast.POSITION.TOP_RIGHT });
        }
    } finally {
        processing.value = false;
    }
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '560px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, a, input, textarea, .p-editor, .p-datepicker, .p-inputtext, .p-toggleswitch')) return;
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
    <Dialog :visible="visible" modal @update:visible="val => !val && close()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">

                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" type="button" @click="close">
                        <AppIcon name="times" :size="18" />
                    </button>
                    <span class="ios-nav-title">{{ isEdit ? 'Edit Program' : 'New Program' }}</span>
                    <button class="ios-nav-btn ios-nav-action" type="button" @click="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : (isEdit ? 'Save' : 'Add') }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body">

                    <!-- Program Details -->
                    <div class="ios-section">
                        <div class="ios-section-label">Program Details</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Name</span>
                                <InputText v-model="form.name" placeholder="Program name" class="ios-row-input" />
                            </div>
                            <small v-if="errors.name"
                                style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                {{ errors.name[0] }}
                            </small>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Shortname</span>
                                <InputText v-model="form.shortname" placeholder="e.g. PROG" class="ios-row-input" />
                            </div>
                            <small v-if="errors.shortname"
                                style="color:#FF3B30; padding: 2px 16px 6px; display:block; font-size:12px;">
                                {{ errors.shortname[0] }}
                            </small>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="ios-section">
                        <div class="ios-section-label">Description</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last" style="align-items: flex-start; padding: 10px 16px;">
                                <Textarea v-model="form.description" placeholder="Program description..." autoResize
                                    rows="3"
                                    style="flex: 1; border: none; outline: none; resize: none; font-size: 14px; color: #1c1c1e; background: transparent; box-shadow: none; padding: 2px 0;" />
                            </div>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="ios-section">
                        <div class="ios-section-label">Schedule</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Start Date</span>
                                <DatePicker v-model="dateStart" dateFormat="M dd, yy" showIcon iconDisplay="input"
                                    placeholder="Select date" class="ios-datepicker" style="flex: 1;" />
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">End Date</span>
                                <DatePicker v-model="dateEnd" dateFormat="M dd, yy" showIcon iconDisplay="input"
                                    placeholder="Select date" class="ios-datepicker" style="flex: 1;" />
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card" style="padding: 8px 12px;">
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
                    </div>

                    <!-- Status -->
                    <div class="ios-section">
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Active</span>
                                <ToggleSwitch v-model="form.is_active" :trueValue="1" :falseValue="0" size="small"
                                    style="--p-toggleswitch-checked-background: #34C759;" />
                            </div>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Label (slightly lighter than default) */
:deep(.ios-row-label) {
    color: #8E8E93 !important;
    font-weight: 500;
}

/* InputText inside ios-row */
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
    color: #1c1c1e !important;
    font-size: 13px;
    padding: 4px 24px 4px 8px;
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

/* ToggleSwitch green */
:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #34C759 !important;
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
