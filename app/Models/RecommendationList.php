<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RecommendationList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'list_number',
        'report_title',
        'recommendation_status',
        'paper_size',
        'orientation',
        'record_count',
        'total_projected_expense',
        'selected_record_ids',
        'records_snapshot',
        'budget_allocation_key',
        'budget_program',
        'budget_fiscal_year',
        'budget_rc_code',
        'budget_rc_name',
        'budget_allocation',
        'prepared_by',
        'prepared_by_position',
        'prepared_by_office',
        'approved_by',
        'approved_by_position',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'record_count' => 'integer',
        'total_projected_expense' => 'decimal:2',
        'selected_record_ids' => 'array',
        'records_snapshot' => 'array',
        'budget_allocation' => 'array',
        'budget_fiscal_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $user = Auth::user();

            if ($user) {
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
            }
        });

        static::updating(function (self $model) {
            $user = Auth::user();

            if ($user) {
                $model->updated_by = $user->id;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }
}