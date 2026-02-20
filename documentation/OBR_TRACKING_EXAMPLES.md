// Quick Integration Example for VoucherWizard
// Add these imports and functions to your VoucherWizard.vue component

// ============ IMPORT SECTION (add to top of script) ============
// import { useOBRTracking } from '@/composables/useOBRTracking';

// ============ IN SCRIPT SETUP SECTION ============

// Initialize OBR tracking composable
// const { obrData, searchOBR, mapOBRToVoucher } = useOBRTracking();

// Add to reactive data section
// const obrSearch = ref({
//     query: '',
//     showResults: false
// });

// ============ FUNCTION EXAMPLES ============

/**
 * Example 1: Search OBR in Obligation step
 * Use Case: Pre-fill voucher data from existing OBR
 */
// const handleOBRSearch = async () => {
//     if (!obrSearch.value.query.trim()) {
//         toast.add({
//             severity: 'warn',
//             summary: 'Warning',
//             detail: 'Please enter an OBR number',
//             life: 3000
//         });
//         return;
//     }

//     await searchOBR(obrSearch.value.query);

//     if (!obrData.error && obrData.items.length > 0) {
//         // Auto-populate voucher with first OBR result
//         mapOBRToVoucher(obrData.items[0], voucherData);
//         obrSearch.value.showResults = true;
//         toast.add({
//             severity: 'success',
//             summary: 'Success',
//             detail: `OBR data loaded: ${obrData.items.length} record(s)`,
//             life: 3000
//         });
//     } else {
//         toast.add({
//             severity: 'error',
//             summary: 'Error',
//             detail: obrData.error || 'No OBR records found',
//             life: 5000
//         });
//     }
// };

/**
 * Example 2: Validate OBR Number on Blur
 * Use Case: Real-time validation in obligation step
 */
// const validateOBRNumber = async (obrNumber) => {
//     if (obrNumber && obrNumber.length > 3) {
//         try {
//             await axios.get(`/api/obr-tracking/search/${obrNumber}`);
//             // OBR exists
//             logger.info(`OBR ${obrNumber} validated`);
//         } catch (err) {
//             logger.warn(`OBR ${obrNumber} not found in tracking system`);
//         }
//     }
// };

/**
 * Example 3: Add OBR Search UI to Step 2
 * Insert this in the template where you want the OBR search
 */
// <div class="mt-4 pt-4 border-t border-gray-300">
//     <label class="block text-sm font-medium text-gray-700 mb-2">
//         Search Existing OBR (Optional)
//     </label>
//     <div class="flex gap-2">
//         <input 
//             v-model="obrSearch.query"
//             type="text"
//             placeholder="Enter OBR number (e.g., 200-25-12-24188)"
//             class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
//         />
//         <button
//             @click="handleOBRSearch"
//             :disabled="obrData.loading"
//             class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
//         >
//             <i v-if="obrData.loading" class="pi pi-spin pi-spinner mr-2"></i>
//             Search
//         </button>
//     </div>

//     <!-- OBR Search Results -->
//     <div v-if="obrSearch.showResults && obrData.items.length > 0" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
//         <p class="text-sm text-green-900 mb-2">
//             Found {{ obrData.items.length }} OBR record(s). Data auto-populated above.
//         </p>
//         <div v-for="item in obrData.items.slice(0, 3)" :key="item.id" class="text-xs text-green-800 mb-1">
//             <i class="pi pi-check text-green-600 mr-1"></i> 
//             {{ item.obrNumber || item.number }} - {{ item.payee }}
//         </div>
//     </div>

//     <!-- OBR Error -->
//     <div v-if="obrData.error" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
//         {{ obrData.error }}
//     </div>
// </div>

// ============ TESTING EXAMPLES ============

/**
 * Test in browser console:
 * 
 * // Test search OBR
 * fetch('/api/obr-tracking/search/200-25-12-24188')
 *     .then(res => res.json())
 *     .then(data => console.log('OBR Data:', data))
 *     .catch(err => console.error('Error:', err));
 * 
 * // Test with filters
 * const params = new URLSearchParams({
 *     type: 'GF',
 *     fiscal_year: 2025,
 *     sortField: 'obrDate',
 *     sortDirection: 'desc',
 *     page: 0,
 *     pageSize: 10,
 *     obrNo: '200-25-12-24188'
 * });
 * fetch(`/api/obr-tracking?${params}`)
 *     .then(res => res.json())
 *     .then(data => console.log('OBR Results:', data));
 */

// ============ CURL EXAMPLES (for testing in terminal) ============

/*
# Search single OBR
curl -X GET "http://localhost:8000/api/obr-tracking/search/200-25-12-24188"

# With custom filters
curl -X GET "http://localhost:8000/api/obr-tracking?type=GF&fiscal_year=2025&sortField=obrDate&sortDirection=desc&page=0&pageSize=10&obrNo=200-25-12-24188"

# Using Windows PowerShell
$uri = "http://localhost:8000/api/obr-tracking/search/200-25-12-24188"
$response = Invoke-RestMethod -Uri $uri -Method Get
$response | ConvertTo-Json | Write-Host
*/

// ============ IMPLEMENTATION CHECKLIST ============
/*
☐ 1. Verify OBRTrackingController.php exists in app/Http/Controllers/
☐ 2. Verify OBR routes added to routes/api.php
☐ 3. Create useOBRTracking.js composable in resources/js/composables/
☐ 4. Test API endpoints:
     - GET /api/obr-tracking/search/{obrNo}
     - GET /api/obr-tracking?type=GF&fiscal_year=2025&...
☐ 5. Import composable in VoucherWizard.vue
☐ 6. Add OBR search UI to Step 2
☐ 7. Test search functionality
☐ 8. Verify data mapping to voucher fields
☐ 9. Add error handling and user feedback
☐ 10. Test in create and edit modes
*/
