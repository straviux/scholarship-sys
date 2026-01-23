<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class HelpController extends Controller
{
    /**
     * Display the help and instructions page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Help/Index');
    }
}
