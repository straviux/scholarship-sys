<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, onMounted } from 'vue';
import IosModal from '@/Components/ui/IosModal.vue';
import { usePermission } from '@/composable/permissions';

// ─── Props ───
const props = defineProps({
    user: Object,
});

// ─── Permission helpers ───
const { hasPermission, isAdmin } = usePermission();

// ─── State ───
const showHelpDialog = ref(false);
const systemUpdates = ref([]);
const drawerItems = ref([]);
const drawerLoading = ref(true);

// ─── App-drawer icon color palette — cycles through iOS-style vibrancy ───
const iconPalette = [
    'bg-[#007AFF]',   // blue
    'bg-[#34C759]',   // green
    'bg-[#FF9500]',   // orange
    'bg-[#AF52DE]',   // purple
    'bg-[#FF3B30]',   // red
    'bg-[#5856D6]',   // indigo
    'bg-[#FF2D55]',   // pink
    'bg-[#00C7BE]',   // teal
];

// ─── Convert PrimeIcons to AppIcon names ───
const primeToAppIcon = {
    'pi pi-home': 'home',
    'pi pi-chart-bar': 'chart-bar',
    'pi pi-file': 'file',
    'pi pi-clipboard': 'clipboard-list',
    'pi pi-check-circle': 'check-circle',
    'pi pi-users': 'users',
    'pi pi-credit-card': 'credit-card',
    'pi pi-graduation-cap': 'graduation-cap',
    'pi pi-bell': 'bell',
    'pi pi-question-circle': 'question-circle',
    'pi pi-book': 'book',
    'pi pi-list': 'list',
    'pi pi-building': 'building',
    'pi pi-code': 'code',
    'pi pi-sliders-h': 'sliders',
    'pi pi-lock': 'lock',
    'pi pi-trash': 'trash',
    'pi pi-download': 'download',
    'pi pi-megaphone': 'megaphone',
    'pi pi-cog': 'cog',
    'pi pi-align-justify': 'align-justify',
};

const iconFor = (piIcon) => primeToAppIcon[piIcon] || 'circle';

// ─── Fetch sidebar menu items as drawer cards ───
const MENU_CACHE_KEY = 'scholarship_sidebar_menu_cache';
const MENU_CACHE_TIME_KEY = 'scholarship_sidebar_menu_cache_time';
const CACHE_DURATION = 3600000; // 1 hour

const fetchDrawerItems = async () => {
    drawerLoading.value = true;
    try {
        // Try cache first (same format as AdminLayout)
        const cached = localStorage.getItem(MENU_CACHE_KEY);
        const timestamp = localStorage.getItem(MENU_CACHE_TIME_KEY);
        if (cached && timestamp) {
            const cacheAge = Date.now() - parseInt(timestamp);
            if (cacheAge < CACHE_DURATION) {
                drawerItems.value = flattenMenuItems(JSON.parse(cached) || []);
                drawerLoading.value = false;
            }
        }

        const response = await fetch('/api/menu/sidebar', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            drawerItems.value = flattenMenuItems(data || []);
        }
    } catch (err) {
        console.error('Failed to load drawer items:', err);
    } finally {
        drawerLoading.value = false;
    }
};

// Flatten nested menu structure into a flat list of cards,
// only including items the user has permission for.
function flattenMenuItems(items, depth = 0) {
    const flat = [];
    for (const item of items) {
        // Skip Home (already on the page) and group headers without a route
        if (item.route === 'home.index' || item.route === 'home') continue;
        if (item.is_group && (!item.children || item.children.length === 0)) continue;

        // If this item itself requires a permission the user lacks, skip it entirely
        if (item.permission && !hasPermission(item.permission)) continue;

        if (item.children && item.children.length > 0) {
            flat.push(...flattenMenuItems(item.children, depth + 1));
        } else if (item.route) {
            flat.push({
                id: item.id || item.route,
                label: item.name || item.label,
                icon: iconFor(item.icon),
                route: item.route,
                description: item.description || '',
            });
        }
    }
    return flat;
}

