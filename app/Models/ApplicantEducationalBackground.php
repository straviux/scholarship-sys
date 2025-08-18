<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApplicantEducationalBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'applicant_id',
        'level',
        'address',
        'degree',
        'course',
        'academic_honors',
        'start_date',
        'end_date',
    ];
}
