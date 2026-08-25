<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\PlayGame;
use App\Models\Category;
use App\Models\TodayResult;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\SubCategory;
    use Illuminate\Support\Facades\Log;

use App\Models\WithdrawalMoney;
use Carbon\Carbon;
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
                'category_id' => 'nullable|integer',
                'subcategory_id' => 'nullable|integer',
                'subcategory_name' => 'nullable|string',
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
                PlayGame::create([
                    'user_id' => $user_id,
                    'user_name' => $user_name,
                    'category_id' => $category_id,
                    'Playing_Name' => $Playing_Name,
                    'play_type' => $play_type,
                    'ander_harup' => $ander_harup,
                    'bahar_harup' => $bahar_harup,
                    'play_game_id' => $play_game_id,
                    'entered_number' => json_encode($entered_number),
                    'entered_amount' => json_encode($entered_amount),
                    'won_amount' => null, // No winning amount calculation for other cases
                ]);
            }

            return response()->json(['message' => 'Game played successfully'], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            // Handle any unexpected errors
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
    
public function DoublePlayGame(Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'entered_data' => 'required|array',
            'entered_data.*.number' => 'required|numeric',
            'entered_data.*.amount' => 'required|numeric',
            'category_id' => 'required|integer',
            'subcategory_id' => 'required|integer',
            'subcategory_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $category_id = $validated['category_id'];
        $subcategory_id = $validated['subcategory_id'];
        $subcategory_name = $validated['subcategory_name'];
        $entered_data = $validated['entered_data'];
        
        $category = Category::where('id', $category_id)->firstOrFail();

        $current_time = Carbon::now();

        $open_time = Carbon::parse($category->open_time);
        $last_time = Carbon::parse($category->last_time)->subMinutes(1);

        if ($open_time->greaterThan($last_time)) {
            $can_play = $current_time->greaterThanOrEqualTo($open_time) || $current_time->lessThanOrEqualTo($last_time);
        } else {
            $can_play = $current_time->between($open_time, $last_time);
        }

        if (!$can_play) {
            return response()->json(['error' => 'You cannot play now, this game is closed.'], 422);
        }

        $user = Auth::user();
        $user_id = $user->id;
        $available_balance = $user->balance;
        $user_name = $user->name;

        $total_entered_amount = array_sum(array_column($entered_data, 'amount'));

        if ($total_entered_amount > $available_balance) {
            return response()->json(['error' => 'Insufficient balance. Please add more money to play all games'], 400);
        }

        $user->balance -= $total_entered_amount;
        $user->save();

        // Create game records
        foreach ($entered_data as $data) {
            PlayGame::create([
                'user_id' => $user_id,
                'category_id' => $category_id,
                'play_game_id' => $subcategory_id,
                'Playing_Name' => $subcategory_name,
                'user_name' => $user_name,
                'entered_number' => $data['number'],
                'entered_amount' => $data['amount'],
                'won_amount' => null
            ]);
        }

        return response()->json(['status' => 200, 'message' => 'Game played successfully and balance updated'], 200);

    } catch (\Throwable $th) {
        // Log the error for debugging purposes
        Log::error('Error in DoublePlayGame: '.$th->getMessage());

        return response()->json(['error' => 'An error occurred'], 500);
    }
}

