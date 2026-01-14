# JasperReport Backend Service Implementation Sample

This document shows the complete implementation of the sample JasperReport backend methods added to your ReportController.

## Quick Start

### 1. Generate Waiting List Report (Sync)
```bash
curl -X GET "http://localhost:8000/api/jasper/report/waiting-list?scholarship_id=1&status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:** PDF file download

---

## API Methods Implemented

### 1. **Get Waiting List Report** (Synchronous)
**Route:** `GET /api/jasper/report/waiting-list`

**Method:** `ReportController@getWaitingListReport`

**Parameters:**
- `scholarship_id` (optional): Filter by scholarship ID
- `status` (optional): Filter by status (pending, approved, etc.)
- `date_from` (optional): Start date for filtering
- `date_to` (optional): End date for filtering

**Example:**
```bash
GET /api/jasper/report/waiting-list?scholarship_id=1&status=pending
```

**Response:** 
- Success: PDF file download
- Error: JSON with error message

---

### 2. **Generate Report Async** (Asynchronous - Queued)
**Route:** `POST /api/jasper/report/generate-async`

**Method:** `ReportController@generateReportAsync`

**Request Body:**
```json
{
  "template": "waiting_list",
  "filters": {
    "scholarship_id": 1,
    "status": "pending",
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
  }
}
```

**Example:**
```bash
curl -X POST "http://localhost:8000/api/jasper/report/generate-async" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "template": "waiting_list",
    "filters": {"scholarship_id": 1}
  }'
```

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "message": "Report generation queued. Check status using job_id."
}
```

---

### 3. **Check Report Status**
**Route:** `GET /api/jasper/report/status/{jobId}`

**Method:** `ReportController@getReportStatus`

**Parameters:**
- `jobId` (required): Job ID from async generation response

**Example:**
```bash
GET /api/jasper/report/status/550e8400-e29b-41d4-a716-446655440000
```

**Response (Pending):**
```json
{
  "status": "processing",
  "progress": "50%",
  "message": "Report generation in progress..."
}
```

**Response (Complete):**
```json
{
  "status": "completed",
  "file_path": "storage/reports/waiting_list_123456789_abc123.pdf",
  "download_url": "/api/jasper/reports/download",
  "created_at": "2024-01-15T10:30:00Z"
}
```

**Response (Not Found):**
```json
{
  "status": "not_found",
  "message": "Report job not found or expired"
}
```

---

### 4. **Generate Custom Report**
**Route:** `POST /api/jasper/report/custom`

**Method:** `ReportController@generateCustomReport`

**Request Body:**
```json
{
  "template": "waiting_list",
  "format": "pdf",
  "parameters": {
    "scholarship_id": 1,
    "status": "approved",
    "custom_param": "custom_value"
  }
}
```

**Example:**
```bash
curl -X POST "http://localhost:8000/api/jasper/report/custom" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "template": "waiting_list",
    "format": "pdf",
    "parameters": {
      "scholarship_id": 1,
      "status": "approved"
    }
  }'
```

**Response:**
- Success: PDF file download
- Error: JSON with validation or execution error

---

## Backend Service Usage (In Your Code)

### Using JasperReportService Directly

```php
use App\Services\JasperReportService;

// In your controller or service
$jasper = app(JasperReportService::class);

// Generate report
$pdfPath = $jasper
    ->template('waiting_list')
    ->parameters([
        'scholarship_id' => 1,
        'status' => 'approved',
    ])
    ->format('pdf')
    ->generate();

// Download the file
return response()->download($pdfPath);
```

### Using Queue for Async Generation

```php
use App\Jobs\GenerateJasperReport;

$jobId = \Illuminate\Support\Str::uuid();

GenerateJasperReport::dispatch([
    'job_id' => (string)$jobId,
    'template' => 'waiting_list',
    'filters' => [
        'scholarship_id' => 1,
        'status' => 'pending',
    ],
], auth()->id());

// Return job ID to client for status checking
return response()->json([
    'job_id' => (string)$jobId,
    'status' => 'queued'
]);
```

---

## Configuration

### Supported Templates

Check your `config/jasperreports.php`:

```php
'templates' => [
    'reports' => [
        'waiting_list' => 'reports/waiting-list.jrxml',
        'certificate' => 'certificates/certificate.jrxml',
        // Add your templates here
    ],
],
```

### Supported Formats

- `pdf` (default)
- `xlsx` - Excel spreadsheet
- `docx` - Word document
- `html` - HTML output

---

## Frontend Integration Examples

### Vue.js Component Example

```vue
<template>
  <div>
    <button @click="generateReport">Generate Waiting List Report</button>
    <div v-if="loading">Generating report...</div>
    <div v-if="jobId">
      <p>Job ID: {{ jobId }}</p>
      <button @click="checkStatus">Check Status</button>
      <p v-if="reportStatus">Status: {{ reportStatus.status }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: false,
      jobId: null,
      reportStatus: null,
    };
  },
  methods: {
    async generateReport() {
      this.loading = true;
      try {
        const response = await fetch('/api/jasper/report/generate-async', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            template: 'waiting_list',
            filters: {
              scholarship_id: this.$route.params.id,
            },
          }),
        });
        const data = await response.json();
        this.jobId = data.job_id;
      } finally {
        this.loading = false;
      }
    },
    async checkStatus() {
      const response = await fetch(`/api/jasper/report/status/${this.jobId}`);
      this.reportStatus = await response.json();
    },
  },
};
</script>
```

### JavaScript Example

```javascript
// Generate async report
async function generateReport() {
  const response = await fetch('/api/jasper/report/generate-async', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      template: 'waiting_list',
      filters: { scholarship_id: 1 },
    }),
  });

  const data = await response.json();
  return data.job_id;
}

// Check status periodically
async function pollStatus(jobId) {
  const response = await fetch(`/api/jasper/report/status/${jobId}`);
  const status = await response.json();
  
  if (status.status === 'completed') {
    window.location.href = `/api/jasper/reports/download?file=${status.file_path}`;
  } else if (status.status !== 'not_found') {
    setTimeout(() => pollStatus(jobId), 3000); // Poll every 3 seconds
  }
}
```

---

## Common Issues

### Issue: JasperStarter not found
**Solution:** Check your `.env` file has correct paths:
```env
JASPERSTARTER_BIN=C:\jasperstarter\lib\jasperstarter.jar
JAVA_HOME=C:\Program Files\Java\jdk-21
```

### Issue: Template not configured
**Solution:** Add template to `config/jasperreports.php`:
```php
'templates' => [
    'reports' => [
        'your_template' => 'reports/your-template.jrxml',
    ],
],
```

### Issue: Timeout on large reports
**Solution:** Use async generation (`/generate-async`) instead of sync endpoints

### Issue: Queue jobs not processing
**Solution:** Start the queue worker:
```bash
php artisan queue:work
```

---

## See Also

- [JasperReports Setup Guide](JASPERREPORTS_SETUP_GUIDE.md)
- [JasperReports Implementation Checklist](JASPERREPORTS_IMPLEMENTATION_CHECKLIST.md)
- [JasperReports Troubleshooting](JASPERREPORTS_WINDOWS_TROUBLESHOOTING.md)
