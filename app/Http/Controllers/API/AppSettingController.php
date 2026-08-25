<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AppSettingController extends Controller
{
    /**
     * Get all app settings (Public API for Mobile App)
     */
    public function getSettings()
    {
        try {
            $settings = AppSetting::pluck('value', 'key')->toArray();

            // Structure the response to include default values if not set
            $response = [
                'qr_code_image' => isset($settings['qr_code']) ? asset($settings['qr_code']) : null,
                'app_version' => $settings['app_version'] ?? '1.0.0',
                'force_update' => isset($settings['force_update']) ? filter_var($settings['force_update'], FILTER_VALIDATE_BOOLEAN) : false,
                'update_url' => $settings['update_url'] ?? '',
            ];

            return response()->json([
                'status' => 'success',
                'data' => $response
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve settings',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the QR Code (Admin API)
     */
    public function updateQRCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'qr_code_image' => 'required|image|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 403);
            }

            $image = $request->file('qr_code_image');
            $imageName = time() . '_qr.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/qr_codes'), $imageName);

            $qrPath = 'uploads/qr_codes/' . $imageName;

            AppSetting::updateOrCreate(
                ['key' => 'qr_code'],
                ['value' => $qrPath]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'QR Code updated successfully',
                'qr_code_url' => asset($qrPath)
            ], 200);

        } catch (\Throwable $th) {
            \Log::error('Update QR Code Error: ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update QR Code'
            ], 500);
        }
    }

    /**
     * Update App Version details (Admin API)
     */
    public function updateAppVersion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'app_version' => 'required|string',
                'force_update' => 'required|boolean',
                'update_url' => 'required|url',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 403);
            }

            $validated = $validator->validated();

            AppSetting::updateOrCreate(['key' => 'app_version'], ['value' => $validated['app_version']]);
            AppSetting::updateOrCreate(['key' => 'force_update'], ['value' => $validated['force_update'] ? 'true' : 'false']);
            AppSetting::updateOrCreate(['key' => 'update_url'], ['value' => $validated['update_url']]);

            return response()->json([
                'status' => 'success',
                'message' => 'App Version details updated successfully'
            ], 200);

        } catch (\Throwable $th) {
            \Log::error('Update App Version Error: ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update App Version'
            ], 500);
        }
    }
}
