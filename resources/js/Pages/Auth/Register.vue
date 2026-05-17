<script setup>
import InputError from "@/Components/ui/inputs/InputError.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from 'vue';

const form = useForm({
    name: "",
    username: "",
    password: "",
    password_confirmation: "",
});

const isDark = ref(localStorage.getItem('theme') === 'dark');

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>

    <Head title="Sign Up" />

    <div class="mac-login-screen h-dvh overflow-hidden relative flex items-center justify-center antialiased"
        :class="{ dark: isDark }">
        <div class="mac-wallpaper absolute inset-0"></div>

        <div class="relative z-[1] flex flex-col items-center w-full max-w-[340px] p-5">
            <div class="flex flex-col items-center w-full">
                <div class="flex items-center gap-4 mb-3.5">
                    <img src="/images/yakap-logo.png" alt="YAKAP"
                        class="mac-logo w-16 h-16 object-contain rounded-[14px]" />
                    <img src="/images/pgp-logo.png" alt="PGP"
                        class="mac-logo w-16 h-16 object-contain rounded-[14px]" />
                </div>

                <h1 class="mac-title text-[22px] font-semibold tracking-[-0.3px] m-0 mb-1 text-center">Create Account
                </h1>
                <p class="mac-subtitle text-[13px] mt-0 mb-7 font-normal">Join the scholarship portal</p>

                <form @submit.prevent="submit" class="w-full flex flex-col items-center gap-2.5">
                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="user" :size="14" />
                            </InputIcon>
                            <InputText v-model="form.name" placeholder="Full Name" required autofocus
                                autocomplete="name" class="mac-input" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]" :message="form.errors.name" />
                    </div>

                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="at" :size="14" />
                            </InputIcon>
                            <InputText v-model="form.username" placeholder="Username" required autocomplete="username"
                                class="mac-input" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]" :message="form.errors.username" />
                    </div>

                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="lock" :size="14" />
                            </InputIcon>
                            <Password v-model="form.password" placeholder="Password" :feedback="false" toggleMask
                                inputClass="mac-input" class="mac-password" required autocomplete="new-password" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]" :message="form.errors.password" />
                    </div>

                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="check-circle" :size="14" />
                            </InputIcon>
                            <Password v-model="form.password_confirmation" placeholder="Confirm Password"
                                :feedback="false" toggleMask inputClass="mac-input" class="mac-password" required
                                autocomplete="new-password" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]"
                            :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-center w-full mt-0.5">
                        <Link :href="route('login')"
                            class="mac-link text-xs font-medium no-underline flex items-center gap-1">
                            <AppIcon name="sign-in" :size="11" />
                            Already have an account? Sign in
                        </Link>
                    </div>

                    <div class="flex w-full items-center justify-between mt-2">
                        <AppButton :icon="isDark ? 'sun' : 'moon'" @click="toggleTheme"
                            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'" text rounded
                            class="mac-theme-toggle" />
                        <AppButton type="submit" icon="arrow-right" :loading="form.processing"
                            :disabled="form.processing" rounded class="mac-submit" />
                    </div>
                </form>
            </div>

            <div class="mac-footer mt-10 flex items-center gap-1.5 text-[11px]">
                <span>Scholarship Management System</span>
                <span class="text-sm leading-none">·</span>
                <span>© {{ new Date().getFullYear() }}</span>
            </div>
        </div>
    </div>
</template>

