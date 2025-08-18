<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'extension_name',
        'father_name',
        'father_occupation',
        'father_birthdate',
        'father_contact_no',
        'mother_name',
        'mother_occupation',
        'mother_birthdate',
        'mother_contact_no',
        'municipality',
        'barangay',
        'address',
        'temporary_municipality',
        'temporary_barangay',
        'temporary_address',
        'birthdate',
        'civil_status',
        'religion',
        'contact_no',
        'email',
        'gender',
        'birthdate',
        'contact_no',
        'remarks',
        'is_active',
        'is_approve',
        'is_denied',
        'is_pending',
        'created_by',
        'updated_by',
    ];


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function educationalBackgrounds()
    {
        return $this->hasMany(ApplicantEducationalBackground::class, 'applicant_id');
    }

    public function scholarshipGrant()
    {
        return $this->hasMany(
            Scholar::class,
            'applicant_id'
        );
    }

    public function ongoingScholarshipGrant()
    {
        return $this->hasOne(
            Scholar::class,
            'applicant_id'
        )->where('is_active', true);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $user = Auth::user();
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
        });
        static::updating(function ($model) {
            $user = Auth::user();
            $model->updated_by = $user->id;
        });
    }
}
