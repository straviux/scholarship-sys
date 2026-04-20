<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';

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

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '460px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, textarea, select, a, .p-select, .p-checkbox, .p-autocomplete')) return;
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

// Reset drag position when modal opens
watch(() => props.visible, (val) => {
    if (val) {
        dragOffset.value = { x: 0, y: 0 };
    }
});
</script>

<template>
    <Dialog :visible="visible" modal @update:visible="val => !val && handleCancel()"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="handleCancel">
                        <AppIcon name="times" :size="14" />
                    </button>
                    <span class="ios-nav-title">YAKAP Category</span>
                    <button class="ios-nav-btn ios-nav-action" @click="handleConfirm" :disabled="!canConfirm">
                        <AppIcon name="arrow-right" :size="14" />
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body">
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
                </div>
            </div>
        </template>
    </Dialog>
</template>
