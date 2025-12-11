# JasperReports Implementation - Complete Setup Summary

## ✅ Implementation Complete

JasperReports integration has been successfully implemented for the scholarship system. This document summarizes what has been set up and how to use it.

---

## 📦 Files Created/Modified

### Core Configuration
- ✅ `config/jasperreports.php` - Main configuration file with all settings

### Services
- ✅ `app/Services/JasperReportDataService.php` - Data retrieval and formatting service (600+ lines)
  - Methods: `getScholarshipProfiles()`, `getScholarshipRecords()`, `getDisbursements()`
  - Export formats: JSON, CSV
  - Permission enforcement for JPM fields
  
- ✅ `app/Services/JasperReportService.php` - Core report generation service (400+ lines)
  - Template compilation and caching
  - Command building and execution
  - Multiple format support (PDF, XLSX, DOCX, HTML)
  - Error handling and logging

### Jobs & Background Processing
- ✅ `app/Jobs/GenerateJasperReport.php` - Async report generation job
  - Queue-based processing
  - Automatic retry logic
  - Callback notifications

### Controllers
- ✅ `app/Http/Controllers/ReportController.php` - Updated with new methods:
  - `generateCertificate()` - Generate approval certificates
  - `generateDisbursementForm()` - Generate batch disbursement forms
  - `generateJasperReport()` - Generic JasperReports report generation
  - `listGeneratedReports()` - List available reports
  - `downloadGeneratedReport()` - Download previous reports

### Routes
- ✅ `routes/web.php` - New JasperReports endpoints:
  ```
  POST /api/jasper/certificate
  POST /api/jasper/disbursement-form
  POST /api/jasper/report
  GET  /api/jasper/reports
  POST /api/jasper/reports/download
  ```

### Templates
- ✅ Directory structure created:
  - `storage/jasper-templates/reports/`
  - `storage/jasper-templates/certificates/`
  - `storage/jasper-templates/forms/`
  
- ✅ Sample template: `storage/jasper-templates/certificates/approval-certificate.jrxml`

### Frontend Components
- ✅ `resources/js/Components/Modals/GenerateCertificateModal.vue` - Vue 3 component for certificate generation

### Documentation
- ✅ `JASPERREPORTS_SETUP_GUIDE.md` - Complete setup and usage guide (300+ lines)
- ✅ `JASPERREPORTS_IMPLEMENTATION_SUMMARY.md` - This file

---

## 🚀 Quick Start

### 1. Prerequisites Installation

**Windows:**
```powershell
# Install Java
choco install openjdk

# Download JasperStarter from https://sourceforge.net/projects/jasperstarter/
# Extract and note the path
```

**Linux/macOS:**
```bash
sudo apt-get install openjdk-11-jdk  # Ubuntu/Debian
brew install openjdk@11               # macOS

# Download JasperStarter and extract
```

### 2. Environment Configuration

Update `.env`:
```env
JASPER_ENABLED=true
JAVA_HOME=/path/to/java
JASPERSTARTER_BIN=/path/to/jasperstarter/bin/jasperstarter
JASPER_JAVA_MEMORY=512M
JASPER_TIMEOUT=60
```

### 3. Create Directories

```bash
mkdir -p storage/jasper-templates/{reports,certificates,forms}
mkdir -p storage/jasper-{compiled,cache,output}
chmod -R 755 storage/jasper-*
```

### 4. Test Configuration

```bash
php artisan tinker
> config('jasperreports')  // Should show all settings
> exit
```

---

## 📋 Core Features Implemented

### 1. Data Export Service
- Retrieves scholarship profiles, records, and disbursements
- Filters by date range, program, school, course, municipality
- Permission-aware (hides JPM fields for unauthorized users)
- Exports to JSON, CSV formats
- Supports statistics and aggregation

### 2. Report Generation Service
- Compiles JRXML templates to Jasper format
- Manages template caching
- Builds and executes JasperStarter commands
- Supports multiple output formats
- Comprehensive error handling and logging
- Memory and timeout configuration

### 3. Async Processing
- Background job for long-running reports
- Queue-based with retry logic
- Callback notifications
- Automatic cleanup

### 4. API Endpoints
- Certificate generation
- Disbursement form generation
- Generic report generation
- Report listing and download

---

## 🎯 Usage Examples

### Generate a Certificate via API

```bash
curl -X POST http://localhost:8000/api/jasper/certificate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_id": "12345678-1234-1234-1234-123456789012",
    "template": "approval_certificate",
    "format": "pdf"
  }'
```

### Backend Service Usage

```php
$jasper = app(\App\Services\JasperReportService::class);

$path = $jasper
    ->template('approval_certificate')
    ->parameter('applicant_name', 'John Doe')
    ->parameter('course', 'BS Computer Science')
    ->parameter('approval_date', now())
    ->format('pdf')
    ->generate();

return response()->file($path);
```

### Async Report Generation

```php
\App\Jobs\GenerateJasperReport::dispatch([
    'template' => 'waiting_list',
    'format' => 'pdf',
    'filters' => ['date_from' => '2024-01-01'],
    'export_data' => true,
], auth()->id());
```

### Vue Component Usage

```vue
<script setup>
import GenerateCertificateModal from '@/Components/Modals/GenerateCertificateModal.vue';
import { ref } from 'vue';

const showCertificateModal = ref(false);
const selectedApplicant = ref(null);

const openCertificate = (applicant) => {
    selectedApplicant.value = applicant;
    showCertificateModal.value = true;
};
</script>

<template>
    <Button 
        icon="pi pi-certificate" 
        @click="openCertificate(applicant)"
    />
    
    <GenerateCertificateModal 
        :show="showCertificateModal"
        :applicant="selectedApplicant"
        @update:show="showCertificateModal = $event"
    />
</template>
```

