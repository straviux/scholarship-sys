<script setup>
import { ref, watch } from 'vue';
import { useGSAPAnimation } from '@/composables/useGSAPAnimation';
import { selectAnimation } from '@/utils/animations';
import Select from 'primevue/select';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null,
    },
    options: {
        type: Array,
        required: true,
    },
    optionLabel: String,
    optionValue: String,
    placeholder: String,
    filter: Boolean,
    filterFields: Array,
    showClear: Boolean,
    autoFilterFocus: Boolean,
    disabled: Boolean,
    // PrimeVue pt prop for styling
    pt: Object,
});

const emit = defineEmits(['update:modelValue', 'show', 'hide']);

const select = ref(null);
const { animate, ctx } = useGSAPAnimation();

const onShow = () => {
    const overlay = select.value?.overlayViewChild;
    if (overlay?.el) {
        animate(overlay.el, selectAnimation.open());
    }
    emit('show');
};

const onHide = () => {
    const overlay = select.value?.overlayViewChild;
    if (overlay?.el) {
        animate(overlay.el, selectAnimation.close());
    }
    emit('hide');
};
</script>

<template>
    <Select ref="select" :model-value="modelValue" :options="options" :option-label="optionLabel"
        :option-value="optionValue" :placeholder="placeholder" :filter="filter" :filter-fields="filterFields"
        :show-clear="showClear" :auto-filter-focus="autoFilterFocus" :disabled="disabled" :pt="pt" @show="onShow"
        @hide="onHide" @update:model-value="(val) => emit('update:modelValue', val)" />
</template>
