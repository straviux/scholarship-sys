<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summaries
     */
    public function index(): Response
    {
        try {
            // Get daily statistics for the current month
            $dailyStats = $this->getDailyStatistics();
            
            // Get monthly statistics for the current year
            $monthlyStats = $this->getMonthlyStatistics();
            
            // Get program distribution
            $programDistribution = $this->getProgramDistribution();
            
            // Get course distribution
            $courseDistribution = $this->getCourseDistribution();
            
            // Get status distribution
            $statusDistribution = $this->getStatusDistribution();
            
            // Get school distribution
            $schoolDistribution = $this->getSchoolDistribution();
            
            // Get recent statistics
            $recentStats = $this->getRecentStatistics();
            
            // Get yearly trends
            $yearlyTrends = $this->getYearlyTrends();

            return Inertia::render('Dashboard/Index', [
                'dailyStats' => $dailyStats,
                'monthlyStats' => $monthlyStats,
                'programDistribution' => $programDistribution,
                'courseDistribution' => $courseDistribution,
                'statusDistribution' => $statusDistribution,
                'schoolDistribution' => $schoolDistribution,
                'recentStats' => $recentStats,
                'yearlyTrends' => $yearlyTrends,
                'dashboard_links' => [
                    'dashboard',
                    'scholar.index',
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Dashboard error: ' . $e->getMessage());
            
            // Return default empty data to prevent 500 error
            return Inertia::render('Dashboard/Index', [
                'dailyStats' => $this->getEmptyChartData(),
                'monthlyStats' => $this->getEmptyChartData(),
                'programDistribution' => $this->getEmptyPieChartData(),
                'courseDistribution' => $this->getEmptyChartData(),
                'statusDistribution' => $this->getEmptyPieChartData(),
                'schoolDistribution' => $this->getEmptyChartData(),
                'recentStats' => $this->getEmptyStats(),
                'yearlyTrends' => $this->getEmptyChartData(),
                'dashboard_links' => [
                    'dashboard',
                    'scholar.index',
                ]
            ]);
        }
    }    /**
     * Get daily statistics for current month
     */
    private function getDailyStatistics()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $dailyData = ScholarshipRecord::select(
            DB::raw('DATE(date_filed) as date'),
            DB::raw('COUNT(*) as applications'),
            DB::raw('SUM(CASE WHEN scholarship_status = 1 THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN scholarship_status = 0 THEN 1 ELSE 0 END) as pending'),
            DB::raw('SUM(CASE WHEN scholarship_status = 4 THEN 1 ELSE 0 END) as rejected')
        )
            ->whereBetween('date_filed', [$startOfMonth, $endOfMonth])
            ->groupBy(DB::raw('DATE(date_filed)'))
            ->orderBy('date')
            ->get();

        return [
            'labels' => $dailyData->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $dailyData->pluck('applications'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                ],
                [
                    'label' => 'Approved',
                    'data' => $dailyData->pluck('approved'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                ],
                [
                    'label' => 'Pending',
                    'data' => $dailyData->pluck('pending'),
                    'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
                    'borderColor' => 'rgba(255, 206, 86, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                ]
            ]
        ];
    }

    /**
     * Get monthly statistics for current year
     */
    private function getMonthlyStatistics()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $monthlyData = ScholarshipRecord::select(
            DB::raw('MONTH(date_filed) as month'),
            DB::raw('COUNT(*) as applications'),
            DB::raw('SUM(CASE WHEN scholarship_status = 1 THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN scholarship_status = 0 THEN 1 ELSE 0 END) as pending')
        )
            ->whereBetween('date_filed', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('MONTH(date_filed)'))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => collect($months)->map(function ($month, $index) use ($monthlyData) {
                        $data = $monthlyData->where('month', $index + 1)->first();
                        return $data ? $data->applications : 0;
                    }),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Approved',
                    'data' => collect($months)->map(function ($month, $index) use ($monthlyData) {
                        $data = $monthlyData->where('month', $index + 1)->first();
                        return $data ? $data->approved : 0;
                    }),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2
                ]
            ]
        ];
    }

    /**
     * Get program distribution
     */
    private function getProgramDistribution()
    {
        try {
            $programData = ScholarshipRecord::join('courses', 'scholarship_records.course_id', '=', 'courses.id')
                ->join('scholarship_programs', 'courses.scholarship_program_id', '=', 'scholarship_programs.id')
                ->select('scholarship_programs.shortname as program', DB::raw('COUNT(*) as count'))
                ->whereNotNull('scholarship_records.course_id')
                ->whereNotNull('courses.scholarship_program_id')
                ->groupBy('scholarship_programs.id', 'scholarship_programs.shortname')
                ->orderBy('count', 'desc')
                ->get();

            if ($programData->isEmpty()) {
                return $this->getEmptyPieChartData();
            }

            return [
                'labels' => $programData->pluck('program'),
                'datasets' => [
                    [
                        'data' => $programData->pluck('count'),
                        'backgroundColor' => [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40',
                            '#FF6384',
                            '#C9CBCF'
                        ],
                        'hoverBackgroundColor' => [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40',
                            '#FF6384',
                            '#C9CBCF'
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in getProgramDistribution: ' . $e->getMessage());
            return $this->getEmptyPieChartData();
        }
    }

    /**
     * Get course distribution
     */
    private function getCourseDistribution()
    {
        try {
            $courseData = ScholarshipRecord::join('courses', 'scholarship_records.course_id', '=', 'courses.id')
                ->select('courses.shortname as course', DB::raw('COUNT(*) as count'))
                ->whereNotNull('scholarship_records.course_id')
                ->groupBy('courses.id', 'courses.shortname')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            if ($courseData->isEmpty()) {
                return $this->getEmptyChartData();
            }

            return [
                'labels' => $courseData->pluck('course'),
                'datasets' => [
                    [
                        'label' => 'Students',
                        'data' => $courseData->pluck('count'),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in getCourseDistribution: ' . $e->getMessage());
            return $this->getEmptyChartData();
        }
    }

    /**
     * Get status distribution
     */
    private function getStatusDistribution()
    {
        $statusData = ScholarshipRecord::select(
            DB::raw('CASE 
                WHEN scholarship_status = 0 THEN "Pending"
                WHEN scholarship_status = 1 THEN "Approved"
                WHEN scholarship_status = 2 THEN "Completed"
                WHEN scholarship_status = 3 THEN "Suspended"
                WHEN scholarship_status = 4 THEN "Cancelled"
                ELSE "Unknown"
            END as status'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('scholarship_status')
            ->get();

        return [
            'labels' => $statusData->pluck('status'),
            'datasets' => [
                [
                    'data' => $statusData->pluck('count'),
                    'backgroundColor' => [
                        '#FFA726', // Pending - Orange
                        '#66BB6A', // Approved - Green
                        '#42A5F5', // Completed - Blue
                        '#EF5350', // Suspended - Red
                        '#AB47BC'  // Cancelled - Purple
                    ]
                ]
            ]
        ];
    }

    /**
     * Get school distribution
     */
    private function getSchoolDistribution()
    {
        try {
            $schoolData = ScholarshipRecord::join('schools', 'scholarship_records.school_id', '=', 'schools.id')
                ->select('schools.shortname as school', DB::raw('COUNT(*) as count'))
                ->whereNotNull('scholarship_records.school_id')
                ->groupBy('schools.id', 'schools.shortname')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            if ($schoolData->isEmpty()) {
                return $this->getEmptyChartData();
            }

            return [
                'labels' => $schoolData->pluck('school'),
                'datasets' => [
                    [
                        'label' => 'Students',
                        'data' => $schoolData->pluck('count'),
                        'backgroundColor' => 'rgba(153, 102, 255, 0.8)',
                        'borderColor' => 'rgba(153, 102, 255, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSchoolDistribution: ' . $e->getMessage());
            return $this->getEmptyChartData();
        }
    }

    /**
     * Get recent statistics
     */
    private function getRecentStatistics()
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $thisWeek = Carbon::now()->startOfWeek();
            $thisMonth = Carbon::now()->startOfMonth();

            return [
                'today' => ScholarshipRecord::whereDate('date_filed', $today)->count(),
                'yesterday' => ScholarshipRecord::whereDate('date_filed', $yesterday)->count(),
                'thisWeek' => ScholarshipRecord::where('date_filed', '>=', $thisWeek)->count(),
                'thisMonth' => ScholarshipRecord::where('date_filed', '>=', $thisMonth)->count(),
                'total' => ScholarshipRecord::count(),
                'totalProfiles' => ScholarshipProfile::count(),
                'pendingApplications' => ScholarshipRecord::where('scholarship_status', 0)->count(),
                'approvedApplications' => ScholarshipRecord::where('scholarship_status', 1)->count(),
                'completedApplications' => ScholarshipRecord::where('scholarship_status', 2)->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error in getRecentStatistics: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    /**
     * Get yearly trends
     */
    private function getYearlyTrends()
    {
        $yearlyData = ScholarshipRecord::select(
            DB::raw('YEAR(date_filed) as year'),
            DB::raw('COUNT(*) as applications'),
            DB::raw('SUM(CASE WHEN scholarship_status = 1 THEN 1 ELSE 0 END) as approved')
        )
            ->groupBy(DB::raw('YEAR(date_filed)'))
            ->orderBy('year')
            ->get();

        return [
            'labels' => $yearlyData->pluck('year'),
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $yearlyData->pluck('applications'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'fill' => false
                ],
                [
                    'label' => 'Approved',
                    'data' => $yearlyData->pluck('approved'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => false
                ]
            ]
        ];
    }

    /**
     * Get empty chart data for error fallback
     */
    private function getEmptyChartData()
    {
        return [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'No Data',
                    'data' => [],
                    'backgroundColor' => 'rgba(200, 200, 200, 0.2)',
                    'borderColor' => 'rgba(200, 200, 200, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Get empty pie chart data for error fallback
     */
    private function getEmptyPieChartData()
    {
        return [
            'labels' => ['No Data'],
            'datasets' => [
                [
                    'data' => [1],
                    'backgroundColor' => ['rgba(200, 200, 200, 0.8)'],
                    'hoverBackgroundColor' => ['rgba(200, 200, 200, 1)']
                ]
            ]
        ];
    }

    /**
     * Get empty stats for error fallback
     */
    private function getEmptyStats()
    {
        return [
            'today' => 0,
            'yesterday' => 0,
            'thisWeek' => 0,
            'thisMonth' => 0,
            'total' => 0,
            'totalProfiles' => 0,
            'pendingApplications' => 0,
            'approvedApplications' => 0,
            'completedApplications' => 0
        ];
    }
}
