<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoterProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'middlename',
        'birthdate',
        'contact_no',
        'precinct_no',
        'gender',
        'remarks',
        'position',
        'parent_id',
        'barangay',
        'purok'
    ];


    public function members()
    {
        return $this->hasMany(VoterProfile::class, 'parent_id');
    }
    public function leader()
    {
        return $this->belongsTo(VoterProfile::class, 'parent_id');
    }
}
