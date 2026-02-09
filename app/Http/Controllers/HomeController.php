<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the home/portal page.
     */
    public function index()
    {
        return Inertia::render('Home/Index', [
            'pendingApplicants' => 0,
            'pendingRequirements' => 0,
            'activeApplications' => 0,
            'recentAnnouncements' => $this->getRecentAnnouncements(),
        ]);
    }

    /**
     * Get recent announcements for display on home page.
     */
    private function getRecentAnnouncements()
    {
        return [
            [
                'id' => 1,
                'title' => 'Application Deadline Extended',
                'message' => 'The scholarship application deadline has been extended to March 31, 2026.',
                'type' => 'Update',
                'date' => 'February 6, 2026',
            ],
            [
                'id' => 2,
                'title' => 'New Scholarship Program Available',
                'message' => 'Check out our newly launched STEM Excellence Scholarship program.',
                'type' => 'New',
                'date' => 'February 3, 2026',
            ],
            [
                'id' => 3,
                'title' => 'Required Documentation Update',
                'message' => 'Please review the updated list of required documents before submitting your application.',
                'type' => 'Reminder',
                'date' => 'February 1, 2026',
            ],
        ];
    }
}
