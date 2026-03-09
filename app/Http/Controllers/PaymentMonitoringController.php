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

        // Get all active scholarship records with their profiles
        $records = ScholarshipRecord::where('unified_status', 'active')
            ->with(['profile' => function ($query) {
                $query->select('profile_id', 'first_name', 'last_name', 'middle_name', 'extension_name', 'email');
            }])
            ->select('id', 'profile_id', 'academic_year', 'year_level', 'course_id', 'unified_status', 'term')
            ->get();

        // Map records and fetch OBR data from fund_transactions
        $paymentData = $records->map(function ($record) {
            $profileId = $record->profile_id;

            // Find fund_transactions where this profile_id is in the scholar_ids JSON array
            $fundTransaction = FundTransaction::where(function ($query) use ($profileId) {
                $query->whereJsonContains('scholar_ids', $profileId)
                    ->orWhereJsonContains('scholar_ids', ['profile_id' => $profileId]);
            })
                ->first();

            // Get individual scholar amount from scholar_ids
            $individualAmount = '';
            if ($fundTransaction && $fundTransaction->scholar_ids) {
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

                // If no individual amount found, use null/empty
                if ($individualAmount === '') {
                    $individualAmount = null;
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
                // OBR data (blank if no fund transaction)
                'obr_no' => $fundTransaction?->obr_no ?? '',
                'transaction_status' => $fundTransaction?->transaction_status ?? '',
                'amount' => $individualAmount,
                'date_obligated' => $fundTransaction?->created_at ?? '',
                'remarks' => $fundTransaction?->remarks ?? '',
            ];
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

        // Get unique transaction statuses for filter dropdown
        $statuses = FundTransaction::whereNotNull('transaction_status')
            ->distinct()
            ->pluck('transaction_status')
            ->map(function ($status) {
                return ['label' => $status, 'value' => $status];
            })
            ->toArray();

        return Inertia::render('PaymentMonitoring/Index', [
            'paymentData' => $paymentData->values(),
            'availableStatuses' => $statuses,
            'filters' => [
                'search' => $searchQuery,
                'transaction_status' => $transactionStatusFilter,
                'academic_year' => $academicYearFilter,
                'semester' => $semesterFilter,
            ],
        ]);
    }
}
