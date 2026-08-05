<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFundTransactionRequest;
use App\Http\Requests\UpdateFundTransactionRequest;
use App\Http\Requests\UpdateFundTransactionStatusRequest;
use App\Models\FundTransaction;
use App\Models\FundTransactionDocument;
use App\Services\FundTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class FundTransactionController extends Controller
{

    /**
     * Statuses that lock a record from further edits by non-administrators.
     */
    private const LOCKED_STATUSES = ['Paid', 'Claimed'];

    public function __construct(
        private FundTransactionService $service,
    ) {}

    /**
     * Whether a voucher's current status locks it from non-admin edits.
     */
    private function isLocked(FundTransaction $voucher): bool
    {
        return in_array($voucher->transaction_status, self::LOCKED_STATUSES, true);
    }

    /**
     * Store a newly created voucher.
     */
    public function store(StoreFundTransactionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if (empty($data['transaction_status'])) {
                $data['transaction_status'] = 'On Process';
            }

            $voucher = $this->service->create($data);

            return response()->json([
                'message' => 'Voucher created successfully',
                'id' => $voucher->id,
                'data' => $voucher,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating voucher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all vouchers with optional server-side filtering.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = FundTransaction::with('creator')->latest();

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('payee_name', 'like', "%{$search}%")
                        ->orWhere('disbursement_type', 'like', "%{$search}%")
                        ->orWhereHas('creator', fn($q2) => $q2->where('name', 'like', "%{$search}%"));

                    // Search scholar names stored in scholar_ids JSON objects [{name: "...", ...}]
                    // JSON_SEARCH is case-sensitive, so use LOWER() + LIKE on cast JSON for case-insensitive search
                    $q->orWhereRaw("LOWER(CAST(scholar_ids AS CHAR)) LIKE ?", ['%"name":"%' . strtolower($search) . '%"%']);
                });
            }

            if ($status = $request->get('obr_status')) {
                $query->where('transaction_status', $status);
            }

            if ($obrNoMode = $request->get('obr_no_mode')) {
                if ($obrNoMode === 'with') {
                    $query->whereRaw("TRIM(COALESCE(obr_no, '')) <> ''");
                } elseif ($obrNoMode === 'without') {
                    $query->whereRaw("TRIM(COALESCE(obr_no, '')) = ''");
                }
            }

            if ($type = $request->get('obr_type')) {
                $normalizedType = (string) Str::of((string) $type)
                    ->trim()
                    ->lower()
                    ->replaceMatches('/[\s-]+/', '_');

                $query->whereRaw(
                    "LOWER(REPLACE(REPLACE(COALESCE(obr_type, ''), ' ', '_'), '-', '_')) = ?",
                    [$normalizedType]
                );
            }

            if ($disbType = $request->get('disbursement_type')) {
                $query->where('disbursement_type', $disbType);
            }

            if ($createdBy = $request->get('created_by')) {
                $query->where('created_by', $createdBy);
            }

            if ($allocation = $request->get('allocation')) {
                $query->where('particulars_name', $allocation);
            }

            $perPage = (int) $request->get('per_page', 10);
            $paginated = $query->paginate($perPage);

            $total = FundTransaction::count();
            $myCount = Auth::id() ? FundTransaction::where('created_by', Auth::id())->count() : 0;

            return response()->json([
                'data'             => $paginated->items(),
                'total'            => $total,
                'filtered_total'   => $paginated->total(),
                'per_page'         => $paginated->perPage(),
                'current_page'     => $paginated->currentPage(),
                'last_page'        => $paginated->lastPage(),
                'my_records_count' => $myCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching vouchers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific voucher.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $voucher = FundTransaction::with(['creator', 'scholarshipProgram:id,name,shortname'])->findOrFail($id);

            return response()->json(['data' => $voucher], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fund transaction not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update a voucher.
     */
    public function update(UpdateFundTransactionRequest $request, int $id): JsonResponse
    {
        try {
            $voucher = FundTransaction::findOrFail($id);

            if ($this->isLocked($voucher) && !Auth::user()->hasRole('administrator')) {
                return response()->json([
                    'message' => 'This record is locked because it has been marked Paid or Claimed. Only an administrator can edit it.',
                ], 403);
            }

            $voucher = $this->service->update($voucher, $request->validated());

            return response()->json([
                'message' => 'Voucher updated successfully',
                'data' => $voucher,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating voucher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update only the status and remarks of a voucher.
     */
    public function updateStatus(UpdateFundTransactionStatusRequest $request, int $id): JsonResponse
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $voucher = $this->service->updateStatus($voucher, $request->validated());

            return response()->json([
                'message' => 'Transaction status updated successfully',
                'data' => $voucher,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating status for voucher ' . $id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error updating transaction status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a voucher (admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('administrator')) {
            return response()->json(['message' => 'Only administrators can delete vouchers'], 403);
        }

        try {
            $voucher = FundTransaction::findOrFail($id);
            $this->service->delete($voucher);

            return response()->json(['message' => 'Voucher deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting voucher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proxy OBR tracking info from external service.
     */
    public function getObrTrackingInfo(Request $request): JsonResponse
    {
        try {
            $client = new Client();
            $response = $client->get('https://tracking.pgpict.com/api/obr-tracking-info', [
                'query' => $request->query(),
                'timeout' => 10,
                'verify' => false,
            ]);

            $trackingData = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'data' => $trackingData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch tracking information from server. Please try again later.',
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}
