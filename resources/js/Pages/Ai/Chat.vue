<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import AiLayout from '@/Layouts/AiLayout.vue';

defineOptions({ layout: AiLayout });

const props = defineProps({
    aiStatus: { type: Object, default: () => ({ provider: '', model: '' }) },
});

const SUGGESTIONS = [
    'Show overall summary',
    'How many pending applications?',
    'Scholars by municipality',
    'Fund transactions this fiscal year',
    'Show disbursements summary',
    'Recent activity',
];

const GREETING = {
    role: 'assistant',
    content:
        "👋 Hello! I'm **YAKAP Scholar AI**. I can help you query scholarship data — applications, scholars, disbursements, fund transactions, and more.\n\nTry a suggestion below, or just ask in plain language.",
};

const conversations = ref([]);
const usageToday = ref(0);
const currentConversationId = ref(null);
const messages = ref([{ ...GREETING }]);
const input = ref('');
const loading = ref(false);
const sidebarOpen = ref(true);
const scrollEl = ref(null);

const showSuggestions = computed(() => messages.value.length <= 1);

// ─── helpers ───────────────────────────────────────────────

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
    });
};

const renderMarkdown = (text) => {
    if (!text) return '';
    const escape = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    let html = escape(text);
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-slate-800 text-indigo-300 text-[0.85em]">$1</code>');
    html = html.replace(/\n/g, '<br>');
    return html;
};

const formatRelative = (dt) => {
    if (!dt) return '';
    const d = new Date(dt);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return d.toLocaleDateString();
};

// ─── API calls ─────────────────────────────────────────────

const fetchConversations = async () => {
    try {
        const { data } = await axios.get(route('ai.conversations.index'));
        conversations.value = data.conversations || [];
        usageToday.value = data.usage_today || 0;
    } catch (e) { /* silent */ }
};

const loadConversation = async (id) => {
    if (loading.value) return;
    try {
        const { data } = await axios.get(route('ai.conversations.show', id));
        currentConversationId.value = data.conversation.id;
        messages.value = data.conversation.messages.map((m) => ({ role: m.role, content: m.content }));
        scrollToBottom();
    } catch (e) { /* silent */ }
};

const newChat = () => {
    currentConversationId.value = null;
    messages.value = [{ ...GREETING }];
    input.value = '';
};

const deleteConversation = async (id) => {
    if (!confirm('Delete this conversation? This cannot be undone.')) return;
    try {
        await axios.delete(route('ai.conversations.destroy', id));
        if (currentConversationId.value === id) newChat();
        await fetchConversations();
    } catch (e) { /* silent */ }
};

const send = async (text) => {
    const content = (text ?? input.value).trim();
    if (!content || loading.value) return;

    messages.value.push({ role: 'user', content });
    input.value = '';
    loading.value = true;
    scrollToBottom();

    try {
        const { data } = await axios.post(route('ai.chat.send'), {
            message: content,
            conversation_id: currentConversationId.value,
        });
        currentConversationId.value = data.conversation_id;
        messages.value.push({ role: 'assistant', content: data.reply ?? 'No response.' });
        await fetchConversations();
    } catch (err) {
        const status = err?.response?.status;
        let msg = err?.response?.data?.error
            || err?.response?.data?.message
            || 'Sorry, something went wrong contacting the AI service.';
        if (status === 429) msg = 'Rate limit reached — please wait a minute before sending another message.';
        messages.value.push({ role: 'assistant', content: `⚠️ ${msg}` });
    } finally {
        loading.value = false;
        scrollToBottom();
    }
};

const handleKey = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
};

onMounted(() => {
    fetchConversations();
});
</script>

