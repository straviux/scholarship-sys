<script setup>
import { ref, onMounted, onUnmounted, provide } from "vue";
import SidebarLink from "@/Components/ui/navigation/SidebarLink.vue";
import NotificationDropdown from "@/Components/ui/navigation/NotificationDropdown.vue";
import ActivityLogsDropdown from "@/Components/ui/navigation/ActivityLogsDropdown.vue";
import MaintenanceAlertModal from "@/Components/MaintenanceAlertModal.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { usePermission } from "@/composable/permissions";
import logger from '@/utils/logger';

// PrimeVue Components
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Popover from 'primevue/popover';

const { hasRole, hasPermission } = usePermission();
const $page = usePage();
const toggleMenu = ref(false);
const sidebarMinimized = ref(localStorage.getItem('sidebarMinimized') === 'true');
const userMenuRef = ref(null);
const unreadUpdatesCount = ref(0);
const activityLogsDropdownRef = ref(null);
const currentDateTime = ref(new Date());
const serverTimezone = ref('');
const menuItems = ref([]);
const menuLoading = ref(true);
const expandedMenus = ref(new Set());

// Maintenance status state
const maintenanceStatus = ref(null);
let maintenanceCheckIntervalId = null;
let maintenanceAdjustIntervalId = null;
let currentMaintenanceCheckInterval = null;

// Check if user is admin
function isAdmin() {
    return hasRole('admin') || hasRole('administrator');
}

// Check if maintenance is currently active
function isMaintenanceActive() {
    return maintenanceStatus.value?.is_under_maintenance === true;
}

// Check if non-admin user should be blocked
function shouldBlockNonAdminAccess() {
    return isMaintenanceActive() && !isAdmin();
}

function toggleSidebarMinimized() {
    sidebarMinimized.value = !sidebarMinimized.value;
    localStorage.setItem('sidebarMinimized', sidebarMinimized.value ? 'true' : 'false');
}

function toggleUserMenu(event) {
    userMenuRef.value.toggle(event);
}

function getRoleDisplay() {
    const user = $page.props.auth?.user;
    if (!user) return 'Guest';

    // Try different possible role structures
    if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
        return user.roles[0].name || user.roles[0];
    }

    if (user.role && typeof user.role === 'string') {
        return user.role;
    }

    if (user.role && user.role.name) {
        return user.role.name;
    }

    // Fallback to checking specific role properties
    if (user.is_admin) return 'Administrator';
    if (user.is_moderator) return 'Moderator';

    return 'User';
}

// Function to refresh activity logs dropdown
function refreshActivityLogs() {
    if (activityLogsDropdownRef.value && activityLogsDropdownRef.value.refreshActivities) {
        activityLogsDropdownRef.value.refreshActivities();
    }
}

// Provide refresh function to child components
provide('refreshActivityLogs', refreshActivityLogs);

// Fetch unread updates count
async function fetchUnreadCount() {
    try {
        const response = await fetch('/api/system-updates/unread-count');
        const data = await response.json();
        unreadUpdatesCount.value = data.unread_count;
    } catch (error) {
        logger.error('Error fetching unread count:', error);
    }
}

// Fetch server time
async function fetchServerTime() {
    try {
        const response = await fetch('/api/server-time', {
            credentials: 'include',
        });
        const data = await response.json();
        currentDateTime.value = new Date(data.timestamp);
        serverTimezone.value = data.timezone;
    } catch (error) {
        logger.error('Error fetching server time:', error);
    }
}

// Fetch maintenance status and compute time until start
async function fetchMaintenanceStatus() {
    try {
        // Use public endpoint so all users (not just admins) can see maintenance alerts
        const response = await fetch('/api/maintenance/status', {
            credentials: 'include',
        });
        if (!response.ok) return;

        const data = await response.json();
        maintenanceStatus.value = data;

        // If we have a scheduled start time, start smart polling
        if (data.announcement?.countdown?.start_time) {
            scheduleSmartPolling(data.announcement.countdown.start_time);
        }
    } catch (error) {
        logger.error('Error fetching maintenance status:', error);
    }
}

