<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import logger from '@/utils/logger';

const toast = useToast();

const testState = reactive({
    // Search mode
    searchObrNo: '',
    searchResults: [],
    searchLoading: false,
    searchError: '',

    // Filter mode
    filters: {
        type: 'GF',
        fiscal_year: new Date().getFullYear(),
        sortField: 'obrDate',
        sortDirection: 'desc',
        page: 0,
        pageSize: 10,
        payee: '',
        obrNo: ''
    },
    filterResults: [],
    filterLoading: false,
    filterError: '',

    // Tracking Info mode
    trackingInfoParams: {
        fiscal_year: new Date().getFullYear(),
        obr_no: '200-25-12-24188',
        dv_no: '25-12-23743',
        type: 'GF'
    },
    trackingInfoData: null,
    trackingInfoLoading: false,
    trackingInfoError: '',

    // Response display
    selectedTab: 'search', // 'search', 'filter', or 'tracking'
    rawResponse: null,
    responseTime: 0
});

/**
 * Search OBR by number
 */
const handleSearchOBR = async () => {
    if (!testState.searchObrNo.trim()) {
        testState.searchError = 'Please enter an OBR number';
        return;
    }

    testState.searchLoading = true;
    testState.searchError = '';
    testState.searchResults = [];

    const startTime = performance.now();

    try {
        logger.info(`Testing OBR search: ${testState.searchObrNo}`);

        const response = await axios.get(`/api/obr-tracking/search/${testState.searchObrNo}`);

        testState.responseTime = Math.round(performance.now() - startTime);
        testState.rawResponse = response.data;

        // Extract data from response
        const data = response.data.data || response.data || [];
        testState.searchResults = Array.isArray(data) ? data : [];

        logger.info(`Search request completed. Data type: ${typeof data}, Array: ${Array.isArray(data)}`);
        logger.info(`Found ${testState.searchResults.length} records`);
        console.log('Full response:', response.data);

        if (response.data.success === false) {
            testState.searchError = response.data.message || 'Search returned no results';
        } else if (testState.searchResults.length === 0) {
            testState.searchError = 'No records found for this OBR number';
        }
    } catch (error) {
        testState.responseTime = Math.round(performance.now() - startTime);
        testState.searchError = `Error: ${error.response?.data?.message || error.message}`;
        testState.rawResponse = error.response?.data || { error: error.message };
        logger.error('OBR search error:', error);
        console.error('Full error:', error);
    } finally {
        testState.searchLoading = false;
    }
};

/**
 * Fetch with filters
 */
const handleFilterSearch = async () => {
    testState.filterLoading = true;
    testState.filterError = '';
    testState.filterResults = [];

    const startTime = performance.now();

    try {
        logger.info('Testing OBR with filters:', testState.filters);

        const response = await axios.get('/api/obr-tracking', {
            params: testState.filters
        });

        testState.responseTime = Math.round(performance.now() - startTime);
        testState.rawResponse = response.data;

        // Extract data from response
        const data = response.data.data || response.data || [];
        testState.filterResults = Array.isArray(data) ? data : [];

        logger.info(`Filter request completed. Found ${testState.filterResults.length} records`);
        console.log('Full response:', response.data);

        if (response.data.success === false) {
            testState.filterError = response.data.message || 'Filter search returned no results';
        } else if (testState.filterResults.length === 0) {
            testState.filterError = 'No records found for the applied filters';
        }
    } catch (error) {
        testState.responseTime = Math.round(performance.now() - startTime);
        testState.filterError = `Error: ${error.response?.data?.message || error.message}`;
        testState.rawResponse = error.response?.data || { error: error.message };
        logger.error('OBR filter search error:', error);
        console.error('Full error:', error);
    } finally {
        testState.filterLoading = false;
    }
};

/**
 * Copy to clipboard
 */
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(JSON.stringify(text, null, 2)).then(() => {
        logger.info('Response copied to clipboard');
    });
};

/**
 * Export results as JSON
 */
