# OBR Tracking - Finding the Real API Endpoint

## Analysis

The link `https://tracking.pgpict.com/obr-tracking?type=GF&fiscal_year=2025&...` is a **web interface**, not a direct API endpoint. The actual API data fetching happens behind the scenes via JavaScript.

## How to Find the Real API Endpoint

### Method 1: Browser Developer Tools (Recommended)

**Steps**:
1. Visit: `https://tracking.pgpict.com/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&payee=&obrNo=200-25-12-24188`
2. Open **Developer Tools** (F12 or Ctrl+Shift+I)
3. Go to **Network** tab
4. **Reload the page** (F5)
5. Look for XHR/Fetch requests (filter by "XHR" on top)
6. The actual API endpoint will be listed (e.g., `/api/obr-tracking` or similar)
7. Click on the request to see:
   - Request URL
   - Request method (GET/POST)
   - Headers
   - Query parameters
   - Response format

### Method 2: Inspect Page Source

1. Right-click on page → "View Page Source" (Ctrl+U)
2. Search for:
   - `fetch(` or `axios.get(` or `$.ajax(`
   - `https://` or `/api/`
   - API endpoint URLs
3. Note the endpoint structure

### Method 3: Search Network for "obr"

In Developer Tools → Network tab:
- Search for requests containing "obr"
- Look for JSON responses
- Check the URL pattern

## Common API Endpoint Patterns

Based on the link structure, the real API likely uses one of these patterns:

```
# Pattern 1: RESTful endpoint with query params
GET https://tracking.pgpict.com/api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10

# Pattern 2: Abbreviated path
GET https://tracking.pgpict.com/api/obr?type=GF&fiscal_year=2025&...

# Pattern 3: Dashboard/data endpoint
GET https://tracking.pgpict.com/api/dashboard/obr-data?type=GF&fiscal_year=2025&...

# Pattern 4: Search endpoint
POST https://tracking.pgpict.com/api/search
Body: { type: "GF", fiscal_year: 2025, ... }

# Pattern 5: GraphQL
POST https://tracking.pgpict.com/graphql
Body: { query: "...", variables: {...} }
```

## What to Look For in Network Request

Once you find the actual API request, note:

```
URL: https://tracking.pgpict.com/[API_PATH]
Method: GET or POST
Headers:
  - Authorization: Bearer token (if needed)
  - Content-Type: application/json
  - X-Requested-With: XMLHttpRequest

Query Parameters (if GET):
  - type=GF
  - fiscal_year=2025
  - sortField=obrDate
  - sortDirection=desc
  - page=0
  - pageSize=10

Response Format:
  - JSON object with "data", "items", "results", etc.
  - Total count/pagination info
  - Metadata about the results
```

## Example Network Investigation

**Expected request in Network tab**:
```
Name: obr-tracking (or similar)
Status: 200 (or other success code)
Type: xhr
Initiator: fetch or XMLHttpRequest
Size: 2.5 KB (varies)
Time: 150ms (varies)
```

**Clicking on it shows**:
- Headers tab: Request/response headers
- Preview tab: Formatted response
- Response tab: Raw response
- Cookies tab: Any session cookies

## Once You Find the Endpoint

### Update the Controller

```php
// app/Http/Controllers/OBRTrackingController.php

public function getOBRTracking(Request $request)
{
    try {
        // Replace with actual API endpoint
        $apiUrl = 'https://tracking.pgpict.com/api/obr-tracking'; // FIND THIS
        
        $response = Http::timeout(10)->get($apiUrl, $request->query());
        
        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch OBR tracking data',
                'status' => $response->status()
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
```

### Test with cURL

Once you have the real endpoint:

```bash
# Test the real API directly
curl -X GET "https://tracking.pgpict.com/api/obr-tracking?type=GF&fiscal_year=2025&page=0&pageSize=10"

# With full parameters
curl -X GET "https://tracking.pgpict.com/api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&obrNo=200-25-12-24188"
```

### Test in Browser Console

```javascript
// Test the real API
fetch('https://tracking.pgpict.com/api/obr-tracking?type=GF&fiscal_year=2025&page=0&pageSize=10')
    .then(res => res.json())
    .then(data => console.log('API Response:', data))
    .catch(err => console.error('Error:', err));
```

## Possible Response Structures

### Response Type 1: Array of OBR Objects
```json
[
  {
    "id": "...",
    "obrNumber": "200-25-12-24188",
    "obrDate": "2025-12-24",
    "payee": "...",
    "amount": 50000.00,
    "type": "GF",
    "status": "APPROVED"
  },
  ...
]
```

### Response Type 2: Paginated Response
```json
{
  "data": [
    {
      "id": "...",
      "obrNumber": "200-25-12-24188",
      ...
    }
  ],
  "pagination": {
    "total": 150,
    "page": 0,
    "pageSize": 10,
    "totalPages": 15
  }
}
```

### Response Type 3: Wrapped Response
```json
{
  "success": true,
  "message": "Success",
  "data": [...]
}
```

## Checklist for Finding API

- [ ] Open site in browser: https://tracking.pgpict.com/obr-tracking
- [ ] Open Developer Tools (F12)
- [ ] Go to Network tab
- [ ] Type in search: "200-25-12-24188"
- [ ] Click Search button
- [ ] Look for XHR/Fetch requests
- [ ] Click on the request with OBR data
- [ ] Note the Request URL
- [ ] Check the Response format
- [ ] Copy the endpoint URL
- [ ] Test with cURL or Postman
- [ ] Update OBRTrackingController with real endpoint
- [ ] Test in your application

## Authentication Check

The API might require:
- **No auth** (public)
- **API Key** in header
- **Bearer token** (from login)
- **Session cookie** (already authenticated)
- **CSRF token**

Check Network tab → Headers to see what's being sent.

## Next Steps

1. **Identify the real API endpoint** using Developer Tools
2. **Test the endpoint** with cURL or Postman
3. **Document the response format**
4. **Update OBRTrackingController.php** with the correct endpoint
5. **Test your Laravel wrapper API**
6. **Integrate into VoucherWizard**

---

Once you identify the real endpoint, share it and I'll update the implementation.
