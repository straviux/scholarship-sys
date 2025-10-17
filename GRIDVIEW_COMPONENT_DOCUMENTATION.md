# GridView Component Documentation

## Overview

The `GridView` component is a reusable base component for displaying items in a responsive grid layout with built-in pagination. It's designed to work with any type of data and provides extensive customization through slots.

## Location

- **Base Component**: `resources/js/Components/GridView.vue`
- **Example Card Component**: `resources/js/Components/ApplicantGridCard.vue`
- **Usage Example**: `resources/js/Pages/Applicants/Index.vue`

---

## GridView Component

### Props

| Prop             | Type    | Default                          | Description                              |
| ---------------- | ------- | -------------------------------- | ---------------------------------------- |
| `items`          | Array   | `[]`                             | Array of items to display in the grid    |
| `columns`        | Object  | `{ xs: 1, md: 2, lg: 3, xl: 4 }` | Responsive column configuration          |
| `emptyMessage`   | String  | `'No items to display'`          | Message shown when no items available    |
| `emptyIcon`      | String  | `'pi pi-inbox'`                  | PrimeIcon class for empty state          |
| `totalRecords`   | Number  | `0`                              | Total number of records (for pagination) |
| `rows`           | Number  | `10`                             | Number of rows per page                  |
| `first`          | Number  | `0`                              | Index of first record                    |
| `showPagination` | Boolean | `true`                           | Whether to show pagination controls      |

### Events

| Event         | Payload                          | Description                    |
| ------------- | -------------------------------- | ------------------------------ |
| `page-change` | `{ page: Number, rows: Number }` | Emitted when user changes page |

### Slots

The GridView component uses **scoped slots** to allow complete customization of card content:

#### Available Slots

- `header` - Card header (e.g., avatar, images)
- `title` - Card title (e.g., name, heading)
- `content` - Card body/content
- `footer` - Card footer (e.g., action buttons)

Each slot receives:

- `item` - The current item being rendered
- `index` - The index of the current item

---

## Basic Usage

### Simple Example

```vue
<template>
	<GridView
		:items="users"
		:total-records="totalUsers"
		:rows="20"
		:first="currentPage"
		@page-change="handlePageChange"
	>
		<template #header="{ item: user }">
			<img :src="user.avatar" class="w-full h-48 object-cover" />
		</template>

		<template #title="{ item: user }">
			<h3 class="font-bold text-lg">{{ user.name }}</h3>
		</template>

		<template #content="{ item: user }">
			<p>{{ user.email }}</p>
			<p>{{ user.role }}</p>
		</template>

		<template #footer="{ item: user }">
			<Button label="View Profile" @click="viewUser(user)" />
		</template>
	</GridView>
</template>

<script setup>
	import GridView from '@/Components/GridView.vue';

	const users = ref([]);
	const totalUsers = ref(0);
	const currentPage = ref(0);

	const handlePageChange = ({ page, rows }) => {
		// Fetch new data
		currentPage.value = page * rows;
	};
</script>
```

### Custom Column Configuration

```vue
<GridView
	:items="products"
	:columns="{
		xs: 1, // 1 column on mobile
		sm: 2, // 2 columns on small tablets
		md: 3, // 3 columns on tablets
		lg: 4, // 4 columns on desktop
		xl: 6, // 6 columns on large screens
	}"
>
    <!-- slots -->
</GridView>
```

### Without Pagination

```vue
<GridView :items="items" :show-pagination="false">
    <!-- slots -->
</GridView>
```

### Custom Empty State

```vue
<GridView :items="items" empty-message="No products available" empty-icon="pi pi-shopping-cart">
    <!-- slots -->
</GridView>
```

---

## Advanced Example: Applicants Grid

Here's how the Applicants page uses the GridView component:

