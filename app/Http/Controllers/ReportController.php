<?php

namespace App\Http\Controllers;

use App\Exports\WaitingListExport;
use App\Exports\ScholarshipReportExport;
use Illuminate\Http\Request;
use App\Models\ScholarshipProfile;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Get the Chrome executable path with fallback logic
     * Tries the configured path first, then fallback paths
     * 
     * @return string
     * @throws \Exception
     */
    protected function getChromePath()
    {
        $primaryPath = config('scholarship.browsershot.chrome_path');

        // Try primary path first
        if ($primaryPath && file_exists($primaryPath)) {
            return $primaryPath;
        }

        // Try fallback paths
        $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // If no valid path found, throw exception
        throw new \Exception(
            'Chrome executable not found. Please configure CHROME_PATH in your .env file or install Chrome/Chromium. ' .
                'Tried paths: ' . $primaryPath . ', ' . implode(', ', $fallbackPaths)
        );
    }

    /**
     * Generate a PDF waiting list report using Spatie/Browsershot.
     */
    public function generateWaitinglist(Request $request)
    {
        // Build query based on filters
        // $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
        //     $q->where('scholarship_status', 0)->latest('created_at');
        // }])->where('is_on_waiting_list', '=', 1);
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) {
                $q->where('scholarship_status', 0)
                    ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
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
        $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

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

        $html = View::make('waiting_list_report', [
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
            $browsershot = Browsershot::html($html)
                ->setChromePath($this->getChromePath())
                ->showBackground()
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
     * Generate an excel waiting list report using Laravel Excel.
     */
    public function generateExcelWaitinglist(Request $request)
    {
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) {
                $q->where('scholarship_status', 0)
                    ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
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
        $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && !$showJpmOnly && !$hideJpm;

        // Generate filename with current date and time
        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "scholarship_waiting_list_{$currentDateTime}.xlsx";

        return Excel::download(new WaitingListExport($profiles, $summary, $filters, $reportType, $canViewJpm), $filename);


        // $html = Excel::download('waiting_list_report', [
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
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant']);

        // Filter by approval status (pending, approved, auto_approved, declined)
        if ($request->filled('approval_status')) {
            $statuses = is_array($request->approval_status)
                ? $request->approval_status
                : explode(',', $request->approval_status);

            $query->whereHas('scholarshipGrant', function ($q) use ($statuses) {
                $q->whereIn('approval_status', $statuses);
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
            if (!$request->filled('approval_status')) {
                $summary['by_approval_status'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->approval_status) ? ucwords(str_replace('_', ' ', $grant->approval_status)) : 'No Status';
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
        $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

        // Handle grouping
        $groupBy = $request->input('group_by', 'none');
        $groupedProfiles = null;

        if ($reportType === 'list') {
            if ($groupBy === 'none') {
                $groupedProfiles = collect(['All Records' => $profiles]);
            } else {
                $groupedProfiles = $profiles->groupBy(function ($p) use ($groupBy) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;

                    switch ($groupBy) {
                        case 'approval_status':
                            return ($grant && $grant->approval_status) ? ucwords(str_replace('_', ' ', $grant->approval_status)) : 'No Status';
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
                            return $p->municipality ?: 'No Municipality';
                        default:
                            return 'All Records';
                    }
                })->sortKeys();
            }
        }

        $showSequenceNumbers = $request->filled('show_sequence_numbers') && in_array($request->show_sequence_numbers, [1, '1', true, 'true'], true);

        $html = View::make('scholarship_report', [
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
            $browsershot = Browsershot::html($html)
                ->setChromePath($this->getChromePath())
                ->showBackground()
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
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant']);

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $statuses = is_array($request->approval_status)
                ? $request->approval_status
                : explode(',', $request->approval_status);

            $query->whereHas('scholarshipGrant', function ($q) use ($statuses) {
                $q->whereIn('approval_status', $statuses);
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
            if (!$request->filled('approval_status')) {
                $summary['by_approval_status'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->approval_status) ? ucwords(str_replace('_', ' ', $grant->approval_status)) : 'No Status';
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
        $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && !$showJpmOnly && !$hideJpm;

        // Generate filename with current date and time
        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "scholarship_report_{$currentDateTime}.xlsx";

        return Excel::download(new ScholarshipReportExport($profiles, $summary, $filters, $reportType, $canViewJpm), $filename);
    }
}
