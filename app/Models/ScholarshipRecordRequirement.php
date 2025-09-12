<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipRecordRequirement extends Model
{
    protected $fillable = ['record_id', 'requirement_id', 'file_name', 'file_path'];

    public function record()
    {
        return $this->belongsTo(ScholarshipRecord::class, 'record_id');
    }

    public function programRequirement()
    {
        return $this->belongsTo(ProgramRequirement::class, 'requirement_id');
    }
}
