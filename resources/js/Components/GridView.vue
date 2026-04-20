<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
        default: () => []
    },
    columns: {
        type: Object,
        default: () => ({
            xs: 1,    // Mobile
            sm: 1,    // Small tablets
            md: 2,    // Tablets
            lg: 3,    // Desktop
            xl: 4     // Large desktop
        })
    },
    emptyMessage: {
        type: String,
        default: 'No items to display'
    },
    emptyIcon: {
        type: String,
        default: 'inbox'
    },
    // Pagination props
    totalRecords: {
        type: Number,
        default: 0
    },
    rows: {
        type: Number,
        default: 10
    },
    first: {
        type: Number,
        default: 0
    },
    showPagination: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['page-change']);

// Compute grid classes based on columns prop
const gridClasses = computed(() => {
    const { xs, sm, md, lg, xl } = props.columns;
    return [
        'grid gap-4',
        `grid-cols-${xs}`,
        sm && `sm:grid-cols-${sm}`,
        md && `md:grid-cols-${md}`,
        lg && `lg:grid-cols-${lg}`,
        xl && `xl:grid-cols-${xl}`
    ].filter(Boolean).join(' ');
});

// Pagination handlers
const handlePageChange = (page, rows) => {
    emit('page-change', { page, rows });
};

const currentPage = computed(() => Math.floor(props.first / props.rows) + 1);
const totalPages = computed(() => Math.ceil(props.totalRecords / props.rows));
const isFirstPage = computed(() => props.first === 0);
const isLastPage = computed(() => props.first + props.rows >= props.totalRecords);
</script>

<template>
    <div class="grid-view-container">
        <!-- Empty State -->
        <div v-if="!items || items.length === 0" class="text-center py-12 text-gray-500">
            <AppIcon :name="emptyIcon" :size="48" />
            <p class="mt-4">{{ emptyMessage }}</p>
        </div>

        <!-- Grid Items -->
        <div v-else :class="gridClasses">
            <!-- Default slot for complete custom components -->
            <template v-if="$slots.default">
                <slot :item="item" :index="index" v-for="(item, index) in items"
                    :key="item.id || item.profile_id || index"></slot>
            </template>

            <!-- Card-based slots for granular control -->
            <Card v-else v-for="(item, index) in items" :key="item.id || item.profile_id || index"
                class="hover:shadow-lg transition-shadow duration-200">
                <!-- Header Slot -->
                <template #header v-if="$slots.header">
                    <slot name="header" :item="item" :index="index"></slot>
                </template>

                <!-- Title Slot -->
                <template #title v-if="$slots.title">
                    <slot name="title" :item="item" :index="index"></slot>
                </template>

                <!-- Content Slot -->
                <template #content v-if="$slots.content">
                    <slot name="content" :item="item" :index="index"></slot>
                </template>

                <!-- Footer Slot -->
                <template #footer v-if="$slots.footer">
                    <slot name="footer" :item="item" :index="index"></slot>
                </template>
            </Card>
        </div>

        <!-- Grid Pagination -->
        <div v-if="showPagination && totalRecords > rows && items.length > 0" class="flex justify-center mt-6">
            <div class="flex items-center gap-2">
                <!-- First Page -->
                <AppButton icon="angle-double-left" @click="handlePageChange(0, rows)" :disabled="isFirstPage"
                    size="small" outlined v-tooltip.top="'First Page'" />

                <!-- Previous Page -->
                <AppButton icon="angle-left" @click="handlePageChange(Math.floor(first / rows) - 1, rows)"
                    :disabled="isFirstPage" size="small" outlined v-tooltip.top="'Previous Page'" />

                <!-- Page Info -->
                <div class="flex items-center gap-2 px-4">
                    <span class="text-sm text-gray-600">
                        Showing {{ first + 1 }} to {{ Math.min(first + rows, totalRecords) }} of {{ totalRecords }}
                    </span>
                    <span class="text-xs text-gray-400">
                        (Page {{ currentPage }} of {{ totalPages }})
                    </span>
                </div>

                <!-- Next Page -->
                <AppButton icon="angle-right" @click="handlePageChange(Math.floor(first / rows) + 1, rows)"
                    :disabled="isLastPage" size="small" outlined v-tooltip.top="'Next Page'" />

                <!-- Last Page -->
                <AppButton icon="angle-double-right" @click="handlePageChange(Math.floor(totalRecords / rows), rows)"
                    :disabled="isLastPage" size="small" outlined v-tooltip.top="'Last Page'" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.grid-view-container {
    width: 100%;
}
</style>
