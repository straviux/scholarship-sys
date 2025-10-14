# JPM Modal - Quick Reference

## Component Location

`resources/js/Pages/Applicants/Modal/JpmModal.vue`

## Features

- ✅ Mutual exclusivity between JPM members and "Not JPM"
- ✅ Smart display: Only shows family members that exist
- ✅ **Reset button**: Clear all JPM status with one click
- ✅ Visual feedback for disabled states
- ✅ Helper text for guidance

## Usage

```vue
<script setup>
	import JpmModal from './Modal/JpmModal.vue';

	const showJpmModal = ref(false);
	const selectedProfile = ref(null);

	const openModal = (profile) => {
		selectedProfile.value = profile;
		showJpmModal.value = true;
	};

	const handleSuccess = (payload) => {
		// Handle successful save
		console.log('JPM data updated:', payload);
	};
</script>

<template>
	<JpmModal
		:show="showJpmModal"
		:profile="selectedProfile"
		@update:show="showJpmModal = $event"
		@success="handleSuccess"
	/>
</template>
```

## Props

| Prop      | Type    | Required | Description                                  |
| --------- | ------- | -------- | -------------------------------------------- |
| `show`    | Boolean | Yes      | Controls modal visibility                    |
| `profile` | Object  | Yes      | Applicant profile data containing JPM fields |

## Events

| Event         | Payload | Description                                                         |
| ------------- | ------- | ------------------------------------------------------------------- |
| `update:show` | Boolean | Emitted when modal visibility changes                               |
| `success`     | Object  | Emitted when JPM data is saved successfully, contains saved payload |

## Profile Object Requirements

```javascript
{
    profile_id: Number,
    first_name: String,
    last_name: String,
    contact_no: String,
    father_name: String,      // Optional - checkbox hidden if empty
    mother_name: String,      // Optional - checkbox hidden if empty
    guardian_name: String,    // Optional - checkbox hidden if empty
    is_jpm_member: Boolean,
    is_father_jpm: Boolean,
    is_mother_jpm: Boolean,
    is_guardian_jpm: Boolean,
    is_not_jpm: Boolean,
    jpm_remarks: String
}
```

## Mutual Exclusivity Rules

### Rule 1: "Not a JPM Member" Checked

- ✅ "Not a JPM Member" is checked
- ❌ All family member checkboxes are **unchecked** automatically
- 🚫 All family member checkboxes are **disabled**

### Rule 2: Any JPM Member Checked

- ✅ Any family member checkbox is checked
- ❌ "Not a JPM Member" is **unchecked** automatically
- 🚫 "Not a JPM Member" is **disabled**

### Rule 3: No Checkboxes Selected

- ✅ All checkboxes are **enabled**
- ✏️ User can select any option

### Rule 4: Reset Button

- 🔄 Clears **all JPM checkboxes** (members and "Not JPM")
- 🚫 Disabled when no checkboxes are selected
- 💡 Provides quick way to start fresh
- 📢 Shows toast notification: "All JPM status cleared"

## Smart Display Rules

### Family Member Visibility

| Field     | Condition                            | Display            |
| --------- | ------------------------------------ | ------------------ |
| Applicant | Always                               | ✅ Always shown    |
| Father    | `father_name` exists and not empty   | ✅ Shown with name |
| Mother    | `mother_name` exists and not empty   | ✅ Shown with name |
| Guardian  | `guardian_name` exists and not empty | ✅ Shown with name |

## Example Scenarios

### Scenario A: User Checks "Not JPM"

```javascript
// Before
{ is_jpm_member: true, is_father_jpm: true, is_not_jpm: false }

// User checks "Not JPM"

// After (automatic)
{ is_jpm_member: false, is_father_jpm: false, is_not_jpm: true }
```

### Scenario B: User Checks "Applicant"

```javascript
// Before
{ is_jpm_member: false, is_not_jpm: true }

// User checks "Applicant"

// After (automatic)
{ is_jpm_member: true, is_not_jpm: false }
```

### Scenario C: Profile with Only Mother

```javascript
// Profile data
{
    father_name: null,
    mother_name: "Jane Doe",
    guardian_name: ""
}

// Modal displays:
☑️ Applicant
☑️ Mother (Jane Doe)
---
☐ Not a JPM Member
```

### Scenario D: User Clicks Reset Button

```javascript
// Before
{ is_jpm_member: true, is_father_jpm: true, is_not_jpm: false }

// User clicks reset button (🔄 icon)

// After (automatic)
{ is_jpm_member: false, is_father_jpm: false, is_mother_jpm: false, is_guardian_jpm: false, is_not_jpm: false }

// Toast notification: "All JPM status cleared"
```

## Visual States

### Normal State

```
☐ Applicant
☐ Father (John Doe)
☐ Mother (Jane Doe)
```

### Disabled State (when "Not JPM" is checked)

```
⬜ Applicant (grayed out, cursor: not-allowed)
⬜ Father (John Doe) (grayed out, cursor: not-allowed)
⬜ Mother (Jane Doe) (grayed out, cursor: not-allowed)
```

### Helper Text Display

```
☑️ Applicant
---
⬜ Not a JPM Member (grayed out)
ℹ️ Uncheck all JPM members above to enable this option
```

## API Endpoint

**Route:** `waitinglist.updateJpmStatus`
**Method:** `PUT`
**URL:** `/waiting-list/{id}/jpm-status`

**Payload:**

```javascript
{
    is_jpm_member: Boolean,
    is_father_jpm: Boolean,
    is_mother_jpm: Boolean,
    is_guardian_jpm: Boolean,
    is_not_jpm: Boolean,
    jpm_remarks: String
}
```

**Response:**

- Success: Redirects back with success message
- Error: Redirects back with error message

## Styling Classes

### Enabled State

```css
.flex.items-center.gap-2.cursor-pointer.hover: bg-gray-50.p-2.rounded;
```

### Disabled State

```css
.flex.items-center.gap-2.cursor-pointer.hover: bg-gray-50.p-2.rounded .opacity-50.cursor-not-allowed;
```

## Common Issues & Solutions

### Issue 1: Modal doesn't open

**Solution:** Ensure `show` prop is set to `true` and `profile` prop is not null

### Issue 2: Checkboxes don't disable

**Solution:** Check that watchers are properly set up in the component

### Issue 3: Family members always show

**Solution:** Verify profile data has correct field names (`father_name`, `mother_name`, `guardian_name`)

### Issue 4: Changes don't persist

**Solution:** Ensure `@success` event handler updates the parent component's data

## Customization

### Change Modal Width

```vue
<Dialog :style="{ width: '700px' }">
```

### Customize Helper Text

```vue
<p class="text-xs text-gray-500 mt-1 ml-7" v-if="hasAnyJpmMember">
    Your custom message here
</p>
```

### Add Additional Fields

```javascript
const jpmForm = ref({
	// ... existing fields
	your_custom_field: false,
});
```

## Accessibility

- ✅ Proper label associations
- ✅ Disabled state clearly indicated
- ✅ Helper text provides context
- ✅ Keyboard navigable
- ✅ Screen reader friendly

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers

## Performance Notes

- 🚀 Lightweight component (~6KB)
- ⚡ Reactive watchers for instant feedback
- 💾 Optimistic UI updates
- 🔄 Automatic cleanup on modal close
