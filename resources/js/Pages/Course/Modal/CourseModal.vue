<script setup>
import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';
import AppIcon from '@/Components/ui/AppIcon.vue';
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

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
</script>

<template>
    <IosModal :visible="visible" :title="modalTitle" width="580px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" :action-label="isEdit ? 'Save' : 'Add'"
        :loading="processing" @action="submit" @update:visible="val => !val && closeModal()">
                    <!-- PROGRAM SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Scholarship Program</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="bookmark-fill" style="color: #007AFF; font-size: 13px;" />
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
                                    <AppIcon name="graduation-cap" style="color: #AF52DE; font-size: 13px;" />
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.name" placeholder="Course name" class="ios-row-input" />
                                </div>
                            </div>
                            <!-- Shortname -->
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="tag" style="color: #FF9500; font-size: 13px;" />
                                    Shortname
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.shortname" placeholder="e.g. BSIT" class="ios-row-input" />
                                </div>
                            </div>
                            <!-- Field of Study -->
                            <div class="ios-row ios-row-last">
                                <div class="ios-row-label">
                                    <AppIcon name="book" style="color: #5856D6; font-size: 13px;" />
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
                                    <AppIcon name="calendar" style="color: #FF3B30; font-size: 13px;" />
                                    Start Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.start_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                                <span class="ios-date-separator">—</span>
                                <div class="ios-row-label">
                                    <AppIcon name="calendar" style="color: #FF3B30; font-size: 13px;" />
                                    End Date
                                </div>
                                <div class="ios-row-control">
                                    <DatePicker v-model="form.end_date" placeholder="Select date" showButtonBar
                                        dateFormat="M dd, yy" class="ios-datepicker" showIcon iconDisplay="input" />
                                </div>
                            </div>
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
                                    <AppIcon name="check-circle" style="color: #34C759; font-size: 13px;" />
                                    Active
                                </div>
                                <ToggleSwitch v-model="form.is_active" :trueValue="1" :falseValue="0" />
                            </label>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
    </IosModal>
</template>


<!-- Unscoped: targets teleported Dialog elements at body level -->
