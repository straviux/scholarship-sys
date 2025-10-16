<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Barangay;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    public function index()
    {
        $municipalities = Municipality::orderBy('name')->get();
        return response()->json($municipalities);
    }

    public function getBarangays($municipalityId)
    {
        $barangays = Barangay::where('municipality_id', $municipalityId)
            ->orderBy('name')
            ->get();
        return response()->json($barangays);
    }
}
