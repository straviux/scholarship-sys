# JasperReports Implementation Checklist

## ✅ Already Completed in Code

- [x] Configuration file created (`config/jasperreports.php`)
- [x] Data service created (`app/Services/JasperReportDataService.php`)
- [x] Report service created (`app/Services/JasperReportService.php`)
- [x] Async job created (`app/Jobs/GenerateJasperReport.php`)
- [x] Controller methods added to `ReportController.php`
  - [x] `generateCertificate()`
  - [x] `generateDisbursementForm()`
  - [x] `generateJasperReport()`
  - [x] `listGeneratedReports()`
  - [x] `downloadGeneratedReport()`
- [x] API routes registered (`routes/web.php`)
- [x] Sample certificate template created
- [x] Vue component template provided
- [x] Documentation created (setup guide + this file)

---

## 📋 Action Items for You

### Phase 1: Infrastructure Setup (Do First)

**Windows:**
- [ ] Download Java JDK 21: https://www.oracle.com/java/technologies/downloads/
  - Install to default location (e.g., `C:\Program Files\Java\jdk-21`)
- [ ] Set JAVA_HOME environment variable:
  - Open Settings → Environment Variables
  - Add `JAVA_HOME = C:\Program Files\Java\jdk-21`
  - Restart terminal
- [ ] Download JasperStarter 3.6+: https://sourceforge.net/projects/jasperstarter/
  - Extract to `C:\jasperstarter`
- [ ] Update `.env` in project root:
  ```env
  JASPER_ENABLED=true
  JAVA_HOME=C:\Program Files\Java\jdk-21
  JASPERSTARTER_BIN=C:\jasperstarter\bin\jasperstarter.bat
  JASPER_JAVA_MEMORY=512M
  JASPER_TIMEOUT=60
  ```

**Linux/macOS:**
- [ ] Install Java: `sudo apt-get install openjdk-11-jdk` (or `brew install openjdk@11`)
- [ ] Download JasperStarter: Extract to `/opt/jasperstarter`
- [ ] Update `.env`:
  ```env
  JASPER_ENABLED=true
  JASPERSTARTER_BIN=/opt/jasperstarter/bin/jasperstarter
  ```

### Phase 2: Directory Setup

- [ ] Create template directories:
  ```bash
  mkdir -p storage/jasper-templates/{reports,certificates,forms}
  mkdir -p storage/jasper-{compiled,cache,output}
  chmod -R 755 storage/jasper-*
  ```
- [ ] Verify PHP can write to these directories

### Phase 3: Verify Installation

- [ ] Test Java installation:
  ```bash
  java -version
  ```
- [ ] Test JasperStarter:
  ```bash
  jasperstarter -v
  ```
- [ ] Test Laravel configuration:
  ```bash
  php artisan tinker
  > config('jasperreports')  // Should show config
  > exit
  ```

### Phase 4: Design Templates (Using Jaspersoft Studio)

- [ ] Download Jaspersoft Studio (free): https://community.jaspersoft.com/project/jaspersoft-studio
- [ ] Install and open Jaspersoft Studio
- [ ] Create templates for your use cases:
  - [ ] `storage/jasper-templates/reports/waiting-list.jrxml` (for list reports)
  - [ ] `storage/jasper-templates/reports/scholarship-profile.jrxml` (for profile reports)
  - [ ] `storage/jasper-templates/forms/disbursement-voucher.jrxml` (for forms)
  - [ ] More templates as needed
  
  **For each template:**
  - [ ] Define parameters matching available data fields
  - [ ] Design layout in Jaspersoft Studio
  - [ ] Test with sample data
  - [ ] Save as `.jrxml`
  - [ ] Place in appropriate subdirectory

- [ ] Update `config/jasperreports.php` with new templates:
  ```php
  'templates' => [
      'reports' => [
          'waiting_list' => 'reports/waiting-list.jrxml',
          'scholarship_profile' => 'reports/scholarship-profile.jrxml',
          'disbursement_voucher' => 'forms/disbursement-voucher.jrxml',
      ],
  ],
  ```

### Phase 5: Test API Endpoints

- [ ] Use Postman or cURL to test endpoints:
  
  **Generate Certificate:**
  ```bash
  curl -X POST http://localhost:8000/api/jasper/certificate \
    -H "Content-Type: application/json" \
    -d '{
      "profile_id": "YOUR_UUID",
      "template": "approval_certificate",
      "format": "pdf"
    }'
  ```

  **Generate Report:**
  ```bash
  curl -X POST http://localhost:8000/api/jasper/report \
    -H "Content-Type: application/json" \
    -d '{
      "template": "waiting_list",
      "format": "pdf",
      "filters": {},
      "async": false
    }'
  ```

- [ ] Verify reports are generated and downloadable
- [ ] Check output in `storage/jasper-output/`

### Phase 6: Frontend Integration

**In `resources/js/Pages/Applicants/Index.vue`:**

- [ ] Add certificate button to Actions column:
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

- [ ] Add import for GenerateCertificateModal:
  ```javascript
  import GenerateCertificateModal from '@/Components/Modals/GenerateCertificateModal.vue';
  ```

