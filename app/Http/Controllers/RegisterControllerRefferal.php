<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class RegisterControllerRefferal extends Controller
{
    public function Signup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'mobile' => 'required|numeric|digits_between:10,15',
                'password' => 'required|string|min:8',
                'referral_code' => 'nullable|string|max:255|exists:users,referral_code'
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()], 403);
            }
            $validated = $validator->validated();
            $name = $validated['name'];
            $mobile = $validated['mobile'];
            $password = $validated['password'];
            $referral_code = $request->input('referral_code', null);
            $referrer = null;
            if ($referral_code) {
                $referrer = User::where('referral_code', $referral_code)->first();
            }
            $user = User::create([
                'name' => $name,
                'mobile' => $mobile,
                'password' => Hash::make($password),
                'referrer_id' => $referrer ? $referrer->id : null,
                'referral_code' => $this->generateUniqueReferralCode(),
                'balance' => 0,
            ]);
            if ($referrer) {
                Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                ]);
            }
            return response()->json(['status' => 'success', 'user' => $user], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|numeric',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 403);
        }

        $credentials = $request->only('mobile', 'password');
        $user = User::where('mobile', $credentials['mobile'])->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number is incorrect'], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Password is incorrect'], 401);
        }

        // At this point, both the mobile number and password are correct
        Auth::login($user);
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(['status' => 'success', 'token' => $token, 'user' => $user]);
    }

    private function generateUniqueReferralCode()
    {
        do {
            $code = Str::random(6);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function profile()
    {
        try {

            dd('check');
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function spend(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 403);
            }

            $validated = $validator->validated();
            $user = User::find($validated['user_id']);
            $amount = $validated['amount'];
            if ($user->balance < $amount) {
                return response()->json(['status' => 'error', 'message' => 'Insufficient balance. Please add more money to spend.'], 403);
            }
            $user->balance -= $amount;
            $user->save();
            $this->creditReferralBonus($user, $amount);
            return response()->json(['status' => 'success', 'message' => 'Amount spent and referral bonus credited'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }


    private function creditReferralBonus(User $user, $amount)
    {
        if ($user->referrer_id) {
            $referrer = User::find($user->referrer_id);
            if ($referrer) {
                $bonus = $amount * 0.05;
                $referrer->earnings += $bonus;
                $referrer->balance += $bonus;
                $referrer->save();
            }
        }
    }

    public function add_money(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $validated = $validator->validate();
            $amount = $validated['amount'];

            // Get authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            DB::beginTransaction();

            try {
                if ($user->referrer_id) {
                    $referrer = User::find($user->referrer_id);

                    if ($referrer) {
                        $bonusAmount = $amount * 0.05;
                        $referrer->balance += $bonusAmount;
                        $referrer->save();
                        $referrerTransaction = new Transaction();
                        $referrerTransaction->user_id = $referrer->id;
                        $referrerTransaction->transaction_type = 'bonus';
                        $referrerTransaction->amount = $bonusAmount;
                        $referrerTransaction->description = 'Referral bonus';
                        $referrerTransaction->available_balance = $referrer->balance;
                        $referrerTransaction->save();
                    }
                }

                // Update user's balance
                $user->balance += $amount;
                $user->save();

                // Create a transaction record for user's main transaction
                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->transaction_type = 'credit';
                $transaction->amount = $amount;
                $transaction->description = 'Added money to balance';
                $transaction->available_balance = $user->balance;
                $transaction->save();

                // Commit transaction if all actions succeed
                DB::commit();

                return response()->json(['status' => 'success', 'message' => 'Amount added to user balance'], 200);
            } catch (\Throwable $th) {
                // Rollback transaction if any error occurs
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function admin_logout(Request $request)
    {
        try {
            // Revoke the token for the current user
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Admin user logged out successfully.'
            ]);
        } catch (\Throwable $th) {
            // Handle any unexpected errors
            return response()->json([
                'message' => 'Failed to logout. Please try again later.'
            ], 500);
        }
    }
}
