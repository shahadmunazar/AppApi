<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\PlayGame;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllTransactionController extends Controller
{
    public function alltransaction(Request $request)
    {
        try {
            $transaction_type = $request->transaction_type;
            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }
            $user_id = $user->id;
            $allTransactions = Transaction::where('user_id', $user_id)->get();
            return response()->json(['status' => 200, 'data' => $allTransactions, 'message' => 'All Transactions']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function All_StateMents(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
            }

            $user_id = $user->id;
            $play_games_All = PlayGame::with('category')
                ->where('user_id', $user_id)
                ->get()
                ->map(function ($playGame) {
                    $data = [
                        'id' => $playGame->id,
                        'user_id' => $playGame->user_id,
                        'category_id' => $playGame->category_id,
                        'category_name' => $playGame->category->name,
                        'status' => $playGame->status,
                        'created_at' => $playGame->created_at,
                        'updated_at' => $playGame->updated_at,
                    ];

                    if ($playGame->status == 'won') {
                        $data['won_amount'] = $playGame->won_amount;
                        $data['today_number'] = $playGame->today_number;
                    } elseif ($playGame->status == 'lost') {
                        $data['loss_amount'] = $playGame->loss_amount;
                        $data['today_number'] = $playGame->today_number;
                    } elseif (in_array($playGame->status, ['waiting', 'not_opened'])) {
                        $data['message'] = 'waiting to open';
                    }

                    return $data;
                });

            return response()->json([
                'status' => 200,
                'data' => $play_games_All,
                'message' => 'Retrieved All Transactions',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
