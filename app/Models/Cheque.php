<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'cheque_id';

    protected $fillable = [
        'disbursement_id',
        'cheque_no',
        'date_released',
        'remarks',
        'processed_by',
    ];

    protected $casts = [
        'date_released' => 'date',
    ];

    /**
     * Get the disbursement that owns the cheque.
     */
    public function disbursement()
    {
        return $this->belongsTo(Disbursement::class, 'disbursement_id', 'disbursement_id');
    }

    /**
     * Get the user who processed the cheque.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
