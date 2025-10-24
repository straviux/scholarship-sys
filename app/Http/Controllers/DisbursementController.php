<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Cheque;
use App\Models\ScholarshipProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DisbursementController extends Controller
{
    /**
     * Get disbursements for a specific profile
     */
    public function index(Request $request, $profileId)
    {
        $disbursements = Disbursement::with(['cheques', 'creator'])
            ->where('profile_id', $profileId)
            ->orderBy('date_obligated', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($disbursements);
    }

    /**
     * Store a new disbursement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:scholarship_profiles,profile_id',
            'disbursement_type' => 'required|in:regular,reimbursement,financial_assistance',
            'payee' => 'required|string|max:255',
            'obr_no' => 'nullable|string|max:255',
            'date_obligated' => 'nullable|date',
            'year_level' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        $disbursement = Disbursement::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Disbursement created successfully',
            'disbursement' => $disbursement->load(['cheques', 'creator'])
        ]);
    }

    /**
     * Update a disbursement
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'disbursement_type' => 'required|in:regular,reimbursement,financial_assistance',
            'payee' => 'required|string|max:255',
            'obr_no' => 'nullable|string|max:255',
            'date_obligated' => 'nullable|date',
            'year_level' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Disbursement updated successfully',
            'disbursement' => $disbursement->load(['cheques', 'creator'])
        ]);
    }

    /**
     * Delete a disbursement
     */
    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disbursement deleted successfully'
        ]);
    }

    /**
     * Add cheque to disbursement
     */
    public function addCheque(Request $request, $disbursementId)
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:255',
            'status' => 'required|in:pending,released,cleared,cancelled,bounced',
            'date_issued' => 'nullable|date',
            'date_released' => 'nullable|date',
            'date_cleared' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $validated['disbursement_id'] = $disbursementId;
        $validated['processed_by'] = Auth::id();

        $cheque = Cheque::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cheque added successfully',
            'cheque' => $cheque->load('processor')
        ]);
    }

    /**
     * Update cheque
     */
    public function updateCheque(Request $request, $chequeId)
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:255',
            'status' => 'required|in:pending,released,cleared,cancelled,bounced',
            'date_issued' => 'nullable|date',
            'date_released' => 'nullable|date',
            'date_cleared' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $cheque = Cheque::findOrFail($chequeId);
        $cheque->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cheque updated successfully',
            'cheque' => $cheque->load('processor')
        ]);
    }

    /**
     * Delete cheque
     */
    public function destroyCheque($chequeId)
    {
        $cheque = Cheque::findOrFail($chequeId);
        $cheque->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cheque deleted successfully'
        ]);
    }
}
