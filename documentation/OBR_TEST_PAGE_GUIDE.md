# OBR Tracking API Verification Guide

## Status

The standalone `/test/obr-tracking` page has been retired. This document now covers the supported way to verify OBR Tracking behavior: call the API endpoints directly from an authenticated browser session or API client.

## Supported Verification Paths

### 1. Search by OBR Number

```http
GET /api/obr-tracking/search/{obrNo}
```

Example:

```http
GET /api/obr-tracking/search/200-25-12-24188
```

### 2. Filtered Search

```http
GET /api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&payee=&obrNo=200-25-12-24188
```

## Requirements

- Use a logged-in browser session or authenticated API client.
- Verify against known sample OBR numbers such as `200-25-12-24188`.

## Expected Response Shape

```json
{
    "success": true,
    "data": [
        {
            "id": "...",
            "obr_desc": "200-25-12-24188",
            "obr_payee_name": "...",
            "obr_amount": 0.0
        }
    ],
    "recordsFiltered": 1,
    "raw": {}
}
```

## Browser Console Check

Open Developer Tools and run:

```javascript
fetch('/api/obr-tracking/search/200-25-12-24188')
    .then((response) => response.json())
    .then((data) => {
        console.log('OBR response:', data);
        console.table(data.data || []);
    })
    .catch((error) => console.error('OBR request failed:', error));
```

## Verification Workflow

1. Confirm the request returns HTTP 200.
2. Confirm `success` is `true`.
3. Confirm `data` is an array and `recordsFiltered` matches the result count when present.
4. Confirm expected fields such as `obr_desc`, `obr_payee_name`, and `obr_amount` are populated.

## Common Failures

- `401 Unauthorized`: authenticate first.
- Empty `data` array: verify the OBR number and filter values.
- Timeout or network failure: check upstream OBR service availability.

## Related References

- `documentation/OBR_TRACKING_API_INTEGRATION.md`
- `documentation/OBR_TRACKING_EXAMPLES.md`

## Historical Note

Older references to `/test/obr-tracking` in prior troubleshooting notes are obsolete and should not be used for current verification.
