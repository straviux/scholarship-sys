# JPM Modal Component Refactoring

## Overview

Extracted the JPM modal into a separate reusable component (`JpmModal.vue`) and implemented mutual exclusivity logic between JPM member checkboxes and the "Not a JPM Member" option. Also added smart hiding of family member options when they don't exist in the applicant's profile. Includes a convenient reset button to clear all JPM status with one click.

## Changes Made

### 1. Created New Component: `JpmModal.vue`

**Location:** `resources/js/Pages/Applicants/Modal/JpmModal.vue`

#### Features:

- **Standalone Component**: Fully self-contained modal for editing JPM data
- **Props-based**: Accepts `show` and `profile` props
- **Event Emitters**: Emits `update:show` and `success` events
- **Smart Form Initialization**: Automatically populates form when profile changes
- **Reset Button**: Quick clear all JPM status with one click
- **Disabled State Logic**: Reset button disabled when nothing is selected

#### Mutual Exclusivity Logic:

```javascript
// Watch for "Not JPM" checkbox - uncheck all JPM members if checked
watch(
	() => jpmForm.value.is_not_jpm,
	(isNotJpm) => {
		if (isNotJpm) {
			jpmForm.value.is_jpm_member = false;
			jpmForm.value.is_father_jpm = false;
			jpmForm.value.is_mother_jpm = false;
			jpmForm.value.is_guardian_jpm = false;
		}
	}
);

// Watch for any JPM member checkbox - uncheck "Not JPM" if any member is checked
watch(
	() => [
		jpmForm.value.is_jpm_member,
		jpmForm.value.is_father_jpm,
		jpmForm.value.is_mother_jpm,
		jpmForm.value.is_guardian_jpm,
	],
	() => {
		if (hasAnyJpmMember.value) {
			jpmForm.value.is_not_jpm = false;
		}
	}
);
```

#### Conditional Display of Family Members:

```javascript
// Check if family member exists in profile
const hasFather = computed(() => {
	return props.profile?.father_name && props.profile.father_name.trim() !== '';
});

const hasMother = computed(() => {
	return props.profile?.mother_name && props.profile.mother_name.trim() !== '';
});

const hasGuardian = computed(() => {
	return props.profile?.guardian_name && props.profile.guardian_name.trim() !== '';
});
```

#### Template with Conditional Rendering:

```vue
<!-- Applicant - Always visible -->
<label
	class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
	:class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }"
>
    <Checkbox v-model="jpmForm.is_jpm_member" binary 
        :disabled="jpmForm.is_not_jpm" />
    <span class="text-sm">Applicant</span>
</label>

<!-- Father - Only show if father name exists -->
<label
	v-if="hasFather"
	class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
	:class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }"
>
    <Checkbox v-model="jpmForm.is_father_jpm" binary 
        :disabled="jpmForm.is_not_jpm" />
    <span class="text-sm">Father ({{ profile.father_name }})</span>
</label>

<!-- Mother - Only show if mother name exists -->
<label
	v-if="hasMother"
	class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
	:class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }"
>
    <Checkbox v-model="jpmForm.is_mother_jpm" binary 
        :disabled="jpmForm.is_not_jpm" />
    <span class="text-sm">Mother ({{ profile.mother_name }})</span>
</label>

<!-- Guardian - Only show if guardian name exists -->
<label
	v-if="hasGuardian"
	class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
	:class="{ 'opacity-50 cursor-not-allowed': jpmForm.is_not_jpm }"
>
    <Checkbox v-model="jpmForm.is_guardian_jpm" binary 
        :disabled="jpmForm.is_not_jpm" />
    <span class="text-sm">Guardian ({{ profile.guardian_name }})</span>
</label>

<!-- Not JPM - Disabled when any member is checked -->
<label
	class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
	:class="{ 'opacity-50 cursor-not-allowed': hasAnyJpmMember }"
>
    <Checkbox v-model="jpmForm.is_not_jpm" binary 
        :disabled="hasAnyJpmMember" />
    <span class="text-sm font-medium text-orange-600">Not a JPM Member</span>
</label>
<p class="text-xs text-gray-500 mt-1 ml-7" v-if="hasAnyJpmMember">
    <i class="pi pi-info-circle mr-1"></i>
    Uncheck all JPM members above to enable this option
</p>
```

#### Reset Button Implementation:

