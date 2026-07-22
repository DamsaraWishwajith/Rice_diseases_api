<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Farmer;
use Illuminate\Support\Facades\Validator;

class FarmerController extends Controller
{
    /**
     * Display a listing of the farmers.
     */
    public function index()
    {
        $farmers = Farmer::with(['supervisor', 'diseaseReports' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();

        $data = $farmers->map(function ($farmer) {
            $reports      = $farmer->diseaseReports;
            $scansCount   = $reports->count();
            $latestReport = $reports->first(); // already sorted desc

            // Most recent disease (not Healthy)
            $activeDisease = $reports->first(function ($r) {
                return !in_array(strtolower($r->disease_name), ['healthy', 'none', '']);
            });

            return array_merge($farmer->toArray(), [
                'scans'     => $scansCount,
                'disease'   => $activeDisease ? $activeDisease->disease_name : 'None',
                'last_scan' => $latestReport ? $latestReport->created_at : null,
            ]);
        });

        return response()->json($data);
    }

    /**
     * Store a newly created farmer in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'variety' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $farmer = Farmer::create($request->all());

        return response()->json([
            'message' => 'Farmer created successfully',
            'farmer' => $farmer,
        ], 201);
    }
}
