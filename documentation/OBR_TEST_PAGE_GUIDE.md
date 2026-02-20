# OBR Tracking API Test Page Guide

## Overview

A comprehensive test page has been created to test the OBR Tracking API endpoints. It provides a user-friendly interface for testing search and filter functionality.

## Accessing the Test Page

**URL**: `http://localhost:8000/test/obr-tracking`

**Requirements**:
- Must be logged in
- Must have `system-report` role (admin access)

## Features

### 1. Search Tab
Search for OBR records by OBR number.

**How to use**:
1. Switch to "Search OBR" tab
2. Enter an OBR number (e.g., `200-25-12-24188`)
3. Click "Search" or press Enter
4. Results appear in the right panel

**Sample OBR Numbers**:
The page includes pre-filled sample numbers for quick testing:
- 200-25-12-24188
- 200-25-01-12345
- 200-25-06-98765

### 2. Advanced Filter Tab
Search with multiple filter parameters.

**Available Filters**:
| Parameter | Options |
|-----------|---------|
| Type | GF, BUR, DV |
| Fiscal Year | Numeric (e.g., 2025) |
| Sort Field | OBR Date, Payee, Amount |
| Sort Direction | Ascending, Descending |
| Page Size | Numeric (5, 10, 20, 50, etc.) |
| Page Number | Numeric (0-based) |
| OBR Number | Optional text |
| Payee | Optional payee name |

**How to use**:
1. Switch to "Advanced Filter" tab
2. Adjust filter parameters
3. Click "Apply Filter"
4. Results appear in the right panel

### 3. Results Display

**Stats Panel**:
- Shows total results found
- Displays response time in milliseconds

**Results Table**:
- Displays up to 5 results
- Shows all fields from each record
- Full field values visible on hover

**Raw Response**:
- Shows complete JSON response from API
- Useful for debugging

### 4. Export Options

**Copy to Clipboard**:
- Copies all results as formatted JSON

**Export as JSON**:
- Downloads results as a `.json` file
- Uses timestamp in filename
- Easy to share or analyze elsewhere

**Clear**:
- Clears all search results and filters
- Resets error messages

## API Endpoints

The test page queries these endpoints:

### 1. Search by OBR Number
```
GET /api/obr-tracking/search/{obrNo}
```

**Example**:
```
GET /api/obr-tracking/search/200-25-12-24188
```

**Response**:
```json
{
    "success": true,
    "data": [
        {
            "id": "...",
            "obrNumber": "200-25-12-24188",
            "payee": "...",
            "amount": 0.00,
            ...
        }
    ],
    "raw": {...}
}
```

### 2. Advanced Filter Search
```
GET /api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&payee=&obrNo=200-25-12-24188
```

**Response**: Same as search endpoint

## Testing Workflow

### Test 1: Basic Search
1. Go to `/test/obr-tracking`
2. Enter: `200-25-12-24188`
3. Click Search
4. Verify results appear in right panel
5. Check response time

### Test 2: Filter by Year
1. Switch to Advanced Filter tab
2. Set Fiscal Year to 2025
3. Increase Page Size to 50
4. Click Apply Filter
5. View all results for that year

### Test 3: Filter by Payee
1. In Advanced Filter tab
2. Enter payee name
3. Keep other filters as default
4. Click Apply Filter
5. Check filtered results

### Test 4: Pagination
1. Set Page Size to 10
2. Apply filter
3. Results limited to 10 items
4. Change Page to 1 for next page

### Test 5: Export
1. Run any search
2. Click Export (JSON)
3. File downloads to computer
4. Open in text editor or JSON viewer

## Performance Monitoring

The test page displays **response time** in milliseconds.

**Expected Performance**:
- Local API call: 50-200ms
- External service call: 200-1000ms
- Timeout: 10 seconds (if exceeds, error shown)

## Error Scenarios

### Error: OBR Not Found
```
Error: Search returned no results
```
**Solution**: Verify OBR number format and existence

### Error: Connection Timeout
```
Error: Request timeout
```
**Solution**: Check external service availability

### Error: Invalid Parameters
```
Error: Invalid request
```
**Solution**: Verify filter parameters are valid

### Error: Authentication Required
```
401 Unauthorized
```
**Solution**: Log in with valid credentials

## Browser Console Debugging

Open Developer Tools (F12) and run:

```javascript
// Test API directly
fetch('/api/obr-tracking/search/200-25-12-24188')
    .then(res => res.json())
    .then(data => console.log('Response:', data))
    .catch(err => console.error('Error:', err));

// View full response object
const testResults = [/* results from page */];
console.table(testResults);
```

## Integration with VoucherWizard

Once testing is complete, integrate into VoucherWizard:

1. Use the `useOBRTracking` composable
2. Add search field to Step 2 (Obligations)
3. Pre-populate voucher from OBR data
4. Show OBR validation error if not found

See `OBR_TRACKING_EXAMPLES.md` for implementation code.

## Logging & Debugging

The test page logs all operations to browser console:
- Search attempts
- Response data
- Error messages
- Performance metrics

Check browser DevTools → Console for details.

## Troubleshooting

### Results not showing
1. Check browser console for errors (F12)
2. Verify OBR number format
3. Check server logs for API errors
4. Verify external service is accessible

### Slow responses
1. Check network tab (F12 → Network)
2. Monitor external service performance
3. Consider caching OBR data
4. Implement retry logic for timeouts

### Export not working
1. Check browser permissions
2. Verify pop-up blocker isn't active
3. Try copying to clipboard instead
4. Check available disk space

## Next Steps

1. ✅ Test all endpoints with sample data
2. ✅ Verify error handling
3. ✅ Check response time performance
4. ✅ Export and review data structure
5. Integrate into VoucherWizard component
6. Add OBR linking to voucher system
7. Implement caching strategy
8. Set up monitoring/alerts

---

**Test Page Path**: `/test/obr-tracking`
**Status**: Ready for testing
**Last Updated**: February 20, 2026
