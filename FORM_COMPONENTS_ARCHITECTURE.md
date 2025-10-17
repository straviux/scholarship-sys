# Scholarship Form Components Architecture

## 📋 Overview

The scholarship system now uses a **shared component architecture** for managing both **Applicants** (waiting list) and **Scholars** (approved/active). This ensures code reuse while maintaining clear separation of concerns.

## 🏗️ Component Structure

```
resources/js/Components/
├── forms/ (Base Field Components - Reusable)
│   ├── PersonalInformationFields.vue    # Name, DOB, Gender, Address, etc.
│   ├── FamilyInformationFields.vue      # Father, Mother, Guardian info
│   └── AcademicInformationFields.vue    # Program, Course, School, Year, etc.
│
├── ApplicantFormModal.vue               # For Waiting List (applicants)
└── ScholarFormModal.vue                 # For Active Scholars
```

## 🎯 Component Purposes

### 1. **Base Field Components** (Already existed - kept as-is)

These are pure, reusable field groups that both modals use:

- **PersonalInformationFields.vue**

  - All personal data fields
  - Uses custom selects: GenderSelect, CivilStatusSelect, MunicipalitySelect, BarangaySelect
  - Emits updates via `v-model`

- **FamilyInformationFields.vue**

  - Father, Mother, Guardian information
  - Standard input fields with FloatLabel

- **AcademicInformationFields.vue**
  - Program, School, Course, Year Level, Term, Academic Year
  - Uses custom selects: ProgramSelect, SchoolSelect, CourseSelect, YearLevelSelect
  - Optional remarks field (Textarea)

### 2. **ApplicantFormModal.vue** (Renamed from ApplicationFormModal)

**Purpose:** Create/Edit applicants in the waiting list

**Key Features:**

- ✅ Academic fields are **OPTIONAL** (applicants may not have full info yet)
- ✅ Duplicate name validation on Step 1
- ✅ 3-step stepper (Personal → Family → Academic)
- ✅ Uses routes: `waitinglist.store` / `waitinglist.update`
- ✅ Sets `is_on_waiting_list = true` by default
- ✅ Non-linear stepper (clickable headers)

**Usage:**

```vue
<ApplicantFormModal
	v-model:visible="showModal"
	:mode="'create'"
	:profile="selectedApplicant"
	@success="handleSuccess"
/>
```

**Props:**

- `visible` (Boolean) - Controls modal visibility
- `mode` (String) - 'create' or 'edit'
- `profile` (Object) - Applicant data (for edit mode)

**Emits:**

- `update:visible` - When modal closes
- `success` - When form submission succeeds

### 3. **ScholarFormModal.vue** (NEW)

**Purpose:** Add/Edit active scholars (already approved)

**Key Features:**

- ✅ Academic fields are **REQUIRED** (scholars must have complete info)
- ✅ More strict Step 1 validation (requires municipality + contact number)
- ✅ 3-step stepper with mandatory academic completion
- ✅ Uses routes: `scholars.store` / `scholars.update`
- ✅ Sets `date_filed` to today by default for new scholars
- ✅ Blue info banner on Step 3 indicating required fields
- ✅ Non-linear stepper (clickable headers)

**Usage:**

```vue
<ScholarFormModal
	v-model:visible="showScholarModal"
	:mode="'create'"
	:profile="selectedScholar"
	@success="handleSuccess"
/>
```

**Props:**

- `visible` (Boolean) - Controls modal visibility
- `mode` (String) - 'create' or 'edit'
- `profile` (Object) - Scholar data (for edit mode)

**Emits:**

- `update:visible` - When modal closes
- `success` - When form submission succeeds

## 🔄 Data Flow

### ApplicantFormModal (Waiting List)

```
User fills form → Submit → Transform data → POST/PUT to waitinglist routes
                                          ↓
                                    Backend creates/updates profile
                                          ↓
                                    is_on_waiting_list = true
                                    scholarship_status = 0 (pending)
```

### ScholarFormModal (Active Scholars)

```
User fills form → Validate ALL academic fields → Transform data → POST/PUT to scholars routes
                                                               ↓
                                                         Backend creates/updates profile
                                                               ↓
                                                         scholarship_status = 1/2/3
                                                         (approved/ongoing/graduated)
```

## 🎨 Validation Differences

| Field          | ApplicantFormModal | ScholarFormModal     |
| -------------- | ------------------ | -------------------- |
| First Name     | ✅ Required        | ✅ Required          |
| Last Name      | ✅ Required        | ✅ Required          |
| Municipality   | ⚠️ Recommended     | ✅ Required          |
| Contact Number | ⚠️ Optional        | ✅ Required          |
| Program        | ⚠️ Optional        | ✅ Required          |
| School         | ⚠️ Optional        | ✅ Required          |
| Course         | ⚠️ Optional        | ✅ Required          |
| Year Level     | ⚠️ Optional        | ✅ Required          |
| Term           | ⚠️ Optional        | ✅ Required          |
| Academic Year  | ⚠️ Optional        | ✅ Required          |
| Date Filed     | ⚠️ Optional        | ✅ Auto-set to today |