// Schedule smart polling based on time until maintenance
function scheduleSmartPolling(startTimeStr) {
    const startTime = new Date(startTimeStr);
    const now = new Date();
    const minutesUntilStart = (startTime - now) / (1000 * 60);

    console.log(`⏰ Maintenance check scheduled. Minutes until start: ${minutesUntilStart.toFixed(1)}`);

    // Determine polling interval based on time remaining
    let newInterval;
    if (minutesUntilStart > 10) {
        newInterval = 5 * 60 * 1000; // 5 minutes if more than 10 mins away
        console.log('🟢 5-minute polling (far away)');
    } else if (minutesUntilStart > 0) {
        newInterval = 30 * 1000; // 30 seconds if less than 10 mins away
        console.log('🟡 30-second polling (close)');
    } else {
        newInterval = 10 * 1000; // 10 seconds if maintenance is starting
        console.log('🔴 10-second polling (starting soon)');
    }

    // Update interval if it changed
    if (newInterval !== currentMaintenanceCheckInterval) {
        currentMaintenanceCheckInterval = newInterval;

        // Clear old interval
        if (maintenanceCheckIntervalId) {
            clearInterval(maintenanceCheckIntervalId);
        }

        // Set new interval
        maintenanceCheckIntervalId = setInterval(() => {
            fetchMaintenanceStatus();
        }, newInterval);
    }

    // Recheck interval adjustment every minute
    if (!maintenanceAdjustIntervalId) {
        maintenanceAdjustIntervalId = setInterval(() => {
            const now = new Date();
            const remaining = (startTime - now) / (1000 * 60);
            if (remaining <= 10 && remaining > 0) {
                // Crossed into the 10-minute window
                scheduleSmartPolling(startTimeStr);
            }
        }, 60 * 1000); // Check every minute
    }
}

// Fetch on mount and set interval to refresh
let intervalId = null;

