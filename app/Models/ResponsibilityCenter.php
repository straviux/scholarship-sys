<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResponsibilityCenter extends Model
{
    use HasFactory;

    protected $table = 'responsibility_centers';

    protected $fillable = [
        'code',
        'name',
        'fiscal_year',
    ];

    /**
     * Get the particulars for this responsibility center
     */
    public function particulars()
    {
        return $this->hasMany(Particular::class);
    }
}
