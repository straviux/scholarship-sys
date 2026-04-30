<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';


const props = defineProps({
    reportData: Object,
    stats: Object,
});

// Date range selection
const startDate = ref(null);
const endDate = ref(null);
const selectedDate = ref(null);
const isDialogVisible = ref(false);

// Helper: Format date as YYYY-MM-DD string
const formatDateString = (date) => {
    const d = new Date(date);
    return d.toISOString().split('T')[0];
};

// Initialize date range to last 2 years
const initializeDates = () => {
    const today = new Date();
    endDate.value = formatDateString(today);
    startDate.value = formatDateString(new Date(today.getFullYear() - 2, 0, 1));
};

initializeDates();

// Check if date is in range
const isInRange = (dateString) => {
    if (!dateString || !startDate.value || !endDate.value) return false;
    // Extract just the date portion (first 10 chars = YYYY-MM-DD)
    const checkDate = dateString.substring(0, 10);
    return checkDate >= startDate.value && checkDate <= endDate.value;
};

// Helper to extract date (first 10 chars = YYYY-MM-DD)
const extractDate = (dateString) => dateString?.substring(0, 10);

// Get all records grouped by date
const getReportData = computed(() => {
    const dateMap = {};

    // Collect profiles by created_at
    props.reportData?.profiles?.forEach(item => {
        if (item.created_at && isInRange(item.created_at)) {
            const date = extractDate(item.created_at);
            if (!dateMap[date]) {
                dateMap[date] = { date, profiles: 0, vouchers: new Set() };
            }
            dateMap[date].profiles++;
        }
    });

    // Collect unique vouchers by created_at
    props.reportData?.vouchers?.forEach(item => {
        if (item.created_at && isInRange(item.created_at)) {
            const date = extractDate(item.created_at);
            if (!dateMap[date]) {
                dateMap[date] = { date, profiles: 0, vouchers: new Set() };
            }
            // Convert to string to ensure proper deduplication
            dateMap[date].vouchers.add(String(item.voucher_id));
        }
    });

    // Convert to array and sort by date descending
    const result = Object.values(dateMap)
        .map(item => ({
            ...item,
            vouchers: item.vouchers.size,
            profiles: item.profiles,
            total: item.profiles + item.vouchers.size
        }))
        .sort((a, b) => new Date(b.date) - new Date(a.date));

    return result;
});

// Set date range presets
const setDateRange = (days) => {
    const today = new Date();
    endDate.value = formatDateString(today);
    startDate.value = formatDateString(new Date(today.getTime() - days * 24 * 60 * 60 * 1000));
};

// Get profiles for a specific date
const getProfilesForDate = (date) => {
    return props.reportData?.profiles?.filter(p => extractDate(p.created_at) === date) || [];
};

// Get vouchers for a specific date
const getVouchersForDate = (date) => {
    return props.reportData?.vouchers?.filter(v => extractDate(v.created_at) === date) || [];
};

// Open details for a date
const viewDetails = (date) => {
    selectedDate.value = date;
    isDialogVisible.value = true;
};

// Close modal
const closeModal = () => {
    selectedDate.value = null;
    isDialogVisible.value = false;
};
</script>

