<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { usePermission } from '@/composable/permissions';

const { hasPermission, hasRole } = usePermission();

const props = defineProps({
    user: Object,
    pendingApplicants: {
        type: Number,
        default: 0
    },
    pendingRequirements: {
        type: Number,
        default: 0
    },
    activeApplications: {
        type: Number,
        default: 0
    },
    recentAnnouncements: {
        type: Array,
        default: () => []
    }
});

const showHelpDialog = ref(false);
const systemUpdates = ref([]);

// Fetch system updates
const fetchSystemUpdates = async () => {
    try {
        const response = await fetch('/api/system-updates', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            systemUpdates.value = data.updates || [];
        }
    } catch (error) {
        console.error('Error fetching system updates:', error);
        // Fallback to empty array
        systemUpdates.value = [];
    }
};

// Fetch system updates on mount
onMounted(() => {
    fetchSystemUpdates();
});

// Sort system updates chronologically (newest first)
const sortedSystemUpdates = computed(() => {
    return [...systemUpdates.value].sort((a, b) => {
        // Parse dates and sort in descending order (newest first)
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB - dateA;
    });
});

// Service Cards Data
const serviceCards = computed(() => {
    const cards = [];

    if (hasPermission('applicants.create')) {
        cards.push({
            id: 'apply',
            title: 'New Application',
            description: 'Submit a new scholarship application',
            icon: 'pi pi-file-edit',
            color: 'blue',
            action: () => router.visit(route('waitinglist.index')),
            badge: props.pendingApplicants > 0 ? props.pendingApplicants : null,
            badgeLabel: 'To Review'
        });
    }

    if (hasPermission('applicants.view')) {
        cards.push({
            id: 'applications',
            title: 'Scholarship Profiles',
            description: 'View and manage scholarship profiles',
            icon: 'pi pi-list',
            color: 'green',
            action: () => router.visit(route('waitinglist.index')),
            badge: props.activeApplications > 0 ? props.activeApplications : null,
            badgeLabel: 'Active'
        });
    }

    if (hasPermission('disbursements.view')) {
        cards.push({
            id: 'vouchers',
            title: 'Vouchers',
            description: 'View and generate disbursement vouchers',
            icon: 'pi pi-credit-card',
            color: 'orange',
            action: () => router.visit(route('vouchers.index'))
        });
    }

    if (hasPermission('documents.view')) {
        cards.push({
            id: 'documents',
            title: 'Documents and Forms',
            description: 'Access downloadable documents and forms',
            icon: 'pi pi-file-pdf',
            color: 'purple',
            action: () => router.visit(route('documents.index'))
        });
    }

    return cards;
});

// Important Links
const importantLinks = [
    {
        label: 'Help & Support',
        icon: 'pi pi-question-circle',
        description: 'Get help with your applications',
        action: () => showHelpDialog.value = true
    },
    {
        label: 'Contact Us',
        icon: 'pi pi-envelope',
        description: 'Reach out to our support team',
        href: 'mailto:scholarship@example.com'
    },
    {
        label: 'Scholarship Programs',
        icon: 'pi pi-building',
        description: 'Learn about available programs',
        action: () => router.visit(route('scholarshipprograms.index'))
    },
    {
        label: 'Guidelines',
        icon: 'pi pi-book',
        description: 'Read application guidelines',
        href: '#'
    }
];

// Get card background color
const getCardBackground = (color) => {
    const colors = {
        blue: 'bg-blue-50',
        green: 'bg-green-50',
        orange: 'bg-orange-50',
        purple: 'bg-purple-50'
    };
    return colors[color] || 'bg-gray-50';
};

// Get card icon color
const getCardIconColor = (color) => {
    const colors = {
        blue: 'text-blue-600',
        green: 'text-green-600',
        orange: 'text-orange-600',
        purple: 'text-purple-600'
    };
    return colors[color] || 'text-gray-600';
};

// Get card shadow
const getCardShadow = (color) => {
    const shadows = {
        blue: 'shadow-blue-200',
        green: 'shadow-green-200',
        orange: 'shadow-orange-200',
        purple: 'shadow-purple-200'
    };
    return shadows[color] || 'shadow-gray-200';
};
</script>

