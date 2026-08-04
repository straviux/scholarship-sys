<?php

namespace App\Services;

use App\Models\FundTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FundTransactionService
{
    /**
     * Generate a unique transaction ID for the current month.
     *
     * Format: FTR-YYYYMM-0001
     */
    public function generateTransactionId(): string
    {
        $prefix = sprintf('FTR-%s-', now()->format('Ym'));

        $lastVoucher = FundTransaction::withTrashed()
            ->where('transaction_id', 'like', $prefix . '%')
            ->orderBy('transaction_id', 'desc')
            ->first();

        $nextNumber = $lastVoucher
            ? (int) substr($lastVoucher->transaction_id, -4) + 1
            : 1;

        return $prefix . sprintf('%04d', $nextNumber);
    }

    /**
     * Create a new fund transaction.
     *
     * @param array $data Validated data from FormRequest
     * @return FundTransaction
     */
    public function create(array $data): FundTransaction
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $data['transaction_id'] = $this->generateTransactionId();
                    $data['status_updated_at'] = now();

                    $voucher = FundTransaction::create($data);

                    Log::info('fund_transaction_created', [
                        'id' => $voucher->id,
                        'transaction_id' => $voucher->transaction_id,
                        'created_by' => $data['created_by'] ?? null,
                    ]);

                    return $voucher;
                });
            } catch (QueryException $exception) {
                if ($attempt === $maxAttempts || ! $this->causedByDuplicateTransactionId($exception)) {
                    throw $exception;
                }

                Log::warning('fund_transaction_transaction_id_collision', [
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException('Unable to generate a unique transaction ID.');
    }

    private function causedByDuplicateTransactionId(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = $exception->errorInfo[2] ?? $exception->getMessage();

        if ($sqlState !== '23000' || $driverCode !== 1062) {
            return false;
        }

        return str_contains($message, 'fund_transactions_transaction_id_unique')
            || str_contains($message, 'vouchers_voucher_number_unique');
    }

    /**
     * Update an existing fund transaction.
     *
     * @param FundTransaction $voucher
     * @param array $data Validated data from FormRequest
     * @return FundTransaction
     */
    public function update(FundTransaction $voucher, array $data): FundTransaction
    {
        Log::debug('FundTransaction update attempt', [
            'id' => $voucher->id,
            'transaction_status' => $data['transaction_status'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $voucher->update($data);
        $voucher->refresh();

        Log::debug('FundTransaction update result', [
            'id' => $voucher->id,
            'transaction_status_after' => $voucher->transaction_status,
        ]);

        return $voucher;
    }

    /**
     * Update only the status and remarks of a fund transaction.
     *
     * @param FundTransaction $voucher
     * @param array $data Validated data (transaction_status, remarks)
     * @return FundTransaction
     */
    public function updateStatus(FundTransaction $voucher, array $data): FundTransaction
    {
        Log::debug('Updating status for voucher ' . $voucher->id, $data);

        $voucher->update($data);
        $voucher->refresh();

        Log::debug('Status updated for voucher ' . $voucher->id, [
            'transaction_status' => $voucher->transaction_status,
            'remarks' => $voucher->remarks,
        ]);

        return $voucher;
    }

    /**
     * Soft-delete a fund transaction.
     */
    public function delete(FundTransaction $voucher): bool
    {
        $voucher->delete();
        return true;
    }

}
