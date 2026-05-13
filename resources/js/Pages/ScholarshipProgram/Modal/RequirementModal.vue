<script setup>
import { ref, watch } from "vue";
import axios from 'axios';
import { toast } from '@/utils/toast';
import IosModal from '@/Components/ui/IosModal.vue';

const props = defineProps({
    visible: Boolean,
    program: Object,
    requirements: Array,
});
const emit = defineEmits(['update:visible', 'saved']);

const processing = ref(false);
const selectedIds = ref([]);

watch(() => props.visible, (val) => {
    if (val && props.program) {
        selectedIds.value = [...(props.program.requirements || [])].map(r => r.id);
    }
});

const toggleRequirement = (id) => {
    const i = selectedIds.value.indexOf(id);
    if (i >= 0) selectedIds.value.splice(i, 1);
    else selectedIds.value.push(id);
};

const close = () => emit('update:visible', false);

const submit = async () => {
    if (processing.value || !props.program) return;
    processing.value = true;
    try {
        const res = await axios.put(
            route('scholarshipprograms.update-requirement', props.program.id),
            { requirements: selectedIds.value }
        );
        toast.success(res.data.message || 'Requirements updated', { position: toast.POSITION.TOP_RIGHT });
        emit('saved', res.data.program);
        close();
    } catch {
        toast.error('Failed to update requirements', { position: toast.POSITION.TOP_RIGHT });
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <IosModal :visible="visible" title="Program Requirements" width="500px" max-width="95vw"
        body-style="padding: 0 16px;" :show-action="true" action-label="Save" :loading="processing"
        @action="submit" @update:visible="val => !val && close()">

                    <!-- Program Info -->
                    <div class="ios-section">
                        <div class="ios-section-label">Program</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <span class="ios-row-label">Name</span>
                                <span style="font-size: 14px; color: #1c1c1e; font-weight: 600;">{{ program?.name
                                }}</span>
                            </div>
                            <div class="ios-row ios-row-last">
                                <span class="ios-row-label">Shortname</span>
                                <span style="font-size: 13px; color: #8E8E93;">{{ program?.shortname }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Requirements Checklist -->
                    <div class="ios-section">
                        <div class="ios-section-label">
                            Requirements
                            <span style="color: #8E8E93; font-weight: 400; margin-left: 6px;">
                                ({{ selectedIds.length }} selected)
                            </span>
                        </div>
                        <div class="ios-card">
                            <template v-if="requirements?.length">
                                <div v-for="(req, index) in requirements" :key="req.id" class="ios-row"
                                    :class="index === requirements.length - 1 ? 'ios-row-last' : ''"
                                    style="cursor: pointer;" @click="toggleRequirement(req.id)">
                                    <span style="font-size: 14px; color: #1c1c1e; flex: 1;">{{ req.name }}</span>
                                    <AppIcon v-if="selectedIds.includes(req.id)" name="check-circle" :size="20"
                                        class="text-[#34C759] flex-shrink-0" />
                                    <AppIcon v-else name="circle" :size="20" class="text-[#C7C7CC] flex-shrink-0" />
                                </div>
                            </template>
                            <div v-else class="ios-row ios-row-last"
                                style="color: #8E8E93; font-size: 13px; justify-content: center; padding: 16px;">
                                No requirements available
                            </div>
                        </div>
                    </div>

                    <div style="height: 20px;"></div>
    </IosModal>
</template>