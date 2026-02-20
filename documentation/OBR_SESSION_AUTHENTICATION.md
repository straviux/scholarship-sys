# OBR API - Session & Authentication Issue

## Problem

The OBR Tracking API (`https://tracking.pgpict.com/api/obr-dv-list`) requires an **active authenticated session** from the `tracking.pgpict.com` website to return data.

When called from external sources (like your Laravel app), without the proper session cookies, it returns:
```json
{
  "data": [],
  "recordsFiltered": 0
}
```

## What We Discovered

- ✅ API endpoint is: `https://tracking.pgpict.com/api/obr-dv-list`
- ✅ API structure worked when I tested initially (returned 2 records for OBR `200-25-12-24188`)
- ❌ Now returns 0 records (likely due to session expiration or access restrictions)
- ❌ Cannot maintain session from Laravel backend directly

## Why This Happens

The tracking system at pgpict.com likely:
1. Requires user login with credentials
2. Issues session cookies upon login
3. Only returns data to authenticated, logged-in users
4. Sessions expire after inactivity

Since your Laravel app is making requests from a server (not browser), it doesn't have:
- Browser cookies
- Authenticated session
- User context

## Solutions

### Solution 1: Use Direct Links (Simplest)
For now, instead of API integration, provide direct links to tracking.pgpict.com:

```javascript
// In VoucherWizard or elsewhere
const openOBRTracking = (obrNo) => {
    const url = `https://tracking.pgpict.com/obr-tracking?type=GF&fiscal_year=2025&obrNo=${obrNo}`;
    window.open(url, '_blank');
};
```

Users open the tracking system, verify OBR details manually, then return to your app to fill in the form.

### Solution 2: Iframe Integration
Embed the tracking system in an iframe (if CORS allows):

```vue
<!-- In VoucherWizard -->
<iframe 
    src="https://tracking.pgpict.com/obr-tracking" 
    class="w-full h-96 border rounded"
    title="OBR Tracking System"
></iframe>
```

**Note**: Only works if tracking.pgpict.com allows embedding (check CORS headers).

### Solution 3: Server-Side Session Management

Create a Laravel "session proxy":

```php
// app/Http/Controllers/OBRTrackingController.php

// Cache the session cookie
private $cookieJar = null;

public function authenticate()
{
    // Use provided credentials to login to pgpict.com
    // Return authenticated with their session cookie
}

public function getOBRTracking(Request $request)
{
    // Use cached cookie jar for requests
    try {
        $response = Http::withCookies($this->cookieJar, 'tracking.pgpict.com')
            ->get('https://tracking.pgpict.com/api/obr-dv-list', $request->query());
        
        return response()->json($response->json());
    } catch (Exception $e) {
        // Handle error
    }
}
```

**Requires**: Credentials to pgpict.com system

### Solution 4: Scheduled Background Fetch & Cache

Create a Laravel job that periodically fetches and caches OBR data:

```php
// app/Jobs/SyncOBRData.php

class SyncOBRData implements ShouldQueue
{
    public function handle()
    {
        // Login to tracking.pgpict.com with credentials
        // Fetch all OBR data for current fiscal year
        // Cache it in your database
        // Return fresh data for XX hours
    }
}

// Schedule in Kernel.php
$schedule->job(new SyncOBRData)->daily();
```

This way:
- ✅ You maintain your own OBR database
- ✅ No real-time dependency on external API
- ✅ Can work offline
- ✅ Faster responses

---

## Recommended Approach

**For now, use Solution 1 (Direct Links)**

In VoucherWizard Step 2, add:

```vue
<div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
    <p class="text-sm text-blue-900 mb-3">
        <i class="pi pi-info-circle mr-2"></i>
        Need to verify OBR details?
    </p>
    <input 
        v-model="obrNumber"
        type="text"
        placeholder="Enter OBR number"
        class="w-full px-3 py-2 border border-gray-300 rounded mb-2"
    />
    <button
        @click="openOBRTracking"
        class="w-full py-2 px-3 bg-blue-600 text-white rounded hover:bg-blue-700"
    >
        <i class="pi pi-external-link mr-2"></i>
        Check in Tracking System
    </button>
</div>

<script setup>
const openOBRTracking = () => {
    if (!obrNumber.value) {
        toast.add({ severity: 'warn', summary: 'Enter OBR Number' });
        return;
    }
    window.open(
        `https://tracking.pgpict.com/obr-tracking?type=GF&fiscal_year=2025&obrNo=${obrNumber.value}`,
        '_blank'
    );
};
</script>
```

This:
- ✅ Opens their tracking system in new tab
- ✅ User verifies OBR manually
- ✅ Returns to app and fills form
- ✅ No session/auth issues
- ✅ Simple and reliable

---

## Long-Term Solution

Contact pgpict.com and request:
1. **API Key**: For server-to-server authentication
2. **CORS Configuration**: Allow cross-origin requests
3. **Service Account**: Dedicated credentials for programmatic access
4. **Webhook Support**: Real-time OBR updates (optional)

---

## Updated Test Page

The test page (`/test/obr-tracking`) now:
- ✅ Shows this warning about authentication
- ✅ Provides instructions to login to tracking.pgpict.com first
- ✅ Explains why results are empty
- ✅ Offers alternative solutions

---

## Files Updated

1. `OBRTrackingController.php`:
   - Added better error handling
   - Added SSL verification options
   - Added comments about session requirements

2. `OBRTestPage.vue`:
   - Added authentication warning banner
   - Better explanation of the situation
   - Guidance for next steps

3. Documentation files (created):
   - `OBR_SESSION_AUTHENTICATION.md` (this file)
   - Guidance for alternative approaches

---

## Testing Workaround

To test if the API works when you have a session:

1. Open: `https://tracking.pgpict.com/obr-tracking`
2. Log in (if required)
3. Do a search on their website
4. Quickly copy the Network request URL from DevTools
5. Test that URL from terminal/Postman (while session is fresh)
6. Results should appear

---

## Next Steps

Choose one:
- **Quick**: Use direct links (Solution 1) ✅
- **Medium**: Add iframe (Solution 2) - needs CORS check
- **Complex**: Build session proxy (Solution 3)
- **Best**: Set up OBR data sync job (Solution 4)

Recommend **Solution 1** (Direct Links) as temporary solution while you work with pgpict.com on API access.

---

**Status**: Authentication issue identified  
**Impact**: Test page returns 0 results without session  
**Workaround**: Direct links or iframe alternative  
**Long-term**: Request API key from pgpict.com
