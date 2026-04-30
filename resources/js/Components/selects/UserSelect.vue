<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object],
        default: null,
    },
    users: {
        type: Array,
        default: () => [],
    },
    customPlaceholder: {
        type: String,
        default: 'Select user',
    },
    iosCompact: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    showClear: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const normalizeUserToken = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'object' && value !== null) {
        return value.id !== undefined && value.id !== null && value.id !== ''
            ? String(value.id)
            : null;
    }

    return String(value);
};

const userOptions = computed(() => {
    const seen = new Set();

    return props.users
        .flatMap((entry) => Array.isArray(entry) ? entry : [entry])
        .filter((user) => user?.id && user?.name)
        .filter((user) => {
            const key = String(user.id);

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        })
        .map((user) => ({
            id: user.id,
            name: user.name,
            email: user.email ?? '',
        }));
});

const resolveUserId = (value) => {
    const token = normalizeUserToken(value);

    if (!token) {
        return null;
    }

    return userOptions.value.find((user) => String(user.id) === token)?.id
        ?? (typeof value === 'object' && value !== null ? value.id ?? null : value);
};

const isSameUserId = (left, right) => normalizeUserToken(left) === normalizeUserToken(right);

const localValue = ref(resolveUserId(props.modelValue));

watch(
    [() => props.modelValue, () => userOptions.value],
    () => {
        const resolvedValue = resolveUserId(props.modelValue);

        if (!isSameUserId(localValue.value, resolvedValue)) {
            localValue.value = resolvedValue;
        }
    },
    { immediate: true, deep: true }
);

watch(localValue, (value) => {
    const resolvedValue = resolveUserId(value);

    if (!isSameUserId(resolvedValue, props.modelValue)) {
        emit('update:modelValue', resolvedValue);
    }
});

const selectDisabled = computed(() => props.disabled || !userOptions.value.length);

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'user-select-overlay overflow-hidden' },
        pcFilter: { root: { class: '!rounded-lg !border-gray-300' } },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'user-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});
</script>

<template>
    <Select v-model="localValue" :options="userOptions" optionLabel="name" optionValue="id" filter
        :filterFields="['name', 'email']" autoFilterFocus :showClear="showClear" :placeholder="customPlaceholder"
        class="w-full" :disabled="selectDisabled" :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start uppercase">
                <div>{{userOptions.find((user) => user.id === slotProps.value)?.name || slotProps.value}}</div>
            </div>
            <span v-else>
                <div class="flex items-start">{{ slotProps.placeholder }}</div>
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex flex-col items-start leading-tight">
                <div class="uppercase">{{ slotProps.option.name }}</div>
                <div v-if="slotProps.option.email" class="text-[11px] text-surface-500 normal-case">
                    {{ slotProps.option.email }}
                </div>
            </div>
        </template>
    </Select>
</template>

<style>
.user-select-overlay {
    border-radius: 12px;
}

.user-select-root--compact {
    border-radius: 0.875rem;
}
</style>