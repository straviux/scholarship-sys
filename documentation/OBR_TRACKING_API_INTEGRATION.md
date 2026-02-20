# OBR Tracking API Integration Guide

## Overview
This guide explains how to integrate the OBR (Obligation Request) tracking API endpoint into your application.

## Backend Setup

### Controller
The `OBRTrackingController` has been created at:
- **File**: `app/Http/Controllers/OBRTrackingController.php`
- **Methods**:
  - `getOBRTracking()` - Generic OBR tracking fetch with query parameters
  - `searchOBR($obrNo)` - Search OBR by number

### API Routes
Registered in `routes/api.php`:

```php
// Get OBR tracking data with custom filters
GET /api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&obrNo=200-25-12-24188

// Search OBR by number
GET /api/obr-tracking/search/{obrNo}
```

## Allowed Query Parameters

| Parameter | Type | Default | Example |
|-----------|------|---------|---------|
| `type` | string | GF | GF, BUR, etc. |
| `fiscal_year` | integer | Current year | 2025 |
| `sortField` | string | obrDate | obrDate, payee, etc. |
| `sortDirection` | string | desc | asc, desc |
| `page` | integer | 0 | 0, 1, 2... |
| `pageSize` | integer | 10 | 5, 10, 20, 50... |
| `payee` | string | - | Payee name |
| `obrNo` | string | - | OBR number |

## Frontend Usage (Vue 3)

### Basic Example - Fetch with Parameters

```javascript
// In your Vue component
import axios from 'axios';

const fetchOBRTracking = async (filters) => {
    try {
        const response = await axios.get('/api/obr-tracking', {
            params: {
                type: filters.type || 'GF',
                fiscal_year: filters.fiscal_year || new Date().getFullYear(),
                sortField: filters.sortField || 'obrDate',
                sortDirection: filters.sortDirection || 'desc',
                page: filters.page || 0,
                pageSize: filters.pageSize || 10,
                obrNo: filters.obrNo || '',
                payee: filters.payee || ''
            }
        });
        
        console.log('OBR Data:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error fetching OBR tracking:', error);
        throw error;
    }
};
```

### Search by OBR Number

```javascript
const searchOBRByNumber = async (obrNumber) => {
    try {
        const response = await axios.get(`/api/obr-tracking/search/${obrNumber}`);
        console.log('Search result:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error searching OBR:', error);
        throw error;
    }
};
```

### In VoucherWizard Component

Add this to the VoucherWizard to integrate OBR tracking:

```javascript
// Add to script section
import axios from 'axios';

// Add state
const obrTracking = ref([]);
const obrSearchQuery = ref('');
const obrLoading = ref(false);

// Add function
const fetchOBRData = async (obrNo) => {
    obrLoading.value = true;
    try {
        const response = await axios.get(`/api/obr-tracking/search/${obrNo}`);
        obrTracking.value = response.data.data || [];
        
        // Optionally populate voucher data from OBR
        if (obrTracking.value.length > 0) {
            const obrData = obrTracking.value[0];
            
            // Map OBR data to voucher fields
            voucherData.obligations.payee_name = obrData.payeeName || '';
            voucherData.obligations.account_code = obrData.accountCode || '';
            // ... map other fields as needed
        }
    } catch (error) {
        console.error('Error fetching OBR:', error);
    } finally {
        obrLoading.value = false;
    }
};
```

## Response Format

### Success Response
```json
{
    "success": true,
    "data": "HTML content or parsed data",
    "raw": {
        "items": [...],
        "total": 2,
        "page": 0,
        "pageSize": 10
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message here",
    "status": 500
}
```

## Testing

### Using Postman or cURL

```bash
# Search by OBR number
curl -X GET "http://localhost:8000/api/obr-tracking/search/200-25-12-24188"

# With custom parameters
curl -X GET "http://localhost:8000/api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&obrNo=200-25-12-24188"
```

### Using FastAPI or Direct HTTP

```javascript
// In browser console
fetch('/api/obr-tracking/search/200-25-12-24188')
    .then(res => res.json())
    .then(data => console.log(data))
    .catch(err => console.error(err));
```

## Error Handling

The controller includes comprehensive error handling:

- ✓ HTTP timeout (10 seconds)
- ✓ Failed request status checking
- ✓ Exception logging
- ✓ Validation of query parameters
- ✓ User-friendly error messages

## Security Considerations

1. **Parameters Validation**: Only allowed parameters are passed to external service
2. **Authentication**: Routes protected by `web` middleware (requires user login)
3. **Error Logging**: Errors logged for auditing
4. **Timeout**: 10-second timeout to prevent hanging requests
5. **CORS**: Ensure CORS is properly configured if needed

## Integration Points

### Voucher Creation/Edit
- Pre-fill voucher data from OBR tracking
- Link OBR number to voucher record
- Show OBR status alongside voucher status

### Obligation Details
- Validate OBR existence before creating obligation
- Display OBR details in preview panel
- Suggest account codes from OBR data

## Troubleshooting

### Issue: "Failed to fetch OBR tracking data"
**Solution**: 
- Check external service is accessible: `https://tracking.pgpict.com/`
- Verify network connectivity
- Check server error logs for details

### Issue: Empty response
**Solution**:
- Verify OBR number format (e.g., 200-25-12-24188)
- Check fiscal year parameter
- Try with fewer filter parameters

### Issue: Timeout errors
**Solution**:
- External service may be slow
- Increase timeout in controller (currently 10 seconds)
- Implement retry logic with exponential backoff

## Future Enhancements

1. Cache OBR data for performance
2. Add background sync for OBR status
3. Implement webhook for real-time updates
4. Add OBR number validation on the fly
5. Create OBR detailed view modal
