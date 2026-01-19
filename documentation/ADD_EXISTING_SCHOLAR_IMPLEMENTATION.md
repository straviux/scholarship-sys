# Add Existing Scholar Implementation

## Overview

This document describes the implementation of the "Add Existing Scholar" functionality, which allows users to add scholars (students who are already active in scholarship programs) through the Scholarship Index page using the **ScholarFormModal** component.

## Features Implemented

### 1. **Add Existing Scholar Button**

- Located in the Scholarship Index page (`Pages/Scholarship/Index.vue`)
- Accessible via the "Add Record" dropdown button in the toolbar
- Opens the `ScholarFormModal` in create mode when clicked

### 2. **ScholarFormModal Component**

**Location:** `resources/js/Components/modals/ScholarFormModal.vue`

**Key Features:**

- **Three-step wizard** for data collection:

  1. Personal Information
  2. Family Information
  3. Academic Information (Required - all fields must be filled)

- **Strict Validation:**

  - All academic fields are **required** for scholars
  - Blue info banner: "All academic fields are required for scholars"
  - Submit button disabled until all required fields are filled
  - Real-time validation tooltips

- **Additional Fields for Scholars:**

  - Date Filed (defaults to current date)
  - Remarks

- **Dual Mode Support:**
  - `mode="create"` - Add new scholar
  - `mode="edit"` - Edit existing scholar (with profile prop)

### 3. **Backend Route & Controller**

**Route:** `scholars.store`

```php
Route::post('/scholars', [ScholarController::class, 'store'])->name('scholars.store');
```

**Controller:** `app/Http/Controllers/ScholarController.php`

**Key Behavior:**

- Creates scholarship profile with `is_on_waiting_list = false`
- Creates scholarship record with `scholarship_status = 1` (Active/Approved)
- Auto-sets `date_approved = date_filed` if not provided
- Validates required academic fields (year_level, term, academic_year)
- Uses database transactions for data integrity
- Supports both IDs and names for school, course, and program

## Key Differences: Applicant vs Scholar

| Feature             | Applicant (Waiting List)    | Scholar (Existing)                    |
| ------------------- | --------------------------- | ------------------------------------- |
| Academic Info       | Optional                    | **Required**                          |
| Waiting List Status | `is_on_waiting_list = true` | `is_on_waiting_list = false`          |
| Scholarship Status  | `0` (Pending)               | `1` (Active/Approved)                 |
| Auto-approval       | No                          | Yes (date_approved set automatically) |
| Route               | `waitinglist.store`         | `scholars.store`                      |
| Modal               | `ApplicantFormModal`        | `ScholarFormModal`                    |

## Data Flow

### Frontend to Backend

```javascript
// ScholarFormModal prepares data:
{
    // Personal Information
    first_name, middle_name, last_name, extension_name,
    contact_no, contact_no_2, email,
    date_of_birth, gender, place_of_birth,
    civil_status, religion, indigenous_group,

    // Address (using names, not IDs)
    municipality: municipality?.name,
    barangay: barangay?.name,
    address,

    // Family Information
    father_name, father_occupation, father_contact_no,
    mother_name, mother_occupation, mother_contact_no,
    guardian_name, guardian_occupation, guardian_relationship,
    guardian_contact_no, parents_guardian_gross_monthly_income,

    // Academic Information (REQUIRED) - both IDs and names
    program_id, program,
    school_id, school,
    course_id, course,
    year_level, term, academic_year,

    // Scholar-specific fields
    date_filed, remarks
}

    // Address (using names, not IDs)
    municipality: municipality?.name,
    barangay: barangay?.name,
    address,

    // Family Information
    father_name, father_occupation, father_contact_no,
    mother_name, mother_occupation, mother_contact_no,
    guardian_name, guardian_occupation, guardian_relationship,
    guardian_contact_no, parents_guardian_gross_monthly_income,

    // Academic Information (REQUIRED) - both IDs and names
    program_id, program,
    school_id, school,
    course_id, course,
    year_level, term, academic_year,

    // Scholar-specific fields
    date_filed, date_approved, remarks
}
```

