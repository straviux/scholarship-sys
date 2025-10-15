# Personal Information Fields Expansion

## Overview

Added 7 new personal information fields to enhance the scholarship application system's data collection capabilities. These fields are now available in both quick applicant entry and full scholar entry modals.

## Date Completed

January 2025

## New Fields Added

### 1. Secondary Contact Number

- **Field Name**: `secondary_contact_no`
- **Component**: `InputText`
- **Location**: Contact Fields Row (alongside primary contact and email)
- **Purpose**: Provide alternative contact information

### 2. Date of Birth

- **Field Name**: `date_of_birth`
- **Component**: `InputText` with `type="date"`
- **Location**: Personal Details Row 1
- **Purpose**: Capture applicant's birth date for eligibility verification

### 3. Gender

- **Field Name**: `gender`
- **Component**: `Select` (PrimeVue dropdown)
- **Options**:
  - Male
  - Female
- **Location**: Personal Details Row 1
- **Purpose**: Demographic information

### 4. Place of Birth

- **Field Name**: `place_of_birth`
- **Component**: `MunicipalitySelect` (custom component)
- **Location**: Personal Details Row 1
- **Purpose**: Capture municipality of birth using existing municipality selector

### 5. Civil Status

- **Field Name**: `civil_status`
- **Component**: `Select` (PrimeVue dropdown)
- **Options**:
  - Single
  - Married
  - Widowed
  - Separated
  - Divorced
- **Location**: Personal Details Row 2
- **Purpose**: Marital status information

### 6. Religion

- **Field Name**: `religion`
- **Component**: `InputText`
- **Location**: Personal Details Row 2
- **Purpose**: Religious affiliation (free text)

### 7. Indigenous Group

- **Field Name**: `indigenous_group`
- **Component**: `InputText`
- **Location**: Personal Details Row 2
- **Purpose**: Capture indigenous community membership if applicable

## Files Modified

### 1. PersonalInformationFields.vue

**Purpose**: Reusable component containing all personal information form fields

**Changes Made**:

- Added 7 new field inputs in template
- Expanded contact row from 2 to 3 fields
- Added two new rows for personal details (3 fields each)
- Imported `Select` component from PrimeVue
- Imported `MunicipalitySelect` component
- Added `genderOptions` array with Male/Female values
- Added `civilStatusOptions` array with 5 marital status options
- Updated `modelValue` prop default to include all 13 fields

**Layout Structure**:

```
Row 1 (Name Fields - 12-column grid):
  - Last Name (3 cols)
  - First Name (3 cols)
  - Middle Name (4 cols)
  - Extension (2 cols)

Row 2 (Contact Fields - 2-column grid, 3 fields):
  - Contact Number
  - Secondary Contact Number ✨ NEW
  - Email

Row 3 (Personal Details 1 - 3-column grid):
  - Date of Birth ✨ NEW
  - Gender ✨ NEW
  - Place of Birth ✨ NEW

Row 4 (Personal Details 2 - 3-column grid):
  - Civil Status ✨ NEW
  - Religion ✨ NEW
  - Indigenous Group ✨ NEW
```

### 2. AddApplicantModal.vue

**Purpose**: Quick applicant entry modal with minimal validation

**Changes Made**:

- Expanded form object from 6 to 13 fields
- Updated `personalInfo` computed getter to include all 13 fields
- Updated `personalInfo` computed setter to map all 13 fields

**Form Fields** (13 total):

```javascript
const form = useForm({
	first_name: '',
	middle_name: '',
	last_name: '',
	extension_name: '',
	contact_no: '',
	secondary_contact_no: '', // ✨ NEW
	email: '',
	date_of_birth: '', // ✨ NEW
	gender: '', // ✨ NEW
	place_of_birth: '', // ✨ NEW
	civil_status: '', // ✨ NEW
	religion: '', // ✨ NEW
	indigenous_group: '', // ✨ NEW
});
```

### 3. AddExistingModal.vue

**Purpose**: Complete scholar entry modal with required academic information

**Changes Made**:

- Expanded form object from 6 to 13 personal info fields (plus 5 academic fields)
- Updated `personalInfo` computed getter to include all 13 fields
- Updated `personalInfo` computed setter to map all 13 fields

**Form Fields** (18 total):

