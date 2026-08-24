<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ContentModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Validator;

class CategoryControllerUser extends Controller
{
    public function index(Request $request)
    {
        try {
            $category = Category::all();
            return response()->json(['status' => 200, 'data' => $category]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.'], 500);
        }
    }


//get content for chnage function 

public function Content_Game()
    {
        try {
            $user = Auth::user();
            $user_balance = $user->balance;
            $contentList = ContentModel::all();
            return response()->json([
                'status' => true,
                'user_balance' =>$user_balance,
                'message' => 'Content list retrieved successfully',
                'data' => $contentList
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
public function subcategory()
{
    try {
        $current_time = Carbon::now('Asia/Kolkata');
       
        $user = Auth::user();
        
        // Fetch all categories and subcategories
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $subcategories_array = $subcategories->map(function ($subcategory) {
            return [
                'subcategory_id' => $subcategory->id,
                'subcategory_name' => $subcategory->name,
            ];
        })->toArray();
        //added extra three hours end of the month
        $is_end_of_month = $current_time->copy()->subHours(3)->isSameDay($current_time->copy()->endOfMonth());
        if($is_end_of_month){
                $groupedSubcategories = $categories->map(function ($category) use ($current_time, $subcategories_array) {
            // Parse times
            $category_open_time = Carbon::parse($category->open_time, 'Asia/Kolkata');
            $category_close_time = Carbon::parse($category->last_time, 'Asia/Kolkata');

            // Handle "12:00 AM" specifically as the start of the next day
            if ($category->last_time === '12:00 AM') {
                $category_close_time->addDay();
            }

            // Format times in 12-hour format with AM/PM
            $formatted_open_time = $category_open_time->format('h:i A');
            $formatted_close_time = $category_close_time->format('h:i A');
            
            // Check if the close time is past midnight (i.e., next day)
            if ($category_close_time->lessThan($category_open_time)) {
                // If current time is between open time and midnight
                $is_category_open = $current_time->between($category_open_time, $category_open_time->copy()->endOfDay()) ||
                                    $current_time->between($category_open_time->copy()->startOfDay(), $category_close_time);
            } else {
                // Regular case where close time is on the same day
                $is_category_open = $current_time->between($category_open_time, $category_close_time);
            }
            
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'open_time' => $formatted_open_time,
                'close_time' => $formatted_close_time,
                'is_open' => false,
                'subcategory_count' => count($subcategories_array),
                'subcategories' => $subcategories_array,
            ];
        })->sortByDesc('is_open')->values();

        // Return response as JSON
        return response()->json(['status' => 200, 'data' => $groupedSubcategories]);
        }{
        
        $groupedSubcategories = $categories->map(function ($category) use ($current_time, $subcategories_array) {
            // Parse times
            $category_open_time = Carbon::parse($category->open_time, 'Asia/Kolkata');
            $category_close_time = Carbon::parse($category->last_time, 'Asia/Kolkata');

            // Handle "12:00 AM" specifically as the start of the next day
            if ($category->last_time === '12:00 AM') {
                $category_close_time->addDay();
            }

            // Format times in 12-hour format with AM/PM
            $formatted_open_time = $category_open_time->format('h:i A');
            $formatted_close_time = $category_close_time->format('h:i A');
            
            // Check if the close time is past midnight (i.e., next day)
            if ($category_close_time->lessThan($category_open_time)) {
                // If current time is between open time and midnight
                $is_category_open = $current_time->between($category_open_time, $category_open_time->copy()->endOfDay()) ||
                                    $current_time->between($category_open_time->copy()->startOfDay(), $category_close_time);
            } else {
                // Regular case where close time is on the same day
                $is_category_open = $current_time->between($category_open_time, $category_close_time);
            }
            
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'open_time' => $formatted_open_time,
                'close_time' => $formatted_close_time,
                'is_open' => $is_category_open,
                'subcategory_count' => count($subcategories_array),
                'subcategories' => $subcategories_array,
            ];
        })->sortByDesc('is_open')->values();

        // Return response as JSON
        return response()->json(['status' => 200, 'data' => $groupedSubcategories]);
        }
    } catch (\Throwable $th) {
        dd($th);
        // Handle any exceptions
        return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.'], 500);
    }
}






 public function play_game_haruf(Request $request)
{
    try {
        $category_id = $request->category_id;
        $subcategory_id = $request->sub_category_id;
        $user = Auth::user();
        $user_balance = $user->balance;

        $subCategory = SubCategory::find($subcategory_id);

        if (!$subCategory) {
            return response()->json(['status' => 404, 'message' => 'Subcategory not found.'], 404);
        }

        $play_type = [
            'ander_harup' => [],
            'bahar_harup' => [],
        ];

        for ($i = 0; $i <= 9; $i++) {
            $play_type['ander_harup'][] = [
                'number' => $i,
                'entered_amount' => '',
                'category_id' => $subCategory->category_id,
                'Playing_Name' => $subCategory->name,
                'subcategory_id' => $subCategory->id,
            ];
            $play_type['bahar_harup'][] = [
                'number' => $i,
                'entered_amount' => '',
                'category_id' => $subCategory->category_id,
                'Playing_Name' => $subCategory->name,
                'subcategory_id' => $subCategory->id,

            ];
        }

        return response()->json(['status' => 200, 'data' => ['user_amount' => $user_balance, 'play_game' => $play_type]]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 500, 'message' => 'An error occurred.'], 500);
    }
}


public function play_Crossing_Game(Request $request)
{
    try {
        $category_id = $request->category_id;
        $subcategory_id = $request->sub_category_id;
        $user = Auth::user();
        $user_balance = $user->balance;

        $subCategory = SubCategory::find($subcategory_id);

        if (!$subCategory) {
            return response()->json(['status' => 404, 'message' => 'Subcategory not found.'], 404);
        }

        $play_type = [
            'entered_amount_1' => '',
            'entered_amount_2' => '',
            'entered_amount' => '',
            'category_id' => $subCategory->category_id,
            'Playing_Name' => $subCategory->name,
            'subcategory_id' => $subCategory->id,
        ];

        return response()->json(['status' => 200, 'data' => ['user_amount' => $user_balance, 'play_game' => $play_type]]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 500, 'message' => 'An error occurred.'], 500);
    }
}


public function Play_Game_Jodi(Request $request)
{
    try {
        $category_id = $request->category_id;
        $subcategory_id = $request->sub_category_id;
        $user = Auth::user();
        $user_balance = $user->balance;

        $subCategory = SubCategory::find($subcategory_id);

        if (!$subCategory) {
            return response()->json(['status' => 404, 'message' => 'Subcategory not found.'], 404);
        }

        $play_type = [];
        for ($i = 1; $i <= 100; $i++) {
            $number = str_pad($i, 2, '0', STR_PAD_LEFT);
            $play_type[] = [
                'number' => $number,
                'entered_amount' => '',
                'category_id' => $subCategory->category_id,
                'Playing_Name' => $subCategory->name,
                            'subcategory_id' => $subCategory->id,

            ];
        }

        // Add number '00'
        $play_type[] = [
            'number' => '00',
            'entered_amount' => '',
            'category_id' => $subCategory->category_id,
            'Playing_Name' => $subCategory->name,
                        'subcategory_id' => $subCategory->id,

        ];

        return response()->json(['status' => 200, 'data' => ['user_amount' => $user_balance, 'play_game' => $play_type]]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 500, 'message' => 'An error occurred.'], 500);
    }
}