public function HarupPlayGame(Request $request){
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'entered_data' => 'nullable|array',
            'entered_data.ander_harup' => 'nullable|array',
            'entered_data.bahar_harup' => 'nullable|array',
            'entered_data.ander_harup.*.number' => 'nullable|numeric',
            'entered_data.ander_harup.*.amount' => 'nullable|numeric',
            'entered_data.bahar_harup.*.number' => 'nullable|numeric',
            'entered_data.bahar_harup.*.amount' => 'nullable|numeric',
            'category_id' => 'required|integer',
            'subcategory_id' => 'required|integer',
            'subcategory_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Extract validated data
        $validated = $validator->validated();
        $category_id = $validated['category_id'];
        $subcategory_id = $validated['subcategory_id'];
        $subcategory_name = $validated['subcategory_name'];
        $entered_data = $validated['entered_data'];

        $category = Category::where('id', $category_id)->firstOrFail();

        $current_time = Carbon::now();

        $open_time = Carbon::parse($category->open_time);
        $last_time = Carbon::parse($category->last_time)->subMinutes(1);

        if ($open_time->greaterThan($last_time)) {
            $can_play = $current_time->greaterThanOrEqualTo($open_time) || $current_time->lessThanOrEqualTo($last_time);
        } else {
            $can_play = $current_time->between($open_time, $last_time);
        }

        if (!$can_play) {
            return response()->json(['error' => 'You cannot play now, this game is closed.'], 422);
        }
        
        $user = Auth::user();
        $user_id = $user->id;
        $available_balance = $user->balance;
        $user_name = $user->name;

        // Calculate the total amount entered for both ander_harup and bahar_harup
        $total_entered_amount = array_sum(array_column($entered_data['ander_harup'], 'amount')) +
                                array_sum(array_column($entered_data['bahar_harup'], 'amount'));

        if ($total_entered_amount > $available_balance) {
            return response()->json(['error' => 'Insufficient balance. Please add more money to play all games'], 400);
        }

        // Deduct the amount from user's balance
        $user->balance -= $total_entered_amount;
        $user->save();

        // Create game records for ander_harup
        foreach ($entered_data['ander_harup'] as $data) {
            PlayGame::create([
                'user_id' => $user_id,
                'category_id' => $category_id,
                'play_game_id' => $subcategory_id,
                'Playing_Name' => $subcategory_name,
                'play_type' => 'ander_harup',
                'user_name' => $user_name,
                'entered_number' => $data['number'],
                'entered_amount' => $data['amount'],
                'won_amount' => null,
                'type' => 'ander_harup'
            ]);
        }

        // Create game records for bahar_harup
        foreach ($entered_data['bahar_harup'] as $data) {
            PlayGame::create([
                'user_id' => $user_id,
                'category_id' => $category_id,
                'play_game_id' => $subcategory_id,
                'Playing_Name' => $subcategory_name,
                'play_type' => 'bahar_harup',
                'user_name' => $user_name,
                'entered_number' => $data['number'],
                'entered_amount' => $data['amount'],
                'won_amount' => null,
                'type' => 'bahar_harup'
            ]);
        }

        return response()->json(['status' => 200, 'message' => 'Game played successfully and balance updated'], 200);

    } catch (\Throwable $th) {
        Log::error($th->getMessage());
        return response()->json(['error' => 'An error occurred'], 500);
    }
    
    
    
}

