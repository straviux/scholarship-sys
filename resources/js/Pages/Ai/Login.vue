<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('ai.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-[#0f1117] text-slate-100 px-4 py-10">

        <Head>
            <title>AI Assistant Login</title>
        </Head>

        <div class="w-full max-w-md">
            <!-- Logo / Heading -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-xl shadow-indigo-500/30 mb-4">
                    <span class="text-2xl font-bold text-white">AI</span>
                </div>
                <h1 class="text-2xl font-semibold">YAKAP Scholar AI</h1>
                <p class="text-sm text-slate-400 mt-1">Sign in to query scholarship data &amp; generate reports.</p>
            </div>

            <div v-if="status" class="mb-4 text-sm text-emerald-400 text-center">{{ status }}</div>

            <form @submit.prevent="submit" class="bg-[#13161f] border border-slate-800 rounded-2xl p-6 shadow-2xl">
                <!-- Username -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Username</label>
                    <InputText v-model="form.username" type="text" autocomplete="username" autofocus
                        class="w-full !bg-[#0f1117] !border-slate-700 !text-slate-100"
                        :class="{ '!border-red-500': form.errors.username }" />
                    <div v-if="form.errors.username" class="text-xs text-red-400 mt-1">{{ form.errors.username }}</div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Password</label>
                    <Password v-model="form.password" toggle-mask :feedback="false"
                        input-class="w-full !bg-[#0f1117] !border-slate-700 !text-slate-100" class="w-full ai-password"
                        :class="{ '!border-red-500': form.errors.password }" />
                    <div v-if="form.errors.password" class="text-xs text-red-400 mt-1">{{ form.errors.password }}</div>
                </div>

                <!-- Remember -->
                <label class="flex items-center gap-2 mb-5 text-sm text-slate-300 cursor-pointer select-none">
                    <input v-model="form.remember" type="checkbox"
                        class="rounded border-slate-600 bg-[#0f1117] text-indigo-500 focus:ring-indigo-500" />
                    <span>Remember me</span>
                </label>

                <button type="submit" :disabled="form.processing"
                    class="w-full py-2.5 rounded-lg font-medium bg-indigo-600 hover:bg-indigo-500 text-white transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span v-if="form.processing">Signing in…</span>
                    <span v-else>Sign in</span>
                </button>

                <p class="text-[11px] text-center text-slate-500 mt-5">
                    Restricted to <span class="text-slate-300">Administrators</span> and <span
                        class="text-slate-300">Program Managers</span> only.
                </p>
            </form>

            <p class="text-center text-xs text-slate-500 mt-6">
                <a :href="route('login')" class="hover:text-slate-300 transition">← Back to main system</a>
            </p>
        </div>
    </div>
</template>

<style scoped>
.ai-password :deep(input) {
    width: 100%;
    background-color: #0f1117 !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
</style>
