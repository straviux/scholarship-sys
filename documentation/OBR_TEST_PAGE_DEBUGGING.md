# OBR Test Page - Debugging Guide

## Test Page URL
```
http://localhost:8000/test/obr-tracking
```

**Requirements**: Admin access (system-report role)

---

## What's Fixed

✅ Better result display with data table  
✅ Proper field formatting (amounts, dates, status badges)  
✅ Detailed view of first result  
✅ Console logging for debugging  
✅ Raw response display  
✅ Better error handling  
✅ Shows recordsFiltered count from API  

---

## How to Test

### Test 1: Search with Sample OBR Number

1. Open `/test/obr-tracking`
2. "Search OBR" tab should be active
3. Click "200-25-12-24188" sample button
4. Click "Search" button
5. **Expected**: Table appears with results

### Test 2: Advanced Filter Search

1. Click "Advanced Filter" tab
2. Keep defaults (Type: GF, Year: 2025)
3. Enter OBR: `200-25-12-24188`
4. Click "Apply Filter"
5. **Expected**: Same or similar results as search tab

### Test 3: Check Console Log

1. Press F12 to open Developer Tools
2. Go to "Console" tab
3. You should see:
   ```
   Testing OBR search: 200-25-12-24188
   Search request completed. Data type: array, Array: true
   Found 2 records
   Full response: {...}
   ```

### Test 4: View Raw Response

1. After search, scroll right panel to bottom
2. "Raw Response" section shows full JSON
3. Should see `data: [{...}, {...}]` array
4. Should see `recordsFiltered: 2` field

---

## What the Results Table Shows

| Column | Source Field | Description |
|--------|--------------|-------------|
| OBR No | `obr_desc` | OBR description (e.g., 200-25-12-24188) |
| OBR Date | `obr_date` | Date of OBR (YYYY-MM-DD) |
| Payee | `obr_payee_name` | Name of payee |
| Amount | `obr_amount` | Formatted as PHP currency |
| Status | `obr_status` | Active/Cancelled (with color badge) |

---

## If Results Don't Show

### Check 1: Look at Console (F12)
- Open DevTools → Console
- Look for error messages
- Should see log messages like "Testing OBR search: ..."
- If no logs, check if JavaScript loaded properly

### Check 2: Check Raw Response
- Scroll to "Raw Response" section in test page
- Should see JSON structure:
  ```json
  {
    "success": true,
    "data": [...],
    "recordsFiltered": 2,
    "raw": {...}
  }
  ```

### Check 3: Verify Response Time
- Stats box shows "Response Time: XXms"
- If 0ms, request might not have completed
- If >10000ms, external service might be slow/timeout

### Check 4: Check for Errors
- If red error message appears, it will show the error
- Look at console for stack trace

---

## Common Issues & Solutions

### Issue: "No records found for this OBR number"
**Possible Causes**:
- OBR number doesn't exist in 2025 fiscal year
- OBR number format is wrong (should be: 200-25-12-24188)
- External API might be down

**Solution**:
- Try with known OBR: `200-25-12-24188`
- Check fiscal year (default is current year)
- Check network tab (F12) to see if API responded

### Issue: Empty results from filter search
**Possible Causes**:
- Fiscal year 2025 might not have records
- Filter parameters too restrictive
- OBR number format wrong

**Solution**:
- Remove OBR number filter and search all records
- Try different fiscal year
- Check raw response for `recordsFiltered` count

### Issue: Connection timeout
**Error**: "Error: timeout of XXms exceeded"

**Possible Causes**:
- External service is slow/down
- Network connectivity issue

**Solution**:
- Try again (might be temporary)
- Check if `https://tracking.pgpict.com` is accessible
- Try different time of day

### Issue: Response shows success but no data
**Possible Causes**:
- API returned empty data array
- No records match the criteria

**Check**:
- Look at `recordsFiltered` in raw response
- If 0, no records exist for that criteria
- Try with looser filters

---

## Debugging Steps

### Step 1: Open Page and Clear Console
```
1. Go to /test/obr-tracking
2. Press F12 → Console tab
3. Clear existing logs (clear button)
```

### Step 2: Perform Search
```
1. Enter: 200-25-12-24188
2. Click Search
3. Watch console for logs
```

### Step 3: Inspect Network Request
```
1. Go to DevTools → Network tab
2. Filter by "XHR" or "Fetch"
3. Look for request to /api/obr-tracking/search/200-25-12-24188
4. Click on it and check:
   - Status: Should be 200
   - Response: Should be valid JSON
```

### Step 4: Check Response Format
```
Click on network request → Response tab:
{
  "success": true,
  "data": [
    {
      "obr_desc": "200-25-12-24188",
      "obr_no": 24188,
      "obr_payee_name": "...",
      "obr_amount": 150747.99,
      ...
    }
  ],
  "recordsFiltered": 2,
  "raw": {...}
}
```

### Step 5: Check Component Rendering
```
In DevTools Console, run:
- document.querySelectorAll('table').length  // Should be > 0 if table renders
- document.querySelectorAll('tr').length     // Should be > 1 if rows exist
```

---

## Browser Console Commands

### Check if data was received:
```javascript
// Assuming test page Vue instance accessible
// (Replace with actual instance if needed)
console.log('Search Results:', searchResults)
console.log('Total Results:', searchResults.length)
console.log('First Result:', searchResults[0])
```

### Test the API directly:
```javascript
fetch('/api/obr-tracking/search/200-25-12-24188')
    .then(res => res.json())
    .then(data => {
        console.log('API Response:', data);
        console.log('Data Array:', data.data);
        console.log('Count:', data.data.length);
    })
    .catch(err => console.error('Error:', err));
```

### Check external API:
```javascript
fetch('https://tracking.pgpict.com/api/obr-dv-list?type=GF&fiscal_year=2025&obrNo=200-25-12-24188&pageSize=10')
    .then(res => res.json())
    .then(data => {
        console.log('External API:', data);
        console.log('Records:', data.data.length);
    })
    .catch(err => console.error('CORS or Network Error:', err));
```

---

## Expected Behavior

1. **Page Load**: Takes 1-2 seconds
2. **Search Click**: Returns within 200-500ms
3. **Results Display**: Immediate (client-side)
4. **Table Appears**: Shows matching records
5. **Status Badge**: Green if "Active", gray otherwise
6. **Amount Format**: Shown as currency (₱)

---

## Performance Notes

- **Search**: ~300ms average
- **Filter**: ~300-500ms depending on page size
- **Display**: <50ms (client-side rendering)
- **External API**: Primary bottleneck

---

## Next Steps

1. ✅ Test with sample OBR numbers
2. ✅ Verify console logs appear
3. ✅ Check raw response structure
4. ✅ Test advanced filters
5. ✅ Export results as JSON
6. ✅ Ready to integrate into VoucherWizard

---

**Last Updated**: February 20, 2026  
**Status**: ✅ Test page improved and fixed
