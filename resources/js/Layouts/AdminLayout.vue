<script setup>
import { ref } from "vue";
import SidebarLink from "@/Components/SidebarLink.vue";
import NotificationDropdown from "@/Components/NotificationDropdown.vue";
import { Link } from "@inertiajs/vue3";
import { usePermission } from "@/composable/permissions";
import { DynamicHeroicon } from 'vue-dynamic-heroicons';

const { hasRole, hasPermission } = usePermission();
import {
    Squares2X2Icon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    IdentificationIcon,
    DocumentDuplicateIcon,
    BookOpenIcon,
    ClipboardIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    AdjustmentsHorizontalIcon,
    LockClosedIcon,
    ArrowRightStartOnRectangleIcon,
    TableCellsIcon,
    ShieldExclamationIcon,
    DocumentTextIcon,
    BellIcon
} from "@heroicons/vue/20/solid";
const toggleMenu = ref(false);
const sidebarMinimized = ref(localStorage.getItem('sidebarMinimized') === 'true');

function toggleSidebarMinimized() {
    sidebarMinimized.value = !sidebarMinimized.value;
    localStorage.setItem('sidebarMinimized', sidebarMinimized.value ? 'true' : 'false');
}
</script>

<template>
    <div class="w-full h-full flex">
        <!-- component -->
        <aside
            class="hidden fixed z-10 top-0 left-0 md:flex flex-col bg-[#222831] transition-all duration-300 h-screen min-w-0"
            :class="[sidebarMinimized ? 'md:w-[110px] w-[110px]' : 'md:w-[250px] w-[250px]', toggleMenu ? 'flex!' : '']">

            <div class="flex-1 flex flex-col min-h-0 min-w-0">

                <div class="flex items-center justify-center pt-4 pb-2 text-center">
                    <a href="#" title="home" class="text-2xl font-bold font-mono text-gray-200">
                        <img src="/images/pgp-logo.png" :class="sidebarMinimized ? 'w-10' : 'w-36'" alt="logo" />
                        <p v-if="!sidebarMinimized" class="text-sm mt-4">Scholarship Program</p>
                    </a>

                </div>
                <ul v-if="!sidebarMinimized"
                    class="menu space-y-6 md:space-y-4 mt-8 text-normal md:text-sm w-full text-gray-300 hover:text-gray-50 overflow-y-auto min-h-0 min-w-0 block h-[calc(100vh-160px)]">
                    <li>
                        <SidebarLink :href="route('dashboard')"
                            :active="route().current('dashboard') || route().current('home') || route().current('index')">
                            <Squares2X2Icon class="h-5 w-5 mr-2" />
                            <span class="font-medium">Dashboard</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <AcademicCapIcon class="h-5 w-5 mr-2" />
                                <span class="-mr-1 font-medium">Scholarship</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li>
                                    <SidebarLink :href="route('profile.waitinglist')"
                                        :active="route().current('profile.waitinglist')">
                                        <ClipboardDocumentCheckIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Waiting List</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('profile.index')"
                                        :active="route().current('profile.index')">
                                        <IdentificationIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Existing</span>
                                        <div class="indicator ml-6">
                                            <!-- <span class="indicator-item text-xs indicator-middle badge badge-secondary" v-if="pendingApplicantCount > 0">{{ pendingApplicantCount }}</span> -->
                                        </div>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship_records.index')"
                                        :active="route().current('scholarship_records.index') || route().current('scholarship_records.showbyprogram')">
                                        <DocumentDuplicateIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Grant Records</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li v-if="hasRole('administrator') || hasRole('moderator')">
                        <details open>
                            <summary>
                                <TableCellsIcon class="h-5 w-5 mr-2" />
                                <span class="-mr-1 font-medium">Library</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li v-if="hasPermission('manage-scholarship-programs')">
                                    <SidebarLink :href="route('scholarshipprograms.index')"
                                        :active="route().current('scholarshipprograms.index')">
                                        <BookOpenIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Programs</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="hasPermission('manage-program-courses')">
                                    <SidebarLink :href="route('courses.index')"
                                        :active="route().current('courses.index')">
                                        <AcademicCapIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Courses</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('program_requirements.index')"
                                        :active="route().current('program_requirements.index')">
                                        <ClipboardIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Requirements</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('school.index')"
                                        :active="route().current('school.index')">
                                        <BuildingOfficeIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Schools</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <details open>
                            <summary>
                                <ShieldExclamationIcon class="h-5 w-5 mr-2" />
                                <span class="-mr-1 font-medium">Administrator</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('users.index')"
                                        :active="route().current('users.index')">
                                        <UserGroupIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Users</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('roles.index')"
                                        :active="route().current('roles.index') || route().current('roles.create')">
                                        <AdjustmentsHorizontalIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Roles</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('permissions.index')"
                                        :active="route().current('permissions.index') || route().current('permissions.create')">
                                        <LockClosedIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">Permissions</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('admin.system-report')"
                                        :active="route().current('admin.system-report')">
                                        <TableCellsIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">System Report</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('admin.system-updates')"
                                        :active="route().current('admin.system-updates')">
                                        <BellIcon class="h-5 w-5 mr-2" />
                                        <span class="-mr-1 font-medium indent-3">System Updates</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                </ul>
                <ul v-else
                    class="menu space-y-4 mt-8 w-full text-gray-300 hover:text-gray-50 items-center min-h-0 min-w-0 block h-[calc(100vh-160px)] overflow-y-auto overflow-x-hidden">
                    <li>
                        <SidebarLink :href="route('dashboard')"
                            :active="route().current('dashboard') || route().current('index')"
                            class="flex flex-col justify-center text-center">

                            <Squares2X2Icon class="h-6 w-6" />
                            <span class="text-xs">dashboard</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('profile.waitinglist')"
                            :active="route().current('profile.waitinglist')"
                            class="flex flex-col justify-center text-center">

                            <ClipboardDocumentCheckIcon class="h-6 w-6" />
                            <span class="text-xs">waiting list</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('profile.index')" :active="route().current('profile.index')"
                            class="flex flex-col justify-center text-center">

                            <IdentificationIcon class="h-6 w-6" />
                            <span class="text-xs">existing</span>
                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('scholarship_records.index')"
                            :active="route().current('scholarship_records.index') || route().current('scholarship_records.showbyprogram')"
                            class="flex flex-col justify-center text-center">

                            <DocumentDuplicateIcon class="h-6 w-6" />
                            <span class="text-xs">scholarship records</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasPermission('manage-scholarship-programs')">
                        <SidebarLink :href="route('scholarshipprograms.index')"
                            :active="route().current('scholarshipprograms.index')"
                            class="flex flex-col justify-center text-center">

                            <BookOpenIcon class="h-6 w-6" />
                            <span class="text-xs">scholarship programs</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasPermission('manage-program-courses')">
                        <SidebarLink :href="route('courses.index')" :active="route().current('courses.index')"
                            class="flex flex-col justify-center text-center">

                            <AcademicCapIcon class="h-6 w-6" />
                            <span class="text-xs">courses</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('program_requirements.index')"
                            :active="route().current('program_requirements.index')"
                            class="flex flex-col items-center justify-center text-center">

                            <ClipboardIcon class="h-6 w-6" />
                            <span class="text-xs">reqs</span>

                        </SidebarLink>
                    </li>
                    <li>
                        <SidebarLink :href="route('school.index')" :active="route().current('school.index')"
                            class="flex flex-col justify-center text-center">

                            <BuildingOfficeIcon class="h-6 w-6" />
                            <span class="text-xs">schools</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('users.index')" :active="route().current('users.index')"
                            class="flex flex-col justify-center text-center">

                            <UserGroupIcon class="h-6 w-6" />
                            <span class="text-xs">users</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('roles.index')"
                            :active="route().current('roles.index') || route().current('roles.create')"
                            class="flex flex-col justify-center text-center">

                            <AdjustmentsHorizontalIcon class="h-6 w-6" />
                            <span class="text-xs">roles</span>

                        </SidebarLink>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <SidebarLink :href="route('permissions.index')"
                            :active="route().current('permissions.index') || route().current('permissions.create')"
                            class="flex flex-col justify-center text-center">

                            <LockClosedIcon class="h-6 w-6" />
                            <span class="text-xs">permissions</span>

                        </SidebarLink>
                    </li>
                </ul>
            </div>
            <div class="px-2 flex justify-end items-center border-t">
                <button @click="toggleSidebarMinimized" class="ml-2 p-2 rounded hover:bg-gray-700 focus:outline-none"
                    :aria-label="sidebarMinimized ? 'Expand sidebar' : 'Minimize sidebar'">
                    <DynamicHeroicon :name="sidebarMinimized ? 'chevron-double-right' : 'chevron-double-left'"
                        class="text-gray-300" :size="6" />
                </button>
            </div>
        </aside>

        <div
            :class="['ml-auto mb-6 w-full min-w-0 flex flex-col', sidebarMinimized ? 'md:w-[calc(100%-110px)] w-[calc(100%-110px)]' : 'md:w-[calc(100%-250px)] w-[calc(100%-250px)]']">
            <div class="sticky z-10 top-0 h-16 border-b bg-[#222831] lg:py-2.5">
                <div class="px-6 flex items-center justify-between space-x-4">
                    <h5 class="text-xl md:text-2xl text-gray-300 hover:text-gray-50 font-medium lg:block">

                        <slot name="header" />
                    </h5>
                    <button class="w-12 h-16 -mr-2 border-none lg:hidden">
                        <Transition>
                            <DynamicHeroicon :name="toggleMenu ? 'x' : 'menu'"
                                class="text-gray-50 button transition-all" :size="9"
                                @click="toggleMenu = !toggleMenu" />
                        </Transition>
                    </button>
                    <!-- <div v-if="!toggleMenu" class="h-full w-full">test</div> -->
                    <div class="space-x-6 hidden md:flex items-center justify-center">
                        <!-- Notification Dropdown -->
                        <NotificationDropdown
                            :unread-count="($page.props.auth.user && $page.props.auth.user.unread_notifications_count) || 0" />

                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button"
                                class="text-white flex items-center text-sm font-medium space-x-3">
                                <!-- User Avatar -->
                                <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center"
                                    :class="{ 'bg-gradient-to-br from-indigo-500 to-purple-600': !$page.props.auth.user.has_profile_photo }">
                                    <img v-if="$page.props.auth.user.has_profile_photo"
                                        :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name"
                                        class="w-full h-full object-cover" />
                                    <span v-else class="text-sm font-bold text-white">
                                        {{ ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
                                <span class="sm:hidden">{{ $page.props.auth.user.name }}</span>
                            </div>
                            <ul tabindex="0"
                                class="menu dropdown-content bg-base-100 rounded-box z-1 mt-4 w-52 p-2 shadow-sm">
                                <li>
                                    <Link class="px-2 py-3 flex items-center space-x-2 group"
                                        :href="route('user.profile')">
                                    <DocumentTextIcon class="h-4 w-4" />
                                    <span>User Profile</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link class="px-2 py-3 flex items-center space-x-2 group" :href="route('logout')"
                                        method="post" as="button">
                                    <ArrowRightStartOnRectangleIcon class="h-4 w-4" />
                                    <span>Logout</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>

            <div class="px-4 md:px-6 pt-6">
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

.v-enter-active,
.v-leave-active {
    transition: opacity 0.1s ease;
}

.v-enter-from,
.v-leave-to {
    opacity: 0;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

.menu :where(li ul)::before {

    background-color: #ccc;
}
</style>