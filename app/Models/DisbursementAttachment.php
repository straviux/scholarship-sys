<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementAttachment extends Model
{
    use SoftDeletes;

    protected $table = 'disbursement_attachments';
    protected $primaryKey = 'attachment_id';

    protected $fillable = [
        'disbursement_id',
        'attachment_type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'original_size',
        'compression_ratio',
    ];

    /**
     * Get the disbursement that owns the attachment.
     */
    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class, 'disbursement_id', 'disbursement_id');
    }
}
