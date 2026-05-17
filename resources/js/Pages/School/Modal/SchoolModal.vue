<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';
import AppIcon from '@/Components/ui/AppIcon.vue';
import IosModal from '@/Components/ui/IosModal.vue';

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
</script>

<template>
    <IosModal :visible="visible" :title="modalTitle" width="580px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" :action-label="isEdit ? 'Save' : 'Add'"
        :loading="form.processing" @action="submit" @update:visible="val => !val && closeModal()">

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
    </IosModal>
</template>
