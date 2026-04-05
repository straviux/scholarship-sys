<?php

namespace App\Http\Controllers;

use App\Models\ResponsibilityCenter;
use App\Models\Particular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponsibilityCenterController extends Controller
{
    /**
     * Get all responsibility centers with their particulars
     */
    public function index()
    {
        try {
            $responsibilityCenters = ResponsibilityCenter::with(['particulars.program'])
                ->orderBy('code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $responsibilityCenters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch responsibility centers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new responsibility center
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:responsibility_centers,code',
                'name' => 'required|string',
                'fiscal_year' => 'nullable|string'
            ]);

            $responsibilityCenter = ResponsibilityCenter::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Responsibility Center created successfully',
                'data' => $responsibilityCenter
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create responsibility center',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a responsibility center
     */
    public function update(Request $request, $id)
    {
        try {
            $responsibilityCenter = ResponsibilityCenter::findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|unique:responsibility_centers,code,' . $id,
                'name' => 'required|string',
                'fiscal_year' => 'nullable|string'
            ]);

            $responsibilityCenter->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Responsibility Center updated successfully',
                'data' => $responsibilityCenter
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Responsibility Center not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update responsibility center',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a responsibility center and its particulars
     */
    public function destroy($id)
    {
        try {
            \Log::info('Attempting to delete responsibility center with ID: ' . $id);

            $responsibilityCenter = ResponsibilityCenter::findOrFail($id);
            \Log::info('Found responsibility center: ' . $responsibilityCenter->name);

            // Delete associated particulars
            $deletedParticulars = $responsibilityCenter->particulars()->delete();
            \Log::info('Deleted ' . $deletedParticulars . ' particulars');

            // Delete responsibility center
            $responsibilityCenter->delete();
            \Log::info('Responsibility center deleted successfully');

            return response()->json([
                'success' => true,
                'message' => 'Responsibility Center deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('RC not found with ID: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Responsibility Center not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting RC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete responsibility center',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new particular for a responsibility center
     */
    public function storeParticular(Request $request, $id)
    {
        try {
            $responsibilityCenter = ResponsibilityCenter::findOrFail($id);

            $validated = $request->validate([
                'name'                  => 'required|string',
                'account_code'          => 'required|string',
                'scholarship_program_id' => 'required|exists:scholarship_programs,id',
                'allotment'             => 'required|numeric|min:0',
                'date_approved'         => 'nullable|date',
                'date_expired'          => 'nullable|date',
            ]);

            $particular = $responsibilityCenter->particulars()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Particular created successfully',
                'data' => $particular->load('program')
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Responsibility Center not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create particular',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a particular
     */
    public function updateParticular(Request $request, $id, $particulerId)
    {
        try {
            $responsibilityCenter = ResponsibilityCenter::findOrFail($id);
            $particular = $responsibilityCenter->particulars()->findOrFail($particulerId);

            $validated = $request->validate([
                'name'                  => 'required|string',
                'account_code'          => 'required|string',
                'scholarship_program_id' => 'required|exists:scholarship_programs,id',
                'allotment'             => 'required|numeric|min:0',
                'date_approved'         => 'nullable|date',
                'date_expired'          => 'nullable|date',
            ]);

            $particular->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Particular updated successfully',
                'data' => $particular->load('program')
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update particular',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a particular
     */
    public function destroyParticular($id, $particulerId)
    {
        try {
            $responsibilityCenter = ResponsibilityCenter::findOrFail($id);
            $particular = $responsibilityCenter->particulars()->findOrFail($particulerId);

            $particular->delete();

            return response()->json([
                'success' => true,
                'message' => 'Particular deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete particular',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
