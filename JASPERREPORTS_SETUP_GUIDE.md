# JasperReports Implementation Guide

## Overview

This guide provides step-by-step instructions for setting up and using JasperReports integration in the scholarship system.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Template Design](#template-design)
5. [API Usage](#api-usage)
6. [Frontend Integration](#frontend-integration)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### System Requirements

- **Java 11+** (JDK or JRE)
- **PHP 8.2+**
- **Laravel 11.9+**
- **Windows, Linux, or macOS**

### Installation

#### 1. Install Java

**Windows:**
- Download JDK from: https://www.oracle.com/java/technologies/downloads/
- Or use a package manager: `choco install openjdk`
- Set `JAVA_HOME` environment variable:
  ```powershell
  [Environment]::SetEnvironmentVariable("JAVA_HOME", "C:\Program Files\Java\jdk-21", "Machine")
  ```

**Linux/macOS:**
```bash
# Ubuntu/Debian
sudo apt-get install openjdk-11-jdk

# macOS
brew install openjdk@11
```

#### 2. Install JasperStarter

JasperStarter is a CLI tool for running JasperReports without needing a full JasperReports Server installation.

**Download:**
- Visit: https://sourceforge.net/projects/jasperstarter/
- Download the latest release (v3.6+)
- Extract to a known location

**Windows:**
```powershell
# Example: Extract to C:\jasperstarter
# Set environment variable
[Environment]::SetEnvironmentVariable("JASPERSTARTER_BIN", "C:\jasperstarter\bin\jasperstarter.bat", "Machine")
```

**Linux/macOS:**
```bash
# Extract to /opt/jasperstarter
export JASPERSTARTER_BIN="/opt/jasperstarter/bin/jasperstarter"
```

#### 3. Verify Installation

```bash
# Test Java
java -version

# Test JasperStarter
jasperstarter -v
```

---

## Configuration

### 1. Update .env File

```env
# JasperReports Configuration
JASPER_ENABLED=true
JAVA_HOME=/path/to/java (Windows: C:\Program Files\Java\jdk-21)
JASPERSTARTER_PATH=jasperstarter
JASPERSTARTER_BIN=/path/to/jasperstarter/bin/jasperstarter (or .bat on Windows)
JASPER_JAVA_MEMORY=512M
JASPER_TIMEOUT=60
JASPER_DATASOURCE_TYPE=json
JASPER_LOG_QUERIES=false
JASPER_DEBUG=false
JASPER_RETENTION_DAYS=7
JASPER_ENABLED=true
```

### 2. Verify Configuration

The config file is already created at `config/jasperreports.php` with all necessary settings.

### 3. Create Required Directories

```bash
php artisan storage:link
mkdir -p storage/jasper-templates/reports
mkdir -p storage/jasper-templates/certificates
mkdir -p storage/jasper-templates/forms
mkdir -p storage/jasper-compiled
mkdir -p storage/jasper-cache
mkdir -p storage/jasper-output
```

---

## Template Design

### 1. Install Jaspersoft Studio

Download from: https://community.jaspersoft.com/project/jaspersoft-studio

This is the visual designer for creating JasperReports templates.

### 2. Create Templates

#### Sample Template Structure

All templates go into: `storage/jasper-templates/`

**Directory Structure:**
```
storage/jasper-templates/
├── reports/
│   ├── waiting-list.jrxml
│   ├── scholarship-profile.jrxml
│   └── scholarship-summary.jrxml
├── certificates/
│   ├── approval-certificate.jrxml (already provided)
│   └── completion-certificate.jrxml
└── forms/
    ├── disbursement-voucher.jrxml
    └── batch-form.jrxml
```

#### Template Parameters

Define parameters in your .jrxml files that match backend data:

```xml
<parameter name="applicant_name" class="java.lang.String"/>
<parameter name="course" class="java.lang.String"/>
<parameter name="school" class="java.lang.String"/>
<parameter name="approval_date" class="java.util.Date"/>
<parameter name="data" class="java.util.Collection"/>
```

#### Data Source Configuration

For JSON data, templates should define:

```xml
<queryString language="json">
    <![CDATA[data]]>
</queryString>
```

For detailed template creation, refer to: https://www.jaspersoft.com/community

### 3. Register Templates

Add template configuration in `config/jasperreports.php`:

```php
'templates' => [
    'reports' => [
        'waiting_list' => 'reports/waiting-list.jrxml',
        'approval_certificate' => 'certificates/approval-certificate.jrxml',
        'disbursement_voucher' => 'forms/disbursement-voucher.jrxml',
    ],
],
```

---

## API Usage

### 1. Generate Certificate

**Endpoint:** `POST /api/jasper/certificate`

**Request:**
```json
{
    "profile_id": "uuid-here",
    "template": "approval_certificate",
    "format": "pdf"
}
```

**Response (Sync):**
```
PDF file download
```

**Response (Async):**
```json
{
    "message": "Certificate generation started.",
    "status": "queued"
}
```

### 2. Generate Disbursement Form

**Endpoint:** `POST /api/jasper/disbursement-form`

**Request:**
```json
{
    "disbursement_ids": ["uuid1", "uuid2"],
    "format": "pdf"
}
```

### 3. Generate Report

**Endpoint:** `POST /api/jasper/report`

**Request:**
```json
{
    "template": "waiting_list",
    "format": "pdf",
    "filters": {
        "date_from": "2024-01-01",
        "date_to": "2024-12-31",
        "school_ids": ["uuid1"],
        "program_ids": ["uuid2"]
    },
    "async": false
}
```

### 4. List Generated Reports

**Endpoint:** `GET /api/jasper/reports`

**Response:**
```json
{
    "reports": [
        {
            "name": "report_timestamp.pdf",
            "path": "path/to/report",
            "size": 1024000,
            "created_at": 1702300800
        }
    ]
}
```

### 5. Download Report

**Endpoint:** `POST /api/jasper/reports/download`

**Request:**
```json
{
    "filename": "report_timestamp.pdf"
}
```

---

## Frontend Integration

### 1. Add Certificate Generation Button

In `resources/js/Pages/Applicants/Index.vue`:

```vue
<Button 
    icon="pi pi-certificate" 
    severity="success" 
    size="small" 
    rounded 
    outlined
    v-tooltip.top="'Download Certificate'"
    @click="generateCertificate(slotProps.data)"
    v-if="hasPermission('applicants.view')" 
/>
```

### 2. Add Certificate Generation Handler

```javascript
import axios from 'axios';

const generateCertificate = async (applicant) => {
    try {
        const response = await axios.post('/api/jasper/certificate', {
            profile_id: applicant.profile_id,
            template: 'approval_certificate',
            format: 'pdf'
        }, {
            responseType: 'blob'
        });

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `certificate_${applicant.profile_id}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.parentElement.removeChild(link);

    } catch (error) {
        console.error('Certificate generation failed:', error);
        alert('Failed to generate certificate: ' + error.response.data.message);
    }
};
```

### 3. Add Disbursement Form Generation

```vue
<Button 
    icon="pi pi-file" 
    severity="info" 
    @click="generateDisbursementForm"
    v-if="selectedRows.length > 0 && hasPermission('disbursements.view')"
>
    Generate Disbursement Form
</Button>
```

---

## Usage Examples

### Backend Service Usage

```php
// In a Controller or Service

$jasper = app(\App\Services\JasperReportService::class);
$dataService = app(\App\Services\JasperReportDataService::class);

// Get data
$profiles = $dataService->getScholarshipProfiles([
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
]);

// Generate certificate
$certificatePath = $jasper
    ->template('approval_certificate')
    ->parameter('applicant_name', 'John Doe')
    ->parameter('course', 'BS Computer Science')
    ->parameter('school', 'State University')
    ->format('pdf')
    ->generate();
```

### Queue Job Usage

```php
// Dispatch async report generation
\App\Jobs\GenerateJasperReport::dispatch([
    'template' => 'waiting_list',
    'format' => 'pdf',
    'filters' => [],
    'parameters' => [],
], auth()->id());
```

---

## Troubleshooting

### Issue: "JasperStarter not found"

**Solution:**
1. Verify JasperStarter is installed
2. Set `JASPERSTARTER_BIN` in `.env` to full path
3. On Windows: Use `.bat` extension: `C:\path\jasperstarter.bat`
4. On Linux/macOS: Ensure executable permissions: `chmod +x /path/jasperstarter`

### Issue: "Java not found"

**Solution:**
1. Install Java JDK 11+
2. Set `JAVA_HOME` environment variable
3. Restart your terminal/IDE
4. Verify: `java -version`

### Issue: Template compilation fails

**Solution:**
1. Verify template is valid XML
2. Check template path in config
3. Ensure file exists and is readable
4. Run in debug mode: Set `JASPER_DEBUG=true`

### Issue: Timeout during report generation

**Solution:**
1. Increase `JASPER_TIMEOUT` in `.env` (in seconds)
2. Increase `JASPER_JAVA_MEMORY` (e.g., `1024M`)
3. For large datasets, use async queuing
4. Optimize template complexity

### Issue: Permission denied errors

**Solution:**
1. Ensure `storage/` directory is writable
2. Linux: `chmod -R 755 storage/`
3. Check user permissions for template directories
4. Verify file ownership

### Enable Debug Mode

```bash
# In .env
JASPER_DEBUG=true
JASPER_LOG_QUERIES=true
```

Check logs at: `storage/logs/laravel.log`

---

## Performance Optimization

### 1. Use Queue for Large Reports

```php
// Dispatch async for reports with large datasets
$async = ($dataService->getCount() > 5000);

\App\Jobs\GenerateJasperReport::dispatch($config, auth()->id());
```

### 2. Cache Compiled Templates

```env
# In .env
JASPER_CACHE_TEMPLATES=true
```

### 3. Batch Multiple Reports

```php
// Process multiple reports in a queue
collect($profiles)->chunk(500)->each(function ($chunk) {
    \App\Jobs\GenerateJasperReport::dispatch([
        'template' => 'scholarship_profile',
        'format' => 'pdf',
        'parameters' => ['data' => $chunk],
    ], auth()->id());
});
```

---

## Security Considerations

1. **Validate all user inputs** - Use Laravel request validation
2. **Check permissions** - Use gate/policy authorization
3. **Sanitize parameters** - Prevent injection attacks
4. **Limit file access** - Use signed URLs for downloads
5. **Secure templates** - Don't expose template logic to users

---

## Next Steps

1. Install Jaspersoft Studio and create your custom templates
2. Place .jrxml files in `storage/jasper-templates/`
3. Register templates in `config/jasperreports.php`
4. Test API endpoints with Postman or cURL
5. Integrate frontend components
6. Monitor logs and performance

---

## Resources

- **JasperReports Documentation:** https://www.jaspersoft.com/community
- **JasperStarter GitHub:** https://sourceforge.net/projects/jasperstarter/
- **Jaspersoft Studio:** https://community.jaspersoft.com/project/jaspersoft-studio
- **Laravel Queues:** https://laravel.com/docs/queues

