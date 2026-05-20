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
        'request_date',
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
        'highlight_jpm_members',
        'include_endorsed_by',
        'show_remarks',
        'prepared_by',
        'prepared_by_position',
        'prepared_by_office',
        'approved_by',
        'approved_by_position',
        'approved_by_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'record_count' => 'integer',
        'total_projected_expense' => 'decimal:2',
        'selected_record_ids' => 'array',
        'records_snapshot' => 'array',
        'budget_allocation' => 'array',
        'highlight_jpm_members' => 'boolean',
        'include_endorsed_by' => 'boolean',
        'show_remarks' => 'boolean',
        'budget_fiscal_year' => 'integer',
        'approved_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id')->select(['id', 'name']);
    }
}
