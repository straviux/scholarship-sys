<script setup>
import Checkbox from "@/Components/ui/inputs/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { isAndroid, isIOS, isMobile } from '@basitcodeenv/vue3-device-detect'
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    username: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleRememberMe = () => {
    form.remember = !form.remember;
};
</script>

<template>

    <Head title="Sign In" />

    <div
        class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center p-4">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-white/40 backdrop-blur-sm">
            <div class="absolute inset-0 opacity-40 pattern-bg"></div>
        </div>

        <!-- Login Card -->
        <div class="relative w-full max-w-md">
            <!-- Status Message -->
            <div v-if="status" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="pi pi-check-circle text-green-600 mr-2"></i>
                    <p class="text-green-700 text-sm font-medium">{{ status }}</p>
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white/80 backdrop-blur-lg shadow-2xl rounded-2xl border border-white/50 overflow-hidden">
                <!-- Header -->
                <div class="px-8 pt-8 pb-6 text-center">
                    <!-- Logo/Icon -->
                    <div
                        class="mx-auto w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <i class="pi pi-graduation-cap text-white text-3xl"></i>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back</h1>
                    <p class="text-gray-600">Sign in to your scholarship portal</p>
                </div>

                <!-- Form -->
                <div class="px-8 pb-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Username Field -->
                        <div class="space-y-2">
                            <InputLabel for="username" value="Username" class="text-gray-700 font-medium" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="pi pi-user text-gray-400"></i>
                                </div>
                                <TextInput id="username" type="text"
                                    class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    v-model="form.username" required autofocus autocomplete="username"
                                    placeholder="Enter your username" />
                            </div>
                            <InputError class="mt-1" :message="form.errors.username" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <InputLabel for="password" value="Password" class="text-gray-700 font-medium" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="pi pi-lock text-gray-400"></i>
                                </div>
                                <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    v-model="form.password" required autocomplete="current-password"
                                    placeholder="Enter your password" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                    <i class="pi" :class="showPassword ? 'pi-eye-slash' : 'pi-eye'"></i>
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer group">
                                <div class="relative">
                                    <Checkbox name="remember" v-model:checked="form.remember"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-2 transition-all duration-200 w-4 h-4" />
                                    <div v-if="form.remember"
                                        class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <i class="pi pi-check text-white text-xs"></i>
                                    </div>
                                </div>
                                <span
                                    class="ml-3 text-sm text-gray-700 select-none group-hover:text-gray-900 transition-colors duration-200">
                                    Keep me signed in
                                </span>
                                <div class="ml-2 group relative">
                                    <i class="pi pi-question-circle text-gray-400 hover:text-gray-600 cursor-help transition-colors duration-200"></i>
                                    <!-- Tooltip -->
                                    <div
                                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-10">
                                        Stay logged in for 30 days on this device
                                        <div
                                            class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900">
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <Link :href="route('register')"
                                class="text-sm text-indigo-600 hover:text-indigo-500 transition-colors duration-200 font-medium hover:underline flex items-center gap-1">
                                <i class="pi pi-user-plus"></i>
                                Sign up
                            </Link>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <Button type="submit" label="Sign In" icon="pi pi-sign-in"
                                class="w-full"
                                :loading="form.processing"
                                :disabled="form.processing" />
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    Scholarship Management System
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    © {{ new Date().getFullYear() }} All rights reserved
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom animations */
@keyframes float {

    0%,
    100% {
        transform: translateY(0px);
    }

    50% {
        transform: translateY(-10px);
    }
}

.floating {
    animation: float 6s ease-in-out infinite;
}

/* Custom input focus styles */
input:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Background pattern */
.pattern-bg {
    background-image: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
    background-size: 60px 60px;
}

/* Custom checkbox styles */
input[type="checkbox"]:checked {
    background-color: #4f46e5;
    border-color: #4f46e5;
}

input[type="checkbox"]:focus {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.5);
}

/* Tooltip enhancements */
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}

.group:hover .group-hover\:visible {
    visibility: visible;
}

/* Remember me hover effects */
.remember-label:hover input[type="checkbox"] {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .backdrop-blur-lg {
        background-color: rgba(255, 255, 255, 0.9);
    }
}
</style>
