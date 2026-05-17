<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from '@/Components/ui/AppIcon.vue';
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    visible: Boolean,
    requirement: Object,
});

const emit = defineEmits(['update:visible', 'saved']);
const isEdit = computed(() => !!props.requirement);
const modalTitle = computed(() => isEdit.value ? 'Edit Requirement' : 'New Requirement');

const form = useForm({
    name: '',
    description: '',
    remarks: '',
    is_active: 1,
});

watch(() => props.visible, (val) => {
    if (val) {
        form.clearErrors();
        form.name = props.requirement?.name ?? '';
        form.description = props.requirement?.description ?? '';
        form.remarks = props.requirement?.remarks ?? '';
        form.is_active = props.requirement?.is_active ?? 1;
    }
});

const submit = () => {
    const opts = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(isEdit.value ? 'Requirement updated successfully.' : 'Requirement added successfully.');
            emit('saved');
        },
    };
    if (isEdit.value) {
        form.put(route('program_requirements.update', props.requirement.id), opts);
    } else {
        form.post(route('program_requirements.store'), opts);
    }
};

const closeModal = () => emit('update:visible', false);
</script>

<template>
    <IosModal :visible="visible" :title="modalTitle" width="520px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" :action-label="isEdit ? 'Save' : 'Add'"
        :loading="form.processing" @action="submit" @update:visible="val => !val && closeModal()">

                    <!-- REQUIREMENT DETAILS SECTION -->
                    <div class="ios-section">
                        <div class="ios-section-label">Requirement Details</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="ios-row-label">
                                    <AppIcon name="file-check" style="color: #007AFF; font-size: 13px;" />
                                    Name
                                </div>
                                <div class="ios-row-control">
                                    <InputText v-model="form.name" placeholder="Requirement name"
                                        class="ios-row-input" />
                                </div>
                            </div>
                            <div class="ios-row ios-row-last ios-row-textarea">
                                <div class="ios-row-label" style="align-self: flex-start; padding-top: 8px;">
                                    <AppIcon name="list" style="color: #5856D6; font-size: 13px;" />
                                    Description
                                </div>
                                <Textarea v-model="form.description" placeholder="Brief description (optional)" rows="2"
                                    autoResize class="ios-textarea" />
                            </div>
                        </div>
                        <div v-if="form.errors.name" class="ios-section-footer ios-error">{{ form.errors.name }}</div>
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
                                    <AppIcon name="check-circle" style="color: #34C759; font-size: 13px;" />
                                    Active
                                </div>
                                <ToggleSwitch v-model="form.is_active" :trueValue="1" :falseValue="0" size="small" />
                            </label>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
    </IosModal>
</template>
