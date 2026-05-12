<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class Particular extends Model
{
    use HasFactory;

    protected $table = 'particulars';

    protected $fillable = [
        'responsibility_center_id',
        'name',
        'description',
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

    /**
     * Get all scholarship programs linked to this particular.
     */
    public function programs()
    {
        return $this->belongsToMany(ScholarshipProgram::class, 'particular_programs')
            ->withTimestamps();
    }

    /**
     * Scope a query to particulars assigned to a scholarship program.
     */
    public function scopeForScholarshipProgram($query, int $programId)
    {
        return $query->where(function ($innerQuery) use ($programId) {
            $innerQuery->where('scholarship_program_id', $programId)
                ->orWhereHas('programs', fn($programQuery) => $programQuery->where('scholarship_programs.id', $programId));
        });
    }

    /**
     * Resolve the assigned programs, preferring the many-to-many relation.
     */
    public function resolvedPrograms(): Collection
    {
        if ($this->relationLoaded('programs') && $this->programs->isNotEmpty()) {
            return $this->programs;
        }

        if ($this->relationLoaded('program') && $this->program) {
            return collect([$this->program]);
        }

        if ($this->scholarship_program_id) {
            $program = $this->program()->first();

            return $program ? collect([$program]) : collect();
        }

        return collect();
    }
}
