<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Disease;
use Illuminate\Support\Facades\Validator;

class DiseaseController extends Controller
{
    /**
     * Get information about a specific disease.
     */
    public function getDiseaseInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Try searching by name case-insensitively
        $disease = Disease::where('name', 'LIKE', '%' . $request->name . '%')->first();

        if (!$disease) {
            return response()->json([
                'message' => 'Disease not found in knowledge base.'
            ], 404);
        }

        return response()->json([
            'name' => $disease->name,
            'note' => $disease->note,
            'solutions' => $disease->solutions,
        ]);
    }
}
