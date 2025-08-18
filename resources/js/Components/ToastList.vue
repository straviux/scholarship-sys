<template>
    <div class="fixed top-4 right-4 z-50 space-y-4 max-w-xs ">
        <ToastListItem v-for="(msg, msgIndex) in items" :key="msgIndex" :message="msg.message"
            @remove="remove(msgIndex)" />

    </div>
</template>

<script setup>
import { ref } from "vue";
import ToastListItem from "@/Components/ToastListItem.vue"

const items = ref([{ message: 'Set yourself free' }, { message: 'You can do it' }, { message: 'Fly for fun' }])
const remove = (index) => {
    console.log(index)
    items.value.splice(index, 1)
}
</script>

<style lang="scss" scoped></style>