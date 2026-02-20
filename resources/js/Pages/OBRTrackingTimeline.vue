<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import logger from '@/utils/logger';

const toast = useToast();

const state = reactive({
    params: {
        fiscal_year: 2025,
        obr_no: '200-25-12-24188',
        dv_no: '25-12-23743',
        type: 'GF'
    },
    trackingData: null,
    loading: false,
    error: '',
    responseTime: 0,
    rawResponse: null
});

/**
 * Get tracking information
 */
const getTrackingInfo = async () => {
    if (!state.params.obr_no) {
        state.error = 'Please enter OBR number';
        return;
    }

    state.loading = true;
    state.error = '';
    state.trackingData = null;

    const startTime = performance.now();

    try {
        logger.info('Fetching tracking info:', state.params);

        const response = await axios.get('/api/obr-tracking-info', {
            params: state.params
        });

        state.responseTime = Math.round(performance.now() - startTime);
        state.rawResponse = response.data;

        if (response.data.success) {
            state.trackingData = response.data.data || response.data;

            // If DV number was auto-fetched, update the UI
            if (response.data.used_dv_no && !state.params.dv_no) {
                state.params.dv_no = response.data.used_dv_no;
                logger.info('DV number auto-fetched:', response.data.used_dv_no);
            }

            logger.info('Tracking info fetched successfully');
            console.log('Tracking info:', state.trackingData);
        } else {
            state.error = response.data.message || 'Failed to fetch tracking info';
            logger.warn('Tracking info fetch failed:', response.data.message);
        }
    } catch (error) {
        state.responseTime = Math.round(performance.now() - startTime);
        state.error = error.response?.data?.message || error.message;
        state.rawResponse = error.response?.data || { error: error.message };
        logger.error('Tracking info error:', error);
    } finally {
        state.loading = false;
    }
};

/**
 * Copy to clipboard
 */
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(JSON.stringify(text, null, 2)).then(() => {
        toast.add({
            severity: 'success',
            summary: 'Copied',
            detail: 'Data copied to clipboard',
            life: 2000
        });
    });
};

/**
 * Export as JSON file
 */
const exportData = () => {
    if (!state.trackingData) return;

    const jsonStr = JSON.stringify(state.trackingData.tracking_information, null, 2);
    const blob = new Blob([jsonStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `tracking-${state.params.obr_no}-${new Date().getTime()}.json`;
    a.click();
    URL.revokeObjectURL(url);
};

/**
 * Clear all data
 */
const clearAll = () => {
    state.trackingData = null;
    state.error = '';
    state.rawResponse = null;
};

</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="pi pi-history text-blue-600"></i>
                    OBR Tracking Timeline
                </h1>
                <p class="text-gray-600 mt-2">View the complete tracking history for an OBR and Disbursement Voucher</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Input Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 space-y-4 sticky top-6">
                        <h2 class="text-xl font-semibold text-gray-900">Parameters</h2>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year</label>
                            <input v-model.number="state.params.fiscal_year" type="number"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select v-model="state.params.type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="GF">GF</option>
                                <option value="BUR">BUR</option>
                                <option value="DV">DV</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OBR Number *</label>
                            <input v-model="state.params.obr_no" type="text" placeholder="e.g., 200-25-12-24188"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">DV Number</label>
                                <span v-if="state.params.dv_no && state.rawResponse?.used_dv_no"
                                    class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Auto-fetched</span>
                            </div>
                            <input v-model="state.params.dv_no" type="text" placeholder="e.g., 25-12-23743 (optional)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <!-- Get Timeline Button -->
                        <button @click="getTrackingInfo" :disabled="state.loading"
                            class="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-colors">
                            <i v-if="state.loading" class="pi pi-spin pi-spinner mr-2"></i>
                            {{ state.loading ? 'Loading...' : 'Get Timeline' }}
                        </button>

                        <!-- Action Buttons -->
                        <button @click="clearAll"
                            class="w-full py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium transition-colors text-sm">
                            <i class="pi pi-times mr-2"></i>
                            Clear
                        </button>

                        <!-- Error -->
                        <div v-if="state.error"
                            class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                            <i class="pi pi-exclamation-circle mr-2"></i>
                            {{ state.error }}
                        </div>

                        <!-- Stats -->
                        <div v-if="state.trackingData" class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-xs text-gray-600 mb-2 font-medium">STATS</p>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Entries:</span>
                                    <span class="font-bold">{{ state.trackingData.tracking_information?.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Response:</span>
                                    <span class="font-bold">{{ state.responseTime }}ms</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div class="lg:col-span-2">
                    <div v-if="state.loading" class="text-center py-12">
                        <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
                        <p class="mt-4 text-gray-600">Fetching tracking information...</p>
                    </div>

                    <div v-else-if="!state.trackingData" class="bg-white rounded-lg shadow-md p-8 text-center">
                        <i class="pi pi-inbox text-5xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-4">Enter OBR and DV numbers above to view tracking timeline</p>
                        <button @click="() => {
                            state.params.obr_no = '200-25-12-24188';
                            state.params.dv_no = '25-12-23743';
                            getTrackingInfo();
                        }"
                            class="inline-block py-2 px-4 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-medium">
                            Load Sample Data
                        </button>
                    </div>

                    <div v-else-if="state.trackingData && state.trackingData.tracking_information"
                        class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="pi pi-list text-blue-600 mr-2"></i>
                            Tracking History
                        </h2>

                        <div class="space-y-4 max-h-[600px] overflow-y-auto">
                            <div v-for="(entry, idx) in state.trackingData.tracking_information" :key="idx"
                                class="flex gap-4 pb-4 border-b border-gray-200 last:border-b-0">
                                <!-- Timeline number -->
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 text-blue-600 font-bold text-sm">
                                        {{ idx + 1 }}
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-base">{{ entry.trn_remarks }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ entry.trn_date }}</p>

                                    <!-- DV indicator -->
                                    <div v-if="entry.dv_no" class="mt-2">
                                        <span
                                            class="inline-block text-xs font-mono bg-purple-100 text-purple-700 px-2 py-1 rounded">
                                            DV: {{ entry.dv_no }}
                                        </span>
                                    </div>

                                    <!-- Status indicator -->
                                    <div class="mt-2">
                                        <span class="inline-block text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                            Status: {{ entry.trn_status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Buttons -->
                        <div class="mt-6 flex gap-2 pt-4 border-t border-gray-200">
                            <button @click="copyToClipboard(state.trackingData.tracking_information)"
                                class="flex-1 py-2 px-4 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-medium transition-colors text-sm">
                                <i class="pi pi-copy mr-2"></i>Copy Data
                            </button>
                            <button @click="exportData"
                                class="flex-1 py-2 px-4 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 font-medium transition-colors text-sm">
                                <i class="pi pi-download mr-2"></i>Export JSON
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar */
div::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

div::-webkit-scrollbar-track {
    background: transparent;
}

div::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

div::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
