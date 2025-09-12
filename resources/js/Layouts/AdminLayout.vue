<script setup>
import { ref } from "vue";
import SidebarLink from "@/Components/SidebarLink.vue";
import { Link } from "@inertiajs/vue3";
import { usePermission } from "@/composable/permissions";
import { DynamicHeroicon } from 'vue-dynamic-heroicons';

const { hasRole, hasPermission } = usePermission();
import {
    Squares2X2Icon,
    ShieldExclamationIcon,
    ArrowRightStartOnRectangleIcon,
} from "@heroicons/vue/20/solid";
const toggleMenu = ref(false);

</script>

<template>
    <div class="w-full h-full flex">
        <!-- component -->
        <aside
            class="hidden fixed z-10 top-0 left-0 md:flex flex-col bg-[#222831] transition duration-300 md:w-[250px] h-screen min-w-0"
            :class="{ 'flex!': toggleMenu }">
            <div class="flex-1 flex flex-col min-h-0 min-w-0">
                <div class="flex items-center justify-center pt-4 pb-2 text-center">
                    <a href="#" title="home" class="text-2xl font-bold font-mono text-gray-200">
                        <img src="/images/pgp-logo.png" class="w-36" alt="logo" />
                        <p class="text-sm mt-4">Scholarship Program</p>
                    </a>
                </div>
                <ul
                    class="menu space-y-6 md:space-y-4 mt-8 text-normal md:text-sm w-full text-gray-300 hover:text-gray-50 overflow-y-auto min-h-0 min-w-0 block h-[calc(100vh-160px)]">
                    <li>
                        <SidebarLink :href="route('dashboard')" :active="route().current('dashboard')">
                            <Squares2X2Icon class=" h-5 w-5" />

                            <p class="ml-1 font-medium">Dashboard</p>
                        </SidebarLink>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <DynamicHeroicon name="academicCap" :size="6" />
                                <span class="-mr-1 font-medium">Scholarship</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li>
                                    <SidebarLink :href="route('profile.waitinglist')" :active="route().current('profile.waitinglist') ||
                                        route().current('profile.waitinglist')">
                                        <span class="-mr-1 font-medium indent-3">Waiting List</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('profile.index')"
                                        :active="route().current('profile.index')">
                                        <span class="-mr-1 font-medium indent-3">Profiles</span>
                                        <div class="indicator ml-6">
                                            <!-- <span class="indicator-item text-xs indicator-middle badge badge-secondary"
                                                v-if="pendingApplicantCount > 0">{{
                                                    pendingApplicantCount }}</span> -->
                                        </div>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink :href="route('scholarship_records.index')" :active="route().current('scholarship_records.index') ||
                                        route().current('scholarship_records.showbyprogram')">
                                        <span class="-mr-1 font-medium indent-3">Grant Records</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li v-if="hasRole('administrator') || hasRole('moderator')">
                        <details open>
                            <summary>
                                <DynamicHeroicon name="table" :size="6" />
                                <span class="-mr-1 font-medium">Library</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li v-if="hasPermission('manage-scholarship-programs')">
                                    <SidebarLink :href="route('scholarshipprograms.index')"
                                        :active="route().current('scholarshipprograms.index')">
                                        <span class="-mr-1 font-medium indent-3">Programs</span>
                                    </SidebarLink>
                                </li>

                                <li v-if="hasPermission('manage-program-courses')">
                                    <SidebarLink :href="route('courses.index')"
                                        :active="route().current('courses.index')">
                                        <span class="-mr-1 font-medium indent-3">Courses</span>
                                    </SidebarLink>
                                </li>

                                <li>
                                    <SidebarLink :href="route('program_requirements.index')"
                                        :active="route().current('program_requirements.index')">
                                        <span class="-mr-1 font-medium indent-3">Requirements</span>
                                    </SidebarLink>
                                </li>

                                <li>
                                    <SidebarLink :href="route('school.index')"
                                        :active="route().current('school.index')">
                                        <span class="-mr-1 font-medium indent-3">Schools</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li v-if="hasRole('administrator')">
                        <details open>
                            <summary>
                                <ShieldExclamationIcon class="h-5 w-5" />
                                <span class="-mr-1 font-medium">Administrator</span>
                            </summary>
                            <ul class="space-y-1 mt-2">
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('users.index')"
                                        :active="route().current('users.index')">
                                        <!-- <UsersIcon class="h-5 w-5" /> -->
                                        <span class="-mr-1 font-medium indent-3">Users</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('roles.index')" :active="route().current('roles.index') ||
                                        route().current('roles.create')">
                                        <!-- <CogIcon class="h-5 w-5" /> -->
                                        <span class="-mr-1 font-medium indent-3">Roles</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink v-if="hasRole('administrator')" :href="route('permissions.index')"
                                        :active="route().current('permissions.index') ||
                                            route().current('permissions.create')">
                                        <!-- <ShieldExclamationIcon class="h-5 w-5" /> -->
                                        <span class="-mr-1 font-medium indent-3">Permissions</span>
                                    </SidebarLink>
                                </li>
                            </ul>
                        </details>
                    </li>

                </ul>
            </div>
            <div class="px-6 pt-4 flex justify-between items-center border-t">
                <Link class="px-4 py-3 flex items-center space-x-4 rounded-md text-gray-300 hover:text-gray-50 group "
                    :href="route('logout')" method="post" as="button">
                <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                <span>Logout</span>
                </Link>
            </div>
        </aside>

        <div class="ml-auto mb-6 w-full md:w-[calc(100%-250px)] min-w-0 flex flex-col">
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
                    <div class="space-x-4 hidden md:flex">
                        <!-- <button aria-label="notification"
                            class="w-10 h-10 rounded-xl border bg-gray-100 focus:bg-gray-100 active:bg-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 m-auto text-gray-600"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                            </svg>
                        </button> -->
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost rounded-btn text-white">Welcome, {{
                                $page.props.auth.user.name }}</div>
                            <ul tabindex="0"
                                class="menu dropdown-content bg-base-100 rounded-box z-1 mt-4 w-52 p-2 shadow-sm">
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