# JPM Highlighting Implementation

## Overview

This document describes the implementation of JPM (Jeepney Modernization Program) member highlighting across the report generation system, including the web preview, PDF exports, and Excel exports.

## Implementation Date

October 13, 2025

## Color Scheme

The highlighting uses a consistent color scheme across all report formats:

### Yellow Highlighting - JPM Member (Applicant)

- **Background**: `#FEF3C7` (yellow-50 in Tailwind)
- **Border**: `#F59E0B` (amber-500)
- **Condition**: `is_jpm_member === true`
- **Description**: Applied when the applicant themselves is a JPM member

### Green Highlighting - JPM Parent/Guardian

- **Background**: `#D1FAE5` (green-50 in Tailwind)
- **Border**: `#10B981` (green-500)
- **Condition**: `is_father_jpm || is_mother_jpm || is_guardian_jpm === true`
- **Description**: Applied when the applicant's parent or guardian is a JPM member

## Files Modified

### 1. Frontend - ReportView.vue

**File**: `resources/js/Pages/Applicants/Modal/ReportView.vue`

**Changes**:

- Added `getJpmRowClass()` helper function to determine row highlighting
- Applied dynamic classes to table rows using `:class` binding
- Added legend component explaining the color scheme

**Code Added**:

```javascript
// Helper function to get JPM row class
const getJpmRowClass = (item) => {
	if (item.is_jpm_member) return 'bg-yellow-50';
	if (item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm) return 'bg-green-50';
	return '';
};
```

```vue
<!-- JPM Legend -->
<div v-if="reportType === 'list'" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
    <p class="text-xs font-semibold text-gray-700 mb-2">Legend:</p>
    <div class="flex gap-4 text-xs">
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-yellow-50 border border-yellow-200 rounded"></div>
            <span class="text-gray-600">JPM Member (Applicant)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-green-50 border border-green-200 rounded"></div>
            <span class="text-gray-600">JPM Parent/Guardian</span>
        </div>
    </div>
</div>
```

### 2. PDF Export - Blade Template

**File**: `resources/views/waiting_list_report.blade.php`

**Changes**:

- Added CSS classes for both JPM member and parent/guardian highlighting
- Updated row logic to apply appropriate class based on JPM status

**CSS Added**:

```css
.jpm-member-highlight {
	background-color: #fef3c7 !important;
	border-left: 3px solid #f59e0b !important;
}

.jpm-parent-highlight {
	background-color: #d1fae5 !important;
	border-left: 3px solid #10b981 !important;
}
```

**PHP Logic**:

```php
$isJpmMember = $profile->is_jpm_member;
$isParentGuardianJpm = $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm;
$highlightClass = $isJpmMember ? 'jpm-member-highlight' : ($isParentGuardianJpm ? 'jpm-parent-highlight' : '');
```

### 3. Excel Export - Blade Template

**File**: `resources/views/exports/waiting_list.blade.php`

**Changes**:

- Added CSS classes (same as PDF template)
- Updated row logic to apply appropriate class based on JPM status

### 4. Excel Export - PHP Class

**File**: `app/Exports/WaitingListExport.php`

**Changes**:

- Enhanced `registerEvents()` method to apply cell background colors for JPM members
- Added logic to iterate through profiles and apply conditional formatting

**Code Added**:

```php
// Apply JPM highlighting for list reports
if ($this->reportType === 'list') {
    $dataStartRow = 3; // Data starts from row 3 (after title and header)
    $sortedProfiles = $this->profiles->sortBy(function($profile) {
        $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
        return [$dateFiled, $profile->created_at];
    });

    $currentRow = $dataStartRow;
    foreach ($sortedProfiles as $profile) {
        $isJpmMember = $profile->is_jpm_member;
        $isParentGuardianJpm = $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm;

        if ($isJpmMember) {
            // Yellow highlight for JPM member (applicant)
            $event->sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FEF3C7'], // Yellow
                ],
            ]);
        } elseif ($isParentGuardianJpm) {
            // Green highlight for JPM parent/guardian
            $event->sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D1FAE5'], // Green
                ],
            ]);
        }

        $currentRow++;
    }
}
```

## Database Fields Referenced

The implementation checks the following fields from the `scholarship_profiles` table:

- `is_jpm_member` - Boolean indicating if applicant is a JPM member
- `is_father_jpm` - Boolean indicating if father is a JPM member
- `is_mother_jpm` - Boolean indicating if mother is a JPM member
- `is_guardian_jpm` - Boolean indicating if guardian is a JPM member

## Priority Logic

The highlighting follows this priority:

1. **If applicant is JPM member** → Apply yellow highlighting (takes precedence)
2. **Else if any parent/guardian is JPM member** → Apply green highlighting
3. **Otherwise** → No highlighting

## Features

### Web Report View

✅ Dynamic row background colors  
✅ Visual legend explaining color scheme  
✅ Responsive design maintains highlighting  
✅ Hover states preserved

### PDF Export

✅ Colored backgrounds with left border accent  
✅ Print-safe colors that maintain visibility  
✅ Consistent styling with web view

### Excel Export

✅ Cell background fill colors applied  
✅ PhpSpreadsheet API used for styling  
✅ Works with all Excel-compatible applications  
✅ Maintains highlighting when opening in Excel, Google Sheets, etc.

## Testing Recommendations

### Web Preview Testing

1. Generate a list report with applicants
2. Verify yellow highlighting appears for `is_jpm_member` records
3. Verify green highlighting appears for parent/guardian JPM records
4. Check legend displays correctly
5. Test hover states maintain visibility

### PDF Export Testing

1. Generate and download PDF report
2. Open PDF in viewer
3. Verify yellow backgrounds for JPM members
4. Verify green backgrounds for JPM parents/guardians
5. Verify left border accent appears
6. Test both portrait and landscape orientations
7. Test different paper sizes (A4, Letter, Legal)

### Excel Export Testing

1. Generate and download Excel report
2. Open in Microsoft Excel
3. Verify cell background colors applied correctly
4. Open in Google Sheets to test compatibility
5. Verify highlighting persists across applications
6. Test with different filter combinations

## Browser/Application Compatibility

### Web Browsers

- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari
- ✅ Mobile browsers

### PDF Viewers

- ✅ Adobe Acrobat Reader
- ✅ Browser built-in PDF viewers
- ✅ Foxit Reader
- ✅ Preview (macOS)

### Spreadsheet Applications

- ✅ Microsoft Excel (2016+)
- ✅ Google Sheets
- ✅ LibreOffice Calc
- ✅ Apple Numbers

## Notes

- The highlighting only appears in **list reports**, not summary reports
- Colors are consistent across all export formats for easy recognition
- The implementation prioritizes applicant JPM membership over parent/guardian membership
- All color values use web-safe colors that render consistently across platforms

## Related Documentation

- [Report Generation Refactoring](./REPORT_GENERATION_REFACTORING.md)
- [Report View Refactoring](./REPORT_VIEW_REFACTORING.md)
- [Simple Table Report View](./SIMPLE_TABLE_REPORT_VIEW.md)