const exportResults = () => {
    const data = testState.selectedTab === 'search' ? testState.searchResults : testState.filterResults;
    const jsonStr = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `obr-results-${new Date().getTime()}.json`;
    a.click();
    URL.revokeObjectURL(url);
};

/**
 * Open OBR in tracking system (workaround for session requirement)
 */
const openInTrackingSystem = () => {
    const obrNo = testState.selectedTab === 'search' ? testState.searchObrNo : testState.filters.obrNo;
    if (!obrNo || !obrNo.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Enter OBR Number',
            detail: 'Please enter an OBR number to view',
            life: 3000
        });
        return;
    }

    const url = `https://tracking.pgpict.com/obr-tracking?type=GF&fiscal_year=${testState.filters.fiscal_year}&obrNo=${encodeURIComponent(obrNo)}`;
    window.open(url, '_blank');

    logger.info(`Opened tracking system for OBR: ${obrNo}`);
};

/**
 * Get detailed OBR tracking information
 */
const handleGetTrackingInfo = async () => {
    if (!testState.trackingInfoParams.obr_no || !testState.trackingInfoParams.dv_no) {
        testState.trackingInfoError = 'Please enter OBR number and DV number';
        return;
    }

    testState.trackingInfoLoading = true;
    testState.trackingInfoError = '';
    testState.trackingInfoData = null;

    const startTime = performance.now();

    try {
        logger.info('Fetching OBR tracking info:', testState.trackingInfoParams);

        const response = await axios.get('/api/obr-tracking-info', {
            params: testState.trackingInfoParams
        });

        testState.responseTime = Math.round(performance.now() - startTime);
        testState.rawResponse = response.data;

        if (response.data.success) {
            testState.trackingInfoData = response.data.data || response.data;
            logger.info('Tracking info fetched successfully');
            console.log('Tracking info:', testState.trackingInfoData);
        } else {
            testState.trackingInfoError = response.data.message || 'Failed to fetch tracking info';
            logger.warn('Tracking info fetch failed:', response.data.message);
        }
    } catch (error) {
        testState.responseTime = Math.round(performance.now() - startTime);
        testState.trackingInfoError = `Error: ${error.response?.data?.message || error.message}`;
        testState.rawResponse = error.response?.data || { error: error.message };
        logger.error('Tracking info error:', error);
        console.error('Full error:', error);
    } finally {
        testState.trackingInfoLoading = false;
    }
};

/**
 * Clear all data
 */
const clearAll = () => {
    testState.searchObrNo = '';
    testState.searchResults = [];
    testState.searchError = '';
    testState.filterResults = [];
    testState.filterError = '';
    testState.trackingInfoData = null;
    testState.trackingInfoError = '';
    testState.rawResponse = null;
};

/**
 * Sample OBR numbers for testing
 */
const sampleObrNumbers = [
    '200-25-12-24188',
    '200-25-01-12345',
    '200-25-06-98765'
];

const displayResults = computed(() => {
    return testState.selectedTab === 'search' ? testState.searchResults : testState.filterResults;
});

const displayError = computed(() => {
    if (testState.selectedTab === 'search') return testState.searchError;
    if (testState.selectedTab === 'filter') return testState.filterError;
    return testState.trackingInfoError;
});