<template>

    <Head title="My Reports" />
    <AdminLayout>
        <div class="p-4 short:p-3 space-y-4 short:space-y-2">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl short:text-xl font-bold text-gray-900">My Reports</h1>
                    <p class="mt-1 text-gray-600">View your encoded profiles and vouchers</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 short:p-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Date Range</h3>
                <div class="flex flex-wrap items-end gap-3">
                    <Button @click="setDateRange(7)" label="Last 7 Days" severity="info" outlined size="small" />
                    <Button @click="setDateRange(30)" label="Last 30 Days" severity="info" outlined size="small" />
                    <Button @click="setDateRange(90)" label="Last 90 Days" severity="info" outlined size="small" />
                    <Button @click="setDateRange(365)" label="Last Year" severity="info" outlined size="small" />

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">From</label>
                        <InputText v-model="startDate" type="date" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">To</label>
                        <InputText v-model="endDate" type="date" />
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Records Summary</h3>
                    <p class="text-sm text-gray-600 mt-1">Showing {{ getReportData.length }} date(s) with {{
                        stats?.total_profiles || 0 }} profiles and {{ stats?.total_vouchers || 0 }} vouchers</p>
                </div>

                <div v-if="getReportData.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-900 w-12">#</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-900">Profiles</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-900">Vouchers</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-900">Total</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-900">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(item, index) in getReportData" :key="item.date"
                                class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3 text-gray-700 font-medium">{{ index + 1 }}</td>
                                <td class="px-6 py-3 text-gray-700">
                                    {{ new Date(item.date).toLocaleDateString('en-US', {
                                        weekday: 'short', year:
                                            'numeric', month: 'short', day: 'numeric'
                                    }) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-700">
                                        {{ item.profiles }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold bg-purple-100 text-purple-700">
                                        {{ item.vouchers }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-700">
                                        {{ item.total }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <button @click="viewDetails(item.date)"
                                        class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium cursor-pointer">
                                        View
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="px-6 py-8 text-center text-gray-500">
                    <p>No records found for the selected date range</p>
                </div>
            </div>

            <!-- Details Dialog -->
            <Dialog v-model:visible="isDialogVisible"
                :header="`Records for ${selectedDate ? new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : ''}`"
                :modal="true" :maximizable="true" class="w-full md:w-3xl lg:w-4xl" @hide="closeModal">
                <!-- Profiles Section -->
                <div class="mb-8">
                    <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-bold">{{
                            getProfilesForDate(selectedDate).length }}</span>
                        Profiles
                    </h4>
                    <div v-if="getProfilesForDate(selectedDate).length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-green-50 border-b border-green-200">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-green-700">Name</th>
                                    <th class="px-4 py-2 text-left font-semibold text-green-700">Date Filed</th>
                                    <th class="px-4 py-2 text-left font-semibold text-green-700">Status</th>
                                    <th class="px-4 py-2 text-left font-semibold text-green-700">Created At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(profile, idx) in getProfilesForDate(selectedDate)" :key="idx"
                                    class="hover:bg-green-50">
                                    <td class="px-4 py-2 text-gray-700">{{ profile.profile_name }}</td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">{{ profile.date_filed ? new
                                        Date(profile.date_filed).toLocaleDateString('en-US', {
                                            year: 'numeric', month:
                                                'short', day: 'numeric'
                                        }) : 'N/A' }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            :class="['px-2 py-1 rounded text-xs font-semibold', 'bg-green-100 text-green-700']">
                                            {{ profile.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">{{ profile.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-gray-500 text-sm">No profiles created on this date</div>
                </div>

                <!-- Vouchers Section -->
                <div>
                    <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-bold">{{
                            getVouchersForDate(selectedDate).length }}</span>
                        Vouchers
                    </h4>
                    <div v-if="getVouchersForDate(selectedDate).length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-purple-50 border-b border-purple-200">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-purple-700">Voucher ID</th>
                                    <th class="px-4 py-2 text-left font-semibold text-purple-700">Voucher #</th>
                                    <th class="px-4 py-2 text-left font-semibold text-purple-700">Amount</th>
                                    <th class="px-4 py-2 text-left font-semibold text-purple-700">Status</th>
                                    <th class="px-4 py-2 text-left font-semibold text-purple-700">Created At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="voucher in getVouchersForDate(selectedDate)" :key="voucher.voucher_id"
                                    class="hover:bg-purple-50">
                                    <td class="px-4 py-2 text-gray-700 font-mono">{{ voucher.voucher_id }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ voucher.transaction_id || 'N/A' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ voucher.amount ?
                                        parseFloat(voucher.amount).toFixed(2) : 'N/A' }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            :class="['px-2 py-1 rounded text-xs font-semibold', voucher.transaction_status === 'active' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700']">
                                            {{ voucher.transaction_status || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">{{ voucher.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-gray-500 text-sm">No vouchers created on this date</div>
                </div>
            </Dialog>
        </div>
    </AdminLayout>
</template>
