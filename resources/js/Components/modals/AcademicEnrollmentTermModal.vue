<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '720px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">{{ mode === 'add' ? 'Add Academic Term' : 'Edit Academic Term' }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click.stop="submitTerm" :disabled="processing">
                        <AppIcon name="check" :size="14" />
                    </button>
                </div>

                <div class="ios-body">
                    <div style="display: flex; flex-direction: column; gap: 12px; padding: 16px 0;">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Year Level *</label>
                                <YearLevelSelect v-model="form.year_level" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Status</label>
                                <Select v-model="form.unified_status" :options="statusOptions" optionLabel="label"
                                    optionValue="value" placeholder="Select Status" fluid />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Academic Year <span v-if="isG12Term"
                                        class="text-xs text-gray-500 dark:text-gray-400">(Optional for G12)</span><span
                                        v-else>*</span></label>
                                <AcademicYearSelect v-model="form.academic_year" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Term <span v-if="isG12Term"
                                        class="text-xs text-gray-500 dark:text-gray-400">(Optional for G12)</span><span
                                        v-else>*</span></label>
                                <TermSelect v-model="form.term" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Date Filed</label>
                                <DatePicker v-model="form.date_filed" dateFormat="yy-mm-dd" showIcon fluid />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Date Approved</label>
                                <DatePicker v-model="form.date_approved" dateFormat="yy-mm-dd" showIcon fluid />
                            </div>
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Grant Provision</label>
                            <Select v-model="form.grant_provision" :options="grantProvisionOptions" optionLabel="label"
                                optionValue="value" placeholder="Select Grant Provision" fluid showClear />
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Remarks</label>
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
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { toast } from '@/utils/toast';
import { useDraggableModal } from '@/composables/useDraggableModal';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';
import { useSystemOptions } from '@/composables/useSystemOptions';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    mode: { type: String, default: 'add' },
    term: { type: Object, default: null },
    enrollmentId: { type: [Number, String], default: null },
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();
const { statusOptions: rawStatusOptions } = useScholarshipStatus();
const allowedTermStatuses = new Set(['pending', 'approved', 'active', 'completed', 'denied', 'withdrawn', 'loa', 'suspended', 'unknown']);

const processing = ref(false);
const grantProvisionOptions = useSystemOptions('grant_provision');
const statusOptions = computed(() => rawStatusOptions.value.filter((option) => allowedTermStatuses.has(option.value) && option.value !== 'unknown'));
const form = ref(getDefaultForm());

function getDefaultForm() {
    return {
        year_level: null,
        academic_year: null,
        term: null,
        date_filed: null,
        date_approved: null,
        unified_status: 'pending',
        grant_provision: null,
        remarks: '',
    };
}

const isG12Term = computed(() => {
    const yearLevel = typeof form.value.year_level === 'object'
        ? form.value.year_level?.value
        : form.value.year_level;

    return yearLevel === 'G12';
});

watch(() => props.visible, async (val) => {
    if (!val) {
        return;
    }

    if (props.mode === 'edit' && props.term?.id) {
        await loadTerm(props.term.id);
        return;
    }

    form.value = {
        ...getDefaultForm(),
        date_filed: new Date(),
    };
    await nextTick();
});

const formatDateForApi = (value) => {
    if (!value) {
        return null;
    }

    const date = new Date(value);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const normalizeOptionValue = (value) => {
    if (!value) {
        return null;
    }

    return typeof value === 'object' ? value.value ?? value.label ?? null : value;
};

const getEditorTextContent = (value) => {
    const html = String(value ?? '').trim();

    if (!html) {
        return '';
    }

    if (typeof DOMParser !== 'undefined') {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return (doc.body.textContent || '').replace(/\u00a0/g, ' ').trim();
    }

    return html.replace(/<[^>]+>/g, ' ').replace(/&nbsp;/gi, ' ').trim();
};

const normalizeEditorHtml = (value) => {
    const html = String(value ?? '').trim();
    return getEditorTextContent(html) ? html : null;
};

const fillForm = (term) => {
    form.value = {
        year_level: term?.year_level ?? null,
        academic_year: term?.academic_year ?? null,
        term: term?.term ?? null,
        date_filed: term?.date_filed ? new Date(term.date_filed) : null,
        date_approved: term?.date_approved ? new Date(term.date_approved) : null,
        unified_status: term?.unified_status ?? 'pending',
        grant_provision: term?.grant_provision ?? null,
        remarks: term?.remarks ?? '',
    };
};

const loadTerm = async (termId) => {
    processing.value = true;

    try {
        const response = await axios.get(route('academic-enrollment-terms.show', termId));
        fillForm(response.data?.data ?? props.term);
        await nextTick();
    } catch (error) {
        console.error('Failed to load academic term:', error);
        toast.error('Failed to load academic term details.');
        close();
    } finally {
        processing.value = false;
    }
};

const close = () => {
    resetDrag();
    emit('update:visible', false);
    form.value = getDefaultForm();
};

const extractErrorMessage = (error, fallback) => {
    const errors = error?.response?.data?.errors;
    if (errors && typeof errors === 'object') {
        const firstError = Object.values(errors).flat()[0];
        if (firstError) {
            return firstError;
        }
    }

    return error?.response?.data?.message || fallback;
};

const submitTerm = async () => {
    const yearLevel = normalizeOptionValue(form.value.year_level);
    const academicYear = normalizeOptionValue(form.value.academic_year);
    const termValue = normalizeOptionValue(form.value.term);

    if (!yearLevel) {
        toast.error('Year level is required.');
        return;
    }

    if (!isG12Term.value && !academicYear) {
        toast.error('Academic year is required.');
        return;
    }

    if (!isG12Term.value && !termValue) {
        toast.error('Term is required.');
        return;
    }

    if (props.mode === 'add' && !props.enrollmentId) {
        toast.error('Select an academic enrollment before adding a term.');
        return;
    }

    processing.value = true;

    try {
        const payload = {
            year_level: yearLevel,
            academic_year: academicYear,
            term: termValue,
            date_filed: formatDateForApi(form.value.date_filed),
            date_approved: formatDateForApi(form.value.date_approved),
            unified_status: normalizeOptionValue(form.value.unified_status) ?? 'pending',
            grant_provision: normalizeOptionValue(form.value.grant_provision),
            remarks: normalizeEditorHtml(form.value.remarks),
        };

        const response = props.mode === 'add'
            ? await axios.post(route('academic-enrollment-terms.store', props.enrollmentId), payload)
            : await axios.put(route('academic-enrollment-terms.update', props.term.id), payload);

        toast.success(response.data?.message || 'Academic term saved successfully.');
        close();
        emit('success');
    } catch (error) {
        console.error('Failed to save academic term:', error);
        toast.error(extractErrorMessage(error, 'Failed to save academic term.'));
    } finally {
        processing.value = false;
    }
};
</script>