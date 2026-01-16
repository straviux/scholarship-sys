<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\Disbursement;
use Illuminate\Database\Eloquent\Collection;

/**
 * JasperReportDataService
 * 
 * Converts Eloquent queries and Laravel data into formats suitable for JasperReports.
 * Handles data retrieval, filtering, permission enforcement, and format conversion.
 */
class JasperReportDataService
{
    /**
     * Log instance
     */
    protected $logger;

    /**
     * Current user instance
     */
    protected $user;

    public function __construct()
    {
        $this->logger = Log::channel(config('jasperreports.logging.channel'));
        $this->user = Auth::user();
    }

    /**
     * Get scholarship profiles for reports with permission-based filtering
     * 
     * @param array $filters
     * @param array $columns
     * @return Collection
     */
    public function getScholarshipProfiles(array $filters = [], array $columns = ['*']): Collection
    {
        $query = ScholarshipProfile::query();

        // Filter for waiting list (pending status only)
        // For waiting list reports, only show records with unified_status = 'pending'
        $query->whereHas('scholarshipGrant', function ($q) {
            $q->where('unified_status', 'pending')
                ->orderBy('date_filed', 'desc')
                ->orderBy('created_at', 'desc');
        });

        // Apply date filtering
        if (!empty($filters['date_from'])) {
            $query->whereDate('date_filed', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date_filed', '<=', $filters['date_to']);
        }

        // Apply relationship filtering
        if (!empty($filters['program_ids'])) {
            $query->whereHas('scholarship_grant', function ($q) use ($filters) {
                $q->whereIn('program_id', $filters['program_ids']);
            });
        }

        if (!empty($filters['school_ids'])) {
            $query->whereHas('scholarship_grant', function ($q) use ($filters) {
                $q->whereIn('school_id', $filters['school_ids']);
            });
        }

        if (!empty($filters['course_ids'])) {
            $query->whereHas('scholarship_grant', function ($q) use ($filters) {
                $q->whereIn('course_id', $filters['course_ids']);
            });
        }

        if (!empty($filters['municipalities'])) {
            $query->whereIn('municipality', (array)$filters['municipalities']);
        }

        if (!empty($filters['year_levels'])) {
            $query->whereHas('scholarship_grant', function ($q) use ($filters) {
                $q->whereIn('year_level', (array)$filters['year_levels']);
            });
        }

        // Apply JPM filtering with permission check
        if (auth()->user()->can('jpm.view')) {
            if (!empty($filters['jpm_filter'])) {
                if ($filters['jpm_filter'] === 'jpm_only') {
                    $query->where('is_jpm_member', true);
                } elseif ($filters['jpm_filter'] === 'hide_jpm') {
                    $query->where('is_jpm_member', false);
                }
            }
        } else {
            // Hide JPM fields for users without permission
            // Query still returns the records but JPM columns won't be exposed
        }

        // Apply unified status filtering (overrides default if provided)
        if (!empty($filters['approval_statuses'])) {
            $query->whereHas('scholarship_grant', function ($q) use ($filters) {
                $q->whereIn('unified_status', (array)$filters['approval_statuses']);
            });
        }

        // Eager load relationships for performance
        $query->with([
            'scholarship_grant' => function ($q) {
                $q->with(['program', 'school', 'course']);
            }
        ]);

        return $query->get($columns);
    }

    /**
     * Get scholarship records with related data
     * 
     * @param array $filters
     * @param array $columns
     * @return Collection
     */
    public function getScholarshipRecords(array $filters = [], array $columns = ['*']): Collection
    {
        $query = ScholarshipRecord::query();

        // Apply filters similar to profiles
        if (!empty($filters['date_from'])) {
            $query->whereDate('date_filed', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date_filed', '<=', $filters['date_to']);
        }

        if (!empty($filters['approval_statuses'])) {
            $query->whereIn('unified_status', (array)$filters['approval_statuses']);
        }

        $query->with(['profile', 'program', 'school', 'course']);

        return $query->get($columns);
    }

    /**
     * Get disbursement records
     * 
     * @param array $filters
     * @param array $columns
     * @return Collection
     */
    public function getDisbursements(array $filters = [], array $columns = ['*']): Collection
    {
        $query = Disbursement::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('date_obligated', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date_obligated', '<=', $filters['date_to']);
        }

        if (!empty($filters['disbursement_types'])) {
            $query->whereIn('disbursement_type', (array)$filters['disbursement_types']);
        }

        if (!empty($filters['obr_statuses'])) {
            $query->whereIn('obr_status', (array)$filters['obr_statuses']);
        }

        $query->with(['profile', 'record']);

        return $query->get($columns);
    }

    /**
     * Export data to JSON format for JasperReports
     * 
     * @param Collection $data
     * @param string $rootElement
     * @return string (JSON)
     */
    public function toJson(Collection $data, string $rootElement = 'data'): string
    {
        // Filter sensitive data based on permissions
        $filtered = $data->map(function ($item) {
            return $this->filterSensitiveFields($item);
        });

        return json_encode([
            $rootElement => $filtered->toArray(),
            'count' => $filtered->count(),
            'timestamp' => now()->toIso8601String(),
            'user' => $this->user->name,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Export data to CSV format
     * 
     * @param Collection $data
     * @param string $filename
     * @return string (CSV path)
     */
    public function toCsv(Collection $data, string $filename = 'export.csv'): string
    {
        $path = config('jasperreports.output.path') . '/' . $filename;

        $handle = fopen($path, 'w');

        if ($data->isEmpty()) {
            fclose($handle);
            return $path;
        }

        // Get headers from first record
        $firstItem = $data->first();
        $headers = array_keys($this->filterSensitiveFields($firstItem)->toArray());

        fputcsv($handle, $headers);

        foreach ($data as $item) {
            fputcsv($handle, array_values($this->filterSensitiveFields($item)->toArray()));
        }

        fclose($handle);

        return $path;
    }

    /**
     * Filter sensitive fields based on user permissions
     * 
     * @param mixed $item
     * @return mixed
     */
    protected function filterSensitiveFields($item)
    {
        if (method_exists($item, 'toArray')) {
            $data = $item->toArray();
        } else {
            $data = (array)$item;
        }

        // Hide JPM fields if user doesn't have permission
        if (!auth()->user()->can('jpm.view')) {
            unset($data['is_jpm_member']);
            unset($data['is_father_jpm']);
            unset($data['is_mother_jpm']);
            unset($data['is_guardian_jpm']);
            unset($data['jpm_remarks']);
        }

        // Hide conditional approval details for non-admin users
        if (!auth()->user()->hasRole('administrator')) {
            unset($data['conditional_requirements']);
            unset($data['conditional_deadline']);
        }

        return (object)$data;
    }

    /**
     * Get aggregated statistics for summary reports
     * 
     * @param array $filters
     * @param string $groupBy
     * @return array
     */
    public function getStatistics(array $filters = [], string $groupBy = 'program'): array
    {
        $profiles = $this->getScholarshipProfiles($filters);

        if ($groupBy === 'program') {
            return $profiles->groupBy('scholarship_grant.0.program.shortname')
                ->map(fn($group) => [
                    'name' => $group->first()['scholarship_grant'][0]['program']['shortname'] ?? 'Unknown',
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $profiles->count()) * 100, 2),
                ])
                ->values()
                ->toArray();
        }

        if ($groupBy === 'school') {
            return $profiles->groupBy('scholarship_grant.0.school.shortname')
                ->map(fn($group) => [
                    'name' => $group->first()['scholarship_grant'][0]['school']['shortname'] ?? 'Unknown',
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $profiles->count()) * 100, 2),
                ])
                ->values()
                ->toArray();
        }

        // Default: return total count
        return [
            ['name' => 'Total', 'count' => $profiles->count()]
        ];
    }

    /**
     * Log data retrieval operation
     * 
     * @param string $operation
     * @param int $recordCount
     * @param array $filters
     * @return void
     */
    public function logOperation(string $operation, int $recordCount, array $filters = []): void
    {
        if (config('jasperreports.logging.enabled')) {
            $this->logger->info("JasperReports Data Export", [
                'operation' => $operation,
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'record_count' => $recordCount,
                'filters' => $filters,
                'timestamp' => now(),
            ]);
        }
    }
}
