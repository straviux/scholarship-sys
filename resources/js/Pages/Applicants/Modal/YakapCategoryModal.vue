<script setup>
import { ref, watch, computed } from 'vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'selected']);

const selectedCategory = ref('yakap-capitol');
const selectedLocation = ref(null);

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol', icon: 'building' },
    { label: 'YAKAP School', value: 'yakap-school', icon: 'graduation-cap' },
    { label: 'YAKAP Field', value: 'yakap-field', icon: 'map-pin' }
];

// Clear location when category is yakap-capitol
watch(selectedCategory, (newCategory) => {
    if (newCategory === 'yakap-capitol') {
        selectedLocation.value = null;
    }
});

const canConfirm = computed(() => {
    if (selectedCategory.value === 'yakap-capitol') return true;
    return !!selectedLocation.value;
});

const handleConfirm = () => {
    let locationValue = selectedLocation.value;
    if (locationValue) {
        if (typeof locationValue === 'object' && locationValue?.name) {
            locationValue = locationValue.name;
        }
    }

    emit('selected', {
        category: selectedCategory.value,
        location: locationValue || ''
    });
    emit('update:visible', false);
    resetForm();
};

const handleCancel = () => {
    emit('update:visible', false);
    resetForm();
};

const resetForm = () => {
    selectedCategory.value = 'yakap-capitol';
    selectedLocation.value = null;
};
</script>

<template>
    <IosModal :visible="visible" title="YAKAP Category" width="460px" max-width="calc(100vw - 24px)"
        body-style="padding: 0 16px;" :show-action="true" action-icon="arrow-right"
        :action-disabled="!canConfirm" @action="handleConfirm" @update:visible="val => !val && handleCancel()">
                    <!-- Description -->
                    <div class="ios-section">
                        <div class="ios-section-footer" style="padding: 0 16px;">
                            Select a YAKAP category for this new applicant.
                        </div>
                    </div>

                    <!-- Category Selection -->
                    <div class="ios-section">
                        <div class="ios-section-label">Category</div>
                        <div class="ios-card">
                            <div v-for="(opt, idx) in yakapCategoryOptions" :key="opt.value" class="ios-row"
                                :class="{ 'ios-row-last': idx === yakapCategoryOptions.length - 1 }"
                                style="cursor: pointer;" @click="selectedCategory = opt.value">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <AppIcon :name="opt.icon" :size="16" style="color: #007AFF;" />
                                    <span class="ios-row-label">{{ opt.label }}</span>
                                </div>
                                <AppIcon v-if="selectedCategory === opt.value" name="check" :size="14"
                                    style="color: #007AFF;" />
                            </div>
                        </div>
                    </div>

                    <!-- Municipality Selection for YAKAP Field -->
                    <div v-if="selectedCategory === 'yakap-field'" class="ios-section">
                        <div class="ios-section-label">Municipality</div>
                        <div class="ios-card" style="padding: 12px;">
                            <MunicipalitySelect v-model="selectedLocation" placeholder="Select Municipality"
                                class="w-full" :clearable="false" />
                        </div>
                        <div class="ios-section-footer">
                            Select the municipality for YAKAP Field assignment.
                        </div>
                    </div>

                    <!-- School Selection for YAKAP School -->
                    <div v-if="selectedCategory === 'yakap-school'" class="ios-section">
                        <div class="ios-section-label">School</div>
                        <div class="ios-card" style="padding: 12px;">
                            <SchoolSelect v-model="selectedLocation" placeholder="Select School" class="w-full"
                                :clearable="false" />
                        </div>
                        <div class="ios-section-footer">
                            Select the school for YAKAP School assignment.
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
    </IosModal>
</template>