```vue
<template>
	<GridView
		:items="applicants"
		:total-records="totalRecords"
		:rows="rows"
		:first="first"
		:columns="{ xs: 1, md: 2, lg: 3, xl: 4 }"
		empty-message="No applicants to display"
		empty-icon="pi pi-users"
		@page-change="onPageChange"
	>
		<!-- Header with Avatar and Badges -->
		<template #header="{ item: applicant }">
			<div class="relative">
				<div class="flex justify-center pt-6 pb-4 bg-gradient-to-br from-indigo-50 to-blue-50">
					<img
						v-if="applicant.gender == 'M'"
						src="/images/male-avatar.png"
						class="rounded-full w-24 h-24 border-4 border-white shadow-md"
					/>
					<img
						v-else-if="applicant.gender == 'F'"
						src="/images/female-avatar.png"
						class="rounded-full w-24 h-24 border-4 border-white shadow-md"
					/>
					<Avatar
						v-else
						:label="getApplicantInitials(applicant)"
						class="w-24 h-24 text-2xl"
						shape="circle"
					/>
				</div>

				<!-- Badges -->
				<Tag
					v-if="showJpmColumns && getJpmStatus(applicant)"
					severity="success"
					class="absolute top-2 right-2"
				>
					<i class="pi pi-star-fill mr-1"></i> JPM
				</Tag>

				<Tag
					v-if="applicant.priority_level && applicant.priority_level !== 'normal'"
					:severity="getPrioritySeverity(applicant.priority_level)"
					class="absolute top-2 left-2"
				>
					<i class="pi pi-flag-fill mr-1"></i>
					{{ formatPriorityName(applicant.priority_level) }}
				</Tag>
			</div>
		</template>

		<!-- Title with Name -->
		<template #title="{ item: applicant }">
			<div class="text-center">
				<div class="font-bold text-lg text-gray-800">
					{{ applicant.last_name }}, {{ applicant.first_name }}
				</div>
				<div class="text-sm text-gray-500">
					{{ applicant.middle_name }} {{ applicant.extension_name }}
				</div>
			</div>
		</template>

		<!-- Content with Details -->
		<template #content="{ item: applicant }">
			<div class="space-y-3 text-sm">
				<!-- Queue Numbers -->
				<div class="flex justify-center gap-2 flex-wrap">
					<div class="text-center px-2 py-1 bg-indigo-100 rounded-md">
						<div class="text-xs font-bold">#{{ applicant.sequence_number || '-' }}</div>
						<div class="text-xs">{{ applicant.scholarship_grant?.[0]?.program?.shortname }}</div>
					</div>
					<!-- More badges... -->
				</div>

				<Divider />

				<!-- Information -->
				<div class="space-y-2">
					<div class="flex items-start gap-2">
						<i class="pi pi-phone text-gray-400 mt-1"></i>
						<span>{{ applicant.contact_no || 'N/A' }}</span>
					</div>
					<!-- More info fields... -->
				</div>
			</div>
		</template>

		<!-- Footer with Actions -->
		<template #footer="{ item: applicant }">
			<div class="flex gap-2 justify-center flex-wrap">
				<Button
					icon="pi pi-check-circle"
					label="Review"
					severity="success"
					size="small"
					@click="openProfileReviewModal(applicant)"
				/>
				<Button
					icon="pi pi-user-edit"
					severity="help"
					size="small"
					@click="editApplicant(applicant)"
				/>
				<Button
					icon="pi pi-trash"
					severity="danger"
					size="small"
					@click="confirmDeleteApplicant(applicant)"
				/>
			</div>
		</template>
	</GridView>
</template>

<script setup>
	import GridView from '@/Components/GridView.vue';
	// ... other imports

	const onPageChange = ({ page, rows }) => {
		// Handle pagination
	};
</script>
```

---

## ApplicantGridCard Component (Optional)

For even more reusability, you can create specialized card components. The `ApplicantGridCard.vue` is an example:

### Usage

```vue
<GridView :items="applicants">
    <template #default="{ item: applicant }">
        <ApplicantGridCard
            :applicant="applicant"
            :show-jpm-badge="showJpmColumns && getJpmStatus(applicant)"
            :jpm-details="getJpmMemberDetails(applicant)"
            :actions="[
                { 
                    name: 'review', 
                    icon: 'pi pi-check-circle',
                    label: 'Review',
                    severity: 'success',
                    condition: (app) => hasPermission('create-scholar-profile')
                },
                { 
                    name: 'edit', 
                    icon: 'pi pi-user-edit',
                    severity: 'help',
                    tooltip: 'Edit'
                },
                { 
                    name: 'delete', 
                    icon: 'pi pi-trash',
                    severity: 'danger',
                    tooltip: 'Delete',
                    condition: (app) => hasPermission('delete-scholar-profile')
                }
            ]"
            @action="handleCardAction"
        />
    </template>
</GridView>
```

---

## Styling

The GridView component uses Tailwind CSS utility classes. Cards automatically get:

- Hover effects (`hover:shadow-lg`)
- Smooth transitions (`transition-shadow duration-200`)
- Responsive grid layout

### Customizing Card Appearance

You can customize individual cards by adding classes in your slot content:

```vue
<template #header="{ item }">
	<div class="bg-blue-500 p-4">
		<!-- Custom header style -->
	</div>
</template>
```

---

## Pagination Features

The built-in pagination includes:

- **First page** button
- **Previous page** button
- **Current page info** (e.g., "Showing 1 to 20 of 156")
- **Page count** (e.g., "Page 1 of 8")
- **Next page** button
- **Last page** button
- **Tooltips** on all buttons
- **Disabled states** for first/last pages

---

## Best Practices

1. **Use Scoped Slots**: Always use scoped slots to access item data
2. **Key Prop**: GridView automatically uses `item.id` or index as key
3. **Responsive Design**: Customize column configuration based on your content
4. **Empty States**: Always provide meaningful empty messages
5. **Loading States**: Consider adding loading states before data loads
6. **Performance**: For large datasets, implement server-side pagination

---

## Complete Feature List

✅ Responsive grid layout (mobile to desktop)  
✅ Customizable columns per breakpoint  
✅ Built-in pagination with navigation  
✅ Empty state with custom message/icon  
✅ Scoped slots for complete customization  
✅ Hover effects and transitions  
✅ PrimeVue Card integration  
✅ Tooltip support  
✅ Page change events  
✅ Flexible styling with Tailwind CSS

---

## Migration Guide

### Before (Manual Grid):

```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <Card v-for="item in items" :key="item.id">
        <!-- Card content -->
    </Card>
</div>
<!-- Manual pagination -->
```

### After (GridView Component):

```vue
<GridView :items="items" @page-change="handlePageChange">
    <template #content="{ item }">
        <!-- Card content -->
    </template>
</GridView>
```

---

## Support

For issues or questions about the GridView component:

1. Check this documentation
2. Review the example in `Applicants/Index.vue`
3. Inspect the component source at `Components/GridView.vue`

---

**Created**: October 2025  
**Version**: 1.0.0  
**Framework**: Vue 3 + PrimeVue + Tailwind CSS
