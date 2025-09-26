<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScholarshipProfile;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class ReportController extends Controller
{
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
            $query->whereHas('scholarshipGrant.school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                $cq->where('shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('name', 'like', '%' . $request->course . '%');
            });
        }
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                $cq->where('shortname', 'like', '%' . $request->year_level . '%')
                    ->orWhere('name', 'like', '%' . $request->year_level . '%');
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

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $summary = null;
        if ($reportType === 'summary') {
            $summary = [
                'total' => $profiles->count(),
            ];
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'no_program';
                })->map(fn($group) => $group->count());
            }
            if (!$request->filled('school')) {
                $summary['by_school'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->school) ? $grant->school->name : 'no_school';
                })->map(fn($group) => $group->count());
            }
            if (!$request->filled('course')) {
                $summary['by_course'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->course) ? $grant->course->name : 'no_course';
                })->map(fn($group) => $group->count());
            }
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
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            // 'date_from' => $request->get('date_from', ''),
            // 'date_to' => $request->get('date_to', ''),
            'date_filed' => ($request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from'))->translatedFormat('F d, Y') : '')
                . ($request->get('date_from') && $request->get('date_to') ? ' to ' : '')
                . ($request->get('date_to') ? \Carbon\Carbon::parse($request->get('date_to'))->translatedFormat('F d, Y') : '')
        ];

        $html = View::make('waiting_list_report', [
            'profiles' => $profiles,
            'summary' => $summary,
            'reportType' => $reportType,
            'filters' => $filters,
        ])->render();

        $paperSize = $request->get('paper_size', 'A4');
        $orientation = $request->get('orientation', 'portrait');
        try {
            $browsershot = Browsershot::html($html)
                // Make sure to set the correct path to your Chrome or Chromium executable
                ->setChromePath('C:\Users\Administrator\.cache\puppeteer\chrome-headless-shell\win64-140.0.7339.82\chrome-headless-shell-win64\chrome-headless-shell.exe')
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
}
