<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'voucher_type',
        'explanation',
        'payee_type',
        'payee_name',
        'payee_address',
        'responsibility_center',
        'account_code',
        'particulars_name',
        'particulars_description',
        'amount',
        'scholar_ids',
        'notes',
        'remarks',
        'obr_type',
        'created_by',
    ];

    protected $casts = [
        'scholar_ids' => 'array',
        'particulars_description' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user who created the voucher.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the associated scholars.
     */
    public function scholars()
    {
        if (empty($this->scholar_ids)) {
            return [];
        }
        return ScholarshipProfile::whereIn('profile_id', $this->scholar_ids)->get();
    }

    /**
     * Get the responsibility center.
     */
    public function responsibilityCenter()
    {
        return $this->belongsTo(ResponsibilityCenter::class, 'responsibility_center', 'id');
    }
}
