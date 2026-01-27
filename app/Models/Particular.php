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
    ];

    /**
     * Get the responsibility center that owns this particular
     */
    public function responsibilityCenter()
    {
        return $this->belongsTo(ResponsibilityCenter::class);
    }
}
