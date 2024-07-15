<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\PlayGame;
use Illuminate\Http\Request;
use illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

// Import Validator facade

class PlayGameController extends Controller
{
    public function playGame(Request $request)
    {
        try {
            // Define validation rules
            $validator = Validator::make($request->all(), [
                'entered_number' => 'nullable|array',
                'entered_number.*' => 'nullable|numeric',
                'entered_amount' => 'nullable|array',
                'entered_amount.*' => 'nullable|numeric',
                'category_id' => 'nullable|string',
                'play_game_id' => 'nullable|string',
                'Playing_Name' => 'nullable|string',
                'play_type' => 'nullable|string',
                'ander_harup' => 'nullable|string',
                'bahar_harup' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
            $entered_number = $validated['entered_number'] ?? [];
            $entered_amount = $validated['entered_amount'] ?? [];
            $category_id = $validated['category_id'] ?? null;
            $Playing_Name = $validated['Playing_Name'] ?? null;
            $play_type = $validated['play_type'] ?? null;
            $ander_harup = $validated['ander_harup'] ?? null;
            $bahar_harup = $validated['bahar_harup'] ?? null;
            $play_game_id = $validated['play_game_id'] ?? null;

            $user = Auth::user();
            $user_id = $user->id;
            $user_name = $user->name;

            if ($Playing_Name === 'Double') {
                foreach ($entered_number as $index => $number) {
                    $amount = $entered_amount[$index] ?? 0;
                    $loss_amount = $amount;
                    $calculate_won_amount = $amount * 10; // Example winning calculation
                    PlayGame::create([
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'category_id' => $category_id,
                        'Playing_Name' => $Playing_Name,
                        'play_type' => $play_type,
                        'ander_harup' => $ander_harup,
                        'bahar_harup' => $bahar_harup,
                        'play_game_id' => $play_game_id,
                        'entered_number' => $number,
                        'entered_amount' => $amount,
                        'won_amount' => $calculate_won_amount,
                        'loss_amount' => $loss_amount,
                    ]);
                }
            } else {
                // Handle other cases where 'Playing_Name' is not 'Double'
                PlayGame::create([
                    'user_id' => $user_id,
                    'user_name' => $user_name,
                    'category_id' => $category_id,
                    'Playing_Name' => $Playing_Name,
                    'play_type' => $play_type,
                    'ander_harup' => $ander_harup,
                    'bahar_harup' => $bahar_harup,
                    'play_game_id' => $play_game_id,
                    'entered_number' => json_encode($entered_number), // Store array as JSON
                    'entered_amount' => json_encode($entered_amount), // Store array as JSON
                    'won_amount' => null, // No winning amount calculation for other cases
                ]);
            }

            return response()->json(['message' => 'Game played successfully'], 200);
        } catch (\Throwable $th) {
            // Handle any unexpected errors
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

}
