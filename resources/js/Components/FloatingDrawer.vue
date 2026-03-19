<template>
    <Drawer v-bind="$attrs" :pt="drawerPt">
        <slot />
        <template v-for="(_, name) in $slots" :key="name" #[name]>
            <slot :name="name" />
        </template>
    </Drawer>
</template>

<script setup>
const drawerPt = {
    root: { class: 'floating-drawer' },
    mask: { class: 'floating-drawer-mask' }
};
</script>

<style>
.floating-drawer.p-drawer {
    border-radius: 2rem !important;
    height: calc(100vh - 2rem) !important;
    margin: 1rem !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
}

.floating-drawer .p-drawer-header {
    border-radius: 2rem 2rem 0 0;
}

.floating-drawer .p-drawer-content {
    border-radius: 0 0 2rem 2rem;
}

.floating-drawer-mask {
    backdrop-filter: blur(4px);
}
</style>
