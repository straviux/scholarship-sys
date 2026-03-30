<template>
    <Dialog :visible="visible" modal :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }"
        @update:visible="val => emit('update:visible', val)">
        <template #container>
            <div class="ios-modal" :style="[{ width: '700px' }, dragStyle]">
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">{{ mode === 'add' ? 'Add Scholarship Record' : 'Edit Scholarship Record'
                        }}</span>
                    <button class="ios-nav-btn ios-nav-action" @click.stop="submitRecord" :disabled="processing">
                        <i class="pi pi-check"></i>
                    </button>
                </div>
                <div class="ios-body">
                    <div style="display: flex; flex-direction: column; gap: 12px; padding: 16px 0;">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Program <span v-if="isG12Record"
                                        class="text-xs text-gray-500">(Optional for G12)</span><span
                                        v-else>*</span></label>
                                <ProgramSelect v-model="form.program_id" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">School <span v-if="isG12Record"
                                        class="text-xs text-gray-500">(Optional for G12)</span><span
                                        v-else>*</span></label>
                                <SchoolSelect v-model="form.school_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Course <span v-if="isG12Record"
                                        class="text-xs text-gray-500">(N/A for G12)</span><span v-else>*</span></label>
                                <CourseSelect v-model="form.course_id"
                                    :scholarship-program-id="typeof form.program_id === 'object' ? form.program_id?.id : form.program_id"
                                    :disabled="isG12Record" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Year Level *</label>
                                <YearLevelSelect v-model="form.year_level" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="ios-form-group">
                                <label class="ios-label">Academic Year <span v-if="isG12Record"
                                        class="text-xs text-gray-500">(Optional for G12)</span><span
                                        v-else>*</span></label>
                                <AcademicYearSelect v-model="form.academic_year" />
                            </div>
                            <div class="ios-form-group">
                                <label class="ios-label">Term <span v-if="isG12Record"
                                        class="text-xs text-gray-500">(Optional for G12)</span><span
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
                            <label class="ios-label">Status</label>
                            <Select v-model="form.unified_status" :options="statusOptions" optionLabel="label"
                                optionValue="value" placeholder="Select Status" fluid />
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Grant Provision</label>
                            <Select v-model="form.grant_provision" :options="grantProvisionOptions" optionLabel="label"
                                optionValue="value" placeholder="Select Grant Provision" fluid showClear />
                        </div>

                        <div class="ios-form-group">
                            <label class="ios-label">Remarks</label>
                            <InputText v-model="form.remarks" placeholder="Enter remarks" fluid />
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useDraggableModal } from '@/composables/useDraggableModal';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import ProgramSelect from '@/Components/selects/ProgramSelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import YearLevelSelect from '@/Components/selects/YearLevelSelect.vue';
import AcademicYearSelect from '@/Components/selects/AcademicYearSelect.vue';
import TermSelect from '@/Components/selects/TermSelect.vue';
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';

const props = defineProps({
    visible: { type: Boolean, default: false },
    mode: { type: String, default: 'add' }, // 'add' or 'edit'
    record: { type: Object, default: null },
    profileId: { type: [Number, String], required: true }
});

const emit = defineEmits(['update:visible', 'success']);

const { dragStyle, onDragStart, resetDrag } = useDraggableModal();

const { statusOptions: rawStatusOptions } = useScholarshipStatus();
const statusOptions = computed(() => rawStatusOptions.value.filter(s => s.value !== 'unknown'));

const grantProvisionOptions = [
    { label: 'Matriculation', value: 'Matriculation' },
    { label: 'RLE', value: 'RLE' },
    { label: 'Tuition', value: 'Tuition' },
    { label: 'RLE and Tuition', value: 'RLE and Tuition' }
];

const processing = ref(false);
const form = ref(getDefaultForm());

function getDefaultForm() {
    return {
        grant_id: null,
        program_id: null,
        school_id: null,
        course_id: null,
        year_level: null,
        academic_year: null,
        term: null,
        date_filed: null,
        date_approved: null,
        unified_status: 'pending',
        grant_provision: null,
        remarks: null
    };
}

const isG12Record = computed(() => {
    const val = typeof form.value.year_level === 'object' ? form.value.year_level?.value : form.value.year_level;
    return val === 'G12';
});

