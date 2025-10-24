<template>
    <Select :modelValue="modelValue" @update:modelValue="handleUpdate" :options="religionOptions" optionLabel="label"
        optionValue="value" :inputId="inputId" :placeholder="placeholder" variant="filled" fluid showClear filter />
</template>

<script setup>
import Select from 'primevue/select';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    inputId: {
        type: String,
        default: 'religion'
    },
    placeholder: {
        type: String,
        default: 'Select Religion'
    }
});

const emit = defineEmits(['update:modelValue']);

const religionOptions = [
    { label: 'Roman Catholic', value: 'Roman Catholic' },
    { label: 'Islam', value: 'Islam' },
    { label: 'Iglesia ni Cristo', value: 'Iglesia ni Cristo' },
    { label: 'Life Church', value: 'Life Church' },
    { label: 'Pentecostal', value: 'Pentecostal' },
    { label: 'Evangelical', value: 'Evangelical' },
    { label: 'Baptist', value: 'Baptist' },
    { label: 'Jehovah\'s Witnesses', value: 'Jehovah\'s Witnesses' },
    { label: 'Methodist', value: 'Methodist' },
    { label: 'Seventh-day Adventist', value: 'Seventh-day Adventist' },
    { label: 'Foursquare Gospel Church', value: 'Foursquare Gospel Church' },
    { label: 'The Church of Jesus Christ of Latter-day Saints (Mormon)', value: 'The Church of Jesus Christ of Latter-day Saints (Mormon)' },
    { label: 'Bible Baptist Church', value: 'Bible Baptist Church' },
    { label: 'Philippine Independent Church (Aglipayan)', value: 'Philippine Independent Church' },
    { label: 'United Church of Christ in the Philippines', value: 'United Church of Christ in the Philippines' },
    { label: 'Church of Christ', value: 'Church of Christ' },
    { label: 'Born Again Christian', value: 'Born Again Christian' },
    { label: 'Protestant', value: 'Protestant' },
    { label: 'None', value: 'None' },
];

const handleUpdate = (value) => {
    emit('update:modelValue', value);
};
</script>
