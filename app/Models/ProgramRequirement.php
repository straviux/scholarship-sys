<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramRequirement extends Model
{

    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'program_id',
        'requirement_id',
    ];

    public function scholarshipRecordRequirements()
    {
        return $this->hasMany(ScholarshipRecordRequirement::class);
    }
}
