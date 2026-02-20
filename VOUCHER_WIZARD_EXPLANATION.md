# VoucherWizard.vue Component Explanation

## Overview
The **VoucherWizard** is a multi-step modal dialog component designed for creating and editing financial vouchers in a scholarship management system. It guides users through a structured workflow to capture all necessary obligations, disbursement details, and scholar information.

## Component Type
- **Vue 3 Component** (Composition API with `<script setup>`)
- **Purpose**: Multi-step form wizard for voucher management
- **Mode**: Supports both `create` and `edit` operations

---

## Key Features

### 1. **Multi-Step Workflow (5 Steps)**
The wizard progresses through five distinct steps:

| Step | Title | Purpose |
|------|-------|---------|
| 1 | Select Scholars | Search and select scholars for the voucher |
| 2 | Obligation Request | Enter payment obligations and accounting details |
| 3 | Disbursements/Payroll | Specify disbursement type and explanation |
| 4 | List of Scholars | Review selected scholars and add optional course info |
| 5 | Review & Create | Final review before submission |

### 2. **Scholar Selection (Step 1)**
- **Search Functionality**: Real-time local search by name or email (debounced)
- **Select Options**:
  - Individual scholar selection with checkboxes
  - "Select All" checkbox for quick bulk selection
  - Displays scholar details: Name, Year Level, Course, School
  - Shows year level formatting (e.g., "1st YEAR", "2nd YEAR")
- **Search-Only Mode**: Only displays scholars when user actively searches

### 3. **Obligation Details (Step 2)**
Captures comprehensive payment information:

**Payee Information:**
- Payee Type: Scholar or School
- Payee Selection: Dropdown (scholars) or text input (school name)
- Payee Address: Optional address field

**Accounting Details:**
- **OBR Type**: REGULAR, FINANCIAL ASSISTANCE, or REIMBURSEMENT
- **Responsibility Center**: Dropdown from API (code-based)
- **Particulars/Account Code**: Cascading dropdown (depends on RC selection)
- **Particulars Description**: Rich text editor (Quill) for detailed notes

**Amount & Academic Info:**
- Amount: Per-head amount (₱ currency formatted)
- Academic Year: Optional select component
- Term/Semester: Optional select component

### 4. **Disbursement Details (Step 3)**
- **Disbursement Type**: Radio buttons for "Disbursement Voucher" or "Payroll"
- **Explanation**: Rich text editor (Quill) for detailed explanation
- Auto-fill course field from Letter of Support (LOS) course if empty

### 5. **Scholar List (Step 4)**
- Displays all selected scholars with confirmation
- Optional course name entry
- Serves as a final checkpoint before submission

### 6. **Review & Create (Step 5)**
Comprehensive summary including:
- Obligation details with payee and account info
- Scholar breakdown with per-head and total amounts
- Disbursement explanation
- Notes summary
- Course and academic year information

---

## State Management

### Form Data Structure
```javascript
voucherData = {
  voucher_number: string,
  scholars: array,
  obligations: {
    payee_type: 'scholar' | 'school',
    payee_id: string,
    payee_name: string,
    payee_address: string,
    responsibility_center: string,
    account_code: string,
    particulars_name: string,
    particulars_description: string (HTML),
    amount: number,
    obr_type: 'REGULAR' | 'FINANCIAL ASSISTANCE' | 'REIMBURSEMENT'
  },
  disbursements: {
    type: 'disbursements' | 'payroll',
    explanation: string (HTML),
    los_course: string,
    course: string,
    academic_year: string,
    semester: string
  },
  summary: {
    notes: string,
    transaction_status: 'pending' | 'suspended' | 'completed'
  }
}
```

### Key Refs & Reactives
- `step`: Current wizard step (1-5)
- `isOpen`: Dialog visibility state
- `selectedScholars`: Array of selected scholar objects
- `searchQuery`: Search input value
- `selectAll`: Toggle state for select all checkbox
- `loading`: Loading state for async operations
- `error`: Error message display
- `scholars`: All available scholars from API
- `responsibilityCenters`: Available responsibility centers from API

---

## Key Functions

### Scholar Management
- `fetchScholars()`: Fetches scholars from `/api/scholars` endpoint
- `performSearch()`: Local search filtering on name, email
- `toggleSelectAll()`: Selects/deselects all filtered scholars
- `updateSelectedCount()`: Updates selected scholars list
- `getSelectedScholarsList()`: Returns selected scholars array

### Navigation
- `nextStep()`: Moves to next step with validation
- `previousStep()`: Moves to previous step
- `closeWizard()`: Closes modal and resets form

### Data Submission
- `handleSubmit()`: Submits voucher to API
  - POST: `/api/vouchers` (create mode)
  - PUT: `/api/vouchers/{id}` (edit mode)
  - Handles responses and errors
  - Shows toast notifications