## 🛠️ Backend Routes Required

### For ApplicantFormModal

```php
// routes/web.php
Route::post('/waitinglist', [ScholarshipProfileController::class, 'store'])->name('waitinglist.store');
Route::put('/waitinglist/{profile}', [ScholarshipProfileController::class, 'update'])->name('waitinglist.update');
```

### For ScholarFormModal (NEW - needs to be created)

```php
// routes/web.php
Route::post('/scholars', [ScholarController::class, 'store'])->name('scholars.store');
Route::put('/scholars/{profile}', [ScholarController::class, 'update'])->name('scholars.update');
```

**Note:** You'll need to create `ScholarController` with `store()` and `update()` methods that:

1. Validate all academic fields as required
2. Set appropriate `scholarship_status` (1 = approved, 2 = ongoing, 3 = graduated)
3. Create both `ScholarshipProfile` and `ScholarshipRecord` entries
4. Do NOT set `is_on_waiting_list = true`

## 📦 Select Components Used

Both modals use the same select components (already working perfectly):

- **PersonalInformationFields:**

  - `GenderSelect`
  - `CivilStatusSelect`
  - `MunicipalitySelect`
  - `BarangaySelect`

- **AcademicInformationFields:**
  - `ProgramSelect`
  - `SchoolSelect`
  - `CourseSelect`
  - `YearLevelSelect`
  - `TermSelect` (if exists)

All select components return objects with proper structure:

```javascript
{
    id: 1,
    name: "Full Name",
    shortname: "Short Name"
}
```

The modals handle transformation to extract the correct values before submission.

## 🚀 Migration Guide

### Old Code (Before)

```vue
<ApplicationFormModal v-model:visible="showModal" @success="handleSuccess" />
```

### New Code (After)

```vue
<!-- For Applicants (Waiting List) -->
<ApplicantFormModal
	v-model:visible="showApplicantModal"
	:mode="'create'"
	@success="handleSuccess"
/>

<!-- For Scholars (Active) -->
<ScholarFormModal v-model:visible="showScholarModal" :mode="'create'" @success="handleSuccess" />
```

## ✅ Benefits of This Architecture

1. **Code Reuse** - All field components are shared
2. **Clear Separation** - Different validation rules for different user types
3. **Maintainable** - Easy to modify applicant or scholar flows independently
4. **Scalable** - Can add more form types (e.g., Alumni) easily
5. **Type Safety** - Clear distinction between applicants and scholars
6. **Consistent UX** - Same look and feel across the app

## 🔧 Example Implementation

### In a Page Component (e.g., Scholars/Index.vue)

```vue
<template>
	<div>
		<Button label="Add Scholar" icon="pi pi-plus" @click="openScholarModal" />

		<ScholarFormModal
			v-model:visible="showScholarModal"
			:mode="scholarMode"
			:profile="selectedScholar"
			@success="refreshScholarsList"
		/>
	</div>
</template>

<script setup>
	import { ref } from 'vue';
	import ScholarFormModal from '@/Components/ScholarFormModal.vue';

	const showScholarModal = ref(false);
	const scholarMode = ref('create');
	const selectedScholar = ref(null);

	const openScholarModal = () => {
		scholarMode.value = 'create';
		selectedScholar.value = null;
		showScholarModal.value = true;
	};

	const refreshScholarsList = () => {
		// Reload scholars data
		router.reload({ only: ['scholars'] });
	};
</script>
```

## 📝 Next Steps

1. ✅ Create `ScholarController` with `store()` and `update()` methods
2. ✅ Add routes for `scholars.store` and `scholars.update`
3. ✅ Update backend validation to require academic fields for scholars
4. ✅ Test both modals thoroughly
5. ✅ Update any other pages that might need ScholarFormModal

## 🐛 Troubleshooting

**Issue:** Select components not working

- **Solution:** Ensure all select components are properly imported in the field components

**Issue:** Routes not found

- **Solution:** Check that `scholars.store` and `scholars.update` routes exist in `routes/web.php`

**Issue:** Data not saving

- **Solution:** Verify backend controller receives correct field names (check network tab)

**Issue:** Stepper headers not clickable

- **Solution:** Ensure `linear` prop is NOT present on `<Stepper>` component

---

**Created:** October 16, 2025  
**Version:** 1.0  
**Author:** Scholarship System Team
