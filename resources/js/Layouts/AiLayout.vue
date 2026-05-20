<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const primaryRole = computed(() => {
    const roles = user.value?.roles ?? [];
    if (roles.includes('administrator')) return 'Administrator';
    if (roles.includes('program_manager')) return 'Program Manager';
    return roles[0] ?? '';
});

const aiStatus = computed(() => page.props.aiStatus ?? { provider: '', model: '' });

const logoutForm = useForm({});
const logout = () => logoutForm.post(route('ai.logout'));
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#0f1117] text-slate-100">

        <Head>
            <title>YAKAP Scholar AI</title>
        </Head>

        <!-- Top bar -->
        <header
            class="flex items-center justify-between border-b border-slate-800/80 bg-[#13161f]/90 backdrop-blur px-6 py-3">
            <Link :href="route('ai.chat')" class="flex items-center gap-3 group">
                <div
                    class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/20">
                    AI
                </div>
                <div class="leading-tight">
                    <div class="font-semibold text-sm">YAKAP Scholar AI</div>
                    <div class="text-[11px] text-slate-400">Scholarship Insights</div>
                </div>
            </Link>

            <div class="flex items-center gap-4">
                <div v-if="aiStatus.provider"
                    class="hidden md:flex items-center gap-2 text-[11px] px-2.5 py-1 rounded-full bg-slate-800/70 border border-slate-700"
                    :title="`AI provider: ${aiStatus.provider} · ${aiStatus.model}`">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="uppercase tracking-wide text-slate-300">{{ aiStatus.provider }}</span>
                    <span class="text-slate-500">·</span>
                    <span class="text-slate-400 truncate max-w-[160px]">{{ aiStatus.model }}</span>
                </div>
                <div v-if="user" class="text-right leading-tight hidden sm:block">
                    <div class="text-sm font-medium">{{ user.name }}</div>
                    <div class="text-[11px] text-indigo-300">{{ primaryRole }}</div>
                </div>
                <button type="button" @click="logout"
                    class="text-sm px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                    Sign out
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-hidden">
            <slot />
        </main>
    </div>
</template>