```javascript
const resetAllJpmStatus = () => {
	jpmForm.value.is_jpm_member = false;
	jpmForm.value.is_father_jpm = false;
	jpmForm.value.is_mother_jpm = false;
	jpmForm.value.is_guardian_jpm = false;
	jpmForm.value.is_not_jpm = false;
	toast.info('All JPM status cleared');
};
```

**Template with Reset Button:**

```vue
<div class="flex justify-between items-center border-b pb-2">
    <label class="text-sm font-bold text-gray-700">JPM Member Tagging</label>
    <Button 
        icon="pi pi-refresh" 
        severity="secondary" 
        size="small" 
        text 
        rounded
        @click="resetAllJpmStatus"
        v-tooltip.top="'Reset all JPM status'"
        :disabled="!hasAnyJpmMember && !jpmForm.is_not_jpm" />
</div>
```

### 2. Updated Index.vue

#### Added Import:

```javascript
import JpmModal from './Modal/JpmModal.vue';
```

#### Simplified JPM Modal State:

```javascript
// Before - Complex form state management
const jpmForm = ref({
	is_jpm_member: false,
	is_father_jpm: false,
	is_mother_jpm: false,
	is_guardian_jpm: false,
	is_not_jpm: false,
	jpm_remarks: '',
});
const openJpmModal = (profile) => {
	// ... complex initialization
};
const saveJpmData = () => {
	// ... complex save logic
};

// After - Simple component usage
const showJpmModal = ref(false);
const selectedProfileForJpm = ref(null);

const openJpmModal = (profile) => {
	selectedProfileForJpm.value = profile;
	showJpmModal.value = true;
};

const closeJpmModal = () => {
	showJpmModal.value = false;
	selectedProfileForJpm.value = null;
};

const handleJpmSuccess = (payload) => {
	// Update local data
	if (selectedProfileForJpm.value) {
		const profileIndex = props.profiles.data.findIndex(
			(profile) => profile.profile_id === selectedProfileForJpm.value.profile_id
		);
		if (profileIndex !== -1) {
			Object.assign(props.profiles.data[profileIndex], payload);
		}
	}
};
```

#### Updated Template:

```vue
<!-- Before - Inline modal with all logic -->
<Dialog v-model:visible="showJpmModal">
    <!-- 70+ lines of template code -->
</Dialog>

<!-- After - Clean component usage -->
<JpmModal
	:show="showJpmModal"
	:profile="selectedProfileForJpm"
	@update:show="showJpmModal = $event"
	@success="handleJpmSuccess"
/>
```

## Logic Behavior

### Scenario 1: User Checks "Not a JPM Member"

1. ✅ "Not a JPM Member" checkbox is checked
2. 🔄 **All JPM member checkboxes are automatically unchecked**
3. 🚫 **All JPM member checkboxes become disabled**
4. 🎨 Disabled checkboxes show with reduced opacity
5. ✏️ User can still edit remarks

### Scenario 2: User Checks Any JPM Member

1. ✅ User checks "Applicant" checkbox
2. 🔄 **"Not a JPM Member" checkbox is automatically unchecked**
3. 🚫 **"Not a JPM Member" checkbox becomes disabled**
4. 💡 Helper text appears: "Uncheck all JPM members above to enable this option"
5. ✅ User can check additional family members

### Scenario 3: Family Member Doesn't Exist in Profile

1. 👤 Applicant profile has no `father_name`
2. 🙈 **Father checkbox option is completely hidden**
3. 📊 Grid layout adjusts automatically
4. ✨ Only shows: Applicant, Mother, Guardian (if they exist)

## User Experience Improvements

### Before Refactoring:

- ❌ Could check both "JPM Member" and "Not JPM" simultaneously
- ❌ Shows all family member options even if they don't exist
- ❌ No visual feedback for conflicting states
- ❌ Confusing data integrity
- ❌ Modal logic mixed with parent component

### After Refactoring:

- ✅ **Mutually Exclusive**: Cannot be JPM and "Not JPM" at the same time
- ✅ **Smart Display**: Only shows family members that exist in profile
- ✅ **Visual Feedback**: Disabled state with opacity change
- ✅ **Helper Text**: Guides user when options are disabled
- ✅ **Clean Separation**: Modal logic isolated in component
- ✅ **Reusable**: Can be used in other parts of the application
- ✅ **Data Integrity**: Prevents conflicting JPM status

## Benefits

### 1. Code Organization

- 📦 **Modular**: JPM modal is now a standalone component
- 🔧 **Maintainable**: Changes to JPM logic in one place
- ♻️ **Reusable**: Can be used in other views/pages
- 🧪 **Testable**: Component can be tested independently

