<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class ScholarshipProfile extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'profile_id';
    use HasFactory, Notifiable, HasUuids;
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
        'guardian_name',
        'guardian_relationship',
        'guardian_contact_no',
        'guardian_occupation',
        'municipality',
        'barangay',
        'address',
        'temporary_municipality',
        'temporary_barangay',
        'temporary_address',
        'birthdate',
        'contact_no',
        'contact_no_2',
        'email',
        'gender',
        'civil_status',
        'religion',
        'remarks',
        'date_filed',
        'is_active',
        'created_by',
        'updated_by',
        'is_on_waiting_list',
        'application_status',
        'application_status_remarks',
        'application_status_date',
        'parents_guardian_gross_monthly_income',
    ];

    public function educationalBackgrounds()
    {
        return $this->hasMany(EducationalBackground::class, 'profile_id');
    }

    public function scholarshipGrant()
    {
        return $this->hasMany(
            ScholarshipRecord::class,
            'profile_id'
        )->with(['program', 'course', 'requirements', 'school']);
    }

    public function ongoingScholarshipGrant()
    {
        return $this->hasOne(
            ScholarshipRecord::class,
            'profile_id'
        )->with(['program', 'course'])->whereIn('scholarship_status', [1, 3]);
    }

    public function pendingScholarshipGrant()
    {
        return $this->hasOne(
            ScholarshipRecord::class,
            'profile_id'
        )->with(['program', 'course'])->where('scholarship_status', 0);
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $user = Auth::user();
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
            // Generate 8-char unique_id: initials + last 5 digits of timestamp
            $year = date('y');
            $last = strtoupper(substr($model->last_name, 0, 1));
            $first = strtoupper(substr($model->first_name, 0, 1));
            if (!empty($model->middle_name)) {
                $third = strtoupper(substr($model->middle_name, 0, 1));
            } else {
                $third = strtoupper(substr($model->first_name, 1, 1));
            }
            $initials = $last . $first . $third;
            do {
                $timePart = substr((string)time(), -3);
                $uniqueId = $initials . $year . $timePart;
            } while (self::where('unique_id', $uniqueId)->exists());
            $model->unique_id = $uniqueId;
        });
        static::updating(function ($model) {
            $user = Auth::user();
            $model->updated_by = $user->id;
        });
    }
}