<template>

    <Head>
        <title>YAKAP Scholar AI</title>
    </Head>

    <div class="h-full flex">
        <!-- Sidebar -->
        <aside class="border-r border-slate-800 bg-[#0b0d13] transition-all duration-200 flex flex-col"
            :class="sidebarOpen ? 'w-64' : 'w-0 overflow-hidden'">
            <div class="p-3 border-b border-slate-800 flex items-center justify-between gap-2">
                <button type="button" @click="newChat"
                    class="flex-1 text-sm px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition flex items-center justify-center gap-2">
                    <span>＋</span> New chat
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-2 py-2 space-y-1">
                <div v-if="conversations.length === 0" class="text-xs text-slate-500 px-2 py-3 text-center">
                    No previous chats yet.
                </div>
                <div v-for="c in conversations" :key="c.id"
                    class="group rounded-lg px-2.5 py-2 text-sm cursor-pointer flex items-start gap-2 transition"
                    :class="currentConversationId === c.id ? 'bg-indigo-900/40 text-white' : 'hover:bg-slate-800/60 text-slate-300'"
                    @click="loadConversation(c.id)">
                    <div class="flex-1 min-w-0">
                        <div class="truncate">{{ c.title }}</div>
                        <div class="text-[10px] text-slate-500">{{ formatRelative(c.updated_at) }}</div>
                    </div>
                    <button type="button" @click.stop="deleteConversation(c.id)"
                        class="opacity-0 group-hover:opacity-100 text-slate-500 hover:text-red-400 transition text-xs"
                        title="Delete">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Status footer -->
            <div class="border-t border-slate-800 px-3 py-2 text-[10px] text-slate-500 space-y-0.5">
                <div class="flex items-center justify-between">
                    <span>Provider</span>
                    <span class="text-slate-300 uppercase">{{ aiStatus.provider }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Model</span>
                    <span class="text-slate-400 truncate ml-2" :title="aiStatus.model">{{ aiStatus.model }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Messages today</span>
                    <span class="text-slate-300">{{ usageToday }}</span>
                </div>
            </div>
        </aside>

        <!-- Chat area -->
        <div class="flex-1 flex flex-col min-w-0 max-w-4xl mx-auto w-full px-4 py-4">
            <!-- Toolbar -->
            <div class="flex items-center justify-between mb-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen"
                    class="text-xs px-2.5 py-1.5 rounded-lg bg-slate-800/70 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
                    :title="sidebarOpen ? 'Hide sidebar' : 'Show sidebar'">
                    ☰ History
                </button>
                <button v-if="messages.length > 1" type="button" @click="newChat"
                    class="text-xs px-2.5 py-1.5 rounded-lg bg-slate-800/70 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                    Clear chat
                </button>
            </div>

            <!-- Messages -->
            <div ref="scrollEl" class="flex-1 overflow-y-auto space-y-4 pr-1">
                <template v-for="(m, i) in messages" :key="i">
                    <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed shadow" :class="m.role === 'user'
                            ? 'bg-indigo-600 text-white rounded-br-sm'
                            : 'bg-[#1e2235] text-slate-100 border border-slate-800 rounded-bl-sm'">
                            <div v-html="renderMarkdown(m.content)" />
                        </div>
                    </div>
                </template>

                <div v-if="loading" class="flex justify-start">
                    <div
                        class="bg-[#1e2235] border border-slate-800 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce"
                            style="animation-delay: 0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce"
                            style="animation-delay: 150ms"></span>
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce"
                            style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>

            <!-- Suggestions -->
            <div v-if="showSuggestions" class="flex flex-wrap gap-2 mt-4">
                <button v-for="s in SUGGESTIONS" :key="s" type="button" @click="send(s)"
                    class="text-xs px-3 py-1.5 rounded-full bg-[#1a1e2b] hover:bg-[#252a3c] border border-slate-700/70 text-slate-200 transition">
                    {{ s }}
                </button>
            </div>

            <!-- Input -->
            <div class="mt-4">
                <div
                    class="flex items-end gap-2 bg-[#13161f] border border-slate-800 rounded-2xl p-2 focus-within:border-indigo-500/70 transition">
                    <Textarea v-model="input" rows="1" auto-resize
                        placeholder="Ask about scholars, applications, fund transactions…"
                        class="flex-1 !bg-transparent !border-0 !text-slate-100 !shadow-none !resize-none focus:!ring-0"
                        @keydown="handleKey" :disabled="loading" />
                    <button type="button" @click="send()" :disabled="loading || !input.trim()"
                        class="shrink-0 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Send
                    </button>
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5 px-2">
                    AI responses may contain inaccuracies — verify critical figures against source modules.
                </p>
            </div>
        </div>
    </div>
</template>
