<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\FundTransaction;
use App\Models\ScholarshipProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DisbursementManagementController extends Controller
{
    /**
     * Display list of disbursements grouped by OBR with mapping status
     */
    public function index(Request $request)
    {
        $filters = $request->query();

        $query = Disbursement::query();

        // Search by OBR No
        if ($request?->get('search')) {
            $search = $request->get('search');
            $query->where('obr_no', 'like', "%{$search}%");
        }

        // Filter by OBR Status
        if ($request?->get('obr_status') && $request->get('obr_status') !== 'all') {
            $query->where('obr_status', $request->get('obr_status'));
        }

        // Filter by Academic Year
        if ($request?->get('academic_year') && $request->get('academic_year') !== 'all') {
            $query->where('academic_year', $request->get('academic_year'));
        }

        // Filter by Semester
        if ($request?->get('semester') && $request->get('semester') !== 'all') {
            $query->where('semester', $request->get('semester'));
        }

        // Filter unmapped only
        if ($request?->get('unmapped_only')) {
            $mappedObrNos = FundTransaction::pluck('obr_no')->toArray();
            $query->whereNotIn('obr_no', $mappedObrNos);
        }

        $disbursements = $query->with('profile')->orderBy('created_at', 'desc')->get();

        // Group by OBR No to show related records
        $groupedByOBR = [];
        foreach ($disbursements->groupBy('obr_no') as $obrNo => $group) {
            $firstRecord = $group->first();

            // Check if this OBR is already mapped to fund_transactions
            $existingTransaction = FundTransaction::where('obr_no', $obrNo)->first();

            $profiles = [];
            foreach ($group as $disbursement) {
                $profiles[] = [
                    'disbursement_id' => $disbursement->disbursement_id,
                    'profile_id' => $disbursement->profile_id,
                    'scholar_name' => $disbursement->profile?->full_name ?? 'Unknown',
                    'year_level' => $disbursement->year_level,
                    'amount' => (float) $disbursement->amount,
                    'remarks' => $disbursement->remarks,
                ];
            }

            $groupedByOBR[] = [
                'obr_no' => $obrNo,
                'obr_status' => $firstRecord->obr_status,
                'disbursement_type' => $firstRecord->disbursement_type,
                'date_obligated' => $firstRecord->date_obligated?->format('Y-m-d'),
                'academic_year' => $firstRecord->academic_year,
                'semester' => $firstRecord->semester,
                'total_amount' => (float) $group->sum('amount'),
                'profile_count' => $group->count(),
                'profiles' => $profiles,
                'is_mapped' => $existingTransaction ? true : false,
                'existing_transaction_id' => $existingTransaction?->id,
                'remarks' => $firstRecord->remarks,
            ];
        }

        // Get all OBR statuses for filter
        $obrStatuses = Disbursement::distinct()->pluck('obr_status')->filter()->unique()->values();
        $academicYears = Disbursement::distinct()->pluck('academic_year')->filter()->unique()->sort()->values();
        $semesters = ['1st Semester', '2nd Semester'];

        return Inertia::render('DisbursementManagement/Index', [
            'disbursements' => $groupedByOBR,
            'filters' => $filters,
            'obrStatuses' => $obrStatuses,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
        ]);
    }

    /**
     * Show mapping form for selected OBR
     */
    public function show($obrNo)
    {
        $disbursements = Disbursement::where('obr_no', $obrNo)
            ->with('profile', 'attachments')
            ->get();

        if ($disbursements->isEmpty()) {
            return back()->withErrors('OBR not found');
        }

        $existingTransaction = FundTransaction::where('obr_no', $obrNo)->first();

        // Get attachments from the first disbursement
        $firstDisbursement = $disbursements->first();
        $attachments = $firstDisbursement->attachments->map(function ($a) {
            // Check if file exists in storage
            $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($a->file_path);

            return [
                'id' => $a->attachment_id,
                'name' => $a->file_name,
                'type' => $a->file_type,
                'size' => $a->file_size,
                'path' => $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->url($a->file_path) : null,
                'attachment_type' => $a->attachment_type,
                'file_exists' => $fileExists,
                'file_path' => $a->file_path, // Include raw path for debugging
            ];
        })->toArray();

        return Inertia::render('DisbursementManagement/Create', [
            'obrNo' => $obrNo,
            'disbursements' => $disbursements->map(function ($d) {
                return [
                    'disbursement_id' => $d->disbursement_id,
                    'profile_id' => $d->profile_id,
                    'scholar_name' => $d->profile?->full_name ?? 'Unknown',
                    'year_level' => $d->year_level,
                    'amount' => $d->amount,
                    'disbursement_type' => $d->disbursement_type,
                    'obr_status' => $d->obr_status,
                    'date_obligated' => $d->date_obligated,
                    'academic_year' => $d->academic_year,
                    'semester' => $d->semester,
                    'remarks' => $d->remarks,
                ];
            })->toArray(),
            'existingTransaction' => $existingTransaction ? [
                'id' => $existingTransaction->id,
                'voucher_type' => $existingTransaction->voucher_type,
                'scholar_ids' => $existingTransaction->scholar_ids,
                'amount' => $existingTransaction->amount,
            ] : null,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Create fund transaction from disbursements
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'obr_no' => 'required|string',
            'obr_type' => 'nullable|string',
            'payee_type' => 'required|in:scholar,school,individual',
            'payee_name' => 'required|string|max:255',
            'payee_address' => 'nullable|string',
            'voucher_type' => 'required|in:disbursements,payroll',
            'scholar_ids' => 'required|array|min:1',
            'scholar_ids.*' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'explanation' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        // Get the first disbursement to pull obr_status and other details
        $firstDisbursement = Disbursement::where('obr_no', $validated['obr_no'])->first();

        if (!$firstDisbursement) {
            return back()->withErrors('OBR not found');
        }

        // Check if fund transaction already exists for this OBR
        $existingTransaction = FundTransaction::where('obr_no', $validated['obr_no'])->first();

        if ($existingTransaction) {
            // Update existing transaction
            $existingTransaction->update([
                'obr_type' => $validated['obr_type'],
                'payee_type' => $validated['payee_type'],
                'payee_name' => $validated['payee_name'],
                'payee_address' => $validated['payee_address'],
                'voucher_type' => $validated['voucher_type'],
                'scholar_ids' => $validated['scholar_ids'],
                'amount' => $validated['amount'],
                'explanation' => $validated['explanation'],
                'remarks' => $validated['remarks'],
                'transaction_status' => $firstDisbursement->obr_status,
                'date_obligated' => $firstDisbursement->date_obligated,
                'fiscal_year' => $firstDisbursement->academic_year,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('disbursement-management.index')
                ->with('success', 'Fund transaction updated successfully');
        }

        // Create new transaction
        $fundTransaction = FundTransaction::create([
            'obr_no' => $validated['obr_no'],
            'obr_type' => $validated['obr_type'],
            'payee_type' => $validated['payee_type'],
            'payee_name' => $validated['payee_name'],
            'payee_address' => $validated['payee_address'],
            'voucher_type' => $validated['voucher_type'],
            'scholar_ids' => $validated['scholar_ids'],
            'amount' => $validated['amount'],
            'explanation' => $validated['explanation'],
            'remarks' => $validated['remarks'],
            'transaction_status' => $firstDisbursement->obr_status,
            'date_obligated' => $firstDisbursement->date_obligated,
            'fiscal_year' => $firstDisbursement->academic_year,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('disbursement-management.index')
            ->with('success', 'Fund transaction created successfully');
    }
}
