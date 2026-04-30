<template>

    <Head title="Mobile Upload Settings" />
    <AdminLayout>
        <Toast />

        <AdminPageShell title="Mobile Upload Settings"
            description="Configure the mobile upload entrypoint, upload limits, token lifetime, and image optimization defaults from one iOS-styled control surface."
            icon="mobile" eyebrow="Admin Settings">
            <template #actions>
                <AppButton label="Save Settings" icon="save" class="rounded-full" :loading="saving"
                    @click="saveSettings" />
            </template>

            <section class="ios-section">
                <div class="ios-section-label">Configuration</div>
                <Card class="ios-page-panel">
                    <template #content>
                        <Tabs v-model:value="activeTab">
                            <TabList>
                                <Tab value="general">
                                    <AppIcon name="globe" class="mr-2" />General
                                </Tab>
                                <Tab value="uploads">
                                    <AppIcon name="upload" class="mr-2" />File Uploads
                                </Tab>
                                <Tab value="tokens">
                                    <AppIcon name="key" class="mr-2" />Tokens
                                </Tab>
                                <Tab value="image">
                                    <AppIcon name="image" class="mr-2" />Image Optimization
                                </Tab>
                            </TabList>

                            <TabPanels>
                                <!-- ─── General Tab ─── -->
                                <TabPanel value="general">
                                    <div class="space-y-6 py-2">
                                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex gap-3">
                                            <AppIcon name="info-circle" class="text-blue-500 mt-0.5" />
                                            <p class="text-sm text-blue-700">
                                                The Base URL is used to generate QR code links sent to mobile devices.
                                                For devices on the same LAN (local network), enable LAN IP detection so
                                                the
                                                QR code points to the server's local IP instead of a domain.
                                            </p>
                                        </div>

                                        <!-- Base URL -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Base URL
                                                <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <InputText v-model="form.general.base_url"
                                                placeholder="https://your-domain.com or http://192.168.1.10:8000"
                                                class="w-full" />
                                            <p class="text-xs text-gray-500 mt-1">
                                                The root URL appended to all mobile upload links. No trailing slash.
                                            </p>
                                        </div>

                                        <!-- LAN IP Detection -->
                                        <div
                                            class="flex items-start gap-4 p-4 rounded-2xl border border-gray-200 bg-gray-50">
                                            <ToggleSwitch v-model="form.general.use_lan_ip" size="small" />
                                            <div>
                                                <p class="font-semibold text-gray-800 text-sm">LAN IP Detection</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Automatically detect the server's local IP address for QR codes.
                                                    Useful when mobile devices are on the same Wi-Fi network.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Manual LAN IP override -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">LAN IP
                                                Override
                                                <span class="text-gray-400 font-normal">(optional)</span></label>
                                            <InputText v-model="form.general.lan_ip_override"
                                                placeholder="e.g. 192.168.1.100" class="w-full" />
                                            <p class="text-xs text-gray-500 mt-1">
                                                Leave blank to use auto-detection. Set a fixed IP if auto-detection
                                                gives
                                                the wrong address.
                                            </p>
                                        </div>

                                        <!-- Port override -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Port Override
                                                <span class="text-gray-400 font-normal">(optional)</span></label>
                                            <InputText v-model="form.general.port_override" placeholder="e.g. 8000"
                                                class="w-full" style="max-width: 200px" />
                                            <p class="text-xs text-gray-500 mt-1">
                                                Leave blank to use the port from the Base URL. Set only if the app
                                                listens
                                                on a non-standard port.
                                            </p>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- ─── File Uploads Tab ─── -->
                                <TabPanel value="uploads">
                                    <div class="space-y-4 py-2">
                                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex gap-3">
                                            <AppIcon name="exclamation-triangle" class="text-amber-500 mt-0.5" />
                                            <p class="text-sm text-amber-700">
                                                Configure max file size and allowed file types for each upload entity.
                                                1 MB = 1024 KB.
                                            </p>
                                        </div>

                                        <div v-for="entity in uploadEntities" :key="entity.key"
                                            class="border border-gray-200 rounded-2xl overflow-hidden">
                                            <!-- Entity header -->
                                            <div
                                                class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-200">
                                                <AppIcon :name="entity.icon" class="text-indigo-500" />
                                                <span class="font-semibold text-gray-800">{{ entity.label }}</span>
                                            </div>

                                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Max size -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                                        Max File Size (KB)
                                                    </label>
                                                    <InputNumber v-model="form.uploads[entity.key].max_size_kb"
                                                        :min="512" :max="102400" :step="512" showButtons
                                                        buttonLayout="horizontal" class="w-full" fluid />
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        {{ (form.uploads[entity.key].max_size_kb / 1024).toFixed(1) }}
                                                        MB
                                                    </p>
                                                </div>

                                                <!-- Allowed types -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-3">Allowed
                                                        File
                                                        Types</label>
                                                    <div class="flex flex-wrap gap-3">
                                                        <div v-for="ft in availableFileTypes" :key="ft"
                                                            class="flex items-center gap-2">
                                                            <Checkbox v-model="form.uploads[entity.key].allowed_types"
                                                                :value="ft" :inputId="`${entity.key}-${ft}`" />
                                                            <label :for="`${entity.key}-${ft}`"
                                                                class="text-sm font-mono cursor-pointer select-none">.{{
                                                                    ft
                                                                }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- ─── Tokens Tab ─── -->
                                <TabPanel value="tokens">
                                    <div class="space-y-4 py-2">
                                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex gap-3">
                                            <AppIcon name="clock" class="text-blue-500 mt-0.5" />
                                            <p class="text-sm text-blue-700">
                                                Set how many <strong>minutes</strong> a mobile upload QR code / token
                                                remains valid for each entity type.
                                                After expiry the link will no longer work and a new QR code must be
                                                generated.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div v-for="entity in uploadEntities" :key="entity.key"
                                                class="border border-gray-200 rounded-2xl p-5">
                                                <div class="flex items-center gap-2 mb-4">
                                                    <AppIcon :name="entity.icon" class="text-indigo-500" />
                                                    <span class="font-semibold text-gray-800 text-sm">{{ entity.label
                                                        }}</span>
                                                </div>
                                                <InputNumber v-model="form.tokens[entity.key]" :min="1" :max="525600"
                                                    :step="60" showButtons buttonLayout="horizontal" class="w-full"
                                                    fluid />
                                                <p class="text-xs text-gray-400 mt-2 text-center">
                                                    minutes until expiry
                                                    <span class="block text-gray-300">
                                                        (~{{ (form.tokens[entity.key] / 1440).toFixed(1) }} days)
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>

                                <!-- ─── Image Optimization Tab ─── -->
                                <TabPanel value="image">
                                    <div class="space-y-6 py-2">
                                        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex gap-3">
                                            <AppIcon name="image" class="text-purple-500 mt-0.5" />
                                            <p class="text-sm text-purple-700">
                                                Images are optimized on upload to reduce storage and bandwidth.
                                                Quality affects file size vs. visual clarity. Dimensions set the maximum
                                                pixel size.
                                            </p>
                                        </div>

                                        <!-- JPEG Quality -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                JPEG Quality
                                                <span
                                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                                    {{ form.image.jpeg_quality }}%
                                                </span>
                                            </label>
                                            <p class="text-xs text-gray-500 mb-3">Lower = smaller file, less detail.
                                                Higher
                                                = larger file, better quality.</p>
                                            <div class="flex items-center gap-4">
                                                <span class="text-xs text-gray-400 w-8">10%</span>
                                                <input v-model.number="form.image.jpeg_quality" type="range" min="10"
                                                    max="100" step="5" class="flex-1 accent-indigo-600" />
                                                <span class="text-xs text-gray-400 w-10">100%</span>
                                            </div>
                                            <div class="flex justify-between mt-2 px-8">
                                                <span class="text-xs text-gray-400">Smaller file</span>
                                                <span class="text-xs text-gray-400">Best quality</span>
                                            </div>
                                        </div>

                                        <!-- Max Dimensions -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Max Width
                                                    (px)</label>
                                                <InputNumber v-model="form.image.max_width" :min="320" :max="8000"
                                                    :step="80" showButtons buttonLayout="horizontal" fluid
                                                    class="w-full" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Max Height
                                                    (px)</label>
                                                <InputNumber v-model="form.image.max_height" :min="320" :max="8000"
                                                    :step="80" showButtons buttonLayout="horizontal" fluid
                                                    class="w-full" />
                                            </div>
                                        </div>

                                        <!-- Auto-rotate -->
                                        <div
                                            class="flex items-start gap-4 p-4 rounded-2xl border border-gray-200 bg-gray-50">
                                            <ToggleSwitch v-model="form.image.auto_rotate" size="small" />
                                            <div>
                                                <p class="font-semibold text-gray-800 text-sm">Auto-rotate from EXIF</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Automatically rotate images based on the EXIF orientation tag
                                                    embedded
                                                    by phone cameras.
                                                    Recommended: enabled.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Preserve original format -->
                                        <div
                                            class="flex items-start gap-4 p-4 rounded-2xl border border-gray-200 bg-gray-50">
                                            <ToggleSwitch v-model="form.image.preserve_original_format" size="small" />
                                            <div>
                                                <p class="font-semibold text-gray-800 text-sm">Preserve PNG &amp; GIF
                                                    format
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    When enabled, PNG files are kept as PNG (lossless, max compression)
                                                    and
                                                    GIF files
                                                    are kept as GIF — neither is converted to JPEG. Disable to convert
                                                    all
                                                    images to JPEG.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </section>
        </AdminPageShell>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageShell from '@/Components/admin/AdminPageShell.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const toast = useToast();
const activeTab = ref('general');
const saving = ref(false);

// Deep-clone settings into reactive form
const form = ref(JSON.parse(JSON.stringify(props.settings)));

const availableFileTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];

const uploadEntities = [
    { key: 'disbursement', label: 'Disbursement', icon: 'money-bill' },
    { key: 'scholarship_record', label: 'Scholarship Record', icon: 'graduation-cap' },
    { key: 'profile', label: 'Profile Photo', icon: 'user' },
    { key: 'requirement', label: 'Requirement', icon: 'file-check' },
    { key: 'fund_transaction', label: 'Fund Transaction', icon: 'wallet' },
];

async function saveSettings() {
    saving.value = true;
    try {
        await axios.post(route('admin.mobile-upload-settings.update'), form.value);
        toast.add({
            severity: 'success',
            summary: 'Saved',
            detail: 'Mobile upload settings saved successfully.',
            life: 3000,
        });
    } catch (err) {
        const detail = err.response?.data?.message
            ?? err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat().join(' ')
            : 'Failed to save settings.';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: detail,
            life: 5000,
        });
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
:deep(.p-inputtext),
:deep(.p-select) {
    border-radius: 1rem;
}

:deep(.p-checkbox .p-checkbox-box) {
    border-radius: 0.5rem;
}

input[type="range"] {
    height: 6px;
    border-radius: 9999px;
    cursor: pointer;
}
</style>
