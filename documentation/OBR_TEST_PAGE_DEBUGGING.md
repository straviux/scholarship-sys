# OBR Tracking API Debugging Guide

## Status

The dedicated `/test/obr-tracking` UI has been removed. Use this guide to debug the live API endpoints directly.

## Fast Checks

1. Open DevTools.
2. Use an authenticated browser session.
3. Trigger a direct request to the OBR API.
4. Inspect the network response and returned payload.

## Direct API Checks

### Search by OBR Number

```javascript
fetch('/api/obr-tracking/search/200-25-12-24188')
  .then((response) => response.json())
  .then((data) => {
    console.log('Search response:', data);
    console.table(data.data || []);
  })
  .catch((error) => console.error('Search failed:', error));
```

### Filtered Query

```javascript
fetch('/api/obr-tracking?type=GF&fiscal_year=2025&page=0&pageSize=10&obrNo=200-25-12-24188')
  .then((response) => response.json())
  .then((data) => console.log('Filtered response:', data))
  .catch((error) => console.error('Filter failed:', error));
```

## What to Verify

- HTTP status is `200`.
- `success` is `true`.
- `data` is an array.
- `recordsFiltered` is present when the filtered endpoint returns it.
- Fields such as `obr_desc`, `obr_payee_name`, `obr_amount`, and `obr_date` are populated as expected.

## Common Failures

### `401 Unauthorized`

- Re-authenticate before testing.

### Empty `data` Array

- Re-check the OBR number.
- Loosen filters such as fiscal year or type.

### Timeout or Network Error

- Retry the request.
- Check upstream OBR service availability.

### Unexpected Payload Shape

- Compare the response against the examples in `documentation/OBR_TRACKING_EXAMPLES.md`.
- Re-check the integration notes in `documentation/OBR_TRACKING_API_INTEGRATION.md`.

## Network Tab Workflow

1. Open DevTools → Network.
2. Filter by `Fetch/XHR`.
3. Repeat the request.
4. Inspect the request URL, response status, and JSON payload.

## Historical Note

Older instructions that mention `/test/obr-tracking`, raw-response panels, or test-page tabs are obsolete and should be ignored.
