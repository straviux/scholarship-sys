# Export Functionality Implementation

## Overview

Added export functionality for filtered applicant data with paper size and orientation selection dialog.

## Implementation Date

October 14, 2025

## Changes Made

### 1. Frontend Components

#### New File: `ExportModal.vue`

**Location**: `resources/js/Pages/Applicants/Modal/ExportModal.vue`

**Features**:

- Paper size selection (A4, Letter, Legal)
- Orientation selection (Portrait, Landscape)
- Export format selection (Excel .xlsx, PDF .pdf)
- Displays count of records to be exported
- Uses current filter settings from main page

**Props**:

- `show` - Boolean to control modal visibility
- `filters` - Object containing all current filter values
- `totalRecords` - Number of records that will be exported

**Export Settings**:

- Default paper size: A4
- Default orientation: Landscape
- Default format: Excel (.xlsx)

#### Updated: `Index.vue`

**Location**: `resources/js/Pages/Applicants/Index.vue`

**Changes**:

1. Imported `ExportModal` component
2. Added state management:
   ```javascript
   const showExportModal = ref(false);
   const openExportModal = () => {
   	showExportModal.value = true;
   };
   ```
3. Updated Export button to open new modal:
   ```vue
   <Button @click="openExportModal" label="Export" icon="pi pi-download" />
   ```
4. Added ExportModal component in template:
   ```vue
   <ExportModal
   	:show="showExportModal"
   	@update:show="showExportModal = $event"
   	:filters="filter"
   	:totalRecords="props.profiles_total"
   />
   ```

### 2. Backend Implementation

#### Updated: `WaitingListController.php`

**Location**: `app/Http/Controllers/WaitingListController.php`

**New Method**: `export(Request $request)`

**Functionality**:

- Applies same filtering logic as index method:
  - Date range filtering (date_from, date_to)
  - School, course, year level filtering
  - Municipality filtering
  - Applicant name search
  - Parent/guardian name search
  - Global search across multiple fields
  - JPM-only filtering
  - Program filtering

**Export Process**:

1. Builds query with all active filters
2. Retrieves all matching profiles (no pagination)
3. Calculates summary statistics:
   - Total applicants
   - Male/female count
4. Gets export settings from request:
   - `export_format` (xlsx/pdf)
   - `paper_size` (A4/Letter/Legal)
   - `orientation` (portrait/landscape)
5. Checks user JPM viewing permission using `Gate::allows('can-view-jpm')`
6. Returns appropriate file format:
   - **Excel**: Uses `WaitingListExport` class with Maatwebsite Excel → `.xlsx` file
   - **PDF**: Uses **Spatie Browsershot** with Chrome headless browser → `.pdf` file
     - Generates HTML from export view
     - Converts HTML to PDF using Browsershot
     - Supports custom paper sizes and orientations
     - Includes page numbers and generation date in footer
     - Uses `getChromePath()` helper method for Chrome executable detection

**Generated Filename Format**: `applicants_export_YYYY-MM-DD_HHmmss.xlsx` or `.pdf`

#### Updated: `web.php`

**Location**: `routes/web.php`

**New Route**:

```php
Route::get('/applicants-export', [WaitingListController::class, 'export'])
    ->name('waitinglist.export');
```

### 3. Export Flow

1. **User clicks "Export" button** in Applicants page
2. **ExportModal opens** showing:
   - Paper size options
   - Orientation options
   - Format options (Excel/PDF)
   - Count of records to be exported
3. **User selects preferences** and clicks "Export Now"
4. **Frontend builds URL** with all filter parameters + export settings
5. **Opens export URL in new tab** (triggers download)
6. **Backend processes request**:
   - Applies all filters from URL
   - Generates export file
   - Returns file for download
7. **Modal closes automatically** after 1 second

### 4. Key Features

✅ **Uses Current Filters**: Exports exactly what user sees on screen
✅ **No Preview**: Direct download instead of preview modal
✅ **Paper Configuration**: Customizable paper size and orientation
✅ **Format Options**: Excel or PDF export
✅ **JPM Highlighting**: Preserves JPM member highlighting in exports (if user has permission)
✅ **Loading State**: Shows loading indicator during export
✅ **Automatic Download**: Opens in new tab/triggers download automatically

