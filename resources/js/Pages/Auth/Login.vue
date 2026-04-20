<script setup>
import InputError from "@/Components/ui/inputs/InputError.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
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

const isDark = ref(localStorage.getItem('theme') === 'dark');

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>

    <Head title="Sign In" />

    <div class="mac-login-screen h-dvh overflow-hidden relative flex items-center justify-center antialiased"
        :class="{ dark: isDark }">
        <div class="mac-wallpaper absolute inset-0"></div>

        <div class="relative z-[1] flex flex-col items-center w-full max-w-[340px] p-5">
            <Message v-if="status" severity="success" :closable="false" class="mac-status">
                {{ status }}
            </Message>

            <div class="flex flex-col items-center w-full">
                <div class="flex items-center gap-4 mb-3.5">
                    <img src="/images/yakap-logo.png" alt="YAKAP"
                        class="mac-logo w-16 h-16 object-contain rounded-[14px]" />
                    <img src="/images/pgp-logo.png" alt="PGP"
                        class="mac-logo w-16 h-16 object-contain rounded-[14px]" />
                </div>

                <h1 class="mac-title text-[22px] font-semibold tracking-[-0.3px] m-0 mb-1 text-center">Scholarship
                    Management</h1>
                <p class="mac-subtitle text-[13px] mt-0 mb-7 font-normal">Sign in to continue</p>

                <form @submit.prevent="submit" class="w-full flex flex-col items-center gap-2.5">
                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="user" :size="14" />
                            </InputIcon>
                            <InputText id="username" v-model="form.username" placeholder="Username" required autofocus
                                autocomplete="username" class="mac-input" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]" :message="form.errors.username" />
                    </div>

                    <div class="w-full">
                        <IconField class="mac-iconfield">
                            <InputIcon>
                                <AppIcon name="lock" :size="14" />
                            </InputIcon>
                            <Password v-model="form.password" placeholder="Password" :feedback="false" toggleMask
                                inputClass="mac-input" class="mac-password" required autocomplete="current-password" />
                        </IconField>
                        <InputError class="mt-1 pl-1 text-xs text-[#FF3B30]" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between w-full mt-0.5">
                        <label class="mac-checkbox-label flex items-center gap-2 cursor-pointer text-xs">
                            <ToggleSwitch v-model="form.remember" />
                            <span>Remember me</span>
                        </label>
                        <Link :href="route('register')" class="mac-link text-xs font-medium no-underline">
                            Create Account
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

<style scoped>
/* Font stack */
.mac-login-screen {
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
}

/* ═══ Light theme colors ═══ */

.mac-wallpaper {
    background: linear-gradient(160deg, #f5f5f7 0%, #e8e8ed 40%, #f0f0f3 70%, #e5e5ea 100%);
}

.mac-logo {
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
}

.mac-title {
    color: #1d1d1f;
}

.mac-subtitle {
    color: rgba(0, 0, 0, 0.45);
}

.mac-checkbox-label {
    color: rgba(0, 0, 0, 0.45);
    transition: color 0.15s;
}

.mac-checkbox-label:hover {
    color: rgba(0, 0, 0, 0.65);
}

.mac-link {
    color: #007AFF;
    transition: color 0.15s;
}

.mac-link:hover {
    color: #0055D4;
}

.mac-footer {
    color: rgba(0, 0, 0, 0.3);
}

/* ═══ PrimeVue component overrides ═══ */

:deep(.mac-status.p-message) {
    width: 100%;
    margin-bottom: 24px;
    border-radius: 10px;
    backdrop-filter: blur(20px);
    font-size: 13px;
    font-weight: 500;
}

:deep(.mac-iconfield) {
    width: 100%;
}

:deep(.mac-iconfield .p-inputicon) {
    color: rgba(0, 0, 0, 0.3);
}

:deep(.mac-iconfield .p-inputtext),
:deep(.mac-input) {
    width: 100%;
    background: rgba(255, 255, 255, 0.7) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: 10px !important;
    color: #1d1d1f !important;
    font-size: 14px;
    height: 40px;
    font-family: inherit;
    backdrop-filter: blur(20px);
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}

:deep(.mac-iconfield .p-inputtext:focus),
:deep(.mac-input:focus) {
    border-color: rgba(0, 122, 255, 0.5) !important;
    background: rgba(255, 255, 255, 0.85) !important;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15) !important;
}

