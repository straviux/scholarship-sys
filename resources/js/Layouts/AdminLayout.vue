<script setup>
import { ref, onMounted, onUnmounted, provide, watch } from "vue";
import SidebarLink from "@/Components/ui/navigation/SidebarLink.vue";
import NotificationDropdown from "@/Components/ui/navigation/NotificationDropdown.vue";
import ActivityLogsDropdown from "@/Components/ui/navigation/ActivityLogsDropdown.vue";
import MaintenanceAlertModal from "@/Components/MaintenanceAlertModal.vue";
import ScrollToTop from "@/Components/ui/ScrollToTop.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { useSmoothScroll } from "@/composables/useSmoothScroll";
import { usePermission } from "@/composable/permissions";
import { useTheme } from "@/composables/useTheme";
import logger from '@/utils/logger';

const { hasRole, hasPermission } = usePermission();
const { scrollToTop } = useSmoothScroll();
const { theme, navDark, cycleTheme, getThemeIcon, getThemeLabel } = useTheme();
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
const MENU_CACHE_KEY = 'scholarship_sidebar_menu_cache';
const MENU_CACHE_TIMESTAMP_KEY = 'scholarship_sidebar_menu_cache_time';
const CACHE_DURATION = 60 * 60 * 1000; // 1 hour in milliseconds

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