// Load menu items from API
async function loadMenuItems() {
    try {
        menuLoading.value = true;

        // Set timeout to prevent indefinite loading
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout

        const response = await fetch('/api/menu/sidebar', {
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include'
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`API returned ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
            menuItems.value = result.data;

            // Expand parent menus by default
            result.data.forEach(item => {
                if (item.children && Array.isArray(item.children) && item.children.length > 0) {
                    expandedMenus.value.add(item.id);
                }
            });
        } else {
            logger.warn('Invalid menu response format:', result);
            menuItems.value = [];
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            logger.error('Menu API request timed out after 15 seconds');
        } else {
            logger.error('Error loading menu items:', error);
        }
        // Fallback to empty menu if API fails
        menuItems.value = [];
    } finally {
        menuLoading.value = false;
    }
}

// Toggle menu expansion
function toggleMenuExpansion(menuId) {
    if (expandedMenus.value.has(menuId)) {
        expandedMenus.value.delete(menuId);
    } else {
        expandedMenus.value.add(menuId);
    }
}

// Get route name from menu item
function getMenuRoute(menuItem) {
    return menuItem.route ? route(menuItem.route) : null;
}

onMounted(() => {
    loadMenuItems();
    fetchUnreadCount();
    fetchServerTime();

    // Fetch maintenance status for all users - they need to see upcoming maintenance
    fetchMaintenanceStatus();

    // Refresh unread count every 30 seconds
    intervalId = setInterval(fetchUnreadCount, 30000);

    // Update server time every second (client-side increment for smooth display)
    const dateTimeIntervalId = setInterval(() => {
        currentDateTime.value = new Date(currentDateTime.value.getTime() + 1000);
    }, 1000);

    return () => {
        if (dateTimeIntervalId) clearInterval(dateTimeIntervalId);
    };
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    if (maintenanceCheckIntervalId) {
        clearInterval(maintenanceCheckIntervalId);
    }
    if (maintenanceAdjustIntervalId) {
        clearInterval(maintenanceAdjustIntervalId);
    }
});
</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <MaintenanceAlertModal />

    <!-- Maintenance Blocking Screen for Non-Admin Users -->
    <div v-if="shouldBlockNonAdminAccess()"
        class="fixed inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center z-[999]">
        <div class="max-w-md w-full mx-4 text-center flex flex-col items-center justify-center">
            <!-- Icon -->
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-yellow-500 animate-pulse" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4v2m0 4v2M8 5h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7a2 2 0 012-2z"></path>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-4xl font-bold text-white mb-4 break-words">
                {{ maintenanceStatus?.announcement?.title || 'System Maintenance' }}
            </h1>

            <!-- Message -->
            <p class="text-gray-300 text-lg mb-6 leading-relaxed w-full max-w-sm mx-auto">
                {{ maintenanceStatus?.announcement?.message || `We are performing scheduled maintenance. Please try
                again later.` }}
            </p>

            <!-- Status Badge -->
            <div class="inline-block px-6 py-3 rounded-full mb-8" :class="{
                'bg-blue-500': maintenanceStatus?.announcement?.type === 'info',
                'bg-yellow-500': maintenanceStatus?.announcement?.type === 'warning',
                'bg-red-500': maintenanceStatus?.announcement?.type === 'critical',
            }">
                <span class="text-white font-semibold">
                    {{ (maintenanceStatus?.announcement?.type || 'maintenance').toUpperCase() }} IN PROGRESS
                </span>
            </div>

            <!-- End Time Info -->
            <div class="bg-gray-700 bg-opacity-50 border border-gray-600 rounded-lg p-6 mb-8 w-full max-w-sm mx-auto">
                <p class="text-gray-400 text-sm mb-2">Expected to complete:</p>
                <p class="text-white text-xl font-mono font-semibold break-words">
                    {{ maintenanceStatus?.announcement?.countdown?.end_time ? new
                        Date(maintenanceStatus.announcement.countdown.end_time).toLocaleString() : 'TBD' }}
                </p>
            </div>

            <!-- Support Message -->
            <div class="bg-blue-500 bg-opacity-20 border border-blue-400 rounded-lg p-4 w-full max-w-sm mx-auto">
                <p class="text-blue-200 text-sm">
                    <i class="pi pi-info-circle mr-2"></i>
                    Please check back shortly. We appreciate your patience!
                </p>
            </div>

            <!-- Refresh Hint -->
            <p class="text-gray-500 text-xs mt-8">
                This page will automatically update when maintenance is complete.
            </p>
        </div>
    </div>

    <div v-if="!shouldBlockNonAdminAccess()" class="w-full h-full flex">
        <!-- Floating Sidebar -->
        <aside
            class="hidden fixed z-10 top-20 left-4 md:flex flex-col bg-[#222831] transition-all duration-300 rounded-xl shadow-xl min-w-0 h-[calc(100vh-96px)]"
            :class="[sidebarMinimized ? 'md:w-[110px] w-[110px]' : 'md:w-[220px] w-[220px]', toggleMenu ? 'flex!' : '']">

            <div class="flex-1 flex flex-col min-h-0 min-w-0 p-0 overflow-hidden">
                <Button v-slot="slotProps" asChild>
                    <button v-bind="slotProps.a11yAttrs" @click="toggleSidebarMinimized"
                        class="flex w-full bg-[#222831] pt-2 px-2 text-gray-500 cursor-pointer rounded-tr-xl rounded-tl-xl justify-end">
                        <i :class="{ 'pi pi-window-maximize': sidebarMinimized, 'pi pi-window-minimize': !sidebarMinimized }"
                            style="font-size: 0.7rem"></i>
                    </button>
                </Button>
                <!-- <Button class="flex bg-[#222831]" @click="toggleSidebarMinimized"
                    :aria-label="sidebarMinimized ? 'Expand sidebar' : 'Minimize sidebar'" size="small">
                    <i
                        :class="{ 'pi pi-chevron-right': sidebarMinimized, 'pi pi-chevron-left': !sidebarMinimized }"></i>
                </Button> -->
                <!-- User Profile Section -->
                <div v-if="!sidebarMinimized" class="px-4 py-2 border-b border-gray-700">

                    <div class="flex items-center space-x-3">

                        <Avatar
                            :image="$page.props.auth.user.has_profile_photo ? $page.props.auth.user.profile_photo_url : null"
                            :label="!$page.props.auth.user.has_profile_photo ? ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() : null"
                            size="xlarge" shape="circle" class="flex-shrink-0 sidebar-avatar-large" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-200 truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ getRoleDisplay() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Minimized User Profile -->
                <div v-else class="flex items-center justify-center py-4 border-b border-gray-700">
                    <Avatar
                        :image="$page.props.auth.user.has_profile_photo ? $page.props.auth.user.profile_photo_url : null"
                        :label="!$page.props.auth.user.has_profile_photo ? ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() : null"
                        size="large" shape="circle" class="sidebar-avatar-medium" />
                </div>
                <!-- Dynamic Menu from API (Full Width) -->
                <ul v-if="!sidebarMinimized && !menuLoading"
                    class="menu space-y-3 md:space-y-2 mt-2 px-3 pb-3 text-sm md:text-xs w-full text-gray-300 hover:text-gray-50 overflow-y-auto min-h-0 min-w-0 block flex-1">
                    <template v-for="item in menuItems" :key="item.id">
                        <!-- Menu item without children -->
                        <li v-if="!item.children || item.children.length === 0">
                            <SidebarLink :href="item.route ? route(item.route) : '#'"
                                :active="item.route && route().current(item.route)">
                                <i :class="[item.icon, 'mr-2 text-sm']"></i>
                                <span class="font-medium">{{ item.name }}</span>
                                <Badge v-if="item.name === 'System Updates' && unreadUpdatesCount > 0"
                                    :value="unreadUpdatesCount" severity="danger" class="ml-auto" />
                            </SidebarLink>
                        </li>

                        <!-- Menu item with children (dropdown) -->
                        <li v-else>
                            <div @click="toggleMenuExpansion(item.id)"
                                class="cursor-pointer flex items-center justify-between py-1 px-2 rounded hover:bg-gray-700 transition-colors">
                                <span class="flex items-center flex-1">
                                    <i :class="[item.icon, 'mr-2 text-sm']"></i>
                                    <span class="-mr-1 font-medium">{{ item.name }}</span>
                                </span>
                                <i :class="expandedMenus.has(item.id) ? 'pi pi-chevron-down' : 'pi pi-chevron-right'"
                                    style="font-size: 0.65rem" class="transition-transform duration-200"></i>
                            </div>
                            <!-- Children list - show/hide based on expanded state -->
                            <ul v-if="expandedMenus.has(item.id)" class="space-y-1 mt-1 ml-2">
                                <li v-for="child in item.children" :key="child.id">
                                    <SidebarLink :href="child.route ? route(child.route) : '#'"
                                        :active="child.route && route().current(child.route)">
                                        <i :class="[child.icon, 'mr-2 text-sm']"></i>
                                        <span class="-mr-1 font-medium">{{ child.name }}</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>

                <!-- Loading state -->
                <div v-if="menuLoading" class="flex items-center justify-center py-8">
                    <i class="pi pi-spin pi-spinner text-gray-400" style="font-size: 1.5rem"></i>
                </div>
                <!-- Dynamic Menu from API (Minimized Width) -->
                <ul v-if="sidebarMinimized && !menuLoading"
                    class="menu space-y-3 mt-2 px-2 pb-4 w-full text-gray-300 hover:text-gray-50 items-center min-h-0 min-w-0 block flex-1 overflow-y-auto overflow-x-hidden">
                    <template v-for="item in menuItems" :key="item.id">
                        <!-- Single menu item (minimized) -->
                        <li v-if="!item.children || item.children.length === 0">
                            <SidebarLink :href="item.route ? route(item.route) : '#'"
                                :active="item.route && route().current(item.route)"
                                class="flex flex-col justify-center text-center">
                                <i :class="[item.icon, 'text-xl']"></i>
                                <span class="text-xs">{{ item.name.split(' ').slice(0, 1).join(' ').toLowerCase()
                                }}</span>
                            </SidebarLink>
                        </li>

                        <!-- Parent menu item with children (minimized) -->
                        <li v-else class="relative group">
                            <div class="flex flex-col justify-center text-center cursor-pointer">
                                <i :class="[item.icon, 'text-xl']"></i>
                                <span class="text-xs">{{ item.name.split(' ').slice(0, 1).join(' ').toLowerCase()
                                }}</span>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </aside>

        <div class="w-full min-w-0 flex flex-col">
            <div class="sticky z-10 top-0 h-16 border-b bg-[#222831] lg:py-2.5">
                <div class="px-6 flex items-center justify-between space-x-4">
                    <!-- Logo and App Name -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <img src="/images/pgp-logo.png" class="w-8 h-8 object-contain" alt="logo" />
                            <span class="text-lg font-semibold text-gray-200">Scholarship Program</span>
                        </div>
                        <div class="hidden md:block w-px h-6 bg-gray-600"></div>
                        <!-- Server Date/Time Display -->
                        <div class="hidden md:flex flex-col items-start justify-center text-gray-300 text-sm px-4">
                            <div class="font-semibold">{{ currentDateTime.toLocaleDateString('en-US', {
                                weekday:
                                    'short', month: 'short', day: 'numeric', year: 'numeric'
                            }) }}</div>
                            <div class="text-xs text-gray-400">{{ currentDateTime.toLocaleTimeString('en-US', {
                                hour:
                                    '2-digit', minute: '2-digit', second: '2-digit', hour12: true
                            }) }} {{ serverTimezone }}
                            </div>
                        </div>
                    </div>
                    <button @click="toggleMenu = !toggleMenu"
                        class="md:hidden text-gray-300 hover:text-white focus:outline-none p-2">
                        <i class="pi" :class="toggleMenu ? 'pi-times' : 'pi-bars'"></i>
                    </button>
                    <div class="space-x-6 hidden md:flex items-center justify-center">
                        <!-- Help Link -->
                        <Link :href="route('help.index')"
                            class="text-gray-300 hover:text-white hover:bg-gray-700 transition-colors duration-200 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-2"
                            :class="{ 'bg-gray-700 text-white': route().current('help.index') }">
                            <i class="pi pi-question-circle"></i>
                            <span class="hidden lg:inline">Help</span>
                        </Link>

                        <!-- Notification Dropdown -->
                        <NotificationDropdown
                            :unread-count="($page.props.auth.user && $page.props.auth.user.unread_notifications_count) || 0"
                            :class="{ 'animate-shake': ($page.props.auth.user && $page.props.auth.user.unread_notifications_count) > 0 }" />

                        <!-- Activity Logs Dropdown -->
                        <ActivityLogsDropdown ref="activityLogsDropdownRef" />

                        <!-- User Settings Menu -->
                        <div class="relative">
                            <!-- <Button @click="toggleUserMenu" text rounded
                                class="text-gray-300 hover:text-white cursor-pointer transition-colors duration-200">
                                <i class="pi pi-cog" style="font-size: 1.2rem"></i>
                            </Button> -->
                            <Button icon="pi pi-cog" severity="secondary" variant="text" size="large" rounded
                                aria-label="Bookmark" @click="toggleUserMenu" />

                            <Popover ref="userMenuRef" class="w-56">
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="pi pi-user text-blue-600"></i>
                                            <h3 class="text-base font-semibold text-gray-900">User Menu</h3>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $page.props.auth.user.name }}
                                    </p>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <Link :href="route('user.reports')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                        <i class="pi pi-file-pdf text-gray-600"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-gray-900">My Reports</span>
                                            <p class="text-xs text-gray-500">View your generated reports</p>
                                        </div>
                                    </Link>

                                    <Link :href="route('user.settings')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                        <i class="pi pi-cog text-gray-600"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-gray-900">Settings</span>
                                            <p class="text-xs text-gray-500">Account preferences</p>
                                        </div>
                                    </Link>

                                    <Link :href="route('user-activity-logs.index')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                        <i class="pi pi-chart-line text-gray-600"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-gray-900">Activity</span>
                                            <p class="text-xs text-gray-500">View your recent activity</p>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Footer -->
                                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50">
                                    <Link :href="route('logout')" method="post" as="button"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors duration-200">
                                        <i class="pi pi-sign-out"></i>
                                        <span class="text-sm font-medium">Sign Out</span>
                                    </Link>
                                </div>
                            </Popover>
                        </div>

                    </div>
                </div>
            </div>

            <div class="px-4 md:px-6 pt-6" :class="sidebarMinimized ? 'md:ml-[130px]' : 'md:ml-[240px]'">
                <!-- <ToastList /> -->
                <slot />
            </div>
        </div>
    </div>
</template>
<style>
/* Hide text visually but keep for screen readers */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.menu,
aside {
    box-sizing: border-box;
}

.menu :where(li ul)::before {
    background-color: #ccc;
}

/* PrimeVue component customizations */
.p-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.p-button-text {
    color: inherit;
}

.p-overlaypanel .p-overlaypanel-content {
    padding: 0;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
    max-height: 24rem;
    overflow: hidden;
}

.p-overlaypanel {
    margin-top: 0.5rem;
}

/* Custom avatar sizes for sidebar */
.sidebar-avatar-large {
    width: 4.5rem !important;
    height: 4.5rem !important;
    font-size: 1.5rem !important;
}

.sidebar-avatar-medium {
    width: 3.5rem !important;
    height: 3.5rem !important;
    font-size: 1.25rem !important;
}

/* Hide scrollbar but maintain scroll functionality */
.menu {
    /* Hide scrollbar for Chrome, Safari and Opera */
    scrollbar-width: none;
    /* Firefox */
    -ms-overflow-style: none;
    /* Internet Explorer 10+ */
}

.menu::-webkit-scrollbar {
    display: none;
    /* Safari and Chrome */
}
</style>