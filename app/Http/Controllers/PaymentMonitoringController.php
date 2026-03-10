<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\ScholarshipRecord;
use App\Models\FundTransaction;
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
                    'voucher_type' => '',
                    'amount' => '',
                    'date_obligated' => '',
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
                    'transaction_status' => $fundTransaction->transaction_status ?? '',
                    'voucher_type' => $fundTransaction->voucher_type ?? '',
                    'amount' => $individualAmount,
                    'date_obligated' => $fundTransaction->date_obligated ?? $fundTransaction->created_at ?? '',
                    'remarks' => $fundTransaction->remarks ?? '',
                ];
            })->toArray();
        })->values();

        // Convert to collection for filtering operations
        $paymentData = collect($paymentData);

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