### 5. Filter Parameters Supported

All current filters are passed to export:

- `name` - Applicant name
- `parent_name` - Parent/guardian name
- `program` - Scholarship program
- `school` - School
- `course` - Course
- `municipality` - Municipality
- `year_level` - Year level
- `date_from` - Date filed from
- `date_to` - Date filed to
- `global_search` - Global search term
- `show_jpm_only` - JPM filter flag

### 6. Dependencies

**Existing**:

- `WaitingListExport` class (app/Exports/WaitingListExport.php)
- Maatwebsite Excel package
- **Spatie Browsershot** for PDF generation (replaces DOMPDF)
- Chrome/Chromium headless browser

**New**:

- None (uses existing infrastructure)

**PDF Generation Technology**:

- ✅ **Browsershot** (Spatie) - Modern HTML to PDF conversion using Chrome
- ❌ ~~DOMPDF~~ - Removed due to compatibility issues

### 7. User Interface

**Export Button Location**: Info bar (right side), next to records display

**Modal Design**:

- Clean, compact dialog (450px width)
- Two-column grid for paper settings
- Radio buttons for format selection
- Info message showing record count
- Cancel and Export Now buttons

### 8. Security & Permissions

- Uses Laravel authentication middleware
- Respects JPM viewing permissions (`can-view-jpm`)
- Only authenticated users can export
- Filters are sanitized server-side

### 9. Testing Recommendations

1. Test with various filter combinations
2. Test Excel export with large datasets
3. Test PDF export with different paper sizes/orientations
4. Verify JPM highlighting appears correctly (if permission granted)
5. Test with no filters applied (export all)
6. Test with Show JPM Only filter active
7. Verify filename format and timestamp

## Build Status

✅ Build successful in 12.46s (with Browsershot implementation)
✅ No errors or warnings
✅ ExportModal component bundled successfully (3.78 kB)

## PDF Generation Technology Update

### Why Browsershot Instead of DOMPDF?

**Issue with DOMPDF**:

- Error: `Class "Dompdf\Dompdf" not found`
- Compatibility issues with Laravel Excel package

**Solution - Browsershot**:

- ✅ Already installed in project (`spatie/browsershot: ^5.0`)
- ✅ Uses Chrome/Chromium headless browser for accurate PDF rendering
- ✅ Better HTML/CSS support (renders exactly like in browser)
- ✅ Handles complex layouts and styling perfectly
- ✅ Proven implementation in existing `ReportController`

### Browsershot Implementation Details

**Key Features**:

1. **Chrome Path Detection**: Uses `getChromePath()` helper method

   - Tries primary path from config
   - Falls back to alternative paths
   - Throws descriptive error if Chrome not found

2. **PDF Generation Options**:

   ```php
   Browsershot::html($html)
       ->setChromePath($this->getChromePath())
       ->showBackground()              // Include background colors/images
       ->showBrowserHeaderAndFooter()  // Enable header/footer
       ->footerHtml(...)               // Page numbers + generation date
       ->margins(4, 4, 4, 4)          // 4mm margins all sides
       ->landscape()                   // Or portrait (based on user selection)
       ->format('A4')                  // Paper size
       ->pdf()                         // Generate PDF
   ```

3. **Paper Size Handling**:

   - **A4 / Letter**: Uses `->format()` method
   - **Legal / Long**: Uses custom size `->setPaperSize(215.9, 330.2)` in mm

4. **Error Handling**:
   - Catches exceptions during PDF generation
   - Logs errors for debugging
   - Returns JSON error response with details

### Configuration Requirements

**Chrome/Chromium Path** (in `.env` or `config/scholarship.php`):

```env
CHROME_PATH=/path/to/chrome
```

**Fallback Paths** (configured in `config/scholarship.php`):

- Windows: `C:\Program Files\Google\Chrome\Application\chrome.exe`
- Linux: `/usr/bin/chromium-browser`, `/usr/bin/google-chrome`
- macOS: `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`

## Notes

- Export respects all active filters automatically
- No need to manually configure filters in export dialog
- Uses same query builder as main index page for consistency
- Export filename includes timestamp for uniqueness
- Opens in new tab to allow continued browsing while downloading
- **PDF generation requires Chrome/Chromium to be installed on server**
