<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class TestPageController extends Controller
{
    /**
     * Show OBR Tracking Test Page
     */
    public function obrTest()
    {
        return Inertia::render('OBRTrackingTimeline');
    }
}
