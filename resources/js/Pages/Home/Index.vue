<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, onMounted } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

// ─── Props ───
const props = defineProps({
    user: Object,
});

// ─── State ───
const systemUpdates = ref([]);

// ─── System Updates ───
const fetchSystemUpdates = async () => {
    try {
        const response = await fetch('/api/system-updates', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            systemUpdates.value = data.updates || [];
        }
    } catch (error) {
        console.error('Error fetching system updates:', error);
        systemUpdates.value = [];
    }
};

onMounted(() => {
    fetchSystemUpdates();
});

const sortedSystemUpdates = computed(() => {
    return [...systemUpdates.value].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

</script>

<template>
    <Head title="Home" />
    <AdminLayout>
        <!-- Toolbar -->
        <Toolbar class="mb-4 -mt-2 !rounded-4xl !px-8">
            <template #start>
                <div class="flex items-center gap-3">
                    <AppIcon name="graduation-cap" :size="32" class="text-indigo-900" />
                    <div>
                        <h1 class="text-2xl short:text-xl font-bold text-gray-700">PGP Scholarship Portal</h1>
                        <p class="text-sm text-gray-600">A centralized platform for managing scholarship applications, requirements, and updates.</p>
                    </div>
                </div>
            </template>
        </Toolbar>

        <div class="flex flex-column gap-4">

            <!-- ═══ SYSTEM UPDATES ═══ -->
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-500 mb-3">SYSTEM UPDATES</div>
                <Card>
                    <template #content>
                        <template v-if="sortedSystemUpdates.length > 0">
                            <div v-for="(update, idx) in sortedSystemUpdates.slice(0, 5)" :key="idx"
                                class="cursor-pointer hover:surface-hover border-round p-3 transition-duration-200"
                                :class="{ 'border-bottom-1 surface-border': idx < Math.min(sortedSystemUpdates.length, 5) - 1 }"
                                @click="router.visit(route('system-updates.index'))">
                                <div class="flex align-items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold white-space-nowrap overflow-hidden text-overflow-ellipsis">{{ update.title }}</div>
                                        <div class="text-xs text-500 mt-1 line-height-2">{{ update.content }}</div>
                                        <Tag :value="update.created_at" severity="secondary" class="mt-2" />
                                    </div>
                                  
                                </div>
                            </div>
                            <div v-if="sortedSystemUpdates.length > 5" class="text-center pt-2 border-top-1 surface-border">
                                <Button label="View All Updates" link size="small"
                                    @click="router.visit(route('system-updates.index'))" />
                            </div>
                        </template>
                        <div v-else class="flex flex-column align-items-center py-6 text-500">
                            <i class="pi pi-inbox text-3xl mb-2" />
                            <span class="text-sm">No system updates at the moment</span>
                        </div>
                    </template>
                </Card>
            </div>

        </div>

        <!-- Help Dialog -->
        <Dialog v-model:visible="showHelpDialog" header="Help & Support" :modal="true" :style="{ width: '640px' }"
            :breakpoints="{ '960px': '95vw' }">
            <div class="flex flex-column gap-3">
                <Panel header="Contact" toggleable :collapsed="false">
                    <div class="flex flex-column gap-3">
                        <div class="flex align-items-center gap-2">
                            <i class="pi pi-users text-primary" />
                            <span class="font-semibold">Scholarship Support Team</span>
                        </div>
                        <div class="flex align-items-center gap-2">
                            <i class="pi pi-envelope text-green-500" />
                            <span class="text-primary">scholarship@example.com</span>
                        </div>
                        <div class="flex align-items-center gap-2">
                            <i class="pi pi-phone text-orange-500" />
                            <span>+1 (555) XXX-XXXX</span>
                        </div>
                    </div>
                </Panel>
                <Panel header="Frequently Asked" toggleable :collapsed="false">
                    <div class="flex flex-column gap-3">
                        <div>
                            <p class="font-medium">How long does application review take?</p>
                            <p class="text-sm text-500">Applications are typically reviewed within 2–4 weeks of submission.</p>
                        </div>
                        <div>
                            <p class="font-medium">Can I edit my application after submission?</p>
                            <p class="text-sm text-500">You can edit until your application is under review.</p>
                        </div>
                    </div>
                </Panel>
            </div>
        </Dialog>
    </AdminLayout>
</template>

