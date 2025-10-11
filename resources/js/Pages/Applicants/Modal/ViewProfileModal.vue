<template>
    <Dialog :visible="isOpen" modal :header="getHeaderTitle()" :style="{ width: '90vw', maxWidth: '1200px' }"
        :closable="true" @update:visible="(value) => !value && handleCloseModal()" class="p-fluid">
        <!-- Header Summary Card -->
        <div class="grid grid-cols-12 gap-2 mb-2">
            <div class="col-span-12">
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <Avatar :label="getInitials()" size="large" shape="circle" class="bg-gray-600 text-white" />
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex flex-col items-start gap-1">
                                        <h3 class="text-xl font-bold text-gray-900">{{ getFullName() }}</h3>
                                        <div class="flex items-center gap-4">

                                            <div class="flex gap-3 text-sm text-gray-600">
                                                <span><i class="pi pi-phone mr-1"></i>{{ profile.contact_no || 'N/A'
                                                }}</span>
                                                <span><i class="pi pi-envelope mr-1"></i>{{ profile.email || 'N/A'
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="text-center px-2 py-1 bg-indigo-100 rounded-md border border-indigo-200">
                                                <div class="text-sm font-bold text-indigo-600 leading-tight">
                                                    #{{ profile.sequence_number || '-' }}
                                                </div>
                                                <div class="text-xs text-indigo-700 leading-tight">Program</div>
                                            </div>
                                            <div
                                                class="text-center px-2 py-1 bg-purple-100 rounded-md border border-purple-200">
                                                <div class="text-sm font-bold text-purple-600 leading-tight">
                                                    #{{ profile.sequence_number_by_course || '-' }}
                                                </div>
                                                <div class="text-xs text-purple-700 leading-tight">Course</div>
                                            </div>
                                            <div
                                                class="text-center px-2 py-1 bg-orange-100 rounded-md border border-orange-200">
                                                <div class="text-sm font-bold text-orange-600 leading-tight">
                                                    #{{ profile.daily_sequence_number || '-' }}
                                                </div>
                                                <div class="text-xs text-orange-700 leading-tight">Date</div>
                                            </div>
                                            <div
                                                class="text-center px-2 py-1 bg-green-100 rounded-md border border-green-200">
                                                <div class="text-sm font-bold text-green-600 leading-tight">
                                                    #{{ profile.sequence_number_by_school_course || '-' }}
                                                </div>
                                                <div class="text-xs text-green-700 leading-tight">School/Course</div>
                                            </div>
                                        </div>
                                        <div class="bg-emerald-100 border border-emerald-300 rounded-lg px-2 py-1">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar text-emerald-700 text-sm"></i>
                                                <div>
                                                    <div class="text-xs text-emerald-600 font-medium leading-tight">Date
                                                        Filed
                                                    </div>
                                                    <div class="text-sm font-bold text-emerald-800 leading-tight">{{
                                                        formatDate(profile.date_filed) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Tabbed Content -->
        <TabView>
            <TabPanel header="Personal Info">
                <div class="grid grid-cols-12 gap-4">
                    <!-- Personal Information -->
                    <div class="col-span-6">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-blue-600"></i>
                                    Personal Information
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Full
                                                Name</label>
                                            <InputText :value="getFullName()" readonly class="w-full" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                            <InputText
                                                :value="profile.gender === 'M' ? 'Male' : profile.gender === 'F' ? 'Female' : 'N/A'"
                                                readonly class="w-full" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary
                                                Contact</label>
                                            <InputText :value="profile.contact_no || 'N/A'" readonly class="w-full" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Secondary
                                                Contact</label>
                                            <InputText :value="profile.contact_no_2 || 'N/A'" readonly class="w-full" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email
                                            Address</label>
                                        <InputText :value="profile.email || 'N/A'" readonly class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                        <Textarea :value="getFullAddress()" readonly class="w-full" rows="2" />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Academic Information -->
                    <div class="col-span-6">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-graduation-cap text-green-600"></i>
                                    Academic Information
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.program?.shortname || 'N/A'"
                                            readonly class="w-full" />

                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">School</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.school?.shortname || 'N/A'"
                                            readonly class="w-full" />

                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.course?.shortname || 'N/A'"
                                            readonly class="w-full" />

                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.year_level || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic
                                            Year</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.academic_year || 'N/A'"
                                            readonly class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                                        <InputText :value="profile.scholarship_grant?.[0]?.term || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                    <Textarea :value="profile.remarks || 'No remarks provided'" readonly class="w-full"
                                        rows="2" />
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </TabPanel>

            <TabPanel header="Family Info">
                <div class="grid grid-cols-12 gap-4">
                    <!-- Father Information -->
                    <div class="col-span-4">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-blue-600"></i>
                                    Father
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                        <InputText :value="profile.father_name || 'N/A'" readonly class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                        <InputText :value="profile.father_occupation || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                        <InputText :value="profile.father_contact_no || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Mother Information -->
                    <div class="col-span-4">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-pink-600"></i>
                                    Mother
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                        <InputText :value="profile.mother_name || 'N/A'" readonly class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                        <InputText :value="profile.mother_occupation || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                        <InputText :value="profile.mother_contact_no || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Guardian Information -->
                    <div class="col-span-4">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-users text-green-600"></i>
                                    Guardian
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                        <InputText :value="profile.guardian_name || 'N/A'" readonly class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                                        <InputText :value="profile.guardian_relationship || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                                        <InputText :value="profile.guardian_contact_no || 'N/A'" readonly
                                            class="w-full" />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <!-- Monthly Income -->
                <div class="mt-4">
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-wallet text-amber-600"></i>
                                Financial Information
                            </div>
                        </template>
                        <template #content>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Gross Income</label>
                                <InputText :value="formatCurrency(profile.parents_guardian_gross_monthly_income)"
                                    readonly class="w-full" />
                            </div>
                        </template>
                    </Card>
                </div>
            </TabPanel>
        </TabView>

        <!-- Action Buttons -->
        <template #footer>
            <div class="dialog-footer">
                <div class="flex gap-3">
                    <template v-if="hasPermission('approve-scholar-profile')">
                        <Button label="Decline" icon="pi pi-times" severity="danger" outlined
                            @click="declineApplication" />
                        <Button label="Approve" icon="pi pi-check" severity="success"
                            @click="showApproveModal = true" />
                    </template>
                </div>
                <Button label="Close" icon="pi pi-times" severity="secondary" outlined @click="handleCloseModal" />
            </div>
        </template>

        <!-- Decline Modal -->
        <Dialog v-model:visible="showDeclineModal" modal header="Decline Application" :style="{ width: '450px' }">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Decline</label>
                <Textarea v-model="form.remarks" rows="4" class="w-full" placeholder="Please provide a reason..." />
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDeclineModal = false" />
                <Button label="Confirm Decline" severity="danger" @click="confirmDecline" />
            </template>
        </Dialog>

        <!-- Approve Modal -->
        <Dialog v-model:visible="showApproveModal" modal header="Approve Application" :style="{ width: '450px' }">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Approved</label>
                <Calendar v-model="form.date_approved" showIcon dateFormat="yy-mm-dd" class="w-full" />
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showApproveModal = false" />
                <Button label="Confirm Approval" severity="success" @click="confirmApprove" />
            </template>
        </Dialog>
    </Dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import moment from 'moment';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { usePermission } from '@/composable/permissions';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Calendar from 'primevue/calendar';
import Avatar from 'primevue/avatar';

const { hasPermission, hasRole } = usePermission();

const props = defineProps({
    profile: Object,
    action: String,
    msg: String,
    errors: Object,
    isOpen: Boolean
});

const emit = defineEmits(['close']);

const form = useForm({
    date_approved: '',
    remarks: ''
});

const showDeclineModal = ref(false);
const showApproveModal = ref(false);

// Helper Functions
const getHeaderTitle = () => {
    return 'View Application';
};

const getFullName = () => {
    const parts = [
        props.profile.last_name,
        props.profile.first_name,
        props.profile.middle_name,
        props.profile.extension_name
    ].filter(Boolean);

    if (parts.length === 0) return 'N/A';

    const lastName = parts[0];
    const otherNames = parts.slice(1).join(' ');
    return `${lastName}, ${otherNames}`;
};

const getInitials = () => {
    const firstName = props.profile.first_name || '';
    const lastName = props.profile.last_name || '';
    return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase() || 'NA';
};

const getFullAddress = () => {
    const parts = [
        props.profile.address,
        props.profile.barangay,
        props.profile.municipality
    ].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : 'N/A';
};

const formatDate = (date) => {
    return date ? moment(date).format('MMM DD, YYYY') : 'N/A';
};

const formatCurrency = (amount) => {
    return amount ? `₱${Number(amount).toLocaleString()}` : 'N/A';
};

const handleCloseModal = () => {
    showDeclineModal.value = false;
    showApproveModal.value = false;
    emit('close');
};

const declineApplication = () => {
    showDeclineModal.value = true;
};

const confirmDecline = () => {
    const recordId = props.profile.scholarship_grant && Array.isArray(props.profile.scholarship_grant) && props.profile.scholarship_grant.length > 0
        ? props.profile.scholarship_grant[0].id
        : null;

    if (recordId) {
        form.put(route("scholarship-record.decline", recordId), {
            onSuccess: (response) => {
                toast.success("Application declined successfully", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                router.visit(route('profile.waitinglist', { id: props.profile.profile_id, action: 'view' }));
            },
            onError: (err) => {
                form.errors = err;
                console.log(err);
            }
        });
    } else {
        toast.error("No scholarship record found to decline.", {
            position: toast.POSITION.TOP_RIGHT,
        });
    }
    showDeclineModal.value = false;
    handleCloseModal();
};

const confirmApprove = () => {
    const recordId = props.profile.scholarship_grant && Array.isArray(props.profile.scholarship_grant) && props.profile.scholarship_grant.length > 0
        ? props.profile.scholarship_grant[0].id
        : null;

    if (recordId) {
        form.post(route("scholarship-record.approve", recordId), {
            onSuccess: (response) => {
                toast.success("Application approved successfully", {
                    position: toast.POSITION.TOP_RIGHT,
                });
                router.visit(route('profile.index', { id: props.profile.profile_id, action: 'view' }));
            },
            onError: (err) => {
                form.errors = err;
                console.log(err);
            }
        });
    } else {
        toast.error("No scholarship record found to approve.", {
            position: toast.POSITION.TOP_RIGHT,
        });
    }
    showApproveModal.value = false;
    handleCloseModal();
};
</script>

<style scoped>
.p-tabview .p-tabview-nav li .p-tabview-nav-link {
    font-weight: 500;
}

.p-card .p-card-title {
    font-size: 1rem;
    font-weight: 600;
}

.p-card .p-card-content {
    padding-top: 0;
}

.dialog-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}
</style>