<template>

    <Head title="Home" />
    <AdminLayout>
        <Toolbar class="border-0 bg-transparent p-0">
            <template #start>
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-16 h-16">
                        <i class="pi pi-graduation-cap text-indigo-900" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-700">PGP Scholarship Portal</h1>
                        <p class="text-gray-600 mt-1">A centralized platform for managing scholarship applications,
                            requirements, and updates.</p>
                    </div>
                </div>
            </template>
        </Toolbar>
        <div class="space-y-8 py-12">
            <!-- Service Cards Section -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <i class="pi pi-th-large text-2xl text-blue-600"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Services</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                    <div v-for="card in serviceCards" :key="card.id" :class="[
                        'p-6 rounded-lg cursor-pointer transition-all duration-300 transform hover:scale-105 text-center shadow-md hover:shadow-lg',
                        getCardBackground(card.color),
                        getCardShadow(card.color)
                    ]" @click="card.action">
                        <div class="flex justify-center mb-4">
                            <div :class="['text-6xl', getCardIconColor(card.color)]">
                                <i :class="card.icon" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ card.title }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ card.description }}</p>
                        <div v-if="card.badge" class="inline-block">
                            <Badge v-if="card.badge" :value="card.badge" severity="danger" />
                            <div class="text-xs text-gray-500 font-medium mt-1">
                                {{ card.badge }} {{ card.badgeLabel }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area - System Updates -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- System Updates -->
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-bell text-3xl text-indigo-600"></i>
                        <h2 class="text-2xl font-bold text-gray-800">System Updates</h2>
                    </div>
                    <Panel>
                        <div v-if="systemUpdates && systemUpdates.length > 0" class="space-y-4">
                            <div v-for="(update, idx) in sortedSystemUpdates.slice(0, 5)" :key="idx"
                                class="p-4 rounded shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                                @click="router.visit(route('system-updates.index'))">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-semibold text-gray-800">{{ update.title }}</h4>
                                    <Tag v-if="update.type" :value="update.type" severity="info" class="text-xs" />
                                </div>
                                <p class="text-sm text-gray-600 mb-2">{{ update.content }}</p>
                                <div class="text-xs text-gray-500">
                                    {{ update.created_at }}
                                </div>
                            </div>
                            <div v-if="sortedSystemUpdates.length > 5" class="mt-4 pt-4 border-t text-center">
                                <Button label="View All System Updates" icon="pi pi-arrow-right" link
                                    @click="router.visit(route('system-updates.index'))" class="w-full"
                                    severity="info" />
                            </div>
                            <div v-if="systemUpdates.length === 0" class="text-center py-8">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No system updates at the moment</p>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">No system updates at the moment</p>
                        </div>
                    </Panel>
                </div>

                <!-- Important Links & Help -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-link text-2xl text-indigo-600"></i>
                        <h2 class="text-2xl font-bold text-gray-800">Quick Links</h2>
                    </div>
                    <Panel>
                        <div class="space-y-3">
                            <div v-for="link in importantLinks" :key="link.label"
                                class="p-3 border rounded-lg hover:bg-indigo-50 cursor-pointer transition-colors"
                                @click="link.action || (link.href ? window.open(link.href) : null)">
                                <div class="flex items-start gap-3">
                                    <div class="text-indigo-600 text-xl pt-1">
                                        <i :class="link.icon"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800">{{ link.label }}</h4>
                                        <p class="text-xs text-gray-500">{{ link.description }}</p>
                                    </div>
                                    <i class="pi pi-chevron-right text-gray-400 pt-1"></i>
                                </div>
                            </div>
                        </div>
                    </Panel>
                </div>
            </div>

            <!-- Mental Model Section -->
            <Panel header="Mental Model: What Do I Need To Do?" class="border-t-4 border-t-indigo-600">
                <Tabs>
                    <TabList>
                        <Tab>I'm New</Tab>
                        <Tab>I Have Pending Items</Tab>
                        <Tab>Need Help?</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel>
                            <div class="space-y-4">
                                <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold flex-shrink-0">
                                        1</div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Start Application</h4>
                                        <p class="text-sm text-gray-600">Click "Apply for Scholarship" to begin your
                                            application process</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold flex-shrink-0">
                                        2</div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Complete Information</h4>
                                        <p class="text-sm text-gray-600">Fill in your personal, family, and academic
                                            information</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold flex-shrink-0">
                                        3</div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Upload Requirements</h4>
                                        <p class="text-sm text-gray-600">Submit required documents in the Requirements
                                            section</p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel>
                            <div class="space-y-4">
                                <div class="flex items-start gap-4 p-4 bg-orange-50 rounded-lg">
                                    <i class="pi pi-exclamation-triangle text-2xl text-orange-600 flex-shrink-0"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Check Your Status</h4>
                                        <p class="text-sm text-gray-600">Go to "Check Status" to see what items need
                                            attention</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-orange-50 rounded-lg">
                                    <i class="pi pi-cloud-upload text-2xl text-orange-600 flex-shrink-0"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-1">Upload Missing Documents</h4>
                                        <p class="text-sm text-gray-600">Use "Upload Requirements" to submit any missing
                                            documents</p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel>
                            <div class="space-y-4">
                                <div class="p-4 bg-green-50 rounded-lg">
                                    <h4 class="font-semibold text-gray-800 mb-2">Common Issues</h4>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check text-green-600"></i>
                                            <span>Login issues? Reset your password on the login page</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check text-green-600"></i>
                                            <span>Application rejected? Check the feedback section for details</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check text-green-600"></i>
                                            <span>Document not uploading? Ensure file is in supported format</span>
                                        </li>
                                    </ul>
                                </div>
                                <Button label="Contact Support" icon="pi pi-envelope" severity="info" class="w-full"
                                    @click="showHelpDialog = true" />
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </Panel>
        </div>

        <!-- Help Dialog -->
        <Dialog v-model:visible="showHelpDialog" modal header="Help & Support" :style="{ width: '50vw' }">
            <div class="space-y-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <h4 class="font-semibold text-blue-900 mb-2">Scholarship Support Team</h4>
                    <p class="text-sm text-blue-800 mb-2">Email: scholarship@example.com</p>
                    <p class="text-sm text-blue-800">Phone: +1 (555) XXX-XXXX</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <h4 class="font-semibold text-gray-800 mb-3">Common Questions</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="font-medium text-gray-700">Q: How long does application review take?</p>
                            <p class="text-sm text-gray-600 mt-1">A: Applications are typically reviewed within 2-4
                                weeks of submission</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700">Q: Can I edit my application after submission?</p>
                            <p class="text-sm text-gray-600 mt-1">A: You can edit until your application is under review
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Close" @click="showHelpDialog = false" severity="secondary" />
            </template>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
/* Add custom animations if needed */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

:deep(.p-panel) {
    border: 1px solid #e5e7eb;
}
</style>