const displayLoading = computed(() => {
    if (testState.selectedTab === 'search') return testState.searchLoading;
    if (testState.selectedTab === 'filter') return testState.filterLoading;
    return testState.trackingInfoLoading;
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 short:p-3">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-6 short:mb-3">
                <h1 class="text-4xl short:text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="pi pi-history text-blue-600"></i>
                    OBR Tracking Timeline
                </h1>
                <p class="text-gray-600 mt-2">View the complete tracking history for an OBR and Disbursement Voucher</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 short:gap-2">
                <!-- Info Banner -->
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold text-yellow-900 mb-2">
                        <i class="pi pi-exclamation-triangle mr-2"></i>
                        API Session Required
                    </h3>
                    <p class="text-sm text-yellow-800 mb-3">
                        The OBR API requires an authenticated session from tracking.pgpict.com.
                        Use the <strong>"Open in Tracking System"</strong> button to view OBR details directly.
                    </p>
                    <div class="flex gap-2">
                        <Button label="Go to Tracking System" icon="pi pi-external-link" severity="warning"
                            class="flex-1" @click="window.open('https://tracking.pgpict.com', '_blank')" />
                        <a href="/documentation/OBR_SESSION_AUTHENTICATION.md" target="_blank"
                            class="flex-1 py-2 px-3 bg-yellow-100 text-yellow-900 rounded hover:bg-yellow-200 text-sm font-medium transition-colors text-center">
                            <i class="pi pi-question-circle mr-2"></i>
                            Learn More
                        </a>
                    </div>
                </div>


                <!-- Search Tab -->
                <div v-if="testState.selectedTab === 'search'"
                    class="bg-white rounded-lg shadow-md p-4 short:p-3 space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Search by OBR Number</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            OBR Number
                        </label>
                        <InputText v-model="testState.searchObrNo" type="text" placeholder="e.g., 200-25-12-24188"
                            class="w-full" @keyup.enter="handleSearchOBR" />
                    </div>

                    <!-- Sample OBR Numbers -->
                    <div>
                        <p class="text-xs text-gray-600 mb-2">Sample OBR Numbers:</p>
                        <div class="space-y-1">
                            <button v-for="sample in sampleObrNumbers" :key="sample"
                                @click="testState.searchObrNo = sample"
                                class="w-full text-left px-3 py-1 text-sm bg-gray-50 hover:bg-blue-50 border border-gray-200 rounded hover:border-blue-300 transition-colors">
                                {{ sample }}
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <Button label="Search" icon="pi pi-search" :loading="testState.searchLoading"
                            @click="handleSearchOBR" />
                        <Button label="Open in System" icon="pi pi-external-link" severity="warning"
                            @click="openInTrackingSystem" />
                    </div>

                    <!-- Error -->
                    <div v-if="testState.searchError"
                        class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        <i class="pi pi-exclamation-circle mr-2"></i>
                        {{ testState.searchError }}
                    </div>
                </div>

                <!-- Filter Tab -->
                <div v-else class="bg-white rounded-lg shadow-md p-4 short:p-3 space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Advanced Filter</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <Select v-model="testState.filters.type"
                                :options="[{ value: 'GF', label: 'GF' }, { value: 'BUR', label: 'BUR' }, { value: 'DV', label: 'DV' }]"
                                optionLabel="label" optionValue="value" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year</label>
                            <InputText v-model.number="testState.filters.fiscal_year" type="number" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Field</label>
                            <Select v-model="testState.filters.sortField"
                                :options="[{ value: 'obrDate', label: 'OBR Date' }, { value: 'payee', label: 'Payee' }, { value: 'amount', label: 'Amount' }]"
                                optionLabel="label" optionValue="value" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
                            <Select v-model="testState.filters.sortDirection"
                                :options="[{ value: 'asc', label: 'Ascending' }, { value: 'desc', label: 'Descending' }]"
                                optionLabel="label" optionValue="value" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Page Size</label>
                            <InputText v-model.number="testState.filters.pageSize" type="number" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Page</label>
                            <InputText v-model.number="testState.filters.page" type="number" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OBR Number (optional)</label>
                        <InputText v-model="testState.filters.obrNo" type="text" placeholder="e.g., 200-25-12-24188"
                            class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payee (optional)</label>
                        <InputText v-model="testState.filters.payee" type="text" placeholder="Payee name"
                            class="w-full" />
                    </div>

                    <!-- Filter Buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <Button label="Apply Filter" icon="pi pi-filter" :loading="testState.filterLoading"
                            @click="handleFilterSearch" />
                        <Button label="Open in System" icon="pi pi-external-link" severity="warning"
                            @click="openInTrackingSystem" />
                    </div>

                    <!-- Error -->
                    <div v-if="testState.filterError"
                        class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        <i class="pi pi-exclamation-circle mr-2"></i>
                        {{ testState.filterError }}
                    </div>
                </div>

                <!-- Tracking Info Tab -->
                <div v-if="testState.selectedTab === 'tracking'"
                    class="bg-white rounded-lg shadow-md p-4 short:p-3 space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Get Tracking Details</h2>
                    <p class="text-sm text-gray-600">Fetch detailed tracking information for a specific OBR and DV</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year</label>
                            <InputText v-model.number="testState.trackingInfoParams.fiscal_year" type="number"
                                class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <Select v-model="testState.trackingInfoParams.type"
                                :options="[{ value: 'GF', label: 'GF' }, { value: 'BUR', label: 'BUR' }, { value: 'DV', label: 'DV' }]"
                                optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OBR Number *</label>
                        <InputText v-model="testState.trackingInfoParams.obr_no" type="text"
                            placeholder="e.g., 200-25-12-24188" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">DV Number *</label>
                        <InputText v-model="testState.trackingInfoParams.dv_no" type="text"
                            placeholder="e.g., 25-12-23743" class="w-full" />
                    </div>

                    <!-- Get Tracking Info Buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <Button label="Get Info" icon="pi pi-info-circle" :loading="testState.trackingInfoLoading"
                            @click="handleGetTrackingInfo" />
                        <Button label="Open in System" icon="pi pi-external-link" severity="warning"
                            @click="openInTrackingSystem" />
                    </div>

                    <!-- Error -->
                    <div v-if="testState.trackingInfoError"
                        class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        <i class="pi pi-exclamation-circle mr-2"></i>
                        {{ testState.trackingInfoError }}
                    </div>

                    <!-- Tracking Info Display -->
                    <div v-if="testState.trackingInfoData" class="space-y-4" <!-- Tracking Timeline -->
                        <div class="bg-white border-l-4 border-green-600 rounded-lg shadow-md p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="pi pi-list text-green-600 mr-2"></i>
                                Tracking Timeline ({{ testState.trackingInfoData.tracking_information?.length }}
                                entries)
                            </h3>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <div v-for="(entry, idx) in testState.trackingInfoData.tracking_information" :key="idx"
                                    class="border-b border-gray-200 pb-3 last:border-b-0">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600 text-sm font-bold">
                                                {{ idx + 1 }}
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ entry.trn_remarks }}</p>
                                            <p class="text-xs text-gray-600 mt-1">{{ entry.trn_date }}</p>
                                            <div v-if="entry.dv_no" class="text-xs text-gray-500 mt-1">
                                                <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">DV: {{
                                                    entry.dv_no }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Copy & Download -->
                        <div class="flex gap-2">
                            <Button label="Copy Data" icon="pi pi-copy" severity="info" outlined class="flex-1"
                                @click="copyToClipboard(testState.trackingInfoData.tracking_information)" />
                            <Button label="Export" icon="pi pi-download" severity="success" outlined class="flex-1"
                                @click="exportResults" />
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-lg shadow-md p-4 space-y-2">
                    <div class="flex gap-2">
                        <Button label="Export" icon="pi pi-download" severity="success" class="flex-1"
                            :disabled="displayResults.length === 0" @click="exportResults" />
                        <Button label="Clear" icon="pi pi-times" severity="secondary" class="flex-1"
                            @click="clearAll" />
                    </div>
                    <Button label="Open in Tracking System" icon="pi pi-external-link" class="w-full" severity="help"
                        @click="openInTrackingSystem" />
                </div>
            </div>

            <!-- Right Panel: Results -->
            <div class="space-y-6">
                <!-- Stats -->
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-600">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm">Results Found</p>
                            <p class="text-3xl short:text-xl font-bold text-green-600">{{ displayResults.length }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Response Time</p>
                            <p class="text-3xl short:text-xl font-bold text-blue-600">{{ testState.responseTime || 0
                                }}ms</p>
                        </div>
                    </div>
                    <div v-if="testState.rawResponse && testState.rawResponse.recordsFiltered !== undefined"
                        class="mt-3 pt-3 border-t border-gray-200 text-sm">
                        <p class="text-gray-600">Total Records (filtered): <span class="font-bold text-gray-900">{{
                            testState.rawResponse.recordsFiltered }}</span></p>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-lg shadow-md p-4 short:p-3">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Results</h3>

                    <div v-if="displayLoading" class="text-center py-8">
                        <i class="pi pi-spin pi-spinner text-3xl short:text-xl text-blue-600"></i>
                        <p class="mt-2 text-gray-600">Loading...</p>
                    </div>

                    <div v-else-if="displayResults.length === 0" class="text-center py-8 text-gray-500">
                        <i class="pi pi-inbox text-4xl short:text-2xl mb-2"></i>
                        <p>No results yet. Start a search above.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-if="displayResults.length > 0">
                            <!-- Summary -->
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-900">
                                <i class="pi pi-info-circle mr-2"></i>
                                Showing {{ displayResults.slice(0, 5).length }} of {{ displayResults.length }} results
                            </div>

                            <!-- Results Grid -->
                            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-100 border-b border-gray-300">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">OBR No</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">OBR Date</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Payee</th>
                                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Amount</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in displayResults.slice(0, 5)" :key="index"
                                            class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                            <td class="px-3 py-2 font-mono text-blue-700">{{ item.obr_desc ||
                                                item.obr_no }}</td>
                                            <td class="px-3 py-2">{{ item.obr_date || '---' }}</td>
                                            <td class="px-3 py-2 max-w-xs truncate" :title="item.obr_payee_name">{{
                                                item.obr_payee_name || '---' }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">{{ item.obr_amount ?
                                                `₱${parseFloat(item.obr_amount).toLocaleString('en-PH',
                                                    { minimumFractionDigits: 2 })}` : '---' }}</td>
                                            <td class="px-3 py-2">
                                                <span :class="[
                                                    'px-2 py-1 rounded text-xs font-medium',
                                                    item.obr_status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                                ]">
                                                    {{ item.obr_status || '---' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="displayResults.length > 5"
                                class="text-center text-gray-600 text-sm py-3 bg-gray-50 border border-gray-200 rounded">
                                ... and {{ displayResults.length - 5 }} more results
                            </div>
                        </div>

                        <!-- Detailed View for First Result -->
                        <div v-if="displayResults.length > 0"
                            class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <h4 class="font-semibold text-purple-900 mb-3">First Result - Detailed View</h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div v-for="(value, key) in displayResults[0]" :key="key" class="pb-2">
                                    <span class="font-medium text-gray-700">{{ key }}:</span>
                                    <div class="text-gray-900 break-words">{{ value || '---' }}</div>
                                </div>
                            </div>
                        </div>

                        <Button label="Copy to Clipboard" icon="pi pi-copy" severity="info" outlined class="w-full mt-4"
                            @click="copyToClipboard(displayResults)" />
                    </div>
                </div>

                <!-- Raw Response -->
                <div v-if="testState.rawResponse" class="bg-white rounded-lg shadow-md p-4 short:p-3">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Raw Response</h3>
                    <p class="text-xs text-gray-600 mb-3">
                        <i class="pi pi-info-circle mr-1"></i>
                        Open browser console (F12) for full debugging details
                    </p>
                    <pre
                        class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto max-h-96">{{ JSON.stringify(testState.rawResponse, null, 2) }}</pre>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="mt-6 short:mt-3 bg-white rounded-lg shadow-md p-4 short:p-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">API Endpoints</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="font-mono text-blue-600 mb-2">GET /api/obr-tracking/search/{obrNo}</p>
                        <p class="text-gray-600">Search by OBR number</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="font-mono text-blue-600 mb-2">GET /api/obr-tracking?...</p>
                        <p class="text-gray-600">Advanced filter search</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for pre */
pre::-webkit-scrollbar {
    height: 8px;
}

pre::-webkit-scrollbar-track {
    background: #374151;
}

pre::-webkit-scrollbar-thumb {
    background: #6B7280;
    border-radius: 4px;
}

pre::-webkit-scrollbar-thumb:hover {
    background: #9CA3AF;
}
</style>