---

## 🔧 Configuration Options

### Environment Variables

```env
# JasperReports Enable/Disable
JASPER_ENABLED=true

# Java Configuration
JAVA_HOME=/path/to/java/home
JASPER_JAVA_MEMORY=512M
JASPER_TIMEOUT=60

# JasperStarter Path
JASPERSTARTER_PATH=jasperstarter
JASPERSTARTER_BIN=/full/path/to/jasperstarter

# Data Source
JASPER_DATASOURCE_TYPE=json

# Queue Configuration
QUEUE_CONNECTION=redis  # or sync for testing
JASPER_QUEUE_TIMEOUT=300

# Logging
JASPER_LOG_QUERIES=false
JASPER_DEBUG=false
```

### Config File Options

See `config/jasperreports.php` for:
- Template paths and organization
- Output formats (PDF, XLSX, DOCX, HTML)
- Cache settings
- Performance tuning
- Retention policies

---

## 📊 Template Design

### Supported Data Parameters

From `JasperReportDataService`:

```php
// For Profiles
[
    'applicant_name',
    'date_filed',
    'municipality',
    'course',
    'school',
    'year_level',
    'father_name',        // Requires jpm.view permission
    'mother_name',        // Requires jpm.view permission
    'guardian_name',      // Requires jpm.view permission
    'is_jpm_member',      // Requires jpm.view permission
    'remarks',
    'created_by',
    // ... 50+ fields available
]
```

### Creating Custom Templates

1. **Install Jaspersoft Studio** (free visual designer)
2. **Create new report** with same parameter names as data
3. **Save as .jrxml** in `storage/jasper-templates/`
4. **Register in config** `jasperreports.php`
5. **Test via API**

---

## 🔒 Security Features

✅ **Permission Enforcement**
- Checks authorization before report generation
- Hides sensitive fields based on user permissions
- JPM fields only visible to users with `jpm.view` permission

✅ **Input Validation**
- All request inputs validated with Laravel rules
- File paths sanitized to prevent directory traversal
- Template names whitelisted in config

✅ **Error Handling**
- Exceptions caught and logged
- Sensitive data not exposed to frontend
- Proper HTTP status codes returned

✅ **Logging**
- All operations logged to `storage/logs/laravel.log`
- Debug mode available for troubleshooting
- Failed jobs tracked with retry information

---

## 📈 Performance Considerations

### Large Reports
- Use async queuing for 5000+ records
- Enable template caching (default: enabled)
- Batch process multiple reports
- Increase Java memory as needed

### Optimization Tips
```env
# For large reports
JASPER_JAVA_MEMORY=1024M
JASPER_TIMEOUT=300
QUEUE_CONNECTION=redis  # Better than sync for high volume
```

### Monitoring
```php
// Check queue jobs
php artisan queue:failed

// Retry failed jobs
php artisan queue:retry

// Monitor logs
tail -f storage/logs/laravel.log | grep -i jasper
```

---

## 🐛 Troubleshooting

### Common Issues

**"JasperStarter not found"**
- Verify `JASPERSTARTER_BIN` path in .env
- Check file exists and is executable
- On Windows: Ensure .bat extension

**"Java not found"**
- Install JDK 11+
- Set `JAVA_HOME` environment variable
- Restart terminal after changing env vars

**"Template compilation failed"**
- Validate XML syntax in .jrxml file
- Check template file permissions (readable)
- Enable debug: `JASPER_DEBUG=true`
- Check logs: `storage/logs/laravel.log`

**"Permission denied"**
- Ensure `storage/` is writable: `chmod -R 755 storage/`
- Check user permissions on template directories
- On Windows: Run with appropriate privileges

### Debug Mode

```env
JASPER_DEBUG=true
JASPER_LOG_QUERIES=true
```

Then check `storage/logs/laravel.log` for detailed information.

---

## 📚 Additional Resources

- **Setup Guide:** See `JASPERREPORTS_SETUP_GUIDE.md`
- **JasperReports Docs:** https://www.jaspersoft.com/community
- **JasperStarter:** https://sourceforge.net/projects/jasperstarter/
- **Jaspersoft Studio:** https://community.jaspersoft.com/project/jaspersoft-studio

---

## ✨ Next Steps

1. **Install Prerequisites**
   - Java 11+
   - JasperStarter
   - Configure .env

2. **Design Templates**
   - Download Jaspersoft Studio
   - Create .jrxml templates for your reports
   - Place in `storage/jasper-templates/`

3. **Register Templates**
   - Update `config/jasperreports.php`
   - Add template paths and names

4. **Test API Endpoints**
   - Use Postman or cURL
   - Start with certificate generation
   - Test various formats

5. **Frontend Integration**
   - Add buttons to Index.vue (copy from GenerateCertificateModal.vue)
   - Integrate certificate generation modal
   - Add disbursement form generation

6. **Production Deployment**
   - Set `JASPER_ENABLED=true`
   - Configure queue connection (Redis recommended)
   - Set appropriate memory limits
   - Enable logging for monitoring

---

## 📝 Summary

**Total Lines of Code Added:**
- Services: 1,000+
- Controllers: 150+
- Configuration: 100+
- Components: 100+
- Templates: 50+
- **Total: 1,400+ lines**

**Capabilities Enabled:**
✅ Certificate generation  
✅ Form generation  
✅ Report generation (PDF, XLSX, DOCX, HTML)  
✅ Async processing with queues  
✅ Permission-based filtering  
✅ Template caching and compilation  
✅ Error handling and logging  
✅ Multiple output formats  

**Ready to Use:**
- All services configured
- Sample template provided
- API endpoints documented
- Frontend component example included
- Complete setup guide provided

