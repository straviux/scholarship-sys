<?php

namespace App\Http\Controllers;

use App\Exports\ApplicantExport;
use App\Exports\ScholarshipReportExport;
use Illuminate\Http\Request;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Get the Chrome executable path with fallback logic
     * Tries the configured path first, then fallback paths
     * If none found, returns null to let Browsershot use auto-detection
     * 
     * @return string|null
     */
    protected function getChromePath()
    {
        $primaryPath = config('scholarship.browsershot.chrome_path');

        // Try primary path first if explicitly configured
        if ($primaryPath && file_exists($primaryPath)) {
            return $primaryPath;
        }

        // Try fallback paths
        $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
        foreach ($fallbackPaths as $path) {
            // Check if path is a direct file
            if (file_exists($path) && is_file($path)) {
                return $path;
            }

            // Search recursively in directory for Chrome executable
            if (is_dir($path)) {
                $chromePath = $this->findChromeInDirectory($path);
                if ($chromePath && file_exists($chromePath)) {
                    return $chromePath;
                }
            }
        }

        // Return null to let Browsershot use auto-detection and Puppeteer to download Chrome if needed
        // This is safer than throwing an exception as Browsershot can find Chrome automatically
        return null;
    }

    /**
     * Get the grouping value for a profile based on the grouping field
     * 
     * @param ScholarshipProfile $profile
     * @param string $groupBy
     * @return string
     */
    private function getGroupValue($profile, $groupBy)
    {
        $grant = is_iterable($profile->scholarshipGrant) ? $profile->scholarshipGrant->first() : $profile->scholarshipGrant;

        switch ($groupBy) {
            case 'unified_status':
                return ($grant && $grant->unified_status) ? ucwords(str_replace('_', ' ', $grant->unified_status)) : 'No Status';
            case 'grant_provision':
                return ($grant && $grant->grant_provision) ? ucwords(str_replace('_', ' ', $grant->grant_provision)) : 'No Provision';
            case 'school':
                return ($grant && $grant->school) ? $grant->school->name : 'No School';
            case 'program':
                return ($grant && $grant->program) ? $grant->program->name : 'No Program';
            case 'course':
                return ($grant && $grant->course) ? $grant->course->name : 'No Course';
            case 'year_level':
                return ($grant && $grant->year_level) ? $grant->year_level : 'No Year Level';
            case 'municipality':
                return $profile->municipality ?: 'No Municipality';
            default:
                return 'All Records';
        }
    }

    /**
     * Recursively search for chrome.exe in a directory
     * Compatible with Windows and Unix-like systems
     *
     * @param string $directory
     * @return string|null
     */
    private function findChromeInDirectory($directory)
    {
        try {
            $iterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
            $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($recursiveIterator as $file) {
                if ($file->getFilename() === 'chrome.exe' && $file->isFile()) {
                    return $file->getRealPath();
                }
            }
        } catch (\Exception $e) {
            // If directory cannot be read, silently continue
        }

        return null;
    }

    /**
     * Generate a PDF applicants report using Spatie/Browsershot.
     */
    public function generateApplicantReport(Request $request)
    {
        // Build query based on filters
        // $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
        //     $q->where('unified_status', 'pending')->latest('created_at');
        // }])->where('is_on_waiting_list', '=', 1);
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) {
                $q->where('unified_status', 'pending')
                    ->orderBy('date_filed', 'desc')
                    ->orderBy('created_at', 'desc');
            });
        // Optionally, you can add date_approved filters/output here if needed



        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }
        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                $q->where(function ($subQuery) use ($schools) {
                    foreach ($schools as $school) {
                        $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                            ->orWhere('schools.name', 'like', '%' . $school . '%');
                    }
                });
            });
        }
        if ($request->filled('courses') || $request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                if ($request->filled('courses')) {
                    $courses = explode(',', $request->courses);
                    $cq->where(function ($subQuery) use ($courses) {
                        foreach ($courses as $course) {
                            $course = trim($course);
                            $subQuery->orWhere('courses.shortname', 'like', '%' . $course . '%')
                                ->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                } else {
                    $cq->where('courses.shortname', 'like', '%' . $request->course . '%')
                        ->orWhere('courses.name', 'like', '%' . $request->course . '%');
                }
            });
        }
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%')
                    ->whereNotNull('year_level');
            });
        }
        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
            });
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        // JPM Filters - handle both string and boolean values (and check for non-empty)
        if ($request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        if ($request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $summary = null;
        if ($reportType === 'summary') {
            // Generate summary based on filtered results
            // Only exclude summary if filter has single value (not multiple selections)
            $summary = [
                'total' => $profiles->count(),
            ];

            // Program summary: exclude only if single program selected
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'no_program';
                })->map(fn($group) => $group->count());
            }

            // School summary: always include (even if schools are filtered, show breakdown of selected schools)
            $summary['by_school'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->school) ? $grant->school->name : 'no_school';
            })->map(fn($group) => $group->count());

            // Course summary: always include (even if courses are filtered, show breakdown of selected courses)
            $summary['by_course'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->course) ? $grant->course->name : 'no_course';
            })->map(fn($group) => $group->count());

            // Year level summary: exclude only if single year level selected
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'no_year_level';
                })->map(fn($group) => $group->count());
            }
        }

        $filters = [
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'courses' => $request->get('courses', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            // 'date_from' => $request->get('date_from', ''),
            // 'date_to' => $request->get('date_to', ''),
            'date_filed' => ($request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->translatedFormat('F d, Y') : '')
                . ($request->get('date_from') && $request->get('date_to') ? ' to ' : '')
                . ($request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->translatedFormat('F d, Y') : ''),
            'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true),
            'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true),
        ];

        // Check if user has permission to view JPM highlighting
        // Only enable highlighting if user has permission AND enableJpmHighlighting is true
        $enableJpmHighlighting = $request->filled('enable_jpm_highlighting') && in_array($request->enable_jpm_highlighting, [1, '1', true, 'true'], true);
        $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true);
        $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true);
        $canViewJpm = $request->user() && $request->user()->can('jpm.view') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

        // Handle grouping based on group_by parameter
        $groupBy = $request->input('group_by', 'none');
        $groupedProfiles = null;

        if ($reportType === 'list') {
            if ($groupBy === 'none') {
                // No grouping - return all records in a single group
                $groupedProfiles = collect([
                    'All Records' => $profiles
                ]);
            } else {
                // Group profiles based on selected field
                $groupedProfiles = $profiles->groupBy(function ($p) use ($groupBy) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;

                    switch ($groupBy) {
                        case 'school':
                            return ($grant && $grant->school) ? $grant->school->name : 'No School';
                        case 'program':
                            return ($grant && $grant->program) ? $grant->program->name : 'No Program';
                        case 'course':
                            return ($grant && $grant->course) ? $grant->course->name : 'No Course';
                        case 'year_level':
                            return ($grant && $grant->year_level) ? $grant->year_level : 'No Year Level';
                        case 'municipality':
                            return $p->municipality ?: 'No Municipality';
                        default:
                            return 'All Records';
                    }
                })->sortKeys(); // Sort alphabetically by group name
            }
        }

        // Get show_sequence_numbers parameter
        $showSequenceNumbers = $request->filled('show_sequence_numbers') && in_array($request->show_sequence_numbers, [1, '1', true, 'true'], true);

        $html = View::make('applicants_report', [
            'profiles' => $profiles,
            'groupedProfiles' => $groupedProfiles,
            'groupBy' => $groupBy,
            'summary' => $summary,
            'reportType' => $reportType,
            'filters' => $filters,
            'canViewJpm' => $canViewJpm,
            'showSequenceNumbers' => $showSequenceNumbers,
        ])->render();

        $paperSize = $request->get('paper_size', 'A4');
        $orientation = $request->get('orientation', 'portrait');
        try {
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->showBackground()
                ->showBrowserHeaderAndFooter()
                ->footerHtml('<div class="report-footer" style="font-size: 9px; color: #444;position:fixed;right:0.5cm;bottom:0.1cm;">
                    <span>Generated on <span class="date "></span></span>
                    <span> - Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                </div>')
                ->margins(4, 4, 4, 4);



            if ($orientation === 'landscape') {
                $browsershot->landscape();
                // Handle PH Long custom size
            }
            // Handle PH Long custom size
            if ($paperSize === 'Long') {
                $browsershot->setPaperSize(215.9, 330.2);
            } else {
                $browsershot->format($paperSize);
            }

            $pdf = $browsershot->pdf();
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="scholarship_report.pdf"');
    }

    /**
     * Generate an excel applicants report using Laravel Excel.
     */
    public function generateExcelApplicants(Request $request)
    {
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) {
                $q->where('unified_status', 'pending')
                    ->orderBy('date_filed', 'desc')
                    ->orderBy('created_at', 'desc');
            });
        // Optionally, you can add date_approved filters/output here if needed



        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }
        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                $q->where(function ($subQuery) use ($schools) {
                    foreach ($schools as $school) {
                        $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                            ->orWhere('schools.name', 'like', '%' . $school . '%');
                    }
                });
            });
        }
        if ($request->filled('courses') || $request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                if ($request->filled('courses')) {
                    $courses = explode(',', $request->courses);
                    $cq->where(function ($subQuery) use ($courses) {
                        foreach ($courses as $course) {
                            $course = trim($course);
                            $subQuery->orWhere('courses.shortname', 'like', '%' . $course . '%')
                                ->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                } else {
                    $cq->where('courses.shortname', 'like', '%' . $request->course . '%')
                        ->orWhere('courses.name', 'like', '%' . $request->course . '%');
                }
            });
        }
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%')
                    ->whereNotNull('year_level');
            });
        }
        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
            });
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        // JPM Filters - handle both string and boolean values (and check for non-empty)
        if ($request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        if ($request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $summary = null;
        if ($reportType === 'summary') {
            // Generate summary based on filtered results
            // Only exclude summary if filter has single value (not multiple selections)
            $summary = [
                'total' => $profiles->count(),
            ];

            // Program summary: exclude only if single program selected
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'no_program';
                })->map(fn($group) => $group->count());
            }

            // School summary: always include (even if schools are filtered, show breakdown of selected schools)
            $summary['by_school'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->school) ? $grant->school->name : 'no_school';
            })->map(fn($group) => $group->count());

            // Course summary: always include (even if courses are filtered, show breakdown of selected courses)
            $summary['by_course'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->course) ? $grant->course->name : 'no_course';
            })->map(fn($group) => $group->count());

            // Year level summary: exclude only if single year level selected
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'no_year_level';
                })->map(fn($group) => $group->count());
            }
        }

        $filters = [
            'name' => $request->get('name', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'courses' => $request->get('courses', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            // 'date_from' => $request->get('date_from', ''),
            // 'date_to' => $request->get('date_to', ''),
            'date_filed' => ($request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->translatedFormat('F d, Y') : '')
                . ($request->get('date_from') && $request->get('date_to') ? ' to ' : '')
                . ($request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->translatedFormat('F d, Y') : ''),
            'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
            'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
        ];

        // Check if user has permission to view JPM highlighting
        // Disable JPM highlighting when show_jpm_only or hide_jpm filter is active
        $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
        $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
        $canViewJpm = $request->user() && $request->user()->can('jpm.view') && !$showJpmOnly && !$hideJpm;

        // Generate filename with current date and time
        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "scholarship_applicants_{$currentDateTime}.xlsx";

        return Excel::download(new ApplicantExport($profiles, $summary, $filters, $reportType, $canViewJpm), $filename);


        // $html = Excel::download('applicants_report', [
        //     'profiles' => $profiles,
        //     'summary' => $summary,
        //     'reportType' => $reportType,
        //     'filters' => $filters,
        // ]);
    }

    /**
     * Generate a PDF scholarship report using Spatie/Browsershot.
     * This method is for scholarship profiles (approved, pending, declined, etc.)
     */
    public function generateScholarshipPdf(Request $request)
    {
        // Build query based on filters
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
            // scholarshipStatus relationship removed - column no longer exists
        }]);

        // Filter by unified status (pending, approved, denied, active, completed)
        if ($request->filled('unified_status')) {
            $statuses = is_array($request->unified_status)
                ? $request->unified_status
                : explode(',', $request->unified_status);

            $query->whereHas('scholarshipGrant', function ($q) use ($statuses) {
                $q->whereIn('unified_status', $statuses);
            });
        }

        // Filter by grant provision (full, partial, tuition_only, rle_and_tuition)
        if ($request->filled('grant_provision')) {
            $provisions = is_array($request->grant_provision)
                ? $request->grant_provision
                : explode(',', $request->grant_provision);

            $query->whereHas('scholarshipGrant', function ($q) use ($provisions) {
                $q->whereIn('grant_provision', $provisions);
            });
        }

        // Existing filters
        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                $q->where(function ($subQuery) use ($schools) {
                    foreach ($schools as $school) {
                        $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                            ->orWhere('schools.name', 'like', '%' . $school . '%');
                    }
                });
            });
        }

        if ($request->filled('courses') || $request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                if ($request->filled('courses')) {
                    $courses = explode(',', $request->courses);
                    $cq->where(function ($subQuery) use ($courses) {
                        foreach ($courses as $course) {
                            $course = trim($course);
                            $subQuery->orWhere('courses.shortname', 'like', '%' . $course . '%')
                                ->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                } else {
                    $cq->where('courses.shortname', 'like', '%' . $request->course . '%')
                        ->orWhere('courses.name', 'like', '%' . $request->course . '%');
                }
            });
        }

        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%')
                    ->whereNotNull('year_level');
            });
        }

        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        // JPM Filters
        if ($request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        if ($request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $groupBy = $request->input('group_by', 'none');
        $groupBySecondary = $request->input('group_by_secondary', 'none');
        $groupByTertiary = $request->input('group_by_tertiary', 'none');
        $summary = null;

        if ($reportType === 'summary') {
            $summary = [
                'total' => $profiles->count(),
            ];

            // Approval status summary
            if (!$request->filled('unified_status')) {
                $summary['by_unified_status'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->unified_status) ? ucwords(str_replace('_', ' ', $grant->unified_status)) : 'No Status';
                })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                    if ($groupBy === 'unified_status' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                        $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                            return $this->getGroupValue($p, $groupBySecondary);
                        });
                        if ($groupBySecondary === 'year_level') {
                            $subgroups = $subgroups->sortBy(function ($count, $name) {
                                if (preg_match('/\d+/', $name, $matches)) {
                                    return (int)$matches[0];
                                }
                                return PHP_INT_MAX;
                            });
                        } else {
                            $subgroups = $subgroups->sortKeys();
                        }

                        // Handle tertiary grouping
                        if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                            return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                                $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                    return $this->getGroupValue($p, $groupByTertiary);
                                });
                                if ($groupByTertiary === 'year_level') {
                                    $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                        if (preg_match('/\d+/', $name, $matches)) {
                                            return (int)$matches[0];
                                        }
                                        return PHP_INT_MAX;
                                    });
                                } else {
                                    $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                                }
                                return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                            });
                        }

                        return $subgroups->map(fn($subgroup) => $subgroup->count());
                    }
                    return $group->count();
                });
            }

            // Grant provision summary
            if (!$request->filled('grant_provision')) {
                $summary['by_grant_provision'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->grant_provision) ? ucwords(str_replace('_', ' ', $grant->grant_provision)) : 'No Provision';
                })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                    if ($groupBy === 'grant_provision' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                        $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                            return $this->getGroupValue($p, $groupBySecondary);
                        });
                        if ($groupBySecondary === 'year_level') {
                            $subgroups = $subgroups->sortBy(function ($count, $name) {
                                if (preg_match('/\d+/', $name, $matches)) {
                                    return (int)$matches[0];
                                }
                                return PHP_INT_MAX;
                            });
                        } else {
                            $subgroups = $subgroups->sortKeys();
                        }

                        // Handle tertiary grouping
                        if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                            return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                                $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                    return $this->getGroupValue($p, $groupByTertiary);
                                });
                                if ($groupByTertiary === 'year_level') {
                                    $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                        if (preg_match('/\d+/', $name, $matches)) {
                                            return (int)$matches[0];
                                        }
                                        return PHP_INT_MAX;
                                    });
                                } else {
                                    $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                                }
                                return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                            });
                        }

                        return $subgroups->map(fn($subgroup) => $subgroup->count());
                    }
                    return $group->count();
                });
            }

            // Program summary
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'No Program';
                })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                    if ($groupBy === 'program' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                        $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                            return $this->getGroupValue($p, $groupBySecondary);
                        });
                        if ($groupBySecondary === 'year_level') {
                            $subgroups = $subgroups->sortBy(function ($count, $name) {
                                if (preg_match('/\d+/', $name, $matches)) {
                                    return (int)$matches[0];
                                }
                                return PHP_INT_MAX;
                            });
                        } else {
                            $subgroups = $subgroups->sortKeys();
                        }

                        // Handle tertiary grouping
                        if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                            return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                                $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                    return $this->getGroupValue($p, $groupByTertiary);
                                });
                                if ($groupByTertiary === 'year_level') {
                                    $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                        if (preg_match('/\d+/', $name, $matches)) {
                                            return (int)$matches[0];
                                        }
                                        return PHP_INT_MAX;
                                    });
                                } else {
                                    $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                                }
                                return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                            });
                        }

                        return $subgroups->map(fn($subgroup) => $subgroup->count());
                    }
                    return $group->count();
                });
            }

            // School summary
            $summary['by_school'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->school) ? $grant->school->name : 'No School';
            })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                if ($groupBy === 'school' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                    $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                        return $this->getGroupValue($p, $groupBySecondary);
                    });
                    if ($groupBySecondary === 'year_level') {
                        $subgroups = $subgroups->sortBy(function ($count, $name) {
                            if (preg_match('/\d+/', $name, $matches)) {
                                return (int)$matches[0];
                            }
                            return PHP_INT_MAX;
                        });
                    } else {
                        $subgroups = $subgroups->sortKeys();
                    }

                    // Handle tertiary grouping
                    if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                        return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                            $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                return $this->getGroupValue($p, $groupByTertiary);
                            });
                            if ($groupByTertiary === 'year_level') {
                                $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                    if (preg_match('/\d+/', $name, $matches)) {
                                        return (int)$matches[0];
                                    }
                                    return PHP_INT_MAX;
                                });
                            } else {
                                $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                            }
                            return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                        });
                    }

                    return $subgroups->map(fn($subgroup) => $subgroup->count());
                }
                return $group->count();
            });

            // Course summary
            $summary['by_course'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->course) ? $grant->course->name : 'No Course';
            })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                if ($groupBy === 'course' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                    $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                        return $this->getGroupValue($p, $groupBySecondary);
                    });
                    if ($groupBySecondary === 'year_level') {
                        $subgroups = $subgroups->sortBy(function ($count, $name) {
                            if (preg_match('/\d+/', $name, $matches)) {
                                return (int)$matches[0];
                            }
                            return PHP_INT_MAX;
                        });
                    } else {
                        $subgroups = $subgroups->sortKeys();
                    }

                    // Handle tertiary grouping
                    if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                        return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                            $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                return $this->getGroupValue($p, $groupByTertiary);
                            });
                            if ($groupByTertiary === 'year_level') {
                                $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                    if (preg_match('/\d+/', $name, $matches)) {
                                        return (int)$matches[0];
                                    }
                                    return PHP_INT_MAX;
                                });
                            } else {
                                $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                            }
                            return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                        });
                    }

                    return $subgroups->map(fn($subgroup) => $subgroup->count());
                }
                return $group->count();
            });

            // Year level summary
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'No Year Level';
                })->map(function ($group) use ($groupBy, $groupBySecondary, $groupByTertiary) {
                    if ($groupBy === 'year_level' && $groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                        $subgroups = $group->groupBy(function ($p) use ($groupBySecondary) {
                            return $this->getGroupValue($p, $groupBySecondary);
                        });
                        if ($groupBySecondary === 'year_level') {
                            $subgroups = $subgroups->sortBy(function ($count, $name) {
                                if (preg_match('/\d+/', $name, $matches)) {
                                    return (int)$matches[0];
                                }
                                return PHP_INT_MAX;
                            });
                        } else {
                            $subgroups = $subgroups->sortKeys();
                        }

                        // Handle tertiary grouping
                        if ($groupByTertiary !== 'none' && $groupByTertiary !== $groupBySecondary && $groupByTertiary !== $groupBy) {
                            return $subgroups->map(function ($tertiary_group) use ($groupByTertiary) {
                                $tertiary_subgroups = $tertiary_group->groupBy(function ($p) use ($groupByTertiary) {
                                    return $this->getGroupValue($p, $groupByTertiary);
                                });
                                if ($groupByTertiary === 'year_level') {
                                    $tertiary_subgroups = $tertiary_subgroups->sortBy(function ($count, $name) {
                                        if (preg_match('/\d+/', $name, $matches)) {
                                            return (int)$matches[0];
                                        }
                                        return PHP_INT_MAX;
                                    });
                                } else {
                                    $tertiary_subgroups = $tertiary_subgroups->sortKeys();
                                }
                                return $tertiary_subgroups->map(fn($tertiary) => $tertiary->count());
                            });
                        }

                        return $subgroups->map(fn($subgroup) => $subgroup->count());
                    }
                    return $group->count();
                });
            }
        }

        $filters = [
            'unified_status' => $request->get('unified_status', ''),
            'grant_provision' => $request->get('grant_provision', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'courses' => $request->get('courses', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'date_filed' => ($request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->translatedFormat('F d, Y') : '')
                . ($request->get('date_from') && $request->get('date_to') ? ' to ' : '')
                . ($request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->translatedFormat('F d, Y') : ''),
            'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true),
            'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true),
        ];

        // JPM highlighting permission
        $enableJpmHighlighting = $request->filled('enable_jpm_highlighting') && in_array($request->enable_jpm_highlighting, [1, '1', true, 'true'], true);
        $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true);
        $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true);
        $canViewJpm = $request->user() && $request->user()->can('jpm.view') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

        // Handle grouping
        $groupBy = $request->input('group_by', 'none');
        $groupBySecondary = $request->input('group_by_secondary', 'none');
        $groupedProfiles = null;

        if ($reportType === 'list') {
            if ($groupBy === 'none') {
                $groupedProfiles = collect(['All Records' => $profiles]);
            } else {
                // Primary grouping
                $groupedProfiles = $profiles->groupBy(function ($p) use ($groupBy) {
                    return $this->getGroupValue($p, $groupBy);
                })->sortKeys();

                // Secondary grouping (sub-grouping within primary groups)
                if ($groupBySecondary !== 'none' && $groupBySecondary !== $groupBy) {
                    $groupedProfiles = $groupedProfiles->map(function ($group) use ($groupBySecondary) {
                        return $group->groupBy(function ($p) use ($groupBySecondary) {
                            return $this->getGroupValue($p, $groupBySecondary);
                        })->sortKeys();
                    });
                }
            }
        }

        $showSequenceNumbers = $request->filled('show_sequence_numbers') && in_array($request->show_sequence_numbers, [1, '1', true, 'true'], true);
        $includeRemarks = $request->filled('include_remarks') && in_array($request->include_remarks, [1, '1', true, 'true'], true);
        $includeGrantProvision = $request->filled('include_grant_provision') && in_array($request->include_grant_provision, [1, '1', true, 'true'], true);

        $html = View::make('scholarship_report_pdf', [
            'profiles' => $profiles,
            'groupedProfiles' => $groupedProfiles,
            'groupBy' => $groupBy,
            'groupBySecondary' => $groupBySecondary,
            'summary' => $summary,
            'reportType' => $reportType,
            'filters' => $filters,
            'canViewJpm' => $canViewJpm,
            'showSequenceNumbers' => $showSequenceNumbers,
            'includeRemarks' => $includeRemarks,
            'includeGrantProvision' => $includeGrantProvision,
        ])->render();

        $paperSize = $request->get('paper_size', 'A4');
        $orientation = $request->get('orientation', 'portrait');

        try {
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->showBackground()
                ->showBrowserHeaderAndFooter()
                ->footerHtml('<div class="report-footer" style="font-size: 9px; color: #444;position:fixed;right:0.5cm;bottom:0.1cm;">
                    <span>Generated on <span class="date "></span></span>
                    <span> - Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                </div>')
                ->margins(4, 4, 4, 4);

            if ($orientation === 'landscape') {
                $browsershot->landscape();
            }

            if ($paperSize === 'Long') {
                $browsershot->setPaperSize(215.9, 330.2);
            } else {
                $browsershot->format($paperSize);
            }

            $pdf = $browsershot->pdf();
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="scholarship_report.pdf"');
    }

    /**
     * Generate an Excel scholarship report using Laravel Excel.
     * This method is for scholarship profiles (approved, pending, declined, etc.)
     */
    public function generateScholarshipExcel(Request $request)
    {
        // Build query based on filters
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
            // scholarshipStatus relationship removed - column no longer exists
        }]);

        // Filter by unified status
        if ($request->filled('unified_status')) {
            $statuses = is_array($request->unified_status)
                ? $request->unified_status
                : explode(',', $request->unified_status);

            $query->whereHas('scholarshipGrant', function ($q) use ($statuses) {
                $q->whereIn('unified_status', $statuses);
            });
        }

        // Filter by grant provision
        if ($request->filled('grant_provision')) {
            $provisions = is_array($request->grant_provision)
                ? $request->grant_provision
                : explode(',', $request->grant_provision);

            $query->whereHas('scholarshipGrant', function ($q) use ($provisions) {
                $q->whereIn('grant_provision', $provisions);
            });
        }

        // Existing filters
        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                $q->where(function ($subQuery) use ($schools) {
                    foreach ($schools as $school) {
                        $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                            ->orWhere('schools.name', 'like', '%' . $school . '%');
                    }
                });
            });
        }

        if ($request->filled('courses') || $request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                if ($request->filled('courses')) {
                    $courses = explode(',', $request->courses);
                    $cq->where(function ($subQuery) use ($courses) {
                        foreach ($courses as $course) {
                            $course = trim($course);
                            $subQuery->orWhere('courses.shortname', 'like', '%' . $course . '%')
                                ->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                } else {
                    $cq->where('courses.shortname', 'like', '%' . $request->course . '%')
                        ->orWhere('courses.name', 'like', '%' . $request->course . '%');
                }
            });
        }

        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%')
                    ->whereNotNull('year_level');
            });
        }

        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        // JPM Filters
        if ($request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        if ($request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $summary = null;

        if ($reportType === 'summary') {
            $summary = [
                'total' => $profiles->count(),
            ];

            // Approval status summary
            if (!$request->filled('unified_status')) {
                $summary['by_unified_status'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->unified_status) ? ucwords(str_replace('_', ' ', $grant->unified_status)) : 'No Status';
                })->map(fn($group) => $group->count());
            }

            // Grant provision summary
            if (!$request->filled('grant_provision')) {
                $summary['by_grant_provision'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->grant_provision) ? ucwords(str_replace('_', ' ', $grant->grant_provision)) : 'No Provision';
                })->map(fn($group) => $group->count());
            }

            // Program summary
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'No Program';
                })->map(fn($group) => $group->count());
            }

            // School summary
            $summary['by_school'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->school) ? $grant->school->name : 'No School';
            })->map(fn($group) => $group->count());

            // Course summary
            $summary['by_course'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->course) ? $grant->course->name : 'No Course';
            })->map(fn($group) => $group->count());

            // Year level summary
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'No Year Level';
                })->map(fn($group) => $group->count());
            }
        }

        $filters = [
            'approval_status' => $request->get('approval_status', ''),
            'grant_provision' => $request->get('grant_provision', ''),
            'name' => $request->get('name', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'courses' => $request->get('courses', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'date_filed' => ($request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->translatedFormat('F d, Y') : '')
                . ($request->get('date_from') && $request->get('date_to') ? ' to ' : '')
                . ($request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->translatedFormat('F d, Y') : ''),
            'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
            'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
        ];

        // JPM highlighting permission
        $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
        $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
        $canViewJpm = $request->user() && $request->user()->can('jpm.view') && !$showJpmOnly && !$hideJpm;

        // Include remarks and grant provision
        $includeRemarks = $request->filled('include_remarks') && in_array($request->include_remarks, [1, '1', true, 'true'], true);
        $includeGrantProvision = $request->filled('include_grant_provision') && in_array($request->include_grant_provision, [1, '1', true, 'true'], true);

        // Generate filename with current date and time
        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "scholarship_report_{$currentDateTime}.xlsx";

        return Excel::download(new ScholarshipReportExport($profiles, $summary, $filters, $reportType, $canViewJpm, $includeRemarks, $includeGrantProvision), $filename);
    }

    /**
     * Export selected applicants as PDF
     */
    public function exportSelectedPdf(Request $request)
    {
        // Get profile IDs from request
        $profileIds = array_filter(array_map('trim', explode(',', $request->input('profile_ids', ''))));

        if (empty($profileIds)) {
            return response()->json(['error' => 'No profiles selected'], 400);
        }

        // Fetch selected profiles
        $profiles = ScholarshipProfile::with(['scholarshipGrant'])
            ->whereIn('profile_id', $profileIds)
            ->get();

        if ($profiles->isEmpty()) {
            return response()->json(['error' => 'No profiles found'], 404);
        }

        $paperSize = $request->input('paper_size', 'A4');
        $orientation = $request->input('orientation', 'landscape');
        $includeRemarks = filter_var($request->input('include_remarks', false), FILTER_VALIDATE_BOOLEAN);
        $includeGrantProvision = filter_var($request->input('include_grant_provision', true), FILTER_VALIDATE_BOOLEAN);

        // Build filters array (empty for selected applicants)
        $filters = [];

        // Check if user has permission to view JPM highlighting
        $canViewJpm = $request->user() && $request->user()->can('jpm.view');

        // Always use list report type for selected applicants (no summary)
        $reportType = 'list';

        // Render HTML view for PDF
        $html = View::make('exports.selected-applicants', [
            'profiles' => $profiles,
            'reportType' => $reportType,
            'summary' => null,
            'filters' => $filters,
            'canViewJpm' => $canViewJpm,
            'paperSize' => $paperSize,
            'orientation' => $orientation,
            'includeRemarks' => $includeRemarks,
            'includeGrantProvision' => $includeGrantProvision,
        ])->render();

        try {
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->showBackground()
                ->showBrowserHeaderAndFooter()
                ->footerHtml('<div class="report-footer" style="font-size: 9px; color: #444;position:fixed;right:0.5cm;bottom:0.1cm;">
                    <span>Generated on <span class="date "></span></span>
                    <span> - Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                </div>')
                ->margins(4, 4, 4, 4);

            if ($orientation === 'landscape') {
                $browsershot->landscape();
            }

            // Handle PH Long custom size
            if ($paperSize === 'Legal') {
                $browsershot->setPaperSize(215.9, 330.2);
            } else {
                $browsershot->format($paperSize);
            }

            $pdf = $browsershot->pdf();

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="selected-applicants.pdf"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export selected applicants as Excel
     */
    public function exportSelectedExcel(Request $request)
    {
        // Get profile IDs from request
        $profileIds = array_filter(array_map('trim', explode(',', $request->input('profile_ids', ''))));

        if (empty($profileIds)) {
            return response()->json(['error' => 'No profiles selected'], 400);
        }

        // Fetch selected profiles
        $profiles = ScholarshipProfile::with(['scholarshipGrant'])
            ->whereIn('profile_id', $profileIds)
            ->get();

        if ($profiles->isEmpty()) {
            return response()->json(['error' => 'No profiles found'], 404);
        }

        // Always use list report type for selected applicants (no summary)
        $reportType = 'list';

        // Build filters array (empty for selected applicants)
        $filters = [];

        // Check if user has permission to view JPM highlighting
        $canViewJpm = $request->user() && $request->user()->can('jpm.view');

        // Get toggle parameters
        $includeRemarks = filter_var($request->input('include_remarks', false), FILTER_VALIDATE_BOOLEAN);
        $includeGrantProvision = filter_var($request->input('include_grant_provision', true), FILTER_VALIDATE_BOOLEAN);

        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "selected-applicants_{$currentDateTime}.xlsx";

        // Store the file temporarily
        $path = 'exports/' . $filename;
        Excel::store(
            new ScholarshipReportExport($profiles, null, $filters, $reportType, $canViewJpm, $includeRemarks, $includeGrantProvision),
            $path,
            'local'
        );

        // Read and return the file with inline disposition (view in browser)
        $file = Storage::disk('local')->get($path);

        // Clean up the temporary file
        Storage::disk('local')->delete($path);

        return response($file)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Export interviewed applicants as PDF
     */
    public function exportInterviewedPdf(Request $request)
    {
        $records = $this->getInterviewedRecords($request);

        if ($records->isEmpty()) {
            return response()->json(['error' => 'No records found'], 404);
        }

        $paperSize = $request->input('paper_size', 'A4');
        $orientation = $request->input('orientation', 'landscape');
        $includeAssessment = filter_var($request->input('include_assessment', true), FILTER_VALIDATE_BOOLEAN);
        $reportType = $request->input('report_type', 'list');
        $groupBy = $request->input('group_by', 'none');

        $html = View::make('exports.interviewed-applicants', [
            'records' => $records,
            'reportType' => $reportType,
            'groupBy' => $groupBy,
            'includeAssessment' => $includeAssessment,
            'paperSize' => $paperSize,
            'orientation' => $orientation,
        ])->render();

        try {
            $browsershot = Browsershot::html($html);

            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->showBackground()
                ->showBrowserHeaderAndFooter()
                ->footerHtml('<div style="font-size: 9px; color: #444; position:fixed; right:0.5cm; bottom:0.1cm;">
                    <span>Generated on <span class="date"></span></span>
                    <span> - Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                </div>')
                ->margins(4, 4, 4, 4);

            if ($orientation === 'landscape') {
                $browsershot->landscape();
            }

            if ($paperSize === 'Legal') {
                $browsershot->setPaperSize(215.9, 330.2);
            } else {
                $browsershot->format($paperSize);
            }

            $pdf = $browsershot->pdf();

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="interviewed-applicants.pdf"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export interviewed applicants as Excel
     */
    public function exportInterviewedExcel(Request $request)
    {
        $records = $this->getInterviewedRecords($request);

        if ($records->isEmpty()) {
            return response()->json(['error' => 'No records found'], 404);
        }

        $includeAssessment = filter_var($request->input('include_assessment', true), FILTER_VALIDATE_BOOLEAN);

        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "interviewed-applicants_{$currentDateTime}.xlsx";

        // Build spreadsheet data
        $headers = ['#', 'Last Name', 'First Name', 'Program', 'Course', 'Recommendation', 'Interview Date', 'Interviewer'];
        if ($includeAssessment) {
            array_splice($headers, 6, 0, ['Academic Potential', 'Financial Need', 'Communication Skills']);
        }

        $rows = [];
        foreach ($records as $idx => $record) {
            $row = [
                $idx + 1,
                $record->profile->last_name ?? '',
                $record->profile->first_name ?? '',
                $record->program->shortname ?? 'N/A',
                $record->course->shortname ?? 'N/A',
            ];
            if ($includeAssessment) {
                $row[] = ucfirst($record->academic_potential ?? 'N/A');
                $row[] = ucfirst($record->financial_need_level ?? 'N/A');
                $row[] = ucfirst($record->communication_skills ?? 'N/A');
            }
            $row[] = $this->formatRecommendationLabel($record->recommendation);
            $row[] = $record->interviewed_at ? \Carbon\Carbon::parse($record->interviewed_at)->format('M d, Y') : 'N/A';
            $row[] = $record->interviewer->name ?? 'N/A';
            $rows[] = $row;
        }

        // Create a simple Excel export using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Interviewed Applicants');

        // Write headers
        foreach ($headers as $colIdx => $header) {
            $sheet->setCellValue([$colIdx + 1, 1], $header);
        }

        // Style headers
        $headerStyle = $sheet->getStyle([1, 1, count($headers), 1]);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('E2E8F0');

        // Write rows
        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $value) {
                $sheet->setCellValue([$colIdx + 1, $rowIdx + 2], $value);
            }
        }

        // Auto-size columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $path = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $writer->save($path);

        $file = file_get_contents($path);
        unlink($path);

        return response($file)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Get interviewed records based on request filters
     */
    private function getInterviewedRecords(Request $request)
    {
        $ids = array_filter(array_map('trim', explode(',', $request->input('ids', ''))));

        $query = ScholarshipRecord::with([
            'profile' => function ($q) {
                $q->select('profile_id', 'first_name', 'last_name', 'middle_name', 'contact_no');
            },
            'program' => function ($q) {
                $q->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname');
            },
            'course' => function ($q) {
                $q->select('courses.id', 'courses.name', 'courses.shortname');
            },
            'school' => function ($q) {
                $q->select('schools.id', 'schools.name', 'schools.shortname');
            },
            'interviewer'
        ]);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->where('unified_status', 'interviewed');
        }

        return $query->orderBy('interviewed_at', 'desc')->get();
    }

    /**
     * Format recommendation label
     */
    private function formatRecommendationLabel($value)
    {
        $labels = [
            'recommended' => 'Recommended for Approval',
            'further_evaluation' => 'For Further Evaluation',
            'not_recommended' => 'Not Recommended',
        ];
        return $labels[$value] ?? 'N/A';
    }
}
