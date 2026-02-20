<template>

    <Head title="Review Deleted Records" />
    <AdminLayout>
        <div class="px-6 py-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Deleted Records Management</h1>
                    <p class="text-gray-600 mt-2">Review and restore deleted applicants and scholarship records</p>
                </div>
            </div>

            <!-- Search Filter -->
            <div class="mb-6">
                <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg p-3">
                    <i class="pi pi-search text-gray-500"></i>
                    <input v-model="searchQuery" type="text" placeholder="Search by name..."
                        class="flex-1 outline-none text-gray-700 placeholder-gray-400" />
                    <button v-if="searchQuery" @click="searchQuery = ''" class="text-gray-500 hover:text-gray-700">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
            </div>

            <!-- Tabs for switching between deleted profiles and records -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex gap-4">
                    <button @click="activeTab = 'profiles'" :class="[
                        'px-4 py-2 font-medium text-sm border-b-2 transition-colors',
                        activeTab === 'profiles'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-600 hover:text-gray-900'
                    ]">
                        <i class="pi pi-user mr-2"></i>Deleted Profiles ({{ filteredProfiles.length }})
                    </button>
                    <button @click="activeTab = 'records'" :class="[
                        'px-4 py-2 font-medium text-sm border-b-2 transition-colors',
                        activeTab === 'records'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-600 hover:text-gray-900'
                    ]">
                        <i class="pi pi-award mr-2"></i>Deleted Scholarship Grants ({{ filteredRecords.length }})
                    </button>
                </nav>
            </div>

            <!-- Info banner -->
            <div v-if="activeTab === 'profiles'" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800"><strong>✓ Tip:</strong> Restoring a profile will restore the entire
                    profile AND all their deleted scholarship grants.</p>
            </div>
            <div v-if="activeTab === 'records'" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800"><strong>✓ Tip:</strong> Restoring a scholarship grant will restore
                    only that individual grant record. The applicant profile must exist to restore this.</p>
            </div>

            <!-- Deleted Applicant Profiles Tab -->
            <div v-if="activeTab === 'profiles'" class="space-y-4">
                <div v-if="filteredProfiles.length === 0"
                    class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <i class="pi pi-info-circle text-2xl text-blue-500 mb-2"></i>
                    <p class="text-gray-700">No deleted profiles found</p>
                </div>

                <div v-for="profile in filteredProfiles" :key="profile.profile_id"
                    class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ profile.last_name }}, {{ profile.first_name }}{{ profile.middle_name ? ' ' +
                                    profile.middle_name : '' }}
                            </h3>
                            <div class="mt-2 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Profile ID</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.profile_id }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Contact</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.contact_no }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.email || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Scholarship Records</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.records_count }} record(s)
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p class="text-xs text-gray-500">
                                    <i class="pi pi-trash text-red-500 mr-1"></i>
                                    Deleted {{ formatDate(profile.deleted_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2 ml-4">
                            <Button icon="pi pi-undo" severity="success" rounded
                                @click="restoreProfile(profile.profile_id)"
                                title="Restore this applicant and their records" />
                            <Button icon="pi pi-trash" severity="danger" rounded
                                @click="confirmPermanentlyDeleteProfile(profile)"
                                title="Permanently delete (cannot be undone)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deleted Scholarship Grants Tab -->
            <div v-if="activeTab === 'records'" class="space-y-4">
                <div v-if="filteredRecords.length === 0"
                    class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <i class="pi pi-info-circle text-2xl text-blue-500 mb-2"></i>
                    <p class="text-gray-700">No deleted scholarship grants found</p>
                </div>

                <div v-for="record in filteredRecords" :key="record.id"
                    class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ record.profile_name }}
                            </h3>
                            <div class="mt-2 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Program</p>
                                    <p class="text-sm font-medium text-gray-900">{{ record.program_name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <p class="text-sm font-medium text-gray-900">{{ record.status }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p class="text-xs text-gray-500">
                                    <i class="pi pi-trash text-red-500 mr-1"></i>
                                    Deleted {{ formatDate(record.deleted_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2 ml-4">
                            <Button icon="pi pi-undo" severity="success" rounded @click="restoreRecord(record.id)"
                                title="Restore this record" />
                            <Button icon="pi pi-trash" severity="danger" rounded
                                @click="confirmPermanentlyDeleteRecord(record)"
                                title="Permanently delete (cannot be undone)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Profile Confirmation Modal -->
        <Dialog v-model:visible="showDeleteProfileConfirmDialog" modal header="Confirm Permanent Deletion"
            :style="{ width: '500px' }">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Permanently Delete Profile</p>
                        <p class="text-sm text-gray-600">This action CANNOT be undone</p>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-3">
                    <p class="text-sm text-red-800">
                        <strong>Profile:</strong> {{ profileToDelete ? `${profileToDelete.last_name},
                        ${profileToDelete.first_name}${profileToDelete.middle_name ? ' ' + profileToDelete.middle_name :
                                ''}` : 'N/A' }}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteProfileConfirmDialog = false" outlined />
                <Button label="Permanently Delete" severity="danger" @click="permanentlyDeleteProfile" />
            </template>
        </Dialog>

        <!-- Delete Record Confirmation Modal -->
        <Dialog v-model:visible="showDeleteRecordConfirmDialog" modal header="Confirm Permanent Deletion"
            :style="{ width: '500px' }">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-triangle text-2xl text-red-500"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Permanently Delete Scholarship Record</p>
                        <p class="text-sm text-gray-600">This action CANNOT be undone</p>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-3">
                    <p class="text-sm text-red-800">
                        <strong>Record:</strong> {{ recordToDelete ? recordToDelete.profile_name : 'N/A' }}
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDeleteRecordConfirmDialog = false" outlined />
                <Button label="Permanently Delete" severity="danger" @click="permanentlyDeleteRecord" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    deletedProfiles: {
        type: Array,
        default: () => []
    },
    deletedRecords: {
        type: Array,
        default: () => []
    }
});

console.log('DeletedRecords component loaded');
console.log('Deleted Profiles:', props.deletedProfiles);
console.log('Deleted Records:', props.deletedRecords);

// Profile IDs for debugging
if (props.deletedProfiles.length > 0) {
    console.log('First profile ID:', props.deletedProfiles[0].id);
    console.log('First profile data:', props.deletedProfiles[0]);
}

const activeTab = ref('profiles');
const searchQuery = ref('');
const showDeleteProfileConfirmDialog = ref(false);
const profileToDelete = ref(null);
const showDeleteRecordConfirmDialog = ref(false);
const recordToDelete = ref(null);

const filteredProfiles = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.deletedProfiles;
    }
    const query = searchQuery.value.toLowerCase();
    return props.deletedProfiles.filter(profile => {
        const fullName = `${profile.last_name}, ${profile.first_name}${profile.middle_name ? ' ' + profile.middle_name : ''}`.toLowerCase();
        return fullName.includes(query) || profile.email?.toLowerCase().includes(query) || profile.contact_no?.includes(query);
    });
});

const filteredRecords = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.deletedRecords;
    }
    const query = searchQuery.value.toLowerCase();
    return props.deletedRecords.filter(record => {
        return record.profile_name?.toLowerCase().includes(query) || record.program_name?.toLowerCase().includes(query);
    });
});

