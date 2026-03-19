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
        systemUpdates.value = [];
    }
};

onMounted(() => {
    fetchSystemUpdates();
});

const sortedSystemUpdates = computed(() => {
    return [...systemUpdates.value].sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB - dateA;
    });
});

// Service Cards
const serviceCards = computed(() => {
    const cards = [];

    if (hasPermission('applicants.create')) {
        cards.push({
            id: 'apply',
            title: 'New Application',
            description: 'Submit a new scholarship application',
            icon: 'pi pi-file-edit',
            iconBg: 'bg-[#007AFF]',
            action: () => router.visit(route('applicants.index')),
            badge: props.pendingApplicants > 0 ? props.pendingApplicants : null,
            badgeIcon: 'pi pi-clock',
            badgeColor: 'text-orange-500'
        });
    }

    if (hasPermission('applicants.view')) {
        cards.push({
            id: 'applications',
            title: 'Scholarship Profiles',
            description: 'View and manage scholarship profiles',
            icon: 'pi pi-list',
            iconBg: 'bg-[#34C759]',
            action: () => router.visit(route('applicants.index')),
            badge: props.activeApplications > 0 ? props.activeApplications : null,
            badgeIcon: 'pi pi-check-circle',
            badgeColor: 'text-green-500'
        });
    }

    if (hasPermission('disbursements.view')) {
        cards.push({
            id: 'fund_transactions',
            title: 'Fund Transactions',
            description: 'View and generate fund transactions',
            icon: 'pi pi-credit-card',
            iconBg: 'bg-[#FF9500]',
            action: () => router.visit(route('fund_transactions.index'))
        });
    }

    if (hasPermission('documents.view')) {
        cards.push({
            id: 'documents',
            title: 'Documents & Forms',
            description: 'Access downloadable documents and forms',
            icon: 'pi pi-file-pdf',
            iconBg: 'bg-[#AF52DE]',
            action: () => router.visit(route('documents.index'))
        });
    }

    return cards;
});

// Quick Links
const quickLinks = [
    {
        label: 'Help & Support',
        icon: 'pi pi-question-circle',
        iconColor: 'text-[#007AFF]',
        description: 'Get help with your applications',
        action: () => showHelpDialog.value = true
    },
    {
        label: 'Contact Us',
        icon: 'pi pi-envelope',
        iconColor: 'text-[#34C759]',
        description: 'Reach out to our support team',
        href: 'mailto:scholarship@example.com'
    },
    {
        label: 'Scholarship Programs',
        icon: 'pi pi-building',
        iconColor: 'text-[#FF9500]',
        description: 'Learn about available programs',
        action: () => router.visit(route('scholarshipprograms.index'))
    },
    {
        label: 'Guidelines',
        icon: 'pi pi-book',
        iconColor: 'text-[#AF52DE]',
        description: 'Read application guidelines',
        href: '#'
    }
];


</script>

