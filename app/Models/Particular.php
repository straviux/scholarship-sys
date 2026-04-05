<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Particular extends Model
{
    use HasFactory;

    protected $table = 'particulars';

    protected $fillable = [
        'responsibility_center_id',
        'name',
        'account_code',
        'scholarship_program_id',
        'allotment',
        'date_approved',
        'date_expired',
    ];

    protected $casts = [
        'allotment'     => 'decimal:2',
        'date_approved' => 'date:Y-m-d',
        'date_expired'  => 'date:Y-m-d',
    ];

    /**
     * Get the responsibility center that owns this particular
     */
    public function responsibilityCenter()
    {
        return $this->belongsTo(ResponsibilityCenter::class);
    }

    /**
     * Get the scholarship program linked to this particular
     */
    public function program()
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }
}
