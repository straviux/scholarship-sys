<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationalBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'profile_id',
        'level',
        'school_address',
        'degree',
        'course',
        'academic_honors',
        'start_date',
        'end_date',
    ];
}
