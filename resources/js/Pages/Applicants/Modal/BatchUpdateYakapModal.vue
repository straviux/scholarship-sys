<script setup>
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from '@/utils/toast';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    show: Boolean,
    selectedRows: { type: Array, default: () => [] },
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'success']);

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' },
];

const batchYakapForm = useForm({
    yakap_category: '',
    yakap_location: '',
});

const close = () => {
    emit('update:show', false);
    batchYakapForm.reset();
};

const handleCategoryChange = () => {
    batchYakapForm.yakap_location = null;
};

const submit = () => {
    if (props.selectedRows.length === 0) {
        toast.error('No applicants selected');
        return;
    }
    if (!batchYakapForm.yakap_category) {
        toast.error('Please select a YAKAP category');
        return;
    }

    let yakapLocation = batchYakapForm.yakap_location;
    if (yakapLocation && typeof yakapLocation === 'object') yakapLocation = yakapLocation.name || '';

    const profileIds = props.selectedRows.map(row => row.profile_id);

    axios.post(route('scholarship-record.batch-update-yakap'), {
        profile_ids: profileIds,
        yakap_category: batchYakapForm.yakap_category,
        yakap_location: yakapLocation || null,
    }).then(() => {
        close();
        toast.success(`YAKAP category updated for ${profileIds.length} applicant(s)!`);
        emit('success');
    }).catch(error => {
        toast.error('Failed to update YAKAP categories');
        console.error(error.response?.data || error);
    });
};
</script>

<template>
    <IosModal :visible="show" title="Batch Update YAKAP" width="680px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" action-label="Update All"
        :action-disabled="!batchYakapForm.yakap_category" @action="submit" @update:visible="val => !val && close()">
                    <!-- Selection Summary -->
                    <div class="ios-section">
                        <div class="ios-section-label">Selection Summary</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Selected Applicants</span>
                                <span class="ios-badge">{{ selectedRows.length }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label" style="color: #8E8E93; font-size: 13px;">
                                    Batch update will apply YAKAP category to all selected applicants
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Applicants Preview -->
                    <div class="ios-section">
                        <div class="ios-section-label">Applicants</div>
                        <div class="ios-card ios-applicant-list">
                            <div v-for="(row, idx) in selectedRows.slice(0, 8)" :key="idx" class="ios-row">
                                <span class="ios-row-label">{{ row.last_name }}, {{ row.first_name }}</span>
                            </div>
                            <div v-if="selectedRows.length > 8" class="ios-row ios-row-last"
                                style="color: #8E8E93; font-size: 13px; justify-content: center;">
                                ... and {{ selectedRows.length - 8 }} more
                            </div>
                        </div>
                    </div>

                    <!-- YAKAP Category Selection -->
                    <div class="ios-section">
                        <div class="ios-section-label">Update Options</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">YAKAP Category</span>
                                <div class="ios-row-control ios-select">
                                    <Select v-model="batchYakapForm.yakap_category" :options="yakapCategoryOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select Category"
                                        @change="handleCategoryChange" />
                                </div>
                            </div>

                            <!-- Municipality (YAKAP Field) -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-field'" class="ios-row">
                                <span class="ios-row-label">Municipality</span>
                                <div class="ios-row-control ios-select">
                                    <MunicipalitySelect v-model="batchYakapForm.yakap_location"
                                        placeholder="Select Municipality" :clearable="false" />
                                </div>
                            </div>

                            <!-- School (YAKAP School) -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-school'" class="ios-row">
                                <span class="ios-row-label">School</span>
                                <div class="ios-row-control ios-select">
                                    <SchoolSelect v-model="batchYakapForm.yakap_location" placeholder="Select School"
                                        :clearable="false" />
                                </div>
                            </div>

                            <!-- Capitol description -->
                            <div v-if="batchYakapForm.yakap_category === 'yakap-capitol'" class="ios-row ios-row-last">
                                <span style="font-size: 13px; color: #8E8E93;">No specific location required</span>
                            </div>
                        </div>
                        <div class="ios-section-footer">
                            Select the YAKAP category to apply to all selected applicants
                        </div>
                    </div>

                    <!-- Bottom spacing -->
                    <div style="height: 20px;"></div>
    </IosModal>
</template>