public function number_History()
{
    try {
        // Fetch all categories
        $categories = Category::all();
        
        // Fetch the first opened category
        $openedCategory = Category::where('status', 'opened')->first();

        // Initialize the result array
        $result = [];

        // Fetch today's date and yesterday's date in Y-m-d format
        $today = businessDate();
        $yesterday = \Carbon\Carbon::parse(businessDate())->subDay()->toDateString();

        // Loop through each category to fetch the relevant data
        foreach ($categories as $category) {
            // Fetch today's number for the current category
            $today_number = TodayResult::where('category_id', $category->id)
                ->whereDate('open_time', $today)
                ->value('open_number');

            // Fetch yesterday's number for the current category
            $yesterday_number = TodayResult::where('category_id', $category->id)
                ->whereDate('open_time', $yesterday)
                ->value('open_number');

            // Add the category data to the result array
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

        // Return the result as a JSON response
        return response()->json([
            'status' => 200,
            'data' => [
                'results' => $result,
                'category' => [
                    'id' => $openedCategory->id ?? null,
                    'name' => $category_name,
                    'now_open_number' => $opened_number,
                    'open_time' => $open_time,
                ]
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error in number_History method: ' . $e->getMessage());

        // Return the error as a JSON response
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching the number history.',
        ], 500);
    }
}



//function for add money

 public function Add_money(Request $request)
{
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'image' => 'nullable|image|max:10240', // Validate image (10MB max)
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 403, 'errors' => $validator->errors()]);
        }

        // Retrieve validated data
        $validated = $validator->validate();
        $amount = $validated['amount'];

        $user = Auth::user();
        $user_id = $user->id;

        // Check for existing transaction
        $existingTransaction = \App\Models\AddMoneyRequest::where('user_id', $user_id)
            ->where('status', '=', 'pending')
            ->first();


        if ($existingTransaction) {
            return response()->json([
                'status' => '403',
                'message' => 'Please wait for payment confirmation from admin.'
            ], 403);
        }

        DB::beginTransaction();

        // Handle the image upload
        $image = $request->file('image');
        $imageName = null;
        if ($image) {
            $imageName = time() . '_' . $image->getClientOriginalName(); // Create a unique name for the image
            $image->move(public_path('uploads'), $imageName); // Store image in the public uploads directory
        }

        // Create a new add money request
        $addMoneyRequest = new \App\Models\AddMoneyRequest();
        $addMoneyRequest->user_id = $user_id;
        $addMoneyRequest->amount = $amount;
        $addMoneyRequest->image = $imageName;
        $addMoneyRequest->status = 'pending';
        $addMoneyRequest->save();

        DB::commit();

        return response()->json(['status' => 'success', 'message' => 'Amount added to user balance'], 200);
    } catch (\Throwable $th) {
        DB::rollback();
        \Log::error('Add_money Error: ' . $th->getMessage());
        return response()->json(['status' => 'error', 'message' => 'Failed to add amount. Please try again later.'], 500);
    }
}

    
    
    public function Request_money(Request $request)
{
    try {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'request_money' => 'required|integer|min:500',
            'mobile_no' => 'nullable|integer',
            'upi_id' => 'nullable|string',
            'qr_code_image' => 'nullable|image|max:10240', // Validate image (10MB max)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'data' => $validator->errors(),
                'message' => 'Validation failed',
            ], 403);
        }

        $timezone = 'Asia/Kolkata';
        $currentTime = Carbon::now($timezone);
        $startTime = $currentTime->copy()->setTime(6, 30);
        $endTime = $currentTime->copy()->setTime(11, 00);

        if ($currentTime->lessThan($startTime) || $currentTime->greaterThan($endTime)) {
            return response()->json([
                'status' => 403,
                'data' => null,
                'message' => 'Requests can only be made between 6:30 AM and 11:30 AM.',
            ], 403);
        }

        $user = Auth::user();
        $user_id = $user->id;
        $user_balance = $user->balance;
        $validated = $validator->validated();
        $request_money = $validated['request_money'];

        $pending_request = WithdrawalMoney::where('user_id', $user_id)
            ->where('withdrawal_money_status', 'not_accepted')
            ->first();

        if ($pending_request) {
            return response()->json([
                'status' => 403,
                'data' => null,
                'message' => 'Please wait, you already have a pending withdrawal request.',
            ], 403);
        }

        if ($user_balance < $request_money) {
            return response()->json([
                'status' => 403,
                'data' => null,
                'message' => 'Insufficient balance. You do not have enough money to request this amount.',
            ], 403);
        }

        // Handle the image upload if present
        $qr_code_image_path = null;
        if ($request->hasFile('qr_code_image')) {
            $qr_code_image_path = $request->file('qr_code_image')->store('qr_codes', 'public');
        }

        if ($request_money > 4999) {
            $account_holder_name = $request->input('account_holder_name');
            $account_number = $request->input('account_number');
            $ifsc_code = $request->input('ifsc_code');

            $additional_validator = Validator::make([
                'account_holder_name' => $account_holder_name,
                'account_number' => $account_number,
                'ifsc_code' => $ifsc_code,
            ], [
                'account_holder_name' => 'required|string',
                'account_number' => 'required|integer',
                'ifsc_code' => 'required|string',
            ]);

            if ($additional_validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'data' => $additional_validator->errors(),
                    'message' => 'Additional validation failed',
                ], 403);
            }

            $withdrawal = new WithdrawalMoney();
            $withdrawal->user_id = $user_id;
            $withdrawal->request_money = $request_money;
            $withdrawal->mobile_no = $validated['mobile_no'] ?? $user->mobile;
            $withdrawal->upi_id = $validated['upi_id'] ?? null;
            $withdrawal->qr_code_image = $qr_code_image_path;
            $withdrawal->withdrawal_money_status = 'not_accepted';
            $withdrawal->acount_holder_name = $account_holder_name;
            $withdrawal->account_number = $account_number;
            $withdrawal->ifsc_code = $ifsc_code;
            $withdrawal->save();
        } else {
            $withdrawal = new WithdrawalMoney();
            $withdrawal->user_id = $user_id;
            $withdrawal->request_money = $request_money;
            $withdrawal->mobile_no = $validated['mobile_no'] ?? $user->mobile;
            $withdrawal->upi_id = $validated['upi_id'] ?? null;
            $withdrawal->qr_code_image = $qr_code_image_path;
            $withdrawal->withdrawal_money_status = 'not_accepted';
            $withdrawal->save();
        }
        
        $user = User::find($user_id);

