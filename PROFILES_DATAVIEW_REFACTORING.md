# Profiles Page Refactoring - PrimeVue DataView Implementation

**Date:** October 15, 2025  
**Status:** ✅ Complete

## Overview

Refactored the Scholarship Profiles page (`resources/js/Pages/Scholarship/Profiles.vue`) to use PrimeVue DataView component with comprehensive filtering functionalities copied from the Waiting List page.

## Changes Made

### 1. **UI Components Replaced**

- ❌ Removed: `DataTable` with `Column` components
- ✅ Added: `DataView` component with List and Grid layouts
- ✅ Added: `SelectButton` for layout toggle
- ✅ Added: `Avatar` component for profile display
- ✅ Added: `Toolbar` for consistent header design
- ✅ Added: `Popover` for actions menu

### 2. **Layout Options**

Implemented two view modes:

- **List Layout**: Horizontal card-style display with full information
- **Grid Layout**: Vertical card-style display in responsive grid (1-4 columns)

### 3. **Filtering System** (Copied from Waiting List)

#### Basic Filters (Always Visible):

- Applicant Name (text input)
- Program (select dropdown)
- Course (select dropdown)
- Approval Status (select dropdown)

#### Advanced Filters (Collapsible):

- School (select dropdown)
- Municipality (select dropdown)
- Year Level (select dropdown)

#### Additional Features:

- Global search across all fields
- Records per page selector (10, 15, 25, 50, 100)
- Show/Hide advanced filters toggle
- Clear all filters button

### 4. **Data Display Features**

#### List View:

- Profile avatar with initials
- Full name display
- Contact information (phone, municipality)
- Academic information (program, course, school)
- Approval status chips with color coding
- Total scholarships count
- Application date
- Action buttons (View, History)

#### Grid View:

- Centered layout with avatar
- Truncated text for better fit
- Same information as list view
- Responsive columns (1-4 based on screen size)

### 5. **Interactive Features**

- **Layout Toggle**: Switch between list and grid views
- **Pagination**: Server-side pagination with configurable rows
- **Keyboard Shortcut**: Ctrl+K for search focus (prepared)
- **Filter Debouncing**: 500ms delay to reduce API calls
- **URL State Persistence**: All filters persist in URL parameters

### 6. **Prepared Buttons** (No Functionality Yet)

As requested, buttons are ready but not functional:

- ✅ "View Full Profile" button (prepared)
- ✅ "View History" button (connected to existing route)
- ✅ "Generate Report" button (prepared)
- ✅ "Export Data" button (prepared)

## Technical Implementation

### Filter Logic

```javascript
const filterList = (resetToPage1 = false) => {
	// Extract filter values
	const program = filter.program?.shortname?.toLowerCase() || '';
	const course = filter.course?.shortname?.toLowerCase() || '';
	// ... etc

	// Build query parameters
	const params = {};
	if (program) params.program = program;
	// ... etc

	// Navigate with filters
	router.get(route('scholarship.profiles'), params, {
		preserveState: true,
		preserveScroll: true,
	});
};
```

### Watchers

- Filter changes trigger `filterList()` with 500ms debounce
- Pagination changes are immediate (no debounce)
- Global search has 500ms debounce
- Records per page change resets to page 1

### Props Expected from Backend

```php
[
    'profiles' => [
        'data' => [],      // Array of profile objects
        'total' => 0,      // Total count
    ],
    'filters' => [
        'name' => '',
        'program' => '',
        'course' => '',
        'school' => '',
        'municipality' => '',
        'year_level' => '',
        'approval_status' => null,
        'global_search' => '',
        'records' => 15,
        'page' => 1,
    ],
    'programs' => [],           // Array of programs
    'approvalStatuses' => [],   // Array of status options
    'declineReasons' => [],     // Decline reasons
    'profiles_total' => 0,      // Total count (separate)
]
```

## Custom Select Components Used

All from `@/Components/selects/`:

- `CourseSelect.vue`
- `MunicipalitySelect.vue`
- `RecordsSelect.vue`
- `ProgramSelect.vue`
- `SchoolSelect.vue`
- `YearLevelSelect.vue`

## Styling Features

- Hover effects on cards
- Smooth transitions
- Custom scrollbar styling
- Responsive breakpoints
- Truncated text for grid view
- Proper spacing and alignment

## Status Chips Color Coding

```javascript
'approved' => 'success'           // Green
'pending' => 'warning'            // Orange
'declined' => 'danger'            // Red
'auto_approved' => 'info'         // Blue
'conditionally_approved' => 'contrast'  // Gray
default => 'secondary'            // Gray
```

## Routes Used

- `scholarship.profiles` - Main profiles page
- `scholarship.profile.history` - Profile history (connected)
- `profile.edit` - Edit profile (prepared)

## Next Steps (Future Implementation)

1. Connect "View Full Profile" button to detail page
2. Implement "Generate Report" functionality
3. Implement "Export Data" functionality
4. Add keyboard shortcut handler for Ctrl+K
5. Add sorting capabilities
6. Add batch operations
7. Add profile quick actions menu

## Benefits

✅ Modern card-based UI  
✅ Flexible view options (list/grid)  
✅ Comprehensive filtering system  
✅ Better mobile responsiveness  
✅ Consistent with Waiting List UX  
✅ Improved data visualization  
✅ Better user experience  
✅ Scalable architecture

## Files Modified

1. `resources/js/Pages/Scholarship/Profiles.vue` - Complete refactoring

## Testing Checklist

- [ ] List view displays correctly
- [ ] Grid view displays correctly
- [ ] Layout toggle works
- [ ] All filters work independently
- [ ] Global search works
- [ ] Pagination works
- [ ] Records per page changes work
- [ ] Filter combinations work
- [ ] Clear filters works
- [ ] URL state persists correctly
- [ ] Responsive on mobile devices
- [ ] View History button works
- [ ] Status chips display correctly
- [ ] Avatar initials generate correctly

## Notes

- The component uses Inertia.js `useForm` for filter state management
- All filter changes preserve scroll position
- Filter debouncing prevents excessive API calls
- Layout preference could be saved to localStorage (future enhancement)
- Component is fully TypeScript-ready (uses proper typing)
