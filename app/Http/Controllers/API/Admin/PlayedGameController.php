<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PlayGame;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class PlayedGameController extends Controller
{
    public function played_game(Request $request)
    {
        set_time_limit(120); // Increase the execution time limit

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()], 403);
            }

            $validated = $validator->validated();
            $categoryId = $validated['category_id'];

            // Check if category is opened
            $category = Category::where('id', $categoryId)->where('status', 'opened')->first();
            if (!$category) {
                return response()->json(['status' => 404, 'message' => 'Category not found or not opened.'], 404);
            }

            $today_open_number = $category->no_open;
            $chunkSize = 100; // Adjust chunk size as needed

            PlayGame::where('category_id', $categoryId)
                ->where('status', 'waiting')
                ->chunk($chunkSize, function ($playedGames) use ($today_open_number) {
                    foreach ($playedGames as $game) {
                        $this->processGame($game, $today_open_number);
                    }
                });

            return response()->json(['status' => 200, 'message' => 'Played games processed successfully.'], 200);
        } catch (\Throwable $th) {
            Log::error("Error occurred: " . $th->getMessage());
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing your request.'], 500);
        }
    }

    private function processGame($game, $today_open_number)
    {
        $play_game_id = $game->play_game_id;
        $user_entered_numbers = (string) $game->entered_number;
        $user_entered_amount = (float) $game->entered_amount;
        $user_id = $game->user_id;
        $play_types = $game->play_type;

        // Retrieve user
        $user = User::find($user_id);
        if (!$user) {
            Log::warning("User not found for ID: $user_id, skipping game processing.");
            return;
        }

        $user_balance = $user->balance;
        $available_balance = $user_balance;

        $won_amount = 0;
        $loss_amount = 0;
        $status = 'waiting'; // Default status
        $transaction_type = 'debit'; // Default transaction type

        // Log values for debugging
        Log::info("Processing Game ID: $game->id with Entered Number: $user_entered_numbers, Today's Number: $today_open_number");

        // Process the game based on play_game_id
        switch ($play_game_id) {
            case 1:
            case 3:
            case 4:
                // Compare entered numbers with the open number
                if ($today_open_number == $user_entered_numbers) {
                    $won_amount = $user_entered_amount * 95; // Adjust multiplier as needed
                    $available_balance += $won_amount; // Update available balance before creating the transaction
                    $status = 'won';
                    $transaction_type = 'credit';
                } else {
                    $loss_amount = $user_entered_amount;
                    $status = 'lost';
                    $transaction_type = 'debit';
                }
                break;

            case 2:
                // Compare entered numbers with the open number for specific play types
                $check_today_numbers = (string) $today_open_number;
                $first_today_number = $check_today_numbers[0] ?? null;
                $second_today_number = $check_today_numbers[1] ?? null;
                $first_today_value = (int) $first_today_number;
                $second_today_value = (int) $second_today_number;
                $today_entered_numbers = (int) $user_entered_numbers;

                if ($play_types === 'ander_harup') {
                    if ($first_today_value === $today_entered_numbers) {
                        $won_amount = $user_entered_amount * 9.5;
                        $available_balance += $won_amount; // Update available balance before creating the transaction
                        $status = 'won';
                        $transaction_type = 'credit';
                    } else {
                        $loss_amount = $user_entered_amount;
                        $status = 'lost';
                        $transaction_type = 'debit';
                    }
                } elseif ($play_types === 'bahar_harup') {
                    if ($second_today_value === $today_entered_numbers) {
                        $won_amount = $user_entered_amount * 9.5;
                        $available_balance += $won_amount; // Update available balance before creating the transaction
                        $status = 'won';
                        $transaction_type = 'credit';
                    } else {
                        $loss_amount = $user_entered_amount;
                        $status = 'lost';
                        $transaction_type = 'debit';
                    }
                }
                break;

            default:
                Log::warning("Unknown play_game_id: $play_game_id, skipping game processing.");
                return; // Skip processing for unknown play_game_id
        }

        // Log final values before update
        Log::info("Final Status: $status, Won Amount: $won_amount, Loss Amount: $loss_amount, Available Balance: $available_balance");

        // Create transaction
        Transaction::create([
            'user_id' => $user_id,
            'transaction_type' => $transaction_type,
            'amount' => $status === 'won' ? $won_amount : $loss_amount,
            'description' => 'Game Result: ' . ucfirst($status),
            'transaction_date' => Carbon::now(),
            'available_balance' => $available_balance
        ]);

        // Update user balance only if won
        if ($status === 'won') {
            $user->balance = $available_balance;
            if ($user->save()) {
                Log::info("Balance updated successfully for User ID: $user_id, New Balance: $available_balance");
            } else {
                Log::error("Failed to update balance for User ID: $user_id");
            }
        }

        // Update played game status and amounts
        $game->update([
            'status' => $status,
            'won_amount' => $status === 'won' ? $won_amount : null,
            'loss_amount' => $status === 'lost' ? $loss_amount : null
        ]);

        // Log after update
        Log::info("Updated Game ID: $game->id with Status: $status, Won Amount: $won_amount, Loss Amount: $loss_amount");
    }
}
