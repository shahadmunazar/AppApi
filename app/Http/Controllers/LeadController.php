<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Lead;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20|unique:leads,mobile',
            'message' => 'nullable|string',
            'source' => 'nullable|string|max:255',
        ], [
            'mobile.unique' => 'you are already sent message with us we will contact you soon'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['ip'] = $request->ip();
        $lead = Lead::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully.',
            'data' => $lead
        ], 201);
    }
}