```javascript
const form = useForm({
	// Personal Information (13 fields)
	first_name: '',
	middle_name: '',
	last_name: '',
	extension_name: '',
	contact_no: '',
	secondary_contact_no: '', // ✨ NEW
	email: '',
	date_of_birth: '', // ✨ NEW
	gender: '', // ✨ NEW
	place_of_birth: '', // ✨ NEW
	civil_status: '', // ✨ NEW
	religion: '', // ✨ NEW
	indigenous_group: '', // ✨ NEW

	// Academic Information (5 fields)
	program: null,
	school: null,
	course: null,
	year_level: null,
	municipality: null,
});
```

## Component Architecture

### Two-Way Data Binding

All three components use Vue's v-model pattern for seamless data synchronization:

```vue
<!-- Parent Components -->
<PersonalInformationFields v-model="personalInfo" />

<!-- PersonalInformationFields emits updates via -->
@update:modelValue="updateField('field_name', $event)"
```

### Computed Property Pattern

Both modal components use computed properties to map between Inertia form and component props:

```javascript
const personalInfo = computed({
	get: () => ({
		/* map form fields to object */
	}),
	set: (value) => {
		/* map object back to form fields */
	},
});
```

## Technical Details

### New Component Imports

```javascript
import Select from 'primevue/select';
import MunicipalitySelect from '@/Components/MunicipalitySelect.vue';
```

### Dropdown Options

```javascript
const genderOptions = [
	{ label: 'Male', value: 'Male' },
	{ label: 'Female', value: 'Female' },
];

const civilStatusOptions = [
	{ label: 'Single', value: 'Single' },
	{ label: 'Married', value: 'Married' },
	{ label: 'Widowed', value: 'Widowed' },
	{ label: 'Separated', value: 'Separated' },
	{ label: 'Divorced', value: 'Divorced' },
];
```

## UI/UX Improvements

### Responsive Layout

- All new fields use Tailwind's responsive grid system
- Contact row expanded to accommodate third field
- Personal details organized in logical groupings (3 fields per row)

### Input Types

- Date of Birth: Native HTML5 date picker for better UX
- Gender & Civil Status: Dropdown selectors for data consistency
- Place of Birth: Reused existing MunicipalitySelect component
- Religion & Indigenous Group: Free text inputs for flexibility

### Field Organization

```
Contact Information (Row 2):
  └─ Primary Contact, Secondary Contact, Email

Personal Demographics (Row 3):
  └─ Date of Birth, Gender, Place of Birth

Personal Details (Row 4):
  └─ Civil Status, Religion, Indigenous Group
```

## Build Status

✅ **Build Successful** - No compilation errors

- Build completed in 19.30s
- All components compiled successfully
- No TypeScript/Vue errors

## Next Steps Required

### Backend Implementation

1. **Database Migration**

   - Add 7 new columns to `profiles` table:
     ```php
     $table->string('secondary_contact_no')->nullable();
     $table->date('date_of_birth')->nullable();
     $table->string('gender')->nullable();
     $table->string('place_of_birth')->nullable();
     $table->string('civil_status')->nullable();
     $table->string('religion')->nullable();
     $table->string('indigenous_group')->nullable();
     ```

2. **Model Updates**

   - Add new fields to `$fillable` array in Profile model

3. **Controller Validation**
   - Update `ScholarshipProfileController::storeApplicant()`
   - Update `ScholarshipProfileController::storeExisting()`
   - Add validation rules for new fields

### Testing Checklist

- [ ] Test AddApplicantModal with all new fields
- [ ] Test AddExistingModal with all new fields
- [ ] Verify date picker functionality
- [ ] Test gender dropdown selection
- [ ] Test civil status dropdown selection
- [ ] Verify MunicipalitySelect for place of birth
- [ ] Test form submission with new data
- [ ] Verify database storage
- [ ] Test form validation (if applicable)

## Benefits

### For Users

- More comprehensive applicant profiles
- Better demographic data collection
- Improved applicant eligibility verification
- Alternative contact options

### For Administrators

- Enhanced reporting capabilities
- Better demographic analysis
- Improved applicant screening
- More complete applicant records

### For Developers

- Reusable component architecture
- Single source of truth for field definitions
- Easy to add/modify fields in future
- Consistent data handling across modals

## Related Documentation

- See `PERSONAL_INFO_COMPONENT_REFACTORING.md` for component creation details
- See `PERSONAL_INFO_LAYOUT_UPDATE.md` for layout optimization history
- See `MODAL_COMPONENTS_REFACTORING.md` for modal extraction details

## Summary

Successfully expanded the personal information collection system from 6 to 13 fields by adding secondary contact, date of birth, gender, place of birth, civil status, religion, and indigenous group. All changes implemented in the shared PersonalInformationFields component and propagated to both AddApplicantModal and AddExistingModal components. Build verification completed successfully with no errors.
