<script setup>
import { ref } from "vue";
import SidebarLink from "@/Components/SidebarLink.vue";
import NotificationDropdown from "@/Components/NotificationDropdown.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { usePermission } from "@/composable/permissions";

// PrimeVue Components
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';

const { hasRole, hasPermission } = usePermission();
const $page = usePage();
const toggleMenu = ref(false);
const sidebarMinimized = ref(localStorage.getItem('sidebarMinimized') === 'true');
const userMenuRef = ref(null);

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
</script>

<template>
    <div class="w-full h-full flex">
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
                <ul v-if="!sidebarMinimized"
                    class="menu space-y-3 md:space-y-2 mt-2 px-3 pb-3 text-sm md:text-xs w-full text-gray-300 hover:text-gray-50 overflow-y-auto min-h-0 min-w-0 block flex-1">
                    <li>
                        <SidebarLink :href="route('dashboard')"
                            :active="route().current('dashboard') || route().current('home') || route().current('index')">
                            <i class="pi pi-home mr-2 text-sm"></i>
                            <span class="font-medium">Dashboard</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <details open>
                            <summary class="cursor-pointer">
                                <i class="pi pi-graduation-cap mr-2 text-sm"></i>
                                <span class="-mr-1 font-medium">Scholarship</span>
                            </summary>
                            <ul class="space-y-1 mt-1 ml-2">
                                <li>
                                    <SidebarLink :href="route('waitinglist.index')"
                                        :active="route().current('waitinglist.index')">
                                        <i class="pi pi-clipboard mr-2 text-sm"></i>
                                        <span class="-mr-1 font-medium">Waiting List</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship.profiles')"
                                        :active="route().current('scholarship.profiles')">
                                        <i class="pi pi-users mr-2 text-sm"></i>
                                        <span class="-mr-1 font-medium">Profiles</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship.applications')"
                                        :active="route().current('scholarship.applications')">
                                        <i class="pi pi-database mr-2 text-sm"></i>
                                        <span class="-mr-1 font-medium">Records</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship.completions')"
                                        :active="route().current('scholarship.completions')">
                                        <i class="pi pi-check-circle mr-2 text-sm"></i>
                                        <span class="-mr-1 font-medium">Completions</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship.renewals')"
                                        :active="route().current('scholarship.renewals')">
                                        <i class="pi pi-refresh mr-2 text-sm"></i>
                                        <span class="-mr-1 font-medium">Renewals</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <SidebarLink :href="route('admin.system-updates')"
                            :active="route().current('admin.system-updates')">
                            <i class="pi pi-bell mr-2"></i>
                            <span class="font-medium">System Updates</span>
                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator') || hasRole('moderator')">
                        <details open>
                            <summary>
                                <i class="pi pi-table mr-2"></i>
                                <span class="-mr-1 font-medium">Library</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li v-if="hasPermission('manage-scholarship-programs')">
                                    <SidebarLink :href="route('scholarshipprograms.index')"
                                        :active="route().current('scholarshipprograms.index')">
                                        <i class="pi pi-book mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Programs</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="hasPermission('manage-program-courses')">
                                    <SidebarLink :href="route('courses.index')"
                                        :active="route().current('courses.index')">
                                        <i class="pi pi-graduation-cap mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Courses</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('program_requirements.index')"
                                        :active="route().current('program_requirements.index')">
                                        <i class="pi pi-list mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Requirements</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('school.index')"
                                        :active="route().current('school.index')">
                                        <i class="pi pi-building mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Schools</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <details open>
                            <summary>
                                <i class="pi pi-shield mr-2"></i>
                                <span class="-mr-1 font-medium">Administrator</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('users.index')"
                                        :active="route().current('users.index')">
                                        <i class="pi pi-users mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Users</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('roles.index')"
                                        :active="route().current('roles.index') || route().current('roles.create')">
                                        <i class="pi pi-cog mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Roles</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('permissions.index')"
                                        :active="route().current('permissions.index') || route().current('permissions.create')">
                                        <i class="pi pi-lock mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">Permissions</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('admin.system-report')"
                                        :active="route().current('admin.system-report')">
                                        <i class="pi pi-chart-bar mr-2"></i>
                                        <span class="-mr-1 font-medium indent-3">System Stats</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                </ul>
                <ul v-else
                    class="menu space-y-3 mt-2 px-2 pb-4 w-full text-gray-300 hover:text-gray-50 items-center min-h-0 min-w-0 block flex-1 overflow-y-auto overflow-x-hidden">
                    <li>
                        <SidebarLink :href="route('dashboard')"
                            :active="route().current('dashboard') || route().current('index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-home text-xl"></i>
                            <span class="text-xs">dashboard</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('waitinglist.index')" :active="route().current('waitinglist.index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-clipboard text-xl"></i>
                            <span class="text-xs">waiting list</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('scholarship.applications')"
                            :active="route().current('scholarship.applications')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-database text-xl"></i>
                            <span class="text-xs">records</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('scholarship.completions')"
                            :active="route().current('scholarship.completions')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-check-circle text-xl"></i>
                            <span class="text-xs">completions</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('scholarship.renewals')"
                            :active="route().current('scholarship.renewals')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-refresh text-xl"></i>
                            <span class="text-xs">renewals</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('admin.system-updates')"
                            :active="route().current('admin.system-updates')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-bell text-xl"></i>
                            <span class="text-xs">system updates</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasPermission('manage-scholarship-programs')">
                        <SidebarLink :href="route('scholarshipprograms.index')"
                            :active="route().current('scholarshipprograms.index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-book text-xl"></i>
                            <span class="text-xs">programs</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasPermission('manage-program-courses')">
                        <SidebarLink :href="route('courses.index')" :active="route().current('courses.index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-graduation-cap text-xl"></i>
                            <span class="text-xs">courses</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('program_requirements.index')"
                            :active="route().current('program_requirements.index')"
                            class="flex flex-col items-center justify-center text-center">

                            <i class="pi pi-list text-xl"></i>
                            <span class="text-xs">reqs</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('school.index')" :active="route().current('school.index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-building text-xl"></i>
                            <span class="text-xs">schools</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('users.index')" :active="route().current('users.index')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-users text-xl"></i>
                            <span class="text-xs">users</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('roles.index')"
                            :active="route().current('roles.index') || route().current('roles.create')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-cog text-xl"></i>
                            <span class="text-xs">roles</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('permissions.index')"
                            :active="route().current('permissions.index') || route().current('permissions.create')"
                            class="flex flex-col justify-center text-center">

                            <i class="pi pi-lock text-xl"></i>
                            <span class="text-xs">permissions</span>

                        </SidebarLink>
                    </li>
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
                        <h5 class="text-xl md:text-2xl text-gray-300 hover:text-gray-50 font-medium hidden md:block">
                            <slot name="header" />
                        </h5>
                    </div>
                    <Button @click="toggleMenu = !toggleMenu" :icon="toggleMenu ? 'pi pi-times' : 'pi pi-bars'" text
                        rounded size="large" class="w-12 h-16 -mr-2 lg:hidden text-gray-50" />
                    <!-- <div v-if="!toggleMenu" class="h-full w-full">test</div> -->
                    <div class="space-x-6 hidden md:flex items-center justify-center">
                        <!-- Notification Dropdown -->
                        <NotificationDropdown
                            :unread-count="($page.props.auth.user && $page.props.auth.user.unread_notifications_count) || 0" />

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
                                    <Link :href="route('user.profile')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="pi pi-user text-gray-600"></i>
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900">Profile</span>
                                        <p class="text-xs text-gray-500">View and edit your profile</p>
                                    </div>
                                    </Link>

                                    <Link :href="route('user.profile')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="pi pi-cog text-gray-600"></i>
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900">Settings</span>
                                        <p class="text-xs text-gray-500">Account preferences</p>
                                    </div>
                                    </Link>

                                    <Link :href="route('user.profile')"
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