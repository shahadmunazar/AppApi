<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function subcategory()
    {
        try {
            $current_time = Carbon::now('Asia/Kolkata');

            // Fetch subcategories with their categories
            $subcategories = SubCategory::with('category')->get();
            $groupedSubcategories = $subcategories->groupBy('category_id')->map(function ($group) use ($current_time) {
                $category = $group->first()->category;
                $category_open_time = Carbon::parse($category->open_time, 'Asia/Kolkata');
                $category_close_time = Carbon::parse($category->close_time, 'Asia/Kolkata');
                $is_category_open = $current_time->between($category_open_time, $category_close_time);
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'is_open' => $is_category_open,
                    'subcategories' => $group,
                ];
            })->values();
            return response()->json(['status' => 200, 'data' => $groupedSubcategories]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.'], 500);
        }
    }

    public function play_game(Request $request)
    {
        try {
            $category_id = $request->category_id;
            $play_id = $request->play_id;
            $play_name = $request->play_name;
            $subcategories_id  = SubCategory::where('category_id', $category_id)->get();
            foreach ($subcategories_id as $key => $category_id) {
                $category_id_all = $category_id->id;
            $subCategory = SubCategory::find($play_id);
            if ($subCategory && $subCategory->name === $play_name) {
                    $group1 = [$category_id_all];
                    $group2 = [$category_id_all];
                    $group3 = [$category_id_all];
                    $group4 = [$category_id_all];
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
            }

        } catch (\Throwable $th) {
            dd($th);
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
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()]);
            }
            $validated = $validator->validate();
            $entered_number_1 = $validated['entered_number_1'];
            $entered_number_2 = $validated['entered_number_2'];
            $entered_amount = $validated['entered_amount'];

            $crossing_numbers = [];

            for ($i = 0; $i < strlen($entered_number_1); $i++) {
                $digit_1 = $entered_number_1[$i];

                for ($j = 0; $j < strlen($entered_number_2); $j++) {
                    $digit_2 = $entered_number_2[$j];
                    $crossing_numbers[] = $digit_1 . $digit_2;
                }
            }
            $total_amount = count($crossing_numbers) * $entered_amount;
            $response_data = [
                'crossing_numbers' => $crossing_numbers,
                'total_amount' => $total_amount,
            ];
            return response()->json(['status' => 200, 'message' => 'Request processed successfully.', 'data' => $response_data]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.']);
        }
    }

}
