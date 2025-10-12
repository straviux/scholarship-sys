<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'scholarship_approval_history';

    protected $fillable = [
        'scholarship_record_id',
        'action',
        'previous_status',
        'new_status',
        'performed_by',
        'remarks',
        'performed_at'
    ];

    protected $casts = [
        'performed_at' => 'datetime'
    ];

    public $timestamps = false; // Using custom created_at

    public function scholarshipRecord()
    {
        return $this->belongsTo(ScholarshipRecord::class, 'scholarship_record_id', 'id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionConfig()
    {
        $actions = [
            'approved' => [
                'label' => 'Approved',
                'color' => 'success',
                'icon' => 'pi-check-circle'
            ],
            'declined' => [
                'label' => 'Declined',
                'color' => 'danger',
                'icon' => 'pi-times-circle'
            ],
            'conditional' => [
                'label' => 'Conditional Approval',
                'color' => 'info',
                'icon' => 'pi-info-circle'
            ],
            'resubmitted' => [
                'label' => 'Resubmitted',
                'color' => 'secondary',
                'icon' => 'pi-refresh'
            ],
            'completed' => [
                'label' => 'Completed',
                'color' => 'success',
                'icon' => 'pi-check-circle'
            ],
            'discontinued' => [
                'label' => 'Discontinued',
                'color' => 'warning',
                'icon' => 'pi-pause-circle'
            ],
            'renewal_application' => [
                'label' => 'New Application',
                'color' => 'info',
                'icon' => 'pi-plus-circle'
            ]
        ];

        return $actions[$this->action] ?? [
            'label' => ucfirst($this->action),
            'color' => 'secondary',
            'icon' => 'pi-circle'
        ];
    }

    public function getActionLabel()
    {
        return $this->getActionConfig()['label'];
    }

    public function getActionColor()
    {
        return $this->getActionConfig()['color'];
    }

    public function getActionIcon()
    {
        return $this->getActionConfig()['icon'];
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByPerformer($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }
}