:deep(.mac-iconfield .p-inputtext::placeholder),
:deep(.mac-input::placeholder) {
    color: rgba(0, 0, 0, 0.3) !important;
}

:deep(.mac-password) {
    width: 100%;
}

:deep(.mac-password .p-password-toggle-mask-btn) {
    color: rgba(0, 0, 0, 0.3) !important;
    width: 32px;
    height: 32px;
}

:deep(.mac-password .p-password-toggle-mask-btn:hover) {
    color: rgba(0, 0, 0, 0.55) !important;
    background: transparent !important;
}

:deep(.p-toggleswitch) {
    transform: scale(0.8);
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
    background: #007AFF !important;
}

:deep(.mac-submit.p-button) {
    width: 36px !important;
    height: 36px !important;
    min-width: 36px !important;
    padding: 0 !important;
    background: #007AFF !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(0, 122, 255, 0.25);
    transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
}

:deep(.mac-submit.p-button:hover) {
    background: #0055D4 !important;
    transform: scale(1.05);
    box-shadow: 0 4px 14px rgba(0, 122, 255, 0.3);
}

:deep(.mac-submit.p-button:active) {
    transform: scale(0.95);
}

:deep(.mac-submit.p-button:disabled) {
    opacity: 0.5;
    transform: none;
}

:deep(.mac-theme-toggle.p-button) {
    width: 36px !important;
    height: 36px !important;
    min-width: 36px !important;
    border: none !important;
    background: transparent !important;
    color: #f5a623 !important;
    font-size: 20px;
    transition: all 0.3s ease;
}

:deep(.mac-theme-toggle.p-button .p-button-icon) {
    font-size: 20px;
}

:deep(.mac-theme-toggle.p-button:hover) {
    background: rgba(0, 0, 0, 0.05) !important;
    color: #e09500 !important;
    transform: scale(1.1);
}

/* ═══ Dark mode overrides ═══ */

.dark .mac-wallpaper {
    background: linear-gradient(160deg, #1c2028 0%, #222831 40%, #1e242d 70%, #222831 100%);
}

.dark .mac-logo {
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.4));
}

.dark .mac-title {
    color: #f5f5f7;
}

.dark .mac-subtitle {
    color: rgba(255, 255, 255, 0.45);
}

.dark .mac-checkbox-label {
    color: rgba(255, 255, 255, 0.45);
}

.dark .mac-checkbox-label:hover {
    color: rgba(255, 255, 255, 0.65);
}

.dark .mac-link {
    color: #0A84FF;
}

.dark .mac-link:hover {
    color: #409CFF;
}

.dark .mac-footer {
    color: rgba(255, 255, 255, 0.25);
}

.dark .mac-status {
    background: rgba(52, 199, 89, 0.15);
    border-color: rgba(52, 199, 89, 0.3);
    color: #30d158;
}

.dark :deep(.mac-theme-toggle.p-button) {
    color: rgba(255, 255, 255, 0.5) !important;
}

.dark :deep(.mac-theme-toggle.p-button:hover) {
    background: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.8) !important;
}

.dark :deep(.mac-iconfield .p-inputicon) {
    color: rgba(255, 255, 255, 0.3);
}

.dark :deep(.mac-iconfield .p-inputtext),
.dark :deep(.mac-input) {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #f5f5f7 !important;
}

.dark :deep(.mac-iconfield .p-inputtext:focus),
.dark :deep(.mac-input:focus) {
    border-color: rgba(10, 132, 255, 0.5) !important;
    background: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.2) !important;
}

.dark :deep(.mac-iconfield .p-inputtext::placeholder),
.dark :deep(.mac-input::placeholder) {
    color: rgba(255, 255, 255, 0.25) !important;
}

.dark :deep(.mac-password .p-password-toggle-mask-btn) {
    color: rgba(255, 255, 255, 0.3) !important;
}

.dark :deep(.mac-password .p-password-toggle-mask-btn:hover) {
    color: rgba(255, 255, 255, 0.6) !important;
}

.dark :deep(.mac-submit.p-button) {
    background: #0A84FF !important;
    box-shadow: 0 2px 8px rgba(10, 132, 255, 0.3);
}

.dark :deep(.mac-submit.p-button:hover) {
    background: #409CFF !important;
    box-shadow: 0 4px 14px rgba(10, 132, 255, 0.4);
}
</style>