### Backend Processing

```php
// ScholarController@store
1. Validates all fields including required academic fields
2. Creates ScholarshipProfile with is_on_waiting_list = false
3. Resolves course/school/program from IDs or names
4. Creates ScholarshipRecord with:
   - scholarship_status = 1 (Active)
   - date_approved = date_filed (auto-approval)
   - is_active = 1
```

## Validation Rules

### Required Fields (Step 1)

- First Name
- Last Name
- Municipality
- Contact Number

### Required Fields (Step 3)

- Program
- School
- Course
- Year Level
- Term
- Academic Year

### Optional Fields

- All family information fields
- Date Filed (defaults to current date)
- Date Approved (defaults to date_filed)
- Remarks

## Usage Example

```vue
<!-- In any page that needs to add existing scholars -->
<ScholarFormModal v-model:visible="showScholarModal" mode="create" @success="refreshData" />

<script setup>
	import ScholarFormModal from '@/Components/modals/ScholarFormModal.vue';

	const showScholarModal = ref(false);

	const openScholarModal = () => {
		showScholarModal.value = true;
	};

	const refreshData = () => {
		router.reload({ preserveState: true });
	};
</script>
```

## API Endpoints

### POST `/scholars`

**Purpose:** Create a new active scholar

**Request Body:** See "Data Flow" section above

**Response:**

- Success: Redirect with success message
- Error: Returns validation errors

**Validation:**

- Checks for duplicate names
- Validates all required fields
- Validates foreign key existence (course_id, school_id, program_id)

## Testing Checklist

- [ ] Open Scholarship Index page
- [ ] Click "Add New Record" → "Add Existing"
- [ ] Fill in required fields in Step 1
- [ ] Verify duplicate name validation works
- [ ] Fill in family information in Step 2
- [ ] Fill in ALL academic fields in Step 3 (required)
- [ ] Submit the form
- [ ] Verify scholar is created with:
  - `is_on_waiting_list = false`
  - `scholarship_status = 1`
  - Active scholarship record exists
- [ ] Verify scholar appears in "Existing" profile type filter
- [ ] Verify scholar does NOT appear in waiting list

## Files Modified/Created

### Created:

- `app/Http/Controllers/ScholarController.php`
- `resources/js/Components/modals/ScholarFormModal.vue`
- `ADD_EXISTING_SCHOLAR_IMPLEMENTATION.md` (this file)

### Modified:

- `routes/web.php` - Added `scholars.store` and `scholars.update` routes
- `resources/js/Pages/Scholarship/Index.vue` - Added button and ScholarFormModal integration

### Shared Components Used:

- `resources/js/Components/forms/fields/PersonalInformationFields.vue`
- `resources/js/Components/forms/fields/FamilyInformationFields.vue`
- `resources/js/Components/forms/fields/AcademicInformationFields.vue`

## Benefits

1. **Clear Separation:** Distinct workflow for adding existing scholars vs applicants
2. **Data Integrity:** Required academic information ensures complete records
3. **Automatic Approval:** Scholars are automatically approved upon creation
4. **Reusable Components:** Shares field components with ApplicantFormModal
5. **Consistent UI:** Uses the same stepper approach as applicant form
6. **Dual Mode:** Single modal component handles both create and edit operations
7. **Consistent UI:** Uses the same stepper approach as applicant form

## Future Enhancements

- [ ] Add scholar photo upload
- [ ] Add document attachment support
- [ ] Add bulk import for multiple scholars
- [ ] Add scholar transfer functionality
- [ ] Add scholarship renewal workflow
- [ ] Add scholarship termination workflow

---

**Last Updated:** October 16, 2025
**Version:** 1.0.0
