<template>
    <IosModal :visible="show" title="Tracking Timeline" width="500px" max-width="90vw"
        body-style="padding: 16px;" @update:visible="val => emit('update:show', val)">
        <div class="pt-4">
                        <div v-if="trackingData" class="ios-section mb-4">
                            <!-- Tracking Timeline -->
                            <div v-if="trackingData.tracking_information && trackingData.tracking_information.length > 0"
                                class="ios-card px-4">
                                <div v-for="(entry, index) in trackingData.tracking_information" :key="index"
                                    class="flex gap-3 py-3"
                                    :class="index < trackingData.tracking_information.length - 1 ? '[border-bottom:0.5px_solid_#e5e5ea]' : ''">
                                    <div
                                        class="w-7 h-7 bg-[#007AFF] rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <AppIcon name="check" :size="11" class="text-white" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[13px] text-[#1c1c1e] dark:text-gray-200">{{ entry.trn_remarks ||
                                            entry.transaction_description || entry.description }}</p>
                                        <p class="text-[12px] text-[#8E8E93] mt-[3px]">{{
                                            formatDate(entry.trn_date || entry.transaction_date || entry.date)
                                        }}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- No Timeline -->
                            <div v-else class="ios-card py-8 px-4 text-center text-[#8E8E93]">
                                <p class="text-sm">No tracking information</p>
                            </div>
                        </div>

                        <div v-else class="p-12 text-center text-[#8E8E93]">
                            <AppIcon name="spinner" :size="24" class="mb-2 block" />
                            <p class="text-[13px]">Loading...</p>
                        </div>
        </div>
    </IosModal>
</template>

<script setup>
import IosModal from '@/Components/ui/IosModal.vue';

defineProps({
    show: {
        type: Boolean,
        required: true
    },
    trackingData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:show']);
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
