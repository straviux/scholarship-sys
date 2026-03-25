<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                    <p class="mt-1 text-gray-600">View your account information and encoded data</p>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Account Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <p class="text-sm text-gray-600">Full Name</p>
                            <p class="text-lg font-semibold text-gray-900">{{ user.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <p class="text-sm text-gray-600">Email Address</p>
                            <p class="text-lg font-semibold text-gray-900">{{ user.email }}</p>
                        </div>

                        <!-- Role -->
                        <div>
                            <p class="text-sm text-gray-600">Role</p>
                            <div class="mt-1">
                                <span v-for="role in user.roles" :key="role.id"
                                    class="inline-block mr-2 px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                    {{ role.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div v-if="user.phone">
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="text-lg font-semibold text-gray-900">{{ user.phone }}</p>
                        </div>

                        <!-- Office Designation -->
                        <div v-if="user.office_designation">
                            <p class="text-sm text-gray-600">Office Designation</p>
                            <p class="text-lg font-semibold text-gray-900">{{ user.office_designation }}</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <Link href="/user/settings" class="text-blue-600 hover:text-blue-800 font-semibold">
                            Edit Profile ✏️
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div v-if="Object.keys(stats).length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-gray-600 text-sm font-semibold uppercase">Total Applicants</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_applicants }}</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-gray-600 text-sm font-semibold uppercase">Active Scholars</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_scholars }}</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-gray-600 text-sm font-semibold uppercase">Total Vouchers</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_vouchers }}</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-gray-600 text-sm font-semibold uppercase">Pending Applications</div>
                    <div class="text-3xl font-bold text-red-600 mt-2">{{ stats.pending_applications }}</div>
                </div>
            </div>

            <!-- Encoded Data Tables -->
            <div v-if="Object.keys(encodedData).length > 0" class="space-y-6">
                <!-- Applicants Table -->
                <div v-if="encodedData.applicants && encodedData.applicants.length > 0"
                    class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Applicants (Last 10)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Program</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Status</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="applicant in encodedData.applicants" :key="applicant.id"
                                    class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ applicant.first_name }} {{ applicant.last_name }}</td>
                                    <td class="px-6 py-4">{{ applicant.program?.name || '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-semibold" :class="{
                                            'bg-yellow-100 text-yellow-800': applicant.status === 'pending',
                                            'bg-green-100 text-green-800': applicant.status === 'approved',
                                            'bg-red-100 text-red-800': applicant.status === 'rejected',
                                        }">
                                            {{ applicant.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ formatDate(applicant.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Scholars Table -->
                <div v-if="encodedData.scholars && encodedData.scholars.length > 0"
                    class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Active Scholars (Last 10)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Program</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Status</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Year Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="scholar in encodedData.scholars" :key="scholar.id"
                                    class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ scholar.first_name }} {{ scholar.last_name }}</td>
                                    <td class="px-6 py-4">{{ scholar.program?.name || '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            {{ scholar.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ scholar.year_level || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vouchers Table -->
                <div v-if="encodedData.vouchers && encodedData.vouchers.length > 0"
                    class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Vouchers (Last 10)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Voucher #</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Type</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Amount</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="voucher in encodedData.vouchers" :key="voucher.id"
                                    class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold">{{ voucher.transaction_id }}</td>
                                    <td class="px-6 py-4">{{ voucher.type || '-' }}</td>
                                    <td class="px-6 py-4">₱ {{ formatCurrency(voucher.amount) }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ formatDate(voucher.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    user: Object,
    stats: Object,
    encodedData: Object,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
    if (!amount) return '0.00';
    return parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