// Handle logout with full page reload
function handleLogout() {
    router.post(route('logout'), {}, {
        onFinish: () => {
            // Force a full page reload to clear Inertia state
            window.location.href = '/login';
        }
    });
}

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

    console.log(`🔵 Maintenance check scheduled. Minutes until start: ${minutesUntilStart.toFixed(1)}`);

    // Determine polling interval based on time remaining
    let newInterval;
    if (minutesUntilStart > 10) {
        newInterval = 5 * 60 * 1000; // 5 minutes if more than 10 mins away
        console.log('[DEBUG] 5-minute polling (far away)');
    } else if (minutesUntilStart > 0) {
        newInterval = 30 * 1000; // 30 seconds if less than 10 mins away
        console.log('[DEBUG] 30-second polling (close)');
    } else {
        newInterval = 10 * 1000; // 10 seconds if maintenance is starting
        console.log('ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â´ 10-second polling (starting soon)');
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
let dateTimeIntervalId = null;

// Cache management functions
function getCachedMenu() {
    try {
        const cached = localStorage.getItem(MENU_CACHE_KEY);
        const timestamp = localStorage.getItem(MENU_CACHE_TIMESTAMP_KEY);

        if (!cached || !timestamp) {
            return null;
        }

        // Check if cache is expired
        const cacheAge = Date.now() - parseInt(timestamp);
        if (cacheAge > CACHE_DURATION) {
            logger.info('Menu cache expired, will refresh from API');
            return null;
        }

        logger.info('Loading menu from cache');
        return JSON.parse(cached);
    } catch (error) {
        logger.warn('Error reading menu cache:', error);
        return null;
    }
}

function setCachedMenu(menu) {
    try {
        localStorage.setItem(MENU_CACHE_KEY, JSON.stringify(menu));
        localStorage.setItem(MENU_CACHE_TIMESTAMP_KEY, Date.now().toString());
        logger.info('Menu cache updated');
    } catch (error) {
        logger.warn('Error saving menu cache:', error);
    }
}

function clearMenuCache() {
    try {
        localStorage.removeItem(MENU_CACHE_KEY);
        localStorage.removeItem(MENU_CACHE_TIMESTAMP_KEY);
        logger.info('Menu cache cleared');
    } catch (error) {
        logger.warn('Error clearing menu cache:', error);
    }
}

// Load menu items from API with caching
// Menu is displayed immediately from cache, then refreshed in background

// Load menu items from API with caching
// Menu is displayed immediately from cache, then refreshed in background
async function loadMenuItems(showLoadingState = true) {
    // STEP 1: Try to load from cache immediately
    const cachedMenu = getCachedMenu();
    if (cachedMenu && cachedMenu.length > 0) {
        logger.info('Restoring menu from cache');
        menuItems.value = cachedMenu;

        // Restore expanded state for parent menus
        cachedMenu.forEach(item => {
            if (item.children && Array.isArray(item.children) && item.children.length > 0) {
                expandedMenus.value.add(item.id);
            }
        });

        // Don't show loading state if we have cached menu on initial load
        if (showLoadingState) {
            menuLoading.value = false;
        }
    }

    // STEP 2: Fetch fresh menu from API in background
    // If we loaded from cache, this is a silent background update
    const maxRetries = 3;
    let retryCount = 0;

    const attemptLoad = async () => {
        try {
            // Only show loading state if we don't have cached data and it's the initial load
            if (menuItems.value.length === 0 && showLoadingState) {
                menuLoading.value = true;
            }

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
                // Handle specific error codes
                if (response.status === 401) {
                    // Unauthorized - user likely needs to log in again
                    logger.warn('User session expired, menu not loaded');
                    if (menuItems.value.length === 0) {
                        menuItems.value = [];
                    }
                    return;
                }

                if (response.status >= 500 && retryCount < maxRetries) {
                    // Retry on server errors
                    retryCount++;
                    logger.warn(`Menu API returned ${response.status}, retrying... (attempt ${retryCount}/${maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, 1000 * retryCount)); // Exponential backoff
                    return attemptLoad();
                }

                throw new Error(`API returned ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                // Only update if data changed (avoid unnecessary re-renders)
                const newMenuJson = JSON.stringify(result.data);
                const oldMenuJson = JSON.stringify(menuItems.value);

                if (newMenuJson !== oldMenuJson) {
                    logger.info('Menu updated from API');
                    menuItems.value = result.data;

                    // Expand parent menus by default
                    expandedMenus.value.clear();
                    result.data.forEach(item => {
                        if (item.children && Array.isArray(item.children) && item.children.length > 0) {
                            expandedMenus.value.add(item.id);
                        }
                    });

                    // Update cache with fresh menu
                    setCachedMenu(result.data);
                } else {
                    logger.debug('Menu unchanged, cache is current');
                }
            } else {
                logger.warn('Invalid menu response format:', result);
                if (menuItems.value.length === 0) {
                    menuItems.value = [];
                }
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                // Request was aborted - this could happen during navigation
                logger.debug('Menu API request was aborted (likely due to navigation)');

                // Retry on timeout if we haven't exceeded max retries
                if (retryCount < maxRetries) {
                    retryCount++;
                    logger.warn(`Menu API timeout, retrying... (attempt ${retryCount}/${maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, 1000 * retryCount));
                    return attemptLoad();
                }
            } else {
                logger.error('Error loading menu items:', error);
            }
            // Keep existing menu items on error (don't clear if we have cache)
            if (menuItems.value.length === 0) {
                menuItems.value = [];
            }
        } finally {
            // Always hide loading state when API request completes
            menuLoading.value = false;
        }
    };

    // Execute the background fetch
    return attemptLoad();
}

// Toggle menu expansion
function toggleMenuExpansion(menuId) {
    if (expandedMenus.value.has(menuId)) {
        expandedMenus.value.delete(menuId);
    } else {
        expandedMenus.value.add(menuId);
    }
}

// Get route URL from menu item, with error handling for missing routes
function getMenuItemRoute(menuItem) {
    if (!menuItem.route) return '#';
    try {
        return route(menuItem.route);
    } catch (error) {
        // If route doesn't exist in Ziggy, log warning and return # to prevent navigation
        logger.warn(`Route '${menuItem.route}' not found in route list, item '${menuItem.name}' will be inactive`);
        return '#';
    }
}

// Check if a route matches a menu item's route pattern (including prefix matching)
function routeMatches(menuRoute) {
    if (!menuRoute) return false;
    try {
        const currentRoute = route().current();

        // Exact match
        if (currentRoute === menuRoute) return true;

        // Current route is a direct sub-route of the menu route
        // e.g., menu 'scholarship.profiles' matches 'scholarship.profiles.show'
        if (currentRoute && currentRoute.startsWith(menuRoute + '.')) {
            return true;
        }

        // Handle plural menu route matching singular sub-routes
        // e.g., 'scholarship.profiles' should also match 'scholarship.profile.show'
        if (menuRoute === 'scholarship.profiles') {
            return currentRoute && currentRoute.startsWith('scholarship.profile.');
        }

        return false;
    } catch (error) {
        return false;
    }
}

// Check if menu item is active, with error handling for missing routes
function isMenuItemActive(menuItem) {
    if (!menuItem.route) return false;
    return routeMatches(menuItem.route);
}

// Check if parent menu item should be active (either itself or any children)
function isParentMenuItemActive(menuItem) {
    // First check if the parent itself is active
    if (isMenuItemActive(menuItem)) return true;

    // Then check if any of its children are active
    if (menuItem.children && Array.isArray(menuItem.children)) {
        return menuItem.children.some(child => isMenuItemActive(child));
    }

    // Also check if the current route matches the parent's route pattern
    if (menuItem.route && routeMatches(menuItem.route)) {
        return true;
    }

    return false;
}

// Close mobile sidebar on Inertia navigation
let removeNavigateListener = null;

// Auto-hide content scrollbar on idle
const contentRef = ref(null);
let scrollTimer = null;
function onContentScroll() {
    contentRef.value?.classList.add('is-scrolling');
    if (scrollTimer) clearTimeout(scrollTimer);
    scrollTimer = setTimeout(() => {
        contentRef.value?.classList.remove('is-scrolling');
    }, 1000);
}

onMounted(() => {
    // Attach scroll listener to content area (not window)
    if (contentRef.value) {
        contentRef.value.addEventListener('scroll', onContentScroll, { passive: true });
    }
    loadMenuItems(true); // true = show loading state on initial load
    fetchUnreadCount();
    fetchServerTime();

    // Fetch maintenance status for all users - they need to see upcoming maintenance
    fetchMaintenanceStatus();

    // Refresh unread count every 30 seconds
    intervalId = setInterval(fetchUnreadCount, 30000);

    // Update server time every second (client-side increment for smooth display)
    dateTimeIntervalId = setInterval(() => {
        currentDateTime.value = new Date(currentDateTime.value.getTime() + 1000);
    }, 1000);

    // Close mobile menu when navigating to a new page
    removeNavigateListener = router.on('navigate', () => {
        toggleMenu.value = false;
        // Smooth scroll to top on page navigation
        scrollToTop(0.5);
    });

    // Note: We intentionally do NOT refresh menu on navigation because:
    // 1. Menu doesn't change when navigating between pages
    // 2. Menu only needs to refresh if permissions/roles change
    // 3. This prevents extra API calls and keeps navigation fast
    // Menu will remain cached until cache expires (1 hour) or user logs out
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    if (dateTimeIntervalId) {
        clearInterval(dateTimeIntervalId);
    }
    if (maintenanceCheckIntervalId) {
        clearInterval(maintenanceCheckIntervalId);
    }
    if (maintenanceAdjustIntervalId) {
        clearInterval(maintenanceAdjustIntervalId);
    }
    if (removeNavigateListener) {
        removeNavigateListener();
    }
    if (contentRef.value) {
        contentRef.value.removeEventListener('scroll', onContentScroll);
    }
    if (scrollTimer) clearTimeout(scrollTimer);
});
</script>

<template>
    <Toast position="top-right" :life="3500" :baseZIndex="20000" />
    <ConfirmDialog></ConfirmDialog>
    <MaintenanceAlertModal />

    <!-- Maintenance Blocking Screen for Non-Admin Users -->
    <div v-if="shouldBlockNonAdminAccess()"
        class="fixed inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center z-[999]">
        <div class="max-w-md w-full mx-4 text-center flex flex-col items-center justify-center">
            <!-- Icon -->
            <div class="mb-6 short:mb-3">
                <i class="pi pi-exclamation-triangle text-yellow-500 animate-pulse text-[5rem] short:text-[3rem]"></i>
            </div>

            <!-- Title -->
            <h1 class="text-4xl short:text-2xl font-bold text-white mb-4 short:mb-2 break-words">
                {{ maintenanceStatus?.announcement?.title || 'System Maintenance' }}
            </h1>

            <!-- Message -->
            <p class="text-gray-300 text-lg short:text-sm mb-6 short:mb-3 leading-relaxed w-full max-w-sm mx-auto">
                {{ maintenanceStatus?.announcement?.message || `We are performing scheduled maintenance. Please try
                again later.` }}
            </p>

            <!-- Status Badge -->
            <div class="inline-block px-6 short:px-3 py-3 short:py-2 rounded-full mb-8 short:mb-4" :class="{
                'bg-blue-500': maintenanceStatus?.announcement?.type === 'info',
                'bg-yellow-500': maintenanceStatus?.announcement?.type === 'warning',
                'bg-red-500': maintenanceStatus?.announcement?.type === 'critical',
            }">
                <span class="text-white font-semibold">
                    {{ (maintenanceStatus?.announcement?.type || 'maintenance').toUpperCase() }} IN PROGRESS
                </span>
            </div>

            <!-- End Time Info -->
            <div
                class="bg-gray-700 bg-opacity-50 border border-gray-600 rounded-lg p-4 short:p-2 mb-8 short:mb-4 w-full max-w-sm mx-auto">
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
            <p class="text-gray-500 text-xs mt-8 short:mt-4">
                This page will automatically update when maintenance is complete.
            </p>
        </div>
    </div>

    <div v-if="!shouldBlockNonAdminAccess()" class="w-full h-full flex">
        <!-- Mobile Backdrop -->
        <div v-if="toggleMenu" @click="toggleMenu = false" class="fixed inset-0 bg-black/50 z-20 md:hidden" />

        <!-- Floating Sidebar -->
        <aside
            class="fixed z-30 md:z-20 top-0 left-0 md:top-20 md:left-4 flex flex-col dark:bg-[#222831] dark:shadow-none transition-[width,transform] duration-300 rounded-4xl min-w-0 h-full md:h-[calc(100vh-96px)]"
            :class="[
                sidebarMinimized ? 'md:w-[110px]' : 'md:w-[220px]',
                toggleMenu ? 'w-[280px] translate-x-0' : '-translate-x-full md:translate-x-0',
                navDark ? 'dark' : '',
            ]">

            <div class="flex-1 flex flex-col min-h-0 min-w-0 p-0 overflow-hidden rounded-4xl">
                <Button v-slot="slotProps" asChild>
                    <button v-bind="slotProps.a11yAttrs" @click="toggleSidebarMinimized"
                        class="flex w-full dark:bg-transparent pt-4 px-4 text-gray-400 dark:text-gray-500 cursor-pointer justify-end">
                        <i :class="{ 'pi pi-window-maximize': sidebarMinimized, 'pi pi-window-minimize': !sidebarMinimized }"
                            style="font-size: 0.7rem;margin-right: 8px;"></i>
                    </button>
                </Button>
                <!-- <Button class="flex bg-[#222831]" @click="toggleSidebarMinimized"
                    :aria-label="sidebarMinimized ? 'Expand sidebar' : 'Minimize sidebar'" size="small">
                    <i
                        :class="{ 'pi pi-chevron-right': sidebarMinimized, 'pi pi-chevron-left': !sidebarMinimized }"></i>
                </Button> -->
                <!-- User Profile Section -->
                <div v-if="!sidebarMinimized" class="px-4 py-2 dark:border-b dark:border-gray-700">

                    <div class="flex items-center space-x-3">

                        <Avatar
                            :image="$page.props.auth.user.has_profile_photo ? $page.props.auth.user.profile_photo_url : null"
                            :label="!$page.props.auth.user.has_profile_photo ? ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() : null"
                            size="xlarge" shape="circle" class="flex-shrink-0 sidebar-avatar-large !bg-transparent" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ getRoleDisplay() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Minimized User Profile -->
                <div v-else class="flex items-center justify-center py-4 dark:border-b dark:border-gray-700">
                    <Avatar
                        :image="$page.props.auth.user.has_profile_photo ? $page.props.auth.user.profile_photo_url : null"
                        :label="!$page.props.auth.user.has_profile_photo ? ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() : null"
                        size="large" shape="circle" class="sidebar-avatar-medium" />
                </div>
                <!-- Dynamic Menu from API (Full Width) -->
                <ul v-if="!sidebarMinimized && (menuItems.length > 0 || !menuLoading)"
                    class="menu space-y-3 md:space-y-2 mt-2 px-3 pb-20 text-sm md:text-xs w-full text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-50 overflow-y-auto min-h-0 min-w-0 block flex-1 relative"
                    :class="{ 'opacity-60 pointer-events-none': menuLoading }">
                    <template v-for="item in menuItems" :key="item.id">
                        <!-- Menu item without children -->
                        <li v-if="!item.children || item.children.length === 0">
                            <SidebarLink :href="getMenuItemRoute(item)" :active="item.route && isMenuItemActive(item)">
                                <i :class="[item.icon, 'mr-2 text-sm']"></i>
                                <span class="font-medium">{{ item.name }}</span>
                                <Badge v-if="item.name === 'System Updates' && unreadUpdatesCount > 0"
                                    :value="unreadUpdatesCount" severity="danger" class="ml-auto" />
                            </SidebarLink>
                        </li>

                        <!-- Menu item with children (dropdown) -->
                        <li v-else>
                            <div @click="toggleMenuExpansion(item.id)"
                                class="cursor-pointer flex items-center justify-between py-1 px-2 rounded transition-colors"
                                :class="isParentMenuItemActive(item) ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
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
                                    <SidebarLink :href="getMenuItemRoute(child)"
                                        :active="child.route && isMenuItemActive(child)">
                                        <i :class="[child.icon, 'mr-2 text-sm']"></i>
                                        <span class="-mr-1 font-medium">{{ child.name }}</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>

                <!-- Loading state - only show as full spinner if no menu items exist yet -->
                <div v-if="menuLoading && menuItems.length === 0" class="flex items-center justify-center py-8 flex-1">
                    <i class="pi pi-spin pi-spinner text-gray-400" style="font-size: 1.5rem"></i>
                </div>

                <!-- Overlay loading indicator when menu is reloading (not initial load) -->
                <div v-if="menuLoading && menuItems.length > 0"
                    class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
                    <i class="pi pi-spin pi-spinner text-gray-200" style="font-size: 1.2rem"></i>
                </div>
                <!-- Dynamic Menu from API (Minimized Width) -->
                <ul v-if="sidebarMinimized && (menuItems.length > 0 || !menuLoading)"
                    class="menu space-y-3 mt-2 px-2 pb-20 w-full text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-50 items-center min-h-0 min-w-0 block flex-1 overflow-y-auto overflow-x-hidden relative"
                    :class="{ 'opacity-60 pointer-events-none': menuLoading }">
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

                <!-- Mobile-only: Quick Actions at bottom of sidebar -->
                <div class="md:hidden dark:border-t dark:border-gray-700 p-3 space-y-1">
                    <Link :href="route('help.index')" @click="toggleMenu = false"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="pi pi-question-circle"></i>
                        <span>Help</span>
                    </Link>
                    <Link :href="route('user.settings')" @click="toggleMenu = false"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="pi pi-cog"></i>
                        <span>Settings</span>
                    </Link>
                    <Link :href="route('user-activity-logs.index')" @click="toggleMenu = false"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="pi pi-chart-line"></i>
                        <span>Activity</span>
                    </Link>
                    <button @click="handleLogout"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm w-full">
                        <i class="pi pi-sign-out"></i>
                        <span>Sign Out</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="w-full min-w-0 flex flex-col h-screen overflow-hidden">
            <div class="flex-shrink-0 relative z-20 h-16 dark:bg-[#222831] dark:border-b dark:border-white/5 lg:py-2.5"
                :class="{ dark: navDark }">
                <div class="px-4 md:px-6 flex items-center justify-between space-x-2 md:space-x-4 h-full">
                    <!-- Mobile hamburger -->
                    <button @click="toggleMenu = !toggleMenu"
                        class="md:hidden flex-shrink-0 p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white cursor-pointer">
                        <i class="pi text-lg" :class="toggleMenu ? 'pi-times' : 'pi-bars'"></i>
                    </button>
                    <!-- Logo and App Name -->
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <img src="/images/pgp-logo.png" class="w-8 h-8 object-contain" alt="logo" />
                            <span
                                class="text-lg font-semibold text-gray-800 dark:text-gray-200 hidden sm:inline">Scholarship
                                Program</span>
                        </div>
                        <div class="hidden md:block w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                        <!-- Server Date/Time Display -->
                        <div
                            class="hidden md:flex flex-col items-start justify-center text-gray-600 dark:text-gray-300 text-sm px-4">
                            <div class="font-semibold">{{ currentDateTime.toLocaleDateString('en-US', {
                                weekday:
                                    'short', month: 'short', day: 'numeric', year: 'numeric'
                            }) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                currentDateTime.toLocaleTimeString('en-US',
                                    {
                                        hour:
                                            '2-digit', minute: '2-digit', second: '2-digit', hour12: true
                                    }) }} {{ serverTimezone }}
                            </div>
                        </div>
                    </div>
                    <!-- Mobile notification icon -->
                    <NotificationDropdown class="md:hidden flex-shrink-0"
                        :unread-count="($page.props.auth.user && $page.props.auth.user.unread_notifications_count) || 0"
                        :class="{ 'animate-shake': ($page.props.auth.user && $page.props.auth.user.unread_notifications_count) > 0 }" />
                    <div class="space-x-1 hidden md:flex items-center justify-center">
                        <!-- Help Link -->
                        <Link :href="route('help.index')"
                            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 px-3 py-2 rounded-md text-sm font-medium flex items-center gap-2"
                            :class="{ 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white': route().current('help.index') }">
                            <i class="pi pi-question-circle"></i>
                            <span class="hidden lg:inline">Help</span>
                        </Link>

                        <!-- Notification Dropdown -->
                        <NotificationDropdown
                            :unread-count="($page.props.auth.user && $page.props.auth.user.unread_notifications_count) || 0"
                            :class="{ 'animate-shake': ($page.props.auth.user && $page.props.auth.user.unread_notifications_count) > 0 }" />

                        <!-- Activity Logs Dropdown -->
                        <ActivityLogsDropdown ref="activityLogsDropdownRef" />

                        <!-- Theme Toggle -->
                        <Button :icon="getThemeIcon()" severity="secondary" variant="text" size="large" rounded
                            :aria-label="'Theme: ' + getThemeLabel()" :title="'Theme: ' + getThemeLabel()"
                            @click="cycleTheme" v-tooltip.bottom="'Switch Theme'" />

                        <!-- User Settings Menu -->
                        <div class="relative">
                            <!-- <Button @click="toggleUserMenu" text rounded
                                class="text-gray-300 hover:text-white cursor-pointer transition-colors duration-200">
                                <i class="pi pi-cog" style="font-size: 1.2rem"></i>
                            </Button> -->
                            <Button icon="pi pi-cog" severity="secondary" variant="text" size="large" rounded
                                aria-label="Bookmark" @click="toggleUserMenu" v-tooltip.bottom="'User Menu'" />

                            <Popover ref="userMenuRef" class="w-56 !rounded-2xl">
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="pi pi-user text-blue-600"></i>
                                            <h3 class="text-base font-semibold">User Menu</h3>
                                        </div>
                                    </div>
                                    <p class="text-xs opacity-60 mt-1">
                                        {{ $page.props.auth.user.name }}
                                    </p>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <Link :href="route('user.reports')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                        <i class="pi pi-file-pdf opacity-70"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">My Reports</span>
                                            <p class="text-xs opacity-60">View your generated reports</p>
                                        </div>
                                    </Link>

                                    <Link :href="route('user.settings')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                        <i class="pi pi-cog opacity-70"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Settings</span>
                                            <p class="text-xs opacity-60">Account preferences</p>
                                        </div>
                                    </Link>

                                    <Link :href="route('user-activity-logs.index')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                        <i class="pi pi-chart-line opacity-70"></i>
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Activity</span>
                                            <p class="text-xs opacity-60">View your recent activity</p>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Footer -->
                                <div
                                    class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <Button @click="handleLogout" label="Sign Out" icon="pi pi-sign-out"
                                        severity="danger" variant="text" class="w-full" />
                                </div>
                            </Popover>
                        </div>

                    </div>
                </div>
            </div>

            <div ref="contentRef"
                class="content-scroll flex-1 overflow-y-auto px-4 md:px-6 pt-6 short:pt-3 pb-10 short:pb-4 transition-[margin-left] duration-300"
                :class="sidebarMinimized ? 'md:ml-[130px]' : 'md:ml-[240px]'">
                <!-- <ToastList /> -->
                <slot />
            </div>

            <!-- Scroll to Top Button -->
            <ScrollToTop :showThreshold="400" :scrollDuration="0.8" />
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
    background: transparent;
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

/* Auto-hide scrollbar: visible on hover, hidden when idle */
.menu {
    scrollbar-width: thin;
    scrollbar-color: transparent transparent;
    transition: scrollbar-color 0.3s;
}

.menu:hover {
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.menu::-webkit-scrollbar {
    width: 4px;
}

.menu::-webkit-scrollbar-track {
    background: transparent;
    margin-top: 8px;
    margin-bottom: 32px;
}

.menu::-webkit-scrollbar-thumb {
    background: transparent;
    border-radius: 4px;
}

.menu:hover::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
}

/* Cool blue-gray frosted glass background */
.content-bg {
    background:
        radial-gradient(ellipse at 20% 10%, rgba(230, 235, 242, 0.7) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 30%, rgba(220, 228, 238, 0.6) 0%, transparent 45%),
        radial-gradient(ellipse at 10% 70%, rgba(225, 231, 240, 0.5) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 85%, rgba(222, 229, 239, 0.6) 0%, transparent 40%),
        radial-gradient(ellipse at 50% 50%, rgba(228, 233, 241, 0.55) 0%, transparent 60%),
        linear-gradient(160deg, rgba(235, 239, 245, 0.5) 0%, rgba(225, 230, 238, 0.4) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Dark mode content background */
.dark .content-bg,
html.dark .content-bg {
    background:
        radial-gradient(ellipse at 20% 10%, rgba(34, 40, 49, 0.6) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 30%, rgba(28, 34, 44, 0.5) 0%, transparent 45%),
        radial-gradient(ellipse at 50% 50%, rgba(30, 37, 48, 0.4) 0%, transparent 60%),
        linear-gradient(160deg, #16181f 0%, #1a1e27 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Auto-hide content area scrollbar */
.content-scroll {
    scrollbar-width: thin;
    scrollbar-color: transparent transparent;
    transition: scrollbar-color 0.3s;
}

.content-scroll.is-scrolling {
    scrollbar-color: rgba(156, 163, 175, 0.4) transparent;
}

.content-scroll::-webkit-scrollbar {
    width: 6px;
}

.content-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.content-scroll::-webkit-scrollbar-thumb {
    background: transparent;
    border-radius: 4px;
}

.content-scroll.is-scrolling::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.4);
}

/* Disable html/body scroll — content area handles it */
html,
body {
    overflow: hidden;
    height: 100%;
    background:
        radial-gradient(ellipse at 20% 10%, rgba(230, 235, 242, 0.7) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 30%, rgba(220, 228, 238, 0.6) 0%, transparent 45%),
        radial-gradient(ellipse at 10% 70%, rgba(225, 231, 240, 0.5) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 85%, rgba(222, 229, 239, 0.6) 0%, transparent 40%),
        radial-gradient(ellipse at 50% 50%, rgba(228, 233, 241, 0.55) 0%, transparent 60%),
        linear-gradient(160deg, rgba(235, 239, 245, 0.5) 0%, rgba(225, 230, 238, 0.4) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Dark mode body */
html.dark,
html.dark body {
    background: #16181f;
    color-scheme: dark;
}
</style>
