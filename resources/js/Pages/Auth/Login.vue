<script setup>
import Checkbox from "@/Components/ui/inputs/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import PrimaryButton from "@/Components/ui/buttons/PrimaryButton.vue";
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
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
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
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z">
                            </path>
                        </svg>
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
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
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
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                </div>
                                <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    v-model="form.password" required autocomplete="current-password"
                                    placeholder="Enter your password" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.5 6.5m3.378 3.378a3 3 0 014.243 4.243M9.878 9.878c-.81-.81-1.418-1.85-1.664-3.043m8.908 8.908c.81.81 1.418 1.85 1.664 3.043M12 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.563 3.029M3 3l18 18">
                                        </path>
                                    </svg>
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
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span
                                    class="ml-3 text-sm text-gray-700 select-none group-hover:text-gray-900 transition-colors duration-200">
                                    Keep me signed in
                                </span>
                                <div class="ml-2 group relative">
                                    <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help transition-colors duration-200"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
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
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                    </path>
                                </svg>
                                Sign up
                            </Link>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <PrimaryButton
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                                :disabled="form.processing">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span v-if="form.processing">Signing in...</span>
                                <span v-else>Sign In</span>
                            </PrimaryButton>
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
