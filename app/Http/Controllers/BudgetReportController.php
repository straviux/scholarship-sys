<?php

namespace App\Http\Controllers;

use App\Models\FundTransaction;
use App\Models\ScholarshipProgram;
use App\Models\ResponsibilityCenter;
use App\Models\Particular;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetReportController extends Controller
{
    private const STATUSES = ['PAID', 'CLAIMED', 'TRANSFERRED', 'ON PROCESS', 'DENIED'];

    /**
     * JSON API endpoint for the modal report.
     */
    public function api(Request $request): JsonResponse
    {
        $request->validate([
            'program_id'   => 'required|integer|exists:scholarship_programs,id',
            'fiscal_year'  => 'required|string',
            'rc_id'        => 'required|integer|exists:responsibility_centers,id',
            'particular_id' => 'required|integer|exists:particulars,id',
        ]);

        $program    = ScholarshipProgram::findOrFail($request->integer('program_id'));
        $rc         = ResponsibilityCenter::findOrFail($request->integer('rc_id'));
        $particular = Particular::findOrFail($request->integer('particular_id'));
        $fiscalYear = $request->get('fiscal_year');

        $transactions = FundTransaction::with('creator')
            ->where('responsibility_center', $rc->code)
            ->where('particulars_name', $particular->name)
            ->where('fiscal_year', $fiscalYear)
            ->whereIn('transaction_status', self::STATUSES)
            ->orderBy('date_obligated')
            ->orderBy('created_at')
            ->get();

        $rows = $transactions->map(function ($ft) {
            return [
                'date_obligated' => $ft->date_obligated
                    ? Carbon::parse($ft->date_obligated)->format('M d, Y')
                    : ($ft->created_at ? Carbon::parse($ft->created_at)->format('M d, Y') : ''),
                'account_code'   => $ft->account_code ?? '',
                'prepared_by'    => $ft->creator?->name ?? '',
                'payee'          => $ft->payee_name ?? '',
                'particulars'    => strip_tags($ft->particulars_description ?? ''),
                'credit'         => (float) $ft->amount,
                'status'         => $ft->transaction_status,
                'obr_no'         => $ft->obr_no ?? '',
            ];
        })->values()->toArray();

        return response()->json([
            'program_name'    => $program->name,
            'fiscal_year'     => $fiscalYear,
            'rc_name'         => $rc->name,
            'rc_code'         => $rc->code,
            'particular_name' => $particular->name,
            'account_code'    => $particular->account_code ?? '',
            'allotment'       => (float) ($particular->allotment ?? 0),
            'rows'            => $rows,
            'sub_credit'      => collect($rows)->sum('credit'),
        ]);
    }

    /**
     * Cascade: RCs available for a given program + fiscal year.
     */
    public function rcenters(Request $request): JsonResponse
    {
        $request->validate([
            'program_id'  => 'required|integer|exists:scholarship_programs,id',
            'fiscal_year' => 'required|string',
        ]);

        $rcIds = Particular::where('scholarship_program_id', $request->integer('program_id'))
            ->whereHas('responsibilityCenter', fn($q) => $q->where('fiscal_year', $request->get('fiscal_year')))
            ->pluck('responsibility_center_id')
            ->unique();

        $rcs = ResponsibilityCenter::whereIn('id', $rcIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($rcs);
    }

    /**
     * Cascade: Particulars available for a given RC + program.
     */
    public function particulars(Request $request): JsonResponse
    {
        $request->validate([
            'rc_id'      => 'required|integer|exists:responsibility_centers,id',
            'program_id' => 'required|integer|exists:scholarship_programs,id',
        ]);

        $particulars = Particular::where('responsibility_center_id', $request->integer('rc_id'))
            ->where('scholarship_program_id', $request->integer('program_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'account_code', 'allotment']);

        return response()->json($particulars);
    }
}
