<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFundTransactionRequest;
use App\Http\Requests\UpdateFundTransactionRequest;
use App\Http\Requests\UpdateFundTransactionStatusRequest;
use App\Models\FundTransaction;
use App\Models\FundTransactionDocument;
use App\Services\FundTransactionService;
use App\Traits\ManagesChromeForPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Client;
use Spatie\Browsershot\Browsershot;

class FundTransactionController extends Controller
{
    use ManagesChromeForPdf;

    public function __construct(
        private FundTransactionService $service,
    ) {}

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
                $query->where('obr_status', $status);
            }

            if ($type = $request->get('obr_type')) {
                $query->where('obr_type', $type);
            }

            if ($disbType = $request->get('disbursement_type')) {
                $query->where('disbursement_type', $disbType);
            }

            if ($createdBy = $request->get('created_by')) {
                $query->where('created_by', $createdBy);
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
            $voucher = FundTransaction::with('creator')->findOrFail($id);

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
     * Generate OBR PDF using Browsershot.
     */
    public function generateOBRPdf(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $html = view('vouchers.obr', ['voucher' => $voucher])->render();

            $browsershot = Browsershot::html($html);
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->format('A4')->margins(0, 0, 0, 0);
            $pdf = $browsershot->pdf();

            $filename = 'OBR-' . $voucher->transaction_id . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate OBR Excel using Maatwebsite Excel.
     */
    public function generateOBRExcel(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $filename = 'OBR-' . $voucher->transaction_id . '.xlsx';

            return Excel::download(
                new \App\Exports\VoucherOBRExport($voucher),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate DV (Disbursement Voucher) PDF using Browsershot.
     */
    public function generateDVPdf(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $html = view('vouchers.disbursement', ['voucher' => $voucher])->render();

            $browsershot = Browsershot::html($html);
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)->paperSize(216, 330, 'mm');
            $pdf = $browsershot->pdf();

            $filename = 'DV-' . $voucher->transaction_id . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate DV (Disbursement Voucher) Excel using Maatwebsite Excel.
     */
    public function generateDVExcel(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $filename = 'DV-' . $voucher->transaction_id . '.xlsx';

            return Excel::download(
                new \App\Exports\VoucherOBRExport($voucher),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Payroll PDF using Browsershot.
     */
    public function generatePayrollPdf(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $html = view('vouchers.payroll', ['voucher' => $voucher])->render();

            $browsershot = Browsershot::html($html);
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)
                ->paperSize(330, 215.9, 'mm')
                ->showBackground()
                ->printBackground();

            $pdf = $browsershot->pdf();

            $filename = 'Payroll-' . $voucher->transaction_id . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate List of Scholars PDF using Browsershot.
     */
    public function generateListOfScholarsPdf(int $id)
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $html = view('vouchers.list_of_scholars', ['voucher' => $voucher])->render();

            $browsershot = Browsershot::html($html);
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)
                ->paperSize(210, 297, 'mm')
                ->showBackground()
                ->printBackground();

            $pdf = $browsershot->pdf();

            $filename = 'ListOfScholars-' . $voucher->transaction_id . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a document for a fund transaction.
     */
    public function uploadDocument(Request $request, int $id): JsonResponse
    {
        try {
            $voucher = FundTransaction::findOrFail($id);

            $validated = $request->validate([
                'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
                'document_type' => ['required', 'in:obr,dv_payroll,los,cheque'],
            ]);

            $document = $this->service->uploadDocument(
                $voucher,
                $request->file('document'),
                $validated['document_type']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $document->id,
                    'document_type' => $validated['document_type'],
                    'filename' => $document->filename,
                    'path' => $document->path,
                    'file_size' => $document->file_size,
                    'qr_code' => $document->qr_code,
                    'verified' => false,
                    'uploaded_at' => $document->created_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document for a fund transaction.
     */
    public function downloadDocument(int $id, string $docType)
    {
        $validDocTypes = ['obr', 'dv_payroll', 'los', 'cheque'];
        if (!in_array($docType, $validDocTypes)) {
            return response()->json(['message' => 'Invalid document type'], 400);
        }

        try {
            FundTransaction::findOrFail($id);

            $documentPath = storage_path('app/documents/fund-transactions/' . $id);

            if (!file_exists($documentPath)) {
                return response()->json(['message' => 'Document not found'], 404);
            }

            $files = glob($documentPath . '/' . $docType . '_*');

            if (empty($files)) {
                return response()->json(['message' => 'Document not found'], 404);
            }

            $filePath = $files[0];
            $filename = basename($filePath);
            $mimeType = mime_content_type($filePath);
            $fileContent = file_get_contents($filePath);

            return response($fileContent)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', strlen($fileContent))
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error downloading document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a document for a fund transaction.
     */
    public function deleteDocument(int $id, string $docType): JsonResponse
    {
        $validDocTypes = ['obr', 'dv_payroll', 'los', 'cheque'];
        if (!in_array($docType, $validDocTypes)) {
            return response()->json(['message' => 'Invalid document type'], 400);
        }

        try {
            $voucher = FundTransaction::findOrFail($id);
            $this->service->deleteDocument($voucher, $docType);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify document upload via QR code.
     */
    public function verifyDocumentQR(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'qr_code' => ['required', 'string'],
            ]);

            $voucher = FundTransaction::findOrFail($id);
            $document = $this->service->verifyDocumentQR($voucher, $validated['qr_code']);

            return response()->json([
                'success' => true,
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'filename' => $document->filename,
                    'verified' => true,
                    'verified_at' => $document->updated_at,
                ],
                'message' => 'Document verified successfully via QR',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all documents for a fund transaction.
     */
    public function getDocuments(int $id): JsonResponse
    {
        try {
            $voucher = FundTransaction::findOrFail($id);
            $documents = $voucher->documents()->get();

            return response()->json([
                'success' => true,
                'data' => $documents->map(function ($doc) use ($voucher) {
                    return [
                        'id' => $doc->id,
                        'document_type' => $doc->document_type,
                        'filename' => $doc->filename,
                        'file_size' => $doc->file_size,
                        'mime_type' => $doc->mime_type,
                        'uploaded_by' => $doc->uploadedBy?->name,
                        'verified' => $doc->verified,
                        'uploaded_at' => $doc->created_at,
                        'download_url' => "/api/fund-transactions/{$voucher->id}/document/{$doc->document_type}/download",
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for mobile document upload.
     */
    public function generateQrCode(Request $request, int $id): JsonResponse
    {
        $transaction = FundTransaction::findOrFail($id);
        $transaction->generateUploadToken();

        $docType = $request->input('doc_type');
        $validDocTypes = ['obr', 'dv_payroll', 'los', 'cheque'];

        if (!in_array($docType, $validDocTypes)) {
            $docType = null;
        }

        return response()->json([
            'qr_code_svg' => $transaction->getUploadQrCode(250, $docType),
            'url' => $transaction->getMobileUploadUrl($docType),
            'expires_at' => $transaction->upload_token_expires_at?->toIso8601String(),
        ]);
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
