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
        $allParticulars = Particular::with(['program', 'programs', 'responsibilityCenter'])
            ->whereNotNull('allotment')
            ->get();

        $budgetParticulars = $allParticulars
            ->map(function ($particular) {
                $responsibilityCenter = $particular->responsibilityCenter;
                $programs = $particular->resolvedPrograms()
                    ->filter(fn($program) => filled($program?->id))
                    ->unique('id')
                    ->sortBy(fn($program) => $program->shortname ?? $program->name ?? '');

                if ($programs->isEmpty()) {
                    return null;
                }

                $fiscalYear = $responsibilityCenter?->fiscal_year;
                $rcCode = $responsibilityCenter?->code;
                $particularName = $particular->name;
                $accountCode = $particular->account_code;
                $dateStart = $particular->date_approved?->format('Y-m-d');
                $dateEnd = $particular->date_expired?->format('Y-m-d');
                $programLabels = $programs
                    ->map(fn($program) => $program->shortname ?: $program->name)
                    ->filter()
                    ->values();

                return [
                    'key' => implode('||', [
                        $fiscalYear ?? '',
                        $rcCode ?? '',
                        $accountCode ?? '',
                        $particularName ?? '',
                        $dateStart ?? '',
                        $dateEnd ?? '',
                    ]),
                    'program' => $programLabels->implode(', '),
                    'programs' => $programLabels->all(),
                    'program_ids' => $programs->pluck('id')->map(fn($id) => (int) $id)->values()->all(),
                    'fiscal_year' => $fiscalYear,
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                    'rc_name' => $responsibilityCenter?->name,
                    'rc_code' => $rcCode,
                    'account_code' => $accountCode,
                    'particular_name' => $particularName,
                    'description' => trim((string) ($particular->description ?: $particular->name)),
                    'total_allotment' => (float) ($particular->allotment ?? 0),
                ];
            })
            ->filter()
            ->groupBy(fn($row) => $row['key'] ?? '')
            ->filter(fn($items, $key) => $key !== '|||' && !empty($items->first()['program_ids'] ?? []))
            ->map(function ($items, $key) {
                $first = $items->first();
                $programs = collect($items)
                    ->flatMap(fn($item) => $item['programs'] ?? [])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
                $programIds = collect($items)
                    ->flatMap(fn($item) => $item['program_ids'] ?? [])
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'key' => $key,
                    'program' => $programs->implode(', '),
                    'programs' => $programs->all(),
                    'program_ids' => $programIds->all(),
                    'fiscal_year' => $first['fiscal_year'] ?? '',
                    'date_start' => $first['date_start'] ?? null,
                    'date_end' => $first['date_end'] ?? null,
                    'rc_name' => $first['rc_name'] ?? '',
                    'rc_code' => $first['rc_code'] ?? '',
                    'account_code' => $first['account_code'] ?? '',
                    'particular_name' => $first['particular_name'] ?? '',
                    'description' => $first['description'] ?? '',
                    'total_allotment' => (float) collect($items)->sum('total_allotment'),
                ];
            })
            ->sortBy([
                ['rc_name', 'asc'],
                ['account_code', 'asc'],
                ['description', 'asc'],
                ['date_start', 'asc'],
                ['date_end', 'asc'],
                ['fiscal_year', 'desc'],
            ])
            ->values();

        $fiscalYears = $budgetParticulars->pluck('fiscal_year')->unique()->filter()->sort()->values();

        // Build a lookup for matching paid/claimed transactions back to the originating
        // RC/account allocation. Program assignments stay on the same allocation row.
        $particularsLookup = $budgetParticulars
            ->filter(fn($row) => $row['rc_code'] && $row['particular_name'])
            ->values();

        // Build a lookup: [program_id][rc_code] → fiscal_year
        // Scoped to program so we don't cross-contaminate fiscal years between programs
        $programRcFiscalYears = [];
        foreach ($budgetParticulars as $row) {
            $rc = $row['rc_code'];
            $fy = $row['fiscal_year'];
            foreach ($row['program_ids'] ?? [] as $pid) {
                if ($pid && $rc && $fy !== null) {
                    $programRcFiscalYears[$pid][$rc] = $fy;
                }
            }
        }

        // Pre-build profile_id → program_name map so we can infer the program for legacy
        // transactions that lack scholarship_program_id, using the actual scholars in the voucher.
        // The program() relationship is hasOneThrough(Course), so course_id must be in the select.
        $profileProgramMap = ScholarshipRecord::with('program')
            ->whereNotNull('course_id')
            ->select('profile_id', 'course_id')
            ->get()
            ->mapWithKeys(fn($r) => [$r->profile_id => $r->program?->id])
            ->filter()
            ->all();

        // Sum Paid/Claimed FundTransaction amounts directly — avoids active-scholar-only
        // limitation and the scholar_ids NULL issue from paymentData-based computation.
        $disbursedByAllocation = [];
        FundTransaction::whereIn('transaction_status', ['Paid', 'Claimed'])
            ->select('responsibility_center', 'particulars_name', 'account_code', 'fiscal_year', 'date_obligated', 'created_at', 'amount', 'scholarship_program_id', 'scholar_ids')
            ->get()
            ->each(function ($tx) use ($particularsLookup, $programRcFiscalYears, $profileProgramMap, &$disbursedByAllocation) {
                $matches = $particularsLookup->filter(function ($particular) use ($tx) {
                    $accountCodeMatches = blank($tx->account_code)
                        || blank($particular['account_code'] ?? null)
                        || $particular['account_code'] === $tx->account_code;

                    return $particular['rc_code'] === $tx->responsibility_center
                        && $particular['particular_name'] === $tx->particulars_name
                        && $accountCodeMatches;
                });

                if ($tx->scholarship_program_id) {
                    $matches = $matches->filter(function ($particular) use ($tx) {
                        return in_array(
                            (int) $tx->scholarship_program_id,
                            array_map('intval', $particular['program_ids'] ?? []),
                            true
                        );
                    });

                    $fy = $tx->fiscal_year
                        ?? ($programRcFiscalYears[$tx->scholarship_program_id][$tx->responsibility_center] ?? null);
                } else {
                    $scholarArr = is_array($tx->scholar_ids)
                        ? $tx->scholar_ids
                        : json_decode($tx->scholar_ids ?? '[]', true);

                    $inferredProgramIds = collect(is_array($scholarArr) ? $scholarArr : [])
                        ->map(fn($scholar) => is_array($scholar) ? ($scholar['profile_id'] ?? null) : $scholar)
                        ->filter()
                        ->map(fn($profileId) => $profileProgramMap[$profileId] ?? null)
                        ->filter()
                        ->unique()
                        ->values();

                    if ($inferredProgramIds->isNotEmpty()) {
                        $matches = $matches->filter(function ($particular) use ($inferredProgramIds) {
                            return collect($particular['program_ids'] ?? [])
                                ->map(fn($id) => (int) $id)
                                ->intersect($inferredProgramIds)
                                ->isNotEmpty();
                        });
                    }

                    $fy = $tx->fiscal_year;
                }

                if (filled($fy)) {
                    $matches = $matches->filter(
                        fn($particular) => (string) ($particular['fiscal_year'] ?? '') === (string) $fy
                    );
                }

                $transactionDate = substr((string) ($tx->date_obligated ?: $tx->created_at ?: ''), 0, 10);
                if ($transactionDate) {
                    $matches = $matches->filter(function ($particular) use ($transactionDate) {
                        $dateStart = $particular['date_start'] ?? null;
                        $dateEnd = $particular['date_end'] ?? null;

                        if ($dateStart && $transactionDate < $dateStart) {
                            return false;
                        }

                        if ($dateEnd && $transactionDate > $dateEnd) {
                            return false;
                        }

                        return true;
                    });
                }

                if (!isset($matches) || $matches->count() !== 1) {
                    return;
                }

                $allocationKey = $matches->first()['key'] ?? null;
                if (!$allocationKey) {
                    return;
                }

                $disbursedByAllocation[$allocationKey] =
                    ($disbursedByAllocation[$allocationKey] ?? 0.0) + (float) ($tx->amount ?? 0);
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
            'disbursedByAllocation' => $disbursedByAllocation,
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
