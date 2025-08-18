<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoterResource;
use Illuminate\Http\Request;
use App\Models\Voter;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{

    public function initVoters($file)
    {
        $records = File::json(base_path('storage/app/' . $file));
        // dd($records);
        foreach ($records['voters'] as $record) {
            $voter = new Voter;
            $voter->voter_name = $record['voter_name'];
            $voter->precinct_no = $record['precinct_no'];
            $voter->barangay_name = $record['barangay_name'];
            $voter->municipality_name = $record['municipality_name'];
            $voter->pro_voter_id = $record['pro_voter_id'];
            $voter->save();
        }

        return $file . ' initiated successfully';
    }

    public function findVoter(): Response
    {

        $mun = Voter::distinct()->pluck('municipality_name');

        $name = app()->request['searchname'];
        $list_year = app()->request['list_year'];
        $municipality = app()->request['filtermunicipality'];
        $barangay = app()->request['filterbarangay'];
        $precinct = app()->request['filterprecinct'];
        $showresults = app()->request['results'] ?? 100;

        $bgy = $municipality ? Voter::where('municipality_name', '=', $municipality)->distinct()->pluck('barangay_name') : [];
        $query = !$name ? Voter::where('elect_year', '=', $list_year)->where('municipality_name', '=', $municipality)->where('barangay_name', 'LIKE', "%{$barangay}%")
            ->where('voter_name', 'LIKE', "%{$name}%")->where('precinct_no', 'LIKE', "%{$precinct}%") :
            Voter::where('elect_year', '=', $list_year)->where('municipality_name', 'LIKE', "%{$municipality}%")->where('barangay_name', 'LIKE', "%{$barangay}%")
            ->where('voter_name', 'LIKE', "%{$name}%")->where('precinct_no', 'LIKE', "%{$precinct}%");

        return Inertia::render('VotersList/VotersListIndex', [
            'q' => ['searchname' => $name, 'filtermunicipality' => $municipality, 'filterbarangay' => $barangay, 'filterprecinct' => $precinct, 'results' => $showresults],
            'municipalities' => $mun,
            'barangays' => $bgy,
            'precincts' => $barangay ? Voter::where('municipality_name', '=', $municipality)->where('barangay_name', '=', $barangay)->distinct()->pluck('precinct_no') : [],
            'voters' => !$name && !$municipality ? [] : VoterResource::collection(
                $query->paginate($showresults)->onEachSide(1)
                    ->through(function ($voter) {
                        return $voter;
                    })
                    ->withQueryString()
            ),
            'search_count' => $query->count(),
            'total_count' => Voter::where('elect_year', '=', $list_year)->count(),

        ]);
    }

    public function findVoterApi(Request $request)
    {
        // $bgy = Voter::distinct()->get('barangay_name')->toArray();
        $name = $request->searchname ?? "";
        $municipality = $request->municipality ?? "";
        $barangay = $request->barangay ?? "";
        // $precinct = $request->barangay;
        // // // $showresults = app()->request['results'] ?? 100;
        $query = Voter::where('elect_year', '=', 2025)->where('municipality_name', '=', $municipality)->where('barangay_name', 'LIKE', "%{$barangay}%")
            ->where('voter_name', 'LIKE', "%{$name}%")->limit(10)->get();

        return response()->json(VoterResource::collection($query));
    }
}
