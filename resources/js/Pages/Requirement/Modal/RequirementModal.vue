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

.ios-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
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

.ios-error {
    color: #FF3B30;
}

.ios-card {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #E5E5EA;
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

.ios-row-textarea {
    align-items: flex-start;
    padding: 8px 16px;
}

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