### Data Management
- `loadEditData()`: Populates form with existing voucher data (edit mode)
- `resetVoucherData()`: Clears all form fields to defaults

### Display Helpers
- `formatYearLevel()`: Converts year level to formatted form (e.g., "1st YEAR")
- `formatCurrency()`: Formats numbers as PHP currency
- `getStepTitle()`: Returns dynamic step title based on mode
- `getPayeeDisplay()`: Returns formatted payee name
- `getRCDisplay()`: Returns responsibility center display
- `getSelectedParticularName()`: Returns selected particular name

### Computed Properties
- `filteredScholars`: Scholars matching search query
- `selectedCount`: Number of selected scholars
- `selectedRC`: Currently selected responsibility center object
- `currentParticulars`: Particulars of selected RC
- `selectedParticular`: Details of selected particular
- `selectedPayeeScholar`: Scholar object for payee selection
- `totalAmount`: Per-head amount × number of scholars

---

## Integration Details

### External Components
- `AcademicYearSelect`: Custom select for academic year
- `TermSelect`: Custom select for semester
- `QuillEditor`: Rich text editor for descriptions

### API Endpoints
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/scholars` | GET | Fetch all scholars |
| `/api/responsibility-centers` | GET | Fetch responsibility centers with particulars |
| `/api/vouchers` | POST | Create new voucher |
| `/api/vouchers/{id}` | PUT | Update existing voucher |

### Libraries Used
- **Vue 3**: Composition API, reactivity
- **Inertia.js**: Page context and CSRF token
- **Axios**: HTTP requests
- **PrimeVue**: UI components (Dialog, Drawer, Toast)
- **Quill**: Rich text editing
- **PrimeFlex**: Responsive grid utilities

---

## Validation

### Step 1 (Scholar Selection)
- ✓ Requires at least one scholar selected before proceeding
- Error: "Please select at least one scholar"

### Step 2 (Obligations)
- Validates payee selection and account code via UI state
- Responsibility center required for account code selection

### Step 5 (Final Submit)
- Builds payee name based on type and selection
- Parses amount to float
- Handles multiple scholars with " & CO." notation

---

## Edit Mode Behavior

When `mode="edit"` and `initialData` is provided:

1. Loads voucher data into form
2. Auto-selects scholars based on `scholar_ids`
3. Populates all fields from existing voucher
4. Displays voucher number in information banner
5. Changes submit button text to "Update Voucher"
6. Uses PUT request instead of POST

---

## UI Components

### Modal Dialog (PrimeVue)
- Responsive width: 90% (max 1100px for Step 2, 640px for others)
- Progress bar showing completion percentage
- Header includes dynamic step title

### Right Drawer (Obligation Preview)
- Shows real-time preview of selected scholars (Step 1)
- Shows obligation preview with summary table (Steps 2-5)
- Displays scholars breakdown and total amount

### Progress Indicators
- Step counter: "Step X of 5"
- Completion percentage (20% per step)
- Visual progress bar

---

## Event Emitters

| Event | Payload | Purpose |
|-------|---------|---------|
| `close` | None | Emitted when wizard closes |
| `scholar-selected` | (scholars, disbursementType) | Emitted on successful submit |

---

## CSS & Styling

- **Framework**: Tailwind CSS with scoped styles
- **Colors**: 
  - Primary: Blue (`#2563eb`)
  - Success: Green
  - Warning: Yellow
  - Danger: Red
- **Dialog Header**: Linear gradient blue background
- **Text Alignment**: Quill editor supports text alignment (center, right, justify)
- **Responsive**: Grid layout adapts to mobile/tablet/desktop

---

## Props

```javascript
props = {
  mode: 'create' | 'edit',      // Wizard mode
  voucherId: Number | String,    // Voucher ID for edit mode
  initialData: Object,           // Existing data for edit
  visible: Boolean               // Dialog visibility
}
```

---

## Lifecycle

1. **onMounted**: 
   - Sets CSRF token from meta tag
   - Fetches scholars
   - Fetches responsibility centers
   - Loads edit data or resets for create mode
   - Opens dialog

2. **Watchers**:
   - `searchQuery`: Debounced search (300ms)
   - `visible` prop: Syncs with isOpen ref
   - `initialData`: Reloads data in edit mode
   - `los_course`: Auto-fills course field

---

## Error Handling

- Try-catch blocks for all async operations
- Toast notifications for success/error messages
- Error messages displayed in UI
- Logger.js integration for debugging
- Graceful fallbacks for missing data

---

## Notes

- Only **active scholars** are displayed
- Search is **local** (client-side filtering of loaded scholars)
- Voucher number is **auto-generated** on creation
- "& CO." notation added to payee name when multiple scholars selected
- Rich text editors (Quill) support formatting, lists, links, and text alignment
- Academic year and semester are **optional fields**
- Total amount calculated dynamically based on per-head amount and scholar count
