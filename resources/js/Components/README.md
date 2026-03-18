# Components Folder Structure

## 📁 Organized Component Architecture

The components have been reorganized into a clean, maintainable folder structure for better organization and scalability.

## 🗂️ Folder Structure

```
resources/js/Components/
├── forms/                          # Form-related components
│   └── fields/                     # Reusable field components
│       ├── PersonalInformationFields.vue
│       ├── FamilyInformationFields.vue
│       └── AcademicInformationFields.vue
│
├── modals/                         # Modal/Dialog components
│   ├── ApplicantFormModal.vue     # For adding/editing applicants
│   ├── ScholarFormModal.vue       # For adding/editing scholars
│   ├── AddApplicantModal.vue
│   ├── AddExistingModal.vue
│   └── Modal.vue                   # Base modal component
│
├── selects/                        # Custom select/dropdown components
│   ├── AcademicYearSelect.vue
│   ├── BarangaySelect.vue
│   ├── CivilStatusSelect.vue
│   ├── CourseSelect.vue
│   ├── GenderSelect.vue
│   ├── MunicipalitySelect.vue
│   ├── ProfileSelect.vue
│   ├── ProgramSelect.vue
│   ├── RecordsSelect.vue
│   ├── SchoolSelect.vue
│   ├── TermSelect.vue
│   └── YearLevelSelect.vue
│
├── ui/                             # General UI components
│   ├── buttons/                    # Button components
│   │   ├── PrimaryButton.vue
│   │   ├── SecondaryButton.vue
│   │   └── DangerButton.vue
│   │
│   ├── inputs/                     # Input components
│   │   ├── TextInput.vue
│   │   ├── TextArea.vue
│   │   ├── DateInput.vue
│   │   ├── Checkbox.vue
│   │   ├── InputLabel.vue
│   │   └── InputError.vue
│   │
│   ├── navigation/                 # Navigation components
│   │   ├── NavLink.vue
│   │   ├── ResponsiveNavLink.vue
│   │   ├── SidebarLink.vue
│   │   ├── Dropdown.vue
│   │   ├── DropdownLink.vue
│   │   └── NotificationDropdown.vue
│   │
│   └── table/                      # Table components
│       ├── Table.vue
│       ├── TableRow.vue
│       ├── TableHeaderCell.vue
│       ├── TableDataCell.vue
│       └── Pagination.vue
│
└── ApplicationLogo.vue             # Brand logo component (root level)
```

## 📋 Component Categories

### 1. **Forms** (`forms/`)

Components related to form structure and organization.

#### **Fields** (`forms/fields/`)

Reusable field group components shared across different forms:

- **PersonalInformationFields.vue** - Personal data fields (name, DOB, gender, address)
- **FamilyInformationFields.vue** - Family information fields (father, mother, guardian)
- **AcademicInformationFields.vue** - Academic fields (program, course, school, year level)

**Usage Example:**

```vue
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';

<PersonalInformationFields
	v-model:first_name="form.first_name"
	v-model:last_name="form.last_name"
	v-model:gender="form.gender"
	:show-header="false"
/>
```

### 2. **Modals** (`modals/`)

Dialog and modal components for user interactions.

- **ApplicantFormModal.vue** - For creating/editing applicants
- **ScholarFormModal.vue** - For creating/editing active scholars
- **AddApplicantModal.vue** - Quick add applicant modal
- **AddExistingModal.vue** - Add existing profile modal
- **Modal.vue** - Base modal wrapper component

**Usage Example:**

```vue
import ApplicantFormModal from '@/Components/modals/ApplicantFormModal.vue';

<ApplicantFormModal v-model:visible="showModal" :mode="'create'" @success="handleSuccess" />
```

### 3. **Selects** (`selects/`)

Custom dropdown/select components for specific data types.

**Data Selects:**

- **CourseSelect.vue** - Course selection
- **SchoolSelect.vue** - School selection
- **ProgramSelect.vue** - Scholarship program selection
- **ProfileSelect.vue** - Profile selection

**Location Selects:**

- **MunicipalitySelect.vue** - Municipality selection
- **BarangaySelect.vue** - Barangay selection

**Academic Selects:**

- **YearLevelSelect.vue** - Year level selection (1st, 2nd, 3rd, 4th)
- **TermSelect.vue** - Academic term selection
- **AcademicYearSelect.vue** - Academic year selection

**Other Selects:**

- **GenderSelect.vue** - Gender selection (Male/Female)
- **CivilStatusSelect.vue** - Civil status selection
- **RecordsSelect.vue** - Records per page selection

**Usage Example:**

```vue
import CourseSelect from '@/Components/selects/CourseSelect.vue';

<CourseSelect v-model="form.course" label="shortname" custom-placeholder="Select Course" />
```