- [ ] Add modal to template:
  ```vue
  <GenerateCertificateModal 
      :show="showCertificateModal"
      :applicant="selectedApplicant"
      @update:show="showCertificateModal = $event"
  />
  ```

- [ ] Add handler in script:
  ```javascript
  const showCertificateModal = ref(false);
  const selectedApplicant = ref(null);
  
  const generateCertificate = (applicant) => {
      selectedApplicant.value = applicant;
      showCertificateModal.value = true;
  };
  ```

- [ ] Similarly add disbursement form generation:
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

### Phase 7: Queue Configuration (Optional)

- [ ] If using async reports, configure queue:
  ```env
  # In .env
  QUEUE_CONNECTION=redis  # For production
  JASPER_QUEUE=jasper-reports
  ```

- [ ] Start queue worker:
  ```bash
  php artisan queue:work --queue=jasper-reports
  ```

### Phase 8: Production Deployment

- [ ] Ensure Java and JasperStarter are installed on production server
- [ ] Set `JASPER_ENABLED=true` in production `.env`
- [ ] Configure appropriate memory settings:
  ```env
  JASPER_JAVA_MEMORY=1024M
  JASPER_TIMEOUT=300
  ```
- [ ] Set up Redis or other queue for async processing
- [ ] Configure log rotation:
  ```bash
  sudo logrotate -f /etc/logrotate.d/laravel
  ```
- [ ] Set up cron job for cleanup:
  ```bash
  # Every day, delete reports older than 7 days
  0 0 * * * find /path/to/storage/jasper-output -type f -mtime +7 -delete
  ```

### Phase 9: Testing & Validation

- [ ] Test certificate generation:
  - [ ] Generate certificate in format: PDF
  - [ ] Download and verify content
  - [ ] Test other formats (DOCX, XLSX)

- [ ] Test report generation:
  - [ ] Generate waiting list report
  - [ ] Test with various filters
  - [ ] Verify data accuracy
  - [ ] Test async generation

- [ ] Test permission enforcement:
  - [ ] As admin: Should see all fields
  - [ ] As moderator: Should not see certain fields
  - [ ] Verify JPM fields hidden from unauthorized users

- [ ] Performance testing:
  - [ ] Generate large report (1000+ records)
  - [ ] Check memory usage
  - [ ] Monitor execution time
  - [ ] Verify queue processing

### Phase 10: Documentation & Handoff

- [ ] Document custom templates created
- [ ] Document any customizations made
- [ ] Create template modification guide for team
- [ ] Set up monitoring/alerts for report generation failures
- [ ] Train team on certificate/report generation UI

---

## 📞 Troubleshooting During Setup

### Java not found
```bash
# Verify Java is installed
java -version

# Check JAVA_HOME
echo $JAVA_HOME  # Linux/Mac
echo %JAVA_HOME%  # Windows PowerShell
```

### JasperStarter not found
```bash
# Verify JasperStarter location
ls /path/to/jasperstarter/bin/jasperstarter

# Verify it's executable
chmod +x /path/to/jasperstarter/bin/jasperstarter

# Test it
jasperstarter -v
```

### Directories permission issue
```bash
# Fix permissions
chmod -R 755 storage/jasper-*
chown -R www-data:www-data storage/jasper-*  # For Apache
```

### Template not found error
- Check file exists: `storage/jasper-templates/your-template.jrxml`
- Check it's registered in `config/jasperreports.php`
- Check template name matches config key

### Report generation times out
- Increase `JASPER_TIMEOUT` in `.env`
- Increase `JASPER_JAVA_MEMORY` in `.env`
- Use async processing for large reports

---

## 🎯 Success Criteria

You'll know it's working when:

✅ Java installation verified  
✅ JasperStarter working  
✅ Templates created and registered  
✅ API endpoints return 200 status  
✅ Reports generate successfully  
✅ Frontend buttons work  
✅ Certificates download correctly  
✅ Permission filtering works  
✅ Async queue processes jobs  
✅ No errors in `storage/logs/laravel.log`

---

## 📚 Reference Files

| File | Purpose |
|------|---------|
| `config/jasperreports.php` | All configuration |
| `app/Services/JasperReportService.php` | Report generation logic |
| `app/Services/JasperReportDataService.php` | Data retrieval logic |
| `app/Http/Controllers/ReportController.php` | API endpoints |
| `app/Jobs/GenerateJasperReport.php` | Async processing |
| `JASPERREPORTS_SETUP_GUIDE.md` | Detailed setup guide |
| `JASPERREPORTS_IMPLEMENTATION_SUMMARY.md` | Technical summary |

---

## 📞 Support

If you encounter issues:

1. **Check logs:** `tail -f storage/logs/laravel.log | grep -i jasper`
2. **Enable debug:** Set `JASPER_DEBUG=true` in `.env`
3. **Test manually:** Run JasperStarter directly to verify installation
4. **Check config:** `php artisan tinker` then `config('jasperreports')`
5. **Review guide:** See `JASPERREPORTS_SETUP_GUIDE.md` for detailed troubleshooting

---

## ✨ You're All Set!

All the backend infrastructure is ready. Now it's just a matter of:
1. Installing prerequisites
2. Creating templates
3. Testing endpoints
4. Integrating frontend

**Estimated time: 2-4 hours** (depending on how many custom templates you need)