if (!$user) {
    return response()->json([
        'status' => 404,
        'message' => 'User not found',
    ], 404);
}

// Ensure sufficient balance
if ($user->balance < $request_money) {
    return response()->json([
        'status' => 403,
        'message' => 'Insufficient balance',
    ], 403);
}

// Deduct the requested amount
$user->balance -= $request_money;
$user->save();

        $qr_code_image_url = $qr_code_image_path ? asset('storage/' . $qr_code_image_path) : null;

        return response()->json([
            'status' => 200,
            'data' => [
                'qr_code_image_url' => $qr_code_image_url,
            ],
            'message' => 'Request processed successfully',
        ], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 403,
            'data' => null,
            'message' => 'An error occurred while processing the request',
            'error' => $th->getMessage(),
        ], 403);
    }
}









 public function Request_Add_money_list(Request $request)
{
    try {
        // Get search term from request
        $searchTerm = $request->input('search');

        // Build the query
        $query = \App\Models\AddMoneyRequest::with(['user:id,name,mobile']) // Include only 'id', 'name', 'mobile' from the related user
            ->orderBy('id', 'DESC');

        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->whereHas('user', function ($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('mobile', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        // Fetch the requests
        $all_requests = $query->get();
        
        // Map to match the expected format for Admin panel
        $mapped_requests = $all_requests->map(function ($req) {
            return [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'amount' => $req->amount,
                'image' => $req->image,
                'confirm_payment' => $req->status === 'pending' ? 'not_confirm' : ($req->status === 'approved' ? 'received_successfully' : 'rejected'),
                'transaction_type' => 'credit',
                'description' => 'Add money request',
                'created_at' => $req->created_at,
                'user' => $req->user,
            ];
        });

        // Return response with data
        return response()->json([
            'status' => 200,
            'data' => $mapped_requests
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to Get List. Please try again later.'
        ], 500);
    }
}


    
    
     public function confirm_payment(Request $request, $payment_id)
{
    try {
        $confirm_payment_status = $request->confirm_payment;

        // If the status is 'not_confirm', update the description and return a response
        if ($confirm_payment_status === 'not_confirm') {
            $addMoneyRequest = \App\Models\AddMoneyRequest::find($payment_id);
            if ($addMoneyRequest) {
                $addMoneyRequest->status = 'rejected';
                $addMoneyRequest->save();
            }

            return response()->json([
                'status' => 200,
                'message' => 'Payment update not approved by admin.',
            ], 200);
        }

        // Proceed with payment confirmation if status is not 'not_confirm'
        $addMoneyRequest = \App\Models\AddMoneyRequest::find($payment_id);
        if (!$addMoneyRequest) {
            return response()->json([
                'status' => 403,
                'message' => 'Transaction not found.',
            ], 403);
        }

        if ($addMoneyRequest->status === "approved") {
            return response()->json([
                'status' => 403,
                'message' => 'Payment Already Confirmed',
            ], 403);
        }

        $user_id = $addMoneyRequest->user_id;
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
                $bonusAmount = $addMoneyRequest->amount * 0.05;
                $referrer->balance += $bonusAmount;
                $referrer->save();

                // Log the referral bonus transaction
                Transaction::create([
                    'user_id' => $referrer->id,
                    'transaction_type' => 'bonus',
                    'amount' => $bonusAmount,
                    'description' => 'Referral bonus',
                    'available_balance' => $referrer->balance,
                    'confirm_payment' => 'received_successfully',
                ]);
            }
        }

        // Update the request status
        $addMoneyRequest->status = 'approved';
        $addMoneyRequest->save();

        // Create the actual transaction
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->transaction_type = 'credit';
        $transaction->amount = $addMoneyRequest->amount;
        $transaction->description = 'Added money to balance';
        $transaction->confirm_payment = 'received_successfully';
        $transaction->image = $addMoneyRequest->image;
        $transaction->available_balance = $user->balance + $addMoneyRequest->amount;
        $transaction->save();

        $user->balance += $addMoneyRequest->amount;
        $user->save();

        return response()->json([
            'message' => 'Payment status updated successfully.',
            'transaction' => $addMoneyRequest
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'An error occurred while updating the payment status.',
            'error' => $th->getMessage()
        ], 500);
    }
}

    
public function AllTransaction(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();
        $user_id = $user->id;

        // Fetch all transactions for the user where payment is confirmed
        $all_transaction = Transaction::where('user_id', $user_id)
    ->whereBetween('created_at', [\Carbon\Carbon::parse(businessDate())->subDays(7)->format('Y-m-d') . ' 03:21:00', businessEnd()]) // Last 7 days
    ->orderBy('id', 'DESC')
    ->get();

        // Prepare the response data
        $response = [];
        foreach ($all_transaction as $transaction) {
            $response[] = [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'transaction_type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'image' => $transaction->image,
                'transaction_date' => $transaction->transaction_date,
                'available_balance' => $transaction->available_balance,
                'created_at_date' => Carbon::parse($transaction->created_at)->format('d-M-Y'), // Date with month as Aug
                'created_at_time' => Carbon::parse($transaction->created_at)->format('H:i:s'), // Time only
                'updated_at_date' => Carbon::parse($transaction->updated_at)->format('d-M-Y'), // Date with month as Aug
                'updated_at_time' => Carbon::parse($transaction->updated_at)->format('H:i:s'), // Time only
                'confirm_payment' => $transaction->confirm_payment,
            ];
        }

        // Return success response with formatted data
        return response()->json([
            'status' => 200,
            'data' => $response,
            'message' => 'All transactions retrieved successfully.'
        ], 200);
    } catch (\Throwable $th) {
        // Log the error for debugging
        Log::error('Error fetching transactions: ' . $th->getMessage());

        // Return error response
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching transactions.'
        ], 500);
    }
}
 
     public function delete_added_req($transaction_id)
    {
        try {
            // Find the transaction by ID
            $transaction = Transaction::findOrFail($transaction_id);

            // Delete the transaction
            $transaction->delete();

            // Return a success response
            return response()->json(['status' => 200, 'message' => 'Transaction deleted successfully.']);
        } catch (ModelNotFoundException $e) {
            // Log the exception and return a not found response
            Log::error("Transaction not found: " . $e->getMessage());
            return response()->json(['status' => 404, 'message' => 'Transaction not found.'], 404);
        } catch (\Throwable $e) {
            // Log the exception and return a server error response
            Log::error("Failed to delete transaction: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete transaction. Please try again later.'], 500);
        }
    }




    public function WithdrawalMoney(Request $request)
{
    try {
        $user = Auth::user();
        $user_id = $user->id;

        // Fetch and format the withdrawal money data
        $withdrawal_money = WithdrawalMoney::where('user_id', $user_id)
            ->orderBy('id', 'DESC')
                ->whereBetween('created_at', [\Carbon\Carbon::parse(businessDate())->subDays(7)->format('Y-m-d') . ' 03:21:00', businessEnd()]) // Last 7 days
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'request_money' => $item->request_money,
                    'mobile_no' => $item->mobile_no,
                    'upi_id' => $item->upi_id,
                    'qr_code_image' => $item->qr_code_image,
                    'withdrawal_status' => $item->withdrawal_status,
                    'created_at' => \Carbon\Carbon::parse($item->created_at)->format('d M Y'), // Format the date as "12 Aug YYYY"
                ];
            });

        return response()->json(['status' => 200, 'data' => $withdrawal_money, 'message' => 'All Transaction Withdrawal Money']);
    } catch (\Throwable $th) {
        return response()->json(['error' => 'An error occurred'], 500);
    }
}