    public function play_game(Request $request)
    {
        try {
            $play_id = $request->play_id;
            $play_name = $request->play_name;
            $subCategory = SubCategory::find($play_id);
            if ($subCategory && $subCategory->name === $play_name) {
                $group1 = [1, 5, 9, 13, 17, 21, 25, 29, 33, 37];
                $group2 = [2, 6, 10, 14, 18, 22, 26, 30, 34, 38];
                $group3 = [3, 7, 11, 15, 19, 23, 27, 31, 35, 39];
                $group4 = [4, 8, 12, 16, 20, 24, 28, 32, 36, 40];
                if (in_array($play_id, $group1)) {
                    $play_type = [];
                    for ($i = 0; $i < 100; $i++) {
                        $play_type[] = [
                            'entered_number' => '',
                            'entered_amount' => '',
                            'category_id' => $subCategory->category_id,
                            'Playing_Name' => $subCategory->name,
                        ];
                    }
                } elseif (in_array($play_id, $group2)) {
                    $play_type = [
                        'ander_harup' => [],
                        'bahar_harup' => [],
                    ];

                    for ($i = 0; $i <= 9; $i++) {
                        $play_type['ander_harup'][] = [
                            'number' => $i,
                            'entered_amount' => '',
                            'category_id' => $subCategory->category_id,
                            'Playing_Name' => $subCategory->name,

                        ];
                        $play_type['bahar_harup'][] = [
                            'number' => $i,
                            'entered_amount' => '',
                            'category_id' => $subCategory->category_id,
                            'Playing_Name' => $subCategory->name,
                        ];
                    }
                } elseif (in_array($play_id, $group3)) {
                    $play_type = [
                        'entered_amount_1' => '',
                        'entered_amount_2' => '',
                        'entered_amount' => '',
                        'category_id' => $subCategory->category_id,
                        'Playing_Name' => $subCategory->name,
                    ];
                } elseif (in_array($play_id, $group4)) {
                    $play_type = [];
                    for ($i = 1; $i <= 100; $i++) {
                        $number = str_pad($i, 2, '0', STR_PAD_LEFT);
                        $play_type[] = [
                            'number' => $number,
                            'entered_amount' => '',
                            'category_id' => $subCategory->category_id,
                            'Playing_Name' => $subCategory->name,
                        ];
                    }
                    $play_type[] = [
                        'number' => '00',
                        'entered_amount' => '',
                        'category_id' => $subCategory->category_id,
                        'Playing_Name' => $subCategory->name,
                    ];
                }
                return response()->json([
                    'status' => 200,
                    'message' => 'Request processed successfully.',
                    'data' => ['play_type' => $play_type],
                ]);
            } else {
                return response()->json(['status' => 400, 'message' => 'Invalid play_id or play_name.']);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.']);
        }
    }

 public function crossing_number(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'entered_number_1' => 'required|string|min:0|max:9',
            'entered_number_2' => 'required|string|min:0|max:9',
            'entered_amount' => 'required|integer|min:0|max:99999',
            'delete_index' => 'nullable|integer|min:1', // Optional parameter for deletion
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 403, 'errors' => $validator->errors()]);
        }

        $validated = $validator->validate();
        $entered_number_1 = $validated['entered_number_1'];
        $entered_number_2 = $validated['entered_number_2'];
        $entered_amount = $validated['entered_amount'];
        $delete_index = $validated['delete_index'] ?? null;

        $crossing_numbers = [];

        for ($i = 0; $i < strlen($entered_number_1); $i++) {
            $digit_1 = $entered_number_1[$i];

            for ($j = 0; $j < strlen($entered_number_2); $j++) {
                $digit_2 = $entered_number_2[$j];
                $crossing_numbers[] = $digit_1 . $digit_2;
            }
        }

        // If delete_index is provided, remove the corresponding crossing number
        if ($delete_index !== null && isset($crossing_numbers[$delete_index - 1])) {
            unset($crossing_numbers[$delete_index - 1]);
            $crossing_numbers = array_values($crossing_numbers); // Re-index array
        }

        // Add index and amount to crossing numbers
        $indexed_crossing_numbers = [];
        foreach ($crossing_numbers as $index => $number) {
            $indexed_crossing_numbers[] = [
                'id' => $index + 1,
                'number' => $number,
                'amount' => $entered_amount
            ];
        }

        $total_amount = count($crossing_numbers) * $entered_amount;
        $response_data = [
            'crossing_numbers' => $indexed_crossing_numbers,
            'total_amount' => $total_amount,
        ];

        return response()->json(['status' => 200, 'message' => 'Request processed successfully.', 'data' => $response_data]);
    } catch (\Throwable $th) {
        return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.']);
    }
}



}
