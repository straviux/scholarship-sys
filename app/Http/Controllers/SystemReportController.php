<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use App\Models\User;

class SystemReportController extends Controller
{
    /**
     * Display the system status report dashboard
     */
    public function index(): Response
    {
        // Only allow administrators to access this report
        if (!Gate::allows('admin')) {
            abort(403, 'Access denied. Administrator privileges required.');
        }

        $report = $this->generateSystemReport();

        return Inertia::render('Admin/SystemReport/Index', [
            'report' => $report
        ]);
    }

    /**
     * Generate comprehensive system report
     */
    public function generateSystemReport(): array
    {
        return [
            'executive_summary' => $this->getExecutiveSummary(),
            'data_integrity' => $this->getDataIntegrityReport(),
            'application_status' => $this->getApplicationStatusReport(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'geographic_distribution' => $this->getGeographicReport(),
            'academic_analysis' => $this->getAcademicReport(),
            'system_health' => $this->getSystemHealth(),
            'user_activity' => $this->getUserActivityReport(),
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Executive summary with key metrics
     */
    private function getExecutiveSummary(): array
    {
        $totalRecords = ScholarshipRecord::count();
        $totalProfiles = ScholarshipProfile::count();

        return [
            'total_scholarship_records' => $totalRecords,
            'total_scholarship_profiles' => $totalProfiles,
            'pending_applications' => ScholarshipRecord::where('scholarship_status', 0)->count(),
            'approved_applications' => ScholarshipRecord::where('scholarship_status', 1)->count(),
            'rejected_applications' => ScholarshipRecord::where('scholarship_status', 2)->count(),
            'total_users' => User::count(),
            'active_programs' => ScholarshipProgram::count(),
            'total_schools' => School::count(),
            'total_courses' => Course::count(),
            'approval_rate' => $totalRecords > 0 ? round((ScholarshipRecord::where('scholarship_status', 1)->count() / $totalRecords) * 100, 2) : 0
        ];
    }

    /**
     * Data integrity analysis
     */
    private function getDataIntegrityReport(): array
    {
        return [
            'records_without_programs' => ScholarshipRecord::whereNull('program_id')->count(),
            'records_without_courses' => ScholarshipRecord::whereNull('course_id')->count(),
            'records_without_schools' => ScholarshipRecord::whereNull('school_id')->count(),
            'profiles_without_records' => ScholarshipProfile::whereDoesntHave('scholarshipGrant')->count(),
            'records_without_profiles' => ScholarshipRecord::whereDoesntHave('profile')->count(),
            'invalid_date_ranges' => $this->getInvalidDateRanges(),
            'duplicate_applications' => $this->findDuplicateApplications(),
            'orphaned_requirements' => $this->getOrphanedRequirements()
        ];
    }

    /**
     * Application status breakdown
     */
    private function getApplicationStatusReport(): array
    {
        return [
            'by_status' => ScholarshipRecord::selectRaw('
                CASE 
                    WHEN scholarship_status = 0 THEN "Pending"
                    WHEN scholarship_status = 1 THEN "Approved" 
                    WHEN scholarship_status = 2 THEN "Rejected"
                    ELSE "Unknown"
                END as status_name,
                scholarship_status,
                COUNT(*) as count
            ')->groupBy('scholarship_status')->get(),

            'by_program' => ScholarshipRecord::leftJoin('scholarship_programs', 'scholarship_records.program_id', '=', 'scholarship_programs.id')
                ->selectRaw('
                    COALESCE(scholarship_programs.shortname, "No Program") as program_name,
                    scholarship_status,
                    COUNT(*) as count
                ')
                ->groupBy('scholarship_records.program_id', 'scholarship_programs.shortname', 'scholarship_status')
                ->orderBy('count', 'desc')
                ->get(),

            'monthly_trends' => ScholarshipRecord::selectRaw('
                YEAR(date_filed) as year,
                MONTH(date_filed) as month,
                scholarship_status,
                COUNT(*) as count
            ')
                ->whereYear('date_filed', now()->year)
                ->groupByRaw('YEAR(date_filed), MONTH(date_filed), scholarship_status')
                ->orderByRaw('YEAR(date_filed) ASC, MONTH(date_filed) ASC')
                ->get(),

            'processing_times' => $this->getProcessingTimes()
        ];
    }

    /**
     * Performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'average_processing_time_days' => $this->getAverageProcessingTime(),
            'applications_this_month' => ScholarshipRecord::whereMonth('date_filed', now()->month)
                ->whereYear('date_filed', now()->year)->count(),
            'applications_last_month' => ScholarshipRecord::whereMonth('date_filed', now()->subMonth()->month)
                ->whereYear('date_filed', now()->subMonth()->year)->count(),
            'peak_application_days' => $this->getPeakApplicationDays(),
            'system_usage_stats' => $this->getSystemUsageStats()
        ];
    }

    /**
     * Geographic distribution analysis
     */
    private function getGeographicReport(): array
    {
        return [
            'by_municipality' => ScholarshipProfile::selectRaw('
                COALESCE(municipality, "Not Specified") as municipality,
                COUNT(*) as count
            ')
                ->groupBy('municipality')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get(),

            'by_school' => ScholarshipRecord::leftJoin('schools', 'scholarship_records.school_id', '=', 'schools.id')
                ->selectRaw('
                    COALESCE(schools.name, "No School") as school_name,
                    COUNT(*) as count
                ')
                ->groupBy('scholarship_records.school_id', 'schools.name')
                ->orderBy('count', 'desc')
                ->limit(15)
                ->get(),

            'regional_distribution' => $this->getRegionalDistribution()
        ];
    }

    /**
     * Academic analysis
     */
    private function getAcademicReport(): array
    {
        return [
            'by_course' => ScholarshipRecord::leftJoin('courses', 'scholarship_records.course_id', '=', 'courses.id')
                ->selectRaw('
                    COALESCE(courses.name, "No Course") as course_name,
                    COUNT(*) as total_applications,
                    SUM(CASE WHEN scholarship_status = 1 THEN 1 ELSE 0 END) as approved,
                    ROUND((SUM(CASE WHEN scholarship_status = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as approval_rate
                ')
                ->groupBy('scholarship_records.course_id', 'courses.name')
                ->orderBy('total_applications', 'desc')
                ->limit(15)
                ->get(),

            'by_year_level' => ScholarshipRecord::selectRaw('
                COALESCE(year_level, "Not Specified") as year_level,
                COUNT(*) as count,
                ROUND(AVG(CASE WHEN scholarship_status = 1 THEN 1.0 ELSE 0.0 END) * 100, 2) as approval_rate
            ')
                ->groupBy('year_level')
                ->orderBy('count', 'desc')
                ->get(),

            'program_effectiveness' => $this->getProgramEffectiveness()
        ];
    }

    /**
     * System health checks
     */
    private function getSystemHealth(): array
    {
        return [
            'database_status' => $this->checkDatabaseConnection(),
            'storage_metrics' => $this->getStorageMetrics(),
            'cache_status' => $this->getCacheStatus(),
            'recent_activity' => $this->getRecentActivity(),
            'system_errors' => $this->getSystemErrors()
        ];
    }

    /**
     * User activity report
     */
    private function getUserActivityReport(): array
    {
        return [
            'total_users' => User::count(),
            'active_users_today' => User::whereDate('updated_at', today())->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'user_roles_distribution' => $this->getUserRolesDistribution(),
            'last_login_activity' => $this->getLastLoginActivity()
        ];
    }

    // Helper methods for detailed analysis

    private function getInvalidDateRanges(): int
    {
        return ScholarshipRecord::whereRaw('date_filed > date_approved')
            ->orWhereRaw('date_approved > NOW()')
            ->count();
    }

    private function findDuplicateApplications(): int
    {
        return ScholarshipRecord::selectRaw('profile_id, COUNT(*) as count')
            ->groupBy('profile_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    private function getOrphanedRequirements(): int
    {
        return DB::table('scholarship_record_requirements')
            ->leftJoin('scholarship_records', 'scholarship_record_requirements.record_id', '=', 'scholarship_records.id')
            ->whereNull('scholarship_records.id')
            ->count();
    }

    private function getProcessingTimes(): array
    {
        return ScholarshipRecord::whereNotNull('date_approved')
            ->selectRaw('
                AVG(DATEDIFF(date_approved, date_filed)) as avg_days,
                MIN(DATEDIFF(date_approved, date_filed)) as min_days,
                MAX(DATEDIFF(date_approved, date_filed)) as max_days
            ')
            ->first()
            ->toArray();
    }

    private function getAverageProcessingTime(): float
    {
        $avg = ScholarshipRecord::whereNotNull('date_approved')
            ->selectRaw('AVG(DATEDIFF(date_approved, date_filed)) as avg_days')
            ->first();

        return $avg ? round($avg->avg_days, 1) : 0;
    }

    private function getPeakApplicationDays(): array
    {
        return ScholarshipRecord::selectRaw('
            DAYNAME(date_filed) as day_name,
            COUNT(*) as count
        ')
            ->groupBy(DB::raw('DAYOFWEEK(date_filed)'), 'day_name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getSystemUsageStats(): array
    {
        return [
            'total_logins_today' => 0, // Would need login tracking
            'peak_concurrent_users' => 0, // Would need session tracking
            'average_session_duration' => 0 // Would need session tracking
        ];
    }

    private function getRegionalDistribution(): array
    {
        return ScholarshipProfile::selectRaw('
            SUBSTRING_INDEX(municipality, " ", -1) as region,
            COUNT(*) as count
        ')
            ->whereNotNull('municipality')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getProgramEffectiveness(): array
    {
        return ScholarshipProgram::leftJoin('scholarship_records', 'scholarship_programs.id', '=', 'scholarship_records.program_id')
            ->selectRaw('
                scholarship_programs.shortname,
                scholarship_programs.name,
                COUNT(scholarship_records.id) as total_applications,
                SUM(CASE WHEN scholarship_records.scholarship_status = 1 THEN 1 ELSE 0 END) as approved,
                ROUND((SUM(CASE WHEN scholarship_records.scholarship_status = 1 THEN 1 ELSE 0 END) / COUNT(scholarship_records.id)) * 100, 2) as success_rate
            ')
            ->groupBy('scholarship_programs.id', 'scholarship_programs.shortname', 'scholarship_programs.name')
            ->orderBy('total_applications', 'desc')
            ->get()
            ->toArray();
    }

    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();
            return 'Connected';
        } catch (\Exception $e) {
            return 'Disconnected';
        }
    }

    private function getStorageMetrics(): array
    {
        $publicPath = public_path();
        $storagePath = storage_path();

        return [
            'public_storage_used' => $this->formatBytes($this->getDirectorySize($publicPath)),
            'private_storage_used' => $this->formatBytes($this->getDirectorySize($storagePath)),
            'total_files' => count(Storage::allFiles())
        ];
    }

    private function getCacheStatus(): string
    {
        try {
            Cache::put('health_check', 'ok', 1);
            return Cache::get('health_check') === 'ok' ? 'Working' : 'Failed';
        } catch (\Exception $e) {
            return 'Failed';
        }
    }

    private function getRecentActivity(): array
    {
        return [
            'new_applications_today' => ScholarshipRecord::whereDate('date_filed', today())->count(),
            'approvals_today' => ScholarshipRecord::whereDate('date_approved', today())->count(),
            'profile_updates_today' => ScholarshipProfile::whereDate('updated_at', today())->count()
        ];
    }

    private function getSystemErrors(): array
    {
        // This would require error logging implementation
        return [
            'errors_today' => 0,
            'critical_errors' => 0,
            'warnings' => 0
        ];
    }

    private function getUserRolesDistribution(): array
    {
        // Since we use Spatie Permission, we need to query the model_has_roles table
        $adminCount = User::role('administrator')->count();
        $moderatorCount = User::role('moderator')->count();
        $totalUsers = User::count();
        $regularUsers = $totalUsers - $adminCount - $moderatorCount;

        $result = [];
        if ($adminCount > 0) {
            $result[] = ['role' => 'Administrator', 'count' => $adminCount];
        }
        if ($moderatorCount > 0) {
            $result[] = ['role' => 'Moderator', 'count' => $moderatorCount];
        }
        if ($regularUsers > 0) {
            $result[] = ['role' => 'User', 'count' => $regularUsers];
        }

        return $result;
    }

    private function getLastLoginActivity(): array
    {
        return [
            'users_logged_in_today' => User::whereDate('updated_at', today())->count(),
            'users_logged_in_week' => User::whereBetween('updated_at', [now()->subWeek(), now()])->count(),
            'inactive_users' => User::where('updated_at', '<', now()->subMonth())->count()
        ];
    }

    private function getDirectorySize($directory): int
    {
        $size = 0;
        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        return $size;
    }

    private function formatBytes($size, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Export report as JSON
     */
    public function exportJson()
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Access denied. Administrator privileges required.');
        }

        $report = $this->generateSystemReport();

        return response()->json($report)
            ->header('Content-Disposition', 'attachment; filename="system-report-' . now()->format('Y-m-d-H-i-s') . '.json"');
    }

    /**
     * Display encoded summary report for logged-in user
     */
    public function getUserSummaryReport(Request $request): Response
    {
        $user = $request->user();

        // Generate user-specific summary
        $userSummary = $this->generateUserSummaryData($user);

        // Encode the summary (base64 for simplicity, could use other encoding methods)
        $encodedSummary = base64_encode(json_encode($userSummary));

        $reportData = [
            'user_summary' => $userSummary,
            'encoded_summary' => $encodedSummary,
            'generated_at' => now()->toDateTimeString(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url,
            'has_profile_photo' => $user->hasProfilePhoto()
        ];

        return Inertia::render('User/UserProfile', [
            'reportData' => $reportData
        ]);
    }

    /**
     * Generate user-specific summary data
     */
    private function generateUserSummaryData(User $user): array
    {
        // Get user's role information
        $userRoles = $user->getRoleNames()->toArray();
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        // Basic user statistics
        $userStats = [
            'basic_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'created_at' => $user->created_at->toDateTimeString(),
                'roles' => $userRoles,
                'permissions_count' => count($userPermissions),
                'last_login' => $user->updated_at->toDateTimeString(), // Approximation
            ],
            'system_access' => [
                'can_view_dashboard' => $user->can('view dashboard') || in_array('administrator', $userRoles) || in_array('moderator', $userRoles),
                'can_manage_scholarships' => $user->hasPermissionTo('manage-scholarship-programs') ?? false,
                'can_view_reports' => in_array('administrator', $userRoles),
                'access_level' => $this->getUserAccessLevel($userRoles),
            ],
            'encoding_statistics' => $this->getUserEncodingStatistics($user)
        ];

        // If user has higher privileges, include more detailed stats
        if (in_array('administrator', $userRoles) || in_array('moderator', $userRoles)) {
            $userStats['privileged_access'] = [
                'total_scholarship_records' => ScholarshipRecord::count(),
                'total_profiles' => ScholarshipProfile::count(),
                'total_programs' => ScholarshipProgram::count(),
                'total_courses' => Course::count(),
                'total_schools' => School::count(),
                'system_users' => User::count(),
                'report_generated_by' => 'privileged_user'
            ];
        }

        return $userStats;
    }

    /**
     * Determine user access level based on roles
     */
    private function getUserAccessLevel(array $roles): string
    {
        if (in_array('administrator', $roles)) {
            return 'full_admin';
        } elseif (in_array('moderator', $roles)) {
            return 'moderator';
        } elseif (in_array('user', $roles)) {
            return 'basic_user';
        } else {
            return 'guest';
        }
    }

    /**
     * Get user-specific encoding statistics
     */
    private function getUserEncodingStatistics(User $user): array
    {
        $userId = $user->id;

        // Get scholarship applications created by this user
        $applicationsCreated = ScholarshipRecord::where('created_by', $userId)->count();

        // Get applications updated by this user, but exclude those they also created
        // (if user created and updated same record, count only as created)
        $applicationsUpdated = ScholarshipRecord::where('updated_by', $userId)
            ->where('created_by', '!=', $userId)
            ->count();

        // Get recent activity (current month) - only count created applications
        $recentApplications = ScholarshipRecord::where('created_by', $userId)
            ->where('created_at', '>=', now()->startOfMonth())->count();

        // Get most active months for this user
        $monthlyActivity = ScholarshipRecord::where('created_by', $userId)
            ->select(DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->toArray();

        return [
            'applications' => [
                'total_created' => $applicationsCreated,
                'total_updated' => $applicationsUpdated,
                'recent_created' => $recentApplications,
            ],
            'summary' => [
                'total_encoded' => $applicationsCreated,
                'total_contributions' => $applicationsCreated + $applicationsUpdated,
                'recent_activity' => $recentApplications,
            ],
            'breakdowns' => $this->getUserBreakdownStatistics($userId),
            'monthly_activity' => $monthlyActivity,
            'first_encoding_date' => $this->getUserFirstEncodingDate($userId),
            'latest_activity_date' => $this->getUserLatestActivity($userId),
        ];
    }

    /**
     * Get breakdown statistics for user's applications
     */
    private function getUserBreakdownStatistics(int $userId): array
    {
        // Get program breakdown (all time)
        $programBreakdown = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->leftJoin('scholarship_programs', 'scholarship_records.program_id', '=', 'scholarship_programs.id')
            ->select(
                DB::raw('COALESCE(scholarship_programs.name, "No Program") as program_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('scholarship_programs.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get program breakdown (current month)
        $programBreakdownCurrentMonth = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->where('scholarship_records.created_at', '>=', now()->startOfMonth())
            ->leftJoin('scholarship_programs', 'scholarship_records.program_id', '=', 'scholarship_programs.id')
            ->select(
                DB::raw('COALESCE(scholarship_programs.name, "No Program") as program_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('scholarship_programs.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get course breakdown (all time)
        $courseBreakdown = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->leftJoin('courses', 'scholarship_records.course_id', '=', 'courses.id')
            ->select(
                DB::raw('COALESCE(courses.name, "No Course") as course_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('courses.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get course breakdown (current month)
        $courseBreakdownCurrentMonth = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->where('scholarship_records.created_at', '>=', now()->startOfMonth())
            ->leftJoin('courses', 'scholarship_records.course_id', '=', 'courses.id')
            ->select(
                DB::raw('COALESCE(courses.name, "No Course") as course_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('courses.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get school breakdown (all time)
        $schoolBreakdown = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->leftJoin('schools', 'scholarship_records.school_id', '=', 'schools.id')
            ->select(
                DB::raw('COALESCE(schools.name, "No School") as school_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('schools.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get school breakdown (current month)
        $schoolBreakdownCurrentMonth = ScholarshipRecord::where('scholarship_records.created_by', $userId)
            ->where('scholarship_records.created_at', '>=', now()->startOfMonth())
            ->leftJoin('schools', 'scholarship_records.school_id', '=', 'schools.id')
            ->select(
                DB::raw('COALESCE(schools.name, "No School") as school_name'),
                DB::raw('count(*) as count')
            )
            ->groupBy('schools.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        return [
            'by_program' => $programBreakdown,
            'by_course' => $courseBreakdown,
            'by_school' => $schoolBreakdown,
            'by_program_current_month' => $programBreakdownCurrentMonth,
            'by_course_current_month' => $courseBreakdownCurrentMonth,
            'by_school_current_month' => $schoolBreakdownCurrentMonth,
        ];
    }

    /**
     * Get the date of user's first encoding activity
     */
    private function getUserFirstEncodingDate(int $userId): ?string
    {
        $firstRecord = ScholarshipRecord::where('created_by', $userId)
            ->orderBy('created_at', 'asc')
            ->first();

        return $firstRecord?->created_at?->toDateTimeString();
    }

    /**
     * Get user's latest activity date (either creating or updating records)
     */
    private function getUserLatestActivity(int $userId): ?string
    {
        // Get latest creation activity
        $latestCreated = ScholarshipRecord::where('created_by', $userId)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        // Get latest update activity
        $latestUpdated = ScholarshipRecord::where('updated_by', $userId)
            ->where('created_by', '!=', $userId) // Exclude records they created
            ->orderBy('updated_at', 'desc')
            ->value('updated_at');

        // Return the most recent of the two
        if (!$latestCreated && !$latestUpdated) {
            return null;
        }

        if (!$latestCreated) {
            return $latestUpdated->toDateTimeString();
        }

        if (!$latestUpdated) {
            return $latestCreated->toDateTimeString();
        }

        return $latestCreated->gt($latestUpdated)
            ? $latestCreated->toDateTimeString()
            : $latestUpdated->toDateTimeString();
    }
}