public function DeleteAddMoney(Request $request)
{
    try {
        // Validate the request to ensure 'id' is provided
        $request->validate([
            'id' => 'required|integer',
        ]);

        $transaction_id = $request->id;

        // Find the transaction record
        $transaction_d = Transaction::where('id', $transaction_id)->first();
        if (!$transaction_d) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $find_user_id = $transaction_d->user_id;
        $amount = $transaction_d->amount;

        // Find the user record
        $user = User::where('id', $find_user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Subtract the amount from the user's balance
        $user->balance -= $amount;

        // Save the updated user balance
        $user->save();

        // Delete the transaction
        $transaction_d->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully',
            'user_balance' => $user->wallet_balance,
        ]);
    } catch (\Exception $e) {
        // Handle errors gracefully
        return response()->json([
            'message' => 'An error occurred',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function AddMoneyList(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();
        $user_id = $user->id;

        // Fetch all add money requests for the user
        $requests = \App\Models\AddMoneyRequest::where('user_id', $user_id)
            ->whereBetween('created_at', [\Carbon\Carbon::parse(businessDate())->subDays(7)->format('Y-m-d') . ' 03:21:00', businessEnd()]) // Last 7 days
            ->orderBy('id', 'DESC')
            ->get();

        // Prepare the response data
        $response = $requests->map(function ($req) use ($user) {
            // Format dates
            $created_at = \Carbon\Carbon::parse($req->created_at)->format('M d, Y, h:i A');
            $updated_at = \Carbon\Carbon::parse($req->updated_at)->format('M d, Y, h:i A');

            return [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'transaction_type' => 'credit',
                'amount' => $req->amount,
                'description' => 'Add money request',
                'image' => $req->image,
                'transaction_date' => $created_at,
                'available_balance' => $user->balance,
                'created_at' => $created_at,
                'updated_at' => $updated_at,
                'confirm_payment' => $req->status === 'pending' ? 'not_confirm' : ($req->status === 'approved' ? 'received_successfully' : 'rejected'),
            ];
        });

        // Return success response with data
        return response()->json([
            'status' => 200,
            'data' => $response,
            'message' => 'All credit transactions retrieved successfully.'
        ], 200);
    } catch (\Throwable $th) {
        \Log::error('AddMoneyList Error: ' . $th->getMessage());
        // Handle any unexpected errors
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching credit transactions.',
            'error' => $th->getMessage()
        ], 500);
    }
}



