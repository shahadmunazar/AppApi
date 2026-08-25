<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\ContentModel;

use App\Models\PlayGame;
use App\Models\TodayResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    
     public function Content_Game()
    {
        try {
            $contentList = ContentModel::all();
            return response()->json([
                'status' => true,
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

    public function Content_Game_post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content_name' => 'required|string|max:255',
            'serial_number' => 'required|integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $newContent = ContentModel::create([
                'content_name' => $request->content_name,
                'serial_number' => $request->serial_number,
                'status' => $request->status ?? false,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Content created successfully',
                'data' => $newContent
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Content_Game_details($content_id)
    {
        try {
            $content = ContentModel::find($content_id);

            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Content details retrieved successfully',
                'data' => $content
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve content details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Content_Game_update(Request $request, $content_id)
    {
        $validator = Validator::make($request->all(), [
            'content_name' => 'string|max:255',
            'serial_number' => 'integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors occurred',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $content = ContentModel::find($content_id);

            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            $content->update($request->only(['content_name', 'serial_number', 'status']));

            return response()->json([
                'status' => true,
                'message' => 'Content updated successfully',
                'data' => $content
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Content_Game_delete($content_id)
    {
        try {
            $content = ContentModel::find($content_id);

            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            $content->delete();

            return response()->json([
                'status' => true,
                'message' => 'Content deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function get_All_Category()
    {
        try {
            $category_all = Category::select('id', 'name', 'open_time', 'last_time', 'no_open','status','active')->get();
            return response()->json(['status' => 200, 'data' => $category_all, 'message' => 'All Data Has Been Retrieved']);
        } catch (\Throwable $th) {
        }
    }

    public function add_category(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'open_time' => 'required|date_format:H:i',
                'last_time' => 'required|date_format:H:i',
                'no_open' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => '403', 'errors' => $validator->errors()], 403);
            }

            $validated = $validator->validated();
            $name = $validated['name'];
            $open_time = $validated['open_time'];
            $last_time = $validated['last_time'];
            $no_open = $validated['no_open'];

            $data = Category::create([
                'name' => $name,
                'open_time' => $open_time,
                'last_time' => $last_time,
                'no_open' => $no_open
            ]);

            return response()->json(['status' => 'success', 'data' => $data, 'message' => 'New Category Added Successfully'], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function GetCategory_Details(Request $request, $category_id)
    {
        try {
            $category_details  = Category::where('id', $category_id)->select('id', 'name', 'open_time', 'last_time', 'no_open')->first();
            return response()->json(['status' => 200, 'data' => $category_details, 'message' => 'Category Details Retrieved Succefully']);
        } catch (\Throwable $th) {
        }
    }


public function active_subcategory(Request $request, $category_id)
    {
        // Define the validation rules
        $validator = Validator::make($request->all(), [
            'active' => 'required|boolean',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        }

        // Find the category by ID
        $category = Category::find($category_id);

        // Check if the category exists
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 403);
        }

        // Update the active status
        $category->active = $request->input('active');
        $category->save();

        return response()->json(['status',200,'message' => 'Category status updated successfully']);
    }
    public function update_category(Request $request, $category_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'open_time' => 'required|date_format:H:i',
                'last_time' => 'required|date_format:H:i',
                'no_open' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 400);
            }
            $validated = $validator->validated();
            $category = Category::where('id', $category_id)->first();
            if (!$category) {
                return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
            }
            $category->update([
                'name' => $validated['name'],
                'open_time' => $validated['open_time'],
                'last_time' => $validated['last_time'],
                'no_open' => $validated['no_open']
            ]);
            return response()->json(['status' => 'success', 'data' => $category, 'message' => 'Category updated successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function delete_category(Request $request, $category_id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($category_id);
            $category->delete();
            return response()->json(['status' => 'success', 'message' => 'Category deleted successfully'], 200);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    //get subcategory
    public function get_subcategory()
    {
        try {
            $subCategories = SubCategory::all();
            return response()->json(['status' => 200, 'data' => $subCategories, 'message' => 'All Sub Categories Retrieved Successfully']);
        } catch (\Throwable $th) {
            dd($th);
            //throw $th;
        }
    }
    public function add_subcategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|integer',
                'name' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 403, 'error', 'errors' => $validator->errors()], 403);
            }
            $validated = $validator->validate();
            $category_id = $validated['category_id'];
            $name  = $validated['name'];

            $data = SubCategory::create([
                'category_id' => $category_id,
                'name' => $name
            ]);
            return response()->json(['status' => 200, 'data' => $data, 'message' => 'Sub Category Added Successfully']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function GetSubCategory_Details(Request $request, $category_id)
    {
        try {
            $subCategories_details = SubCategory::where("category_id", $category_id)
                ->get(["name", "id", "category_id"]);
            return response()->json(['status' => 200, 'data' => $subCategories_details]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    public function update_subcategory(Request $request, $category_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|integer',
                'name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 403, 'errors' => $validator->errors()], 403);
            }

            $validated = $validator->validate();
            $category_id = $validated['category_id'];
            $name = $validated['name'];

            // Find the SubCategory by ID
            $subcategory = SubCategory::find($category_id);

            if (!$subcategory) {
                return response()->json(['status' => 404, 'message' => 'SubCategory not found'], 404);
            }
            $subcategory->category_id = $category_id;
            $subcategory->name = $name;
            $subcategory->save();

            return response()->json(['status' => 200, 'data' => $subcategory, 'message' => 'SubCategory updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }
    public function delete_subcategory(Request $request, $category_id)
    {
        try {
            $subcategory = SubCategory::withTrashed()->findOrFail($category_id);
            $subcategory->delete();
            return response()->json(['status' => 'success', 'message' => 'Sub Category deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }
    
public function AllPlayed_Game(Request $request)
{
    try {
        $play_game_status = $request->input('plan_game'); // Retrieve the plan_game status filter
        $page = $request->input('page', 1); // Default to page 1 if no page is provided
        $limit = 100; // Number of records per page
        $offset = ($page - 1) * $limit; // Calculate the offset based on the page

        // If the status is 'all', fetch all games
        if ($play_game_status == 'all') {
            $total_records = PlayGame::count(); // Get total number of records
            $play_games = PlayGame::orderBy('id', 'desc') // Order by ID descending
                ->skip($offset) // Apply pagination offset
                ->take($limit) // Limit the number of records per page
                ->get();

            // Fetch category name for each play game
            foreach ($play_games as $game) {
                $category = Category::where('id', $game->category_id)->select('name')->first(); // Fetch the category name
                $game->category_name = $category ? $category->name : null; // Add category name to the game object
            }
        } else {
            // If a status is provided, filter by the given status
            $total_records = PlayGame::where('status', $play_game_status)->count(); // Get total records with the specific status
            $play_games = PlayGame::where('status', $play_game_status)
                ->orderBy('id', 'desc') // Order by ID descending
                ->skip($offset) // Apply pagination offset
                ->take($limit) // Limit the number of records per page
                ->get();

            // Fetch category name for each play game
            foreach ($play_games as $game) {
                $category = Category::where('id', $game->category_id)->select('name')->first(); // Fetch the category name
                $game->category_name = $category ? $category->name : null; // Add category name to the game object
            }
        }

        // Calculate the total number of pages
        $total_pages = ceil($total_records / $limit);

        // Return the response in JSON format
        return response()->json([
            'status' => 200,
            'data' => $play_games,
            'current_page' => $page,
            'total_fetched' => $play_games->count(),
            'total_records' => $total_records,
            'total_pages' => $total_pages,
        ]);
    } catch (\Throwable $th) {
        // Catch and return any errors
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while fetching the data.',
        ], 500);
    }
}





public function result_today(Request $request)
{
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
            'no_open' => 'required|string',
            'open_time' => 'nullable|string',
            'status' => 'required|in:opened,not_opened',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'errors' => $validator->errors(),
            ], 403);
        }

        $validated = $validator->validated();
        $id = $validated['id'];
        $no_open = $validated['no_open'];
        $open_time  = $validated['open_time'];
        $status = $validated['status'];

        // Find the category by id
        $category = Category::find($id);

        // Check if the category is not opened
        if ($category->status !== 'opened') {
            return response()->json([
                'status' => 403,
                'message' => 'Category is not opened, so it cannot be updated.',
            ], 403);
        }

        // Update the category's status and no_open
        // $category->status = $status;
        $category->no_open = $no_open;
        $category->save();

        // Store or update today's result for this category
        TodayResult::updateOrCreate(
            [
                'category_id' => $id,
                'category_name' => $category->name,
                'open_time' => $open_time,
                'created_at' => businessDate(), // Ensure it's today's result
            ],
            [
                'open_number' => $no_open,
                
                'open_time' => $open_time
            ]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Status updated and result recorded successfully.',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred. Please try again later.',
        ], 500);
    }
}


public function AllPlaysGame() {
    try {
        // Fetch all results ordered by 'id' in descending order
        $today_results = TodayResult::orderBy('id', 'desc')->get();
        
        // Return a JSON response with the results
        return response()->json([
            'status' => 200,
            'data' => $today_results,
            'message' => 'All results have been received'
        ], 200);

    } catch (\Exception $e) {
        // Log the error for debugging purposes

        // Return a JSON response indicating an error occurred
        return response()->json([
            'error' => 'An error occurred'
        ], 500);
    }
}

public function AllPlaysGame_details($no_id) {
    try {
        // Fetch the specific play game record by ID
        $play_game = TodayResult::find($no_id);
        
        // Check if the record exists
        if (!$play_game) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Return the record details
        return response()->json([
            'status' => 200,
            'data' => $play_game,
            'message' => 'Play game details retrieved successfully'
        ], 200);

    } catch (\Exception $e) {
        // Log the error
        Log::error('Error fetching play game details: ' . $e->getMessage());

        // Return an error response
        return response()->json(['error' => 'An error occurred'], 500);
    }
}


public function AllPlaysGameUpdate(Request $request) {
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'open_time' => 'nullable|string',
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Extract validated data
        $validated = $validator->validated();
        $play_game = TodayResult::find($validated['id']);

        // Check if the record exists
        if (!$play_game) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Update the record with new data
        $play_game->open_time = $validated['open_time'];
        // Update other fields as needed
        $play_game->save();

        // Return a success response
        return response()->json([
            'status' => 200,
            'message' => 'Play game record updated successfully'
        ], 200);

    } catch (\Exception $e) {
        // Log the error
        Log::error('Error updating play game record: ' . $e->getMessage());

        // Return an error response
        return response()->json(['error' => 'An error occurred'], 500);
    }
}


public function AllPlaysGameDelete(Request $request) {
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Extract validated data
        $validated = $validator->validated();
        $play_game = TodayResult::find($validated['id']);

        // Check if the record exists
        if (!$play_game) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Delete the record
        $play_game->delete();

        // Return a success response
        return response()->json([
            'status' => 200,
            'message' => 'Play game record deleted successfully'
        ], 200);

    } catch (\Exception $e) {
        // Log the error
        Log::error('Error deleting play game record: ' . $e->getMessage());

        // Return an error response
        return response()->json(['error' => 'An error occurred'], 500);
    }
}





    public function update_status(Request $request)
{
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
            'status' => 'required|in:opened,not_opened',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 403,
                'errors' => $validator->errors(),
            ]);
        }

        // Retrieve validated data
        $validated = $validator->validated();
        $id = $validated['id'];
        $status = $validated['status'];

        // Find the category by id
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found',
            ], 404);
        }

        // Check if the request is to set the status to 'opened'
        if ($status === 'opened') {
            // Check if any other category is currently 'opened'
            $openedCategory = Category::where('status', 'opened')
                ->where('id', '!=', $id)
                ->first();

            if ($openedCategory) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Another category is already opened. Please close it first.',
                    'opened_category' => [
                        'id' => $openedCategory->id,
                        'name' => $openedCategory->name,
                    ],
                ], 403);
            }
        }

        // Update the category status
        $category->status = $status;
        $category->save();

        return response()->json([
            'status' => 200,
            'message' => 'Status updated successfully',
        ]);

    } catch (\Throwable $th) {
        // Log the exception for debugging if needed

        return response()->json([
            'status' => 500,
            'message' => 'An error occurred. Please try again later.',
        ], 500);
    }
}



}
