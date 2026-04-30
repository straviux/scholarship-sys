<template>
    <Select v-model="localValue" :options="profiles" filter autoFilterFocus showClear optionLabel="name"
        @filter="onFilter" placeholder="Search Name" class="w-full" :size="iosCompact ? 'small' : undefined"
        :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{ slotProps.value.name }}</div>
            </div>
            <span v-else>
                <div class="flex itesm-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start uppercase">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
    </Select>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useApi } from '@/composable/api';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    label: {
        type: String,
        default: 'name',
    },
    iosCompact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const localValue = ref(props.modelValue);
const profiles = ref([]);

const { data, loading, error, fetchData } = useApi(route('api.profiles.existing'));

const onFilter = async (event) => {
    const query = event.value;
    if (!query || query.length < 2) {
        profiles.value = [];
        return;
    }
    await fetchData({ q: query });
    profiles.value = data.value || [];
};

watch(() => props.modelValue, (val) => {
    localValue.value = val;
});

watch(localValue, (val) => {
    emit('update:modelValue', val);
});

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'profile-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'profile-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});
</script>

<style scoped>
.w-full {
    width: 100%;
}
</style>

<style>
.profile-select-overlay {
    border-radius: 12px;
}

.profile-select-root--compact {
    border-radius: 0.875rem;
}
</style>
