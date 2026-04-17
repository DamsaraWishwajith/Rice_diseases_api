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
        // For now, listing all farmers. 
        // In a real app, you'd likely filter by the logged-in supervisor.
        $farmers = Farmer::with('supervisor')->get();
        return response()->json($farmers);
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