// ─── System Updates ───
const fetchSystemUpdates = async () => {
    try {
        const response = await fetch('/api/system-updates', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
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
    fetchDrawerItems();
    fetchSystemUpdates();
});

const sortedSystemUpdates = computed(() => {
    return [...systemUpdates.value].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

const getSystemUpdateIcon = (type) => ({
    info: 'info-circle',
    warning: 'exclamation-triangle',
    success: 'check-circle',
    error: 'times-circle',
}[type] || 'info-circle');

const getSystemUpdateIconColor = (type) => ({
    info: 'text-[#007AFF]',
    warning: 'text-[#FF9500]',
    success: 'text-[#34C759]',
    error: 'text-[#FF3B30]',
}[type] || 'text-[#8E8E93]');

// ─── Navigate to a named route ───
function navigateTo(routeName) {
    try {
        router.visit(route(routeName));
    } catch {
        // route helper may throw if the route name doesn't exist in Ziggy
        console.warn('Unknown route:', routeName);
    }
}
</script>

<template>
    <Head title="Home" />
    <AdminLayout>
        <!-- Toolbar -->
        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <AppIcon name="graduation-cap" :size="32" class="text-indigo-900" />
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">PGP Scholarship Portal</h1>
                        <p class="text-sm text-gray-600">A centralized platform for managing scholarship applications, requirements, and updates.</p>
                    </div>
                </div>
            </template>
        </Toolbar>

        <div class="space-y-4 short:space-y-2">

            <!-- ═══ APP DRAWER — all available links as large icon cards ═══ -->
            <div class="ios-section !mt-0">
                <div class="ios-section-label">QUICK ACTIONS</div>

                <!-- Loading skeleton -->
                <div v-if="drawerLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 short:gap-2">
                    <div v-for="n in 6" :key="n"
                        class="ios-card animate-pulse p-4 flex flex-col items-center text-center">
                        <div class="w-14 h-14 rounded-2xl bg-[#E5E5EA] mb-3" />
                        <div class="h-3 w-20 bg-[#E5E5EA] rounded mb-1.5" />
                        <div class="h-2.5 w-16 bg-[#E5E5EA] rounded" />
                    </div>
                </div>

                <!-- Drawer grid -->
                <div v-else
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 short:gap-2">
                    <div v-for="(item, idx) in drawerItems" :key="item.id"
                        class="ios-card group cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)]"
                        @click="navigateTo(item.route)">
                        <div class="p-4 short:p-3 flex flex-col items-center text-center">
                            <!-- Large icon -->
                            <div
                                :class="[iconPalette[idx % iconPalette.length], 'w-14 h-14 short:w-12 short:h-12 rounded-2xl flex items-center justify-center mb-3 shadow-sm transition-transform duration-200 group-hover:scale-110']">
                                <AppIcon :name="item.icon" :size="24" class="text-white" />
                            </div>
                            <h3 class="text-sm font-semibold text-[#1d1d1f] mb-0.5 leading-tight">{{ item.label }}</h3>
                            <p v-if="item.description" class="text-[11px] text-[#8E8E93] leading-tight">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ System Updates ═══ -->
            <div class="ios-section !mt-0">
                <div class="ios-section-label">SYSTEM UPDATES</div>
                <div class="ios-card overflow-hidden">
                    <div v-if="sortedSystemUpdates.length > 0">
                        <div v-for="(update, idx) in sortedSystemUpdates.slice(0, 5)" :key="idx"
                            class="ios-row cursor-pointer hover:bg-[#F2F2F7] transition-colors"
                            @click="router.visit(route('system-updates.index'))">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-[#1d1d1f] truncate">{{ update.title }}</h4>
                                    <p class="text-xs text-[#6D6D72] mt-0.5 line-clamp-2">{{ update.content }}</p>
                                    <span class="text-xs text-[#8E8E93] mt-1 block">{{ update.created_at }}</span>
                                </div>
                                <AppIcon v-if="update.type" :name="getSystemUpdateIcon(update.type)" :size="16"
                                    :class="['flex-shrink-0', getSystemUpdateIconColor(update.type)]"
                                    v-tooltip.left="update.type" />
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
                        <AppIcon name="inbox" :size="36" class="text-[#C7C7CC] mb-2" />
                        <p class="text-sm text-[#8E8E93]">No system updates at the moment</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Help Dialog -->
        <IosModal :visible="showHelpDialog" title="Help & Support" width="640px" max-width="95vw"
            body-style="padding: 0;" @update:visible="showHelpDialog = $event">
            <div class="w-full max-h-[85vh] overflow-hidden rounded-[14px] bg-[#F2F2F7] shadow-[0_8px_30px_rgba(0,0,0,0.12)] flex flex-col">
                <div class="flex-1 overflow-y-auto px-4 pt-4 pb-6 space-y-[22px] [-webkit-overflow-scrolling:touch]">
                    <div class="ios-section !mt-0">
                        <div class="ios-section-label">CONTACT</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <div class="flex items-center gap-3">
                                    <AppIcon name="users" :size="18" class="text-[#007AFF]" />
                                    <div>
                                        <div class="text-sm font-semibold text-[#1d1d1f]">Scholarship Support Team</div>
                                    </div>
                                </div>
                            </div>
                            <div class="ios-row">
                                <div class="flex items-center gap-3">
                                    <AppIcon name="envelope" :size="18" class="text-[#34C759]" />
                                    <span class="text-sm text-[#007AFF]">scholarship@example.com</span>
                                </div>
                            </div>
                            <div class="ios-row !border-b-0">
                                <div class="flex items-center gap-3">
                                    <AppIcon name="phone" :size="18" class="text-[#FF9500]" />
                                    <span class="text-sm text-[#1d1d1f]">+1 (555) XXX-XXXX</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-section !mt-0">
                        <div class="ios-section-label">FREQUENTLY ASKED</div>
                        <div class="ios-card">
                            <div class="ios-row">
                                <p class="text-sm font-medium text-[#1d1d1f]">How long does application review take?</p>
                                <p class="text-xs text-[#6D6D72] mt-1">Applications are typically reviewed within 2–4 weeks of submission.</p>
                            </div>
                            <div class="ios-row !border-b-0">
                                <p class="text-sm font-medium text-[#1d1d1f]">Can I edit my application after submission?</p>
                                <p class="text-xs text-[#6D6D72] mt-1">You can edit until your application is under review.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </IosModal>
    </AdminLayout>
</template>