public function WonMoneyList(Request $request)
{
    try {
        $user = Auth::user();
        $user_id = $user->id;

        // Fetch won transactions for the user with category details
        $wonTransactions = PlayGame::with('category') // Eager load the category relationship
            ->where('user_id', $user_id)
            ->where('status', 'won')
                ->whereBetween('created_at', [\Carbon\Carbon::parse(businessDate())->subDays(7)->format('Y-m-d') . ' 03:21:00', businessEnd()]) // Last 7 days
            ->orderBy('id', 'DESC')
            ->get();

        // Prepare the response data
        $response = $wonTransactions->map(function ($transaction) {
            // Format dates
            $created_at = Carbon::parse($transaction->created_at)->format('d-M-Y');
            $updated_at = Carbon::parse($transaction->updated_at)->format('d-M-Y');

            // Create description including category name and play name
            $categoryName = $transaction->category ? $transaction->category->name : 'N/A';
            $playName = $transaction->Playing_Name ? $transaction->Playing_Name : 'N/A';
            $description = "Won The Game in Category- $categoryName, Play Name- $playName";

            return [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'Play_Name' => $categoryName, // Fetch category name
                'Playing_name' => $playName,
                'status' => $transaction->status,
                'won_amount' => $transaction->won_amount,
                'entered_number' => $transaction->entered_number,
                'entered_amount' => $transaction->entered_amount,
                'description' => $description, // Custom description including category and play name
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ];
        });

        return response()->json(['status' => 200, 'data' => $response, 'message' => 'All won transactions']);
    } catch (\Throwable $th) {
        // Handle any unexpected errors
        return response()->json(['error' => 'An error occurred: ' . $th->getMessage()], 500);
    }
}




    
public function All_playGame(Request $request)
{
    try {
        $user = Auth::user();
        $user_id = $user->id;
        $user_name  = $user->name;

        // Fetch played games for the user
        $played_games = PlayGame::where('user_id', $user_id)
                ->whereBetween('created_at', [\Carbon\Carbon::parse(businessDate())->subDays(7)->format('Y-m-d') . ' 03:21:00', businessEnd()]) // Last 7 days
            ->orderBy('id', 'DESC')
            ->get();

        // Prepare the response data
        $response = [];
        foreach ($played_games as $game) {
            // Fetch category name
            $category = Category::find($game->category_id);
            $category_name = $category ? $category->name : 'Unknown Category';

            // Fetch subcategory name
            $subcategory = SubCategory::find($game->play_game_id);
            $subcategory_name = $subcategory ? $subcategory->name : 'Unknown Subcategory';

            // Format dates
            $created_at = Carbon::parse($game->created_at)->format('d-M-Y');
            $updated_at = Carbon::parse($game->updated_at)->format('d-M-Y');

            // Append game data to response
            $response[] = [
                'id' => $game->id,
                'user_id' => $game->user_id,
                'user_name' => $user_name,
                'category_id' => $game->category_id,
                'category_name' => $category_name,
                'Playing_Name' => $game->Playing_Name,
                'play_type' => $game->play_type,
                'ander_harup' => $game->ander_harup,
                'bahar_harup' => $game->bahar_harup,
                'play_game_id' => $game->play_game_id,
                'subcategory_name' => $subcategory_name,
                'today_number' => $game->today_number,
                'after_open_number_block' => $game->after_open_number_block,
                'open_time_number' => $game->open_time_number,
                'loss_amount' => $game->loss_amount,
                'won_amount' => $game->won_amount,
                'entered_number' => $game->entered_number,
                'entered_amount' => $game->entered_amount,
                'status' => $game->status,
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ];
        }

        return response()->json(['played_games' => $response]);
    } catch (\Throwable $th) {
        // Handle any unexpected errors
        return response()->json([
            'message' => 'Failed to fetch played games. Please try again later.',
            'status' => 500
        ], 500);
    }
}