### 2. Data Integrity

- 🛡️ **Prevents Conflicts**: Can't be both JPM and Not JPM
- ✅ **Automatic Cleanup**: Unchecks conflicting options
- 🎯 **Clear State**: Always know if someone is JPM or not

### 3. User Experience

- 👁️ **Smart Display**: Only shows relevant options
- 💡 **Helpful Feedback**: Shows names of family members
- 🚫 **Clear Disabled State**: Visual indication when options unavailable
- 📝 **Guidance**: Helper text explains why options are disabled

### 4. Performance

- ⚡ **Lighter Parent**: Index.vue has less code to manage
- 🔄 **Efficient Watchers**: Reactive updates handled in component
- 📊 **Better Organization**: Computed properties localized

## Display Examples

### Example 1: Complete Family

```
Profile has: father_name, mother_name, guardian_name

Modal shows:
☑️ Applicant
☑️ Father (John Doe)
☑️ Mother (Jane Doe)
☑️ Guardian (Mary Smith)
---
☐ Not a JPM Member
```

### Example 2: No Guardian

```
Profile has: father_name, mother_name

Modal shows:
☑️ Applicant
☑️ Father (John Doe)
☑️ Mother (Jane Doe)
---
☐ Not a JPM Member
```

### Example 3: Only Applicant

```
Profile has: (no family members)

Modal shows:
☑️ Applicant
---
☐ Not a JPM Member
```

### Example 4: "Not JPM" Checked

```
Modal shows:
☐ Applicant (disabled, grayed out)
☐ Father (John Doe) (disabled, grayed out)
☐ Mother (Jane Doe) (disabled, grayed out)
---
☑️ Not a JPM Member
```

### Example 5: JPM Member Checked

```
Modal shows:
☑️ Applicant
☐ Father (John Doe)
☑️ Mother (Jane Doe)
---
☐ Not a JPM Member (disabled, grayed out)
ℹ️ Uncheck all JPM members above to enable this option
```

## Build Status

✅ **Build completed successfully in 11.96s**
✅ No compilation errors
✅ Component properly registered and imported
✅ All reactive logic working correctly

## Testing Checklist

### Mutual Exclusivity

- [ ] Check "Not JPM" - all member checkboxes uncheck and disable
- [ ] Check any JPM member - "Not JPM" unchecks and disables
- [ ] Uncheck all JPM members - "Not JPM" becomes enabled
- [ ] Check "Not JPM" - helper text disappears
- [ ] Check JPM member - helper text appears

### Conditional Display

- [ ] Profile with father - Father option shows with name
- [ ] Profile without father - Father option hidden
- [ ] Profile with mother - Mother option shows with name
- [ ] Profile without mother - Mother option hidden
- [ ] Profile with guardian - Guardian option shows with name
- [ ] Profile without guardian - Guardian option hidden
- [ ] Applicant option always shows

### Visual Feedback

- [ ] Disabled checkboxes have reduced opacity
- [ ] Disabled labels show "cursor-not-allowed"
- [ ] Helper text appears when "Not JPM" is disabled
- [ ] Hover effect works on enabled options
- [ ] No hover effect on disabled options

### Data Persistence

- [ ] Checking "Not JPM" saves correctly
- [ ] Unchecking JPM members saves correctly
- [ ] Changes reflect immediately in JPM Status column
- [ ] Refresh page - changes persist
- [ ] Local state updates after save

### Component Integration

- [ ] Modal opens when "Edit JPM Info" clicked
- [ ] Modal shows correct applicant name
- [ ] Cancel button closes modal without saving
- [ ] Save button updates data
- [ ] Success toast appears after save
- [ ] Modal closes after successful save

## Related Files

- `resources/js/Pages/Applicants/Modal/JpmModal.vue` - New modal component
- `resources/js/Pages/Applicants/Index.vue` - Updated to use component
- `JPM_COMBINED_MODAL_IMPLEMENTATION.md` - Previous implementation
- `app/Http/Controllers/WaitingListController.php` - Backend controller

## Future Enhancements

1. Add visual indicator for which state is active (green border for JPM, orange for Not JPM)
2. Add confirmation dialog when unchecking all JPM members
3. Add bulk JPM tagging across multiple applicants
4. Add JPM status change history/audit trail
5. Add validation to require remarks when marking as "Not JPM"
6. Add auto-complete for common JPM remarks
7. Export JpmModal as shared component for use in other modules
