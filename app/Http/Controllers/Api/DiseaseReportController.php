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

        // Send Push Notification to other supervisors in the district
        try {
            $supervisor = \App\Models\User::find($request->user_id);
            if ($supervisor && !empty($supervisor->district)) {
                $firebaseService = new \App\Services\FirebaseService();
                $topic = 'district_' . $supervisor->district;
                $title = "New Disease Reported in " . $supervisor->district;
                $body = "Supervisor " . $supervisor->username . " reported " . $diseaseName;
                
                $firebaseService->sendNotificationToTopic($topic, $title, $body, [
                    'report_id' => (string)$report->id,
                    'disease' => $diseaseName,
                    'supervisor' => $supervisor->username,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Notification failed: " . $e->getMessage());
        }

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

    /**
     * Get all disease reports for a specific supervisor.
     */
    public function getSupervisorReports(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Fetch reports where user_id matches supervisor_id
        $reports = DiseaseReport::with(['farmer', 'diseaseInfo'])
            ->where('user_id', $request->supervisor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch all standard diseases to do fuzzy matching for existing inconsistent data
        $allDiseases = \App\Models\Disease::all();

        // Transform the data to ensure specific fields are easy to access
        $data = $reports->map(function ($report) use ($allDiseases) {
            $solutions = 'No solutions available';
            
            // 1. Try direct relationship match first
            if ($report->diseaseInfo) {
                $solutions = $report->diseaseInfo->solutions;
            } else {
                // 2. Fuzzy matching: replace underscores with spaces and remove all spaces for comparison
                $normalizedReportName = strtolower(str_replace(['_', ' '], '', $report->disease_name));
                
                $matchedDisease = $allDiseases->first(function ($disease) use ($normalizedReportName) {
                    $normalizedDbName = strtolower(str_replace(['_', ' '], '', $disease->name));
                    return $normalizedDbName === $normalizedReportName;
                });

                if ($matchedDisease) {
                    $solutions = $matchedDisease->solutions;
                }
            }

            return [
                'report_id' => $report->id,
                'farmer_id' => $report->farmer_id,
                'farmer_name' => $report->farmer ? $report->farmer->name : 'Unknown',
                'disease_name' => $report->disease_name,
                'disease_image' => url($report->disease_image),
                'customer_note' => $report->customer_note,
                'recommend_solutions' => $solutions,
                'created_at' => $report->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all disease reports/alerts in the supervisor's district.
     */
    public function getDistrictAlerts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $supervisor = \App\Models\User::find($request->supervisor_id);
        $district = $supervisor->district;

        if (empty($district)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Fetch reports where the reporter's district OR the farmer's district matches
        $reports = DiseaseReport::with(['farmer', 'user'])
            ->where(function($query) use ($district) {
                $query->whereHas('user', function($q) use ($district) {
                    $q->where('district', $district);
                })->orWhereHas('farmer', function($q) use ($district) {
                    $q->where('district', $district);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch all standard diseases to do fuzzy matching for solutions
        $allDiseases = \App\Models\Disease::all();

        $data = $reports->map(function ($report) use ($allDiseases) {
            $solutions = 'No solutions available';
            
            if ($report->diseaseInfo) {
                $solutions = $report->diseaseInfo->solutions;
            } else {
                $normalizedReportName = strtolower(str_replace(['_', ' '], '', $report->disease_name));
                
                $matchedDisease = $allDiseases->first(function ($disease) use ($normalizedReportName) {
                    $normalizedDbName = strtolower(str_replace(['_', ' '], '', $disease->name));
                    return $normalizedDbName === $normalizedReportName;
                });

                if ($matchedDisease) {
                    $solutions = $matchedDisease->solutions;
                }
            }

            return [
                'id' => $report->id,
                'farmer' => $report->farmer ? $report->farmer->name : ($report->user ? $report->user->username : 'Unknown'),
                'disease' => $report->disease_name,
                'severity' => $this->calculateSeverity($report->disease_name),
                'time' => $report->created_at->diffForHumans(),
                'created_at' => $report->created_at->toIso8601String(),
                'read' => false,
                'district' => $report->farmer ? $report->farmer->district : ($report->user ? $report->user->district : 'Unknown'),
                'image' => $report->disease_image ? url($report->disease_image) : null,
                'note' => $report->customer_note ?? '',
                'solutions' => $solutions,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function calculateSeverity($diseaseName)
    {
        $normalized = strtolower(str_replace([' ', '_'], '', $diseaseName));
        if (str_contains($normalized, 'blight')) {
            return 'High';
        }
        if (str_contains($normalized, 'blast')) {
            return 'High';
        }
        if (str_contains($normalized, 'spot')) {
            return 'Medium';
        }
        return 'Low';
    }
}
