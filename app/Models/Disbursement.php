<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'disbursement_id';

    protected $fillable = [
        'profile_id',
        'disbursement_type',
        'payee',
        'obr_no',
        'date_obligated',
        'year_level',
        'semester',
        'academic_year',
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date_obligated' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the profile that owns the disbursement.
     */
    public function profile()
    {
        return $this->belongsTo(ScholarshipProfile::class, 'profile_id', 'profile_id');
    }

    /**
     * Get the user who created the disbursement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the cheques for the disbursement.
     */
    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'disbursement_id', 'disbursement_id');
    }

    /**
     * Get the latest cheque for the disbursement.
     */
    public function latestCheque()
    {
        return $this->hasOne(Cheque::class, 'disbursement_id', 'disbursement_id')->latest();
    }
}
