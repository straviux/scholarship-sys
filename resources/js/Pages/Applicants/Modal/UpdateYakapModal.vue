<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import MunicipalitySelect from '@/Components/selects/MunicipalitySelect.vue';
import SchoolSelect from '@/Components/selects/SchoolSelect.vue';

const props = defineProps({
    show: Boolean,
    profile: Object,
    refreshActivityLogs: Function,
});

const emit = defineEmits(['update:show', 'success']);

const yakapCategoryOptions = [
    { label: 'YAKAP Capitol', value: 'yakap-capitol' },
    { label: 'YAKAP School', value: 'yakap-school' },
    { label: 'YAKAP Field', value: 'yakap-field' },
];

const originalYakapCategory = ref('');
const originalYakapLocation = ref('');
const updateYakapForm = useForm({
    yakap_category: '',
    yakap_location: '',
});

const onShow = () => {
    if (!props.profile) return;
    const grants = Array.isArray(props.profile.scholarshipGrant) ? props.profile.scholarshipGrant : [];
    const grant = grants.length > 0 ? grants[0] : null;

    if (!grant) {
        axios.get(route('scholarship-record.get-or-create', props.profile.profile_id))
            .then(response => {
                const g = response.data;
                originalYakapCategory.value = g.yakap_category || 'yakap-capitol';
                originalYakapLocation.value = g.yakap_location || '';
                updateYakapForm.yakap_category = g.yakap_category || 'yakap-capitol';
                updateYakapForm.yakap_location = g.yakap_location || '';
            })
            .catch(() => toast.error('Failed to create scholarship record'));
    } else {
        originalYakapCategory.value = grant.yakap_category || 'yakap-capitol';
        originalYakapLocation.value = grant.yakap_location || '';
        updateYakapForm.yakap_category = grant.yakap_category || 'yakap-capitol';
        updateYakapForm.yakap_location = grant.yakap_location || '';
    }
};

const close = () => {
    emit('update:show', false);
    updateYakapForm.reset();
};

const handleCategoryChange = () => {
    updateYakapForm.yakap_location = null;
};

const submit = () => {
    if (!props.profile) return;
    const grants = Array.isArray(props.profile.scholarshipGrant) ? props.profile.scholarshipGrant : [];
    const grant = grants.length > 0 ? grants[0] : null;

    if (!grant || !grant.id) {
        toast.error('Unable to update: No scholarship record exists.');
        return;
    }

    const categoryChanged = updateYakapForm.yakap_category !== originalYakapCategory.value;
    const locationChanged = updateYakapForm.yakap_location !== originalYakapLocation.value;
    if (!categoryChanged && !locationChanged) { close(); return; }

    let yakapLocation = updateYakapForm.yakap_location;
    if (yakapLocation && typeof yakapLocation === 'object') yakapLocation = yakapLocation.name || '';

    axios.put(route('scholarship-record.update-yakap', grant.id), {
        yakap_category: updateYakapForm.yakap_category,
        yakap_location: yakapLocation || null,
    }).then(() => {
        close();
        toast.success('YAKAP category updated successfully!');
        emit('success');
        if (props.refreshActivityLogs) props.refreshActivityLogs();
    }).catch(error => {
        toast.error('Failed to update YAKAP category');
        console.error(error.response?.data || error);
    });
};

/* ── Drag ── */
const dragOffset = ref({ x: 0, y: 0 });
const dragStart = ref(null);
const modalStyle = computed(() => ({
    width: '520px',
    transform: `translate(${dragOffset.value.x}px, ${dragOffset.value.y}px)`,
}));

function onDragStart(e) {
    if (e.target.closest('button, input, select, a, .p-select')) return;
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
</script>

<template>
    <Dialog :visible="show" modal @update:visible="val => !val && close()" @show="onShow"
        :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
        <template #container>
            <div class="ios-modal" :style="modalStyle">
                <!-- Nav Bar -->
                <div class="ios-nav-bar" @pointerdown="onDragStart">
                    <button class="ios-nav-btn ios-nav-cancel" @click="close"><i class="pi pi-times"></i></button>
                    <span class="ios-nav-title">YAKAP Category</span>
                    <button class="ios-nav-btn ios-nav-action" @click="submit" :disabled="updateYakapForm.processing">
                        {{ updateYakapForm.processing ? 'Updating...' : 'Update' }}
                    </button>
                </div>

                <!-- Body -->
                <div class="ios-body" v-if="profile">
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

                    <!-- YAKAP Category -->
                    <div class="ios-section">
                        <div class="ios-section-label">Category</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">YAKAP Category</span>
                                <div class="ios-row-control ios-select">
                                    <Select v-model="updateYakapForm.yakap_category" :options="yakapCategoryOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select Category"
                                        @change="handleCategoryChange" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Municipality (YAKAP Field) -->
                    <div v-if="updateYakapForm.yakap_category === 'yakap-field'" class="ios-section">
                        <div class="ios-section-label">Location</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Municipality</span>
                                <div class="ios-row-control ios-select">
                                    <MunicipalitySelect v-model="updateYakapForm.yakap_location"
                                        placeholder="Select Municipality" :clearable="false" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- School (YAKAP School) -->
                    <div v-if="updateYakapForm.yakap_category === 'yakap-school'" class="ios-section">
                        <div class="ios-section-label">Location</div>
                        <div class="ios-card">
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">School</span>
                                <div class="ios-row-control ios-select">
                                    <SchoolSelect v-model="updateYakapForm.yakap_location" placeholder="Select School"
                                        :clearable="false" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom spacing -->
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

:deep(.ios-select .p-select) {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #8E8E93;
    padding: 0;
    width: 100%;
    min-height: unset;
}

:deep(.ios-select .p-select-label) {
    color: #8E8E93 !important;
    text-align: left;
    padding: 4px 2px 4px 8px;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.ios-select .p-select-dropdown) {
    color: #C7C7CC !important;
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
