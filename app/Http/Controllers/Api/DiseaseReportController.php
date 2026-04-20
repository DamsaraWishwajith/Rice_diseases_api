<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DiseaseReport;
use Illuminate\Support\Facades\Validator;

class DiseaseReportController extends Controller
{
    /**
     * Store a newly created disease report in storage.
     */
    public function store(Request $request)
    {
        // Flexible validation to handle both standard and user-provided spellings
        $rules = [
            'user_id'       => 'required|exists:users,id',
            'farmer_id'     => 'nullable|exists:farmers,id',
            'customer_note' => 'nullable|string',
        ];

        if ($request->has('disease_name') || $request->has('diseas_name')) {
            $rules['disease_name'] = 'sometimes|string|max:255';
            $rules['diseas_name']  = 'sometimes|string|max:255';
        } else {
            $rules['disease_name'] = 'required|string|max:255';
        }

        if ($request->hasFile('disease_image') || $request->hasFile('diseas_image')) {
            $rules['disease_image'] = 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048';
            $rules['diseas_image']  = 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['disease_image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get values from whichever key was provided
        $diseaseName = $request->input('disease_name') ?? $request->input('diseas_name');
        $imageFile   = $request->file('disease_image')  ?? $request->file('diseas_image');

        $imagePath = null;
        if ($imageFile) {
            $imageName = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
            $imageFile->move(public_path('uploads/reports'), $imageName);
            $imagePath = 'uploads/reports/' . $imageName;
        }

        $report = DiseaseReport::create([
            'user_id'       => $request->user_id,
            'farmer_id'     => $request->farmer_id ?? null,
            'disease_name'  => $diseaseName,
            'disease_image' => $imagePath,
            'customer_note' => $request->customer_note,
        ]);

        return response()->json([
            'message' => 'Disease report saved successfully',
            'report'  => $report->load('farmer'),
        ], 201);
    }

    /**
     * Display a listing of reports for a user.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reports = DiseaseReport::with('farmer')
            ->where('user_id', $request->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reports);
    }
}
