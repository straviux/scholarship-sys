<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from 'vue';

const form = useForm({
    name: "",
    username: "",
    password: "",
    password_confirmation: "",
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordConfirmationVisibility = () => {
    showPasswordConfirmation.value = !showPasswordConfirmation.value;
};
</script>

<template>

    <Head title="Sign Up" />

    <div
        class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center p-4">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-white/40 backdrop-blur-sm">
            <div class="absolute inset-0 opacity-40 pattern-bg"></div>
        </div>

        <!-- Register Card -->
        <div class="relative w-full max-w-md">
            <!-- Main Card -->
            <div class="bg-white/80 backdrop-blur-lg shadow-2xl rounded-2xl border border-white/50 overflow-hidden">
                <!-- Header -->
                <div class="px-8 pt-8 pb-6 text-center">
                    <!-- Logo/Icon -->
                    <div
                        class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                            </path>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Create Account</h1>
                    <p class="text-gray-600">Join the scholarship portal</p>
                </div>

                <!-- Form -->
                <div class="px-8 pb-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Field -->
                        <div class="space-y-2">
                            <InputLabel for="name" value="Full Name" class="text-gray-700 font-medium" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <TextInput id="name" type="text"
                                    class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.name" required autofocus autocomplete="name"
                                    placeholder="Enter your full name" />
                            </div>
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>

                        <!-- Username Field -->
                        <div class="space-y-2">
                            <InputLabel for="username" value="Username" class="text-gray-700 font-medium" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </div>
                                <TextInput id="username" type="text"
                                    class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.username" required autocomplete="username"
                                    placeholder="Choose a username" />
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
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.password" required autocomplete="new-password"
                                    placeholder="Create a password" />
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

                        <!-- Confirm Password Field -->
                        <div class="space-y-2">
                            <InputLabel for="password_confirmation" value="Confirm Password"
                                class="text-gray-700 font-medium" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <TextInput id="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.password_confirmation" required autocomplete="new-password"
                                    placeholder="Confirm your password" />
                                <button type="button" @click="togglePasswordConfirmationVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                    <svg v-if="!showPasswordConfirmation" class="h-5 w-5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
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
                            <InputError class="mt-1" :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <PrimaryButton
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
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
                                <span v-if="form.processing">Creating Account...</span>
                                <span v-else>Create Account</span>
                            </PrimaryButton>
                        </div>

                        <!-- Back to Login -->
                        <div class="text-center">
                            <Link :href="route('login')"
                                class="text-sm text-green-600 hover:text-green-500 transition-colors duration-200 font-medium hover:underline flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                                </path>
                            </svg>
                            Already have an account? Sign in
                            </Link>
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
/* Background pattern */
.pattern-bg {
    background-image: radial-gradient(circle at 50% 50%, rgba(34, 197, 94, 0.05) 0%, transparent 50%);
    background-size: 60px 60px;
}

/* Custom input focus styles */
input:focus {
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .backdrop-blur-lg {
        background-color: rgba(255, 255, 255, 0.9);
    }
}
</style>
