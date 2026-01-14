# JasperReportController Setup

## What Was Created

### 1. **Controller: `app/Http/Controllers/JasperReportController.php`**

A simple controller with 3 methods:

#### Method 1: `pendingApplications()`
- **Route:** `GET /api/jasper/pending-applications`
- **Does:** Generates PDF report for all pending applications
- **Returns:** PDF file download
- **Example:**
  ```
  GET http://localhost:8000/api/jasper/pending-applications
  ```

#### Method 2: `approvedApplications()`
- **Route:** `GET /api/jasper/approved-applications`
- **Does:** Generates PDF report for all approved applications
- **Returns:** PDF file download

#### Method 3: `reportByStatus(string $status)`
- **Route:** `GET /api/jasper/applications/{status}`
- **Does:** Generates report for any status (pending, approved, declined, etc.)
- **Returns:** PDF file download
- **Example:**
  ```
  GET http://localhost:8000/api/jasper/applications/pending
  ```

---

## How It Works

```php
// Inside the controller
$this->jasper  // JasperReportService (injected in constructor)
    ->template('pending_applications')  // Use the template
    ->parameters([...])  // Pass data
    ->format('pdf')  // Set output format
    ->generate();  // Create the PDF
```

---

## Configuration Added

Added 3 new template entries to `config/jasperreports.php`:

```php
'reports' => [
    'pending_applications' => 'reports/pending-applications.jrxml',
    'approved_applications' => 'reports/approved-applications.jrxml',
    'applications_report' => 'reports/applications-report.jrxml',
],
```

---

## Routes Added

All routes protected with `auth` middleware:

```php
GET  /api/jasper/pending-applications      → pendingApplications()
GET  /api/jasper/approved-applications     → approvedApplications()
GET  /api/jasper/applications/{status}     → reportByStatus($status)
```

---

## Next Steps

### 1. **Create the JRXML Templates**

You need to create these files in `storage/jasper-templates/reports/`:

- `pending-applications.jrxml`
- `approved-applications.jrxml`
- `applications-report.jrxml`

Use **Jaspersoft Studio** (free) to design them.

### 2. **Test the Routes**

Once templates exist, test via:

```bash
# Download pending applications report
curl http://localhost:8000/api/jasper/pending-applications \
  -H "Authorization: Bearer YOUR_TOKEN"

# Or visit in browser (if logged in)
http://localhost:8000/api/jasper/pending-applications
```

### 3. **Customize as Needed**

The controller methods are very simple. You can modify them to:
- Add date filters
- Add status filters
- Pass different data
- Generate different formats (xlsx, docx, etc.)

---

## Quick Usage

### From Browser (logged in)
```
http://localhost:8000/api/jasper/pending-applications
```

### From JavaScript
```javascript
window.location.href = '/api/jasper/pending-applications';
```

### From cURL
```bash
curl -X GET "http://localhost:8000/api/jasper/pending-applications" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### From Another Controller
```php
use App\Http\Controllers\JasperReportController;

$controller = app(JasperReportController::class);
$download = $controller->pendingApplications(new Request());
```

---

## File Structure

```
app/
  Http/
    Controllers/
      JasperReportController.php  ← NEW

config/
  jasperreports.php  ← MODIFIED (added templates)

routes/
  web.php  ← MODIFIED (added routes)

storage/
  jasper-templates/
    reports/
      pending-applications.jrxml  ← NEEDS TO BE CREATED
      approved-applications.jrxml  ← NEEDS TO BE CREATED
      applications-report.jrxml  ← NEEDS TO BE CREATED
```

---

## Summary

✅ **JasperReportController** created with 3 simple methods
✅ **Routes** configured and protected
✅ **Config** updated with template names
⏳ **Templates** still need to be created using Jaspersoft Studio
