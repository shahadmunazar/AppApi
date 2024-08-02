<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\PlayGame;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\WithdrawalMoney;
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
                    'won_amount' => null, 
                ]);
            }

            return response()->json(['message' => 'Game played successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }


    public function Add_money(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()]);
            }

            $validated = $validator->validate();
            $amount = $validated['amount'];

            $user = Auth::user();
            $user_id = $user->id;

            $existingTransaction = Transaction::where('user_id', $user_id)
                ->where('confirm_payment', 'not_confirm')
                ->first();

            if ($existingTransaction) {
                return response()->json([
                    'status' => '403',
                    'message' => 'Please wait for payment confirmation from admin.'
                ], 403);
            }
            DB::beginTransaction();
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->transaction_type = 'credit';
            $transaction->amount = $amount;
            $transaction->description = 'Added money to balance';
            $transaction->available_balance = $user->balance + $amount;
            $transaction->confirm_payment = 'not_confirm';
            $transaction->save();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Amount added to user balance'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to add amount. Please try again later.'], 500);
        }
    }


    public function Request_Add_money_list(Request $request)
    {
        try {
            $all_transaction_added = Transaction::where('confirm_payment', 'not_confirm')->where('transaction_type', 'credit')->get();
            return response()->json(['status' => 200, 'data' => $all_transaction_added]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to Get List. Please try again later.'], 500);
        }
    }

    public function confirm_payment(Request $request, $payment_id)
    {
        try {
            $transaction = Transaction::where('transaction_type', 'credit')->find($payment_id);
            if (!$transaction) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Transaction not found.',
                ], 403);
            }
            if ($transaction->confirm_payment === "received_successfully") {
                return response()->json(['status' => 403, 'message' => 'Payment Already Confirmed'], 403);
            }
            $user_id = $transaction->user_id;
            $user = User::find($user_id);
            if (!$user) {
                return response()->json([
                    'status' => 403,
                    'message' => 'User not found.',
                ], 403);
            }
            $referrer_id = $user->referrer_id;
            if ($referrer_id) {
                $referrer = User::find($referrer_id);
                if ($referrer) {
                    $bonusAmount = $transaction->amount * 0.05;
                    $referrer->balance += $bonusAmount;
                    $referrer->save();
                    $referrerTransaction = new Transaction();
                    $referrerTransaction->user_id = $referrer->id;
                    $referrerTransaction->transaction_type = 'bonus';
                    $referrerTransaction->amount = $bonusAmount;
                    $referrerTransaction->description = 'Referral bonus';
                    $referrerTransaction->available_balance = $referrer->balance;
                    $referrerTransaction->confirm_payment = 'received_successfully';
                    $referrerTransaction->save();
                }
            }
            $transaction->confirm_payment = 'received_successfully';
            $user->balance += $transaction->amount;
            $user->save();
            $transaction->save();
            return response()->json([
                'message' => 'Payment status updated successfully.',
                'transaction' => $transaction
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error occurred while updating the payment status.',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function Request_money(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_money' => 'required|integer',
                'mobile_no' => 'required|integer',
                'upi_id' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()]);
            }
            $user = Auth::user();
            $user_id = $user->id;
            $validated = $validator->validated();
            $check_already_Requested_money = WithdrawalMoney::where('user_id', $user_id)->where('withdrawal_money_status', 'not_accepted')->first();
            if ($check_already_Requested_money) {
                return response()->json(['status' => 403, 'message' => 'Please Wait You Already Make A Request To Withdrawal Money']);
            }
            $request_money = $validated['request_money'];
            $latest_withdrawal = Transaction::where('user_id', $user_id)
                ->where('transaction_type', 'withdrawal')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latest_withdrawal) {
                $total_balance_won = Transaction::where('user_id', $user_id)
                    ->where('transaction_type', 'won')
                    ->where('created_at', '>', $latest_withdrawal->created_at)
                    ->sum('amount');
            } else {
                $total_balance_won = Transaction::where('user_id', $user_id)
                    ->where('transaction_type', 'won')
                    ->sum('amount');
            }

            // Check if the total balance is sufficient
            if ($total_balance_won < $request_money) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient balance. You do not have enough money to request this amount.',
                ], 403);
            }

            if ($request_money > 4999) {
                // Additional validation for large requests
                $additional_validator = Validator::make($request->all(), [
                    'acount_holder_name' => 'required|string',
                    'account_number' => 'required|integer',
                    'ifsc_code' => 'required|string',
                    'bank_name' => 'required|string',
                    'branch_name' => 'required|string',
                ]);

                if ($additional_validator->fails()) {
                    return response()->json(['status' => 403, 'errors' => $additional_validator->errors()]);
                }
                $additional_validated = $additional_validator->validated();

                // Create the withdrawal request with additional details
                WithdrawalMoney::create([
                    'user_id' => $user_id,
                    'request_money' => $request_money,
                    'mobile_no' => $validated['mobile_no'] ?? null,
                    'upi_id' => $validated['upi_id'] ?? null,
                    'acount_holder_name' => $additional_validated['acount_holder_name'],
                    'account_number' => $additional_validated['account_number'],
                    'ifsc_code' => $additional_validated['ifsc_code'],
                    'bank_name' => $additional_validated['bank_name'],
                    'branch_name' => $additional_validated['branch_name'],
                    'withdrawal_status' => 'not_accepted', // default status
                ]);
            } else {
                // Handle requests less than or equal to 4999
                WithdrawalMoney::create([
                    'user_id' => $user_id,
                    'request_money' => $request_money,
                    'mobile_no' => $validated['mobile_no'] ?? null,
                    'upi_id' => $validated['upi_id'] ?? null,
                    'withdrawal_status' => 'not_accepted', // default status
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Request processed successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the request',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function AllTransaction(Request $request)
    {
        try {
            $user = Auth::user();
            $user_id  = $user->id;
            $all_transaction  = Transaction::where('user_id', $user_id)->where('confirm_payment', 'received_successfully')->get();
            return response()->json(['status' => 200, 'data' => $all_transaction, 'message' => 'All Transaction Get']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    public function WithdrawalMoney(Request $request)
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;
            $withdrawal_money = Transaction::where('user_id', $user_id)->where('transaction_type', 'withdrawal')->where('confirm_payment', 'received_successfully')->get();
            return response()->json(['status' => 200, 'data' => $withdrawal_money, 'message' => 'All Transaction withdrawal Money']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    public function AddMoneyList(Request $request)
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;
            $withdrawal_money = Transaction::where('user_id', $user_id)->where('transaction_type', 'credit')->where('confirm_payment', 'received_successfully')->get();
            return response()->json(['status' => 200, 'data' => $withdrawal_money, 'message' => 'All Transaction withdrawal Money']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    public function WonMoneyList(Request $request)
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;
            $withdrawal_money = Transaction::where('user_id', $user_id)->where('transaction_type', 'won')->where('confirm_payment', 'received_successfully')->get();
            return response()->json(['status' => 200, 'data' => $withdrawal_money, 'message' => 'All Transaction withdrawal Money']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred'], 500);

        }
    }


    

    


}