// Populate form when editing
watch(() => props.visible, async (val) => {
    if (val && props.mode === 'edit' && props.record) {
        form.value = {
            grant_id: props.record.id,
            program_id: props.record.program || props.record.program_id,
            school_id: props.record.school || props.record.school_id,
            course_id: props.record.course || props.record.course_id,
            year_level: props.record.year_level,
            academic_year: props.record.academic_year,
            term: props.record.term,
            date_filed: props.record.date_filed ? new Date(props.record.date_filed) : null,
            date_approved: props.record.date_approved ? new Date(props.record.date_approved) : null,
            unified_status: props.record.unified_status || 'pending',
            grant_provision: props.record.grant_provision || null,
            remarks: props.record.remarks
        };
        await nextTick();
    } else if (val && props.mode === 'add') {
        form.value = getDefaultForm();
        form.value.date_filed = new Date();
    }
});

const close = () => {
    resetDrag();
    emit('update:visible', false);
    form.value = getDefaultForm();
};

const formatDateForAPI = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const submitRecord = async () => {
    processing.value = true;
    try {
        const yearLevelValue = typeof form.value.year_level === 'object' ? form.value.year_level?.value : form.value.year_level;
        const isG12 = yearLevelValue === 'G12';

        if (!isG12) {
            if (!form.value.program_id) { toast.error('Program is required'); processing.value = false; return; }
            if (!form.value.school_id) { toast.error('School is required'); processing.value = false; return; }
            if (!form.value.academic_year) { toast.error('Academic Year is required'); processing.value = false; return; }
            if (!form.value.term) { toast.error('Term is required'); processing.value = false; return; }
        }
        if (!yearLevelValue) { toast.error('Year Level is required'); processing.value = false; return; }

        const toUpperCase = (value) => {
            if (!value || typeof value !== 'string') return value;
            return value.toUpperCase();
        };

        const formData = {
            profile_id: props.profileId,
            program_id: typeof form.value.program_id === 'object' ? form.value.program_id?.id : form.value.program_id,
            school_id: typeof form.value.school_id === 'object' ? form.value.school_id?.id : form.value.school_id,
            course_id: typeof form.value.course_id === 'object' ? form.value.course_id?.id : form.value.course_id,
            year_level: yearLevelValue,
            academic_year: typeof form.value.academic_year === 'object' ? form.value.academic_year?.value : form.value.academic_year,
            term: typeof form.value.term === 'object' ? form.value.term?.value : form.value.term,
            date_filed: formatDateForAPI(form.value.date_filed),
            date_approved: formatDateForAPI(form.value.date_approved),
            unified_status: form.value.unified_status,
            grant_provision: toUpperCase(form.value.grant_provision),
            remarks: toUpperCase(form.value.remarks),
            yakap_category: form.value.yakap_category || null,
            yakap_location: form.value.yakap_location || null,
        };

        if (props.mode === 'add') {
            await axios.post(route('scholarship_records.store'), formData);
            toast.success('Scholarship record added successfully');
        } else {
            await axios.put(route('scholarship_records.update', form.value.grant_id), formData);
            toast.success('Scholarship record updated successfully');
        }

        close();
        emit('success');
    } catch (error) {
        console.error('Error submitting scholarship record:', error);
        const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Failed to save scholarship record';
        toast.error(typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg));
    } finally {
        processing.value = false;
    }
};
</script>

<style scoped>
.ios-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.ios-nav-bar {
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 48px;
    cursor: grab;
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
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.ios-nav-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}


.ios-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0 16px;
}


.ios-form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ios-label {
    font-size: 13px;
    font-weight: 500;
    color: #3C3C43;
    padding-left: 2px;
}

:deep(.p-inputtext),
:deep(.p-select) {
    border-radius: 10px;
}

:deep(.p-datepicker) {
    border-radius: 10px;
}

/* Dark mode */
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

:global(.dark) .ios-nav-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

:global(.dark) .ios-body {
    color: #d1d5db;
}

:global(.dark) .ios-label {
    color: #d1d5db;
}
</style>

<style>
.ios-dialog-root.p-dialog {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.ios-dialog-root .p-dialog-content {
    padding: 0 !important;
    background: transparent !important;
}

.ios-dialog-mask {
    background: rgba(0, 0, 0, 0.4);
}
</style>
