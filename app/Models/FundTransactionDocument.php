<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FundTransactionDocument extends Model
{
    use HasFactory;

    protected $table = 'fund_transaction_documents';

    protected $fillable = [
        'fund_transaction_id',
        'document_type',
        'filename',
        'path',
        'file_size',
        'original_file_size',
        'mime_type',
        'uploaded_by',
        'qr_code',
        'verified',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the fund transaction this document belongs to
     */
    public function fundTransaction()
    {
        return $this->belongsTo(FundTransaction::class, 'fund_transaction_id');
    }

    /**
     * Get the user who uploaded this document
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Generate a QR code for this document
     */
    public function generateQRCode()
    {
        // Create a unique identifier for QR scanning
        $qrData = base64_encode(json_encode([
            'document_id' => $this->id,
            'fund_transaction_id' => $this->fund_transaction_id,
            'document_type' => $this->document_type,
            'timestamp' => now()->timestamp,
        ]));

        $this->update(['qr_code' => $qrData]);

        return $qrData;
    }

    /**
     * Mark document as verified via QR
     */
    public function markAsVerified()
    {
        $this->update(['verified' => true]);
        return $this;
    }
}
