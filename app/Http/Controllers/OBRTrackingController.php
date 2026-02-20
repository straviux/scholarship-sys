<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OBRTrackingController extends Controller
{
    /**
     * Fetch OBR tracking data from external service
     * 
     * Real API Endpoint: https://tracking.pgpict.com/api/obr-dv-list
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOBRTracking(Request $request)
    {
        try {
            $query = $request->query();

            // Build the URL with query parameters
            // Real API endpoint discovered: /api/obr-dv-list
            $url = 'https://tracking.pgpict.com/api/obr-dv-list';

            // Allowed parameters
            $allowedParams = ['type', 'fiscal_year', 'sortField', 'sortDirection', 'page', 'pageSize', 'payee', 'obrNo'];
            $queryParams = array_intersect_key($query, array_flip($allowedParams));

            // Make HTTP request with cookies to maintain session
            // The external API requires authentication via cookies/session
            $response = Http::timeout(10)
                ->withoutVerifying()  // Skip SSL verification if needed
                ->acceptJson()
                ->get($url, $queryParams);

            if (!$response->successful()) {
                Log::error('OBR Tracking API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch OBR tracking data',
                    'status' => $response->status()
                ], $response->status());
            }

            // Parse the response - API returns { data: [...], recordsFiltered: number }
            $jsonData = $response->json();

            return response()->json([
                'success' => true,
                'data' => $jsonData['data'] ?? [],
                'recordsFiltered' => $jsonData['recordsFiltered'] ?? 0,
                'raw' => $jsonData
            ]);
        } catch (\Exception $e) {
            Log::error('OBR Tracking Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching OBR tracking data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search OBR by number
     * 
     * @param string $obrNo
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchOBR($obrNo)
    {
        return $this->getOBRTracking(new Request([
            'type' => 'GF',
            'fiscal_year' => date('Y'),
            'sortField' => 'obrDate',
            'sortDirection' => 'desc',
            'page' => 0,
            'pageSize' => 10,
            'obrNo' => $obrNo
        ]));
    }

    /**
     * Get detailed OBR tracking information
     * 
     * API Endpoint: https://tracking.pgpict.com/api/obr-tracking-info
     * Parameters: fiscal_year, obr_no, dv_no (optional), type
     * If dv_no is not provided, it will be fetched from obr-dv-list
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOBRTrackingInfo(Request $request)
    {
        try {
            // Validate parameters (both dv_no and type are optional)
            $validated = $request->validate([
                'fiscal_year' => 'required|numeric',
                'obr_no' => 'required|string',
                'dv_no' => 'nullable|string',
                'type' => 'nullable|string'
            ]);

            $dv_no = $validated['dv_no'] ?? '';
            $type = $validated['type'] ?? '';

            // If dv_no or type is not provided, fetch from obr-dv-list
            if (empty($dv_no) || empty($type)) {
                // Use provided type or default to 'GF' for the initial fetch
                $searchType = !empty($type) ? $type : 'GF';

                Log::info('Fetching from obr-dv-list', [
                    'obr_no' => $validated['obr_no'],
                    'fiscal_year' => $validated['fiscal_year'],
                    'search_type' => $searchType
                ]);

                // Fetch DV list for this OBR
                $dvListResponse = Http::timeout(10)
                    ->withoutVerifying()
                    ->acceptJson()
                    ->get('https://tracking.pgpict.com/api/obr-dv-list', [
                        'fiscal_year' => $validated['fiscal_year'],
                        'type' => $searchType,
                        'obrNo' => $validated['obr_no'],
                        'pageSize' => 100
                    ]);

                if ($dvListResponse->successful()) {
                    $dvListData = $dvListResponse->json();

                    // Extract the first DV number and type from the results
                    if (!empty($dvListData['data']) && is_array($dvListData['data'])) {
                        $firstEntry = $dvListData['data'][0];

                        // Get DV number if not provided
                        if (empty($dv_no)) {
                            $dv_no = $firstEntry['dv_no'] ?? $firstEntry['dv_desc'] ?? null;
                            if ($dv_no) {
                                Log::info('Fetched DV number from obr-dv-list', ['dv_no' => $dv_no]);
                            }
                        }

                        // Get type if not provided
                        if (empty($type)) {
                            $type = $firstEntry['type'] ?? $firstEntry['obr_type'] ?? $searchType ?? null;
                            if ($type) {
                                Log::info('Fetched type from obr-dv-list', ['type' => $type]);
                            }
                        }
                    }
                }
            }

            // Ensure type has a value before proceeding
            if (empty($type)) {
                $type = 'GF'; // Final fallback to 'GF' if still empty
            }

            // Build the URL with query parameters
            $url = 'https://tracking.pgpict.com/api/obr-tracking-info';

            // Prepare parameters for tracking info endpoint
            $trackingParams = [
                'fiscal_year' => $validated['fiscal_year'],
                'obr_no' => $validated['obr_no'],
                'dv_no' => $dv_no,
                'type' => $type
            ];

            Log::info('Calling OBR tracking-info endpoint', [
                'params' => $trackingParams
            ]);

            // Make HTTP request
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->acceptJson()
                ->get($url, $trackingParams);

            if (!$response->successful()) {
                Log::error('OBR Tracking Info API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'params' => $trackingParams
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch OBR tracking info. Please verify the OBR number and fiscal year are correct.',
                    'status' => $response->status(),
                    'params' => $trackingParams
                ], $response->status());
            }

            // Parse the response
            $jsonData = $response->json();

            return response()->json([
                'success' => true,
                'data' => $jsonData,
                'raw' => $jsonData,
                'used_dv_no' => $dv_no,  // Return which DV number was used
                'used_type' => $type      // Return which type was used
            ]);
        } catch (\Exception $e) {
            Log::error('OBR Tracking Info Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching OBR tracking info: ' . $e->getMessage()
            ], 500);
        }
    }
}
