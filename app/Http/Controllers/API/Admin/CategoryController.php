<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function get_All_Category()
    {
        try {
            $category_all = Category::select('id', 'name', 'open_time', 'last_time', 'no_open')->get();
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
}
