<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\WhatsAppNumber;
use Carbon\Carbon;
use App\Models\TodayResult;
use Illuminate\Support\Facades\Password;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class RegisterControllerRefferal extends Controller
{
    
    public function deleteToken(Request $request)
{
    try {
        // Get the user_id from the request
        $userid = $request->user_id;
        $user = User::find($userid);

    if ($user) {
        $token = $user->tokens()->where('name', 'authToken')->first();
        // dd($token);
        if ($token) {
            $token->delete();
            return response()->json(['message' => 'Token deleted successfully']);
        }

        return response()->json(['message' => 'Token not found'], 404);
    }

    return response()->json(['error' => 'User not found'], 404);
        
    } catch (\Exception $e) {
        // Handle any exceptions that occur
        return response()->json(['status' => 'error', 'message' => 'An error occurred', 'error' => $e->getMessage()], 500);
    }
}
   public function Signup(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'required|numeric|digits_between:10,15|unique:users,mobile',
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
        // Check if mobile number validation failed due to uniqueness constraint
        if (strpos($th->getMessage(), 'users_mobile_unique') !== false) {
            return response()->json(['status' => 'error', 'message' => 'This mobile number has already been taken.'], 422);
        }
        return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
    }
}

public function AdminRegister(Request $request){
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'required|numeric|digits_between:10,15|unique:users,mobile',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|string|min:8|same:password', // Confirm password validation
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
        // Check if mobile number validation failed due to uniqueness constraint
        if (strpos($th->getMessage(), 'users_mobile_unique') !== false) {
            return response()->json(['status' => 'error', 'message' => 'This mobile number has already been taken.'], 422);
        }
        return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
    }
}

public function change_password_admin(Request $request)
{
    try {
        // Create a validator instance
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'password' => 'required|string|min:8|confirmed', // Ensure password confirmation is present
        ]);

        // Check if the validation fails
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Fetch the user
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 403);
        }

        // Update the password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['status'=>200,'message' => 'Password changed successfully'], 200);

    } catch (\Exception $e) {
        // Handle errors
        return response()->json(['message' => 'An error occurred while changing the password', 'error' => $e->getMessage()], 500);
    }
}
// Route::post('add-new-users',[RegisterControllerRefferal::class,'AdminRegister']);
// Route::put('change-password-admin',[RegisterControllerRefferal::class,'change_password_admin']);


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
        $user = User::where('status',0)->where('mobile', $credentials['mobile'])->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number is incorrect'], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Password is incorrect'], 401);
        }
        
       

   
        Auth::login($user);
        $token = $user->createToken('authToken')->plainTextToken;
        return response()->json(['status' => 'success', 'token' => $token, 'user' => $user]);
    
        // At this point, both the mobile number and password are correct
        
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
    
    public function user_logout(Request $request)
    {
        try {
            if ($request->user()->role !== 'user') {
                return response()->json([
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'User logged out successfully.'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to logout. Please try again later.'
            ], 500);
        }
    }


    public function change_password(Request $request)
{
    try {
        $user = Auth::user();

        // Validate the request input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed', // The `confirmed` rule ensures a matching `new_password_confirmation` field
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 403);
        }

        // Check if the provided current password matches the user's actual password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 403,
                'message' => 'Current password is incorrect',
            ], 403);
        }

        // Check if the new password is the same as the current password
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'status' => 403,
                'message' => 'New password cannot be the same as the current password',
            ], 403);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => 'Password changed successfully',
        ], 200);
    } catch (\Exception $e) {
        // Return an error response if something goes wrong
        return response()->json([
            'status' => 500,
            'message' => 'Failed to change password. Please try again later.',
            'error' => $e->getMessage(), // Debugging purpose, can be removed in production
        ], 500);
    }
}



    public function forget_password(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|digits:10',
                'token' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors(), 'status' => 403], 403);
            }

            // Retrieve the validated data
            $validated = $validator->validate();
            $mobile = $validated['mobile'];
            $token = $validated['token'];
            $newPassword = $validated['new_password'];

            // Check if the user exists
            $checkuser = User::where('mobile', $mobile)->first();
            if ($checkuser) {
                // Verify the token
                if (!Password::tokenExists($checkuser, $token)) {
                    return response()->json([
                        'message' => 'Invalid or expired token.',
                        'status' => 400
                    ], 400);
                }

                // Update the password
                $checkuser->password = Hash::make($newPassword);
                $checkuser->save();

                // Invalidate the token
                Password::deleteToken($checkuser, $token);

                return response()->json([
                    'message' => 'Password updated successfully.',
                    'status' => 200
                ]);
            } else {
                return response()->json([
                    'message' => 'User with this mobile number not found.',
                    'status' => 404
                ], 404);
            }
        } catch (\Throwable $th) {
            // Handle any unexpected errors
            return response()->json([
                'message' => 'Failed to process request. Please try again later.',
                'status' => 500
            ], 500);
        }
    }
    
 public function all_frontend_numbers()
{
    try {
        // Fetch all categories
        $categories = Category::all();
        
        // Fetch the first opened category
        $openedCategory = Category::where('status', 'opened')->first();

        // Initialize the result array
        $result = [];
        
        $today = businessDate();
        $yesterday = \Carbon\Carbon::parse(businessDate())->subDay()->toDateString();

        // Loop through each category to fetch the relevant data
        foreach ($categories as $category) {
            $today_number = TodayResult::where('category_id', $category->id)
                ->whereDate('created_at', $today)
                ->value('open_number');
                
            $yesterday_number = TodayResult::where('category_id', $category->id)
                ->whereDate('created_at', $yesterday)
                ->value('open_number');
                
            $result[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'today_number' => $today_number ?? null,
                'yesterday_number' => $yesterday_number ?? null,
            ];
        }

        // Initialize variables for currently open number, category name, and open time
        $opened_number = null;
        $category_name = null;
        $open_time = null;

        // Check if there is an opened category
        if ($openedCategory) {
            $opened_number = $openedCategory->no_open;
            $category_name = $openedCategory->name;
            $open_time = $openedCategory->last_time;
        }

        // Fetch yesterday's number for the first opened category
        $category_for_first = Category::where('status', 'opened')->first();
        $yesterday_number_one = TodayResult::where('category_id', $category_for_first->id ?? 0)
            ->whereDate('created_at', $yesterday)
            ->first();

        // Prepare data for yesterday's number
        $yesterday_number_data = [
            'id' => $yesterday_number_one->id ?? null,
            'category_name' => $category_for_first->name ?? null,
            'open_number' => $yesterday_number_one->open_number ?? null,
            'open_time' => $yesterday_number_one->created_at ?? null, // Adjust if the timestamp needs to be formatted
        ];

        // Return the result as a JSON response
        return response()->json([
            'status' => 200,
            'data' => [
                'results' => $result,
                'yesterday_number' => $yesterday_number_data,
                'category' => [
                    'id' => $openedCategory->id ?? null,
                    'name' => $category_name,
                    'now_open_number' => $opened_number,
                    'open_time' => $open_time,
                ]
            ]
        ]);
    } catch (\Exception $e) {
        // Log the error
        \Log::error('Error in all_frontend_numbers method: ' . $e->getMessage());

        // Return the error as a JSON response
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching the number history.',
        ], 500);
    }
}


