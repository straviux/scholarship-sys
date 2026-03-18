<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/ui/inputs/InputError.vue";
import InputLabel from "@/Components/ui/inputs/InputLabel.vue";
import TextInput from "@/Components/ui/inputs/TextInput.vue";
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
                        <i class="pi pi-user-plus text-white text-3xl"></i>
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
                                    <i class="pi pi-user text-gray-400"></i>
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
                                    <i class="pi pi-at text-gray-400"></i>
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
                                    <i class="pi pi-lock text-gray-400"></i>
                                </div>
                                <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.password" required autocomplete="new-password"
                                    placeholder="Create a password" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                    <i class="pi" :class="showPassword ? 'pi-eye-slash' : 'pi-eye'"></i>
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
                                    <i class="pi pi-check-circle text-gray-400"></i>
                                </div>
                                <TextInput id="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="pl-10 pr-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    v-model="form.password_confirmation" required autocomplete="new-password"
                                    placeholder="Confirm your password" />
                                <button type="button" @click="togglePasswordConfirmationVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                    <i class="pi" :class="showPasswordConfirmation ? 'pi-eye-slash' : 'pi-eye'"></i>
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <Button type="submit" label="Create Account" icon="pi pi-user-plus"
                                severity="success" class="w-full"
                                :loading="form.processing"
                                :disabled="form.processing" />
                        </div>

                        <!-- Back to Login -->
                        <div class="text-center">
                            <Link :href="route('login')"
                                class="text-sm text-green-600 hover:text-green-500 transition-colors duration-200 font-medium hover:underline flex items-center justify-center gap-1">
                                <i class="pi pi-sign-in"></i>
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
