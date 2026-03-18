<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipRecordAttachment extends Model
{
    use SoftDeletes;

    protected $table = 'scholarship_record_attachments';
    protected $primaryKey = 'attachment_id';

    protected $fillable = [
        'scholarship_record_id',
        'attachment_name',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'original_size',
        'compression_ratio',
        'page_number',
    ];

    /**
     * Get the scholarship record that owns the attachment.
     */
    public function scholarshipRecord(): BelongsTo
    {
        return $this->belongsTo(ScholarshipRecord::class, 'scholarship_record_id', 'id');
    }
}
