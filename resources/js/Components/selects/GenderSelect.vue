<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="genderOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" class="w-full"
        :size="iosCompact ? 'small' : undefined" :pt="selectPt">
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-start">
                <div>{{genderOptions.find(o => o.value === slotProps.value)?.label || slotProps.value}}</div>
            </div>
            <span v-else>{{ slotProps.placeholder }}</span>
        </template>
        <template #option="slotProps">
            <div class="flex items-start">
                <div>{{ slotProps.option.label }}</div>
            </div>
        </template>
    </Select>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    inputId: {
        type: String,
        default: 'gender'
    },
    placeholder: {
        type: String,
        default: 'Select Gender'
    },
    iosCompact: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

const genderOptions = [
    { label: 'Male', value: 'M' },
    { label: 'Female', value: 'F' },
];

const handleUpdate = (value) => {
    emit('update:modelValue', value);
};

const selectPt = computed(() => {
    const basePt = {
        overlay: { class: 'gender-select-overlay overflow-hidden' },
    };

    if (!props.iosCompact) {
        return basePt;
    }

    return {
        ...basePt,
        root: { class: 'gender-select-root--compact', style: 'min-height: 2.25rem;' },
        label: { style: 'padding: 0.4375rem 0.75rem; font-size: 0.8125rem; line-height: 1.2;' },
        dropdown: { style: 'width: 2.25rem;' },
    };
});
</script>

<style>
.gender-select-overlay {
    border-radius: 12px;
}

.gender-select-root--compact {
    border-radius: 0.875rem;
}
</style>
