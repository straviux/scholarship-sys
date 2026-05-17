<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    profile: Object,
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'success']);

const remarksForm = useForm({
    remarks: ''
});

// When showing, populate form
const onShow = () => {
    if (props.profile) {
        remarksForm.remarks = props.profile.remarks || '';
    }
};

watch(() => props.show, (visible) => {
    if (visible) {
        onShow();
    }
});

const close = () => {
    emit('update:show', false);
    remarksForm.reset();
};

const submit = () => {
    remarksForm.post(route('applicants.update-remarks', props.profile.profile_id), {
        onSuccess: () => {
            toast.success('Remarks updated successfully!');
            close();
            emit('success');
            if (props.refreshActivityLogs) props.refreshActivityLogs();
        },
        onError: () => {
            toast.error('Failed to update remarks');
        }
    });
};
</script>

<template>
    <IosModal :visible="show" title="Remarks" width="560px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" action-label="Save"
        :loading="remarksForm.processing" @action="submit" @update:visible="val => !val && close()">
        <div v-if="profile">
                    <!-- Applicant Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicant</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">{{ profile.last_name }}, {{ profile.first_name }}</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ profile.contact_no }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Input -->
                    <div class="ios-section">
                        <div class="ios-section-label">Remarks</div>
                        <div class="ios-card">
                            <Editor v-model="remarksForm.remarks" editorStyle="height: 150px">
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
                        <div v-if="remarksForm.errors.remarks" class="ios-section-footer ios-error">
                            {{ remarksForm.errors.remarks }}
                        </div>
                    </div>

                    <!-- Bottom spacing -->
                    <div style="height: 20px;"></div>
        </div>
    </IosModal>
</template>

