<template>

    <Head :title="`${profile.first_name} ${profile.last_name} - Scholar Profile`" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" text rounded @click="router.visit(route('scholarship.profiles'))"
                        v-tooltip.top="'Back to Profiles'" />
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ profile.first_name }} {{ profile.middle_name }} {{ profile.last_name }}
                            {{ profile.extension_name }}
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Scholar Profile</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button icon="pi pi-pencil" label="Edit" severity="warning" outlined @click="editProfile"
                        v-tooltip.top="'Edit Profile'" />
                    <Button icon="pi pi-history" label="History" severity="secondary" outlined @click="viewHistory"
                        v-tooltip.top="'View History'" />
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-lg shadow">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Personal Information</Tab>
                        <Tab value="1">Family Information</Tab>
                        <Tab value="2">Academic Information</Tab>
                        <Tab value="3">Attachments</Tab>
                        <Tab value="4">Obligations & Transactions</Tab>
                    </TabList>
                    <TabPanels>
                        <!-- Personal Information Tab -->
                        <TabPanel value="0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <!-- Basic Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Full Name</label>
                                        <p class="text-gray-900">{{ fullName }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Date of Birth</label>
                                        <p class="text-gray-900">{{ formatDate(profile.date_of_birth) }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Age</label>
                                        <p class="text-gray-900">{{ calculateAge(profile.date_of_birth) }} years old</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Gender</label>
                                        <p class="text-gray-900">{{ profile.gender || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Civil Status</label>
                                        <p class="text-gray-900">{{ profile.civil_status || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Religion</label>
                                        <p class="text-gray-900">{{ profile.religion || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Place of Birth</label>
                                        <p class="text-gray-900">{{ profile.place_of_birth || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Indigenous Group</label>
                                        <p class="text-gray-900">{{ profile.indigenous_group || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Contact Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Primary Contact</label>
                                        <p class="text-gray-900">{{ profile.contact_no || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Secondary Contact</label>
                                        <p class="text-gray-900">{{ profile.contact_no_2 || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Email</label>
                                        <p class="text-gray-900">{{ profile.email || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Permanent Address</label>
                                        <p class="text-gray-900">
                                            {{ profile.address }}<br>
                                            {{ profile.barangay }}, {{ profile.municipality }}
                                        </p>
                                    </div>

                                    <div v-if="profile.temporary_address || profile.temporary_municipality">
                                        <label class="text-sm font-medium text-gray-600">Present Address</label>
                                        <p class="text-gray-900">
                                            {{ profile.temporary_address || 'N/A' }}<br>
                                            {{ profile.temporary_barangay || 'N/A' }}, {{ profile.temporary_municipality
                                                ||
                                            'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Family Information Tab -->
                        <TabPanel value="1">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                                <!-- Father's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Father's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.father_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.father_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.father_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Mother's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Mother's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.mother_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.mother_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.mother_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Guardian's Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Guardian's Information
                                    </h3>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Name</label>
                                        <p class="text-gray-900">{{ profile.guardian_name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Relationship</label>
                                        <p class="text-gray-900">{{ profile.guardian_relationship || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Occupation</label>
                                        <p class="text-gray-900">{{ profile.guardian_occupation || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Contact</label>
                                        <p class="text-gray-900">{{ profile.guardian_contact_no || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Gross Monthly Income -->
                                <div class="col-span-full">
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Parents/Guardian Gross Monthly
                                            Income</label>
                                        <p class="text-gray-900 text-lg font-semibold">
                                            {{ formatCurrency(profile.parents_guardian_gross_monthly_income) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Academic Information Tab -->
                        <TabPanel value="2">
                            <div class="p-6">
                                <div v-if="currentGrant" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Program</label>
                                            <p class="text-gray-900">{{ currentGrant.program || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">School</label>
                                            <p class="text-gray-900">{{ currentGrant.school || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Course</label>
                                            <p class="text-gray-900">{{ currentGrant.course || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Year Level</label>
                                            <p class="text-gray-900">{{ currentGrant.year_level || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Term</label>
                                            <p class="text-gray-900">{{ currentGrant.term || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Academic Year</label>
                                            <p class="text-gray-900">{{ currentGrant.academic_year || 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Date Filed</label>
                                            <p class="text-gray-900">{{ formatDate(currentGrant.date_filed) }}</p>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-600">Date Approved</label>
                                            <p class="text-gray-900">{{ formatDate(currentGrant.date_approved) }}</p>
                                        </div>

                                        <div class="col-span-full">
                                            <label class="text-sm font-medium text-gray-600">Remarks</label>
                                            <p class="text-gray-900">{{ currentGrant.remarks || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12">
                                    <i class="pi pi-info-circle text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No academic information available</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Attachments Tab -->
                        <TabPanel value="3">
                            <div class="p-6">
                                <div class="text-center py-12">
                                    <i class="pi pi-paperclip text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">Attachments Module</p>
                                    <p class="text-gray-400 text-sm mt-2">Coming soon - File upload and document
                                        management
                                    </p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Obligations/Transactions Tab -->
                        <TabPanel value="4">
                            <div class="p-6">
                                <div class="text-center py-12">
                                    <i class="pi pi-money-bill text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">Obligations & Transactions Module</p>
                                    <p class="text-gray-400 text-sm mt-2">Coming soon - Obligation requests and
                                        transaction
                                        history</p>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>

        <!-- Edit Modal -->
        <ScholarFormModal v-model:visible="showEditModal" mode="edit" :profile="profile" @success="handleSuccess" />
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';

const props = defineProps({
    profile: Object,
});

// State
const showEditModal = ref(false);

// Computed
const fullName = computed(() => {
    return `${props.profile.first_name} ${props.profile.middle_name || ''} ${props.profile.last_name} ${props.profile.extension_name || ''}`.trim();
});

const currentGrant = computed(() => {
    return props.profile.scholarship_grant?.[0] || null;
});

// Methods
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const calculateAge = (dateString) => {
    if (!dateString) return 'N/A';
    const birthDate = new Date(dateString);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const editProfile = () => {
    showEditModal.value = true;
};

const viewHistory = () => {
    router.visit(route('scholarship.profile.history', props.profile.profile_id));
};

const handleSuccess = () => {
    showEditModal.value = false;
    router.reload({ only: ['profile'] });
};
</script>