const showNotification = (message, type = 'success') => {
    if (type === 'error') {
        toast.error(message);
    } else {
        toast.success(message);
    }
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const restoreProfile = async (profileId) => {
    if (!profileId) {
        showNotification('Invalid profile ID', 'error');
        return;
    }

    try {
        const response = await axios.post(`/admin/profiles/${profileId}/restore`);
        showNotification(response.data?.message || 'Profile and related records restored successfully.');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        console.error('Restore error:', error);
        showNotification(error.response?.data?.message || 'Failed to restore profile', 'error');
    }
};

const restoreRecord = async (recordId) => {
    if (!recordId) {
        showNotification('Invalid record ID', 'error');
        return;
    }

    try {
        const response = await axios.post(`/admin/scholarship-records/${recordId}/restore`);
        showNotification(response.data.message);
        setTimeout(() => location.reload(), 1500);
    } catch (error) {
        showNotification(error.response?.data?.message || 'Failed to restore record', 'error');
    }
};

const confirmPermanentlyDeleteProfile = async (profile) => {
    profileToDelete.value = profile;
    showDeleteProfileConfirmDialog.value = true;
};

const permanentlyDeleteProfile = async () => {
    if (!profileToDelete.value) return;

    const profile = profileToDelete.value;
    showDeleteProfileConfirmDialog.value = false;

    try {
        await axios.delete(route('admin.profiles.permanently-delete', profile.id));
        showNotification('Profile permanently deleted.');
        setTimeout(() => location.reload(), 1500);
    } catch (error) {
        showNotification(error.response?.data?.message || 'Failed to permanently delete profile', 'error');
    } finally {
        profileToDelete.value = null;
    }
};

const confirmPermanentlyDeleteRecord = async (record) => {
    recordToDelete.value = record;
    showDeleteRecordConfirmDialog.value = true;
};

const permanentlyDeleteRecord = async () => {
    if (!recordToDelete.value) return;

    const record = recordToDelete.value;
    showDeleteRecordConfirmDialog.value = false;

    try {
        await axios.delete(route('admin.records.permanently-delete', record.id));
        showNotification('Record permanently deleted.');
        setTimeout(() => location.reload(), 1500);
    } catch (error) {
        showNotification(error.response?.data?.message || 'Failed to permanently delete record', 'error');
    } finally {
        recordToDelete.value = null;
    }
};
</script>

<style scoped>
.p-button {
    padding: 0.5rem 1rem;
}
</style>
