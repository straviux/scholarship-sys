<?php

namespace App\Http\Controllers;

use App\Services\JasperReportService;
use Illuminate\Http\Request;

/**
 * JasperReportController
 * 
 * Controller for generating JasperReports
 */
class JasperReportController extends Controller
{
    /**
     * The JasperReportService instance
     */
    protected $jasper;

    /**
     * Constructor
     * Inject JasperReportService
     */
    public function __construct(JasperReportService $jasper)
    {
        $this->jasper = $jasper;
    }

    // Add your report methods here
}
