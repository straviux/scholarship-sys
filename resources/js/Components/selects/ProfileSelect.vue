<template>
    <!-- <Select v-model="localValue" :options="profiles" :optionLabel="props.label" optionValue="id"
        placeholder="Search Profile" :loading="loading" :filter="true" showClear class="w-full" @filter="onFilter" /> -->

    <Select v-model="localValue" :options="profiles" filter autoFilterFocus showClear optionLabel="name"
        @filter="onFilter" placeholder="Search Name" class="w-full">
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
import { ref, watch } from 'vue';
// import { Dropdown } from 'primevue/dropdown';
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
    // console.log(val)
});

watch(localValue, (val) => {
    emit('update:modelValue', val);
});
</script>

<style scoped>
.w-full {
    width: 100%;
}
</style>