// all_frontend_months
public function all_frontend_months(Request $request)
{
    try {
        // Get the selected year and month from the request
        $year = $request->input('year', Carbon::now()->year); // Default to current year if not provided
        $month = $request->input('month', Carbon::now()->month); // Default to current month if not provided

        // Fetch all categories
        $categories = Category::all();

        // Get the start and end of the selected month and year
        $startOfMonth = Carbon::create($year, $month)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month)->endOfMonth();

        // Initialize the result array
        $result = new \stdClass(); // Use an object instead of an array

        // Loop through each day of the month
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dayOfMonth = $date->format('j'); // Day of the month without leading zeros
            $formattedDate = $date->format('Y-m-d'); // Full date format

            // Initialize array for current day's category numbers
            $dailyData = [];

            // Loop through each category
            foreach ($categories as $category) {
                $categoryName = $category->name;
                $existingRecord = TodayResult::where('category_id', $category->id)
                    ->whereDate('created_at', $formattedDate)
                    ->first();

                // Determine the open_number value
                $openNumber = $existingRecord ? $existingRecord->open_number : '--';

                // Add the open number to the daily data array
                $dailyData[] = $openNumber;
            }

            // Add the daily data to the result object, indexed by day of the month
            $result->{$dayOfMonth} = $dailyData;
        }

        // Prepare the final response structure
        $response = [
            'status' => 200,
            'year' => $year,
            'month' => $month,
            'categories' => $categories->pluck('name'),
            'results' => $result,
        ];

        // Return the result as a JSON response
        return response()->json(['data' => $response]);
    } catch (\Exception $e) {
        // Log the error
        \Log::error('Error in all_frontend_months method: ' . $e->getMessage());

        // Return the error as a JSON response
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching the monthly report.',
        ], 500);
    }
}







public function addWhatsAppNumber(Request $request)
    {
        try {
            // Validate the file input
            $validator = Validator::make($request->all(), [
                'csv_file' => 'required|mimes:csv,txt|max:2048',
            ]);

            // Return validation errors if any
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            // Open the CSV file for reading
            $file = fopen($request->file('csv_file'), 'r');
            $header = fgetcsv($file); // Get the first row (header)

            // Loop through each row of the CSV and insert into the database
            while (($row = fgetcsv($file)) !== false) {
                WhatsAppNumber::create([
                    'Name' => $row[0] ?? null,
                    'Mobile' => $row[1] ?? null,
                    // 'status' => $row[2] ?? null,
                ]);
            }

            fclose($file); // Close the file

            return response()->json(['success' => 'CSV data imported successfully.']);

        } catch (\Throwable $th) {
            dd($th);
            // Handle the exception
            return response()->json(['error' => 'Something went wrong! Please try again.'], 500);
        }
    }





    
}
