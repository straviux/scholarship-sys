# Quick Deployment Guide - Current Report Stack

## What Changed

The report cleanup in this branch removes the old server-side Chrome and Browsershot setup for legacy applicant and interviewed-applicant reporting.

Current state:
- Selected applicant export uses the client-side Vue print and Excel flow.
- Interviewed applicant export and report preview use the client-side Vue print and Excel flow.
- Legacy report routes and dead report templates tied to the removed flow have been deleted.
- No Browsershot, Puppeteer, or Chrome path setup is required by the current codebase.

## Standard Deployment Steps

1. Pull the latest code.
2. Install PHP dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Install frontend dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```
4. Refresh Laravel caches:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Restart the web server or PHP process manager.

## Verification Checklist

- [ ] Applicant selected export opens a PDF preview/print window.
- [ ] Applicant selected export creates an Excel file.
- [ ] Interviewed applicants report preview opens successfully.
- [ ] Interviewed applicants Excel export completes successfully.
- [ ] JPM report preview still loads.
- [ ] OBR and other fund-transaction document flows still open successfully.

## Environment Notes

These legacy variables are no longer part of the current report deployment requirements and can be removed from deployment notes or environment templates if they still exist there:

```env
CHROME_PATH=
BROWSERSHOT_TIMEOUT=
BROWSERSHOT_HEADLESS=
PUPPETEER_CACHE_DIR=
```

## Troubleshooting

### Preview window does not open

- Allow pop-ups for the application host.
- Confirm the latest built frontend assets are deployed.

### Report UI loads but preview is blank

- Run `php artisan optimize:clear`.
- Rebuild assets with `npm run build`.
- Hard-refresh the browser after deployment.

### Excel export fails

- Check `storage/logs/laravel.log` for export exceptions.
- Confirm Composer dependencies installed successfully, especially `maatwebsite/excel`.

## Deployment Summary

Use the normal Laravel plus Vite deployment flow. Do not add Chrome, Puppeteer, or Browsershot setup steps for this branch.
