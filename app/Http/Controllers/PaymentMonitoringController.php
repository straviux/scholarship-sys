<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\ScholarshipRecord;
use App\Models\FundTransaction;
use App\Models\Particular;
use Illuminate\Support\Facades\DB;

class PaymentMonitoringController extends Controller
{
    /**
     * Display a listing of payment monitoring for active scholarship records.
     */
    public function index(Request $request): Response
    {
        // Get search and filter parameters
        $searchQuery = $request->input('search', '');
        $transactionStatusFilter = $request->input('transaction_status', '');
        $academicYearFilter = $request->input('academic_year', '');
        $semesterFilter = $request->input('semester', '');
        $programFilter = $request->input('program', '');
        $courseFilter = $request->input('course', '');
        $schoolFilter = $request->input('school', '');

        // Get all active scholarship records with their profiles, courses, programs, and schools
        $records = ScholarshipRecord::where('unified_status', 'active')
            ->with([
                'profile' => function ($query) {
                    $query->select('profile_id', 'first_name', 'last_name', 'middle_name', 'extension_name', 'email');
                },
                'course' => function ($query) {
                    $query->select('id', 'name', 'scholarship_program_id');
                },
                'course.scholarshipProgram' => function ($query) {
                    $query->select('id', 'name');
                },
                'school' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->select('id', 'profile_id', 'academic_year', 'year_level', 'course_id', 'program_id', 'school_id', 'unified_status', 'term')
            ->get();

        // Map records and fetch OBR data from fund_transactions
        $paymentData = $records->flatMap(function ($record) {
            $profileId = $record->profile_id;

            // Find ALL fund_transactions where this profile_id is in the scholar_ids JSON array
            $fundTransactions = FundTransaction::where(function ($query) use ($profileId) {
                $query->whereJsonContains('scholar_ids', $profileId)
                    ->orWhereJsonContains('scholar_ids', ['profile_id' => $profileId]);
            })
                ->orderBy('created_at', 'desc')
                ->get();

            // If no OBRs, create one row with empty OBR data
            if ($fundTransactions->isEmpty()) {
                return [[
                    'id' => $record->id,
                    'profile_id' => $profileId,
                    'scholar_name' => $record->profile->full_name ?? 'N/A',
                    'record_id' => $record->id,
                    'academic_year' => $record->academic_year,
                    'year_level' => $record->year_level,
                    'term' => $record->term,
                    'unified_status' => $record->unified_status,
                    'program' => $record->course?->scholarshipProgram?->name ?? '',
                    'course' => $record->course?->name ?? '',
                    'school' => $record->school?->name ?? '',
                    // OBR data (blank if no fund transaction)
                    'obr_no' => '',
                    'transaction_status' => '',
                    'disbursement_type' => '',
                    'amount' => '',
                    'date_obligated' => '',
                    'fund_fiscal_year' => '',
                    'remarks' => '',
                ]];
            }

            // Create a row for each OBR
            return $fundTransactions->map(function ($fundTransaction) use ($record, $profileId) {
                // Get individual scholar amount from scholar_ids
                $individualAmount = '';
                if ($fundTransaction->scholar_ids) {
                    $scholarIds = is_array($fundTransaction->scholar_ids) ? $fundTransaction->scholar_ids : json_decode($fundTransaction->scholar_ids, true);

                    // Look for the current scholar's individual amount
                    foreach ($scholarIds as $scholar) {
                        if (is_array($scholar)) {
                            if (($scholar['profile_id'] ?? null) == $profileId) {
                                // Check for different possible keys: 'amount' or 'individualAmount'
                                if (isset($scholar['amount'])) {
                                    $individualAmount = $scholar['amount'];
                                    break;
                                } elseif (isset($scholar['individualAmount'])) {
                                    $individualAmount = $scholar['individualAmount'];
                                    break;
                                }
                            }
                        }
                    }

                    // If no individual amount found, fall back to transaction amount
                    if ($individualAmount === '') {
                        $individualAmount = $fundTransaction->amount ?? null;
                    }
                }

                return [
                    'id' => $record->id,
                    'profile_id' => $profileId,
                    'scholar_name' => $record->profile->full_name ?? 'N/A',
                    'record_id' => $record->id,
                    'academic_year' => $record->academic_year,
                    'year_level' => $record->year_level,
                    'term' => $record->term,
                    'unified_status' => $record->unified_status,
                    'program' => $record->course?->scholarshipProgram?->name ?? '',
                    'course' => $record->course?->name ?? '',
                    'school' => $record->school?->name ?? '',
                    // OBR data
                    'obr_no' => $fundTransaction->obr_no ?? '',
                    'obr_type' => $fundTransaction->obr_type ?? '',
                    'transaction_status' => $fundTransaction->transaction_status ?? '',
                    'disbursement_type' => $fundTransaction->disbursement_type ?? '',
                    'amount' => $individualAmount,
                    'date_obligated' => $fundTransaction->date_obligated ?? $fundTransaction->created_at ?? '',
                    'fund_fiscal_year' => $fundTransaction->fiscal_year ?? '',
                    'remarks' => $fundTransaction->remarks ?? '',
                ];
            })->toArray();
        })->values();

        // Convert to collection for filtering operations
        $paymentData = collect($paymentData);

        // ── Budget monitoring (computed before any page filters) ──────────────
        $allParticulars = Particular::with(['program', 'responsibilityCenter'])
            ->whereNotNull('scholarship_program_id')
            ->whereNotNull('allotment')
            ->get();

        $budgetParticulars = $allParticulars
            ->groupBy(fn($p) => ($p->program?->name ?? '') . '||' . ($p->responsibilityCenter?->fiscal_year ?? ''))
            ->filter(fn($items, $key) => $key !== '||' && explode('||', $key, 2)[0] !== '')
            ->map(function ($items, $key) {
                [$prog, $fy] = explode('||', $key, 2);
                return [
                    'program' => $prog,
                    'fiscal_year' => $fy,
                    'total_allotment' => (float) $items->sum('allotment'),
                ];
            })
            ->values();

        $fiscalYears = $budgetParticulars->pluck('fiscal_year')->unique()->filter()->sort()->values();

        // Build a lookup: program_id → program_name (for transactions that have scholarship_program_id)
        $programIdToName = $allParticulars
            ->filter(fn($p) => $p->scholarship_program_id && $p->program?->name)
            ->mapWithKeys(fn($p) => [$p->scholarship_program_id => $p->program->name])
            ->all();

        // Build a lookup: rc_code + particular_name → [program_name, fiscal_year]
        // (fallback for older transactions without scholarship_program_id)
        $particularsLookup = $allParticulars
            ->map(fn($p) => [
                'rc_code'         => $p->responsibilityCenter?->code,
                'particular_name' => $p->name,
                'program_name'    => $p->program?->name,
                'program_id'      => $p->scholarship_program_id,
                'fiscal_year'     => $p->responsibilityCenter?->fiscal_year,
            ])
            ->filter(fn($p) => $p['rc_code'] && $p['program_name'])
            ->values();

        // Build a lookup: [program_id][rc_code] → fiscal_year
        // Scoped to program so we don't cross-contaminate fiscal years between programs
        $programRcFiscalYears = [];
        foreach ($allParticulars as $p) {
            $pid = $p->scholarship_program_id;
            $rc  = $p->responsibilityCenter?->code;
            $fy  = $p->responsibilityCenter?->fiscal_year;
            if ($pid && $rc && $fy !== null) {
                $programRcFiscalYears[$pid][$rc] = $fy;
            }
        }

        // Pre-build profile_id → program_name map so we can infer the program for legacy
        // transactions that lack scholarship_program_id, using the actual scholars in the voucher.
        // The program() relationship is hasOneThrough(Course), so course_id must be in the select.
        $profileProgramMap = ScholarshipRecord::with('program')
            ->whereNotNull('course_id')
            ->select('profile_id', 'course_id')
            ->get()
            ->mapWithKeys(fn($r) => [$r->profile_id => $r->program?->name])
            ->filter()
            ->all();

        // Sum Paid/Claimed FundTransaction amounts directly — avoids active-scholar-only
        // limitation and the scholar_ids NULL issue from paymentData-based computation.
        $disbursedByProgramYear = [];
        FundTransaction::whereIn('transaction_status', ['Paid', 'Claimed'])
            ->select('responsibility_center', 'particulars_name', 'fiscal_year', 'amount', 'scholarship_program_id', 'scholar_ids')
            ->get()
            ->each(function ($tx) use ($particularsLookup, $programIdToName, $programRcFiscalYears, $profileProgramMap, &$disbursedByProgramYear) {
                // Prefer direct program ID match (new transactions)
                if ($tx->scholarship_program_id && isset($programIdToName[$tx->scholarship_program_id])) {
                    $prog = $programIdToName[$tx->scholarship_program_id];
                    // Infer fiscal_year from the program's own RC, not from any RC in the system
                    $fy = $tx->fiscal_year
                        ?? ($programRcFiscalYears[$tx->scholarship_program_id][$tx->responsibility_center] ?? '');
                } else {
                    // Legacy transactions without scholarship_program_id:
                    // 1st choice — infer program from the scholars stored in the voucher.
                    // This is the most accurate source because the scholars are always tied to a program.
                    $prog = null;
                    $scholarArr = is_array($tx->scholar_ids)
                        ? $tx->scholar_ids
                        : json_decode($tx->scholar_ids ?? '[]', true);
                    if (is_array($scholarArr)) {
                        foreach ($scholarArr as $s) {
                            $pid = is_array($s) ? ($s['profile_id'] ?? null) : $s;
                            if ($pid && isset($profileProgramMap[$pid])) {
                                $prog = $profileProgramMap[$pid];
                                break;
                            }
                        }
                    }

                    // 2nd choice — fall back to rc_code + particular_name match
                    if (!$prog) {
                        $match = $particularsLookup->first(
                            fn($p) => $p['rc_code'] === $tx->responsibility_center
                                && $p['particular_name'] === $tx->particulars_name
                        );
                        if (!$match) return;
                        $prog = $match['program_name'];
                        $fy   = $tx->fiscal_year ?? ($match['fiscal_year'] ?? '');
                    } else {
                        // Infer fiscal_year scoped to the inferred program's RC
                        $progId = array_search($prog, $programIdToName);
                        $fy = $tx->fiscal_year
                            ?? ($progId ? ($programRcFiscalYears[$progId][$tx->responsibility_center] ?? '') : '');
                    }
                }
                $disbursedByProgramYear[$prog][$fy] =
                    ($disbursedByProgramYear[$prog][$fy] ?? 0.0) + (float) ($tx->amount ?? 0);
            });

        // Apply search filter
        if ($searchQuery) {
            $paymentData = $paymentData->filter(function ($item) use ($searchQuery) {
                return stripos($item['scholar_name'], $searchQuery) !== false;
            });
        }

        // Apply transaction status filter
        if ($transactionStatusFilter && $transactionStatusFilter !== '') {
            $paymentData = $paymentData->filter(function ($item) use ($transactionStatusFilter) {
                if ($transactionStatusFilter === 'no-obr') {
                    // Filter for records without OBR assigned
                    return !$item['transaction_status'];
                } else {
                    // Filter for specific transaction status
                    return $item['transaction_status'] === $transactionStatusFilter;
                }
            });
        }

        // Apply academic year filter
        if ($academicYearFilter) {
            $paymentData = $paymentData->filter(function ($item) use ($academicYearFilter) {
                return $item['academic_year'] === $academicYearFilter;
            });
        }

        // Apply semester/term filter
        if ($semesterFilter) {
            $paymentData = $paymentData->filter(function ($item) use ($semesterFilter) {
                return $item['term'] === $semesterFilter;
            });
        }

        // Apply program filter
        if ($programFilter) {
            $paymentData = $paymentData->filter(function ($item) use ($programFilter) {
                return $item['program'] === $programFilter;
            });
        }

        // Apply course filter
        if ($courseFilter) {
            $courseFilter = is_array($courseFilter) ? $courseFilter : [$courseFilter];
            $courseFilter = array_filter($courseFilter); // Remove empty values
            if (count($courseFilter) > 0) {
                $paymentData = $paymentData->filter(function ($item) use ($courseFilter) {
                    return in_array($item['course'], $courseFilter);
                });
            }
        }

        // Apply school filter
        if ($schoolFilter) {
            $paymentData = $paymentData->filter(function ($item) use ($schoolFilter) {
                return $item['school'] === $schoolFilter;
            });
        }

        // Define transaction status options (enum values from disbursements.obr_status)
        $statuses = [
            ['label' => 'LOA', 'value' => 'LOA'],
            ['label' => 'IRREGULAR', 'value' => 'IRREGULAR'],
            ['label' => 'TRANSFERRED', 'value' => 'TRANSFERRED'],
            ['label' => 'CLAIMED', 'value' => 'CLAIMED'],
            ['label' => 'PAID', 'value' => 'PAID'],
            ['label' => 'ON PROCESS', 'value' => 'ON PROCESS'],
            ['label' => 'DENIED', 'value' => 'DENIED'],
        ];

        return Inertia::render('PaymentMonitoring/Index', [
            'paymentData' => $paymentData->values(),
            'availableStatuses' => $statuses,
            'budgetParticulars' => $budgetParticulars,
            'disbursedByProgramYear' => $disbursedByProgramYear,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $searchQuery,
                'transaction_status' => $transactionStatusFilter,
                'academic_year' => $academicYearFilter,
                'semester' => $semesterFilter,
                'program' => $programFilter,
                'course' => $courseFilter,
                'school' => $schoolFilter,
            ],
        ]);
    }
}