### 4. **UI Components** (`ui/`)

General-purpose UI components.

#### **Buttons** (`ui/buttons/`)

- **PrimaryButton.vue** - Primary action buttons (blue)
- **SecondaryButton.vue** - Secondary action buttons (gray)
- **DangerButton.vue** - Danger action buttons (red)

**Usage Example:**

```vue
import PrimaryButton from '@/Components/ui/buttons/PrimaryButton.vue';

<PrimaryButton @click="handleSubmit">Save</PrimaryButton>
```

#### **Inputs** (`ui/inputs/`)

- **TextInput.vue** - Standard text input
- **TextArea.vue** - Multi-line text input
- **DateInput.vue** - Date picker input
- **Checkbox.vue** - Checkbox input
- **InputLabel.vue** - Form input label
- **InputError.vue** - Form validation error display

**Usage Example:**

```vue
import TextInput from '@/Components/ui/inputs/TextInput.vue'; import InputLabel from
'@/Components/ui/inputs/InputLabel.vue'; import InputError from
'@/Components/ui/inputs/InputError.vue';

<InputLabel for="name" value="Name" />
<TextInput id="name" v-model="form.name" />
<InputError :message="form.errors.name" />
```

#### **Navigation** (`ui/navigation/`)

- **NavLink.vue** - Navigation link component
- **ResponsiveNavLink.vue** - Mobile-responsive nav link
- **SidebarLink.vue** - Sidebar navigation link
- **Dropdown.vue** - Dropdown menu
- **DropdownLink.vue** - Dropdown menu item
- **NotificationDropdown.vue** - Notification dropdown

**Usage Example:**

```vue
import NavLink from '@/Components/ui/navigation/NavLink.vue';

<NavLink :href="route('dashboard')" :active="route().current('dashboard')">
    Dashboard
</NavLink>
```

#### **Table** (`ui/table/`)

- **Table.vue** - Base table wrapper
- **TableRow.vue** - Table row component
- **TableHeaderCell.vue** - Table header cell
- **TableDataCell.vue** - Table data cell
- **Pagination.vue** - Pagination controls

**Usage Example:**

```vue
import Table from '@/Components/ui/table/Table.vue'; import TableHeaderCell from
'@/Components/ui/table/TableHeaderCell.vue'; import TableDataCell from
'@/Components/ui/table/TableDataCell.vue';

<Table>
    <TableHeaderCell>Name</TableHeaderCell>
    <TableHeaderCell>Email</TableHeaderCell>
</Table>
```

## 🔄 Import Path Changes

### Before (Flat Structure):

```javascript
import PersonalInformationFields from '@/Components/PersonalInformationFields.vue';
import ApplicantFormModal from '@/Components/ApplicantFormModal.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
```

### After (Organized Structure):

```javascript
import PersonalInformationFields from '@/Components/forms/fields/PersonalInformationFields.vue';
import ApplicantFormModal from '@/Components/modals/ApplicantFormModal.vue';
import CourseSelect from '@/Components/selects/CourseSelect.vue';
import PrimaryButton from '@/Components/ui/buttons/PrimaryButton.vue';
```

## 📝 Migration Checklist

When updating existing code to use the new structure:

- [ ] Update import paths in all `.vue` files
- [ ] Update import paths in all `.js` files
- [ ] Update any dynamic imports
- [ ] Test all pages to ensure components load correctly
- [ ] Run `npm run build` to verify compilation
- [ ] Check for any broken imports in browser console

## 🎯 Benefits

1. **Better Organization** - Related components are grouped together
2. **Easier to Find** - Clear categorization makes components easy to locate
3. **Scalability** - Easy to add new components in appropriate folders
4. **Maintainability** - Logical structure makes maintenance easier
5. **Onboarding** - New developers can understand the structure quickly

## 🔍 Quick Reference

| Component Type | Location         | Example                       |
| -------------- | ---------------- | ----------------------------- |
| Field Groups   | `forms/fields/`  | PersonalInformationFields.vue |
| Modals         | `modals/`        | ApplicantFormModal.vue        |
| Selects        | `selects/`       | CourseSelect.vue              |
| Buttons        | `ui/buttons/`    | PrimaryButton.vue             |
| Inputs         | `ui/inputs/`     | TextInput.vue                 |
| Navigation     | `ui/navigation/` | NavLink.vue                   |
| Tables         | `ui/table/`      | Table.vue                     |

## 🚀 Next Steps

After reorganization, you'll need to:

1. **Update all import statements** in your codebase
2. **Run a global search** for old import paths
3. **Test thoroughly** to ensure nothing is broken
4. **Update documentation** with new import paths

---

**Last Updated:** October 16, 2025  
**Version:** 2.0  
**Status:** ✅ Structure Implemented
