<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Scholar;
use App\Models\Applicant;
use App\Models\FundTransaction;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Show the user profile page
     */
    public function show()
    {
        $user = auth()->user();

        // Load user roles
        $user->load('roles');

        // Get statistics based on user role
        $stats = $this->getStatistics($user);

        // Get encoded data (applicants, scholars, vouchers)
        $encodedData = $this->getEncodedData($user);

        return Inertia::render('User/Profile', [
            'user' => $user,
            'stats' => $stats,
            'encodedData' => $encodedData,
        ]);
    }

    /**
     * Get statistics based on user role
     */
    private function getStatistics($user)
    {
        $query = [];

        if ($user->hasAnyRole(['administrator', 'admin'])) {
            // Admin sees all data
            $query['total_applicants'] = Applicant::count();
            $query['total_scholars'] = Scholar::where('status', 'active')->count();
            $query['total_vouchers'] = FundTransaction::count();
            $query['pending_applications'] = Applicant::where('status', 'pending')->count();
        } elseif ($user->hasRole('program_manager')) {
            // Program manager sees their program's data
            $programs = $user->programs()->pluck('id');
            $query['total_applicants'] = Applicant::whereIn('program_id', $programs)->count();
            $query['total_scholars'] = Scholar::whereIn('program_id', $programs)->where('status', 'active')->count();
            $query['total_vouchers'] = FundTransaction::whereIn('program_id', $programs)->count();
            $query['pending_applications'] = Applicant::whereIn('program_id', $programs)->where('status', 'pending')->count();
        }

        return $query;
    }

    /**
     * Get encoded data (applicants, scholars, vouchers)
     */
    private function getEncodedData($user)
    {
        $data = [];

        if ($user->hasAnyRole(['administrator', 'admin'])) {
            // Admin sees all data
            $data['applicants'] = Applicant::with('program')
                ->latest()
                ->limit(10)
                ->get();

            $data['scholars'] = Scholar::with('program')
                ->where('status', 'active')
                ->latest()
                ->limit(10)
                ->get();

            $data['vouchers'] = FundTransaction::with('program')
                ->latest()
                ->limit(10)
                ->get();
        } elseif ($user->hasRole('program_manager')) {
            // Program manager sees their program's data
            $programs = $user->programs()->pluck('id');

            $data['applicants'] = Applicant::whereIn('program_id', $programs)
                ->with('program')
                ->latest()
                ->limit(10)
                ->get();

            $data['scholars'] = Scholar::whereIn('program_id', $programs)
                ->where('status', 'active')
                ->with('program')
                ->latest()
                ->limit(10)
                ->get();

            $data['vouchers'] = FundTransaction::whereIn('program_id', $programs)
                ->with('program')
                ->latest()
                ->limit(10)
                ->get();
        }

        return $data;
    }
}
