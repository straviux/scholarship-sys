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
    { label: 'YAKAP Capitol', value: 'yakap-capitol', icon: 'pi pi-building' },
    { label: 'YAKAP School', value: 'yakap-school', icon: 'pi pi-graduation-cap' },
    { label: 'YAKAP Field', value: 'yakap-field', icon: 'pi pi-map-marker' }
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
                    <button class="ios-nav-btn ios-nav-cancel" @click="handleCancel"><i
                            class="pi pi-times"></i></button>
                    <span class="ios-nav-title">YAKAP Category</span>
                    <button class="ios-nav-btn ios-nav-action" @click="handleConfirm" :disabled="!canConfirm"><i
                            class="pi pi-arrow-right"></i></button>
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
                                    <i :class="opt.icon" style="color: #007AFF; font-size: 16px;"></i>
                                    <span class="ios-row-label">{{ opt.label }}</span>
                                </div>
                                <i v-if="selectedCategory === opt.value" class="pi pi-check"
                                    style="color: #007AFF; font-size: 14px;"></i>
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

<style scoped>
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

.ios-nav-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
    flex-shrink: 0;
    cursor: grab;
    user-select: none;
}

.ios-nav-bar:active {
    cursor: grabbing;
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
    font-size: 17px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: opacity 0.15s;
}

.ios-nav-btn:hover {
    opacity: 0.6;
}

.ios-nav-cancel {
    left: 16px;
    color: #8E8E93;
    font-size: 20px;
}

.ios-nav-action {
    right: 16px;
    color: #374151;
    font-weight: 600;
}

.ios-nav-action:disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.ios-body {
    flex: 1;
    overflow-y: auto;
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
    font-size: 14px;
    color: #000;
    letter-spacing: -0.4px;
    font-weight: 500;
}
</style>

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