public function details_payment(Request $request, $id)
{
    try {
        // Fetch the transaction details based on the ID
        $transaction = Transaction::findOrFail($id);

        // Uncomment this line if you want to see the transaction data in your debug output
        // dd($transaction);

        // Return the details as a JSON response
        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 200);
    } catch (ModelNotFoundException $e) {
        // Handle case where the transaction is not found
        Log::error("Transaction not found: ID {$id}", ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Transaction not found.',
        ], 404);
    } catch (\Exception $e) {
        // Uncomment this line if you want to debug the exception
        // dd($e);

        // Log the exception for debugging purposes
        
        // Handle other potential errors
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching the transaction details.',
        ], 500);
    }
}


public function PlayGame_Harup(Request $request) {
    try {
        $category_id = $request->category_id;
        $game_type = $request->game_type;

        // Create a datetime for the current day
        $created_at = businessEnd();
        $startOfDay = businessStart();

        // Initialize betting totals and total amount
        $bettingTotals = [];
        $totalAmount = 0; // Initialize total amount

        // Initialize betting totals for keys 0 to 9 (no leading zero)
        for ($i = 0; $i <= 9; $i++) {             
            $key = (string) $i; // Convert integer to string without padding
            $bettingTotals[$key] = 0;         
        }

        // Fetching play games for the given category and date range
        $play_games = PlayGame::where('status', 'waiting')
            ->where('category_id', $category_id)
            ->get();

        // Process each game and calculate total amount based on entered numbers
        foreach ($play_games as $game) {
            if ($game->play_game_id == 2) {
                if ($game->play_type === $game_type) {
                    // Fix: Remove str_pad() and ensure the key matches bettingTotals format
                    $enteredNumber = (string) $game->entered_number; 

                    // Check if the entered number is in the betting totals array
                    if (array_key_exists($enteredNumber, $bettingTotals)) {
                        // Accumulate the total amount for that number
                        $bettingTotals[$enteredNumber] += $game->entered_amount; // Update the betting totals
                        $totalAmount += $game->entered_amount; // Add raw entered amount to total
                    }
                }
            }
        }

        // Convert bettingTotals array to an array of objects
        $bettingTotalsArray = array_map(function ($key, $amount) {
            return [
                'number' => $key,
                'total' => $amount,
            ];
        }, array_keys($bettingTotals), $bettingTotals);

        // Return response with calculated amounts
        return response()->json([
            'success' => true,
            'message' => 'Total amount calculated',
            'totalAmount' => $totalAmount,
            'bettingTotals' => $bettingTotalsArray // Use the array of objects here
        ], 200);

    } catch (\Exception $e) {
        // For debugging purposes
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}








public function PlayGame_Category(Request $request)
{
    try {
        $category_id = $request->category_id;
        $created_at = businessEnd();
        $startOfDay = businessStart();
        
        // Initialize bettingTotals for all numbers 00 to 100
        $bettingTotals = [];
        for ($i = 0; $i <= 100; $i++) {
            $key = str_pad($i, 2, '0', STR_PAD_LEFT);
            $bettingTotals[$key] = 0;  // Initialize with 0
        }

        // Fetch play games within the specified date range
        $play_games = PlayGame::where('status', 'waiting')
            ->where('category_id', $category_id)
            ->whereBetween('created_at', [$startOfDay, $created_at]) // Use between condition to fetch records for today with added minutes
            ->get();

        // Calculate the total betting amounts for each number
        foreach ($play_games as $play_game) {
            $entered_numbers = is_array($play_game->entered_number)
                ? $play_game->entered_number
                : (array) json_decode($play_game->entered_number, true);
            $entered_amounts = is_array($play_game->entered_amount)
                ? $play_game->entered_amount
                : (array) json_decode($play_game->entered_amount, true);

            if (!is_array($entered_numbers) || !is_array($entered_amounts)) {
                continue;
            }

            // Loop through entered numbers and their corresponding amounts
            foreach ($entered_numbers as $index => $number) {
                $key = str_pad($number, 2, '0', STR_PAD_LEFT);
                $amount = $entered_amounts[$index] ?? 0;

                if (isset($bettingTotals[$key])) {
                    $bettingTotals[$key] += $amount;
                }
            }
        }

        // Find minimum and maximum betting amounts
        $minAmount = min($bettingTotals);
        $maxAmount = max($bettingTotals);
        $formattedMinAmount = number_format($minAmount, 2);
        $formattedMaxAmount = number_format($maxAmount, 2);

        // Find numbers with minimum and maximum amounts
        $minNumbers = array_keys($bettingTotals, $minAmount);
        $maxNumbers = array_keys($bettingTotals, $maxAmount);

        $formattedMinNumbers = array_map(fn($number) => str_pad($number, 2, '0', STR_PAD_LEFT), $minNumbers);
        $formattedMaxNumbers = array_map(fn($number) => str_pad($number, 2, '0', STR_PAD_LEFT), $maxNumbers);

        // Prepare the response for all numbers' betting totals
        $response = [];
        foreach ($bettingTotals as $number => $total) {
            $formattedTotal = number_format($total, 2);
            $response[] = ['number' => $number, 'total' => $formattedTotal];
        }

        // Calculate the total amount for all betting
        $totalAmount = array_sum($bettingTotals);

        return response()->json([
            'status' => 200,
            'data' => [
                'all_results' => $response,
                'minAmount' => ['amount' => $formattedMinAmount, 'numbers' => $formattedMinNumbers],
                'maxAmount' => ['amount' => $formattedMaxAmount, 'numbers' => $formattedMaxNumbers],
                'totalAmount' => number_format($totalAmount, 2)  // Format the total amount
            ],
            'message' => 'All Numbers Amount Will be Show Here',
        ]);
    } catch (\Throwable $th) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}








}