<template>

    <Head title="Home" />
    <AdminLayout>
        <!-- Toolbar — consistent with other pages -->
        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <i class="pi pi-graduation-cap text-indigo-900" style="font-size: 2rem"></i>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-700">PGP Scholarship Portal</h1>
                        <p class="text-sm text-gray-600">A centralized platform for managing scholarship applications,
                            requirements, and updates.</p>
                    </div>
                </div>
            </template>
        </Toolbar>

        <div class="space-y-6">

            <!-- ─── Services Section ─── -->
            <div class="ios-section">
                <div class="ios-section-label">SERVICES</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="card in serviceCards" :key="card.id" class="ios-card service-card group cursor-pointer"
                        @click="card.action">
                        <div class="p-6 flex flex-col items-center text-center">
                            <div
                                :class="[card.iconBg, 'w-12 h-12 rounded-[14px] flex items-center justify-center mb-3 shadow-sm transition-transform duration-200 group-hover:scale-110']">
                                <i :class="card.icon" class="text-white" style="font-size: 1.25rem"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-[#1d1d1f] mb-1">{{ card.title }}</h3>
                            <p class="text-xs text-[#6D6D72] leading-relaxed">{{ card.description }}</p>
                            <div v-if="card.badge" class="mt-3 flex items-center gap-1.5"
                                v-tooltip.bottom="card.badge + ' item(s)'">
                                <i :class="[card.badgeIcon, card.badgeColor]" style="font-size: 0.85rem"></i>
                                <Badge :value="card.badge" severity="danger" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── System Updates + Quick Links ─── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- System Updates -->
                <div class="lg:col-span-2 ios-section">
                    <div class="ios-section-label">SYSTEM UPDATES</div>
                    <div class="ios-card overflow-hidden">
                        <div v-if="sortedSystemUpdates.length > 0">
                            <div v-for="(update, idx) in sortedSystemUpdates.slice(0, 5)" :key="idx"
                                class="ios-row cursor-pointer hover:bg-[#F2F2F7] transition-colors"
                                @click="router.visit(route('system-updates.index'))">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-[#1d1d1f] truncate">{{ update.title }}
                                        </h4>
                                        <p class="text-xs text-[#6D6D72] mt-0.5 line-clamp-2">{{ update.content }}</p>
                                        <span class="text-xs text-[#8E8E93] mt-1 block">{{ update.created_at }}</span>
                                    </div>
                                    <i v-if="update.type" class="pi flex-shrink-0" :class="{
                                        'pi-info-circle text-[#007AFF]': update.type === 'info',
                                        'pi-exclamation-triangle text-[#FF9500]': update.type === 'warning',
                                        'pi-check-circle text-[#34C759]': update.type === 'success',
                                        'pi-times-circle text-[#FF3B30]': update.type === 'error'
                                    }" style="font-size: 1rem" v-tooltip.left="update.type"></i>
                                </div>
                            </div>
                            <div v-if="sortedSystemUpdates.length > 5"
                                class="px-4 py-3 text-center border-t border-[#E5E5EA]/50">
                                <button
                                    class="text-[#007AFF] text-sm font-semibold hover:text-[#0055D4] transition-colors"
                                    @click="router.visit(route('system-updates.index'))">
                                    View All Updates
                                </button>
                            </div>
                        </div>
                        <div v-else class="py-10 text-center">
                            <i class="pi pi-inbox text-3xl text-[#C7C7CC] mb-2"></i>
                            <p class="text-sm text-[#8E8E93]">No system updates at the moment</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="ios-section">
                    <div class="ios-section-label">QUICK LINKS</div>
                    <div class="ios-card overflow-hidden">
                        <div v-for="(link, idx) in quickLinks" :key="link.label"
                            class="ios-row cursor-pointer hover:bg-[#F2F2F7] transition-colors"
                            @click="link.action ? link.action() : (link.href ? window.open(link.href) : null)">
                            <div class="flex items-center gap-3">
                                <i :class="[link.icon, link.iconColor]" style="font-size: 1.1rem"></i>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-[#1d1d1f]">{{ link.label }}</h4>
                                    <p class="text-xs text-[#8E8E93]">{{ link.description }}</p>
                                </div>
                                <i class="pi pi-chevron-right text-[#C7C7CC]" style="font-size: 0.75rem"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Help Dialog — iOS style -->
        <Dialog v-model:visible="showHelpDialog" modal
            :pt="{ root: { class: 'ios-dialog-root' }, mask: { class: 'ios-dialog-mask' } }">
            <template #container>
                <div class="ios-help-modal">
                    <!-- Nav bar -->
                    <div class="ios-help-nav">
                        <button class="text-[#8E8E93] text-xl leading-none" @click="showHelpDialog = false">
                            <i class="pi pi-times"></i>
                        </button>
                        <span class="ios-help-title">Help & Support</span>
                        <div class="w-5"></div>
                    </div>

                    <!-- Body -->
                    <div class="ios-help-body">
                        <div class="ios-section">
                            <div class="ios-section-label">CONTACT</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-users text-[#007AFF]"></i>
                                        <div>
                                            <div class="text-sm font-semibold text-[#1d1d1f]">Scholarship Support Team
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ios-row">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-envelope text-[#34C759]"></i>
                                        <span class="text-sm text-[#007AFF]">scholarship@example.com</span>
                                    </div>
                                </div>
                                <div class="ios-row !border-b-0">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-phone text-[#FF9500]"></i>
                                        <span class="text-sm text-[#1d1d1f]">+1 (555) XXX-XXXX</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ios-section">
                            <div class="ios-section-label">FREQUENTLY ASKED</div>
                            <div class="ios-card">
                                <div class="ios-row">
                                    <p class="text-sm font-medium text-[#1d1d1f]">How long does application review take?
                                    </p>
                                    <p class="text-xs text-[#6D6D72] mt-1">Applications are typically reviewed within
                                        2–4 weeks of submission.</p>
                                </div>
                                <div class="ios-row !border-b-0">
                                    <p class="text-sm font-medium text-[#1d1d1f]">Can I edit my application after
                                        submission?</p>
                                    <p class="text-xs text-[#6D6D72] mt-1">You can edit until your application is under
                                        review.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
/* ─── iOS Section Labels ─── */
.ios-section {
    margin-top: 0;
}

.ios-section-label {
    font-size: 13px;
    color: #6D6D72;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    padding: 0 20px 8px;
    font-weight: 500;
}

/* ─── iOS Card (grouped rows) ─── */
.ios-card {
    background: #FFFFFF;
    border-radius: 2rem;
    border: 0.5px solid #E5E5EA;
}

.ios-row {
    padding: 14px 20px;
    border-bottom: 0.5px solid rgba(60, 60, 67, 0.12);
}

.ios-row:last-child {
    border-bottom: none;
}

/* ─── Service Card hover ─── */
.service-card {
    transition: box-shadow 0.2s, transform 0.2s;
}

.service-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* ─── Help Dialog (iOS) ─── */
:deep(.ios-dialog-root.p-dialog) {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-width: 440px;
}

:deep(.ios-dialog-mask) {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
}

.ios-help-modal {
    background: #F2F2F7;
    border-radius: 14px;
    max-height: 85vh;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    width: 100%;
}

.ios-help-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: #FFFFFF;
    border-bottom: 0.5px solid #E5E5EA;
}

.ios-help-title {
    font-size: 17px;
    font-weight: 600;
    color: #000;
    letter-spacing: -0.4px;
}

.ios-help-body {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 16px 16px 24px;
}

.ios-help-body .ios-section+.ios-section {
    margin-top: 22px;
}
</style>
